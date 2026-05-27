<?php
/**
 * LoginPress Learndash Captcha Trait.
 *
 * Contains all the methods related to captchas on Learndash forms.
 *
 * @package   LoginPress-Pro
 * @subpackage Traits\Loginpress-Integration
 * @since 6.1.0
 */

/**
 * LoginPress Learndash Captcha Trait file.
 *
 * Prevent direct access.
 *
 * @package LoginPress-Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! trait_exists( 'LoginPress_Learndash_Captcha_Trait' ) ) {
	/**
	 * LoginPress Learndash Captcha Trait.
	 *
	 * Contains all the methods related to captchas on Learndash forms.
	 *
	 * @package   LoginPress-Pro
	 * @subpackage Traits\Loginpress-Integration
	 * @since 6.1.0
	 */
	trait LoginPress_Learndash_Captcha_Trait {

		/**
		 * It will check the current user's IP attempts and if user reached the limit, it will return the error message.
		 *
		 * @return WP_Error The error message which will be shown to the user.
		 * @since 5.0.0
		 */
		public function learndash_alert_message_captcha_callback() {
			return new \WP_Error(
				'captcha_error',
				__( '<strong>Error:</strong> Please complete the captcha', 'loginpress-pro' )
			);
		}

		/**
		 * Adds reCAPTCHA field to LearnDash login fields.
		 *
		 * @return string LearnDash login fields with reCAPTCHA added.
		 * @since 5.0.0
		 */
		public function loginpress_add_recaptcha_to_ld_login_fields() {

			ob_start();

			$lp_recaptcha = LoginPress_Recaptcha::instance();
			$lp_recaptcha->loginpress_recaptcha_field();
			$lp_recaptcha->loginpress_recaptcha_script();
			$recaptcha_field = ob_get_clean();

			return $recaptcha_field;
		}

		/**
		 * Adds reCAPTCHA field to LearnDash register fields.
		 *
		 * @return void
		 * @since 5.0.0
		 */
		public function loginpress_add_recaptcha_to_ld_register() {
			$lp_recaptcha = LoginPress_Recaptcha::instance();
			$lp_recaptcha->loginpress_recaptcha_field();
			$lp_recaptcha->loginpress_recaptcha_script();
		}

		/**
		 * RECAPTCHA script callback.
		 *
		 * @return void
		 * @since 5.0.0
		 */
		public function ld_form_script_callback() {
			$lp_recaptcha_instance = LoginPress_Recaptcha::instance();
			add_action( 'wp_head', array( $lp_recaptcha_instance, 'loginpress_recaptcha_script' ) );
		}

		/**
		 * Enable reCAPTCHA on LearnDash login form.
		 *
		 * This function checks if reCAPTCHA is enabled and if the user has entered a valid response.
		 * If the response is invalid, it adds a filter to display an error message.
		 *
		 * @param array $user An array containing the user's login credentials.
		 * @return WP_User|WP_Error The user object or error.
		 * @since 5.0.0
		 */
		public function loginpress_ld_login_form_captcha_enable( $user ) {
			if ( is_wp_error( $user ) || ! isset( $_POST['learndash-login-form'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return $user;
			}

			if ( ! $user instanceof WP_User ) {
				return new WP_Error( 'invalid_user', __( '<strong>Error:</strong> Invalid user data.', 'loginpress-pro' ) );
			}
			if ( $user ) {
				$lp_recaptcha = LoginPress_Recaptcha::instance();
				$cap_type     = isset( $this->loginpress_settings['recaptcha_type'] ) ? $this->loginpress_settings['recaptcha_type'] : 'v2-robot';

				if ( isset( $_POST['g-recaptcha-response'] ) && ! empty( sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

					if ( 'v3' === $cap_type ) {

						$good_score = $this->loginpress_settings['good_score'];
						$score      = $lp_recaptcha->loginpress_v3_recaptcha_verifier();
						if ( $score < $good_score ) {

								return new \WP_Error(
									'captcha_error',
									__( '<strong>Error:</strong> Please complete the captcha', 'loginpress-pro' )
								);

						}
						return $user;
					} else {
						$response = $lp_recaptcha->loginpress_recaptcha_verifier();
						if ( $response->isSuccess() ) {
							return $user;
						}
						if ( ! $response->isSuccess() ) {
								return new \WP_Error(
									'captcha_error',
									__( '<strong>Error:</strong> Please complete the captcha', 'loginpress-pro' )
								);
							// }
						}
					}
				} else {
					return new \WP_Error(
						'captcha_error',
						__( '<strong>Error:</strong> Please complete the captcha', 'loginpress-pro' )
					);
				}
			} else {
				return $user;
			}
		}

		/**
		 * LLLA error callback.
		 *
		 * @param WP_Error $err The error object.
		 * @return WP_Error The modified error object.
		 * @since 5.0.0
		 */
		public function llla_login_ldlms_error_callback( $err ) {
			$lp_recaptcha = LoginPress_Recaptcha::instance();
			$err->remove( 'login-error' );
            $err -> add( 'login-error', __( wp_kses_post(  $lp_recaptcha->loginpress_recaptcha_error() ), 'loginpress-pro' ) ); // @codingStandardsIgnoreLine.
			return $err;
		}

		/**
		 * Turnstile error callback.
		 *
		 * @param WP_Error $err The error object.
		 * @return WP_Error The modified error object.
		 * @since 5.0.0
		 */
		public function llla_login_ld_turnstile_error_callback( $err ) {
			$lp_recaptcha = LoginPress_Turnstile::instance();
			$err->remove( 'login-error' );
			$err->add( 'login-error', wp_kses_post( $lp_recaptcha->loginpress_turnstile_error() ) );
			return $err;
		}

		/**
		 * HCaptcha error callback.
		 *
		 * @param WP_Error $err The error object.
		 * @return WP_Error The modified error object.
		 * @since 5.0.0
		 */
		public function llla_login_ldlms_hcaptcha_error_callback( $err ) {
			$lp_recaptcha = LoginPress_Hcaptcha::instance();
			$err->remove( 'login-error' );
            $err -> add( 'login-error', __( wp_kses_post(  $lp_recaptcha->loginpress_hcaptcha_error() ), 'loginpress-pro' ) ); // @codingStandardsIgnoreLine.
			return $err;
		}

		/**
		 * Enables reCAPTCHA on the LearnDash register form.
		 *
		 * @param mixed $errors The current validation status of the form.
		 * @return mixed The updated validation status of the form.
		 * @since 5.0.0
		 */
		public function loginpress_ld_register_form_captcha_enable( $errors ) {
			$lp_recaptcha    = LoginPress_Recaptcha::instance();
			static $executed = false;
			if ( is_wp_error( $errors ) || ! isset( $_POST['learndash-registration-form'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return $errors;
			}
			// Prevent duplicate execution.
			if ( $executed ) {
				add_filter( 'learndash_registration_errors', array( $this, 'learndash_alert_message_captcha_callback' ), 10, 3 );
				return $errors;
			}
			$executed = true;
			$cap_type = isset( $this->loginpress_settings['recaptcha_type'] ) ? $this->loginpress_settings['recaptcha_type'] : 'v2-robot';

			if ( isset( $_POST['g-recaptcha-response'] ) || isset( $_POST['captcha_response'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

				if ( 'v3' === $cap_type ) {

					$good_score = $this->loginpress_settings['good_score'];
					$score      = $lp_recaptcha->loginpress_v3_recaptcha_verifier();
					if ( $score < $good_score ) {
						add_filter( 'learndash_registration_errors', array( $this, 'learndash_alert_message_captcha_callback' ), 10, 3 );
						return new WP_Error( 'recaptcha_error', $lp_recaptcha->loginpress_recaptcha_error() );
					}
				} else {

					$response = $lp_recaptcha->loginpress_recaptcha_verifier();
					if ( ! $response->isSuccess() ) {
						add_filter( 'learndash_registration_errors', array( $this, 'learndash_alert_message_captcha_callback' ), 10, 3 );
						return new WP_Error( 'recaptcha_error', $lp_recaptcha->loginpress_recaptcha_error() );
					}
				}
			} elseif ( ! isset( $_POST['g-recaptcha-response'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				learndash_get_template_part(
					'modules/alert.php',
					array(
						'type'    => 'warning',
						'icon'    => 'alert',
						'message' => esc_html__( 'Captcha verification failed', 'loginpress-pro' ),
					),
					true
				);
				add_filter( 'learndash_registration_errors_after', array( $this, 'learndash_alert_message_captcha_callback' ), 99, 3 );
				return new WP_Error( 'recaptcha_error', $lp_recaptcha->loginpress_recaptcha_error() );
			}

				return $errors;
		}


		/**
		 * Adds turnstile field to LearnDash login fields.
		 *
		 * @return string LearnDash login fields with turnstile added.
		 * @since 5.0.0
		 */
		public function loginpress_add_turnstile_to_ld_login_fields() {
			ob_start();

			/* Cloudflare CAPTCHA Settings. */
			$lp_turnstile = LoginPress_Turnstile::instance();
			$lp_turnstile->loginpress_turnstile_field( 'learndash' ); // Use correct integration key.
			$lp_turnstile->loginpress_turnstile_script();

			$turnstile_field = ob_get_clean();

			return $turnstile_field;
		}


		/**
		 * Adds turnstile field to LearnDash register fields.
		 *
		 * @return void
		 * @since 5.0.0
		 */
		public function loginpress_add_turnstile_to_ld_register_fields() {

			/* Cloudflare CAPTCHA Settings. */
			$lp_turnstile = LoginPress_Turnstile::instance();
			$lp_turnstile->loginpress_turnstile_field( 'learndash' );
			$lp_turnstile->loginpress_turnstile_script();
		}

		/**
		 * Enables turnstile on the LearnDash login form.
		 *
		 * @param mixed $creds The user credentials.
		 * @return WP_User|WP_Error The user object or error.
		 * @since 5.0.0
		 */
		public function loginpress_ld_login_form_turnstile_enable( $creds ) {
			// Skip if already an error.
			if ( is_wp_error( $creds ) || ( ! isset( $_POST['learndash-login-form'] ) && ! isset( $_POST['learndash-registration-form'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return $creds;
			}

			// Retrieve the secret key from the plugin settings.
			$secret_key = isset( $this->loginpress_settings['secret_key_cf'] ) ? $this->loginpress_settings['secret_key_cf'] : '';
			// Sanitize the Turnstile response from the form submission.
			$response = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			// If no response is received, return a captcha error.
			if ( ! $response ) {
				return new WP_Error( 'captcha_error', __( 'Please wait for the captcha to complete.', 'loginpress-pro' ) );
			}

			// Verify the Turnstile response with Cloudflare's siteverify API.
			$verify_response = wp_remote_post(
				'https://challenges.cloudflare.com/turnstile/v0/siteverify',
				array(
					'body' => array(
						'secret'   => $secret_key,         // Your secret key.
						'response' => $response,           // Captcha response from user.
						'remoteip' => sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ), // User's IP address.
					),
				)
			);

			// Retrieve and decode the API response.
			$response_body = wp_remote_retrieve_body( $verify_response );
			$result        = json_decode( $response_body, true );
			if ( empty( $result['success'] ) ) {

				return new \WP_Error(
					'captcha_error',
					__( '<strong>Error:</strong> Please complete the captcha', 'loginpress-pro' )
				);
			} else {
				return $creds;
			}
		}


		/**
		 * Enables turnstile on the LearnDash register form.
		 *
		 * @param mixed  $valid The current validation status of the form.
		 * @param array  $posted_data The data submitted via the form.
		 * @param string $location The location of the form.
		 * @return mixed The updated validation status of the form.
		 * @since 5.0.0
		 */
		public function ld_register_form_turnstile_enable( $valid, $posted_data, $location ) {
			$ld_register = isset( $this->settings['enable_captcha_ld']['register_learndash'] ) ? $this->settings['enable_captcha_ld']['register_learndash'] : false;
			if ( 'registration' === $location && false === $ld_register ) {
				return;
			}
			if ( $posted_data ) {

				// Retrieve the secret key from the plugin settings.
				$secret_key = isset( $this->loginpress_settings['secret_key_cf'] ) ? $this->loginpress_settings['secret_key_cf'] : '';
				// Sanitize the Turnstile response from the form submission.
				$response = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

				// If no response is received, return a captcha error.
				if ( ! $response ) {
					return new WP_Error( 'captcha_error', __( 'Please wait for the captcha to complete.', 'loginpress-pro' ) );
				}

				// Verify the Turnstile response with Cloudflare's siteverify API.
				$verify_response = wp_remote_post(
					'https://challenges.cloudflare.com/turnstile/v0/siteverify',
					array(
						'body' => array(
							'secret'   => $secret_key,         // Your secret key.
							'response' => $response,           // Captcha response from user.
							'remoteip' => sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ), // User's IP address.
						),
					)
				);

				// Retrieve and decode the API response.
				$response_body = wp_remote_retrieve_body( $verify_response );
				$result        = json_decode( $response_body, true );
				if ( empty( $result['success'] ) ) {
					return new WP_Error( 'captcha_error', __( '<b>Error:</b> Please verify the captcha', 'loginpress-pro' ) );
				} else {
					return $valid;
				}
			} else {
				return $valid;
			}
		}


		/**
		 * Adds hCaptcha field to LearnDash login fields.
		 *
		 * @return string LearnDash login fields with hCaptcha added.
		 * @since 5.0.0
		 */
		public function loginpress_add_hcaptcha_to_ld_login_fields() {

			ob_start();

			/* Cloudflare CAPTCHA Settings. */
			$lp_hcaptcha = LoginPress_Hcaptcha::instance();
			$lp_hcaptcha->loginpress_hcaptcha_field();
			$lp_hcaptcha->loginpress_hcaptcha_enqueue( 'ld' );
			$hcaptcha_field = ob_get_clean();

			return $hcaptcha_field;
		}

		/**
		 * Enable hCaptcha on LearnDash login form.
		 *
		 * This function checks if hCaptcha is enabled and if the user has entered a valid response.
		 * If the response is invalid, it adds a filter to display an error message.
		 *
		 * @param array $creds An array containing the user's login credentials.
		 * @return WP_User|WP_Error The user object or error.
		 * @since 5.0.0
		 */
		public function loginpress_ld_login_form_hcaptcha_enable( $creds ) {
			// Skip if already an error.
			if ( is_wp_error( $creds ) || ! isset( $_POST['learndash-login-form'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return $creds;
			}

			if ( $creds ) {
				$hcap_secret_key = isset( $this->loginpress_settings['hcaptcha_secret_key'] ) ? $this->loginpress_settings['hcaptcha_secret_key'] : '';
				if ( isset( $_POST['h-captcha-response'] ) || isset( $_POST['captcha_response'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$post_response = isset( $_POST['h-captcha-response'] ) ? sanitize_text_field( wp_unslash( $_POST['h-captcha-response'] ) ) : sanitize_text_field( wp_unslash( $_POST['captcha_response'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$response      = wp_remote_post(
						'https://hcaptcha.com/siteverify',
						array(
							'body' => array(
								'secret'   => $hcap_secret_key,
								'response' => $post_response,
								'remoteip' => sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
							),
						)
					);

					$response_body = wp_remote_retrieve_body( $response );
					$result        = json_decode( $response_body );
					if ( ! $result->success ) {
						return new WP_Error( 'captcha_error', __( '<b>Error:</b> Please verify the captcha', 'loginpress-pro' ) );
					} else {
						return $creds;
					}
				} elseif ( ( isset( $_POST['wp-submit'] ) || isset( $_POST['login'] ) ) && ! isset( $_POST['h-captcha-response'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					return new WP_Error( 'captcha_error', __( '<b>Error:</b> Please verify the captcha', 'loginpress-pro' ) );
				}
				return $creds;
			}
		}

		/**
		 * Adds hCaptcha field to LearnDash register fields.
		 *
		 * @return void
		 * @since 5.0.0
		 */
		public function loginpress_add_hcaptcha_to_ld_register_fields() {

			/* Cloudflare CAPTCHA Settings. */
			$lp_hcaptcha = LoginPress_Hcaptcha::instance();
			$lp_hcaptcha->loginpress_hcaptcha_field();
			$lp_hcaptcha->loginpress_hcaptcha_enqueue( 'ld' );
		}

		/**
		 * Enables hCaptcha on the LearnDash register form.
		 *
		 * @param mixed $valid The current validation status of the form.
		 * @param array $posted_data The data submitted via the form.
		 * @return mixed The updated validation status of the form.
		 * @since 5.0.0
		 */
		public function loginpress_ld_register_form_hcaptcha_enable( $valid, $posted_data ) {
			if ( ! isset( $_POST['learndash-registration-form'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return $valid;
			}
			static $executed = false;

			// Prevent duplicate execution.
			if ( $executed ) {
				add_filter( 'learndash_registration_errors', array( $this, 'learndash_alert_message_captcha_callback' ), 10, 3 );
				return $valid;
			}
			$executed = true;
			if ( $posted_data ) {

				$hcap_secret_key = isset( $this->loginpress_settings['hcaptcha_secret_key'] ) ? $this->loginpress_settings['hcaptcha_secret_key'] : '';
				if ( isset( $_POST['h-captcha-response'] ) || isset( $_POST['captcha_response'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$response = wp_remote_post(
						'https://hcaptcha.com/siteverify',
						array(
							'body' => array(
								'secret'   => $hcap_secret_key,
								'response' => sanitize_text_field( wp_unslash( $_POST['h-captcha-response'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
								'remoteip' => sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
							),
						)
					);

					$response_body = wp_remote_retrieve_body( $response );
					$result        = json_decode( $response_body );
					if ( ! $result->success ) {
						add_filter( 'learndash_registration_errors_after', array( $this, 'llla_login_ldlms_hcaptcha_error_callback' ) );

						return new WP_Error( 'captcha_error', __( '<b>Error:</b> Please verify the Hcaptcha', 'loginpress-pro' ) );
					}
				} elseif ( ! isset( $_POST['h-captcha-response'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
						add_filter( 'learndash_registration_errors_after', array( $this, 'llla_login_ldlms_hcaptcha_error_callback' ) );

					return new WP_Error( 'captcha_error', __( '<b>Error:</b> Please verify the Hcaptcha', 'loginpress-pro' ) );
				}
			}
			return $valid;
		}
	}
}
