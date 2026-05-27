<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * LoginPress Easy Digital Downloads Integration.
 *
 * Handles the integration of LoginPress features with the Easy Digital Downloads platform.
 * This includes social login positioning, captcha integration, and other EDD-specific features.
 *
 * @package LoginPress-Pro
 * @since 5.0.0
 */

/**
 * LoginPress Easy Digital Downloads Integration file.
 *
 * Prevent direct access.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Handles the integration of LoginPress features with the EDD platform.
 *
 * @since 5.0.0
 */
class LoginPress_Easy_Digital_Downloads_Integration {

	/**
	 * The settings array.
	 *
	 * @var array
	 * @since 5.0.0
	 */
	public $settings;

	/**
	 * Variable that checks for LoginPress settings.
	 *
	 * @var array
	 * @since 5.0.0
	 */
	public $loginpress_settings;

	/**
	 * Variable that stores position of social login on EDD login form.
	 *
	 * @var string
	 * @since 5.0.0
	 */
	public $social_position_edd_lf;

	/**
	 * Variable that stores position of social login on EDD register form.
	 *
	 * @var string
	 * @since 5.0.0
	 */
	public $social_position_edd_rf;

	/**
	 * Variable that stores position of social login on EDD checkout form.
	 *
	 * @var string
	 * @since 5.0.0
	 */
	public $social_position_edd_co;

	/**
	 * The constructor.
	 *
	 * @since 5.0.0
	 * @return void
	 */
	public function __construct() {
		$this->settings               = get_option( 'loginpress_integration_settings' );
		$this->loginpress_settings    = get_option( 'loginpress_captcha_settings' );
		$this->social_position_edd_lf = isset( $this->settings['social_position_edd_lf'] ) ? $this->settings['social_position_edd_lf'] : 'default';
		$this->social_position_edd_rf = isset( $this->settings['social_position_edd_rf'] ) ? $this->settings['social_position_edd_rf'] : 'default';
		$this->social_position_edd_co = isset( $this->settings['social_position_edd_co'] ) ? $this->settings['social_position_edd_co'] : 'default';
		$this->loginpress_pro_edd_hooks();
	}

	/**
	 * Register EDD-related hooks for LoginPress.
	 *
	 * This function binds LoginPress functionality with EDD by hooking into
	 * relevant actions and filters provided by the EDD plugin.
	 * Useful for customizing or enhancing the EDD login and registration flows.
	 *
	 * @since 5.0.0
	 * @return void
	 */
	public function loginpress_pro_edd_hooks() {
		$enable_social_edd_lf = isset( $this->settings['enable_social_edd_lf'] ) ? $this->settings['enable_social_edd_lf'] : '';
		$enable_social_edd_rf = isset( $this->settings['enable_social_edd_rf'] ) ? $this->settings['enable_social_edd_rf'] : '';
		$enable_social_edd_co = isset( $this->settings['enable_social_edd_co'] ) ? $this->settings['enable_social_edd_co'] : '';

		$edd_captcha_register = isset( $this->settings['enable_captcha_edd']['register_edd_block'] ) ? $this->settings['enable_captcha_edd']['register_edd_block'] : false;
		$edd_captcha_co       = isset( $this->settings['enable_captcha_edd']['checkout_edd_block'] ) ? $this->settings['enable_captcha_edd']['checkout_edd_block'] : false;
		$edd_captcha_login    = isset( $this->settings['enable_captcha_edd']['login_edd_block'] ) ? $this->settings['enable_captcha_edd']['login_edd_block'] : false;
		$captchas_enabled     = isset( $this->loginpress_settings['enable_captchas'] ) ? $this->loginpress_settings['enable_captchas'] : 'off';
		$addons               = get_option( 'loginpress_pro_addons' );
		if ( isset( $addons['social-login']['is_active'] ) && $addons['social-login']['is_active'] ) {
			if ( ! class_exists( 'LoginPress_Social' ) ) {
				require_once LOGINPRESS_PRO_ROOT_PATH . '/addons/social-login/social-login.php';
			}

			// Social login for EDD login form.
			if ( 'off' !== $enable_social_edd_lf ) {
				if ( 'above' === $this->social_position_edd_lf || 'above_separator' === $this->social_position_edd_lf ) {
					add_action( 'edd_login_fields_before', array( $this, 'loginpress_social_edd_login_above' ) );
				} elseif ( 'default' === $this->social_position_edd_lf || 'below' === $this->social_position_edd_lf ) {
					add_action( 'edd_login_fields_after', array( $this, 'loginpress_social_edd_login_below' ) );
				}
			}

			// Social login for EDD register form.
			if ( 'off' !== $enable_social_edd_rf ) {
				if ( 'above' === $this->social_position_edd_rf || 'above_separator' === $this->social_position_edd_rf ) {
					add_action( 'edd_register_form_fields_before', array( $this, 'loginpress_social_edd_register_above' ) );
				} elseif ( 'default' === $this->social_position_edd_rf || 'below' === $this->social_position_edd_rf ) {
					add_action( 'edd_register_form_fields_after', array( $this, 'loginpress_social_edd_register_below' ) );
				}
			}

			// Social login for EDD checkout form.
			if ( 'off' !== $enable_social_edd_co ) {
				if ( 'above' === $this->social_position_edd_co || 'above_separator' === $this->social_position_edd_co ) {
					add_action( 'edd_before_purchase_form', array( $this, 'loginpress_social_edd_checkout_above' ) );
				} elseif ( 'default' === $this->social_position_edd_co || 'below' === $this->social_position_edd_co ) {
					add_action( 'edd_purchase_form_after_submit', array( $this, 'loginpress_social_edd_checkout_below' ) );
				}
			}
		}

		if ( 'off' !== $captchas_enabled ) {
			$captchas_type = isset( $this->loginpress_settings['captchas_type'] ) ? $this->loginpress_settings['captchas_type'] : 'type_recaptcha';
			if ( 'type_cloudflare' === $captchas_type ) {

				/* Cloudflare CAPTCHA Settings */
				$cf_site_key   = isset( $this->loginpress_settings['site_key_cf'] ) ? $this->loginpress_settings['site_key_cf'] : '';
				$cf_secret_key = isset( $this->loginpress_settings['secret_key_cf'] ) ? $this->loginpress_settings['secret_key_cf'] : '';
				$validated     = isset( $this->loginpress_settings['validate_cf'] ) && 'on' === $this->loginpress_settings['validate_cf'] ? true : false;
				if ( ! empty( $cf_site_key ) && ! empty( $cf_secret_key ) && $validated ) {
					if ( $edd_captcha_login ) {
						// Priority is 0 so that the captcha is above the social login.
						add_filter( 'edd_login_fields_after', array( $this, 'loginpress_add_turnstile_to_edd_register_fields' ), 0 );
						add_action( 'edd_user_login', array( $this, 'loginpress_edd_login_form_turnstile_enable' ), 0 ); // Priority is 0 so that the captcha is validated before all.
					}
					if ( $edd_captcha_register ) {
						// Priority is 0 so that the captcha is above the social login.
						add_filter( 'edd_register_form_fields_after', array( $this, 'loginpress_add_turnstile_to_edd_register_fields' ), 0 );
						add_filter( 'edd_process_register_form', array( $this, 'loginpress_edd_register_form_turnstile_enable' ), 10, 3 );
					}
					if ( $edd_captcha_co ) {
						// Priority is 9999 so that the captcha is validated after all.
						add_filter( 'edd_purchase_form_before_submit', array( $this, 'loginpress_add_turnstile_to_edd_register_fields' ), 10 );
						add_filter( 'edd_checkout_error_checks', array( $this, 'loginpress_edd_co_form_turnstile_enable' ), 9999 );
					}
				}
			} elseif ( 'type_recaptcha' === $captchas_type ) {
				/* Add reCAPTCHA on registration form */
				if ( $edd_captcha_login ) {
					// Priority is 0 so that the captcha is above the social login.
					add_filter( 'edd_login_fields_after', array( $this, 'loginpress_add_recaptcha_to_edd_register' ), 0 );
					add_action( 'edd_user_login', array( $this, 'loginpress_edd_register_form_captcha_enable' ), 0 );
				}
				if ( $edd_captcha_register ) {
					// Priority is 0 so that the captcha is above the social login.
					add_filter( 'edd_register_form_fields_after', array( $this, 'loginpress_add_recaptcha_to_edd_register' ), 0 );
					add_filter( 'edd_process_register_form', array( $this, 'loginpress_edd_register_form_captcha_enable' ), 10, 3 );
				}
				if ( $edd_captcha_co ) {
					// Priority is 0 so that the captcha is above the social login.
					add_filter( 'edd_purchase_form_before_submit', array( $this, 'loginpress_add_recaptcha_to_edd_register' ), 0 );
					add_filter( 'edd_checkout_error_checks', array( $this, 'loginpress_edd_co_form_captcha_enable' ), 10 );
				}
			} elseif ( 'type_hcaptcha' === $captchas_type ) {
				$hcap_site_key   = isset( $this->loginpress_settings['hcaptcha_site_key'] ) ? $this->loginpress_settings['hcaptcha_site_key'] : '';
				$hcap_secret_key = isset( $this->loginpress_settings['hcaptcha_secret_key'] ) ? $this->loginpress_settings['hcaptcha_secret_key'] : '';

				if ( ! empty( $hcap_site_key ) && ! empty( $hcap_secret_key ) && isset( $this->loginpress_settings['hcaptcha_verified'] ) && 'on' === $this->loginpress_settings['hcaptcha_verified'] ) {
					if ( $edd_captcha_login ) {
						// Priority is 0 so that the captcha is above the social login.
						add_filter( 'edd_login_fields_after', array( $this, 'loginpress_add_hcaptcha_to_edd_register_fields' ), 0 );
						add_action( 'edd_user_login', array( $this, 'loginpress_edd_register_form_hcaptcha_enable' ), 11 );
					}
					if ( $edd_captcha_register ) {
						// Priority is 0 so that the captcha is above the social login.
						add_filter( 'edd_register_form_fields_after', array( $this, 'loginpress_add_hcaptcha_to_edd_register_fields' ), 0 );
						add_action( 'edd_process_register_form', array( $this, 'loginpress_edd_register_form_hcaptcha_enable' ), 10, 3 );
					}
					if ( $edd_captcha_co ) {
						// Priority is 0 so that the captcha is above the social login.
						add_filter( 'edd_purchase_form_before_submit', array( $this, 'loginpress_add_hcaptcha_to_edd_register_fields' ), 0 );
						add_filter( 'edd_checkout_error_checks', array( $this, 'loginpress_edd_register_form_hcaptcha_enable' ), 10 );
					}
				}
			}
		}
	}

	/**
	 * Add the separator text between social login buttons and the form.
	 *
	 * @since 5.0.0
	 * @return void
	 */
	public function loginpress_social_login_separator() {
		$separator_text = apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) );
		echo "<span class='social-sep'><span>" . esc_html( $separator_text ) . '</span></span>';
	}

	/**
	 * Adds social login above the EDD login fields.
	 *
	 * @since 5.0.0
	 * @return void
	 */
	public function loginpress_social_edd_login_above() {
		$loginpress_social = LoginPress_Social::instance();
		$loginpress_social->loginpress_social_login();

		if ( 'above_separator' === $this->social_position_edd_lf ) {
			$this->loginpress_social_login_separator();
		}
	}

	/**
	 * Adds social login below the EDD login fields.
	 *
	 * @since 5.0.0
	 * @return void
	 */
	public function loginpress_social_edd_login_below() {
		$loginpress_social = LoginPress_Social::instance();

		if ( 'default' === $this->social_position_edd_lf ) {
			$this->loginpress_social_login_separator();
		}

		$loginpress_social->loginpress_social_login();
	}

	/**
	 * Adds social login above the EDD register fields.
	 *
	 * @since 5.0.0
	 * @return void
	 */
	public function loginpress_social_edd_register_above() {
		$loginpress_social = LoginPress_Social::instance();
		$loginpress_social->loginpress_social_login();

		if ( 'above_separator' === $this->social_position_edd_rf ) {
			$this->loginpress_social_login_separator();
		}
	}

	/**
	 * Adds social login below the EDD register fields.
	 *
	 * @since 5.0.0
	 * @return void
	 */
	public function loginpress_social_edd_register_below() {
		$loginpress_social = LoginPress_Social::instance();

		if ( 'default' === $this->social_position_edd_rf ) {
			$this->loginpress_social_login_separator();
		}

		$loginpress_social->loginpress_social_login();
	}

	/**
	 * Adds social login above the EDD checkout fields.
	 *
	 * @since 5.0.0
	 * @return void
	 */
	public function loginpress_social_edd_checkout_above() {
		$loginpress_social = LoginPress_Social::instance();
		$loginpress_social->loginpress_social_login();

		if ( 'above_separator' === $this->social_position_edd_co ) {
			$this->loginpress_social_login_separator();
		}
	}

	/**
	 * Adds social login below the EDD checkout fields.
	 *
	 * @since 5.0.0
	 * @return void
	 */
	public function loginpress_social_edd_checkout_below() {
		$loginpress_social = LoginPress_Social::instance();

		if ( 'default' === $this->social_position_edd_co ) {
			$this->loginpress_social_login_separator();
		}

		$loginpress_social->loginpress_social_login();
	}

	/**
	 * Verify CAPTCHA using a common API call.
	 *
	 * @param string $url     The verification API endpoint.
	 * @param string $secret  The secret key.
	 * @param string $token   The user response token.
	 *
	 * @return array|WP_Error Response from wp_remote_post.
	 */
	public function loginpress_verify_captcha( $url, $secret, $token ) {
		return wp_remote_post(
			esc_url_raw( $url ),
			array(
				'body' => array(
					'secret'   => sanitize_text_field( $secret ),
					'response' => sanitize_text_field( $token ),
					'remoteip' => sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
				),
			)
		);
	}

	/**
	 * Adds turnstile field to EDD login fields.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function loginpress_add_turnstile_to_edd_register_fields() {

		/* Cloudflare CAPTCHA Settings. */
		$lp_turnstile = LoginPress_Turnstile::instance();
		$lp_turnstile->loginpress_turnstile_field( 'edd' ); // Use correct integration key.
		$lp_turnstile->loginpress_turnstile_script();
	}

	/**
	 * Enables turnstile on the EDD login form.
	 *
	 * @param mixed $result The current validation status of the form.
	 * @return mixed The updated validation status of the form.
	 * @since 5.0.0
	 */
	public function loginpress_edd_login_form_turnstile_enable( $result ) {
		// Retrieve the secret key from the plugin settings.
		$secret_key = isset( $this->loginpress_settings['secret_key_cf'] ) ? $this->loginpress_settings['secret_key_cf'] : '';

		// Sanitize the Turnstile response from the form submission.
		$response = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// If no response is received, return a captcha error.
		if ( empty( $response ) ) {
			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			edd_set_error( 'turnstile_error', __( 'Please wait for the captcha to complete.', 'loginpress-pro' ) );
			edd_redirect( esc_url( add_query_arg( array(), home_url( $request_uri ) ) ) );
		}

		// Verify the Turnstile response with Cloudflare's siteverify API.
		$verify_response = $this->loginpress_verify_captcha(
			'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			$secret_key,
			$response
		);

		// Retrieve and decode the API response.
		$response_body = wp_remote_retrieve_body( $verify_response );
		$result_data   = json_decode( $response_body, true );
		if ( isset( $result_data['success'] ) && ! empty( $result_data['success'] ) ) {
			return $result;
		}
		$lp_turnstile = LoginPress_Turnstile::instance();
		$request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		edd_set_error( 'turnstile_error', wp_kses_post( $lp_turnstile->loginpress_turnstile_error() ) );
		edd_redirect( esc_url( add_query_arg( array(), home_url( $request_uri ) ) ) );
	}

	/**
	 * Enables turnstile on the EDD register form.
	 *
	 * @return mixed The updated validation status of the form.
	 * @since 5.0.0
	 */
	public function loginpress_edd_register_form_turnstile_enable() {

		// Retrieve the secret key from the plugin settings.
		$secret_key = isset( $this->loginpress_settings['secret_key_cf'] ) ? $this->loginpress_settings['secret_key_cf'] : '';

		// Sanitize the Turnstile response from the form submission.
		$response = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// If no response is received, return a captcha error.
		if ( empty( $response ) ) {
			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			edd_set_error( 'turnstile_error', __( 'Please wait for the captcha to complete.', 'loginpress-pro' ) );
			edd_redirect( esc_url( add_query_arg( array(), home_url( $request_uri ) ) ) );
		}

		// Verify the Turnstile response with Cloudflare's siteverify API.
		$verify_response = $this->loginpress_verify_captcha(
			'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			$secret_key,
			$response
		);

		// Retrieve and decode the API response.
		$response_body = wp_remote_retrieve_body( $verify_response );
		$result_data   = json_decode( $response_body, true );

		if ( empty( $result_data['success'] ) ) {
			$lp_turnstile = LoginPress_Turnstile::instance();
			$request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			edd_set_error( 'turnstile_error', wp_kses_post( $lp_turnstile->loginpress_turnstile_error() ) );
			edd_redirect( esc_url( add_query_arg( array(), home_url( $request_uri ) ) ) );
		}
	}

	/**
	 * Enables turnstile on the EDD checkout form.
	 *
	 * @return mixed The updated validation status of the form.
	 * @since 5.0.0
	 */
	public function loginpress_edd_co_form_turnstile_enable() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return null; // Skip validation during AJAX requests.
		}
		// Retrieve the secret key from the plugin settings.
		$secret_key = isset( $this->loginpress_settings['secret_key_cf'] ) ? $this->loginpress_settings['secret_key_cf'] : '';

		// Sanitize the Turnstile response from the form submission.
		$response = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// If no response is received, return a captcha error.
		if ( empty( $response ) ) {
			edd_set_error( 'turnstile_error', __( 'Please wait for the captcha to complete.', 'loginpress-pro' ) );
		}

		// Verify the Turnstile response with Cloudflare's siteverify API.
		$verify_response = $this->loginpress_verify_captcha(
			'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			$secret_key,
			$response
		);

		// Retrieve and decode the API response.
		$response_body = wp_remote_retrieve_body( $verify_response );
		$result_data   = json_decode( $response_body, true );

		if ( empty( $result_data['success'] ) ) {
			$lp_turnstile = LoginPress_Turnstile::instance();
			edd_set_error( 'turnstile_error', wp_kses_post( $lp_turnstile->loginpress_turnstile_error() ) );
		}

		return null;
	}

	/**
	 * Adds reCAPTCHA field to EDD register fields.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function loginpress_add_recaptcha_to_edd_register() {
		$lp_recaptcha = LoginPress_Recaptcha::instance();
		$lp_recaptcha->loginpress_recaptcha_field();
		$lp_recaptcha->loginpress_recaptcha_script();
	}

	/**
	 * Enables reCAPTCHA on the EDD checkout form.
	 *
	 * @param mixed $result The current validation status of the form.
	 * @return mixed The updated validation status of the form.
	 * @since 5.0.0
	 */
	public function loginpress_edd_register_form_captcha_enable( $result ) {
		$lp_recaptcha = LoginPress_Recaptcha::instance();
		$cap_type     = isset( $this->loginpress_settings['recaptcha_type'] ) ? $this->loginpress_settings['recaptcha_type'] : 'v2-robot';
		$request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		if ( isset( $_POST['g-recaptcha-response'] ) && ! empty( sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( 'v3' === $cap_type ) {

				$good_score = $this->loginpress_settings['good_score'];
				$score      = $lp_recaptcha->loginpress_v3_recaptcha_verifier();
				if ( $score < $good_score ) {
					edd_set_error( 'recaptcha_error', __( 'Please verify reCAPTCHA', 'loginpress-pro' ) );
					edd_redirect( esc_url( add_query_arg( array(), home_url( $request_uri ) ) ) );
					return null;
				}
			} else {
				$response = $lp_recaptcha->loginpress_recaptcha_verifier();
				if ( $response->isSuccess() ) {
					return $result;
				}
				if ( ! $response->isSuccess() ) {
					edd_set_error( 'recaptcha_error', __( 'Please verify reCAPTCHA', 'loginpress-pro' ) );
					edd_redirect( esc_url( add_query_arg( array(), home_url( $request_uri ) ) ) );
					return null;
				}
			}
			return $result;
		}
		edd_set_error( 'recaptcha_error', __( 'Please verify reCAPTCHA', 'loginpress-pro' ) );
		edd_redirect( esc_url( add_query_arg( array(), home_url( $request_uri ) ) ) );
		return null;
	}

	/**
	 * Enables reCAPTCHA on the EDD checkout form.
	 *
	 * @return mixed The updated validation status of the form.
	 * @since 5.0.0
	 */
	public function loginpress_edd_co_form_captcha_enable() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return null; // Skip validation during AJAX requests.
		}
		$lp_recaptcha = LoginPress_Recaptcha::instance();
		$cap_type     = isset( $this->loginpress_settings['recaptcha_type'] ) ? $this->loginpress_settings['recaptcha_type'] : 'v2-robot';

		if ( isset( $_POST['g-recaptcha-response'] ) && ! empty( sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( 'v3' === $cap_type ) {

				$good_score = $this->loginpress_settings['good_score'];
				$score      = $lp_recaptcha->loginpress_v3_recaptcha_verifier();

				if ( $score < $good_score ) {
					edd_set_error( 'recaptcha_error', __( 'Please verify reCAPTCHA', 'loginpress-pro' ) );
				}
			} else {
				$response = $lp_recaptcha->loginpress_recaptcha_verifier();
				if ( $response->isSuccess() ) {
					return null;
				}
				if ( ! $response->isSuccess() ) {
					edd_set_error( 'recaptcha_error', __( 'Please verify reCAPTCHA', 'loginpress-pro' ) );
				}
			}
			return null;
		}
		edd_set_error( 'recaptcha_error', __( 'Please verify reCAPTCHA', 'loginpress-pro' ) );
	}

	/**
	 * Adds hCaptcha field to EDD login fields.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function loginpress_add_hcaptcha_to_edd_register_fields() {

		$lp_hcaptcha = LoginPress_Hcaptcha::instance();
		$lp_hcaptcha->loginpress_hcaptcha_field();
		$lp_hcaptcha->loginpress_hcaptcha_enqueue( 'edd' );
	}

	/**
	 * Enables hCaptcha on the EDD checkout form.
	 *
	 * @return mixed The updated validation status of the form.
	 * @since 5.0.0
	 */
	public function loginpress_edd_register_form_hcaptcha_enable() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return null; // Skip validation during AJAX requests.
		}
		$hcap_secret_key = isset( $this->loginpress_settings['hcaptcha_secret_key'] ) ? $this->loginpress_settings['hcaptcha_secret_key'] : '';
		$request_uri     = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		if ( isset( $_POST['h-captcha-response'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

			$response = $this->loginpress_verify_captcha(
				'https://hcaptcha.com/siteverify',
				$hcap_secret_key,
				sanitize_text_field( wp_unslash( $_POST['h-captcha-response'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			);

			$response_body = wp_remote_retrieve_body( $response );
			$result_data   = json_decode( $response_body );
			if ( ! $result_data->success ) {
				edd_set_error( 'hcaptcha_error', esc_html__( 'Please verify hcaptcha.', 'loginpress-pro' ) );
				edd_redirect( esc_url( add_query_arg( array(), home_url( $request_uri ) ) ) );
				return false;
			} else {
				edd_unset_error( 'hcaptcha_error' ); // Clear error before registration continues.
				return true;
			}
		} elseif ( ! isset( $_POST['h-captcha-response'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			edd_set_error( 'hcaptcha_error', esc_html__( 'Please verify hcaptcha.', 'loginpress-pro' ) );
			edd_redirect( esc_url( add_query_arg( array(), home_url( $request_uri ) ) ) );
			return false;
		}
		return true;
	}
}

new LoginPress_Easy_Digital_Downloads_Integration();
