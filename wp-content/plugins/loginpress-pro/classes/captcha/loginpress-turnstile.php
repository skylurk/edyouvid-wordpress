<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * LoginPress turnstile.
 *
 * @since 4.0.0
 * @version 5.0.1
 * @package LoginPress
 */

if ( ! class_exists( 'LoginPress_Turnstile' ) ) {

	/**
	 * LoginPress_Turnstile
	 *
	 * @since 4.0.0
	 * @version 6.1.0
	 */
	class LoginPress_Turnstile {

		/**
		 * Holds the singleton instance of the class.
		 *
		 * @var static|null
		 */
		protected static $instance = null;

		/**
		 * Variable that Check for Captcha settings.
		 *
		 * @var array
		 * @since 4.0.0
		 */
		public $loginpress_captcha_settings;

		/**
		 * Class Constructor.
		 *
		 * @since 4.0.0
		 * @version 6.1.0
		 * @param array $captcha Captcha settings.
		 * @return void
		 */
		public function __construct( $captcha ) {

			$this->loginpress_captcha_settings = $captcha;
			$this->hooks();
		}
		/**
		 * Add all hooks.
		 *
		 * @since 4.0.0
		 * @version 6.1.0
		 * @return void
		 */
		private function hooks() {

			add_action( 'loginpress_cf_validate_key', array( $this, 'loginpress_cf_validate_key' ), 10 );
			$captchas_enabled = isset( $this->loginpress_captcha_settings['enable_captchas'] ) ? $this->loginpress_captcha_settings['enable_captchas'] : 'off';

			if ( 'off' !== $captchas_enabled ) {
				$captchas_type = isset( $this->loginpress_captcha_settings['captchas_type'] ) ? $this->loginpress_captcha_settings['captchas_type'] : 'type_recaptcha';

				if ( 'type_cloudflare' === $captchas_type ) {

					/* Cloudflare CAPTCHA Settings */
					$cf_site_key   = isset( $this->loginpress_captcha_settings['site_key_cf'] ) ? $this->loginpress_captcha_settings['site_key_cf'] : '';
					$cf_secret_key = isset( $this->loginpress_captcha_settings['secret_key_cf'] ) ? $this->loginpress_captcha_settings['secret_key_cf'] : '';
					$validated     = isset( $this->loginpress_captcha_settings['validate_cf'] ) && 'on' === $this->loginpress_captcha_settings['validate_cf'] ? true : false;
					if ( ! empty( $cf_site_key ) && ! empty( $cf_secret_key ) && $validated ) {
						$cf_login        = isset( $this->loginpress_captcha_settings['captcha_enable_cf']['login_form'] ) ? $this->loginpress_captcha_settings['captcha_enable_cf']['login_form'] : false;
						$cf_lostpass     = isset( $this->loginpress_captcha_settings['captcha_enable_cf']['lostpassword_form'] ) ? $this->loginpress_captcha_settings['captcha_enable_cf']['lostpassword_form'] : false;
						$cf_register     = isset( $this->loginpress_captcha_settings['captcha_enable_cf']['register_form'] ) ? $this->loginpress_captcha_settings['captcha_enable_cf']['register_form'] : false;
						$cf_comments     = isset( $this->loginpress_captcha_settings['captcha_enable_cf']['comment_form_defaults'] ) ? $this->loginpress_captcha_settings['captcha_enable_cf']['comment_form_defaults'] : false;
						$cf_woo_login    = isset( $this->loginpress_captcha_settings['captcha_enable_cf']['woocommerce_login_form'] ) ? $this->loginpress_captcha_settings['captcha_enable_cf']['woocommerce_login_form'] : false;
						$cf_woo_register = isset( $this->loginpress_captcha_settings['captcha_enable_cf']['woocommerce_register_form'] ) ? $this->loginpress_captcha_settings['captcha_enable_cf']['woocommerce_register_form'] : false;
						// Add Turnstile to Login Form.
						$add_on     = get_option( 'loginpress_pro_addons' );
						$hide_login = 0;
						if ( isset( $add_on['hide-login']['is_active'] ) && $add_on['hide-login']['is_active'] ) {
							$hide_login = get_option( 'loginpress_hidelogin', array() );
							if ( ! isset( $hide_login['rename_login_slug'] ) ) {
								$hide_login = array( 'rename_login_slug' => '/wp-login.php' ); // Default value.
							}
						} else {
							$hide_login = array( 'rename_login_slug' => '/wp-login.php' ); // Default value.
						}
						if ( $cf_login ) {
							// Add turnstile fields and script to the widget forms.
							add_action(
								'loginpress_after_login_form_widget',
								function () {
									$this->loginpress_turnstile_field( 'login' );
									$this->loginpress_turnstile_script();
								}
							);
							add_action( 'login_form', array( $this, 'loginpress_turnstile_field' ) );
							add_action( 'login_enqueue_scripts', array( $this, 'loginpress_turnstile_script' ) );
							add_filter(
								'authenticate',
								function ( $user, $username, $password ) use ( $hide_login ) {
									// Exclude WooCommerce login requests.
									if ( isset( $_SERVER['REQUEST_URI'] ) && false !== strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), $hide_login['rename_login_slug'] ) ) {
											return $this->loginpress_turnstile_auth( $user, $username, $password, 'login' );
									}
										return $user;
								},
								99,
								3
							);
						}

						// Add reCaptcha to Lost Password Form.
						if ( $cf_lostpass ) {
							// Add reCaptcha fields and script to the widget forms.
							add_action(
								'loginpress_after_lost_password_form_widget',
								function () {
									$this->loginpress_turnstile_field( 'lost' );
									$this->loginpress_turnstile_script();
								}
							);
							add_action( 'lostpassword_form', array( $this, 'loginpress_turnstile_field' ) );
							add_action( 'login_enqueue_scripts', array( $this, 'loginpress_turnstile_script' ) );
							add_filter(
								'allow_password_reset',
								function ( $user, $username ) {
									return $this->loginpress_turnstile_auth( $user, $username, '', 'lostpassword' );
								},
								10,
								2
							);
						}

						// Add reCaptcha to Registration Form.
						if ( $cf_register ) {
							// Add reCaptcha fields and script to the widget forms.
							add_action(
								'loginpress_after_reg_form_widget',
								function () {
									$this->loginpress_turnstile_field( 'register' );
									$this->loginpress_turnstile_script();
								}
							);
							add_action( 'register_form', array( $this, 'loginpress_turnstile_field' ) );
							add_action( 'login_enqueue_scripts', array( $this, 'loginpress_turnstile_script' ) );
							add_filter(
								'registration_errors',
								function ( $errors, $sanitized_user_login, $user_email ) {
									return $this->loginpress_turnstile_auth( $errors, $sanitized_user_login, $user_email, 'register' );
								},
								96,
								3
							);
						}

						// Add Turnstile to Comments Section.
						if ( $cf_comments ) {
							add_action( 'comment_id_fields', array( $this, 'loginpress_turnstile_to_comment_form' ) );
							add_action( 'comment_form', array( $this, 'loginpress_turnstile_script' ) );
							add_action(
								'pre_comment_on_post',
								function ( $comment_post_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
									return $this->loginpress_turnstile_auth( null, '', '', 'comment' );
								},
								10,
								1
							);
						}
					}
				}
			}
		}

		/**
		 * Class Methods for handling Cloudflare Turnstile integration.
		 *
		 * @since 4.0.0
		 * @version 6.1.0
		 * @param string $form_type Form type identifier.
		 * @return void
		 */
		public function loginpress_turnstile_field( $form_type = '' ) {
			// Fetch the site key from the plugin settings.
			$site_key = isset( $this->loginpress_captcha_settings['site_key_cf'] ) ? $this->loginpress_captcha_settings['site_key_cf'] : '';
			$theme    = isset( $this->loginpress_captcha_settings['cf_theme'] ) ? $this->loginpress_captcha_settings['cf_theme'] : 'light'; // Default to 'light' if not set
			// If the site key is available, render the Turnstile widget.
			if ( $site_key ) {
				$id = 'cf-turnstile-' . esc_attr( $form_type ); // Dynamically set the ID
				// Wrapper div to center-align the Turnstile widget with responsive scaling.
				echo '<div id="' . esc_attr( $id ) . '" class="cf-turnstile-wrapper" align="left">';
				// Render the Cloudflare Turnstile widget with responsive JavaScript scaling applied.
				echo '<div class="cf-turnstile" data-sitekey="' . esc_attr( $site_key ) . '" data-theme="' . esc_attr( $theme ) . '" style="width: 300px; height: auto;"></div>';
				echo '</div>';
			}
		}

		/**
		 * Enqueue the Turnstile JavaScript API from Cloudflare to load the widget.
		 *
		 * @since 4.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function loginpress_turnstile_script() {
			wp_enqueue_script( 'cloudflare-turnstile', LOGINPRESS_CF_TURNSTILE_URL, array(), LOGINPRESS_PRO_VERSION, true );
		}

		/**
		 * Verify the user's Turnstile response and authenticate the user.
		 *
		 * @since 4.0.0
		 * @version 6.1.0
		 * @param WP_User|WP_Error|null $user     The user object, WP_Error on failure, or null if not authenticated.
		 * @param string                $username The username.
		 * @param string                $password The password.
		 * @param string                $form_type The form type (e.g., login, register, lostpassword, etc.).
		 * @return WP_User|WP_Error The user object on success, or WP_Error on failure.
		 */
		public function loginpress_turnstile_auth( $user = null, $username = '', $password = '', $form_type = '' ) {

			/**
			 * Filter to conditionally bypass Turnstile validation for specific login forms.
			 *
			 * This filter allows developers to programmatically skip Turnstile verification
			 * during the authentication process based on custom logic (e.g., username, form type).
			 *
			 * Returning true will bypass Turnstile validation. Returning false will allow it to proceed.
			 *
			 * @param bool   $bypass     Whether to bypass Turnstile validation. Default false.
			 * @param string $username   The username or email attempting to log in.
			 * @param string $form_type  The form context: 'login', 'register', 'reset', etc.
			 *
			 * @since 5.0.1
			 * @return bool
			 */
			if ( apply_filters( 'loginpress_before_turnstile_validation', false, $username, $form_type ) ) {
				return $user;
			}

			if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
				return $user;
			}

			// Retrieve the secret key from the plugin settings.
			$secret_key = isset( $this->loginpress_captcha_settings['secret_key_cf'] ) ? $this->loginpress_captcha_settings['secret_key_cf'] : '';
			// Sanitize the Turnstile response from the form submission.
			$response = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( isset( $_POST['captcha_response'] ) || isset( $_POST['woocommerce-lost-password-nonce'] ) || isset( $_POST['woocommerce-register-nonce'] ) || isset( $_POST['_llms_login_user_nonce'] ) || isset( $_POST['learndash-login-form'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return $user;
			}
			// If no response is received, return a captcha error.
			if ( ! $response ) {
				$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
				// Case: Login form on /wp-login.php without "action=register".
				if ( 'login' === $form_type && false !== strpos( $request_uri, '/wp-login.php' ) && false === strpos( $request_uri, '/wp-login.php?action=register' ) ) {
					return new WP_Error( 'captcha_error', __( 'Please wait for the captcha to complete in the login form.', 'loginpress-pro' ) );
				} elseif ( 'register' === $form_type && false !== strpos( $request_uri, '/wp-login.php?action=register' ) ) { // Case: Register form on /wp-login.php with "action=register".
					return new WP_Error( 'captcha_error', __( 'Please wait for the captcha to complete in the registration form.', 'loginpress-pro' ) );
				} elseif ( 'login' !== $form_type && 'register' !== $form_type && false === strpos( $request_uri, '/wp-login.php' ) ) { // Case: Any other form or unknown location (e.g., comments, lost password, etc.).
					return new WP_Error( 'captcha_error', __( 'Please wait for the captcha to complete.', 'loginpress-pro' ) );
				} else {
					return new WP_Error( 'captcha_error', __( 'Please wait for the captcha to complete.', 'loginpress-pro' ) );
				}
			}
			$remote_ip = ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) : ( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' );
			// Verify the Turnstile response with Cloudflare's siteverify API.
			$verify_response = wp_remote_post(
				'https://challenges.cloudflare.com/turnstile/v0/siteverify',
				array(
					'body' => array(
						'secret'   => $secret_key,         // Your secret key.
						'response' => $response,           // Captcha response from user.
						'remoteip' => $remote_ip, // User's IP address.
					),
				)
			);

			// Retrieve and decode the API response.
			$response_body = wp_remote_retrieve_body( $verify_response );
			$result        = json_decode( $response_body, true );

			if ( empty( $result['success'] ) ) {
				$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
				// Case: Login form on /wp-login.php without "action=register".
				if ( 'login' === $form_type && false !== strpos( $request_uri, '/wp-login.php' ) && false === strpos( $request_uri, 'action=register' ) ) {
					return new WP_Error( 'captcha_error', $this->loginpress_turnstile_error() );
				} elseif ( 'register' === $form_type && false !== strpos( $request_uri, '/wp-login.php?action=register' ) ) { // Case: Register form on /wp-login.php with "action=register".
					return new WP_Error( 'captcha_error', $this->loginpress_turnstile_error() );
				} elseif ( 'login' !== $form_type && 'register' !== $form_type && false === strpos( $request_uri, '/wp-login.php' ) ) { // Case: Any other form or unknown location (e.g., comments, lost password, etc.).
					return new WP_Error( 'captcha_error', $this->loginpress_turnstile_error() );
				} else {
					return new WP_Error( 'captcha_error', $this->loginpress_turnstile_error() );
				}
			}

			// If everything is valid, return the user object for successful login.
			return $user;
		}
		/**
		 * Verify the user's Turnstile response and authenticate the user in login widget.
		 *
		 * @since 4.0.0
		 * @version 6.1.0
		 * @param WP_User|WP_Error|null $user     The user object, WP_Error on failure, or null if not authenticated.
		 * @param string                $username The username.
		 * @param string                $password The password.
		 * @param string                $form_type The form type (e.g., login, register, lostpassword, etc.).
		 * @return WP_User|WP_Error The user object on success, or WP_Error on failure.
		 */
		public function loginpress_turnstile_auth_widget( $user = null, $username = '', $password = '', $form_type = '' ) {
			if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
				return $user;
			}
			// Retrieve the secret key from the plugin settings.
			$secret_key = isset( $this->loginpress_captcha_settings['secret_key_cf'] ) ? $this->loginpress_captcha_settings['secret_key_cf'] : '';
			// Sanitize the Turnstile response from the form submission.
			$response = isset( $_POST['captcha_response'] ) ? sanitize_text_field( wp_unslash( $_POST['captcha_response'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			// If no response is received, return a captcha error.
			if ( ! $response ) {
				$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
				// Case: Login form on /wp-login.php without "action=register".
				if ( 'login' === $form_type && false !== strpos( $request_uri, '/wp-login.php' ) && false === strpos( $request_uri, '/wp-login.php?action=register' ) ) {
					return new WP_Error( 'captcha_error', __( 'Please wait for the captcha to complete in the login form.', 'loginpress-pro' ) );
				} elseif ( 'register' === $form_type && false !== strpos( $request_uri, '/wp-login.php?action=register' ) ) { // Case: Register form on /wp-login.php with "action=register".
					return new WP_Error( 'captcha_error', __( 'Please wait for the captcha to complete in the registration form.', 'loginpress-pro' ) );
				} elseif ( 'login' !== $form_type && 'register' !== $form_type && false === strpos( $request_uri, '/wp-login.php' ) ) { // Case: Any other form or unknown location (e.g., comments, lost password, etc.).
					return new WP_Error( 'captcha_error', __( 'Please wait for the captcha to complete.', 'loginpress-pro' ) );
				} else {
					return new WP_Error( 'captcha_error', __( 'Please wait for the captcha to complete.', 'loginpress-pro' ) );
				}
			}
			$remote_ip = ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) : ( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' );
			// Verify the Turnstile response with Cloudflare's siteverify API.
			$verify_response = wp_remote_post(
				'https://challenges.cloudflare.com/turnstile/v0/siteverify',
				array(
					'body' => array(
						'secret'   => $secret_key,         // Your secret key.
						'response' => $response,           // Captcha response from user.
						'remoteip' => $remote_ip, // User's IP address.
					),
				)
			);

			// Retrieve and decode the API response.
			$response_body = wp_remote_retrieve_body( $verify_response );
			$result        = json_decode( $response_body, true );
			if ( empty( $result['success'] ) ) {
				$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
				// Case: Login form on /wp-login.php without "action=register".
				if ( 'login' === $form_type && false !== strpos( $request_uri, '/wp-login.php' ) && false === strpos( $request_uri, 'action=register' ) ) {
					return new WP_Error( 'captcha_error', $this->loginpress_turnstile_error() );
				} elseif ( 'register' === $form_type && false !== strpos( $request_uri, '/wp-login.php?action=register' ) ) { // Case: Register form on /wp-login.php with "action=register".
					return new WP_Error( 'captcha_error', $this->loginpress_turnstile_error() );
				} elseif ( 'login' !== $form_type && 'register' !== $form_type && false === strpos( $request_uri, '/wp-login.php' ) ) { // Case: Any other form or unknown location (e.g., comments, lost password, etc.).
					return new WP_Error( 'captcha_error', $this->loginpress_turnstile_error() );
				} else {
					return new WP_Error( 'captcha_error', $this->loginpress_turnstile_error() );
				}
			}

			// If everything is valid, return the user object for successful login.
			return $user;
		}

		/**
		 * Verify the user's Turnstile response and authenticate the user for WooCommerce registration.
		 *
		 * @param WP_User|WP_Error|null $user     The user object, WP_Error on failure, or null if not authenticated.
		 * @param string                $username The username.
		 * @param string                $password The password.
		 * @param string                $form_type The form type (e.g., login, register, lostpassword, etc.).
		 *
		 * @since 4.0.0
		 * @return WP_User|WP_Error The user object on success, or WP_Error on failure.
		 */

		/**
		 * Inject the Turnstile widget into the comment form.
		 *
		 * @since 4.0.0
		 * @version 6.1.0
		 * @param array $default_fields Default comment form settings.
		 * @return array Modified comment form settings.
		 */
		public function loginpress_turnstile_to_comment_form( $default_fields ) {
			// Inject the Turnstile widget into the comment form.
			$this->loginpress_turnstile_field();

			// Return the default comment form settings.
			return $default_fields;
		}

		/**
		 * Get turnstile error message.
		 *
		 * @since 4.0.0
		 * @version 6.1.0
		 * @return string Custom error message.
		 */
		public function loginpress_turnstile_error() {

			$loginpress_settings = get_option( 'loginpress_customization' );
			$turnstile_message   = isset( $loginpress_settings['turnstile_error_message'] ) ? $loginpress_settings['turnstile_error_message'] : __( '<strong>ERROR:</strong> Captcha verification failed. Please try again.', 'loginpress-pro' );

			$allowed_html = array(
				'a'      => array(),
				'br'     => array(),
				'em'     => array(),
				'strong' => array(),
				'i'      => array(),
			);
			return wp_kses( $turnstile_message, $allowed_html );
		}

		/**
		 * Main Instance.
		 *
		 * @since 4.0.0
		 * @version 6.1.0
		 * @return static|null
		 */
		public static function instance() {

			if ( is_null( self::$instance ) ) {
				$loginpress_captcha_settings = get_option( 'loginpress_captcha_settings' );
				self::$instance              = new self( $loginpress_captcha_settings );
			}
			return self::$instance;
		}

		/**
		 * Validate the keys for turnstile.
		 *
		 * @since 4.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function loginpress_cf_validate_key() {

			$validated = isset( $this->loginpress_captcha_settings['validate_cf'] ) ? $this->loginpress_captcha_settings['validate_cf'] : 'off';
				$theme = isset( $this->loginpress_captcha_settings['cf_theme'] ) ? $this->loginpress_captcha_settings['cf_theme'] : 'light'; // Default to 'light' if not set.
						$this->loginpress_turnstile_script();
				$site_key = isset( $this->loginpress_captcha_settings['site_key_cf'] ) ? $this->loginpress_captcha_settings['site_key_cf'] : '';
				// If the site key is available, render the Turnstile widget.
			if ( $site_key ) {
				$this->loginpress_turnstile_field( 'validate' );
			} else {
				?>
					<div id="cf-turnstile-validate"></div>
				<?php
			}
			?>
				<script>
					jQuery(document).ready(function($) {
						const $captchaDropdown = $('#loginpress_captcha_settings\\[captchas_type\\]');
						var $site_key = $('input[name="loginpress_captcha_settings[site_key_cf]"]');
						var $secret_key = $('input[name="loginpress_captcha_settings[secret_key_cf]"]');
						var $submit_btn = $('#loginpress_captcha_settings #submit');
						var $state = 'false';
						var $cf_enabled = 'on';
						var theme = "<?php echo esc_js( $theme ); ?>"; // Pass PHP variable for theme
						var validated = "<?php echo esc_js( $validated ); ?>";
						$submit_btn.prop('disabled', false);
						function validate_turnstile($state = 'false') {
							// Reset Turnstile
							$submit_btn.prop('disabled', false);
			
							if (!$site_key.val() || !$secret_key.val()) {
								$submit_btn.prop('disabled', true);
							} else {
								if ($state === 'true' || $site_key.val() && $secret_key.val()) {

									$submit_btn.prop('disabled', true);
									$('#cf-turnstile-validate').html('');
									// Render Turnstile widget
									turnstile.render('#cf-turnstile-validate', {
										sitekey: $site_key.val(),
										theme:theme,
										callback: function() {
											$submit_btn.prop('disabled', false);
											$cf_enabled = 'on';
											
										},
									});
									$('tr.validate_cf').show();			
									$('#cf-turnstile-validate').show();
									return;
								}
							}
						}
						$site_key.on('input', function() {
							$('tr.site_key_cf svg').hide();
							if($secret_key.val().trim() !== ''){
								validate_turnstile('true');
								setTimeout(function() {
									if ($submit_btn.prop('disabled')) {
										$('#cf-turnstile-validate').html('<span style="color: red;">Failed to load Turnstile. Please enter correct site key.</span>');
									}
								}, 7000);
							}
						});
			
						$secret_key.on('input', function() {
							$('tr.secret_key_cf svg').hide();
							validate_turnstile('true');
						});

						var CorrectIcon = `<svg width="26" height="26" viewBox="0 0 26 26" xmlns="http://www.w3.org/2000/svg"><title>success</title><g fill="none" class="nc-icon-wrapper"><path fill-rule="evenodd" clip-rule="evenodd" d="M13 26c7.18 0 13-5.82 13-13S20.18 0 13 0 0 5.82 0 13s5.82 13 13 13z" fill="#22A753"/><path d="M11 14.586l6.293-6.293a1 1 0 1 1 1.414 1.414L11 17.414l-3.707-3.707a1 1 0 1 1 1.414-1.414L11 14.586z" fill="#fff"/></g></svg>`;
						var WrongIcon   = `<svg width="26" height="26" viewBox="0 0 26 26" xmlns="http://www.w3.org/2000/svg"><title>Error</title><g fill="none" class="nc-icon-wrapper"><path fill-rule="evenodd" clip-rule="evenodd" d="M13 26c7.18 0 13-5.82 13-13S20.18 0 13 0 0 5.82 0 13s5.82 13 13 13z" fill="#CB2431"/><path fill-rule="evenodd" clip-rule="evenodd" d="M8.183 8.183a.625.625 0 0 0 0 .884L12.116 13l-3.933 3.933a.625.625 0 1 0 .884.884L13 13.884l3.933 3.933a.625.625 0 1 0 .884-.884L13.884 13l3.933-3.933a.625.625 0 1 0-.884-.884L13 12.116 9.067 8.183a.625.625 0 0 0-.884 0z" fill="#fff"/><path d="M8.183 9.067l-.353.354.353-.354zm0-.884L7.83 7.83l.353.353zM12.116 13l.354.354.353-.354-.353-.354-.354.354zm-3.933 3.933l-.353-.354.353.354zm0 .884l-.353.354.353-.354zM13 13.884l.354-.354-.354-.353-.354.353.354.354zm3.933 3.933l.354-.354-.354.354zm.884-.884l-.354.354.354-.354zM13.884 13l-.354-.354-.353.354.353.354.354-.354zm3.933-4.817l.354-.353-.354.353zm-.884 0l-.354-.353.354.353zM13 12.116l-.354.354.354.353.354-.353-.354-.354zM9.067 8.183l.354-.353-.354.353zm-.53.53a.125.125 0 0 1 0-.176l-.708-.708c-.439.44-.439 1.152 0 1.592l.708-.708zm3.933 3.933L8.537 8.713l-.708.708 3.934 3.933.707-.708zm-3.933 4.64l3.933-3.932-.707-.708L7.83 16.58l.707.708zm0 .177a.125.125 0 0 1 0-.176l-.708-.707c-.439.439-.439 1.151 0 1.59l.708-.707zm.176 0a.125.125 0 0 1-.176 0l-.708.707c.44.44 1.152.44 1.592 0l-.708-.707zm3.933-3.933l-3.933 3.933.708.707 3.933-3.933-.708-.707zm4.64 3.933l-3.932-3.933-.708.707 3.933 3.934.708-.708zm.177 0a.125.125 0 0 1-.176 0l-.707.707c.439.44 1.151.44 1.59 0l-.707-.707zm0-.176a.125.125 0 0 1 0 .176l.707.707c.44-.439.44-1.151 0-1.59l-.707.707zm-3.933-3.933l3.933 3.933.707-.707-3.933-3.934-.707.708zm3.933-4.64l-3.933 3.932.707.708 3.934-3.933-.708-.708zm0-.177a.125.125 0 0 1 0 .176l.707.708c.44-.44.44-1.152 0-1.591l-.707.707zm-.176 0a.125.125 0 0 1 .176 0l.707-.708a1.125 1.125 0 0 0-1.59 0l.707.708zm-3.933 3.933l3.933-3.933-.707-.708-3.934 3.934.708.707zm-4.64-3.933l3.932 3.933.708-.707L9.42 7.83l-.708.707zm-.177 0a.125.125 0 0 1 .176 0l.708-.708a1.125 1.125 0 0 0-1.591 0l.707.708z" fill="#fff"/></g></svg>`;
						if ( validated == 'on' ) {

							// Ensure wrapping happens only once
							if (!$site_key.parent().hasClass('cf-input-container')) {
								$site_key.wrap('<div class="cf-input-container"></div>');
								$secret_key.wrap('<div class="cf-input-container"></div>');
								$('.cf-input-container').append(CorrectIcon);
							}
							
							$submit_btn.prop('disabled', false);
							$('tr.validate_cf').hide();
						} else if ( validated != 'on' && $site_key.val() && $secret_key.val()) {
							$site_key.wrap('<div class="cf-input-container"></div>');
							$secret_key.wrap('<div class="cf-input-container"></div>');
							$('.cf-input-container').append(WrongIcon);
							$('tr.validate_cf').show();
						} else{
							$('tr.validate_cf').hide();
						}

					});
						</script>
						<?php
		}
	}
}
?>
