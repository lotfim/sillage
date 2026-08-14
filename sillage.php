<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/lotfim
 * @since             1.0.0
 * @package           Sillage
 *
 * @wordpress-plugin
 * Plugin Name:       Sillage
 * Plugin URI:        https://github.com/lotfim/sillage
 * Description:       GDPR-friendly visit log for WordPress — track who viewed what, when, and from where — with filters, autocomplete, and PDF/CSV/Excel export.
 * Version:           1.0.0
 * Author:            Lotfi MANSEUR
 * Author URI:        https://github.com/lotfim/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       sillage
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'SILLAGE_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-sillage-activator.php
 */
function activate_sillage() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-sillage-activator.php';
	Sillage_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-sillage-deactivator.php
 */
function deactivate_sillage() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-sillage-deactivator.php';
	Sillage_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_sillage' );
register_deactivation_hook( __FILE__, 'deactivate_sillage' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-sillage.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_sillage() {

	$plugin = new Sillage();
	$plugin->run();

}
run_sillage();
