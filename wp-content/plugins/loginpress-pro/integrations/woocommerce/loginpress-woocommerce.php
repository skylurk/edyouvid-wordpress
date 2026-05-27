<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * WooCommerce Integration.
 *
 * @package LoginPress-Pro
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LoginPress WooCommerce Integration Class.
 *
 * Handles the integration of LoginPress features with the WooCommerce platform.
 * This includes social login positioning, captcha integration, and other WooCommerce-specific features.
 *
 * @package LoginPress-Pro
 * @since 5.0.0
 * @version 6.1.0
 */
class LoginPress_Woocommerce_Integration {


	/**
	 * The settings array.
	 *
	 * @var array
	 * @since 5.0.0
	 */
	public $settings;

	/**
	 * The attempts settings array.
	 *
	 * @var array
	 * @since 5.0.0
	 */
	public $attempts_settings;

	/**
	 * The table name for login attempts.
	 *
	 * @var string
	 * @since 5.0.0
	 */
	public $llla_table;

	/**
	 * Variable that checks for LoginPress settings.
	 *
	 * @var string
	 * @since 5.0.0
	 */
	public $loginpress_settings;

	/**
	 * Variable that contains position of social login on WooCommerce login form.
	 *
	 * @var string
	 * @since 5.0.0
	 */
	public $social_position_woo_lf;

	/**
	 * Variable that contains position of social login on WooCommerce register form.
	 *
	 * @var string
	 * @since 5.0.0
	 */
	public $social_position_woo_rf;

	/**
	 * Variable that contains position of social login on WooCommerce checkout form.
	 *
	 * @var string
	 * @since 5.0.0
	 */
	public $social_position_woo_co;

	/**
	 * The constructor.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function __construct() {
		$this->settings               = get_option( 'loginpress_integration_settings' );
		$this->loginpress_settings    = get_option( 'loginpress_captcha_settings' );
		$this->social_position_woo_lf = isset( $this->settings['social_position_woo_lf'] ) ? $this->settings['social_position_woo_lf'] : 'default';
		$this->social_position_woo_rf = isset( $this->settings['social_position_woo_rf'] ) ? $this->settings['social_position_woo_rf'] : 'default';
		$this->social_position_woo_co = isset( $this->settings['social_position_woo_co'] ) ? $this->settings['social_position_woo_co'] : 'default';
		$this->woo_hooks();
	}

	/**
	 * Register WooCommerce-related hooks for LoginPress.
	 *
	 * This function binds LoginPress functionality with WooCommerce by hooking into
	 * relevant actions and filters provided by the WooCommerce plugin.
	 * Useful for customizing or enhancing the WooCommerce login and registration flows.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function woo_hooks() {
		$enable_social_woo_lf = isset( $this->settings['enable_social_woo_lf'] ) ? $this->settings['enable_social_woo_lf'] : '';
		$enable_social_woo_rf = isset( $this->settings['enable_social_woo_rf'] ) ? $this->settings['enable_social_woo_rf'] : '';
		$enable_social_woo_co = isset( $this->settings['enable_social_woo_co'] ) ? $this->settings['enable_social_woo_co'] : '';

		$woo_captcha_login    = isset( $this->settings['enable_captcha_woo']['woocommerce_login_form'] ) ? $this->settings['enable_captcha_woo']['woocommerce_login_form'] : false;
		$woo_captcha_register = isset( $this->settings['enable_captcha_woo']['woocommerce_register_form'] ) ? $this->settings['enable_captcha_woo']['woocommerce_register_form'] : false;
		$woo_captcha_co       = isset( $this->settings['enable_captcha_woo']['woocommerce_checkout_form'] ) ? $this->settings['enable_captcha_woo']['woocommerce_checkout_form'] : false;
		$captchas_enabled     = isset( $this->loginpress_settings['enable_captchas'] ) ? $this->loginpress_settings['enable_captchas'] : 'off';
		$addons               = get_option( 'loginpress_pro_addons' );
		if ( isset( $addons['social-login']['is_active'] ) && $addons['social-login']['is_active'] ) {
			if ( ! class_exists( 'LoginPress_Social' ) ) {
				require_once LOGINPRESS_PRO_ROOT_PATH . '/addons/social-login/social-login.php';
			}

			if ( 'off' !== $enable_social_woo_lf ) {
				if ( 'above' === $this->social_position_woo_lf || 'above_separator' === $this->social_position_woo_lf ) {
					add_action( 'woocommerce_login_form_start', array( $this, 'loginpress_render_social_woo_login_above' ) );
				} elseif ( 'default' === $this->social_position_woo_lf || 'below' === $this->social_position_woo_lf ) {
					add_action( 'woocommerce_login_form_end', array( $this, 'loginpress_render_social_woo_login_below' ) );
				}
			}

			if ( 'off' !== $enable_social_woo_rf ) {
				if ( 'above' === $this->social_position_woo_rf || 'above_separator' === $this->social_position_woo_rf ) {
					add_action( 'woocommerce_register_form_start', array( $this, 'loginpress_render_social_woo_register_above' ) );
				} elseif ( 'default' === $this->social_position_woo_rf || 'below' === $this->social_position_woo_rf ) {
					add_action( 'woocommerce_register_form_end', array( $this, 'loginpress_render_social_woo_register_below' ) );
				}
			}

			if ( 'off' !== $enable_social_woo_co ) {
				if ( 'above' === $this->social_position_woo_co || 'above_separator' === $this->social_position_woo_co ) {
					add_action( 'woocommerce_before_checkout_billing_form', array( $this, 'loginpress_render_social_woo_checkout_above' ) );
				} elseif ( 'default' === $this->social_position_woo_co || 'below' === $this->social_position_woo_co ) {
					add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'loginpress_render_social_woo_checkout_below' ) );
				}
			}
		}

		if ( 'off' !== $captchas_enabled ) {
			$captchas_type = isset( $this->loginpress_settings['captchas_type'] ) ? $this->loginpress_settings['captchas_type'] : 'type_recaptcha';
			if ( 'type_cloudflare' === $captchas_type ) {
				$loginpress_turnstile = LoginPress_Turnstile::instance();
				/* Cloudflare CAPTCHA Settings */
				$cf_site_key   = isset( $this->loginpress_settings['site_key_cf'] ) ? $this->loginpress_settings['site_key_cf'] : '';
				$cf_secret_key = isset( $this->loginpress_settings['secret_key_cf'] ) ? $this->loginpress_settings['secret_key_cf'] : '';
				$validated     = isset( $this->loginpress_settings['validate_cf'] ) && 'on' === $this->loginpress_settings['validate_cf'] ? true : false;
				if ( ! empty( $cf_site_key ) && ! empty( $cf_secret_key ) && $validated ) {
					if ( $woo_captcha_login ) {
						add_action( 'woocommerce_login_form', array( $loginpress_turnstile, 'loginpress_turnstile_field' ) );
						add_action( 'woocommerce_login_form', array( $loginpress_turnstile, 'loginpress_turnstile_script' ) );
						add_filter( 'woocommerce_process_login_errors', array( $this, 'loginpress_turnstile_auth' ) );
					}
					if ( $woo_captcha_register ) {
						add_action( 'woocommerce_register_form', array( $loginpress_turnstile, 'loginpress_turnstile_field' ) );
						add_action( 'woocommerce_register_form', array( $loginpress_turnstile, 'loginpress_turnstile_script' ) );
						add_filter( 'woocommerce_process_registration_errors', array( $this, 'loginpress_turnstile_woo_reg_auth' ), 10, 3 );
					}
					if ( $woo_captcha_co ) {
						add_action( 'woocommerce_after_order_notes', array( $this, 'loginpress_turnstile_field' ) );
						// Add Turnstile script for WooCommerce checkout form.
						add_filter( 'woocommerce_checkout_process', array( $this, 'loginpress_turnstile_woo_reg_auth' ) );
					}
				}
			} elseif ( 'type_recaptcha' === $captchas_type ) {
				$cap_type     = isset( $this->loginpress_settings['recaptcha_type'] ) ? $this->loginpress_settings['recaptcha_type'] : 'v2-robot';
				$lp_recaptcha = LoginPress_Recaptcha::instance();
				/* Add reCAPTCHA on registration form */
				if ( $woo_captcha_register ) {
					add_action( 'woocommerce_register_form', array( $lp_recaptcha, 'loginpress_recaptcha_script' ) );
					add_action( 'woocommerce_register_form', array( $lp_recaptcha, 'loginpress_recaptcha_field' ) );
					add_filter( 'woocommerce_process_registration_errors', array( $lp_recaptcha, 'loginpress_recaptcha_registration_auth' ), 10, 3 );
				} elseif ( isset( $_POST['woocommerce-register-nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					// Return from reCaptcha if woocommerce registration nonce set.
					return;
				}
				if ( $woo_captcha_login ) {
					add_action( 'woocommerce_login_form', array( $lp_recaptcha, 'loginpress_recaptcha_script' ) );
					add_action( 'woocommerce_login_form', array( $lp_recaptcha, 'loginpress_recaptcha_field' ) );
					add_filter( 'woocommerce_process_login_errors', array( $this, 'loginpress_recaptcha_wc_auth' ), 10, 3 );
				} elseif ( isset( $_POST['woocommerce-login-nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					// Return from reCaptcha if woocommerce login nonce set.
					return;
				}
				if ( $woo_captcha_co ) {
					add_action( 'woocommerce_after_order_notes', array( $this, 'loginpress_recaptcha_script' ) );
					add_filter( 'woocommerce_checkout_process', array( $this, 'loginpress_recaptcha_wc_checkout_auth' ) );
				} elseif ( isset( $_POST['woocommerce-login-nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					// Return from reCaptcha if woocommerce login nonce set.
					return;
				}
			} elseif ( 'type_hcaptcha' === $captchas_type ) {
				$lp_hcaptcha     = LoginPress_Hcaptcha::instance();
				$hcap_site_key   = isset( $this->loginpress_settings['hcaptcha_site_key'] ) ? $this->loginpress_settings['hcaptcha_site_key'] : '';
				$hcap_secret_key = isset( $this->loginpress_settings['hcaptcha_secret_key'] ) ? $this->loginpress_settings['hcaptcha_secret_key'] : '';
				$hcaptcha_type   = isset( $this->loginpress_settings['hcaptcha_type'] ) ? $this->loginpress_settings['hcaptcha_type'] : 'normal';
				if ( ! empty( $hcap_site_key ) && ! empty( $hcap_secret_key ) && isset( $this->loginpress_settings['hcaptcha_verified'] ) && 'on' === $this->loginpress_settings['hcaptcha_verified'] ) {
					if ( $woo_captcha_login ) {
						add_action( 'woocommerce_login_form', array( $lp_hcaptcha, 'loginpress_hcaptcha_field' ) );
						add_action( 'woocommerce_login_form', array( $lp_hcaptcha, 'loginpress_hcaptcha_enqueue' ) );
						add_filter( 'woocommerce_process_login_errors', array( $lp_hcaptcha, 'loginpress_hcaptcha_auth' ), 99, 3 );
					}

					if ( $woo_captcha_register ) {
						add_action( 'woocommerce_register_form', array( $lp_hcaptcha, 'loginpress_hcaptcha_field' ) );
						add_action( 'woocommerce_register_form', array( $lp_hcaptcha, 'loginpress_hcaptcha_enqueue' ) );
						add_filter( 'woocommerce_register_post', array( $lp_hcaptcha, 'loginpress_hcaptcha_registration_auth' ), 10, 3 );
					}
					if ( $woo_captcha_co ) {
						add_action( 'woocommerce_after_order_notes', array( $this, 'loginpress_hcaptcha_field' ) );
						// Add hCaptcha verification for WooCommerce checkout form.
						add_filter( 'woocommerce_checkout_process', array( $this, 'loginpress_hcaptcha_co_auth' ) );
					}
				}
			}
		}
	}

	/**
	 * Adds social login above the WooCommerce login fields.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function loginpress_render_social_woo_login_above() {
		LoginPress_Social::instance()->loginpress_social_login();

		if ( 'above_separator' === $this->social_position_woo_lf ) {
			echo '<span class="social-sep"><span>' . esc_html( apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) ) ) . '</span></span>';
		}
	}

	/**
	 * Adds social login below the WooCommerce login fields.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function loginpress_render_social_woo_login_below() {

		if ( 'default' === $this->social_position_woo_lf ) {
			echo '<span class="social-sep"><span>' . esc_html( apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) ) ) . '</span></span>';
		}

		LoginPress_Social::instance()->loginpress_social_login();
	}

	/**
	 * Adds social login above the WooCommerce register fields.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function loginpress_render_social_woo_register_above() {
		LoginPress_Social::instance()->loginpress_social_login();

		if ( 'above_separator' === $this->social_position_woo_rf ) {
			echo '<span class="social-sep"><span>' . esc_html( apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) ) ) . '</span></span>';
		}
	}

	/**
	 * Adds social login below the WooCommerce register fields.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function loginpress_render_social_woo_register_below() {

		if ( 'default' === $this->social_position_woo_rf ) {
			echo '<span class="social-sep"><span>' . esc_html( apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) ) ) . '</span></span>';
		}

		LoginPress_Social::instance()->loginpress_social_login();
	}

	/**
	 * Adds social login above the WooCommerce checkout fields.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function loginpress_render_social_woo_checkout_above() {
		LoginPress_Social::instance()->loginpress_social_login();

		if ( 'above_separator' === $this->social_position_woo_co ) {
			echo '<span class="social-sep"><span>' . esc_html( apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) ) ) . '</span></span>';
		}
	}

	/**
	 * Adds social login below the WooCommerce checkout fields.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function loginpress_render_social_woo_checkout_below() {

		if ( 'default' === $this->social_position_woo_co ) {
			echo '<span class="social-sep"><span>' . esc_html( apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) ) ) . '</span></span>';
		}

		LoginPress_Social::instance()->loginpress_social_login();
	}

	/**
	 * Verify CAPTCHA using a common API call.
	 *
	 * @param string $url     The verification API endpoint.
	 * @param string $secret  The secret key.
	 * @param string $token   The user response token.
	 * @return array|WP_Error Response from wp_remote_post.
	 * @since 5.0.0
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
	 * Verify the user's Turnstile response and authenticate the user.
	 *
	 * @param WP_User|WP_Error|null $validation_error The user object, WP_Error on failure, or null if not authenticated.
	 * @return WP_User|WP_Error The user object on success, or WP_Error on failure.
	 * @since 4.0.0
	 */
	public function loginpress_turnstile_auth( $validation_error ) {
		$loginpress_turnstile = LoginPress_Turnstile::instance();
		// Retrieve the secret key from the plugin settings.
		$secret_key = isset( $this->loginpress_settings['secret_key_cf'] ) ? $this->loginpress_settings['secret_key_cf'] : '';
		// Sanitize the Turnstile response from the form submission.
		$response = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// If no response is received, return a captcha error.
		if ( ! $response ) {
			$validation_error->add( 'captcha_error', esc_html__( 'Please wait for the captcha to complete.', 'loginpress-pro' ) );
		}
		// Verify the Turnstile response with Cloudflare's siteverify API.
		$verify_response = $this->loginpress_verify_captcha(
			'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			$secret_key,
			$response
		);

		// Retrieve and decode the API response.
		$response_body = wp_remote_retrieve_body( $verify_response );
		$result        = json_decode( $response_body, true );
		if ( empty( $result['success'] ) ) {
			$validation_error->add( 'captcha_error', esc_html( $loginpress_turnstile->loginpress_turnstile_error() ) );
		}

		// If everything is valid, return the user object for successful login.
		return $validation_error;
	}

	/**
	 * Verify the user's Turnstile response and authenticate the user for WooCommerce registration.
	 *
	 * @param WP_User|WP_Error|null $user     The user object, WP_Error on failure, or null if not authenticated.
	 * @param string                $username The username.
	 * @param string                $password The password.
	 * @param string                $form_type The form type (e.g., login, register, lostpassword, etc.).
	 * @return WP_User|WP_Error The user object on success, or WP_Error on failure.
	 * @since 4.0.0
	 */
	public function loginpress_turnstile_woo_reg_auth( $user = null, $username = '', $password = '', $form_type = '' ) {
		if ( ! isset( $_POST['woocommerce-register-nonce'] ) && ! isset( $_POST['woocommerce-process-checkout-nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return $user;
		}
		$loginpress_turnstile = LoginPress_Turnstile::instance();
		// Retrieve the secret key from the plugin settings.
		$secret_key = isset( $this->loginpress_settings['secret_key_cf'] ) ? $this->loginpress_settings['secret_key_cf'] : '';
		// Sanitize the Turnstile response from the form submission.
		$response = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// If no response is received, return a captcha error.
		if ( ! $response ) {
			if ( ( 'login' !== $form_type || 'register' !== $form_type ) && false === strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ), '/wp-login.php' ) ) {
				return new WP_Error( 'captcha_error', esc_html__( 'Please wait for the captcha to complete.', 'loginpress-pro' ) );
			}
		}

		// Verify the Turnstile response with Cloudflare's siteverify API.
		$verify_response = $this->loginpress_verify_captcha(
			'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			$secret_key,
			$response
		);

		// Retrieve and decode the API response.
		$response_body = wp_remote_retrieve_body( $verify_response );
		$result        = json_decode( $response_body, true );
		if ( empty( $result['success'] ) ) {
			wc_add_notice( esc_html( $loginpress_turnstile->loginpress_turnstile_error() ), 'error' );
			return new WP_Error( 'captcha_error', esc_html( $loginpress_turnstile->loginpress_turnstile_error() ) );
		}

		// If everything is valid, return the user object for successful login.
		return $user;
	}

	/**
	 * WooCommerce Login Form reCAPTCHA Authentication.
	 *
	 * @param object $validation_error The validation error.
	 * @param string $username The username.
	 * @param string $password The password.
	 * @return object $validation_error The validation error.
	 * @since 4.0.0
	 * @version 5.0.0
	 */
	public function loginpress_recaptcha_wc_auth( $validation_error, $username, $password ) {
		$lp_recaptcha_instance = LoginPress_Recaptcha::instance();
		$cap_type              = isset( $this->loginpress_settings['recaptcha_type'] ) ? $this->loginpress_settings['recaptcha_type'] : 'v2-robot';
		$cap_permission        = isset( $this->loginpress_settings['enable_repatcha'] ) ? $this->loginpress_settings['enable_repatcha'] : 'off';

		if ( $cap_permission || ( isset( $_POST['g-recaptcha-response'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( 'v3' === $cap_type ) {

				$good_score = $this->loginpress_settings['good_score'];
				$score      = $lp_recaptcha_instance->loginpress_v3_recaptcha_verifier();

				if ( $username && $password && $score < $good_score ) {
					$validation_error->add( 'recaptcha_error', esc_html( $lp_recaptcha_instance->loginpress_recaptcha_error() ) );
				}
			} else {
				$response = $lp_recaptcha_instance->loginpress_recaptcha_verifier();
				if ( $username && $password && ! $response->isSuccess() ) {
					$validation_error->add( 'recaptcha_error', esc_html( $lp_recaptcha_instance->loginpress_recaptcha_error() ) );
				}
			}
		} elseif ( ! isset( $_POST['g-recaptcha-response'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$validation_error->add( 'recaptcha_error', esc_html( $lp_recaptcha_instance->loginpress_recaptcha_error() ) );
		}

		return $validation_error;
	}

	/**
	 * WooCommerce Login Form reCAPTCHA Authentication.
	 *
	 * @param object $validation_error The validation error.
	 * @return object $validation_error The validation error.
	 * @since 4.0.0
	 * @version 5.0.0
	 */
	public function loginpress_recaptcha_wc_checkout_auth( $validation_error ) {
		if ( ! isset( $_POST['woocommerce-process-checkout-nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return $validation_error;
		}
		$lp_recaptcha_instance = LoginPress_Recaptcha::instance();
		$cap_type              = isset( $this->loginpress_settings['recaptcha_type'] ) ? $this->loginpress_settings['recaptcha_type'] : 'v2-robot';
		$cap_permission        = isset( $this->loginpress_settings['enable_repatcha'] ) ? $this->loginpress_settings['enable_repatcha'] : 'off';

		if ( $cap_permission || ( isset( $_POST['g-recaptcha-response'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( 'v3' === $cap_type ) {

				$good_score = $this->loginpress_settings['good_score'];
				$score      = $lp_recaptcha_instance->loginpress_v3_recaptcha_verifier();

				if ( $score < $good_score ) {
					wc_add_notice( esc_html( $lp_recaptcha_instance->loginpress_recaptcha_error() ), 'error' );
				}
			} else {
				$response = $lp_recaptcha_instance->loginpress_recaptcha_verifier();
				if ( ! $response->isSuccess() ) {
					wc_add_notice( esc_html( $lp_recaptcha_instance->loginpress_recaptcha_error() ), 'error' );
				}
			}
		} elseif ( ! isset( $_POST['g-recaptcha-response'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			wc_add_notice( esc_html( $lp_recaptcha_instance->loginpress_recaptcha_error() ), 'error' );
		}

		return $validation_error;
	}

	/**
	 * Class Methods for handling Cloudflare Turnstile integration.
	 *
	 * @return void
	 * @since 4.0.0
	 */
	public function loginpress_turnstile_field() {
		$lp_turnstile = LoginPress_Turnstile::instance();
		$lp_turnstile->loginpress_turnstile_field( 'woo-co' );
		$lp_turnstile->loginpress_turnstile_script();
	}

	/**
	 * Class Methods for adding hCaptcha to WooCommerce forms.
	 *
	 * @return void
	 * @since 4.0.0
	 */
	public function loginpress_hcaptcha_field() {
		/* Cloudflare CAPTCHA Settings */
		$lp_hcaptcha = LoginPress_Hcaptcha::instance();
		$lp_hcaptcha->loginpress_hcaptcha_field();
		$lp_hcaptcha->loginpress_hcaptcha_enqueue( 'woo' );
	}

	/**
	 * Class Methods for adding reCAPTCHA to WooCommerce forms.
	 *
	 * @return void
	 * @since 4.0.0
	 */
	public function loginpress_recaptcha_script() {
		$lp_recaptcha_instance = LoginPress_Recaptcha::instance();
		$lp_recaptcha_instance->loginpress_recaptcha_field();
		$lp_recaptcha_instance->loginpress_recaptcha_script();
	}

	/**
	 * Class Methods for validating hCaptcha response on WooCommerce checkout form.
	 *
	 * @param object $user The user object.
	 * @return object The user object.
	 * @since 4.0.0
	 */
	public function loginpress_hcaptcha_co_auth( $user ) {
		if ( ! isset( $_POST['woocommerce-process-checkout-nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return $user;
		}
		$lp_recaptcha    = LoginPress_Hcaptcha::instance();
		$hcap_secret_key = isset( $this->loginpress_settings['hcaptcha_secret_key'] ) ? $this->loginpress_settings['hcaptcha_secret_key'] : '';
		if ( isset( $_POST['h-captcha-response'] ) || isset( $_POST['captcha_response'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$post_response = isset( $_POST['h-captcha-response'] ) ? sanitize_text_field( wp_unslash( $_POST['h-captcha-response'] ) ) : sanitize_text_field( wp_unslash( $_POST['captcha_response'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$response      = $this->loginpress_verify_captcha(
				'https://hcaptcha.com/siteverify',
				$hcap_secret_key,
				$post_response
			);

			$response_body = wp_remote_retrieve_body( $response );
			$result        = json_decode( $response_body );
			if ( ! $result->success ) {
				wc_add_notice( esc_html( $lp_recaptcha->loginpress_hcaptcha_error() ), 'error' );
			} else {
				return $user;
			}
		} elseif ( ( isset( $_POST['wp-submit'] ) || isset( $_POST['login'] ) ) && ! isset( $_POST['h-captcha-response'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			wc_add_notice( esc_html( $lp_recaptcha->loginpress_hcaptcha_error() ), 'error' );
		}
		return $user;
	}
}
new LoginPress_Woocommerce_Integration();
