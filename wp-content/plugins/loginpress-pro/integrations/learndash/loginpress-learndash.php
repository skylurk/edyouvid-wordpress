<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * LoginPress LearnDash Integration
 *
 * @since 5.0.0
 * @version 6.1.0
 */

/**
 * LoginPress LearnDash Integration file.
 *
 * Prevent direct access.
 *
 * @package LoginPress-Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_plugin_inactive( 'sfwd-lms/sfwd_lms.php' ) && ! is_plugin_active_for_network( 'sfwd-lms/sfwd_lms.php' ) ) {
	exit;
}
require_once LOGINPRESS_PRO_ROOT_PATH . '/addons/limit-login-attempts/classes/class-attempts.php';
require_once LOGINPRESS_PRO_ROOT_PATH . '/addons/login-redirects/classes/class-redirects.php';
require_once LOGINPRESS_PRO_ROOT_PATH . '/integrations/learndash/captcha-trait.php';
require_once LOGINPRESS_PRO_ROOT_PATH . '/integrations/learndash/redirect-trait.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

/**
 * Handles the integration of LoginPress features with the LearnDash platform.
 *
 * @since 5.0.0
 */
class LoginPress_Learndash_Integration extends LoginPress_Attempts {
	use LoginPress_Learndash_Captcha_Trait;
	use LoginPress_Learndash_Redirects_Trait;

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
	 * The social settings array.
	 *
	 * @var array
	 * @since 5.0.0
	 */
	public $social_settings;

	/**
	 * The addons array.
	 *
	 * @var array
	 * @since 5.0.0
	 */
	public $addons;

	/**
	 * Variable that contains position of social login on LearnDash login form.
	 *
	 * @var string
	 * @since 5.0.0
	 */
	public $social_position_ld_lf;

	/**
	 * Variable that contains position of social login on LearnDash register form.
	 *
	 * @var string
	 * @since 5.0.0
	 */
	public $social_position_ld_rf;

	/**
	 * Declare a flag to track whether the first hook was triggered.
	 *
	 * @var bool
	 * @since 5.0.0
	 */
	private $learndash_social_hook_triggered = false;

	/**
	 * Class constructor.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->llla_table            = $wpdb->prefix . 'loginpress_limit_login_details';
		$this->attempts_settings     = get_option( 'loginpress_limit_login_attempts' );
		$this->settings              = get_option( 'loginpress_integration_settings' );
		$this->loginpress_settings   = get_option( 'loginpress_captcha_settings' );
		$this->social_settings       = get_option( 'loginpress_social_logins' );
		$this->addons                = get_option( 'loginpress_pro_addons' );
		$this->social_position_ld_lf = isset( $this->settings['social_position_ld_lf'] ) ? $this->settings['social_position_ld_lf'] : 'default';
		$this->social_position_ld_rf = isset( $this->settings['social_position_ld_rf'] ) ? $this->settings['social_position_ld_rf'] : 'default';
		$this->loginpress_ld_hooks();
	}

	/**
	 * Register LearnDash-related hooks for LoginPress.
	 *
	 * This function binds LoginPress functionality with LearnDash by hooking into
	 * relevant actions and filters provided by the LearnDash plugin.
	 * Useful for customizing or enhancing the LearnDash login and registration flows.
	 *
	 * @return void
	 * @since 5.0.0
	 * @version 6.1.0
	 */
	public function loginpress_ld_hooks() {
		global $register_learndash;
		global $register_block;
		if ( is_plugin_active( 'sfwd-lms/sfwd_lms.php' ) || is_plugin_active_for_network( 'sfwd-lms/sfwd_lms.php' ) ) {
			$groups = new WP_Query( array( 'post_type' => 'groups' ) );
			if ( $groups->have_posts() ) {
				add_filter( 'loginpress_login_redirects_tabs', array( $this, 'html_concatenation_with_learndash_callback' ), 10, 1 );
			}
		}
		add_filter( 'loginpress_redirects_structure_html_before', array( $this, 'loginpress_login_redirects_callback' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

		if ( isset( $this->addons['limit-login-attempts']['is_active'] ) && $this->addons['limit-login-attempts']['is_active'] ) {
			if ( ! is_user_logged_in() ) {
				add_action( 'wp_loaded', array( $this, 'llla_wp_loaded_learndash' ) );
			}
		}
		add_action( 'wp_ajax_loginpress_login_redirects_group_update', array( $this, 'loginpress_login_redirects_update_group' ) );
		add_action( 'wp_ajax_loginpress_login_redirects_group_delete', array( $this, 'loginpress_login_redirects_delete_group' ) );
		add_filter( 'loginpress_redirects_structure_html_after', array( $this, 'loginpress_learndash_groups_html_callback' ) );
		add_action( 'admin_footer', array( $this, 'loginpress_learndash_autocomplete_js' ) );
		add_action( 'wp_loaded', array( $this, 'loginpress_group_priority_callback' ) );
		add_filter( 'login_redirect', array( $this, 'loginpress_redirects_after_login_ld' ), 11, 3 );
		add_action( 'clear_auth_cookie', array( $this, 'loginpress_redirects_after_logout' ), 11 );

		$login_learndash       = isset( $this->settings['enable_social_ld_lf'] ) ? $this->settings['enable_social_ld_lf'] : '';
		$register_learndash    = isset( $this->settings['enable_social_ld_rf'] ) ? $this->settings['enable_social_ld_rf'] : '';
		$quiz_learndash        = isset( $this->settings['enable_social_ld_qf'] ) ? $this->settings['enable_social_ld_qf'] : '';
		$social_position_ld_qf = isset( $this->settings['social_position_ld_qf'] ) ? $this->settings['social_position_ld_qf'] : 'below';

		if ( isset( $this->addons['social-login']['is_active'] ) && $this->addons['social-login']['is_active'] ) {
			if ( ! class_exists( 'LoginPress_Social' ) ) {
				require_once LOGINPRESS_PRO_ROOT_PATH . '/addons/social-login/social-login.php';
			}

			add_action( 'learndash-register-modal-register-form-before', array( $this, 'loginpress_social_login_learndash_register' ), 10 );
			add_action( 'learndash_registration_form', array( $this, 'loginpress_learndash_register_widget' ), 10 );

			if ( ! is_user_logged_in() && 'off' !== $quiz_learndash ) {

				if ( 'above' === $social_position_ld_qf ) {
					add_action( 'learndash-quiz-actual-content-before', array( $this, 'loginpress_ld_quiz_above_callback' ) );
				} elseif ( 'below' === $social_position_ld_qf ) {
					add_action( 'learndash-quiz-actual-content-after', array( $this, 'loginpress_ld_quiz_below_callback' ) );
				}
			}

			if ( 'off' !== $login_learndash ) {

				if ( 'default' === $this->social_position_ld_lf || 'below' === $this->social_position_ld_lf ) {
					add_filter( 'login_form_bottom', array( $this, 'loginpress_login_form_bottom_ld_widget_callback' ) );
				} else {
					add_filter( 'login_form_top', array( $this, 'loginpress_login_form_bottom_ld_widget_callback' ) );
				}
			}
		}
		$ld_login         = isset( $this->settings['enable_captcha_ld']['login_learndash'] ) ? $this->settings['enable_captcha_ld']['login_learndash'] : false;
		$ld_register      = isset( $this->settings['enable_captcha_ld']['register_learndash'] ) ? $this->settings['enable_captcha_ld']['register_learndash'] : false;
		$ld_purchase      = isset( $this->settings['enable_captcha_ld']['register_block'] ) ? $this->settings['enable_captcha_ld']['register_block'] : false;
		$ld_lostpass      = isset( $this->settings['enable_captcha_ld']['login_ld_block'] ) ? $this->settings['enable_captcha_ld']['login_ld_block'] : false;
		$captchas_enabled = isset( $this->loginpress_settings['enable_captchas'] ) ? $this->loginpress_settings['enable_captchas'] : 'off';
		if ( 'off' !== $captchas_enabled ) {
			$captchas_type = isset( $this->loginpress_settings['captchas_type'] ) ? $this->loginpress_settings['captchas_type'] : 'type_recaptcha';
			if ( 'type_cloudflare' === $captchas_type ) {
				$loginpress_turnstile = LoginPress_Turnstile::instance();

				/* Cloudflare CAPTCHA Settings. */
				$cf_site_key   = isset( $this->loginpress_settings['site_key_cf'] ) ? $this->loginpress_settings['site_key_cf'] : '';
				$cf_secret_key = isset( $this->loginpress_settings['secret_key_cf'] ) ? $this->loginpress_settings['secret_key_cf'] : '';
				$validated     = isset( $this->loginpress_settings['validate_cf'] ) && 'on' === $this->loginpress_settings['validate_cf'] ? true : false;
				if ( ! empty( $cf_site_key ) && ! empty( $cf_secret_key ) && $validated ) {
					if ( $ld_login ) {
						add_filter( 'login_form_middle', array( $this, 'loginpress_add_turnstile_to_ld_login_fields' ), 99 );
						add_filter( 'wp_authenticate_user', array( $this, 'loginpress_ld_login_form_turnstile_enable' ), 9 );
					}
					$cap_register = isset( $this->loginpress_settings['captcha_enable_cf']['register_form'] ) ? $this->loginpress_settings['captcha_enable_cf']['register_form'] : false;
					if ( $ld_register && ! $cap_register ) {
						add_action( 'register_form', array( $this, 'loginpress_add_turnstile_to_ld_register_fields' ), 99 );
						add_filter( 'registration_errors', array( $this, 'loginpress_ld_login_form_turnstile_enable' ), 96, 3 );
					}
				}
			} elseif ( 'type_recaptcha' === $captchas_type ) {

				/* Add reCAPTCHA on ld login form. */
				if ( $ld_login ) {
					add_filter( 'login_form_middle', array( $this, 'loginpress_add_recaptcha_to_ld_login_fields' ), 99 );
				}
				$cap_register = isset( $this->loginpress_settings['captcha_enable']['register_form'] ) ? $this->loginpress_settings['captcha_enable']['register_form'] : false;

				/* Add reCAPTCHA on registration form. */
				if ( $ld_register && ! $cap_register ) {
					add_action( 'register_form', array( $this, 'loginpress_add_recaptcha_to_ld_register' ), 99 );
				}

				/* Authentication reCAPTCHA on ld login form. */
				if ( ! isset( $_GET['customize_changeset_uuid'] ) && $ld_login ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					add_action( 'wp_authenticate_user', array( $this, 'loginpress_ld_login_form_captcha_enable' ), 9 );
				}

				/* Authentication reCAPTCHA on ld purchase and registration form. */
				if ( ! isset( $_GET['customize_changeset_uuid'] ) && ( $ld_register ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					add_filter( 'registration_errors', array( $this, 'loginpress_ld_register_form_captcha_enable' ), 10, 3 );
				}
			} elseif ( 'type_hcaptcha' === $captchas_type ) {
				$hcap_site_key   = isset( $this->loginpress_settings['hcaptcha_site_key'] ) ? $this->loginpress_settings['hcaptcha_site_key'] : '';
				$hcap_secret_key = isset( $this->loginpress_settings['hcaptcha_secret_key'] ) ? $this->loginpress_settings['hcaptcha_secret_key'] : '';

				if ( ! empty( $hcap_site_key ) && ! empty( $hcap_secret_key ) && isset( $this->loginpress_settings['hcaptcha_verified'] ) && 'on' === $this->loginpress_settings['hcaptcha_verified'] ) {
					if ( $ld_login ) {
						add_filter( 'login_form_middle', array( $this, 'loginpress_add_hcaptcha_to_ld_login_fields' ), 99 );
						add_action( 'wp_authenticate_user', array( $this, 'loginpress_ld_login_form_hcaptcha_enable' ) );
					}
					$cap_register = isset( $this->loginpress_settings['hcaptcha_enable']['register_form'] ) ? $this->loginpress_settings['hcaptcha_enable']['register_form'] : false;
					if ( $ld_register && ! $cap_register ) {
						add_filter( 'register_form', array( $this, 'loginpress_add_hcaptcha_to_ld_register_fields' ), 99 );
						add_filter( 'registration_errors', array( $this, 'loginpress_ld_register_form_hcaptcha_enable' ), 99, 3 );
					}
				}
			}
		}
	}


	/**
	 * Callback to display social login above LearnDash quiz content.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function loginpress_ld_quiz_above_callback() {
		$loginpress_social = LoginPress_Social::instance();
		$loginpress_social->loginpress_social_login();
	}

	/**
	 * Callback to display social login below LearnDash quiz content.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function loginpress_ld_quiz_below_callback() {
		$loginpress_social = LoginPress_Social::instance();
		$loginpress_social->loginpress_social_login();
	}

	/**
	 * Load CSS and JS files at admin side on loginpress-settings page only.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function admin_scripts() {
		if ( isset( $this->addons['social-login']['is_active'] ) && $this->addons['social-login']['is_active'] ) {
			wp_enqueue_style( 'loginpress-social-login', LOGINPRESS_SOCIAL_DIR_PATH . 'assets/css/login.css', array(), LOGINPRESS_PRO_VERSION );
		}

		wp_localize_script(
			'loginpress_learndash_redirect_js',
			'loginpress_redirect_sociallogins',
			array(
				'group_nonce' => wp_create_nonce( 'loginpress-group-redirects-nonce' ),
				// translators: Learndash search group.
				'translate'   => array(
					_x( 'Search Group', 'The label Text of Login Redirect addon learndash group search field', 'loginpress-pro' ),
					_x( 'Search group For Whom To Apply Redirects', 'LoginPress Redirects Description text for Specific learndash group tab\'s search', 'loginpress-pro' ),
				),
			)
		);

		wp_localize_script(
			'loginpress_learndash_redirect_js',
			'loginpress_redirect',
			array(
				// translators: Learndash search role.
				'translate' => array(
					_x( 'Search Role For Whom To Apply Redirects', 'LoginPress Redirects Description text for Specific Roles tab\'s search', 'loginpress-pro' ),
					// translators: Search data.
					sprintf( _x( '%1$sSearch user\'s data from below the list%2$s', 'Search Label on Data tables', 'loginpress-pro' ), '<p class="description">', '</p>' ),
					_x( 'Enter keyword', 'The search keyword for the autologin users.', 'loginpress-pro' ),
				),
			)
		);
	}

	/**
	 * This function will be called on wp_loaded hook, check if LearnDash is active and if index.php is current page.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function llla_wp_loaded_learndash() {
		global $pagenow;
		if ( ( 'index.php' === $pagenow && is_plugin_active( 'sfwd-lms/sfwd_lms.php' ) ) || is_plugin_active_for_network( 'sfwd-lms/sfwd_lms.php' ) ) {
			add_filter( 'learndash_alert_message', array( $this, 'learndash_alert_message_callback' ), 10, 3 );
		}
	}

	/**
	 * It will check the current user's IP attempts and if user reached the limit, it will return the error message.
	 *
	 * @param string $error_message The error message.
	 * @param string $type The type of error.
	 * @param string $icon The icon for the error.
	 * @return string The error message which will be shown to the user.
	 * @since 5.0.0
	 */
	public function learndash_alert_message_callback( $error_message, $type, $icon ) {
		if ( ! isset( $_GET['login'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return $error_message;
		}
		global $wpdb;
		$ip               = $this->get_address();
		$current_time = current_time( 'timestamp' ); // @codingStandardsIgnoreLine.
		$attempts_allowed = isset( $this->attempts_settings['attempts_allowed'] ) ? $this->attempts_settings['attempts_allowed'] : '';
		$minutes_lockout  = isset( $this->attempts_settings['minutes_lockout'] ) ? intval( $this->attempts_settings['minutes_lockout'] ) : '';
		$lockout_time     = $current_time - ( intval( $minutes_lockout ) * 60 );
		$attempt_time = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$this->llla_table}` WHERE `ip` = %s AND `datentime` > %s", $ip, $lockout_time ) ); // @codingStandardsIgnoreLine.
		if ( $attempt_time < $attempts_allowed ) {

			$error_message = wp_kses_post( $this->loginpress_attempts_error( -1 + $attempt_time ) );

		}
		$messages = $error_message;

		return $messages;
	}

	/**
	 * Modifies the registration form for LearnDash by adding or removing the social login action.
	 *
	 * @global string $register_learndash Flag to determine whether to register LearnDash or not.
	 * @return void
	 * @since 5.0.0
	 */
	public function loginpress_social_login_learndash_register() {
		$this->learndash_social_hook_triggered = true;
		global $register_learndash;
		$social_p_ld_rf    = $this->social_position_ld_rf;
		$loginpress_social = LoginPress_Social::instance();
		if ( 'off' !== $register_learndash ) {
			if ( ! has_action( 'register_form', array( $loginpress_social, 'loginpress_social_login' ) ) ) {
				add_action( 'register_form', array( $loginpress_social, 'loginpress_social_login' ) );
			}
			add_action(
				'wp_footer',
				function () use ( $social_p_ld_rf ) {
					$this->loginpress_output_social_login_position_script( $social_p_ld_rf );
				}
			);

		} else {
			remove_action( 'register_form', array( $loginpress_social, 'loginpress_social_login' ) );
		}
	}

	/**
	 * Modifies the registration form for LearnDash by adding or removing the social login action.
	 *
	 * @global string $register_learndash Flag to determine whether to register LearnDash or not.
	 * @return void
	 * @since 5.0.0
	 */
	public function loginpress_learndash_register_widget() {
		if ( $this->learndash_social_hook_triggered ) {
			return;
		}

		global $register_learndash;
		$social_p_ld_rf    = $this->social_position_ld_rf;
		$loginpress_social = LoginPress_Social::instance();
		if ( 'off' !== $register_learndash ) {
			if ( ! has_action( 'register_form', array( $loginpress_social, 'loginpress_social_login' ) ) ) {

				$loginpress_social->loginpress_social_login();
			}
			add_action(
				'wp_footer',
				function () use ( $social_p_ld_rf ) {
					$this->loginpress_output_social_login_position_script( $social_p_ld_rf );
				}
			);

		} else {
			remove_action( 'register_form', array( $loginpress_social, 'loginpress_social_login' ) );
		}
	}

	/**
	 * Modifies the registration form for LearnDash by adding or removing the social login action.
	 *
	 * @param string $position The position of the social login buttons.
	 * @return void
	 * @since 5.0.0
	 */
	public function loginpress_output_social_login_position_script( $position = 'default' ) {
		$separator_text = apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) );
		$separator_text = esc_html( $separator_text );
		?>
		<script>
		document.addEventListener('DOMContentLoaded', function () {
			var formSelectors = ['#learndash_registerform', '#registerform'];
			var position = '<?php echo esc_js( $position ); ?>';
	
			formSelectors.forEach(function(selector) {
				var form = document.querySelector(selector);
				if (!form) return;
	
				var socialDiv    = form.querySelector('.social-networks');
				var submitButton = form.querySelector('input[type="submit"], #wp-submit');
				var usernameField = form.querySelector('input[name="user_login"], input[name="username"]');
	
				if (!socialDiv) return;
	
				switch(position) {
					case 'default':
						if (submitButton) {
							var separator = document.createElement('span');
							separator.className = 'social-sep';
							separator.innerHTML = `<span><?php echo esc_html( $separator_text ); ?></span>`;
							var submitWrapper = submitButton.parentElement;
							submitWrapper.insertAdjacentElement('afterend', separator);
							separator.insertAdjacentElement('afterend', socialDiv);
						}
						break;
	
					case 'below':
						if (submitButton) {
							var submitWrapper = submitButton.parentElement;
							submitWrapper.insertAdjacentElement('afterend', socialDiv);
						}
						break;
	
					case 'above':
						if (usernameField) {
							var usernameWrapper = usernameField.parentElement;
							usernameWrapper.insertAdjacentElement('beforebegin', socialDiv);
						}
						break;
	
					case 'above_separator':
						if (usernameField) {
							var separator = document.createElement('span');
							separator.className = 'social-sep';
							separator.innerHTML = `<span><?php echo esc_html( $separator_text ); ?></span>`;
							var usernameWrapper = usernameField.parentElement;
							usernameWrapper.insertAdjacentElement('beforebegin', separator);
							separator.insertAdjacentElement('beforebegin', socialDiv);
						}
						break;
				}
			});
		});
		</script>
		<?php
	}

	/**
	 * Generates the HTML for the social login buttons at the bottom of the login form.
	 *
	 * @return string The HTML for the social login buttons.
	 * @since 5.0.0
	 */
	public function loginpress_login_form_bottom_ld_widget_callback() {

		$redirect_to = site_url() . sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ); // @codingStandardsIgnoreLine.
		$encoded_url        = rawurlencode( $redirect_to );
		$social_login_class = LoginPress_Social::instance();

		$button_style   = isset( $this->social_settings['social_button_styles'] ) ? $this->social_settings['social_button_styles'] : '';
		$button_text    = $this->social_settings['social_login_button_label'] ?? 'Login with %provider%';
		$provider_order = isset( $this->social_settings['provider_order'] ) && ! empty( $this->social_settings['provider_order'] )
			? ( is_array( $this->social_settings['provider_order'] )
				? $this->social_settings['provider_order']
				: json_decode( $this->social_settings['provider_order'], true ) )
			: array( 'facebook', 'twitter', 'gplus', 'linkedin', 'microsoft', 'apple', 'discord', 'wordpress', 'github' );

		$html = "<div class='social-networks block " . esc_attr( "loginpress-$button_style" ) . "'>";
		if ( 'default' === $this->social_position_ld_lf ) {
			$separator_text = apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) );
			$html          .= "<span class='social-sep'><span>" . esc_html( $separator_text ) . '</span></span>';
		}

		foreach ( $provider_order as $provider ) {
			if ( isset( $this->social_settings[ $provider ] ) && 'on' === $this->social_settings[ $provider ] && ! empty( $this->social_settings[ "{$provider}_status" ] ) && 'not verified' !== strtolower( $this->social_settings[ "{$provider}_status" ] ) ) {
				$client_id_key     = "{$provider}_client_id";
				$client_secret_key = "{$provider}_client_secret";
				$app_id_key        = "{$provider}_app_id";
				$app_secret_key    = "{$provider}_app_secret";

				if ( ( ! empty( $this->social_settings[ $client_id_key ] ) && ! empty( $this->social_settings[ $client_secret_key ] ) ) ||
					( ! empty( $this->social_settings[ $app_id_key ] ) && ! empty( $this->social_settings[ $app_secret_key ] ) ) ||
					( ! empty( $this->social_settings[ "{$provider}_oauth_token" ] ) && ! empty( $this->social_settings[ "{$provider}_token_secret" ] ) ) ||
					( ! empty( $this->social_settings[ "{$provider}_service_id" ] ) && ! empty( $this->social_settings[ "{$provider}_key_id" ] ) && ! empty( $this->social_settings[ "{$provider}_team_id" ] ) && ! empty( $this->social_settings[ "{$provider}_p_key" ] ) ) ) {

					$button_label_key = "{$provider}_button_label";
					if ( 'gplus' === $provider ) {
						$button_label_key = 'google_button_label';
					}
					// Replace 'gplus' with 'Google'.
					$provider_display_name = ( 'gplus' === $provider ) ? 'Google' : ucfirst( $provider );

					$provider_button_text = ! empty( $this->social_settings[ $button_label_key ] )
						? $this->social_settings[ $button_label_key ]
						: ( ! empty( $button_text )
							? str_replace( '%provider%', $provider_display_name, $button_text )
							: 'Login with ' . $provider_display_name );

					$login_id   = "{$provider}_login";
					$icon_class = ( 'gplus' === $provider ) ? 'icon-google-plus' : "icon-$provider";

					$html .= "<a href='" . esc_url_raw( wp_login_url() . "?lpsl_login_id=$login_id&state=" . base64_encode( "redirect_to=$encoded_url" ) . "&redirect_to=$redirect_to" ) . "' title='" . esc_html( $provider_button_text ) . "' rel='nofollow'>"; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
					$html .= "<div class='lpsl-icon-block $icon_class clearfix'>";
					$html .= "<span class='lpsl-login-text'>" . esc_html( $provider_button_text ) . '</span>';
					$html .= $social_login_class->get_provider_icon( $provider ); // Dynamically fetch the provider's icon.
					$html .= '</div></a>';
				}
			}
		}

		$html .= '</div>';
		if ( 'above_separator' === $this->social_position_ld_lf ) {
			$separator_text = apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) );
			$html          .= "<span class='social-sep'><span>" . esc_html( $separator_text ) . '</span></span>';
		}
		return $html;
	}
}

new LoginPress_Learndash_Integration();
