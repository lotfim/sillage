<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Sillage
 * @subpackage Sillage/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin menu, screens, assets, and export action.
 *
 * @since 1.0.0
 */
class Sillage_Admin {

	/**
	 * Plugin slug.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $version;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name Plugin slug.
	 * @param string $version     Version.
	 */
	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the admin menu.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_menu(): void {
		add_menu_page(
			__( 'Sillage', 'sillage' ),
			__( 'Sillage', 'sillage' ),
			'manage_options',
			'sillage',
			array( $this, 'render_logs_page' ),
			'dashicons-visibility',
			30
		);

		add_submenu_page(
			'sillage',
			__( 'Visit log', 'sillage' ),
			__( 'Visit log', 'sillage' ),
			'manage_options',
			'sillage',
			array( $this, 'render_logs_page' )
		);

		add_submenu_page(
			'sillage',
			__( 'Settings', 'sillage' ),
			__( 'Settings', 'sillage' ),
			'manage_options',
			'sillage-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'sillage_settings_group',
			Sillage_Settings::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => Sillage_Settings::defaults(),
			)
		);
	}

	/**
	 * Sanitize settings from the options form.
	 *
	 * @since 1.0.0
	 * @param mixed $input Raw input.
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( $input ): array {
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		return Sillage_Settings::sanitize( $input );
	}

	/**
	 * Enqueue admin assets on Sillage screens only.
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		$screens = array(
			'toplevel_page_sillage',
			'sillage_page_sillage-settings',
		);

		if ( ! in_array( $hook, $screens, true ) ) {
			return;
		}

		$css_rel = 'admin/css/sillage-admin.css';
		$js_rel  = 'admin/js/sillage-admin.js';
		$css     = SILLAGE_PLUGIN_DIR . $css_rel;
		$js      = SILLAGE_PLUGIN_DIR . $js_rel;

		if ( file_exists( $css ) ) {
			wp_enqueue_style(
				'sillage-admin',
				SILLAGE_PLUGIN_URL . $css_rel,
				array(),
				(string) filemtime( $css )
			);
		}

		if ( 'toplevel_page_sillage' !== $hook || ! file_exists( $js ) ) {
			return;
		}

		wp_enqueue_script(
			'sillage-admin',
			SILLAGE_PLUGIN_URL . $js_rel,
			array( 'jquery' ),
			(string) filemtime( $js ),
			true
		);

		wp_localize_script(
			'sillage-admin',
			'sillageAdmin',
			array(
				'restUrl'     => esc_url_raw( rest_url( Sillage_Rest::NAMESPACE . '/' ) ),
				'restNonce'   => wp_create_nonce( 'wp_rest' ),
				'exportUrl'   => esc_url_raw( admin_url( 'admin-post.php' ) ),
				'exportNonce' => wp_create_nonce( 'sillage_export' ),
				'datePicker'  => self::date_picker_config(),
				'i18n'        => array(
					'inProgress'         => __( 'In progress', 'sillage' ),
					'placeholderUser'    => __( 'Filter by user…', 'sillage' ),
					'placeholderContent' => __( 'Filter by content…', 'sillage' ),
					'processing'         => __( 'Loading…', 'sillage' ),
					'zeroRecords'        => __( 'No matching visits.', 'sillage' ),
					'emptyTable'         => __( 'No visits recorded yet. Open a published page or post while logged in on the front of the site.', 'sillage' ),
					'viewContent'        => __( 'View content', 'sillage' ),
					'lengthMenu'         => __( 'Show _MENU_ entries', 'sillage' ),
					'info'               => __( 'Showing _START_ to _END_ of _TOTAL_ visits', 'sillage' ),
					'infoEmpty'          => __( 'Showing 0 to 0 of 0 visits', 'sillage' ),
					'infoFiltered'       => __( '(filtered from _MAX_ total visits)', 'sillage' ),
					'paginateFirst'      => __( 'First', 'sillage' ),
					'paginateLast'       => __( 'Last', 'sillage' ),
					'paginateNext'       => __( 'Next', 'sillage' ),
					'paginatePrev'       => __( 'Previous', 'sillage' ),
					'searching'          => __( 'Searching…', 'sillage' ),
					'noResults'          => __( 'No results found', 'sillage' ),
					'errorLoading'       => __( 'The results could not be loaded.', 'sillage' ),
					'loadingMore'        => __( 'Loading more results…', 'sillage' ),
					'removeAllItems'     => __( 'Remove all items', 'sillage' ),
					/* translators: %d: number of characters still required. */
					'inputTooShort'      => __( 'Please enter %d or more characters.', 'sillage' ),
				),
			)
		);
	}

	/**
	 * Visit log screen.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_logs_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'sillage' ) );
		}

		include SILLAGE_PLUGIN_DIR . 'admin/views/logs.php';
	}

	/**
	 * Settings screen.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'sillage' ) );
		}

		$settings = Sillage_Settings::all();

		include SILLAGE_PLUGIN_DIR . 'admin/views/settings.php';
	}

	/**
	 * Handle admin-post export.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_export(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export logs.', 'sillage' ) );
		}

		check_admin_referer( 'sillage_export' );

		$format_raw = isset( $_GET['format'] ) ? sanitize_key( wp_unslash( $_GET['format'] ) ) : 'csv';
		$format     = Sillage_Export_Format::tryFrom( $format_raw );

		if ( ! $format ) {
			wp_die( esc_html__( 'Invalid export format.', 'sillage' ) );
		}

		$filters = Sillage_Query::filters_from_request( wp_unslash( $_GET ) );

		Sillage_Exporter::stream( $format, $filters );
	}

	/**
	 * Flatpickr options derived from the current user locale.
	 *
	 * Values stay ISO (Y-m-d) in the hidden field; altFormat drives display.
	 *
	 * @since 1.0.0
	 * @return array{locale:string,altFormat:string,placeholder:string}
	 */
	private static function date_picker_config(): array {
		$locale     = get_user_locale();
		$normalized = strtolower( str_replace( '-', '_', $locale ) );
		$lang       = strtok( $normalized, '_' );

		if ( ! is_string( $lang ) || '' === $lang ) {
			$lang = 'en';
		}

		// US English → m/d/Y; French → d/m/Y with jj/mm/aaaa hint; everyone else → d/m/Y.
		if ( 'en_us' === $normalized ) {
			$alt_format  = 'm/d/Y';
			$placeholder = 'mm/dd/yyyy';
		} elseif ( 'fr' === $lang ) {
			$alt_format  = 'd/m/Y';
			$placeholder = 'jj/mm/aaaa';
		} else {
			$alt_format  = 'd/m/Y';
			$placeholder = 'dd/mm/yyyy';
		}

		return array(
			'locale'      => $lang,
			'altFormat'   => $alt_format,
			'placeholder' => $placeholder,
		);
	}
}
