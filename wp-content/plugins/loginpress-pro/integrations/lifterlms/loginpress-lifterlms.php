<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * LoginPress LifterLMS Integration file.
 *
 * Prevent direct access.
 *
 * @package LoginPress-Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LifterLMS Integration
 *
 * @since 5.0.0
 * @version 6.1.0
 */
if ( is_plugin_inactive( 'lifterlms/lifterlms.php' ) && ! is_plugin_active_for_network( 'lifterlms/lifterlms.php' ) ) {
	exit;
}

require_once LOGINPRESS_PRO_ROOT_PATH . '/addons/limit-login-attempts/classes/class-attempts.php';
require_once LOGINPRESS_PRO_ROOT_PATH . '/classes/captcha/loginpress-recaptcha.php';
require_once LOGINPRESS_PRO_ROOT_PATH . '/integrations/lifterlms/captcha-trait.php';
require_once LOGINPRESS_PRO_ROOT_PATH . '/integrations/lifterlms/redirect-trait.php';

/**
 * Handles the integration of LoginPress features with the LifterLMS platform.
 *
 * @since 5.0.0
 * @version 6.1.0
 */
class LoginPress_LifterLMS_Integration extends LoginPress_Attempts {
	use LoginPress_Lifter_Captcha_Trait;
	use LoginPress_Lifter_Redirect_Trait;

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
	 * The table name.
	 *
	 * @var string
	 * @since 5.0.0
	 */
	public $llla_table;

	/**
	 * Variable that checks for LoginPress settings.
	 *
	 * @var array
	 * @since 5.0.0
	 */
	public $loginpress_settings;

	/**
	 * Variable that contains position of social login on LifterLMS login form.
	 *
	 * @var string
	 * @since 5.0.0
	 */
	public $social_position_llms_lf;

	/**
	 * Variable that contains position of social login on LifterLMS register form.
	 *
	 * @var string
	 * @since 5.0.0
	 */
	public $social_position_llms_rf;

	/**
	 * Variable that contains position of social login on LifterLMS checkout form.
	 *
	 * @var string
	 * @since 5.0.0
	 */
	public $social_position_llms_co;

	/**
	 * The constructor.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->llla_table              = $wpdb->prefix . 'loginpress_limit_login_details';
		$this->attempts_settings       = get_option( 'loginpress_limit_login_attempts' );
		$this->settings                = get_option( 'loginpress_integration_settings' );
		$this->loginpress_settings     = get_option( 'loginpress_captcha_settings' );
		$this->social_position_llms_lf = isset( $this->settings['social_position_llms_lf'] ) ? $this->settings['social_position_llms_lf'] : 'default';
		$this->social_position_llms_rf = isset( $this->settings['social_position_llms_rf'] ) ? $this->settings['social_position_llms_rf'] : 'default';
		$this->social_position_llms_co = isset( $this->settings['social_position_llms_co'] ) ? $this->settings['social_position_llms_co'] : 'default';
		$this->loginpress_llms_hooks();
	}

	/**
	 * Register LifterLMS-related hooks for LoginPress.
	 *
	 * This function binds LoginPress functionality with LifterLMS by hooking into
	 * relevant actions and filters provided by the LifterLMS plugin.
	 * Useful for customizing or enhancing the LifterLMS login and registration flows.
	 *
	 * @return void
	 * @since 6.0.0
	 */
	public function loginpress_llms_hooks() {

		$addons = get_option( 'loginpress_pro_addons' );
		if ( isset( $addons['limit-login-attempts']['is_active'] ) && $addons['limit-login-attempts']['is_active'] ) {
			add_filter( 'loginpress_llla_error_filter', array( $this, 'apply_filter_llms_login_errors_callback' ) );
			add_action( 'wp_loaded', array( $this, 'llla_llms_wp_loaded' ) );
		}
		$enable_lifterlms         = isset( $this->settings['enable_social_login_links_lifterlms'] ) ? $this->settings['enable_social_login_links_lifterlms'] : '';
		$lifterlms_purchase_reg   = isset( $enable_lifterlms['lifterlms_purchase_reg'] ) ? $enable_lifterlms['lifterlms_purchase_reg'] : '';
		$login_lifterlms_block    = isset( $enable_lifterlms['login_lifterlms_block'] ) ? $enable_lifterlms['login_lifterlms_block'] : '';
		$register_lifterlms_block = isset( $enable_lifterlms['register_lifterlms_block'] ) ? $enable_lifterlms['register_lifterlms_block'] : '';

		$enable_social_llms_lf = isset( $this->settings['enable_social_llms_lf'] ) ? $this->settings['enable_social_llms_lf'] : '';
		$enable_social_llms_rf = isset( $this->settings['enable_social_llms_rf'] ) ? $this->settings['enable_social_llms_rf'] : '';
		$enable_social_llms_co = isset( $this->settings['enable_social_llms_co'] ) ? $this->settings['enable_social_llms_co'] : '';
		if ( isset( $addons['social-login']['is_active'] ) && $addons['social-login']['is_active'] ) {
			if ( ! class_exists( 'LoginPress_Social' ) ) {
				require_once LOGINPRESS_PRO_ROOT_PATH . '/addons/social-login/social-login.php';
			}

			// Checkout Form Social.
			if ( 'off' !== $enable_social_llms_co ) {
				if ( 'above' === $this->social_position_llms_co || 'above_separator' === $this->social_position_llms_co ) {
					add_action( 'llms_checkout_footer_before', array( $this, 'loginpress_social_checkout_above' ) );
				} elseif ( 'default' === $this->social_position_llms_co || 'below' === $this->social_position_llms_co ) {
					add_action( 'llms_checkout_footer_after', array( $this, 'loginpress_social_checkout_below' ) );
				}
			}

			// Login Form Social.
			if ( 'off' !== $enable_social_llms_lf ) {
				if ( 'above' === $this->social_position_llms_lf || 'above_separator' === $this->social_position_llms_lf ) {
					add_action( 'lifterlms_login_form_start', array( $this, 'loginpress_social_login_above' ) );
				} elseif ( 'default' === $this->social_position_llms_lf || 'below' === $this->social_position_llms_lf ) {
					add_action( 'lifterlms_login_form_end', array( $this, 'loginpress_social_login_below' ) );
				}
			}

			// Register Form Social.
			if ( 'off' !== $enable_social_llms_rf ) {
				if ( 'above' === $this->social_position_llms_rf || 'above_separator' === $this->social_position_llms_rf ) {
					add_action( 'lifterlms_register_form_start', array( $this, 'loginpress_social_register_above' ) );
				} elseif ( 'default' === $this->social_position_llms_rf || 'below' === $this->social_position_llms_rf ) {
					add_action( 'lifterlms_register_form_end', array( $this, 'loginpress_social_register_below' ) );
				}
			}
		}
		$lifter_login     = isset( $this->settings['enable_captcha_llms']['lifter_login_form'] ) ? $this->settings['enable_captcha_llms']['lifter_login_form'] : false;
		$lifter_register  = isset( $this->settings['enable_captcha_llms']['lifter_register_form'] ) ? $this->settings['enable_captcha_llms']['lifter_register_form'] : false;
		$lifter_purchase  = isset( $this->settings['enable_captcha_llms']['lifter_purchase_form'] ) ? $this->settings['enable_captcha_llms']['lifter_purchase_form'] : false;
		$lifter_lostpass  = isset( $this->settings['enable_captcha_llms']['lifter_lostpassword_form'] ) ? $this->settings['enable_captcha_llms']['lifter_lostpassword_form'] : false;
		$captchas_enabled = isset( $this->loginpress_settings['enable_captchas'] ) ? $this->loginpress_settings['enable_captchas'] : 'off';

		if ( 'off' !== $captchas_enabled ) {
			$captchas_type = isset( $this->loginpress_settings['captchas_type'] ) ? $this->loginpress_settings['captchas_type'] : 'type_recaptcha';
			if ( 'type_cloudflare' === $captchas_type ) {

				/* Cloudflare CAPTCHA Settings. */
				$cf_site_key   = isset( $this->loginpress_settings['site_key_cf'] ) ? $this->loginpress_settings['site_key_cf'] : '';
				$cf_secret_key = isset( $this->loginpress_settings['secret_key_cf'] ) ? $this->loginpress_settings['secret_key_cf'] : '';
				$validated     = isset( $this->loginpress_settings['validate_cf'] ) && 'on' === $this->loginpress_settings['validate_cf'] ? true : false;
				if ( ! empty( $cf_site_key ) && ! empty( $cf_secret_key ) && $validated ) {
					if ( $lifter_login ) {
						add_filter( 'lifterlms_person_login_fields', array( $this, 'loginpress_add_turnstile_to_lifter_login_fields' ), 99 );
						add_action( 'lifterlms_login_credentials', array( $this, 'loginpress_llms_login_form_turnstile_enable' ) );
					}
					if ( $lifter_register ) {
						add_filter( 'lifterlms_before_registration_button', array( $this, 'loginpress_add_turnstile_to_lifter_register_fields' ), 99 );
						add_filter( 'lifterlms_user_registration_data', array( $this, 'loginpress_llms_register_form_turnstile_enable' ), 10, 3 );
					}
					if ( $lifter_purchase ) {
						add_action( 'llms_checkout_footer_before', array( $this, 'loginpress_add_turnstile_to_lifter_register_fields' ) );
						add_filter( 'lifterlms_user_registration_data', array( $this, 'loginpress_llms_register_form_turnstile_enable' ), 10, 3 );
					}
					if ( $lifter_lostpass ) {
						add_filter( 'lifterlms_lost_password_fields', array( $this, 'loginpress_add_turnstile_to_lifter_lostpass_fields' ) );
						add_action( 'lostpassword_post', array( $this, 'loginpress_auth_turnstile_lostpassword_llms' ), 10, 2 );
					}
				}
			} elseif ( 'type_recaptcha' === $captchas_type ) {

				/* Add reCAPTCHA on LifterLMS login form. */
				if ( $lifter_login ) {
					add_filter( 'lifterlms_person_login_fields', array( $this, 'loginpress_add_recaptcha_to_lifter_login_fields' ), 99 );
					add_action( 'llms_before_person_login_form', array( $this, 'loginpress_llms_form_script_callback' ) );
				}

				/* Add reCAPTCHA on registration form */
				if ( $lifter_register ) {
					add_action( 'lifterlms_before_registration_button', array( $this, 'loginpress_add_recaptcha_to_lifter_register' ) );
					add_action( 'lifterlms_before_person_register_form', array( $this, 'loginpress_llms_form_script_callback' ) );
				}

				/* Add reCAPTCHA on purchase form */
				if ( $lifter_purchase ) {
					add_action( 'llms_checkout_footer_before', array( $this, 'loginpress_add_recaptcha_to_lifter_register' ) );
					add_action( 'lifterlms_pre_checkout_form', array( $this, 'loginpress_llms_form_script_callback' ) );
				}

				/* Add reCAPTCHA on LifterLMS lost password form */
				if ( $lifter_lostpass ) {
					add_filter( 'lifterlms_lost_password_fields', array( $this, 'loginpress_add_recaptcha_to_lifter_lostpass_fields' ) );
					add_action( 'lifterlms_lost_do_action', array( $this, 'loginpress_llms_form_script_callback' ) );
				}

				/* Authentication reCAPTCHA on LifterLMS login form. */
				if ( ! isset( $_GET['customize_changeset_uuid'] ) && $lifter_login ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					add_action( 'lifterlms_login_credentials', array( $this, 'loginpress_llms_login_form_captcha_enable' ) );
				}

				/* Authentication reCAPTCHA on LifterLMS purchase and registration form. */
				if ( ! isset( $_GET['customize_changeset_uuid'] ) && ( $lifter_purchase || $lifter_register ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					add_filter( 'lifterlms_user_registration_data', array( $this, 'loginpress_llms_register_form_captcha_enable' ), 10, 3 );
				}

				/* Authentication reCAPTCHA on LifterLMS lost-password form. */
				if ( ! isset( $_GET['customize_changeset_uuid'] ) && $lifter_lostpass ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					add_action( 'lostpassword_post', array( $this, 'loginpress_auth_recaptcha_lostpassword_llms' ), 10, 2 );
				}
			} elseif ( 'type_hcaptcha' === $captchas_type ) {
				$hcap_site_key   = isset( $this->loginpress_settings['hcaptcha_site_key'] ) ? $this->loginpress_settings['hcaptcha_site_key'] : '';
				$hcap_secret_key = isset( $this->loginpress_settings['hcaptcha_secret_key'] ) ? $this->loginpress_settings['hcaptcha_secret_key'] : '';

				if ( ! empty( $hcap_site_key ) && ! empty( $hcap_secret_key ) && isset( $this->loginpress_settings['hcaptcha_verified'] ) && 'on' === $this->loginpress_settings['hcaptcha_verified'] ) {
					if ( $lifter_login ) {
						add_filter( 'lifterlms_person_login_fields', array( $this, 'loginpress_add_hcaptcha_to_lifter_login_fields' ), 99 );
						add_action( 'lifterlms_login_credentials', array( $this, 'loginpress_llms_login_form_hcaptcha_enable' ) );
					}
					if ( $lifter_register ) {
						add_filter( 'lifterlms_before_registration_button', array( $this, 'loginpress_add_hcaptcha_to_lifter_register_fields' ), 99 );
						add_filter( 'lifterlms_user_registration_data', array( $this, 'loginpress_llms_register_form_hcaptcha_enable' ), 10, 3 );
					}
					if ( $lifter_purchase ) {
						add_action( 'llms_checkout_footer_before', array( $this, 'loginpress_add_hcaptcha_to_lifter_register_fields' ) );
						add_filter( 'lifterlms_user_registration_data', array( $this, 'loginpress_llms_register_form_hcaptcha_enable' ), 10, 3 );
					}
					if ( $lifter_lostpass ) {
						add_filter( 'lifterlms_lost_password_fields', array( $this, 'loginpress_add_hcaptcha_to_lifter_lostpass_fields' ) );
						add_action( 'lostpassword_post', array( $this, 'loginpress_auth_hcaptcha_lostpassword_llms' ), 10, 2 );
					}
				}
			}
		}
		$membership_courses = new WP_Query(
			array(
				'post_type'      => array( 'course', 'llms_membership' ),
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			),
		);

		if ( ! $membership_courses->have_posts() ) {
			return;
		}

		if ( $membership_courses ) {

			add_action( 'admin_footer', array( $this, 'loginpress_localize_data_for_llms' ) );
			add_action( 'rest_api_init', array( $this, 'loginpress_llms_rest_api' ) );
			add_filter( 'login_redirect', array( $this, 'loginpress_redirects_after_login_llms' ), 12, 3 );
			add_filter( 'woocommerce_login_redirect', array( $this, 'loginpress_redirects_after_login_llms' ), 12, 2 );
			add_filter( 'lifterlms_login_redirect', array( $this, 'loginpress_redirects_after_login_llms' ), 12, 2 );
			add_action( 'clear_auth_cookie', array( $this, 'loginpress_redirects_after_logout' ), 12 );
		}
	}

	/**
	 * Adds social login above the LifterLMS checkout fields.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function loginpress_social_checkout_above() {
		$loginpress_social = LoginPress_Social::instance();
		$loginpress_social->loginpress_social_login();

		if ( 'above_separator' === $this->social_position_llms_co ) {
			$separator_text = apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) );
			echo "<span class='social-sep'><span>" . esc_html( $separator_text ) . '</span></span>';
		}
	}

	/**
	 * Adds social login below the LifterLMS checkout fields.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function loginpress_social_checkout_below() {
		if ( 'default' === $this->social_position_llms_co ) {
			$separator_text = apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) );
			echo "<span class='social-sep'><span>" . esc_html( $separator_text ) . '</span></span>';
		}
		$loginpress_social = LoginPress_Social::instance();
		$loginpress_social->loginpress_social_login();
	}

	/**
	 * Adds social login above the LifterLMS login fields.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function loginpress_social_login_above() {
		$loginpress_social = LoginPress_Social::instance();
		$loginpress_social->loginpress_social_login();

		if ( 'above_separator' === $this->social_position_llms_lf ) {
			$separator_text = apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) );
			echo "<span class='social-sep'><span>" . esc_html( $separator_text ) . '</span></span>';
		}
	}

	/**
	 * Adds social login below the LifterLMS login fields.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function loginpress_social_login_below() {
		if ( 'default' === $this->social_position_llms_lf ) {
			$separator_text = apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) );
			echo "<span class='social-sep'><span>" . esc_html( $separator_text ) . '</span></span>';
		}
		$loginpress_social = LoginPress_Social::instance();
		$loginpress_social->loginpress_social_login();
	}

	/**
	 * Adds social login above the LifterLMS register fields.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function loginpress_social_register_above() {
		$loginpress_social = LoginPress_Social::instance();
		$loginpress_social->loginpress_social_login();

		if ( 'above_separator' === $this->social_position_llms_rf ) {
			$separator_text = apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) );
			echo "<span class='social-sep'><span>" . esc_html( $separator_text ) . '</span></span>';
		}
	}

	/**
	 * Adds social login below the LifterLMS register fields.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function loginpress_social_register_below() {
		if ( 'default' === $this->social_position_llms_rf ) {
			$separator_text = apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) );
			echo "<span class='social-sep'><span>" . esc_html( $separator_text ) . '</span></span>';
		}
		$loginpress_social = LoginPress_Social::instance();
		$loginpress_social->loginpress_social_login();
	}


	/**
	 * Applies filter for LifterLMS login errors.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function apply_filter_llms_login_errors_callback() {
		add_filter( 'lifterlms_user_login_errors', array( $this, 'llla_login_lifterlms_callback' ) );
	}

	/**
	 * Attempts Login Authentication.
	 *
	 * @param object $user Object of the user.
	 * @param string $username Username.
	 * @param string $password Password.
	 * @return object|WP_Error User object on success, WP_Error on failure.
	 * @since 5.0.0
	 */
	public function llms_login_attempts_auth_callback( $user, $username, $password ) {

		if ( isset( $_POST['g-recaptcha-response'] ) && empty( sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}

		if ( $user instanceof WP_User ) {
			return $user;
		}

		if ( ! empty( $username ) && ! empty( $password ) ) {

			$error = new WP_Error();
			global $pagenow, $wpdb;

			$ip = $this->get_address();
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$whitelisted_ip = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->llla_table} WHERE ip = %s AND whitelist = %d", $ip, 1 ) );

			if ( $whitelisted_ip >= 1 ) {
				return;
			} elseif ( 'index.php' === $pagenow && class_exists( 'LifterLMS' ) ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedElseif
				// Empty block for LifterLMS check.
			} else {
				$error->add( 'llla_error', $this->limit_query( $username, $password ) );
			}

			return $error;
		}
	}

	/**
	 * Filter callback to add our login error messages to the LifterLMS login form.
	 *
	 * @param WP_Error $err Error object containing login errors.
	 * @return WP_Error Error object.
	 * @since 5.0.0
	 */
	public function llla_login_lifterlms_callback( $err ) {
		if ( isset( $_POST['llms_login'] ) && isset( $_POST['llms_password'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$username = sanitize_text_field( wp_unslash( $_POST['llms_login'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$password = sanitize_text_field( wp_unslash( $_POST['llms_password'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
		$err->remove( 'login-error' );
		$err->add( 'login-error', wp_kses_post( $this->limit_query( $username, $password ) ) );
		return $err;
	}

	/**
	 * LifterLMS Login Form Attempt Checker.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function llla_llms_wp_loaded() {

		global $pagenow, $wpdb;
		$ip               = $this->get_address();
		$blacklist_check = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$this->llla_table}` WHERE `ip` = %s AND `blacklist` = 1", $ip ) ); // @codingStandardsIgnoreLine.
		$current_time    = current_time( 'timestamp' ); // @codingStandardsIgnoreLine.
		$attempts_allowed = isset( $this->attempts_settings['attempts_allowed'] ) ? $this->attempts_settings['attempts_allowed'] : '';
		$minutes_lockout  = isset( $this->attempts_settings['minutes_lockout'] ) ? intval( $this->attempts_settings['minutes_lockout'] ) : '';
		$lockout_time     = $current_time - ( $minutes_lockout * 60 );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$attempt_time = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$this->llla_table}` WHERE `ip` = %s AND `datentime` > %s AND `whitelist` = 0", $ip, $lockout_time ) );

		// Limit LifterLMS Account access if attempts exceed.
		if ( 'index.php' === $pagenow && class_exists( 'LifterLMS' ) && $attempt_time === $attempts_allowed ) {
			add_action( 'llms_before_person_login_form', array( $this, 'lifterlms_attempt_error' ) );
			add_action( 'lifterlms_login_form_end', array( $this, 'lifterlms_attempt_error_return' ) );

		}

		// Limit LifterLMS Account access if blacklisted.
		if ( 'index.php' === $pagenow && class_exists( 'LifterLMS' ) && get_option( 'permalink_structure' ) && $blacklist_check >= 1 ) {
			add_action( 'llms_before_person_login_form', array( $this, 'lifterlms_blacklist_callback' ) );
		}

		// Retrieving the gateway to saved in db.
		if ( isset( $_POST['_llms_login_user_nonce'] ) ) {
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_llms_login_user_nonce'] ) ), 'llms_login_user' );
			add_filter( 'loginpress_gateway_passed', array( $this, 'loginpress_gaeteway_passed_filter' ) );
		}
	}

	/**
	 * Handles LifterLMS attempt error.
	 *
	 * Retrieves the last attempt time for the current IP address and displays an error message.
	 *
	 * @since 5.0.0
	 * @return void
	 */
	public function lifterlms_attempt_error() {
		global $wpdb;
		$ip = $this->get_address();

		$last_attempt_time = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$this->llla_table}` WHERE `ip` = %s ORDER BY `datentime` DESC", $ip ) ); // @codingStandardsIgnoreLine.
		if ( $last_attempt_time ) {
			$last_attempt_time = $last_attempt_time->datentime;
		}
		echo '<div class="llms-notice">';
		echo wp_kses_post( $this->loginpress_lockout_error( $last_attempt_time ) );
		echo '</div>';
	}

	/**
	 * Attempts error callback.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function lifterlms_attempt_error_return() {
		?>
		<style>
		.llms-person-login-form-wrapper, .llms-error {
			display: none;
		}
		</style>
		
		<?php
	}

	/**
	 * Blacklist error callback.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function lifterlms_blacklist_callback() {
		wp_die( esc_html__( 'You are blacklisted to access the Login Panel', 'loginpress-pro' ), 403 ); // @codingStandardsIgnoreLine.
	}

	/**
	 * Return LifterLMS as the gateway name in the loginpress db.
	 *
	 * @param string $gateway The current gateway name.
	 * @return string The modified gateway name.
	 * @since 5.0.0
	 */
	public function loginpress_gaeteway_passed_filter( $gateway ) {
		$gateway = esc_html__( 'lifterlms', 'loginpress-pro' );
		return $gateway;
	}

	/**
	 * Adds a JavaScript variable to the page which is used to signal that LoginPress
	 * should load the LifterLMS integration script.
	 *
	 * @return void
	 * @since 6.0.0
	 */
	public function loginpress_localize_data_for_llms() {
		?>
		<script>
			window.loginpressEnableLLMS = <?php echo wp_json_encode( true ); ?>;
		</script>
		<?php
	}
}

new LoginPress_LifterLMS_Integration();
