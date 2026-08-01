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
		register_rest_route(
			Config::get_rest_api_root_path(),
			'/get_codes_group_detail/(?P<ID>\d+)/',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_codes_group_detail' ),
				'permission_callback' => array( $this, 'set_admin_codes_group_detail_permissions' ),
			)
		);

		register_rest_route(
			Config::get_rest_api_root_path(),
			'/group_batch_has_name/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'group_batch_has_name' ),
				'permission_callback' => array( $this, 'set_admin_codes_group_detail_permissions' ),
			)
		);

		register_rest_route(
			'uncanny-codes/v1',
			'/bulk_update_expiry_date',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'bulk_update_expire_date' ),
				'permission_callback' => array( $this, 'set_admin_codes_group_detail_permissions' ),
			)
		);
	}


	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function group_batch_has_name( WP_REST_Request $request ) {
		// Verify nonce for security
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'Invalid nonce. Request not authenticated.', 'uncanny-learndash-codes' ), array( 'status' => 403 ) );
		}

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

			//          $table = $wpdb->prefix . Config::$tbl_groups;
			//          $sql   = $wpdb->prepare( "SELECT issue_count FROM {$table} WHERE ID=%d", $ID );

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

	/**
	 * Bulk update expiry dates for multiple codes.
	 * Can handle 1 code or hundreds of codes.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, WP_Error on failure.
	 */
	public function bulk_update_expire_date( $request ) {
		// Verify nonce for security
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'Invalid nonce. Request not authenticated.', 'uncanny-learndash-codes' ), array( 'status' => 403 ) );
		}

		global $wpdb;

		$code_ids         = $request->get_param( 'code_ids' );
		$expire_date      = $request->get_param( 'expire_date' );
		$expiry_time      = $request->get_param( 'expiry_time' );
		$expire_date_time = $expire_date . ' ' . $expiry_time;

		// Validate inputs
		if ( empty( $code_ids ) || ! is_array( $code_ids ) ) {
			return new WP_Error( 'missing_code_ids', esc_html__( 'No codes selected for update.', 'uncanny-learndash-codes' ), array( 'status' => 400 ) );
		}

		if ( empty( $expire_date ) ) {
			return new WP_Error( 'missing_expire_date', esc_html__( 'Expiry date is required.', 'uncanny-learndash-codes' ), array( 'status' => 400 ) );
		}

		// Sanitize code IDs
		$code_ids = array_map( 'absint', $code_ids );
		$code_ids = array_filter( $code_ids ); // Remove any zero values

		if ( empty( $code_ids ) ) {
			return new WP_Error( 'invalid_code_ids', esc_html__( 'No valid code IDs provided.', 'uncanny-learndash-codes' ), array( 'status' => 400 ) );
		}

		$table_name    = $wpdb->prefix . Config::$tbl_codes;
		$updated_count = 0;
		$failed_count  = 0;
		$failed_codes  = array();

		// Update codes one by one for better error handling
		foreach ( $code_ids as $code_id ) {
			$result = $wpdb->update(
				$table_name,
				array( 'expire_date' => $expire_date_time ),
				array( 'ID' => $code_id ),
				array( '%s' ),
				array( '%d' )
			);

			if ( $result !== false ) {
				$updated_count++;
			} else {
				$failed_count++;
				$failed_codes[] = $code_id;
			}
		}

		// Prepare response message
		$total_codes = count( $code_ids );

		if ( $updated_count === $total_codes ) {
			// All codes updated successfully
			$message = sprintf(
				/* translators: %d: number of codes updated */
				_n(
					'%d code expiry date updated successfully.',
					'%d codes expiry dates updated successfully.',
					$updated_count,
					'uncanny-learndash-codes'
				),
				$updated_count
			);

			// Track the expiry update
			$count = get_option( 'uncanny_codes_expiry_updates_count', 0 );
			update_option( 'uncanny_codes_expiry_updates_count', $count + 1 );

			// Track weekly expiry updates
			$weekly_count = get_option( 'uncanny_codes_expiry_updates_week', 0 );
			update_option( 'uncanny_codes_expiry_updates_week', $weekly_count + 1 );

			return new WP_REST_Response(
				array(
					'success'       => true,
					'message'       => $message,
					'updated_count' => $updated_count,
					'total_count'   => $total_codes,
				),
				200
			);
		} elseif ( $updated_count > 0 ) {
			// Partial success
			$message = sprintf(
				/* translators: %1$d: updated count, %2$d: total count */
				esc_html__( '%1$d of %2$d codes updated successfully. Some codes could not be updated.', 'uncanny-learndash-codes' ),
				$updated_count,
				$total_codes
			);

			// Track the expiry update
			$count = get_option( 'uncanny_codes_expiry_updates_count', 0 );
			update_option( 'uncanny_codes_expiry_updates_count', $count + 1 );

			// Track weekly expiry updates
			$weekly_count = get_option( 'uncanny_codes_expiry_updates_week', 0 );
			update_option( 'uncanny_codes_expiry_updates_week', $weekly_count + 1 );

			return new WP_REST_Response(
				array(
					'success'       => true,
					'message'       => $message,
					'updated_count' => $updated_count,
					'failed_count'  => $failed_count,
					'total_count'   => $total_codes,
					'failed_codes'  => $failed_codes,
				),
				200
			);
		} else {
			// Complete failure
			return new WP_Error(
				'bulk_update_failed',
				esc_html__( 'Failed to update any codes. Please try again.', 'uncanny-learndash-codes' ),
				array( 'status' => 500 )
			);
		}
	}
}
