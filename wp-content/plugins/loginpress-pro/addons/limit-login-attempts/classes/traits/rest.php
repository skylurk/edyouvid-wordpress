<?php
/**
 * Prevent direct access.
 *
 * @package LoginPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LoginPress Limit Login Rest Trait
 *
 * Handles rest apis of llla.
 *
 * @package   LoginPress
 * @subpackage Traits\LimitLogin
 * @since 6.1.0
 */

if ( ! trait_exists( 'LoginPress_LimitLogin_Rest_Trait' ) ) {
	/**
	 * LoginPress Limit Login Rest Trait.
	 *
	 * Handles all the rest apis.
	 *
	 * @package   LoginPress
	 * @subpackage Traits\LimitLogin
	 * @since 6.1.0
	 */
	trait LoginPress_LimitLogin_Rest_Trait {

		/**
		 * Get limit login settings.
		 *
		 * @since  6.0.0
		 * @return array The limit login settings.
		 */
		public function loginpress_get_limit_login_settings() {
			$settings = get_option( 'loginpress_limit_login_attempts', array() );
			return wp_parse_args( $settings, false );
		}

		/**
		 * Update limit login settings.
		 *
		 * @param WP_REST_Request $request The REST request object.
		 * @since  6.0.0
		 * @return WP_REST_Response|WP_Error The response or error.
		 */
		public function loginpress_update_limit_login_settings( WP_REST_Request $request ) {
			// Get the settings data sent from the frontend.
			$new_settings = $request->get_json_params();

			// Make sure it's an array.
			if ( ! is_array( $new_settings ) ) {
				return new WP_Error( 'invalid_data', 'Invalid settings data.', array( 'status' => 400 ) );
			}

			// Option name where the settings are stored.
			$option_name = 'loginpress_limit_login_attempts';

			// Get the existing settings to preserve any missing keys.
			$existing_settings = get_option( $option_name, array() );

			// Merge new settings into existing ones to preserve untouched settings.
			$updated_settings = array_merge( $existing_settings, $new_settings );

			// Update the option.
			update_option( $option_name, $updated_settings );

			return rest_ensure_response(
				array(
					'success' => true,
					'message' => 'Limit login settings updated successfully.',
				)
			);
		}

		/**
		 * Get all login attempts.
		 *
		 * @param WP_REST_Request $request The REST request object.
		 * @since  6.0.0
		 * @version 6.1.0
		 * @return WP_REST_Response The response.
		 */
		public function loginpress_get_login_attempts( WP_REST_Request $request ) {
			global $wpdb;
			$table_name = $this->llla_table;
			$search     = $request->get_param( 'search' ) ? $request->get_param( 'search' ) : '';

			$search_sql = '';
			$params     = array();
			if ( ! empty( $search ) ) {
				$like       = '%' . $wpdb->esc_like( $search ) . '%';
				$search_sql = ' AND (ip LIKE %s OR username LIKE %s)';
				$params[]   = $like;
				$params[]   = $like;
			}

			$query = "
			SELECT * FROM {$table_name}
			WHERE (whitelist + blacklist) = 0
			{$search_sql}
			ORDER BY datentime DESC
		"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			// Prepare the full query with parameters.
			$prepared_query = $wpdb->prepare( $query, ...$params ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			$results = $wpdb->get_results( $prepared_query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared

			// Format the datetime before returning.
			$formatted_results = array_map(
				function ( $item ) {
					return array(
						'id'           => (int) $item->id,
						'ip'           => sanitize_text_field( $item->ip ),
						'username'     => sanitize_text_field( $item->username ),
						'datentime'    => gmdate( 'm/d/Y H:i:s', (int) $item->datentime ),
						'gateway'      => sanitize_text_field( $item->gateway ),
						'login_status' => $item->login_status,
					// Add other fields if needed.
					);
				},
				$results
			);

			return new WP_REST_Response( $formatted_results );
		}

		/**
		 * Bulk action callback.
		 *
		 * @param WP_REST_Request $request The REST request object.
		 * @since  6.0.0
		 * @return array|WP_Error Success response or error.
		 */
		public function loginpress_bulk_action_login_attempts( WP_REST_Request $request ) {
			global $wpdb;
			$table_name = $this->llla_table;

			$ids    = $request->get_param( 'ids' );
			$action = $request->get_param( 'action' );

			if ( empty( $ids ) || empty( $action ) ) {
				return new WP_Error( 'invalid_params', 'Invalid parameters', array( 'status' => 400 ) );
			}

			$ids              = array_map( 'intval', $ids );
			$ids_placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

			// Step 1: Get unique IPs for the given IDs.
			$ips = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT DISTINCT ip FROM $table_name WHERE id IN ($ids_placeholders)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
					$ids
				)
			);

			if ( empty( $ips ) ) {
				return new WP_Error( 'no_ips_found', 'No IPs found for the provided IDs', array( 'status' => 404 ) );
			}

			// Step 2: Prepare placeholders for IPs.
			$ips_placeholders = implode( ',', array_fill( 0, count( $ips ), '%s' ) );

			// Step 3: Run action based on IPs.
			switch ( $action ) {
				case 'unlock':
					$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"DELETE FROM $table_name WHERE ip IN ($ips_placeholders)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
							$ips
						)
					);
					break;

				case 'whitelist':
					$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"UPDATE $table_name SET whitelist = 1 WHERE ip IN ($ips_placeholders)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
							$ips
						)
					);
					break;

				case 'blacklist':
					$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"UPDATE $table_name SET blacklist = 1 WHERE ip IN ($ips_placeholders)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
							$ips
						)
					);
					break;
			}

			return array( 'success' => true );
		}


		/**
		 * Clear all login attempts.
		 *
		 * @since  6.0.0
		 * @return array Success response.
		 */
		public function loginpress_clear_all_login_attempts() {
			global $wpdb;
			$table_name = $this->llla_table;

			$query = $wpdb->prepare(
				"DELETE FROM {$table_name} WHERE (whitelist + blacklist) = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				0
			);
			$wpdb->query( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared

			return array( 'success' => true );
		}

		/**
		 * Login attempts action (whitelist, blacklist, unlock) callback.
		 *
		 * @param WP_REST_Request $request The REST request object.
		 * @since  6.0.0
		 * @return array|WP_Error Success response or error.
		 */
		public function loginpress_action_login_attempt( WP_REST_Request $request ) {
			global $wpdb;
			$table_name = $this->llla_table;

			$ip     = $request->get_param( 'ip' );
			$action = $request->get_param( 'action' );

			if ( empty( $ip ) || empty( $action ) ) {
				return new WP_Error( 'invalid_params', 'Invalid parameters', array( 'status' => 400 ) );
			}

			switch ( $action ) {
				case 'unlock':
					$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"DELETE FROM {$table_name} WHERE ip = %s AND whitelist = %d AND blacklist = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							$ip,
							0,
							0
						)
					);
					break;

				case 'whitelist':
					$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"UPDATE {$table_name} SET whitelist = %d WHERE ip = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							1,
							$ip
						)
					);
					break;

				case 'blacklist':
					$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"UPDATE {$table_name} SET blacklist = %d WHERE ip = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							1,
							$ip
						)
					);
					break;
			}

			return array( 'success' => true );
		}

		/**
		 * Get all whitelist records.
		 *
		 * @param WP_REST_Request $request The REST request object.
		 * @since  6.0.0
		 * @return array The whitelist records.
		 */
		public function loginpress_get_whitelist( WP_REST_Request $request ) {
			global $wpdb;
			$table_name = $this->llla_table;

			$per_page = absint( $request->get_param( 'per_page' ) ) ? absint( $request->get_param( 'per_page' ) ) : 10;
			$search   = sanitize_text_field( $request->get_param( 'search' ) ) ? sanitize_text_field( $request->get_param( 'search' ) ) : '';

			$query = "SELECT DISTINCT ip FROM $table_name WHERE whitelist = 1"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			if ( ! empty( $search ) ) {
				$query .= $wpdb->prepare( ' AND ip LIKE %s', '%' . $wpdb->esc_like( $search ) . '%' );
			}

			return $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		}

		/**
		 * Remove from whitelist table.
		 *
		 * @param WP_REST_Request $request The REST request object.
		 * @since  6.0.0
		 * @return array|WP_Error Success response or error.
		 */
		public function loginpress_remove_from_whitelist( WP_REST_Request $request ) {
			global $wpdb;
			$table_name = $this->llla_table;

			$ip = $request->get_param( 'ip' );

			if ( empty( $ip ) ) {
				return new WP_Error( 'missing_ip', __( 'Missing IP address.', 'loginpress-pro' ), array( 'status' => 400 ) );
			}

			$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"DELETE FROM {$table_name} WHERE ip = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$ip
				)
			);

			return array( 'success' => true );
		}

		/**
		 * Clear whitelist table.
		 *
		 * @since  6.0.0
		 * @return array Success response.
		 */
		public function loginpress_clear_whitelist() {
			global $wpdb;
			$table_name = $this->llla_table;

			$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE {$table_name} SET whitelist = %d WHERE whitelist = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					0,
					1
				)
			);

			return array( 'success' => true );
		}

		/**
		 * Get all blacklist entries.
		 *
		 * @param WP_REST_Request $request The REST request object.
		 * @since  6.0.0
		 * @return array The blacklist entries.
		 */
		public function loginpress_get_blacklist( WP_REST_Request $request ) {
			global $wpdb;
			$table_name = $this->llla_table;

			$per_page = absint( $request->get_param( 'per_page' ) ) ? absint( $request->get_param( 'per_page' ) ) : 10;
			$search   = sanitize_text_field( $request->get_param( 'search' ) ) ? sanitize_text_field( $request->get_param( 'search' ) ) : '';

			$query = "SELECT DISTINCT ip FROM $table_name WHERE blacklist = 1"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			if ( ! empty( $search ) ) {
				$query .= $wpdb->prepare( ' AND ip LIKE %s', '%' . $wpdb->esc_like( $search ) . '%' );
			}

			return $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		}

		/**
		 * Remove from blacklist table.
		 *
		 * @param WP_REST_Request $request The REST request object.
		 * @since  6.0.0
		 * @return array|WP_Error Success response or error.
		 */
		public function loginpress_remove_from_blacklist( WP_REST_Request $request ) {
			global $wpdb;
			$table_name = $this->llla_table;

			$ip = $request->get_param( 'ip' );

			if ( empty( $ip ) ) {
				return new WP_Error( 'missing_ip', __( 'Missing IP address.', 'loginpress-pro' ), array( 'status' => 400 ) );
			}

			$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"DELETE FROM {$table_name} WHERE ip = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$ip
				)
			);

			return array( 'success' => true );
		}

		/**
		 * Clear blacklist table entries.
		 *
		 * @since  6.0.0
		 * @return array Success response.
		 */
		public function loginpress_clear_blacklist() {
			global $wpdb;
			$table_name = $this->llla_table;

			$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE {$table_name} SET blacklist = %d WHERE blacklist = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					0,
					1
				)
			);

			return array( 'success' => true );
		}

		/**
		 * Add entry to whitelist or blacklist table.
		 *
		 * @param WP_REST_Request $request The REST request object.
		 * @since  6.0.0
		 * @return WP_REST_Response The response.
		 */
		public function loginpress_rest_add_ip( WP_REST_Request $request ) {
			global $wpdb;

			$ip           = $request->get_param( 'ip' );
			$list_type    = $request->get_param( 'list_type' );
			$current_time = time(); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

			if ( empty( $ip ) || ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return new WP_REST_Response(
					array( 'message' => __( 'Invalid or missing IP address.', 'loginpress-pro' ) ),
					400
				);
			}

			$table        = $this->llla_table; // replace with your actual table.
			$exist_record = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE ip = %s LIMIT 1", $ip ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			// Ensure only one record per IP.
			if ( count( $exist_record ) > 0 ) {
				$id_to_keep = $exist_record[0]->id;
				$wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE ip = %s AND id != %d", $ip, $id_to_keep ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}
			$exist_record = array_slice( $exist_record, 0, 1 );

			if ( 'whitelist' === $list_type ) {
				if ( count( $exist_record ) > 0 && '1' !== $exist_record[0]->whitelist ) {
					// phpcs:disable
					$wpdb->update(
						$table,
						array(
							'whitelist' => 1,
							'blacklist' => 0,
							'gateway'   => 'Manually',
						),
						array( 'ip' => $ip )
					);
					// phpcs:enable

					$action  = '1' === $exist_record[0]->blacklist ? 'move_black_to_white' : 'new_whitelist';
					$message = 'move_black_to_white' === $action
						? __( 'IP Address already existed, successfully moved from blacklist to whitelist.', 'loginpress-pro' )
						: __( 'Successfully added IP Address to whitelist.', 'loginpress-pro' );

						return new WP_REST_Response(
							array(
								'message' => $message,
								'action'  => $action,
							)
						);
				}

				if ( count( $exist_record ) === 0 ) {
					$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
						$table,
						array(
							'ip'        => $ip,
							'whitelist' => 1,
							'datentime' => $current_time,
							'gateway'   => 'Manually',
						)
					);
						return new WP_REST_Response(
							array(
								'message' => __( 'Successfully added IP Address to whitelist.', 'loginpress-pro' ),
								'action'  => 'new_whitelist',
							)
						);
				}

				return new WP_REST_Response(
					array(
						'message' => __( 'IP Address already exists in whitelist.', 'loginpress-pro' ),
						'action'  => 'already_whitelist',
					)
				);
			}

			if ( 'blacklist' === $list_type ) {
				if ( count( $exist_record ) > 0 && '1' !== $exist_record[0]->blacklist ) {
					// phpcs:disable
					$wpdb->update(
						$table,
						array(
							'blacklist' => 1,
							'whitelist' => 0,
							'gateway'   => 'Manually',
						),
						array( 'ip' => $ip )
					);
					// phpcs:enable

					$action  = '1' === $exist_record[0]->whitelist ? 'move_white_to_black' : 'new_blacklist';
					$message = 'move_white_to_black' === $action
						? __( 'IP Address already existed, successfully moved from whitelist to blacklist.', 'loginpress-pro' )
						: __( 'Successfully added IP Address to blacklist.', 'loginpress-pro' );

						return new WP_REST_Response(
							array(
								'message' => $message,
								'action'  => $action,
							)
						);
				}

				if ( count( $exist_record ) === 0 ) {
					$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
						$table,
						array(
							'ip'        => $ip,
							'blacklist' => 1,
							'datentime' => $current_time,
							'gateway'   => 'Manually',
						)
					);
						return new WP_REST_Response(
							array(
								'message' => __( 'Successfully added IP Address to blacklist.', 'loginpress-pro' ),
								'action'  => 'new_blacklist',
							)
						);
				}

				return new WP_REST_Response(
					array(
						'message' => __( 'IP Address already exists in blacklist.', 'loginpress-pro' ),
						'action'  => 'already_blacklist',
					)
				);
			}

			return new WP_REST_Response( array( 'message' => __( 'Invalid list type.', 'loginpress-pro' ) ), 400 );
		}
	}
}
