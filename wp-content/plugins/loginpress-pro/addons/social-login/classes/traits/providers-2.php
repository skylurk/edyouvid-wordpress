<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * LoginPress Social Login Providers 2 Trait file.
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
 * Handles the social logins on login page of Apple, Github, Discord and Microsoft.
 *
 * @package   LoginPress
 * @subpackage Traits\SocialLogin
 * @since 6.1.0
 */

if ( ! trait_exists( 'LoginPress_Social_Login_Providers2_Trait' ) ) {
	/**
	 * LoginPress Social Login Settings Trait.
	 *
	 * Handles Some functions from loginpress-social-check.php file.
	 * Handles the social logins on login page of Apple, Github, Discord and Microsoft.
	 *
	 * @package   LoginPress
	 * @subpackage Traits\SocialLogin
	 * @since 6.1.0
	 */
	trait LoginPress_Social_Login_Providers2_Trait {

		/**
		 * Login with Apple Account.
		 *
		 * @return void
		 * @since 4.0.0
		 * @version 6.1.0
		 */
		public function loginpress_on_apple_login() {
			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/class-loginpress-apple.php';
			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';

			$response_class       = new stdClass();
			$apple_login          = new LoginPress_Apple();
			$loginpress_utilities = new LoginPress_Social_Utilities();
			$profile              = $apple_login->apple_login( $response_class );
			// Check if the email/s are passed from the filter, it will only allow the email/s to login.
			$is_apple_restricted = apply_filters( 'loginpress_social_login_apple_domains', false );

			if ( $is_apple_restricted && is_array( $is_apple_restricted ) ) {
				if ( ! $this->loginpress_is_eligible_social_domain( $profile->email, $is_apple_restricted ) ) {
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

			if ( isset( $profile->status ) && 'SUCCESS' === $profile->status ) {
				if ( $this->lp_check_verification_flow() ) {
					exit();
				}

				if ( ! empty( $profile->email ) ) {
					$role   = get_option( 'default_role' );
					$result = $this->loginpress_create_result_obj( $profile, 'apple' );
					global $wpdb;
					$sha_verifier       = sha1( $result->deutype . $result->deuid );
					$row = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s", $result->deutype, $result->deuid, $sha_verifier)); // @codingStandardsIgnoreLine.
					$user_object        = get_user_by( 'email', $profile->email );
					$lp_social_settings = get_option( 'loginpress_social_logins' );
					$restrict_enabled   = isset( $lp_social_settings['restrict_admin_sl'] ) ? $lp_social_settings['restrict_admin_sl'] : 'off';

					if ( 'on' === $restrict_enabled && $user_object && $this->lp_is_user_restricted_from_sl( $user_object ) ) {
						$this->lp_show_social_login_restriction_error();
					}
					if ( ! $row ) {
						// check if there is already a user with the email address provided from social login.
						if ( false !== $user_object ) {
							// user already there so log him in.
							$id  = $user_object->ID;
							$row = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `user_id` LIKE %d", $id)); // @codingStandardsIgnoreLine.

							if ( ! $row ) {
								$loginpress_utilities->link_user( $id, $result );
							}
							if ( 'subscriber' === $role ) {
								$loginpress_utilities->_home_url( $user_object, 'success_apple', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'success_apple' );
							}
							die();
						}
						$loginpress_utilities->register_user( $result->username, $result->email );
						$user_object = get_user_by( 'email', $result->email );
						do_action( 'loginpress_social_login_registered', $user_object );
						$id = $user_object->ID;
						$loginpress_utilities->update_usermeta( $id, $result, $role );
						if ( 'subscriber' === $role ) {
							$loginpress_utilities->_home_url( $user_object, 'success_apple', 'subscriber' );
						} else {
							$loginpress_utilities->_home_url( $user_object, 'success_apple' );
						}
						exit();
					} elseif ( isset( $row[0] ) && ( $row[0]->identifier === $result->deuid ) ) {
						$user_object = get_user_by( 'email', $result->email );
						$id          = $user_object->ID;
						if ( 'subscriber' === $role ) {
							$loginpress_utilities->_home_url( $user_object, 'success_apple', 'subscriber' );
						} else {
							$loginpress_utilities->_home_url( $user_object, 'success_apple' );
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
				if ( isset( $_REQUEST['error'] ) ) { // @codingStandardsIgnoreLine.
					$redirect_url = isset( $_REQUEST['redirect_to'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['redirect_to'] ) ) : site_url(); // @codingStandardsIgnoreLine.
					$loginpress_utilities->redirect( $redirect_url );
				}
				die();
			}
		}

		/**
		 * Login with Github Account.
		 *
		 * @return void
		 * @since 4.0.0
		 * @version 6.1.0
		 */
		public function loginpress_on_github_login() {
			$lp_social_settings   = get_option( 'loginpress_social_logins' );
			$github_client_id     = $lp_social_settings['github_client_id'];
			$github_client_secret = $lp_social_settings['github_client_secret'];
			$github_redirect_uri  = $lp_social_settings['github_redirect_uri'];
			$github_app_name      = $lp_social_settings['github_app_name'];

			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';

			$loginpress_utilities = new LoginPress_Social_Utilities();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback parameter.
			if ( isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) {
				// Execute HTTP request to retrieve the access token.
				$params       = array(
					'code'          => sanitize_text_field( wp_unslash( $_GET['code'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'client_id'     => $github_client_id,
					'client_secret' => $github_client_secret,
					'redirect_uri'  => $github_redirect_uri,
				);
				$response     = wp_remote_post(
					'https://github.com/login/oauth/access_token',
					array(
						'body'    => $params,
						'headers' => array( 'Accept' => 'application/json' ),
					)
				);
					$response = json_decode( ( wp_remote_retrieve_body( $response ) ), true );

					// Make sure access token is valid.
				if ( isset( $response['access_token'] ) && ! empty( $response['access_token'] ) ) {

					$response = wp_remote_get(
						'https://api.github.com/user/emails',
						array(
							'headers' => array(
								'Authorization' => 'Bearer ' . $response['access_token'],
								'User-Agent'    => $github_app_name,
							),
						)
					);
					$profile  = json_decode( ( wp_remote_retrieve_body( $response ) ), true );

					if ( isset( $profile[0]['email'] ) && ! empty( $profile[0]['email'] ) ) {
						if ( $this->lp_check_verification_flow() ) {
							exit();
						}

						$result = $this->loginpress_create_result_obj( $profile, 'github' );

						global $wpdb;
						$sha_verifier     = sha1( $result->deutype . $result->deuid );
						$row          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s LIMIT 1", $result->deutype, $result->deuid, $sha_verifier ) ); // @codingStandardsIgnoreLine.
						$user_object      = get_user_by( 'email', $profile[0]['email'] );
						$role             = get_option( 'default_role' );
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
									$loginpress_utilities->_home_url( $user_object, 'success_github', 'subscriber' );
								} else {
									$loginpress_utilities->_home_url( $user_object, 'success_github' );
								}
								die();
							}
							$loginpress_utilities->register_user( $result->username, $result->email );
							$user_object = get_user_by( 'email', $result->email );
							do_action( 'loginpress_social_login_registered', $user_object );
							$id = $user_object->ID;
							$loginpress_utilities->update_usermeta( $id, $result, $role );
							if ( 'subscriber' === $role ) {
								$loginpress_utilities->_home_url( $user_object, 'success_github', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'success_github' );
							}
							exit();
						} elseif ( ( isset( $row[0] ) && $row[0]->identifier === $result->deuid ) ) {
							$user_object = get_user_by( 'email', $result->email );
							$id          = $user_object->ID;
							if ( 'subscriber' === $role ) {
									$loginpress_utilities->_home_url( $user_object, 'success_github', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'success_github' );
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
				// Define params and redirect to Github Authentication page.
				$params = array(
					'client_id'    => $github_client_id,
					'redirect_uri' => $github_redirect_uri,
					'scope'        => 'user,user:email',
				);
				wp_redirect( 'https://github.com/login/oauth/authorize?' . http_build_query( $params ) ); // phpcs:ignore
				exit;
			}
		}

		/**
		 * Handle the discord login process.
		 *
		 * @return void
		 * @since 4.0.0
		 * @version 6.1.0
		 */
		public function loginpress_on_discord_login() {
			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';
			$loginpress_utilities = new LoginPress_Social_Utilities();
			$settings             = get_option( 'loginpress_social_logins' );
			$client_id            = $settings['discord_client_id'];
			$client_secret        = $settings['discord_client_secret'];
			$discord_redirect     = $settings['discord_redirect_uri'];
			$discord_gen_url      = $settings['discord_generated_url'];

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback parameter.
			if ( isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) {

				$payload           = array(
					'code'          => sanitize_text_field( wp_unslash( $_GET['code'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
					'grant_type'    => 'authorization_code',
					'redirect_uri'  => $discord_redirect,
					'scope'         => 'identify%20email',
				);
				$payload_string    = http_build_query( $payload );
				$discord_token_url = 'https://discord.com/api/oauth2/token';
				$response          = wp_remote_post(
					$discord_token_url,
					array(
						'body' => $payload_string,
					)
				);

					$response_body = wp_remote_retrieve_body( $response );
					$response      = json_decode( $response_body, true );

					// Make sure access token is valid.
				if ( isset( $response['access_token'] ) && ! empty( $response['access_token'] ) ) {
					// Execute HTTP request to retrieve the user info associated with the Discord account.
					$response      = wp_remote_get(
						'https://discord.com/api/users/@me',
						array(
							'headers' => array( 'Authorization' => 'Bearer ' . $response['access_token'] ),
						)
					);
					$response_body = wp_remote_retrieve_body( $response );
					$profile       = json_decode( $response_body, true );

					// Make sure the profile data exists.
					if ( isset( $profile['email'] ) ) {
						if ( $this->lp_check_verification_flow() ) {
							exit();
						}

						$result = $this->loginpress_create_result_obj( $profile, 'discord' );

						global $wpdb;
						$sha_verifier     = sha1( $result->deutype . $result->deuid );
						$row          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s LIMIT 1", $result->deutype, $result->deuid, $sha_verifier ) ); // @codingStandardsIgnoreLine.
						$user_object      = get_user_by( 'email', $profile['email'] );
						$role             = get_option( 'default_role' );
						$restrict_enabled = isset( $settings['restrict_admin_sl'] ) ? $settings['restrict_admin_sl'] : 'off';

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
									$loginpress_utilities->_home_url( $user_object, 'discord_login', 'subscriber' );
								} else {
									$loginpress_utilities->_home_url( $user_object, 'discord_login' );
								}
								die();
							}
							$loginpress_utilities->register_user( $result->username, $result->email );
							$user_object = get_user_by( 'email', $result->email );
							do_action( 'loginpress_social_login_registered', $user_object );
							$id = $user_object->ID;
							$loginpress_utilities->update_usermeta( $id, $result, $role );
							if ( 'subscriber' === $role ) {
								$loginpress_utilities->_home_url( $user_object, 'success_discord', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'success_discord' );
							}
							exit();
						} elseif ( ( isset( $row[0] ) && $row[0]->provider_name === $result->deutype ) && ( $row[0]->identifier === $result->deuid ) ) {
								$user_object = get_user_by( 'email', $result->email );
								$id          = $user_object->ID;
							if ( 'subscriber' === $role ) {
									$loginpress_utilities->_home_url( $user_object, 'success_discord', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'success_discord' );
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

				header( 'Location: ' . $discord_gen_url );
				exit;
			}
		}

		/**
		 * Login with Microsoft Account.
		 *
		 * @return void
		 * @since 5.0.0
		 * @version 6.1.0
		 */
		public function loginpress_on_microsoft_login() {
			require_once LOGINPRESS_SOCIAL_DIR_PATH . 'sdk/microsoft/vendor/autoload.php';
			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/class-loginpress-microsoft.php';
			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';
			$loginpress_utilities = new LoginPress_Social_Utilities();
			$settings             = get_option( 'loginpress_social_logins' );
			$client_id            = $settings['microsoft_app_id'];
			$client_secret        = $settings['microsoft_app_secret'];
			$callback             = $settings['microsoft_redirect_uri'];
			$scopes               = array( 'User.Read', 'offline_access' );
			// If tenant type is not set, default to 'common' for backward compatibility.
			$tenant_type = isset( $settings['microsoft_tenant_type'] ) && ! empty( $settings['microsoft_tenant_type'] ) ? $settings['microsoft_tenant_type'] : 'common';
			if ( 'tenant_id' === $tenant_type ) {
				$tenant = $settings['microsoft_tenant_id'];
			} else {
				$tenant = $tenant_type;
			}

			$microsoft_handler = new LoginPress_Microsoft( $tenant, $client_id, $client_secret, $callback, $scopes );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback parameter.
			if ( isset( $_GET['lpsl_login_id'] ) && 'microsoft_login' === sanitize_text_field( wp_unslash( $_GET['lpsl_login_id'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$microsoft_handler->loginpress_microsoft_login();
			} else {
				$data                    = $microsoft_handler->loginpress_handle_returning_user();
				$id                      = $data->getId();
				$name                    = $data->getDisplayName();
				$result                  = new stdClass();
				$result->status          = 'SUCCESS';
				$result->deuid           = $id;
				$result->deutype         = 'microsoft';
				$result->first_name      = $data->getGivenName();
				$result->about           = '';
				$result->gender          = '';
				$result->url             = '';
				$result->last_name       = $data->getsurname();
				$result->email           = $data->getUserPrincipalName();
				$result->username        = ( '' !== $data->getGivenName() ) ? strtolower( $data->getGivenName() ) : $data['email'];
				$result->deuimage        = get_avatar_url( $result->email, array( 'size' => 150 ) );
				$is_microsoft_restricted = apply_filters( 'loginpress_social_login_microsoft_domains', false );

				if ( $is_microsoft_restricted && is_array( $is_microsoft_restricted ) ) {
					if ( ! $this->loginpress_is_eligible_social_domain( $data->getUserPrincipalName(), $is_microsoft_restricted ) ) {
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
				global $wpdb;
				$sha_verifier = sha1( $result->deutype . $result->deuid );
				$row          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s LIMIT 1", $result->deutype, $result->deuid, $sha_verifier ) ); // @codingStandardsIgnoreLine.

				$user_object = get_user_by( 'email', $data->getUserPrincipalName() );
				if ( $this->lp_check_verification_flow() ) {
					exit();
				}
				$role             = get_option( 'default_role' );
				$restrict_enabled = isset( $settings['restrict_admin_sl'] ) ? $settings['restrict_admin_sl'] : 'off';

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
							$loginpress_utilities->_home_url( $user_object, 'success_microsoft', 'subscriber' );
						} else {
							$loginpress_utilities->_home_url( $user_object, 'success_microsoft' );
						}
						die();
					}
					$loginpress_utilities->register_user( $result->username, $result->email );
					$user_object = get_user_by( 'email', $result->email );
					do_action( 'loginpress_social_login_registered', $user_object );
					$id = $user_object->ID;
					$loginpress_utilities->update_usermeta( $id, $result, $role );
					if ( 'subscriber' === $role ) {
						$loginpress_utilities->_home_url( $user_object, 'success_microsoft', 'subscriber' );
					} else {
						$loginpress_utilities->_home_url( $user_object, 'success_microsoft' );
					}
					exit();
				} elseif ( ( isset( $row[0] ) && $row[0]->provider_name === $result->deutype ) && ( $row[0]->identifier === $result->deuid ) ) {

						$user_object = get_user_by( 'email', $result->email );
						$id          = $user_object->ID;
						$loginpress_utilities->_home_url( $user_object, 'success_microsoft' );
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
