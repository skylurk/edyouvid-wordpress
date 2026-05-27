<?php
/**
 * LoginPress Main Admin Trait file.
 *
 * @package LoginPress
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LoginPress Main Admin Trait.
 *
 * Handles Some helping functions from loginpress-main.php file.
 * Handles the Admin side of the plugin.
 *
 * @package   LoginPress
 * @subpackage Traits\Loginpress-main
 * @since   6.1.0
 */

if ( ! trait_exists( 'LoginPress_Main_Admin_Trait' ) ) {
	/**
	 * LoginPress Main Admin Trait.
	 *
	 * Handles Some helping functions from loginpress-main.php file.
	 * Handles the Admin side of the plugin.
	 *
	 * @package   LoginPress
	 * @subpackage Traits\Loginpress-main
	 * @since   6.1.0
	 */
	trait LoginPress_Main_Admin_Trait {

		/**
		 * Register the rest routes for social login.
		 *
		 * @since  6.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function lp_main_register_routes() {
			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/license',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'loginpress_pro_get_license_data' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/license',
				array(
					array(
						'methods'             => 'POST',
						'callback'            => array( $this, 'loginpress_pro_handle_license_request' ),
						'permission_callback' => 'loginpress_rest_can_manage_options',
						'args'                => array(
							'action'      => array(
								'required'          => true,
								'validate_callback' => array( $this, 'loginpress_rest_action' ),
							),
							'license_key' => array(
								'required'          => true,
								'sanitize_callback' => 'sanitize_text_field',
							),
						),
					),
				)
			);

			// Search users.
			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/search-users',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_search_users' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);
		}

		/**
		 * Search users in autologin table.
		 *
		 * @param WP_REST_Request $request The REST request object.
		 * @return array
		 * @since   6.0.0
		 */
		public function loginpress_search_users( WP_REST_Request $request ) {
			$search = sanitize_text_field( $request['search'] );

			$user_query = new WP_User_Query(
				array(
					'search'         => $search . '*', // Note the asterisk for partial matching.
					'search_columns' => array( 'user_login', 'user_email' ),
					'number'         => 10, // Limit results.
				)
			);

			$users = array();
			// If no results, return immediately.
			if ( empty( $user_query->get_results() ) ) {
				return array( 'users' => $users );
			}
			foreach ( $user_query->get_results() as $user ) {
				$users[] = array(
					'ID'         => (int) $user->ID,
					'user_login' => sanitize_user( $user->user_login ),
					'user_email' => sanitize_email( $user->user_email ),
				);
			}

			return array( 'users' => $users );
		}

		/**
		 * Get license data.
		 *
		 * @since  6.0.0
		 * @version 6.1.0
		 * @return array
		 */
		public function loginpress_pro_get_license_data() {
			$registration_data = get_option( 'loginpress_pro_registration_data' );
			$license_key       = isset( $registration_data['license_key'] ) ? $registration_data['license_key'] : '';
			$license_data      = isset( $registration_data['license_data'] ) ? $registration_data['license_data'] : array();

			$status        = isset( $license_data['license'] ) ? $license_data['license'] : 'invalid';
			$expiration    = isset( $license_data['expires'] ) ? $license_data['expires'] : '';
			$type          = ( isset( $license_data['price_id'] ) && '6' === $license_data['price_id'] ) ? 'lifetime' : 'yearly';
			$error_message = isset( $registration_data['error_message'] ) ? $registration_data['error_message'] : '';

			// Mask the license key (e.g., ****-****-****-c8f).
			$masked_key = '';
			if ( $license_key ) {
				$visible_part = substr( $license_key, -4 );
				$masked_part  = str_repeat( '*', strlen( $license_key ) - 4 );
				$masked_key   = $masked_part . $visible_part;
			}

			return array(
				'success'     => true,
				'license_key' => sanitize_text_field( $masked_key ),
				'status'      => sanitize_text_field( $status ),
				'expiration'  => sanitize_text_field( 'lifetime' === $type ? 'lifetime' : $expiration ),
				'type'        => sanitize_text_field( $type ),
				'error'       => sanitize_text_field( ( 'valid' !== $status ) ? $error_message : '' ),
			);
		}

		/**
		 * Handle license activation/deactivation.
		 *
		 * @since 6.0.0
		 * @version 6.1.0
		 * @param WP_REST_Request $request The REST request object.
		 * @return array
		 */
		public function loginpress_pro_handle_license_request( WP_REST_Request $request ) {
			$action      = $request->get_param( 'action' );
			$license_key = $request->get_param( 'license_key' );

			if ( 'activate' === $action ) {
				$result = self::activate_license( $license_key );
			} else {
				$license = get_option( 'loginpress_pro_license_key' );
				$result  = self::deactivate_license( $license );
			}

			// Mask the license key (e.g., ****-****-****-c8f).
			$masked_key = '';
			if ( $result['license_key'] ) {
				$visible_part = substr( $result['license_key'], -4 );
				$masked_part  = str_repeat( '*', strlen( $result['license_key'] ) - 4 );
				$masked_key   = $masked_part . $visible_part;
			}
			if ( ! empty( $result['license_data']['success'] ) ) {
				update_option( 'loginpress_pro_license_key', $result['license_key'] );
			}
			// Prepare response based on the result.
			$response = array(
				'success'     => ! empty( $result['license_data']['success'] ),
				'license_key' => sanitize_text_field( $masked_key ),
				'status'      => sanitize_text_field( get_option( 'loginpress_pro_license_status', 'invalid' ) ),
				'expiration'  => sanitize_text_field( self::get_expiration_date() ),
				'type'        => sanitize_text_field( self::get_license_type() ),
			);

			// Include error message if activation failed.
			if ( ! empty( $result['error_message'] ) ) {
				$response['error'] = sanitize_text_field( $result['error_message'] );
			}

			return $response;
		}

		/**
		 * Check user permissions.
		 *
		 * @since  6.0.0
		 * @version 6.1.0
		 * @param string $param The parameter to check.
		 * @return bool
		 */
		public function loginpress_rest_action( $param ) {
			return in_array( $param, array( 'activate', 'deactivate' ), true );
		}

		/**
		 * Install LoginPress add-ons for registered users automatically from WordPress.
		 *
		 * @since 6.1.0
		 * @param object $api The API object.
		 * @param string $action The action to perform.
		 * @param object $args The arguments.
		 * @return object|false
		 */
		public function install_addons( $api, $action, $args ) {

			$data = array();

			if ( 'plugin_information' === $action && empty( $api ) && ( ! empty( $_GET['lgp'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				if ( ! self::is_registered() ) {
					echo esc_html__( 'No license key Registered by user.', 'loginpress-pro' );
					return false;
				}

				$data['license'] = get_option( 'loginpress_pro_license_key' );

				if ( empty( $data['license'] ) ) {
					echo esc_html__( 'No license key entered by user.', 'loginpress-pro' );
					return false;
				}

				$addon_id = isset( $_GET['id'] ) ? absint( sanitize_text_field( wp_unslash( $_GET['id'] ) ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				$api_params = array(
					'loginpress_action' => 'install-loginpress-addons',
					'license'           => $data['license'],
					'slug'              => $args->slug,
					'addon_id'          => $addon_id,
					'url'               => home_url(),
				);

				$request = wp_remote_post(
					self::LOGINPRESS_SITE_URL,
					array(
						'sslverify' => false,
						'body'      => $api_params,
					)
				);

				if ( is_wp_error( $request ) || 200 !== $request['response']['code'] ) {
					return false;
				} else {
					$plugin = json_decode( wp_remote_retrieve_body( $request ) );

					$api                = new stdClass();
					$api->name          = $plugin->name;
					$api->version       = $plugin->new_version;
					$api->download_link = $plugin->download_url;
				}
			}

			return $api;
		}

		/**
		 * LoginPress Google Fonts.
		 *
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function loginpress_pro_google_fonts() {

			check_ajax_referer( 'loginpressprocustomize_nonce', 'nonce' );
			if ( current_user_can( 'manage_options' ) ) {
				$loginpress_google_font = isset( $_POST['fontName'] ) ? sanitize_text_field( wp_unslash( $_POST['fontName'] ) ) : false;

				if ( ! $loginpress_google_font ) {
					return;
				}

				$json_file       = file_get_contents( LOGINPRESS_PRO_ROOT_PATH . '/fonts/google-web-fonts.txt' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				$json_font       = json_decode( $json_file );
				$json_font_array = $json_font->items;
				$font_array      = array();

				foreach ( $json_font_array as $key ) {
					$loginpress_get_font = $loginpress_google_font === $key->family ? $loginpress_google_font : false;
					if ( $loginpress_get_font ) {
						$font_array[] = $key;
					}
				}

				$loginpress_font_name = $font_array[0]->family;
				$font_weights         = $font_array[0]->variants;
				$font_weight          = implode( ',', $font_weights );
				$subsets              = $font_array[0]->subsets;
				$subset               = implode( ',', $subsets );
				$font_families        = array();
				$font_families[]      = "{$loginpress_font_name}:{$font_weight}";
				$query_args           = array(
					'family' => rawurlencode( implode( '|', $font_families ) ),
					'subset' => rawurlencode( $subset ),
				);

				$fonts_url = add_query_arg( $query_args, 'https://fonts.googleapis.com/css' );

				echo esc_url_raw( $fonts_url );
			}
			wp_die();
		}

		/**
		 * Create Admin Menu Page.
		 *
		 * @since 1.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function admin_menu() {

			/**
			 * Add sub-menu page for Managing the License.
			 */
			add_submenu_page(
				'loginpress-settings',
				esc_html__( 'Activate your license to get automatic plugin updates and premium support.', 'loginpress-pro' ),
				'<b style="color:#5fcf80">' . esc_html__( 'License Manager', 'loginpress-pro' ) . '</b>',
				'administrator', // phpcs:ignore
				'loginpress-license',
				array( $this, 'loginpress_license' )
			);

			/**
			 * Apply filters for hide/show the LoginPress license page.
			 *
			 * @since 2.1.1
			 * @var boolean
			 */
			$show_license = apply_filters( 'loginpress_show_license_page', true );

			if ( ! $show_license ) {
				add_action( 'admin_menu', array( $this, 'loginress_license_menu_page_removing' ), 11 );
			}
		}

		/**
		 * LoginPress License.
		 *
		 * @version 6.1.0
		 * @return void
		 */
		public function loginpress_license() {
			// If version < 6.0.0 include legacy license manager.
			if ( defined( 'LOGINPRESS_VERSION' ) && ( version_compare( LOGINPRESS_VERSION, '6.0.0', '<' ) || version_compare( LOGINPRESS_PRO_VERSION, '6.0.0', '<' ) ) ) {
				include_once LOGINPRESS_PRO_ROOT_PATH . '/includes/license-manager.php';
			}
		}

		/**
		 * Redirect the License page to the React one if LoginPress is 6.0.0 or greater.
		 *
		 * @version 6.1.0
		 * @return void
		 */
		public function redirect_license_page() {
			// Only run in wp-admin.
			if ( ! is_admin() ) {
				return;
			}

			// If user is on loginpress-license page AND version >= 6.0.0.
			if (
			isset( $_GET['page'] ) && // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'loginpress-license' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) && // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			defined( 'LOGINPRESS_VERSION' ) &&
			version_compare( LOGINPRESS_VERSION, '6.0.0', '>=' ) &&
			version_compare( LOGINPRESS_PRO_VERSION, '6.0.0', '>=' )
			) {
				wp_safe_redirect( admin_url( 'admin.php?page=loginpress-settings&tab=loginpress_pro_license' ) );
				exit;
			}
		}

		/**
		 * The admin scripts.
		 *
		 * @since 6.1.0
		 * @param string $hook The page ID.
		 * @return void
		 */
		public function loginpress_admin_scripts( $hook ) {

			if ( 'toplevel_page_loginpress-settings' === $hook || 'loginpress_page_loginpress-license' === $hook || 'users.php' === $hook || 'widgets.php' === $hook ) {
				wp_enqueue_script( 'jquery-ui-core' );
				wp_enqueue_script( 'jquery-ui-autocomplete' );

				$loginpress_setting = get_option( 'loginpress_setting' );
				$cap_language       = isset( $loginpress_setting['hcaptcha_language'] ) ? $loginpress_setting['hcaptcha_language'] : 'en';
				wp_enqueue_script( 'main-js', plugins_url( '../../assets/js/main.js', __FILE__ ), array( 'jquery' ), LOGINPRESS_PRO_VERSION, true );
				wp_localize_script(
					'main-js',
					'loginpressLicense',
					array(
						'ajaxurl'       => admin_url( 'admin-ajax.php' ),
						'license_nonce' => wp_create_nonce( 'loginpress_deactivate_license' ),
						'admin_url'     => admin_url(),
						'recaptchaUrl'  => 'https://www.google.com/recaptcha/api.js',
						'hcaptchaUrl'   => 'https://js.hcaptcha.com/1/api.js?hl=' . $cap_language,
					)
				);

				if ( isset( $_GET['page'] ) && 'loginpress-settings' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					if ( version_compare( LOGINPRESS_VERSION, '6.0.0', '<' ) || version_compare( LOGINPRESS_PRO_VERSION, '6.0.0', '<' ) ) {
						wp_enqueue_script(
							'loginpress-all-addons',
							plugins_url( '../assets/js/main.min.js', __DIR__ ),
							array( 'jquery' ), // Dependencies.
							LOGINPRESS_PRO_VERSION,
							true
						);
					} else {
						wp_enqueue_script(
							'loginpress-all-addons',
							plugins_url( '../addons/social-login/assets/js/providers.js', __DIR__ ),
							array( 'jquery' ), // Dependencies.
							LOGINPRESS_PRO_VERSION,
							true
						);
					}
				}

				// All addons status.
				$addons = get_option( 'loginpress_pro_addons', array() );

				// Filter only active addons.
				$active_addons = array();
				if ( ! empty( $addons ) && is_array( $addons ) ) {
					foreach ( $addons as $key => $addon ) {
						if ( ! empty( $addon['is_active'] ) ) {
							$active_addons[] = $key; // Store only active addon slugs.
						}
					}
				}

				// Pass active addons to JS.
				wp_localize_script(
					'loginpress-all-addons', // Your main JS file handle.
					'loginpressProData',
					array( 'activeAddons' => $active_addons )
				);

				// Social login.
				$provider_descriptions = array(
					'facebook'  => sprintf(
						'<p>%s</p><a href="%s" target="_blank">%s</a>',
						__( 'Follow our step-by-step guide here in case you need any help setting up the Facebook social login.', 'loginpress-pro' ),
						esc_url( 'https://loginpress.pro/doc/add-facebook-social-login-loginpress/?utm_source=loginpress-pro&utm_medium=social-login-settings&utm_campaign=user-guide&utm_content=facebook' ),
						__( 'How to Integrate Facebook Social Login on WordPress', 'loginpress-pro' )
					),
					'twitter'   => sprintf(
						'<p>%s</p><a href="%s" target="_blank">%s</a>',
						__( 'Follow our step-by-step guide here in case you need any help setting up the Twitter social login.', 'loginpress-pro' ),
						esc_url( 'https://loginpress.pro/doc/add-twitter-social-login-loginpress/?utm_source=loginpress-pro&utm_medium=social-login-settings&utm_campaign=user-guide&utm_content=twitter' ),
						__( 'How to Integrate Twitter Social Login on WordPress', 'loginpress-pro' )
					),
					'google'    => sprintf(
						'<p>%s</p><a href="%s" target="_blank">%s</a>',
						__( 'Follow our step-by-step guide here in case you need any help setting up the Google social login.', 'loginpress-pro' ),
						esc_url( 'https://loginpress.pro/doc/add-google-social-login-loginpress/?utm_source=loginpress-pro&utm_medium=social-login-settings&utm_campaign=user-guide&utm_content=google' ),
						__( 'How to Integrate Google Social Login on WordPress', 'loginpress-pro' )
					),
					'linkedin'  => sprintf(
						'<p>%s</p><a href="%s" target="_blank">%s</a>',
						__( 'Follow our step-by-step guide here in case you need any help setting up the Linkedin social login.', 'loginpress-pro' ),
						esc_url( 'https://loginpress.pro/doc/add-linkedin-social-login-loginpress/?utm_source=loginpress-pro&utm_medium=social-login-settings&utm_campaign=user-guide&utm_content=linkedin' ),
						__( 'How to Integrate LinkedIn Social Login on WordPress', 'loginpress-pro' )
					),
					'microsoft' => sprintf(
						'<p>%s</p><a href="%s" target="_blank">%s</a>',
						__( 'Follow our step-by-step guide here in case you need any help setting up the microsoft social login.', 'loginpress-pro' ),
						esc_url( 'https://loginpress.pro/doc/add-microsoft-social-login-on-wordpress/?utm_source=loginpress-pro&utm_medium=social-login-settings&utm_campaign=user-guide&utm_content=microsoft' ),
						__( 'Steps For Setting Up Microsoft Social Login on WordPress with LoginPress', 'loginpress-pro' )
					),
					'apple'     => sprintf(
						'<p>%s</p><a href="%s" target="_blank">%s</a>',
						__( 'Follow our step-by-step guide here in case you need any help setting up the Apple social login.', 'loginpress-pro' ),
						esc_url( 'https://loginpress.pro/doc/add-apple-social-login-on-wordpress/?utm_source=loginpress-pro&utm_medium=social-login-settings&utm_campaign=user-guide&utm_content=apple' ),
						__( 'How to Integrate Apple Social Login on WordPress', 'loginpress-pro' )
					),
					'discord'   => sprintf(
						'<p>%s</p><a href="%s" target="_blank">%s</a>',
						__( 'Follow our step-by-step guide here in case you need any help setting up the Discord social login.', 'loginpress-pro' ),
						esc_url( 'https://loginpress.pro/doc/add-discord-social-login-on-wordpress/?utm_source=loginpress-pro&utm_medium=social-login-settings&utm_campaign=user-guide&utm_content=discord' ),
						__( 'How to Integrate Discord Social Login on WordPress', 'loginpress-pro' )
					),
					'wordpress' => sprintf(
						'<p>%s</p><a href="%s" target="_blank">%s</a>',
						__( 'Follow our step-by-step guide here in case you need any help setting up the WordPress social login.', 'loginpress-pro' ),
						esc_url( 'https://loginpress.pro/doc/add-wordpress-social-login-with-loginpress/?utm_source=loginpress-pro&utm_medium=social-login-settings&utm_campaign=user-guide&utm_content=wordpress' ),
						__( 'How to Integrate WordPress.com Social Login using LoginPress', 'loginpress-pro' )
					),
					'github'    => sprintf(
						'<p>%s</p><a href="%s" target="_blank">%s</a>',
						__( 'Follow our step-by-step guide here in case you need any help setting up the Github social login.', 'loginpress-pro' ),
						esc_url( 'https://loginpress.pro/doc/add-github-social-login-on-wordpress/?utm_source=loginpress-pro&utm_medium=social-login-settings&utm_campaign=user-guide&utm_content=github' ),
						__( 'How to Integrate GitHub Social Login on WordPress', 'loginpress-pro' )
					),
					'amazon'    => sprintf(
						'<p>%s</p><a href="%s" target="_blank">%s</a>',
						__( 'Follow our step-by-step guide here in case you need any help setting up the Amazon social login.', 'loginpress-pro' ),
						esc_url( 'https://loginpress.pro/doc/add-amazon-social-login-on-wordpress/?utm_source=loginpress-pro&utm_medium=social-login-settings&utm_campaign=user-guide&utm_content=amazon' ),
						__( 'How to Integrate Amazon Social Login on WordPress', 'loginpress-pro' )
					),
					'pinterest' => sprintf(
						'<p>%s</p><a href="%s" target="_blank">%s</a>',
						__( 'Follow our step-by-step guide here in case you need any help setting up the Pinterest social login.', 'loginpress-pro' ),
						esc_url( 'https://loginpress.pro/doc/add-pinterest-social-login-on-wordpress/?utm_source=loginpress-pro&utm_medium=social-login-settings&utm_campaign=user-guide&utm_content=pinterest' ),
						__( 'How to Integrate Pinterest Social Login on WordPress', 'loginpress-pro' )
					),
					'disqus'    => sprintf(
						'<p>%s</p><a href="%s" target="_blank">%s</a>',
						__( 'Follow our step-by-step guide here in case you need any help setting up the Disqus social login.', 'loginpress-pro' ),
						esc_url( 'https://loginpress.pro/doc/add-disqus-social-login-on-wordpress/?utm_source=loginpress-pro&utm_medium=social-login-settings&utm_campaign=user-guide&utm_content=disqus' ),
						__( 'How to Integrate Disqus Social Login on WordPress', 'loginpress-pro' )
					),
					'reddit'    => sprintf(
						'<p>%s</p><a href="%s" target="_blank">%s</a>',
						__( 'Follow our step-by-step guide here in case you need any help setting up the Reddit social login.', 'loginpress-pro' ),
						esc_url( 'https://loginpress.pro/doc/add-reddit-social-login-on-wordpress/?utm_source=loginpress-pro&utm_medium=social-login-settings&utm_campaign=user-guide&utm_content=reddit' ),
						__( 'How to Integrate Reddit Social Login on WordPress', 'loginpress-pro' )
					),
					'spotify'   => sprintf(
						'<p>%s</p><a href="%s" target="_blank">%s</a>',
						__( 'Follow our step-by-step guide here in case you need any help setting up the Spotify social login.', 'loginpress-pro' ),
						esc_url( 'https://loginpress.pro/doc/add-spotify-social-login-on-wordpress/?utm_source=loginpress-pro&utm_medium=social-login-settings&utm_campaign=user-guide&utm_content=spotify' ),
						__( 'How to Integrate Spotify Social Login on WordPress', 'loginpress-pro' )
					),
					'twitch'    => sprintf(
						'<p>%s</p><a href="%s" target="_blank">%s</a>',
						__( 'Follow our step-by-step guide here in case you need any help setting up the Twitch social login.', 'loginpress-pro' ),
						esc_url( 'https://loginpress.pro/doc/add-twitch-social-login-on-wordpress/?utm_source=loginpress-pro&utm_medium=social-login-settings&utm_campaign=user-guide&utm_content=twitch' ),
						__( 'How to Integrate Twitch Social Login on WordPress', 'loginpress-pro' )
					),
				);

				// Localize the script to pass data to JavaScript.
				wp_localize_script(
					'loginpress-all-addons',
					'loginpressProviderData',
					array(
						'descriptions'            => $provider_descriptions,
						'twitterclientID'         => __( 'Twitter Client ID', 'loginpress-pro' ),
						'twitterclientSecret'     => __( 'Twitter Client Secret', 'loginpress-pro' ),
						'twitterclientIDDesc'     => __( 'Enter your Twitter OAuth 2.0 Client ID.', 'loginpress-pro' ),
						'twitterclientSecretDesc' => __( 'Enter your Twitter OAuth 2.0 Client Secret.', 'loginpress-pro' ),
						'twitterapiKey'           => __( 'Twitter API Key', 'loginpress-pro' ),
						'twitterapiSecret'        => __( 'Twitter API Secret Key', 'loginpress-pro' ),
						'twitterapiKeyDesc'       => __( 'Enter Your Consumer API key.', 'loginpress-pro' ),
						'twitterapiSecretDesc'    => __( 'Enter Your Consumer API secret key.', 'loginpress-pro' ),
						'lookingproviderheading'  => __( 'Looking for Another Provider?', 'loginpress-pro' ),
						'lookingprovidercontent'  => __( 'Need a provider not listed here?', 'loginpress-pro' ),
						'lookingproviderbr'       => __( 'Let us know!', 'loginpress-pro' ),
						'notVerified'             => __( 'Not Verified', 'loginpress-pro' ),
						'enabled'                 => __( 'Enabled', 'loginpress-pro' ),
						'disabled'                => __( 'Disabled', 'loginpress-pro' ),
						'yetToVerify'             => __( 'Yet to Verify', 'loginpress-pro' ),
						'HelpCenter'              => __( 'Help Center', 'loginpress-pro' ),
						'ChooseProvider'          => __( 'Choose another provider', 'loginpress-pro' ),
						'ContactUs'               => __( 'Contact Us', 'loginpress-pro' ),
						'configure'               => __( 'Configure', 'loginpress-pro' ),
					)
				);

				// Get the absolute path to your main plugin file.
				$plugin_file                    = WP_PLUGIN_DIR . '/loginpress-pro/loginpress-pro.php';
				$loginpress_social_dir_url      = plugins_url( 'addons/social-login/', $plugin_file );
				$loginpress_integration_dir_url = plugins_url( 'integrations/', $plugin_file );

				wp_localize_script(
					'loginpress-all-addons',
					'loginPressGlobal',
					array(
						'socialDirPath'      => trailingslashit( $loginpress_social_dir_url ),
						'integrationDirPath' => trailingslashit( $loginpress_integration_dir_url ),
					)
				);
				// Get the database option value.
				$social_login_option = get_option( 'loginpress_social_logins' );
				// Pass the value to JavaScript.
				wp_localize_script(
					'loginpress-all-addons',
					'loginpressData', // Object name in JS.
					array(
						'socialLoginOption' => $social_login_option, // Add your option here.
					)
				);

				wp_localize_script(
					'loginpress-all-addons',
					'loginpress_social_ajax',
					array(
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
						'nonce'   => wp_create_nonce( 'loginpress_ajax_nonce' ),
					)
				);

				// Auto-login.
				wp_localize_script(
					'loginpress-all-addons',
					'loginpress_autologin',
					array(
						'loginpress_autologin_nonce' => wp_create_nonce( 'loginpress-user-autologin-nonce' ),
						'loginpress_autologin_popup' => wp_create_nonce( 'loginpress-autologin-popup-nonce' ),
						'translate'                  => array(
							_x( 'Enabled', 'Enable from the burger box selection of Autologin', 'loginpress-pro' ),
							_x( 'Disabled', 'Disabled from the burger box selection Autologin', 'loginpress-pro' ),
							_x( 'Lifetime', 'Lifetime from the burger box selection Autologin', 'loginpress-pro' ),
							_x( 'New Link Created', 'New Link Created LoginPress Autologin', 'loginpress-pro' ),
							_x( 'Day', 'Remaining time partial string in Autologin', 'loginpress-pro' ),
							_x( 'Days', 'Remaining time partial string in Autologin', 'loginpress-pro' ),
							_x( 'Left', 'Remaining time partial string in Autologin', 'loginpress-pro' ),
							_x( 'Last', 'Remaining time partial string in Autologin', 'loginpress-pro' ),
							/* Translators: %1$s The paragraph tag for the autologin search bar. */
							sprintf( _x( '%1$sSearch user\'s data from below the list%2$s', 'Search Label on Data tables', 'loginpress-pro' ), '<p class="description">', '</p>' ),
							_x( 'Enter keyword', 'The search keyword for the autologin users.', 'loginpress-pro' ),
							_x( 'Unlimited Clicks', 'For the unlimited clicks of the autologin users text.', 'loginpress-pro' ),
							_x( ' Clicks Used', 'For the lefted clicks of the autologin users text.', 'loginpress-pro' ),

						),
					)
				);

				// Redirect.
				wp_localize_script(
					'loginpress-all-addons',
					'loginpress_redirect',
					array(
						'user_nonce' => wp_create_nonce( 'loginpress-user-redirects-nonce' ),
						'role_nonce' => wp_create_nonce( 'loginpress-role-redirects-nonce' ),
						'translate'  => array(
							_x( 'Search Username', 'The label Text of Login Redirect addon Username search field', 'loginpress-pro' ),
							_x( 'Search Username For Whom To Apply Redirects', 'LoginPress Redirects Description text for Specific Username tab\'s search', 'loginpress-pro' ),
							_x( 'Search Roles', 'The label Text of Login Redirect addon Roles search field', 'loginpress-pro' ),
							_x( 'Search Role For Whom To Apply Redirects', 'LoginPress Redirects Description text for Specific Roles tab\'s search', 'loginpress-pro' ),
							// translators: Search data.
							sprintf( _x( '%1$sSearch user\'s data from below the list%2$s', 'Search Label on Data tables', 'loginpress-pro' ), '<p class="description">', '</p>' ),
							_x( 'Enter keyword', 'The search keyword for the autologin users.', 'loginpress-pro' ),
						),
					)
				);

				// Hide login.
				wp_localize_script(
					'loginpress-all-addons',
					'loginpress_hidelogin_local',
					array(
						'admin_url' => admin_url( 'admin.php?page=loginpress-settings' ),
						'security'  => wp_create_nonce( 'loginpress-reset-login-nonce' ),
					)
				);

				// LLLA.
				wp_localize_script(
					'loginpress-all-addons',
					'loginpress_llla',
					array(
						'manual_ip_cta' => wp_create_nonce( 'ip_add_remove' ),
						'user_nonce'    => wp_create_nonce( 'loginpress-user-llla-nonce' ),
						'bulk_nonce'    => wp_create_nonce( 'loginpress-llla-bulk-nonce' ),
						'csv_nonce'     => wp_create_nonce( 'loginpress-llla-csv-nonce' ),
						'spinner_url'   => LOGINPRESS_DIR_URL . '/img/loginpress-sniper.gif',
						'img_url'       => LOGINPRESS_PRO_ADDONS_DIR . '/limit-login-attempts/assets/img/llla_confirm.svg',
						'translate'     => array(
							_x( 'Please select at least one item to perform this action on.', 'LLLA bulk action when no action is selected before submission', 'loginpress-pro' ),
							_x( 'Clear', 'The Clear button text from LoginPress Limit Login Attempt\'s Whitelist and Blacklist tabs, LLLA', 'loginpress-pro' ),
							_x( 'This IP', 'This String is a partial when an IP is whitelisted or blacklisted, LLLA', 'loginpress-pro' ),
							_x( 'is Whitelisted', 'This String is a partial when an IP is manually whitelisted, LLLA', 'loginpress-pro' ),
							_x( 'is Blacklisted', 'This String is a partial when an IP is manually blacklisted, LLLA', 'loginpress-pro' ),
							_x( ' Your IP format is not correct ', 'The IP not valid error message when Manual IP is given false, LLLA', 'loginpress-pro' ),
							_x( ' Please enter an IP address ', 'The IP is empty, LLLA', 'loginpress-pro' ),
							_x( ' You entered a reserved IP ', 'Entered Reserved IP, LLLA', 'loginpress-pro' ),
							_x( ' No Whitelist IP found ', 'Remove all IPs from whitelist, LLLA', 'loginpress-pro' ),
							_x( ' No Blacklist IP found ', 'Remove all IPs from blacklist LLLA', 'loginpress-pro' ),
							_x( ' No IP found ', 'Remove all IPs from attempts log, LLLA', 'loginpress-pro' ),
							_x( 'Download CSV', 'The button text to Download CSV file, LLLA', 'loginpress-pro' ),
						),
					)
				);

			}
		}
	}
}
