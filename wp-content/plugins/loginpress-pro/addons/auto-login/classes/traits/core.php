<?php //phpcs:ignore
/**
 * LoginPress AutoLogin Core Trait.
 *
 * Contains core functionality for auto-login features.
 *
 * @package LoginPress
 * @category Core
 * @author WPBrigade
 * @since 6.1.0
 */

if ( ! trait_exists( 'LoginPress_AutoLogin_Core_Trait' ) ) {

	/**
	 * LoginPress AutoLogin Core Trait
	 *
	 * @since 3.0.0
	 * @version 6.1.0
	 */
	trait LoginPress_AutoLogin_Core_Trait {

		/**
		 * Register the rest routes for autologin.
		 *
		 * @since 6.0.0
		 * @return void
		 */
		public function lp_autologin_register_routes() {
			// Get auto-login users.
			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/autologin-users',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'loginpress_get_autologin_users' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			// Add user to auto-login.
			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/add-autologin-user',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_add_autologin_user' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			// Populate popup data.
			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/populate-(?P<type>[a-zA-Z0-9-]+)',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_populate_popup_data' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			// Handle various actions.
			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/autologin-(?P<action>[a-zA-Z0-9-]+)',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_handle_autologin_action' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			// Update settings.
			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/update-(?P<type>[a-zA-Z0-9-]+)',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_update_settings' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);
		}

		/**
		 * Get users in autologin table.
		 *
		 * @since 6.0.0
		 * @return array Array of users with autologin data.
		 */
		public function loginpress_get_autologin_users() {
			// Only get user IDs for performance.
			$user_query = new WP_User_Query(
				array(
					'fields' => 'ID',
				)
			);

			$users = array();

			foreach ( $user_query->get_results() as $user_id ) {
				$user_info = get_userdata( $user_id );
				$user_meta = get_user_meta( $user_id, 'loginpress_autologin_user', true );
				if ( empty( $user_meta ) ) {
					continue;
				}
				$users[] = array(
					'ID'         => $user_id,
					'user_login' => $user_info->user_login,
					'user_email' => $user_info->user_email,
					'meta'       => $user_meta,
				);
			}

			return array( 'users' => $users );
		}

		/**
		 * Add a user in autologin table.
		 *
		 * @param WP_REST_Request $request The REST request object.
		 * @return array Array with success status and URL.
		 * @since 6.0.0
		 */
		public function loginpress_add_autologin_user( WP_REST_Request $request ) {
			$user_id = intval( $request['user_id'] );

			// Generate a unique code.
			$loginpress_code = wp_generate_password( 32, false );

			// Set default values.
			$date            = gmdate( 'Y-m-d' );
			$default_date    = gmdate( 'Y-m-d', strtotime( "$date +7 day" ) );
			$default_expire  = apply_filters( 'loginpress_autologin_default_expiration', $default_date );
			$meta            = get_user_meta( $user_id, 'loginpress_autologin_user', true );
			$emails          = isset( $meta['emails'] ) && ! empty( $meta['emails'] ) ? $meta['emails'] : '';
			$expire          = isset( $meta['expire'] ) && ! empty( $meta['expire'] ) ? $meta['expire'] : 'unchecked';
			$update_elements = array(
				'state'           => sanitize_text_field( 'enable' ),
				'emails'          => sanitize_text_field( $emails ), // These are updated when a user sends email to multiple user.
				'code'            => sanitize_text_field( $loginpress_code ),
				'expire'          => sanitize_text_field( $expire ),
				'duration'        => sanitize_text_field( $default_expire ),
				'unlimited_click' => 'checked',
			);

			update_user_meta( $user_id, 'loginpress_autologin_user', $update_elements );

			return array(
				'success' => true,
				'url'     => esc_url_raw(
					add_query_arg(
						'loginpress_code',
						rawurlencode( $loginpress_code ),
						site_url( '/' )
					)
				),
			);
		}

		/**
		 * Function that populates the popups.
		 *
		 * @param WP_REST_Request $request The REST request object.
		 * @return array Popup data array.
		 * @since 6.0.0
		 */
		public function loginpress_populate_popup_data( WP_REST_Request $request ) {
			$type      = sanitize_text_field( $request['type'] );
			$user_id   = intval( $request['id'] );
			$user_meta = get_user_meta( $user_id, 'loginpress_autologin_user', true );

			switch ( $type ) {
				case 'duration':
					return array(
						'never_expire'    => sanitize_text_field( '1' === $user_meta['expire'] ? 'checked' : '' ),
						'expire_duration' => sanitize_text_field( $user_meta['duration'] ?? '' ),
					);

				case 'email':
					return array(
						'emails' => isset( $user_meta['emails'] ) && is_array( $user_meta['emails'] )
							? array_map( 'sanitize_email', $user_meta['emails'] )
							: array(),
					);

				case 'link-count':
					return array(
						'link_count' => isset( $user_meta['link_count'] )
							? absint( $user_meta['link_count'] )
							: 0,
						'unlimited'  => isset( $user_meta['unlimited_click'] )
							? sanitize_text_field( $user_meta['unlimited_click'] )
							: 'unchecked',
					);
			}

			return array();
		}

		/**
		 * Update autologin settings.
		 *
		 * @param WP_REST_Request $request The REST request object.
		 * @return array|WP_Error Success response or error.
		 * @since 6.0.0
		 */
		public function loginpress_update_settings( WP_REST_Request $request ) {
			$type    = sanitize_text_field( $request['type'] );
			$data    = $request->get_json_params();
			$user_id = ! empty( $data['id'] ) ? absint( $data['id'] ) : 0;
			// Bail if no valid user ID.
			if ( ! $user_id || ! get_userdata( $user_id ) ) {
				return new WP_Error(
					'invalid_user',
					__( 'Invalid or missing user ID.', 'loginpress-pro' ),
					array( 'status' => 400 )
				);
			}
			$meta = get_user_meta( $user_id, 'loginpress_autologin_user', true );

			switch ( $type ) {
				case 'duration':
					$meta['expire']   = sanitize_text_field( $data['never_expire'] );
					$meta['duration'] = sanitize_text_field( $data['expire_duration'] );
					break;

				case 'email':
					if ( ! empty( $user_id ) && ! empty( $data['emails'] ) ) {
						$emails = sanitize_text_field( wp_unslash( $data['emails']['value'] ) );
						update_user_meta(
							$user_id,
							'loginpress_autologin_user',
							array_merge(
								$meta,
								array(
									'emails' => $emails,
								)
							)
						);
						$this->loginpress_autologin_multiusers_email( $user_id );
					}
					break;

				case 'link-count':
					if ( true === $data['unlimited']['checked'] ) {
						$meta['link_count']      = '';
						$meta['link_click']      = 0;
						$meta['unlimited_click'] = 'checked';
					} else {
						$meta['link_count']      = intval( $data['link_count']['value'] );
						$meta['link_click']      = 0;
						$meta['unlimited_click'] = '';
					}
					break;
			}

			update_user_meta( $user_id, 'loginpress_autologin_user', $meta );
			return array( 'success' => true );
		}

		/**
		 * Handle autologin actions.
		 *
		 * @param WP_REST_Request $request The REST request object.
		 * @return array Success response.
		 * @since 6.0.0
		 * @version 6.1.2
		 */
		public function loginpress_handle_autologin_action( WP_REST_Request $request ) {
			$action  = $request['action'];
			$user_id = intval( $request['user_id'] );
			$data    = $request->get_json_params();

			switch ( $action ) {
				case 'delete':
					// Delete the autologin user meta for the given user.
					delete_user_meta( $user_id, 'loginpress_autologin_user' );
					break;

				case 'new-link':
					// Create a new autologin link - reset all meta like a new user.
					$loginpress_code = sanitize_text_field( $data['code'] );
					$date            = gmdate( 'Y-m-d' );
					$default_date    = gmdate( 'Y-m-d', strtotime( "$date +7 day" ) );
					$default_expire  = apply_filters( 'loginpress_autologin_default_expiration', $default_date );
					$meta            = get_user_meta( $user_id, 'loginpress_autologin_user', true );
					$emails          = isset( $meta['emails'] ) && ! empty( $meta['emails'] ) ? $meta['emails'] : '';
					$link_count      = isset( $meta['link_count'] ) && ! empty( $meta['link_count'] ) ? $meta['link_count'] : 10;
					$unlimited_click = isset( $meta['unlimited_click'] ) && ! empty( $meta['unlimited_click'] ) ? $meta['unlimited_click'] : 'checked';

					$update_elements = array(
						'state'           => sanitize_text_field( 'enable' ),
						'emails'          => sanitize_text_field( $emails ),
						'code'            => sanitize_text_field( $loginpress_code ),
						'expire'          => 'unchecked', // Reset expire status when creating new link.
						'duration'        => sanitize_text_field( $default_expire ),
						'link_count'      => sanitize_text_field( $link_count ), // Preserve link count settings.
						'unlimited_click' => sanitize_text_field( $unlimited_click ), // Preserve unlimited click setting.
						'link_click'      => 0, // Reset click counter for new link.
					);

					update_user_meta( $user_id, 'loginpress_autologin_user', $update_elements );
					return array(
						'success' => true,
						'url'     => esc_url( home_url() . '/?loginpress_code=' . $loginpress_code ),
					);
					break; //phpcs:ignore

				case 'change-state':
					// Change the autologin state in the user meta.
					$state         = sanitize_text_field( $data['state'] );
					$meta          = get_user_meta( $user_id, 'loginpress_autologin_user', true );
					$meta['state'] = $state;
					update_user_meta( $user_id, 'loginpress_autologin_user', $meta );
					break;

				case 'email-user':
					// Send an autologin link email to the user.
					$this->send_autologin_email( $user_id );
					break;
			}

			return array( 'success' => true );
		}

		/**
		 * Update the click counts for all users with the meta key 'loginpress_autologin_user', or for a specific user ID if provided.
		 *
		 * @param array $args {
		 *     Array of arguments to pass to the filter 'loginpress_autologin_update_clicks_args'.
		 *
		 *     @type int    $user_id       The ID of the user to update the click count for. If empty, all users with the meta key will be updated.
		 *     @type int    $max_clicks    The maximum number of clicks allowed.
		 *     @type int    $click_count   The current number of clicks.
		 *     @type string $unlimited_click Either 'checked' or 'unchecked', indicating whether the click count is unlimited.
		 * }
		 * @return bool True if the click counts were updated, false otherwise.
		 * @since 4.0.0
		 */
		private function loginpress_autologin_update_clicks( $args = array() ) {
			// Check if the filter 'loginpress_autologin_link_count' has any hooks attached.
			if ( ! has_filter( 'loginpress_autologin_link_count' ) ) {
				// If no filter is attached, exit the function early.
				return false;
			}

			// If a specific user ID is provided.
			if ( isset( $args['user_id'] ) && ! empty( $args['user_id'] ) ) {
				$user_id = $args['user_id'];
				$meta    = get_user_meta( $user_id, 'loginpress_autologin_user', true );

				// Update the meta for the specific user.
				if ( $meta ) {
					update_user_meta(
						$user_id,
						'loginpress_autologin_user',
						array_merge(
							$meta,
							array(
								'link_count'      => isset( $args['max_clicks'] ) ? (int) $args['max_clicks'] : 0,
								'unlimited_click' => $args['unlimited_click'],
							)
						)
					);
				}
			} else {
				// If no specific user ID, update all users with the meta key.
				$users = get_users(
					array(
						'meta_key' => 'loginpress_autologin_user', // phpcs:ignore
						'fields'   => 'ID', // Fetch only user IDs.
					)
				);

				foreach ( $users as $user_id ) {
					$meta = get_user_meta( $user_id, 'loginpress_autologin_user', true );

					// Update the meta for each user.
					if ( $meta ) {
						update_user_meta(
							$user_id,
							'loginpress_autologin_user',
							array_merge(
								$meta,
								array(
									'link_count'      => isset( $args['max_clicks'] ) ? (int) $args['max_clicks'] : 0,
									'unlimited_click' => $args['unlimited_click'],
								)
							)
						);
					}
				}
			}
			// No return filter applied; just return true or another appropriate result.
			return true;
		}

		/**
		 * Function that emails user for autologin.
		 *
		 * @param int $user_id The user ID.
		 * @return bool True if email was sent successfully, false otherwise.
		 * @since 6.0.0
		 */
		public function send_autologin_email( $user_id ) {
			if ( empty( $user_id ) ) {
				return false;
			}

			$user = get_userdata( $user_id );
			if ( ! $user ) {
				return false;
			}

			$meta = get_user_meta( $user_id, 'loginpress_autologin_user', true );
			if ( empty( $meta['code'] ) ) {
				return false;
			}

			$email        = $user->user_email;
			$code         = home_url() . '/?loginpress_code=' . $meta['code'];
			$blog_name    = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
			$user_name    = ! empty( $user->first_name ) ? $user->first_name : $user->display_name;
			$allowed_html = LoginPress_AutoLogin_Utilities::get_allowed_html();
			$message      = esc_html__( 'Following is the Auto Login link details for the Blog ', 'loginpress-pro' );
			$message     .= ucwords( $blog_name ) . "\n\n";
			$message     .= esc_html__( 'User Name: ', 'loginpress-pro' ) . ucwords( $user_name ) . "\n\n";
			$message     .= esc_html__( 'User Role: ', 'loginpress-pro' ) . ucwords( implode( ', ', $user->roles ) ) . "\n\n";
			$message     .= esc_html__( 'Autologin Link: ', 'loginpress-pro' );
			$message     .= esc_url_raw( $code );

			return wp_mail(
				$email,
				apply_filters(
					'loginpress_autologin_email_subject',
					// translators: Auto login link.
					sprintf( esc_html_x( '[%s] Auto Login Link', 'Blogname', 'loginpress-pro' ), $blog_name )
				),
				wp_kses(
					apply_filters( 'loginpress_autologin_email_msg', $message, $blog_name, $user_name, $code ),
					$allowed_html
				)
			);
		}

		/**
		 * Function that emails to multiple users for autologin.
		 *
		 * @param int $user_id The ID of the user.
		 * @return void
		 * @since 1.0.0
		 */
		public function loginpress_autologin_multiusers_email( $user_id ) {
			// User will have to login in order to perform this task and should have the "manage_options" rights as well.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No cheating, huh!' );
			}
			$user   = get_userdata( $user_id );
			$meta   = get_user_meta( $user_id, 'loginpress_autologin_user', true );
			$emails = $user->user_email;
			if ( ! empty( $meta['emails'] ) ) {
				$emails   = explode( ',', $meta['emails'] );
				$emails[] = $user->user_email;
			}
			$meta         = get_user_meta( $user_id, 'loginpress_autologin_user', true );
			$code         = home_url() . '/?loginpress_code=' . $meta['code'];
			$blog_name    = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
			$user_name    = isset( $user->first_name ) && ! empty( $user->first_name ) ? $user->first_name : $user->display_name;
			$allowed_html = LoginPress_AutoLogin_Utilities::get_allowed_html();
			/* Translators: The Autologin multi-user email */
			$message  = esc_html__( 'Following is the Auto Login link details for the Blog ', 'loginpress-pro' );
			$message .= esc_html( ucwords( $blog_name ) ) . "\n\n";
			$message .= esc_html__( 'User Name: ', 'loginpress-pro' ) . esc_html( ucwords( $user_name ) ) . "\n\n";
			$message .= esc_html__( 'User Role: ', 'loginpress-pro' ) . esc_html( ucwords( implode( ', ', $user->roles ) ) ) . "\n\n";
			$message .= esc_html__( 'Autologin Link: ', 'loginpress-pro' );
			$message .= esc_url_raw( $code );
			$mail     = wp_mail(
				$emails,
				/* translators: Blog name. */
				esc_html( apply_filters( 'loginpress_autologin_email_subject', sprintf( esc_html_x( '[%s] Auto Login Link', 'Blogname', 'loginpress-pro' ), $blog_name ) ) ),
				wp_kses( apply_filters( 'loginpress_autologin_email_msg', $message, $blog_name, $user_name, $code ), $allowed_html )
			);
			if ( version_compare( LOGINPRESS_VERSION, '6.0.0', '<' ) ) {
				wp_die();
			}
		}
	}
}
