<?php
/**
 * LoginPress LifterLMS Redirect Trait.
 *
 * Contains all the methods related to LLMS login and logout redirects.
 *
 * @package   LoginPress-Pro
 * @subpackage Traits\Loginpress-Integration
 * @since 6.1.0
 */

/**
 * LoginPress LifterLMS Redirect Trait file.
 *
 * Prevent direct access.
 *
 * @package LoginPress-Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! trait_exists( 'LoginPress_Lifter_Redirect_Trait' ) ) {
	/**
	 * LoginPress LifterLMS Redirect Trait.
	 *
	 * Contains all the methods related to LLMS login and logout redirects.
	 *
	 * @package   LoginPress-Pro
	 * @subpackage Traits\Loginpress-Integration
	 * @since 6.1.0
	 */
	trait LoginPress_Lifter_Redirect_Trait {

		/**
		 * Registers REST API endpoints for LoginPress and LLMS Integration.
		 *
		 * Endpoints:
		 *
		 * - `GET /llms-data`: Returns data for searching LLMS Courses and Memberships.
		 * - `GET /redirect-llms`: Returns role redirects for LLMS.
		 * - `POST /llms-redirect-update`: Updates role redirects for LLMS.
		 *
		 * @since 6.0.0
		 */
		public function loginpress_llms_rest_api() {

			// Return data for Search to LLMS Course/Membership.
			register_rest_route(
				'loginpress/v1',
				'/llms-data',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'loginpress_llms_data' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			// Get role redirects.
			register_rest_route(
				'loginpress/v1',
				'/redirect-llms',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'loginpress_llms_redirect_data' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);

			register_rest_route(
				'loginpress/v1',
				'/llms-redirect-update',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'loginpress_llms_redirect_update' ),
					'permission_callback' => 'loginpress_rest_can_manage_options',
				)
			);
		}

		/**
		 * Retrieves LLMS courses and memberships data.
		 *
		 * @param WP_REST_Request $request The REST API request object.
		 *
		 * @return array An associative array containing LLMS data with labels and values.
		 *
		 * @since 6.0.0
		 */
		public function loginpress_llms_data( WP_REST_Request $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

			$llms = $this->loginpress_get_posts_data( array( 'course', 'llms_membership' ), 'publish', 'ids,post_name,post_type', -1 );

			$llms_data = array();
			foreach ( $llms as $llm => $value ) {
				if ( is_object( $value ) && 'course' === $value->post_type ) {
					$llms_data[ $llm ]['label'] = __( 'Course : ', 'loginpress-pro' ) . $value->post_title;
					$llms_data[ $llm ]['value'] = $value->post_name;
				} elseif ( is_object( $value ) && 'llms_membership' === $value->post_type ) {
					$llms_data[ $llm ]['label'] = __( 'Membership : ', 'loginpress-pro' ) . $value->post_title;
					$llms_data[ $llm ]['value'] = $value->post_name;
				}
				continue;
			}
			return $llms_data;
		}

		/**
		 * Retrieves stored LLMS login and logout redirects.
		 *
		 * This function fetches the login and logout redirect data for LLMS courses
		 * and memberships from the options table, returning an array of redirect
		 * information.
		 *
		 * @return array An array of associative arrays with keys 'name', 'login', and 'logout'.
		 *
		 * @since 6.0.0
		 */
		public function loginpress_llms_redirect_data() {
			$loginpress_redirects_llms = get_option( 'loginpress_redirects_llms', array() );

			$llms = array();
			// Accumulate all items in the $llms array.
			foreach ( $loginpress_redirects_llms as $key => $value ) {
				// Ensure $value has the expected keys.
				$llms[] = array(
					'value'  => $key,
					'login'  => $value['login'],
					'logout' => $value['logout'],
					'name'   => $value['name'],
				);
			}
			return $llms;
		}

		/**
		 * Updates the redirects for LLMS courses and memberships.
		 *
		 * Expects the following parameters in the request:
		 * - `llms`: The LLMS course or membership ID.
		 * - `login`: The login redirect URL.
		 * - `logout`: The logout redirect URL.
		 *
		 * @param WP_REST_Request $request The REST API request object.
		 *
		 * @return array An associative array with a 'success' key containing a boolean value indicating success.
		 *
		 * @since 6.0.0
		 */
		public function loginpress_llms_redirect_update( WP_REST_Request $request ) {
			$params = $request->get_params();
			if ( empty( $params ) ) {
				return new WP_Error( 'missing_params', __( 'Missing required parameters', 'loginpress-pro' ), array( 'status' => 400 ) );
			}

			$llms     = sanitize_text_field( $params['llms'] );
			$login    = esc_url_raw( $params['login'] );
			$logout   = esc_url_raw( $params['logout'] );
			$name     = sanitize_text_field( $params['name'] );
			$priority = 10;

			$add_llms          = get_option( 'loginpress_redirects_llms', array() );
			$add_llms[ $llms ] = array(
				'login'    => $login,
				'logout'   => $logout,
				'name'     => $name,
				'priority' => $priority,
			);

			update_option( 'loginpress_redirects_llms', $add_llms );
			return array( 'success' => true );
		}

		/**
		 * This function is called when a user logs in.
		 * It checks if the user is a member of any LifterLMS Course/Membership and redirects them accordingly.
		 *
		 * @param string $redirect_to where to redirect.
		 * @param string $requested_redirect_to requested redirect.
		 * @param object $is_user user object.
		 * @return string
		 * @since  6.0.0
		 */
		public function loginpress_redirects_after_login_llms( $redirect_to, $requested_redirect_to, $is_user = null ) {

			// incase of woocommerce or lifterlms login redirect hooks.
			$wc_hook   = 'woocommerce_login_redirect' === current_filter() ? true : false;
			$llms_hook = 'lifterlms_login_redirect' === current_filter() ? true : false;
			if ( $wc_hook ) {
				$user = $requested_redirect_to;
			} elseif ( $llms_hook ) {
				$user = get_userdata( $requested_redirect_to );
			} else {
				$user = $is_user;
			}

			$loginpress_redirects = new LoginPress_Set_Login_Redirect();

			if ( ! isset( $user->ID ) ) {
				return $redirect_to;
			}

			$user_redirects_url = $loginpress_redirects->loginpress_redirect_url( $user->ID, 'loginpress_login_redirects_url' );
			$role_redirects_url = $loginpress_redirects->loginpress_role_redirect( $user );
			$group_redirect_url = $loginpress_redirects->loginpress_group_redirect( $user );
			$llms_redirects_url = get_option( 'loginpress_redirects_llms' );

			if ( ! isset( $user->roles ) || ! is_array( $user->roles ) || ! empty( $user_redirects_url )
				|| true === $role_redirects_url || true === $group_redirect_url || empty( $llms_redirects_url ) ) {
				return $redirect_to;
			}

			$courses = $this->loginpress_get_posts_data( array( 'course' ) );

			$course_data = $this->loginpress_llms_login_logout_data( $courses, 'login', $user->ID, $redirect_to );

			if ( empty( $course_data ) ) {

				$memberships = $this->loginpress_get_posts_data( array( 'llms_membership' ) );

				$membership_data = $this->loginpress_llms_login_logout_data( $memberships, 'login', $user->ID, $redirect_to );

			}

			$final_data = ! empty( $membership_data ) ? $membership_data : $course_data;

			if ( empty( $final_data ) ) {
				return $redirect_to;
			}
			usort( $final_data, array( $this, 'loginpress_sort_by_priority' ) );
			$highest_priority_one = reset( $final_data );
			$login_url            = $highest_priority_one['login'];

			if ( $loginpress_redirects->is_inner_link( $login_url ) ) {
				return $login_url;
			}

			$loginpress_redirects->loginpress_safe_redirects( $user->ID, $user->name, $user, $login_url );
		}

		/**
		 * Handles user logout redirects based on priority: user-specific, role-based, or LearnDash group-based settings.
		 *
		 * @return void
		 * @since  6.0.0
		 */
		public function loginpress_redirects_after_logout() {

			$user_id              = get_current_user_id();
			$user                 = wp_get_current_user();
			$loginpress_redirects = new LoginPress_Set_Login_Redirect();
			$user_redirects_url   = $loginpress_redirects->loginpress_redirect_url( $user_id, 'loginpress_login_redirects_url' );
			$role_redirects_url   = LoginPress_Set_Login_Redirect::loginpress_role_redirect( $user );
			$group_redirect_url   = LoginPress_Set_Login_Redirect::loginpress_group_redirect( $user );
			$llms_redirects_url   = get_option( 'loginpress_redirects_llms' );

			if ( 0 !== $user_id ) {

				if ( empty( $user_redirects_url ) && false === $role_redirects_url && false === $group_redirect_url && ! empty( $llms_redirects_url ) ) {

					$courses = $this->loginpress_get_posts_data( array( 'course' ) );

					$course_data = $this->loginpress_llms_login_logout_data( $courses, 'logout', $user->ID );

					if ( empty( $course_data ) ) {

						$memberships = $this->loginpress_get_posts_data( array( 'llms_membership' ) );

						$membership_data = $this->loginpress_llms_login_logout_data( $memberships, 'logout', $user->ID );

					}

					$final_data = ! empty( $membership_data ) ? $membership_data : $course_data;
					if ( empty( $final_data ) ) {
						wp_safe_redirect( '/' );
						exit;
					}
					usort( $final_data, array( $this, 'loginpress_sort_by_priority' ) );
					$highest_priority_one = reset( $final_data );
					$logout_url           = $highest_priority_one['logout'];
					wp_safe_redirect( $logout_url );
					exit;
				}
			}
		}

		/**
		 * Sort by priority
		 *
		 * @param array $llms_first_val First value to compare.
		 * @param array $llms_second_val Second value to compare.
		 * @since 6.0.0
		 * @return bool
		 */
		public function loginpress_sort_by_priority( $llms_first_val, $llms_second_val ) {
			return $llms_first_val['priority'] === $llms_second_val['priority'] ? 1 : $llms_second_val['priority'] <=> $llms_first_val['priority'];
		}

		/**
		 * Retrieves a list of posts based on provided parameters
		 *
		 * @param array  $post_type   Array of post types to retrieve.
		 * @param string $post_status Post status to filter by.
		 * @param string $fields      Fields to retrieve.
		 * @param int    $numberposts    Number of posts to retrieve.
		 *
		 * @return array Array of posts
		 * @since 6.0.0
		 */
		public function loginpress_get_posts_data( $post_type = array( 'posts' ), $post_status = 'publish', $fields = 'ids,post_name', $numberposts = -1 ) {

			return get_posts(
				array(
					'post_type'   => $post_type,
					'post_status' => $post_status,
					'fields'      => $fields,
					'numberposts' => $numberposts,
				)
			);
		}

		/**
		 * Retrieves an array of posts data with login/logout redirects and their priorities
		 *
		 * @param array  $posts_data Array of posts to retrieve redirects for.
		 * @param string $action login/logout action.
		 * @param int    $user_id User ID to check for enrollment.
		 * @param string $redirect_to URL to fall back to if no redirects are found.
		 *
		 * @return array Array of post related redirects and priorities
		 * @since 6.0.0
		 */
		public function loginpress_llms_login_logout_data( $posts_data, $action, $user_id = null, $redirect_to = '/' ) {

			$llms_redirects_url = get_option( 'loginpress_redirects_llms' );
			$post_data          = array();
			foreach ( $posts_data as $post ) {
				$post_name    = $post->post_name;
				$post_id      = $post->ID;
				$user_in_post = $this->loginpress_is_user_llms_enrolled( $user_id, $post_id );
				if ( isset( $llms_redirects_url[ $post_name ] ) && $user_in_post ) {
					$post_data[ $post_name ][ $action ]  = isset( $llms_redirects_url[ $post_name ][ $action ] ) && ! empty( $llms_redirects_url[ $post_name ][ $action ] ) ? $llms_redirects_url[ $post_name ][ $action ] : $redirect_to;
					$post_data[ $post_name ]['priority'] = $llms_redirects_url[ $post_name ]['priority'];
				}
			}

			return $post_data;
		}

		/**
		 * Checks if a user is enrolled in a LLMS course or membership.
		 *
		 * @since 6.0.0
		 *
		 * @param int $user_id    WP User ID of the user.
		 * @param int $product_id WP Post ID of a Course or Membership.
		 *
		 * @return bool
		 */
		public function loginpress_is_user_llms_enrolled( $user_id, $product_id ) {

			// Create an LLMS_Student object for the user.
			$student = new LLMS_Student( $user_id );
			return $student->is_enrolled( $product_id, 'all' );
		}
	}
}
