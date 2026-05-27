<?php
/**
 * LoginPress Social Login Providers 4 Trait file.
 *
 * @package LoginPress Social Login
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LoginPress Social Login Providers Trait.
 *
 * Handles Some functions from loginpress-social-check.php file.
 * Handles the social logins on login page of WordPress, Spotify and Twitch.
 *
 * @package   LoginPress
 * @subpackage Traits\SocialLogin
 * @since     6.1.0
 */

if ( ! trait_exists( 'LoginPress_Social_Login_Providers4_Trait' ) ) {
	/**
	 * LoginPress Social Login Settings Trait.
	 *
	 * Handles Some functions from loginpress-social-check.php file.
	 * Handles the social logins on login page of WordPress, Spotify and Twitch.
	 *
	 * @package   LoginPress
	 * @subpackage Traits\SocialLogin
	 * @since   6.1.0
	 */
	trait LoginPress_Social_Login_Providers4_Trait {

				/**
				 * Login with WordPress Account.
				 *
				 * @since 4.0.0
				 * @version 6.1.0
				 * @return void
				 */
		public function loginpress_on_wordpress_login() {
			$lp_social_settings      = get_option( 'loginpress_social_logins' );
			$wordpress_client_id     = $lp_social_settings['wordpress_client_id'];
			$wordpress_client_secret = $lp_social_settings['wordpress_client_secret'];
			$wordpress_redirect_uri  = $lp_social_settings['wordpress_redirect_uri'];

			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';

			$loginpress_utilities = new LoginPress_Social_Utilities();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback parameter.
			if ( isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) {
				// Execute HTTP request to retrieve the access token.
				$params       = array(
					'client_id'     => $wordpress_client_id,
					'redirect_uri'  => $wordpress_redirect_uri,
					'client_secret' => $wordpress_client_secret,
					'code'          => sanitize_text_field( wp_unslash( $_GET['code'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'grant_type'    => 'authorization_code',
				);
				$response     = wp_remote_post(
					'https://public-api.wordpress.com/oauth2/token',
					array(
						'body' => $params,
					)
				);
					$response = json_decode( ( wp_remote_retrieve_body( $response ) ), true );

					// Make sure access token is valid.
				if ( isset( $response['access_token'] ) && ! empty( $response['access_token'] ) ) {

					$response = wp_remote_get(
						'https://public-api.wordpress.com/rest/v1.1/me',
						array(
							'headers' => array( 'Authorization' => 'Bearer ' . $response['access_token'] ),
						)
					);
					$profile  = json_decode( ( wp_remote_retrieve_body( $response ) ), true );
					// Make sure the profile data exists.
					if ( isset( $profile['email'] ) && isset( $profile['email_verified'] ) && true === $profile['email_verified'] ) {
						if ( $this->lp_check_verification_flow() ) {
							exit();
						}

							$result = $this->loginpress_create_result_obj( $profile, 'WordPress' );
							$role   = get_option( 'default_role' );
							global $wpdb;
							$sha_verifier     = sha1( $result->deutype . $result->deuid );
							$row          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s LIMIT 1", $result->deutype, $result->deuid, $sha_verifier ) ); // @codingStandardsIgnoreLine.
							$user_object      = get_user_by( 'email', $profile['email'] );
							$restrict_enabled = isset( $lp_social_settings['restrict_admin_sl'] ) ? $lp_social_settings['restrict_admin_sl'] : 'off';

						if ( 'on' === $restrict_enabled && $user_object && $this->lp_is_user_restricted_from_sl( $user_object ) ) {
							$this->lp_show_social_login_restriction_error();
						}
						if ( ! $row ) {
							// check if there is already a user with the email address provided from social login.
							if ( false !== $user_object ) {
								// user already there so log him in.
								$id  = $user_object->ID;
								$row = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `user_id` LIKE %d LIMIT 1", $id ) ); // @codingStandardsIgnoreLine.

								if ( ! $row ) {
									$loginpress_utilities->link_user( $id, $result );
								}
								if ( 'subscriber' === $role ) {
									$loginpress_utilities->_home_url( $user_object, 'success_wordpress', 'subscriber' );
								} else {
									$loginpress_utilities->_home_url( $user_object, 'success_wordpress' );
								}
								die();
							}
							$loginpress_utilities->register_user( $result->username, $result->email );
							$user_object = get_user_by( 'email', $result->email );
							do_action( 'loginpress_social_login_registered', $user_object );
							$id = $user_object->ID;
							$loginpress_utilities->update_usermeta( $id, $result, $role );
							if ( 'subscriber' === $role ) {
								$loginpress_utilities->_home_url( $user_object, 'success_wordpress', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'success_wordpress' );
							}
							exit();
						} elseif ( ( isset( $row[0] ) && (int) $row[0]->identifier === $result->deuid ) ) {
								$user_object = get_user_by( 'email', $result->email );
								$id          = $user_object->ID;
							if ( 'subscriber' === $role ) {
								$loginpress_utilities->_home_url( $user_object, 'success_wordpress', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'success_wordpress' );
							}

								exit();
						} else {
							// user not found in our database.
							echo esc_html__( 'user not found in our database', 'loginpress-pro' );
						}
					} elseif ( isset( $profile['email_verified'] ) && true !== $profile['email_verified'] ) {
						add_filter( 'authenticate', array( $this, 'loginpress_wp_social_login_error' ), 40, 3 );
					} else {
						add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
					}
				} else {
					add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
				}
			} else {
				// Define params and redirect to WordPress Authentication page.
				$params = array(
					'client_id'     => $wordpress_client_id,
					'response_type' => 'code',
					'redirect_uri'  => $wordpress_redirect_uri,
				);
				wp_redirect( 'https://public-api.wordpress.com/oauth2/authorize?' . http_build_query( $params ) ); // phpcs:ignore
				exit;
			}
		}

		/**
		 * Login with Spotify Account.
		 *
		 * @since 5.0.0
		 * @return void
		 */
		public function loginpress_on_spotify_login() {
			$lp_social_settings    = get_option( 'loginpress_social_logins' );
			$spotify_client_id     = $lp_social_settings['spotify_client_id'];
			$spotify_client_secret = $lp_social_settings['spotify_client_secret'];
			$spotify_redirect_uri  = $lp_social_settings['spotify_redirect_uri'];

			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';

			$loginpress_utilities = new LoginPress_Social_Utilities();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback parameter.
			if ( isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) {
				// Execute HTTP request to retrieve the access token.
				$params = array(
					'grant_type'    => 'authorization_code',
					'code'          => sanitize_text_field( wp_unslash( $_GET['code'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'redirect_uri'  => $spotify_redirect_uri,
					'client_id'     => $spotify_client_id,
					'client_secret' => $spotify_client_secret,
				);

				$response = wp_remote_post(
					'https://accounts.spotify.com/api/token',
					array(
						'body'    => $params,
						'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
					)
				);

					$response = json_decode( wp_remote_retrieve_body( $response ), true );

					// Make sure access token is valid.
				if ( isset( $response['access_token'] ) && ! empty( $response['access_token'] ) ) {
					// Retrieve user profile information.
					$profile_response = wp_remote_get(
						'https://api.spotify.com/v1/me',
						array(
							'headers' => array( 'Authorization' => 'Bearer ' . $response['access_token'] ),
						)
					);

					$profile = json_decode( wp_remote_retrieve_body( $profile_response ), true );

					if ( isset( $profile['id'] ) && ! empty( $profile['id'] ) ) {
						if ( $this->lp_check_verification_flow() ) {
							exit();
						}
						// Process the user profile information.
						$result = $this->loginpress_create_result_obj( $profile, 'spotify' );

						global $wpdb;
						$sha_verifier = sha1( $result->deutype . $result->deuid );
						$row          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s LIMIT 1", $result->deutype, $result->deuid, $sha_verifier ) ); // @codingStandardsIgnoreLine.
						$user_object  = get_user_by( 'email', $profile['email'] );
						$role         = get_option( 'default_role' );

						$restrict_enabled = isset( $lp_social_settings['restrict_admin_sl'] ) ? $lp_social_settings['restrict_admin_sl'] : 'off';

						if ( 'on' === $restrict_enabled && $user_object && $this->lp_is_user_restricted_from_sl( $user_object ) ) {
							$this->lp_show_social_login_restriction_error();
						}
						if ( ! $row ) {
							// check if there is already a user with the email address provided from social login.
							if ( false !== $user_object ) {
								// user already there so log him in.
								$id  = $user_object->ID;
								$row = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `user_id` LIKE %d LIMIT 1", $id ) ); // @codingStandardsIgnoreLine.

								if ( ! $row ) {
									$loginpress_utilities->link_user( $id, $result );
								}
								if ( 'subscriber' === $role ) {
									$loginpress_utilities->_home_url( $user_object, 'success_spotify', 'subscriber' );
								} else {
									$loginpress_utilities->_home_url( $user_object, 'success_spotify' );
								}
								die();
							}
							$loginpress_utilities->register_user( $result->username, $result->email );
							$user_object = get_user_by( 'email', $result->email );
							do_action( 'loginpress_social_login_registered', $user_object );
							$id = $user_object->ID;
							$loginpress_utilities->update_usermeta( $id, $result, $role );
							if ( 'subscriber' === $role ) {
								$loginpress_utilities->_home_url( $user_object, 'success_spotify', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'success_spotify' );
							}
							exit();
						} elseif ( ( isset( $row[0] ) && $row[0]->identifier === $result->deuid ) ) {
								$user_object = get_user_by( 'email', $result->email );
								$id          = $user_object->ID;
							if ( 'subscriber' === $role ) {
									$loginpress_utilities->_home_url( $user_object, 'success_spotify', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'success_spotify' );
							}

								exit();
						} else {
							// user not found in our database.
							echo esc_html__( 'user not found in our database', 'loginpress-pro' );
						}
					} else {
						add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
					}
				} else {
					add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
				}
			} else {
				// Define params and redirect to Spotify Authentication page.
				$params = array(
					'response_type' => 'code',
					'client_id'     => $spotify_client_id,
					'redirect_uri'  => $spotify_redirect_uri,
					'scope'         => 'user-read-email user-read-private',
					'state'         => uniqid( '', true ), // Generate a unique state parameter to prevent CSRF attacks.
				);

				wp_redirect( 'https://accounts.spotify.com/authorize?' . http_build_query( $params ) ); // phpcs:ignore
				exit;
			}
		}


		/**
		 * Login with Twitch Account.
		 *
		 * @since 5.0.0
		 * @return void
		 */
		public function loginpress_on_twitch_login() {
			$lp_social_settings   = get_option( 'loginpress_social_logins' );
			$twitch_client_id     = $lp_social_settings['twitch_client_id'];
			$twitch_client_secret = $lp_social_settings['twitch_client_secret'];
			$twitch_redirect_uri  = $lp_social_settings['twitch_redirect_uri'];

			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';

			$loginpress_utilities = new LoginPress_Social_Utilities();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback parameter.
			if ( isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) {
				// Execute HTTP request to retrieve the access token.
				$params = array(
					'client_id'     => $twitch_client_id,
					'client_secret' => $twitch_client_secret,
					'code'          => sanitize_text_field( wp_unslash( $_GET['code'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'grant_type'    => 'authorization_code',
					'redirect_uri'  => $twitch_redirect_uri,
				);

				$response = wp_remote_post(
					'https://id.twitch.tv/oauth2/token',
					array(
						'body'    => $params,
						'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
					)
				);

					$response = json_decode( wp_remote_retrieve_body( $response ), true );

					// Make sure access token is valid.
				if ( isset( $response['access_token'] ) && ! empty( $response['access_token'] ) ) {
					// Retrieve user profile information.
					$profile_response = wp_remote_get(
						'https://api.twitch.tv/helix/users',
						array(
							'headers' => array(
								'Authorization' => 'Bearer ' . $response['access_token'],
								'Client-ID'     => $twitch_client_id,
							),
						)
					);

					$profile = json_decode( wp_remote_retrieve_body( $profile_response ), true );

					if ( isset( $profile['data'][0]['id'] ) && ! empty( $profile['data'][0]['id'] ) ) {
						if ( $this->lp_check_verification_flow() ) {
							exit();
						}
						// Process the user profile information.
						$result = $this->loginpress_create_result_obj( $profile, 'twitch' );

						global $wpdb;
						$sha_verifier = sha1( $result->deutype . $result->deuid );
						$row          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s LIMIT 1", $result->deutype, $result->deuid, $sha_verifier ) ); // @codingStandardsIgnoreLine.
						$user_object  = get_user_by( 'email', $profile['data'][0]['email'] );
						$role         = get_option( 'default_role' );

						$restrict_enabled = isset( $lp_social_settings['restrict_admin_sl'] ) ? $lp_social_settings['restrict_admin_sl'] : 'off';

						if ( 'on' === $restrict_enabled && $user_object && $this->lp_is_user_restricted_from_sl( $user_object ) ) {
							$this->lp_show_social_login_restriction_error();
						}
						if ( ! $row ) {
							// check if there is already a user with the email address provided from social login.
							if ( false !== $user_object ) {
								// user already there so log him in.
								$id  = $user_object->ID;
								$row = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `user_id` LIKE %d LIMIT 1", $id ) ); // @codingStandardsIgnoreLine.

								if ( ! $row ) {
									$loginpress_utilities->link_user( $id, $result );
								}
								if ( 'subscriber' === $role ) {
									$loginpress_utilities->_home_url( $user_object, 'success_twitch', 'subscriber' );
								} else {
									$loginpress_utilities->_home_url( $user_object, 'success_twitch' );
								}
								die();
							}
							$loginpress_utilities->register_user( $result->username, $result->email );
							$user_object = get_user_by( 'email', $result->email );
							do_action( 'loginpress_social_login_registered', $user_object );
							$id = $user_object->ID;
							$loginpress_utilities->update_usermeta( $id, $result, $role );
							if ( 'subscriber' === $role ) {
								$loginpress_utilities->_home_url( $user_object, 'success_twitch', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'success_twitch' );
							}
							exit();
						} elseif ( ( isset( $row[0] ) && $row[0]->identifier === $result->deuid ) ) {
								$user_object = get_user_by( 'email', $result->email );
								$id          = $user_object->ID;
							if ( 'subscriber' === $role ) {
									$loginpress_utilities->_home_url( $user_object, 'success_twitch', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'success_twitch' );
							}

								exit();
						} else {
							// user not found in our database.
							echo esc_html__( 'user not found in our database', 'loginpress-pro' );
						}
					} else {
						add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
					}
				} else {
					add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
				}
			} else {
				// Define params and redirect to Twitch Authentication page.
				$params = array(
					'response_type' => 'code',
					'client_id'     => $twitch_client_id,
					'redirect_uri'  => $twitch_redirect_uri,
					'scope'         => 'user:read:email',
				);

				wp_redirect( 'https://id.twitch.tv/oauth2/authorize?' . http_build_query( $params ) ); // phpcs:ignore
				exit;
			}
		}
	}
}
