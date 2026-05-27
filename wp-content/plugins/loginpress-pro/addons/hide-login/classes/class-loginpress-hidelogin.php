<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * LoginPress_HideLogin_Main class
 *
 * @package   LoginPress
 * @category  Core
 * @author    WPBrigade
 * @version   6.1.0
 */

if ( ! class_exists( 'LoginPress_HideLogin_Main' ) ) :

	// Include the settings trait.
	require_once __DIR__ . '/traits/settings.php';

	/**
	 * LoginPress_HideLogin_Main class
	 *
	 * @since 3.0.0
	 * @version 6.1.0
	 */
	class LoginPress_HideLogin_Main {

		use LoginPress_HideLogin_Settings;

		/**
		 * WordPress login PHP flag.
		 *
		 * @since  3.0.0
		 * @access private
		 * @var bool
		 */
		private $wp_login_php;

		/**
		 * The Instance of Hide Login Class.
		 *
		 * @since 3.0.0
		 * @var object
		 */
		protected static $instance = null;

		/**
		 * The LoginPress Hide Login Slug.
		 *
		 * @since 3.0.0
		 * @var string
		 */
		private $slug;

		/**
		 * Class Constructor.
		 *
		 * @since 1.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function __construct() {

			$this->hooks();
		}

		/**
		 * Hook into actions and filters.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		private function hooks() {
			add_action( 'rest_api_init', array( $this, 'lp_hidelogin_register_routes' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'loginpress_hidelogin_admin_action_scripts' ) );
			add_filter( 'loginpress_settings_tab', array( $this, 'loginpress_hidelogin_tab' ), 10, 1 );
			add_filter( 'loginpress_settings_fields', array( $this, 'loginpress_hidelogin_settings_array' ), 10, 1 );
			add_filter( 'loginpress_hidelogin', array( $this, 'loginpress_hidelogin_callback' ), 10, 2 );

			$loginpress_hidelogin = get_option( 'loginpress_hidelogin' );
			$this->slug           = isset( $loginpress_hidelogin['rename_login_slug'] ) ? $loginpress_hidelogin['rename_login_slug'] : '';

			if ( ! empty( $this->slug ) ) {
				add_action( 'plugins_loaded', array( $this, 'loginpress_hidelogin_loaded' ), 30 );
				add_action( 'wp_loaded', array( $this, 'loginpress_hidelogin_wp_loaded' ) );
				add_filter( 'site_url', array( $this, 'site_url' ), 10, 4 );
				add_filter( 'network_site_url', array( $this, 'network_site_url' ), 10, 3 );
				add_filter( 'wp_redirect', array( $this, 'wp_redirect' ), 10, 2 );
				add_action( 'wp_ajax_reset_login_slug', array( $this, 'loginpress_hidelogin_reset_login_slug' ) );

				remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
			}
		}

		/**
		 * LoginPress Addon updater.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public function init_addon_updater() {
			if ( class_exists( 'LoginPress_AddOn_Updater' ) ) {
				$updater = new LoginPress_AddOn_Updater( 2162, LOGINPRESS_HIDE_ROOT_FILE, $this->version );
			}
		}

		/**
		 * Function hidelogin_use_slashes is used to check the trailing slashes.
		 *
		 * @since 3.0.0
		 * @return string Permalink structure.
		 */
		private function hidelogin_use_slashes() {

			return ( '/' === substr( get_option( 'permalink_structure' ), -1, 1 ) );
		}

		/**
		 * The hidelogin_user_trailing description.
		 *
		 * @param string $input_string Description.
		 * @since 3.0.0
		 * @return string Trailing slash or no trailing slash.
		 */
		private function hidelogin_user_trailing( $input_string ) {

			return $this->hidelogin_use_slashes() ? trailingslashit( $input_string ) : untrailingslashit( $input_string );
		}

		/**
		 * The Template Loader.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		private function wp_template_loader() {
			global $pagenow;

			$pagenow = 'index.php'; // @codingStandardsIgnoreLine.

			if ( ! defined( 'WP_USE_THEMES' ) ) {
				define( 'WP_USE_THEMES', true );
			}
			wp();
			if ( isset( $_SERVER['REQUEST_URI'] ) && $_SERVER['REQUEST_URI'] === $this->hidelogin_user_trailing( str_repeat( '-/', 10 ) ) ) {
				$_SERVER['REQUEST_URI'] = $this->hidelogin_user_trailing( '/wp-login-php/' );
			}
			require_once ABSPATH . WPINC . '/template-loader.php';
			die;
		}

		/**
		 * Create the new login slug.
		 *
		 * @since 1.0.0
		 * @version 3.0.0
		 * @return string The New Login Slug.
		 */
		private function new_login_slug() {

			$loginpress_hidelogin = get_option( 'loginpress_hidelogin' );
			$slug                 = isset( $loginpress_hidelogin['rename_login_slug'] ) ? $loginpress_hidelogin['rename_login_slug'] : 'mylogin';

			return $slug;
		}

		/**
		 * New Login URL.
		 *
		 * @param string $scheme The Scheme.
		 * @since 3.0.0
		 * @return string New Login URL.
		 */
		public function new_login_url( $scheme = null ) {

			if ( get_option( 'permalink_structure' ) ) {

				return $this->hidelogin_user_trailing( home_url( '/', $scheme ) . $this->new_login_slug() );
			} else {

				return home_url( '/', $scheme ) . '?' . $this->new_login_slug();
			}
		}

		/**
		 * Main Instance.
		 *
		 * @since 1.0.0
		 * @return object Main instance of the Class.
		 * @static
		 * @see loginpress_hidelogin_loader()
		 */
		public static function instance() {

			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * The loginpress_hidelogin_loaded For multi-site URL parse.
		 *
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function loginpress_hidelogin_loaded() {

			global $pagenow;
			$request = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

			// Check is multi-site setup.
			if ( ! is_multisite() && ( ( ! empty( $request ) && strpos( $request, 'wp-signup' ) !== false ) || strpos( $request, 'wp-activate' ) !== false ) ) {

				wp_die( esc_html__( 'This feature is not enabled.', 'loginpress-pro' ) );

			}

			// Set request variable.
			if ( isset( $_SERVER['REQUEST_URI'] ) ) {
				$request = wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
			}

			// If wp-login.php or admin page is accessed.
			if ( isset( $request['path'] ) && ( strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'wp-login.php' ) !== false || untrailingslashit( $request['path'] ) === site_url( 'wp-login', 'relative' ) ) && ! is_admin() ) {

				$this->wp_login_php     = true;
				$_SERVER['REQUEST_URI'] = $this->hidelogin_user_trailing( '/' . str_repeat( '-/', 10 ) );

			} elseif ( isset( $request['path'] ) && untrailingslashit( $request['path'] ) === home_url( $this->new_login_slug(), 'relative' ) || ( ! get_option( 'permalink_structure' ) && isset( $_GET[ $this->new_login_slug() ] ) && empty( $_GET[ $this->new_login_slug() ] ) ) ) { // @codingStandardsIgnoreLine.

				$pagenow = 'wp-login.php'; // @codingStandardsIgnoreLine.

			}
		}

		/**
		 * The loginpress_hidelogin_wp_loaded For Site URL parse.
		 *
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function loginpress_hidelogin_wp_loaded() {

			global $pagenow;
			global $TRP_LANGUAGE; // @codingStandardsIgnoreLine.

			// limit wp-admin access.
			if ( is_admin() && ! is_user_logged_in() && ! defined( 'DOING_AJAX' ) && 'admin-post.php' !== $pagenow ) {
				apply_filters( 'loginpress_hidelogin_wp_admin_redirect', wp_safe_redirect( get_site_url() . '/404' ) );
				exit();
			}

			$request = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) : '';
			$slug    = isset( $this->slug ) ? '/' . $this->slug . '/' : '';

			// if URL lang_code and TranslatePress is activated.
			if ( null !== $TRP_LANGUAGE ) { // @codingStandardsIgnoreLine.
				$language_code     = explode( '_', $TRP_LANGUAGE ); // @codingStandardsIgnoreLine.
				$additional_slug[] = isset( $this->slug ) ? '/' . $language_code[0] . '/' . $this->slug . '/' : '';
			} else {
				$additional_slug[] = isset( $this->slug ) ? '/' . $this->slug : '';
			}

			if ( 'wp-login.php' === $pagenow && $request['path'] !== $this->hidelogin_user_trailing( $request['path'] ) && get_option( 'permalink_structure' ) ) {

				wp_safe_redirect( $this->hidelogin_user_trailing( $this->new_login_url() ) . ( ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . wp_unslash( $_SERVER['QUERY_STRING'] ) : '' ) );
				die;

			} elseif ( $this->wp_login_php ) {
				$get_referer = wp_get_referer();
				if ( $get_referer ) {

					$referer = wp_parse_url( $get_referer );
					if ( strpos( $get_referer, 'wp-activate.php' ) !== false && ( $referer ) && ! empty( $referer['query'] ) ) {

						parse_str( $referer['query'], $referer );

						if ( ! empty( $referer['key'] ) ) {
							$result = wpmu_activate_signup( $referer['key'] );
							if ( is_wp_error( $result ) && ( $result->get_error_code() === 'already_active' || $result->get_error_code() === 'blog_taken' ) ) {
								wp_safe_redirect( $this->new_login_url() . ( ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . wp_unslash( $_SERVER['QUERY_STRING'] ) : '' ) );
								die;
							}
						}
					}
				}

				$this->wp_template_loader();
				/**
				 * If the request path matches with the additional slug.
				 */
			} elseif ( ( 'index.php' === $pagenow && strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), $slug ) === true ) ||
			( 'index.php' === $pagenow && isset( $request['path'] ) && strpos( $request['path'], $additional_slug[0] ) === true ) ||
			( 'wp-login.php' === $pagenow ) ) {

				global $error, $interim_login, $action, $user_login;
				@require_once ABSPATH . 'wp-login.php'; // @codingStandardsIgnoreLine.
				die;
			} elseif ( ( 'index.php' === $pagenow && true === strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), $slug ) ) ||
			( 'index.php' === $pagenow && strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), $additional_slug[0] ) === true ) ||
			( 'wp-login.php' === $pagenow ) ) {

				global $error, $interim_login, $action, $user_login;
				@require_once ABSPATH . 'wp-login.php'; // @codingStandardsIgnoreLine.
				die;

			}
		}

		/**
		 * Retrieves the URL for the current site where WordPress application files.
		 *
		 * @param string $url The URL.
		 * @param string $path The Path.
		 * @param string $scheme The Scheme.
		 * @param string $blog_id Blog ID.
		 * @since 3.0.0
		 * @return string Modified URL.
		 */
		public function site_url( $url, $path, $scheme, $blog_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

			return $this->loginpress_filter_login_page( $url, $scheme );
		}

		/**
		 * Retrieves the site URL for the current network.
		 *
		 * @param string $url The URL.
		 * @param string $path The Path.
		 * @param string $scheme The Scheme.
		 * @since 3.0.0
		 * @return string Modified URL.
		 */
		public function network_site_url( $url, $path, $scheme ) {

			return $this->loginpress_filter_login_page( $url, $scheme );
		}

		/**
		 * Redirect to the location.
		 *
		 * @param string $location The path to redirect to.
		 * @param int    $status   Status code to use.
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return string Modified location.
		 */
		public function wp_redirect( $location, $status ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

			return $this->loginpress_filter_login_page( $location );
		}

		/**
		 * Filter the Login Page.
		 *
		 * @param string $url     The URL.
		 * @param string $scheme  The Scheme.
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return string Modified URL.
		 */
		public function loginpress_filter_login_page( $url, $scheme = null ) {

			if ( strpos( $url, 'wp-login.php' ) !== false ) {

				if ( is_ssl() ) {
					$scheme = 'https';
				}

				$args = explode( '?', $url );
				if ( isset( $args[1] ) ) {
					parse_str( $args[1], $args );
					$url = add_query_arg( $args, $this->new_login_url( $scheme ) );
				} else {
					$url = $this->new_login_url( $scheme );
				}
			}
			return $url;
		}
	}
endif;
