<?php
/**
 * LoginPress Captcha Rest Trait file.
 *
 * @package LoginPress Captcha
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LoginPress Captcha Rest Trait.
 *
 * Handles Some helping functions from class-loginpress-captcha.php file.
 * Registers all the rest apis for Captchas.
 *
 * @package   LoginPress
 * @subpackage Traits\Captcha
 * @since 6.1.0
 */

if ( ! trait_exists( 'LoginPress_Captcha_Rest_Trait' ) ) {
	/**
	 * LoginPress Captcha Settings Trait.
	 *
	 * Handles Some helping functions from class-loginpress-captcha.php file.
	 * Registers all the rest apis for Captchas.
	 *
	 * @package   LoginPress
	 * @subpackage Traits\Captcha
	 * @since   6.1.0
	 */
	trait LoginPress_Captcha_Rest_Trait {

		/**
		 * Register the rest routes for social login.
		 *
		 * @since 6.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function lp_captcha_register_routes() {
			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/captcha-settings/',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_captcha_settings' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/captcha-settings/',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'save_captcha_settings' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/verify-turnstile',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_verify_turnstile_api' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/verify-hcaptcha',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_verify_hcaptcha' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/verify-recaptcha',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_verify_recaptcha' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/verify-recaptcha-v3',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_verify_recaptcha_v3' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/captcha-options/',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'loginpress_get_captcha_type_options' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/captcha-tab-visibility/',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'loginpress_get_captcha_tab_visibility' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);
		}


		/**
		 * Verify recaptcha callback.
		 *
		 * @since 6.0.0
		 * @version 6.1.0
		 * @param WP_REST_Request $request Full details about the request.
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function loginpress_verify_recaptcha( $request ) {
			$response_token = sanitize_text_field( $request['response'] );
			$secret         = sanitize_text_field( $request['secret'] );

			if ( empty( $response_token ) || empty( $secret ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'error'   => esc_html__( 'Missing required fields', 'loginpress-pro' ),
					),
					400
				);
			}

			$verify_url = 'https://www.google.com/recaptcha/api/siteverify';

			$response = wp_remote_post(
				$verify_url,
				array(
					'body' => array(
						'secret'   => $secret,
						'response' => $response_token,
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'error'   => esc_html__( 'Request failed: ', 'loginpress-pro' ) . esc_html( $response->get_error_message() ),
					),
					500
				);
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			return new WP_REST_Response(
				array(
					'success' => isset( $body['success'] ) && $body['success'],
					'data'    => $body,
				)
			);
		}

		/**
		 * Verify recaptcha V3 callback.
		 *
		 * @since 6.0.0
		 * @version 6.1.0
		 * @param WP_REST_Request $request Full details about the request.
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function loginpress_verify_recaptcha_v3( $request ) {
			$response_token = sanitize_text_field( $request['response'] );
			$secret         = sanitize_text_field( $request['secret'] );

			if ( empty( $response_token ) || empty( $secret ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'error'   => esc_html__( 'Missing required fields', 'loginpress-pro' ),
					),
					400
				);
			}

			$verify_url = LOGINPRESS_RECAPTCHA_VERIFY_URL;

			$response = wp_remote_post(
				$verify_url,
				array(
					'body' => array(
						'secret'   => $secret,
						'response' => $response_token,
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'error'   => esc_html__( 'Request failed: ', 'loginpress-pro' ) . esc_html( $response->get_error_message() ),
					),
					500
				);
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			// For V3, also check the score if available.
			$is_valid = isset( $body['success'] ) && $body['success'];
			return new WP_REST_Response(
				array(
					'success' => $is_valid,
					'data'    => $body,
				)
			);
		}

		/**
		 * Verify hcaptcha callback.
		 *
		 * @since 6.0.0
		 * @version 6.1.0
		 * @param WP_REST_Request $request Full details about the request.
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function loginpress_verify_hcaptcha( $request ) {
			$response_token = sanitize_text_field( $request['response'] );
			$secret         = sanitize_text_field( $request['secret'] );

			if ( empty( $response_token ) || empty( $secret ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'error'   => esc_html__( 'Missing required fields', 'loginpress-pro' ),
					),
					400
				);
			}

			$verify_url = 'https://hcaptcha.com/siteverify';

			$response = wp_remote_post(
				$verify_url,
				array(
					'body' => array(
						'secret'   => $secret,
						'response' => $response_token,
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'error'   => esc_html__( 'Request failed: ', 'loginpress-pro' ) . esc_html( $response->get_error_message() ),
					),
					500
				);
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			return new WP_REST_Response(
				array(
					'success' => isset( $body['success'] ) && $body['success'],
					'data'    => $body,
				)
			);
		}

		/**
		 * Verify turnstile callback.
		 *
		 * @since 6.0.0
		 * @version 6.1.0
		 * @param WP_REST_Request $request Full details about the request.
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function loginpress_verify_turnstile_api( WP_REST_Request $request ) {
			$secret   = sanitize_text_field( $request->get_param( 'secret' ) );
			$response = sanitize_text_field( $request->get_param( 'response' ) );

			if ( empty( $secret ) || empty( $response ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => esc_html__( 'Missing parameters', 'loginpress-pro' ),
					),
					400
				);
			}

			$verify = wp_remote_post(
				'https://challenges.cloudflare.com/turnstile/v0/siteverify',
				array(
					'body' => array(
						'secret'   => $secret,
						'response' => $response,
						'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
					),
				)
			);

			if ( is_wp_error( $verify ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => esc_html__( 'Request failed', 'loginpress-pro' ),
					),
					500
				);
			}

			$body = json_decode( wp_remote_retrieve_body( $verify ), true );

			if ( isset( $body['success'] ) && $body['success'] ) {
				return new WP_REST_Response( array( 'success' => true ) );
			}

			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $body['error-codes'] ?? array( 'Unknown error' ),
				),
				400
			);
		}

		/**
		 * Get all captcha settings.
		 *
		 * @since 6.0.0
		 * @version 6.1.0
		 * @return WP_REST_Response Response object with captcha settings.
		 */
		public function get_captcha_settings() {
			$settings = get_option( 'loginpress_captcha_settings', array() );
			return rest_ensure_response( $settings );
		}

		/**
		 * Get filtered captcha type options.
		 * This is for loginpress versions >= 6.0.0
		 *
		 * @since 6.1.0
		 * @return WP_REST_Response Response object with filtered captcha options.
		 */
		public function loginpress_get_captcha_type_options() {
			$default_options = array(
				'type_recaptcha'  => __( 'Google reCAPTCHA', 'loginpress-pro' ),
				'type_hcaptcha'   => __( 'hCaptcha', 'loginpress-pro' ),
				'type_cloudflare' => __( 'Cloudflare Turnstile', 'loginpress-pro' ),
			);

			/**
			 * Filter captcha options to allow users to exclude specific captcha types.
			 *
			 * @since 6.1.0
			 * @param array $default_options Default captcha options array.
			 * @return array Filtered captcha options.
			 */
			$filtered_options = apply_filters( 'loginpress_captcha_type_options', $default_options );

			// Convert to React-friendly format.
			$react_options = array();
			foreach ( $filtered_options as $value => $label ) {
				$react_options[] = array(
					'value' => $value,
					'label' => $label,
				);
			}

			return rest_ensure_response( $react_options );
		}

		/**
		 * Get captcha tab visibility status.
		 * This is for loginpress versions >= 6.0.0
		 *
		 * @since 6.1.0
		 * @return WP_REST_Response Response object with tab visibility status.
		 */
		public function loginpress_get_captcha_tab_visibility() {
			/**
			 * Filter to control captcha tab visibility.
			 *
			 * @since 6.1.0
			 * @param bool $visible Whether the captcha tab should be visible. Default true.
			 * @return bool True if tab should be visible, false to hide it.
			 */
			$is_visible = apply_filters( 'loginpress_captcha_tab_visible', true );

			return rest_ensure_response(
				array(
					'visible' => $is_visible,
				)
			);
		}

		/**
		 * Save captcha settings.
		 *
		 * @since 6.0.0
		 * @version 6.1.0
		 * @param WP_REST_Request $request Full details about the request.
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function save_captcha_settings( WP_REST_Request $request ) {
			$params    = $request->get_params();
			$sanitized = array();

			// Sanitize all fields.
			foreach ( $params as $key => $value ) {
				if ( is_array( $value ) ) {
					$sanitized[ $key ] = array_map( 'sanitize_text_field', $value );
				} else {
					$sanitized[ $key ] = sanitize_text_field( $value );
				}
			}
			unset( $sanitized['_locale'] );
			update_option( 'loginpress_captcha_settings', $sanitized );
			return rest_ensure_response(
				array(
					'success' => true,
					'message' => esc_html__( 'Settings saved successfully!', 'loginpress-pro' ),
				)
			);
		}

		/**
		 * Verifies and updates Google reCAPTCHA settings when saving.
		 *
		 * Checks the type of reCAPTCHA selected and verifies the response using the
		 * appropriate secret key. Updates or deletes the corresponding option based
		 * on the verification result.
		 *
		 * @since 4.0.0
		 * @version 6.1.0
		 * @param array  $new_value The new reCAPTCHA settings to be saved.
		 * @param array  $old_value The previous reCAPTCHA settings.
		 * @param string $option    The name of the option being updated.
		 * @return array The updated reCAPTCHA settings.
		 */
		public function loginpress_verify_recaptcha_on_save( $new_value, $old_value, $option ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			if ( version_compare( LOGINPRESS_VERSION, '6.0.0', '>=' ) && version_compare( LOGINPRESS_PRO_VERSION, '6.0.0', '>=' ) ) {
				return $new_value;
			}
			if ( isset( $this->loginpress_captcha_settings['validate_cf'] ) && 'on' === $this->loginpress_captcha_settings['validate_cf'] ) {
				$new_value['validate_cf'] = 'on';
			}
			if ( isset( $this->loginpress_settings['enable_repatcha'] ) ) {
				return $new_value;
			}
			if ( ( ! isset( $new_value['recaptcha_type'] ) && ! isset( $old_value['recaptcha_type'] ) )
			|| ( isset( $new_value['captchas_type'] ) && 'type_recaptcha' !== $new_value['captchas_type'] ) ) {
				if ( 'type_cloudflare' === $new_value['captchas_type'] ) {
					if ( isset( $new_value['site_key_cf'] ) && isset( $old_value['site_key_cf'] ) && $new_value['site_key_cf'] === $old_value['site_key_cf'] &&
					isset( $new_value['secret_key_cf'] ) && isset( $old_value['secret_key_cf'] ) && $new_value['secret_key_cf'] === $old_value['secret_key_cf'] &&
					isset( $old_value['validate_cf'] ) && 'on' === $old_value['validate_cf'] ) {
						return $new_value;
					}

					$site_key   = $new_value['site_key_cf'];
					$secret_key = $new_value['secret_key_cf'];
					$response   = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
					// Perform validation by calling the Turnstile API.
					$verify_response = wp_remote_post(
						'https://challenges.cloudflare.com/turnstile/v0/siteverify',
						array(
							'body' => array(
								'secret'   => $secret_key,
								'response' => $response, // Send a dummy value as 'response' for verification.
							),
						)
					);
						// Parse the response from the API.
					if ( ! is_wp_error( $verify_response ) ) {
						$response_body = wp_remote_retrieve_body( $verify_response );
						$result        = json_decode( $response_body, true );
						// If the keys are valid, update 'validate_cf' to 'verified'.
						if ( isset( $result['success'] ) && $result['success'] ) {
							$new_value['validate_cf'] = 'on';
						} else {
							$new_value['validate_cf'] = 'off';
						}
					} else {
						// Handle any errors during the API call.
						$new_value['validate_cf'] = 'off';
					}
					return $new_value;

				} elseif ( ( isset( $new_value['captchas_type'] ) && 'type_hcaptcha' === $new_value['captchas_type'] ) ||
				( isset( $old_value['captchas_type'] ) && 'type_hcaptcha' === $old_value['captchas_type'] ) ) {

					if ( ( isset( $this->loginpress_captcha_settings['hcaptcha_verified'] ) && 'on' === $this->loginpress_captcha_settings['hcaptcha_verified'] ) &&
						$new_value['hcaptcha_secret_key'] === $old_value['hcaptcha_secret_key'] ) {
						return $new_value;
					}

					$hcap_secret_key = isset( $new_value['hcaptcha_secret_key'] ) ? $new_value['hcaptcha_secret_key'] : '';
					if ( isset( $_POST['h-captcha-response'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
						$response = wp_remote_post(
							'https://hcaptcha.com/siteverify',
							array(
								'body' => array(
									'secret'   => $hcap_secret_key,
									'response' => sanitize_text_field( wp_unslash( $_POST['h-captcha-response'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
									'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
								),
							)
						);

							$response_body = wp_remote_retrieve_body( $response );
							$result        = json_decode( $response_body );
						if ( $result->success ) {
							$new_value['hcaptcha_verified'] = 'on';
						} elseif ( ! $result->success ) {
							$new_value['hcaptcha_verified'] = 'off';
						}
							return $new_value;
					}
				}
			}
			$cap_type = $new_value['recaptcha_type'] ? $new_value['recaptcha_type'] : $old_value['recaptcha_type'];

			if ( 'v2-robot' === $cap_type ) {
				if ( ! isset( $new_value['secret_key'] ) && ! isset( $old_value['secret_key'] ) ) {
					return $new_value;
				}
				$secret = isset( $new_value['secret_key'] ) ? $new_value['secret_key'] : ( isset( $old_value['secret_key'] ) ? $old_value['secret_key'] : '' );
			} else {
				return $new_value;
			}

			include LOGINPRESS_PRO_ROOT_PATH . '/lib/recaptcha/src/autoload.php';
			if ( ini_get( 'allow_url_fopen' ) ) {
				$recaptcha = new \ReCaptcha\ReCaptcha( $secret );
			} else {
				$recaptcha = new \ReCaptcha\ReCaptcha( $secret, new \ReCaptcha\RequestMethod\CurlPost() );
			}
			$recaptcha_response = isset( $_POST['g-recaptcha-response'] ) ? wp_unslash( sanitize_text_field( $_POST['g-recaptcha-response'] ) ) : ''; // @codingStandardsIgnoreLine.
			$response = $recaptcha->verify( wp_unslash( $recaptcha_response ), isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' ); // @codingStandardsIgnoreLine.

			if ( 'v2-robot' === $cap_type ) {
				if ( ( isset( $this->loginpress_captcha_settings['v2_robot_verified'] ) && 'on' === $this->loginpress_captcha_settings['v2_robot_verified'] ) &&
				$new_value['secret_key'] === $old_value['secret_key'] ) {
					return $new_value;
				}

				if ( $response->isSuccess() ) {
					$new_value['v2_robot_verified'] = 'on';
				}
				if ( ! $response->isSuccess() ) {
					$new_value['v2_robot_verified'] = 'off';
				}
			}

			return $new_value;
		}
	}
}
