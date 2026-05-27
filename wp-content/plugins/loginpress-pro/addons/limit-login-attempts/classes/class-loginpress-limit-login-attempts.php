<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * LoginPress Limit Login Attempts Main class file.
 *
 * @package   LoginPress
 * @subpackage Classes\LimitLogin
 * @since     3.0.0
 * @version 6.1.0
 */

if ( ! class_exists( 'LoginPress_Limit_Login_Attempts_Main' ) ) :
	include_once __DIR__ . '/traits/rest.php';
	include_once __DIR__ . '/traits/ajax.php';
	/**
	 * Main Class.
	 */
	class LoginPress_Limit_Login_Attempts_Main {
		use LoginPress_LimitLogin_Rest_Trait;
		use LoginPress_LimitLogin_Ajax_Trait;

		/**
		 * Variable for LoginPress Limit Login Attempts table name.
		 *
		 * @var string $llla_table Custom table name for Limit Login Attempts.
		 * @since 3.0.0
		 */
		protected $llla_table;

		/**
		 * Variable for wp_login_php.
		 *
		 * @since  3.0.0
		 * @access private
		 * @var    bool
		 */
		private $wp_login_php;

		/**
		 * Variable that Check for LoginPress Key.
		 *
		 * @var string
		 * @since 6.0.0
		 */
		public $attempts_settings;

		/**
		 * Instance of this class.
		 *
		 * @since    3.0.0
		 * @var      object
		 */
		protected static $llla_instance = null;

		/**
		 * Class constructor.
		 *
		 * @since 3.0.0
		 */
		public function __construct() {

			global $wpdb;
			$this->llla_table = $wpdb->prefix . 'loginpress_limit_login_details';
			$this->hooks();
			$this->includes();
		}

		/**
		 * Action hooks.
		 *
		 * @since 3.0.0
		 */
		public function hooks() {
			add_action( 'rest_api_init', array( $this, 'lp_llla_register_routes' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
			add_filter( 'loginpress_settings_tab', array( $this, 'loginpress_limit_login_attempts_tab' ), 10, 1 );
			add_filter( 'loginpress_settings_fields', array( $this, 'loginpress_limit_login_attempts_settings_array' ), 10, 1 );
			add_action( 'wp_ajax_loginpress_load_limit_login_tabs', array( $this, 'loginpress_load_limit_login_tabs' ) );
			add_action( 'wp_ajax_loginpress_limit_login_attempts_log_script', array( $this, 'loginpress_limit_login_attempts_log_callback' ) );
			add_action( 'wp_ajax_loginpress_limit_login_attempts_whitelist_script', array( $this, 'loginpress_limit_login_attempts_whitelist_callback' ) );
			add_action( 'wp_ajax_loginpress_limit_login_attempts_blacklist_script', array( $this, 'loginpress_limit_login_attempts_blacklist_callback' ) );
			add_action( 'loginpress_limit_login_attempts_log_script', array( $this, 'loginpress_limit_login_attempts_log_content' ) );
			add_action( 'loginpress_limit_login_attempts_whitelist_script', array( $this, 'loginpress_limit_login_attempts_whitelist_content' ) );
			add_action( 'loginpress_limit_login_attempts_blacklist_script', array( $this, 'loginpress_limit_login_attempts_blacklist_content' ) );

			$this->attempts_settings = get_option( 'loginpress_limit_login_attempts' );

			$limit_concurrent = isset( $this->attempts_settings['limit_concurrent_sessions'] ) ? sanitize_text_field( $this->attempts_settings['limit_concurrent_sessions'] ) : 'off';
			if ( 'on' === $limit_concurrent ) {
				add_action( 'authenticate', array( $this, 'loginpress_check_concurrent_sessions' ), 100, 3 );
			}
		}

		/**
		 * Register the rest routes for Limit-login-attempts.
		 *
		 * @since  6.0.0
		 * @return void
		 */
		public function lp_llla_register_routes() {
			// Get users with redirects.
			register_rest_route(
				'loginpress/v1',
				'/limit-login-settings',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'loginpress_get_limit_login_settings' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);
			register_rest_route(
				'loginpress/v1',
				'/limit-login-settings',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_update_limit_login_settings' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);
			register_rest_route(
				'loginpress/v1',
				'/login-attempts-logs',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'loginpress_get_login_attempts' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			// Bulk actions.
			register_rest_route(
				'loginpress/v1',
				'/login-attempts/bulk-action',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_bulk_action_login_attempts' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			// Clear all.
			register_rest_route(
				'loginpress/v1',
				'/login-attempts/clear-all',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_clear_all_login_attempts' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			// Individual action.
			register_rest_route(
				'loginpress/v1',
				'/login-attempts/action',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_action_login_attempt' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			register_rest_route(
				'loginpress/v1',
				'/whitelist-list',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'loginpress_get_whitelist' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			register_rest_route(
				'loginpress/v1',
				'/whitelist-list/remove',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_remove_from_whitelist' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			register_rest_route(
				'loginpress/v1',
				'/whitelist-list/clear-all',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_clear_whitelist' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			// Blacklist endpoints (similar to whitelist).
			register_rest_route(
				'loginpress/v1',
				'/blacklist-list',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'loginpress_get_blacklist' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			register_rest_route(
				'loginpress/v1',
				'/blacklist-list/remove',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_remove_from_blacklist' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			register_rest_route(
				'loginpress/v1',
				'/blacklist-list/clear-all',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_clear_blacklist' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);
			register_rest_route(
				'loginpress/v1',
				'/limit-login-add-ip',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_rest_add_ip' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);
		}

		/**
		 * LoginPress Addon updater.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public function init_addon_updater() {

			if ( class_exists( 'LoginPress_AddOn_Updater' ) ) {

				$updater = new LoginPress_AddOn_Updater( 2328, LOGINPRESS_LIMIT_LOGIN_ROOT_FILE, $this->version );
			}
		}

		/**
		 * Include files.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public function includes() {

			include_once LOGINPRESS_LIMIT_LOGIN_DIR_PATH . 'classes/class-attempts.php';
			include_once LOGINPRESS_LIMIT_LOGIN_DIR_PATH . 'classes/class-ajax.php';
		}

		/**
		 * Load CSS and JS files at admin side on loginpress-settings page only.
		 *
		 * @param string $hook the Page ID.
		 * @return void
		 * @since  3.0.0
		 * @version 6.1.0
		 */
		public function admin_scripts( $hook ) {

			if ( 'toplevel_page_loginpress-settings' !== $hook ) {
				return;
			}

			wp_enqueue_style( 'loginpress_limit_login_stlye', LOGINPRESS_LIMIT_LOGIN_DIR_URL . 'assets/css/style.css', array(), LOGINPRESS_PRO_VERSION );
			wp_enqueue_style( 'loginpress_datatables_style', LOGINPRESS_PRO_DIR_URL . 'assets/css/jquery.dataTables.min.css', array(), LOGINPRESS_PRO_VERSION );
			wp_enqueue_script( 'loginpress_datatables_js', LOGINPRESS_PRO_DIR_URL . 'assets/js/jquery.dataTables.min.js', array( 'jquery' ), LOGINPRESS_PRO_VERSION, false );
			wp_enqueue_style( 'loginpress_data_tables_responsive', LOGINPRESS_PRO_DIR_URL . 'assets/css/rowReorder.dataTables.min.css', array(), LOGINPRESS_PRO_VERSION );
			wp_enqueue_script( 'loginpress_datatables_responsive_row', LOGINPRESS_PRO_DIR_URL . 'assets/js/dataTables.rowReorder.min.js', array( 'jquery' ), LOGINPRESS_PRO_VERSION, false );
			wp_enqueue_style( 'datatables_buttons_css', 'https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css', array(), '2.3.6' );
			wp_enqueue_script( 'datatables_buttons_js', 'https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js', array( 'jquery' ), '2.3.6', true );
			wp_enqueue_script( 'datatables_buttons_html5_js', 'https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js', array( 'jquery' ), '2.3.6', true );
			wp_enqueue_script( 'datatables_jszip_js', 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js', array(), '3.10.1', true );
		}


		/**
		 * Setting tab for Limit Login Attempts.
		 *
		 * @param  array $loginpress_tabs Rest of the settings tabs of LoginPress.
		 * @return array $limit_login_tab Limit Login Attempts tab.
		 * @since  3.0.0
		 */
		public function loginpress_limit_login_attempts_tab( $loginpress_tabs ) {

			$_limit_login_tab = array(
				array(
					'id'         => 'loginpress_limit_login_attempts',
					'title'      => __( 'Limit Login Attempts', 'loginpress-pro' ),
					'sub-title'  => __( 'Limits for login attempts', 'loginpress-pro' ),
					/* translators: * %s: HTML tags */
					'desc'       => $this->tab_desc(),
					'video_link' => '1-L14gHC8R0',
				),
			);

			$limit_login_tab = array_merge( $loginpress_tabs, $_limit_login_tab );

			return $limit_login_tab;
		}

		/**
		 * Show the size of the login attempts log table in database based on the number of rows in a notification.
		 *
		 * @since 4.0.0
		 * @return string $size_in_mb The size of the table in MB.
		 */
		public function loginpress_login_attempts_log_table_size() {
			$host              = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
			$request_uri       = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$current_page_url  = ( is_ssl() ? 'https://' : 'http://' ) . $host . $request_uri;
			$settings_page_url = home_url( '/wp-admin/admin.php?page=loginpress-settings' );

			// Only add filters if the user is logged out or on the settings page.
			if ( ! is_user_logged_in() || $current_page_url !== $settings_page_url ) {
				return;
			}
			global $wpdb;

			$table_name = esc_sql( $wpdb->prefix . 'loginpress_limit_login_details' );

			// Analyze the table to ensure accurate statistics.
			$wpdb->query( $wpdb->prepare( 'ANALYZE TABLE %i', $table_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			// Query to fetch the number of rows in the table.
			$row_count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			// Define a threshold for the number of rows.
			$row_threshold = 1000;

			// Apply filters.
			$row_threshold = apply_filters( 'loginpress_limit_login_attempts_row_threshold', $row_threshold );

			if ( $row_count < $row_threshold ) {
				return '';
			} else {
				return sprintf( // translators: Notice to optimize performance.
					__( '%1$s Your logs table contains approximately %3$s%2$s rows %4$s. Consider clearing them to optimize database performance.%5$s', 'loginpress-pro' ),
					'<span class="loginpress_table_size_notify">',
					esc_html( number_format( $row_count ) ),
					'<strong>',
					'</strong>',
					'</span>'
				);
			}
		}

		/**
		 * The tab_desc description of the tab 'loginpress settings'.
		 *
		 * @since 1.0.0
		 * @version 6.1.0
		 * @return string $html The tab description.
		 */
		public function tab_desc() {

			$notify = $this->loginpress_login_attempts_log_table_size();
			$html   = '';
			if ( ! empty( $notify ) ) {
				$html .= $notify;
			}
			// Translators: Limit login attempts description.
			$html .= sprintf( __( '%1$sThe Limit Login Attempts add-on helps you easily keep track of how many times each user tries to log in and limits the number of attempts they can make. This way, your website is protected from brute force attacks, when hackers try lots of passwords to get in. %2$s', 'loginpress-pro' ), '<p>', '</p>' );
			// Translators: Tabs.
			$html .= sprintf( __( '%1$s%3$sSettings%4$s %5$sAttempt Details%4$s %6$sWhitelist%4$s %7$sBlacklist%4$s%2$s', 'loginpress-pro' ), '<div class="loginpress-limit-login-tab-wrapper">', '</div>', '<a href="#loginpress_limit_login_settings" class="loginpress-limit-login-tab loginpress-limit-login-active">', '</a>', '<a href="#loginpress_limit_logs" class="loginpress-limit-login-tab">', '<a href="#loginpress_limit_login_whitelist" class="loginpress-limit-login-tab">', '<a href="#loginpress_limit_login_blacklist" class="loginpress-limit-login-tab">' );
			// Empty placeholders where content will be loaded dynamically.
			$html .= '<div id="loginpress_limit_login_content"></div>';

			return $html;
		}

		/**
		 * Setting Fields for Limit Login Attempts.
		 *
		 * @param array $setting_array Settings fields of free version.
		 * @return array Limit Login Attempts settings fields.
		 * @since  3.0.0
		 * @version 6.1.0
		 */
		public function loginpress_limit_login_attempts_settings_array( $setting_array ) {

			$_limit_login_settings = array(
				array(
					'name'    => 'attempts_allowed',
					'label'   => __( 'Attempts Allowed', 'loginpress-pro' ),
					'desc'    => __( 'Allowed Attempts In Numbers (How Many)', 'loginpress-pro' ),
					'type'    => 'number',
					'min'     => 1,
					'default' => '4',
				),
				array(
					'name'    => 'minutes_lockout',
					'label'   => __( 'Lockout Minutes', 'loginpress-pro' ),
					'desc'    => __( 'Lockout Minutes In Numbers (How Many)', 'loginpress-pro' ),
					'type'    => 'number',
					'min'     => 1,
					'default' => '20',
				),
				array(
					'name'              => 'lockout_message',
					'label'             => __( 'Lockout Message', 'loginpress-pro' ),
					'desc'              => __( 'Message for user(s) after reaching maximum login attempts.', 'loginpress-pro' ),
					'type'              => 'text',
					'default'           => '',
					'sanitize_callback' => 'sanitize_text',
				),
				array(
					'name'              => 'ip_add_remove',
					'label'             => __( 'IP Address', 'loginpress-pro' ),
					'type'              => 'text',
					'callback'          => array( $this, 'loginpress_ip_add_remove_callback' ),
					'sanitize_callback' => 'sanitize_text',
				),
				array(
					'name'  => 'disable_xml_rpc_request',
					'label' => __( 'Disable XML RPC Request', 'loginpress-pro' ),
					'desc'  => __( 'The XMLRPC is a system that allows remote updates to WordPress from other applications.', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				array(
					'name'  => 'ip_intelligence',
					'label' => __( 'IP Intelligence', 'loginpress-pro' ),
					'desc'  => __( 'Enable IP Intelligence to detect brute force attacks and block suspicious IP addresses.', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
			);

			$limit_login_settings = array( 'loginpress_limit_login_attempts' => $_limit_login_settings );

			return array_merge( $limit_login_settings, $setting_array );
		}

		/**
		 * Main Instance.
		 *
		 * @since 3.0.0
		 * @static
		 * @return object Main instance of the Class
		 */
		public static function instance() {

			if ( is_null( self::$llla_instance ) ) {
				self::$llla_instance = new self();
			}
			return self::$llla_instance;
		}

		/**
		 * Ip add or remove setting callback.
		 *
		 * @since 3.0.0
		 * @param array $args argument of setting.
		 * @return void $html
		 */
		public function loginpress_ip_add_remove_callback( $args ) {

			$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$type        = isset( $args['type'] ) ? $args['type'] : 'text';
			$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . esc_attr( $args['placeholder'] ) . '"';

			$whitelist = __( 'WhiteList', 'loginpress-pro' );
			$blacklist = __( 'BlackList', 'loginpress-pro' );
			$spinner   = '<span class="lla-spinner"><img src="' . esc_url( LOGINPRESS_DIR_URL . 'img/loginpress-sniper.gif' ) . '" /></span>';

			$html  = '<input type="' . esc_attr( $type ) . '" class="' . esc_attr( $size ) . '-text" id="' . esc_attr( $args['section'] ) . '[' . esc_attr( $args['id'] ) . ']" name="' . esc_attr( $args['section'] ) . '[' . esc_attr( $args['id'] ) . ']" value="" ' . $placeholder . '/>';
			$html .= '<p class="description"><button class="button loginpress-attempts-whitelist add_white_list" data-action="white_list" type="button">' . esc_html( $whitelist ) . '</button><button class="button loginpress-attempts-blacklist add_black_list" data-action="black_list" type="button">' . esc_html( $blacklist ) . ' </button>' . $spinner . '</p>';

			echo $html; // @codingStandardsIgnoreLine.
		}


		/**
		 * Limit concurrent sessions.
		 *
		 * @param WP_User|WP_Error $user The user object or error.
		 * @param string           $username The username.
		 * @param string           $password The password.
		 * @since 6.0.0
		 * @return WP_User|WP_Error The user object or error.
		 */
		public function loginpress_check_concurrent_sessions( $user, $username, $password ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			if ( is_wp_error( $user ) ) {
				return $user; // Skip if previous auth failed.
			}

			$settings       = $this->attempts_settings;
			$user_roles     = $user->roles;
			$excluded_roles = ! empty( $settings['exclude_roles'] ) ? $settings['exclude_roles'] : array();

			$user_roles_lower     = array_map( 'strtolower', $user_roles );
			$excluded_roles_lower = array_map( 'strtolower', $excluded_roles );

			if ( array_intersect( $excluded_roles_lower, $user_roles_lower ) ) {
				return $user;
			}

			$max_sessions  = isset( $settings['max_session_count'] ) ? intval( $settings['max_session_count'] ) : 1;
			$sessions      = WP_Session_Tokens::get_instance( $user->ID );
			$session_count = count( $sessions->get_all() );
			if ( $session_count < $max_sessions ) {
				return $user; // Allow login.
			}

			// Don't allow login.
			return new WP_Error(
				'session_limit_exceeded',
				__( 'You have reached the maximum number of concurrent logins. Please log out from other devices/Screens to continue.', 'loginpress-pro' )
			);
		}
	}

endif;
