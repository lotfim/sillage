<?php
/**
 * The core plugin class.
 *
 * @package    Sillage
 * @subpackage Sillage/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads dependencies and registers hooks.
 *
 * @since 1.0.0
 */
class Sillage {

	/**
	 * Hook loader.
	 *
	 * @since 1.0.0
	 * @var Sillage_Loader
	 */
	protected Sillage_Loader $loader;

	/**
	 * Plugin slug.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected string $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected string $version;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->version     = defined( 'SILLAGE_VERSION' ) ? SILLAGE_VERSION : '1.0.0';
		$this->plugin_name = 'sillage';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load required files.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function load_dependencies(): void {
		require_once SILLAGE_PLUGIN_DIR . 'includes/class-sillage-loader.php';
		require_once SILLAGE_PLUGIN_DIR . 'includes/class-sillage-i18n.php';
		require_once SILLAGE_PLUGIN_DIR . 'includes/class-sillage-database.php';
		require_once SILLAGE_PLUGIN_DIR . 'includes/class-sillage-settings.php';
		require_once SILLAGE_PLUGIN_DIR . 'includes/class-sillage-tracker.php';
		require_once SILLAGE_PLUGIN_DIR . 'includes/class-sillage-query.php';
		require_once SILLAGE_PLUGIN_DIR . 'includes/class-sillage-rest.php';
		require_once SILLAGE_PLUGIN_DIR . 'includes/class-sillage-cron.php';
		require_once SILLAGE_PLUGIN_DIR . 'includes/class-sillage-export-format.php';
		require_once SILLAGE_PLUGIN_DIR . 'includes/class-sillage-exporter.php';
		require_once SILLAGE_PLUGIN_DIR . 'includes/class-sillage-geoip.php';
		require_once SILLAGE_PLUGIN_DIR . 'includes/class-sillage-privacy.php';
		require_once SILLAGE_PLUGIN_DIR . 'admin/class-sillage-admin.php';
		require_once SILLAGE_PLUGIN_DIR . 'public/class-sillage-public.php';

		$this->loader = new Sillage_Loader();
	}

	/**
	 * Set up translations.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function set_locale(): void {
		$plugin_i18n = new Sillage_I18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Admin hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function define_admin_hooks(): void {
		$plugin_admin = new Sillage_Admin( $this->plugin_name, $this->version );
		$plugin_rest  = new Sillage_Rest();

		$this->loader->add_action( 'plugins_loaded', 'Sillage_Database', 'maybe_upgrade' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'register_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_assets' );
		$this->loader->add_action( 'admin_post_sillage_export', $plugin_admin, 'handle_export' );
		$this->loader->add_action( 'rest_api_init', $plugin_rest, 'register_routes' );
		$this->loader->add_action( Sillage_Cron::HOOK, 'Sillage_Cron', 'purge' );
		$this->loader->add_action( 'init', 'Sillage_Cron', 'schedule' );

		$this->loader->add_filter( 'wp_privacy_personal_data_exporters', 'Sillage_Privacy', 'register_exporter' );
		$this->loader->add_filter( 'wp_privacy_personal_data_erasers', 'Sillage_Privacy', 'register_eraser' );
	}

	/**
	 * Public hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function define_public_hooks(): void {
		$plugin_public = new Sillage_Public( $this->plugin_name, $this->version );

		$this->loader->add_action( 'template_redirect', 'Sillage_Tracker', 'maybe_log' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
	}

	/**
	 * Register hooks with WordPress.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function run(): void {
		$this->loader->run();
	}

	/**
	 * Plugin slug.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_plugin_name(): string {
		return $this->plugin_name;
	}

	/**
	 * Loader.
	 *
	 * @since 1.0.0
	 * @return Sillage_Loader
	 */
	public function get_loader(): Sillage_Loader {
		return $this->loader;
	}

	/**
	 * Version.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_version(): string {
		return $this->version;
	}
}
