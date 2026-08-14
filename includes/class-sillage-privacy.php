<?php
/**
 * WordPress personal data exporter and eraser.
 *
 * @package    Sillage
 * @subpackage Sillage/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integrates Sillage logs with Tools → Export / Erase Personal Data.
 *
 * @since 1.0.0
 */
class Sillage_Privacy {

	/**
	 * Register the exporter.
	 *
	 * @since 1.0.0
	 * @param array<string, array<string, mixed>> $exporters Exporters.
	 * @return array<string, array<string, mixed>>
	 */
	public static function register_exporter( array $exporters ): array {
		$exporters['sillage-logs'] = array(
			'exporter_friendly_name' => __( 'Sillage visit log', 'sillage' ),
			'callback'               => array( self::class, 'export' ),
		);

		return $exporters;
	}

	/**
	 * Register the eraser.
	 *
	 * @since 1.0.0
	 * @param array<string, array<string, mixed>> $erasers Erasers.
	 * @return array<string, array<string, mixed>>
	 */
	public static function register_eraser( array $erasers ): array {
		$erasers['sillage-logs'] = array(
			'eraser_friendly_name' => __( 'Sillage visit log', 'sillage' ),
			'callback'             => array( self::class, 'erase' ),
		);

		return $erasers;
	}

	/**
	 * Export log rows for an email address.
	 *
	 * @since 1.0.0
	 * @param string $email_address Email.
	 * @param int    $page          Page (1-based).
	 * @return array{data: array<int, array<string, mixed>>, done: bool}
	 */
	public static function export( string $email_address, int $page = 1 ): array {
		global $wpdb;

		$page   = max( 1, $page );
		$limit  = 100;
		$offset = ( $page - 1 ) * $limit;
		$table  = Sillage_Database::table();
		$user   = get_user_by( 'email', $email_address );

		if ( $user ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE user_id = %d OR user_email = %s ORDER BY id ASC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is internal.
					$user->ID,
					$email_address,
					$limit,
					$offset
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE user_email = %s ORDER BY id ASC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is internal.
					$email_address,
					$limit,
					$offset
				)
			);
		}

		$data = array();

		foreach ( (array) $rows as $row ) {
			$data[] = array(
				'group_id'    => 'sillage-logs',
				'group_label' => __( 'Sillage visit log', 'sillage' ),
				'item_id'     => 'sillage-log-' . (int) $row->id,
				'data'        => array(
					array(
						'name'  => __( 'User', 'sillage' ),
						'value' => $row->user_nicename,
					),
					array(
						'name'  => __( 'Email', 'sillage' ),
						'value' => $row->user_email,
					),
					array(
						'name'  => __( 'IP address', 'sillage' ),
						'value' => $row->ip_address,
					),
					array(
						'name'  => __( 'Content', 'sillage' ),
						'value' => $row->object_title,
					),
					array(
						'name'  => __( 'Type', 'sillage' ),
						'value' => $row->object_type,
					),
					array(
						'name'  => __( 'Entry date', 'sillage' ),
						'value' => $row->entry_date,
					),
					array(
						'name'  => __( 'Exit date', 'sillage' ),
						'value' => $row->exit_date ? $row->exit_date : '',
					),
				),
			);
		}

		return array(
			'data' => $data,
			'done' => count( (array) $rows ) < $limit,
		);
	}

	/**
	 * Erase log rows for an email address.
	 *
	 * @since 1.0.0
	 * @param string $email_address Email.
	 * @param int    $page          Page (1-based).
	 * @return array{items_removed: bool, items_retained: bool, messages: array<int, string>, done: bool}
	 */
	public static function erase( string $email_address, int $page = 1 ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- WP privacy eraser signature; deletion is batched, not offset-paged.
		unset( $page );
		global $wpdb;

		$table = Sillage_Database::table();
		$user  = get_user_by( 'email', $email_address );

		if ( $user ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table} WHERE user_id = %d OR user_email = %s LIMIT 500", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is internal.
					$user->ID,
					$email_address
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table} WHERE user_email = %s LIMIT 500", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is internal.
					$email_address
				)
			);
		}

		$deleted = (int) $deleted;

		return array(
			'items_removed'  => $deleted > 0,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => $deleted < 500,
		);
	}
}
