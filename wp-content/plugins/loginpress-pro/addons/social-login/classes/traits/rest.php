<?php
/**
 * LoginPress Social Login Rest Trait file.
 *
 * @package LoginPress Social Login
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LoginPress Social Login Rest Trait.
 *
 * Handles Some helping functions from social-login.php file.
 * Registers all the rest apis for Social Login.
 *
 * @package   LoginPress
 * @subpackage Traits\SocialLogin
 * @since     6.1.0
 */

if ( ! trait_exists( 'LoginPress_Social_Login_Rest_Trait' ) ) {
	/**
	 * LoginPress Social Login Settings Trait.
	 *
	 * Handles Some helping functions from social-login.php file.
	 * Registers all the rest apis for Social Login.
	 *
	 * @package   LoginPress
	 * @subpackage Traits\SocialLogin
	 * @since   6.1.0
	 */
	trait LoginPress_Social_Login_Rest_Trait {
		/**
		 * Register the rest routes for social login.
		 *
		 * @since  6.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function lp_sociallogin_register_routes() {
			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/social-login-settings',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'loginpress_get_social_login_settings' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/social-login-settings-update',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_update_social_login_settings' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);
		}

		/**
		 * Get the social login settings.
		 *
		 * @since  6.0.0
		 * @version 6.1.0
		 * @return array
		 */
		public function loginpress_get_social_login_settings() {
			$settings = get_option( 'loginpress_social_logins', array() );
			return wp_parse_args( $settings, false );
		}

		/**
		 * Update the social login settings.
		 *
		 * @since  6.0.0
		 * @version 6.1.0
		 * @param WP_REST_Request $request The REST request object.
		 * @return array|WP_Error
		 */
		public function loginpress_update_social_login_settings( WP_REST_Request $request ) {
			// Get the settings data sent from the frontend.
			$new_settings = $request->get_json_params();
			// Make sure it's an array.
			if ( ! is_array( $new_settings ) ) {
				return new WP_Error( 'invalid_data', esc_html__( 'Invalid settings data.', 'loginpress-pro' ), array( 'status' => 400 ) );
			}

			// Option name where the settings are stored.
			$option_name = 'loginpress_social_logins';

			// Get the existing settings to preserve any missing keys.
			$existing_settings = (array) $this->settings;

			// Merge new settings into existing ones to preserve untouched settings.
			$updated_settings = array_merge( $existing_settings, $new_settings );

			// Update the option.
			update_option( $option_name, $updated_settings );

			return rest_ensure_response(
				array(
					'success' => true,
					'message' => esc_html__( 'Social login settings updated successfully.', 'loginpress-pro' ),
				)
			);
		}

		/**
		 * LoginPress Social Login Block render callback.
		 *
		 * @param mixed $attributes Containing the attributes of each provider.
		 * @since 5.0.0
		 * @version 6.1.0
		 * @return string
		 */
		public function loginpress_render_social_login_block( $attributes ) {
			$shortcode = '[loginpress_social_login';
			foreach ( $attributes as $key => $value ) {
				// Convert camelCase to snake_case (e.g., disableApple → disable_apple).
				$attr_name  = strtolower( preg_replace( '/(?<!^)[A-Z]/', '_$0', $key ) );
				$shortcode .= sprintf( ' %s="%s"', esc_attr( $attr_name ), $value ? 'true' : 'false' );
			}
			$shortcode .= ']';

			return do_shortcode( $shortcode );
		}

		/**
		 * Add social avatar to user profile.
		 *
		 * @param mixed  $avatar The Avatar.
		 * @param mixed  $id_or_email The ID or Email of user.
		 * @param int    $size The size of the avatar.
		 * @param string $default_avatar Default Avatar.
		 * @param bool   $alt Alternative.
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return string
		 */
		public function insert_avatar( $avatar, $id_or_email, $size = 96, $default_avatar = '', $alt = false ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			$host             = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
			$request_uri      = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$current_page_url = ( is_ssl() ? 'https://' : 'http://' ) . $host . $request_uri;
			$options_page_url = home_url( '/wp-admin/options-discussion.php' );
			if ( $current_page_url === $options_page_url ) {
				return $avatar;
			}
			global $wpdb;
			$user = false;
			$id   = 0;

			if ( is_numeric( $id_or_email ) ) {

				$id   = (int) $id_or_email;
				$user = get_user_by( 'id', $id );

			} elseif ( is_object( $id_or_email ) ) {

				if ( ! empty( $id_or_email->user_id ) ) {
					$id   = (int) $id_or_email->user_id;
					$user = get_user_by( 'id', $id );
				}
			} else {
				$user = get_user_by( 'email', $id_or_email );
			}

			if ( $user && is_object( $user ) ) {
				$avatar_url = $wpdb->get_results( $wpdb->prepare( "SELECT photo_url FROM `$this->table_name` WHERE user_id = %d", $id ) ); // @codingStandardsIgnoreLine.

				if ( $avatar_url ) {
					$avatar_url = $avatar_url[0]->photo_url;
					$avatar     = preg_replace( '/src=("|\').*?("|\')/i', 'src=\'' . $avatar_url . '\'', $avatar );
					$avatar     = preg_replace( '/srcset=("|\').*?("|\')/i', 'srcset=\'' . $avatar_url . '\'', $avatar );
				}
			}

			return $avatar;
		}

		/**
		 * LoginPress Addon updater.
		 *
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function init_addon_updater() {
			if ( class_exists( 'LoginPress_AddOn_Updater' ) ) {
				$updater = new LoginPress_AddOn_Updater( 2335, __FILE__, $this->version );
			}
		}

		/**
		 * Get the button label for a social login provider.
		 *
		 * @param string $provider The name of the provider (e.g., 'facebook', 'twitter').
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return array The status setting array.
		 */
		public function get_provider_button_label_setting( $provider ) {
			return array(
				'name'  => 'WordPress' === $provider ? 'wordpress_button_label' : "{$provider}_button_label",
				/* translators: Button label */
				'label' => sprintf( __( '%s Button Label', 'loginpress-pro' ), ucfirst( $provider ) ),
				'desc'  => sprintf( /* translators: Button label description */
					__( 'Customize the label for the %s login button. ', 'loginpress-pro' ),
					ucfirst( $provider )
				),
				'type'  => 'text',
				'std'   => 'Login with %provider%', // Default value.
			);
		}

		/**
		 * Get the status setting for a social login provider.
		 *
		 * @param string $provider The name of the provider (e.g., 'facebook', 'twitter').
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return array The status setting array.
		 */
		public function get_provider_status_setting( $provider ) {
			return array(
				'name'     => "{$provider}_status",
				/* translators: The provider's status */
				'label'    => sprintf( __( '%s Status', 'loginpress-pro' ), ucfirst( $provider ) ),
				/* translators: Description of provider status setting */
				'desc'     => sprintf( __( 'The current status of %s login.', 'loginpress-pro' ), ucfirst( $provider ) ),
				'std'      => 'Not verified', // Default value.
				'callback' => array( $this, 'lpsl_provider_status' ),
			);
		}

		/**
		 * Render the shortcode field with copy button.
		 *
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function render_social_login_html() {
			echo '
			<div class="loginpress-socialshortcode-box">
				<input 
					type="text" 
					id="loginpress-shortcode" 
					value="[loginpress_social_login]" 
					class="description"
					readonly 
					 
				/>
				<div class="copy-email-icon-wrapper sociallogin-copy-code" id="copy-shortcode-button" data-tooltip="Copy">
					<svg class="sociallogin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
						<g clip-path="url(#clip0_27_294)">
							<path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"></path>
							<path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"></path>
						</g>
						<defs>
							<clipPath id="clip0_27_294">
								<rect width="22" height="22" fill="white" transform="matrix(-1 0 0 1 22 0)"></rect>
							</clipPath>
						</defs>
					</svg>
				</div>
			</div>';
			echo '<p class="description">' . esc_html__( 'Place this shortcode where you want to add social login buttons.', 'loginpress-pro' ) . '</p>';
		}

		/**
		 * Social Login Admin scripts.
		 *
		 * @param int $hook The page ID.
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function loginpress_social_login_admin_action_scripts( $hook ) {
			if ( 'toplevel_page_loginpress-settings' === $hook ) {
				wp_enqueue_script( 'jquery-ui-sortable' );
				wp_enqueue_style( 'loginpress-admin-social-login', plugins_url( '../../assets/css/style.css', __FILE__ ), array(), LOGINPRESS_PRO_VERSION );
			}
		}

		/**
		 * Social Login Settings tab's.
		 *
		 * @param array $loginpress_tabs The social login addon tabs.
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return array The Social login setting tabs and their attributes.
		 */
		public function settings_tab( $loginpress_tabs ) {
			$new_tab = array(
				array(
					'id'         => 'loginpress_social_logins',
					'title'      => __( 'Social Login', 'loginpress-pro' ),
					'sub-title'  => __( 'Third Party login access', 'loginpress-pro' ),
					/* Translators: The Social login tabs */
					'desc'       => $this->tab_desc(),
					'video_link' => '45S3i9PJhLA',
				),
			);
			return array_merge( $loginpress_tabs, $new_tab );
		}

		/**
		 * The tab_desc description of the tab 'loginpress settings'.
		 *
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return string The tab description.
		 */
		public function tab_desc() {
			// translators: Social login addons description.
			$html = sprintf( __( '%1$sSocial Login add-on allows your users to log in and register using their Facebook, Google, X (Twitter) accounts and more. By integrating these social media platforms into your login system, you can eliminate spam and bot registrations effectively.%2$s', 'loginpress-pro' ), '<p>', '</p>' );
			// translators: Tabs.
			$html .= sprintf( __( '%1$s%3$sSettings%4$s %5$sStyles%4$s %6$sProviders%4$s%2$s', 'loginpress-pro' ), '<div class="loginpress-social-login-tab-wrapper">', '</div>', '<a href="#loginpress_social_login_settings" class="loginpress-social-login-tab loginpress-social-login-active">', '</a>', '<a href="#loginpress_social_login_styles" class="loginpress-social-login-tab">', '<a href="#loginpress_social_login_providers" class="loginpress-social-login-tab">' );

			return $html;
		}

		/**
		 * Multicheck with icons.
		 *
		 * @param array $args settings field args.
		 * @since 4.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function lpsl_multicheck_with_icons( $args ) {
			$value = $this->lpsl_get_option( $args['id'], $args['section'], $args['std'] );
			if ( ! isset( $value ) || empty( $value ) ) {
				// Retrieve the current value of the option.
				$options = get_option( 'loginpress_social_logins', array() );
				// Ensure the retrieved value is an array. If not, reset it to an empty array.
				if ( ! is_array( $options ) ) {
					$options = array();
				}
				$options[ $args['id'] ] = 'default';
				update_option( 'loginpress_social_logins', $options );

				// Get the updated value for use in your callback or rendering logic.
				$value = $options[ $args['id'] ];
			}
			// Ensure $value is a string (radio options save single value).
			$value = is_array( $value ) ? '' : $value;

			$html  = '<fieldset>';
			$html .= sprintf( '<input type="hidden" name="%1$s[%2$s]" value="" />', $args['section'], $args['id'] );

			// Start the container for checkboxes.
			$html .= '<div class="loginpress-multicheck-container">';

			foreach ( $args['options'] as $key => $option ) {
				$html .= '<div class="loginpress-multicheck-item">';
				$html .= '<label for="wpb-' . esc_attr( $args['section'] ) . '[' . esc_attr( $args['id'] ) . '][' . esc_attr( $key ) . ']">';
				$html .= '<input type="radio" class="loginpress-radio" id="wpb-' . esc_attr( $args['section'] ) . '[' . esc_attr( $args['id'] ) . '][' . esc_attr( $key ) . ']" name="' . esc_attr( $args['section'] ) . '[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $key ) . '" ' . checked( $value, $key, false ) . ' style="margin-right: 8px;" />';
				$html .= '<span>' . esc_html( $option['label'] ) . '</span>'; // Label now on the right of the radio button.
				if ( ! empty( $option['icon'] ) ) {
					$html .= '<div class="loginpress-icon">' . $option['icon'] . '</div>'; // SVG icon below the checkbox.
				}
				$html .= '</label>';
				$html .= '</div>';
			}

			$html .= '</div>'; // Close the container.
			$html .= $this->lpsl_get_field_description( $args );
			$html .= '</fieldset>';

			// Enqueue a script for managing single-selection behavior.
			$html .= '<script>
				document.addEventListener("DOMContentLoaded", function () {
					const radios = document.querySelectorAll("input[type=radio][name=\'' . esc_js( $args['section'] ) . '[' . esc_js( $args['id'] ) . ']\']");
					radios.forEach((radio) => {
						radio.addEventListener("change", () => {
							radios.forEach((r) => r.checked = false);
							radio.checked = true;
						});
			});
		});
	</script>';

			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * Displays verify settings card.
		 *
		 * @param array $args settings field args.
		 * @since 4.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function lpsl_provider_status( $args ) {
			// Get the provider name from the args.
			$value       = esc_attr( $this->lpsl_get_option( $args['id'], $args['section'], $args['std'] ) );
			$size        = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$type        = 'hidden';
			$placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';
			$provider    = $args['id']; // Example: "facebook_status".

			// Remove "_status" and capitalize the first letter.
			$provider_name = ucfirst( str_replace( '_status', '', $provider ) );
			if ( 'Gplus' === $provider_name ) {
				$provider_name = 'Google';
			}
			// Retrieve the provider's status from the database.
			$status = ! empty( $value ) ? $value : 'Not verified';
			// Normalize status for class usage.
			$status_class = strtolower( str_replace( ' ', '-', $status ) );
			$heading      = '';
			$description  = '';
			$button_label = '';
			if ( 'verified' === $status_class ) {
				$heading      = __( 'App Configuration Successful', 'loginpress-pro' );
				$description  = __( 'You have successfully configured your app. This social provider is now set up correctly, allowing users to log in without issues.', 'loginpress-pro' );
				$button_label = __( 'Verify Settings Again', 'loginpress-pro' );
			} elseif ( 'not-verified' === $status_class ) {
				$heading      = __( 'Test Your App Configuration', 'loginpress-pro' );
				$description  = __( 'Before you can start letting your users register with your app, it needs to be tested. This test ensures no users have trouble with the login and regitration process. If you encounter an error during the test, review your app settings. Otherwise, your configuration is fine.', 'loginpress-pro' );
				$button_label = __( 'Verify Settings', 'loginpress-pro' );
			} elseif ( 'yet-to-verify' === $status_class ) {
				$heading      = __( 'App Verification Required', 'loginpress-pro' );
				$description  = __( 'Your app must be verified in the latest version of Loginpress Social Login. Previously configured social logins will still work, but app verification is necessary to stay compliant.', 'loginpress-pro' );
				$button_label = __( 'Verify Settings', 'loginpress-pro' );
			}
			// Generate HTML structure with button.
			$html  = '<div>';
			$html .= '    <div>';
			$html .= '<div class="provider-container ' . esc_attr( $status_class ) . '"><div class="provider-description"><h3>' . $heading . '</h3><p>' . $description . '</p></div>';
			$html .= '        <button type="button" id="verify-settings" class="button button-primary loginpress-verify-provider" data-provider="' . esc_attr( $provider ) . '">';
			$html .= $button_label;
			$html .= '        </button></div>';
			$html .= sprintf( '<input type="%1$s" class="%2$s-text" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"%6$s/>', $type, $size, $args['section'], $args['id'], $status, $placeholder );
			$html .= '    </div>';
			$html .= '</div>';

			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * Renders a hidden input with a JSON encoded value.
		 *
		 * Used for storing the provider order in the database.
		 *
		 * @param array $args Field arguments passed from the settings API.
		 * @see loginpress_get_required_keys_for_provider()
		 * @since 4.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function lpsl_callback_hidden( $args ) {
			// Get the current value of the field.
			$loginpress_social_logins = get_option( 'loginpress_social_logins' );
			$value                    = isset( $loginpress_social_logins['provider_order'] ) ? $loginpress_social_logins['provider_order'] : '';

			// Ensure the value is JSON encoded only once.
			if ( is_array( $value ) ) {
				$encoded_value = wp_json_encode( $value ); // Encode only if it's an array.
			} else {
				$encoded_value = $value; // Assume it's already encoded.
			}
			// Generate the HTML for the hidden input.
			$html = sprintf(
				'<input type="hidden" id="%1$s[%2$s]" name="%1$s[%2$s]" value="%3$s" />',
				$args['section'],
				$args['id'],
				esc_attr( $encoded_value )
			);

			// Optionally display a description (useful for debugging).
			$html .= $this->lpsl_get_field_description( $args );

			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * Get the required keys for a specific provider.
		 *
		 * @param string $provider The provider name.
		 * @since 4.0.0
		 * @version 6.1.0
		 * @return array List of required keys for the provider.
		 */
		private function loginpress_get_required_keys_for_provider( $provider ) {
			$required_fields = array(
				'facebook'  => array( 'app_id', 'app_secret' ),
				'twitter'   => array( 'oauth_token', 'token_secret' ),
				'gplus'     => array( 'client_id', 'client_secret', 'redirect_uri' ),
				'linkedin'  => array( 'client_id', 'client_secret', 'redirect_uri' ),
				'microsoft' => array( 'app_id', 'app_secret', 'redirect_uri' ),
				'github'    => array( 'client_id', 'client_secret', 'redirect_uri' ),
				'discord'   => array( 'client_id', 'client_secret', 'redirect_uri' ),
				'wordpress' => array( 'client_id', 'client_secret', 'redirect_uri' ),
				'apple'     => array( 'service_id', 'key_id', 'team_id', 'p_key' ),
			);

			return $required_fields[ $provider ] ?? array();
		}
	}
}
