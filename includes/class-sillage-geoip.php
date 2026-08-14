<?php
/**
 * IP geolocation lookup URL helper.
 *
 * @package    Sillage
 * @subpackage Sillage/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the external IP lookup link used in the admin table.
 *
 * @since 1.0.0
 */
class Sillage_Geoip {

	/**
	 * Build a lookup URL for an IP address.
	 *
	 * @since 1.0.0
	 * @param string $ip IPv4 or IPv6 address (possibly anonymized).
	 * @return string
	 */
	public static function lookup_url( string $ip ): string {
		$base = Sillage_Settings::geoip_base_url();
		$ip   = rawurlencode( $ip );

		return $base . $ip;
	}
}
