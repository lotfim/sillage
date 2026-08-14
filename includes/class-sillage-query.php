<?php
/**
 * Shared log query builder.
 *
 * @package    Sillage
 * @subpackage Sillage/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds filtered SELECT/COUNT queries for the admin list and exports.
 *
 * @since 1.0.0
 */
class Sillage_Query {

	/**
	 * Allowed ORDER BY columns.
	 *
	 * @since 1.0.0
	 * @var array<string>
	 */
	private const ORDERABLE = array(
		'user_nicename',
		'user_email',
		'ip_address',
		'object_title',
		'object_type',
		'entry_date',
		'exit_date',
	);

	/**
	 * Sanitize filters from a request-like array.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $params Raw params.
	 * @return array<string, mixed>
	 */
	public static function filters_from_request( array $params ): array {
		$filters = array(
			'user_id'   => 0,
			'object_id' => 0,
			'date_from' => '',
			'date_to'   => '',
			'search'    => '',
		);

		if ( isset( $params['user_id'] ) ) {
			$filters['user_id'] = absint( $params['user_id'] );
		}

		if ( isset( $params['object_id'] ) ) {
			$filters['object_id'] = absint( $params['object_id'] );
		}

		if ( isset( $params['date_from'] ) && self::is_ymd( (string) $params['date_from'] ) ) {
			$filters['date_from'] = (string) $params['date_from'];
		}

		if ( isset( $params['date_to'] ) && self::is_ymd( (string) $params['date_to'] ) ) {
			$filters['date_to'] = (string) $params['date_to'];
		}

		if ( isset( $params['search'] ) ) {
			$filters['search'] = sanitize_text_field( (string) $params['search'] );
		}

		return $filters;
	}

	/**
	 * Count rows matching filters.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $filters Sanitized filters.
	 * @return int
	 */
	public static function count( array $filters ): int {
		global $wpdb;

		$table = Sillage_Database::table();
		$built = self::where_clause( $filters );

		$sql = "SELECT COUNT(*) FROM {$table} {$built['sql']}";

		if ( array() !== $built['args'] ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$built['args'] ) );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Fetch a page of rows.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $filters Sanitized filters.
	 * @param int                  $offset  Offset.
	 * @param int                  $limit   Limit.
	 * @param string               $orderby Column.
	 * @param string               $order   ASC or DESC.
	 * @return array<int, object>
	 */
	public static function get_rows( array $filters, int $offset, int $limit, string $orderby = 'entry_date', string $order = 'DESC' ): array {
		global $wpdb;

		$table   = Sillage_Database::table();
		$built   = self::where_clause( $filters );
		$orderby = in_array( $orderby, self::ORDERABLE, true ) ? $orderby : 'entry_date';
		$order   = 'ASC' === strtoupper( $order ) ? 'ASC' : 'DESC';
		$offset  = max( 0, $offset );
		$limit   = min( 500, max( 1, $limit ) );

		$sql = "SELECT * FROM {$table} {$built['sql']} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		$args   = $built['args'];
		$args[] = $limit;
		$args[] = $offset;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$args ) );

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Build WHERE SQL and placeholder args.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $filters Sanitized filters.
	 * @return array{sql: string, args: array<int, mixed>}
	 */
	private static function where_clause( array $filters ): array {
		global $wpdb;

		$where = array( '1=1' );
		$args  = array();

		if ( ! empty( $filters['user_id'] ) ) {
			$where[] = 'user_id = %d';
			$args[]  = (int) $filters['user_id'];
		}

		if ( ! empty( $filters['object_id'] ) ) {
			$where[] = 'object_id = %d';
			$args[]  = (int) $filters['object_id'];
		}

		if ( ! empty( $filters['date_from'] ) ) {
			$where[] = 'entry_date >= %s';
			$args[]  = $filters['date_from'] . ' 00:00:00';
		}

		if ( ! empty( $filters['date_to'] ) ) {
			$where[] = 'entry_date <= %s';
			$args[]  = $filters['date_to'] . ' 23:59:59';
		}

		if ( ! empty( $filters['search'] ) && strlen( (string) $filters['search'] ) >= 2 ) {
			$like    = '%' . $wpdb->esc_like( (string) $filters['search'] ) . '%';
			$where[] = '( user_nicename LIKE %s OR user_email LIKE %s OR object_title LIKE %s OR ip_address LIKE %s )';
			$args[]  = $like;
			$args[]  = $like;
			$args[]  = $like;
			$args[]  = $like;
		}

		return array(
			'sql'  => 'WHERE ' . implode( ' AND ', $where ),
			'args' => $args,
		);
	}

	/**
	 * Whether a string is YYYY-MM-DD.
	 *
	 * @since 1.0.0
	 * @param string $value Raw date.
	 * @return bool
	 */
	private static function is_ymd( string $value ): bool {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
			return false;
		}

		$dt = DateTime::createFromFormat( 'Y-m-d', $value );

		return $dt instanceof DateTime && $dt->format( 'Y-m-d' ) === $value;
	}
}
