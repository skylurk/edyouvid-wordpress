<?php
/**
 * Twitter Login
 *
 * @package LoginPress Social Login
 * @since 1.0.0
 * @version 6.1.0
 */

use Abraham\TwitterOAuth\TwitterOAuth;
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( ! class_exists( 'LoginPress_Twitter' ) ) {

	/**
	 * LoginPress_Twitter
	 *
	 * @since 1.0.0
	 * @version 6.1.0
	 */
	class LoginPress_Twitter {
		/**
		 * Twitter Login oauth 2.0.
		 *
		 * @return object $response The Twitter login response.
		 * @since 1.0.0
		 * @version 6.1.0
		 */
		public function twitter_login_oauth2() {
			require LOGINPRESS_SOCIAL_DIR_PATH . 'sdk/twitter/autoload.php';
			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';

			$loginpress_utilities = new LoginPress_Social_Utilities();
			$request              = wp_unslash( $_REQUEST ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$response             = new stdClass();

			$login_settings = get_option( 'loginpress_social_logins' );
			$client_id      = $login_settings['twitter_oauth_token'];  // Twitter API v2 Client ID.
			$client_secret  = $login_settings['twitter_token_secret'];
			$callback_url   = $login_settings['twitter_callback_url'];
			// Handle Twitter Redirect with Authorization Code.
			if ( isset( $request['code'] ) && isset( $request['state'] ) ) {
				$code           = sanitize_text_field( $request['code'] );
				$returned_state = sanitize_text_field( $request['state'] );

				// Validate State Parameter (CSRF Protection).
				$stored_state = get_transient( 'loginpress_twitter_auth_state' );
				if ( ! $stored_state || $stored_state !== $returned_state ) {
					$response->status        = 'ERROR';
					$response->error_message = __( 'Invalid state parameter. Authentication request may have been tampered with.', 'loginpress-pro' );
					return $response;
				}

				// Retrieve Code Verifier for PKCE.
				$code_verifier = get_transient( 'loginpress_twitter_code_verifier' );
				if ( ! $code_verifier ) {
					$response->status        = 'ERROR';
					$response->error_message = __( 'Session expired. Please try logging in again.', 'loginpress-pro' );
					return $response;
				}

				// Exchange Code for Access Token (FIXED).
				$token_url   = 'https://api.twitter.com/2/oauth2/token';
				$auth_header = base64_encode( $client_id . ':' . $client_secret ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Required for Twitter OAuth Basic Authentication.

				$body = array(
					'code'          => $code,
					'grant_type'    => 'authorization_code',
					'client_id '    => $client_id,
					'redirect_uri'  => $callback_url,
					'code_verifier' => $code_verifier,
				);

				// phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_init, WordPress.WP.AlternativeFunctions.curl_curl_setopt, WordPress.WP.AlternativeFunctions.curl_curl_exec, WordPress.WP.AlternativeFunctions.curl_curl_close -- Twitter OAuth2 requires specific headers and authentication flow not fully supported by wp_remote_post.
				$ch = curl_init();
				curl_setopt( $ch, CURLOPT_URL, $token_url );
				curl_setopt( $ch, CURLOPT_POST, true );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $body ) );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
				curl_setopt(
					$ch,
					CURLOPT_HTTPHEADER,
					array(
						'Authorization: Basic ' . $auth_header,
						'Content-Type: application/x-www-form-urlencoded',
					)
				);
				$token_response = json_decode( curl_exec( $ch ), true );
				curl_close( $ch );
				// phpcs:enable WordPress.WP.AlternativeFunctions.curl_curl_init, WordPress.WP.AlternativeFunctions.curl_curl_setopt, WordPress.WP.AlternativeFunctions.curl_curl_exec, WordPress.WP.AlternativeFunctions.curl_curl_close

				// Validate Access Token Response.
				if ( isset( $token_response['access_token'] ) ) {
					$access_token = $token_response['access_token'];

					// Fetch User Info.
					$user_url = 'https://api.twitter.com/2/users/me?user.fields=id,name,profile_image_url,username';
					// phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_init, WordPress.WP.AlternativeFunctions.curl_curl_setopt, WordPress.WP.AlternativeFunctions.curl_curl_exec, WordPress.WP.AlternativeFunctions.curl_curl_close -- Twitter OAuth2 requires specific headers and authentication flow not fully supported by wp_remote_get.
					$ch = curl_init();
					curl_setopt( $ch, CURLOPT_URL, $user_url );
					curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
					curl_setopt(
						$ch,
						CURLOPT_HTTPHEADER,
						array(
							"Authorization: Bearer $access_token",
						)
					);
					$user_response = json_decode( curl_exec( $ch ), true );
					curl_close( $ch );
					// phpcs:enable WordPress.WP.AlternativeFunctions.curl_curl_init, WordPress.WP.AlternativeFunctions.curl_curl_setopt, WordPress.WP.AlternativeFunctions.curl_curl_exec, WordPress.WP.AlternativeFunctions.curl_curl_close
					if ( isset( $user_response['data'] ) ) {

						$user_profile = $user_response['data'];

						$email    = $user_profile['email'] ?? $user_profile['username'] . '@twitter.com';
						$username = strtolower( $user_profile['username'] );
						$wp_user  = get_user_by( 'email', $email );

						$response->status     = 'SUCCESS';
						$response->deuid      = $user_profile['id'];
						$response->deutype    = 'twitter';
						$response->username   = $username;
						$response->email      = $email;
						$response->first_name = explode( ' ', $user_profile['name'], 2 )[0];
						$response->last_name  = explode( ' ', $user_profile['name'], 2 )[1] ?? '';
						$response->deuimage   = $user_profile['profile_image_url'];
						$response->location   = $user_profile['location'] ?? '';
					} else {
						$response->status        = 'ERROR';
						$response->error_message = __( 'Failed to fetch user profile.', 'loginpress-pro' );
					}
				} else {
					$response->status        = 'ERROR';
					$response->error_message = __( 'Failed to retrieve access token.', 'loginpress-pro' );
				}
			} else {
				// Generate Twitter Login URL.
				$authorize_url = 'https://twitter.com/i/oauth2/authorize';
				$code_verifier = bin2hex( random_bytes( 32 ) );  // PKCE Code Verifier.
				set_transient( 'loginpress_twitter_code_verifier', $code_verifier, 300 ); // Store for 5 minutes.
				$code_challenge = rtrim( strtr( base64_encode( hash( 'sha256', $code_verifier, true ) ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Required for Twitter OAuth2 PKCE code challenge.

				// Generate State Parameter for Security.
				$state = bin2hex( random_bytes( 16 ) );
				set_transient( 'loginpress_twitter_auth_state', $state, 300 );

				$query_params = array(
					'response_type'         => 'code',
					'client_id'             => $client_id,
					'redirect_uri'          => $callback_url,
					'scope'                 => 'tweet.read users.read offline.access',
					'state'                 => $state,  // Include CSRF protection.
					'code_challenge'        => $code_challenge,
					'code_challenge_method' => 'S256',
				);
				$auth_url     = $authorize_url . '?' . http_build_query( $query_params );

				$loginpress_utilities->redirect( $auth_url );
			}

			return $response;
		}

		/**
		 * Twitter Login oauth 1.1.
		 *
		 * @return object $response The Twitter login response.
		 * @since 1.0.0
		 * @version 6.1.0
		 */
		public function twitter_login() {

			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';
			require LOGINPRESS_SOCIAL_DIR_PATH . 'sdk/twitter/autoload.php';
			$loginpress_utilities = new LoginPress_Social_Utilities();

			$request      = $_REQUEST; // @codingStandardsIgnoreLine.
			$site         = $loginpress_utilities->loginpress_site_url();
			$callback_url = $loginpress_utilities->loginpress_callback_url();
			$response     = new stdClass();

			$lp_twitter_oauth = get_option( 'loginpress_twitter_oauth' );
			$login_settings   = get_option( 'loginpress_social_logins' );

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback, verifier acts as CSRF protection.
			if ( isset( $_REQUEST['oauth_verifier'], $_REQUEST['oauth_token'] ) && sanitize_text_field( wp_unslash( $_REQUEST['oauth_token'] ) ) === $lp_twitter_oauth['oauth_token'] ) {
				$request_token                       = array();
				$request_token['oauth_token']        = $lp_twitter_oauth['oauth_token'];
				$request_token['oauth_token_secret'] = $lp_twitter_oauth['oauth_token_secret'];

				$connection   = new TwitterOAuth( $login_settings['twitter_oauth_token'], $login_settings['twitter_token_secret'], $request_token['oauth_token'], $request_token['oauth_token_secret'] );
				$access_token = $connection->oauth( 'oauth/access_token', array( 'oauth_verifier' => sanitize_text_field( wp_unslash( $_REQUEST['oauth_verifier'] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				update_option( 'loginpress_twitter_access', $access_token );
			}

			if ( ! isset( $request['oauth_token'] ) && ! isset( $request['oauth_verifier'] ) ) {
				// Get identity from user and redirect browser to OpenID Server.
				if ( ! isset( $request['oauth_token'] ) || '' === $request['oauth_token'] ) {
					$twitter_obj = new TwitterOAuth( $login_settings['twitter_oauth_token'], $login_settings['twitter_token_secret'] );

					try {
						$request_token = $twitter_obj->oauth( 'oauth/request_token', array( 'oauth_verifier' => $login_settings['twitter_callback_url'] ) );
					} catch ( Exception $e ) {
						echo esc_html( $e );
					}

					$_SESSION['oauth_token']        = isset( $request_token['oauth_token'] ) ? $request_token['oauth_token'] : ''; // phpcs:ignore WordPress.VIP.SessionVariableUsage.SessionVarsProhibited
					$_SESSION['oauth_token_secret'] = isset( $request_token['oauth_token_secret'] ) ? $request_token['oauth_token_secret'] : ''; // phpcs:ignore WordPress.VIP.SessionVariableUsage.SessionVarsProhibited

					$session_array = array(
						'oauth_token'    => isset( $_SESSION['oauth_token'] ) ? sanitize_text_field( $_SESSION['oauth_token'] ) : '', // phpcs:ignore WordPress.VIP.SessionVariableUsage.SessionVarsProhibited, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
					'oauth_token_secret' => isset( $_SESSION['oauth_token_secret'] ) ? sanitize_text_field( $_SESSION['oauth_token_secret'] ) : '', // phpcs:ignore WordPress.VIP.SessionVariableUsage.SessionVarsProhibited, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
					);
					update_option( 'loginpress_twitter_oauth', $session_array );

					$url = $twitter_obj->url( 'oauth/authorize', array( 'oauth_token' => $request_token['oauth_token'] ) );
					/* If last connection failed don't display authorization link. */
					if ( $url ) :
						try {
							$loginpress_utilities->redirect( $url );
						} catch ( Exception $e ) {
							$response->status        = 'ERROR';
							$response->error_code    = 2;
							$response->error_message = 'Could not get AuthorizeUrl.';
						}
					endif;
				} else {
					$response->status        = 'ERROR';
					$response->error_code    = 2;
					$response->error_message = 'INVALID AUTHORIZATION';
				}
			} elseif ( isset( $request['oauth_token'] ) && isset( $request['oauth_verifier'] ) ) {
				/* Create TwitterAuth object with app key/secret and token key/secret from default phase. */
				$access_token = get_option( 'loginpress_twitter_access' );
				$twitter_obj  = new TwitterOAuth( $login_settings['twitter_oauth_token'], $login_settings['twitter_token_secret'], $access_token['oauth_token'], $access_token['oauth_token_secret'] );
				/* Remove no longer needed request tokens. */
				$params = array(
					'include_email'    => 'true',
					'include_entities' => 'true',
					'skip_status'      => 'true',
				);

				$user_profile = $twitter_obj->get( 'account/verify_credentials', $params );

				/* Request access twitterObj from twitter. */
				$response->status        = 'SUCCESS';
				$response->deuid         = $user_profile->id;
				$response->deutype       = 'twitter';
				$response->name          = explode( ' ', $user_profile->name, 2 );
				$response->first_name    = $response->name[0];
				$response->last_name     = ( isset( $response->name[1] ) ) ? $response->name[1] : '';
				$response->deuimage      = $user_profile->profile_image_url_https;
				$response->email         = isset( $user_profile->email ) ? $user_profile->email : $user_profile->screen_name . '@twitter.com';
				$response->username      = ( '' !== $user_profile->screen_name ) ? strtolower( $user_profile->screen_name ) : $user_email;
				$response->url           = $user_profile->url;
				$response->about         = isset( $user_profile->description ) ? $user_profile->description : '';
				$response->gender        = isset( $user_profile->gender ) ? $user_profile->gender : 'N/A';
				$response->location      = $user_profile->location;
				$response->error_message = '';
			} else { // User Canceled your Request.
				$response->status        = 'ERROR';
				$response->error_code    = 1;
				$response->error_message = 'USER CANCELED REQUEST';
			}
			return $response;
		}
	}
}
