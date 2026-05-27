<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Plugin Name: LoginPress Pro
 * Plugin URI: https://loginpress.pro?utm_source=loginpress-pro&utm_medium=plugins&utm_campaign=loginpress-home&utm_content=plugin-uri
 * Description: LoginPress Pro adds premium features in LoginPress core/free plugin.
 * Version: 6.1.2
 * Author: WPBrigade
 * Author URI: https://wpbrigade.com/?utm_source=loginpress-pro&utm_medium=plugins&utm_campaign=wpbrigade-home&utm_content=author-uri
 * License: GPLv2+
 * Text Domain: loginpress-pro
 * Domain Path: /languages
 * Requires at least: 4.0
 * Tested up to: 6.9
 * Requires Plugins: loginpress
 * GitHub Plugin URI: https://github.com/WPBrigade/loginpress-pro
 *
 * @package LoginPress-pro
 */

if ( ! class_exists( 'LoginPress_Pro_Init' ) ) :

	/**
	 * LoginPress Pro Initialization Class
	 *
	 * @since 3.0.0
	 * @version 6.1.2
	 */
	class LoginPress_Pro_Init {

		/**
		 * Version number
		 *
		 * @var string
		 */
		public $version = '6.1.2';

		/**
		 * Instance variable
		 *
		 * @var [bool] $instance
		 * @since 1.0.0
		 */
		private static $instance = null;

		/**
		 * Constructor Function
		 *
		 * @version 6.1.0
		 * @since 1.0.0
		 */
		private function __construct() {

			$this->define_constants();
			$this->hooks();
		}

		/**
		 * Define LoginPress Constants
		 */
		private function define_constants() {

			$this::define( 'LOGINPRESS_PRO_ADDONS_DIR', plugin_dir_url( __FILE__ ) . 'addons' );
			$this::define( 'LOGINPRESS_PRO_DIR_URL', plugin_dir_url( __FILE__ ) );
			$this::define( 'LOGINPRESS_PRO_ROOT_PATH', __DIR__ );
			$this::define( 'LOGINPRESS_PRO_UPGRADE_PATH', __FILE__ );
			$this::define( 'LOGINPRESS_PRO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
			$this::define( 'LOGINPRESS_PRO_THEME', LOGINPRESS_PRO_ROOT_PATH . '/themes/' );
			$this::define( 'LOGINPRESS_PRO_DIR_PATH', plugin_dir_path( __FILE__ ) );
			$this::define( 'LOGINPRESS_PRO_PLUGIN_ROOT', dirname( plugin_basename( __FILE__ ) ) );
			$this::define( 'LOGINPRESS_PRO_STORE_URL', 'https://WPBrigade.com' );
			$this::define( 'LOGINPRESS_PRO_PRODUCT_NAME', 'LoginPress Pro' );
			$this::define( 'LOGINPRESS_PRO_VERSION', $this->version );
			$this->define( 'LOGINPRESS_REST_NAMESPACE', 'loginpress/v1' );
			$this::define( 'LOGINPRESS_CF_TURNSTILE_URL', 'https://challenges.cloudflare.com/turnstile/v0/api.js' );
			$this::define( 'LOGINPRESS_GOOGLE_RECAPTCHA_URL', 'https://www.google.com/recaptcha/api.js?render=explicit' );
			$this::define( 'LOGINPRESS_HCAPTCHA_URL', 'https://js.hcaptcha.com/1/api.js' );
			$this::define( 'LOGINPRESS_PRO_REACT_JS', LOGINPRESS_PRO_DIR_URL . 'build/index.js' );
			$this::define( 'LOGINPRESS_PRO_REACT_CSS', LOGINPRESS_PRO_DIR_URL . 'build/index.css' );
			$this::define( 'LOGINPRESS_RECAPTCHA_VERIFY_URL', 'https://www.google.com/recaptcha/api/siteverify' );
		}

		/**
		 * Define constant if not already set.
		 *
		 * @param string $name Name of the constant.
		 * @param string $value the value of the constant.
		 * @return void
		 */
		public static function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 *
		 * @since 3.0.0
		 * @version 6.1.0
		 */
		public function hooks() {

			add_action( 'plugins_loaded', array( $this, 'loginpress_instance' ), 20 );
			register_activation_hook( __FILE__, array( $this, 'loginpress_pro_activate' ) );
			register_deactivation_hook( __FILE__, array( $this, 'loginpress_deactivate' ) );
			add_action( 'wp_ajax_loginpress_activate_free', array( $this, 'loginpress_plugin_activation' ) );
			add_action( 'admin_notices', array( $this, 'loginpress_show_update_notice' ) );
		}

		/**
		 * LoginPress Instance
		 *
		 * @return void
		 */
		public function loginpress_instance() {

			add_action( 'admin_enqueue_scripts', array( $this, 'loginpress_pro_admin_action_scripts' ) );

			// Makes sure the plugin is defined before trying to use it.
			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}
			if ( ! class_exists( 'LoginPress' ) && file_exists( WP_PLUGIN_DIR . '/loginpress/loginpress.php' ) ) {
				add_action( 'admin_notices', array( $this, 'loginpress_activate_free_activation' ) );
			} elseif ( ! file_exists( WP_PLUGIN_DIR . '/loginpress/loginpress.php' ) ) {
				add_action( 'admin_notices', array( $this, 'lp_update_free' ) );
			}
			if ( is_multisite() && is_plugin_active_for_network( 'loginpress/loginpress.php' ) ) { // @codingStandardsIgnoreLine.
				// Plugin is activated.
			} elseif ( ! class_exists( 'LoginPress' ) ) {
				add_action( 'admin_menu', array( $this, 'loginpress_pro_register_action_page' ) );
				return;
			}

			if ( ! class_exists( 'LoginPress' ) ) {
				return;
			}

			// Add 3.0 into notice
			// delete_site_option('loginpress_pro_intro_dismiss');.
			$dismissed = isset( $_GET['loginpress_pro_intro_dismiss'] ) && ! empty( $_GET['loginpress_pro_intro_dismiss'] ) ? $_GET['loginpress_pro_intro_dismiss'] : false; // @codingStandardsIgnoreLine.

			if ( false !== get_site_option( 'loginpress_pro_intro_dismiss' ) || $dismissed ) {
				$this->loginpress_pro_notice_dismiss( 'loginpress-pro-intro-dismiss-nonce', 'loginpress_pro_intro_dismiss' );
			}

			include_once LOGINPRESS_PRO_ROOT_PATH . '/classes/loginpress-setup-30.php';
			new LoginPress_Pro_Setup_30( true );

			include_once LOGINPRESS_PRO_ROOT_PATH . '/classes/loginpress-main.php';
			new LoginPress_Pro();
		}

		/**
		 * Update loginpress to 6.0.0 notice on loginpress pages.
		 *
		 * @since 4.0.0
		 * @version 6.1.0
		 */
		public function loginpress_show_update_notice() {

			if ( isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$page = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( strpos( $page, 'loginpress' ) === 0 && version_compare( LOGINPRESS_VERSION, '6.0.0', '<' ) ) {

					$update_url = admin_url( 'update.php?action=upgrade-plugin&plugin=loginpress%2Floginpress.php&_wpnonce=' . wp_create_nonce( 'upgrade-plugin_loginpress/loginpress.php' ) );
					// Output the notification.
					?>
				<div class="loginpress-notification-bar" >
					<p><?php echo esc_html__( 'A new version of LoginPress is available.', 'loginpress-pro' ); ?> <a href="<?php echo esc_url( $update_url ); ?>" target="_self">
					<?php echo esc_html__( 'Update now ', 'loginpress-pro' ); ?></a><?php echo esc_html__( 'to access new features.', 'loginpress-pro' ); ?></p>
				</div>
					<?php
				}
			}
		}


		/**
		 * Notice if LoginPress Free is not activate.
		 *
		 * @since 3.0.6
		 */
		public function loginpress_activate_free_activation() {

			$action = 'activate';
			$slug   = 'loginpress/loginpress.php';
			$link   = wp_nonce_url(
				add_query_arg(
					array(
						'action' => $action,
						'plugin' => $slug,
					),
					admin_url( 'plugins.php' )
				),
				$action . '-plugin_' . $slug
			);

			printf(
				'<div class="notice notice-error is-dismissible">
		<p>%1$s<a href="%2$s" style="text-decoration:none">%3$s</a></p></div>',
				esc_html__( 'LoginPress Free is needed for LoginPress Pro &mdash; ', 'loginpress-pro' ),
				esc_url( $link ),
				esc_html__( 'Click here to activate LoginPress Free', 'loginpress-pro' )
			);
		}

		/**
		 * Check and Dismiss addon message.
		 *
		 * @param string $nonce nonce value.
		 * @param string $option option name.
		 * @since 1.1.3
		 * @version 6.1.0
		 * @return void
		 */
		private function loginpress_pro_notice_dismiss( $nonce, $option ) {

			if ( ! is_admin() ||
				! current_user_can( 'manage_options' ) ||
				! isset( $_GET['_wpnonce'] ) ||
				! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), $nonce ) ||
				! isset( $_GET[ $option ] ) ) {

				return;
			}

			add_site_option( $option, 'yes' );
		}


		/**
		 * Enqueue Admin Scripts
		 *
		 * @param [type] $hook current admin page.
		 * @return void
		 * @since 6.0.0
		 * @version 6.1.0
		 */
		public function loginpress_pro_admin_action_scripts( $hook ) {

			if ( 'toplevel_page_loginpress-settings' === $hook || 'users.php' === $hook ) {

				wp_enqueue_script( 'jquery' );
				wp_enqueue_script( 'loginpress-admin-action', plugins_url( 'assets/js/admin-action.js', __FILE__ ), array( 'jquery', 'wp-api-fetch', 'wp-element' ), LOGINPRESS_PRO_VERSION, false );

				wp_localize_script(
					'loginpress-admin-action',
					'loginpress_pro_local',
					array(
						'update_nonce' => wp_create_nonce( 'updates' ),
						'active_nonce' => wp_create_nonce( 'loginpress_active_free' ),
						'admin_url'    => admin_url( 'admin.php?page=loginpress-settings' ),
						'search_nonce' => wp_create_nonce( 'loginpress_autocomplete_search_nonce' ),
						// wp_rest nonce will be verified in Rest response automatically.
						'nonce'        => wp_create_nonce( 'wp_rest' ),
						'siteUrl'      => site_url(),
						'homeUrl'      => home_url(),
					)
				);

				if ( version_compare( LOGINPRESS_VERSION, '6.0.0', '>=' ) && version_compare( LOGINPRESS_PRO_VERSION, '6.0.0', '>=' ) ) {

					$react_script_asset_path = plugin_dir_path( __FILE__ ) . 'build/index.asset.php';
					if ( ! file_exists( $react_script_asset_path ) ) {
						// You can throw an error or simply return if the build file doesn't exist.
						return;
					}

					$react_script_asset = require $react_script_asset_path;

					// CRITICAL: Make the Pro React script dependent on the Free React script.
					// 'loginpress-free-script' is the handle you use in the free plugin.
					$react_dependencies   = $react_script_asset['dependencies'];
					$react_dependencies[] = 'loginpress-react-settings';

					wp_enqueue_script(
						'loginpress-pro-react-script', // A unique handle for the pro react script.
						LOGINPRESS_PRO_REACT_JS,
						$react_dependencies,
						LOGINPRESS_PRO_VERSION,
						true // Load in footer.
					);
					wp_set_script_translations(
						'loginpress-pro-react-script',
						'loginpress-pro',
						plugin_dir_path( __FILE__ ) . 'languages'
					);
					wp_enqueue_style( 'loginpress_pro_style_react', LOGINPRESS_PRO_REACT_CSS, array(), LOGINPRESS_PRO_VERSION );
				}
				wp_enqueue_style( 'loginpress-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), LOGINPRESS_PRO_VERSION );

			}
			wp_enqueue_style( 'loginpress-pro-admin-styles', plugins_url( 'assets/css/admin-notifications.css', __FILE__ ), array(), LOGINPRESS_PRO_VERSION );
		}

		/**
		 * Add Menu Page
		 *
		 * @return void
		 */
		public function loginpress_pro_register_action_page() {

			add_menu_page( __( 'LoginPress', 'loginpress-pro' ), __( 'LoginPress', 'loginpress-pro' ), 'manage_options', 'loginpress-settings', array( $this, 'loginpress_pro_main_menu' ), plugins_url( 'assets/img/icon.svg', __FILE__ ), 50 );
		}

		/**
		 * Add pro's main menu
		 *
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function loginpress_pro_main_menu() {

			include_once LOGINPRESS_PRO_ROOT_PATH . '/includes/require-free.php';
		}

		/**
		 * Update loginpress free
		 *
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function lp_update_free() {

			$action = 'install-plugin';
			$slug   = 'loginpress';
			$link   = wp_nonce_url(
				add_query_arg(
					array(
						'action'     => $action,
						'plugin'     => $slug,
						'is_install' => true,
					),
					admin_url( 'update.php' )
				),
				$action . '_' . $slug
			);

			$is_install_request = isset( $_GET['is_install'] ) && sanitize_text_field( wp_unslash( $_GET['is_install'] ) ) === '1' ? false : true; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $is_install_request ) {
				printf(
					'<div class="notice notice-error is-dismissible">
					<p>%1$s<a href="%2$s" style="text-decoration:none">%3$s</a></p></div>',
					esc_html__( 'Please update LoginPress to latest Free version to enable PRO features &mdash; ', 'loginpress-pro' ),
					esc_url( $link ),
					esc_html__( 'Install now', 'loginpress-pro' )
				);
			}
		}

		/**
		 * LoginPress Activation callback
		 * Activates all addons on first installation except login-logout-menu
		 *
		 * @since 6.0.0
		 * @version 6.0.2
		 * @return void
		 */
		public function loginpress_pro_activate() {
			// Check if this is the first activation.
			if ( false === get_option( 'loginpress_pro_addons' ) ) {
				require_once LOGINPRESS_DIR_PATH . 'classes/class-loginpress-addons-meta.php';
				// Use the existing method to create the addons array.
				if ( class_exists( 'LoginPress_Addons_Meta' ) ) {

					LoginPress_Addons_Meta::addons_options_array();
				}

				// Get the newly created option.
				$addons = get_option( 'loginpress_pro_addons' );

				// Activate all addons except login-logout-menu and login-widget.
				if ( $addons && is_array( $addons ) ) {
					$excluded_addons = array( 'login-logout-menu' );

					foreach ( $addons as $slug => $addon ) {
						if ( ! in_array( $slug, $excluded_addons, true ) ) {
							$addons[ $slug ]['is_active'] = true;
						}
					}
					// Update the option with activated addons.
					update_option( 'loginpress_pro_addons', $addons );
				}
			}
			global $wpdb;
			$limit_table  = $wpdb->prefix . 'loginpress_limit_login_details';
			$social_table = $wpdb->prefix . 'loginpress_social_login_details';
			require_once LOGINPRESS_PRO_DIR_PATH . 'classes/loginpress-manage-addons.php';
			if ( class_exists( 'LoginPress_Manage_Addons' ) ) {
				$manage_addons = new LoginPress_Manage_Addons();
				// Check and activate missing addons.
				if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $limit_table ) ) !== $limit_table ) { // phpcs:ignore
					$manage_addons->loginpress_pro_addon_activation_cb( 'limit-login-attempts' );
				}

				if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $social_table ) ) !== $social_table ) { // phpcs:ignore
					$manage_addons->loginpress_pro_addon_activation_cb( 'social-login' );
				}
			}
		}

		/**
		 * LoginPress Deactivation callback.
		 *
		 * @return void
		 */
		public function loginpress_deactivate() {

			$selected_preset          = get_option( 'customize_presets_settings', 'minimalist' );
			$loginpress_default_theme = 'default1' === $selected_preset ? 'default1' : 'minimalist';

			update_option( 'customize_presets_settings', $loginpress_default_theme );
		}

		/**
		 * [loginpress_plugin_activation LoginPress (Free) Plugin Activation Callback]
		 *
		 * @since 2.0.7
		 * @version 6.1.0
		 */
		public function loginpress_plugin_activation() {

			check_ajax_referer( 'loginpress_active_free', '_wpnonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No cheating, huh!' );
			}

			$plugin = isset( $_POST['path'] ) ? sanitize_text_field( wp_unslash( $_POST['path'] ) ) : '';

			if ( ! is_plugin_active( $plugin ) ) {
				activate_plugin( $plugin );
			}

			wp_die();
		}

		/**
		 * Main Instance
		 *
		 * @since 3.0.0
		 * @static
		 * @see loginPress_pro_loader()
		 * @return Main instance
		 */
		public static function instance() {

			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}
	}
endif;

// phpcs:disable Universal.Files.SeparateFunctionsFromOO.Mixed
/**
 * Check user permissions
 *
 * @since  6.0.0
 */
function loginpress_rest_can_manage_options() {
	return current_user_can( 'manage_options' );
}

/**
 * Returns the main instance of WP to prevent the need to use globals.
 *
 * @since  3.0.0
 * @return LoginPress_Pro_Init
 */
function loginpress_pro_loader() {

	return LoginPress_Pro_Init::instance();
}

// Call the function.
loginpress_pro_loader();
// phpcs:enable Universal.Files.SeparateFunctionsFromOO.Mixed
