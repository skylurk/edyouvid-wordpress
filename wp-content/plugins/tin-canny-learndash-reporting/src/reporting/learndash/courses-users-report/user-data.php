<?php

namespace uncanny_learndash_reporting;

/**
 *
 */
class UserData {

	/**
	 * @var null
	 */
	public static $user_role = null;
	// Extracted methods related to user data management

	/**
	 * @var null
	 */
	public static $group_leaders_group_ids = null;


	/**
	 * @return array|null
	 */
	public static function get_administrators_group_ids() {

		if ( ! self::$group_leaders_group_ids ) {
			self::$group_leaders_group_ids = learndash_get_administrators_group_ids( get_current_user_id() );
		}

		return self::$group_leaders_group_ids;
	}

	/**
	 * @return string|null
	 */
	public static function get_user_role() {
		if ( ! self::$user_role ) {

			// Default value
			self::$user_role = 'unknown';

			// is it an administrator
			if ( uotc_is_current_user_admin() ) {
				self::$user_role = 'administrator';
			} elseif ( learndash_is_group_leader_user( get_current_user_id() ) ) {
				// Is it a group leader
				self::$user_role = 'group_leader';
			}
		}

		return self::$user_role;
	}

	/**
	 * @return array
	 */
	public static function get_user_avatar() {
		$response = array(
			'message' => '',
			'success' => false,
			'data'    => array(),
		);

		// Check if the user id is defined.
		$user_id = absint( ultc_get_filter_var( 'user_id', 0, INPUT_POST ) );
		if ( empty( $user_id ) ) {
			$response['message']    = __( 'Invalid user ID', 'uncanny-learndash-reporting' );
			$response['error_code'] = 1;

			return $response;
		}

		// Get avatar
		$avatar_url = get_avatar_url( $user_id );

		// Check if it has a valid value
		if ( false !== $avatar_url ) {
			// It's valid, save it
			$response['data']['avatar'] = $avatar_url;
			// and change "success" value
			$response['success'] = true;
		} else {
			$response['message']    = __( "We couldn't find an avatar.", 'uncanny-learndash-reporting' );
			$response['error_code'] = 2;
		}

		return $response;
	}
}
