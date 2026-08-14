<?php
/**
 * Plugin settings defaults and accessors.
 *
 * @package    Sillage
 * @subpackage Sillage/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Centralized Sillage settings stored in a single option.
 *
 * @since 1.0.0
 */
class Sillage_Settings {

	/**
	 * Option name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public const OPTION_KEY = 'sillage_settings';

	/**
	 * Default settings.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'ip_anonymization' => false,
			'retention_days'   => 90,
			'geoip_base_url'   => 'https://ipinfo.io/',
			'pdf_company_name' => '',
			'pdf_show_filters' => false,
		);
	}

	/**
	 * Seed defaults on first install without overwriting unknown keys.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function maybe_seed_defaults(): void {
		$existing = get_option( self::OPTION_KEY, null );

		if ( null !== $existing && is_array( $existing ) ) {
			update_option( self::OPTION_KEY, self::merge_with_defaults( $existing ) );
			return;
		}

		update_option( self::OPTION_KEY, self::defaults() );
	}

	/**
	 * Merge stored settings with defaults.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $stored Stored option.
	 * @return array<string, mixed>
	 */
	public static function merge_with_defaults( array $stored ): array {
		return array_merge( self::defaults(), $stored );
	}

	/**
	 * Get all settings.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		$stored = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return self::merge_with_defaults( $stored );
	}

	/**
	 * Get a single setting.
	 *
	 * @since 1.0.0
	 * @param string $key     Setting key.
	 * @param mixed  $fallback Fallback.
	 * @return mixed
	 */
	public static function get( string $key, $fallback = null ) {
		$all = self::all();

		return $all[ $key ] ?? $fallback;
	}

	/**
	 * Whether IP anonymization is enabled.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public static function ip_anonymization_enabled(): bool {
		return (bool) self::get( 'ip_anonymization', false );
	}

	/**
	 * Retention period in days. 0 means never auto-purge.
	 *
	 * @since 1.0.0
	 * @return int
	 */
	public static function retention_days(): int {
		return max( 0, (int) self::get( 'retention_days', 90 ) );
	}

	/**
	 * Base URL for IP geolocation lookup links.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function geoip_base_url(): string {
		$url = (string) self::get( 'geoip_base_url', 'https://ipinfo.io/' );

		return trailingslashit( esc_url_raw( $url ) );
	}

	/**
	 * Company name for the PDF title. Empty keeps the default heading.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function pdf_company_name(): string {
		return (string) self::get( 'pdf_company_name', '' );
	}

	/**
	 * Whether the PDF should list the active filters.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public static function pdf_show_filters(): bool {
		return (bool) self::get( 'pdf_show_filters', false );
	}

	/**
	 * Sanitize and persist settings from the admin form.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $input Raw input.
	 * @return array<string, mixed>
	 */
	public static function sanitize( array $input ): array {
		$current = self::all();

		$current['ip_anonymization'] = ! empty( $input['ip_anonymization'] );
		$current['retention_days']   = isset( $input['retention_days'] ) ? max( 0, absint( $input['retention_days'] ) ) : 90;

		$geo                       = isset( $input['geoip_base_url'] ) ? esc_url_raw( (string) $input['geoip_base_url'] ) : '';
		$current['geoip_base_url'] = '' !== $geo ? trailingslashit( $geo ) : 'https://ipinfo.io/';

		$company                     = isset( $input['pdf_company_name'] ) ? sanitize_text_field( (string) $input['pdf_company_name'] ) : '';
		$current['pdf_company_name'] = substr( $company, 0, 191 );
		$current['pdf_show_filters'] = ! empty( $input['pdf_show_filters'] );

		return $current;
	}
}
