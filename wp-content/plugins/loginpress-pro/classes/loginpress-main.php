<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * LoginPress Pro main file.
 *
 * @package LoginPress Pro
 */

require_once LOGINPRESS_PRO_DIR_PATH . 'classes/traits/main-admin.php';
require_once LOGINPRESS_PRO_DIR_PATH . 'classes/traits/main-license.php';
/**
 * LoginPress Pro main class
 */
class LoginPress_Pro {
	use LoginPress_Main_Admin_Trait;
	use LoginPress_Main_License_Trait;

	/**
	 * The name and ID of the download on WPBrigade.com for this plugin.
	 */
	const LOGINPRESS_PRODUCT_NAME = 'LoginPress Pro';
	const LOGINPRESS_PRODUCT_ID   = 1837;

	/**
	 * The URL of the our store.
	 */
	const LOGINPRESS_SITE_URL = 'https://wpbrigade.com/';

	/**
	 * The WP options registration data key.
	 */
	const REGISTRATION_DATA_OPTION_KEY = 'loginpress_pro_registration_data';

	/**
	 * Function constructor.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->hooks();
		$this->includes();
		$this->loginpress_pro_hook();
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @return void
	 * @since 1.0.0
	 * @version 6.1.0
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'textdomain' ) );
		add_filter( 'loginpress_pro_add_template', array( $this, 'customizer_template_array' ), 10, 1 );
		add_action( 'loginpress_add_pro_theme', array( $this, 'add_pro_theme' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'loginpress_admin_scripts' ), 10, 1 );
		add_action( 'login_head', array( $this, 'login_page_custom_head' ) );
		add_action( 'admin_init', array( $this, 'init_plugin_updater' ), 0 );
		add_action( 'admin_init', array( $this, 'manage_license' ) );
		add_action( 'wp', array( $this, 'loginpress_member_only_site' ) );
		add_action( 'login_footer', array( $this, 'loginpress_custom_footer' ), 11 );
		add_action( 'login_enqueue_scripts', array( $this, 'loginpress_enqueue_jquery' ), 1 );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'redirect_license_page' ) );
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'loginpress_pro_customizer_js' ) );
		add_action( 'wp_ajax_loginpress_pro_google_fonts', array( $this, 'loginpress_pro_google_fonts' ) );
		if ( version_compare( LOGINPRESS_VERSION, '3.0.0', '>=' ) ) {
			add_filter( 'loginpress_settings_tab', array( $this, 'loginpress_license_tab' ), 99, 1 );
		}
		add_action( 'rest_api_init', array( $this, 'lp_main_register_routes' ) );
	}

	/**
	 * LoginPress Customizer Scripts.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function loginpress_pro_customizer_js() {

		wp_enqueue_script( 'loginpress-pro-customize-control', plugins_url( '../assets/js/customizer.js', __FILE__ ), array( 'jquery', 'customize-preview' ), LOGINPRESS_PRO_VERSION, true );
		wp_localize_script(
			'loginpress-pro-customize-control',
			'LoginPressProCustomize',
			array(
				'font_nonce' => wp_create_nonce( 'loginpressprocustomize_nonce' ),
			)
		);
	}

	/**
	 * Manage the Login Head to handle the style on login page.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function login_page_custom_head() {

		// Include CSS File in head.
		include_once LOGINPRESS_PRO_DIR_PATH . 'assets/css/style-login.php';
	}

	/**
	 * Force user to login before viewing the site.
	 *
	 * @return void
	 * @since 2.0.13
	 * @version 6.1.0
	 */
	public function loginpress_member_only_site() {

		$exclude_forcelogin     = apply_filters( 'loginpress_exclude_forcelogin', false );
		$loginpress_setting     = get_option( 'loginpress_setting' );
		$force_login_ids        = apply_filters( 'loginpress_apply_forcelogin_only_on', false );
		$force_login_permission = isset( $loginpress_setting['force_login'] ) ? $loginpress_setting['force_login'] : 'off';

		$allow_bots = isset( $loginpress_setting['allow_bots'] ) ? sanitize_text_field( $loginpress_setting['allow_bots'] ) : 'off';
		$bot_names  = isset( $loginpress_setting['allowed_bots'] ) && is_array( $loginpress_setting['allowed_bots'] )
		? array_map( 'sanitize_text_field', $loginpress_setting['allowed_bots'] )
			: array();

		$default_bots = array( 'Googlebot', 'Bingbot', 'Slurp', 'DuckDuckBot', 'YandexBot', 'Baiduspider' );

		/**
		 * This filter will disable the "Force Login" for specific crawlers given by the user.
		 *
		 * @since 3.0.0
		 */
		$bot_names = apply_filters( 'loginpress_add_custom_crawlers', $bot_names, $default_bots );

		if ( 'on' === $force_login_permission ) {
			if ( 'on' === $allow_bots && function_exists( 'is_user_logged_in' ) && ! is_user_logged_in() && isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
				$user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
				foreach ( $bot_names as $bot ) {
					if ( false !== stripos( $user_agent, $bot ) ) {
						return; // Allow bot.
					}
				}
			}
		}

		/**
		 * This filter will disable the "Force Login" and will enable it only on certain posts/Pages.
		 *
		 * @since 3.0.0
		 */
		if ( 'on' === $force_login_permission && false !== $force_login_ids && function_exists( 'is_user_logged_in' ) && ! is_user_logged_in() ) {
			global $post;
			$post_id   = isset( $post->ID ) ? $post->ID : false;
			$post_slug = isset( $post->post_name ) ? $post->post_name : false;

			// if array is provided by user.
			if ( is_array( $force_login_ids ) ) {
				foreach ( $force_login_ids as $value ) {
					if ( $post_slug === $value || $post_id === $value ) {
						auth_redirect();
					}
				}
			} elseif ( $post_slug === $force_login_ids || $post_id === $force_login_ids ) {
				// if single value is provided by user.
				auth_redirect();
			}
			return;
		}

		/**
		 * Exclude 404 Page from Force Login.
		 *
		 * @version 6.1.0
		 */
		if ( is_404() ) {
			return;
		}

		if ( 'on' === $force_login_permission && function_exists( 'is_user_logged_in' ) && ! is_user_logged_in() ) {

			global $post;
			$post_id   = isset( $post->ID ) ? $post->ID : false;
			$post_slug = isset( $post->post_name ) ? $post->post_name : false;

			if ( $post_slug && false !== $exclude_forcelogin ) {

				// if array is provided by user.
				if ( is_array( $exclude_forcelogin ) ) {
					foreach ( $exclude_forcelogin as $value ) {
						if ( $post_slug === $value || $post_id === $value ) {
							return;
						}
					}
				} elseif ( $post_slug === $exclude_forcelogin || $post_id === $exclude_forcelogin ) {
					// if single value is provided by user.
					return;
				}
			}

			/**
			 * Hook to prevent force login.
			 *
			 * @since 2.4.0
			 */
			if ( apply_filters( 'loginpress_prevent_forcelogin', false ) ) {
				return;
			}

			if ( apply_filters( 'loginpress_forcelogin_noauth', false ) ) {
				wp_safe_redirect( wp_login_url() );
			} else {
				auth_redirect();
			}
		}
	}

	/**
	 * LoginPress_custom_footer is used to call the script in footer.
	 *
	 * @return void
	 * @since 2.3.0
	 */
	public function loginpress_custom_footer() {

		include LOGINPRESS_PRO_ROOT_PATH . '/assets/js/script-login.php';
	}

	/**
	 * Enqueue jQuery on login page.
	 *
	 * @return void
	 * @since 2.0.10
	 */
	public function loginpress_enqueue_jquery() {

		wp_enqueue_script( 'jquery', false, array(), LOGINPRESS_PRO_VERSION, false );
	}

	/**
	 * Load Languages.
	 *
	 * @return void
	 * @since 1.0.0
	 * @version 6.1.0
	 */
	public function textdomain() {

		load_plugin_textdomain( 'loginpress-pro', false, LOGINPRESS_PRO_PLUGIN_ROOT . '/languages/' );
	}

	/**
	 * Return array of loginpress_customization.
	 *
	 * @return array
	 * @since 2.0.6
	 */
	public function loginpress_customization_array() {

		$loginpress_key = get_option( 'loginpress_customization' );

		if ( is_array( $loginpress_key ) ) {
			return $loginpress_key;
		} else {
			return array();
		}
	}

	/**
	 * Call WordPress hooks if array_key_exists in $loginpress_key.
	 *
	 * @return void
	 * @since 2.0.3
	 */
	public function loginpress_pro_hook() {

		$loginpress_key = $this->loginpress_customization_array();

		if ( array_key_exists( 'reset_hint_text', $loginpress_key ) && ! empty( $loginpress_key['reset_hint_text'] ) ) {
			add_filter( 'password_hint', array( $this, 'loginpress_password_hint' ) );
		}
	}

	/**
	 * WordPress reset password hint text.
	 *
	 * @return string $reset_hint
	 * @since 2.0.3
	 */
	public function loginpress_password_hint() {

		$loginpress_key = $this->loginpress_customization_array();
		$reset_hint     = $loginpress_key['reset_hint_text'];

		return esc_js( $reset_hint );
	}

	/**
	 * Change reset password hint text.
	 *
	 * @param string $message The change rest message.
	 * @return HTML the message with a p tag.
	 * @since 2.0.3
	 */
	public function change_reset_message( $message ) {

		$loginpress_key = $this->loginpress_customization_array();

		if ( $loginpress_key ) {
			if ( isset( $_GET['action'] ) && 'rp' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( array_key_exists( 'reset_hint_message', $loginpress_key ) && ! empty( $loginpress_key['reset_hint_message'] ) ) {
					$message = $loginpress_key['reset_hint_message'];
					return ! empty( $message ) ? '<p class="message">' . wp_kses_post( $message ) . '</p>' : '';
				}
			}
		}
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 *
	 * @return void
	 * @since 1.0.0
	 * @version 6.1.0
	 */
	public function includes() {

		if ( is_admin() ) {
			include_once LOGINPRESS_PRO_ROOT_PATH . '/classes/loginpress-addon-updater.php';
			include_once LOGINPRESS_PRO_ROOT_PATH . '/classes/loginpress-ajax.php';
		}

		include_once LOGINPRESS_PRO_ROOT_PATH . '/classes/class-loginpress-captcha.php';
		new LoginPress_Captcha();

		include_once LOGINPRESS_PRO_ROOT_PATH . '/integrations/loginpress-integration.php';
		new LoginPress_Integration();

		include_once LOGINPRESS_PRO_ROOT_PATH . '/classes/loginpress-customize.php';
		new LoginPress_Pro_Entities();

		include_once LOGINPRESS_PRO_ROOT_PATH . '/classes/loginpress-manage-addons.php';
		new LoginPress_Manage_Addons();

		$loginpress_setting = get_option( 'loginpress_setting' );

		if ( isset( $loginpress_setting['enable_user_verification'] ) && 'on' === $loginpress_setting['enable_user_verification'] ) {
			include_once LOGINPRESS_PRO_ROOT_PATH . '/classes/loginpress-approve-user.php';
			new LoginPress_Approve_User();
		}
	}

	/**
	 * Customizer templates.
	 *
	 * @param array $loginpress_free_themes Custom LoginPress Themes.
	 * @return array[templates] $loginpress_pro_templates Custom LoginPress Themes.
	 * @since 1.0.0
	 */
	public function customizer_template_array( $loginpress_free_themes ) {

		$loginpress_pro_array  = array();
		$loginpress_theme_name = array(
			'',
			'',
			__( 'Company', 'loginpress-pro' ),
			__( 'Persona', 'loginpress-pro' ),
			__( 'Corporate', 'loginpress-pro' ),
			__( 'Corporate', 'loginpress-pro' ),
			__( 'Startup', 'loginpress-pro' ),
			__( 'Wedding', 'loginpress-pro' ),
			__( 'Wedding #2', 'loginpress-pro' ),
			__( 'Company', 'loginpress-pro' ),
			__( 'Bikers', 'loginpress-pro' ),
			__( 'Fitness', 'loginpress-pro' ),
			__( 'Shopping', 'loginpress-pro' ),
			__( 'Writers', 'loginpress-pro' ),
			__( 'Persona', 'loginpress-pro' ),
			__( 'Geek', 'loginpress-pro' ),
			__( 'Innovation', 'loginpress-pro' ),
			__( 'Photographers', 'loginpress-pro' ),
			__( 'Animated Wapo', 'loginpress-pro' ),
			__( 'Animated Wapo 2', 'loginpress-pro' ),
		);

		$_count = 2;
		while ( $_count <= 18 ) :

			$loginpress_pro_array[ "default{$_count}" ] = array(
				'img'       => plugins_url( "loginpress/img/bg{$_count}.jpg", LOGINPRESS_ROOT_PATH ),
				'thumbnail' => plugins_url( "loginpress/img/thumbnail/default-{$_count}.png", LOGINPRESS_ROOT_PATH ),
				'id'        => "default{$_count}",
				'name'      => $loginpress_theme_name[ $_count ],
			);
			++$_count;
		endwhile;

		$loginpress_pro_offer = array(
			'default19' => array(
				'img'       => plugins_url( 'loginpress/assets/img/bg17.jpg', LOGINPRESS_ROOT_PATH ),
				'thumbnail' => plugins_url( 'loginpress/img/thumbnail/custom-design.png', LOGINPRESS_ROOT_PATH ),
				'id'        => 'default19',
				'name'      => __( 'Custom Design', 'loginpress-pro' ),
				'link'      => 'yes',
			),
		);

		$loginpress_pro_themes    = array_merge( $loginpress_pro_array, $loginpress_pro_offer );
		$loginpress_pro_templates = array_merge( $loginpress_free_themes, $loginpress_pro_themes );
		return $loginpress_pro_templates;
	}

	/**
	 * Load the Pro Templates.
	 *
	 * @param string $selected_preset Selected preset.
	 * @return void
	 * @since 1.0.0
	 */
	public function add_pro_theme( $selected_preset ) {

		include_once apply_filters( 'loginpress_premium_theme', LOGINPRESS_PRO_THEME . $selected_preset . '.php' );
	}

	/**
	 * Initialize the plugin updater class.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function init_plugin_updater() {

		// Require the updater class, if not already present.
		if ( ! class_exists( 'LOGINPRESS_PRO_SL_Plugin_Updater' ) ) {
			include_once LOGINPRESS_PRO_ROOT_PATH . '/lib/LOGINPRESS_PRO_SL_Plugin_Updater.php';
		}

		// Retrieve our license key from the DB.
		$license_key = $this->get_registered_license_key();

		// Setup the updater.
		try {
			// Initialize the updater.
			$edd_updater = new LOGINPRESS_PRO_SL_Plugin_Updater(
				self::LOGINPRESS_SITE_URL,
				LOGINPRESS_PRO_UPGRADE_PATH,
				array(
					'version' => LOGINPRESS_PRO_VERSION,
					'license' => $license_key,
					'item_id' => self::LOGINPRESS_PRODUCT_ID,
					'author'  => 'captian',
					'beta'    => false,
					'timeout' => 15,
				)
			);
		} catch ( Exception $e ) {
			// Log error silently - Exception details not needed.
			unset( $e );
		}
	}

	/**
	 * LoginPress_license_menu_page_removing Remove LoginPress License page.
	 *
	 * @return void
	 * @since 2.1.1
	 */
	public function loginress_license_menu_page_removing() {

		remove_submenu_page( 'loginpress-settings', 'loginpress-license' );
	}

	/**
	 * Check LoginPress module compatibility.
	 *
	 * @param string $slug The addon slug.
	 * @return boolean
	 * @since 3.0.0
	 */
	public static function addon_wrapper( $slug ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		include LOGINPRESS_DIR_PATH . 'classes/class-loginpress-addons.php';

		if ( defined( 'LOGINPRESS_VERSION' ) && class_exists( 'LoginPress_Addons' ) ) {
			$obj_loginpress_addons = new LoginPress_Addons();
		}

		if ( defined( 'LOGINPRESS_VERSION' ) && version_compare( LOGINPRESS_VERSION, '3.0.5', '>=' ) ) {
				return true;
		}
	}
	/**
	 * Adding a tab for Integration at LoginPress Settings Page.
	 *
	 * @param array $loginpress_tabs Rest of the settings tabs of LoginPress.
	 * @return array $loginpress_pro_tabs Integration tab.
	 * @since 4.0.1
	 */
	public function loginpress_interation_tab( $loginpress_tabs ) {
		$integrations = array(
			array(
				'id'     => 'woocommerce',
				'name'   => __( 'WooCommerce', 'loginpress-pro' ),
				'desc'   => __( 'Quick, secure logins for your WooCommerce store.', 'loginpress-pro' ),
				'img'    => 'woocommerce.svg',
				'target' => '.enable_captcha_woo, .enable_social_woo',
			),
			array(
				'id'     => 'edd',
				'name'   => __( 'Easy Digital Downloads', 'loginpress-pro' ),
				'desc'   => __( 'Secure digital purchases with login enhancements.', 'loginpress-pro' ),
				'img'    => 'edd.svg',
				'target' => '.enable_captcha_edd, .enable_social_login_links_edd',
			),
			array(
				'id'     => 'buddypress',
				'name'   => __( 'BuddyPress', 'loginpress-pro' ),
				'desc'   => __( 'Boost community logins with social and captcha support.', 'loginpress-pro' ),
				'img'    => 'buddypress.svg',
				'target' => '.enable_captcha_bp, .enable_social_login_links_bp',
			),
			array(
				'id'     => 'buddyboss',
				'name'   => __( 'BuddyBoss', 'loginpress-pro' ),
				'desc'   => __( 'Hassle-free login experience for your BuddyBoss community.', 'loginpress-pro' ),
				'img'    => 'buddyboss.svg',
				'target' => '.enable_captcha_bb, .enable_social_login_links_bb',
			),
			array(
				'id'     => 'lifterlms',
				'name'   => __( 'LifterLMS', 'loginpress-pro' ),
				'desc'   => __( 'Let students log in easily and securely.', 'loginpress-pro' ),
				'img'    => 'lifterlms.svg',
				'target' => '.enable_captcha_llms, .enable_social_login_links_lifterlms',
			),
			array(
				'id'     => 'learndash',
				'name'   => __( 'LearnDash', 'loginpress-pro' ),
				'desc'   => __( 'Simplify learning access with our login tools.', 'loginpress-pro' ),
				'img'    => 'learndash.svg',
				'target' => '.enable_captcha_ld, .enable_social_ld',
			),
		);

		ob_start();
		?>
		<p><?php esc_html_e( 'LoginPress integrates with the most popular WordPress plugins to enhance your login experience. Our Social Login, CAPTCHA and Limit Login Attempts features among others are easily integrated into these platforms, helping you streamline user access and enhance security.', 'loginpress-pro' ); ?></p>
		<div id="loginpress-integration-management">
			<div id="integration-cards-container" class="loginpress-integration-container" style="display: flex; flex-wrap: wrap;">
				<?php foreach ( $integrations as $integration ) : ?>
					<div class="loginpress-integration-card" data-integration="<?php echo esc_attr( $integration['id'] ); ?>" data-target="<?php echo esc_attr( $integration['target'] ); ?>">
						<div class="loginpress-integration-head">
							<img src="<?php echo esc_url( LOGINPRESS_PRO_DIR_URL . 'assets/img/' . $integration['img'] ); ?>" alt="<?php echo esc_attr( $integration['name'] ); ?>">
						</div>
						<div class="loginpress-integration-body">
							<h3><?php echo esc_html( $integration['name'] ); ?></h3>
							<p><?php echo esc_html( $integration['desc'] ); ?></p>
						</div>
						<div class="loginpress-integration-foot">
							<span class="loginpress-integration-comingsoon"><?php esc_html_e( 'Coming soon', 'loginpress-pro' ); ?></span>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		$desc_html = ob_get_clean();

		$license_tab = array(
			array(
				'id'        => 'loginpress_integration_settings',
				'title'     => __( 'Integrations', 'loginpress-pro' ),
				'sub-title' => __( 'All Plugin Integrations', 'loginpress-pro' ),
				'desc'      => $desc_html,
			),
		);

		return array_merge( $loginpress_tabs, $license_tab );
	}


	/**
	 * Adding a tab for Licensing at LoginPress Settings Page.
	 *
	 * @param array $loginpress_tabs Rest of the settings tabs of LoginPress.
	 * @return array $loginpress_pro_tabs License tab.
	 * @since 3.0.0
	 */
	public function loginpress_license_tab( $loginpress_tabs ) {
		$license_tab = array(
			array(
				'id'         => 'loginpress_pro_license',
				'title'      => __( 'License Manager', 'loginpress-pro' ),
				'sub-title'  => __( 'Manage Your License Key', 'loginpress-pro' ),
				/* Translators: %1$s The line break tag. */
				'desc'       => sprintf( __( 'Validating license key is mandatory to use automatic updates and plugin support.', 'loginpress-pro' ), '<p>', '</p>' ),
				'video_link' => 'M2M3G2TB9Dk',
			),
		);

		$loginpress_pro_tabs = array_merge( $loginpress_tabs, $license_tab );

		return $loginpress_pro_tabs;
	}
}
