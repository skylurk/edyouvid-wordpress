<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Addon Name: LoginPress - Social Login
 * Description: This is a premium add-on of LoginPress WordPress plugin by <a href="https://wpbrigade.com/">WPBrigade</a> which allows you to login using social media accounts    like Facebook, Twitter and Google/G+ etc
 *
 * @package loginpress
 * @category Core
 * @author WPBrigade
 * @since 5.0.0
 * @version 6.1.0
 */

if ( ! class_exists( 'LoginPress_Social' ) ) :
	include_once __DIR__ . '/classes/traits/settings.php';
	include_once __DIR__ . '/classes/traits/rest.php';
	include_once __DIR__ . '/classes/traits/frontend.php';
	/**
	 * LoginPress_Social
	 *
	 * @since 1.0.0
	 * @version 6.1.0
	 */
	final class LoginPress_Social {
		use LoginPress_Social_Login_Settings_Trait;
		use LoginPress_Social_Login_Rest_Trait;
		use LoginPress_Social_Login_Frontend_Trait;

		/**
		 * Is short code used.
		 *
		 * @since 1.0.0
		 * @var bool Shortcode usage flag.
		 */
		private $is_shortcode = false;

		/**
		 * The plugin instance.
		 *
		 * @since 1.0.0
		 * @var object Plugin instance object.
		 */
		protected static $instance = null;

		/**
		 * Table name.
		 *
		 * @since 1.0.0
		 * @var string Database table name.
		 */
		protected $table_name;

		/**
		 * Class constructor.
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function __construct() {

			if ( LoginPress_Pro::addon_wrapper( 'social-login' ) ) {
				$this->settings = get_option( 'loginpress_social_logins' );
				global $wpdb;
				$this->table_name = $wpdb->prefix . 'loginpress_social_login_details';
				$this->define_constants();
				$this->hooks();
			}
		}

		/**
		 * The settings array.
		 *
		 * @since 1.0.0
		 * @var array Social login settings array.
		 */
		public $settings;

		/**
		 * Define LoginPress Constants.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		private function define_constants() {

			LoginPress_Pro_Init::define( 'LOGINPRESS_SOCIAL_DIR_PATH', plugin_dir_path( __FILE__ ) );
			LoginPress_Pro_Init::define( 'LOGINPRESS_SOCIAL_DIR_URL', plugin_dir_url( __FILE__ ) );
		}

		/**
		 * Hook into actions and filters.
		 *
		 * @since 3.0.0
		 * @version 6.1.0
		 */
		private function hooks() {
			add_action( 'rest_api_init', array( $this, 'lp_sociallogin_register_routes' ) );

			$enable   = isset( $this->settings['enable_social_login_links'] ) ? $this->settings['enable_social_login_links'] : array();
			$login    = isset( $enable['login'] ) && $enable['login'] ? 'login' : '';
			$register = isset( $enable['register'] ) && $enable['register'] ? 'register' : '';
			$comment  = isset( $enable['comment'] ) && $enable['comment'] ? 'comment' : '';

			if ( 'login' === $login ) {
				add_action( 'login_form', array( $this, 'loginpress_social_login' ) );
			}
			if ( 'register' === $register ) {
				add_action( 'register_form', array( $this, 'loginpress_social_login' ) );
			}

			if ( 'comment' === $comment ) {
				add_action( 'comment_form_must_log_in_after', array( $this, 'loginpress_social_login' ) );
			}

			add_action( 'init', array( $this, 'loginpress_register_social_login_block' ) );
			add_action( 'admin_init', array( $this, 'loginpress_update_process_complete' ), 10, 2 );
			add_action( 'init', array( $this, 'session_init' ) );
			add_filter( 'loginpress_settings_tab', array( $this, 'settings_tab' ), 15 );
			add_filter( 'loginpress_settings_fields', array( $this, 'settings_field' ), 10 );
			add_action( 'delete_user', array( $this, 'delete_user_row' ) );
			add_filter( 'login_message', array( $this, 'loginpress_social_login_register_error' ), 100, 1 );
			add_action( 'admin_enqueue_scripts', array( $this, 'loginpress_social_login_admin_action_scripts' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'load_login_assets' ), 1 );
			add_action( 'login_enqueue_scripts', array( $this, 'load_login_assets' ), 1 );
			add_action( 'login_footer', array( $this, 'login_page_custom_footer' ) );
			add_filter( 'get_avatar', array( $this, 'insert_avatar' ), 1, 5 );
			// Register AJAX actions.
			add_action( 'wp_ajax_loginpress_update_verification', array( $this, 'loginpress_lpsl_settings_verification' ) );
			add_action( 'wp_ajax_loginpress_save_social_login_order', array( $this, 'loginpress_save_social_login_order' ) );

			add_shortcode( 'loginpress_social_login', array( $this, 'loginpress_social_login_shortcode' ) );
			add_filter( 'cron_schedules', array( $this, 'loginpress_add_weekly_cron_schedule' ) );
			register_activation_hook( __FILE__, array( $this, 'loginpress_create_jwt_cron' ) );
			register_deactivation_hook( __FILE__, array( $this, 'loginpress_clear_cron_job' ) );
			add_action( 'loginpress_check_jwt_token', array( $this, 'loginpress_check_jwt_token' ) );
		}

		/**
		 * LoginPress Social Login Block.
		 *
		 * @since 5.0.0
		 * @return void
		 */
		public function loginpress_register_social_login_block() {
			wp_register_script(
				'loginpress-block-social-login',
				plugins_url( '/blocks/block.js', __FILE__ ),
				array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-i18n', 'wp-components', 'wp-server-side-render' ),
				filemtime( plugin_dir_path( __FILE__ ) . '/blocks/block.js' ),
				true
			);

			// Get plugin option and parse the serialized string.
			$raw_option  = get_option( 'loginpress_social_logins' );
			$option_data = $raw_option;
			wp_localize_script(
				'loginpress-block-social-login',
				'LoginPressSocialLogins',
				array(
					'statuses' => array(
						'apple'     => $option_data['apple_status'] ?? '',
						'google'    => $option_data['gplus_status'] ?? '',
						'facebook'  => $option_data['facebook_status'] ?? '',
						'twitter'   => $option_data['twitter_status'] ?? '',
						'linkedin'  => $option_data['linkedin_status'] ?? '',
						'microsoft' => $option_data['microsoft_status'] ?? '',
						'github'    => $option_data['github_status'] ?? '',
						'discord'   => $option_data['discord_status'] ?? '',
						'wordpress' => $option_data['wordpress_status'] ?? '',
						'amazon'    => $option_data['amazon_status'] ?? '',
						'pinterest' => $option_data['pinterest_status'] ?? '',
						'spotify'   => $option_data['spotify_status'] ?? '',
						'twitch'    => $option_data['twitch_status'] ?? '',
						'reddit'    => $option_data['reddit_status'] ?? '',
						'disqus'    => $option_data['disqus_status'] ?? '',
					),
				)
			);

			register_block_type(
				plugin_dir_path( __FILE__ ) . '/blocks/block.json',
				array(
					'render_callback' => array( $this, 'loginpress_render_social_login_block' ),
					'script'          => 'loginpress-block-social-login',
				)
			);
		}


		/**
		 * Main Instance.
		 *
		 * @since 3.0.0
		 * @static
		 * @see loginPress_social_loader()
		 * @return LoginPress_Social Main instance
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Delete user row from the table.
		 *
		 * @since 3.0.0
		 * @param int $user_id The user ID.
		 * @return void
		 */
		public function delete_user_row( $user_id ) {
			global $wpdb;
			$wpdb->query( $wpdb->prepare( "DELETE FROM `{$this->table_name}` WHERE `user_id` = %d", $user_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}


		/**
		 * Plugin activation for check multi site activation.
		 *
		 * @since 1.0.0
		 * @param array $network_wide all networks.
		 * @return void
		 */
		public static function loginpress_social_activation( $network_wide ) {
			if ( function_exists( 'is_multisite' ) && is_multisite() && $network_wide ) {
				global $wpdb;
				// Get this so we can switch back to it later.
				$current_blog = $wpdb->blogid;
				// Get all blogs in the network and activate plugin on each one.
				$blog_ids = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM %s", $wpdb->blogs ) ); // @codingStandardsIgnoreLine.
				foreach ( $blog_ids as $blog_id ) {
					switch_to_blog( $blog_id );
					self::loginpress_social_create_table();
				}
				switch_to_blog( $current_blog );
				return;
			} else {
				self::loginpress_social_create_table();
			}
		}

		/**
		 * Create DB table on plugin activation.
		 *
		 * @since 1.0.5
		 * @version 6.1.0
		 * @return void
		 */
		public static function loginpress_social_create_table() {

			global $wpdb;
			// Create user details table.
			$table_name = "{$wpdb->prefix}loginpress_social_login_details";

			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
				id int(11) NOT NULL AUTO_INCREMENT,
				user_id int(11) NOT NULL,
				provider_name varchar(50) NOT NULL,
				identifier varchar(255) NOT NULL,
				sha_verifier varchar(255) NOT NULL,
				email varchar(255) NOT NULL,
				email_verified varchar(255) NOT NULL,
				first_name varchar(150) NOT NULL,
				last_name varchar(150) NOT NULL,
				profile_url varchar(255) NOT NULL,
				website_url varchar(255) NOT NULL,
				photo_url varchar(255) NOT NULL,
				display_name varchar(150) NOT NULL,
				description varchar(255) NOT NULL,
				gender varchar(10) NOT NULL,
				language varchar(20) NOT NULL,
				age varchar(10) NOT NULL,
				birthday int(11) NOT NULL,
				birthmonth int(11) NOT NULL,
				birthyear int(11) NOT NULL,
				phone varchar(75) NOT NULL,
				address varchar(255) NOT NULL,
				country varchar(75) NOT NULL,
				region varchar(50) NOT NULL,
				city varchar(50) NOT NULL,
				zip varchar(25) NOT NULL,
				UNIQUE KEY id (id),
				KEY user_id (user_id),
				KEY provider_name (provider_name)
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}

		/**
		 * Load assets on login screen.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public function load_login_assets() {

			wp_enqueue_style( 'loginpress-social-login', plugins_url( 'assets/css/login.css', __FILE__ ), array(), LOGINPRESS_PRO_VERSION );
		}

		/**
		 * Update the verification status of each provider on plugin update.
		 *
		 * @return void
		 * @since 1.0.0
		 * @version 6.1.2
		 */
		public function loginpress_update_process_complete() {
			// Fetch the serialized loginpress_social_login option.

			$social_login_data = $this->settings;
			if ( empty( $social_login_data ) ) {
				return; // No data found, nothing to process.
			}
			if ( empty( $social_login_data['apple_status'] ) && empty( $social_login_data['gplus_status'] ) && empty( $social_login_data['facebook_status'] ) && empty( $social_login_data['twitter_status'] ) && empty( $social_login_data['linkedin_status'] ) && empty( $social_login_data['microsoft_status'] ) && empty( $social_login_data['github_status'] ) && empty( $social_login_data['discord_status'] ) && empty( $social_login_data['wordpress_status'] ) ) {
				// Unserialize the data.
				$enabled_providers = maybe_unserialize( $social_login_data );

				if ( ! is_array( $enabled_providers ) ) {
					return; // Data is not in the expected format.
				}

				// Fetch all entries from the social login details table in one query.
				global $wpdb;
				$social_login_details = $wpdb->get_results( "SELECT provider_name FROM `$this->table_name`", ARRAY_A ); //phpcs:ignore

				// Correct "glpus" to "gplus" if found in the results.
				if ( is_array( $social_login_details ) ) {
					foreach ( $social_login_details as &$detail ) {
						if ( isset( $detail['provider_name'] ) && 'glpus' === $detail['provider_name'] ) {
							$detail['provider_name'] = 'gplus';
						}
					}
				}
				// Convert the result to a simple array of provider names for quick lookup.
				$existing_providers = array_column( $social_login_details, 'provider_name' );
				// Iterate over enabled providers to check their statuses.
				foreach ( $enabled_providers as $provider => $settings ) {

					$social_login_providers = array( 'facebook', 'twitter', 'gplus', 'linkedin', 'microsoft', 'github', 'discord', 'wordpress', 'apple' );
					if ( in_array( $provider, $social_login_providers, true ) ) {
						// Check if the provider is enabled and has necessary fields populated.
						if ( ! empty( $settings ) && 'on' === $settings ) {
							$required_keys     = $this->loginpress_get_required_keys_for_provider( $provider );
							$all_fields_filled = true;

							// Check if all required fields are filled.
							foreach ( $required_keys as $key ) {
								if ( empty( $enabled_providers[ "{$provider}_{$key}" ] ) ) {
									$all_fields_filled = false;
									break;
								}
							}

							// Update the provider status.
							if ( $all_fields_filled ) {
								if ( in_array( $provider, $existing_providers, true ) ) {
									$enabled_providers[ "{$provider}_status" ] = __( 'yet to verify', 'loginpress-pro' );
								} else {
									$enabled_providers[ "{$provider}_status" ] = __( 'not verified', 'loginpress-pro' );
								}
							} else {
								$enabled_providers[ "{$provider}_status" ] = __( 'not verified', 'loginpress-pro' );
							}
						}
					}
				}
				// Ensure the data being stored is always an array.
				if ( ! is_array( $enabled_providers ) ) {
					$enabled_providers = array();
				}
				// Serialize and update the modified data.
				update_option( 'loginpress_social_logins', $enabled_providers );
			}

			// Compatibility with older versions for provider order. updating the DB array with addition of new social providers.
			$provider_order = isset( $this->settings['provider_order'] ) ? $this->settings['provider_order'] : '';

			if ( is_array( $provider_order ) ) {
				$lp_social_login_array = $provider_order;
			} elseif ( ! empty( $provider_order ) ) {
				$provider_order        = trim( $provider_order, '[]"' );
				$lp_social_login_array = explode( ',', $provider_order );
				$lp_social_login_array = array_map(
					function ( $value ) {
						return trim( $value, ' "' );
					},
					$lp_social_login_array
				);
			} else {
				$lp_social_login_array = array();
			}
			if ( isset( $this->settings['provider_order'] ) && ! empty( $lp_social_login_array )
			&& ! in_array( 'amazon', $lp_social_login_array, true ) && version_compare( LOGINPRESS_PRO_VERSION, '4.0.1', '>=' ) ) {
				// Additional providers.
				$additional_providers = array( 'disqus', 'reddit', 'amazon', 'spotify', 'pinterest', 'twitch' );
				$missing_providers    = array_diff( $additional_providers, $lp_social_login_array );

				// If there are missing providers, add them to the array.
				if ( ! empty( $missing_providers ) ) {
					$lp_social_login_array = array_merge( $lp_social_login_array, $missing_providers );
					$lpsl_settings         = get_option( 'loginpress_social_logins' );
					// Update the option with the new provider order in the string format.
					$lpsl_settings['provider_order'] = $lp_social_login_array;
					update_option( 'loginpress_social_logins', $lpsl_settings );
				}
			}
		}

		/**
		 * Get field description for display.
		 *
		 * @param array $args Settings field args.
		 * @return string Field description HTML.
		 * @since 4.0.0
		 */
		public function lpsl_get_field_description( $args ) {
			if ( ! empty( $args['desc'] ) ) {
				$desc = sprintf( '<p class="description">%s</p>', $args['desc'] );
			} else {
				$desc = '';
			}

			return $desc;
		}

		/**
		 * Get the value of a settings field.
		 *
		 * @param string $option Settings field name.
		 * @param string $section The section name this field belongs to.
		 * @param string $default_value Default text if it's not found.
		 * @return string Field value or default.
		 * @since 4.0.0
		 */
		public function lpsl_get_option( $option, $section, $default_value = '' ) {

			$options = get_option( $section );

			if ( isset( $options[ $option ] ) ) {
				return $options[ $option ];
			}

			return $default_value;
		}

		/**
		 * Add a 'weekly' schedule to WP Cron.
		 *
		 * @param array $schedules WP Cron schedules.
		 * @return array Modified schedules array.
		 * @since 4.0.0
		 * @version 6.1.0
		 */
		public function loginpress_add_weekly_cron_schedule( $schedules ) {
			if ( ! isset( $schedules['weekly'] ) ) {
				$schedules['weekly'] = array(
					'interval' => WEEK_IN_SECONDS,
					'display'  => __( 'Once Weekly', 'loginpress-pro' ),
				);
			}
			return $schedules;
		}

		/**
		 * Schedules a weekly event to check the JWT token if not already scheduled.
		 *
		 * This function ensures that an event named 'loginpress_check_jwt_token'
		 * is scheduled to run on a weekly basis using WordPress's Cron API.
		 *
		 * @return void
		 * @since 4.0.0
		 * @version 6.1.0
		 */
		public function loginpress_create_jwt_cron() {
			if ( ! wp_next_scheduled( 'loginpress_check_jwt_token' ) ) {
				wp_schedule_event( time(), 'weekly', 'loginpress_check_jwt_token' );
			}
		}

		/**
		 * Clears the scheduled weekly event when the plugin is deactivated.
		 *
		 * @return void
		 * @since 4.0.0
		 * @version 6.1.0
		 */
		public function loginpress_clear_cron_job() {
			wp_clear_scheduled_hook( 'loginpress_check_jwt_token' );
		}

		/**
		 * Checks the expiration of the Apple JWT token and regenerates it if
		 * it will expire in less than 7 days.
		 *
		 * This function is called by a scheduled weekly event to ensure the
		 * token remains valid.
		 *
		 * @return void
		 * @since 4.0.0
		 * @version 6.1.0
		 */
		public function loginpress_check_jwt_token() {
			// Fetch the social logins option.
			$social_logins = get_option( 'loginpress_social_logins' );

			if ( isset( $social_logins['apple_secret'] ) ) {
				// Decode the JWT to check expiration.
				$jwt_parts = explode( '.', $social_logins['apple_secret'] );
				if ( ( is_array( $jwt_parts ) || is_object( $jwt_parts ) ) && count( $jwt_parts ) === 3 ) {
					$payload = json_decode( base64_decode( $jwt_parts[1] ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

					if ( isset( $payload['exp'] ) ) {
						$current_time = time();
						$expires_in   = $payload['exp'] - $current_time;
						// If the token will expire in less than 7 days, regenerate.
						if ( $expires_in < WEEK_IN_SECONDS ) {
							$apple_instance = new LoginPress_Apple();
							$apple_instance->generate_token( $social_logins );
						}
					}
				}
			}
		}

		/**
		 * Ajax callback for updating verification status.
		 *
		 * @return void
		 * @since 4.0.0
		 */
		public function loginpress_lpsl_settings_verification() {
			// Verify nonce for security.
			check_ajax_referer( 'loginpress_ajax_nonce', 'security' );

			if ( ! isset( $_POST['provider'] ) ) {
				wp_send_json_error( array( 'message' => __( 'Provider not specified', 'loginpress-pro' ) ) );
			}

			$provider = sanitize_text_field( wp_unslash( $_POST['provider'] ) );
			// Retrieve existing settings.
			$social_logins = get_option( 'loginpress_social_logins', array() );

			// Ensure it's an array before updating.
			if ( ! is_array( $social_logins ) ) {
				$social_logins = array();
			}

			// Update the provider's status.
			$social_logins[ "{$provider}_status" ] = 'verified';

			// Save the updated array.
			update_option( 'loginpress_social_logins', $social_logins );
			/* Translators: verification for specific social provider. */
			wp_send_json_success( array( 'success' => sprintf( __( 'Verification updated for %s', 'loginpress-pro' ), $provider ) ) );
		}


		/**
		 * Ajax callback for updating providers order.
		 *
		 * @return void
		 * @since 4.0.0
		 */
		public function loginpress_save_social_login_order() {
			// Verify nonce for security.
			check_ajax_referer( 'loginpress_ajax_nonce', 'security' );

			if ( ! isset( $_POST['loginpress_provider_order'] ) || ! is_array( $_POST['loginpress_provider_order'] ) ) {
				wp_send_json_error( 'Invalid order data' );
			}

			$order = array_map( 'sanitize_text_field', wp_unslash( $_POST['loginpress_provider_order'] ) );

			// Retrieve the existing 'loginpress_social_logins' option.
			$social_logins = get_option( 'loginpress_social_logins', array() );

			// Ensure $social_logins is an array.
			if ( ! is_array( $social_logins ) ) {
				$social_logins = array();
			}

			// Update only the order in the 'loginpress_social_logins' option.
			$social_logins['provider_order'] = $order;

			// Save the updated option back to the database.
			update_option( 'loginpress_social_logins', $social_logins );

			// Respond with success.
			wp_send_json_success( 'Order saved successfully' );
		}
	}

endif;
// phpcs:disable

if ( ! function_exists( 'loginpress_social_loader' ) ) {

	/**
	 * Returns the main instance of WP to prevent the need to use globals.
	 *
	 * @return LoginPress_Social Instance of Social Login class.
	 * @since 3.0.0
	 */
	function loginpress_social_loader() {
		return LoginPress_Social::instance();
	}
}

add_action( 'plugins_loaded', 'loginpress_sl_instance', 25 );

/**
 * Check if LoginPress Pro is install and active.
 *
 * @return void
 * @since 3.0.0
 */
function loginpress_sl_instance() {

	// Call the function.
	loginpress_social_loader();
}
// phpcs:enable
