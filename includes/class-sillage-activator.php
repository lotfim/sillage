<?php
/**
 * Fired during plugin activation.
 *
 * @package    Sillage
 * @subpackage Sillage/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activation tasks: schema, defaults, cron.
 *
 * @since 1.0.0
 */
class Sillage_Activator {

	/**
	 * Activate the plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function activate(): void {
		require_once plugin_dir_path( __FILE__ ) . 'class-sillage-database.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-sillage-settings.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-sillage-cron.php';

		Sillage_Database::migrate();
		Sillage_Settings::maybe_seed_defaults();
		Sillage_Cron::schedule();
	}
}
