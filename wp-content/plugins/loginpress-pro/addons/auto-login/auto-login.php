<?php // phpcs:ignore
/**
 * Addon Name: LoginPress - Auto Login
 * Description: LoginPress - Auto Login is the best Login plugin by <a href="https://wpbrigade.com/">WPBrigade</a> which allows you to login without Username and Password.
 *
 * @package loginPress
 * @category Core
 * @author WPBrigade
 * @version 6.1.0
 */

if ( ! class_exists( 'LoginPress_AutoLogin' ) ) :
		include_once LOGINPRESS_PRO_DIR_PATH . 'addons/auto-login/classes/class-loginpress-autologin-utilities.php';
		include_once LOGINPRESS_PRO_DIR_PATH . 'addons/auto-login/classes/traits/core.php';
		include_once LOGINPRESS_PRO_DIR_PATH . 'addons/auto-login/classes/traits/admin.php';
		include_once LOGINPRESS_PRO_DIR_PATH . 'addons/auto-login/classes/traits/ajax.php';
		/**
		 * LoginPress AutoLogin Class
		 *
		 * @since 3.0.0
		 * @version 6.1.0
		 */
	final class LoginPress_AutoLogin {

		use LoginPress_AutoLogin_Core_Trait;
		use LoginPress_AutoLogin_Admin_Trait;
		use LoginPress_AutoLogin_Ajax_Trait;

		/**
		 * Class constructor.
		 *
		 * @return void
		 * @since 3.0.0
		 */
		public function __construct() {

			if ( LoginPress_Pro::addon_wrapper( 'auto-login' ) ) {
				$this->hooks();
				$this->define_constants();
				$this->includes();
			}
		}

		/**
		 * Hook into actions and filters.
		 *
		 * @since 1.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function hooks() {
			add_action( 'rest_api_init', array( $this, 'lp_autologin_register_routes' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
			add_filter( 'loginpress_settings_tab', array( $this, 'loginpress_autologin_tab' ), 10, 1 );
			add_filter( 'loginpress_settings_fields', array( $this, 'loginpress_autologin_settings_array' ), 10, 1 );
			add_filter( 'loginpress_autologin', array( $this, 'loginpress_autologin_callback' ), 10, 1 );
			$utilities = new LoginPress_AutoLogin_Utilities();
			add_action( 'admin_footer', array( $utilities, 'loginpress_autologin_autocomplete_js' ) );
			add_action( 'wp_ajax_loginpress_autologin', array( $this, 'autologin_update_user_meta' ) );
			add_action( 'wp_ajax_loginpress_autologin_emailuser', array( $this, 'loginpress_autologin_emailuser' ) );
			add_action( 'wp_ajax_loginpress_autologin_delete', array( $this, 'autologin_delete_user_meta' ) );
			add_action( 'wp_ajax_loginpress_change_autologin_state', array( $this, 'loginpress_change_autologin_state' ) );
			add_action( 'wp_ajax_loginpress_update_duration', array( $this, 'loginpress_update_duration' ) );
			add_action( 'wp_ajax_loginpress_update_email', array( $this, 'loginpress_update_email' ) );
			add_action( 'wp_ajax_loginpress_update_link_count', array( $this, 'loginpress_update_link_count' ) );
			add_action( 'wp_ajax_loginpress_populate_popup_duration', array( $this, 'loginpress_populate_popup_duration' ) );
			add_action( 'wp_ajax_loginpress_populate_popup_email', array( $this, 'loginpress_populate_popup_email' ) );
			add_action( 'wp_ajax_loginpress_populate_link_count', array( $this, 'loginpress_populate_link_count' ) );
			add_action( 'init', array( $this, 'init_link_count_updater' ), 10 );
			add_action( 'loginpress_autologin_script', array( $this, 'autologin_script_content' ) );
			add_action( 'wp_ajax_loginpress_autologin_script', array( $this, 'autologin_script_html' ) );
		}


		/**
		 * Includes include files.
		 *
		 * @since 1.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function includes() {

			include_once LOGINPRESS_AUTOLOGIN_DIR_PATH . 'classes/class-user-login.php';
		}

		/**
		 * Define LoginPress AutoLogin Constants.
		 *
		 * @return void
		 * @since 1.0.0
		 */
		private function define_constants() {
			LoginPress_Pro_Init::define( 'LOGINPRESS_AUTOLOGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
		}

		/**
		 * LoginPress Addon updater.
		 *
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function init_addon_updater() {
			if ( class_exists( 'LoginPress_AddOn_Updater' ) ) {
				$updater = new LoginPress_AddOn_Updater( 2324, __FILE__, $this->version );
			}
		}
	}
endif;

new LoginPress_AutoLogin();
