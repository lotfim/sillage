<?php
/**
 * Public-facing tracker enqueue.
 *
 * @package    Sillage
 * @subpackage Sillage/public
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues the exit-beacon script when a visit was recorded.
 *
 * @since 1.0.0
 */
class Sillage_Public {

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
	 * Enqueue the exit tracker when a session token exists.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts(): void {
		$token = Sillage_Tracker::session_token();

		if ( '' === $token ) {
			return;
		}

		$rel  = 'public/js/sillage-tracker.js';
		$path = SILLAGE_PLUGIN_DIR . $rel;
		$ver  = file_exists( $path ) ? (string) filemtime( $path ) : $this->version;

		wp_enqueue_script(
			'sillage-tracker',
			SILLAGE_PLUGIN_URL . $rel,
			array(),
			$ver,
			true
		);

		wp_localize_script(
			'sillage-tracker',
			'sillageTracker',
			array(
				'sessionToken' => $token,
				'exitUrl'      => esc_url_raw( rest_url( Sillage_Rest::NAMESPACE . '/track/exit' ) ),
				'restNonce'    => wp_create_nonce( 'wp_rest' ),
			)
		);
	}
}
