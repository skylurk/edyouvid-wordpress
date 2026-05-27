<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Addon Name: LoginPress - Limit Login Attempts
 * Description: LoginPress - Limit Login Attempts is the best for <code>wp-login</code> Login Attemps plugin by <a href="https://wpbrigade.com/">WPBrigade</a> which allows you to restrict user attempts.
 *
 * @package LoginPress
 * @category Core
 * @author WPBrigade
 * @since 3.0.0
 * @version 6.1.0
 */

if ( ! class_exists( 'LoginPress_Limit_Login_Attempts' ) ) :

	/**
	 * LoginPress_Limit_Login_Attempts
	 */
	class LoginPress_Limit_Login_Attempts {
		/**
		 * Current page URL.
		 *
		 * @var string
		 * @since 3.0.0
		 */
		private $current_page_url;

		/**
		 * Settings page URL.
		 *
		 * @var string
		 * @since 3.0.0
		 */
		public $settings_page_url;

		/**
		 * Class constructor.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public function __construct() {
			$host                    = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
			$request_uri             = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$this->current_page_url  = ( is_ssl() ? 'https://' : 'http://' ) . $host . $request_uri;
			$this->settings_page_url = home_url( '/wp-admin/admin.php?page=loginpress-settings' );
			$this->hooks();
			$this->define_constants();
		}

		/**
		 * Hook into actions and filters.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public function hooks() {

			add_action( 'plugins_loaded', array( $this, 'loginpress_limit_login_instance' ), 25 );
			add_action( 'wpmu_new_blog', array( $this, 'loginpress_limit_login_activation' ) );
			add_action( 'admin_init', array( $this, 'loginpress_limit_login_admin_init' ), 10, 2 );
		}

		/**
		 * Define LoginPress Limit Login Attempts Constants.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		private function define_constants() {

			LoginPress_Pro_Init::define( 'LOGINPRESS_LIMIT_LOGIN_ROOT_PATH', __DIR__ );
			LoginPress_Pro_Init::define( 'LOGINPRESS_LIMIT_LOGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
			LoginPress_Pro_Init::define( 'LOGINPRESS_LIMIT_LOGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
			LoginPress_Pro_Init::define( 'LOGINPRESS_LIMIT_LOGIN_ROOT_FILE', __FILE__ );
		}

		/**
		 * Run LoginPress_limit_login_loader().
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public function loginpress_limit_login_instance() {
			if ( LoginPress_Pro::addon_wrapper( 'limit-login-attempts' ) ) {
					$this->loginpress_limit_login_loader();
			}
		}

		/**
		 * Admin initialization function.
		 *
		 * This function checks if the current plugin is being updated, and if so,
		 * it verifies the existence of a specific column in the database table
		 * `loginpress_limit_login_details`. If the 'password' column is found,
		 * it triggers its removal to ensure data integrity. and adding a new column `login_status`
		 * to the table if it doesn't exist.
		 *
		 * @since 3.3.0
		 * @version 6.0.0
		 * @return void
		 */
		public function loginpress_limit_login_admin_init() {

			if ( defined( 'LOGINPRESS_PRO_VERSION' ) && version_compare( LOGINPRESS_PRO_VERSION, '4.0.0', '>=' ) ) {
				global $wpdb;
				$table_name    = "{$wpdb->prefix}loginpress_limit_login_details";
				$column_exists = $wpdb->get_results( $wpdb->prepare( 'SHOW COLUMNS FROM %i LIKE %s', $table_name, 'password' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$login_exists  = $wpdb->get_results( $wpdb->prepare( 'SHOW COLUMNS FROM %i LIKE %s', $table_name, 'login_status' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

				if ( ! empty( $column_exists ) ) {
					$this->loginpress_remove_password_column();
				}

				if ( empty( $login_exists ) ) {
					$this->loginpress_add_login_status_column();
				}
			}
		}

		/**
		 * Remove password column from loginpress_limit_login_details table.
		 *
		 * @since 3.3.0
		 * @return void
		 */
		public function loginpress_remove_password_column() {

			if ( ! current_user_can( 'manage_options' ) ) {
				return; // Restrict access to admins or similar roles.
			}

			global $wpdb;

			$table_name = "{$wpdb->prefix}loginpress_limit_login_details";
			// phpcs:disable
			$wpdb->query(
				$wpdb->prepare(
					'ALTER TABLE %i DROP COLUMN %i',
					$table_name,
					'password'
				)
			);
			// phpcs:enable
		}

		/**
		 * Add a new column `login_status` to the `loginpress_limit_login_details` table if it doesn't exist.
		 *
		 * @since 4.0.1
		 * @return void
		 */
		public function loginpress_add_login_status_column() {

			if ( ! current_user_can( 'manage_options' ) ) {
				return; // Restrict access to admins or similar roles.
			}

			global $wpdb;

			$table_name = "{$wpdb->prefix}loginpress_limit_login_details";
			// phpcs:disable
			$wpdb->query(
				$wpdb->prepare(
					'ALTER TABLE %i ADD COLUMN login_status varchar(255) NOT NULL',
					$table_name
				)
			);
			// phpcs:enable
		}

		/**
		 * Returns the main instance of WP to prevent the need to use globals.
		 *
		 * @since  3.0.0
		 * @return object LoginPress_Limit_Login_Attempts_Main
		 */
		public function loginpress_limit_login_loader() {

			include_once LOGINPRESS_LIMIT_LOGIN_ROOT_PATH . '/classes/class-loginpress-limit-login-attempts.php';
			return LoginPress_Limit_Login_Attempts_Main::instance();
		}

		/**
		 * Run some custom tasks on plugin activation.
		 *
		 * @param bool $network_wide Network-wide check.
		 * @since 3.0.0
		 * @version 3.3.1
		 * @return void
		 */
		public function loginpress_limit_login_activation( $network_wide ) {
			// Only add filters if the user is logged out or on the settings page.
			if ( ! is_user_logged_in() || $this->current_page_url === $this->settings_page_url ) {
				if ( function_exists( 'is_multisite' ) && is_multisite() && $network_wide ) {

					global $wpdb;
					// Get this so we can switch back to it later.
					$current_blog = $wpdb->blogid;
					// Get all blogs in the network and activate the plugin on each one.
					$blog_ids = $wpdb->get_col( $wpdb->prepare( 'SELECT blog_id FROM %s', $wpdb->blogs ) ); // @codingStandardsIgnoreLine.

					foreach ( $blog_ids as $blog_id ) {
						switch_to_blog( $blog_id );
						$this->loginpress_limit_create_table();
					}
					switch_to_blog( $current_blog );
					return;
				} else {
					$this->loginpress_limit_create_table(); // normal activation.
				}
			}
		}

		/**
		 * Create Db table on plugin activation.
		 *
		 * @since 3.0.0
		 * @version 6.0.0
		 * @return void
		 */
		public function loginpress_limit_create_table() {

			global $wpdb;
			// create user details table.
			$table_name = "{$wpdb->prefix}loginpress_limit_login_details";

			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
				id int(11) NOT NULL AUTO_INCREMENT,
				ip varchar(255) NOT NULL,
				username varchar(255) NOT NULL,
				datentime varchar(255) NOT NULL,
				gateway varchar(255) NOT NULL,
				whitelist int(11) NOT NULL,
				blacklist int(11) NOT NULL,
				login_status varchar(255) NOT NULL,
				UNIQUE KEY id (id)
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

			// Set default settings.
			if ( ! get_option( 'loginpress_limit_login_attempts' ) ) {

				update_option(
					'loginpress_limit_login_attempts',
					array(
						'attempts_allowed' => 4,
						'minutes_lockout'  => 20,
					)
				);
			}
		}
	}

endif;

new LoginPress_Limit_Login_Attempts();
