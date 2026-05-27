<?php

namespace uncanny_learndash_reporting;

use stdClass;
use WP_REST_Request;
use uncanny_learndash_reporting\Cache;

/**
 *
 */
class CourseData extends BuildReportData {

	/**
	 * @var array
	 */
	public static $group_leader_groups = array();

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return mixed|null
	 */
	public static function get_users_overview( WP_REST_Request $request ) {

		return Config::profile_function(array(__CLASS__, 'get_courses_overview'), $request);
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return mixed|null
	 */
	public static function get_courses_overview( WP_REST_Request $request ) {

		$json_return = array();

		$json_return['learnDashLabels'] = MiscFunctions::get_labels();
		$json_return['links']           = MiscFunctions::get_links();

		$json_return['message'] = '';
		$json_return['success'] = true;
		$json_return['data']    = Config::profile_function(array(__CLASS__, 'course_progress_data'));

		return apply_filters( 'tc_api_get_courses_overview', $json_return );
	}

	/**
	 * Collect general user course data and LearnDash Labels
	 *
	 * @return array|mixed
	 */
	public static function course_progress_data( ) {

		return array(
			'userList'   => Config::profile_function(array(__CLASS__, 'get_courses_overview_data'), 'both'),
			'courseList' => Config::profile_function(array(__CLASS__, 'get_course_list')),
			'success'    => true,
		);
	}

	/**
	 * @param $type
	 *
	 * @return array|mixed|null
	 */
	public static function get_courses_overview_data( $type = 'both' ) {

		$cache = Cache::get( __FUNCTION__ );

		if ( ! empty( $cache ) ) {
			return apply_filters( 'uo_get_courses_overview_data', $cache, $type );
		}

		// Build the dashboard data
		self::get_dashboard_data();

		$return['users_overview'] = self::$all_user_data_rearranged;
		$return['completions']    = self::$completions_rearranged;
		$return['in_progress']    = self::$in_progress_rearranged;
		$return['course_access_list']   = self::$course_access_list;
		$return['course_quiz_averages'] = self::$course_quiz_average;
		$return['course_completion_by_course'] = self::$course_completion_by_course;
		$return['dashboard_data']              = self::$dashboard_data_object;

		Cache::create( __FUNCTION__, $return );

		/**
		 * Filters the course overview data
		 */
		return apply_filters( 'uo_get_courses_overview_data', $return, $type );
	}

	/**
	 * @param $leader_id
	 * @param $has_admin_access
	 *
	 * @return array
	 */
	public static function get_groups_list( $leader_id, $has_admin_access = false ) {
		$flag = false === $has_admin_access ? 0 : 1;
		$cache = Cache::get( __FUNCTION__ . $leader_id . $flag );

		if ( ! empty( $cache ) ) {
			return $cache;
		}

		$rearrange_group_list = array();
		// Admin group lists
		if ( 0 === self::$isolated_group_id && ( $has_admin_access || learndash_is_group_leader_user( $leader_id ) ) ) {
			$groups = self::learndash_get_administrators_group_ids( $leader_id, $has_admin_access );
			if ( ! isset( self::$group_leader_groups[ $leader_id ] ) ) {
				self::$group_leader_groups[ $leader_id ] = $groups;
			}
			if ( $groups ) {
				foreach ( $groups as $group_id ) {
					$rearrange_group_list[ $group_id ]['ID']                   = $group_id;
					$rearrange_group_list[ $group_id ]['post_title']           = get_the_title( $group_id );
					$rearrange_group_list[ $group_id ]['groups_course_access'] = learndash_group_enrolled_courses( $group_id );
					$rearrange_group_list[ $group_id ]['groups_user']          = learndash_get_groups_user_ids( $group_id );
				}
			}
		} elseif ( 0 !== self::$isolated_group_id && ( $has_admin_access || learndash_is_group_leader_user( $leader_id ) ) ) {
			// Specific group
			if ( ! isset( self::$group_leader_groups[ $leader_id ] ) ) {
				self::$group_leader_groups[ $leader_id ] = self::$isolated_group_id;
			}
			$group_id = self::$isolated_group_id;

			$rearrange_group_list[ $group_id ]['ID']                   = $group_id;
			$rearrange_group_list[ $group_id ]['post_title']           = get_the_title( $group_id );
			$rearrange_group_list[ $group_id ]['groups_course_access'] = learndash_group_enrolled_courses( $group_id );
			$rearrange_group_list[ $group_id ]['groups_user']          = learndash_get_groups_user_ids( $group_id );
		}

		Cache::create( __FUNCTION__ . $leader_id . $flag, $rearrange_group_list );

		return $rearrange_group_list;
	}

	/**
	 * LearnDash get administrators group IDs
	 *
	 * @param $leader_id
	 * @param bool $has_admin_access - Allows for custom role checks
	 *
	 * @return array
	 */
	public static function learndash_get_administrators_group_ids( $leader_id, $has_admin_access = false) {

		// Return all groups if admin user.
		if ( $has_admin_access || learndash_is_admin_user( $leader_id ) ) {
			$group_ids = get_posts(
				array(
					'post_type'      => learndash_get_post_type_slug( 'group' ),
					'posts_per_page' => 99999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
				'fields'             => 'ids',
				)
			);
			return ! empty( $group_ids ) ? $group_ids : array();
		}

		// Query for group IDs by leaders meta key.
		global $wpdb;
		$group_ids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM $wpdb->usermeta WHERE meta_key LIKE %s AND user_id = %d", 'learndash_group_leaders_%%', $leader_id ) );
		$group_ids = ! empty( $group_ids ) ? array_unique( $group_ids, SORT_NUMERIC ) : array();

		// If hierarchical groups are enabled, get all child groups.
		if ( learndash_is_groups_hierarchical_enabled() ) {
			$all_children = self::get_all_learndash_group_child_posts_ids();
			if ( ! empty( $all_children ) ) {
				foreach ( $group_ids as $group_id ) {
					$children_ids = self::learndash_get_group_children_ids( $group_id, $all_children );
					if ( ! empty( $children_ids ) ) {
						$group_ids = array_merge( $group_ids, $children_ids );
					}
				}
				$group_ids = array_unique( $group_ids, SORT_NUMERIC );
			}
		}

		return $group_ids;
	}

	/**
	 * Return all child groups
	 *
	 * @return array - array of child group objects with ID and post_parent
	 */
	public static function get_all_learndash_group_child_posts_ids() {

		static $all_children = null;
		if ( is_null( $all_children ) ) {
			$children_query = new \WP_Query(
				array(
					'post_type'           => learndash_get_post_type_slug( 'group' ),
					'post_parent__not_in' => array( 0 ), // Only retrieve child posts
					'posts_per_page'      => 99999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
				'fields'                  => 'id=>parent', // Retrieve only ID and post_parent
				)
			);
			$all_children   = $children_query->have_posts() ? $children_query->posts : array();
			wp_reset_postdata();
		}

		return $all_children;
	}

	/**
	 * LearnDash get group children IDs
	 *
	 * @param $group_id
	 *
	 * @return array
	 */
	public static function learndash_get_group_children_ids( $group_id, $all_children = null ) {

		$group_id = absint( $group_id );
		if ( empty( $group_id ) ) {
			return array();
		}

		if ( is_null( $all_children ) ) {
			$all_children = self::get_all_learndash_group_child_posts_ids();
		}

		if ( empty( $all_children ) ) {
			return array();
		}

		$children = array();
		foreach ( $all_children as $child ) {
			if ( $child->post_parent === $group_id ) {
				$children[] = $child->ID;
				$children2  = self::learndash_get_group_children_ids( $child->ID, $all_children );
				if ( ! empty( $children2 ) ) {
					$children = array_merge( $children, $children2 );
				}
			}
		}

		return ! empty( $children ) ? array_unique( $children, SORT_NUMERIC ) : array();
	}
}
