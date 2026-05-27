<?php

namespace uncanny_learndash_codes;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class Shortcodes
 * @package uncanny_learndash_codes
 */
class Rest_Api extends Config {

	/**
	 * Shortcodes constructor.
	 */
	public function __construct() {
		// register api class.
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );

	}

	/**
	 * Rest API Custom Endpoints
	 *
	 * @since 1.0
	 */
	public function register_routes() {

		// ex. https://www.example.com/wp-json/uncanny-codes/v2/get_codes_group_detail/123/.
		register_rest_route( Config::get_rest_api_root_path(), '/get_codes_group_detail/(?P<ID>\d+)/', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_codes_group_detail' ),
			'permission_callback' => array( $this, 'set_admin_codes_group_detail_permissions' ),
		) );

		register_rest_route( Config::get_rest_api_root_path(), '/group_batch_has_name/', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'group_batch_has_name' ),
			'permission_callback' => array( $this, 'set_admin_codes_group_detail_permissions' ),
		) );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function group_batch_has_name( WP_REST_Request $request ) {

		// The rest response object.
		$response               = (object) array();
		$response->is_available = true;

		$batch_name = sanitize_text_field( $request->get_param( 'batch_name' ) );
		// Default return message.
		$response->message = esc_html__( 'There was a WordPress error. Please reload the page and trying again.', 'uncanny-learndash-codes' );
		$response->success = false;
		if ( empty( $batch_name ) ) {
			$response = new WP_REST_Response( $response, 200 );

			return $response;
		}

		global $wpdb;
		$table = $wpdb->prefix . Config::$tbl_groups;
		$sql   = $wpdb->prepare( "SELECT name FROM {$table} WHERE name LIKE %s", $batch_name );

		$codes_group = $wpdb->get_row( $sql );

		if ( empty( $codes_group ) ) {
			$response->message      = esc_html__( 'Batch name available', 'uncanny-learndash-codes' );
			$response->success      = true;
			$response->is_available = true;
		} else {
			$response->message      = esc_html__( 'Batch name already used', 'uncanny-learndash-codes' );
			$response->success      = true;
			$response->is_available = false;
		}

		$response = new WP_REST_Response( $response, 200 );

		return $response;

	}

	/**
	 * @param $data
	 *
	 * @return object|WP_REST_Response
	 */
	public function get_codes_group_detail( WP_REST_Request $request ) {

		// The rest response object.
		$response = (object) array();

		$ID = absint( $request->get_param( 'ID' ) );
		// Default return message.
		$response->message = esc_html__( 'There was a WordPress error. Please reload the page and trying again.', 'uncanny-learndash-codes' );
		$response->success = false;


		if ( $ID ) {

//			$table = $wpdb->prefix . Config::$tbl_groups;
//			$sql   = $wpdb->prepare( "SELECT issue_count FROM {$table} WHERE ID=%d", $ID );

			//$codes_group = $wpdb->get_row( $sql );
			$codes_group = SharedFunctionality::ulc_get_issue_count( $ID );

			global $wpdb;
			$table1 = $wpdb->prefix . Config::$tbl_codes;
			$table2 = $wpdb->prefix . Config::$tbl_codes_usage;
			$sql    = $wpdb->prepare( "SELECT count(ID) FROM {$table1} WHERE code_group=%d AND order_id=%d AND ID NOT IN (SELECT code_id FROM {$table2})", $ID, 0 );

			$codes_available = $wpdb->get_var( $sql );

			if ( ! empty( $codes_group ) ) {
				$response->success      = true;
				$response->total        = $codes_group;
				$response->available    = $codes_available;
				$response->stock_status = ( 0 === absint( $codes_available ) ) ? 'outofstock' : 'instock';
				$response->ID           = $ID;
			} else {
				$response->message = esc_html__( 'Data for the group cannot be found', 'uncanny-learndash-codes' );
			}
		}


		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * This is our callback function that embeds our resource in a WP_REST_Response
	 */
	public function set_admin_codes_group_detail_permissions() {

		$capability = apply_filters( 'set_admin_codes_group_detail_permissions', 'manage_options' );

		// Restrict endpoint to only users who have the edit_posts capability.
		if ( ! current_user_can( $capability ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'You do not have the capability to save module settings.', 'uncanny-learndash-codes' ), array( 'status' => 401 ) );
		}

		// This is a black-listing approach. You could alternatively do this via white-listing, by returning false here and changing the permissions check.
		return true;
	}
}
