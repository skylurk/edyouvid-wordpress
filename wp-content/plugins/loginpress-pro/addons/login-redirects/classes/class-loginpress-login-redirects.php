<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * LoginPress Login Redirects Main Class.
 *
 * @package LoginPress
 * @subpackage LoginRedirects
 * @since 3.0.0
 * @version 6.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

if ( ! class_exists( 'LoginPress_Login_Redirect_Main' ) ) :
	include_once __DIR__ . '/traits/redirects.php';
	include_once __DIR__ . '/traits/utils.php';
	/**
	 * LoginPress Login Redirects Main class.
	 *
	 * @package   LoginPress
	 * @subpackage LoginRedirects
	 * @since     3.0.0
	 * @version   6.1.0
	 */
	class LoginPress_Login_Redirect_Main {
		use LoginPress_Login_Redirects_Trait;
		use LoginPress_Redirects_Utils_Trait;

		/**
		 * Instance of this class.
		 *
		 * @var object
		 * @since 3.0.0
		 */
		protected static $instance = null;

		/**
		 * Class constructor.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public function __construct() {

			$this->hooks();
			$this->includes();
		}

		/**
		 * Call action hooks.
		 *
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function hooks() {
			add_action( 'rest_api_init', array( $this, 'lp_login_redirect_register_routes' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
			add_filter( 'loginpress_settings_tab', array( $this, 'loginpress_login_redirects_tab' ), 10, 1 );
			add_filter( 'loginpress_settings_fields', array( $this, 'loginpress_login_redirects_settings_array' ), 10, 1 );
			add_filter( 'loginpress_login_redirects', array( $this, 'loginpress_login_redirects_callback' ), 10, 1 );
			add_action( 'admin_footer', array( $this, 'loginpress_login_redirects_autocomplete_js' ) );
			add_action( 'wp_ajax_loginpress_login_redirects_update', array( $this, 'login_redirects_update_user_meta' ) );
			add_action( 'wp_ajax_loginpress_login_redirects_delete', array( $this, 'login_redirects_delete_user_meta' ) );
			add_action( 'wp_ajax_loginpress_login_redirects_role_update', array( $this, 'login_redirects_update_role' ) );
			add_action( 'wp_ajax_loginpress_login_redirects_role_delete', array( $this, 'login_redirects_delete_role' ) );
			add_action( 'wp_ajax_loginpress_login_redirect_script', array( $this, 'login_redirect_script_html' ) );
			add_action( 'loginpress_login_redirect_script', array( $this, 'login_redirect_script_content' ) );
		}


		/**
		 * LoginPress Addon updater.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public function init_addon_updater() {
			if ( class_exists( 'LoginPress_AddOn_Updater' ) ) {

				$updater = new LoginPress_AddOn_Updater( 2341, LOGINPRESS_REDIRECT_ROOT_FILE, $this->version );
			}
		}

		/**
		 * Include files.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public function includes() {

			include_once LOGINPRESS_REDIRECT_DIR_PATH . 'classes/class-redirects.php';
		}

		/**
		 * Load CSS and JS files at admin side on loginpress-settings page only.
		 *
		 * @param string $hook The Page ID.
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function admin_scripts( $hook ) {

			if ( 'toplevel_page_loginpress-settings' !== $hook ) {
				return;
			}

			wp_enqueue_script( 'jquery-ui-core' );
			wp_enqueue_script( 'jquery-ui-autocomplete' );

			wp_enqueue_style( 'loginpress_login_redirect_stlye', LOGINPRESS_PRO_ADDONS_DIR . '/login-redirects/assets/css/style.css', array(), LOGINPRESS_PRO_VERSION );

			wp_enqueue_style( 'loginpress_datatables_style', LOGINPRESS_PRO_DIR_URL . 'assets/css/jquery.dataTables.min.css', array(), LOGINPRESS_PRO_VERSION );

			wp_enqueue_script( 'loginpress_datatables_js', LOGINPRESS_PRO_DIR_URL . 'assets/js/jquery.dataTables.min.js', array( 'jquery' ), LOGINPRESS_PRO_VERSION, false );
		}

		/**
		 * Setting tab for Login Redirects.
		 *
		 * @param array $loginpress_tabs Rest of the settings tabs of LoginPress.
		 * @since 3.0.0
		 * @return array Login Redirects tab.
		 */
		public function loginpress_login_redirects_tab( $loginpress_tabs ) {

			$_login_redirects_tab = array(
				array(
					'id'         => 'loginpress_login_redirects',
					'title'      => __( 'Login Redirects', 'loginpress-pro' ),
					'sub-title'  => __( 'Automatically redirects the login', 'loginpress-pro' ),
					/* translators: * %s: HTML tags */
					'desc'       => $this->tab_desc(),
					'video_link' => 'EYqt8-iegeQ',
				),
			);
			$login_redirects_tab  = array_merge( $loginpress_tabs, $_login_redirects_tab );
			return $login_redirects_tab;
		}

		/**
		 * The tab_desc description of the tab 'loginpress settings'.
		 *
		 * @since 3.0.0
		 * @return string The tab description.
		 */
		public function tab_desc() {
			// translators: Login redirects description.
			$html = sprintf( __( '%1$sWith the Login Redirects add-on, you can easily redirect users based on their roles and specific usernames. Additionally, you can use this add-on to restrict access to certain pages for subscribers, guests, or customers instead of the default wp-admin page.%2$s', 'loginpress-pro' ), '<p>', '</p>' );
			// translators: Tabs.
			$html .= sprintf( __( '%1$s%2$sSpecific Users%3$s %4$sSpecific Roles%3$s ', 'loginpress-pro' ), '<div class="loginpress-redirects-tab-wrapper">', '<a href="#loginpress_login_redirect_users" class="loginpress-redirects-tab loginpress-redirects-active">', '</a>', '<a href="#loginpress_login_redirect_roles" class="loginpress-redirects-tab">' );
			$html  = apply_filters( 'loginpress_login_redirects_tabs', $html );
			$html .= '</div>';

			return $html;
		}
		/**
		 * Setting Fields for Login Redirects.
		 *
		 * @param array $setting_array Settings fields of free version.
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return array Login Redirects settings fields.
		 */
		public function loginpress_login_redirects_settings_array( $setting_array ) {

			$_login_redirects_settings = array(
				array(
					'name'  => 'login_redirects',
					'label' => __( 'Search Username', 'loginpress-pro' ),
					'desc'  => __( 'Search Username For Whom To Apply Redirects', 'loginpress-pro' ),
					'type'  => 'login_redirect',
				),
			);
			$login_redirects_settings  = array( 'loginpress_login_redirects' => $_login_redirects_settings );
			return array_merge( $login_redirects_settings, $setting_array );
		}

		/**
		 * A callback function that will show a search field under Login Redirect tab.
		 *
		 * @param string $args Argument.
		 * @since 3.0.0
		 * @return string HTML content.
		 */
		public function loginpress_login_redirects_callback( $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

			$html  = sprintf( '<input type="%1$s" name="%2$s" id="%2$s" value="" placeholder="%3$s" %4$s', 'text', 'loginpress_redirect_user_search', esc_attr( __( 'Type Username', 'loginpress-pro' ) ), '/>' );
			$html .= sprintf( '<input type="%1$s" name="%2$s" id="%2$s" value="" placeholder="%3$s" %4$s', 'text', 'loginpress_redirect_role_search', esc_attr( __( 'Type Role', 'loginpress-pro' ) ), '/>' );

			/**
			 * Use filter loginpress_redirects_structure_html_before to append custom HTML before the login redirects structure.
			 *
			 * @param string $html HTML content to be prepended before the redirects structure.
			 * @since 5.0.0
			 */
			$redirects_structure_html_before = apply_filters( 'loginpress_redirects_structure_html_before', $html );
			return $redirects_structure_html_before;
		}

		/**
		 * A callback function that will show search result under the search field.
		 * Return should be a string HTML.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public function login_redirect_script_content() {
			/**
			 * Check to apply the script only on the LoginPress Settings page.
			 *
			 * @since 1.1.5
			 */
			if ( isset( $_GET['page'] ) && 'loginpress-settings' !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			$html = '<div class="row-per-page"><span>' . __( 'Show Entries', 'loginpress-pro' ) . '</span> <select id="loginpress_login_redirect_users_select" class="selectbox"><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select></div><table id="loginpress_login_redirect_users" class="loginpress_login_redirect_users">
			<thead><tr>
			<th class="loginpress_user_id">' . esc_html__( 'User ID', 'loginpress-pro' ) . '</th>
			<th class="loginpress_log_userName">' . esc_html__( 'Username', 'loginpress-pro' ) . '</th>
			<th class="loginpress_log_email">' . esc_html__( 'Email', 'loginpress-pro' ) . '</th>
			<th class="loginpress_login_redirect">' . esc_html__( 'Login URL', 'loginpress-pro' ) . '</th>
			<th class="loginpress_logout_redirect">' . esc_html__( 'Logout URL', 'loginpress-pro' ) . '</th>
			<th class="loginpress_action">' . esc_html__( 'Action', 'loginpress-pro' ) . '</th>
			</tr></thead>';

			$html .= '</table>';

			$html .= '<table id="loginpress_login_redirect_roles" class="loginpress_login_redirect_roles">
			<thead><tr>
				<th class="loginpress_user_id">' . esc_html__( 'No', 'loginpress-pro' ) . '</th>
				<th class="loginpress_log_userName">' . esc_html__( 'Role', 'loginpress-pro' ) . '</th>
				<th class="loginpress_login_redirect">' . esc_html__( 'Login URL', 'loginpress-pro' ) . '</th>
				<th class="loginpress_logout_redirect">' . esc_html__( 'Logout URL', 'loginpress-pro' ) . '</th>
				<th class="loginpress_action">' . esc_html__( 'Action', 'loginpress-pro' ) . '</th>
			</tr></thead>';

			$html .= '</table>';

			/**
			 * Use filter loginpress_redirects_structure_html_after to append custom HTML after the login redirects structure.
			 *
			 * @param string $html HTML content to be appended after the redirects structure.
			 * @since 5.0.0
			 */
			$redirects_structure_html_after = apply_filters( 'loginpress_redirects_structure_html_after', $html );
			echo $redirects_structure_html_after; // @codingStandardsIgnoreLine.
		}

		/**
		 * A callback function that will show search result under the search field.
		 * Return should be a string HTML.
		 *
		 * @since 3.0.0
		 * @version 6.1.1
		 * @return void
		 */
		public function login_redirect_script_html() {

			check_ajax_referer( 'loginpress-user-redirects-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No cheating, huh!' );
			}

			/**
			 * Check to apply the script only on the LoginPress Settings page.
			 *
			 * @since 1.1.5
			 */
			if ( isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) !== 'loginpress-settings' ) {
				return;
			}

			$args       = array(
				'blog_id'    => $GLOBALS['blog_id'],
				'meta_query' => array( // @codingStandardsIgnoreLine.
					'relation' => 'OR',
					array( 'key' => 'loginpress_login_redirects_url' ),
					array( 'key' => 'loginpress_logout_redirects_url' ),
				),
			);
			$html       = '';
			$user_query = new WP_User_Query( $args );
			// get_results w.r.t 'meta_key' => 'loginpress_login_redirects_url' || 'loginpress_logout_redirects_url'.
			$redirect_user = $user_query->get_results();
			// Check for results.
			if ( ! empty( $redirect_user ) ) {
				// loop through each user.
				foreach ( $redirect_user as $user ) {
					// get all the user's data.
					$user_info = get_userdata( $user->ID );
					$html     .= '<tr id="loginpress_redirects_user_id_' . esc_attr( $user->ID ) . '" data-login-redirects-user="' . esc_attr( $user->ID ) . '"><td><div class="lp-tbody-cell">' . esc_html( $user_info->ID ) . '</div></td><td class="loginpress_user_name"><div class="lp-tbody-cell">' . esc_html( $user_info->user_login ) . '</div></td><td class="loginpress_login_redirects_email"><div class="lp-tbody-cell">' . esc_html( $user_info->user_email ) . '</div></td><td class="loginpress_login_redirects_url"><div class="lp-tbody-cell"><span class="login-redirects-sniper"><img src="' . esc_url( LOGINPRESS_DIR_URL . 'img/loginpress-sniper.gif' ) . '" /></span><input type="text" value="' . esc_attr( get_user_meta( $user->ID, 'loginpress_login_redirects_url', true ) ) . '" id="loginpress_login_redirects_url" placeholder="' . esc_attr( __( 'Enter URL', 'loginpress-pro' ) ) . '"/></div></td><td class="loginpress_logout_redirects_url"><div class="lp-tbody-cell"><span class="login-redirects-sniper"><img src="' . esc_url( LOGINPRESS_DIR_URL . 'img/loginpress-sniper.gif' ) . '" /></span><input type="text" value="' . esc_attr( get_user_meta( $user->ID, 'loginpress_logout_redirects_url', true ) ) . '" id="loginpress_logout_redirects_url" placeholder="' . esc_attr( __( 'Enter URL', 'loginpress-pro' ) ) . '"/></div></td><td class="loginpress_login_redirects_actions"><div class="lp-tbody-cell"><button type="button" class="button loginpress-user-redirects-update"  value="' . esc_attr( __( 'Update', 'loginpress-pro' ) ) . '" ></button> <button type="button" class="button loginpress-user-redirects-delete" value="' . esc_attr( __( 'Delete', 'loginpress-pro' ) ) . '"  ></button></div></td></tr>';
				}
			} else {
				$html .= '';
			}

			$login_redirect_role = get_option( 'loginpress_redirects_role' );

			// Check for results.
			if ( ! empty( $login_redirect_role ) ) {
				// loop through each role.
				$no = 0;
				foreach ( $login_redirect_role as $role => $value ) {
					// Get display name for the role (convert slug to display name).
					$role_display_name = self::loginpress_get_role_display_name( $role );

					$html .= '<tr id="loginpress_redirects_role_' . esc_attr( $role ) . '" data-login-redirects-role="' . esc_attr( $role ) . '"><td class="loginpress_login_redirects_role sorting_1"><div class="lp-tbody-cell no-of"></div></td><td class="loginpress_user_name"><div class="lp-tbody-cell">' . esc_html( $role_display_name ) . '</div></td><td class="loginpress_login_redirects_url" placeholder="' . esc_attr( __( 'Enter URL', 'loginpress-pro' ) ) . '"><div class="lp-tbody-cell"><span class="login-redirects-sniper"><img src="' . esc_url( LOGINPRESS_DIR_URL . 'img/loginpress-sniper.gif' ) . '" /></span><input type="text" value="' . esc_attr( $value['login'] ) . '" id="loginpress_login_redirects_url" placeholder="' . esc_attr( __( 'Enter URL', 'loginpress-pro' ) ) . '"/></div></td><td class="loginpress_logout_redirects_url"><div class="lp-tbody-cell"><span class="login-redirects-sniper"><img src="' . esc_url( LOGINPRESS_DIR_URL . 'img/loginpress-sniper.gif' ) . '" /></span><input type="text" value="' . esc_attr( $value['logout'] ) . '" id="loginpress_logout_redirects_url" placeholder="' . esc_attr( __( 'Enter URL', 'loginpress-pro' ) ) . '" /></div></td><td class="loginpress_login_redirects_actions"><div class="lp-tbody-cell"><button type="button" class="button loginpress-redirects-role-update" value="' . esc_attr( __( 'Update', 'loginpress-pro' ) ) . '" ></button> <button type="button" class="button loginpress-redirects-role-delete"  value="' . esc_attr( __( 'Delete', 'loginpress-pro' ) ) . '" ></button></div></td></tr>';
				}
			} else {
				$html .= '';
			}

			echo $html; // @codingStandardsIgnoreLine.
			wp_die();
		}

		/**
		 * Ajax function that update the user meta after creating autologin code.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public function login_redirects_update_user_meta() {

			check_ajax_referer( 'loginpress-user-redirects-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No cheating, huh!' );
			}
			if ( isset( $_POST['id'] ) && isset( $_POST['logout'] ) && isset( $_POST['login'] ) ) {
				$user_id           = absint( wp_unslash( $_POST['id'] ) );
				$loginpress_logout = esc_url_raw( wp_unslash( $_POST['logout'] ) );
				$loginpress_login  = esc_url_raw( wp_unslash( $_POST['login'] ) );

				$this->loginpress_update_redirect_url( $user_id, 'loginpress_login_redirects_url', $loginpress_login );
				$this->loginpress_update_redirect_url( $user_id, 'loginpress_logout_redirects_url', $loginpress_logout );

				echo esc_url( $this->loginpress_get_redirect_url( $user_id, 'loginpress_login_redirects_url' ) );
				echo esc_url( $this->loginpress_get_redirect_url( $user_id, 'loginpress_logout_redirects_url' ) );
			}
			wp_die();
		}

		/**
		 * LoginPress_redirects_role.
		 *
		 * @since 3.0.0
		 * @version 6.1.1
		 * @return void
		 */
		public function login_redirects_update_role() {

			check_ajax_referer( 'loginpress-role-redirects-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No cheating, huh!' );
			}
			if ( isset( $_POST['logout'] ) && isset( $_POST['login'] ) && isset( $_POST['role'] ) ) {
				$loginpress_logout = esc_url_raw( wp_unslash( $_POST['logout'] ) );
				$loginpress_login  = esc_url_raw( wp_unslash( $_POST['login'] ) );
				$role_name         = sanitize_text_field( wp_unslash( $_POST['role'] ) );

				// Get the actual WordPress role slug.
				$role = self::loginpress_get_role_slug_from_name( $role_name );

				$check_role = get_option( 'loginpress_redirects_role' );
				$add_role   = array(
					$role => array(
						'login'  => $loginpress_login,
						'logout' => $loginpress_logout,
					),
				);

				if ( $check_role && ! in_array( $role, $check_role, true ) ) {
					$redirect_roles = array_merge( $check_role, $add_role );
				} else {
					$redirect_roles = $add_role;
				}

				update_option( 'loginpress_redirects_role', $redirect_roles, true );
			}
			wp_die();
		}

		/**
		 * Ajax function that delete the user meta after click on delete user redirect button.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public function login_redirects_delete_user_meta() {

			check_ajax_referer( 'loginpress-user-redirects-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No cheating, huh!' );
			}
			if ( isset( $_POST['id'] ) ) {
				$user_id = absint( wp_unslash( $_POST['id'] ) );

				$this->loginpress_delete_redirect_url( $user_id, 'loginpress_login_redirects_url' );
				$this->loginpress_delete_redirect_url( $user_id, 'loginpress_logout_redirects_url' );
				echo esc_html__( 'deleted', 'loginpress-pro' );
			}
			wp_die();
		}

		/**
		 * Delete Role/s.
		 *
		 * @since 1.0.0
		 * @version 6.1.1
		 * @return void
		 */
		public function login_redirects_delete_role() {

			check_ajax_referer( 'loginpress-role-redirects-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No cheating, huh!' );
			}
			if ( isset( $_POST['role'] ) ) {
				$role_name  = sanitize_text_field( wp_unslash( $_POST['role'] ) );
				$check_role = get_option( 'loginpress_redirects_role' );

				// Try multiple approaches to find and delete the role:
				// 1. Try exact match with the role_name as sent (for backward compatibility with old format).
				if ( isset( $check_role[ $role_name ] ) ) {
					$check_role[ $role_name ] = null;
					$check_role               = array_filter( $check_role );
					update_option( 'loginpress_redirects_role', $check_role, true );
				} else {
					// 2. Get the actual WordPress role slug and try that
					$role = self::loginpress_get_role_slug_from_name( $role_name );
					if ( isset( $check_role[ $role ] ) ) {
						$check_role[ $role ] = null;
						$check_role          = array_filter( $check_role );
						update_option( 'loginpress_redirects_role', $check_role, true );
					} else {
						// 3. Try case-insensitive search through all stored keys
						$found_key = null;
						foreach ( $check_role as $stored_key => $value ) {
							// Case-insensitive comparison.
							if ( strtolower( $stored_key ) === strtolower( $role_name ) || strtolower( $stored_key ) === strtolower( $role ) ) {
								$found_key = $stored_key;
								break;
							}
						}
						if ( $found_key ) {
							$check_role[ $found_key ] = null;
							$check_role               = array_filter( $check_role );
							update_option( 'loginpress_redirects_role', $check_role, true );
						}
					}
				}
			}
			wp_die();
		}

		/**
		 * Get user meta.
		 *
		 * @param int    $user_id ID of the user.
		 * @param string $option User meta key.
		 * @since 3.0.0
		 * @return string Redirect URL.
		 */
		public function loginpress_get_redirect_url( $user_id, $option ) {

			if ( ! is_multisite() ) {
				$redirect_url = get_user_meta( $user_id, $option, true );
			} else {
				$redirect_url = get_user_option( $option, $user_id );
			}

			return $redirect_url;
		}

		/**
		 * Update user meta.
		 *
		 * @param int    $user_id ID of the user.
		 * @param string $option User meta key.
		 * @param string $value User meta value.
		 * @since 3.0.0
		 * @return void
		 */
		public function loginpress_update_redirect_url( $user_id, $option, $value ) {

			if ( ! is_multisite() ) {
				update_user_meta( $user_id, $option, $value );
			} else {
				update_user_option( $user_id, $option, $value, true );
			}
		}

		/**
		 * Delete user meta.
		 *
		 * @param int    $user_id ID of the user.
		 * @param string $option User meta key.
		 * @since 3.0.0
		 * @return void
		 */
		public function loginpress_delete_redirect_url( $user_id, $option ) {

			if ( ! is_multisite() ) {
				delete_user_meta( $user_id, $option );
			} else {
				delete_user_option( $user_id, $option, true );
			}
		}

		/**
		 * Main Instance.
		 *
		 * @since 3.0.0
		 * @static
		 * @see loginpress_redirect_login_loader()
		 * @return object Main instance of the Class.
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}
	}
endif;
