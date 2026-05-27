<?php
/**
 * LoginPress LifterLMS Captcha Trait.
 *
 * Contains all the methods related to captchas on Lifter forms.
 *
 * @package   LoginPress-Pro
 * @subpackage Traits\Loginpress-Integration
 * @since 6.1.0
 */

/**
 * LoginPress LifterLMS Captcha Trait file.
 *
 * Prevent direct access.
 *
 * @package LoginPress-Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! trait_exists( 'LoginPress_Lifter_Captcha_Trait' ) ) {
	/**
	 * LoginPress LifterLMS Captcha Trait.
	 *
	 * Contains all the methods related to captchas on Lifter forms.
	 *
	 * @package   LoginPress-Pro
	 * @subpackage Traits\Loginpress-Integration
	 * @since 6.1.0
	 */
	trait LoginPress_Lifter_Captcha_Trait {

		/**
		 * Adds reCAPTCHA field to LifterLMS login fields.
		 *
		 * @param array $fields LifterLMS login fields.
		 * @return array LifterLMS login fields with reCAPTCHA added.
		 * @since 5.0.0
		 */
		public function loginpress_add_recaptcha_to_lifter_login_fields( $fields ) {
			$cap_type = isset( $this->loginpress_settings['recaptcha_type'] ) ? $this->loginpress_settings['recaptcha_type'] : 'v2-robot';
			if ( 'v2-robot' === $cap_type ) {
				ob_start();
				$lp_recaptcha = LoginPress_Recaptcha::instance();
				$lp_recaptcha->loginpress_recaptcha_field();

				$recaptcha_field = ob_get_clean();
			} else {
				$lp_recaptcha    = LoginPress_Recaptcha::instance();
				$recaptcha_field = $lp_recaptcha->loginpress_recaptcha_field();
			}

			$recaptcha_field = array(
				'id'          => 'recaptcha-lifter',
				'type'        => 'html',
				'description' => $recaptcha_field,

			);
			array_splice( $fields, count( $fields ) - 3, 0, array( $recaptcha_field ) );

			return $fields;
		}

		/**
		 * Adds reCAPTCHA field to LifterLMS register fields.
		 *
		 * @return void
		 * @since 5.0.0
		 */
		public function loginpress_add_recaptcha_to_lifter_register() {
			$lp_recaptcha = LoginPress_Recaptcha::instance();

			$recaptcha_field = $lp_recaptcha->loginpress_recaptcha_field();

			llms_form_field(
				array(
					'id'          => 'recaptcha-lifter',
					'type'        => 'html',
					'description' => $recaptcha_field,

				)
			);
		}

		/**
		 * Adds reCAPTCHA field to LifterLMS lost password fields.
		 *
		 * @param array $fields LifterLMS lost password fields.
		 * @return array LifterLMS lost password fields with reCAPTCHA added.
		 * @since 5.0.0
		 */
		public function loginpress_add_recaptcha_to_lifter_lostpass_fields( $fields ) {
			ob_start();
			$lp_recaptcha_instance = LoginPress_Recaptcha::instance();
			$lp_recaptcha_instance->loginpress_recaptcha_field();
			$recaptcha_field = ob_get_clean();

			$recaptcha_field =
				array(
					array(
						'id'          => 'recaptcha-lifter',
						'type'        => 'html',
						'description' => $recaptcha_field,

					),
					array(
						'id'    => 'recaptcha-lifter2',
						'type'  => 'hidden',
						'value' => do_action( 'lifterlms_lost_do_action' ),
					),
				);
			array_splice( $fields, count( $fields ) - 1, 0, $recaptcha_field );

			return $fields;
		}

		/**
		 * RECAPTCHA script callback.
		 *
		 * @return void
		 * @since 5.0.0
		 */
		public function loginpress_llms_form_script_callback() {
			$lp_recaptcha_instance = LoginPress_Recaptcha::instance();
			$lp_recaptcha_instance->loginpress_recaptcha_script();
		}

		/**
		 * Enable reCAPTCHA on LifterLMS login form.
		 *
		 * This function checks if reCAPTCHA is enabled and if the user has entered a valid response.
		 * If the response is invalid, it adds a filter to display an error message.
		 *
		 * @param array $creds An array containing the user's login credentials.
		 * @return array|void The user credentials or void.
		 * @since 5.0.0
		 */
		public function loginpress_llms_login_form_captcha_enable( $creds ) {
			$lp_recaptcha = LoginPress_Recaptcha::instance();
			if ( $creds ) {

				$username       = $creds['user_login'];
				$password       = $creds['user_password'];
				$cap_type       = isset( $this->loginpress_settings['recaptcha_type'] ) ? $this->loginpress_settings['recaptcha_type'] : 'v2-robot';
				$cap_permission = isset( $this->loginpress_settings['enable_repatcha'] ) ? $this->loginpress_settings['enable_repatcha'] : 'off';

				if ( $cap_permission || ( isset( $_POST['g-recaptcha-response'] ) && ! empty( sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

					if ( 'v3' === $cap_type ) {

						$good_score = $this->loginpress_settings['good_score'];
						$score      = $lp_recaptcha->loginpress_v3_recaptcha_verifier();

						if ( $username && $password && $score < $good_score ) {
							add_filter( 'lifterlms_user_login_errors', array( $this, 'llla_login_lifterlms_error_callback' ) );
						}
					} else {
						$response = $lp_recaptcha->loginpress_recaptcha_verifier();
						if ( $response->isSuccess() ) {
							return $creds;
						}
						if ( $username && $password && ! $response->isSuccess() ) {
							add_filter( 'lifterlms_user_login_errors', array( $this, 'llla_login_lifterlms_error_callback' ) );
						}
					}
				}
			} else {
				return $creds;
			}
		}

		/**
		 * RECAPTCHA error callback.
		 *
		 * @param WP_Error $err The error object.
		 * @return WP_Error The modified error object.
		 * @since 5.0.0
		 */
		public function llla_login_lifterlms_error_callback( $err ) {
			$lp_recaptcha = LoginPress_Recaptcha::instance();
			$err->remove( 'login-error' );
			$err->add( 'login-error', wp_kses_post( $lp_recaptcha->loginpress_recaptcha_error() ) );
			return $err;
		}

		/**
		 * Turnstile error callback.
		 *
		 * @param WP_Error $err The error object.
		 * @return WP_Error The modified error object.
		 * @since 5.0.0
		 */
		public function llla_login_lifterlms_turnstile_error_callback( $err ) {
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
		public function llla_login_lifterlms_hcaptcha_error_callback( $err ) {
			$lp_recaptcha = LoginPress_Hcaptcha::instance();
			$err->remove( 'login-error' );
            $err -> add( 'login-error', __( wp_kses_post(  $lp_recaptcha->loginpress_hcaptcha_error() ), 'loginpress-pro' ) ); // @codingStandardsIgnoreLine.
			return $err;
		}

		/**
		 * Enables reCAPTCHA on the LifterLMS register form.
		 *
		 * @param mixed  $valid The current validation status of the form.
		 * @param array  $posted_data The data submitted via the form.
		 * @param string $location The location of the form.
		 * @return mixed The updated validation status of the form.
		 * @since 5.0.0
		 */
		public function loginpress_llms_register_form_captcha_enable( $valid, $posted_data, $location ) {
			$lp_recaptcha    = LoginPress_Recaptcha::instance();
			$lifter_register = isset( $this->settings['enable_captcha_llms']['lifter_register_form'] ) ? $this->settings['enable_captcha_llms']['lifter_register_form'] : false;
			$lifter_purchase = isset( $this->settings['enable_captcha_llms']['lifter_purchase_form'] ) ? $this->settings['enable_captcha_llms']['lifter_purchase_form'] : false;
			if ( 'registration' === $location && false === $lifter_register ) {
				return;
			}
			if ( 'checkout' === $location && false === $lifter_purchase ) {
				return;
			}
			if ( $posted_data ) {

				$cap_type       = isset( $this->loginpress_settings['recaptcha_type'] ) ? $this->loginpress_settings['recaptcha_type'] : 'v2-robot';
				$cap_permission = isset( $this->loginpress_settings['enable_repatcha'] ) ? $this->loginpress_settings['enable_repatcha'] : 'off';

				if ( $cap_permission || ( isset( $_POST['g-recaptcha-response'] ) && ! empty( sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

					if ( 'v3' === $cap_type ) {

						$good_score = $this->loginpress_settings['good_score'];
						$score      = $lp_recaptcha->loginpress_v3_recaptcha_verifier();

						if ( $score < $good_score ) {
							return new WP_Error( 'lifterlms_user_data_invalid', __( '<b>Error:</b> Please verify reCAPTCHA', 'loginpress-pro' ) );
						}
					} else {
						$response = $lp_recaptcha->loginpress_recaptcha_verifier();
						if ( $response->isSuccess() ) {
							return $valid;
						}
						if ( ! $response->isSuccess() ) {
							return new WP_Error( 'lifterlms_user_data_invalid', __( '<b>Error:</b> Please verify reCAPTCHA', 'loginpress-pro' ) );
						}
					}
				}
			} else {
				return $valid;
			}
		}


		/**
		 * Authenticate reCAPTCHA on the LifterLMS lost password page.
		 *
		 * @param WP_Error $err The error object.
		 * @param WP_User  $user The user object.
		 * @return WP_Error The error object with reCAPTCHA validation result.
		 * @since 5.0.0
		 */
		public function loginpress_auth_recaptcha_lostpassword_llms( $err, $user ) {
			if ( $user && isset( $_POST['llms_login'] ) ) { // phpcs:ignore
				$lp_recaptcha   = LoginPress_Recaptcha::instance();
				$cap_type       = isset( $this->loginpress_settings['recaptcha_type'] ) ? $this->loginpress_settings['recaptcha_type'] : 'v2-robot';
				$cap_permission = isset( $this->loginpress_settings['enable_repatcha'] ) ? $this->loginpress_settings['enable_repatcha'] : 'off';

				if ( $cap_permission || ( isset( $_POST['g-recaptcha-response'] ) && ! empty( sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

					if ( 'v3' === $cap_type ) {

						$good_score = $this->loginpress_settings['good_score'];
						$score      = $lp_recaptcha->loginpress_v3_recaptcha_verifier();

						if ( $score < $good_score ) {
							$err->add( 'llms_pass_reset_email_failure', __( '<b>Error:</b> Please verify reCAPTCHA', 'loginpress-pro' ) );
							return $err;
						}
					} else {
						$response = $lp_recaptcha->loginpress_recaptcha_verifier();
						if ( $response->isSuccess() ) {
							return $err;
						}
						if ( ! $response->isSuccess() ) {
							$err->add( 'llms_pass_reset_email_failure', __( '<b>Error:</b> Please verify reCAPTCHA', 'loginpress-pro' ) );
							return $err;
						}
					}
				}
			} else {
				return $err;
			}
		}

		/**
		 * Adds turnstile field to LifterLMS login fields.
		 *
		 * @param array $fields LifterLMS login fields.
		 * @return array LifterLMS login fields with turnstile added.
		 * @since 5.0.0
		 */
		public function loginpress_add_turnstile_to_lifter_login_fields( $fields ) {
			ob_start();

			/* Cloudflare CAPTCHA Settings */
			$lp_turnstile = LoginPress_Turnstile::instance();
			$lp_turnstile->loginpress_turnstile_field( 'lifterlms' );
			$lp_turnstile->loginpress_turnstile_script();
			$turnstile_field = ob_get_clean();

			$turnstile_field = array(
				'id'          => 'turnstile-lifter',
				'type'        => 'html',
				'description' => $turnstile_field,

			);
			array_splice( $fields, count( $fields ) - 3, 0, array( $turnstile_field ) );

			return $fields;
		}


		/**
		 * Adds turnstile field to LifterLMS register fields.
		 *
		 * @return void
		 * @since 5.0.0
		 */
		public function loginpress_add_turnstile_to_lifter_register_fields() {

			ob_start();

			/* Cloudflare CAPTCHA Settings. */
			$lp_turnstile = LoginPress_Turnstile::instance();
			$lp_turnstile->loginpress_turnstile_field( 'lifterlms' );
			$lp_turnstile->loginpress_turnstile_script();
			$turnstile_field = ob_get_clean();

			llms_form_field(
				array(
					'id'          => 'turnstile-lifter',
					'type'        => 'html',
					'description' => $turnstile_field,

				)
			);
		}

		/**
		 * Enables turnstile on the LifterLMS login form.
		 *
		 * @param array $creds The current validation status of the form.
		 * @return array|void The updated validation status of the form.
		 * @since 5.0.0
		 * @version 6.1.0
		 */
		public function loginpress_llms_login_form_turnstile_enable( $creds ) {

			// Retrieve the secret key from the plugin settings.
			$secret_key = isset( $this->loginpress_settings['secret_key_cf'] ) ? $this->loginpress_settings['secret_key_cf'] : '';
			// Sanitize the Turnstile response from the form submission.
			$response = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			// Verify the Turnstile response with Cloudflare's siteverify API.
			$verify_response = wp_remote_post(
				'https://challenges.cloudflare.com/turnstile/v0/siteverify',
				array(
					'body' => array(
						'secret'   => $secret_key,         // Your secret key.
						'response' => $response,           // Captcha response from user.
						'remoteip' => $this->get_address(), // User's IP address.
					),
				)
			);

			// Retrieve and decode the API response.
			$response_body = wp_remote_retrieve_body( $verify_response );
			$result        = json_decode( $response_body, true );
			if ( empty( $result['success'] ) ) {
				add_filter( 'lifterlms_user_login_errors', array( $this, 'llla_login_lifterlms_turnstile_error_callback' ) );
			} else {
				return $creds;
			}
		}

		/**
		 * Adds turnstile field to LifterLMS lost password fields.
		 *
		 * @param array $fields LifterLMS lost password fields.
		 * @return array LifterLMS lost password fields with turnstile added.
		 * @since 5.0.0
		 */
		public function loginpress_add_turnstile_to_lifter_lostpass_fields( $fields ) {

			ob_start();
			$lp_turnstile = LoginPress_Turnstile::instance();
			$lp_turnstile->loginpress_turnstile_field( 'lifterlms' );
			$lp_turnstile->loginpress_turnstile_script();
			$turnstile_field = ob_get_clean();

			$turnstile_field =
					array(
						array(
							'id'          => 'turnstile-lifter',
							'type'        => 'html',
							'description' => $turnstile_field,

						),
						array(
							'id'    => 'turnstile-lifter2',
							'type'  => 'hidden',
							'value' => do_action( 'lifterlms_lost_do_action' ),
						),
					);
			array_splice( $fields, count( $fields ) - 1, 0, $turnstile_field );

			return $fields;
		}

		/**
		 * Enables turnstile on the LifterLMS register form.
		 *
		 * @param mixed  $valid The current validation status of the form.
		 * @param array  $posted_data The data submitted via the form.
		 * @param string $location The location of the form.
		 * @return mixed The updated validation status of the form.
		 * @since 5.0.0
		 * @version 6.1.0
		 */
		public function loginpress_llms_register_form_turnstile_enable( $valid, $posted_data, $location ) {
			$lifter_register = isset( $this->settings['enable_captcha_llms']['lifter_register_form'] ) ? $this->settings['enable_captcha_llms']['lifter_register_form'] : false;
			$lifter_purchase = isset( $this->settings['enable_captcha_llms']['lifter_purchase_form'] ) ? $this->settings['enable_captcha_llms']['lifter_purchase_form'] : false;
			if ( 'registration' === $location && false === $lifter_register ) {
				return;
			}
			if ( 'checkout' === $location && false === $lifter_purchase ) {
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
							'remoteip' => $this->get_address(), // User's IP address.
						),
					)
				);

					// Retrieve and decode the API response.
					$response_body = wp_remote_retrieve_body( $verify_response );
					$result        = json_decode( $response_body, true );
				if ( empty( $result['success'] ) ) {
					return new WP_Error( 'lifterlms_user_data_invalid', __( '<b>Error:</b> Please verify the captcha', 'loginpress-pro' ) );
				} else {
					return $valid;
				}
			} else {
				return $valid;
			}
		}

		/**
		 * Authenticate turnstile on the LifterLMS lost password page.
		 *
		 * @param WP_Error $err The error object.
		 * @param WP_User  $user The user object.
		 * @return WP_Error The error object with turnstile validation result.
		 * @since 5.0.0
		 * @version 6.1.0
		 */
		public function loginpress_auth_turnstile_lostpassword_llms( $err, $user ) {
			if ( $user && isset( $_POST['_lost_password_nonce'] ) ) { // phpcs:ignore
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
							'remoteip' => $this->get_address(), // User's IP address.
						),
					)
				);

					// Retrieve and decode the API response.
					$response_body = wp_remote_retrieve_body( $verify_response );
					$result        = json_decode( $response_body, true );
				if ( empty( $result['success'] ) ) {
					$err->add( 'llms_pass_reset_email_failure', __( '<b>Error:</b> Please verify captcha', 'loginpress-pro' ) );
					return $err;
				} else {
					return $err;
				}
			} else {
				return $err;
			}
		}

		/**
		 * Adds hCaptcha field to LifterLMS login fields.
		 *
		 * @param array $fields LifterLMS login fields.
		 * @return array LifterLMS login fields with hCaptcha added.
		 * @since 5.0.0
		 */
		public function loginpress_add_hcaptcha_to_lifter_login_fields( $fields ) {
			ob_start();
			/* LoginPress_Hcaptcha CAPTCHA Settings */
			$lp_hcaptcha = LoginPress_Hcaptcha::instance();
			$lp_hcaptcha->loginpress_hcaptcha_field();
			$lp_hcaptcha->loginpress_hcaptcha_enqueue( 'llms' );
			$hcaptcha_field = ob_get_clean();

			$hcaptcha_field = array(
				'id'          => 'hcaptcha-lifter',
				'type'        => 'html',
				'description' => $hcaptcha_field,

			);
			array_splice( $fields, count( $fields ) - 3, 0, array( $hcaptcha_field ) );

			return $fields;
		}

		/**
		 * Enable hCaptcha on LifterLMS login form.
		 *
		 * This function checks if hCaptcha is enabled and if the user has entered a valid response.
		 * If the response is invalid, it adds a filter to display an error message.
		 *
		 * @param array $creds An array containing the user's login credentials.
		 * @return array|void The user credentials or void.
		 * @since 5.0.0
		 * @version 6.1.0
		 */
		public function loginpress_llms_login_form_hcaptcha_enable( $creds ) {
			if ( $creds ) {
				$lp_recaptcha    = LoginPress_Hcaptcha::instance();
				$hcap_secret_key = isset( $this->loginpress_settings['hcaptcha_secret_key'] ) ? $this->loginpress_settings['hcaptcha_secret_key'] : '';
				if ( isset( $_POST['h-captcha-response'] ) || isset( $_POST['captcha_response'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$post_response = isset( $_POST['h-captcha-response'] ) ? sanitize_text_field( wp_unslash( $_POST['h-captcha-response'] ) ) : sanitize_text_field( wp_unslash( $_POST['captcha_response'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$response      = wp_remote_post(
						'https://hcaptcha.com/siteverify',
						array(
							'body' => array(
								'secret'   => $hcap_secret_key,
								'response' => $post_response,
								'remoteip' => $this->get_address(),
							),
						)
					);

					$response_body = wp_remote_retrieve_body( $response );
					$result        = json_decode( $response_body );

					if ( ! $result->success ) {
						add_filter( 'lifterlms_user_login_errors', array( $this, 'llla_login_lifterlms_hcaptcha_error_callback' ) );
					} else {
						return $creds;
					}
				} elseif ( ( isset( $_POST['wp-submit'] ) || isset( $_POST['login'] ) ) && ! isset( $_POST['h-captcha-response'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					add_filter( 'lifterlms_user_login_errors', array( $this, 'llla_login_lifterlms_hcaptcha_error_callback' ) );
				}
			} else {
				return $creds;
			}
		}

		/**
		 * Adds hCaptcha field to LifterLMS register fields.
		 *
		 * @return void
		 * @since 5.0.0
		 */
		public function loginpress_add_hcaptcha_to_lifter_register_fields() {
			ob_start();

			/* Cloudflare CAPTCHA Settings. */
			$lp_hcaptcha = LoginPress_Hcaptcha::instance();
			$lp_hcaptcha->loginpress_hcaptcha_field();
			$lp_hcaptcha->loginpress_hcaptcha_enqueue( 'llms-reg' );
			$hcaptcha_field = ob_get_clean();

			llms_form_field(
				array(
					'id'          => 'hcaptcha-lifter',
					'type'        => 'html',
					'description' => $hcaptcha_field,

				)
			);
		}

		/**
		 * Enables hCaptcha on the LifterLMS register form.
		 *
		 * @param mixed  $valid The current validation status of the form.
		 * @param array  $posted_data The data submitted via the form.
		 * @param string $location The location of the form.
		 * @return mixed The updated validation status of the form.
		 * @since 5.0.0
		 * @version 6.1.0
		 */
		public function loginpress_llms_register_form_hcaptcha_enable( $valid, $posted_data, $location ) {
			$lifter_register = isset( $this->settings['enable_captcha_llms']['lifter_register_form'] ) ? $this->settings['enable_captcha_llms']['lifter_register_form'] : false;
			$lifter_purchase = isset( $this->settings['enable_captcha_llms']['lifter_purchase_form'] ) ? $this->settings['enable_captcha_llms']['lifter_purchase_form'] : false;
			if ( 'registration' === $location && false === $lifter_register ) {
				return;
			}
			if ( 'checkout' === $location && false === $lifter_purchase ) {
				return;
			}
			if ( $posted_data ) {
				$hcap_secret_key = isset( $this->loginpress_settings['hcaptcha_secret_key'] ) ? $this->loginpress_settings['hcaptcha_secret_key'] : '';
				if ( isset( $_POST['h-captcha-response'] ) || isset( $_POST['captcha_response'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$response = wp_remote_post(
						'https://hcaptcha.com/siteverify',
						array(
							'body' => array(
								'secret'   => $hcap_secret_key,
								'response' => sanitize_text_field( wp_unslash( $_POST['h-captcha-response'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
								'remoteip' => $this->get_address(),
							),
						)
					);

					$response_body = wp_remote_retrieve_body( $response );
					$result        = json_decode( $response_body );

					if ( ! $result->success ) {
						return new WP_Error( 'lifterlms_user_data_invalid', __( '<b>Error:</b> Please verify the Hcaptcha', 'loginpress-pro' ) );
					}
				} elseif ( ! isset( $_POST['h-captcha-response'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					return new WP_Error( 'lifterlms_user_data_invalid', __( '<b>Error:</b> Please verify the Hcaptcha', 'loginpress-pro' ) );
				}
			} else {
				return $valid;
			}
		}

		/**
		 * Adds hCaptcha field to LifterLMS lost password fields.
		 *
		 * @param array $fields LifterLMS lost password fields.
		 * @return array LifterLMS lost password fields with hCaptcha added.
		 * @since 5.0.0
		 */
		public function loginpress_add_hcaptcha_to_lifter_lostpass_fields( $fields ) {
			ob_start();
			$lp_hcaptcha = LoginPress_Hcaptcha::instance();
			$lp_hcaptcha->loginpress_hcaptcha_field();
			$lp_hcaptcha->loginpress_hcaptcha_enqueue();
			$hcaptcha_field = ob_get_clean();

			$hcaptcha_field =
					array(
						array(
							'id'          => 'hcaptcha-lifter',
							'type'        => 'html',
							'description' => $hcaptcha_field,

						),
						array(
							'id'    => 'hcaptcha-lifter2',
							'type'  => 'hidden',
							'value' => do_action( 'lifterlms_lost_do_action' ),
						),
					);
			array_splice( $fields, count( $fields ) - 1, 0, $hcaptcha_field );
			return $fields;
		}

		/**
		 * Authenticate hCaptcha on the LifterLMS lost password page.
		 *
		 * @param WP_Error $err The error object.
		 * @param WP_User  $user The user object.
		 * @return WP_Error The error object with hCaptcha validation result.
		 * @since 5.0.0
		 * @version 6.1.0
		 */
		public function loginpress_auth_hcaptcha_lostpassword_llms( $err, $user ) {

			if ( $user && isset( $_POST['_lost_password_nonce'] ) ) { // phpcs:ignore
				$hcap_secret_key = isset( $this->loginpress_settings['hcaptcha_secret_key'] ) ? $this->loginpress_settings['hcaptcha_secret_key'] : '';
				$cap_response    = isset( $_POST['captcha_response'] ) ? sanitize_text_field( wp_unslash( $_POST['captcha_response'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( isset( $_POST['h-captcha-response'] ) || $cap_response ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$response = wp_remote_post(
						'https://hcaptcha.com/siteverify',
						array(
							'body' => array(
								'secret'   => $hcap_secret_key,
								'response' => $cap_response ? $cap_response : sanitize_text_field( wp_unslash( $_POST['h-captcha-response'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
								'remoteip' => $this->get_address(),
							),
						)
					);

					$response_body = wp_remote_retrieve_body( $response );
					$result        = json_decode( $response_body );

					if ( ! $result->success ) {
						$err->add( 'llms_pass_reset_email_failure', __( '<b>Error:</b> Please verify hCaptcha', 'loginpress-pro' ) );
						return $err;
					}
				} elseif ( ! isset( $_POST['h-captcha-response'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$err->add( 'llms_pass_reset_email_failure', __( '<b>Error:</b> Please verify hCaptcha', 'loginpress-pro' ) );
					return $err;
				}
			} else {
				return $err;
			}
		}
	}
}
