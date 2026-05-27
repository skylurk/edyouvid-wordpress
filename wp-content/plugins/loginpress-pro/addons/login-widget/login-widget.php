<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * LoginPress - Login Widget Addon.
 *
 * LoginPress - Login widget is the best Login plugin by <a href="https://wpbrigade.com/">WPBrigade</a> which allows you to login from front end.
 *
 * @package   LoginPress
 * @subpackage Widget
 * @category  Core
 * @author    WPBrigade
 * @since     1.0.0
 * @version   6.1.0
 */

// Include utility functions.
require_once plugin_dir_path( __FILE__ ) . 'classes/class-loginpress-widget-utilities.php';

if ( ! class_exists( 'LoginPress_Login_Widget' ) ) :

	/**
	 * LoginPress Login Widget class.
	 *
	 * @package   LoginPress
	 * @subpackage Widget
	 * @category  Core
	 * @since     1.0.0
	 * @version   6.1.0
	 */
	final class LoginPress_Login_Widget {

		/**
		 * Social login settings array.
		 *
		 * @since 1.0.0
		 * @var array Social login settings.
		 */
		public $settings;

		/**
		 * General settings array.
		 *
		 * @since 1.0.0
		 * @var array General plugin settings.
		 */
		public $general_settings;

		/**
		 * Captcha settings array.
		 *
		 * @since 1.0.0
		 * @var array Captcha configuration settings.
		 */
		public $captcha_settings;

		/**
		 * Class constructor.
		 *
		 * @since 1.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function __construct() {

			if ( LoginPress_Pro::addon_wrapper( 'login-widget' ) ) {
				$this->hooks();
				$this->define_constants();
			}
		}

		/**
		 * Hook into actions and filters.
		 *
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function hooks() {

			add_action( 'init', array( $this, 'social_login' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'loginpress_widget_script' ) );
			add_action( 'widgets_init', array( $this, 'register_widget' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
			add_action( 'wp_ajax_loginpress_widget_login_process', array( $this, 'loginpress_widget_ajax' ) );
			add_action( 'wp_ajax_nopriv_loginpress_widget_login_process', array( $this, 'loginpress_widget_ajax' ) );
			add_action( 'wp_ajax_nopriv_loginpress_widget_register_process', array( $this, 'loginpress_widget_register_process' ) );
			add_action( 'wp_ajax_nopriv_loginpress_widget_lost_password_process', array( $this, 'loginpress_widget_lost_password_process' ) );
			$this->settings         = get_option( 'loginpress_social_logins' );
			$this->general_settings = get_option( 'loginpress_setting' );
			$this->captcha_settings = get_option( 'loginpress_captcha_settings' );
		}

		/**
		 * Compatibility of LoginPress - Social Login with Widget Login.
		 *
		 * @since 4.0.0
		 * @version 6.1.0
		 * @return string Social login buttons HTML.
		 */
		public function loginpress_social_login() {

			$redirect_to = site_url() . sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ); // @codingStandardsIgnoreLine.
			$encoded_url        = rawurlencode( $redirect_to );
			$social_login_class = LoginPress_Social::instance();
			if ( ( ! isset( $this->settings['enable_social_login_links']['login'] ) ) && ( ! isset( $this->settings['enable_social_login_links']['register'] ) ) ) {
				return null;
			}

			$button_style   = isset( $this->settings['social_button_styles'] ) ? $this->settings['social_button_styles'] : '';
			$button_text    = $this->settings['social_login_button_label'] ?? 'Login with %provider%';
			$provider_order = isset( $this->settings['provider_order'] ) && ! empty( $this->settings['provider_order'] )
				? ( is_array( $this->settings['provider_order'] )
					? $this->settings['provider_order']
					: json_decode( $this->settings['provider_order'], true ) )
				: array( 'facebook', 'twitter', 'gplus', 'linkedin', 'microsoft', 'apple', 'discord', 'wordpress', 'github', 'amazon', 'pinterest', 'disqus', 'reddit', 'spotify', 'twitch' );

			$html = "<div class='social-networks block " . esc_attr( "loginpress-$button_style" ) . "'>";

				$separator_text = apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) );
				$html          .= "<span class='social-sep'><span>" . esc_html( $separator_text ) . '</span></span>';

			foreach ( $provider_order as $provider ) {
				if ( isset( $this->settings[ $provider ] ) && 'on' === $this->settings[ $provider ] && ! empty( $this->settings[ "{$provider}_status" ] ) && 'not verified' !== strtolower( $this->settings[ "{$provider}_status" ] ) ) {
					$client_id_key     = "{$provider}_client_id";
					$client_secret_key = "{$provider}_client_secret";
					$app_id_key        = "{$provider}_app_id";
					$app_secret_key    = "{$provider}_app_secret";

					if ( ( ! empty( $this->settings[ $client_id_key ] ) && ! empty( $this->settings[ $client_secret_key ] ) ) ||
						( ! empty( $this->settings[ $app_id_key ] ) && ! empty( $this->settings[ $app_secret_key ] ) ) ||
						( ! empty( $this->settings[ "{$provider}_oauth_token" ] ) && ! empty( $this->settings[ "{$provider}_token_secret" ] ) ) ||
						( ! empty( $this->settings[ "{$provider}_service_id" ] ) && ! empty( $this->settings[ "{$provider}_key_id" ] ) && ! empty( $this->settings[ "{$provider}_team_id" ] ) && ! empty( $this->settings[ "{$provider}_p_key" ] ) ) ) {

						$button_label_key = "{$provider}_button_label";
						if ( 'gplus' === $provider ) {
							$button_label_key = 'google_button_label';
						}
						// Replace 'gplus' with 'Google'.
						$provider_display_name = ( 'gplus' === $provider ) ? 'Google' : ucfirst( $provider );

						$provider_button_text = ! empty( $this->settings[ $button_label_key ] )
						? $this->settings[ $button_label_key ]
						: ( ! empty( $button_text )
							? str_replace( '%provider%', $provider_display_name, $button_text )
							: esc_html__( 'Login with ', 'loginpress-pro' ) . $provider_display_name );

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
			return $html;
		}

		/**
		 * LoginPress Addon updater.
		 *
		 * @since 1.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function init_addon_updater() {

			if ( class_exists( 'LoginPress_AddOn_Updater' ) ) {
				$updater = new LoginPress_AddOn_Updater( 2333, __FILE__, $this->version );
			}
		}

		/**
		 * Add social logins.
		 *
		 * @since 3.0.0
		 * @version 6.0.2
		 */
		public function social_login() {

			if ( class_exists( 'LoginPress_Social' ) && true === apply_filters( 'loginpress_social_widget', true ) ) {

				if ( method_exists( 'LoginPress_Social', 'check_social_api_status' ) && true === LoginPress_Social::check_social_api_status() && $this->is_widget_present() ) {
					add_filter( 'login_form_bottom', array( $this, 'loginpress_social_login' ), 1 );
				}
			}
		}

		/**
		 * Check if the login widget is present on the current page.
		 *
		 * Checks for both classic widget and block usage.
		 *
		 * @since 6.0.2
		 * @version 6.0.2
		 * @return bool True if widget is present, false otherwise.
		 */
		private function is_widget_present() {
			// Check if classic widget is active.
			if ( is_active_widget( false, false, 'loginpress-login-widget', true ) ) {
				return true;
			}

			// Check if block exists in current post/page content.
			if ( function_exists( 'has_block' ) ) {
				// For singular pages/posts, check the current post content.
				if ( is_singular() ) {
					global $post;
					if ( $post && has_block( 'loginpress/login-widget', $post ) ) {
						return true;
					}
				}

				// Check if block exists in any widget area (block-based widgets).
				$widget_blocks = get_option( 'widget_block' );
				if ( ! empty( $widget_blocks ) && is_array( $widget_blocks ) ) {
					foreach ( $widget_blocks as $widget_block ) {
						if ( isset( $widget_block['content'] ) && has_blocks( $widget_block['content'] ) ) {
							if ( has_block( 'loginpress/login-widget', $widget_block['content'] ) ) {
								return true;
							}
						}
					}
				}
			}

			// For block widgets, we also need to check template parts and FSE templates.
			// This is a broader check for full site editing compatibility.
			if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
				// In block themes, check if block might be in template parts.
				// We check if block was rendered on previous page load (stored in option).
				// Note: This is a fallback and less precise, but needed for FSE compatibility.
				$block_attributes = get_option( 'loginpress_block_attributes' );
				if ( ! empty( $block_attributes ) && ( is_singular() || is_home() || is_front_page() ) ) {
					// Check if we're likely on a page that could have the block.
					// This is conservative - only for main content areas.
					return true;
				}
			}

			return false;
		}

		/**
		 * Enqueue widget scripts and styles.
		 *
		 * @since 3.0.0
		 * @version 6.0.2
		 * @return void
		 */
		public function loginpress_widget_script() {

			// Only enqueue if the widget is present on the page.
			if ( ! $this->is_widget_present() ) {
				return;
			}

			$attributes            = get_option( 'loginpress_block_attributes' );
			$loginpress_setting    = get_option( 'loginpress_setting' );
			$enable_pass_strength  = isset( $loginpress_setting['enable_pass_strength'] ) ? $loginpress_setting['enable_pass_strength'] : 'off';
			$enable_on_form        = isset( $loginpress_setting['enable_pass_strength_forms']['register'] ) ? $loginpress_setting['enable_pass_strength_forms']['register'] : 'off';
			$min_length            = isset( $loginpress_setting['minimum_pass_char'] ) ? $loginpress_setting['minimum_pass_char'] : '';
			$require_upper_lower   = isset( $loginpress_setting['pass_strength']['lower_upper_char_must'] ) ? $loginpress_setting['pass_strength']['lower_upper_char_must'] : 'off';
			$require_special_char  = isset( $loginpress_setting['pass_strength']['special_char_must'] ) ? $loginpress_setting['pass_strength']['special_char_must'] : 'off';
			$integer_no_must       = isset( $loginpress_setting['pass_strength']['integer_no_must'] ) ? $loginpress_setting['pass_strength']['integer_no_must'] : 'off';
			$enable_reg_pass_field = isset( $loginpress_setting['enable_reg_pass_field'] ) ? $loginpress_setting['enable_reg_pass_field'] : 'off';

			// Enqueue LoginPress Widget JS.
			wp_enqueue_script( 'loginpress-login-widget-script', plugins_url( 'assets/js/script.js', __FILE__ ), array( 'jquery' ), LOGINPRESS_PRO_VERSION, false );
			wp_enqueue_script( 'loginpress-pasword-strength-meter', plugins_url( 'js/password-strength-meter.js', LOGINPRESS_ROOT_FILE ), array( 'jquery', 'password-strength-meter' ), LOGINPRESS_VERSION, true );
			wp_enqueue_script( 'password-strength-meter' );
			// Enqueue Styles.
			wp_enqueue_style( 'loginpress-login-widget-style', plugins_url( 'assets/css/style.css', __FILE__ ), '', LOGINPRESS_PRO_VERSION );

			if ( class_exists( 'LoginPress_Social' ) ) {
				wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css' ); // phpcs:ignore
				if ( ! is_user_logged_in() ) {
					wp_enqueue_style( 'loginpress-social-login', plugins_url( 'social-login/assets/css/login.css', __DIR__ ), array(), LOGINPRESS_PRO_VERSION );
					include LOGINPRESS_SOCIAL_DIR_PATH . 'assets/js/script-login.php';
				}
			}

			$loginpress_widget_option  = get_option( 'widget_loginpress-login-widget' );
			$_loginpress_widget_option = isset( $loginpress_widget_option ) ? $loginpress_widget_option : false;
			if ( $attributes ) {

				// Use block attributes.
				$error_bg_color   = isset( $attributes['errorBgColor'] ) ? $attributes['errorBgColor'] : '#fbb1b7';
				$error_text_color = isset( $attributes['errorTextColor'] ) ? $attributes['errorTextColor'] : '#ae121e';

			} elseif ( $_loginpress_widget_option ) {
				$error_bg_color = isset( $loginpress_widget_option[2]['error_bg_color'] ) ? $loginpress_widget_option[2]['error_bg_color'] : '#fbb1b7';

				$error_text_color = isset( $loginpress_widget_option[2]['error_text_color'] ) ? $loginpress_widget_option[2]['error_text_color'] : '#ae121e';
			}

				$_loginpress_widget_error_bg_clr = "
                .loginpress-login-widget .loginpress_widget_error{
                  background-color: {$error_bg_color};
                  color: {$error_text_color};
                }";
				wp_add_inline_style( 'loginpress-login-widget-style', $_loginpress_widget_error_bg_clr );

			$loginpress_key = get_option( 'loginpress_customization' ) ?: array(); // @codingStandardsIgnoreLine.

			/* Translators: For Invalid username. */
			$invalid_usrname = array_key_exists( 'incorrect_username', $loginpress_key ) && ! empty( $loginpress_key['incorrect_username'] ) ? $loginpress_key['incorrect_username'] : sprintf( __( '%1$sError:%2$s Invalid Username.', 'loginpress-pro' ), '<strong>', '</strong>' );

			/* Translators: For Invalid password. */
			$invalid_pasword = array_key_exists( 'incorrect_password', $loginpress_key ) && ! empty( $loginpress_key['incorrect_password'] ) ? $loginpress_key['incorrect_password'] : sprintf( __( '%1$sError:%2$s Invalid Password.', 'loginpress-pro' ), '<strong>', '</strong>' );

			/* Translators: If username field is empty. */
			$empty_username = array_key_exists( 'empty_username', $loginpress_key ) && ! empty( $loginpress_key['empty_username'] ) ? $loginpress_key['empty_username'] : sprintf( __( '%1$sError:%2$s The username field is empty.', 'loginpress-pro' ), '<strong>', '</strong>' );

			/* Translators: For empty password. */
			$empty_password = array_key_exists( 'empty_password', $loginpress_key ) && ! empty( $loginpress_key['empty_password'] ) ? $loginpress_key['empty_password'] : sprintf( __( '%1$sError:%2$s The password field is empty.', 'loginpress-pro' ), '<strong>', '</strong>' );

			/* Translators: For invalid email. */
			$invalid_email = array_key_exists( 'invalid_email', $loginpress_key ) && ! empty( $loginpress_key['invalid_email'] ) ? $loginpress_key['invalid_email'] : sprintf( __( '%1$sError:%2$s The email address isn\'t correct..', 'loginpress-pro' ), '<strong>', '</strong>' );

			// Pass variables.
			$loginpress_widget_params = array(
				'ajaxurl'               => admin_url( 'admin-ajax.php' ),
				'force_ssl_admin'       => force_ssl_admin() ? 1 : 0,
				'is_ssl'                => is_ssl() ? 1 : 0,
				'empty_username'        => $empty_username,
				'empty_password'        => $empty_password,
				'invalid_username'      => $invalid_usrname,
				'invalid_password'      => $invalid_pasword,
				'invalid_email'         => $invalid_email,
				'lp_widget_nonce'       => wp_create_nonce( 'loginpress_login_widget_security' ),
				'enable_pass_strength'  => $enable_pass_strength,
				'enable_on_form'        => $enable_on_form,
				'min_length'            => $min_length,
				'require_upper_lower'   => $require_upper_lower,
				'require_special_char'  => $require_special_char,
				'integer_no_must'       => $integer_no_must,
				'enable_reg_pass_field' => $enable_reg_pass_field,
			);

			wp_localize_script( 'loginpress-login-widget-script', 'loginpress_widget_params', $loginpress_widget_params );
		}

		/**
		 * Register LoginPress Widget.
		 *
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function register_widget() {
			include_once LOGINPRESS_WIDGET_DIR_PATH . 'classes/class-loginpress-widget.php';
			include_once LOGINPRESS_WIDGET_DIR_PATH . 'classes/class-loginpress-render-block.php';
			register_widget( 'LoginPress_Widget' );
		}

		/**
		 * Define LoginPress Widget Constants.
		 *
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return void
		 */
		private function define_constants() {
			LoginPress_Pro_Init::define( 'LOGINPRESS_WIDGET_DIR_PATH', plugin_dir_path( __FILE__ ) );
		}

		/**
		 * Load JS or CSS files at admin side and enqueue them.
		 *
		 * @param string $hook The current admin page hook.
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function admin_scripts( $hook ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
			if ( ! is_admin() ) {
				wp_enqueue_style( 'loginpress_widget_style', plugins_url( 'assets/css/style.css', __FILE__ ), array(), LOGINPRESS_PRO_VERSION );
				wp_enqueue_script( 'loginpress_widget_js', plugins_url( 'assets/js/script.js', __FILE__ ), array( 'jquery' ), LOGINPRESS_PRO_VERSION, false );
			}
		}

		/**
		 * Retrieve the redirect URL w.r.t Login Redirect Add-On.
		 *
		 * @param int    $user_id User ID.
		 * @param string $option meta key name.
		 * @since 3.0.0
		 *
		 * @return string $redirect_url meta value of the user w.r.t key name.
		 */
		private function loginpress_redirect_url( $user_id, $option ) {
			return LoginPress_Widget_Utilities::get_user_redirect_url( $user_id, $option );
		}

		/**
		 * LoginPress_widget_ajax function.
		 *
		 * @access public
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function loginpress_widget_ajax() {

			check_ajax_referer( 'loginpress_login_widget_security', 'nonce' );

			$data                        = array();
			$data['user_login']          = isset( $_POST['user_login'] ) ? sanitize_text_field( wp_unslash( $_POST['user_login'] ) ) : '';
			$data['user_password']       = isset( $_POST['user_password'] ) ? sanitize_text_field( wp_unslash( $_POST['user_password'] ) ) : '';
			$data['remember']            = isset( $_POST['remember'] ) ? sanitize_text_field( wp_unslash( $_POST['remember'] ) ) : '';
			$redirect_to                 = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
			$secure_cookie               = null;
			$captcha_response            = isset( $_POST['captcha_response'] ) ? sanitize_text_field( wp_unslash( $_POST['captcha_response'] ) ) : '';
			$loginpress_captcha_settings = $this->captcha_settings;
			$cap_login                   = isset( $loginpress_captcha_settings['captcha_enable']['login_form'] ) ? $loginpress_captcha_settings['captcha_enable']['login_form'] : false;
			$cap_login_cf                = isset( $loginpress_captcha_settings['captcha_enable_cf']['login_form'] ) ? $loginpress_captcha_settings['captcha_enable_cf']['login_form'] : false;
			$captchas_type               = isset( $loginpress_captcha_settings['captchas_type'] ) ? $loginpress_captcha_settings['captchas_type'] : 'type_recaptcha';
			$hcaptcha_login              = isset( $loginpress_captcha_settings['hcaptcha_enable']['login_form'] ) ? $loginpress_captcha_settings['hcaptcha_enable']['login_form'] : 'off';
			if ( isset( $loginpress_captcha_settings['enable_captchas'] ) && 'on' === $loginpress_captcha_settings['enable_captchas'] ) {
				if ( ( 'off' !== $hcaptcha_login && 'type_hcaptcha' === $captchas_type ) ||
					( 'off' !== $cap_login && 'type_recaptcha' === $captchas_type ) ||
					( $cap_login_cf && 'type_cloudflare' === $captchas_type ) ) {
					if ( $captcha_response ) {
						if ( $cap_login || $cap_login_cf ) {
							if ( 'type_recaptcha' === $loginpress_captcha_settings['captchas_type'] ) {
								$captcha = new LoginPress_Recaptcha( $this->general_settings, $loginpress_captcha_settings );
								add_filter( 'authenticate', array( $captcha, 'loginpress_recaptcha_auth' ), 99, 3 );
							} elseif ( 'type_hcaptcha' === $loginpress_captcha_settings['captchas_type'] ) {
								$hcaptcha = new LoginPress_Hcaptcha( $loginpress_captcha_settings );
								add_filter( 'authenticate', array( $hcaptcha, 'loginpress_hcaptcha_auth' ), 99, 3 );
							} elseif ( 'type_cloudflare' === $loginpress_captcha_settings['captchas_type'] ) {
								$cf = new LoginPress_Turnstile( $loginpress_captcha_settings );
								add_filter( 'authenticate', array( $cf, 'loginpress_turnstile_auth_widget' ), 99, 4 );
							}
						}
					} else {
						$response['error'] = __( 'Please verify captcha.', 'loginpress-pro' );
						echo wp_json_encode( $response );
						wp_die();
					}
				}
			}
			// If the user wants ssl but the session is not ssl, force a secure cookie.
			if ( ! force_ssl_admin() ) {
				$user = is_email( $data['user_login'] ) ? get_user_by( 'email', $data['user_login'] ) : get_user_by( 'login', sanitize_user( $data['user_login'] ) );

				if ( $user && get_user_option( 'use_ssl', $user->ID ) ) {
					$secure_cookie = true;
					force_ssl_admin( true );
				}
			}

			if ( force_ssl_admin() ) {
				$secure_cookie = true;
			}

			if ( is_null( $secure_cookie ) && force_ssl_admin() ) {
				$secure_cookie = false;
			}

			// Login.
			$user = wp_signon( $data, $secure_cookie );

			// Redirect filter.
			if ( $secure_cookie && strstr( $redirect_to, 'wp-admin' ) ) {
				$redirect_to = str_replace( 'http:', 'https:', $redirect_to );
			}

			/**
			 * Filter login url if Login Redirect addon used. Add separate Login Widget Redirect compatibility.
			 *
			 * @since 1.0.5
			 */
			if ( class_exists( 'LoginPress_Login_Redirect_Main' ) && apply_filters( 'prevent_loginpress_login_widget_redirect', true ) ) {
				$logged_user_id     = $user->data->ID;
				$redirect_to        = $this->loginpress_redirect_url( $logged_user_id, 'loginpress_login_redirects_url' );
				$role_redirects_url = get_option( 'loginpress_redirects_role' );

				if ( empty( $redirect_to ) && ! empty( $role_redirects_url ) ) {
					foreach ( $role_redirects_url as $key => $value ) {
						if ( ! empty( $key ) && ! empty( $user ) ) {
							if ( in_array( $key, $user->roles, true ) ) {
								$redirect_to = $value['login'];
							}
						}
					}
				}
			}

			/**
			 * Add Force password reset compatibility for Login Widget/Block.
			 *
			 * @since 6.0.0
			 * @version 6.1.0
			 */
			if ( class_exists( 'LoginPress_Force_Password_Reset' ) ) {
				if ( defined( 'LOGINPRESS_VERSION' ) && version_compare( LOGINPRESS_VERSION, '6.1.0', '>=' ) ) {
					$redirect_to = LoginPress_Force_Password_Reset::loginpress_user_login_check( $data['user_login'], $user );
				} else {
					// Deprecated way for backward compatibility.
					$force_password_reset = new LoginPress_Force_Password_Reset();
					$redirect_to          = $force_password_reset->loginpress_user_login_check( $data['user_login'], $user );
					if ( empty( $redirect_to ) ) {
						$redirect_to = $_REQUEST['redirect_to'] ?? ''; // phpcs:ignore
					}
				}
			}

			$response = array();

			if ( ! is_wp_error( $user ) ) {

				$response['success']  = 1;
				$response['redirect'] = $redirect_to;
			} else {

				$response['success'] = 0;
				if ( $user->errors ) {

					foreach ( $user->errors as $key => $error ) {

						$response[ $key ] = $error[0];
						break;
					}
				} else {

					$response['error'] = __( 'Please enter your username and password to login.', 'loginpress-pro' );
				}
			}

			echo wp_json_encode( $response );

			wp_die();
		}

		/**
		 * Handles the registration process for the login widget.
		 *
		 * Called by the login widget via an AJAX request.
		 *
		 * @since 4.0.0
		 *
		 * @return void|null
		 */
		public function loginpress_widget_register_process() {
			// Check nonce for security.
			check_ajax_referer( 'loginpress_login_widget_security', 'nonce' );

			// Get registration data from the AJAX request.
			$username                    = isset( $_POST['user_login'] ) ? sanitize_user( wp_unslash( $_POST['user_login'] ) ) : '';
			$email                       = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
			$pass                        = isset( $_POST['loginpress-reg-pass'] ) ? sanitize_text_field( wp_unslash( $_POST['loginpress-reg-pass'] ) ) : '';
			$errors                      = new WP_Error();
			$captcha_response            = isset( $_POST['captcha_response'] ) ? sanitize_text_field( wp_unslash( $_POST['captcha_response'] ) ) : '';
			$loginpress_captcha_settings = $this->captcha_settings;
			$loginpress_settings         = $this->general_settings;
			$enable_verification         = isset( $loginpress_settings['enable_user_verification'] ) ? $loginpress_settings['enable_user_verification'] : '';
			$cap_register                = isset( $loginpress_captcha_settings['captcha_enable']['register_form'] ) ? $loginpress_captcha_settings['captcha_enable']['register_form'] : false;
			$cap_reg_cf                  = isset( $loginpress_captcha_settings['captcha_enable_cf']['register_form'] ) ? $loginpress_captcha_settings['captcha_enable_cf']['register_form'] : false;
			$hcaptcha_reg                = isset( $loginpress_captcha_settings['hcaptcha_enable']['register_form'] ) ? $loginpress_captcha_settings['hcaptcha_enable']['register_form'] : 'off';
			$captchas_type               = isset( $loginpress_captcha_settings['captchas_type'] ) ? $loginpress_captcha_settings['captchas_type'] : 'type_recaptcha';
			if ( isset( $loginpress_captcha_settings['enable_captchas'] ) && 'on' === $loginpress_captcha_settings['enable_captchas'] ) {
				if ( ( 'off' !== $hcaptcha_reg && 'type_hcaptcha' === $captchas_type ) ||
				( 'off' !== $cap_register && 'type_recaptcha' === $captchas_type ) ||
				( $cap_reg_cf && 'type_cloudflare' === $captchas_type ) ) {
					if ( $captcha_response ) {
						if ( class_exists( 'LoginPress_Recaptcha' ) ) {
							if ( $cap_register || $cap_reg_cf ) {
								if ( 'type_recaptcha' === $loginpress_captcha_settings['captchas_type'] ) {
									$captcha = new LoginPress_Recaptcha( $loginpress_settings, $loginpress_captcha_settings );
									add_filter( 'registration_errors', array( $captcha, 'loginpress_recaptcha_registration_auth' ), 10, 3 );
								} elseif ( 'type_hcaptcha' === $loginpress_captcha_settings['captchas_type'] ) {
									$hcaptcha = new LoginPress_Hcaptcha( $loginpress_captcha_settings );
									add_filter( 'registration_errors', array( $hcaptcha, 'loginpress_hcaptcha_registration_auth' ), 10, 3 );
								} elseif ( 'type_cloudflare' === $loginpress_captcha_settings['captchas_type'] ) {
									$cf = new LoginPress_Turnstile( $loginpress_captcha_settings );
									add_filter( 'registration_errors', array( $cf, 'loginpress_turnstile_auth_widget' ), 10, 4 );
								}
							}
						}
					} else {
						wp_send_json_error(
							array(
								'no_response' => true,
								'message'     => __( 'Please verify the Captcha', 'loginpress-pro' ),
							)
						);
						wp_die();
					}
				}
			}

			// Check if the username or email already exists.
			if ( username_exists( $username ) ) {
				wp_send_json_error(
					array(
						'username_exists' => true,
						'message'         => __( 'This username is already taken.', 'loginpress-pro' ),
					)
				);
			}
			if ( email_exists( $email ) ) {
				wp_send_json_error(
					array(
						'email_exists' => true,
						'message'      => __( 'This email is already registered.', 'loginpress-pro' ),
					)
				);
			}

			// If no errors, proceed with registration.
			if ( empty( $errors->get_error_messages() ) ) {
				if ( empty( $pass ) ) {
					$password = wp_generate_password(); // Automatically generate a password.
					$user_id  = wp_create_user( $username, $password, $email );
				} else {
					$user_id = wp_create_user( $username, $pass, $email );
				}

				if ( ! is_wp_error( $user_id ) && 'on' !== $enable_verification ) {
					wp_new_user_notification( $user_id, null, 'user' );
					wp_send_json_success( array( 'message' => __( 'Registration successful. Please check your email.', 'loginpress-pro' ) ) );
				} elseif ( ! is_wp_error( $user_id ) && 'on' === $enable_verification ) {
					wp_send_json_success(
						array(
							'message' => __(
								'Registration successful. An email has been sent to the site administrator. The administrator will review the information that has been submitted and either approved or deny your request. You will receive an email with instructions on what you will need to do next. Thanks for your patience.',
								'loginpress-pro'
							),
						)
					);
				} else {
					wp_send_json_error( array( 'message' => __( 'An error occurred during registration. Please try again.', 'loginpress-pro' ) ) );
				}
			} else {
				wp_send_json_error( array( 'message' => __( 'An unknown error occurred. Please try again.', 'loginpress-pro' ) ) );
			}

			wp_die();
		}

		/**
		 * Handles the lost password form submission.
		 *
		 * Verifies the Captcha response (if Captcha is enabled) and then attempts to retrieve user info based on the input.
		 * If a user is found, generates and sends a password reset email to the user.
		 *
		 * @since 4.0.0
		 * @version 6.1.0
		 * @return void|null
		 */
		public function loginpress_widget_lost_password_process() {
			check_ajax_referer( 'loginpress_login_widget_security', 'nonce' );
			$user_login                  = isset( $_POST['user_login'] ) ? sanitize_text_field( wp_unslash( $_POST['user_login'] ) ) : '';
			$captcha_response            = isset( $_POST['captcha_response'] ) ? sanitize_text_field( wp_unslash( $_POST['captcha_response'] ) ) : '';
			$loginpress_captcha_settings = $this->captcha_settings;
			$cap_lost                    = isset( $loginpress_captcha_settings['captcha_enable']['lostpassword_form'] ) ? $loginpress_captcha_settings['captcha_enable']['lostpassword_form'] : false;
			$cap_lost_cf                 = isset( $loginpress_captcha_settings['captcha_enable_cf']['lostpassword_form'] ) ? $loginpress_captcha_settings['captcha_enable_cf']['lostpassword_form'] : false;
			$hcaptcha_lost               = isset( $loginpress_captcha_settings['hcaptcha_enable']['lostpassword_form'] ) ? $loginpress_captcha_settings['hcaptcha_enable']['lostpassword_form'] : 'off';
			$captchas_type               = isset( $loginpress_captcha_settings['captchas_type'] ) ? $loginpress_captcha_settings['captchas_type'] : 'type_recaptcha';

			if ( isset( $loginpress_captcha_settings['enable_captchas'] ) && 'on' === $loginpress_captcha_settings['enable_captchas'] ) {
				if ( ( 'off' !== $hcaptcha_lost && 'type_hcaptcha' === $captchas_type ) ||
				( 'off' !== $cap_lost && 'type_recaptcha' === $captchas_type ) ||
				( $cap_lost_cf && 'type_cloudflare' === $captchas_type ) ) {
					if ( $captcha_response ) {
						if ( $cap_lost || $cap_lost_cf ) {
							if ( 'type_recaptcha' === $loginpress_captcha_settings['captchas_type'] ) {
								$captcha = new LoginPress_Recaptcha( $this->general_settings, $loginpress_captcha_settings );
								add_filter( 'allow_password_reset', array( $captcha, 'loginpress_recaptcha_lostpassword_auth' ), 10, 2 );
							} elseif ( 'type_hcaptcha' === $loginpress_captcha_settings['captchas_type'] ) {
								$hcaptcha = new LoginPress_Hcaptcha( $loginpress_captcha_settings );
								add_filter( 'allow_password_reset', array( $hcaptcha, 'loginpress_hcaptcha_lostpassword_auth' ), 10, 2 );
							} elseif ( 'type_cloudflare' === $loginpress_captcha_settings['captchas_type'] ) {
								$cf = new LoginPress_Turnstile( $loginpress_captcha_settings );
								add_filter( 'allow_password_reset', array( $cf, 'loginpress_turnstile_auth_widget' ), 10, 4 );
							}
						}
					} else {
						wp_send_json_error( array( 'message' => __( 'Please verify Captcha.', 'loginpress-pro' ) ) );
						wp_die();
					}
				}
			}

			if ( empty( $user_login ) ) {
				wp_send_json_error( array( 'message' => __( 'Username or Email is required', 'loginpress-pro' ) ) );
			}

			// Attempt to retrieve user info based on input.
			$user = get_user_by( 'login', $user_login );
			if ( ! $user ) {
				$user = get_user_by( 'email', $user_login );
			}

			if ( ! $user ) {
				wp_send_json_error( array( 'message' => 'No user found with that username or email' ) );
			}

			// Generate and send the reset password email.
			$reset_email_sent = retrieve_password( $user_login );

			if ( is_wp_error( $reset_email_sent ) ) {
				wp_send_json_error( array( 'message' => $reset_email_sent->get_error_message() ) );
			}

			// Return success response.
			wp_send_json_success( array( 'message' => 'Password reset link has been sent to your email address.' ) );

			wp_die();
		}
	}
endif;

new LoginPress_Login_Widget();
