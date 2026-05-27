<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * LoginPress BuddyBoss Integration.
 *
 * Handles the integration of LoginPress features with the BuddyBoss platform.
 * This includes social login positioning, captcha integration, and other BuddyBoss-specific features.
 *
 * @package LoginPress-Pro
 * @since 5.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Handles the integration of LoginPress features with the BuddyBoss platform.
 *
 * @since 5.0.0
 */
class LoginPress_Buddyboss_Integration {

	/**
	 * The settings array.
	 *
	 * @var array
	 * @since 5.0.0
	 */
	public $settings;

	/**
	 * Variable that checks for LoginPress settings.
	 *
	 * @var array
	 * @since 5.0.0
	 */
	public $loginpress_settings;

	/**
	 * Variable that contains the position of social login on BuddyBoss forms.
	 *
	 * @var string
	 * @since 5.0.0
	 */
	public $bb_social_position;
	/**
	 * The constructor.
	 *
	 * @since 5.0.0
	 * @return void
	 */
	public function __construct() {
		$this->settings            = get_option( 'loginpress_integration_settings' );
		$this->loginpress_settings = get_option( 'loginpress_captcha_settings' );
		$this->bb_social_position  = isset( $this->settings['social_position_bb'] ) ? $this->settings['social_position_bb'] : 'default';
		$this->loginpress_bb_hooks();
	}

	/**
	 * Register BuddyBoss-related hooks for LoginPress.
	 *
	 * This function binds LoginPress functionality with BuddyBoss by hooking into
	 * relevant actions and filters provided by the BuddyBoss plugin.
	 *
	 * @since 5.0.0
	 * @return void
	 */
	public function loginpress_bb_hooks() {
		$bb_social_register = isset( $this->settings['enable_social_login_links_bb'] ) ? $this->settings['enable_social_login_links_bb'] : '';

		$bb_captcha_register = isset( $this->settings['enable_captcha_bb']['register_bb_block'] ) ? $this->settings['enable_captcha_bb']['register_bb_block'] : false;
		$captchas_enabled    = isset( $this->loginpress_settings['enable_captchas'] ) ? $this->loginpress_settings['enable_captchas'] : 'off';
		$addons              = get_option( 'loginpress_pro_addons' );
		if ( isset( $addons['social-login']['is_active'] ) && $addons['social-login']['is_active'] ) {
			if ( ! class_exists( 'LoginPress_Social' ) ) {
				require_once LOGINPRESS_PRO_ROOT_PATH . '/addons/social-login/social-login.php';
			}

			if ( 'off' !== $bb_social_register && ( 'above' === $this->bb_social_position || 'above_separator' === $this->bb_social_position ) ) {
				add_action( 'bp_before_register_page', array( $this, 'loginpress_social_output_above' ) );
			} elseif ( 'off' !== $bb_social_register && ( 'default' === $this->bb_social_position || 'below' === $this->bb_social_position ) ) {
				add_action( 'bp_after_register_page', array( $this, 'loginpress_social_output_below' ) );
			}
		}

		if ( 'off' !== $captchas_enabled ) {
			$captchas_type = isset( $this->loginpress_settings['captchas_type'] ) ? $this->loginpress_settings['captchas_type'] : 'type_recaptcha';
			if ( 'type_cloudflare' === $captchas_type ) {

				/* Cloudflare CAPTCHA Settings */
				$cf_site_key   = isset( $this->loginpress_settings['site_key_cf'] ) ? $this->loginpress_settings['site_key_cf'] : '';
				$cf_secret_key = isset( $this->loginpress_settings['secret_key_cf'] ) ? $this->loginpress_settings['secret_key_cf'] : '';
				$validated     = isset( $this->loginpress_settings['validate_cf'] ) && 'on' === $this->loginpress_settings['validate_cf'] ? true : false;
				if ( ! empty( $cf_site_key ) && ! empty( $cf_secret_key ) && $validated ) {
					if ( $bb_captcha_register ) {
						// Using high priority (9999) to ensure Turnstile field loads after all other fields.
						// This avoids conflicts with buddyboss fields.
						add_filter( 'bp_before_registration_submit_buttons', array( $this, 'loginpress_add_turnstile_to_bp_register_fields' ), 9999 );
						add_filter( 'bp_signup_validate', array( $this, 'loginpress_bp_register_form_turnstile_enable' ), 10, 3 );
					}
				}
			} elseif ( 'type_recaptcha' === $captchas_type ) {
				/* Add reCAPTCHA on registration form */
				if ( $bb_captcha_register ) {
					add_filter( 'bp_before_registration_submit_buttons', array( $this, 'loginpress_add_recaptcha_to_bp_register' ), 9999 );
				}

				/* Authentication reCAPTCHA on buddyboss registration form */
				if ( ! isset( $_GET['customize_changeset_uuid'] ) && ( $bb_captcha_register ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					add_filter( 'bp_signup_validate', array( $this, 'loginpress_bp_register_form_captcha_enable' ) );
				}
			} elseif ( 'type_hcaptcha' === $captchas_type ) {
				$hcap_site_key   = isset( $this->loginpress_settings['hcaptcha_site_key'] ) ? $this->loginpress_settings['hcaptcha_site_key'] : '';
				$hcap_secret_key = isset( $this->loginpress_settings['hcaptcha_secret_key'] ) ? $this->loginpress_settings['hcaptcha_secret_key'] : '';

				if ( ! empty( $hcap_site_key ) && ! empty( $hcap_secret_key ) && isset( $this->loginpress_settings['hcaptcha_verified'] ) && 'on' === $this->loginpress_settings['hcaptcha_verified'] ) {
					if ( $bb_captcha_register ) {
						add_filter( 'bp_before_registration_submit_buttons', array( $this, 'loginpress_add_hcaptcha_to_bp_register_fields' ), 99 );
						add_filter( 'bp_signup_validate', array( $this, 'loginpress_bp_register_form_hcaptcha_enable' ), 10, 3 );
					}
				}
			}
		}
	}

	/**
	 * Adds social login above the BuddyBoss register fields.
	 *
	 * @since 5.0.0
	 * @return void
	 */
	public function loginpress_social_output_above() {
		$loginpress_social = LoginPress_Social::instance();

		$loginpress_social->loginpress_social_login();

		if ( 'above_separator' === $this->bb_social_position ) {
			/**
			 * Filter the separator text between social login buttons and the default form.
			 *
			 * @since 3.0.0
			 *
			 * @param string $separator_text The text displayed between social login and form. Default 'or'.
			 */
			$separator_text = apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) );
			echo "<span class='social-sep'><span>" . esc_html( $separator_text ) . '</span></span>';
		}
	}

	/**
	 * Adds social login below the BuddyBoss register fields.
	 *
	 * @since 5.0.0
	 * @return void
	 */
	public function loginpress_social_output_below() {
		$loginpress_social = LoginPress_Social::instance();

		if ( 'default' === $this->bb_social_position ) {
			/**
			 * Filter the separator text between social login buttons and the default form.
			 *
			 * @since 3.0.0
			 *
			 * @param string $separator_text The text displayed between social login and form. Default 'or'.
			 */
			$separator_text = apply_filters( 'loginpress_social_login_separator', __( 'or', 'loginpress-pro' ) );
			echo "<span class='social-sep'><span>" . esc_html( $separator_text ) . '</span></span>';
		}
		$loginpress_social->loginpress_social_login();
	}

	/**
	 * Adds turnstile field to BuddyBoss register fields.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function loginpress_add_turnstile_to_bp_register_fields() {

		/* Cloudflare CAPTCHA Settings. */
		$lp_turnstile = LoginPress_Turnstile::instance();
		$lp_turnstile->loginpress_turnstile_field( 'bp' ); // Use correct integration key.
		$lp_turnstile->loginpress_turnstile_script();
	}

	/**
	 * Authenticate turnstile response on BuddyBoss register form.
	 *
	 * @param array $result Current validation status of the form.
	 * @return array Updated validation status of the form.
	 * @since 5.0.0
	 */
	public function loginpress_bp_register_form_turnstile_enable( $result ) {
		global $bp;
		$secret_key = isset( $this->loginpress_settings['secret_key_cf'] ) ? $this->loginpress_settings['secret_key_cf'] : '';
		$has_error  = false;

		if ( ! isset( $_POST['cf-turnstile-response'] ) || empty( sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			bp_core_add_message( __( 'Please complete the Turnstile verification.', 'loginpress-pro' ), 'error' );
			$has_error = true;
		} else {
			$verify_response = wp_remote_post(
				'https://challenges.cloudflare.com/turnstile/v0/siteverify',
				array(
					'body' => array(
						'secret'   => $secret_key,
						'response' => sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
						'remoteip' => sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
					),
				)
			);

			$response_body = wp_remote_retrieve_body( $verify_response );
			$result_data   = json_decode( $response_body, true );

			if ( ! isset( $result_data['success'] ) || empty( $result_data['success'] ) ) {
				$lp_turnstile = LoginPress_Turnstile::instance();
				$error_msg    = $lp_turnstile->loginpress_turnstile_error();
				bp_core_add_message( esc_html( $error_msg ), 'error' );
				$has_error = true;
			}
		}

		if ( $has_error ) {
			$bp->signup->errors['turnstile_blocked'] = 'There was a problem with Turnstile verification.';
		}

		return $result;
	}

	/**
	 * Adds reCAPTCHA field to BuddyBoss register fields.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function loginpress_add_recaptcha_to_bp_register() {
		$lp_recaptcha = LoginPress_Recaptcha::instance();
		$lp_recaptcha->loginpress_recaptcha_field();
		$lp_recaptcha->loginpress_recaptcha_script();
	}

	/**
	 * Enables reCAPTCHA on the BuddyBoss register form.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function loginpress_bp_register_form_captcha_enable() {
		$lp_recaptcha = LoginPress_Recaptcha::instance();
		global $bp;
		$cap_type       = isset( $this->loginpress_settings['recaptcha_type'] ) ? $this->loginpress_settings['recaptcha_type'] : 'v2-robot';
		$cap_permission = isset( $this->loginpress_settings['enable_repatcha'] ) ? $this->loginpress_settings['enable_repatcha'] : 'off';
		$errors         = new WP_Error();
		// Fallback error handler.
		$has_error = false;

		if ( ! isset( $_POST['g-recaptcha-response'] ) || empty( sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			bp_core_add_message( __( 'Please complete the reCAPTCHA verification.', 'loginpress-pro' ), 'error' );
			$has_error = true;
		} elseif ( 'v3' === $cap_type ) {
			$good_score = $this->loginpress_settings['good_score'];
			$score      = $lp_recaptcha->loginpress_v3_recaptcha_verifier();

			if ( $score < $good_score ) {
				bp_core_add_message( __( '<strong>Error:</strong> reCAPTCHA score too low.', 'loginpress-pro' ), 'error' );
				$has_error = true;
			}
		} else {
			$response = $lp_recaptcha->loginpress_recaptcha_verifier();
			if ( ! $response->isSuccess() ) {
				$error_msg = $lp_recaptcha->loginpress_recaptcha_error();
				bp_core_add_message( esc_html( $error_msg ), 'error' );
				$has_error = true;
			}
		}

		if ( $has_error ) {
			// Add fake field error to make BuddyPress re-display the form.
			$bp->signup->errors['recaptcha_blocked'] = 'There was a problem with reCAPTCHA verification.';
		}
	}

	/**
	 * Adds hCaptcha field to BuddyBoss register fields.
	 *
	 * @return void
	 * @since 5.0.0
	 */
	public function loginpress_add_hcaptcha_to_bp_register_fields() {

		$lp_hcaptcha = LoginPress_Hcaptcha::instance();
		$lp_hcaptcha->loginpress_hcaptcha_field();
		$lp_hcaptcha->loginpress_hcaptcha_enqueue( 'bp' );
	}

	/**
	 * Enables hCaptcha on the BuddyBoss register form.
	 *
	 * @param mixed $result The current validation status of the form.
	 * @return mixed The updated validation status of the form.
	 * @since 5.0.0
	 */
	public function loginpress_bp_register_form_hcaptcha_enable( $result ) {
		$lp_hcaptcha = LoginPress_Hcaptcha::instance();
		global $bp;
		$hcap_secret_key = isset( $this->loginpress_settings['hcaptcha_secret_key'] ) ? $this->loginpress_settings['hcaptcha_secret_key'] : '';
		$has_error       = false;

		if ( ! isset( $_POST['h-captcha-response'] ) || empty( sanitize_text_field( wp_unslash( $_POST['h-captcha-response'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			bp_core_add_message( __( 'Please complete the hCaptcha verification.', 'loginpress-pro' ), 'error' );
			$has_error = true;
		} else {
			$response      = $this->lp_verify_hcaptcha( $hcap_secret_key, sanitize_text_field( wp_unslash( $_POST['h-captcha-response'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$response_body = wp_remote_retrieve_body( $response );
			$result_data   = json_decode( $response_body );

			if ( ! $result_data->success ) {
				$error_msg = $lp_hcaptcha->loginpress_hcaptcha_error();
				bp_core_add_message( esc_html( $error_msg ), 'error' );
				$has_error = true;
			}
		}

		if ( $has_error ) {
			$bp->signup->errors['hcaptcha_blocked'] = 'There was a problem with hCaptcha verification.';
		}

		return $result;
	}

	/**
	 * Verify hCaptcha response.
	 *
	 * @param string $hcap_secret_key The hCaptcha secret key.
	 * @param string $hcap_response The hCaptcha response.
	 * @return array|WP_Error The response from hCaptcha API.
	 * @since 5.0.0
	 */
	private function lp_verify_hcaptcha( $hcap_secret_key, $hcap_response ) {
		return wp_remote_post(
			'https://hcaptcha.com/siteverify',
			array(
				'body' => array(
					'secret'   => $hcap_secret_key,
					'response' => sanitize_text_field( $hcap_response ),
					'remoteip' => sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
				),
			)
		);
	}
}

new LoginPress_Buddyboss_Integration();
