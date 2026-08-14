<?php
/**
 * The plugin bootstrap file
 *
 * @link              https://github.com/lotfim
 * @since             1.0.0
 * @package           Sillage
 *
 * @wordpress-plugin
 * Plugin Name:       Sillage
 * Plugin URI:        https://github.com/lotfim/sillage
 * Description:       GDPR-friendly visit log for WordPress — track which logged-in users viewed what, when, and from where — with filters, autocomplete, and PDF/CSV/Excel export.
 * Version:           1.0.0
 * Requires at least: 6.8
 * Requires PHP:      8.3
 * Author:            Lotfi MANSEUR
 * Author URI:        https://github.com/lotfim/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       sillage
 * Domain Path:       /languages
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'SILLAGE_VERSION', '1.0.0' );
define( 'SILLAGE_PLUGIN_FILE', __FILE__ );
define( 'SILLAGE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SILLAGE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

$sillage_autoload = SILLAGE_PLUGIN_DIR . 'vendor/autoload.php';
if ( file_exists( $sillage_autoload ) ) {
	require_once $sillage_autoload;
}

/**
 * Plugin activation callback.
 *
 * @since 1.0.0
 * @return void
 */
function sillage_activate() {
	require_once SILLAGE_PLUGIN_DIR . 'includes/class-sillage-activator.php';
	Sillage_Activator::activate();
}

/**
 * Plugin deactivation callback.
 *
 * @since 1.0.0
 * @return void
 */
function sillage_deactivate() {
	require_once SILLAGE_PLUGIN_DIR . 'includes/class-sillage-deactivator.php';
	Sillage_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'sillage_activate' );
register_deactivation_hook( __FILE__, 'sillage_deactivate' );

require SILLAGE_PLUGIN_DIR . 'includes/class-sillage.php';

/**
 * Begins execution of the plugin.
 *
 * @since 1.0.0
 * @return void
 */
function sillage_run() {
	$plugin = new Sillage();
	$plugin->run();
}
sillage_run();
