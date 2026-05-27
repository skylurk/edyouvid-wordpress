<?php
/**
 * LoginPress Widget Utilities File.
 *
 * @package LoginPress
 * @subpackage Widget\Utilities
 * @since 6.1.0
 * @version 6.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * LoginPress Widget Utilities.
 *
 * Contains utility functions for the LoginPress Widget addon.
 *
 * @package   LoginPress
 * @subpackage Widget\Utilities
 * @since     6.1.0
 * @version   6.1.0
 */

if ( ! class_exists( 'LoginPress_Widget_Utilities' ) ) :

	/**
	 * LoginPress Widget Utilities class.
	 *
	 * @package   LoginPress
	 * @subpackage Widget\Utilities
	 * @since     6.1.0
	 * @version   6.1.0
	 */
	class LoginPress_Widget_Utilities {

		/**
		 * Replace user-related placeholders in text.
		 *
		 * @param string $text The text containing placeholders.
		 * @param object $user The user object.
		 * @since 6.1.0
		 * @return string The text with placeholders replaced.
		 */
		public static function replace_user_tags( $text, $user = null ) {
			if ( $user ) {
				$text = str_replace(
					array( '%username%', '%userid%', '%firstname%', '%lastname%', '%name%' ),
					array(
						ucwords( $user->display_name ),
						$user->ID,
						$user->first_name,
						$user->last_name,
						trim( $user->first_name . ' ' . $user->last_name ),
					),
					$text
				);
			}
			return $text;
		}

		/**
		 * Replace user-related placeholders including avatar in text.
		 *
		 * @param string $text The text containing placeholders.
		 * @param object $user The user object.
		 * @param array  $instance Widget instance data.
		 * @since 6.1.0
		 * @return string The text with placeholders replaced.
		 */
		public static function patch_string( $text, $user = null, $instance = array() ) {
			if ( $user ) {
				$text = str_replace(
					array( '%username%', '%userid%', '%firstname%', '%lastname%', '%name%', '%avatar%' ),
					array(
						ucwords( $user->display_name ),
						$user->ID,
						$user->first_name,
						$user->last_name,
						trim( $user->first_name . ' ' . $user->last_name ),
						get_avatar( $user->ID, 38 ),
					),
					$text
				);
			}

			$logout_redirect = wp_logout_url( empty( $instance['logout_redirect_url'] ) ? self::get_current_page_url() : $instance['logout_redirect_url'] );

			$text = str_replace(
				array( '%admin_url%', '%logout_url%' ),
				array( untrailingslashit( admin_url() ), $logout_redirect ),
				$text
			);

			$text = do_shortcode( $text );

			return $text;
		}

		/**
		 * Get the current page URL.
		 *
		 * @since 6.1.0
		 * @return string The current page URL.
		 */
		public static function get_current_page_url() {
			$page_url  = force_ssl_admin() ? 'https://' : 'http://';
			$page_url .= isset( $_SERVER['HTTP_HOST'] ) ? esc_attr( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : ''; // @codingStandardsIgnoreLine.
			$page_url .= isset( $_SERVER['REQUEST_URI'] ) ? esc_attr( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : ''; // @codingStandardsIgnoreLine.

			return esc_url_raw( $page_url );
		}

		/**
		 * Get user redirect URL.
		 *
		 * @param int    $user_id User ID.
		 * @param string $option Meta key name.
		 * @since 6.1.0
		 * @return string Redirect URL.
		 */
		public static function get_user_redirect_url( $user_id, $option ) {
			if ( ! is_multisite() ) {
				$redirect_url = get_user_meta( $user_id, $option, true );
			} else {
				$redirect_url = get_user_option( $option, $user_id );
			}
			return $redirect_url;
		}

		/**
		 * Check if the current screen is an editor screen.
		 *
		 * @since 6.1.0
		 * @return bool True if editor screen, false otherwise.
		 */
		public static function is_editor_screen() {
			if ( isset( $_GET['fl_builder'] ) ) { // @codingStandardsIgnoreLine.
				return true;
			}

			return false;
		}

		/**
		 * Parse widget links from text format.
		 *
		 * @param string $links_text Links in text format (Text | href | capability).
		 * @since 6.1.0
		 * @return array Parsed links array.
		 */
		public static function parse_widget_links( $links_text ) {
			if ( ! is_string( $links_text ) ) {
				return array();
			}

			$raw_links = array_map( 'trim', explode( "\n", $links_text ) );
			$links     = array();

			foreach ( $raw_links as $link ) {
				$link     = array_map( 'trim', explode( '|', $link ) );
				$link_cap = '';

				if ( 3 === count( $link ) ) {
					list( $link_text, $link_href, $link_cap ) = $link;
				} elseif ( 2 === count( $link ) ) {
					list( $link_text, $link_href ) = $link;
				} else {
					continue;
				}

				// Check capability.
				if ( ! empty( $link_cap ) ) {
					if ( ! current_user_can( strtolower( $link_cap ) ) ) {
						continue;
					}
				}

				$links[ sanitize_title( $link_text ) ] = array(
					'text' => $link_text,
					'href' => $link_href,
				);
			}

			return $links;
		}

		/**
		 * Generate widget links HTML.
		 *
		 * @param array $links Links array.
		 * @since 6.1.0
		 * @return string HTML output.
		 */
		public static function generate_widget_links_html( $links ) {
			if ( empty( $links ) || ! is_array( $links ) || 0 === count( $links ) ) {
				return '';
			}

			$html = '<ul class="pagenav loginpress_widget_links">';
			foreach ( $links as $id => $link ) {
				$html .= '<li class="' . esc_attr( $id ) . '-link"><a href="' . esc_url( $link['href'] ) . '">' . wp_kses_post( $link['text'] ) . '</a></li>';
			}
			$html .= '</ul>';

			return $html;
		}

		/**
		 * Get widget default choices.
		 *
		 * @since 6.1.0
		 * @return array Widget choices array.
		 */
		public static function get_widget_choices() {
			return array(
				'logged_out_title'        => array(
					'label'   => __( 'Logged-out Title', 'loginpress-pro' ),
					'default' => __( 'Login', 'loginpress-pro' ),
					'type'    => 'text',
				),
				'logged_out_links'        => array(
					'label'   => __( 'Logged-out Links (Text | href) e.g., Dashboard | %admin_url%', 'loginpress-pro' ),
					'default' => '',
					'type'    => 'textarea',
				),
				'show_lost_password_link' => array(
					'label'   => __( 'Show lost password link', 'loginpress-pro' ),
					'default' => 1,
					'type'    => 'checkbox',
				),
				'lost_password_text'      => array(
					'label'   => __( 'Lost password text', 'loginpress-pro' ),
					'default' => __( 'Lost your password?', 'loginpress-pro' ),
					'type'    => 'text',
				),
				'show_register_link'      => array(
					'label'       => __( 'Show register link', 'loginpress-pro' ),
					'default'     => 1,
					/* Translators: Register String */
					'description' => sprintf( __( '%1$sAnyone can register%2$s must be enabled.', 'loginpress-pro' ), '<a href="' . admin_url( 'options-general.php' ) . '">', '</a>' ),
					'type'        => 'checkbox',
				),
				'registeration_text'      => array(
					'label'   => __( 'Register text', 'loginpress-pro' ),
					'default' => __( 'Register', 'loginpress-pro' ),
					'type'    => 'text',
				),
				'show_rememberme'         => array(
					'label'   => __( 'Show "Remember me" checkbox', 'loginpress-pro' ),
					'default' => 1,
					'type'    => 'checkbox',
				),
				'login_redirect_url'      => array(
					'label'       => __( 'Login Redirect URL', 'loginpress-pro' ),
					'default'     => '',
					'type'        => 'text',
					'placeholder' => __( 'Current page URL', 'loginpress-pro' ),
				),
				'hr-1'                    => array(
					'type'    => 'hr',
					'default' => '',
				),
				'logged_in_title'         => array(
					'label'   => __( 'Logged-in title', 'loginpress-pro' ),
					/* Translators: Welcome String */
					'default' => __( 'Welcome %username%', 'loginpress-pro' ),
					'type'    => 'text',
				),
				'logged_in_links'         => array(
					'label'       => __( 'Logged-in Links (Text | HREF | Capability) e.g., Logout | %logout_url%', 'loginpress-pro' ),
					/* Translators: Register String */
					'description' => sprintf( __( '%1$sCapability%2$s (optional) refers to the type of user who can view the link.', 'loginpress-pro' ), '<a href="http://codex.wordpress.org/Roles_and_Capabilities">', '</a>' ),
					'default'     => __( "Dashboard | %admin_url%\nProfile | %admin_url%/profile.php\nLogout | %logout_url%", 'loginpress-pro' ),
					'type'        => 'textarea',
				),
				'show_avatar'             => array(
					'label'   => __( 'Show logged-in user avatar', 'loginpress-pro' ),
					'default' => 1,
					'type'    => 'checkbox',
				),
				'avatar_size'             => array(
					'label'   => __( 'Logged-in user avatar size', 'loginpress-pro' ),
					'default' => 38,
					'type'    => 'number',
				),
				'logout_redirect_url'     => array(
					'label'       => __( 'Logout Redirect URL', 'loginpress-pro' ),
					'type'        => 'text',
					'placeholder' => __( 'Current page URL', 'loginpress-pro' ),
					'default'     => '',
				),
				'hr-2'                    => array(
					'type'    => 'hr',
					'default' => '',
				),
				'error_short_note'        => array(
					'label'       => __( 'Error Messages', 'loginpress-pro' ),
					/* Translators: Error Message String */
					'description' => sprintf( __( 'You can change the Error messages from %1$sLoginPress Customizer%2$s', 'loginpress-pro' ), '<a href="' . admin_url( 'admin.php?page=loginpress' ) . '">', '</a>' ),
					'type'        => 'note',
					'default'     => '',
				),
				'error_bg_color'          => array(
					'label'   => __( 'Error Background Color', 'loginpress-pro' ),
					'type'    => 'color',
					'default' => '#fbb1b7',
				),
				'error_text_color'        => array(
					'label'   => __( 'Error Text Color', 'loginpress-pro' ),
					'type'    => 'color',
					'default' => '#ae121e',
				),
			);
		}
	}

endif;
