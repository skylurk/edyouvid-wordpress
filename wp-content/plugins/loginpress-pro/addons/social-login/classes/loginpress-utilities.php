<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * LoginPress_Social_Utilities
 *
 * @package LoginPress Social Login
 * @since 3.0.0
 * @version 6.1.0
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

if ( ! class_exists( 'LoginPress_Social_Utilities' ) ) {

	/**
	 * LoginPress_Social_Utilities
	 *
	 * @since 3.0.0
	 * @version 6.1.0
	 */
	class LoginPress_Social_Utilities {

		/**
		 * Get the site URL.
		 *
		 * @since 3.0.0
		 * @return string Site URL
		 */
		public function loginpress_site_url() {
			return site_url();
		}

		/**
		 * Get the callback URL.
		 *
		 * @since 3.0.0
		 * @return string Callback URL
		 */
		public function loginpress_callback_url() {

			$url = wp_login_url();
			if ( strpos( $url, '?' ) === false ) {
				$url .= '?';
			} else {
				$url .= '&';
			}
			return $url;
		}

		/**
		 * Set header location.
		 *
		 * @param string $redirect Redirect URL.
		 * @return void
		 * @since 3.0.0
		 * @version 6.1.0
		 */
		public function redirect( $redirect ) {
			if ( headers_sent() ) {
				// Use JavaScript to redirect if content has been previously sent.
				echo '<script language="JavaScript" type="text/javascript">window.location=\'';
				echo esc_url( $redirect );
				echo '\';</script>';
			} else { // Default Header Redirect.
				header( 'Location: ' . $redirect );
			}
			exit;
		}

		/**
		 * Function to access the protected object properties.
		 *
		 * @param object $graph_object The object of graph.
		 * @param string $property The property of graph.
		 * @return object Value.
		 * @since 3.0.0
		 */
		public function loginpress_fetch_graph_user( $graph_object, $property ) {

			// Using ReflectionClass that repots information about class.
			$reflection = new ReflectionClass( $graph_object );
			// Gets a ReflectionProperty for a class property.
			$get_property = $reflection->getProperty( $property );
			// Set method accessibility.
			$get_property->setAccessible( true );
			// Return the property value w.r.t object.
			return $get_property->getValue( $graph_object );
		}

		/**
		 * Function to insert the user data into plugin's custom table.
		 *
		 * @param int    $user_id ID of the user.
		 * @param object $user_object The object.
		 * @since 3.0.0
		 * @return void
		 */
		public static function link_user( $user_id, $user_object ) {
			global $wpdb;
			$sha_verifier = sha1( $user_object->deutype . $user_object->deuid );
			$table_name   = "{$wpdb->prefix}loginpress_social_login_details";
			$first_name   = sanitize_text_field( $user_object->first_name );
			$last_name    = sanitize_text_field( $user_object->last_name );
			$profile_url  = sanitize_text_field( $user_object->url );
			$photo_url    = sanitize_text_field( $user_object->deuimage );
			$display_name = sanitize_text_field( $user_object->first_name . ' ' . $user_object->last_name );
			$description  = sanitize_text_field( $user_object->about );

			$submit_array = array(
				'user_id'       => $user_id,
				'provider_name' => $user_object->deutype,
				'identifier'    => $user_object->deuid,
				'sha_verifier'  => $sha_verifier,
				'email'         => $user_object->email,
				'first_name'    => $first_name,
				'last_name'     => $last_name,
				'profile_url'   => $profile_url,
				'photo_url'     => $photo_url,
				'display_name'  => $display_name,
				'description'   => $description,
				'gender'        => $user_object->gender,
			);

			$wpdb->insert( $table_name, $submit_array ); // @codingStandardsIgnoreLine.
			if ( ! $user_object ) {
				echo esc_html( 'Data insertion failed' );
			}
		}

		/**
		 * Redirect user after successfully login.
		 *
		 * @param object $user The object of user.
		 * @param string $social_channel The social channel being used.
		 * @param string $subscriber Subscriber information.
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return bool|void
		 */
		public function _home_url( $user, $social_channel = '', $subscriber = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed, PSR2.Methods.MethodDeclaration.Underscore

			$user_id = $user->ID;
			if ( ! $this->set_cookies( $user_id ) ) {
				return false;
			}

			if ( isset( $_COOKIE['lg_redirect_to'] ) ) {
				$redirect = wp_unslash( $_COOKIE['lg_redirect_to'] ); // @codingStandardsIgnoreLine.
				setcookie( 'lg_redirect_to', '', time() - 3600 );
			} elseif ( ! wp_get_referer() ) {
				$redirect = site_url();
			} elseif ( ! strpos( wp_get_referer(), 'wp-login.php' ) ) {
				$redirect = wp_get_referer();
			} else {
				$redirect = admin_url();
			}

			if ( class_exists( 'LoginPress_Set_Login_Redirect' ) && do_action( 'loginpress_redirect_autologin', $user ) ) {
				$user_login_url = do_action( 'loginpress_redirect_autologin', $user );
			} else {
				$user_login_url = apply_filters( 'login_redirect', $redirect, site_url(), wp_signon() );
			}

			/**
			 * Login filter for social logins.
			 *
			 * @since 6.0.0
			 * @version 6.1.0
			 */
			$login_filter = apply_filters( 'loginpress_social_login_redirect', false );

			if ( ! empty( $social_channel ) && is_array( $login_filter ) ) {
				switch ( $social_channel ) {
					case 'success_gplus':
						$social_redirect = $login_filter['success_gplus'];
						break;

					case 'success_apple':
						$social_redirect = $login_filter['success_apple'];
						break;

					case 'success_facebook':
						$social_redirect = $login_filter['success_facebook'];
						break;

					case 'success_twitter':
						$social_redirect = $login_filter['success_twitter'];
						break;

					case 'success_linkedin':
						$social_redirect = $login_filter['success_linkedin'];
						break;
					case 'success_microsoft':
						$social_redirect = $login_filter['success_microsoft'];
						break;
					case 'success_github':
						$social_redirect = $login_filter['success_github'];
						break;
					case 'success_discord':
						$social_redirect = $login_filter['success_discord'];
						break;
					case 'success_wordpress':
						$social_redirect = $login_filter['success_wordpress'];
						break;
					case 'success_amazon':
						$social_redirect = $login_filter['success_amazon'];
						break;
					case 'success_pinterest':
						$social_redirect = $login_filter['success_pinterest'];
						break;
					case 'success_disqus':
						$social_redirect = $login_filter['success_disqus'];
						break;
					case 'success_reddit':
						$social_redirect = $login_filter['success_reddit'];
						break;
					case 'success_twitch':
						$social_redirect = $login_filter['success_twitch'];
						break;
					case 'success_spotify':
						$social_redirect = $login_filter['success_spotify'];
						break;
				}

				wp_safe_redirect( esc_url( $social_redirect ) );
				exit();
			}

			$args         = array(
				'lpsl_login_id' => $social_channel,
			);
			$redirect_url = add_query_arg( $args, $user_login_url );
			wp_safe_redirect( $redirect_url );
			exit();
		}

		/**
		 * Set the cookies for a user ( Remember me ).
		 *
		 * @param int  $user_id The User ID.
		 * @param bool $remember The option.
		 * @since 3.0.0
		 * @version 6.1.0
		 *
		 * @return bool
		 */
		public function set_cookies( $user_id = 0, $remember = true ) {
			if ( ! function_exists( 'wp_set_auth_cookie' ) ) {
				return false;
			}
			if ( ! $user_id ) {
				return false;
			}
			$user = get_user_by( 'id', (int) $user_id );
			wp_clear_auth_cookie();
			wp_set_auth_cookie( $user_id, $remember );
			wp_set_current_user( $user_id );
			do_action( 'wp_login', $user->user_login, $user );
			return true;
		}

		/**
		 * Register the User.
		 *
		 * @param string $user_name The username.
		 * @param string $user_email The email.
		 * @return int User ID.
		 * @since 6.0.0
		 * @version 6.1.0
		 */
		public function register_user( $user_name, $user_email ) {
			$can_social_register = (bool) apply_filters( 'loginpress_social_login_before_register', true );
			/**
			 * Filter to allow registrations only via social login but not by default WordPress ways.
			 *
			 * @param bool $can_register Whether users can register. Default is the value of 'users_can_register' option.
			 * @since 6.1.0
			 */
			$can_register       = apply_filters( 'loginpress_social_login_can_register', get_option( 'users_can_register' ) );
			$loginpress_setting = get_option( 'loginpress_setting' );
			if ( isset( $loginpress_setting['restrict_domains_textarea'] ) && ! empty( $loginpress_setting['restrict_domains_textarea'] ) ) {
				/**
				* This filter is for internal use. It is used to control whether a new user should be allowed to register via social login
				* (e.g., Google, Facebook), depending on the domain restriction settings configured by the admin.
				*
				* @param string $user_email The email.
				* @since 6.0.0
				*/
				$user_email = apply_filters( 'loginpress_social_login_register_email', $user_email );
			}
			if ( $can_register && $can_social_register ) {
				$username        = self::get_username( $user_name );
				$random_password = wp_generate_password( 12, true, false );
				$user_id         = wp_create_user( $username, $random_password, $user_email );
				return $user_id;
			} else {
				wp_safe_redirect(
					add_query_arg(
						array(
							'lp_social_without_reg' => 'true',
						),
						wp_login_url()
					)
				);
				die();
			}
		}

		/**
		 * Get the username based on user ID.
		 *
		 * @param string $user_login The user login.
		 * @return string The user login.
		 * @since 3.0.0
		 */
		public static function get_username( $user_login ) {

			if ( username_exists( $user_login ) ) :

				$i       = 1;
				$user_ID = $user_login;

				do {
					$user_ID = $user_login . '_' . ( $i++ );
				} while ( username_exists( $user_ID ) );

				$user_login = $user_ID;
			endif;

			return $user_login;
		}

		/**
		 * Update the user meta data.
		 *
		 * @param int    $user_id The User ID.
		 * @param object $user_data The User Object.
		 * @param string $role The Role of the User.
		 * @return void
		 * @since 3.0.0
		 */
		public static function update_usermeta( $user_id, $user_data, $role ) {

			$meta_key = array( 'email', 'first_name', 'last_name', 'deuid', 'deutype', 'deuimage', 'description', 'sex' );
			$_object  = array( $user_data->email, $user_data->first_name, $user_data->last_name, $user_data->deuid, $user_data->deutype, $user_data->deuimage, $user_data->about, $user_data->gender );

			$i = 0;
			while ( $i < 8 ) :
				update_user_meta( $user_id, $meta_key[ $i ], $_object[ $i ] );
				++$i;
			endwhile;

			wp_update_user(
				array(
					'ID'           => $user_id,
					'display_name' => $user_data->first_name . ' ' . $user_data->last_name,
					'role'         => $role,
					'user_url'     => $user_data->url,
				)
			);

			self::link_user( $user_id, $user_data );
		}


		/**
		 * Show loginpress social login error/s.
		 *
		 * @param int    $user The User ID.
		 * @param string $username The Username.
		 * @param string $password The Password.
		 * @return WP_Error The Error.
		 * @since 5.0.0
		 */
		public static function loginpress_social_login_error( $user, $username, $password ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			$error = new WP_Error();
			/* Translators: The Error Message. */
			$error->add( 'loginpress_social_login', sprintf( __( '%1$sERROR%2$s: Invalid `Client ID` or `Client Secret` combination?', 'loginpress-pro' ), '<strong>', '</strong>' ) );
			return $error;
		}
	}
}
