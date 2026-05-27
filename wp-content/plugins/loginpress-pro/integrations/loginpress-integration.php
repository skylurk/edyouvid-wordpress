<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * LoginPress Integration Class.
 *
 * Handles the integration of different LoginPress features with other plugins.
 * This includes initialization, settings access, and third-party service hooks
 * such as Turnstile, reCAPTCHA, or hCaptcha.
 *
 * @since 5.0.0
 * @version 6.1.0
 * @package LoginPress
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'LoginPress_Integration' ) ) {
	// Include the required loginpress integration settings trait.
	include_once LOGINPRESS_PRO_ROOT_PATH . '/integrations/settings-trait.php';
	/**
	 * LoginPress_Integration
	 *
	 * @since 5.0.0
	 * @version 6.1.0
	 */
	class LoginPress_Integration {
		use LoginPress_Integration_Settings_Trait;

		/**
		 * Variable that checks for LoginPress settings.
		 *
		 * @var array
		 * @since 5.0.0
		 */
		public $loginpress_settings;

		/**
		 * Variable that checks for Captcha settings.
		 *
		 * @var array
		 * @since 5.0.0
		 */
		public $loginpress_captcha_settings;

		/**
		 * Variable that contains the image of social login position.
		 *
		 * @var string
		 * @since 5.0.0
		 * @version 6.0.0
		 */
		public $loginpress_social_position_image;

		/**
		 * Class Constructor.
		 *
		 * @return void
		 * @since 5.0.0
		 */
		public function __construct() {

			if ( is_plugin_active( 'lifterlms/lifterlms.php' ) || is_plugin_active_for_network( 'lifterlms/lifterlms.php' ) ) {
				include_once LOGINPRESS_PRO_ROOT_PATH . '/integrations/lifterlms/loginpress-lifterlms.php';
			}
			if ( is_plugin_active( 'sfwd-lms/sfwd_lms.php' ) || is_plugin_active_for_network( 'sfwd-lms/sfwd_lms.php' ) ) {
				include_once LOGINPRESS_PRO_ROOT_PATH . '/integrations/learndash/loginpress-learndash.php';
			}

			if ( ( is_plugin_active( 'buddypress/bp-loader.php' ) || is_plugin_active_for_network( 'buddypress/bp-loader.php' ) )
				&& ! is_plugin_active( 'buddyboss-platform/bp-loader.php' )
			) {
				include_once LOGINPRESS_PRO_ROOT_PATH . '/integrations/buddypress/loginpress-buddypress.php';
			}
			if ( is_plugin_active( 'buddyboss-platform/bp-loader.php' ) || is_plugin_active_for_network( 'buddyboss-platform/bp-loader.php' ) ) {
				include_once LOGINPRESS_PRO_ROOT_PATH . '/integrations/buddyboss/loginpress-buddyboss.php';
			}
			if ( is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ) || is_plugin_active_for_network( 'easy-digital-downloads/easy-digital-downloads.php.php' ) ) {
				include_once LOGINPRESS_PRO_ROOT_PATH . '/integrations/edd/loginpress-edd.php';
			}
			if ( is_plugin_active( 'woocommerce/woocommerce.php' ) || is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) {
				include_once LOGINPRESS_PRO_ROOT_PATH . '/integrations/woocommerce/loginpress-woocommerce.php';
			}

			$this->loginpress_settings         = get_option( 'loginpress_setting' );
			$this->loginpress_captcha_settings = get_option( 'loginpress_captcha_settings' );
			$this->hooks();
		}

		/**
		 * Register Integration-related hooks for LoginPress.
		 *
		 * @return void
		 * @since 5.0.0
		 */
		private function hooks() {
			add_action( 'rest_api_init', array( $this, 'lp_integrations_register_routes' ) );
			add_filter( 'loginpress_settings_tab', array( $this, 'integrations_tab' ), 20 );
			add_filter( 'loginpress_settings_fields', array( $this, 'integration_settings_field' ), 10, 1 );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
			add_action( 'admin_init', array( $this, 'loginpress_pro_admin_init' ), 7 );

			$captchas_type = isset( $this->loginpress_captcha_settings['captchas_type'] ) ? $this->loginpress_captcha_settings['captchas_type'] : 'type_recaptcha';

			if ( ! class_exists( 'LoginPress_Recaptcha' ) ) {
				include_once LOGINPRESS_PRO_ROOT_PATH . '/classes/captcha/loginpress-recaptcha.php';
				new LoginPress_Recaptcha( $this->loginpress_settings, $this->loginpress_captcha_settings );
			} else {
				LoginPress_Recaptcha::instance();
			}

			if ( ! class_exists( 'LoginPress_Hcaptcha' ) ) {
				include_once LOGINPRESS_PRO_ROOT_PATH . '/classes/captcha/loginpress-hcaptcha.php';
				new LoginPress_Hcaptcha( $this->loginpress_captcha_settings );
			} else {
				LoginPress_Hcaptcha::instance();
			}

			if ( ! class_exists( 'LoginPress_Turnstile' ) ) {
				include_once LOGINPRESS_PRO_ROOT_PATH . '/classes/captcha/loginpress-turnstile.php';
				new LoginPress_Turnstile( $this->loginpress_captcha_settings );
			} else {
				LoginPress_Turnstile::instance();
			}
		}

		/**
		 * Register the rest routes for integrations.
		 *
		 * @return void
		 * @since 6.0.0
		 */
		public function lp_integrations_register_routes() {
			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/integration-settings',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'loginpress_get_integration_settings' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/integration-settings',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_update_integration_settings' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);
		}

		/**
		 * Check user permissions.
		 *
		 * @return WP_REST_Response The REST response.
		 * @since 6.0.0
		 */
		public function loginpress_get_integration_settings() {
			$settings = get_option( 'loginpress_integration_settings', array() );
			return new WP_REST_Response( $settings, 200 );
		}

		/**
		 * Check user permissions.
		 *
		 * @param WP_REST_Request $request The REST request object.
		 * @return WP_REST_Response The REST response.
		 * @since 6.0.0
		 */
		public function loginpress_update_integration_settings( WP_REST_Request $request ) {
			$params   = $request->get_params();
			$settings = isset( $params['settings'] ) ? $params['settings'] : array();

			// Sanitize the settings before saving.
			$sanitized_settings = array();

			// Checkbox fields.
			$checkbox_fields = array(
				'enable_social_woo_lf',
				'enable_social_woo_rf',
				'enable_social_woo_co',
				'enable_social_ld_lf',
				'enable_social_ld_rf',
				'enable_social_ld_qf',
				'enable_social_llms_lf',
				'enable_social_llms_rf',
				'enable_social_llms_co',
				'enable_social_login_links_bp',
				'enable_social_login_links_bb',
				'enable_social_edd_lf',
				'enable_social_edd_rf',
				'enable_social_edd_co',
			);

			foreach ( $checkbox_fields as $field ) {
				$sanitized_settings[ $field ] = ( isset( $settings[ $field ] ) && 'on' === $settings[ $field ] ) ? 'on' : 'off';
			}

			// Radio fields.
			$radio_fields = array(
				'social_position_woo_lf',
				'social_position_woo_rf',
				'social_position_woo_co',
				'social_position_ld_lf',
				'social_position_ld_rf',
				'social_position_llms_lf',
				'social_position_llms_rf',
				'social_position_llms_co',
				'social_position_bp',
				'social_position_bb',
				'social_position_edd_lf',
				'social_position_edd_rf',
				'social_position_edd_co',
			);

			foreach ( $radio_fields as $field ) {
				$sanitized_settings[ $field ] = isset( $settings[ $field ] ) ? sanitize_text_field( $settings[ $field ] ) : 'default';
			}

			// Multicheck fields.
			$multicheck_fields = array(
				'enable_captcha_woo',
				'enable_captcha_ld',
				'enable_captcha_llms',
				'enable_captcha_bp',
				'enable_captcha_bb',
				'enable_captcha_edd',
			);

			foreach ( $multicheck_fields as $field ) {
				if ( isset( $settings[ $field ] ) && is_array( $settings[ $field ] ) ) {
					$sanitized_settings[ $field ] = array();
					foreach ( $settings[ $field ] as $key => $value ) {
						$sanitized_settings[ $field ][ sanitize_key( $key ) ] = sanitize_text_field( $value );
					}
				} else {
					$sanitized_settings[ $field ] = array();
				}
			}

			update_option( 'loginpress_integration_settings', $sanitized_settings );

			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'Settings saved successfully.', 'loginpress-pro' ),
				),
				200
			);
		}

		/**
		 * Load CSS and JS files at admin side on loginpress-settings page only.
		 *
		 * @param string $hook The Page ID.
		 * @return void
		 * @since 5.0.0
		 */
		public function admin_scripts( $hook ) {
			if ( 'toplevel_page_loginpress-settings' === $hook ) {

				wp_enqueue_script( 'loginpress_integration', LOGINPRESS_PRO_DIR_URL . 'integrations/assets/js/main.js', array( 'jquery' ), LOGINPRESS_PRO_VERSION, true );
				wp_enqueue_style( 'loginpress_integration_css', LOGINPRESS_PRO_DIR_URL . 'integrations/assets/css/main.css', array(), LOGINPRESS_PRO_VERSION, false );
				wp_localize_script(
					'loginpress_integration',
					'loginpress_redirect_sociallogins',
					array(
						'group_nonce' => wp_create_nonce( 'loginpress-group-redirects-nonce' ),
						'translate'   => array(
							// translators: Label for LearnDash group search field in Login Redirect addon.
								_x( 'Search Group', 'The label Text of Login Redirect addon learndash group search field', 'loginpress-pro' ),
							// translators: Description text for LearnDash group tab's search field in Login Redirect addon.
							_x( 'Search group For Whom To Apply Redirects', 'LoginPress Redirects Description text for Specific learndash group tab\'s search', 'loginpress-pro' ),
						),
					)
				);

				wp_localize_script(
					'loginpress_integration',
					'loginpress_redirect_learndash',
					array(
						'translate' => array(
							// translators: Description text for Specific Roles tab's search field in Login Redirect addon.
							_x( 'Search Role For Whom To Apply Redirects', 'LoginPress Redirects Description text for Specific Roles tab\'s search', 'loginpress-pro' ),
							// translators: Search data.
							sprintf( _x( '%1$sSearch user\'s data from below the list%2$s', 'Search Label on Data tables', 'loginpress-pro' ), '<p class="description">', '</p>' ),
							// translators: Placeholder text for the search keyword field for autologin users.
							_x( 'Enter keyword', 'The search keyword for the autologin users.', 'loginpress-pro' ),
						),
					)
				);
				wp_localize_script(
					'loginpress_integration',
					'loginpress_integration_data',
					array(
						'plugins' => array(
							'woocommerce' => array(
								'description' => esc_html__( 'Quick, secure logins for your WooCommerce store.', 'loginpress-pro' ),
								'status'      => $this->loginpress_get_plugin_status( 'woocommerce/woocommerce.php' ),
							),
							'edd'         => array(
								'description' => esc_html__( 'Secure digital purchases with login enhancements.', 'loginpress-pro' ),
								'status'      => $this->loginpress_get_plugin_status( 'easy-digital-downloads/easy-digital-downloads.php' ),
							),
							'buddypress'  => array(
								'description' => esc_html__( 'Boost community logins with social and captcha support.', 'loginpress-pro' ),
								'status'      => $this->loginpress_get_plugin_status( 'buddypress/bp-loader.php' ),
							),
							'buddyboss'   => array(
								'description' => esc_html__( 'Hassle-free login experience for your BuddyBoss community.', 'loginpress-pro' ),
								'status'      => $this->loginpress_get_plugin_status( 'buddyboss-platform/bp-loader.php' ),
							),
							'lifterlms'   => array(
								'description' => esc_html__( 'Let students log in easily and securely.', 'loginpress-pro' ),
								'status'      => $this->loginpress_get_plugin_status( 'lifterlms/lifterlms.php' ),
							),
							'learndash'   => array(
								'description' => esc_html__( 'Simplify learning access with our login tools.', 'loginpress-pro' ),
								'status'      => $this->loginpress_get_plugin_status( 'sfwd-lms/sfwd_lms.php' ),
							),
						),
					),
				);
				wp_localize_script(
					'loginpress_integration',
					'loginpress_integration_translations',
					array(
						'learnMore'        => _x( 'Configuration Guide', 'Link to documentation', 'loginpress-pro' ),
						'back'             => esc_html__( 'Back', 'loginpress-pro' ),
						'messageFirst'     => esc_html__( 'Activate', 'loginpress-pro' ),
						'messageLast'      => esc_html__( 'to proceed', 'loginpress-pro' ),
						'configure'        => esc_html__( 'Configure', 'loginpress-pro' ),
						'helpCenter'       => esc_html__( 'Help Center', 'loginpress-pro' ),
						'followGuide'      => esc_html__( 'Follow our step-by-step guide for', 'loginpress-pro' ),
						'integrationGuide' => esc_html__( 'Integration Guide', 'loginpress-pro' ),
					)
				);
			}
		}

		/**
		 * Check the status of each plugin.
		 *
		 * @param string $plugin_file Plugins main file.
		 * @return string The status of the provider plugin.
		 * @since 5.0.0
		 */
		public function loginpress_get_plugin_status( $plugin_file ) {
			$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;

			if ( ! file_exists( $plugin_path ) ) {
				return esc_html__( 'learn-more', 'loginpress-pro' );
			}

			if ( ! is_plugin_active( $plugin_file ) ) {
				return esc_html__( 'not-active', 'loginpress-pro' );
			}

			// Specific handling to prevent false positives between BuddyPress and BuddyBoss.
			if ( 'buddypress/bp-loader.php' === $plugin_file && class_exists( 'LoginPress_Buddyboss_Integration' ) ) {
				return esc_html__( 'not-active', 'loginpress-pro' ); // BuddyBoss is active, not BuddyPress.
			}

			return esc_html__( 'active', 'loginpress-pro' );
		}

		/**
		 * Initialize admin settings for LoginPress Pro.
		 *
		 * @return void
		 * @since 5.0.0
		 */
		public function loginpress_pro_admin_init() {
			$captchas_enabled = isset( $this->loginpress_captcha_settings['enable_captchas'] ) ? $this->loginpress_captcha_settings['enable_captchas'] : 'off';
			if ( defined( 'LOGINPRESS_PRO_VERSION' ) && version_compare( LOGINPRESS_PRO_VERSION, '4.0.0', '>=' ) && 'off' !== $captchas_enabled ) {
				$captcha_settings     = $this->loginpress_captcha_settings;
				$integration_settings = get_option( 'loginpress_integration_settings', array() );
				if ( ! empty( $integration_settings ) ) {
					return;
				}
				if ( ! is_array( $integration_settings ) ) {
					$integration_settings = array(); // Fallback to empty array.
				}

				// WooCommerce CAPTCHA settings keys.
				$woocommerce_keys = array( 'woocommerce_login_form', 'woocommerce_register_form' );
				$captchas_type    = isset( $this->loginpress_captcha_settings['captchas_type'] ) ? $this->loginpress_captcha_settings['captchas_type'] : 'type_recaptcha';
				// Check if WooCommerce CAPTCHA settings exist.
				$woocommerce_captcha_settings = array();
				if ( 'type_hcaptcha' === $captchas_type ) {
					if ( isset( $captcha_settings['hcaptcha_enable'] ) ) {
						foreach ( $woocommerce_keys as $key ) {
							if ( isset( $captcha_settings['hcaptcha_enable'][ $key ] ) ) {
								$woocommerce_captcha_settings[ $key ] = $captcha_settings['hcaptcha_enable'][ $key ];
							}
						}
					}
				} elseif ( 'type_cloudflare' === $captchas_type ) {
					if ( isset( $captcha_settings['captcha_enable_cf'] ) ) {
						foreach ( $woocommerce_keys as $key ) {
							if ( isset( $captcha_settings['captcha_enable_cf'][ $key ] ) ) {
								$woocommerce_captcha_settings[ $key ] = $captcha_settings['captcha_enable_cf'][ $key ];
							}
						}
					}
				} elseif ( isset( $captcha_settings['captcha_enable'] ) ) {
					foreach ( $woocommerce_keys as $key ) {
						if ( isset( $captcha_settings['captcha_enable'][ $key ] ) ) {
							$woocommerce_captcha_settings[ $key ] = $captcha_settings['captcha_enable'][ $key ];
						}
					}
				}
				// If there are WooCommerce CAPTCHA settings, migrate them.
				if ( ! empty( $woocommerce_captcha_settings ) ) {
					// Add settings to integration options.
					$integration_settings['enable_captcha_woo'] = $woocommerce_captcha_settings;

					// Remove WooCommerce settings from captcha settings.
					foreach ( $woocommerce_keys as $key ) {
						unset( $captcha_settings['captcha_enable'][ $key ] );
					}

					// Save the updated settings.
					update_option( 'loginpress_integration_settings', $integration_settings );
					update_option( 'loginpress_captcha_settings', $captcha_settings );
				}
			}
		}
	}
}
