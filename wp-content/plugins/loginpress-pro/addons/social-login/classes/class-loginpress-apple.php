<?php
/**
 * LoginPress Apple Social Login.
 *
 * @package LoginPress Social Login
 * @since 4.0.0
 * @version 6.1.0
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

require_once LOGINPRESS_SOCIAL_DIR_PATH . 'sdk/apple/vendor/firebase/php-jwt/src/JWT.php';

use Firebase\JWT\JWT;

if ( ! class_exists( 'LoginPress_Apple' ) ) {

	/**
	 * LoginPress_Apple
	 *
	 * @since 4.0.0
	 * @version 6.1.0
	 */
	class LoginPress_Apple {

		/**
		 * Apple login functionality.
		 *
		 * @return object|stdClass Response object with user data or error information.
		 * @since 4.0.0
		 * @version 6.1.0
		 */
		public function apple_login() {
			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';
			$loginpress_utilities = new LoginPress_Social_Utilities();

			$request        = wp_unslash( $_REQUEST ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$site           = $loginpress_utilities->loginpress_site_url();
			$call_back_url  = $loginpress_utilities->loginpress_callback_url();
			$response       = new stdClass();
			$lp_apple_user  = new stdClass();
			$exploder       = isset( $_GET['lpsl_login_id'] ) ? explode( '_', sanitize_text_field( wp_unslash( $_GET['lpsl_login_id'] ) ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$action         = isset( $exploder[1] ) ? $exploder[1] : '';
			$_social_logins = get_option( 'loginpress_social_logins' );
			// Parse the URL to extract components.
			$url_parts = wp_parse_url( $call_back_url );

			if ( isset( $url_parts['host'] ) && isset( $url_parts['path'] ) ) {
				$host = $url_parts['host'];
				$path = $url_parts['path'];

				// Check if the path is not wp-login.php.
				if ( false === strpos( $path, 'wp-login.php' ) ) {
					// Preserve the query string if it exists.
					$query_string = isset( $url_parts['query'] ) ? '?' . $url_parts['query'] : '';

					// Replace the path with wp-login.php.
					$call_back_url = rtrim( $url_parts['scheme'] . '://' . $host, '/' ) . '/wp-login.php' . $query_string;
				}
			}

			$config = array(
				'app_id'     => $_social_logins['apple_service_id'],
				'app_secret' => isset( $_social_logins['apple_secret'] ) && ! empty( $_social_logins['apple_secret'] ) ? $_social_logins['apple_secret'] : $this->generate_token( $_social_logins ),
				'scope'      => 'name email',
			);

			$callback = $call_back_url . 'lpsl_login_id=apple_check';

			if ( 'login' === $action ) {
				// Redirect user to Apple login.
				$apple_login_url = $this->get_apple_login_url( $config, $callback );
				$loginpress_utilities->redirect( $apple_login_url );
			} else {
				// Handle the Apple login response.
				if ( isset( $request['error'] ) ) {
					$response->status        = 'ERROR';
					$response->error_code    = 2;
					$response->error_message = 'INVALID AUTHORIZATION';
					return $response;
				}

				if ( isset( $request['code'] ) ) {
					$code = sanitize_text_field( wp_unslash( $request['code'] ) );
					// Exchange the authorization code for a token.
					$token_response = $this->exchange_code_for_token( $config, $code );

					if ( isset( $token_response['id_token'] ) ) {
						// Decode the ID token to extract user details.
						$user_info = $this->decode_id_token( $token_response['id_token'] );

						if ( ! empty( $user_info ) ) {
							$lp_apple_user->status  = 'SUCCESS';
							$lp_apple_user->deuid   = $user_info['sub'];
							$lp_apple_user->deutype = 'apple';

							$first_name = '';
							$last_name  = '';

							if ( isset( $_POST['user'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Check if Apple user data is present.
								$user_data  = json_decode( sanitize_text_field( wp_unslash( $_POST['user'] ) ), true ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
								$first_name = sanitize_text_field( $user_data['name']['firstName'] ?? '' );
								$last_name  = sanitize_text_field( $user_data['name']['lastName'] ?? '' );
							}

							$lp_apple_user->first_name    = $first_name;
							$lp_apple_user->last_name     = $last_name;
							$lp_apple_user->email         = $user_info['email'];
							$lp_apple_user->username      = $user_info['email'];
							$lp_apple_user->error_message = '';
						} else {
							$lp_apple_user->status        = 'ERROR';
							$lp_apple_user->error_code    = 2;
							$lp_apple_user->error_message = 'INVALID USER DATA';
						}
					} else {
						$response->status        = 'ERROR';
						$response->error_code    = 3;
						$response->error_message = 'TOKEN EXCHANGE FAILED';
						return $response;
					}
				}
			}

			return $lp_apple_user;
		}

		/**
		 * Generate the Apple login URL.
		 *
		 * @param array  $config Configuration array with app credentials.
		 * @param string $callback Callback URL for Apple login.
		 * @return string Apple authorization URL.
		 * @since 4.0.0
		 * @version 6.1.0
		 */
		private function get_apple_login_url( $config, $callback ) {
			$query = http_build_query(
				array(
					'client_id'     => $config['app_id'],
					'redirect_uri'  => $callback,
					'response_type' => 'code',
					'scope'         => $config['scope'],
					'response_mode' => 'form_post',
					'state'         => wp_generate_uuid4(),
				)
			);
			return 'https://appleid.apple.com/auth/authorize?' . $query;
		}

		/**
		 * Exchange authorization code for a token.
		 *
		 * @param array  $config Configuration array with app credentials.
		 * @param string $code Authorization code from Apple.
		 * @return array Token response from Apple.
		 * @since 4.0.0
		 * @version 6.1.0
		 */
		private function exchange_code_for_token( $config, $code ) {
			$response = wp_remote_post(
				'https://appleid.apple.com/auth/token',
				array(
					'body' => array(
						'client_id'     => $config['app_id'],
						'client_secret' => $config['app_secret'],
						'code'          => $code,
						'grant_type'    => 'authorization_code',
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				return array( 'error' => 'Request failed: ' . $response->get_error_message() );
			}

			return json_decode( wp_remote_retrieve_body( $response ), true );
		}

		/**
		 * Decode the Apple ID token.
		 *
		 * @param string $id_token Apple ID token to decode.
		 * @return array Decoded user information from token.
		 * @since 4.0.0
		 * @version 6.1.0
		 */
		private function decode_id_token( $id_token ) {
			$parts = explode( '.', $id_token );
			if ( count( $parts ) === 3 ) {
				return json_decode( base64_decode( $parts[1] ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Decoding Apple JWT token.
			}
			return array();
		}

		/**
		 * Generate the JWT token.
		 *
		 * @param array $_social_logins Social login settings array.
		 * @return string Generated JWT token.
		 * @since 4.0.0
		 * @version 6.1.0
		 */
		public function generate_token( $_social_logins ) {
			// Define your values.
			$team_id     = $_social_logins['apple_team_id'];          // Found in Apple Developer account.
			$key_id      = $_social_logins['apple_key_id'];            // Found in Apple Developer account under Keys.
			$client_id   = $_social_logins['apple_service_id'];      // Your Service ID, e.g., com.example.app.
			$private_key = $_social_logins['apple_p_key']; // Path to your private key file.
			// Create the JWT Header.
			$header = array(
				'alg' => 'ES256',               // Algorithm.
				'kid' => $key_id,               // Key ID.
			);

			// Create the JWT Payload.
			$payload = array(
				'iss' => $team_id,              // Team ID.
				'iat' => time(),                // Issued at time.
				'exp' => time() + ( MONTH_IN_SECONDS * 6 ), // Expiration (6 months from now).
				'aud' => 'https://appleid.apple.com', // Audience.
				'sub' => $client_id,            // Subject (Client ID).
			);

			try {
				// Attempt to generate the JWT.
				$jwt = JWT::encode( $payload, $private_key, 'ES256', $header['kid'] );
				// Save the JWT to the 'apple_secret' option in the database.
				$_social_logins['apple_secret'] = $jwt;
				update_option( 'loginpress_social_logins', $_social_logins );

				// Return the generated JWT if successful.
				return $jwt;
			} catch ( Exception $e ) {
				// Return a generic error message.
				wp_die(
					esc_html__( 'Failed to generate token. Please check your credentials.', 'loginpress-pro' ),
					esc_html__( 'Error', 'loginpress-pro' ),
					array(
						'response'  => 500,
						'back_link' => true, // Optionally add a "back" link.
					)
				);
			}
		}
	}
}
