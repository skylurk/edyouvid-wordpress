<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * LoginPress Set Login Redirect Class.
 *
 * @package LoginPress
 * @subpackage LoginRedirects
 * @since 3.0.0
 * @version 6.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly.
	exit;
}

/**
 * Main class for Login Redirects.
 *
 * @package LoginPress Pro
 * @since 3.0.0
 * @version 6.1.1
 */

if ( ! class_exists( 'LoginPress_Set_Login_Redirect' ) ) :

	// Include the trait for shared role slug functionality.
	require_once __DIR__ . '/traits/redirects.php';

	/**
	 * Sets LoginPress Login Redirects.
	 *
	 * @package LoginPress Pro
	 * @since 3.0.0
	 * @version 6.1.0
	 */
	class LoginPress_Set_Login_Redirect {

		use LoginPress_Login_Redirects_Trait;

		/**
		 * Whether WPML is active.
		 *
		 * @var bool
		 * @since 6.1.1
		 */
		private $wpml_active = false;

		/**
		 * Class constructor.
		 *
		 * @since 3.0.0
		 * @version 6.1.1
		 * @return void
		 */
		public function __construct() {

			// Include WPML compatibility helper.
			if ( defined( 'ICL_SITEPRESS_VERSION' ) && class_exists( 'SitePress' ) ) {
				include_once __DIR__ . '/class-loginpress-wpml-compatibility.php';
			}

			add_filter( 'login_redirect', array( $this, 'loginpress_redirects_after_login' ), 10, 3 );
			add_action( 'clear_auth_cookie', array( $this, 'loginpress_redirects_after_logout' ), 10 );
			add_action( 'loginpress_redirect_autologin', array( $this, 'loginpress_autologin_redirects' ), 10, 1 );
			// User switching hooks - redirect after user switching compatibility.
			add_action( 'switch_to_user', array( $this, 'loginpress_redirect_after_user_switch' ), 10, 4 );
			$this->wpml_active = defined( 'ICL_SITEPRESS_VERSION' ) && class_exists( 'SitePress' );
		}

		/**
		 * Check if inner link provided.
		 *
		 * @param string $url URL of the site.
		 * @since 3.0.0
		 * @return bool True if inner link, false otherwise.
		 */
		public function is_inner_link( $url ) {

			$current_site = wp_parse_url( get_site_url() );
			$current_site = $current_site['host'];

			if ( false !== strpos( $url, $current_site ) ) {
				return true;
			}

			return false;
		}

		/**
		 * This function wraps around the main redirect function to determine whether or not to bypass the WordPress local URL limitation.
		 *
		 * @param string $redirect_to Where to redirect.
		 * @param string $requested_redirect_to Requested redirect.
		 * @param object $user User object.
		 * @since 3.0.0
		 * @version 6.1.1
		 * @return string Redirect URL.
		 */
		public function loginpress_redirects_after_login( $redirect_to, $requested_redirect_to, $user ) {

			if ( apply_filters( 'prevent_loginpress_login_redirect', false ) ) {
				return $redirect_to;
			}

			if ( isset( $user->ID ) ) {
				$user_redirects_url = $this->loginpress_redirect_url( $user->ID, 'loginpress_login_redirects_url' );
				$role_redirects_url = get_option( 'loginpress_redirects_role' );

				if ( isset( $user->roles ) && is_array( $user->roles ) ) {

					if ( ! empty( $user_redirects_url ) ) { // check for specific user.

						// Process URL for WPML compatibility.
						if ( $this->wpml_active ) {
							$user_redirects_url = $this->loginpress_process_wpml_redirect_url( $user_redirects_url );
						}

						if ( $this->is_inner_link( $user_redirects_url ) ) {
							return $user_redirects_url;
						}

						$this->loginpress_safe_redirects( $user->ID, $user->name, $user, $user_redirects_url );

					} elseif ( ! empty( $role_redirects_url ) ) { // check for specific role.

						foreach ( $role_redirects_url as $key => $value ) {
							$login_url = isset( $value['login'] ) && ! empty( $value['login'] ) ? $value['login'] : $redirect_to;
							// Get the actual WordPress role slug from the stored key.
							$role_slug = self::loginpress_get_role_slug_from_name( $key );
							if ( in_array( $role_slug, $user->roles, true ) ) {

								// Process URL for WPML compatibility.
								if ( $this->wpml_active ) {
									$login_url = $this->loginpress_process_wpml_redirect_url( $login_url );
								}

								$this->loginpress_safe_redirects( $user->ID, $user->name, $user, $login_url );

							}
						}
					}
				} else {
					return $redirect_to;
				}
			}
			return $redirect_to;
		}

		/**
		 * Callback for clear_auth_cookie.
		 * Fire after user is logged out.
		 *
		 * @since 3.0.0
		 * @version 6.1.1
		 * @return void
		 */
		public function loginpress_redirects_after_logout() {

			// Prevent method from executing.
			if ( apply_filters( 'prevent_loginpress_logout_redirect', false ) ) {
				return;
			}

			// Prevent LoginPress logout redirects during User Switching operations.
			if ( $this->loginpress_is_user_switching() ) {
				return;
			}

			$user_id = get_current_user_id();

			// Only execute for registered user.
			if ( 0 !== $user_id ) {
				$user_info = get_userdata( $user_id );
				$user_role = $user_info->roles;

				$role_redirects_url = get_option( 'loginpress_redirects_role' );
				$user_redirects_url = $this->loginpress_redirect_url( $user_id, 'loginpress_logout_redirects_url' );

				if ( isset( $user_redirects_url ) && ! empty( $user_redirects_url ) ) {
					// Process URL for WPML compatibility.
					if ( $this->wpml_active ) {
						$user_redirects_url = $this->loginpress_process_wpml_redirect_url( $user_redirects_url );
					}
					wp_safe_redirect( $user_redirects_url );
					exit;
				} elseif ( ! empty( $role_redirects_url ) ) {
					foreach ( $role_redirects_url as $key => $value ) {
						$logout_url = isset( $value['logout'] ) && ! empty( $value['logout'] ) ? $value['logout'] : wp_login_url();

						// Process URL for WPML compatibility.
						if ( $this->wpml_active ) {
							$logout_url = $this->loginpress_process_wpml_redirect_url( $logout_url );
						}
						// Get the actual WordPress role slug from the stored key.
						$role_slug = self::loginpress_get_role_slug_from_name( $key );

						if ( in_array( $role_slug, $user_role, true ) ) {
							wp_safe_redirect( $logout_url );
							exit;
						}
					}
				}
			}
		}

		/**
		 * Set auth cookies for user and redirect on login.
		 *
		 * @param int    $user_id User ID.
		 * @param string $username Username.
		 * @param object $user User object.
		 * @param string $redirect Redirect string.
		 * @since 6.0.0
		 * @return void
		 */
		public function loginpress_safe_redirects( $user_id, $username, $user, $redirect ) {

			wp_set_auth_cookie( $user_id, false );
			do_action( 'wp_login', $username, $user );
			wp_safe_redirect( $redirect );
			exit;
		}

		/**
		 * Compatible Login Redirects with Auto Login Add-On.
		 * Redirect a user to a custom URL for specific auto login link.
		 *
		 * @param object $user User object.
		 * @since 3.0.0
		 * @version 6.1.2
		 * @return void
		 */
		public function loginpress_autologin_redirects( $user ) {

			$user_redirects_url = $this->loginpress_redirect_url( $user->ID, 'loginpress_login_redirects_url' );
			$role_redirects_url = get_option( 'loginpress_redirects_role' );

			if ( isset( $user->roles ) && is_array( $user->roles ) ) {

				if ( ! empty( $user_redirects_url ) ) { // check for specific user.
					// Process URL for WPML compatibility.
					if ( $this->wpml_active ) {
						$user_redirects_url = $this->loginpress_process_wpml_redirect_url( $user_redirects_url );
					}
					$this->loginpress_safe_redirects( $user->ID, $user->name, $user, $user_redirects_url );
				} elseif ( ! empty( $role_redirects_url ) ) { // check for specific role.

					foreach ( $role_redirects_url as $key => $value ) {

						// Get the actual WordPress role slug from the stored key.
						$role_slug = self::loginpress_get_role_slug_from_name( $key );

						if ( in_array( $role_slug, $user->roles, true ) ) {
							$login_url = isset( $value['login'] ) && ! empty( $value['login'] ) ? $value['login'] : '';
							// Process URL for WPML compatibility.
							if ( $this->wpml_active ) {
								$login_url = $this->loginpress_process_wpml_redirect_url( $login_url );
							}
							$this->loginpress_safe_redirects( $user->ID, $user->name, $user, $login_url );
						}
					}
				}
			}
		}

		/**
		 * Get user meta.
		 *
		 * @param int    $user_id ID of the user.
		 * @param string $option User meta key.
		 * @since 3.0.0
		 * @return string Redirect URL.
		 */
		public function loginpress_redirect_url( $user_id, $option ) {

			if ( ! is_multisite() ) {
				$redirect_url = get_user_meta( $user_id, $option, true );
			} else {
				$redirect_url = get_user_option( $option, $user_id );
			}

			return $redirect_url;
		}

		/**
		 * Check if current user has role redirect.
		 *
		 * @param object $user Current user object.
		 * @since 6.0.0
		 * @version 6.1.1
		 * @return bool True if user has role redirect, false otherwise.
		 */
		public static function loginpress_role_redirect( $user ) {

			$role_redirects_url = get_option( 'loginpress_redirects_role' );
			if ( empty( $role_redirects_url ) ) {
				return false;
			}

			foreach ( $role_redirects_url as $key => $value ) {
				// Get the actual WordPress role slug from the stored key.
				$role_slug = self::loginpress_get_role_slug_from_name( $key );

				if ( in_array( $role_slug, $user->roles, true ) ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Check if current user has role redirect from user groups.
		 *
		 * @param object $user Current user object.
		 * @since 6.0.0
		 * @return bool True if user has role redirect, false otherwise.
		 */
		public static function loginpress_group_redirect( $user ) {

			$group_redirect_url = get_option( 'loginpress_redirects_group' );
			if ( empty( $group_redirect_url ) || ! function_exists( 'learndash_is_user_in_group' ) ) {
				return false;
			}

			$groups = get_posts(
				array(
					'post_type'   => 'groups',
					'post_status' => 'publish',
					'numberposts' => -1,
					'fields'      => 'ids,post_name',
				)
			);

			foreach ( $groups as $group ) {
				$group_name = $group->post_name;
				$group_id   = $group->ID;
				// check if user is part of a group.
				$user_in_groups = learndash_is_user_in_group( $user->ID, $group_id );
				if ( isset( $group_redirect_url[ $group_name ] ) && $user_in_groups ) {
					return true;
				}
			}
			return false;
		}
		/**
		 * Check if user switching is in progress
		 *
		 * @return bool True if user switching is in progress
		 * @since 6.0.0
		 */
		private function loginpress_is_user_switching() {
			// Check if we're in a user switching action.
			if ( isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], array( 'switch_to_user', 'switch_to_olduser', 'switch_off' ), true ) ) { // phpcs:ignore
				return true;
			}

			// Check if current user was switched in.
			if ( function_exists( 'current_user_switched' ) && current_user_switched() ) {
				return true;
			}

			return false;
		}

		/**
		 * Process redirect URL for WPML compatibility.
		 *
		 * @param string $redirect_url The redirect URL to process.
		 * @since 6.1.1
		 * @return string Processed redirect URL.
		 */
		private function loginpress_process_wpml_redirect_url( $redirect_url ) {
			if ( $this->wpml_active && class_exists( 'LoginPress_WPML_Compatibility' ) ) {
				return LoginPress_WPML_Compatibility::loginpress_wpml_process_redirect_url( $redirect_url );
			}
			return $redirect_url;
		}

		/**
		 * Redirect after user switching, For user switching plugin compatibility
		 *
		 * @param int $user_id     The ID of the user being switched to.
		 * @since 6.0.0
		 * @version 6.1.1
		 */
		public function loginpress_redirect_after_user_switch( $user_id ) {
			// Get the user object for the user being switched to.
			$user = get_userdata( $user_id );
			if ( ! $user ) {
				return;
			}

			// Apply redirect logic for the switched user.
			$user_redirects_url = $this->loginpress_redirect_url( $user->ID, 'loginpress_login_redirects_url' );
			$role_redirects_url = get_option( 'loginpress_redirects_role' );

			if ( isset( $user->roles ) && is_array( $user->roles ) ) {
				if ( ! empty( $user_redirects_url ) ) {
					// Process URL for WPML compatibility.
					if ( $this->wpml_active ) {
						$user_redirects_url = $this->loginpress_process_wpml_redirect_url( $user_redirects_url );
					}
					wp_safe_redirect( $user_redirects_url );
					exit;
				} elseif ( ! empty( $role_redirects_url ) ) {
					foreach ( $role_redirects_url as $key => $value ) {
						$login_url = isset( $value['login'] ) && ! empty( $value['login'] ) ? $value['login'] : '';
						// Get the actual WordPress role slug from the stored key.
						$role_slug = self::loginpress_get_role_slug_from_name( $key );
						if ( in_array( $role_slug, $user->roles, true ) && ! empty( $login_url ) ) {
							// Process URL for WPML compatibility.
							if ( $this->wpml_active ) {
								$login_url = $this->loginpress_process_wpml_redirect_url( $login_url );
							}
							wp_safe_redirect( $login_url );
							exit;
						}
					}
				}
			}
		}
	}

endif;
new LoginPress_Set_Login_Redirect();
