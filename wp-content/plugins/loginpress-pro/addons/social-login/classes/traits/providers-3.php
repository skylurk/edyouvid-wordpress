<?php
/**
 * LoginPress Social Login Providers 3 Trait file.
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
 * Handles the social logins on login page of Amazon, Pinterest, Disqus and Reddit.
 *
 * @package   LoginPress
 * @subpackage Traits\SocialLogin
 * @since 6.1.0
 */

if ( ! trait_exists( 'LoginPress_Social_Login_Providers3_Trait' ) ) {
	/**
	 * LoginPress Social Login Settings Trait.
	 *
	 * Handles Some functions from loginpress-social-check.php file.
	 * Handles the social logins on login page of Amazon, Pinterest, Disqus and Reddit.
	 *
	 * @package   LoginPress
	 * @subpackage Traits\SocialLogin
	 * @since 6.1.0
	 */
	trait LoginPress_Social_Login_Providers3_Trait {

		/**
		 * Login with Amazon Account.
		 *
		 * @return void
		 * @since 5.0.0
		 * @version 6.1.0
		 */
		public function loginpress_on_amazon_login() {
			$lp_social_settings   = get_option( 'loginpress_social_logins' );
			$amazon_client_id     = $lp_social_settings['amazon_client_id'];
			$amazon_client_secret = $lp_social_settings['amazon_client_secret'];
			$amazon_redirect_uri  = $lp_social_settings['amazon_redirect_uri'];

			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';

			$loginpress_utilities = new LoginPress_Social_Utilities();

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback parameter.
			if ( isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) {
				// Execute HTTP request to retrieve the access token.
				$params = array(
					'grant_type'    => 'authorization_code',
					'code'          => sanitize_text_field( wp_unslash( $_GET['code'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'client_id'     => $amazon_client_id,
					'client_secret' => $amazon_client_secret,
					'redirect_uri'  => $amazon_redirect_uri,
				);

				$response = wp_remote_post(
					'https://api.amazon.com/auth/o2/token',
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
						'https://api.amazon.com/user/profile',
						array(
							'headers' => array( 'Authorization' => 'Bearer ' . $response['access_token'] ),
						)
					);

					$profile = json_decode( wp_remote_retrieve_body( $profile_response ), true );

					if ( isset( $profile['email'] ) && ! empty( $profile['email'] ) ) {
						if ( $this->lp_check_verification_flow() ) {
							exit();
						}

						$result = $this->loginpress_create_result_obj( $profile, 'amazon' );

						global $wpdb;
						$sha_verifier     = sha1( $result->deutype . $result->deuid );
						$row          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s LIMIT 1", $result->deutype, $result->deuid, $sha_verifier ) ); // @codingStandardsIgnoreLine.
						$user_object      = get_user_by( 'email', $profile['email'] );
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
									$loginpress_utilities->_home_url( $user_object, 'success_amazon', 'subscriber' );
								} else {
									$loginpress_utilities->_home_url( $user_object, 'success_amazon' );
								}
								die();
							}
							$loginpress_utilities->register_user( $result->username, $result->email );
							$user_object = get_user_by( 'email', $result->email );
							do_action( 'loginpress_social_login_registered', $user_object );
							$id = $user_object->ID;
							$loginpress_utilities->update_usermeta( $id, $result, $role );
							if ( 'subscriber' === $role ) {
								$loginpress_utilities->_home_url( $user_object, 'success_amazon', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'success_amazon' );
							}
							exit();
						} elseif ( ( isset( $row[0] ) && $row[0]->identifier === $result->deuid ) ) {
								$user_object = get_user_by( 'email', $result->email );
								$id          = $user_object->ID;
							if ( 'subscriber' === $role ) {
									$loginpress_utilities->_home_url( $user_object, 'success_amazon', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'success_amazon' );
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
				// Define params and redirect to Amazon Authentication page.
				$params = array(
					'client_id'     => $amazon_client_id,
					'response_type' => 'code',
					'redirect_uri'  => $amazon_redirect_uri,
					'scope'         => 'profile',
				);

				wp_redirect( 'https://www.amazon.com/ap/oa?' . http_build_query( $params ) ); // phpcs:ignore
				exit;
			}
		}

		/**
		 * Login with Pinterest Account.
		 *
		 * @return void
		 * @since 5.0.0
		 * @version 6.1.0
		 */
		public function loginpress_on_pinterest_login() {
			$lp_social_settings      = get_option( 'loginpress_social_logins' );
			$pinterest_client_id     = $lp_social_settings['pinterest_client_id'];
			$pinterest_client_secret = $lp_social_settings['pinterest_client_secret'];
			$pinterest_redirect_uri  = $lp_social_settings['pinterest_redirect_uri'];

			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';

			$loginpress_utilities = new LoginPress_Social_Utilities();

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback parameter.
			if ( isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) {
				// Execute HTTP request to retrieve the access token.
				$params = array(
					'grant_type'         => 'authorization_code',
					'code'               => sanitize_text_field( wp_unslash( $_GET['code'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'continuous_refresh' => false,
					'scope'              => 'user_accounts:read',
					'redirect_uri'       => $pinterest_redirect_uri,
				);

				$response = wp_remote_post(
					'https://api.pinterest.com/v5/oauth/token',
					array(
						'body'    => $params,
						'headers' => array(
							'Content-Type'  => 'application/x-www-form-urlencoded',
							'Authorization' => 'Basic ' . base64_encode( $pinterest_client_id . ':' . $pinterest_client_secret ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
						),
					)
				);

					$response = json_decode( wp_remote_retrieve_body( $response ), true );
					// Make sure access token is valid.
				if ( isset( $response['access_token'] ) && ! empty( $response['access_token'] ) ) {
					// Retrieve user profile information.
					$profile_response = wp_remote_get(
						'https://api.pinterest.com/v5/user_account',
						array(
							'headers' => array( 'Authorization' => 'Bearer ' . $response['access_token'] ),
						)
					);
					$profile          = json_decode( wp_remote_retrieve_body( $profile_response ), true );

					if ( isset( $profile['username'] ) && ! empty( $profile['username'] ) ) {
						if ( $this->lp_check_verification_flow() ) {
							exit();
						}
						// Process the user profile information.
						$email  = $profile['username'] . '@pinterest.com';
						$result = $this->loginpress_create_result_obj( $profile, 'pinterest', array( 'email' => $email ) );

						global $wpdb;
						$sha_verifier = sha1( $result->deutype . $result->deuid );
						$row          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s LIMIT 1", $result->deutype, $result->deuid, $sha_verifier ) ); // @codingStandardsIgnoreLine.
						$user_object  = get_user_by( 'email', $email );
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
									$loginpress_utilities->_home_url( $user_object, 'success_pinterest', 'subscriber' );
								} else {
									$loginpress_utilities->_home_url( $user_object, 'success_pinterest' );
								}
								die();
							}
							$loginpress_utilities->register_user( $result->username, $result->email );
							$user_object = get_user_by( 'email', $result->email );
							do_action( 'loginpress_social_login_registered', $user_object );
							$id = $user_object->ID;
							$loginpress_utilities->update_usermeta( $id, $result, $role );
							if ( 'subscriber' === $role ) {
								$loginpress_utilities->_home_url( $user_object, 'success_pinterest', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'success_pinterest' );
							}
							exit();
						} elseif ( ( isset( $row[0] ) && $row[0]->identifier === $result->deuid ) ) {
								$user_object = get_user_by( 'email', $result->email );
								$id          = $user_object->ID;
							if ( 'subscriber' === $role ) {
									$loginpress_utilities->_home_url( $user_object, 'success_pinterest', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'success_pinterest' );
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
				// Define params and redirect to Pinterest Authentication page.
				$params = array(
					'response_type' => 'code',
					'client_id'     => $pinterest_client_id,
					'redirect_uri'  => $pinterest_redirect_uri,
					'scope'         => 'user_accounts:read',
					'state'         => 'pinterest',
				);

				wp_redirect( 'https://www.pinterest.com/oauth/?' . http_build_query( $params ) ); // phpcs:ignore
				exit;
			}
		}

		/**
		 * Login with Disqus Account.
		 *
		 * @return void
		 * @since 5.0.0
		 * @version 6.1.0
		 */
		public function loginpress_on_disqus_login() {
			$lp_social_settings  = get_option( 'loginpress_social_logins' );
			$disqus_api_key      = $lp_social_settings['disqus_client_id'];
			$disqus_api_secret   = $lp_social_settings['disqus_client_secret'];
			$disqus_callback_url = $lp_social_settings['disqus_callback_url'];

			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';

			$loginpress_utilities = new LoginPress_Social_Utilities();

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback parameter.
			if ( isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) {
				// Execute HTTP request to retrieve the access token.
				$params = array(
					'grant_type'    => 'authorization_code',
					'code'          => sanitize_text_field( wp_unslash( $_GET['code'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'redirect_uri'  => $disqus_callback_url,
					'client_id'     => $disqus_api_key,
					'client_secret' => $disqus_api_secret,
				);

				$response = wp_remote_post(
					'https://disqus.com/api/oauth/2.0/access_token/',
					array(
						'body'    => $params,
						'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
					)
				);

					$response = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( isset( $response['access_token'] ) && ! empty( $response['access_token'] ) ) {
					// Retrieve user profile information.
					$profile_response = wp_remote_get(
						'https://disqus.com/api/3.0/users/details.json?api_key=' . $disqus_api_key . '&access_token=' . $response['access_token']
					);

					$profile = json_decode( wp_remote_retrieve_body( $profile_response ), true );

					if ( isset( $profile['response']['id'] ) && ! empty( $profile['response']['id'] ) ) {
						if ( $this->lp_check_verification_flow() ) {
							exit();
						}

						$email = $profile['response']['username'] . '@disqus.com';

						$result = $this->loginpress_create_result_obj( $profile, 'disqus', array( 'email' => $email ) );
						global $wpdb;
						$sha_verifier = sha1( $result->deutype . $result->deuid );
						$row          = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->prepare(
								"SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s  LIMIT 1",
								$result->deutype,
								$result->deuid,
								$sha_verifier
							)
						);

						$user_object      = get_user_by( 'email', $email );
						$role             = get_option( 'default_role' );
						$restrict_enabled = isset( $lp_social_settings['restrict_admin_sl'] ) ? $lp_social_settings['restrict_admin_sl'] : 'off';

						if ( 'on' === $restrict_enabled && $user_object && $this->lp_is_user_restricted_from_sl( $user_object ) ) {
								$this->lp_show_social_login_restriction_error();
						}
						if ( ! $row ) {
							if ( false !== $user_object ) {
								$id  = $user_object->ID;
								$row = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `user_id` LIKE %d  LIMIT 1", $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

								if ( ! $row ) {
									$loginpress_utilities->link_user( $id, $result );
								}
								$loginpress_utilities->_home_url( $user_object, 'success_disqus', 'subscriber' === $role ? 'subscriber' : '' );
								die();
							}

							$loginpress_utilities->register_user( $result->username, $result->email );
							$user_object = get_user_by( 'email', $result->email );
							do_action( 'loginpress_social_login_registered', $user_object );
							$id = $user_object->ID;
							$loginpress_utilities->update_usermeta( $id, $result, $role );
							$loginpress_utilities->_home_url( $user_object, 'success_disqus', 'subscriber' === $role ? 'subscriber' : '' );
							exit();
						} elseif ( ( isset( $row[0] ) && $row[0]->identifier === $result->deuid ) ) {
							$user_object = get_user_by( 'email', $result->email );
							$loginpress_utilities->_home_url( $user_object, 'success_disqus', 'subscriber' === $role ? 'subscriber' : '' );
							exit();
						} else {
							echo esc_html__( 'User not found in our database', 'loginpress-pro' );
						}
					} else {
						add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
					}
				} else {
					add_filter( 'authenticate', array( 'LoginPress_Social_Utilities', 'loginpress_social_login_error' ), 40, 3 );
				}
			} else {
				// Redirect to Disqus Authentication page.
				$params = array(
					'client_id'     => $disqus_api_key,
					'redirect_uri'  => $disqus_callback_url,
					'response_type' => 'code',
					'scope'         => 'read,write',
				);

				wp_redirect( 'https://disqus.com/api/oauth/2.0/authorize/?' . http_build_query( $params ) ); // phpcs:ignore
				exit;
			}
		}

		/**
		 * Login with Reddit Account.
		 *
		 * @return void
		 * @since 5.0.0
		 * @version 6.1.0
		 */
		public function loginpress_on_reddit_login() {
			$lp_social_settings   = get_option( 'loginpress_social_logins' );
			$reddit_client_id     = $lp_social_settings['reddit_client_id'];
			$reddit_client_secret = $lp_social_settings['reddit_client_secret'];
			$reddit_redirect_uri  = $lp_social_settings['reddit_redirect_uri'];

			include_once LOGINPRESS_SOCIAL_DIR_PATH . 'classes/loginpress-utilities.php';

			$loginpress_utilities = new LoginPress_Social_Utilities();

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback parameter.
			if ( isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) {
				// Execute HTTP request to retrieve the access token.
				$params = array(
					'grant_type'    => 'authorization_code',
					'code'          => sanitize_text_field( wp_unslash( $_GET['code'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'client_id'     => $reddit_client_id,
					'client_secret' => $reddit_client_secret,
					'redirect_uri'  => $reddit_redirect_uri,
				);

				$response = wp_remote_post(
					'https://www.reddit.com/api/v1/access_token',
					array(
						'body'    => $params,
						'headers' => array(
							'Content-Type'  => 'application/x-www-form-urlencoded',
							'Authorization' => 'Basic ' . base64_encode( $reddit_client_id . ':' . $reddit_client_secret ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
						),
					)
				);

					$response = json_decode( wp_remote_retrieve_body( $response ), true );

					// Make sure access token is valid.
				if ( isset( $response['access_token'] ) && ! empty( $response['access_token'] ) ) {
					// Retrieve user profile information.
					$profile_response = wp_remote_get(
						'https://oauth.reddit.com/api/v1/me',
						array(
							'headers' => array( 'Authorization' => 'Bearer ' . $response['access_token'] ),
						)
					);

					$profile = json_decode( wp_remote_retrieve_body( $profile_response ), true );

					if ( isset( $profile['name'] ) && ! empty( $profile['name'] ) ) {
						if ( $this->lp_check_verification_flow() ) {
							exit();
						}
						// Process the user profile information.
						$email  = $profile['name'] . '@reddit.com';
						$result = $this->loginpress_create_result_obj( $profile, 'reddit', array( 'email' => $email ) );
						global $wpdb;
						$sha_verifier = sha1( $result->deutype . $result->deuid );
						$row          = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}loginpress_social_login_details` WHERE `provider_name` LIKE %s AND `identifier` LIKE %d AND `sha_verifier` LIKE %s LIMIT 1", $result->deutype, $result->deuid, $sha_verifier ) ); // @codingStandardsIgnoreLine.
						$user_object  = get_user_by( 'email', $email );
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
									$loginpress_utilities->_home_url( $user_object, 'success_reddit', 'subscriber' );
								} else {
									$loginpress_utilities->_home_url( $user_object, 'success_reddit' );
								}
								die();
							}
							$loginpress_utilities->register_user( $result->username, $result->email );
							$user_object = get_user_by( 'email', $result->email );
							do_action( 'loginpress_social_login_registered', $user_object );
							$id = $user_object->ID;
							$loginpress_utilities->update_usermeta( $id, $result, $role );
							if ( 'subscriber' === $role ) {
								$loginpress_utilities->_home_url( $user_object, 'success_reddit', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'success_reddit' );
							}
							exit();
						} elseif ( ( isset( $row[0] ) && $row[0]->identifier === $result->deuid ) ) {
								$user_object = get_user_by( 'email', $result->email );
								$id          = $user_object->ID;
							if ( 'subscriber' === $role ) {
									$loginpress_utilities->_home_url( $user_object, 'success_reddit', 'subscriber' );
							} else {
								$loginpress_utilities->_home_url( $user_object, 'success_reddit' );
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
				// Define params and redirect to Reddit Authentication page.
				$params = array(
					'client_id'     => $reddit_client_id,
					'response_type' => 'code',
					'redirect_uri'  => $reddit_redirect_uri,
					'scope'         => 'identity',
					'state'         => uniqid( '', true ), // Generate a unique state parameter to prevent CSRF attacks.
				);

				wp_redirect( 'https://www.reddit.com/api/v1/authorize?' . http_build_query( $params ) ); // phpcs:ignore
				exit;
			}
		}
	}
}
