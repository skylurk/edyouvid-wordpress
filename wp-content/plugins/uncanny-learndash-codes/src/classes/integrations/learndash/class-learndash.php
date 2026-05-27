<?php

namespace uncanny_learndash_codes;

/**
 * Class LearnDash
 * @package uncanny_learndash_codes
 */
class LearnDash extends Config {
	/**
	 * LearnDash constructor.
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * @return array|null|object
	 */
	public static function get_groups() {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type=%s AND post_status=%s", 'groups', 'publish' ) );
	}

	/**
	 * @return array|null|object
	 */
	public static function get_courses() {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type=%s AND post_status=%s", 'sfwd-courses', 'publish' ) );
	}

	/**
	 * @return array|null|object
	 */
	public static function get_lessons() {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type=%s AND post_status=%s", 'sfwd-lessons', 'publish' ) );
	}

	/**
	 * @param $user_id
	 * @param $result
	 */
	public static function set_user_to_course_or_group( $user_id, $result ) {
		if ( ! is_array( $result ) ) {
			return;
		}

		if ( ! function_exists( 'ld_update_group_access' ) || ! function_exists( 'ld_update_course_access' ) || ! function_exists( 'learndash_get_users_group_ids' ) ) {
			return;
		}

		if ( 'group' === (string) $result['for'] ) {
			$user_groups = learndash_get_users_group_ids( $user_id );
			// remove all groups if any.
			if ( 0 === intval( Config::$allow_multiple_groups ) ) {
				if ( $user_groups && ! empty( $user_groups ) ) {
					foreach ( $user_groups as $user_group ) {
						ld_update_group_access( $user_id, $user_group, true );
					}
				}
				foreach ( $result['data'] as $d ) {
					ld_update_group_access( $user_id, $d );
					$transient_key         = 'learndash_user_groups_' . $user_id;
					$transient_key_courses = 'learndash_user_courses_' . $user_id;
					delete_transient( $transient_key );
					delete_transient( $transient_key_courses );
					break; // To only assign first group!
				}
			} elseif ( 1 === intval( Config::$allow_multiple_groups ) ) {
				foreach ( $result['data'] as $d ) {
					ld_update_group_access( $user_id, $d );
					$transient_key         = 'learndash_user_groups_' . $user_id;
					$transient_key_courses = 'learndash_user_courses_' . $user_id;
					delete_transient( $transient_key );
					delete_transient( $transient_key_courses );
				}
			}
		} elseif ( 'course' === (string) $result['for'] ) {
			foreach ( $result['data'] as $course_id ) {
				ld_update_course_access( $user_id, $course_id );
			}
		}

	}

	/**
	 * @param $user_id
	 */
	public static function remove_all_access( $user_id ) {
		$user_groups = learndash_get_users_group_ids( $user_id );
		if ( $user_groups && ! empty( $user_groups ) ) {
			foreach ( $user_groups as $user_group ) {
				ld_update_group_access( $user_id, $user_group, true );
				$transient_key         = 'learndash_user_groups_' . $user_id;
				$transient_key_courses = 'learndash_user_courses_' . $user_id;
				delete_transient( $transient_key );
				delete_transient( $transient_key_courses );
			}
		}
	}
}
