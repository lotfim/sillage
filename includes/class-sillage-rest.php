<?php
/**
 * REST API routes.
 *
 * @package    Sillage
 * @subpackage Sillage/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers Sillage REST endpoints.
 *
 * @since 1.0.0
 */
class Sillage_Rest {

	/**
	 * Namespace.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public const NAMESPACE = 'sillage/v1';

	/**
	 * Register routes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/track/exit',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'track_exit' ),
				'permission_callback' => 'is_user_logged_in',
				'args'                => array(
					'session_token' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/logs',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_logs' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/autocomplete/users',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'autocomplete_users' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => array(
					'search' => array(
						'type'              => 'string',
						'required'          => false,
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/autocomplete/pages',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'autocomplete_pages' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => array(
					'search' => array(
						'type'              => 'string',
						'required'          => false,
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/stats',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_stats' ),
				'permission_callback' => array( $this, 'can_manage' ),
			)
		);
	}

	/**
	 * Capability check for admin endpoints.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Close a visit session.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function track_exit( WP_REST_Request $request ): WP_REST_Response {
		if ( $this->is_exit_rate_limited() ) {
			return new WP_REST_Response( array( 'ok' => false ), 429 );
		}

		$token = (string) $request->get_param( 'session_token' );

		if ( '' === $token ) {
			$raw  = $request->get_body();
			$data = json_decode( $raw, true );
			if ( is_array( $data ) && isset( $data['session_token'] ) ) {
				$token = sanitize_text_field( (string) $data['session_token'] );
			}
		}

		$closed = Sillage_Tracker::close_session( $token );

		return new WP_REST_Response( array( 'ok' => $closed ), $closed ? 200 : 404 );
	}

	/**
	 * DataTables server-side log page.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_logs( WP_REST_Request $request ): WP_REST_Response {
		$params  = $request->get_params();
		$filters = Sillage_Query::filters_from_request( $params );

		if ( isset( $params['search'] ) && is_array( $params['search'] ) && isset( $params['search']['value'] ) ) {
			$filters['search'] = sanitize_text_field( (string) $params['search']['value'] );
		}

		$draw   = isset( $params['draw'] ) ? absint( $params['draw'] ) : 1;
		$start  = isset( $params['start'] ) ? absint( $params['start'] ) : 0;
		$length = isset( $params['length'] ) ? absint( $params['length'] ) : 25;
		$length = min( 100, max( 10, $length ) );

		$columns = array( 'user_nicename', 'user_email', 'ip_address', 'object_title', 'entry_date', 'exit_date' );
		$orderby = 'entry_date';
		$order   = 'DESC';

		$col_index = null;
		if ( isset( $params['order_column'] ) ) {
			$col_index = absint( $params['order_column'] );
		} elseif ( isset( $params['order'][0]['column'] ) ) {
			$col_index = absint( $params['order'][0]['column'] );
		}

		if ( null !== $col_index && isset( $columns[ $col_index ] ) ) {
			$orderby = $columns[ $col_index ];
		}

		$order_dir = '';
		if ( isset( $params['order_dir'] ) ) {
			$order_dir = strtolower( (string) $params['order_dir'] );
		} elseif ( isset( $params['order'][0]['dir'] ) ) {
			$order_dir = strtolower( (string) $params['order'][0]['dir'] );
		}

		if ( 'asc' === $order_dir ) {
			$order = 'ASC';
		}

		$total    = Sillage_Query::count( array() );
		$filtered = Sillage_Query::count( $filters );
		$rows     = Sillage_Query::get_rows( $filters, $start, $length, $orderby, $order );

		$data = array();

		foreach ( $rows as $row ) {
			$type_obj   = get_post_type_object( $row->object_type );
			$label      = $type_obj ? $type_obj->labels->singular_name : $row->object_type;
			$permalink  = get_permalink( (int) $row->object_id );
			$object_url = $permalink ? (string) $permalink : '';

			$data[] = array(
				'id'                 => (int) $row->id,
				'user_nicename'      => $row->user_nicename,
				'user_email'         => $row->user_email,
				'ip_address'         => $row->ip_address,
				'ip_lookup_url'      => Sillage_Geoip::lookup_url( $row->ip_address ),
				'object_id'          => (int) $row->object_id,
				'object_title'       => $row->object_title,
				'object_type'        => $row->object_type,
				'object_type_label'  => $label,
				'object_url'         => $object_url,
				'entry_date'         => $row->entry_date,
				'entry_date_display' => $this->format_datetime( $row->entry_date ),
				'exit_date'          => $row->exit_date,
				'exit_date_display'  => $row->exit_date ? $this->format_datetime( $row->exit_date ) : '',
			);
		}

		return new WP_REST_Response(
			array(
				'draw'            => $draw,
				'recordsTotal'    => $total,
				'recordsFiltered' => $filtered,
				'data'            => $data,
			)
		);
	}

	/**
	 * User autocomplete.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function autocomplete_users( WP_REST_Request $request ) {
		$search = trim( (string) $request->get_param( 'search' ) );

		$args = array(
			'number'  => 20,
			'orderby' => 'display_name',
			'order'   => 'ASC',
			'fields'  => 'all',
		);

		if ( strlen( $search ) >= 1 ) {
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = array( 'user_login', 'user_nicename', 'user_email', 'display_name' );
		}

		$query   = new WP_User_Query( $args );
		$results = array();

		foreach ( $query->get_results() as $user ) {
			$results[] = array(
				'id'       => (int) $user->ID,
				'nicename' => $user->user_nicename,
				'email'    => $user->user_email,
				'text'     => $user->user_nicename . ' (' . $user->user_email . ')',
			);
		}

		return new WP_REST_Response( array( 'results' => $results ) );
	}

	/**
	 * Content autocomplete.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function autocomplete_pages( WP_REST_Request $request ) {
		global $wpdb;

		$search     = trim( (string) $request->get_param( 'search' ) );
		$post_types = array_values( get_post_types( array( 'public' => true ), 'names' ) );

		if ( array() === $post_types ) {
			return new WP_REST_Response( array( 'results' => array() ) );
		}

		$placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
		$sql          = "SELECT ID, post_title, post_type FROM {$wpdb->posts} WHERE post_status = %s AND post_type IN ({$placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders are %s only.
		$args         = array_merge( array( 'publish' ), $post_types );

		if ( '' !== $search ) {
			$sql   .= ' AND post_title LIKE %s';
			$args[] = '%' . $wpdb->esc_like( $search ) . '%';
		}

		$sql .= ' ORDER BY post_title ASC LIMIT 20';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- typed placeholders; title-only autocomplete.
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$args ) );

		$results = array();

		foreach ( (array) $rows as $row ) {
			$type  = get_post_type_object( $row->post_type );
			$label = $type ? $type->labels->singular_name : $row->post_type;
			$title = $row->post_title;

			$results[] = array(
				'id'    => (int) $row->ID,
				'title' => $title,
				'type'  => $row->post_type,
				'text'  => $title . ' (' . $label . ')',
			);
		}

		return new WP_REST_Response( array( 'results' => $results ) );
	}

	/**
	 * Dashboard aggregations.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_stats( WP_REST_Request $request ): WP_REST_Response {
		$filters = Sillage_Query::filters_from_request( $request->get_params() );

		return new WP_REST_Response( Sillage_Stats::build( $filters ) );
	}

	/**
	 * Format a MySQL datetime for display.
	 *
	 * @since 1.0.0
	 * @param string $mysql MySQL datetime.
	 * @return string
	 */
	private function format_datetime( string $mysql ): string {
		$ts = mysql2date( 'U', $mysql, false );

		if ( ! $ts ) {
			return $mysql;
		}

		return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $ts );
	}

	/**
	 * Rate-limit exit beacons per IP (30 / minute).
	 *
	 * @since 1.0.0
	 * @return bool True when limited.
	 */
	private function is_exit_rate_limited(): bool {
		$ip = '';

		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		$key   = 'sillage_exit_' . md5( $ip );
		$count = (int) get_transient( $key );

		if ( $count >= 30 ) {
			return true;
		}

		set_transient( $key, $count + 1, MINUTE_IN_SECONDS );

		return false;
	}
}
