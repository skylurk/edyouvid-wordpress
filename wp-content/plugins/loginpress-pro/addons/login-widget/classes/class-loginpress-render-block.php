<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * For Addon: LoginPress - Login Widget
 * Description: This Gutenberg Block is created for Login widget which allows you to login from front end using Gutenberg.
 *
 * @package LoginPress
 * @author WPBrigade
 * @since 4.0.0
 * @version 6.1.0
 */

// Include utility functions.
require_once plugin_dir_path( __FILE__ ) . 'class-loginpress-widget-utilities.php';

/**
 * LoginPress Widget Block class.
 *
 * @since 4.0.0
 * @version 6.1.0
 */
class LoginPress_Widget_Block {

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 * @version 6.1.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'loginpress_register_block' ) );
	}
	/**
	 * Registers the LoginPress block editor script and block type.
	 *
	 * @since 4.0.0
	 * @version 6.1.0
	 * @return void
	 */
	public function loginpress_register_block() {
		// Register the block editor script.
		wp_register_script(
			'loginpress-block-editor',
			plugins_url( 'blocks/block.js', __DIR__ ), // Fixes incorrect URL path.
			array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n' ),
			filemtime( dirname( __DIR__ ) . '/blocks/block.js' ),
			true
		);

		// Register the block type and specify render_callback for dynamic content.
		register_block_type(
			'loginpress/login-widget',
			array(
				'editor_script'   => 'loginpress-block-editor',
				'render_callback' => array( $this, 'loginpress_render_login_block' ),
				'attributes'      => $this->loginpress_get_block_attributes(),
			)
		);
	}
	/**
	 * Registers block attributes.
	 *
	 * @since 4.0.0
	 * @version 6.1.0
	 * @return array Block attributes array.
	 */
	private function loginpress_get_block_attributes() {
		return array(
			'loggedInTitle'        => array(
				'type'    => 'string',
				// translators: Welcome user.
				'default' => __( 'Welcome %username%', 'loginpress-pro' ),
			),
			'loggedOutTitle'       => array(
				'type'    => 'string',
				'default' => __( 'Login', 'loginpress-pro' ),
			),
			'loggedOutLinks'       => array(
				'type'    => 'string',
				'default' => '',
			),
			'showLostPasswordLink' => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'lostPasswordText'     => array(
				'type'    => 'string',
				'default' => __( 'Lost your password?', 'loginpress-pro' ),
			),
			'showRegisterLink'     => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'registrationText'     => array(
				'type'    => 'string',
				'default' => __( 'Register', 'loginpress-pro' ),
			),
			'showRememberMe'       => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'socialLogin'          => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'loginRedirectUrl'     => array(
				'type'    => 'string',
				'default' => '',
			),
			'loggedInLinks'        => array(
				'type'    => 'string',
				'default' => "Dashboard | %admin_url%\nProfile | %admin_url%/profile.php\nLogout | %logout_url%",
			),
			'showAvatar'           => array(
				'type'    => 'boolean',
				'default' => true,
			),
			'avatarSize'           => array(
				'type'    => 'number',
				'default' => 38,
			),
			'logoutRedirectUrl'    => array(
				'type'    => 'string',
				'default' => '',
			),
			'errorBgColor'         => array(
				'type'    => 'string',
				'default' => '#fbb1b7',
			),
			'errorTextColor'       => array(
				'type'    => 'string',
				'default' => '#ae121e',
			),
		);
	}
	/**
	 * Renders the login block.
	 *
	 * @param array $attributes Block attributes.
	 * @since 4.0.0
	 * @version 6.1.0
	 * @return string Block HTML output.
	 */
	public function loginpress_render_login_block( $attributes ) {
		$loginpress_setting          = get_option( 'loginpress_social_logins' );
		$loginpress_general_settings = get_option( 'loginpress_setting' );
		$additional_class            = isset( $attributes['className'] ) ? $attributes['className'] : '';
		if ( class_exists( 'LoginPress_Attempts' ) ) {
			$llla = new LoginPress_Attempts();
			if ( $llla->llla_time() ) {
				return;
			}
		}
		$output = '';
		update_option( 'loginpress_block_attributes', $attributes );

		// Check if the user is logged in.
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();

			// Replace placeholders in the logged-in title with user details.
			$logged_in_title = LoginPress_Widget_Utilities::replace_user_tags( $attributes['loggedInTitle'], $user );

			$output .= '<h2 class="widget-title">' . esc_html( $logged_in_title ) . '</h2>';

			// Display avatar if the setting is enabled.
			if ( $attributes['showAvatar'] ) {
				$output .= '<div class="avatar_container">' . get_avatar( $user->ID, $attributes['avatarSize'] ) . '</div>';
			}
			$output .= '<ul class="pagenav loginpress_widget_links">'; // Open the list container.

			$logged_in_links = explode( "\n", $attributes['loggedInLinks'] ); // Split the user input by newlines.

			foreach ( $logged_in_links as $link ) {
				// Split each line into its components: Text, HREF, Capability.
				$link_parts = explode( '|', $link );

				if ( count( $link_parts ) >= 2 ) { // Ensure we have at least Text and HREF.
					$text       = trim( $link_parts[0] );
					$href       = trim( $link_parts[1] );
					$capability = isset( $link_parts[2] ) ? trim( $link_parts[2] ) : ''; // Capability is optional.

					// Replace placeholders with actual values.
					$href = str_replace( '%admin_url%', untrailingslashit( admin_url() ), $href );
					$href = str_replace( '%logout_url%', untrailingslashit( wp_logout_url( get_permalink() ) ), $href );

					// Determine the class based on the text.
					$class = '';
					if ( false !== stripos( $text, 'dashboard' ) ) {
						$class = 'dashboard-link';
					} elseif ( false !== stripos( $text, 'profile' ) ) {
						$class = 'profile-link';
					} elseif ( false !== stripos( $text, 'logout' ) ) {
						$class = 'logout-link';
					} else {
						$class = 'custom-link'; // Fallback for any custom links.
					}

					// Check capability if provided.
					if ( empty( $capability ) || current_user_can( $capability ) ) {
						$output .= '<li class="' . esc_attr( $class ) . '"><a href="' . esc_url( $href ) . '">' . esc_html( $text ) . '</a></li>';
					}
				}
			}

			$output .= '</ul>'; // Close the list container.

		} else {
			$enable_reg_pass_field    = isset( $loginpress_general_settings['enable_reg_pass_field'] ) ? $loginpress_general_settings['enable_reg_pass_field'] : 'off';
			$enable_password_strength = isset( $loginpress_general_settings['enable_pass_strength'] ) ? $loginpress_general_settings['enable_pass_strength'] : 'off';
			$enable_pass_strength     = isset( $loginpress_general_settings['enable_pass_strength_forms'] ) ? $loginpress_general_settings['enable_pass_strength_forms'] : 'off';
			$register                 = isset( $enable_pass_strength['register'] ) ? $enable_pass_strength['register'] : false;
			$strength_meter_enable    = isset( $loginpress_general_settings['password_strength_meter'] ) ? $loginpress_general_settings['password_strength_meter'] : 'off';
			$lp_widget                = new LoginPress_Login_Widget();
			// Render the login form for logged-out users.
			$output .= '<div id="loginpress-login-widget" class="widget loginpress-login-widget ' . esc_attr( $additional_class ) . '">';
			$output .= '<h2 class="widget-title">' . esc_html( $attributes['loggedOutTitle'] ) . '</h2>';

			// Render the login form.
			$output .= '<form  id="loginform" name="loginform" class="loginpress-login-widget" action="' . esc_url( wp_login_url() ) . '" method="post">';
			$output .= '<p class="login-username">
                        <label for="user_login">' . esc_html__( 'Username or Email Address', 'loginpress-pro' ) . '</label>
                        <input type="text" name="log" id="user_login" autocomplete="username" class="input">
                    </p>';
			$output .= '<p class="login-password">
                        <label for="user_pass">' . esc_html__( 'Password', 'loginpress-pro' ) . '</label>
                        <input type="password" name="pwd" id="user_pass" autocomplete="current-password" class="input">
                    </p>';
			// Capture the output of the do_action for the Turnstile field.
			ob_start();
			do_action( 'loginpress_after_login_form_widget' ); // Insert Turnstile field.
			$output .= ob_get_clean(); // Append Turnstile output to the $output string.
			if ( ! empty( $attributes['showRememberMe'] ) ) {
				$checked = ( isset( $loginpress_general_settings['auto_remember_me'] ) && 'on' === $loginpress_general_settings['auto_remember_me'] ) ? 'checked' : '';
				$output .= sprintf( '<p class="login-remember"><label><input name="rememberme" type="checkbox" id="rememberme" value="forever" %s> %s</label></p>', $checked, esc_html__( 'Remember Me', 'loginpress-pro' ) );
			}
			$redirect_url = ! empty( $attributes['loginRedirectUrl'] ) ? $attributes['loginRedirectUrl'] : get_permalink();
			$output      .= '<p class="login-submit">
						<input type="submit" name="wp-submit" id="wp-submit-login" class="button button-primary wp-element-button" value="' . esc_attr__( 'Login →', 'loginpress-pro' ) . '">
						<input type="hidden" name="redirect_to" value="' . esc_url( $redirect_url ) . '">
					</p>';
			if ( isset( $loginpress_setting['enable_social_login_links']['login'] ) && $loginpress_setting['enable_social_login_links']['login'] ) {
				if ( class_exists( 'LoginPress_Social' ) && true === apply_filters( 'loginpress_social_widget', true ) ) {
					// Instantiate the LoginPress_Login_Widget class.

					$output .= $lp_widget->loginpress_social_login(); // Output the social login HTML.
				}
			}

			$output .= '</form>';

			// Render the registration form (hidden by default).
			$output .= '<form id="registerform" class="loginpress-register-widget" style="display:none;" action="' . esc_url( wp_registration_url() ) . '" method="post">';
			$output .= '<p class="login-username">
						<label for="user_login">' . esc_html__( 'Username', 'loginpress-pro' ) . '</label>
						<input type="text" name="user_login" id="user_name" class="input">
					</p>';
			$output .= '<p class="login-email">
						<label for="user_email">' . esc_html__( 'Email Address', 'loginpress-pro' ) . '</label>
						<input type="email" name="user_email" id="user_email" class="input">
					</p>';
			if ( isset( $loginpress_general_settings['enable_reg_pass_field'] ) && 'on' === $loginpress_general_settings['enable_reg_pass_field'] ) {
				$output .= '<p class="login-password">
					<label for="user_pass">' . esc_html__( 'Password', 'loginpress-pro' ) . '</label>
					<input type="password" name="user_pass" id="user_pass" class="input">
				</p>';
				$output .= '<p class="login-confirm-password">
							<label for="user_confirm_pass">' . esc_html__( 'Confirm Password', 'loginpress-pro' ) . '</label>
							<input type="password" name="user_confirm_pass" id="user_confirm_pass" class="input">
						</p>';
			}
			ob_start();
			do_action( 'loginpress_after_reg_form_widget' ); // Insert Turnstile field.
			if ( 'on' === $strength_meter_enable && $register ) {
				?>
			<span id="pass-strength-result" style=" padding: 3px 15px; width:100%; display:block;"></span> 
				<?php
			}
			if ( 'on' === $enable_password_strength && $register && 'on' === $enable_reg_pass_field ) {
				?>
				<p class="hint-custom-reg" style="padding: 5px;">
					<?php echo LoginPress_Password_Strength::loginpress_hint_creator(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</p>
				<?php
			}
			$output .= ob_get_clean(); // Append Turnstile output to the $output string.
			$output .= '<p class="register-submit" style="padding-top: 10px;">
						<input type="submit" name="wp-submit" id="wp-submit-register" class="button button-primary wp-element-button" value="' . esc_attr__( 'Register →', 'loginpress-pro' ) . '">
					</p>';
			if ( isset( $loginpress_setting['enable_social_login_links']['register'] ) && $loginpress_setting['enable_social_login_links']['register'] ) {
				if ( class_exists( 'LoginPress_Social' ) && true === apply_filters( 'loginpress_social_widget', true ) ) {
					// Instantiate the LoginPress_Login_Widget class.

					$output .= $lp_widget->loginpress_social_login(); // Output the social login HTML.
				}
			}
			$output .= '</form>';

			// Render the lost password form (hidden by default).
			$output .= '<form id="lostpasswordform" class="loginpress-lost-password-widget" style="display:none;" action="' . esc_url( wp_lostpassword_url() ) . '" method="post">';

			// Form title and description.
			$output .= '<p>' . esc_html__( 'Lost your password? Please enter your username or email address. You will receive a link to create a new password via email.', 'loginpress-pro' ) . '</p>';

			// Username or email input field.
			$output .= '<p class="login-username-or-email">
						<label for="user_login">' . esc_html__( 'Username or Email Address', 'loginpress-pro' ) . '</label>
						<input type="text" name="user_login" id="username" class="input">
					</p>';
			ob_start();
			do_action( 'loginpress_after_lost_password_form_widget' ); // Insert Turnstile field.
			$output .= ob_get_clean(); // Append Turnstile output to the $output string.

			// Submit button for password reset.
			$output .= '<p class="login-submit" style="padding-top: 10px;">
						<input type="submit" name="wp-submit" id="wp-submit-lostpass" class="button button-primary wp-element-button" value="' . esc_attr__( 'Reset Password →', 'loginpress-pro' ) . '">
					</p>';

			$output .= '</form>';

			// Add links for toggling between forms.
			$output .= '<ul class="pagenav loginpress_widget_links">';
			$output .= '<li class="login-link" style="display:none;"><a href="#" id="show_login">' . esc_html__( 'Login', 'loginpress-pro' ) . '</a></li>';
			$output .= '</ul>';
			if ( $attributes['showLostPasswordLink'] || $attributes['showRegisterLink'] ) {
				$output .= '<ul class="pagenav loginpress_widget_links">';

				// Registration Link.
				if ( $attributes['showRegisterLink'] && get_option( 'users_can_register' ) ) {
					$output .= '<li class="register-link"><a href="#" id="show_register">' . esc_html( $attributes['registrationText'] ) . '</a></li>';
				}

				// Lost Password Link.
				if ( $attributes['showLostPasswordLink'] ) {
					$output .= '<li class="lost_password-link"><a href="' . esc_url( wp_lostpassword_url() ) . '">' . esc_html( $attributes['lostPasswordText'] ) . '</a></li>';
				}

				// Add user-provided logged-out links.
				if ( isset( $attributes['loggedOutLinks'] ) ) {
					$logged_out_links = explode( "\n", $attributes['loggedOutLinks'] ); // Split the input by newlines.
					$login_widget     = new LoginPress_Widget();
					foreach ( $logged_out_links as $link ) {
						// Split each line into its components: Text and HREF.
						$link_parts = explode( '|', $link );

						if ( count( $link_parts ) >= 2 ) { // Ensure we have at least Text and HREF.
							$text = trim( $link_parts[0] );
							$href = trim( $link_parts[1] );

							// Replace placeholders with actual values, if necessary.
							$href = str_replace( '%site_url%', site_url(), $href );

							$output .= '<li class="custom-logged-out-link"><a href="' . esc_url( $login_widget->patch_string( $href ) ) . '">' . esc_html( $text ) . '</a></li>';
						}
					}
				}

				$output .= '</ul>';
			}
		}
		$output .= '</div>';

		return $output;
	}
}

new LoginPress_Widget_Block();
