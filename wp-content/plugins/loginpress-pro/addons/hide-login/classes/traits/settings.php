<?php
/**
 * LoginPress HideLogin Settings Trait
 *
 * Handles settings and UI functionality for LoginPress HideLogin.
 *
 * @package   LoginPress
 * @subpackage Traits\HideLogin
 * @since 6.1.0
 */

/**
 * Prevent direct access.
 *
 * @package LoginPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! trait_exists( 'LoginPress_HideLogin_Settings' ) ) {
	/**
	 * LoginPress HideLogin Settings Trait
	 *
	 * Handles settings and UI functionality for LoginPress HideLogin.
	 *
	 * @package   LoginPress
	 * @subpackage Traits\HideLogin
	 * @version 6.1.0
	 */
	trait LoginPress_HideLogin_Settings {

		/**
		 * Register the rest routes for hidelogin.
		 *
		 * @since 6.0.0
		 * @return void
		 */
		public function lp_hidelogin_register_routes() {
			// Get HideLogin settings.
			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/hidelogin-settings',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'loginpress_get_hidelogin_settings' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			// Update HideLogin settings.
			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/hidelogin-settings-update',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_update_hidelogin_settings' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			// Reset login slug.
			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/reset-login-slug',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_reset_login_slug' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);
		}

		/**
		 * Get hidelogin settings.
		 *
		 * @since 6.0.0
		 * @return array Settings array.
		 */
		public function loginpress_get_hidelogin_settings() {
			$settings = get_option( 'loginpress_hidelogin', array() );

			// Set defaults if not exists.
			$defaults = array(
				'rename_login_slug'    => '',
				'is_rename_send_email' => 'off',
				'rename_email_send_to' => get_option( 'admin_email' ),
			);

			return wp_parse_args( $settings, $defaults );
		}

		/**
		 * Reset login slug.
		 *
		 * @param WP_REST_Request $request The REST request object.
		 * @since 6.0.0
		 * @return array Response array.
		 */
		public function loginpress_reset_login_slug( WP_REST_Request $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

			$settings                      = get_option( 'loginpress_hidelogin', array() );
			$settings['rename_login_slug'] = '';
			update_option( 'loginpress_hidelogin', $settings );

			return array( 'success' => true );
		}

		/**
		 * Update hidelogin settings.
		 *
		 * @param WP_REST_Request $request The REST request object.
		 * @since 6.0.0
		 * @return array Response array.
		 */
		public function loginpress_update_hidelogin_settings( WP_REST_Request $request ) {
			// Get and sanitize input data.
			$data = $request->get_json_params();

			// Sanitize inputs.
			$settings = array(
				'rename_login_slug'    => $this->sanitize_login_slug( $data['rename_login_slug'] ),
				'is_rename_send_email' => $this->sanitize_checkbox( $data['is_rename_send_email'] ),
				'rename_email_send_to' => $this->sanitize_email( $data['rename_email_send_to'] ),
			);

			// Update option.
			$updated = update_option( 'loginpress_hidelogin', $settings, false );

			// Send email if needed.
			if ( ! empty( $settings['rename_login_slug'] ) && 'on' === $settings['is_rename_send_email'] ) {
				$this->loginpress_send_notify_email();
			}

			return array(
				'success' => true,
				'data'    => $settings,
				'updated' => $updated,
			);
		}

		/**
		 * Reset login slug back to wp-login.php.
		 *
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function loginpress_hidelogin_reset_login_slug() {

			check_ajax_referer( 'loginpress-reset-login-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No cheating, huh!' );
			}
			// Handle request then generate response using WP_Ajax_Response.
			$loginpress_hidelogin = get_option( 'loginpress_hidelogin' );
			$slug                 = isset( $loginpress_hidelogin['rename_login_slug'] ) ? $loginpress_hidelogin['rename_login_slug'] : '';

			$loginpress_hidelogin_new_val = array();

			foreach ( $loginpress_hidelogin as $key => $value ) {

				if ( 'rename_login_slug' === $key ) {
					$value = '';
				}
				$loginpress_hidelogin_new_val[ $key ] = $value;
			}

			update_site_option( 'loginpress_hidelogin', $loginpress_hidelogin_new_val );
			wp_die();
		}

		/**
		 * The loginpress_hidelogin_tab Setting tab for HideLogin.
		 *
		 * @param array $loginpress_tabs Tabs of free version.
		 * @since 3.0.0
		 * @return array $hidelogin_tab HideLogin tab.
		 */
		public function loginpress_hidelogin_tab( $loginpress_tabs ) {

			$_hidelogin_tab = array(
				array(
					'id'         => 'loginpress_hidelogin',
					'title'      => __( 'Hide Login', 'loginpress-pro' ),
					'sub-title'  => __( 'Hide your login page', 'loginpress-pro' ),
					'desc'       => $this->tab_desc(),
					'video_link' => 'FSE2BH_biZg',
				),
			);
			$hidelogin_tab  = array_merge( $loginpress_tabs, $_hidelogin_tab );

			return $hidelogin_tab;
		}

		/**
		 * The loginpress_hidelogin_settings_array Setting Fields for HideLogin.
		 *
		 * @param array $setting_array Settings fields of free version.
		 * @since 3.0.0
		 * @return array $setting_array HideLogin settings fields after merging.
		 */
		public function loginpress_hidelogin_settings_array( $setting_array ) {

			$_hidelogin_settings = array(
				array(
					'name'              => 'rename_login_slug',
					'label'             => __( 'Rename Login Slug', 'loginpress-pro' ),
					'default'           => __( 'mylogin', 'loginpress-pro' ),
					'desc'              => $this->rename_login_slug_desc(),
					'type'              => 'hidelogin',
					'sanitize_callback' => array( $this, 'sanitize_login_slug' ),
				),
				array(
					'name'              => 'is_rename_send_email',
					'label'             => __( 'Send Email', 'loginpress-pro' ),
					'desc'              => $this->is_rename_send_email_desc(),
					'type'              => 'checkbox',
					'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
				),
				array(
					'name'              => 'rename_email_send_to',
					'label'             => __( 'Email Address', 'loginpress-pro' ),
					'default'           => get_option( 'admin_email' ),
					'desc'              => __( 'Use comma (,) to add more than 1 recipients.', 'loginpress-pro' ),
					'type'              => 'email',
					'multiple'          => true,
					'sanitize_callback' => array( $this, 'sanitize_email' ),
				),
			);
			$hidelogin_settings  = array( 'loginpress_hidelogin' => $_hidelogin_settings );
			return( array_merge( $hidelogin_settings, $setting_array ) );
		}

		/**
		 * Checkbox sanitization callback example.
		 *
		 * Sanitization callback for 'checkbox' type controls. This callback sanitizes `$checked`
		 * as a boolean value, either TRUE or FALSE.
		 *
		 * @param bool $checked Whether the checkbox is checked.
		 * @since 3.0.0
		 * @return bool Whether the checkbox is checked.
		 */
		public function sanitize_checkbox( $checked ) {

			// Boolean check.
			return ( ( isset( $checked ) && 'on' === $checked ) ? 'on' : 'off' );
		}

		/**
		 * Sanitize email address.
		 *
		 * @param array $emails emails to be sanitized.
		 * @since 1.1.4
		 * @version 6.1.0
		 * @return string $emails
		 */
		public function sanitize_email( $emails ) {

			$emails = explode( ',', $emails );

			foreach ( $emails as $email => $value ) {
				$emails[ $email ] = sanitize_email( $value );
			}

			$emails = implode( ',', $emails );

			return $emails;
		}

		/**
		 * Sanitize login url slug.
		 *
		 * Only alpha-numeric characters and dashes are allowed
		 * string will transformed to lower-case and spaces will
		 * remove.
		 *
		 * @param string $slug the slug which is chosen.
		 * @since 3.0.0
		 * @return string $slug
		 */
		public function sanitize_login_slug( $slug ) {

			$slug = trim( $slug );
			$slug = preg_replace( '/[^A-Za-z0-9_\.-]/', '', $slug );
			$slug = strtolower( $slug );

			if ( 'wp-admin' === $slug ) {
				$slug = '';
			}

			return $slug;
		}

		/**
		 * The tab_desc description of the tab 'loginpress settings'.
		 *
		 * @since 1.0.0
		 * @return string $html The tab description.
		 */
		public function tab_desc() {

			$html = '<p>';

			if ( ! is_multisite() || is_super_admin() ) {

				/* translators: %1$s: LoginPress Hide-Login addon description in the settings. */
				$html .= sprintf( __( '%1$sThe Hide Login add-on for LoginPress lets you change the login page URL according to your preference. It will give a hard time to spammers who keep hitting your login page, preventing Brute force attacks.%2$s', 'loginpress-pro' ), '<p>', '</p>' );

			} elseif ( is_multisite() && is_super_admin() && is_plugin_active_for_network( LOGINPRESS_HIDE_PLUGIN_BASENAME ) ) {
				// Tab description if multi-site is enabled.
				/* translators: %s: network admin url */
				$html .= sprintf( __( 'To set a networkwide default, go to <a href="%s">Network Settings</a>.', 'loginpress-pro' ), network_admin_url( 'settings.php#whl-page-input' ) );
			}
			$html .= '</p>';

			return $html;
		}

		/**
		 * Displays a text field under the hidelogin tab.
		 *
		 * @param array $args settings field args.
		 * @param array $value settings field value.
		 * @since 1.0.0
		 * @version 6.1.0
		 * @return string html
		 */
		public function loginpress_hidelogin_callback( $args, $value ) {

			$html        = '';
			$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$type        = isset( $args['type'] ) ? $args['type'] : 'text';
			$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';

			$html .= '<div class="loginpress-hidelogin-slug-wrapper">';
			// If permalink structure is defined.
			if ( get_option( 'permalink_structure' ) ) {

				$html .= '<p class="hide-login_site">' . trailingslashit( home_url() ) . '</p>' . sprintf( '<input type="%1$s" class="%2$s-text hidelogin-slug-input" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"%6$s/>', esc_attr( $type ), esc_attr( $size ), esc_attr( $args['section'] ), esc_attr( $args['id'] ), esc_attr( $value ), esc_attr( $placeholder ) );
				$html .= '<input type="hidden" class="hidelogin_slug_hidden" value="' . esc_attr( trailingslashit( home_url() ) . $value ) . '">';

				// If permalink structure is not defined.
			} else {

				$html .= '<p class="hide-login_site">' . trailingslashit( home_url() ) . '?</p>' . sprintf( '<input type="%1$s" class="%2$s-text hidelogin-slug-input" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"%6$s/>', esc_attr( $type ), esc_attr( $size ), esc_attr( $args['section'] ), esc_attr( $args['id'] ), esc_attr( $value ), esc_attr( $placeholder ) );
				$html .= '<input type="hidden" class="hidelogin_slug_hidden" value="' . esc_attr( trailingslashit( home_url() ) . '?' . $value ) . '">';

			}
			$html .= '<div class="copy-email-icon-wrapper hidelogin-copy-code"><span class="hidelogin-tooltip" id="hidelogin-tooltip">Copy to clipboard</span><svg class="hidelogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path></g><defs><clipPath id="clip0_27_294"><rect width="22" height="22" fill="white" transform="matrix(-1 0 0 1 22 0)"></rect></clipPath></defs></svg><span class="hidelogin_slug_copied"></span></div>';
			$html .= '</div>';
			$html .= '<div class="loginpress-hidelogin-cta-wrapper">';
			$html .= '<input type="button" class="button loginpress-hidelogin-slug" value="' . esc_html__( 'Generate Slug (Randomly)', 'loginpress-pro' ) . '" id="loginpress_create_new_hidelogin_slug" />';

			// If Slug is not defined or empty.
			if ( '' !== $this->slug ) {
				$html .= '<input type="button" class="button button-primary" value="' . esc_html__( 'Reset Login Slug', 'loginpress-pro' ) . '" id="loginpress_reset_login_slug" />';
			}
			$html .= '</div>';

			return $html;
		}

		/**
		 * The rename_login_slug_desc description of the field 'rename_login_slug'.
		 *
		 * @since 1.0.0
		 * @return string $html The login slug description on Hide login settings page.
		 */
		public function rename_login_slug_desc() {

			global $pagenow;
			$loginpress_hidelogin = get_option( 'loginpress_hidelogin' );
			$slug                 = isset( $loginpress_hidelogin['rename_login_slug'] ) ? $loginpress_hidelogin['rename_login_slug'] : '';
			$check                = isset( $loginpress_hidelogin['is_rename_send_email'] ) ? $loginpress_hidelogin['is_rename_send_email'] : 'off';
			$html                 = '';

			$get_page             = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$get_settings_updated = isset( $_GET['settings-updated'] ) ? sanitize_text_field( wp_unslash( $_GET['settings-updated'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( ! is_network_admin() && 'admin.php' === $pagenow && 'loginpress-settings' === $get_page && ! empty( $get_settings_updated ) && '' === $slug ) {
				// Translators: Bookmark login page.
				$html .= sprintf( __( 'Your default login page: %1$s Bookmark this page!', 'loginpress-pro' ), '<strong><a href="' . esc_url( home_url( '/wp-login.php' ) ) . '" target="_blank">' . esc_html( home_url( '/wp-login.php' ) ) . '</a></strong>.' );

			} elseif ( '' === $slug ) {
				// Translators: Bookmark login page.
				$html .= sprintf( __( 'Your default login page: %1$s Bookmark this page!', 'loginpress-pro' ), '<strong><a href="' . esc_url( home_url( '/wp-login.php' ) ) . '" target="_blank">' . esc_html( home_url( '/wp-login.php' ) ) . '</a></strong>' );

			} elseif ( ! is_network_admin() && 'admin.php' === $pagenow && 'loginpress-settings' === $get_page && ! empty( $get_settings_updated ) ) {
				// Translators: Bookmark login page.
				$html .= sprintf( __( 'Here is your login page now: %1$s Bookmark this page!', 'loginpress-pro' ), '<strong><a href="' . esc_url( $this->new_login_url() ) . '" target="_blank">' . esc_html( $this->new_login_url() ) . '</a></strong>' );
				if ( 'on' === $check ) {
					$this->loginpress_send_notify_email();
				}
				// If permalink structure is not defined.
			} else {

				$html .= __( 'Rename your wp-login.php', 'loginpress-pro' );

			}

			return $html;
		}

		/**
		 * The rename_email_send_to_default return email.
		 *
		 * @since 1.0.0
		 * @return mixed string|array $email The email/s.
		 */
		public function rename_email_send_to_default() {
			return get_option( 'admin_email' );
		}

		/**
		 * Loginpress_send_notify_email send email.
		 *
		 * @since 1.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function loginpress_send_notify_email() {
			$loginpress_hidelogin = get_option( 'loginpress_hidelogin' );
			$slug                 = isset( $loginpress_hidelogin['rename_login_slug'] ) ? $loginpress_hidelogin['rename_login_slug'] : '';
			$email                = isset( $loginpress_hidelogin['rename_email_send_to'] ) ? $loginpress_hidelogin['rename_email_send_to'] : '';
			$headers              = array( 'Content-Type: text/html; charset=UTF-8' );

			if ( ! empty( $slug ) && ! empty( $email ) ) {

				$home_url = home_url( '/' );
				$message  = '';
				$message .= esc_html__( 'Email Notification from', 'loginpress-pro' ) . ' ' . esc_url( $home_url ) . '<br />';
				$message .= esc_html__( 'Your New Login Slug is', 'loginpress-pro' ) . ' ' . esc_url( $home_url . $slug ) . '<br />';
				$message .= esc_html__( 'Powered by LoginPress', 'loginpress-pro' );

				/**
				 * Use filter `loginpress_hide_login_email_notification` for return the hide login email notification.
				 *
				 * @param string $message default email notification string.
				 * @param string $slug Newly created slug.
				 * @since 1.1.5
				 */
				$email_body = apply_filters( 'loginpress_hide_login_email_notification', $message, $slug );
				// Escape JS.
				$email_body = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $email_body );

				/**
				 * Use filter `loginpress_hide_login_email_subject` for return the hide login email subject.
				 *
				 * @param string default email subject string.
				 * @since 1.1.5
				 */
				$subject = apply_filters( 'loginpress_hide_login_email_subject', esc_html__( 'Rename wp-login.php by LoginPress', 'loginpress-pro' ) );
				// Escape JS.
				$subject = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $subject );

				wp_mail( trim( $email ), $subject, $email_body, $headers );
			}
		}

		/**
		 * The is_rename_send_email_desc description of the field 'is_rename_send_email'.
		 *
		 * @since 1.0.0
		 * @return string $html The send email description.
		 */
		public function is_rename_send_email_desc() {

			$loginpress_hidelogin = get_option( 'loginpress_hidelogin' );
			$is_send_email        = isset( $loginpress_hidelogin['is_rename_send_email'] ) ? $loginpress_hidelogin['is_rename_send_email'] : '';
			$email_send           = isset( $loginpress_hidelogin['rename_email_send_to'] ) ? $loginpress_hidelogin['rename_email_send_to'] : '';

			$html = '';
			if ( 'off' !== $is_send_email && ! empty( $email_send ) ) {

				$html .= esc_html__( 'Email will be sent to the address defined below.', 'loginpress-pro' );

			} elseif ( 'off' !== $is_send_email && empty( $email_send ) ) {

				$html .= esc_html__( 'Email will be sent to the address(s) defined below.', 'loginpress-pro' );

			} else {

				$html .= esc_html__( 'Send email after changing the wp-login.php slug?', 'loginpress-pro' );

			}

			return $html;
		}

		/**
		 * The rename_email_send_to_desc description of the field 'rename_email_send_to'.
		 *
		 * @since 1.0.0
		 * @return string Email sent or Write a email description.
		 */
		public function rename_email_send_to_desc() {

			$html                 = '';
			$loginpress_hidelogin = get_option( 'loginpress_hidelogin' );
			$email_send           = isset( $loginpress_hidelogin['rename_email_send_to'] ) ? $loginpress_hidelogin['rename_email_send_to'] : '';

			if ( '' !== $email_send ) {

				$html .= esc_html__( 'Email sent.', 'loginpress-pro' );

			} else {

				$html .= esc_html__( 'Write a Email	Address where send the New generated URL', 'loginpress-pro' );

			}

			return $html;
		}

		/**
		 * Method to enqueue admin scripts.
		 *
		 * @param string $hook the current admin page.
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function loginpress_hidelogin_admin_action_scripts( $hook ) {

			if ( 'toplevel_page_loginpress-settings' === $hook ) {

				wp_register_style( 'loginpress-admin-hidelogin', plugins_url( '../assets/css/style.css', __DIR__ ), array(), LOGINPRESS_PRO_VERSION );
				wp_enqueue_style( 'loginpress-admin-hidelogin' );
			}
		}
	}
}
