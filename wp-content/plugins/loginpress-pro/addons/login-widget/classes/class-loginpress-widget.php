<?php
/**
 * LoginPress Widget Class.
 *
 * @package   LoginPress
 * @subpackage Widget
 * @since     1.0.0
 * @version   6.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Include utility functions.
require_once plugin_dir_path( __FILE__ ) . 'class-loginpress-widget-utilities.php';

/**
 * LoginPress Widget class.
 *
 * @package   LoginPress
 * @subpackage Widget
 * @since     1.0.0
 * @version   6.1.0
 * @extends   WP_Widget
 */
class LoginPress_Widget extends WP_Widget {

	/**
	 * Widget instance data.
	 *
	 * @since 1.0.0
	 * @var array Widget instance data.
	 */
	private $instance = array();

	/**
	 * Current user object.
	 *
	 * @since 1.0.0
	 * @var WP_User|null Current user object.
	 */
	private $user = null;

	/**
	 * Widget choices configuration.
	 *
	 * @since 1.0.0
	 * @var array Widget choices configuration array.
	 */
	private $choices = array();

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @version 6.1.0
	 * @return void
	 */
	public function __construct() {

		/* Widget settings. */
		$widget_meta = array(
			'classname'   => 'loginpress-login-widget',
			'description' => __( 'Displays a login widget in the sidebar.', 'loginpress-pro' ),
		);

		/* Create the widget. */
		parent::__construct( 'loginpress-login-widget', __( 'LoginPress: Login Widget (Classic)', 'loginpress-pro' ), $widget_meta );

		add_action( 'admin_enqueue_scripts', array( $this, 'loginpress_widget_color_picker' ) );
	}

	/**
	 * Checks whether the widget is currently active.
	 *
	 * @since 5.0.0
	 * @version 6.1.0
	 * @return bool True if the widget is active, false otherwise.
	 */
	public function loginpress_widget_active() {
		return is_active_widget( false, false, $this->id_base );
	}

	/**
	 * Enqueue color picker scripts for widget admin.
	 *
	 * @param string $page The current admin page.
	 * @since 3.0.0
	 * @version 6.1.0
	 * @return void
	 */
	public function loginpress_widget_color_picker( $page ) {

		if ( 'widgets.php' === $page ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
		}
	}

	/**
	 * Get widget choices for form and update methods.
	 *
	 * @since 3.0.0
	 * @version 6.1.0
	 * @return void
	 */
	public function widget_choices() {
		// Define choices for widget.
		$this->choices = LoginPress_Widget_Utilities::get_widget_choices();
	}

	/**
	 * Replace placeholders in text with user data and URLs.
	 *
	 * @param string $text The text containing placeholders.
	 * @since 3.0.0
	 * @version 6.1.0
	 * @return string The text with placeholders replaced.
	 */
	public function patch_string( $text ) {
		return LoginPress_Widget_Utilities::patch_string( $text, $this->user, $this->instance );
	}


	/**
	 * Generate widget links HTML for logged in/out users.
	 *
	 * @param string $show Status of user (logged_in or logged_out).
	 * @param array  $links Links to be shown.
	 * @since 3.0.0
	 * @version 6.1.0
	 * @return void
	 */
	public function loginpress_widget_link( $show = 'logged_in', $links = array() ) {

		if ( ! is_array( $links ) ) {
			$links = LoginPress_Widget_Utilities::parse_widget_links( $links );
		}

		if ( 'logged_out' === $show ) {

			if ( get_option( 'users_can_register' ) && ! empty( $this->instance['show_register_link'] ) && '1' === $this->instance['show_register_link'] ) {

				$register_text = isset( $this->instance['registeration_text'] ) ? $this->instance['registeration_text'] : __( 'Register', 'loginpress-pro' );

				if ( ! is_multisite() ) {

					$links['register'] = array(
						'text' => $register_text,
						'href' => apply_filters( 'loginpress_widget_register_url', site_url( 'wp-login.php?action=register', 'login' ) ),
					);
				} else {

					$links['register'] = array(
						'text' => $register_text,
						'href' => apply_filters( 'loginpress_widget_register_url', site_url( 'wp-signup.php', 'login' ) ),
					);
				}
			} // endif; show_register_link.
			if ( ! empty( $this->instance['show_lost_password_link'] ) && '1' === $this->instance['show_lost_password_link'] ) {

				$lost_link_text         = isset( $this->instance['lost_password_text'] ) ? $this->instance['lost_password_text'] : __( 'Lost your password?', 'loginpress-pro' );
				$links['lost_password'] = array(
					'text' => $lost_link_text,
					'href' => apply_filters( 'loginpress_widget_lost_password_url', wp_lostpassword_url() ),
				);
			} // endif; show_lost_password_link.
		} // endif; logged_out.

		// Process links with placeholders.
		foreach ( $links as $id => $link ) {
			$links[ $id ]['text'] = $this->patch_string( $link['text'] );
			$links[ $id ]['href'] = $this->patch_string( $link['href'] );
		}

		echo LoginPress_Widget_Utilities::generate_widget_links_html( $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Outputs the content of the widget.
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Widget instance data.
	 * @since 1.0.0
	 * @return void
	 */
	public function widget( $args, $instance ) {

		// Record $instance.
		$this->instance = $instance;

		// Get user.
		if ( is_user_logged_in() ) {
			$this->user = get_user_by( 'id', get_current_user_id() );
		}

		$defaults = array(
			/* Translators: Welcome user */
			'logged_in_title'  => ! empty( $instance['logged_in_title'] ) ? $instance['logged_in_title'] : __( 'Welcome %username%', 'loginpress-pro' ),
			'logged_out_title' => ! empty( $instance['logged_out_title'] ) ? $instance['logged_out_title'] : __( 'Login', 'loginpress-pro' ),
			'show_avatar'      => isset( $instance['show_avatar'] ) ? $instance['show_avatar'] : 1,
			'avatar_size'      => isset( $instance['avatar_size'] ) ? $instance['avatar_size'] : 38,
			'logged_in_links'  => ! empty( $instance['logged_in_links'] ) ? $instance['logged_in_links'] : array(),
			'logged_out_links' => ! empty( $instance['logged_out_links'] ) ? $instance['logged_out_links'] : array(),
		);

		$args = array_merge( $defaults, $args );

		extract( $args ); // @codingStandardsIgnoreLine.

		echo wp_kses_post( $before_widget );

		// Logged in user.
		if ( is_user_logged_in() ) {

			$logged_in_title = $this->replace_tags( $logged_in_title );

			if ( $logged_in_title ) {
				echo wp_kses_post( $before_title . $logged_in_title . $after_title );
			}

			if ( '1' === $show_avatar ) {
				echo '<div class="avatar_container">' . get_avatar( $this->user->ID, $args['avatar_size'] ) . '</div>';
			}

			$this->loginpress_widget_link( 'logged_in', $logged_in_links );

			// Logged out user.
		} else {

			$logged_out_title = $this->replace_tags( $logged_out_title );

			if ( $logged_out_title ) {
				echo wp_kses_post( $before_title . $logged_out_title . $after_title );
			}

			$this->loginpress_login_form( $instance );

			// Render Register Form (hidden by default).
			$this->loginpress_register_form( $instance );
			$this->loginpress_lost_password_form( $instance );

			// Toggle links to switch between login and register.
			echo '<ul class="pagenav loginpress_widget_links">';
			echo '<li class="login-link" style="display:none;"><a href="#" id="show_login">' . esc_html__( 'Login', 'loginpress-pro' ) . '</a></li>';
			echo '</ul>';

			$this->loginpress_widget_link( 'logged_out', $logged_out_links );
		}

		echo wp_kses_post( $after_widget );
	}

	/**
	 * Replace user-related placeholders in text.
	 *
	 * @param string $text The text containing placeholders.
	 * @since 3.0.0
	 * @return string The text with placeholders replaced.
	 */
	public function replace_tags( $text ) {
		return LoginPress_Widget_Utilities::replace_user_tags( $text, $this->user );
	}

	/**
	 * Render the login form for the widget.
	 *
	 * @param array $instance Widget instance data.
	 * @since 4.0.0
	 * @version 6.1.0
	 * @return void
	 */
	public function loginpress_login_form( $instance ) {
		// Record $instance.
		$this->instance     = $instance;
		$loginpress_setting = get_option( 'loginpress_setting' );
		$redirect           = empty( $instance['login_redirect_url'] ) ? $this->redirect_url() : $instance['login_redirect_url'];
		$show_remember_me   = ! isset( $this->instance['show_rememberme'] ) || ! empty( $this->instance['show_rememberme'] );
		$value_remember_me  = isset( $loginpress_setting['auto_remember_me'] ) && 'on' === $loginpress_setting['auto_remember_me'] ? true : false;
		$login_order        = isset( $loginpress_setting['login_order'] ) ? $loginpress_setting['login_order'] : '';
		if ( 'username' === $login_order ) {
			$label = __( 'Username', 'loginpress-pro' );
		} elseif ( 'email' === $login_order ) {
			$label = __( 'Email Address', 'loginpress-pro' );
		} else {
			$label = __( 'Username or Email Address', 'loginpress-pro' );
		}

		$login_form_args = array(
			'echo'           => false,
			'redirect'       => esc_url( $redirect ),
			'label_username' => $label,
			'label_password' => __( 'Password', 'loginpress-pro' ),
			'label_remember' => __( 'Remember Me', 'loginpress-pro' ),
			'label_log_in'   => __( 'Login &rarr;', 'loginpress-pro' ),
			'remember'       => $show_remember_me,
			'value_remember' => $value_remember_me,
		);

		// Add reCaptcha field between the Password and Remember Me fields using the 'login_form_middle' filter.
		add_filter( 'login_form_middle', array( $this, 'loginpress_add_captcha_field' ) );

		// Output the login form.
		echo wp_login_form( $login_form_args );
	}

	/**
	 * Renders the captcha in the login form.
	 *
	 * @param string $content The widget content.
	 * @since 4.0.0
	 * @version 6.1.0
	 * @return string The content with captcha field added.
	 */
	public function loginpress_add_captcha_field( $content ) {
		ob_start();
		do_action( 'loginpress_after_login_form_widget' ); // Insert reCaptcha field.
		$after_login_form_middle = ob_get_clean();
		return $content . $after_login_form_middle; // Append the reCaptcha field after the password input.
	}

	/**
	 * Renders the registration form in the widget.
	 *
	 * @param array $instance Widget instance data.
	 * @since 4.0.0
	 * @version 6.1.0
	 * @return void
	 */
	public function loginpress_register_form( $instance ) {

		$loginpress_setting          = get_option( 'loginpress_setting' );
		$enable_password_strength    = isset( $loginpress_setting['enable_pass_strength'] ) ? $loginpress_setting['enable_pass_strength'] : 'off';
		$enable_pass_strength        = isset( $loginpress_setting['enable_pass_strength_forms'] ) ? $loginpress_setting['enable_pass_strength_forms'] : 'off';
		$register                    = isset( $enable_pass_strength['register'] ) ? $enable_pass_strength['register'] : false;
		$strength_meter_enable       = isset( $loginpress_setting['password_strength_meter'] ) ? $loginpress_setting['password_strength_meter'] : 'off';
		$loginpress_setting          = get_option( 'loginpress_social_logins' );
		$loginpress_general_settings = get_option( 'loginpress_setting' );
		$output                      = '';
		$enable_reg_pass_field       = isset( $loginpress_setting['enable_reg_pass_field'] ) ? $loginpress_setting['enable_reg_pass_field'] : 'off';
		// Render the registration form (hidden by default).
		$output .= '<form id="registerform" class="loginpress-register-widget" style="display:none;" action="' . esc_url( wp_registration_url() ) . '" method="post">';
		$output .= '<p class="login-username">
						<label for="user_login">' . __( 'Username', 'loginpress-pro' ) . '</label>
						<input type="text" name="user_login" id="user_name" class="input">
					</p>';
		$output .= '<p class="login-email">
						<label for="user_email">' . __( 'Email Address', 'loginpress-pro' ) . '</label>
						<input type="email" name="user_email" id="user_email" class="input" >
					</p>';
		if ( isset( $loginpress_general_settings['enable_reg_pass_field'] ) && 'on' === $loginpress_general_settings['enable_reg_pass_field'] ) {
			$output .= '<p class="login-password">
                    <label for="user_pass">' . __( 'Password', 'loginpress-pro' ) . '</label>
                    <input type="password" name="user_pass" id="user_pass" class="input" >
                </p>';
			$output .= '<p class="login-confirm-password" >
							<label for="user_confirm_pass">' . __( 'Confirm Password', 'loginpress-pro' ) . '</label>
							<input type="password" name="user_confirm_pass" id="user_confirm_pass" class="input" >
						</p>';
		}

		ob_start();
		do_action( 'loginpress_after_reg_form_widget' ); // Insert reCaptcha field.
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
		$output .= ob_get_clean(); // Append reCaptcha field to the $output string.
		$output .= '
					<input type="submit" name="wp-submit" id="wp-submit-register" class="button button-primary" style="padding-top: 10px;" value="' . esc_attr__( 'Register', 'loginpress-pro' ) . '">
				';

					// Conditionally add the hidden div for social login links.
		if ( isset( $loginpress_setting['enable_social_login_links']['register'] ) && $loginpress_setting['enable_social_login_links']['register'] ) {
			if ( class_exists( 'LoginPress_Social' ) && true === apply_filters( 'loginpress_social_widget', true ) ) {
				$login_press_widget = new LoginPress_Login_Widget();
				$output            .= $login_press_widget->loginpress_social_login(); // Output the social login HTML.
			}
		}
		$output .= '</form>';

		echo $output; // phpcs:ignore
	}

	/**
	 * Renders the lost password form in the widget.
	 *
	 * @param array $instance Widget instance data.
	 * @since 4.0.0
	 * @return void
	 */
	public function loginpress_lost_password_form( $instance ) {
		$output = '';

		// Render the lost password form (hidden by default).
		$output .= '<form id="lostpasswordform" class="loginpress-lost-password-widget" style="display:none;" action="' . esc_url( wp_lostpassword_url() ) . '" method="post">';

		// Form title and description.
		$output .= '<p>' . __( 'Lost your password? Please enter your username or email address. You will receive a link to create a new password via email.', 'loginpress-pro' ) . '</p>';

		// Username or email input field.
		$output .= '<p class="login-username-or-email">
						<label for="user_login">' . __( 'Username or Email Address', 'loginpress-pro' ) . '</label>
						<input type="text" name="user_login" id="username" class="input">
					</p>';
		ob_start();
		do_action( 'loginpress_after_lost_password_form_widget' ); // Insert reCaptcha field.
		$output .= ob_get_clean(); // Append reCaptcha field to the $output string.
		// Submit button for password reset.
		$output .= '<p class="login-submit" style="padding-top: 10px;">
                <input type="submit" name="wp-submit" id="wp-submit-lostpass" class="button button-primary" value="' . esc_attr__( 'Get New Password', 'loginpress-pro' ) . '">
            </p>';

		$output .= '</form>';

		echo $output; // phpcs:ignore
	}

	/**
	 * Get the current page URL for redirects.
	 *
	 * @since 3.0.0
	 * @return string The current page URL.
	 */
	private function redirect_url() {
		return LoginPress_Widget_Utilities::get_current_page_url();
	}

	/**
	 * Processing widget options on save.
	 *
	 * @param array $new_instance The new options.
	 * @param array $old_instance The previous options.
	 * @since 1.0.0
	 * @version 6.1.0
	 * @return array Updated widget instance.
	 */
	public function update( $new_instance, $old_instance ) {
		$this->widget_choices();

		foreach ( $this->choices as $name => $option ) {
			if ( 'hr' === $option['type'] ) {
				continue;
			}

			if ( 'error_short_note' === $name ) {
				continue;
			}

			$instance[ $name ] = wp_strip_all_tags( stripslashes( $new_instance[ $name ] ) );
		}
		return $instance;
	}

	/**
	 * Check for editor screen.
	 *
	 * @since 3.0.0
	 * @version 6.1.0
	 * @return bool True if editor screen, false otherwise.
	 */
	private function is_editor_screen() {
		return LoginPress_Widget_Utilities::is_editor_screen();
	}


	/**
	 * Outputs the options form on admin.
	 *
	 * @param array $instance Widget instance data.
	 * @since 3.0.0
	 * @version 6.1.0
	 * @return void
	 */
	public function form( $instance ) {
		$this->widget_choices();

		foreach ( $this->choices as $name => $option ) {

			if ( 'hr' === $option['type'] ) {
				echo '<hr style="border: 1px solid #ddd; margin: 1em 0" />';
				continue;
			}

			if ( ! isset( $instance[ $name ] ) ) {
				$instance[ $name ] = $option['default'];
			}

			if ( empty( $option['placeholder'] ) ) {
				$option['placeholder'] = '';
			}

			echo '<p>';

			switch ( $option['type'] ) {

				case 'text':
					?>
					<label for="<?php echo esc_attr( $this->get_field_id( $name ) ); ?>"><?php echo wp_kses_post( $option['label'] ); ?>:</label>
					<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id( $name ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $name ) ); ?>" placeholder="<?php echo esc_attr( $option['placeholder'] ); ?>" value="<?php echo esc_attr( $instance[ $name ] ); ?>" />
					<?php
					break;

				case 'number':
					?>
					<label for="<?php echo esc_attr( $this->get_field_id( $name ) ); ?>"><?php echo wp_kses_post( $option['label'] ); ?>:</label>
					<input type="number" id="<?php echo esc_attr( $this->get_field_id( $name ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $name ) ); ?>" placeholder="<?php echo esc_attr( $option['placeholder'] ); ?>" value="<?php echo esc_attr( $instance[ $name ] ); ?>" />
					<?php
					break;

				case 'checkbox':
					?>
					<label for="<?php echo esc_attr( $this->get_field_id( $name ) ); ?>"><input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id( $name ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $name ) ); ?>" <?php checked( $instance[ $name ], 1 ); ?> value="1" /> <?php echo wp_kses_post( $option['label'] ); ?></label>
					<?php
					break;

				case 'textarea':
					?>
					<label for="<?php echo esc_attr( $this->get_field_id( $name ) ); ?>"><?php echo wp_kses_post( $option['label'] ); ?>:</label>
					<textarea class="widefat" cols="20" rows="3" id="<?php echo esc_attr( $this->get_field_id( $name ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $name ) ); ?>" placeholder="<?php echo esc_attr( $option['placeholder'] ); ?>"><?php echo esc_textarea( $instance[ $name ] ); ?></textarea>
					<?php
					break;

				case 'note':
					echo '<label for="' . esc_attr( $this->get_field_id( $name ) ) . '">' . wp_kses_post( $option['label'] ) . ':</label>';
					break;

				case 'color':
					if ( ! $this->is_editor_screen() ) :
						?>
						<script type="text/javascript">
							(function($) {
								$(function() {
									$(document).ready(function($) {
										$('.color-picker').wpColorPicker();
										$('.color-picker').on('focus', function(){
											var parent = $(this).parent();
											parent.find('.wp-color-result').click();
										});
									});
								});
							})(jQuery);
						</script>
					<?php endif; ?>

					<label for="<?php echo esc_attr( $this->get_field_id( $name ) ); ?>" style="display:block;"><?php echo wp_kses_post( $option['label'] ); ?>:</label>
					<input class="widefat color-picker" type="text" id="<?php echo esc_attr( $this->get_field_id( $name ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $name ) ); ?>" value="<?php echo esc_attr( $instance[ $name ] ); ?>" />
					<?php
					break;
			}

			if ( ! empty( $option['description'] ) ) {
				echo '<span class="description" style="display:block; padding-top:.25em">' . wp_kses_post( $option['description'] ) . '</span>';
			}

			echo '</p>';
		}
	}
}
