<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * LoginPress_Social_Login_Check
 *
 * @package LoginPress_Social_Login
 * @since 1.0.0
 * @version 6.1.0
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( ! class_exists( 'LoginPress_Social_Login_Check' ) ) {
	include_once __DIR__ . '/traits/providers-1.php';
	include_once __DIR__ . '/traits/providers-2.php';
	include_once __DIR__ . '/traits/providers-3.php';
	include_once __DIR__ . '/traits/providers-4.php';
	/**
	 * LoginPress_Social_Login_Check
	 *
	 * @since 1.0.0
	 * @version 6.1.0
	 */
	class LoginPress_Social_Login_Check {
		use LoginPress_Social_Login_Providers_Trait;
		use LoginPress_Social_Login_Providers2_Trait;
		use LoginPress_Social_Login_Providers3_Trait;
		use LoginPress_Social_Login_Providers4_Trait;

		/**
		 * Class constructor.
		 *
		 * @return void
		 * @since 1.0.0
		 */
		public function __construct() {
				$this->set_redirect_to();
				$this->loginpress_check();

			if ( isset( $_REQUEST['oauth_verifier'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$lp_twitter_oauth = get_option( 'loginpress_twitter_oauth' );
				if ( isset( $lp_twitter_oauth['oauth_token'] ) ) {
					$this->loginpress_on_twitter_login();
				}
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback flow, state parameter provides CSRF protection.
			if ( isset( $_GET['code'] ) && isset( $_GET['state'] ) ) {
				if ( isset( $_GET['lpsl_login_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$exploder = explode( '_', sanitize_text_field( wp_unslash( $_GET['lpsl_login_id'] ) ) ); // phpcs:ignore
					if ( 'twitter' === $exploder[0] ) {
						$this->loginpress_on_twitter_login();
					}
				}
			}
		}

		/**
		 * Set Cookie for the `redirect_to` args.
		 *
		 * @return void
		 * @since 3.0.0
		 * @version 6.1.0
		 */
		public function set_redirect_to() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Redirect parameter for OAuth flow.
			if ( isset( $_REQUEST['redirect_to'] ) ) {
				$redirect_to = esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ); // phpcs:ignore
				// 60 seconds ( 1 minute) * 20 = 20 minutes.
				setcookie( 'lg_redirect_to', $redirect_to, time() + ( 60 * 20 ) ); // phpcs:ignore WordPress.VIP.RestrictedFunctions.cookies_setcookie
			}
		}
		/**
		 * Execute the specific Social login.
		 *
		 * @since 1.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function loginpress_check() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verification parameter for OAuth provider verification.
			if ( isset( $_GET['verification'] ) ) {
				set_transient( 'loginpress_verify_status', 1, 120 );
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback flow, state/code parameters provide CSRF protection.
			if ( isset( $_GET['lpsl_login_id'] ) ) {
				$exploder = explode( '_', sanitize_text_field( wp_unslash( $_GET['lpsl_login_id'] ) ) ); // phpcs:ignore
				if ( 'facebook' === $exploder[0] ) {
					if ( version_compare( PHP_VERSION, '5.4.0', '<' ) ) {
						esc_html_e( 'The Facebook SDK requires PHP version 5.4 or higher. Please notify about this error to site admin.', 'loginpress-pro' );
						die();
					}
					$this->loginpress_on_facebook_login();
				} elseif ( 'twitter' === $exploder[0] ) {
					$this->loginpress_on_twitter_login();
				} elseif ( 'gplus' === $exploder[0] ) {
					$this->loginpress_on_google_login();
				} elseif ( 'linkedin' === $exploder[0] ) {
					$this->loginpress_on_linkedin_login();
				} elseif ( 'microsoft' === $exploder[0] ) {
					$this->loginpress_on_microsoft_login();
				} elseif ( 'apple' === $exploder[0] ) {
					$this->loginpress_on_apple_login();
				} elseif ( 'github' === $exploder[0] ) {
					$this->loginpress_on_github_login();
				} elseif ( 'discord' === $exploder[0] ) {
					$this->loginpress_on_discord_login();
				} elseif ( 'wordpress' === $exploder[0] ) { // phpcs:ignore
					$this->loginpress_on_wordpress_login();
				} elseif ( 'amazon' === $exploder[0] ) {
					$this->loginpress_on_amazon_login();
				} elseif ( 'pinterest' === $exploder[0] ) {
					$this->loginpress_on_pinterest_login();
				} elseif ( 'disqus' === $exploder[0] ) {
					$this->loginpress_on_disqus_login();
				} elseif ( 'reddit' === $exploder[0] ) {
					$this->loginpress_on_reddit_login();
				} elseif ( 'spotify' === $exploder[0] ) {
					$this->loginpress_on_spotify_login();
				} elseif ( 'twitch' === $exploder[0] ) {
					$this->loginpress_on_twitch_login();
				}
			}

			if ( isset( $_GET['state'] ) && false !== strpos( sanitize_text_field( wp_unslash( $_GET['state'] ) ), 'lpsl_login=microsoft' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$this->loginpress_on_microsoft_login();
			}
			if ( isset( $_GET['state'] ) && false !== strpos( sanitize_text_field( wp_unslash( $_GET['state'] ) ), 'pinterest' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$this->loginpress_on_pinterest_login();
			}
		}

		/**
		 * Check if the user is verifying the provider.
		 *
		 * @return bool True if verification flow is active, false otherwise.
		 * @since 6.0.0
		 */
		private function lp_check_verification_flow() {
			if ( get_transient( 'loginpress_verify_status' ) ) {
				echo '<script>
					if (window.opener) {
						window.close();
						window.opener.postMessage("verified", window.location.origin);
						
					}
				</script>';
				delete_transient( 'loginpress_verify_status' );
				return true;
			}
			return false;
		}

		/**
		 * Create result object for loginpress.
		 *
		 * @since 5.0.0
		 * @param array  $profile   Profile data.
		 * @param string $deutype  Type of the social login.
		 * @param array  $overrides Overrides for specific providers.
		 * @return object Result object.
		 */
		public function loginpress_create_result_obj( $profile, $deutype, $overrides = array() ) {

			// initialize the result object.
			$result = new stdClass();

			// Default values.
			$result->status   = 'SUCCESS';
			$result->deutype  = $deutype;
			$result->gender   = '';
			$result->url      = '';
			$result->about    = '';
			$result->deuimage = 'https://secure.gravatar.com/avatar/75d23af433e0cea4c0e45a56dba18b30?s=96&d=mm';

			// Handle provider-specific logic.
			switch ( $deutype ) {
				case 'github':
					$email_to_parse = isset( $profile[1]['email'] ) ? $profile[1]['email'] : '';
					preg_match( '/^(\d+)\+([^\@]+)@/', $email_to_parse, $matches );
					$result->deuid      = isset( $matches[1] ) ? $matches[1] : '';
					$result->first_name = isset( $matches[2] ) ? $matches[2] : '';
					$result->last_name  = '';
					$result->email      = isset( $profile[0]['email'] ) ? $profile[0]['email'] : '';
					$result->username   = ! empty( $matches[2] ) ? strtolower( $matches[2] ) : $profile[0]['email'];
					break;

				case 'WordPress':
					$result->deuid      = isset( $profile['ID'] ) ? $profile['ID'] : '';
					$result->first_name = isset( $profile['display_name'] ) ? $profile['display_name'] : '';
					$result->last_name  = '';
					$result->email      = isset( $profile['email'] ) ? $profile['email'] : '';
					$result->username   = ! empty( $profile['display_name'] ) ? strtolower( $profile['display_name'] ) : $profile['email'];
					$result->deuimage   = $profile['avatar_URL'] ?? $result->deuimage;
					$result->deuimage   = isset( $profile['avatar_URL'] ) ? $profile['avatar_URL'] : $result->deuimage;
					break;

				case 'linkedin':
					$result->deuid         = isset( $overrides['deuid'] ) ? $overrides['deuid'] : '';
					$result->first_name    = isset( $overrides['first_name'] ) ? $overrides['first_name'] : '';
					$result->last_name     = isset( $overrides['last_name'] ) ? $overrides['last_name'] : '';
					$result->email         = ! empty( $overrides['email_address'] ) ? $overrides['email_address'] : $result->deuid . '@linkedin.com';
					$result->username      = strtolower( $result->first_name . '_' . $result->last_name );
					$result->gender        = 'N/A';
					$result->deuimage      = isset( $overrides['large_avatar'] ) ? $overrides['large_avatar'] : $result->deuimage;
					$result->error_message = '';
					break;

				case 'gplus':
					$result->deuid      = isset( $profile['sub'] ) ? $profile['sub'] : '';
					$result->first_name = isset( $profile['given_name'] ) ? $profile['given_name'] : '';
					$result->last_name  = '';
					$result->email      = isset( $profile['email'] ) ? $profile['email'] : '';
					$result->username   = ! empty( $profile['given_name'] ) ? strtolower( $profile['given_name'] ) : $profile['email'];
					$result->deuimage   = isset( $profile['picture'] ) ? $profile['picture'] : $result->deuimage;
					break;

				case 'amazon':
					$result->deuid      = isset( $profile['user_id'] ) ? $profile['user_id'] : '';
					$result->first_name = isset( $profile['name'] ) ? $profile['name'] : '';
					$result->last_name  = '';
					$result->email      = isset( $profile['email'] ) ? $profile['email'] : '';
					$result->username   = ! empty( $profile['name'] ) ? strtolower( $profile['name'] ) : $profile['email'];
					break;

				case 'pinterest':
					$result->deuid      = isset( $profile['id'] ) ? $profile['id'] : '';
					$result->first_name = ! empty( $profile['business_name'] ) ? $profile['business_name'] : $profile['name'];
					$result->last_name  = '';
					$result->email      = isset( $overrides['email'] ) ? $overrides['email'] : '';
					$result->username   = ! empty( $profile['username'] ) ? strtolower( $profile['username'] ) : $overrides['email'];
					$result->deuimage   = isset( $profile['profile_image'] ) ? $profile['profile_image'] : $result->deuimage;
					break;

				case 'reddit':
					$result->deuid      = isset( $profile['id'] ) ? $profile['id'] : '';
					$result->first_name = ! empty( $profile['name'] ) ? $profile['name'] : $profile['subreddit']['name'];
					$result->last_name  = '';
					$result->email      = isset( $overrides['email'] ) ? $overrides['email'] : '';
					$result->username   = ! empty( $profile['name'] ) ? strtolower( $profile['name'] ) : $overrides['email'];
					$result->deuimage   = $profile['icon_img'] ?? $result->deuimage;
					$result->deuimage   = isset( $profile['icon_img'] ) ? $profile['icon_img'] : $result->deuimage;
					break;

				case 'spotify':
					$result->deuid      = isset( $profile['id'] ) ? $profile['id'] : '';
					$result->first_name = isset( $profile['display_name'] ) ? $profile['display_name'] : '';
					$result->last_name  = '';
					$result->email      = isset( $profile['email'] ) ? $profile['email'] : $profile['display_name'] . '@spotify.com';
					$result->username   = ! empty( $profile['display_name'] ) ? strtolower( $profile['display_name'] ) : $overrides['email'];
					$result->deuimage   = isset( $profile['images'][0]['url'] ) ? $profile['images'][0]['url'] : $result->deuimage;
					break;

				case 'twitch':
					$result->deuid      = isset( $profile['data'][0]['id'] ) ? $profile['data'][0]['id'] : '';
					$result->first_name = ! empty( $profile['data'][0]['display_name'] ) ? $profile['data'][0]['display_name'] : $profile['data'][0]['login'];
					$result->last_name  = '';
					$result->email      = isset( $profile['data'][0]['email'] ) ? $profile['data'][0]['email'] : $profile['display_name'] . '@spotify.com';
					$result->username   = ! empty( $profile['data'][0]['display_name'] ) ? strtolower( $profile['data'][0]['display_name'] ) : $overrides['email'];
					$result->deuimage   = isset( $profile['data'][0]['profile_image_url'] ) ? $profile['data'][0]['profile_image_url'] : $result->deuimage;
					break;

				case 'discord':
					$result->deuid      = isset( $profile['id'] ) ? $profile['id'] : '';
					$result->first_name = isset( $profile['global_name'] ) ? $profile['global_name'] : '';
					$result->last_name  = '';
					$result->email      = isset( $profile['email'] ) ? $profile['email'] : '';
					$result->username   = ! empty( $profile['username'] ) ? strtolower( $profile['username'] ) : $profile['email'];
					// Extract user details.
					$user_id     = $profile['id'];
					$avatar_hash = $profile['avatar'];

					// Determine avatar format.
					$format = ( strpos( $avatar_hash, 'a_' ) === 0 ) ? 'gif' : 'png';

					// Construct the avatar URL.
					$avatar_url       = "https://cdn.discordapp.com/avatars/{$user_id}/{$avatar_hash}.{$format}";
					$result->deuimage = $profile['avatar'] ? $avatar_url : 'https://secure.gravatar.com/avatar/75d23af433e0cea4c0e45a56dba18b30?s=96&d=mm';
					break;

				case 'disqus':
					$result->deuid      = isset( $profile['response']['id'] ) ? $profile['response']['id'] : '';
					$result->first_name = isset( $profile['response']['name'] ) ? $profile['response']['name'] : '';
					$result->last_name  = '';
					$result->email      = ( $profile['response']['username'] ?? '' ) . '@disqus.com';
					$result->username   = ! empty( $profile['response']['username'] ) ? strtolower( $profile['response']['username'] ) : $result->email;
					$result->deuimage   = $profile['response']['avatar']['permalink'] ?? $result->deuimage;
					break;

				case 'apple':
					if ( is_object( $profile ) ) {
						$result->deuid      = isset( $profile->deuid ) ? $profile->deuid : '';
						$result->first_name = isset( $profile->first_name ) ? $profile->first_name : '';
						$result->last_name  = isset( $profile->last_name ) ? $profile->last_name : '';
						$result->email      = isset( $profile->email ) ? $profile->email : '';
						$result->username   = isset( $profile->username ) ? $profile->username : '';
					}
					break;
			}

			return $result;
		}


		/**
		 * Function loginpress_is_eligible_social_domain to check whether email is eligible or not.
		 *
		 * @param mixed $email Full email address o user taken from social provider.
		 * @param mixed $eligible_domains List of partial eligible domains.
		 * @return bool $found If string is found or not.
		 * @since 3.0.0
		 */
		public function loginpress_is_eligible_social_domain( $email, $eligible_domains ) {
			$found = false;

			foreach ( $eligible_domains as $partial ) {
				if ( strpos( $email, $partial ) !== false ) {
					$found = true;
					break;
				}
			}
			return $found;
		}

		/**
		 * Show loginpress WordPress social login error/s.
		 *
		 * @param int    $user The User ID.
		 * @param string $username The Username.
		 * @param string $password The Password.
		 *
		 * @return WP_Error $error the Error.
		 * @since 5.0.2.
		 */
		public function loginpress_wp_social_login_error( $user, $username, $password ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			$error = new WP_Error();
			/**
			 * Filter the error message shown when WordPress.com email is not verified.
			 *
			 * @since 5.0.2
			 *
			 * @param string $message Default error message.
			 * @param string $username Entered username.
			 */
			$message = apply_filters(
				'loginpress_wp_social_login_unverified_email_message',
				sprintf(    /* Translators: The Error Message. */
					__( '%1$sERROR%2$s: Your WordPress.com account email is not verified. Please verify your email at WordPress.com before logging in.', 'loginpress-pro' ),
					'<strong>',
					'</strong>'
				),
				$username
			);

			$error->add( 'loginpress_social_login', $message );

			return $error;
		}

		/**
		 * Check if user is restricted from social login.
		 *
		 * @param WP_User|false $user_object User object.
		 * @return bool True if user is restricted.
		 * @since 6.0.0
		 */
		private function lp_is_user_restricted_from_sl( $user_object ) {
			/**
			 * Fires when a user is checked for social login restriction.
			 *
			 * This enables logging, notifications, or custom logic by site owners.
			 *
			 * @since 6.0.0
			 *
			 * @param WP_User $user_object The user object being checked.
			 */
			do_action( 'loginpress_before_social_login_restricted', $user_object );
			if ( ! $user_object || ! is_a( $user_object, 'WP_User' ) ) {
				return false;
			}

			// Check if user is super admin (multisite only).
			if ( is_multisite() && is_super_admin( $user_object->ID ) ) {
				return true;
			} elseif ( in_array( 'administrator', $user_object->roles, true ) ) {
				return true;
			}

			$is_restricted = false;
			/**
			 * Filters whether a user is restricted from using social login.
			 *
			 * Developers can use this filter to apply advanced restrictions,
			 * such as limiting by email domain, user meta, or other conditions.
			 *
			 * @since 6.0.0
			 *
			 * @param bool    $is_restricted Whether the user is restricted. Default false.
			 * @param WP_User $user_object   The user object being checked.
			 */
			$is_restricted = apply_filters(
				'loginpress_is_user_restricted_from_social_login',
				$is_restricted,
				$user_object
			);

			return $is_restricted;
		}

		/**
		 * Show social login restriction error.
		 *
		 * @return void
		 * @since 6.0.0
		 */
		private function lp_show_social_login_restriction_error() {
			wp_safe_redirect(
				add_query_arg(
					array(
						'lp_social_login_admin_error' => 'true',
						'lp_social_restricted'        => '1',
					),
					wp_login_url()
				)
			);
			exit;
		}
	}
}
$lpsl_login_check = new LoginPress_Social_Login_Check();
