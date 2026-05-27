<?php
/**
 * LoginPress Social Login Settings Trait file.
 *
 * @package LoginPress Social Login
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LoginPress Social Login Settings Trait.
 *
 * Handles Some helping functions from social-login.php file.
 * Registers all the Social Login Settings.
 *
 * @package   LoginPress
 * @subpackage Traits\SocialLogin
 * @since     6.1.0
 */

if ( ! trait_exists( 'LoginPress_Social_Login_Settings_Trait' ) ) {
	/**
	 * LoginPress Social Login Settings Trait.
	 *
	 * Handles Some helping functions from social-login.php file.
	 * Registers all the Social Login Settings.
	 *
	 * @package   LoginPress
	 * @subpackage Traits\SocialLogin
	 * @since   6.1.0
	 */
	trait LoginPress_Social_Login_Settings_Trait {
		/**
		 * Add the settings fields for the Social Login.
		 *
		 * @param array $setting_array The social login setting array.
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return array An array of setting's fields and their corresponding attributes.
		 */
		public function settings_field( $setting_array ) {

			$_settings_tab = array(
				array(
					'name'    => 'enable_social_login_links',
					'label'   => __( 'Enable Social Login on', 'loginpress-pro' ),
					'desc'    => __( 'Enable Social Login on WordPress default Login, Register, and Comment forms.', 'loginpress-pro' ),
					'type'    => 'multicheck',
					'options' => array(
						'login'    => __( 'Login Form', 'loginpress-pro' ),
						'register' => __( 'Register Form', 'loginpress-pro' ),
						'comment'  => __( 'Comment Form', 'loginpress-pro' ),
					),
				),
				array(
					'name'        => 'social_login_button_label',
					'label'       => __( 'Social Login Button Label', 'loginpress-pro' ),
					'desc'        => __( 'Customize the label for social login buttons. Use <code>%provider%</code> to dynamically include the social provider name in the label. For example, "Login with %provider%" or "Continue with %provider%".', 'loginpress-pro' ),
					'placeholder' => 'Login with %provider%',
					'type'        => 'text',
					'std'         => 'Login with %provider%', // Default value.
				),
				array(
					'name'     => 'social_login_shortcode',
					'label'    => __( 'Social Login Shortcode', 'loginpress-pro' ),
					'type'     => 'callback',
					'callback' => array( $this, 'render_social_login_html' ),
					'desc'     => __( 'Place this shortcode where you want to add social login buttons.', 'loginpress-pro' ),
				),
				// Styles tab.
				array(
					'name'     => 'social_button_styles',
					'label'    => __( 'Button Styles', 'loginpress-pro' ),
					'callback' => array( $this, 'lpsl_multicheck_with_icons' ),
					'std'      => 'default',
					'options'  => array(
						'default'     => array(
							'label' => __( 'Default', 'loginpress-pro' ),
							'icon'  => '<img src="' . esc_url( plugin_dir_url( dirname( __DIR__, 1 ) ) . 'assets/img/default-social-login.svg' ) . '" alt="' . esc_attr__( 'Default width icons', 'loginpress-pro' ) . '" />', // Replace with actual SVG code.
						),
						'full_width'  => array(
							'label' => __( 'Full Width', 'loginpress-pro' ),
							'icon'  => '<img src="' . esc_url( plugin_dir_url( dirname( __DIR__, 1 ) ) . 'assets/img/social-full-width.svg' ) . '" alt="' . esc_attr__( 'Full width icons', 'loginpress-pro' ) . '" />',
						),
						'icons'       => array(
							'label' => __( 'Square Icons', 'loginpress-pro' ),
							'icon'  => '<img src="' . esc_url( plugin_dir_url( dirname( __DIR__, 1 ) ) . 'assets/img/social-icons.svg' ) . '" alt="' . esc_attr__( 'Square Icons', 'loginpress-pro' ) . '" />',
						),
						'round_icons' => array(
							'label' => __( 'Round Icons', 'loginpress-pro' ),
							'icon'  => '<img src="' . esc_url( plugin_dir_url( dirname( __DIR__, 1 ) ) . 'assets/img/round-icon.svg' ) . '" alt="' . esc_attr__( 'Round Icons', 'loginpress-pro' ) . '" />',
						),
					),
				),
				array(
					'name'     => 'social_button_position',
					'label'    => __( 'Button Position', 'loginpress-pro' ),
					'callback' => array( $this, 'lpsl_multicheck_with_icons' ),
					'std'      => 'default',
					'options'  => array(
						'default'         => array(
							'label' => __( 'Default', 'loginpress-pro' ),
							'icon'  => '<img src="' . esc_url( plugin_dir_url( dirname( __DIR__, 1 ) ) . 'assets/img/below-with-seprator.svg' ) . '" alt="' . esc_attr__( 'Default', 'loginpress-pro' ) . '" />',
						),
						'below'           => array(
							'label' => __( 'Below', 'loginpress-pro' ),
							'icon'  => '<img src="' . esc_url( plugin_dir_url( dirname( __DIR__, 1 ) ) . 'assets/img/default.svg' ) . '" alt="' . esc_attr__( 'Below', 'loginpress-pro' ) . '" />',
						),
						'above'           => array(
							'label' => __( 'Above', 'loginpress-pro' ),
							'icon'  => '<img src="' . esc_url( plugin_dir_url( dirname( __DIR__, 1 ) ) . 'assets/img/above.svg' ) . '" alt="' . esc_attr__( 'Above', 'loginpress-pro' ) . '" />',
						),
						'above_separator' => array(
							'label' => __( 'Above with Separator', 'loginpress-pro' ),
							'icon'  => '<img src="' . esc_url( plugin_dir_url( dirname( __DIR__, 1 ) ) . 'assets/img/above-with-separtor.svg' ) . '" alt="' . esc_attr__( 'Above with Separator', 'loginpress-pro' ) . '" />',
						),
					),
				),

				// Providers Tab.
				$this->get_provider_status_setting( 'facebook' ),
				array(
					'name'  => 'facebook',
					'label' => __( 'Facebook Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable Facebook Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'facebook' ),
				array(
					'name'  => 'facebook_app_id',
					'label' => __( 'Facebook App ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter your Facebook App ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'facebook_app_secret',
					'label' => __( 'Facebook App Secret Key', 'loginpress-pro' ),
					'desc'  => __( 'Enter your Facebook App Secret Key.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'facebook_redirect_uri',
					'label' => __( 'Redirect URI', 'loginpress-pro' ),
					'desc'  => sprintf(
						/* translators: The Facebook callback URl. */
						__( '<span id="facebook_redirect_uri"><a href="%2$s?lpsl_login_id=facebook_check">%2$s?lpsl_login_id=facebook_check</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="facebook_redirect_uri" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g><defs><clipPath id="clip0_27_294"><rect width="22" height="22" fill="white" transform="matrix(-1 0 0 1 22 0)"></rect></clipPath></defs></svg></button></span><span class="lp-callback-url"> %1$s <span class="loginpress-copy-link">', 'loginpress-pro' ),
						__( 'Click to copy and paste the callback URL under "Valid OAuth Redirect URIs" in your Facebook API settings.', 'loginpress-pro' ),
						wp_login_url(),
					),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'twitter' ),
				array(
					'name'  => 'twitter',
					'label' => __( 'Twitter Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable Twitter Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'twitter' ),
				array(
					'name'    => 'twitter_api_version',
					'label'   => __( 'Twitter API Version', 'loginpress-pro' ),
					'desc'    => __( 'Select the Twitter API version to use.', 'loginpress-pro' ),
					'type'    => 'select',
					'options' => array(
						'oauth1' => __( 'OAuth 1.1', 'loginpress-pro' ),
						'oauth2' => __( 'OAuth 2.0', 'loginpress-pro' ),
					),
					'default' => 'oauth1',
				),
				array(
					'name'  => 'twitter_oauth_token',
					'label' => __( 'Twitter API key', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Consumer API key.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'twitter_token_secret',
					'label' => __( 'Twitter API secret key', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Consumer API secret key.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'twitter_callback_url',
					'label' => __( 'Twitter Callback URL', 'loginpress-pro' ),
					'desc'  => sprintf(
						/* translators: The Twitter callback URl. */
						__( '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="twitter_callback_url"><a href=%2$s?lpsl_login_id=twitter_login>%2$s?lpsl_login_id=twitter_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="twitter_callback_url" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g><defs><clipPath id="clip0_27_294"><rect width="22" height="22" fill="white" transform="matrix(-1 0 0 1 22 0)"></rect></clipPath></defs></svg></button></span>', 'loginpress-pro' ),
						__( 'Enter Your Callback URL:', 'loginpress-pro' ),
						wp_login_url()
					),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'gplus' ),
				array(
					'name'  => 'gplus',
					'label' => __( 'Google Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable Google Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'google' ),
				array(
					'name'  => 'gplus_client_id',
					'label' => __( 'Client ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'gplus_client_secret',
					'label' => __( 'Client Secret', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client Secret.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'gplus_redirect_uri',
					'label' => __( 'Redirect URI', 'loginpress-pro' ),
					'desc'  => sprintf(
						/* translators: The Google callback URl. */
						__( '<span class="lp-callback-url"> %1$s <span class="loginpress-copy-link"><span id="gplus_redirect_uri"><a href="%2$s?lpsl_login_id=gplus_login">%2$s?lpsl_login_id=gplus_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="gplus_redirect_uri" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g><defs><clipPath id="clip0_27_294"><rect width="22" height="22" fill="white" transform="matrix(-1 0 0 1 22 0)"></rect></clipPath></defs></svg></button></span>', 'loginpress-pro' ),
						__( 'Enter your Redirect URI.', 'loginpress-pro' ),
						wp_login_url(),
					),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'linkedin' ),
				array(
					'name'  => 'linkedin',
					'label' => __( 'LinkedIn Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable LinkedIn Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'linkedin' ),
				array(
					'name'  => 'linkedin_client_id',
					'label' => __( 'Client ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'linkedin_client_secret',
					'label' => __( 'Client Secret', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client Secret.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'linkedin_redirect_uri',
					'label' => __( 'Redirect URI', 'loginpress-pro' ),
					'desc'  => sprintf(
						/* translators: The linkedin callback URl. */
						__(
							'<span class="lp-callback-url">%1$s <span class="loginpress-copy-link"><span id="linkedin_redirect_uri"><a href="%2$s?lpsl_login_id=linkedin_login">%2$s?lpsl_login_id=linkedin_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="linkedin_redirect_uri" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>',
							'loginpress-pro'
						),
						__( 'Enter your Redirect URI.', 'loginpress-pro' ),
						wp_login_url()
					),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'microsoft' ),
				array(
					'name'  => 'microsoft',
					'label' => __( 'Microsoft Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable Microsoft Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'microsoft' ),
				array(
					'name'  => 'microsoft_app_id',
					'label' => __( 'Client ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'microsoft_app_secret',
					'label' => __( 'Client Secret', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client Secret.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'microsoft_redirect_uri',
					'label' => __( 'Redirect URI', 'loginpress-pro' ),
					'desc'  => sprintf( /* translators: The microsoft callback URl. */
						__(
							'<span class="lp-callback-url">%1$s <span class="loginpress-copy-link"><span id="microsoft_redirect_uri"><a href="%2$s">%2$s</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="microsoft_redirect_uri" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>',
							'loginpress-pro'
						),
						__( 'Enter your Redirect URI.', 'loginpress-pro' ),
						wp_login_url()
					),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'github' ),
				array(
					'name'  => 'github',
					'label' => __( 'Github Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable Github Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'github' ),
				array(
					'name'  => 'github_client_id',
					'label' => __( 'Client ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'github_client_secret',
					'label' => __( 'Client Secret', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client Secret.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'github_redirect_uri',
					'label' => __( 'Redirect URI', 'loginpress-pro' ),
					'desc'  => sprintf( /* translators: The github callback URl. */
						__( '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="github_redirect_uri"><a href=%2$s?lpsl_login_id=github_login>%2$s?lpsl_login_id=github_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="github_redirect_uri" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>', 'loginpress-pro' ),
						__( 'Enter Your Callback URL:', 'loginpress-pro' ),
						wp_login_url()
					),

					'type'  => 'text',
				),
				array(
					'name'  => 'github_app_name',
					'label' => __( 'Github App Name', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Github App Name.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'discord' ),
				array(
					'name'  => 'discord',
					'label' => __( 'Discord Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable Discord Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'discord' ),
				array(
					'name'  => 'discord_client_id',
					'label' => __( 'Client ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'discord_client_secret',
					'label' => __( 'Client Secret', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client Secret.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'discord_redirect_uri',
					'label' => __( 'Redirect URI', 'loginpress-pro' ),
					'desc'  => sprintf( /* translators: The discord callback URl. */
						__( '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="discord_redirect_uri"><a href=%2$s?lpsl_login_id=discord_login>%2$s?lpsl_login_id=discord_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="discord_redirect_uri" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>', 'loginpress-pro' ),
						__( 'Enter your Redirect URI.', 'loginpress-pro' ),
						wp_login_url()
					),
					'type'  => 'text',
				),
				array(
					'name'  => 'discord_generated_url',
					'label' => __( 'Discord Generated URL', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Discord Generated URL', 'loginpress-pro' ),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'wordpress' ),
				array(
					'name'  => 'wordpress',
					'label' => __( 'WordPress Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable WordPress Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'WordPress' ),
				array(
					'name'  => 'wordpress_client_id',
					'label' => __( 'Client ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'wordpress_client_secret',
					'label' => __( 'Client Secret', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client Secret.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'wordpress_redirect_uri',
					'label' => __( 'Redirect URI', 'loginpress-pro' ),
					'desc'  => sprintf( /* translators: The wordpress callback URl. */
						__( '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="wordpress_redirect_uri"><a href=%2$s?lpsl_login_id=wordpress_login>%2$s?lpsl_login_id=wordpress_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="wordpress_redirect_uri" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>', 'loginpress-pro' ),
						__( 'Enter your Redirect URI.', 'loginpress-pro' ),
						wp_login_url()
					),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'amazon' ),
				array(
					'name'  => 'amazon',
					'label' => __( 'Amazon Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable Amazon Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'amazon' ),
				array(
					'name'  => 'amazon_client_id',
					'label' => __( 'Client ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'amazon_client_secret',
					'label' => __( 'Client Secret', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client Secret.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'amazon_redirect_uri',
					'label' => __( 'Redirect URI', 'loginpress-pro' ),
					'desc'  => sprintf( /* translators: The amazon callback URl. */
						__( '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="amazon_redirect_uri"><a href=%2$s?lpsl_login_id=amazon_login>%2$s?lpsl_login_id=amazon_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="amazon_redirect_uri" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>', 'loginpress-pro' ),
						__( 'Enter your Redirect URI.', 'loginpress-pro' ),
						wp_login_url()
					),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'pinterest' ),
				array(
					'name'  => 'pinterest',
					'label' => __( 'Pinterest Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable Pinterest Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'pinterest' ),
				array(
					'name'  => 'pinterest_client_id',
					'label' => __( 'Client ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'pinterest_client_secret',
					'label' => __( 'Client Secret', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client Secret.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'pinterest_redirect_uri',
					'label' => __( 'Redirect URI', 'loginpress-pro' ),
					'desc'  => sprintf( /* translators: The Pinterest callback URl. */
						__(
							'<span class="lp-callback-url">%1$s <span class="loginpress-copy-link"><span id="pinterest_redirect_uri"><a href="%2$s">%2$s</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="pinterest_redirect_uri" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>',
							'loginpress-pro'
						),
						__( 'Enter your Redirect URI.', 'loginpress-pro' ),
						home_url()
					),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'reddit' ),
				array(
					'name'  => 'reddit',
					'label' => __( 'Reddit Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable Reddit Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'reddit' ),
				array(
					'name'  => 'reddit_client_id',
					'label' => __( 'Client ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'reddit_client_secret',
					'label' => __( 'Client Secret', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client Secret.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'reddit_redirect_uri',
					'label' => __( 'Redirect URI', 'loginpress-pro' ),
					'desc'  => sprintf( /* translators: The reddit callback URl. */
						__( '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="reddit_redirect_uri"><a href=%2$s?lpsl_login_id=reddit_login>%2$s?lpsl_login_id=reddit_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="reddit_redirect_uri" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>', 'loginpress-pro' ),
						__( 'Enter your Redirect URI.', 'loginpress-pro' ),
						wp_login_url()
					),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'spotify' ),
				array(
					'name'  => 'spotify',
					'label' => __( 'Spotify Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable Spotify Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'spotify' ),
				array(
					'name'  => 'spotify_client_id',
					'label' => __( 'Client ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'spotify_client_secret',
					'label' => __( 'Client Secret', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client Secret.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'spotify_redirect_uri',
					'label' => __( 'Redirect URI', 'loginpress-pro' ),
					'desc'  => sprintf( /* translators: The spotify callback URl. */
						__( '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="spotify_redirect_uri"><a href=%2$s?lpsl_login_id=spotify_login>%2$s?lpsl_login_id=spotify_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="spotify_redirect_uri" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>', 'loginpress-pro' ),
						__( 'Enter your Redirect URI.', 'loginpress-pro' ),
						wp_login_url()
					),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'twitch' ),
				array(
					'name'  => 'twitch',
					'label' => __( 'Twitch Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable twitch Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'twitch' ),
				array(
					'name'  => 'twitch_client_id',
					'label' => __( 'Client ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'twitch_client_secret',
					'label' => __( 'Client Secret', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Client Secret.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'twitch_redirect_uri',
					'label' => __( 'Redirect URI', 'loginpress-pro' ),
					'desc'  => sprintf( /* translators: The twitch callback URl. */
						__( '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="twitch_redirect_uri"><a href=%2$s?lpsl_login_id=twitch_login>%2$s?lpsl_login_id=twitch_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="twitch_redirect_uri" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>', 'loginpress-pro' ),
						__( 'Enter your Redirect URI.', 'loginpress-pro' ),
						wp_login_url()
					),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'disqus' ),
				array(
					'name'  => 'disqus',
					'label' => __( 'Disqus Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable Disqus Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'disqus' ),
				array(
					'name'  => 'disqus_client_id',
					'label' => __( 'API Key', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your API Key.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'disqus_client_secret',
					'label' => __( 'API Secret', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your API Secret.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'disqus_callback_url',
					'label' => __( 'Callback URL', 'loginpress-pro' ),
					'desc'  => sprintf( /* translators: The disqus callback URl. */
						__( '<span class="lp-callback-url">%1$s</span> <span class="loginpress-copy-link"><span id="disqus_callback_url"><a href=%2$s?lpsl_login_id=disqus_login>%2$s?lpsl_login_id=disqus_login</a></span> <button type="button" class="loginpress-copy-btn" data-tooltip="Copy" data-target="disqus_callback_url" style="background: none; border: none; padding: 0; cursor: pointer;"><svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g></svg></button></span>', 'loginpress-pro' ),
						__( 'Enter your Callback URL', 'loginpress-pro' ),
						wp_login_url()
					),
					'type'  => 'text',
				),
				$this->get_provider_status_setting( 'apple' ),
				array(
					'name'  => 'apple',
					'label' => __( 'Apple Login', 'loginpress-pro' ),
					'desc'  => __( 'Enable Apple Login', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				$this->get_provider_button_label_setting( 'apple' ),
				array(
					'name'  => 'apple_service_id',
					'label' => __( 'Service ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Service ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'apple_key_id',
					'label' => __( 'Key ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Key ID.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'apple_team_id',
					'label' => __( 'Team ID', 'loginpress-pro' ),
					'desc'  => __( 'Enter Your Team ID', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'  => 'apple_p_key',
					'label' => __( 'Private Key', 'loginpress-pro' ),
					'desc'  => __( 'Enter The Private Key From the Downloaded File', 'loginpress-pro' ),
					'type'  => 'textarea',
				),
				array(
					'name'  => 'apple_secret',
					'label' => __( 'Apple Secret', 'loginpress-pro' ),
					'desc'  => __( 'This JWT token is generated.', 'loginpress-pro' ),
					'type'  => 'text',
				),
				array(
					'name'     => 'provider_order',
					'label'    => __( 'Provider Order', 'loginpress-pro' ),
					'desc'     => __( 'The order of social login providers.', 'loginpress-pro' ),
					'callback' => array( $this, 'lpsl_callback_hidden' ),
				),
				array(
					'name'  => 'loginpress_info',
					'label' => '', // No label.
					'desc'  => __( 'Enable 2 Facebook Login for authentication.', 'loginpress-pro' ),
					'type'  => 'html',
				),
			);

			// Return the consolidated settings array.
			$_new_tabs = array( 'loginpress_social_logins' => $_settings_tab );
			return( array_merge( $_new_tabs, $setting_array ) );
		}
	}
}
