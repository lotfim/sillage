<?php
/**
 * Database schema and migrations.
 *
 * @package    Sillage
 * @subpackage Sillage/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the custom logs table and versioned migrations.
 *
 * @since 1.0.0
 */
class Sillage_Database {

	/**
	 * Current schema version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public const DB_VERSION = '1.0.0';

	/**
	 * Option name storing the installed schema version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public const DB_VERSION_OPTION = 'sillage_db_version';

	/**
	 * Table name without prefix.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public const TABLE = 'sillage_logs';

	/**
	 * Return the full logs table name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function table(): string {
		global $wpdb;

		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Run migrations if the stored version is behind.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function maybe_upgrade(): void {
		$installed = get_option( self::DB_VERSION_OPTION, '' );

		if ( version_compare( (string) $installed, self::DB_VERSION, '<' ) ) {
			self::migrate();
		}
	}

	/**
	 * Create or update custom tables via dbDelta.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function migrate(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table           = self::table();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			user_nicename varchar(60) NOT NULL,
			user_email varchar(100) NOT NULL,
			ip_address varchar(45) NOT NULL,
			object_id bigint(20) unsigned NOT NULL,
			object_title varchar(255) NOT NULL,
			object_type varchar(40) NOT NULL,
			entry_date datetime NOT NULL,
			exit_date datetime DEFAULT NULL,
			session_token char(64) NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY session_token (session_token),
			KEY user_entry (user_id, entry_date),
			KEY object_entry (object_id, entry_date),
			KEY ip_address (ip_address),
			KEY entry_date (entry_date)
		) {$charset_collate};";

		dbDelta( $sql );

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}
}
