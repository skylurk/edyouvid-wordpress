<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GLD_Api_Users {

	public function register_routes(): void {
		// List users in group + search site users.
		register_rest_route( 'gl-dashboard/v1', '/groups/(?P<id>\d+)/users', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_users' ),
				'permission_callback' => array( 'GLD_Access', 'rest_permission' ),
				'args'                => array(
					'id'     => array( 'validate_callback' => function( $v ) { return is_numeric( $v ); } ),
					'search' => array( 'type' => 'string', 'default' => '' ),
				),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_user' ),
				'permission_callback' => array( 'GLD_Access', 'rest_permission' ),
				'args'                => array(
					'id'      => array( 'validate_callback' => function( $v ) { return is_numeric( $v ); } ),
					'user_id' => array( 'required' => true, 'validate_callback' => function( $v ) { return is_numeric( $v ); } ),
				),
			),
		) );

		// Remove a specific user from a group.
		register_rest_route( 'gl-dashboard/v1', '/groups/(?P<id>\d+)/users/(?P<user_id>\d+)', array(
			'methods'             => WP_REST_Server::DELETABLE,
			'callback'            => array( $this, 'remove_user' ),
			'permission_callback' => array( 'GLD_Access', 'rest_permission' ),
			'args'                => array(
				'id'      => array( 'validate_callback' => function( $v ) { return is_numeric( $v ); } ),
				'user_id' => array( 'validate_callback' => function( $v ) { return is_numeric( $v ); } ),
			),
		) );

		// Search site users (for the add-user autocomplete).
		register_rest_route( 'gl-dashboard/v1', '/users/search', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'search_users' ),
			'permission_callback' => array( 'GLD_Access', 'rest_permission' ),
			'args'                => array(
				'q' => array( 'type' => 'string', 'default' => '' ),
			),
		) );
	}

	public function get_users( WP_REST_Request $request ): WP_REST_Response {
		$group_id = (int) $request['id'];

		if ( ! GLD_Access::leads_group( $group_id ) ) {
			return new WP_REST_Response( array( 'message' => 'Forbidden' ), 403 );
		}

		$user_ids = learndash_get_groups_user_ids( $group_id );
		$users    = array();

		foreach ( $user_ids as $user_id ) {
			$user = get_userdata( $user_id );
			if ( ! $user ) {
				continue;
			}
			$users[] = $this->format_user( $user );
		}

		return new WP_REST_Response( $users, 200 );
	}

	public function add_user( WP_REST_Request $request ): WP_REST_Response {
		$group_id = (int) $request['id'];
		$user_id  = (int) $request['user_id'];

		if ( ! GLD_Access::leads_group( $group_id ) ) {
			return new WP_REST_Response( array( 'message' => 'Forbidden' ), 403 );
		}

		if ( ! get_userdata( $user_id ) ) {
			return new WP_REST_Response( array( 'message' => 'User not found' ), 404 );
		}

		ld_update_group_access( $user_id, $group_id, false );

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	public function remove_user( WP_REST_Request $request ): WP_REST_Response {
		$group_id = (int) $request['id'];
		$user_id  = (int) $request['user_id'];

		if ( ! GLD_Access::leads_group( $group_id ) ) {
			return new WP_REST_Response( array( 'message' => 'Forbidden' ), 403 );
		}

		ld_update_group_access( $user_id, $group_id, true );

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	public function search_users( WP_REST_Request $request ): WP_REST_Response {
		$query = sanitize_text_field( $request['q'] );

		if ( strlen( $query ) < 2 ) {
			return new WP_REST_Response( array(), 200 );
		}

		$users = get_users( array(
			'search'         => '*' . $query . '*',
			'search_columns' => array( 'user_login', 'user_email', 'display_name' ),
			'number'         => 10,
			'fields'         => 'all',
		) );

		$results = array();
		foreach ( $users as $user ) {
			$results[] = $this->format_user( $user );
		}

		return new WP_REST_Response( $results, 200 );
	}

	private function format_user( WP_User $user ): array {
		return array(
			'id'     => $user->ID,
			'name'   => $user->display_name,
			'email'  => $user->user_email,
			'avatar' => get_avatar_url( $user->ID, array( 'size' => 40 ) ),
		);
	}
}
