<?php
/**
 * LoginPress CAPTCHA.
 *
 * @since 4.0.0
 * @package LoginPress
 */

if ( ! class_exists( 'LoginPress_Captcha' ) ) {
	include_once LOGINPRESS_PRO_ROOT_PATH . '/classes/captcha/rest-trait.php';
	/**
	 * LoginPress_Captcha
	 *
	 * @version 6.1.0
	 */
	class LoginPress_Captcha {
		use LoginPress_Captcha_Rest_Trait;

		/**
		 * Variable that checks for LoginPress settings.
		 *
		 * @var array LoginPress settings array.
		 * @since 2.0.1
		 */
		public $loginpress_settings;

		/**
		 * Variable that checks for Captcha settings.
		 *
		 * @var array Captcha settings array.
		 * @since 4.0.0
		 */
		public $loginpress_captcha_settings;

		/**
		 * Class Constructor.
		 *
		 * @since 4.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function __construct() {

			$this->loginpress_settings         = get_option( 'loginpress_setting' );
			$this->loginpress_captcha_settings = get_option( 'loginpress_captcha_settings' );
			$this->hooks();
		}

		/**
		 * Add all hooks.
		 *
		 * @since 4.0.0
		 * @version 6.1.0
		 * @return void
		 */
		private function hooks() {

			add_filter( 'loginpress_pro_settings', array( $this, 'loginpress_pro_settings_array' ), 10, 1 );
			add_filter( 'loginpress_settings_tab', array( $this, 'recaptcha_tab' ), 10 );
			add_filter( 'loginpress_settings_fields', array( $this, 'captcha_settings_field' ), 10, 1 );
			add_action( 'pre_update_option_loginpress_captcha_settings', array( $this, 'loginpress_verify_recaptcha_on_save' ), 10, 3 );
			add_action( 'admin_init', array( $this, 'loginpress_pro_admin_init' ), 7 );
			add_action( 'login_enqueue_scripts', array( $this, 'loginpress_captcha_enqueue_script' ), 1 );
			add_action( 'admin_enqueue_scripts', array( $this, 'loginpress_captchas_enqueue_script' ), 1 );
			add_action( 'rest_api_init', array( $this, 'lp_captcha_register_routes' ) );

			$captchas_type = isset( $this->loginpress_captcha_settings['captchas_type'] ) ? $this->loginpress_captcha_settings['captchas_type'] : 'type_recaptcha';

				include_once LOGINPRESS_PRO_ROOT_PATH . '/classes/captcha/loginpress-recaptcha.php';
			if ( ! class_exists( 'LoginPress_Recaptcha' ) ) {
				new LoginPress_Recaptcha( $this->loginpress_settings, $this->loginpress_captcha_settings );
			}

				include_once LOGINPRESS_PRO_ROOT_PATH . '/classes/captcha/loginpress-hcaptcha.php';
			if ( ! class_exists( 'LoginPress_Hcaptcha' ) ) {
				new LoginPress_Hcaptcha( $this->loginpress_captcha_settings );
			}

				include_once LOGINPRESS_PRO_ROOT_PATH . '/classes/captcha/loginpress-turnstile.php';
			if ( ! class_exists( 'LoginPress_Turnstile' ) ) {
				new LoginPress_Turnstile( $this->loginpress_captcha_settings );
			}
		}

		/**
		 * Enqueue the captcha frontend script on relevant pages.
		 *
		 * @since 5.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function loginpress_captcha_enqueue_script() {
			wp_enqueue_script( 'loginpress_captcha_front', LOGINPRESS_PRO_DIR_URL . 'assets/js/captcha.js', array( 'jquery' ), LOGINPRESS_PRO_VERSION, true );
		}

		/**
		 * Enqueue the captcha script on admin pages.
		 *
		 * @since 5.0.0
		 * @version 6.1.0
		 * @param string $hook The current admin page hook.
		 * @return void
		 */
		public function loginpress_captchas_enqueue_script( $hook ) {
			if ( 'toplevel_page_loginpress-settings' === $hook ) {
				wp_enqueue_script( 'cloudflare-turnstile', LOGINPRESS_CF_TURNSTILE_URL, array(), LOGINPRESS_PRO_VERSION, true );
				wp_enqueue_script( 'google-recaptcha', LOGINPRESS_GOOGLE_RECAPTCHA_URL, array(), LOGINPRESS_PRO_VERSION, true );
				wp_enqueue_script( 'hcaptcha', LOGINPRESS_HCAPTCHA_URL, array(), LOGINPRESS_PRO_VERSION, true );
			}
		}

		/**
		 * LoginPress_pro_settings_array Setting Fields for reCAPTCHA.
		 *
		 * @since 3.0.0
		 * @version 6.1.0
		 * @param array $setting_array Settings fields of free version.
		 * @return array $_new_settings reCAPTCHA settings fields.
		 */
		public function loginpress_pro_settings_array( $setting_array ) {

			$apply_recaptcha_to = array(
				'login_form'        => __( 'Login Form', 'loginpress-pro' ),
				'lostpassword_form' => __( 'Lost Password Form', 'loginpress-pro' ),
				'register_form'     => __( 'Register Form', 'loginpress-pro' ),
			);

			// Introduce in 3.0.
			if ( class_exists( 'woocommerce' ) ) {

				$woo_recaptcha_options = array(
					'woocommerce_login_form'    => __( 'WooCommerce Login Form', 'loginpress-pro' ),
					'woocommerce_register_form' => __( 'WooCommerce Register Form', 'loginpress-pro' ),
				);

				$apply_recaptcha_to = array_merge( $apply_recaptcha_to, $woo_recaptcha_options );
			}

			// Introduce in 3.0.
			if ( get_default_comment_status() ) {

				$comments_options = array(
					'comment_form_defaults' => __( 'Comments Section', 'loginpress-pro' ),
				);

				$apply_recaptcha_to = array_merge( $apply_recaptcha_to, $comments_options );
			}
			$_new_settings = array(
				array(
					'name'  => 'force_login',
					'label' => __( 'Force Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable to force prompt user login for exclusive access.', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				array(
					'name'  => 'enable_user_verification',
					'label' => __( 'New user verification', 'loginpress-pro' ),
					'desc'  => __( 'Allows admin to verify user\'s registration request on the site.', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),

			);

			return( array_merge( $_new_settings, $setting_array ) );
		}

		/**
		 * RECAPTCHA Settings tab's.
		 *
		 * @since 4.0.0
		 * @version 6.1.0
		 * @param array $loginpress_tabs The setting tabs.
		 * @return array The reCAPTCHA setting tabs and their attributes.
		 */
		public function recaptcha_tab( $loginpress_tabs ) {
			// Check if captcha tab should be hidden.
			if ( ! $this->loginpress_before_captcha_tab() ) {
				return $loginpress_tabs;
			}

			$new_tab = array(
				array(
					'id'         => 'loginpress_captcha_settings',
					'title'      => __( 'Captchas', 'loginpress-pro' ),
					'sub-title'  => __( 'CAPTCHA Protection Settings', 'loginpress-pro' ),
					/* Translators: The Captcha tabs */
					'desc'       => $this->tab_desc(),
					'video_link' => '26dUFdX2srU',
				),
			);
			return array_merge( $loginpress_tabs, $new_tab );
		}

		/**
		 * The tab_desc description of the tab 'Captcha Settings'.
		 *
		 * @since 4.0.0
		 * @version 6.1.0
		 * @return string $html The tab description.
		 */
		public function tab_desc() {
			$cap_type      = isset( $this->loginpress_captcha_settings['recaptcha_type'] ) ? $this->loginpress_captcha_settings['recaptcha_type'] : 'v2-robot';
			$cap_site      = isset( $this->loginpress_captcha_settings['site_key'] ) ? $this->loginpress_captcha_settings['site_key'] : '';
			$cap_secret    = isset( $this->loginpress_captcha_settings['secret_key'] ) ? $this->loginpress_captcha_settings['secret_key'] : '';
			$cap_site_v2   = isset( $this->loginpress_captcha_settings['site_key_v2_invisible'] ) ? $this->loginpress_captcha_settings['site_key_v2_invisible'] : '';
			$cap_secret_v2 = isset( $this->loginpress_captcha_settings['secret_key_v2_invisible'] ) ? $this->loginpress_captcha_settings['secret_key_v2_invisible'] : '';
			$captchas_type = isset( $this->loginpress_captcha_settings['captchas_type'] ) ? $this->loginpress_captcha_settings['captchas_type'] : 'type_recaptcha';
			// translators: Captcha feature description.
			$html = sprintf( __( '%1$s The LoginPress CAPTCHA feature lets you easily integrate different types of CAPTCHA services into your login and registration forms. CAPTCHA types offered include Google reCAPTCHA, hCAPTCHA, and other widely used CAPTCHA services. This feature helps prevent spam, bot attacks, and authorized access, ensuring a more secure user experience. %2$s', 'loginpress-pro' ), '<p>', '</p>' );
			// Check if reCaptcha v2 Robot settings have not been verified yet.
			// While site key and secret are present, the selected captcha type is 'v2-robot'.
			// And the overall captcha type is 'type_recaptcha'. If true, display a validation notice.
			if (
			( ( ! isset( $this->loginpress_captcha_settings['v2_robot_verified'] ) || empty( $this->loginpress_captcha_settings['v2_robot_verified'] ) ) && ( ! empty( $cap_site ) && ! empty( $cap_secret ) ) && 'v2-robot' === $cap_type && 'type_recaptcha' === $captchas_type ) ) {

				// Notification to verify the keys.
				$html .= '<div class="captcha-notify-container"><div class="captcha-notify-description">';
				$html .= '<h4>' . esc_html__( 'Validate Your reCaptcha Settings', 'loginpress-pro' ) . '</h4>';
				$html .= '<p>' . esc_html__( 'To ensure seamless functionality, please validate your reCaptcha settings. Existing setups will remain unaffected, but verification is required for continued use.', 'loginpress-pro' ) . '</p>';
				$html .= '</div></div>';
			} elseif ( ( ( ! empty( $cap_site_v2 ) && ! empty( $cap_secret_v2 ) ) && 'v2-invisible' === $cap_type && 'type_recaptcha' === $captchas_type ) ) {
				// Notification to change the reCAPTCHA type from V2.
				$html .= '<div class="captcha-notify-container"><div class="captcha-notify-description">';
				$html .= '<h4>' . esc_html__( 'Please Change Your reCaptcha Type', 'loginpress-pro' ) . '</h4>';
				$html .= '<p>' . esc_html__( 'We no longer support the V2 invisible reCAPTCHA version. Alternatively, you can switch to our reCAPTCHA v3 settings and configure it accordingly if you still prefer an invisible CAPTCHA experience.', 'loginpress-pro' ) . '</p>';
				$html .= '</div></div>';
			}

			return $html;
		}

		/**
		 * Add the settings fields for the Social Login.
		 *
		 * @since 4.0.0
		 * @version 6.1.0
		 * @param array $setting_array The social login setting array.
		 * @return array An array of setting's fields and their corresponding attributes.
		 */
		public function captcha_settings_field( $setting_array ) {
			$languages          = array(
				'af'    => 'Afrikaans',
				'sq'    => 'Albanian',
				'am'    => 'Amharic',
				'ar'    => 'Arabic',
				'hy'    => 'Armenian',
				'az'    => 'Azerbaijani',
				'eu'    => 'Basque',
				'be'    => 'Belarusian',
				'bn'    => 'Bengali',
				'bg'    => 'Bulgarian',
				'bs'    => 'Bosnian',
				'my'    => 'Burmese',
				'ca'    => 'Catalan',
				'ceb'   => 'Cebuano',
				'zh'    => 'Chinese',
				'zh-CN' => 'Chinese Simplified',
				'zh-TW' => 'Chinese Traditional',
				'co'    => 'Corsican',
				'hr'    => 'Croatian',
				'cs'    => 'Czech',
				'da'    => 'Danish',
				'nl'    => 'Dutch',
				'en'    => 'English',
				'eo'    => 'Esperanto',
				'et'    => 'Estonian',
				'fa'    => 'Farsi',
				'fi'    => 'Finnish',
				'fr'    => 'French',
				'fy'    => 'Frisian',
				'gd'    => 'Gaelic',
				'gl'    => 'Galacian',
				'ka'    => 'Georgian',
				'de'    => 'German',
				'el'    => 'Greek',
				'gu'    => 'Gujurati',
				'ht'    => 'Haitian',
				'ha'    => 'Hausa',
				'haw'   => 'Hawaiian',
				'he'    => 'Hebrew',
				'hi'    => 'Hindi',
				'hmn'   => 'Hmong',
				'hu'    => 'Hungarian',
				'is'    => 'Icelandic',
				'ig'    => 'Igbo',
				'id'    => 'Indonesian',
				'ga'    => 'Irish',
				'it'    => 'Italian',
				'ja'    => 'Japanese',
				'jw'    => 'Javanese',
				'kn'    => 'Kannada',
				'kk'    => 'Kazakh',
				'km'    => 'Khmer',
				'rw'    => 'Kinyarwanda',
				'ky'    => 'Kirghiz',
				'ko'    => 'Korean',
				'ku'    => 'Kurdish',
				'lo'    => 'Lao',
				'la'    => 'Latin',
				'lv'    => 'Latvian',
				'lt'    => 'Lithuanian',
				'lb'    => 'Luxembourgish',
				'mk'    => 'Macedonian',
				'mg'    => 'Malagasy',
				'ms'    => 'Malay',
				'ml'    => 'Malayalam',
				'mt'    => 'Maltese',
				'mi'    => 'Maori',
				'mr'    => 'Marathi',
				'mn'    => 'Mongolian',
				'ne'    => 'Nepali',
				'no'    => 'Norwegian',
				'ny'    => 'Nyanja',
				'or'    => 'Oriya',
				'pl'    => 'Polish',
				'pt'    => 'Portuguese',
				'ps'    => 'Pashto',
				'pa'    => 'Punjabi',
				'ro'    => 'Romanian',
				'ru'    => 'Russian',
				'sm'    => 'Samoan',
				'sn'    => 'Shona',
				'sd'    => 'Sindhi',
				'si'    => 'Singhalese',
				'sr'    => 'Serbian',
				'sk'    => 'Slovak',
				'sl'    => 'Slovenian',
				'so'    => 'Somali',
				'st'    => 'Southern Sotho',
				'es'    => 'Spanish',
				'su'    => 'Sundanese',
				'sw'    => 'Swahili',
				'sv'    => 'Swedish',
				'tl'    => 'Tagalog',
				'tg'    => 'Tajik',
				'ta'    => 'Tamil',
				'tt'    => 'Tatar',
				'te'    => 'Telugu',
				'th'    => 'Thai',
				'tr'    => 'Turkish',
				'tk'    => 'Turkmen',
				'ug'    => 'Uyghur',
				'uk'    => 'Ukrainian',
				'ur'    => 'Urdu',
				'uz'    => 'Uzbek',
				'vi'    => 'Vietnamese',
				'cy'    => 'Welsh',
				'xh'    => 'Xhosa',
				'yi'    => 'Yiddish',
				'yo'    => 'Yoruba',
				'zu'    => 'Zulu',
			);
			$apply_recaptcha_to = array(
				'login_form'        => __( 'Login Form', 'loginpress-pro' ),
				'lostpassword_form' => __( 'Lost Password Form', 'loginpress-pro' ),
				'register_form'     => __( 'Register Form', 'loginpress-pro' ),
			);

			// Apply filters to allow modifications.
			$apply_recaptcha_to = apply_filters( 'loginpress_apply_recaptcha_to', $apply_recaptcha_to );

			if ( get_default_comment_status() ) {

				$comments_options = array(
					'comment_form_defaults' => __( 'Comments Section', 'loginpress-pro' ),
				);

				$apply_recaptcha_to = array_merge( $apply_recaptcha_to, $comments_options );
			}

			$_new_tabs = array(
				array(
					'name'  => 'enable_captchas',
					'label' => __( 'Enable/Disable CAPTCHAs', 'loginpress-pro' ),
					'desc'  => __( 'Enable to add CAPTCHAs to your forms.', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				array(
					'name'    => 'captchas_type',
					'label'   => __( 'Select Captcha', 'loginpress-pro' ),
					'desc'    => __( 'Choose CAPTCHA from the options above.', 'loginpress-pro' ),
					'type'    => 'select',
					'default' => 'type_recaptcha',
					'options' => $this->loginpress_captcha_options(),
				),
				array(
					'name'    => 'recaptcha_type',
					'label'   => __( 'reCAPTCHA Version', 'loginpress-pro' ),
					'desc'    => __( 'Select the type of reCAPTCHA', 'loginpress-pro' ),
					'type'    => 'select',
					'default' => 'v2-robot',
					'options' => array(
						'v2-robot' => __( 'V2 I\'m not robot.', 'loginpress-pro' ),
						'v3'       => __( 'V3', 'loginpress-pro' ),
					),
				),
				array(
					'name'              => 'site_key',
					'label'             => __( 'Site Key', 'loginpress-pro' ),
					'desc'              => __( 'Get <a href="https://www.google.com/recaptcha/admin" target="_blank"> reCAPTCHA</a> Site Key.<br> <span class="alert-note">Make sure you  are adding right site key for this domain.</span>', 'loginpress-pro' ),
					'type'              => 'text',
					'sanitize_callback' => 'sanitize_text_field',
				),
				array(
					'name'              => 'secret_key',
					'label'             => __( 'Secret Key', 'loginpress-pro' ),
					'desc'              => __( 'Get <a href="https://www.google.com/recaptcha/admin" target="_blank"> reCAPTCHA</a> Secret Key. <br> <span class="alert-note">Make sure you  are adding right secret key for this domain.</span>', 'loginpress-pro' ),
					'type'              => 'text',
					'sanitize_callback' => 'sanitize_text_field',
				),
				array(
					'name'              => 'site_key_v3',
					'label'             => __( 'Site Key', 'loginpress-pro' ),
					'desc'              => __( 'Get <a href="https://www.google.com/recaptcha/admin" target="_blank"> reCAPTCHA</a> Site Key.<br> <span class="alert-note">Make sure you  are adding right site key for this domain.</span>', 'loginpress-pro' ),
					'type'              => 'text',
					'sanitize_callback' => 'sanitize_text_field',
				),
				array(
					'name'              => 'secret_key_v3',
					'label'             => __( 'Secret Key', 'loginpress-pro' ),
					'desc'              => __( 'Get <a href="https://www.google.com/recaptcha/admin" target="_blank"> reCAPTCHA</a> Secret Key. <br> <span class="alert-note">Make sure you  are adding right secret key for this domain.</span>', 'loginpress-pro' ),
					'type'              => 'text',
					'sanitize_callback' => 'sanitize_text_field',
				),
				array(
					'name'     => 'validate_v2_keys',
					'label'    => esc_html__( 'Validate Keys', 'loginpress-pro' ),
					'callback' => array( $this, 'callback_recaptcha_validate_keys' ),
				),
				array(
					'name'    => 'good_score',
					'label'   => __( 'Select reCaptcha score', 'loginpress-pro' ),
					'desc'    => __( 'Set minimum level of score to be achieved by a human user.', 'loginpress-pro' ),
					'type'    => 'select',
					'default' => '0.5',
					'options' => array(
						'0.1' => '0.1',
						'0.2' => '0.2',
						'0.3' => '0.3',
						'0.4' => '0.4',
						'0.5' => '0.5',
						'0.6' => '0.6',
						'0.7' => '0.7',
						'0.8' => '0.8',
						'0.9' => '0.9',
						'1.0' => '1.0',
					),
				),
				array(
					'name'    => 'captcha_theme',
					'label'   => __( 'Choose Theme', 'loginpress-pro' ),
					'desc'    => __( 'Select a theme for reCAPTCHA', 'loginpress-pro' ),
					'type'    => 'select',
					'default' => 'light',
					'options' => array(
						'light' => 'Light',
						'dark'  => 'Dark',
					),
				),
				array(
					'name'    => 'captcha_language',
					'label'   => __( 'Choose Language', 'loginpress-pro' ),
					'desc'    => __( 'Select a language for reCAPTCHA', 'loginpress-pro' ),
					'type'    => 'select',
					'default' => 'en',
					'options' => $languages,
				),
				array(
					'name'              => 'v2_robot_verified',
					'type'              => 'text',
					'sanitize_callback' => 'sanitize_text_field',
				),
				array(
					'name'              => 'v2_invisible_verified',
					'type'              => 'text',
					'sanitize_callback' => 'sanitize_text_field',
				),
				array(
					'name'    => 'captcha_enable',
					'label'   => __( 'Enable reCAPTCHA on', 'loginpress-pro' ),
					'desc'    => __( 'Choose the form on which you need to apply Google reCAPTCHA.', 'loginpress-pro' ),
					'type'    => 'multicheck',
					'default' => array( 'login_form' => 'login_form' ),
					'options' => $apply_recaptcha_to,
				),
				array(
					'name'    => 'hcaptcha_type',
					'label'   => __( 'hCaptcha Version', 'loginpress-pro' ),
					'desc'    => __( 'Select the type of hCaptcha', 'loginpress-pro' ),
					'type'    => 'select',
					'default' => 'normal',
					'options' => array(
						'normal'    => __( 'Normal', 'loginpress-pro' ),
						'compact'   => __( 'Compact', 'loginpress-pro' ),
						'invisible' => __( 'Invisible', 'loginpress-pro' ),
					),
				),
				array(
					'name'              => 'hcaptcha_site_key',
					'label'             => __( 'Site Key', 'loginpress-pro' ),
					'desc'              => __( 'Get <a href="https://dashboard.hcaptcha.com/sites" target="_blank"> hCaptcha</a> Site Key.<br> <span class="alert-note">Make sure you  are adding right site key.</span>', 'loginpress-pro' ),
					'type'              => 'text',
					'sanitize_callback' => 'sanitize_text_field',
				),
				array(
					'name'              => 'hcaptcha_secret_key',
					'label'             => __( 'Secret Key', 'loginpress-pro' ),
					'desc'              => __( 'Get <a href="https://dashboard.hcaptcha.com/sites" target="_blank"> hCaptcha</a> Secret Key. <br> <span class="alert-note">Make sure you  are adding right secret key.</span>', 'loginpress-pro' ),
					'type'              => 'text',
					'sanitize_callback' => 'sanitize_text_field',
				),
				array(
					'name'     => 'validate_hcaptcha_keys',
					'label'    => esc_html__( 'Validate Keys', 'loginpress-pro' ),
					'callback' => array( $this, 'callback_hcaptcha_validate_keys' ),
				),
				array(
					'name'              => 'hcaptcha_verified',
					'type'              => 'text',
					'sanitize_callback' => 'sanitize_text_field',
				),
				array(
					'name'    => 'hcaptcha_theme',
					'label'   => __( 'Choose Theme', 'loginpress-pro' ),
					'desc'    => __( 'Select a theme for hCaptcha', 'loginpress-pro' ),
					'type'    => 'select',
					'default' => 'light',
					'options' => array(
						'light' => 'Light',
						'dark'  => 'Dark',
					),
				),
				array(
					'name'    => 'hcaptcha_language',
					'label'   => __( 'Choose Language', 'loginpress-pro' ),
					'desc'    => __( 'Select a language for hCaptcha', 'loginpress-pro' ),
					'type'    => 'select',
					'default' => 'en',
					'options' => $languages,

				),
				array(
					'name'    => 'hcaptcha_enable',
					'label'   => __( 'Enable hCaptcha on', 'loginpress-pro' ),
					'desc'    => __( 'Choose the form on which you need to apply hCAPTCHA.', 'loginpress-pro' ),
					'type'    => 'multicheck',
					'default' => array( 'login_form' => 'login_form' ),
					'options' => $apply_recaptcha_to,
				),
				array(
					'name'              => 'site_key_cf',
					'label'             => __( 'Site Key', 'loginpress-pro' ),
					'desc'              => __( 'Get <a href="https://developers.cloudflare.com/turnstile/get-started/" target="_blank"> turnstile</a> Site Key.<br> <span class="alert-note">Make sure you  are adding right site key for this domain.</span>', 'loginpress-pro' ),
					'type'              => 'text',
					'sanitize_callback' => 'sanitize_text_field',
				),
				array(
					'name'              => 'secret_key_cf',
					'label'             => __( 'Secret Key', 'loginpress-pro' ),
					'desc'              => __( 'Get <a href="https://developers.cloudflare.com/turnstile/get-started/" target="_blank"> turnstile</a> Secret Key. <br> <span class="alert-note">Make sure you  are adding right secret key for this domain.</span>', 'loginpress-pro' ),
					'type'              => 'text',
					'sanitize_callback' => 'sanitize_text_field',
				),
				array(
					'name'     => 'validate_cf',
					'label'    => __( 'Validate Keys', 'loginpress-pro' ),
					'callback' => array( $this, 'callback_cf_validate_keys' ),
				),
				array(
					'name'    => 'captcha_enable_cf',
					'label'   => __( 'Enable Turnstile on', 'loginpress-pro' ),
					'desc'    => __( 'Choose the form on which you need to apply Cloudflare Turnstile.', 'loginpress-pro' ),
					'type'    => 'multicheck',
					'default' => array( 'login_form' => 'login_form' ),
					'options' => $apply_recaptcha_to,
				),
				array(
					'name'    => 'cf_theme',
					'label'   => __( 'Choose Theme', 'loginpress-pro' ),
					'desc'    => __( 'Select a theme for Turnstile', 'loginpress-pro' ),
					'type'    => 'select',
					'default' => 'light',
					'options' => array(
						'light' => 'Light',
						'dark'  => 'Dark',
					),
				),

			);

			$_new_tabs = array( 'loginpress_captcha_settings' => $_new_tabs );
			return array_merge( $setting_array, $_new_tabs );
		}

		/**
		 * Initialize admin settings for LoginPress Pro.
		 *
		 * @since 4.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function loginpress_pro_admin_init() {

			$loginpress_settings = get_option( 'loginpress_setting' );
			if ( defined( 'LOGINPRESS_PRO_VERSION' ) && version_compare( LOGINPRESS_PRO_VERSION, '3.9.9', '>=' ) && array_key_exists( 'enable_repatcha', $loginpress_settings ) ) {
				// Retrieve old and new options.
				$old_settings = get_option( 'loginpress_setting', array() );
				$new_settings = get_option( 'loginpress_captcha_settings', array() );

				// Initialize new option as an empty array if it doesn't exist.
				if ( ! is_array( $new_settings ) ) {
					$new_settings = array();
				}

				// Check if recaptcha is enabled in the old settings.
				$enable_recaptcha = isset( $old_settings['enable_repatcha'] ) && 'on' === $old_settings['enable_repatcha'];

				// Map values from old settings to new settings.
				$new_settings['enable_captchas'] = $enable_recaptcha ? 'on' : 'off';
				$new_settings['captchas_type']   = $enable_recaptcha ? 'type_recaptcha' : '';

				// Compatibility of settings.
				if ( isset( $old_settings['recaptcha_type'] ) ) {
					$new_settings['recaptcha_type'] = $old_settings['recaptcha_type'];
					unset( $old_settings['recaptcha_type'] );
				}

				if ( isset( $old_settings['site_key'] ) ) {
					$new_settings['site_key'] = $old_settings['site_key'];
					unset( $old_settings['site_key'] );
				}

				if ( isset( $old_settings['secret_key'] ) ) {
					$new_settings['secret_key'] = $old_settings['secret_key'];
					unset( $old_settings['secret_key'] );
				}

				if ( isset( $old_settings['site_key_v2_invisible'] ) ) {
					$new_settings['site_key_v2_invisible'] = $old_settings['site_key_v2_invisible'];
					unset( $old_settings['site_key_v2_invisible'] );
				}

				if ( isset( $old_settings['secret_key_v2_invisible'] ) ) {
					$new_settings['secret_key_v2_invisible'] = $old_settings['secret_key_v2_invisible'];
					unset( $old_settings['secret_key_v2_invisible'] );
				}

				if ( isset( $old_settings['site_key_v3'] ) ) {
					$new_settings['site_key_v3'] = $old_settings['site_key_v3'];
					unset( $old_settings['site_key_v3'] );
				}

				if ( isset( $old_settings['secret_key_v3'] ) ) {
					$new_settings['secret_key_v3'] = $old_settings['secret_key_v3'];
					unset( $old_settings['secret_key_v3'] );
				}

				if ( isset( $old_settings['good_score'] ) ) {
					$new_settings['good_score'] = $old_settings['good_score'];
					unset( $old_settings['good_score'] );
				}

				if ( isset( $old_settings['captcha_theme'] ) ) {
					$new_settings['captcha_theme'] = $old_settings['captcha_theme'];
					unset( $old_settings['captcha_theme'] );
				}

				if ( isset( $old_settings['captcha_language'] ) ) {
					$new_settings['captcha_language'] = $old_settings['captcha_language'];
					unset( $old_settings['captcha_language'] );
				}

				// Define the expected keys for captcha_enable.
				$captcha_enable_keys = array(
					'login_form',
					'lostpassword_form',
					'register_form',
					'woocommerce_login_form',
					'woocommerce_register_form',
					'comment_form_defaults',
				);

				// Ensure captcha_enable keys are initialized in the new settings.
				if ( ! isset( $new_settings['captcha_enable'] ) || ! is_array( $new_settings['captcha_enable'] ) ) {
					$new_settings['captcha_enable'] = array();
				}

				foreach ( $captcha_enable_keys as $key ) {
					if ( isset( $old_settings['captcha_enable'][ $key ] ) ) {
						$new_settings['captcha_enable'][ $key ] = $old_settings['captcha_enable'][ $key ];
						unset( $old_settings['captcha_enable'][ $key ] );
					} else {
						$new_settings['captcha_enable'][ $key ] = ''; // Set to empty if not present.
					}
				}

				// Unset enable_repatcha from the old settings.
				if ( isset( $old_settings['enable_repatcha'] ) ) {
					unset( $old_settings['enable_repatcha'] );
				}

				// Remove empty captcha_enable from old settings.
				if ( isset( $old_settings['captcha_enable'] ) && empty( $old_settings['captcha_enable'] ) ) {
					unset( $old_settings['captcha_enable'] );
				}

				// Save the updated new option.
				update_option( 'loginpress_captcha_settings', $new_settings );
				// Save the updated old option.
				update_option( 'loginpress_setting', $old_settings );
			}
		}

		/**
		 * Displays reCAPTCHA v2 on a settings field to validate.
		 *
		 * @since 4.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function callback_recaptcha_validate_keys() {
			do_action( 'loginpress_recaptcha_validate_key' );
		}

		/**
		 * Displays hCaptcha on a settings field to validate.
		 *
		 * @since 4.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function callback_hcaptcha_validate_keys() {
			do_action( 'loginpress_hcaptcha_validate_key' );
		}

		/**
		 * Displays turnstile on a settings field to validate.
		 *
		 * @since 4.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function callback_cf_validate_keys() {
			do_action( 'loginpress_cf_validate_key' );
		}

		/**
		 * Get filtered captcha options based on user filter.
		 * This is for loginpress versions < 6.0.0
		 *
		 * @since 6.1.0
		 * @return array Filtered captcha options.
		 */
		public function loginpress_captcha_options() {
			$default_options = array(
				'type_recaptcha'  => __( 'Google reCAPTCHA', 'loginpress-pro' ),
				'type_hcaptcha'   => __( 'hCaptcha', 'loginpress-pro' ),
				'type_cloudflare' => __( 'Cloudflare Turnstile', 'loginpress-pro' ),
			);

			/**
			 * Filter captcha options to allow users to exclude specific captcha types.
			 *
			 * @since 6.1.0
			 * @param array $default_options Default captcha options array.
			 * @return array Filtered captcha options.
			 */
			return apply_filters( 'loginpress_captcha_type_options', $default_options );
		}

		/**
		 * Check if captcha tab should be visible.
		 * This is for loginpress versions < 6.0.0
		 *
		 * @since 6.1.0
		 * @return bool True if tab should be visible, false otherwise.
		 */
		public function loginpress_before_captcha_tab() {
			/**
			 * Filter to control captcha tab visibility.
			 *
			 * @since 6.1.0
			 * @param bool $visible Whether the captcha tab should be visible. Default true.
			 * @return bool True if tab should be visible, false to hide it.
			 */
			return apply_filters( 'loginpress_captcha_tab_visible', true );
		}
	}
}
