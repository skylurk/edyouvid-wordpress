<?php
/**
 * LoginPress Integration Settings Trait.
 *
 * Registers all the settings in Integrations tab.
 *
 * @package   LoginPress-Pro
 * @subpackage Traits\Loginpress-Integration
 * @since 6.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! trait_exists( 'LoginPress_Integration_Settings_Trait' ) ) {
	/**
	 * LoginPress Main Admin Trait.
	 *
	 * Registers all the settings in Integrations tab.
	 *
	 * @package   LoginPress-Pro
	 * @subpackage Traits\Loginpress-Integration
	 * @since 6.1.0
	 */
	trait LoginPress_Integration_Settings_Trait {

		/**
		 * Integration Settings tab's.
		 *
		 * @param array $loginpress_tabs The setting tabs.
		 * @return array The Integration setting tabs and their attributes.
		 * @since 5.0.0
		 */
		public function integrations_tab( $loginpress_tabs ) {
			$new_tab = array(
				array(
					'id'        => 'loginpress_integration_settings',
					'title'     => esc_html__( 'Integrations', 'loginpress-pro' ),
					'sub-title' => esc_html__( 'Integration with other plugins', 'loginpress-pro' ),
					/* Translators: The Captcha tabs */
					'desc'      => $this->tab_desc(),
				),
			);
			return array_merge( $loginpress_tabs, $new_tab );
		}

		/**
		 * The tab_desc description of the tab 'Integration Settings'.
		 *
		 * @return string $html The tab description.
		 * @since 5.0.0
		 */
		public function tab_desc() {
			$html = wp_kses_post(
				sprintf( // translators: Loginpress Integration tab.
					__( '%1$s LoginPress integrates with the most popular WordPress plugins to enhance your login experience. Our Social Login, CAPTCHA and Limit Login Attempts features among others are easily integrated into these platforms, helping you streamline user access and enhance security. %2$s', 'loginpress-pro' ),
					'<p>',
					'</p>'
				)
			);

			return $html;
		}

		/**
		 * Add the settings fields for the Integration tab.
		 *
		 * @param array $setting_array The integration setting array.
		 * @return array An array of setting's fields and their corresponding attributes.
		 * @since 5.0.0
		 */
		public function integration_settings_field( $setting_array ) {
			$this->loginpress_social_position_image = '<img src="' . esc_url( LOGINPRESS_PRO_DIR_URL . 'addons/social-login/assets/img/below-with-seprator.svg' ) . '" alt="' . esc_attr__( 'Default', 'loginpress-pro' ) . '" class="lp-hover-image">';
			$apply_recaptcha_to                     = array();
			$woo_recaptcha_options                  = array(
				'woocommerce_login_form'    => esc_html__( 'WooCommerce Login Form', 'loginpress-pro' ),
				'woocommerce_register_form' => esc_html__( 'WooCommerce Register Form', 'loginpress-pro' ),
				'woocommerce_checkout_form' => esc_html__( 'WooCommerce Checkout Form', 'loginpress-pro' ),
			);
			$button_position_options                = array(
				'default'         => esc_html__( 'Default (Show below the separator)', 'loginpress-pro' ),
				'below'           => esc_html__( 'Below (Show below the fields)', 'loginpress-pro' ),
				'above'           => esc_html__( 'Above (Show above the fields)', 'loginpress-pro' ),
				'above_separator' => esc_html__( 'Above with Separator (Show above the separator)', 'loginpress-pro' ),
			);

			$apply_recaptcha_to = array_merge( $apply_recaptcha_to, $woo_recaptcha_options );

			$_new_tabs = array(
				array(
					'name'    => 'enable_captcha_woo',
					'label'   => esc_html__( 'Enable Captcha on', 'loginpress-pro' ),
					'desc'    => esc_html__( 'Choose the form on which you need to apply the selected captcha.', 'loginpress-pro' ) . '<hr>',
					'type'    => 'multicheck',
					'default' => array( 'login_form' => 'login_form' ),
					'options' => $apply_recaptcha_to,
				),
				array(
					'name'  => 'enable_social_woo_lf',
					'label' => esc_html__( 'Login Form', 'loginpress-pro' ),
					'desc'  => esc_html__( 'Enable to add Social Login on WooCommerce login form.', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				array(
					'name'    => 'social_position_woo_lf',
					'label'   => esc_html__( 'Button Position on Login Form', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => $button_position_options,
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image, // Optional: Add help text if needed.
				),
				array(
					'name'  => 'enable_social_woo_rf',
					'label' => esc_html__( 'Register Form', 'loginpress-pro' ),
					'desc'  => esc_html__( 'Enable to add Social Login on WooCommerce register form.', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				array(
					'name'    => 'social_position_woo_rf',
					'label'   => esc_html__( 'Button Position on Register Form', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => $button_position_options,
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image, // Optional: Add help text if needed.
				),
				array(
					'name'  => 'enable_social_woo_co',
					'label' => esc_html__( 'Checkout Form', 'loginpress-pro' ),
					'desc'  => esc_html__( 'Enable to add Social Login on WooCommerce checkout form.', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				array(
					'name'    => 'social_position_woo_co',
					'label'   => esc_html__( 'Button Position on Checkout Form', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => $button_position_options,
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image, // Optional: Add help text if needed.
				),
				array(
					'name'    => 'enable_captcha_ld',
					'label'   => esc_html__( 'Enable Captcha on', 'loginpress-pro' ),
					'desc'    => esc_html__( 'Choose the form on which you need to apply the selected captcha.', 'loginpress-pro' ) . '<hr>',
					'type'    => 'multicheck',
					'default' => array( 'login_form' => 'login_form' ),
					'options' => array(
						'login_learndash'    => esc_html__( 'Login Form', 'loginpress-pro' ),
						'register_learndash' => esc_html__( 'Register Form', 'loginpress-pro' ),
					),
				),
				array(
					'name'  => 'enable_social_ld_lf',
					'label' => esc_html__( 'Login Form', 'loginpress-pro' ),
					'desc'  => esc_html__( 'Enable to add Social Login on LearnDash login form.', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				array(
					'name'    => 'social_position_ld_lf',
					'label'   => esc_html__( 'Button Position on Login Form', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => $button_position_options,
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image, // Optional: Add help text if needed.
				),
				array(
					'name'  => 'enable_social_ld_rf',
					'label' => esc_html__( 'Register Form', 'loginpress-pro' ),
					'desc'  => esc_html__( 'Enable to add Social Login on LearnDash register form.', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				array(
					'name'    => 'social_position_ld_rf',
					'label'   => esc_html__( 'Button Position on Register Form', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => $button_position_options,
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image, // Optional: Add help text if needed.
				),
				array(
					'name'  => 'enable_social_ld_qf',
					'label' => esc_html__( 'Quiz', 'loginpress-pro' ),
					'desc'  => esc_html__( 'Enable to add Social Login on LearnDash Quiz.', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				array(
					'name'    => 'social_position_ld_qf',
					'label'   => esc_html__( 'Button Position on Quiz', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => array(
						'below' => esc_html__( 'Below (Show below the fields)', 'loginpress-pro' ),
						'above' => esc_html__( 'Above (Show above the fields)', 'loginpress-pro' ),
					),
					'default' => 'below',
					'desc'    => $this->loginpress_social_position_image, // Optional: Add help text if needed.
				),
				array(
					'name'    => 'enable_captcha_llms',
					'label'   => esc_html__( 'Enable Captcha on', 'loginpress-pro' ),
					'desc'    => esc_html__( 'Choose the form on which you need to apply the selected captcha.', 'loginpress-pro' ) . '<hr>',
					'type'    => 'multicheck',
					'default' => array( 'login_form' => 'login_form' ),
					'options' => array(
						'lifter_login_form'        => esc_html__( 'LifterLMS Login Form', 'loginpress-pro' ),
						'lifter_register_form'     => esc_html__( 'LifterLMS Register Form', 'loginpress-pro' ),
						'lifter_lostpassword_form' => esc_html__( 'LifterLMS Lost Password Form', 'loginpress-pro' ),
						'lifter_purchase_form'     => esc_html__( 'LifterLMS Purchase Form', 'loginpress-pro' ),
					),
				),
				array(
					'name'  => 'enable_social_llms_lf',
					'label' => esc_html__( 'Login Form', 'loginpress-pro' ),
					'desc'  => esc_html__( 'Enable to add Social Login on LifterLMS login form.', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				array(
					'name'    => 'social_position_llms_lf',
					'label'   => esc_html__( 'Button Position on Login Form', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => $button_position_options,
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image, // Optional: Add help text if needed.
				),
				array(
					'name'  => 'enable_social_llms_rf',
					'label' => esc_html__( 'Register Form', 'loginpress-pro' ),
					'desc'  => esc_html__( 'Enable to add Social Login on LifterLMS register form.', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				array(
					'name'    => 'social_position_llms_rf',
					'label'   => esc_html__( 'Button Position on Register Form', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => $button_position_options,
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image, // Optional: Add help text if needed.
				),
				array(
					'name'  => 'enable_social_llms_co',
					'label' => esc_html__( 'Purchase Form', 'loginpress-pro' ),
					'desc'  => esc_html__( 'Enable to add Social Login on LifterLMS purchase form.', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				array(
					'name'    => 'social_position_llms_co',
					'label'   => esc_html__( 'Button Position on Purchase Form', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => $button_position_options,
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image, // Optional: Add help text if needed.
				),
				array(
					'name'    => 'enable_captcha_bp',
					'label'   => esc_html__( 'Enable Captcha on', 'loginpress-pro' ),
					'desc'    => esc_html__( 'Choose the form on which you need to apply the selected captcha.', 'loginpress-pro' ) . '<hr>',
					'type'    => 'multicheck',
					'options' => array(
						'register_bp_block' => esc_html__( 'Register Form', 'loginpress-pro' ),
					),
				),
				array(
					'name'  => 'enable_social_login_links_bp',
					'label' => esc_html__( 'Enable Social Login on', 'loginpress-pro' ),
					'desc'  => esc_html__( 'BuddyPress Register Form', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				array(
					'name'    => 'social_position_bp',
					'label'   => esc_html__( 'Button Position on Register Form', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => $button_position_options,
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image,
				),
				array(
					'name'    => 'enable_captcha_bb',
					'label'   => esc_html__( 'Enable Captcha on', 'loginpress-pro' ),
					'desc'    => esc_html__( 'Choose the form on which you need to apply the selected captcha.', 'loginpress-pro' ) . '<hr>',
					'type'    => 'multicheck',
					'options' => array(
						'register_bb_block' => esc_html__( 'Register Form', 'loginpress-pro' ),
					),
				),
				array(
					'name'  => 'enable_social_login_links_bb',
					'label' => esc_html__( 'Enable Social Login on', 'loginpress-pro' ),
					'desc'  => esc_html__( 'BuddyBoss Register Form', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				array(
					'name'    => 'social_position_bb',
					'label'   => esc_html__( 'Button Position on Register Form', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => $button_position_options,
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image,
				),
				array(
					'name'    => 'enable_captcha_edd',
					'label'   => esc_html__( 'Enable Captcha on', 'loginpress-pro' ),
					'desc'    => esc_html__( 'Choose the form on which you need to apply the selected captcha.', 'loginpress-pro' ) . '<hr>',
					'type'    => 'multicheck',
					'options' => array(
						'login_edd_block'    => esc_html__( 'Login Form', 'loginpress-pro' ),
						'register_edd_block' => esc_html__( 'Register Form', 'loginpress-pro' ),
						'checkout_edd_block' => esc_html__( 'Checkout Form', 'loginpress-pro' ),
					),
				),
				array(
					'name'  => 'enable_social_edd_lf',
					'label' => esc_html__( 'Login Form', 'loginpress-pro' ),
					'desc'  => esc_html__( 'Enable to add Social Login on EDD login form.', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				array(
					'name'    => 'social_position_edd_lf',
					'label'   => esc_html__( 'Button Position on Login Form', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => $button_position_options,
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image, // Optional: Add help text if needed.
				),
				array(
					'name'  => 'enable_social_edd_rf',
					'label' => esc_html__( 'Register Form', 'loginpress-pro' ),
					'desc'  => esc_html__( 'Enable to add Social Login on EDD register form.', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				array(
					'name'    => 'social_position_edd_rf',
					'label'   => esc_html__( 'Button Position on Register Form', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => $button_position_options,
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image, // Optional: Add help text if needed.
				),
				array(
					'name'  => 'enable_social_edd_co',
					'label' => esc_html__( 'Checkout Form', 'loginpress-pro' ),
					'desc'  => esc_html__( 'Enable to add Social Login on EDD checkout form.', 'loginpress-pro' ),
					'type'  => 'checkbox',
				),
				array(
					'name'    => 'social_position_edd_co',
					'label'   => esc_html__( 'Button Position on Checkout Form', 'loginpress-pro' ),
					'type'    => 'radio',
					'options' => $button_position_options,
					'default' => 'default',
					'desc'    => $this->loginpress_social_position_image, // Optional: Add help text if needed.
				),
			);

			$_new_tabs = array( 'loginpress_integration_settings' => $_new_tabs );
			return array_merge( $setting_array, $_new_tabs );
		}
	}
}
