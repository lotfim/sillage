<?php
/**
 * Fired during plugin deactivation.
 *
 * @package    Sillage
 * @subpackage Sillage/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deactivation tasks. Data is intentionally kept.
 *
 * @since 1.0.0
 */
class Sillage_Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * Unschedules cron. Does not drop tables or delete options.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function deactivate(): void {
		require_once plugin_dir_path( __FILE__ ) . 'class-sillage-cron.php';

		Sillage_Cron::unschedule();
	}
}
