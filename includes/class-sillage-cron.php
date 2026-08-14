<?php
/**
 * Retention purge cron.
 *
 * @package    Sillage
 * @subpackage Sillage/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Daily purge of log rows older than the configured retention period.
 *
 * @since 1.0.0
 */
class Sillage_Cron {

	/**
	 * Cron hook name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public const HOOK = 'sillage_purge_old_logs';

	/**
	 * Schedule the daily event if missing.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::HOOK );
		}
	}

	/**
	 * Unschedule the daily event.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function unschedule(): void {
		$timestamp = wp_next_scheduled( self::HOOK );

		if ( false !== $timestamp ) {
			wp_unschedule_event( $timestamp, self::HOOK );
		}

		wp_clear_scheduled_hook( self::HOOK );
	}

	/**
	 * Delete log rows older than the retention period.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function purge(): void {
		$days = Sillage_Settings::retention_days();

		if ( $days <= 0 ) {
			return;
		}

		global $wpdb;

		$table    = Sillage_Database::table();
		$cutoff   = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );
		$batch    = 500;
		$max_loop = 50;

		do {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- retention purge.
			$count = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table} WHERE entry_date < %s LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is internal.
					$cutoff,
					$batch
				)
			);

			--$max_loop;
		} while ( $count === $batch && $max_loop > 0 );
	}
}
