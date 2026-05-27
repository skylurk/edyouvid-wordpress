<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * LoginPress Limit Login Attempts class file.
 *
 * @package   LoginPress
 * @subpackage Classes\LimitLogin
 * @since     3.0.0
 * @version 6.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

/**
 * LoginPress Limit Login Attempts class
 *
 * Handling all the methods related to attempts in LoginPress - Limit Login Attempts.
 *
 * @package   LoginPress
 * @subpackage Traits\LimitLogin
 * @since     3.0.0
 * @version 6.1.0
 */

if ( ! class_exists( 'LoginPress_Attempts' ) ) :
	include_once __DIR__ . '/traits/attempts.php';
	/**
	 * LoginPress Limit Login Attempts class
	 *
	 * Handling all the methods related to attempts in LoginPress - Limit Login Attempts.
	 *
	 * @package   LoginPress
	 * @subpackage Traits\LimitLogin
	 * @since     3.0.0
	 * @version 6.1.0
	 */
	class LoginPress_Attempts {
		use LoginPress_LimitLogin_Attempts_Trait;

		/**
		 * Variable for LoginPress Limit Login Attempts table name.
		 *
		 * @var string
		 * @since 3.0.0
		 */
		protected $llla_table;

		/**
		 * Variable that Check for LoginPress Key.
		 *
		 * @var string
		 * @since 3.0.0
		 */
		public $attempts_settings;
		/**
		 * Variable to store the time of the attempt.
		 *
		 * @var string
		 * @since 5.0.0
		 */
		public $attempt_time;
		/**
		 * Variable that Check for LoginPress hidelogin settings.
		 *
		 * @var string
		 * @since 5.0.0
		 */
		public $loginpress_hidelogin;
		/**
		 * Variable that Check for edd block status.
		 *
		 * @var string
		 * @since 5.0.0
		 */
		public $edd_block_error;
		/**
		 * Variable that stores the ip of the user.
		 *
		 * @var string
		 * @since 5.0.0
		 */
		public $ip;
		/**
		 * Variable that stores the username of the user.
		 *
		 * @var string
		 * @since 6.0.0
		 */
		public $username;
		/**
		 * Class constructor.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public function __construct() {
			$this->edd_block_error = true;
			global $wpdb;
			$this->llla_table        = $wpdb->prefix . 'loginpress_limit_login_details';
			$this->attempts_settings = get_option( 'loginpress_limit_login_attempts' );
			if ( ! $this->attempts_settings ) {
				update_option(
					'loginpress_limit_login_attempts',
					array(
						'attempts_allowed' => 4,
						'minutes_lockout'  => 20,
					)
				);
				$this->attempts_settings = array(
					'attempts_allowed' => 4,
					'minutes_lockout'  => 20,
				);
			}
			$this->loginpress_hidelogin = get_option( 'loginpress_hidelogin' );
			$this->ip                   = $this->get_address();
			$is_llla_active             = get_option( 'loginpress_pro_addons' );
			if ( isset( $is_llla_active['limit-login-attempts']['is_active'] ) && $is_llla_active['limit-login-attempts']['is_active'] ) {
				$this->hooks();
			}
		}

		/**
		 * Action hooks.
		 *
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function hooks() {

			add_action( 'wp_loaded', array( $this, 'llla_wp_loaded' ) );
			add_action( 'init', array( $this, 'llla_check_xml_request' ) );
			add_action( 'init', array( $this, 'hide_login_integrate' ) );
			add_action( 'init', array( $this, 'loginpress_login_widget_integrate' ) ); // Integrate Widget Login Add-on.
			add_filter( 'authenticate', array( $this, 'llla_login_attempts_auth' ), 98, 3 );
			add_action( 'wp_login', array( $this, 'llla_login_attempts_wp_login' ), 99, 2 );
			$disable_xml_rpc = isset( $this->attempts_settings['disable_xml_rpc_request'] ) ? $this->attempts_settings['disable_xml_rpc_request'] : '';

			if ( 'on' === $disable_xml_rpc ) {
				$this->disable_xml_rpc();
			}

			$disable_user_rest_endpoint = isset( $this->attempts_settings['disable_user_rest_endpoint'] ) ? $this->attempts_settings['disable_user_rest_endpoint'] : '';
			if ( 'on' === $disable_user_rest_endpoint ) {
				add_filter( 'rest_endpoints', array( $this, 'llla_restrict_user_rest_endpoints' ) );
			}

			$disable_app_rest_endpoint = isset( $this->attempts_settings['disable_app_rest_endpoint'] ) ? $this->attempts_settings['disable_app_rest_endpoint'] : '';
			if ( 'on' === $disable_app_rest_endpoint && ! is_admin() ) {
				add_filter( 'rest_authentication_errors', array( $this, 'llla_restrict_app_rest_endpoints' ) );
			}

			$disable_app_pass_rest_endpoint = isset( $this->attempts_settings['disable_app_pass_rest_endpoint'] ) ? $this->attempts_settings['disable_app_pass_rest_endpoint'] : '';
			if ( 'on' === $disable_app_pass_rest_endpoint ) {
				add_filter( 'wp_is_application_passwords_available', array( $this, 'llla_restrict_app_passwords' ) );
			}
		}

		/**
		 * LoginPress Hide login Integration with TranslatePress and LoginPress Limit Login Attempts.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public function hide_login_integrate() {

			global $pagenow, $wpdb;
			$loginpress_hidelogin = $this->loginpress_hidelogin;
			if ( 'index.php' === $pagenow && $this->llla_time() && isset( $loginpress_hidelogin['rename_login_slug'] ) ) {

				$last_attempt_time = $wpdb->get_var( $wpdb->prepare( "SELECT `datentime` FROM `{$this->llla_table}` WHERE `ip` = %s ORDER BY `datentime` DESC", $this->ip ) ); // @codingStandardsIgnoreLine.

				$slug                 = isset( $loginpress_hidelogin['rename_login_slug'] ) ? $loginpress_hidelogin['rename_login_slug'] : '';
				$admin_url            = get_admin_url( null, '', 'admin' );
				$current_login_url    = home_url() . $slug . '/';
				$additional_login_url = home_url() . $slug;

				if ( isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ) {
					$url = 'https';
				} else {
					$url = 'http';
				}
				// Here append the common URL characters.
				$url .= '://';
				// Append the host(domain name, ip) to the URL.
				$url .= isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
				// Append the requested resource location to the URL.
				$url .= isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

				if ( ( $current_login_url === $url || $admin_url === $url || $additional_login_url === $url ) && $this->llla_time() ) {
					wp_die( wp_kses_post( $this->loginpress_lockout_error( $last_attempt_time ) ), 403 ); // @codingStandardsIgnoreLine.
				}
			}
			$this->llla_wp_loaded();
		}

		/**
		 * Compatibility with LoginPress - Login Widget Add-On.
		 *
		 * @since 3.0.0
		 * @return void|null
		 * @version 6.0.1
		 */
		public function loginpress_login_widget_integrate() {
			if ( is_user_logged_in() ) {
				return null;
			}
			global $wpdb;

			$attempts_allowed  = isset( $this->attempts_settings['attempts_allowed'] ) && intval( $this->attempts_settings['attempts_allowed'] ) !== 0 ? intval( $this->attempts_settings['attempts_allowed'] ) : intval( 4 );
			$last_attempt_time = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ip) FROM `{$this->llla_table}` WHERE `ip` = %s ORDER BY `datentime` DESC", $this->ip ) ); // @codingStandardsIgnoreLine.

			if ( $this->llla_time() && ( $last_attempt_time >= $attempts_allowed ) ) {
				add_filter( 'dynamic_sidebar_params', array( $this, 'loginpress_widget_params' ), 10 );
			}
		}

		/**
		 * Remove LoginPress - Login widgets if LoginPress - Limit Login Attempts applied.
		 *
		 * @param array $params Widget parameter.
		 * @since 3.0.0
		 * @return array Modified widget parameters.
		 */
		public function loginpress_widget_params( $params ) {

			foreach ( $params as $param_index => $param_val ) {

				if ( isset( $param_val['widget_id'] ) && strpos( $param_val['widget_id'], 'loginpress-login-widget' ) !== false ) {
					unset( $params[ $param_index ] );
				}
			}

			return $params;
		}

		/**
		 * Check Auth if request coming from xmlrpc.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public function llla_check_xml_request() {

			global $pagenow;
			if ( 'xmlrpc.php' === $pagenow ) {
				$this->llla_wp_loaded();
			}
		}

		/**
		 * Disable XML RPC request.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public function disable_xml_rpc() {

			add_filter( 'xmlrpc_enabled', '__return_false' );
		}

		/**
		 * Restrict access to user REST API endpoints.
		 *
		 * This function removes the '/wp/v2/users' and '/wp/v2/users/(?P<id>[\d]+)'
		 * endpoints from the list of available endpoints, effectively disabling
		 * access to user data through the REST API.
		 *
		 * @param array $endpoints An array of available REST API endpoints.
		 * @since 6.0.0
		 * @return array The modified array of REST API endpoints.
		 */
		public function llla_restrict_user_rest_endpoints( $endpoints ) {

			// Endpoints to restrict.
			$user_endpoints = array(
				'/wp/v2/users',
				'/wp/v2/users/(?P<id>[\d]+)', // Matches /wp/v2/users/<id>.
			);

			foreach ( $endpoints as $route => $endpoint ) {
				// Check if the route matches any user-related endpoints.
				foreach ( $user_endpoints as $user_endpoint ) {
					if ( preg_match( '#^' . $user_endpoint . '$#', $route ) ) {
						// Modify the endpoint to require authentication.
						$endpoints[ $route ][0]['callback'] = function ( $request ) {
							if ( ! is_user_logged_in() ) {
								return new WP_Error(
									'rest_user_access_denied',
									__( 'You must be logged in to access user data.', 'loginpress-pro' ),
									array( 'status' => 401 )
								);
							}
							// Call the original callback if authenticated.
							return rest_do_request( $request );
						};
					}
				}
			}

			return $endpoints;
		}

		/**
		 * Restrict app password usage for unauthenticated users.
		 *
		 * @since 6.0.0
		 * @return false
		 */
		public function llla_restrict_app_passwords() {
			return false;
		}

		/**
		 * Restrict REST API authentication for unauthenticated users.
		 *
		 * @param WP_Error|null|true $errors Existing authentication errors.
		 * @return WP_Error|null|true Modified errors.
		 * @since 6.0.0
		 */
		public function llla_restrict_app_rest_endpoints( $errors ) {

			if ( is_user_logged_in() ) {
				return $errors;
			}

			// Block authentication attempts for unauthenticated users.
			return new WP_Error(
				'rest_authentication_disabled',
				__( 'REST API authentication is disabled for remote access.', 'loginpress-pro' ),
				array( 'status' => 401 )
			);
		}

		/**
		 * Attempts Login Authentication.
		 *
		 * @param object $user Object of the user.
		 * @param string $username username.
		 * @param string $password password.
		 * @since 3.0.0
		 * @version 6.0.0
		 */
		public function llla_login_attempts_auth( $user, $username, $password ) {
			$this->username     = $username;
			$username_blacklist = isset( $this->attempts_settings['username_blacklisted'] ) ? $this->attempts_settings['username_blacklisted'] : '';

			if ( $username_blacklist ) {

				// Convert textarea input to an array of blocked usernames/patterns.
				$blocked_usernames_array = array_map( 'trim', preg_split( '/[\n,]+/', $username_blacklist ) );

				// Iterate through the blocked usernames or patterns.
				foreach ( $blocked_usernames_array as $blocked_pattern ) {

					if ( strpos( $blocked_pattern, '*' ) !== false ) {

						// If a wildcard is present, treat it as a pattern.
						$regex_pattern = '/^' . str_replace( '\*', '.*', preg_quote( $blocked_pattern, '/' ) ) . '$/i';

						if ( preg_match( $regex_pattern, $username ) ) {
							// Match found using wildcard pattern.
							return $this->loginpress_lla_block_user();
						}
					} elseif ( strtolower( $username ) === strtolower( $blocked_pattern ) ) { // Check for exact matches for non-wildcard entries.
							// Exact match found.
							return $this->loginpress_lla_block_user();
					}
				}
			}

			if ( isset( $_POST['g-recaptcha-response'] ) && empty( sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return;
			}
			if ( false === $this->edd_block_error ) {
				$this->edd_block_error = true;
				return;
			}

			if ( $user instanceof WP_User ) {
				return $user;
			}

			// Is username or password field empty?
			if ( empty( $username ) || empty( $password ) ) {

				if ( is_wp_error( $user ) ) {
					return $user;
				}

				$error = new WP_Error();

				if ( empty( $username ) ) {
					$error->add( 'empty_username', $this->limit_query( $username ) );
				}

				if ( empty( $password ) ) {
					$error->add( 'empty_password', $this->limit_query( $username ) );
				}

				return $error;
			}

			if ( ! empty( $username ) && ! empty( $password ) ) {

				$error = new WP_Error();
				global $pagenow, $wpdb;

				$whitelisted_ip = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->llla_table} WHERE ip = %s AND whitelist = %d LIMIT 1", $this->ip, 1 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				if ( $whitelisted_ip >= 1 ) {
					return null;
				} else {
					// Check if user exists.
					$error->add( 'llla_error', $this->limit_query( $username ) );
				}
				if ( class_exists( 'LifterLMS' ) && 'index.php' === $pagenow ) {
					/**
					 * Filter to show the Limit Login Attempts error on the LifterLMS login form.
					 *
					 * @since 5.0.0
					 */
					apply_filters( 'loginpress_llla_error_filter', false );
				}

				return $error;
			}
		}

		/**
		 * Auto-blaclist ip with specified username.
		 * Block the user by username.
		 *
		 * @return WP_Error The error to display to the user.
		 * @since 6.1.0
		 */
		private function loginpress_lla_block_user() {
			// Fetching user's IP address.
			$user_ip = $this->ip;

			global $wpdb;
			$current_time = current_time( 'timestamp' ); // phpcs:ignore

			// Check if the IP already exists in the database.
			$exist_record = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					"SELECT * FROM {$this->llla_table} WHERE ip = %s LIMIT 1", // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$user_ip
				)
			);

			// Optimize by deleting duplicate entries with same ip.
			if ( count( $exist_record ) > 0 ) {
				$id_to_keep = $exist_record[0]->id;

				$wpdb->query( // phpcs:ignore
					$wpdb->prepare(
						"DELETE FROM {$this->llla_table} WHERE ip = %s AND id != %d", // phpcs:ignore
						$user_ip,
						$id_to_keep
					)
				);
			}

			// Update or insert the record, blacklisting the ip.
			if ( count( $exist_record ) && '1' !== $exist_record[0]->blacklist ) {
				$wpdb->query( // phpcs:ignore
					$wpdb->prepare(
						"UPDATE `{$this->llla_table}` SET `whitelist` = '0', `blacklist` = '1', `gateway` = 'Manually' WHERE ip = %s", // phpcs:ignore
						$user_ip
					)
				);
			} else {
				$wpdb->query( // phpcs:ignore
					$wpdb->prepare(
						"INSERT INTO {$this->llla_table} (ip, blacklist, datentime, gateway) VALUES (%s, %s, %s, %s)", // phpcs:ignore
						$user_ip,
						'1',
						$current_time,
						__( 'Manually', 'loginpress-pro' )
					)
				);
			}

			// Return an error to block the user.
			return new WP_Error( 'username_blacklisted', __( 'You are blacklisted from accessing the site.', 'loginpress-pro' ) );
		}

		/**
		 * Die WordPress login on blacklist or lockout.
		 *
		 * @since  3.0.0
		 */
		public function llla_wp_loaded() {
			if ( is_user_logged_in() ) {
				return;
			}
			if ( class_exists( 'Easy_Digital_Downloads' ) ) {
				// Unset the 'edd_invalid_login' error to prevent the default Easy Digital Downloads login error
				// from displaying, allowing custom error handling via LoginPress.
				edd_unset_error( 'edd_invalid_login' );
			}
			global $pagenow, $wpdb;

			$last_attempt_time = $wpdb->get_var( $wpdb->prepare( "SELECT `datentime` FROM `{$this->llla_table}` WHERE `ip` = %s ORDER BY `datentime` DESC", $this->ip ) ); // @codingStandardsIgnoreLine.

			$blacklist_check = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$this->llla_table}` WHERE `ip` = %s AND `blacklist` = 1", $this->ip ) ); // @codingStandardsIgnoreLine.

			if ( 'xmlrpc.php' === $pagenow && ( $this->llla_time() || $blacklist_check >= 1 ) ) {
				wp_die( wp_kses_post( $this->loginpress_lockout_error( $last_attempt_time ) ), 403 );
			}
			$blacklist_message = isset( $this->attempts_settings['blacklist_message'] ) && ! empty( $this->attempts_settings['blacklist_message'] ) ? sanitize_text_field( $this->attempts_settings['blacklist_message'] ) : __( 'You are not allowed to access admin panel', 'loginpress-pro' );
			// limit wp-admin access.
			if ( is_admin() && $blacklist_check >= 1 ) {
				wp_die( esc_html( $blacklist_message ), 403 ); // @codingStandardsIgnoreLine.
			}

			// limit wp-login.php access if blacklisted.
			if ( 'wp-login.php' === $pagenow && $blacklist_check >= 1 ) {
				wp_die( esc_html( $blacklist_message ), 403 ); // @codingStandardsIgnoreLine.
			}

			// limit wp-login.php access if time remains.
			if ( 'wp-login.php' === $pagenow && $this->llla_time() && $this->loginpress_lockout_error( $last_attempt_time ) ) {
				wp_die( wp_kses_post( $this->loginpress_lockout_error( $last_attempt_time ) ), 403 ); // @codingStandardsIgnoreLine.
			}

			// limit WooCommerce Account access if blacklisted.
			if ( 'index.php' === $pagenow && $blacklist_check >= 1 && class_exists( 'WooCommerce' ) ) {

				remove_shortcode( 'woocommerce_my_account' );
				add_shortcode( 'woocommerce_my_account', array( $this, 'woo_blacklisted_error' ) );
			}

			// limit WooCommerce Account access if time remains.
			if ( $this->llla_time() && class_exists( 'WooCommerce' ) && 'index.php' === $pagenow ) {
				remove_shortcode( 'woocommerce_my_account' );
				remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form' );
				add_shortcode( 'woocommerce_my_account', array( $this, 'woo_attempt_error' ) );
			}

			// Handle EDD login forms when blacklisted.
			if ( 'index.php' === $pagenow && $blacklist_check >= 1 && class_exists( 'Easy_Digital_Downloads' ) ) {
				remove_shortcode( 'edd_login' );
				add_shortcode( 'edd_login', array( $this, 'loginpress_edd_blacklisted_error' ) );
				add_filter( 'render_block', array( $this, 'lp_render_edd_blacklisted_error_block' ), 10, 2 );
			}

			// Handle EDD login forms when lockout time remains.
			if ( $this->llla_time() && class_exists( 'Easy_Digital_Downloads' ) && 'index.php' === $pagenow ) {

				remove_shortcode( 'edd_login' );
				add_shortcode( 'edd_login', array( $this, 'loginpress_edd_attempt_error' ) );
				if ( true === $this->edd_block_error ) {
					$this->edd_block_error = false;
					add_filter( 'render_block', array( $this, 'lp_render_edd_attempt_error_block' ), 10, 2 );
				}
			}
		}

		/**
		 * Replaces the EDD login block with the blacklisted error content.
		 *
		 * @param string $block_content The original block content.
		 * @param array  $block         The block being rendered.
		 * @return string Modified block content.
		 * @since 5.0.0
		 */
		public function lp_render_edd_blacklisted_error_block( $block_content, $block ) {
			if ( isset( $block['blockName'] ) && 'edd/login' === $block['blockName'] ) {
				return $this->loginpress_edd_blacklisted_error( array() );
			}
			return $block_content;
		}

		/**
		 * Replaces the EDD login block with the login attempt error content.
		 *
		 * @param string $block_content The original block content.
		 * @param array  $block         The block being rendered.
		 * @return string Modified block content.
		 * @since 5.0.0
		 */
		public function lp_render_edd_attempt_error_block( $block_content, $block ) {
			if ( isset( $block['blockName'] ) && 'edd/login' === $block['blockName'] ) {
				return $this->loginpress_edd_attempt_error();
			}
			return $block_content;
		}

		/**
		 * Callback for error message 'EDD login blacklisted'
		 *
		 * @since 5.0.0
		 */
		public function loginpress_edd_blacklisted_error() {
			$blacklist_message = isset( $this->attempts_settings['blacklist_message'] ) && ! empty( $this->attempts_settings['blacklist_message'] ) ? sanitize_text_field( $this->attempts_settings['blacklist_message'] ) : __( 'You are not allowed to access admin panel', 'loginpress-pro' );
			echo '<div class="edd-alert-error">';
			echo esc_html( $blacklist_message );// @codingStandardsIgnoreLine.
			echo '</div>';
		}

		/**
		 * Callback for error message 'EDD login attempt error'
		 *
		 * @since 5.0.0
		 */
		public function loginpress_edd_attempt_error() {
			echo '<div class="edd-alert-error">';

			global $wpdb;
			$last_attempt_time = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$this->llla_table}` WHERE `ip` = %s ORDER BY `datentime` DESC", $this->ip ) ); // @codingStandardsIgnoreLine.
			if ( $last_attempt_time ) {
				$last_attempt_time = $last_attempt_time->datentime;
			}
			echo wp_kses_post( $this->loginpress_lockout_error( $last_attempt_time ) );
			echo '</div>';
		}

		/**
		 * Callback for error message 'woocommerce my-account login blacklisted'
		 *
		 * @since 2.1.0
		 */
		public function woo_blacklisted_error() {
			$blacklist_message = isset( $this->attempts_settings['blacklist_message'] ) && ! empty( $this->attempts_settings['blacklist_message'] ) ? sanitize_text_field( $this->attempts_settings['blacklist_message'] ) : __( 'You are not allowed to access admin panel', 'loginpress-pro' );
			?>
			<div class="woocommerce-error">
				<?php
				echo esc_html( $blacklist_message );// @codingStandardsIgnoreLine.
				?>
			</div>
			<?php
		}

		/**
		 * Callback for error message 'woocommerce my-account login attempt'
		 *
		 * @since 2.1.0
		 */
		public function woo_attempt_error() {

			echo '<div class="woocommerce-error">';

			global $wpdb;
			$last_attempt_time = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$this->llla_table}` WHERE `ip` = %s ORDER BY `datentime` DESC", $this->ip ) ); // @codingStandardsIgnoreLine.
			if ( $last_attempt_time ) {
				$last_attempt_time = $last_attempt_time->datentime;
			}
			echo wp_kses_post( $this->loginpress_lockout_error( $last_attempt_time ) );
			echo '</div>';
		}

		/**
		 * Check the limit
		 *
		 * @since 2.1.0
		 * @version 6.1.1
		 * @return array The limit check results.
		 */
		public function user_limit_check() {

			global $wpdb;
			$current_time = current_time( 'timestamp' ); // @codingStandardsIgnoreLine.
			$gate         = $this->gateway();

			$attempts_allowed  = isset( $this->attempts_settings['attempts_allowed'] ) ? $this->attempts_settings['attempts_allowed'] : 4;
			$lockout_increase  = isset( $this->attempts_settings['lockout_increase'] ) ? $this->attempts_settings['lockout_increase'] : 0;
			$minutes_lockout   = isset( $this->attempts_settings['minutes_lockout'] ) ? intval( $this->attempts_settings['minutes_lockout'] ) : 20;
			$last_attempt_time = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$this->llla_table}` WHERE `ip` = %s ORDER BY `datentime` DESC", $this->ip ) ); // @codingStandardsIgnoreLine.

			if ( $last_attempt_time ) {
				$last_attempt_time = $last_attempt_time->datentime;
			}

			$lockout_time = $current_time - ( $minutes_lockout * 60 );
			$attempt_time = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$this->llla_table}` WHERE `ip` = %s AND `datentime` > %s", $this->ip, $lockout_time ) ); // @codingStandardsIgnoreLine.

			return array(
				'attempts_allowed'  => $attempts_allowed,
				'lockout_increase'  => $lockout_increase,
				'minutes_lockout'   => $minutes_lockout,
				'last_attempt_time' => $last_attempt_time,
				'lockout_time'      => $lockout_time,
				'attempt_time'      => $attempt_time,
			);
		}
	}

endif;

new LoginPress_Attempts();
