<?php
/**
 * LoginPress Social Login Providers Trait file.
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
 * Handles the social logins on login page of Facebook, Twitter, Google and Linkedin.
 *
 * @package   LoginPress
 * @subpackage Traits\SocialLogin
 * @since 6.1.0
 */

if ( ! trait_exists( 'LoginPress_Social_Login_Providers_Trait' ) ) {
	/**
	 * LoginPress Social Login Settings Trait.
	 *
	 * Handles Some functions from loginpress-social-check.php file.
	 * Handles the social logins on login page of Facebook, Twitter, Google and Linkedin.
	 *
	 * @package   LoginPress
	 * @subpackage Traits\SocialLogin
	 * @since 6.1.0
	 */
	trait LoginPress_Social_Login_Providers_Trait {

		/**
		 * Login with Facebook Account.
		 *
		 * @return void
		 * @since 5.0.0
		 * @version 6.1.0
		 */
		public function loginpress_on_facebook_login() {
			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/class-loginpress-facebook.php';
			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';
			$response_class         = new stdClass();
			$facebook_login         = new LoginPress_Facebook();
			$loginpress_utilities   = new LoginPress_Social_Utilities();
			$result                 = $facebook_login->facebook_login( $response_class );
			$is_facebook_restricted = apply_filters( 'loginpress_social_login_facebook_domains', false );

			if ( $is_facebook_restricted && is_array( $is_facebook_restricted ) ) {
				if ( ! $this->loginpress_is_eligible_social_domain( $result->email, $is_facebook_restricted ) ) {
					wp_safe_redirect(
						add_query_arg(
							array(
								'lp_social_error' => 'true',
							),
							wp_login_url()
						)
					);
					die();
				}
			}
			if ( isset( $result->status ) && 'SUCCESS' === $result->status ) {
				if ( $this->lp_check_verification_flow() ) {
					exit();
				}
				global $wpdb;
				$sha_verifier = sha1( $result->deutype . $result->deuid );
				$row          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s LIMIT 1", $result->deutype, $result->deuid, $sha_verifier ) ); // @codingStandardsIgnoreLine.
				$user_object  = get_user_by( 'email', $result->email );

				if ( ! isset( $row[0]->email ) && $result->email === $result->deuid . '@facebook.com' ) {
					$result->email = $result->email;

				} elseif ( $result->email === $result->deuid . '@facebook.com' ) {
					$result->email = $row[0]->email;
				}
				$role               = get_option( 'default_role' );
				$lp_social_settings = get_option( 'loginpress_social_logins' );
				$restrict_enabled   = isset( $lp_social_settings['restrict_admin_sl'] ) ? $lp_social_settings['restrict_admin_sl'] : 'off';

				if ( 'on' === $restrict_enabled && $user_object && $this->lp_is_user_restricted_from_sl( $user_object ) ) {
					$this->lp_show_social_login_restriction_error();
				}
				if ( ! $row ) {
					// check if there is already a user with the email address provided from social login already.
					if ( false !== $user_object ) {
						// user already there so log him in.
						$id  = $user_object->ID;
						$row = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `user_id` LIKE %d LIMIT 1", $id ) ); // @codingStandardsIgnoreLine.
						if ( ! $row ) {
							$loginpress_utilities->link_user( $id, $result );
						}
						if ( 'subscriber' === $role ) {
							$loginpress_utilities->_home_url( $user_object, 'success_facebook', 'subscriber' );
						} else {
							$loginpress_utilities->_home_url( $user_object, 'success_facebook' );
						}
						die();
					}
					$loginpress_utilities->register_user( $result->username, $result->email );
					$user_object = get_user_by( 'email', $result->email );
					do_action( 'loginpress_social_login_registered', $user_object );
					$id = $user_object->ID;
					$loginpress_utilities->update_usermeta( $id, $result, $role );
					if ( 'subscriber' === $role ) {
						$loginpress_utilities->_home_url( $user_object, 'success_facebook', 'subscriber' );
					} else {
						$loginpress_utilities->_home_url( $user_object, 'success_facebook' );
					}
					exit();
				} elseif ( ( isset( $row[0] ) && $row[0]->provider_name === $result->deutype ) && ( $row[0]->identifier === $result->deuid ) ) {
					$user_object = get_user_by( 'email', $result->email );
					$id          = $user_object->ID;
					$loginpress_utilities->_home_url( $user_object, 'success_facebook' );
					exit();
				} else {
					// user not found in our database.
					// need to handle an exception.
					echo esc_html__( 'user not found in our database', 'loginpress-pro' );
				}
			} else {
				if ( isset( $_REQUEST['error'] ) ) { // @codingStandardsIgnoreLine.

					$redirect_url = isset( $_REQUEST['redirect_to'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['redirect_to'] ) ) : site_url(); // @codingStandardsIgnoreLine.
					$loginpress_utilities->redirect( $redirect_url );
				}
				die();
			}
		}

		/**
		 * Login with Twitter Account.
		 *
		 * @return void
		 * @since 5.0.0
		 * @version 6.1.0
		 */
		public function loginpress_on_twitter_login() {
			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/class-loginpress-twitter.php';
			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';

			$response_class       = new stdClass();
			$twitter_login        = new LoginPress_Twitter();
			$loginpress_utilities = new LoginPress_Social_Utilities();
			$login_settings       = get_option( 'loginpress_social_logins' );
			if ( isset( $login_settings['twitter_api_version'] ) && 'oauth2' === $login_settings['twitter_api_version'] ) {
				$result = $twitter_login->twitter_login_oauth2( $response_class );
			} else {
				$result = $twitter_login->twitter_login( $response_class );
			}
				$is_twitter_restricted = apply_filters( 'loginpress_social_login_twitter_domains', false );

			if ( $is_twitter_restricted && is_array( $is_twitter_restricted ) ) {
				if ( ! $this->loginpress_is_eligible_social_domain( $result->email, $is_twitter_restricted ) ) {
					wp_safe_redirect(
						add_query_arg(
							array(
								'lp_social_error' => 'true',
							),
							wp_login_url()
						)
					);
					die();
				}
			}
			if ( isset( $result->status ) && is_object( $result ) && 'SUCCESS' === $result->status ) {
				if ( $this->lp_check_verification_flow() ) {
					exit();
				}
				global $wpdb;
				$sha_verifier     = sha1( $result->deutype . $result->deuid );
				$row          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s LIMIT 1", $result->deutype, $result->deuid, $sha_verifier ) ); // @codingStandardsIgnoreLine.
				$role             = get_option( 'default_role' );
				$restrict_enabled = isset( $login_settings['restrict_admin_sl'] ) ? $login_settings['restrict_admin_sl'] : 'off';

				if ( 'on' === $restrict_enabled && $user_object && $this->lp_is_user_restricted_from_sl( $user_object ) ) {
					$this->lp_show_social_login_restriction_error();
				}
				if ( ! $row ) {
					// check if there is already a user with the email address provided from social login already.
					$user_object = get_user_by( 'email', $result->email );

					if ( false !== $user_object ) {
						// user already there so log him in.
						$id  = $user_object->ID;
						$row = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `user_id` LIKE %d LIMIT 1", $id ) ); // @codingStandardsIgnoreLine.

						if ( ! $row ) {
							$loginpress_utilities->link_user( $id, $result );
						}
						if ( 'subscriber' === $role ) {
							$loginpress_utilities->_home_url( $user_object, 'success_twitter', 'subscriber' );
						} else {
							$loginpress_utilities->_home_url( $user_object, 'success_twitter' );
						}
						die();
					}
					$loginpress_utilities->register_user( $result->username, $result->email );
					$user_object = get_user_by( 'email', $result->email );
					do_action( 'loginpress_social_login_registered', $user_object );
					$id = $user_object->ID;
					$loginpress_utilities->update_usermeta( $id, $result, $role );
					if ( 'subscriber' === $role ) {
						$loginpress_utilities->_home_url( $user_object, 'success_twitter', 'subscriber' );
					} else {
						$loginpress_utilities->_home_url( $user_object, 'success_twitter' );
					}
					exit();
				} elseif ( ( isset( $row[0] ) && $row[0]->provider_name === $result->deutype ) && ( $row[0]->identifier == $result->deuid ) ) { // phpcs:ignore

					$user_object = get_user_by( 'email', $result->email );
					$id          = $user_object->ID;
					$loginpress_utilities->_home_url( $user_object, 'success_twitter' );
					exit();
				} else {
					// user not found in our database.
					// need to handle an exception.
					echo esc_html__( 'user not found in our database', 'loginpress-pro' );
				}
			} else {
				if ( isset( $_REQUEST['denied'] ) ) { // @codingStandardsIgnoreLine.
					$redirect_url = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : site_url(); // @codingStandardsIgnoreLine.
					$loginpress_utilities->redirect( $redirect_url );
				}
				die();
			}
		}

		/**
		 * Login with Google Account.
		 *
		 * @return void
		 * @since 5.0.0
		 * @version 6.1.0
		 */
		public function loginpress_on_google_login() {
			$_settings                  = get_option( 'loginpress_social_logins' );
			$google_oauth_client_id     = $_settings['gplus_client_id']; // Google client ID.
			$google_oauth_client_secret = $_settings['gplus_client_secret']; // Google client secret.
			$google_oauth_redirect_uri  = $_settings['gplus_redirect_uri']; // Callback URL.
			$google_oauth_version       = 'v3';

			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';

			$loginpress_utilities = new LoginPress_Social_Utilities();
			// If the captured code param exists and is valid.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback parameter.
			if ( isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) {
				// Execute HTTP request to retrieve the access token.
				$params            = array(
					'code'          => sanitize_text_field( wp_unslash( $_GET['code'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'client_id'     => $google_oauth_client_id,
					'client_secret' => $google_oauth_client_secret,
					'redirect_uri'  => $google_oauth_redirect_uri,
					'grant_type'    => 'authorization_code',
				);
				$response          = wp_remote_post(
					'https://accounts.google.com/o/oauth2/token',
					array(
						'body' => $params,
					)
				);
					$response_body = wp_remote_retrieve_body( $response );
					$response      = json_decode( $response_body, true );

					// Make sure access token is valid.
				if ( isset( $response['access_token'] ) && ! empty( $response['access_token'] ) ) {
					// Execute HTTP request to retrieve the user info associated with the Google account.
					$response      = wp_remote_get(
						'https://www.googleapis.com/oauth2/' . $google_oauth_version . '/userinfo',
						array(
							'headers' => array( 'Authorization' => 'Bearer ' . $response['access_token'] ),
						)
					);
					$response_body = wp_remote_retrieve_body( $response );
					$profile       = json_decode( $response_body, true );

					// Make sure the profile data exists.
					if ( isset( $profile['email'] ) ) {

						$result = $this->loginpress_create_result_obj( $profile, 'gplus' );

						$is_google_restricted = apply_filters( 'loginpress_social_login_google_domains', false );

						if ( $is_google_restricted && is_array( $is_google_restricted ) ) {
							if ( ! $this->loginpress_is_eligible_social_domain( $result->email, $is_google_restricted ) ) {
								wp_safe_redirect(
									add_query_arg(
										array(
											'lp_social_error' => 'true',
										),
										wp_login_url()
									)
								);
								exit;
							}
						}
						global $wpdb;
						$sha_verifier = sha1( $result->deutype . $result->deuid );
						$identifier   = $profile['sub'];
						$row          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s LIMIT 1", $result->deutype, $result->deuid, $sha_verifier ) ); // @codingStandardsIgnoreLine.
						$user_object  = get_user_by( 'email', $profile['email'] );
						if ( $this->lp_check_verification_flow() ) {
							exit();
						}
						$role             = get_option( 'default_role' );
						$restrict_enabled = isset( $_settings['restrict_admin_sl'] ) ? $_settings['restrict_admin_sl'] : 'off';

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
									$loginpress_utilities->_home_url( $user_object, 'success_gplus', 'subscriber' );
								} else {
									$loginpress_utilities->_home_url( $user_object, 'success_gplus' );
								}
								die();
							}
							$loginpress_utilities->register_user( $result->username, $result->email );
							$user_object = get_user_by( 'email', $result->email );
							do_action( 'loginpress_social_login_registered', $user_object );
							$id = $user_object->ID;
							$loginpress_utilities->update_usermeta( $id, $result, $role );
							if ( 'subscriber' === $role ) {
									$loginpress_utilities->_home_url( $user_object, 'success_gplus', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'success_gplus' );
							}
							exit();
						} elseif ( ( isset( $row[0] ) && $row[0]->provider_name === $result->deutype ) && ( $row[0]->identifier === $result->deuid ) ) {
							$user_object = get_user_by( 'email', $result->email );
							$id          = $user_object->ID;
							$loginpress_utilities->_home_url( $user_object, 'success_gplus' );

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
				// Define params and redirect to Google Authentication page.
				$params = array(
					'response_type' => 'code',
					'client_id'     => $google_oauth_client_id,
					'redirect_uri'  => $google_oauth_redirect_uri,
					'scope'         => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
					'access_type'   => 'offline',
					'prompt'        => 'consent',
				);
				wp_redirect( 'https://accounts.google.com/o/oauth2/auth?' . http_build_query( $params ) ); // phpcs:ignore
				exit;
			}
		}

		/**
		 * Login with LinkedIn Account.
		 * Fixed the LinkedIn authorization redirection loop issue.
		 *
		 * @return void
		 * @since 5.0.0
		 * @version 6.1.0
		 */
		public function loginpress_on_linkedin_login() {
			$_settings     = get_option( 'loginpress_social_logins' );
			$client_id     = $_settings['linkedin_client_id'];      // LinkedIn client ID.
			$client_secret = $_settings['linkedin_client_secret']; // LinkedIn client secret.
			$redirect_url  = $_settings['linkedin_redirect_uri']; // Callback URL.

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback parameter.
			if ( ! isset( $_GET['code'] ) ) {

				wp_redirect( "https://www.linkedin.com/oauth/v2/authorization?response_type=code&client_id={$client_id}&redirect_uri={$redirect_url}&state=987654321&scope=openid%20profile%20email" ); // phpcs:ignore
				exit;
			} else {

				$get_access_token = wp_remote_post(
					'https://www.linkedin.com/oauth/v2/accessToken',
					array(
						'body' => array(
							'grant_type'        => 'authorization_code',
							'code'              => sanitize_text_field( wp_unslash( $_GET['code'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
								'redirect_uri'  => $redirect_url,
								'client_id'     => $client_id,
								'client_secret' => $client_secret,
						),
					)
				);

				// Check if the request failed or if the response body is invalid.
				if ( is_wp_error( $get_access_token ) || 200 !== wp_remote_retrieve_response_code( $get_access_token ) ) {
					echo '<script>alert("Invalid client secret or authorization error. Please check your credentials or contact the admin.");</script>';
					exit();
				}

				$_access_token = json_decode( $get_access_token['body'] )->access_token;

				if ( ! $_access_token ) {
					$user_login_url = apply_filters( 'login_redirect', admin_url(), site_url(), wp_signon() );
					wp_safe_redirect( $user_login_url );
					exit;
				}

				$get_user_details = wp_remote_get(
					'https://api.linkedin.com/v2/userinfo',
					array(
						'method' => 'GET', // @codingStandardsIgnoreLine.
						'timeout' => 15,
						'headers' => array( 'Authorization' => 'Bearer ' . $_access_token ),
					)
				);

				if ( ! is_wp_error( $get_user_details ) && isset( $get_user_details['response']['code'] ) && 200 === $get_user_details['response']['code'] ) {

					$light_detail_body = json_decode( wp_remote_retrieve_body( $get_user_details ) );
					$first_name        = isset( $light_detail_body->given_name ) ? $light_detail_body->given_name : '';
					$last_name         = isset( $light_detail_body->family_name ) ? $light_detail_body->family_name : '';
					$large_avatar      = isset( $light_detail_body->picture ) ? $light_detail_body->picture : '';
					$email_address     = isset( $light_detail_body->email ) ? $light_detail_body->email : '';
					$deuid             = isset( $light_detail_body->sub ) ? $light_detail_body->sub : '';
				}

				if ( empty( $email_address ) || empty( $deuid ) ) {
					echo esc_html__( 'user not found in our database', 'loginpress-pro' );
					exit;
				}
				$is_linkedin_restricted = apply_filters( 'loginpress_social_login_linkedin_domains', false );

				if ( $is_linkedin_restricted && is_array( $is_linkedin_restricted ) ) {
					if ( ! $this->loginpress_is_eligible_social_domain( $email_address, $is_linkedin_restricted ) ) {
						wp_safe_redirect(
							add_query_arg(
								array(
									'lp_social_error' => 'true',
								),
								wp_login_url()
							)
						);
						die();
					}
				}
				include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';

				$loginpress_utilities = new LoginPress_Social_Utilities();

				$result = $this->loginpress_create_result_obj(
					$profile,
					'linkedin',
					array(
						'deuid'         => $deuid,
						'first_name'    => $first_name,
						'last_name'     => $last_name,
						'email_address' => $email_address,
						'large_avatar'  => $large_avatar,
					)
				);

				global $wpdb;
				$sha_verifier = sha1( $result->deutype . $deuid );
				$identifier   = $deuid;
				$row          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s LIMIT 1", $result->deutype, $deuid, $sha_verifier ) ); // @codingStandardsIgnoreLine.

				$user_object = get_user_by( 'email', $result->email );
				if ( $this->lp_check_verification_flow() ) {
					exit();
				}

				$restrict_enabled = isset( $_settings['restrict_admin_sl'] ) ? $_settings['restrict_admin_sl'] : 'off';

				if ( 'on' === $restrict_enabled && $user_object && $this->lp_is_user_restricted_from_sl( $user_object ) ) {
					$this->lp_show_social_login_restriction_error();
				}
				if ( ! $row ) {
					$role = get_option( 'default_role' );
					// check if there is already a user with the email address provided from social login already.
					if ( false !== $user_object ) {
						// user already there so log him in.
						$id  = $user_object->ID;
						$row = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `user_id` LIKE %d LIMIT 1", $id ) ); // @codingStandardsIgnoreLine.

						if ( ! $row ) {
							$loginpress_utilities->link_user( $id, $result );
						}
						if ( 'subscriber' === $role ) {
							$loginpress_utilities->_home_url( $user_object, 'success_linkedin', 'subscriber' );
						} else {
							$loginpress_utilities->_home_url( $user_object, 'success_linkedin' );
						}
						die();
					}
					$loginpress_utilities->register_user( $result->username, $result->email );
					$user_object = get_user_by( 'email', $result->email );
					do_action( 'loginpress_social_login_registered', $user_object );
					$id = $user_object->ID;
					$loginpress_utilities->update_usermeta( $id, $result, $role );
					if ( 'subscriber' === $role ) {
						$loginpress_utilities->_home_url( $user_object, 'success_linkedin', 'subscriber' );
					} else {
						$loginpress_utilities->_home_url( $user_object, 'success_linkedin' );
					}
					exit();
				} elseif ( ( isset( $row[0] ) && $row[0]->provider_name === $result->deutype ) && ( $row[0]->identifier === $deuid ) ) {

					$user_object = get_user_by( 'email', $result->email );
					$id          = $user_object->ID;
					$loginpress_utilities->_home_url( $user_object, 'success_linkedin' );

					exit();
				} else {
					// user not found in our database.
					// need to handle an exception.
					echo esc_html__( 'user not found in our database', 'loginpress-pro' );
				}
			}
		}
	}
}
