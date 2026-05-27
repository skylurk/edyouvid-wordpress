<?php // phpcs:ignoreFile Generic.Files.LineLength.TooLong
/**
 * LoginPress Login Redirects Trait.
 *
 * Handles Some helping functions from class-loginpress-login-redirects file.
 *
 * @package   LoginPress
 * @subpackage Traits\LoginRedirects
 * @since 6.1.0
 */

/**
 * Prevent direct access.
 *
 * @package LoginPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! trait_exists( 'LoginPress_Login_Redirects_Trait' ) ) {
	/**
	 * LoginPress Login Redirects Trait.
	 *
	 * Handles Some helping functions from class-loginpress-login-redirects file.
	 *
	 * @package   LoginPress
	 * @subpackage Traits\LoginRedirects
	 * @since 6.1.0
	 */
	trait LoginPress_Login_Redirects_Trait {
		/**
		 * Register the rest routes for login redirects.
		 *
		 * @since 6.0.0
		 * @return void
		 */
		public function lp_login_redirect_register_routes() {
			// Get users with redirects.
			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/redirect-users',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'loginpress_get_redirect_users' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			// Get role redirects.
			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/redirect-roles',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'loginpress_get_redirect_roles' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			// Update user redirect.
			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/user-redirect-update',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_update_user_redirect' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			// Update role redirect.
			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/role-redirect-update',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_update_role_redirect' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			// Delete redirect.
			register_rest_route(
				LOGINPRESS_REST_NAMESPACE,
				'/delete-redirect',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_delete_redirect' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);
		}

		/**
		 * Get all the redirect users.
		 *
		 * @since 6.0.0
		 * @return array Array of users with redirects.
		 */
		public function loginpress_get_redirect_users() {
			$args = array(
				'blog_id'    => $GLOBALS['blog_id'],
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'OR',
					array( 'key' => 'loginpress_login_redirects_url' ),
					array( 'key' => 'loginpress_logout_redirects_url' ),
				),
			);

			$user_query = new WP_User_Query( $args );
			$users      = array();

			foreach ( $user_query->get_results() as $user ) {
				$users[] = array(
					'ID'              => (int) $user->ID,
					'user_login'      => sanitize_text_field( $user->user_login ),
					'user_email'      => sanitize_email( $user->user_email ),
					'login_redirect'  => esc_url_raw( get_user_meta( $user->ID, 'loginpress_login_redirects_url', true ) ),
					'logout_redirect' => esc_url_raw( get_user_meta( $user->ID, 'loginpress_logout_redirects_url', true ) ),
				);
			}

			return $users;
		}

		/**
		 * Get all the redirect roles.
		 *
		 * @since 6.0.0
		 * @version 6.1.1
		 * @return array Array of roles with redirects.
		 */
		public function loginpress_get_redirect_roles() {
			$redirect_roles = get_option( 'loginpress_redirects_role', array() );
			$roles          = array();

			foreach ( $redirect_roles as $name => $data ) {
				// Check if the stored key is a display name (has spaces or mixed case).
				// If so, we need to preserve it exactly as stored for display purposes.
				$is_stored_as_display_name = ( strpos( $name, ' ' ) !== false || $name !== strtolower( $name ) );
				
				// Get the display name for UI purposes.
				// This function preserves the original casing if the stored key is already a display name.
				$display_name = self::loginpress_get_role_display_name( $name );
				
				// If the stored key is a display name and doesn't match WordPress roles exactly,
				// preserve the original stored value to maintain user's intended casing.
				if ( $is_stored_as_display_name && $display_name !== $name ) {
					// Check if it's a case-insensitive match - if so, WordPress might have different casing.
					// In this case, preserve the original stored value.
					global $wp_roles;
					if ( ! isset( $wp_roles ) ) {
						$wp_roles = wp_roles();
					}
					$wp_roles_list = $wp_roles->get_names();
					$found_match   = false;
					foreach ( $wp_roles_list as $slug => $wp_display_name ) {
						if ( strtolower( $wp_display_name ) === strtolower( $name ) ) {
							$found_match = true;
							break;
						}
					}
					// If we found a case-insensitive match but casing differs, preserve original.
					if ( $found_match ) {
						$display_name = $name;
					}
				}
				
				$roles[] = array(
					'name'         => $name, // Store the actual key for deletion/update operations (backward compatibility).
					'display_name' => $display_name, // Display name for UI (preserves original casing).
					'login'        => esc_url_raw( $data['login'] ),
					'logout'       => esc_url_raw( $data['logout'] ),
				);
			}

			return $roles;
		}

		/**
		 * Get WordPress role display name from role slug or name.
		 *
		 * @param string $role_name Role name (can be slug or display name).
		 * @since 6.1.1
		 * @return string WordPress role display name, or formatted name if not found.
		 */
		protected static function loginpress_get_role_display_name( $role_name ) {
			// Get WordPress roles object.
			global $wp_roles;
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = wp_roles(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}

			$roles = $wp_roles->get_names();

			// Check if input already looks like a display name (has spaces or proper capitalization).
			// If it's already in display name format, we should preserve it as-is to maintain original casing.
			$is_display_name_format = ( strpos( $role_name, ' ' ) !== false || $role_name !== strtolower( $role_name ) );

			// First check: If it's a valid role slug (exact match), return its display name.
			if ( isset( $roles[ $role_name ] ) ) {
				return $roles[ $role_name ];
			}

			// Second check: Exact match for display name (case-sensitive) - preserves original casing.
			foreach ( $roles as $slug => $display_name ) {
				if ( $display_name === $role_name ) {
					return $display_name;
				}
			}

			// If input is already in display name format, preserve it as-is BEFORE any case-insensitive matching.
			// This ensures roles stored as display names (like "LMS Manager", "Editor", "Content Editor") 
			// maintain their original casing even if WordPress has a different casing.
			if ( $is_display_name_format ) {
				return $role_name;
			}

			// Third check: Case-insensitive match for slug (only for actual slugs, not display names).
			foreach ( $roles as $slug => $display_name ) {
				if ( strtolower( $slug ) === strtolower( $role_name ) ) {
					return $display_name;
				}
			}

			// Fourth check: Case-insensitive match for display name (only for actual slugs, not display names).
			// This should not run for display name format inputs due to early return above.
			foreach ( $roles as $slug => $display_name ) {
				if ( strtolower( $display_name ) === strtolower( $role_name ) ) {
					return $display_name;
				}
			}

			// Last resort: Format as title case for slugs.
			return ucwords( str_replace( array( '-', '_' ), ' ', $role_name ) );
		}

		/**
		 * Update the user redirect row.
		 *
		 * @param WP_REST_Request $request The REST request object.
		 * @since 6.0.0
		 * @return array|WP_Error Success array or error object.
		 */
		public function loginpress_update_user_redirect( WP_REST_Request $request ) {
			$params = $request->get_params();

			if ( ! isset( $params['id'] ) || ! isset( $params['login'] ) || ! isset( $params['logout'] ) ) {
				return new WP_Error( 'missing_params', __( 'Missing required parameters', 'loginpress-pro' ), array( 'status' => 400 ) );
			}

			$user_id = intval( $params['id'] );
			$login   = esc_url_raw( wp_unslash( $params['login'] ) );
			$logout  = esc_url_raw( wp_unslash( $params['logout'] ) );

			if ( ! is_multisite() ) {
				update_user_meta( $user_id, 'loginpress_login_redirects_url', $login );
				update_user_meta( $user_id, 'loginpress_logout_redirects_url', $logout );
			} else {
				update_user_option( $user_id, 'loginpress_login_redirects_url', $login, true );
				update_user_option( $user_id, 'loginpress_logout_redirects_url', $logout, true );
			}

			return array( 'success' => true );
		}

		/**
		 * Update the role redirect row.
		 *
		 * @param WP_REST_Request $request The REST request object.
		 * @since 6.0.0
		 * @version 6.1.1
		 * @return array|WP_Error Success array or error object.
		 */
		public function loginpress_update_role_redirect( WP_REST_Request $request ) {
			$params = $request->get_params();

			if ( ! isset( $params['role'] ) || ! isset( $params['login'] ) || ! isset( $params['logout'] ) ) {
				return new WP_Error( 'missing_params', __( 'Missing required parameters', 'loginpress-pro' ), array( 'status' => 400 ) );
			}

			$role_name = sanitize_text_field( $params['role'] );
			$login     = esc_url_raw( $params['login'] );
			$logout    = esc_url_raw( $params['logout'] );

			$redirect_roles = get_option( 'loginpress_redirects_role', array() );

			// First, try to find the role by exact match (handles roles stored as display names).
			$found_key = null;
			if ( isset( $redirect_roles[ $role_name ] ) ) {
				// Exact match found - use the existing key.
				$found_key = $role_name;
			} else {
				// Try case-insensitive match to find existing entry.
				foreach ( $redirect_roles as $stored_key => $value ) {
					if ( strtolower( $stored_key ) === strtolower( $role_name ) ) {
						$found_key = $stored_key;
						break;
					}
				}

				// If still not found, get the canonical slug and try that.
				if ( ! $found_key ) {
					$canonical_slug = self::loginpress_get_role_slug_from_name( $role_name );
					
					if ( isset( $redirect_roles[ $canonical_slug ] ) ) {
						$found_key = $canonical_slug;
					} else {
						// Try case-insensitive match with canonical slug.
						foreach ( $redirect_roles as $stored_key => $value ) {
							if ( strtolower( $stored_key ) === strtolower( $canonical_slug ) ) {
								$found_key = $stored_key;
								break;
							}
						}
					}
				}
			}

			// If we found an existing key, update it. Otherwise, use the canonical slug as new key.
			if ( $found_key ) {
				// Update existing entry.
				$redirect_roles[ $found_key ] = array(
					'login'  => $login,
					'logout' => $logout,
				);
			} else {
				// Create new entry with canonical slug.
				$canonical_slug = self::loginpress_get_role_slug_from_name( $role_name );
				$redirect_roles[ $canonical_slug ] = array(
					'login'  => $login,
					'logout' => $logout,
				);
			}

			update_option( 'loginpress_redirects_role', $redirect_roles );

			return array( 'success' => true );
		}

		/**
		 * Get WordPress role slug from role name or display name.
		 * Uses WordPress's role system to get the actual role slug.
		 * Always checks against WordPress's actual role data, no string manipulation.
		 *
		 * @param string $role_name Role name (can be slug or display name).
		 * @since 6.1.1
		 * @return string WordPress role slug, or original string if not found.
		 */
		public static function loginpress_get_role_slug_from_name( $role_name ) {
			// Get WordPress roles object - this is the source of truth.
			global $wp_roles;
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = wp_roles(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}

			// First check: Is it already a valid role slug? Use WordPress's get_role().
			if ( get_role( $role_name ) ) {
				return $role_name;
			}

			// Get all WordPress roles - this is the actual role data from WordPress.
			$roles = $wp_roles->get_names();

			// Second check: Does it match a role slug exactly (case-sensitive first, then case-insensitive)?
			foreach ( $roles as $slug => $display_name ) {
				// Exact match (case-sensitive).
				if ( $slug === $role_name ) {
					return $slug;
				}
				// Case-insensitive match for slug.
				if ( strtolower( $slug ) === strtolower( $role_name ) ) {
					return $slug;
				}
			}

			// Third check: Does it match a display name? Use WordPress's actual display names.
			foreach ( $roles as $slug => $display_name ) {
				// Exact match (case-sensitive).
				if ( $display_name === $role_name ) {
					return $slug;
				}
				// Case-insensitive match for display name.
				if ( strtolower( $display_name ) === strtolower( $role_name ) ) {
					return $slug;
				}
			}

			// If not found in WordPress's role system, return original for backward compatibility.
			return $role_name;
		}

		/**
		 * Delete the user/role redirect row.
		 *
		 * @param WP_REST_Request $request The REST request object.
		 * @since 6.0.0
		 * @version 6.1.1
		 * @return array|WP_Error Success array or error object.
		 */
		public function loginpress_delete_redirect( WP_REST_Request $request ) {
			$params = $request->get_params();

			if ( ! isset( $params['type'] ) || ! isset( $params['id'] ) ) {
				return new WP_Error( 'missing_params', __( 'Missing required parameters', 'loginpress-pro' ), array( 'status' => 400 ) );
			}

			if ( 'user' === $params['type'] ) {
				$user_id = intval( $params['id'] );
				delete_user_meta( $user_id, 'loginpress_login_redirects_url' );
				delete_user_meta( $user_id, 'loginpress_logout_redirects_url' );
			} elseif ( 'llms' === $params['type'] ) {
				$llms          = sanitize_text_field( $params['id'] );
				$redirect_llms = get_option( 'loginpress_redirects_llms', array() );

				if ( isset( $redirect_llms[ $llms ] ) ) {
					unset( $redirect_llms[ $llms ] );
					update_option( 'loginpress_redirects_llms', $redirect_llms );
				}
			} else {
				$role_name      = sanitize_text_field( $params['id'] );
				$redirect_roles = get_option( 'loginpress_redirects_role', array() );

				// Try multiple approaches to find and delete the role:
				// 1. Try exact match with the role_name as sent (for backward compatibility with old format).
				if ( isset( $redirect_roles[ $role_name ] ) ) {
					unset( $redirect_roles[ $role_name ] );
					update_option( 'loginpress_redirects_role', $redirect_roles );
				} else {
					// 2. Get the actual WordPress role slug and try that.
					$role = self::loginpress_get_role_slug_from_name( $role_name );
					if ( isset( $redirect_roles[ $role ] ) ) {
						unset( $redirect_roles[ $role ] );
						update_option( 'loginpress_redirects_role', $redirect_roles );
					} else {
						// 3. Try case-insensitive search through all stored keys.
						$found_key = null;
						foreach ( $redirect_roles as $stored_key => $value ) {
							// Case-insensitive comparison.
							if ( strtolower( $stored_key ) === strtolower( $role_name ) || strtolower( $stored_key ) === strtolower( $role ) ) {
								$found_key = $stored_key;
								break;
							}
						}
						if ( $found_key ) {
							unset( $redirect_roles[ $found_key ] );
							update_option( 'loginpress_redirects_role', $redirect_roles );
						}
					}
				}
			}

			return array( 'success' => true );
		}
	}
}
