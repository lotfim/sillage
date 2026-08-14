<?php
/**
 * Analytics aggregations over sillage_logs.
 *
 * @package    Sillage
 * @subpackage Sillage/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the dashboard payload (KPIs, series, top lists).
 *
 * @since 1.0.0
 */
class Sillage_Stats {

	/**
	 * Maximum inclusive day span for aggregations.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public const MAX_DAYS = 366;

	/**
	 * Default inclusive window (today and 29 days before).
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public const DEFAULT_DAYS = 30;

	/**
	 * Top list size.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public const TOP_LIMIT = 10;

	/**
	 * Default from/to (Y-m-d) in the site timezone.
	 *
	 * @since 1.0.0
	 * @return array{from: string, to: string}
	 */
	public static function default_range(): array {
		$now  = new DateTimeImmutable( 'now', wp_timezone() );
		$from = $now->modify( '-' . ( self::DEFAULT_DAYS - 1 ) . ' days' );

		return array(
			'from' => $from->format( 'Y-m-d' ),
			'to'   => $now->format( 'Y-m-d' ),
		);
	}

	/**
	 * Clamp filters to a bounded date range and return the dashboard payload.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $filters Sanitized filters from Sillage_Query.
	 * @return array<string, mixed>
	 */
	public static function build( array $filters ): array {
		$range        = self::bound_range( $filters );
		$filters      = $range['filters'];
		$granularity  = $range['granularity'];
		$kpis         = self::kpis( $filters );
		$series_rows  = self::series_rows( $filters, $granularity );
		$top_contents = self::top_contents( $filters );
		$top_users    = self::top_users( $filters );
		$by_type      = self::by_type( $filters );

		return array(
			'kpis'         => $kpis,
			'series'       => array(
				'granularity' => $granularity,
				'buckets'     => self::fill_buckets( $series_rows, $range['from'], $range['to'], $granularity ),
			),
			'top_contents' => $top_contents,
			'top_users'    => $top_users,
			'by_type'      => $by_type,
			'range'        => array(
				'from'        => $range['from'],
				'to'          => $range['to'],
				'granularity' => $granularity,
			),
		);
	}

	/**
	 * Apply defaults and the 366-day cap.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $filters Sanitized filters.
	 * @return array{from: string, to: string, granularity: string, filters: array<string, mixed>}
	 */
	public static function bound_range( array $filters ): array {
		$tz      = wp_timezone();
		$default = self::default_range();
		$from    = ! empty( $filters['date_from'] ) ? (string) $filters['date_from'] : $default['from'];
		$to      = ! empty( $filters['date_to'] ) ? (string) $filters['date_to'] : $default['to'];

		$from_dt = DateTimeImmutable::createFromFormat( 'Y-m-d', $from, $tz );
		$to_dt   = DateTimeImmutable::createFromFormat( 'Y-m-d', $to, $tz );

		if ( ! $from_dt instanceof DateTimeImmutable ) {
			$from_dt = DateTimeImmutable::createFromFormat( 'Y-m-d', $default['from'], $tz );
		}

		if ( ! $to_dt instanceof DateTimeImmutable ) {
			$to_dt = DateTimeImmutable::createFromFormat( 'Y-m-d', $default['to'], $tz );
		}

		if ( $from_dt > $to_dt ) {
			$tmp     = $from_dt;
			$from_dt = $to_dt;
			$to_dt   = $tmp;
		}

		$span_days = (int) $from_dt->diff( $to_dt )->days + 1;

		if ( $span_days > self::MAX_DAYS ) {
			$from_dt = $to_dt->modify( '-' . ( self::MAX_DAYS - 1 ) . ' days' );
		}

		$from = $from_dt->format( 'Y-m-d' );
		$to   = $to_dt->format( 'Y-m-d' );

		$filters['date_from'] = $from;
		$filters['date_to']   = $to;
		$filters['search']    = '';

		$from_start  = $from_dt->setTime( 0, 0, 0 );
		$to_end      = $to_dt->setTime( 23, 59, 59 );
		$hours       = ( $to_end->getTimestamp() - $from_start->getTimestamp() ) / HOUR_IN_SECONDS;
		$granularity = $hours <= 48 ? 'hour' : 'day';

		return array(
			'from'        => $from,
			'to'          => $to,
			'granularity' => $granularity,
			'filters'     => $filters,
		);
	}

	/**
	 * KPI row.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $filters Bounded filters.
	 * @return array{visits: int, unique_users: int, unique_contents: int, avg_duration_seconds: int|null}
	 */
	private static function kpis( array $filters ): array {
		$table = Sillage_Database::table();
		$built = Sillage_Query::where_clause( $filters );
		$sql   = "SELECT COUNT(*) AS visits, COUNT(DISTINCT user_id) AS unique_users, COUNT(DISTINCT object_id) AS unique_contents, AVG(CASE WHEN exit_date IS NOT NULL AND exit_date > entry_date THEN TIMESTAMPDIFF(SECOND, entry_date, exit_date) END) AS avg_duration_seconds FROM {$table} {$built['sql']}";

		$row = self::get_row( $sql, $built['args'] );

		$avg = null;
		if ( $row && null !== $row->avg_duration_seconds && '' !== $row->avg_duration_seconds ) {
			$avg = (int) round( (float) $row->avg_duration_seconds );
		}

		return array(
			'visits'               => $row ? (int) $row->visits : 0,
			'unique_users'         => $row ? (int) $row->unique_users : 0,
			'unique_contents'      => $row ? (int) $row->unique_contents : 0,
			'avg_duration_seconds' => $avg,
		);
	}

	/**
	 * Raw series buckets from SQL.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $filters      Bounded filters.
	 * @param string               $granularity  hour|day.
	 * @return array<int, object>
	 */
	private static function series_rows( array $filters, string $granularity ): array {
		$table = Sillage_Database::table();
		$built = Sillage_Query::where_clause( $filters );

		if ( 'hour' === $granularity ) {
			$expr = empty( $built['args'] )
				? "DATE_FORMAT(entry_date, '%Y-%m-%d %H:00:00')"
				: "DATE_FORMAT(entry_date, '%%Y-%%m-%%d %%H:00:00')";
		} else {
			$expr = 'DATE(entry_date)';
		}

		$sql = "SELECT {$expr} AS bucket, COUNT(*) AS visits, COUNT(DISTINCT user_id) AS unique_users FROM {$table} {$built['sql']} GROUP BY {$expr} ORDER BY bucket ASC";

		return self::get_results( $sql, $built['args'] );
	}

	/**
	 * Top contents.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $filters Bounded filters.
	 * @return array<int, array<string, mixed>>
	 */
	private static function top_contents( array $filters ): array {
		$table = Sillage_Database::table();
		$built = Sillage_Query::where_clause( $filters );
		$sql   = "SELECT object_id, MAX(object_title) AS object_title, MAX(object_type) AS object_type, COUNT(*) AS visits FROM {$table} {$built['sql']} GROUP BY object_id ORDER BY visits DESC, object_title ASC LIMIT %d";

		$args   = $built['args'];
		$args[] = self::TOP_LIMIT;
		$rows   = self::get_results( $sql, $args );
		$output = array();

		foreach ( $rows as $row ) {
			$type_obj   = get_post_type_object( (string) $row->object_type );
			$label      = $type_obj ? $type_obj->labels->singular_name : (string) $row->object_type;
			$permalink  = get_permalink( (int) $row->object_id );
			$object_url = $permalink ? (string) $permalink : '';

			$output[] = array(
				'object_id'         => (int) $row->object_id,
				'object_title'      => (string) $row->object_title,
				'object_type'       => (string) $row->object_type,
				'object_type_label' => $label,
				'object_url'        => $object_url,
				'visits'            => (int) $row->visits,
			);
		}

		return $output;
	}

	/**
	 * Top users.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $filters Bounded filters.
	 * @return array<int, array<string, mixed>>
	 */
	private static function top_users( array $filters ): array {
		$table = Sillage_Database::table();
		$built = Sillage_Query::where_clause( $filters );
		$sql   = "SELECT user_id, MAX(user_nicename) AS user_nicename, MAX(user_email) AS user_email, COUNT(*) AS visits FROM {$table} {$built['sql']} GROUP BY user_id ORDER BY visits DESC, user_nicename ASC LIMIT %d";

		$args   = $built['args'];
		$args[] = self::TOP_LIMIT;
		$rows   = self::get_results( $sql, $args );
		$output = array();

		foreach ( $rows as $row ) {
			$output[] = array(
				'user_id'       => (int) $row->user_id,
				'user_nicename' => (string) $row->user_nicename,
				'user_email'    => (string) $row->user_email,
				'visits'        => (int) $row->visits,
			);
		}

		return $output;
	}

	/**
	 * Visits by post type.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $filters Bounded filters.
	 * @return array<int, array<string, mixed>>
	 */
	private static function by_type( array $filters ): array {
		$table = Sillage_Database::table();
		$built = Sillage_Query::where_clause( $filters );
		$sql   = "SELECT object_type, COUNT(*) AS visits FROM {$table} {$built['sql']} GROUP BY object_type ORDER BY visits DESC, object_type ASC";

		$rows   = self::get_results( $sql, $built['args'] );
		$output = array();

		foreach ( $rows as $row ) {
			$type_obj = get_post_type_object( (string) $row->object_type );
			$label    = $type_obj ? $type_obj->labels->singular_name : (string) $row->object_type;

			$output[] = array(
				'object_type' => (string) $row->object_type,
				'label'       => $label,
				'visits'      => (int) $row->visits,
			);
		}

		return $output;
	}

	/**
	 * Insert zero buckets so the chart axis is continuous.
	 *
	 * @since 1.0.0
	 * @param array<int, object> $rows        SQL rows.
	 * @param string             $from        Y-m-d.
	 * @param string             $to          Y-m-d.
	 * @param string             $granularity hour|day.
	 * @return array<int, array{bucket: string, visits: int, unique_users: int}>
	 */
	private static function fill_buckets( array $rows, string $from, string $to, string $granularity ): array {
		$map = array();

		foreach ( $rows as $row ) {
			$key         = (string) $row->bucket;
			$map[ $key ] = array(
				'bucket'       => $key,
				'visits'       => (int) $row->visits,
				'unique_users' => (int) $row->unique_users,
			);
		}

		$tz     = wp_timezone();
		$cursor = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $from . ' 00:00:00', $tz );
		$end    = DateTimeImmutable::createFromFormat(
			'Y-m-d H:i:s',
			'hour' === $granularity ? $to . ' 23:00:00' : $to . ' 00:00:00',
			$tz
		);

		if ( ! $cursor instanceof DateTimeImmutable || ! $end instanceof DateTimeImmutable ) {
			return array_values( $map );
		}

		$modify = 'hour' === $granularity ? '+1 hour' : '+1 day';
		$format = 'hour' === $granularity ? 'Y-m-d H:00:00' : 'Y-m-d';
		$out    = array();

		while ( $cursor <= $end ) {
			$key    = $cursor->format( $format );
			$out[]  = $map[ $key ] ?? array(
				'bucket'       => $key,
				'visits'       => 0,
				'unique_users' => 0,
			);
			$cursor = $cursor->modify( $modify );
		}

		return $out;
	}

	/**
	 * Prepared get_row helper.
	 *
	 * @since 1.0.0
	 * @param string            $sql  SQL with optional placeholders.
	 * @param array<int, mixed> $args Prepare args.
	 * @return object|null
	 */
	private static function get_row( string $sql, array $args ) {
		global $wpdb;

		if ( array() !== $args ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$row = $wpdb->get_row( $wpdb->prepare( $sql, ...$args ) );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$row = $wpdb->get_row( $sql );
		}

		return $row ?: null;
	}

	/**
	 * Prepared get_results helper.
	 *
	 * @since 1.0.0
	 * @param string            $sql  SQL with optional placeholders.
	 * @param array<int, mixed> $args Prepare args.
	 * @return array<int, object>
	 */
	private static function get_results( string $sql, array $args ): array {
		global $wpdb;

		if ( array() !== $args ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$args ) );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$rows = $wpdb->get_results( $sql );
		}

		return is_array( $rows ) ? $rows : array();
	}
}
