<?php

namespace uncanny_learndash_reporting;

use uncanny_learndash_reporting\Config;
use uncanny_learndash_reporting\Cache;
use uncanny_learndash_reporting\tincanny_reporting\Database;

/**
 *
 */
class BuildReportData {

	/**
	 * @var array|mixed|null
	 */
	public static $excluded_roles = array();
	/**
	 * @var array|mixed|null
	 */
	public static $excluded_user_ids = array();
	/**
	 * @var int
	 */
	public static $leader_id = 0;
	/**
	 * @var array
	 */
	public static $leader_groups = array();
	/**
	 * @var array
	 */
	public static $course_list = array();
	/**
	 * @var array
	 */
	public static $groups_list = array();
	/**
	 * @var array
	 */
	public static $course_access_list = array();
	/**
	 * @var array
	 */
	public static $all_user_ids = array();
	/**
	 * @var array
	 */
	public static $dashboard_data_object = array();
	/**
	 * @var array
	 */
	public static $course_access_count = array();
	/**
	 * @var array
	 */
	public static $user_data = array();
	/**
	 * @var array
	 */
	public static $all_user_data_rearranged = array();
	/**
	 * @var array
	 */
	public static $filtered_user_ids = array();
	/**
	 * @var array
	 */
	public static $all_user_ids_rearranged = array();

	/**
	 * @var array
	 */
	public static $completions_by_date = array();
	/**
	 * @var array
	 */
	public static $completions_by_course = array();
	/**
	 * @var int
	 */
	public static $isolated_group_id = 0;
	/**
	 * @var array
	 */
	public static $completions_rearranged = array();
	/**
	 * @var array
	 */
	public static $in_progress_rearranged = array();
	/**
	 * @var array
	 */
	public static $course_quiz_average = array();
	/**
	 * @var array
	 */
	public static $course_completion_by_course = array();
	/**
	 * @var array
	 */
	public static $injected_user_ids = array();

	/**
	 *
	 */
	public function __construct() {
		if ( ultc_filter_has_var( 'group_id' ) ) {
			self::$isolated_group_id = absint( ultc_filter_input( 'group_id' ) );
		}
	}

	/**
	 * Get data for the admin dashboard page
	 *
	 * @return array
	 */
	public static function get_dashboard_data() {
		return Config::profile_function([__CLASS__, 'get_dashboard_data_func']);
	}

	/**
	 * @return array[]|mixed|null
	 */
	public static function get_dashboard_data_func() {

		// Truncate the reporting_api_user_id table
		Database::truncate_reporting_api_user_id_table();

		self::$leader_id = wp_get_current_user()->ID;

		// Ensure filters are applied at the right time
		self::$excluded_roles    = apply_filters( 'uo_tincanny_reporting_exclude_roles', array() );
		self::$excluded_user_ids = apply_filters( 'uo_tincanny_reporting_exclude_user_ids', array() );

		// Get list of all courses
		self::$course_list = Config::profile_function([ __CLASS__, 'get_course_list' ] );

		// Get lists of all groups
		self::$groups_list   = Config::profile_function([  '\uncanny_learndash_reporting\CourseData', 'get_groups_list' ], self::$leader_id, uotc_is_user_admin( self::$leader_id ) );
		self::$leader_groups = isset( CourseData::$group_leader_groups[ self::$leader_id ] ) ? CourseData::$group_leader_groups[ self::$leader_id ] : array();

		if ( self::$groups_list && is_array(self::$groups_list) && learndash_is_group_leader_user( wp_get_current_user() ) ) {
			// Get data from group data
			foreach ( self::$groups_list as $group_id => $group ) {

				if ( isset( $group['groups_user'] ) && isset( $group['groups_course_access'] ) ) {

					foreach ( $group['groups_user'] as $user_id ) {
						if ( 0 !== self::$isolated_group_id ) {
							if ( $group_id == self::$isolated_group_id ) {
								self::$all_user_ids[ (int) $user_id ] = (int) $user_id;
							}
						} else {
							self::$all_user_ids[ (int) $user_id ] = (int) $user_id;
						}
					}
				}
			}
		} else {
			self::$all_user_ids = get_users(
				array(
					'role__not_in' => self::$excluded_roles,
					'exclude'      => self::$excluded_user_ids,
					'fields'       => 'ID',
					'blog_id'      => get_current_blog_id(),
					'cache_results' => false,
				)
			);
		}

		// For admins only
		if ( 0 === CourseData::$isolated_group_id ) {
			if ( apply_filters( 'tincanny_view_all_reports_permission', uotc_is_current_user_admin() ) ) {
				// For WP-Admin Dashboard Page
				Config::profile_function( [ __CLASS__, 'get_data_for_dashboard' ] );
			} elseif ( learndash_is_group_leader_user( wp_get_current_user() ) ) {
				if ( empty( self::$leader_groups ) ) {
					return array(
						'total_users'              => 0,
						'total_courses'            => 0,
						'top_course_completions'   => array(),
						'courses_tincan_completed' => array(),
					);
				}

				Config::profile_function( [ __CLASS__, 'get_courses_for_all_groups' ] );
			}
		} elseif ( 0 !== CourseData::$isolated_group_id && ( uotc_is_current_user_admin() || learndash_is_group_leader_user( wp_get_current_user() ) ) ) {
			self::$all_user_ids = Config::profile_function([ __CLASS__, 'get_group_users' ], CourseData::$isolated_group_id );
			$group_courses      = Config::profile_function([__CLASS__, 'get_group_courses'], CourseData::$isolated_group_id );

			if ( ! empty( $group_courses ) && ! empty( self::$all_user_ids ) ) {
				foreach ( $group_courses as $course_id ) {
					foreach ( self::$all_user_ids as $user_id ) {
						self::$course_access_list[ $course_id ][ (int) $user_id ] = (int) $user_id;
					}
				}
			}
		}

		// eliminating duplicates
		self::$all_user_ids = array_unique( self::$all_user_ids );

		if ( empty( self::$all_user_ids ) ) {
			return array(
				'total_users'              => 0,
				'total_courses'            => 0,
				'top_course_completions'   => array(),
				'courses_tincan_completed' => array(),
			);
		}

		// Exclude IDs sent by the filter
		Config::profile_function( [ __CLASS__, 'exclude_user_ids' ] );

		// Store user IDs in the DB for future queries
		self::$injected_user_ids = self::inject_group_leader_id();

		Database::insert_in_tbl_reporting_api_user_ids( self::$injected_user_ids );

		do_action( 'tincanny_reporting_before_courses_overview_report' );

		Config::profile_function( [ __CLASS__, 'get_all_user_data_rearranged' ] );
		Config::profile_function( [ __CLASS__, 'filter_user_ids' ] );

		foreach ( self::$all_user_ids as $user_id ) {
			if ( ! empty( self::$filtered_user_ids ) && in_array( $user_id, self::$filtered_user_ids, true ) ) {
				continue;
			}
			self::$all_user_ids_rearranged[ $user_id ] = $user_id;
		}

		self::$dashboard_data_object['total_users'] = count( self::$all_user_ids_rearranged );

		// Completion
		Config::profile_function( [ __CLASS__, 'get_top_course_completions_func' ] );
		Config::profile_function( [ __CLASS__, 'get_tincan_completions' ] );

		// unset to avoid memory leak
		self::$all_user_ids = array();
		self::$user_data = array();
		self::$groups_list = array();

		/**
		 * Filters the course overview data
		 */
		return apply_filters( 'uo_get_dashboard_data', self::$dashboard_data_object );
	}

	/**
	 * Inject group leader id
	 *
	 * @param $user_ids
	 * @param $current_user_id
	 *
	 * @return array
	 */
	public static function inject_group_leader_id() {
		$updated_ids = array();
		foreach ( self::$all_user_ids as $user_id ) {
			$updated_ids[] = array(
				$user_id,
				self::$leader_id,
			);
		}

		return $updated_ids;
	}

	/**
	 * @param $group_id
	 *
	 * @return void
	 */
	public static function get_data_for_dashboard() {

		foreach ( self::$course_list as $course_id => $course ) {

			if ( ! isset( self::$course_access_list[ $course_id ] ) ) {
				self::$course_access_list[ $course_id ] = array();
			}
			// Course access
			if ( ! empty( $course->course_user_access_list ) ) {
				foreach ( $course->course_user_access_list as $user_id ) {
					self::$course_access_list[ $course_id ][ (int) $user_id ] = (int) $user_id;
				}
			}

			//Group access
			if ( ! empty( self::$leader_groups ) ) {
				foreach ( self::$leader_groups as $group_id ) {
					$_group_courses = self::get_group_courses( $group_id );
					//Config::log( $_group_courses, '$_group_courses', true, 'dashboard_data' );
					if ( in_array( $course_id, $_group_courses, true ) ) {
						$_group_users = self::get_group_users( $group_id );
						//Config::log( $_group_users, '$_group_users', true, 'dashboard_data' );
						if ( ! empty( $_group_users ) ) {
							foreach ( $_group_users as $group_user_id ) {
								self::$course_access_list[ $course_id ][ (int) $group_user_id ] = (int) $group_user_id;
							}
						}
					}
				}
			}
		}
	}

	/**
	 * @param $group_id
	 *
	 * @return array
	 */
	public static function get_group_courses( $group_id ) {
		return isset( self::$groups_list[ $group_id ]['groups_course_access'] ) ? array_map( 'absint', self::$groups_list[ $group_id ]['groups_course_access'] ) : array();
	}

	/**
	 * @param $group_id
	 *
	 * @return array
	 */
	public static function get_group_users( $group_id ) {
		return isset( self::$groups_list[ $group_id ]['groups_user'] ) ? array_map( 'absint', self::$groups_list[ $group_id ]['groups_user'] ) : array();
	}

	/**
	 * @return void
	 */
	public static function get_courses_for_all_groups() {

		// Get data from group data
		foreach ( self::$leader_groups as $group_id ) {
			$group_users = self::get_group_users( $group_id );

			if ( ! empty( $group_users ) ) {
				self::$all_user_ids = array_merge( self::$all_user_ids, $group_users );
			}

			$group_courses = self::get_group_courses( $group_id );

			if ( ! empty( $group_courses ) ) {
				foreach ( $group_courses as $course_id ) {
					foreach ( $group_users as $user_id ) {
						self::$course_access_list[ $course_id ][ (int) $user_id ] = (int) $user_id;
					}
				}
			}
		}
	}

	/**
	 * @return void
	 */
	public static function exclude_user_ids() {
		if ( ! empty( self::$excluded_user_ids ) ) {
			self::$excluded_user_ids = array_map( 'absint', self::$excluded_user_ids );
			foreach ( self::$all_user_ids as $k => $user_id ) {
				$user_id = (int) $user_id;
				if ( in_array( $user_id, self::$excluded_user_ids, true ) ) {
					unset( self::$all_user_ids[ $k ] );
				}
			}
		}
	}

	/**
	 * @return void
	 */
	public static function get_all_user_data_rearranged() {
		$cache = Cache::get( __FUNCTION__ );

		if ( ! empty( $cache ) ) {
			self::$all_user_data_rearranged = $cache;

			return $cache;
		}

		$all_user_data_rearranged = array();
		// Get user data
		self::$user_data = Config::profile_function([  '\uncanny_learndash_reporting\tincanny_reporting\Database', 'get_user_data' ], self::$leader_id );

		foreach ( self::$user_data as $user ) {
			$user->enrolled    = 0;
			$user->in_progress = 0;
			$user->completed   = 0;
			$roles             = maybe_unserialize( $user->user_roles );
			$user->roles       = is_array( $roles ) ? array_keys( $roles ) : array();

			unset( $user->user_roles );

			// If the role is excluded, no need to add user data
			if ( is_array( $user->roles ) && is_array( self::$excluded_roles ) && array_intersect( $user->roles, self::$excluded_roles ) ) {
				self::$filtered_user_ids[] = $user->ID;
				continue;
			}

			$all_user_data_rearranged[ (int) $user->ID ] = $user;
		}

		Cache::create( __FUNCTION__, $all_user_data_rearranged );

		self::$all_user_data_rearranged = $all_user_data_rearranged;
	}

	/**
	 * @return void
	 */
	public static function filter_user_ids() {
		if ( ! empty( self::$filtered_user_ids ) ) {
			self::$filtered_user_ids = array_map( 'absint', self::$filtered_user_ids );
			Database::remove_user_id_by_role_exclusion( self::$filtered_user_ids, self::$leader_id );
		}
	}

	/**
	 * @return void
	 */
	public static function get_top_course_completions() {
		$cache = Cache::get( __FUNCTION__ );

		if ( ! empty( $cache ) ) {
			self::$completions_rearranged = $cache;

			return $cache;
		}

		$course_users = Config::profile_function([ __CLASS__, 'get_course_users' ] );

		$completions = Config::profile_function(['\uncanny_learndash_reporting\tincanny_reporting\Database', 'get_completions'], self::$leader_id );

		$completions_rearranged = array();

		if ( ! empty( $completions ) ) {
			foreach ( $completions as $completion ) {
				if ( ! isset( self::$all_user_data_rearranged[ (int) $completion->user_id ] ) ) {
					continue;
				}

				if ( ! isset( self::$course_access_list[ $completion->course_id ] ) ) {
					continue;
				}

				if ( ! isset( self::$all_user_ids_rearranged[ (int) $completion->user_id ] ) ) {
					continue;
				}

				$course_price_type = self::$course_list[ $completion->course_id ]->course_price_type;

				if ( isset( self::$course_access_list[ $completion->course_id ][ $completion->user_id ] ) || 'open' === $course_price_type ) {

					$completed_on_date = date( 'Y-m-d', $completion->activity_completed ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

					if ( ! isset( self::$completions_by_course[ $completion->course_id ] ) ) {
						self::$completions_by_course[ $completion->course_id ] = array();
					}

					self::$all_user_data_rearranged[ (int) $completion->user_id ]->completed ++;

					self::$all_user_data_rearranged[ (int) $completion->user_id ]->completed_on[ $completion->course_id ] = array(
						'display'   => learndash_adjust_date_time_display( $completion->activity_completed ),
						'timestamp' => (string) $completion->activity_completed,
					);

					if ( ! isset( self::$completions_by_course[ $completion->course_id ][ $completed_on_date ] ) ) {
						self::$completions_by_course[ $completion->course_id ][ $completed_on_date ] = array( $completion->user_id );
					} else {
						self::$completions_by_course[ $completion->course_id ][ $completed_on_date ][] = $completion->user_id;
					}

					if ( ! isset( self::$completions_by_date[ $completed_on_date ] ) ) {
						self::$completions_by_date[ $completed_on_date ] = 1;
					} else {
						self::$completions_by_date[ $completed_on_date ] ++;
					}

					if ( ! isset( self::$dashboard_data_object['top_course_completions'][ $completion->course_id ] ) ) {
						$completions_rearranged[ $completion->course_id ]                                = 1;
						self::$dashboard_data_object['top_course_completions'][ $completion->course_id ] = array(
							'post_title'              => self::$course_list[ $completion->course_id ]->post_title,
							'course_price_type'       => self::$course_list[ $completion->course_id ]->course_price_type,
							'course_user_access_list' => ( 'open' === $course_price_type ) ? self::$all_user_ids_rearranged : $course_users[ $completion->course_id ],
							'completions'             => 1,
						);
					} else {
						$completions_rearranged[ $completion->course_id ] ++;
						self::$dashboard_data_object['top_course_completions'][ $completion->course_id ]['completions'] ++;
					}
				} else {
					if ( ! isset( self::$dashboard_data_object['top_course_completions'][ $completion->course_id ] ) ) {
						$completions_rearranged[ $completion->course_id ]                                = 0;
						self::$dashboard_data_object['top_course_completions'][ $completion->course_id ] = array(
							'post_title'              => self::$course_list[ $completion->course_id ]->post_title,
							'course_price_type'       => self::$course_list[ $completion->course_id ]->course_price_type,
							'course_user_access_list' => ( 'open' === $course_price_type ) ? self::$all_user_ids_rearranged : $course_users[ $completion->course_id ],
							'completions'             => 0,
						);
					}
				}
			}
		}

		Cache::create( __FUNCTION__, $completions_rearranged );

		self::$completions_rearranged = $completions_rearranged;
	}

	/**
	 * @return void
	 */
	public static function get_top_course_completions_func() {

		Config::profile_function( [ __CLASS__, 'get_top_course_completions' ] );

		Config::profile_function( [ __CLASS__, 'add_remaining_top_course_completions' ] );

		Config::profile_function( [ __CLASS__, 'get_top_in_progress_completions' ] );

		Config::profile_function( [ __CLASS__, 'get_top_activities_completions' ] );

		self::$dashboard_data_object['total_courses'] = count( self::$course_access_list );

		Config::profile_function( [ __CLASS__, 'get_percentages_of_completions' ] );

		if( isset(self::$dashboard_data_object['top_course_completions']) ) {
			usort(
				self::$dashboard_data_object['top_course_completions'],
				function ( $a, $b ) {
					return $b['percentage'] - $a['percentage'];
				}
			);
		}
	}

	/**
	 * @return void
	 */
	public static function add_remaining_top_course_completions() {
		$course_users = Config::profile_function([ __CLASS__, 'get_course_users' ] );
		foreach ( self::$course_list as $course_id => $course ) {
			if ( ! isset( self::$dashboard_data_object['top_course_completions'][ $course_id ] ) ) {
				$course_price_type = $course->course_price_type ?? 'open';
				self::$dashboard_data_object['top_course_completions'][ $course_id ] = array(
					'post_title'              => $course->post_title,
					'course_price_type'       => $course_price_type,
					'course_user_access_list' => ( 'open' === $course_price_type ) ? self::$all_user_ids_rearranged : ( isset( $course_users[ $course_id ] ) ? $course_users[ $course_id ] : array() ),
					'completions'             => 0,
				);
			}
		}
	}

	/**
	 * @return void
	 */
	public static function get_top_in_progress_completions(){
		$cache = Cache::get( __FUNCTION__ );

		if ( ! empty( $cache ) ) {
			self::$in_progress_rearranged = $cache;

			return $cache;
		}

		global $wpdb;
		// In-progress
		$tbl_reporting_api_user = $wpdb->prefix . Database::$tbl_reporting_api_user_id;

		$qry = 			$wpdb->prepare(
			"SELECT a.post_id as course_id, a.user_id
				FROM {$wpdb->prefix}learndash_user_activity a
				JOIN {$tbl_reporting_api_user} t ON t.user_id = a.user_id AND t.group_leader_id = %d
				WHERE a.activity_type = 'course'
				AND ( a.activity_completed = 0 OR a.activity_completed IS NULL )
				AND ( a.activity_started != 0 OR a.activity_updated != 0 )",
			self::$leader_id
		);

		$in_progress            = $wpdb->get_results( $qry );// phpcs:enable WordPress.DB.PreparedSQL

		$in_progress_rearranged = array();

		foreach ( $in_progress as $progress ) {
			if ( ! isset( self::$all_user_data_rearranged[ (int) $progress->user_id ] ) ) {
				continue;
			}
			if (
				isset( self::$course_access_list[ $progress->course_id ] ) &&
				isset( self::$course_access_list[ $progress->course_id ][ (int) $progress->user_id ] )
				||
				(
					isset( self::$course_list[ $progress->course_id ] ) &&
					'open' === self::$course_list[ $progress->course_id ]->course_price_type &&
					isset( self::$all_user_ids_rearranged[ (int) $progress->user_id ] )
				)
			) {

				if ( ! isset( $in_progress_rearranged[ $progress->course_id ] ) ) {
					$in_progress_rearranged[ $progress->course_id ] = 1;
				} else {
					$in_progress_rearranged[ $progress->course_id ] ++;
				}

				if ( isset( self::$all_user_data_rearranged[ (int) $progress->user_id ] ) ) {
					self::$all_user_data_rearranged[ (int) $progress->user_id ]->in_progress ++;
				}
			}
		}

		Cache::create( __FUNCTION__, $in_progress_rearranged );

		self::$in_progress_rearranged = $in_progress_rearranged;

		unset( $in_progress );
		unset( $course_list );
	}

	/**
	 * @return void
	 */
	public static function get_top_activities_completions() {

	    // Try cache first
	    $cache = Cache::get(__FUNCTION__);
	    if (is_array($cache) && ! empty($cache)) {
	        self::$course_quiz_average = $cache;
	        return;
	    }

	    global $wpdb;
	    $tbl_reporting_api_user = $wpdb->prefix . Database::$tbl_reporting_api_user_id;
	    $tbl_activity           = \LDLMS_DB::get_table_name('user_activity');
	    $tbl_activity_meta      = \LDLMS_DB::get_table_name('user_activity_meta');

	    if (empty(self::$course_access_list)) {
	        self::$course_quiz_average = [];
	        Cache::create(__FUNCTION__, []);
	        return;
	    }

	    $course_ids   = array_keys(self::$course_access_list);
	    $placeholders = implode(',', array_fill(0, count($course_ids), '%d'));
	    $leader_id    = self::$leader_id;

	    /**
	     * Step 1: Get all allowed user IDs for this leader
	     */
	    $allowed_users = $wpdb->get_col(
	        $wpdb->prepare(
	            "SELECT user_id FROM {$tbl_reporting_api_user} WHERE group_leader_id = %d",
	            $leader_id
	        )
	    );

	    if (empty($allowed_users)) {
	        self::$course_quiz_average = [];
	        Cache::create(__FUNCTION__, []);
	        return;
	    }


	    $user_placeholders = implode(',', array_fill(0, count($allowed_users), '%d'));

	    /**
	     * Step 2: Get quiz activities for allowed users & allowed courses
	     */
	    $quiz_activities = $wpdb->get_results(
	        $wpdb->prepare(
	            "
	            SELECT activity_id, course_id, post_id, user_id
	            FROM {$tbl_activity}
	            WHERE activity_type = 'quiz'
	              AND course_id IN ($placeholders)
	              AND user_id IN ($user_placeholders)
	            ",
	            array_merge($course_ids, $allowed_users)
	        )
	    );

	    if (empty($quiz_activities)) {
	        self::$course_quiz_average = [];
	        Cache::create(__FUNCTION__, []);
	        return;
	    }

	    /**
	     * Step 3: Get percentages for these activity IDs
	     */
	    $activity_ids = wp_list_pluck($quiz_activities, 'activity_id');
	    $activity_placeholders = implode(',', array_fill(0, count($activity_ids), '%d'));

	    $activity_meta = $wpdb->get_results(
	        $wpdb->prepare(
	            "
	            SELECT activity_id, activity_meta_value AS percentage
	            FROM {$tbl_activity_meta}
	            WHERE activity_meta_key = 'percentage'
	              AND activity_id IN ($activity_placeholders)
	            ",
	            $activity_ids
	        ),
	        OBJECT_K
	    );

	    /**
	     * Step 4: Merge data in PHP
	     */
	    foreach ($quiz_activities as &$act) {
	        if (isset($activity_meta[$act->activity_id])) {
	            $act->activity_percentage = $activity_meta[$act->activity_id]->percentage;
	        } else {
	            $act->activity_percentage = null;
	        }
	    }
	    unset($act);

	    /**
	     * Step 5: Calculate averages per course
	     */
	    $course_quiz_average = [];
	    foreach ($course_ids as $course_id) {
	        $course_quiz_average[$course_id] = self::get_course_quiz_average(
	            $course_id,
	            $quiz_activities,
	            self::$all_user_ids_rearranged
	        );
	    }

	    self::$course_quiz_average = $course_quiz_average;

	    Cache::create(__FUNCTION__, $course_quiz_average, DAY_IN_SECONDS);
	}

	/**
	 * @param $course_id
	 * @param $user_activities
	 * @param $user_ids
	 *
	 * @return int|string
	 */
	public static function get_course_quiz_average( $course_id, $user_activities, $user_ids ) {
		$quiz_scores = array();

		foreach ( $user_activities as $activity ) {

			if ( isset( $user_ids[ (int) $activity->user_id ] ) && (int) $course_id === (int) $activity->course_id && $activity->activity_percentage !== null ) {

				$key = $activity->post_id . $activity->user_id;

				if ( ! isset( $quiz_scores[ $key ] ) || $quiz_scores[ $key ] < $activity->activity_percentage ) {
					$quiz_scores[ $key ] = $activity->activity_percentage;
				}

			}
		}

		if ( count( $quiz_scores ) > 0 ) {
			$average = absint( array_sum( $quiz_scores ) / count( $quiz_scores ) );
		} else {
			$average = 'false';
		}

		return $average;
	}

	/**
	 * @return array
	 */
	public static function get_course_users() {
		static $course_users = null;
		$cache = Cache::get(__FUNCTION__);

		// If cache exists, return it immediately.
		if ( ! empty( $cache ) ) {
			return $cache;
		}

		// If cache isn't working return static cache.
		if ( ! is_null( $course_users ) ) {
			return $course_users;
		}

		// Build the course users array.
		$course_users = array();

		foreach (self::$course_access_list as $course_id => $users) {
			// Calculate user count once and store it
			self::$course_access_count[$course_id] = count($users);

			// Get the course price type, default to 'open'
			$course_price_type = self::$course_list[(int)$course_id]->course_price_type ?? 'open';

			// If the course price type is 'open', increment 'enrolled' for all users once
			if ('open' === $course_price_type) {
				foreach (self::$all_user_data_rearranged as $user_id => $data) {
					self::$all_user_data_rearranged[(int)$user_id]->enrolled++;
				}
			}

			$course_users_temp = array();
			foreach ($users as $user_id => $user_id_) {
				// Check if the user is in the rearranged user IDs
				if (isset(self::$all_user_ids_rearranged[$user_id])) {
					$course_users_temp[$user_id] = $user_id;

					// If the course price type is not 'open', increment 'enrolled' for the user
					if ('open' !== $course_price_type && isset(self::$all_user_data_rearranged[(int)$user_id])) {
						self::$all_user_data_rearranged[(int)$user_id]->enrolled++;
					}
				}
			}
			$course_users[$course_id] = $course_users_temp;
		}

		Cache::create( __FUNCTION__, $course_users );

		return $course_users;
	}

	/**
	 * @return void
	 */
	public static function get_percentages_of_completions() {
		if( ! isset(self::$dashboard_data_object['top_course_completions'] ) || ! is_array(self::$dashboard_data_object['top_course_completions']) ) {
			return;
		}

		foreach ( self::$dashboard_data_object['top_course_completions'] as $i => $d ) {
			$user_count  = count( $d['course_user_access_list'] );
			$completions = $d['completions'];
			$percentage  = 0;

			if ( 0 !== $user_count ) {
				$percentage = number_format( floor( ( $completions / $user_count ) ) * 100, 0 );
			}

			self::$dashboard_data_object['top_course_completions'][ $i ]['percentage'] = $percentage;

			unset( self::$dashboard_data_object['top_course_completions'][ $i ]['course_user_access_list'] );
		}
	}

	/**
	 * @return void
	 */
	public static function get_tincan_completions() {

		$cache = Cache::get( __FUNCTION__ );

		if ( ! empty( $cache ) ) {
			self::$dashboard_data_object['courses_tincan_completed'] = $cache;
		}

		global $wpdb;
		$tbl_reporting_api_user = $wpdb->prefix . Database::$tbl_reporting_api_user_id;

		$completions                = array();

		// min max date
		foreach ( self::$completions_by_date as $date => $amount_completions ) {
			$object              = new \stdClass();
			$object->date        = $date;
			$object->completions = $amount_completions;
			if ( $amount_completions > 0 ) {
				$completions[] = $object;
			}
		}

		$course_completion_by_course = array();
		foreach ( self::$completions_by_course as $completion_course_id => $data ) {
			$course_completion_by_course[ $completion_course_id ] = array();
			foreach ( $data as $date => $count ) {

				$object              = new \stdClass();
				$object->date        = $date;
				$object->completions = count( $count );
				if ( count( $count ) > 0 ) {
					$course_completion_by_course[ $completion_course_id ][] = $object;
				}
			}
		}

		self::$course_completion_by_course = $course_completion_by_course;

		//self::$completions_by_course = array();
		// phpcs:disable WordPress.DB.PreparedSQL
		$qry = $wpdb->prepare(
			"SELECT x.xstored, x.user_id, x.course_id
						FROM {$wpdb->prefix}uotincan_reporting x
						JOIN {$tbl_reporting_api_user} t
							ON t.user_id = x.user_id AND t.group_leader_id = %d
						WHERE x.xstored >= NOW() - INTERVAL 1 MONTH",
			self::$leader_id
		);
		$tin_can_completed = $wpdb->get_results( $qry );

		$temp_array = array();
		foreach ( $tin_can_completed as $completion ) {

			if ( 'group_leader' === UserData::get_user_role() || 0 !== CourseData::$isolated_group_id ) {
				if ( ! isset( self::$all_user_ids_rearranged[ $completion->user_id ] ) ) {
					continue;
				}
				if ( ! isset( self::$course_access_list[ $completion->course_id ] ) ) {
					continue;
				}
			}

			$date = date( 'Y-m-d', strtotime( $completion->xstored ) );// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			if ( ! isset( $temp_array[ $date ] ) ) {
				$temp_array[ $date ] = 1;
			} else {
				$temp_array[ $date ] ++;
			}
		}

		unset( $tin_can_completed );

		$tin_can_stored = array();
		foreach ( $temp_array as $date => $amount_completions ) {
			$object         = new \stdClass();
			$object->date   = $date;
			$object->tinCan = $amount_completions; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( $amount_completions > 0 ) {
				$tin_can_stored[] = $object;
			}
		}

		unset( $temp_array );

		$courses_tincan_completed = array_merge( $tin_can_stored, $completions );

		unset( $tin_can_stored );
		unset( $completions );

		usort(
			$courses_tincan_completed,
			function ( $a, $b ) {
				return strtotime( $a->date ) - strtotime( $b->date );
			}
		);

		Cache::create( __FUNCTION__, $courses_tincan_completed );

		// For Dashboard Graph
		self::$dashboard_data_object['courses_tincan_completed'] = $courses_tincan_completed;
	}

	/**
	 * @return array
	 */
	public static function get_course_list() {

		$cache = Cache::get( __FUNCTION__ );

		if ( ! empty( $cache ) && empty( self::$course_list) ) {
			self::$course_list = $cache;
		}

		if ( ! empty( self::$course_list ) ) {
			return self::$course_list;
		}

		global $wpdb;

		$groups_list                            = array();
		$group_courses                          = array();
		$restrict_group_leader_post             = '';
		$restrict_group_leader_postmeta         = '';
		$restrict_group_leader_associated_posts = '';

		if ( apply_filters( 'tincanny_view_all_reports_permission', uotc_is_current_user_admin() ) && 0 === self::$isolated_group_id ) {
			$course_list = get_posts(
				array(
					'post_type'      => 'sfwd-courses',
					'posts_per_page' => 99999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
					'fields'         => 'ids',
				)
			);

		} else {
			if ( 0 !== self::$isolated_group_id ) {
				$groups_list[] = self::$isolated_group_id;
			} else {
				$groups_list = UserData::get_administrators_group_ids();
			}
			if ( empty( $groups_list ) ) {
				return array();
			}
			foreach ( $groups_list as $group_id ) {
				$__courses = learndash_group_enrolled_courses( $group_id );
				if ( ! empty( $__courses ) ) {
					foreach ( $__courses as $__course_id ) {
						$group_courses[] = $__course_id;
					}
				}
			}
			$course_list = array_unique( $group_courses );
			unset( $group_courses );
		}

		$restrict_group_leader_post             = apply_filters_deprecated(
			'uo_tincanny_reporting_restrict_group_leader_post',
			array(
				$restrict_group_leader_post,
				0,
			),
			4.2,
			'uo_tincanny_reporting_list_of_course_ids'
		);
		$restrict_group_leader_postmeta         = apply_filters_deprecated(
			'uo_tincanny_reporting_restrict_group_leader_postmeta',
			array(
				$restrict_group_leader_postmeta,
				0,
			),
			4.2,
			'uo_tincanny_reporting_list_of_course_ids'
		);
		$restrict_group_leader_associated_posts = apply_filters_deprecated(
			'uo_tincanny_reporting_restrict_group_leader_associated_posts',
			array(
				$restrict_group_leader_associated_posts,
				0,
			),
			4.2,
			'uo_tincanny_reporting_list_of_course_ids'
		);

		$course_list            = apply_filters( 'uo_tincanny_reporting_list_of_course_ids', $course_list );
		$rearranged_course_list = array();
		$course_posts 			= array();
		if ( ! empty( $course_list ) ) {
			$course_posts = get_posts([
				'post_type'      => 'sfwd-courses',
				'post__in'       => $course_list,
				'posts_per_page' => 99999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
				'post_status'    => 'any', // include drafts, private, etc.
			]);

			// Ensure all courses exist in rearranged list
			foreach ( $course_list as $course_id ) {
				if ( ! isset( $rearranged_course_list[ $course_id ] ) ) {
					$rearranged_course_list[ $course_id ] = new \stdClass();
					$rearranged_course_list[ $course_id ]->ID = $course_id;
					$rearranged_course_list[ $course_id ]->post_title = '';
					$rearranged_course_list[ $course_id ]->post_name = '';
				}
			}
		}

		foreach ( $course_posts as $__course ) {
			$rearranged_course_list[ $__course->ID ]             = new \stdClass();
			$rearranged_course_list[ $__course->ID ]->ID         = $__course->ID;
			$rearranged_course_list[ $__course->ID ]->post_title = $__course->post_title;
			$rearranged_course_list[ $__course->ID ]->post_name  = $__course->post_name;
		}

		// Course settings
		if ( ! empty( $course_list ) ) {
			// Query 1
			$sql_string      = "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_sfwd-courses' AND post_id IN (" . join( ', ', $course_list ) . ')';
			$course_settings = $wpdb->get_results( $sql_string ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			$course_users_access_batch = self::course_users_access_batch( $course_list );
			foreach ( $course_settings as $course_setting ) {

				$course_id = (int) $course_setting->post_id;

				$courses_settings_values = maybe_unserialize( $course_setting->meta_value );

				if ( is_array( $courses_settings_values ) ) {

					$rearranged_course_list[ $course_id ]->course_user_access_list = $course_users_access_batch[$course_id] ?? [];

					foreach ( $courses_settings_values as $key => $value ) {
						if ( 'sfwd-courses_course_price_type' === $key ) {
							$rearranged_course_list[ $course_id ]->course_price_type = $value;
						}
					}
				}
				if ( isset( $rearranged_course_list[ $course_id ] ) && isset( $rearranged_course_list[ $course_id ]->course_user_access_list ) ) {
					$rearranged_course_list[ $course_id ]->enrolled_users = count( $rearranged_course_list[ $course_id ]->course_user_access_list );
				}
				// Default value set if course price type settings not found
				if ( ! isset( $rearranged_course_list[ $course_id ]->course_price_type ) ) {
					$rearranged_course_list[ $course_id ]->course_price_type = 'open';
				}
			}
		}
		// Course associated LearnDash Posts
		// Modify custom query to restrict data to group leaders available data
		$courses_posts = array();
		if ( ! empty( $course_list ) ) {
			$sql_string    = "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = 'course_id' OR meta_key LIKE 'ld_course_%' AND post_id IN (" . join( ', ', $course_list ) . ')';
			$courses_posts = $wpdb->get_results( $sql_string );// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			foreach ( $courses_posts as $course_post ) {

				$sub_post_id       = (int) $course_post->post_id;
				$associated_course = (int) $course_post->meta_value;

				if ( ! array_key_exists( $associated_course, $rearranged_course_list ) ) {
					continue;
				}

				// make sure that there is an associate course
				if ( 0 === $associated_course ) {
					continue;
				}
				if ( ! isset( $rearranged_course_list[ $associated_course ]->associatedPosts ) ) {
					$rearranged_course_list[ $associated_course ]->associatedPosts = array();
				}

				$rearranged_course_list[$associated_course]->associatedPosts[] = $sub_post_id;
			}
		}

		self::$course_list = $rearranged_course_list;

		Cache::create( __FUNCTION__, $rearranged_course_list );

		return $rearranged_course_list;
	}

	/**
	 * @param $course_id
	 *
	 * @return array
	 */
	public static function course_user_access( $course_id ) {
		$cache = Cache::get( __FUNCTION__ . $course_id );

		if ( ! empty( $cache ) ) {
			return $cache;
		}

		$users = learndash_get_course_users_access_from_meta( $course_id );
		if ( empty( $users ) ) {
			return array();
		}

		$data = array_map( 'absint', array_unique( $users ) );

		Cache::create( __FUNCTION__ . $course_id, $data );

		return $data;
	}

	protected static function course_users_access_batch( $course_ids ) {
	    global $wpdb;

	    if ( ! is_array( $course_ids ) ) {
	        return [];
	    }

	    // Make sure all course IDs are integers
	    $course_ids = array_map( 'absint', $course_ids );
	    if ( empty( $course_ids ) ) {
	        return [];
	    }

	    // Use a cache key based on all course IDs
	    $cache_key = __FUNCTION__ . '_' . md5( implode( ',', $course_ids ) );
	    $cache = Cache::get( $cache_key );
	    if ( ! empty( $cache ) ) {
	        return $cache;
	    }

		// Prepare meta_keys for all courses (PHP 5.6+ compatible)
		$meta_keys = array_map( function($id) {
			return 'course_' . $id . '_access_from';
		}, $course_ids );

		// Prepare placeholders for the IN clause
		$placeholders = implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) );

		$query = "SELECT meta_key, user_id FROM {$wpdb->usermeta} WHERE meta_key IN ($placeholders)";
		$prepared_query = $wpdb->prepare( $query, $meta_keys );
		$results = $wpdb->get_results( $prepared_query );

	    $course_users = [];

		// Ensure every course ID has an array even if no users exist
		foreach ( $course_ids as $id ) {
			$course_users[ $id ] = [];
		}

	    foreach ( $results as $row ) {
	        // Extract course_id from meta_key
	        if ( preg_match( '/course_(\d+)_access_from/', $row->meta_key, $matches ) ) {
	            $course_id = (int) $matches[1];
	            $course_users[ $course_id ][] = (int) $row->user_id;
	        }
	    }

	    // Remove duplicates
	    foreach ( $course_users as $course_id => $users ) {
	        $course_users[ $course_id ] = array_unique( $users );
	    }

	    // Cache the results
	    Cache::create( $cache_key, $course_users );

	    return $course_users;
	}

}
