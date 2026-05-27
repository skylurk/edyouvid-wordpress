<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Customizer Sections for LoginPress Pro.
 *
 * This class handles the customizer integration for LoginPress Pro features including
 * custom fonts, Google Fonts, CAPTCHA settings, and other login page customizations.
 *
 * @package LoginPress Pro
 * @since 1.0.0
 * @version 6.1.0
 */
class LoginPress_Pro_Entities {

	/**
	 * Class constructor.
	 *
	 * Initializes the class and sets up hooks for customizer integration.
	 *
	 * @return void
	 * @since 1.0.0
	 * @version 6.1.0
	 */
	public function __construct() {

		$this->hooks();
	}

	/**
	 * Hook into actions and filters.
	 *
	 * Sets up WordPress hooks for custom font loading and customizer registration.
	 *
	 * @return void
	 * @since 1.0.0
	 * @version 6.1.0
	 */
	public function hooks() {
		add_action( 'login_head', array( $this, 'loginpress_load_custom_font' ) );
		add_action( 'customize_register', array( $this, 'customize_pro_login_panel' ) );
	}

	/**
	 * Load the custom font on login page.
	 *
	 * @return void
	 * @since 6.0.0
	 */
	public function loginpress_load_custom_font() {
		$options = get_option( 'loginpress_customization' );

		// Early bail if no custom font is set.
		$custom_font = isset( $options['custom_font'] ) ? trim( $options['custom_font'] ) : '';

		if ( ! $custom_font ) {
			return;
		}
		$custom_font = str_replace( ' ', '%20', $custom_font );

		// Validate URL.
		if ( ! filter_var( $custom_font, FILTER_VALIDATE_URL ) ) {
			return;
		}
		// Check for valid font extensions.
		if ( ! preg_match( '/\.(woff2?|woff|ttf|otf)$/i', $custom_font, $match ) ) {
			return;
		}
		// Generate font family name from filename (better approach).
		$path_parts  = pathinfo( $custom_font );
		$base_name   = $path_parts['filename'];
		$base_name   = preg_replace( '/[-_\s](regular|normal|bold|light|medium|black|thin|extralight|semibold|extrabold|italic|oblique)$/i', '', $base_name );
		$base_name   = str_replace( '%20', ' ', $base_name );
		$font_family = sanitize_text_field( $base_name );

		// Map extensions to CSS format().
		$format_map = array(
			'woff2' => 'woff2',
			'woff'  => 'woff',
			'ttf'   => 'truetype',
			'otf'   => 'opentype',
			'eot'   => 'embedded-opentype',
		);
		$ext        = strtolower( $match[1] );
		$format     = isset( $format_map[ $ext ] ) ? $format_map[ $ext ] : 'truetype';

		printf(
			'<style>
				@font-face {
					font-family: "%1$s";
					src: url("%2$s") format("%3$s");
					font-weight: normal;
					font-style: normal;
					font-display: swap;
				}
				body.login {
					font-family: "%1$s", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
				}
				.wp-core-ui #login .wp-generate-pw,
				.login input[type="submit"], 
				.login form .input, 
				.login input[type="text"],
				.login input[type="password"] {
					font-family: "%1$s", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
				}
			</style>',
			esc_html( $font_family ),
			esc_url( $custom_font ),
			esc_html( $format )
		);
	}

	/**
	 * Register plugin settings Panel in WP Customizer.
	 *
	 * @param WP_Customize_Manager $wp_customize The Customizer object.
	 * @return void
	 * @since 1.0.0
	 * @version 6.1.0
	 */
	public function customize_pro_login_panel( $wp_customize ) {

		$loginpress_captcha_settings = get_option( 'loginpress_captcha_settings' );
		$captchas_enabled            = isset( $loginpress_captcha_settings['enable_captchas'] ) ? $loginpress_captcha_settings['enable_captchas'] : 'off';
		if ( 'off' !== $captchas_enabled ) {
			$captchas_type = isset( $loginpress_captcha_settings['captchas_type'] ) ? $loginpress_captcha_settings['captchas_type'] : 'type_recaptcha';
		}

		/**
		 * Section for Google reCAPTCHA.
		 *
		 * @since 1.0.0
		 */
		$wp_customize->add_section(
			'customize_recaptcha',
			array(
				'title'    => __( 'CAPTCHAs', 'loginpress-pro' ),
				'priority' => 24,
				'panel'    => 'loginpress_panel',
			)
		);

		if ( 'off' !== $captchas_enabled && isset( $captchas_type ) && 'type_recaptcha' === $captchas_type ) {
			$wp_customize->add_setting(
				'loginpress_customization[recaptcha_error_message]',
				array(
					'default'           => __( '<strong>ERROR:</strong> Please verify reCAPTCHA', 'loginpress-pro' ),
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'wp_kses_post',
				)
			);

			$wp_customize->add_control(
				'loginpress_customization[recaptcha_error_message]',
				array(
					'label'    => __( 'reCAPTCHA Error Message:', 'loginpress-pro' ),
					'section'  => 'customize_recaptcha',
					'priority' => 5,
					'settings' => 'loginpress_customization[recaptcha_error_message]',
				)
			);
			/**
			 * Select Scale Size.
			 */
			$wp_customize->add_setting(
				'loginpress_customization[recaptcha_size]',
				array(
					'default'    => '1',
					'capability' => 'edit_theme_options',
					'transport'  => 'postMessage',
					'type'       => 'option',
				)
			);
			$wp_customize->add_control(
				'loginpress_customization[recaptcha_size]',
				array(
					'label'       => __( 'Select reCAPTCHA size:', 'loginpress-pro' ),
					'section'     => 'customize_recaptcha',
					'priority'    => 10,
					'settings'    => 'loginpress_customization[recaptcha_size]',
					'type'        => 'select',
					'description' => __( 'Size is only apply on "V2-I\'m not robot" reCAPTCHA type.', 'loginpress-pro' ),
					'choices'     => array(
						'.1' => '10%',
						'.2' => '20%',
						'.3' => '30%',
						'.4' => '40%',
						'.5' => '50%',
						'.6' => '60%',
						'.7' => '70%',
						'.8' => '80%',
						'.9' => '90%',
						'1'  => '100%',
					),
				)
			);
		} elseif ( 'off' !== $captchas_enabled && isset( $captchas_type ) && 'type_hcaptcha' === $captchas_type ) {
			$wp_customize->add_setting(
				'loginpress_customization[hcaptcha_error_message]',
				array(
					'default'           => __( '<strong>ERROR:</strong> Please verify hCaptcha', 'loginpress-pro' ),
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'wp_kses_post',
				)
			);

			$wp_customize->add_control(
				'loginpress_customization[hcaptcha_error_message]',
				array(
					'label'    => __( 'hCaptcha Error Message:', 'loginpress-pro' ),
					'section'  => 'customize_recaptcha',
					'priority' => 5,
					'settings' => 'loginpress_customization[hcaptcha_error_message]',
				)
			);

		} elseif ( 'off' !== $captchas_enabled && isset( $captchas_type ) && 'type_cloudflare' === $captchas_type ) {
			$wp_customize->add_setting(
				'loginpress_customization[turnstile_error_message]',
				array(
					'default'           => __( '<strong>ERROR:</strong> Captcha verification failed. Please try again.', 'loginpress-pro' ),
					'type'              => 'option',
					'capability'        => 'manage_options',
					'transport'         => 'postMessage',
					'sanitize_callback' => 'wp_kses_post',
				)
			);

			$wp_customize->add_control(
				'loginpress_customization[turnstile_error_message]',
				array(
					'label'    => __( 'CloudFlare Error Message:', 'loginpress-pro' ),
					'section'  => 'customize_recaptcha',
					'priority' => 5,
					'settings' => 'loginpress_customization[turnstile_error_message]',
				)
			);

		} else {
			$wp_customize->add_setting(
				'loginpress_customization[captcha_disabled_notice]',
				array(
					'type'       => 'option',
					'capability' => 'manage_options',
					'transport'  => 'postMessage',
				)
			);

			$wp_customize->add_control(
				new LoginPress_Group_Control(
					$wp_customize,
					'loginpress_customization[captcha_disabled_notice]',
					array(
						'settings'  => 'loginpress_customization[captcha_disabled_notice]',
						'label'     => __( 'Captcha not Enabled', 'loginpress-pro' ),
						'section'   => 'customize_recaptcha',
						'type'      => 'group',
						'info_text' => __( 'Please Enable any of the provided captchas to change the Captcha Error Message', 'loginpress-pro' ),
						'priority'  => 5,
					)
				)
			);
		}

		/**
		 * Section for Google Fonts.
		 *
		 * @since 2.0.0
		 */
		$wp_customize->add_section(
			'lpcustomize_google_font',
			array(
				'title'    => __( 'Google Fonts', 'loginpress-pro' ),
				'priority' => 2,
				'panel'    => 'loginpress_panel',
			)
		);

		// Add a Google Font control.
		require_once LOGINPRESS_PRO_ROOT_PATH . '/classes/loginpress-google-font.php';
		$wp_customize->add_setting(
			'loginpress_customization[google_font]',
			array(
				'default'    => '',
				'type'       => 'option',
				'capability' => 'manage_options',
				'transport'  => 'postMessage',
			)
		);
		$wp_customize->add_control(
			new LoginPress_Google_Fonts(
				$wp_customize,
				'loginpress_customization[google_font]',
				array(
					'label'    => __( 'Select Google Font', 'loginpress-pro' ),
					'section'  => 'lpcustomize_google_font',
					'settings' => 'loginpress_customization[google_font]',
					'priority' => 20,
				)
			)
		);

		/**
		 * Add a Custom Font setting.
		 *
		 * @since 6.0.0
		 */
		$wp_customize->add_setting(
			'loginpress_customization[custom_font]',
			array(
				'default'           => '',
				'type'              => 'option',
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'sanitize_callback' => array( $this, 'loginpress_custom_font_sanitize' ),
			)
		);

		$wp_customize->add_control(
			'loginpress_customization[custom_font]',
			array(
				'label'       => __( 'Custom Font URL (.woff/.woff2/.ttf/.otf)', 'loginpress-pro' ),
				'description' => __( 'Enter the URL of a custom font file (.woff, .woff2, .ttf, or .otf). Note: Custom fonts take priority over Google fonts.', 'loginpress-pro' ),
				'section'     => 'lpcustomize_google_font',
				'settings'    => 'loginpress_customization[custom_font]',
				'type'        => 'text',
				'priority'    => 30,
			)
		);

		$wp_customize->add_setting(
			'loginpress_customization[reset_hint_text]',
			array(
				'default'           => __( 'Hint: The password should be at least twelve characters long. To make it stronger, use upper and lower case letters, numbers, and symbols like ! " ? $ % ^ &amp; ).', 'loginpress-pro' ),
				'capability'        => 'manage_options',
				'transport'         => 'postMessage',
				'type'              => 'option',
				'sanitize_callback' => 'wp_kses_post',
			)
		);

		$wp_customize->add_control(
			'loginpress_customization[reset_hint_text]',
			array(
				'label'       => __( 'Reset Password Hint:', 'loginpress-pro' ),
				'section'     => 'section_welcome',
				'priority'    => 32,
				'settings'    => 'loginpress_customization[reset_hint_text]',
				'type'        => 'textarea',
				'description' => __( 'You can change the Hint text that is comes on reset password page.', 'loginpress-pro' ),
			)
		);
	}

	/**
	 * Sanitize custom font URL.
	 *
	 * @param string $input The font URL input.
	 * @return string Sanitized URL or empty string if invalid.
	 * @since 6.0.0
	 */
	public function loginpress_custom_font_sanitize( $input ) {
		// Trim whitespace.
		$input = trim( $input );

		// Return empty if no input.
		if ( empty( $input ) ) {
			return '';
		}

		// Validate URL format.
		if ( ! filter_var( $input, FILTER_VALIDATE_URL ) ) {
			return '';
		}

		// Check for allowed file extensions only.
		if ( ! preg_match( '/\.(woff2?|woff|ttf|otf)$/i', $input ) ) {
			return '';
		}

		// Additional security: Check for suspicious patterns.
		$suspicious_patterns = array(
			'/javascript:/i',
			'/data:/i',
			'/vbscript:/i',
			'/<script/i',
			'/onload=/i',
			'/onerror=/i',
		);

		foreach ( $suspicious_patterns as $pattern ) {
			if ( preg_match( $pattern, $input ) ) {
				return '';
			}
		}

		// Sanitize the URL.
		return esc_url_raw( $input );
	}
}
