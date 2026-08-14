<?php
/**
 * Front-office visit recording.
 *
 * @package    Sillage
 * @subpackage Sillage/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Records an entry row on qualifying singular views and exposes the
 * session token for the exit beacon.
 *
 * @since 1.0.0
 */
class Sillage_Tracker {

	/**
	 * Session token for the current request, if a visit was logged.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private static string $session_token = '';

	/**
	 * Record a visit when this front-office request qualifies.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function maybe_log(): void {
		if ( ! self::should_log() ) {
			return;
		}

		$post = get_queried_object();

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$user = wp_get_current_user();

		if ( $user->ID <= 0 ) {
			return;
		}

		$token = self::generate_token();
		$ip    = self::visitor_ip();

		if ( Sillage_Settings::ip_anonymization_enabled() ) {
			$ip = self::anonymize_ip( $ip );
		}

		global $wpdb;

		$table = Sillage_Database::table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- custom log table insert.
		$inserted = $wpdb->insert(
			$table,
			array(
				'user_id'       => (int) $user->ID,
				'user_nicename' => substr( (string) $user->user_nicename, 0, 60 ),
				'user_email'    => substr( (string) $user->user_email, 0, 100 ),
				'ip_address'    => substr( $ip, 0, 45 ),
				'object_id'     => (int) $post->ID,
				'object_title'  => substr( wp_strip_all_tags( get_the_title( $post ) ), 0, 255 ),
				'object_type'   => substr( (string) $post->post_type, 0, 40 ),
				'entry_date'    => current_time( 'mysql' ),
				'session_token' => $token,
			),
			array( '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
		);

		if ( false !== $inserted ) {
			self::$session_token = $token;
		}
	}

	/**
	 * Session token for the current logged visit, or empty.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function session_token(): string {
		return self::$session_token;
	}

	/**
	 * Close a visit by session token.
	 *
	 * @since 1.0.0
	 * @param string $token Session token.
	 * @return bool True if a row was updated.
	 */
	public static function close_session( string $token ): bool {
		if ( ! self::is_valid_token( $token ) ) {
			return false;
		}

		global $wpdb;

		$table = Sillage_Database::table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- exit beacon update.
		$updated = $wpdb->update(
			$table,
			array(
				'exit_date' => current_time( 'mysql' ),
			),
			array(
				'session_token' => $token,
				'user_id'       => get_current_user_id(),
			),
			array( '%s' ),
			array( '%s', '%d' )
		);

		return false !== $updated && (int) $updated > 0;
	}

	/**
	 * Whether a token looks like a 64-char hex string.
	 *
	 * @since 1.0.0
	 * @param string $token Raw token.
	 * @return bool
	 */
	public static function is_valid_token( string $token ): bool {
		return (bool) preg_match( '/^[a-f0-9]{64}$/', $token );
	}

	/**
	 * Whether this request should produce a log row.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private static function should_log(): bool {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return false;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}

		if ( is_feed() || is_robots() || is_trackback() || is_preview() || is_customize_preview() ) {
			return false;
		}

		if ( ! is_user_logged_in() || ! is_singular() ) {
			return false;
		}

		if ( self::is_prefetch() || self::is_bot() ) {
			return false;
		}

		$post = get_queried_object();

		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		$pto = get_post_type_object( $post->post_type );

		if ( ! $pto || ! $pto->public ) {
			return false;
		}

		/**
		 * Filter whether Sillage should log this request.
		 *
		 * @since 1.0.0
		 * @param bool    $should_log Default decision.
		 * @param WP_Post $post       Queried post.
		 */
		return (bool) apply_filters( 'sillage_should_log', true, $post );
	}

	/**
	 * Detect prefetch / prerender (Speculation Rules, Chrome).
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private static function is_prefetch(): bool {
		$purpose = '';

		if ( isset( $_SERVER['HTTP_SEC_PURPOSE'] ) ) {
			$purpose = strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_SEC_PURPOSE'] ) ) );
		} elseif ( isset( $_SERVER['HTTP_PURPOSE'] ) ) {
			$purpose = strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_PURPOSE'] ) ) );
		}

		if ( '' === $purpose ) {
			return false;
		}

		return str_contains( $purpose, 'prefetch' ) || str_contains( $purpose, 'prerender' );
	}

	/**
	 * Best-effort bot / crawler detection from the user agent.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private static function is_bot(): bool {
		if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return false;
		}

		$ua = strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) );

		$needles = array(
			'bot',
			'spider',
			'crawler',
			'slurp',
			'facebookexternalhit',
			'whatsapp',
			'telegram',
			'preview',
			'gptbot',
			'claudebot',
			'bytespider',
			'applebot',
			'semrush',
			'ahrefs',
			'mj12bot',
			'dotbot',
		);

		foreach ( $needles as $needle ) {
			if ( str_contains( $ua, $needle ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Visitor IP. Defaults to REMOTE_ADDR; filter for reverse proxies.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private static function visitor_ip(): string {
		$ip = '';

		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		/**
		 * Filter the visitor IP before storage / anonymization.
		 *
		 * @since 1.0.0
		 * @param string $ip Detected IP.
		 */
		$ip = (string) apply_filters( 'sillage_visitor_ip', $ip );

		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}

		return '0.0.0.0';
	}

	/**
	 * Mask the last octet of IPv4 or keep an IPv6 /48.
	 *
	 * @since 1.0.0
	 * @param string $ip IP address.
	 * @return string
	 */
	public static function anonymize_ip( string $ip ): string {
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			return (string) preg_replace( '/\.\d+$/', '.0', $ip );
		}

		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$packed = inet_pton( $ip );

			if ( false === $packed || 16 !== strlen( $packed ) ) {
				return $ip;
			}

			// Keep /48 (first 6 bytes), zero the rest.
			$packed = substr( $packed, 0, 6 ) . str_repeat( "\0", 10 );
			$anon   = inet_ntop( $packed );

			return false !== $anon ? $anon : $ip;
		}

		return $ip;
	}

	/**
	 * Generate a 64-char hex session token.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private static function generate_token(): string {
		try {
			$bytes = random_bytes( 32 );
		} catch ( Exception $e ) {
			$bytes = wp_generate_password( 32, true, true );
		}

		return hash( 'sha256', $bytes . microtime( true ) . wp_rand() );
	}
}
