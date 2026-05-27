<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * LoginPress Login Redirects Addon.
 *
 * @package LoginPress
 * @subpackage LoginRedirects
 * @since 1.1.3
 * @version 6.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

/**
 * Addon Name:: LoginPress - Redirect Login
 * Plugin URI: http://www.WPBrigade.com/wordpress/plugins/login-redirects/
 * Description: LoginPress is the best <code>wp-login</code> Login Redirects plugin by <a href="https://wpbrigade.com/">WPBrigade</a> which allows you to redirect user after login.
 *
 * @package loginpress
 * @category Core
 * @author WPBrigade
 * @version 6.1.0
 */

if ( ! class_exists( 'LoginPress_Login_Redirect' ) ) :

	/**
	 * LoginPress Login Redirect main class.
	 *
	 * @package loginpress
	 * @category Core
	 * @since 1.1.3
	 * @version 6.1.0
	 */
	class LoginPress_Login_Redirect {


		/**
		 * Class constructor.
		 *
		 * @since 1.1.3
		 * @return void
		 */
		public function __construct() {

			if ( LoginPress_Pro::addon_wrapper( 'login-redirects' ) ) {
				$this->define_constants();
				$this->hooks();
			}
		}

		/**
		 * Hook into actions and filters.
		 *
		 * @since 1.1.3
		 * @return void
		 */
		public function hooks() {

			add_action( 'plugins_loaded', array( $this, 'loginpress_redirect_login_loader' ), 25 );
		}


		/**
		 * Returns the main instance of WP to prevent the need to use globals.
		 *
		 * @since 1.0.0
		 * @return object LoginPress_Login_Redirect_Main
		 */
		public function loginpress_redirect_login_loader() {
			include_once LOGINPRESS_REDIRECT_ROOT_PATH . '/classes/class-loginpress-login-redirects.php';
			return LoginPress_Login_Redirect_Main::instance();
		}

		/**
		 * Define LoginPress Login Redirects Constants.
		 *
		 * @since 1.1.3
		 * @version 6.1.0
		 * @return void
		 */
		private function define_constants() {

			LoginPress_Pro_Init::define( 'LOGINPRESS_REDIRECT_ROOT_PATH', __DIR__ );
			LoginPress_Pro_Init::define( 'LOGINPRESS_REDIRECT_DIR_PATH', plugin_dir_path( __FILE__ ) );
			LoginPress_Pro_Init::define( 'LOGINPRESS_REDIRECT_ROOT_FILE', __FILE__ );
		}
	}
endif;

new LoginPress_Login_Redirect();
