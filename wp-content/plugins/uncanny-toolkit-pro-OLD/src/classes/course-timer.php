<?php

namespace uncanny_pro_toolkit;

use uncanny_learndash_toolkit as toolkit;
use WP_Error;
use WP_User;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Sample
 *
 * @package uncanny_pro_toolkit
 */
class CourseTimer extends toolkit\Config implements toolkit\RequiredFunctions {

	/**
	 * @var string[]
	 */
	public static $timed_post_types = array(
		'sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz',
	);

	/**
	 * @var string[]
	 */
	public static $settings_metabox_key = [
		'learndash-course-display-content-settings',
		'learndash-lesson-display-content-settings',
		'learndash-topic-display-content-settings',
		'learndash-quiz-display-content-settings',
	];

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array(
			__CLASS__, 'run_frontend_hooks',
		) );
		add_action( 'rest_api_init', array( __CLASS__, 'rest_api_init' ) );
	}

	/*
	 * Initialize frontend actions and filters
	 */
	/**
	 * @return void
	 */
	public static function run_frontend_hooks() {

		if ( true === self::dependants_exist() ) {

			// Add timer idle setting to LearnDash Lessons, Topics, amd Quizzes
			add_filter( 'learndash_post_args', array(
				__CLASS__, 'add_idle_time_to_post_args_legacy',
			) );

			// Add minimum Course Time to quiz settings
			add_filter( 'learndash_post_args', array(
				__CLASS__, 'add_min_course_time_to_post_args',
			) );

			// 3.0+  - Add idle time field
			add_filter( 'learndash_settings_fields', array(
				__CLASS__,
				'add_idle_time_to_post_args',
			), 10, 2 ); // 3.0+

			// 3.0+ - Save custom settings field
			add_filter( 'learndash_metabox_save_fields', array(
				__CLASS__,
				'save_timer_custom_meta',
			), 60, 3 );

			$minimum_course_time_before_quiz_access = self::get_settings_value( 'minimum_course_time_before_quiz_access', __CLASS__ );

			if ( 'on' === $minimum_course_time_before_quiz_access ) {
				// 3.0+  - Add minimum course time field
				add_filter( 'learndash_settings_fields', array(
					__CLASS__,
					'add_minimum_course_time_to_post_args',
				), 10, 2 ); // 3.0+

				// 3.0+ - Save custom settings field
				add_filter( 'learndash_metabox_save_fields', array(
					__CLASS__,
					'save_minimum_course_time_custom_meta',
				), 60, 3 );
				// Filter quiz content
				add_filter( 'the_content', array(
					__CLASS__, 'filter_quiz_content',
				) );
			}

			// Enqueue script for timer functionality
			add_action( 'wp_enqueue_scripts', array(
				__CLASS__, 'add_timer_scripts',
			) );

			// Return time elapse
			add_shortcode( 'uo_time', array( __CLASS__, 'shortcode_uo_time' ) );

			// Return live timer
			add_shortcode( 'uo_time_live', array( __CLASS__, 'shortcode_uo_time_live' ) );

			// Return entire topic time elapsed
			add_shortcode( 'uo_time_topic', array(
				__CLASS__, 'shortcode_uo_time_topic',
			) );

			// Return enite lesson time elapsed
			add_shortcode( 'uo_time_lesson', array(
				__CLASS__, 'shortcode_uo_time_lesson',
			) );

			// Return quiz time elapsed
			add_shortcode( 'uo_time_quiz', array(
				__CLASS__, 'shortcode_uo_time_quiz',
			) );

			// Return user total time elapsed
			add_shortcode( 'uo_time_total', array(
				__CLASS__, 'shortcode_uo_time_total',
			) );

			// Return time elapse
			add_shortcode( 'uo_time_course_completed', array(
				__CLASS__, 'shortcode_uo_time_course_completed',
			) );

			// LD overhauled the course reports, there is now two version. One s before v2.3 upgrade and one after.
			// Validate that data upgrade completed
			$data_upgrade = get_option( 'learndash_data_settings', array() );

			// Data was upgraded
			if ( isset( $data_upgrade['user-meta-courses']['version'] ) ) {

				// Filter Report Headers which sets up the data values
				add_filter( 'learndash_data_reports_headers', array(
					__CLASS__,
					'course_export_upgraded_headers_filter',
				), 10, 2 );


			} else {

				// Data wasn't upgraded

				//There is now a filter for LearnDashMenu => Report
				add_filter( 'course_export_data', array(
					__CLASS__, 'course_export_data_filter',
				), 10, 1 );

			}


			//  Store timer when course completion get triggered
			add_action( 'learndash_course_completed', array(
				__CLASS__, 'uo_course_completed_store_timer',
			) );

			// Delete timer data when user LearnDash data is reset
			add_action( 'personal_options_update', array(
				__CLASS__, 'learndash_delete_user_data',
			) );
			add_action( 'edit_user_profile_update', array(
				__CLASS__, 'learndash_delete_user_data',
			) );
			if ( filter_has_var( INPUT_GET, 'page' ) && 'uncanny-toolkit' === filter_input( INPUT_GET, 'page' ) ) {
				add_action( 'admin_head', array(
					__CLASS__, 'uo_timer_interval_convert_text_to_number',
				) );
			}

			// Tincanny Integration
			add_filter( 'tc_api_get_courses_overview', array(
				__CLASS__, 'tc_api_get_courses_overview',
			), 10, 1 );
			add_filter( 'tc_api_get_courseSingleTable', array(
				__CLASS__, 'tc_api_get_courseSingleTable',
			), 10, 3 );
			add_filter( 'tc_api_get_userSingleCoursesOverviewTable', array(
				__CLASS__,
				'tc_api_get_userSingleCoursesOverviewTable',
			), 10, 3 );
			add_filter( 'tc_api_get_userSingleCourseProgressSummaryTable', array(
				__CLASS__,
				'tc_api_get_userSingleCourseProgressSummaryTable',
			), 10, 3 );
			add_action( 'wp_enqueue_scripts', array(
				__CLASS__, 'add_tc_timer_scripts',
			) );
			add_action( 'admin_enqueue_scripts', array(
				__CLASS__, 'add_tc_timer_scripts_admin',
			) );

			add_action( 'wp_ajax_nopriv_uo_wp_logout_user', array(
				__CLASS__, 'maybe_logout_user',
			) );

			add_action( 'wp_ajax_uo_wp_logout_user', array(
				__CLASS__, 'maybe_logout_user',
			) );
		}
	}

	/**
	 * Does the plugin rely on another function or plugin
	 *
	 * @return boolean || string Return either true or name of function or
	 *     plugin
	 *
	 */
	public static function dependants_exist() {

		/* Checks for LearnDash */
		global $learndash_post_types;
		if ( ! isset( $learndash_post_types ) ) {
			return 'Plugin: LearnDash';
		}

		// Return true if no dependency or dependency is available
		return true;


	}

	/**
	 * @param $json_return
	 * @param $user_id
	 * @param $course_id
	 *
	 * @return array
	 */
	public static function tc_api_get_userSingleCourseProgressSummaryTable( $json_return, $user_id, $course_id ) {

		global $wpdb;

		//Users' Completed Timers
		$sql_string                          = "SELECT meta_value FROM $wpdb->usermeta WHERE meta_key = 'course_timer_completed_{$course_id}' AND user_id = {$user_id}";
		$timer                               = $wpdb->get_var( $sql_string );
		$json_return['data']['timeComplete'] = $timer;

		//Users' Time Spent
		$sql_string             = "SELECT meta_value FROM $wpdb->usermeta WHERE meta_key LIKE 'uo_timer_{$course_id}%' AND user_id = {$user_id}";
		$times_spent            = $wpdb->get_results( $sql_string );
		$times_spent_rearranged = 0;
		foreach ( $times_spent as $time_spent ) {
			$time    = $time_spent->meta_value;
			$seconds = (int) $time;

			$times_spent_rearranged += $seconds;
		}

		$json_return['data']['timeSpent'] = self::convert_second_to_time( $times_spent_rearranged );

		return $json_return;
	}

	/**
	 * @param $second
	 *
	 * @return string
	 */
	private static function convert_second_to_time( $second ) {


		if ( ! $second ) {
			return '';
		}

		$hours  = floor( $second / 3600 );
		$second -= $hours * 3600;

		$minutes = floor( $second / 60 );
		$second  -= $minutes * 60;

		if ( $hours < 10 ) {
			$hours = '0' . $hours;
		}
		if ( $minutes < 10 ) {
			$minutes = '0' . $minutes;
		}
		if ( $second < 10 ) {
			$second = '0' . $second;
		}

		$total_time = ( $hours ) ? $hours . ':' : '';
		$total_time = ( $minutes ) ? $total_time . $minutes . ':' : $total_time . '00:';
		$total_time = ( $second ) ? $total_time . $second : $total_time . '00';


		return $total_time;
	}

	/**
	 * @param $json_return
	 * @param $user_id
	 * @param $rows
	 *
	 * @return mixed
	 */
	public static function tc_api_get_userSingleCoursesOverviewTable( $json_return, $user_id, $rows ) {

		global $wpdb;

		//Users' Completed Timers
		$sql_string       = "SELECT meta_value, meta_key FROM $wpdb->usermeta WHERE meta_key LIKE 'course_timer_completed_%' AND user_id = {$user_id}";
		$timers           = $wpdb->get_results( $sql_string );
		$timer_rearranged = [];
		foreach ( $timers as $timer ) {
			$timer_key = $timer->meta_key;
			$timer_key = explode( '_', $timer_key );
			$time      = $timer->meta_value;
			$seconds   = 0;
			if ( '' !== $time ) {
				$seconds = explode( ':', $time );
				$seconds = ( intval( $seconds[0] ) * 60 * 60 ) + ( intval( $seconds[1] ) * 60 ) + intval( $seconds[2] );
			}

			$timer_rearranged[ (int) $timer_key[3] ] = $seconds;
		}

		//Users' Time Spent
		$sql_string             = "SELECT meta_key, meta_value FROM $wpdb->usermeta WHERE meta_key LIKE 'uo_timer_%' AND user_id = {$user_id}";
		$times_spent            = $wpdb->get_results( $sql_string );
		$times_spent_rearranged = [];
		foreach ( $times_spent as $time_spent ) {

			$time_spent_key = $time_spent->meta_key;
			$time_key       = explode( '_', $time_spent_key );
			$time           = $time_spent->meta_value;
			$seconds        = (int) $time;

			if ( isset( $times_spent_rearranged[ (int) $time_key[2] ] ) ) {
				$times_spent_rearranged[ (int) $time_key[2] ] = $times_spent_rearranged[ (int) $time_key[2] ] + $seconds;
			} else {
				$times_spent_rearranged[ (int) $time_key[2] ] = $seconds;
			}
		}

		foreach ( $json_return['data'] as $row_id => &$row ) {
			$row['timeComplete'] = ( isset( $timer_rearranged[ $row['course_id'] ] ) ) ? self::convert_second_to_time( $timer_rearranged[ $row['course_id'] ] ) : '---';
			$row['timeSpent']    = ( isset( $times_spent_rearranged[ $row['course_id'] ] ) ) ? self::convert_second_to_time( $times_spent_rearranged[ $row['course_id'] ] ) : '---';
		}

		return $json_return;
	}

	/**
	 * @param $json_return
	 * @param $course_id
	 * @param $rows
	 *
	 * @return mixed
	 */
	public static function tc_api_get_courseSingleTable( $json_return, $course_id, $rows ) {

		global $wpdb;

		//Users' Completed Timers
		$sql_string       = "SELECT user_id, meta_value FROM $wpdb->usermeta WHERE meta_key = 'course_timer_completed_{$course_id}'";
		$timers           = $wpdb->get_results( $sql_string );
		$timer_rearranged = [];
		foreach ( $timers as $timer ) {

			$user_id = (int) $timer->user_id;
			$time    = $timer->meta_value;
			$seconds = 0;
			if ( '' !== $time ) {
				$seconds = explode( ':', $time );
				$seconds = ( intval( $seconds[0] ) * 60 * 60 ) + ( intval( $seconds[1] ) * 60 ) + intval( $seconds[2] );
			}

			$timer_rearranged[ $user_id ] = $seconds;
		}

		//Users' Time Spent
		$sql_string             = "SELECT user_id, meta_value FROM $wpdb->usermeta WHERE meta_key LIKE 'uo_timer_{$course_id}%'";
		$times_spent            = $wpdb->get_results( $sql_string );
		$times_spent_rearranged = [];
		foreach ( $times_spent as $time_spent ) {

			$user_id = (int) $time_spent->user_id;
			$time    = $time_spent->meta_value;
			$seconds = (int) $time;

			if ( isset( $times_spent_rearranged[ $user_id ] ) ) {
				$times_spent_rearranged[ $user_id ] = $times_spent_rearranged[ $user_id ] + $seconds;
			} else {
				$times_spent_rearranged[ $user_id ] = $seconds;
			}
		}

		foreach ( $json_return['data'] as $row_id => &$row ) {
			$row['timeComplete'] = ( isset( $timer_rearranged[ $row['user_id'] ] ) ) ? self::convert_second_to_time( $timer_rearranged[ $row['user_id'] ] ) : '---';
			$row['timeSpent']    = ( isset( $times_spent_rearranged[ $row['user_id'] ] ) ) ? self::convert_second_to_time( $times_spent_rearranged[ $row['user_id'] ] ) : '---';
		}

		return $json_return;
	}

	/**
	 * @param $return
	 *
	 * @return array
	 */
	public static function tc_api_get_courses_overview( $return ) {


		global $wpdb;

		//Users' Completed Timers
		$sql_string       = "SELECT user_id, meta_key, meta_value FROM $wpdb->usermeta WHERE meta_key LIKE 'course_timer_completed_%'";
		$timers           = $wpdb->get_results( $sql_string );
		$timer_rearranged = [];
		foreach ( $timers as $timer ) {

			$user_id   = (int) $timer->user_id;
			$timer_key = $timer->meta_key;
			$timer_key = explode( '_', $timer_key );
			$time      = $timer->meta_value;
			$seconds   = 0;
			if ( '' !== $time ) {
				$seconds = explode( ':', $time );
				$seconds = ( intval( $seconds[0] ) * 60 * 60 ) + ( intval( $seconds[1] ) * 60 ) + intval( $seconds[2] );
			}

			$timer_rearranged[ $user_id ][ (int) $timer_key[3] ] = $seconds;
		}

		//Users' Time Spent
		$sql_string             = "SELECT user_id, meta_key, meta_value FROM $wpdb->usermeta WHERE meta_key LIKE 'uo_timer_%'";
		$times_spent            = $wpdb->get_results( $sql_string );
		$times_spent_rearranged = [];
		foreach ( $times_spent as $time_spent ) {

			$user_id        = (int) $time_spent->user_id;
			$time_spent_key = $time_spent->meta_key;
			$time_key       = explode( '_', $time_spent_key );
			$time           = $time_spent->meta_value;
			$seconds        = (int) $time;

			if ( isset( $times_spent_rearranged[ $user_id ][ (int) $time_key[2] ] ) ) {
				$times_spent_rearranged[ $user_id ][ (int) $time_key[2] ] = $times_spent_rearranged[ $user_id ][ (int) $time_key[2] ] + $seconds;
			} else {
				$times_spent_rearranged[ $user_id ][ (int) $time_key[2] ] = $seconds;
			}
		}

		$course_avg_time_complete = [];
		$course_avg_time_spent    = [];

		foreach ( $return['data']['courseList'] as $course_id => $course ) {
			$user_access = $return['data']['userList']['course_access_list'][ $course_id ];
			foreach ( $user_access as $user_id ) {

				if ( isset( $times_spent_rearranged[ $user_id ][ $course_id ] ) ) {
					$course_avg_time_spent[ $course_id ][] = $times_spent_rearranged[ $user_id ][ $course_id ];
				}
				if ( isset( $timer_rearranged[ $user_id ][ $course_id ] ) ) {
					$course_avg_time_complete[ $course_id ][] = $timer_rearranged[ $user_id ][ $course_id ];
				}
			}

			if ( empty( $course_avg_time_complete[ $course_id ] ) ) {
				$avg_seconds = '---';
			} else {
				$total       = array_sum( $course_avg_time_complete[ $course_id ] );
				$count       = count( $course_avg_time_complete[ $course_id ] );
				$avg_seconds = $total / $count;
				$hours       = floor( $avg_seconds / 3600 );
				$minutes     = floor( ( $avg_seconds % 3600 ) / 60 );
				$seconds     = floor( $avg_seconds % 60 );
				$avg_seconds = sprintf( '%02d:%02d:%02d', $hours, $minutes, $seconds );
			}

			$return['additionalData'][ $course_id ]['avgTimeComplete'] = $avg_seconds;

			if ( empty( $course_avg_time_spent[ $course_id ] ) ) {
				$avg_seconds = '---';
			} else {
				$total       = array_sum( $course_avg_time_spent[ $course_id ] );
				$count       = count( $course_avg_time_spent[ $course_id ] );
				$avg_seconds = $total / $count;
				$hours       = floor( $avg_seconds / 3600 );
				$minutes     = floor( ( $avg_seconds % 3600 ) / 60 );
				$seconds     = floor( $avg_seconds % 60 );
				$avg_seconds = sprintf( '%02d:%02d:%02d', $hours, $minutes, $seconds );
			}

			$return['additionalData'][ $course_id ]['avgTimeSpent'] = $avg_seconds;
		}


		return $return;
	}

	/**
	 * @return void
	 */
	public static function uo_timer_interval_convert_text_to_number() {
		?>
		<script>jQuery(document).ready(function ($) {
				$(document).on("click",".ult-directory-module-settings",function() {
					var timerSetting = $(this).data("settings");
					var timerSettingVal = parseInt($('input[name="uo_timer_interval"]').val());
					if(timerSetting=='uncanny_pro_toolkitCourseTimer' && isNaN(timerSettingVal)){
						$('input[name="uo_timer_interval"]').val(20);
					}
				});
				$('input[name="uo_timer_interval"]').blur(function () {
					var val = parseInt($(this).val());
					if (val < 5 || isNaN(val)) {
						$(this).val(20);
					}
				})
			})</script><?php
	}

	/**
	 * Description of class in Admin View
	 *
	 * @return array
	 */
	public static function get_details() {
		$module_id = 'simple-course-timer';

		$class_title = esc_html__( 'Simple Course Timer', 'uncanny-pro-toolkit' );

		$kb_link = 'http://www.uncannyowl.com/knowledge-base/simple-course-timer/';

		/* Sample Simple Description with shortcode */
		$class_description = esc_html__( 'Tracks time spent in all LearnDash courses and detects when a user is idle. Course completion time and total course time are both added to LearnDash reports. Enables blocking access to quizzes until minimum time spent in course.', 'uncanny-pro-toolkit' );

		/* Icon as fontawesome icon */
		$class_icon = '<i class="uo_icon_pro_fa uo_icon_fa fa fa-clock-o"></i><span class="uo_pro_text">PRO</span>';

		$category = 'learndash';
		$type     = 'pro';

		return array(
			'id'               => $module_id,
			'title'            => $class_title,
			'type'             => $type,
			'category'         => $category,
			'kb_link'          => $kb_link, // OR set as null not to display
			'description'      => $class_description,
			'dependants_exist' => self::dependants_exist(),
			/*'settings' => false, // OR */
			'settings'         => self::get_class_settings( $class_title ),
			'icon'             => $class_icon,
		);

	}

	/* Add Idle time to LeearnDash Options Meta Box
	 *@param array $post_args array of options from the LearnDash custom post type option meta box
	 */

	/**
	 * HTML for modal to create settings
	 *
	 * @static
	 *
	 * @param $class_title
	 *
	 * @return HTML
	 */
	public static function get_class_settings( $class_title ) {

		// Get pages to populate drop down
		$args = array(
			'sort_order'  => 'asc',
			'sort_column' => 'post_title',
			'post_type'   => 'page',
			'post_status' => 'publish',
		);

		$pages     = get_pages( $args );
		$drop_down = array();
		array_push( $drop_down, array(
			'value' => '',
			'text'  => esc_attr__( 'Select a Page', 'uncanny-pro-toolkit' ),
		) );

		foreach ( $pages as $page ) {
			if ( empty( $page->post_title ) ) {
				$page->post_title = esc_attr__( '(no title)', 'uncanny-pro-toolkit' );
			}

			array_push( $drop_down, array(
				'value' => $page->ID, 'text' => $page->post_title,
			) );
		}

		// Create options
		$options = array(

			array(
				'type'        => 'text',
				'label'       => esc_attr__( 'Idle Time (in seconds)', 'uncanny-pro-toolkit' ),
				'option_name' => 'default_idle_time',
			),
			array(
				'type'        => 'text',
				'label'       => esc_attr__( 'Idle Message', 'uncanny-pro-toolkit' ),
				'option_name' => 'timed_out_message',
			),
			array(
				'type'        => 'text',
				'label'       => esc_attr__( 'Active Button Label', 'uncanny-pro-toolkit' ),
				'option_name' => 'active_button_text',
			),
			array(
				'type'        => 'text',
				'label'       => esc_attr__( 'Inactive Button Label', 'uncanny-pro-toolkit' ),
				'option_name' => 'inactive_button_text',
			),
			array(
				'type'        => 'select',
				'label'       => esc_attr__( 'Inactive Redirect', 'uncanny-pro-toolkit' ),
				'select_name' => 'timed_out_redirect',
				'options'     => $drop_down,
			),

			array(
				'type'        => 'checkbox',
				'label'       => esc_attr__( 'Enable Logout', 'uncanny-pro-toolkit' ),
				'option_name' => 'enable_logout_mode',
			),
			array(
				'type'       => 'html',
				'class'      => 'uo-additional-information',
				'inner_html' => '<div style="margin-top: -15px;font-style: italic;">' .
								esc_attr__( 'Log users out and redirect to the "Inactive Redirect" page instead of showing the inactivity modal. If the "Inactive Redirect" page is not specified, the user will instead be redirected to the homepage.', 'uncanny-pro-toolkit' ) .
								'</div>',
			),
			array(
				'type'        => 'checkbox',
				'label'       => esc_attr__( 'Enable Quizzes after X time', 'uncanny-pro-toolkit' ),
				'option_name' => 'minimum_course_time_before_quiz_access',
			),
			array(
				'type'       => 'html',
				'class'      => 'uo-additional-information',
				'inner_html' => '<div style="margin-top: -16px;font-style: italic;">' .
								esc_attr__( 'Enable minimum course time before learners can attempt quizzes. Minimum course time value is set in quizzes.', 'uncanny-pro-toolkit' ) .
								'</div>',
			),
			array(
				'type'        => 'text',
				'label'       => esc_attr__( 'Timer Polling Interval (in seconds)', 'uncanny-pro-toolkit' ),
				'option_name' => 'uo_timer_interval',
			),
			array(
				'type'       => 'html',
				'class'      => 'uo-additional-information',
				'inner_html' => '<div style="margin-top: -15px;font-style: italic;">' .
								esc_attr__( 'The minimum polling interval is 5 seconds. Lower values use more server resources.', 'uncanny-pro-toolkit' ) .
								'</div>',
			),
			array(
				'type'        => 'checkbox',
				'label'       => esc_attr__( 'Disable Performance Mode', 'uncanny-pro-toolkit' ),
				'option_name' => 'disable_performance_timer',
			),
			array(
				'type'       => 'html',
				'class'      => 'uo-additional-information',
				'inner_html' => '<div style="margin-top: -13px;font-style: italic;">' .
								esc_attr__( 'Performance Mode uses a simplified REST API call that reduces load on the server.', 'uncanny-pro-toolkit' ) .
								'</div>',
			),
			array(
				'type'        => 'checkbox',
				'label'       => esc_attr__( 'Enable Debug Mode', 'uncanny-pro-toolkit' ),
				'option_name' => 'enable_debug_mode',
			)

		);

		// Build html
		$html = self::settings_output( array(
			'class'   => __CLASS__,
			'title'   => $class_title,
			'options' => $options,
		) );

		return $html;
	}

	/* Add minimum Course Time to LeearnDash Options Meta Box
	 * @since 1.3
	 *
	 *@param array $post_args array of options from the LearnDash custom post type option meta box
	 */

	/**
	 * @param $post_args
	 *
	 * @return array
	 */
	public static function add_idle_time_to_post_args_legacy( $post_args ) {

		if ( class_exists( 'LearnDash_Theme_Register' ) ) {
			return $post_args;
		}
		// Push existing and new fields
		$new_post_args = array();

		// Loop through all post arguments
		foreach ( $post_args as $key => $val ) {

			$new_post_args[ $key ] = $val;
			// add option on LD post type settings meta box
			if ( in_array( $val['post_type'], self::$timed_post_types ) ) {
				//Add new field to top
				$new_post_args[ $key ]['fields']['uo_timer_idle'] = array(
					'name'      => esc_attr__( 'Idle time (seconds)', 'uncanny-pro-toolkit' ),
					'type'      => 'text',
					'help_text' => esc_attr__( 'Set the time in seconds until a user is flagged as inactive for time tracking purposes', 'uncanny-pro-toolkit' ),
					'default'   => '0',
				);
			}
		}

		return $new_post_args;
	}

	/**
	 * @param $post_args
	 *
	 * @return array
	 */
	public static function add_min_course_time_to_post_args( $post_args ) {

		if ( class_exists( 'LearnDash_Theme_Register' ) ) {
			return $post_args;
		}

		$minimum_course_time_before_quiz_access = self::get_settings_value( 'minimum_course_time_before_quiz_access', __CLASS__ );

		if ( 'on' === $minimum_course_time_before_quiz_access ) {

			// Push existing and new fields
			$new_post_args = array();

			// Loop through all post arguments
			foreach ( $post_args as $key => $val ) {

				$new_post_args[ $key ] = $val;

				// add option on LD post type settings meta box
				if ( 'sfwd-quiz' === $val['post_type'] ) {

					//Add new field to top
					$new_post_args[ $key ]['fields']['minimum_course_time_before_quiz_access'] = array(
						'name'      => esc_attr__( 'Minimum Course Time (in minutes)', 'uncanny-pro-toolkit' ),
						'type'      => 'text',
						'help_text' => esc_attr__( 'Only allow access to the quiz when the user\'s time spent in the course EQUALS OR EXCEEDS this value in minutes.', 'uncanny-pro-toolkit' ),
						'default'   => '0',
					);
				}
			}

			return $new_post_args;

		} else {
			return $post_args;
		}

	}

	/**
	 * @param $content
	 *
	 * @return false|string
	 */
	public static function filter_quiz_content( $content ) {

		$current_post = $GLOBALS['post'];

		if ( ! is_object($current_post) || ! isset($current_post->post_type) || 'sfwd-quiz' !== $current_post->post_type ) {
			return $content;
		}

		global $wpdb;

		$user_ID = get_current_user_id();

		$timer_results = self::get_course_time_in_seconds( $current_post->ID, $user_ID );

		if ( $timer_results ) {
			$current_minutes = floor( $timer_results / 60 );
		} else {
			$current_minutes = 0;
		}

		$post_options = learndash_get_setting( $current_post );

		if ( ! isset( $post_options['minimum_course_time_before_quiz_access'] ) || 0 === (int) $post_options['minimum_course_time_before_quiz_access'] ) {
			return $content;
		}

		$current_post = $GLOBALS['post'];

		$quiz_post_html_id = 'post-' . $current_post->ID;

		$ld_quiz_post_html_class = 'learndash_post_' . $current_post->ID;

		$min_minutes = $post_options['minimum_course_time_before_quiz_access'];

		if ( $min_minutes <= $current_minutes ) {
			return $content;
		}

		$course_id = learndash_get_course_id();

		if ( ! $course_id ) {
			$lesson_id = learndash_get_lesson_id();
			$course_id = learndash_get_course_id( $lesson_id );
		}

		if( true !== self::is_course_timer_enabled( $course_id ) ) {
			return $content;
		}

		$course_name = get_the_title( $course_id );
		$permalink   = esc_url( get_permalink( $course_id ) );


		$course_link = '<a href="' . $permalink . '">' . $course_name . '</a>';

		$message = '<div id="ultp-course-timer-quiz-can-not-access">';
		$message .= '<p>' . sprintf( esc_attr__( 'You must spend %1$s minutes in the course before you can attempt the quiz. You have currently spent %2$s minutes. Please go back and review course material before attempting the quiz.', 'uncanny-pro-toolkit' ), $min_minutes, $current_minutes ) . '</p>';
		$message .= '<p>' . sprintf( esc_attr__( 'Return to %s', 'uncanny-pro-toolkit' ), $course_link ) . '</p>';
		$message .= '</div>';

		ob_start();
		?>
		<script>

			var i;

			var lduoQuizContainer = document.getElementById('<?php echo $quiz_post_html_id; ?>');

			if (lduoQuizContainer !== null) {

				lduoQuizContainer.innerHTML = '<?php echo $message; ?>';

			} else {

				// Sometimes theme may forget the id attribute in the article tag ex. Avada... check class
				lduoQuizContainer = document.getElementsByClassName('<?php echo $quiz_post_html_id; ?>');

				// The theme didn't add it as a class either
				if (0 === lduoQuizContainer.length) {
					// check for a learndash ID that was added recently
					var ldQuizPostHtmlClass = document.getElementById('<?php echo $ld_quiz_post_html_class; ?>');

					ldQuizPostHtmlClass.style.display = 'none';

					jQuery(document).ready(function () {
						var ldQuizPostHtmlClass = document.getElementById('<?php echo $ld_quiz_post_html_class; ?>');
						if (null !== ldQuizPostHtmlClass) {
							ldQuizPostHtmlClass.innerHTML = '<?php echo $message; ?>';
							ldQuizPostHtmlClass.style.display = 'block';
						}
					});

				} else {
					for (i = 0; i < lduoQuizContainer.length; i++) {
						lduoQuizContainer[i].innerHTML = '<?php echo $message; ?>';
					}
				}

			}
		</script>
		<?php

		return ob_get_clean();
	}

	/**
	 * @param $course_id
	 * @param $user_ID
	 *
	 * @return string|null
	 */
	public static function get_course_time_in_seconds( $course_id, $user_ID ) {

		global $wpdb;

		$completed_on_meta_key = 'uo_timer_' . learndash_get_course_id( $course_id ) . '_%';

		$query_results = $wpdb->prepare( "SELECT SUM( meta_value ) FROM $wpdb->usermeta WHERE meta_key LIKE %s AND user_id = %d", $completed_on_meta_key, $user_ID );

		$timer_results = $wpdb->get_var( $query_results );

		return $timer_results;

	}

	/**
	 * @param $course_id
	 *
	 * @return bool
	 */
	public static function is_course_timer_enabled( $course_id ) {
		return (bool) apply_filters( 'uo_course_timer_enabled', true, $course_id );
	}

	/**
	 * @return void
	 */
	public static function add_timer_scripts() {


		global $post_type;
		global $post;

		if ( ! is_404() && ! is_archive() && in_array( $post_type, self::$timed_post_types ) && is_user_logged_in() ) {

			$course_id = learndash_get_course_id( $post->ID );

			if ( '0' === $course_id || '' === $course_id ) {
				$lesson_id = learndash_get_lesson_id( $post->ID, $course_id );
				if ( '0' === $lesson_id || '' === $lesson_id ) {
					$course_id = 1;
				} else {
					$course_id = learndash_get_course_id( $lesson_id );
				}
			}

			if ( 'sfwd-courses' === $post->post_type ) {
				$course_id = $post->ID;
			}

			if( true !== self::is_course_timer_enabled( $course_id ) ) {
				return;
			}

			// Set Time out
			$idle_time_out            = 900;
			$feature_time_out_default = self::get_settings_value( 'default_idle_time', __CLASS__ );

			if ( '' !== $feature_time_out_default ) {
				$idle_time_out = (int) $feature_time_out_default;
			}

			$post_options_timeout = learndash_get_setting( $post );

			if ( isset( $post_options_timeout['uo_timer_idle'] ) && 0 !== (int) $post_options_timeout['uo_timer_idle'] ) {
				$idle_time_out = (int) $post_options_timeout['uo_timer_idle'];
			}

			// Set Dialog Text
			$active_button_text         = esc_attr__( 'Still here', 'uncanny-pro-toolkit' );
			$feature_active_button_text = self::get_settings_value( 'active_button_text', __CLASS__ );

			if ( '' !== $feature_active_button_text ) {
				$active_button_text = $feature_active_button_text;
			}

			$inactive_button_text         = esc_attr__( 'I\'m done', 'uncanny-pro-toolkit' );
			$feature_inactive_button_text = self::get_settings_value( 'inactive_button_text', __CLASS__ );

			if ( '' !== $feature_inactive_button_text ) {
				$inactive_button_text = $feature_inactive_button_text;
			}

			$timed_out_message         = esc_attr__( 'Are you still working on this section?', 'uncanny-pro-toolkit' );
			$feature_timed_out_message = self::get_settings_value( 'timed_out_message', __CLASS__ );

			if ( '' !== $feature_timed_out_message ) {
				$timed_out_message = $feature_timed_out_message;
			}

			$timed_out_redirect         = apply_filters( 'uo_course_timer_timedout_redirect_url', home_url() );
			$feature_timed_out_redirect = self::get_settings_value( 'timed_out_redirect', __CLASS__ );

			if ( '' !== $feature_timed_out_redirect ) {
				$timed_out_redirect = get_permalink( $feature_timed_out_redirect );
			}

			$disable_performance_timer         = 'true';
			$feature_disable_performance_timer = self::get_settings_value( 'disable_performance_timer', __CLASS__ );

			if ( '' !== $feature_disable_performance_timer ) {
				$disable_performance_timer = 'false';
			}

			$enable_debug_mode         = 'false';
			$feature_enable_debug_mode = self::get_settings_value( 'enable_debug_mode', __CLASS__ );

			if ( '' !== $feature_enable_debug_mode ) {
				$enable_debug_mode = 'true';
			}

			$enable_logout_mode         = 'false';
			$feature_enable_logout_mode = self::get_settings_value( 'enable_logout_mode', __CLASS__ );

			if ( '' !== $feature_enable_logout_mode && ! current_user_can( apply_filters('uo_time_enable_logout_mode_capability', 'manage_options', $course_id) ) ) {
				$enable_logout_mode = 'true';
			}

			// Dialog Box Style
			$dialog_box_style = plugins_url( basename( dirname( UO_FILE ) ) ) . '/src/assets/legacy/frontend/css/jquery.dialogbox.css';
			wp_enqueue_style( 'jquery-dialog-box-style', $dialog_box_style, array(), UNCANNY_TOOLKIT_PRO_VERSION );

			// Dialog Box Script @ http://www.jqueryscript.net/lightbox/Simple-Flexible-jQuery-Dialog-Popup-Plugin-dialogBox.html
			$idle_timer_url = plugins_url( basename( dirname( UO_FILE ) ) ) . '/src/assets/legacy/frontend/js/jquery.dialogBox.js';
			wp_enqueue_script( 'jquery-dialog-box', $idle_timer_url, array( 'jquery' ), '0.0.2', true );

			// Throst Idle Timer @ https://github.com/thorst/jquery-idletimer
			$idle_timer_url = plugins_url( basename( dirname( UO_FILE ) ) ) . '/src/assets/legacy/frontend/js/idle-timer.js';
			wp_enqueue_script( 'throst-idle-timer', $idle_timer_url, array( 'jquery' ), '1.1.0', true );

			// Timer Script
			$uo_timer_url = plugins_url( basename( dirname( UO_FILE ) ) ) . '/src/assets/legacy/frontend/js/uo-timer.js';
			wp_register_script( 'uo-timer-js', $uo_timer_url, array(
				'throst-idle-timer',
				'jquery-dialog-box',
			), UNCANNY_TOOLKIT_PRO_VERSION, true );

			add_filter( 'nonce_life', array(
				__CLASS__, 'change_course_timer_nonce_life',
			) );
			remove_filter( 'nonce_life', array(
				__CLASS__, 'change_course_timer_nonce_life',
			) );

			// Amount of time added every call // JS interval must match
			$timer_interval = self::get_settings_value( 'uo_timer_interval', __CLASS__ );

			//  Set timer default, mininum interval is 20 seconds
			if ( '' === $timer_interval || (int) $timer_interval < 5 ) {
				$timer_interval = 20;
			}

			$localize_data_array = array(
				'courseID'               		=> $course_id,
				'postID'                 		=> $post->ID,
				'idleTimeOut'            		=> $idle_time_out,
				'apiUrl'                 		=> esc_url_raw( rest_url() . 'uo_pro/v1/add_timer/' ),
				'nonce'                  		=> wp_create_nonce( 'wp_rest' ),
				'redirect'               		=> $timed_out_redirect,
				'inactiveButtonText'     		=> $inactive_button_text,
				'activeButtonText'       		=> $active_button_text,
				'timedOutMessage'        		=> $timed_out_message,
				'uoHeartBeatInterval'    		=> (int) $timer_interval,
				'enablePerformanceTimer' 		=> $disable_performance_timer,
				'enableDebugMode'        		=> $enable_debug_mode,
				'enableLogouMode'  				=> $enable_logout_mode,
				'WPAdminURL'  					=> admin_url('admin-ajax.php'),
				'timerLogoutNonce'  			=> wp_create_nonce('uo_course_timer_logout_nonce'),
				'performanceApiUrl'      		=> apply_filters( 'performance_timer_url', plugins_url( basename( dirname( UO_FILE ) ) ) . '/src/includes/simple_timer_performance.php' ),


			);

			wp_localize_script( 'uo-timer-js', 'uoTimer', $localize_data_array );
			wp_enqueue_script( 'uo-timer-js' );

		}

	}

	/**
	 * @return void
	 */
	public static function add_tc_timer_scripts() {

		global $post;

		if ( empty( $post->ID ) ) {
			return;
		}

		if ( ! has_shortcode( $post->post_content, 'tincanny' ) && ! has_block( 'tincanny/course-user-report', $post ) ) {
			return;
		}

		$plugin_base_url = plugins_url( basename( dirname( UO_FILE ) ) );

		wp_enqueue_script( 'hooks', $plugin_base_url . '/src/assets/legacy/backend/js/hooks.min.js?ver=2.0.4' );

		$script_url = $plugin_base_url . '/src/assets/legacy/backend/js/tincany-timer.js';
		wp_register_script( 'tincany-timer', $script_url, array(
			'jquery',
			'wp-hooks',
			'wp-i18n',
		), UNCANNY_TOOLKIT_PRO_VERSION, true );
		$tincanny_timer = array(
			'localizedStrings' => self::get_js_localized_strings(),
		);


		wp_localize_script( 'tincany-timer', 'tincannyTimer', $tincanny_timer );

		wp_enqueue_script( 'tincany-timer' );

	}

	/**
	 * @return array
	 */
	private static function get_js_localized_strings() {

		/**
		 * listed as they appear in file order under \plugins\tin-canny-learndash-reporting\src\assets\admin\js\scripts\components\*.js
		 */
		return array(
			// tables.js
			'Avg Time To Complete' => esc_attr__( 'Avg Time To Complete', 'uncanny-pro-toolkit' ),
			'Avg Time Spent'       => esc_attr__( 'Avg Time Spent', 'uncanny-pro-toolkit' ),
			'Avg Time To Complete' => esc_attr__( 'Avg Time To Complete', 'uncanny-pro-toolkit' ),
			'Time To Complete'     => esc_attr__( 'Time To Complete', 'uncanny-pro-toolkit' ),
			'Time Spent'           => esc_attr__( 'Time Spent', 'uncanny-pro-toolkit' ),
		);
	}

	/*
	 * Api call to add seconds spent on a module
	 * @param array $data data parse by api call
	 * @return object success or error
	 */

	/**
	 * @param $hook
	 *
	 * @return void
	 */
	public static function add_tc_timer_scripts_admin( $hook ) {

		if ( 'toplevel_page_uncanny-learnDash-reporting' === $hook ) {

			global $wp_version;

			$plugin_base_url = plugins_url( basename( dirname( UO_FILE ) ) );


			if ( ! wp_script_is( 'wp-hooks', $list = 'enqueued' ) ) {
				if ( version_compare( $wp_version, '5', '<' ) ) {
					wp_enqueue_script( 'hooks', $plugin_base_url . '/src/assets/legacy/backend/js/hooks.min.js?ver=2.0.4' );
				} else {
					wp_enqueue_script( 'wp-hooks' );
				}
			}

			$script_url = $plugin_base_url . '/src/assets/legacy/backend/js/tincany-timer.js';
			wp_register_script( 'tincany-timer', $script_url, array(
				'jquery',
				'wp-hooks',
				'wp-i18n',
			), UNCANNY_TOOLKIT_PRO_VERSION, true );
			$tincanny_timer = array(
				'localizedStrings' => self::get_js_localized_strings(),
			);

			wp_localize_script( 'tincany-timer', 'tincannyTimer', $tincanny_timer );


			wp_enqueue_script( 'tincany-timer' );
		}
	}

	/*
	 * Shortcode that displays time spent on course or learndash module
	 *
	 * @param array $attributes Optional wp user id and post id can be passed
	 * @return string Time spent on a single module or all modules in course in formatted seconds
	 */

	/**
	 * @return void
	 */
	public static function rest_api_init() {

		// Call to store the answer in DB
		// if a field is text (?P<whatever>\w+), if a field is int (?P<whatever>\d+)
		register_rest_route( 'uo_pro/v1', '/add_timer/(?P<course_ID>\w+)/(?P<post_ID>\w+)', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'add_timer' ),
			'permission_callback' => function () {
				if ( is_user_logged_in() ) {
					return true;
				}

				return new WP_Error( 'rest_forbidden', esc_attr__( 'You are not allowed to use this resource.', 'uncanny-pro-toolkit' ) );
			},
		) );

	}

	/*
	 * Shortcode that displays time spent on course or learndash module
	 *
	 * @param array $attributes Optional wp user id and post id can be passed
	 * @return string Time spent on a single module or all modules in course in formatted seconds
	 */

	/**
	 * @param $data
	 *
	 * @return array
	 */
	public static function add_timer( $data ) {

		$return_object = array();

		$current_user_id = get_current_user_id();

		// validate inputs
		$course_ID = absint( $data['course_ID'] );
		$post_ID   = absint( $data['post_ID'] );

		// if any of the values are 0 then they didn't validate, storage is not possible
		if ( 0 === $course_ID || 0 === $post_ID ) {
			$return_object['success']             = false;
			$return_object['message']             = esc_attr__( 'One or more the the fields did not validate as a absolute integer', 'uncanny-pro-toolkit' );
			$return_object['fields']['course_ID'] = $data['course_ID'];
			$return_object['fields']['post_ID']   = $data['post_ID'];

			return $return_object;
		}

		if( true !== self::is_course_timer_enabled( $course_ID ) ) {
			$return_object['success']             = false;
			$return_object['message']             = esc_attr__( 'Disabled', 'uncanny-pro-toolkit' );
			$return_object['fields']['course_ID'] = $data['course_ID'];
			$return_object['fields']['post_ID']   = $data['post_ID'];

			return $return_object;
		}

		$meta_key = 'uo_timer_' . $course_ID . '_' . $post_ID;
		$timer    = get_user_meta( $current_user_id, $meta_key, true );

		//  Set timer default
		if ( '' === $timer ) {
			$timer = 0;
		}

		// Amount of time added every call // JS interval must match
		$timer_interval = self::get_settings_value( 'uo_timer_interval', __CLASS__ );

		//  Set timer default, minimum is 20 seconds
		if ( '' === $timer_interval || (int) $timer_interval < 5 ) {
			$timer_interval = 20;
		}


		$timer += $timer_interval;

		update_user_meta( $current_user_id, $meta_key, $timer );

		do_action( 'uo_course_timer_add_timer', $course_ID, $post_ID, $timer_interval );

		$return_object['success'] = true;
		$return_object['time']    = $timer;

		global $uo_time_pre;
		$time_post                  = microtime( true );
		$exec_time                  = $time_post - $uo_time_pre;
		$return_object['exec_time'] = $exec_time;

		return $return_object;
	}

	/*
	 * Get the amount of time it took to complete a course or single module
	 *
	 * @param object $post_object A WP post object
	 * @param int $user_ID WP user ID
	 *
	 * return string Time spent on a single module or all modules on course in formatted seconds
	 */

	/**
	 * @param $attributes
	 *
	 * @return mixed|string|null
	 */
	public static function shortcode_uo_time( $attributes ) {


		$request = shortcode_atts( array(
			'user-id'   => '',
			'course-id' => '',
		), $attributes );

		if ( '' === $request['user-id'] ) {
			$request['user-id'] = get_current_user_id();
		}

		$user_ID = absint( $request['user-id'] );
		$post_ID = absint( $request['course-id'] );

		if ( '' == $request['course-id'] ) {
			global $post;

			if ( ( ! is_404() && ! is_archive() && is_a( $post, 'WP_Post' ) ) ) {
				$course_post = get_post( learndash_get_course_id( $post->ID ) );

				return self::get_uo_time( $course_post, $user_ID );
			}

		} elseif ( $post_ID ) {

			$requested_post = get_post( $post_ID );

			return self::get_uo_time( $requested_post, $user_ID );
		}

		return '';
	}

	/**
	 * @param $post_object
	 * @param $user_ID
	 * @param $raw
	 *
	 * @return mixed|string|null
	 */
	public static function get_uo_time( $post_object, $user_ID, $raw = false ) {

		if ( $post_object->post_type == 'sfwd-courses' ) {

			// count all timed sections of course
			$course_cumulative_time = self::get_course_time_in_seconds( $post_object->ID, $user_ID );

			if ( $raw ) {
				return $course_cumulative_time;
			}

			return self::convert_second_to_time( $course_cumulative_time );

		} elseif ( in_array( $post_object->post_type, self::$timed_post_types ) ) {
			// return specific time
			$course_ID = learndash_get_course_id( $post_object->ID );
			$meta_key  = 'uo_timer_' . $course_ID . '_' . $post_object->ID;
			$timer     = get_user_meta( (int) $user_ID, $meta_key, true );

			if ( $raw ) {
				return $timer;
			}

			return self::convert_second_to_time( (int) $timer );

		}

		return '';

	}

	/**
	 * @return string
	 */
	public static function shortcode_uo_time_live() {

		global $post;
		$course_post = false;
		if ( ! is_404() && ! is_archive() && is_a( $post, 'WP_Post' ) ) {

			// Check this is a LearnDash Post Type
			if ( ! in_array( $post->post_type, self::$timed_post_types ) ) {
				return '';
			}

			$course_post = get_post( learndash_get_course_id( $post->ID ) );
			if ( ! is_a( $course_post, 'WP_Post' ) ) {
				return '';
			}
		}

		$time = self::get_uo_time( $course_post, get_current_user_id() );
		$time = empty( $time ) ? '00:00:00' : $time; // If time is empty, set it to 00:00:00

		return '<span class="uotp-time-live">' . $time . '</span>';
	}

	/**
	 * @param $attributes
	 *
	 * @return mixed|string
	 */
	public static function shortcode_uo_time_course_completed( $attributes ) {

		$request = shortcode_atts( array(
			'user-id'   => '',
			'course-id' => '',
		), $attributes );

		if ( '' === $request['user-id'] ) {
			$request['user-id'] = get_current_user_id();
		}

		$user_ID   = absint( $request['user-id'] );
		$course_ID = absint( $request['course-id'] );

		if ( ! $course_ID ) {
			global $post;

			if ( ( ! is_404() && ! is_archive() && is_a( $post, 'WP_Post' ) ) ) {

				if ( 'sfwd-courses' !== $post->post_type ) {
					return '';
				}

				$course_ID = $post->ID;
			}

		} else {

			$post_type = get_post_type( $course_ID );
			if ( 'sfwd-courses' !== $post_type ) {
				return '';
			}
		}

		if( true !== self::is_course_timer_enabled( $course_ID ) ) {
			return '';
		}

		$meta_key           = 'course_timer_completed_' . $course_ID;
		$time_at_completion = get_user_meta( $user_ID, $meta_key, true );

		return $time_at_completion;

	}

	/**
	 * Filter Before LearnDash CVS is loaded
	 *
	 * @param array $content
	 *
	 * @return array $content
	 */
	public static function course_export_data_filter( $content ) {

		// In this peculiar case, the organization of the key value pairs plays a huge role in organization
		// of the csv file. The CEU value can't popped at the end because there will be a variable number of
		// 'lessons completed' columns for each course/user combo. The CEU value must immediately follow
		// the "course_completed_on" column.

		foreach ( $content as &$data ) {
			$user_ID   = (int) $data['user_id'];
			$course_id = (int) $data['course_id'];

			$new_row_data = array();

			// Re-organize array key value pairs so that CEU comes after course_completed_on.
			// If other filters are present and using the same organization they may push CEU after them
			// depending on the order of the filter execution.
			foreach ( $data as $key => $value ) {
				$new_row_data[ $key ] = $value;
				if ( $key === 'course_completed_on' ) {
					$new_row_data['total_time']      = self::get_uo_time( get_post( $course_id ), $user_ID );
					$meta_key                        = 'course_timer_completed_' . $course_id;
					$new_row_data['completion_time'] = get_user_meta( $user_ID, $meta_key, true );
				}
			}

			$data = $new_row_data;
		}

		return $content;
	}

	/**
	 * Filter Headers Before LearnDash CVS is loaded
	 *
	 * The header filter does to things. It creates the CSV heading and defined
	 * the function that will return the value of the column row.
	 *
	 * @param array $data_headers column definitions
	 * @param string $data_slug The report type
	 *
	 * @return array $data_headers
	 */
	public static function course_export_upgraded_headers_filter( $data_headers, $data_slug ) {

		if ( 'user-courses' === $data_slug ) {

			$data_headers['total_time'] = array(
				'label'   => 'total_time',
				'default' => '',
				'display' => [ __CLASS__, 'report_column' ],
			);

			$data_headers['completion_time'] = array(
				'label'   => 'completion_time',
				'default' => '',
				'display' => [ __CLASS__, 'report_column' ],
			);

		}


		return $data_headers;
	}

	/*
	 * Fetches the sum of all posts associated with the course including the course page
	 *
	 * @param int $course_id The ID of the course
	 * @param int $user_ID The users ID
	 * $return string $total_time Total time in seconds for the course
	 */

	/**
	 * This function defines the content value
	 *
	 * The header filter does to things. It creates the CSV heading and defined
	 * the function that will return the value of the column row.
	 *
	 * @param string $column_value The value of the column
	 * @param string $column_key The key set by $data_headers['sample_key'] @
	 *     course_export_upgraded_headers_filter()
	 * @param object $report_item The LD activity object
	 * @param object $report_user WP_User object
	 *
	 * @return mixed $column_value
	 */
	public static function report_column( $column_value = '', $column_key = '', $report_item = null, $report_user = null ) {

		if ( ! is_object( $report_item ) ) {
			return $column_value;
		}

		switch ( $column_key ) {
			case 'total_time':
				if ( $report_user instanceof WP_User ) {
					$column_value = self::get_uo_time( get_post( $report_item->post_id ), $report_user->ID );
				}
				break;

			case 'completion_time':
				if ( $report_user instanceof WP_User ) {

					$meta_key     = 'course_timer_completed_' . $report_item->post_id;
					$column_value = get_user_meta( $report_user->ID, $meta_key, true );
				}
				break;

			default:
				break;
		}

		return $column_value;

	}

	/*
	 * Convert Seconds to 00:00:00 (hours : minutes : seconds ) Format
	 *
	 * @param int $seconds time in seconds
	 * $return string $total_time seconds formatted
	 */

	/**
	 * @param $data
	 *
	 * @return void
	 */
	public static function uo_course_completed_store_timer( $data ) {

		$user_ID    = $data['user']->ID;
		$course     = $data['course'];
		$meta_key   = 'course_timer_completed_' . $course->ID;
		$meta_value = self::get_uo_time( $course, $user_ID );

		update_user_meta( $user_ID, $meta_key, $meta_value );

	}

	/**
	 * Delete user data
	 *
	 * @param int $user_id
	 *
	 * @since 2.1.0
	 *
	 */
	public static function learndash_delete_user_data( $user_id ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$user = get_user_by( 'id', $user_id );

		if ( ! empty( $user->ID ) && ! empty( filter_input( INPUT_POST, 'learndash_delete_user_data' ) ) && $user->ID == filter_input( INPUT_POST, 'learndash_delete_user_data' ) ) {

			global $wpdb;

			// Delete timer
			$timer_meta_key = 'uo_timer_%';

			$query_results = $wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE %s AND user_id = %d", $timer_meta_key, $user_id );

			// Delete time set at user completions
			$wpdb->query( $query_results );

			$completed_timer_meta_key = 'course_timer_completed_%';

			$query_results = $wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE %s AND user_id = %d", $completed_timer_meta_key, $user_id );

			$wpdb->query( $query_results );


		}
	}


	/**
	 * Delete user course data
	 *
	 * @param $user_id
	 * @param $course_id
	 *
	 * @return void
	 */
	public static function delete_user_course_data( $user_id, $course_id ) {

		$user = get_user_by( 'id', $user_id );


		if ( ! empty( $user->ID ) && ! empty( $course_id ) ) {

			global $wpdb;

			// Delete timer
			$timer_meta_key = 'uo_timer_' . $course_id . '_%%';

			$query_results = $wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE %s AND user_id = %d", $timer_meta_key, $user_id );

			// Delete time set at user completions
			$wpdb->query( $query_results );

			$completed_timer_meta_key = 'course_timer_completed_' . $course_id;

			$query_results = $wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE %s AND user_id = %d", $completed_timer_meta_key, $user_id );

			$wpdb->query( $query_results );
		}
	}

	/*
	 * Update nonce life for timer rest api calls
	 *
	 * @since 1.2.0
	 *
	 * @return int
	 */
	/**
	 * @return int
	 */
	public static function change_course_timer_nonce_life() {
		// 48 hours in seconds
		return 172800;
	}

	/**
	 * Add Idle time to LeearnDash Options Meta Box
	 *
	 * @param $setting_option_fields
	 * @param $settings_metabox_key
	 *
	 * @return mixed
	 */
	public static function add_idle_time_to_post_args( $setting_option_fields, $settings_metabox_key ) {

		if ( in_array( $settings_metabox_key, self::$settings_metabox_key ) ) {

			global $post;

			// add option on LD post type settings meta box

			if ( is_object( $post ) && isset( $post->post_type ) && in_array( $post->post_type, self::$timed_post_types ) ) {

				$learndash_post_settings = learndash_get_setting( $post, null );

				$value = '';
				if ( isset( $learndash_post_settings['uo_timer_idle'] ) ) {
					if ( ! empty( $learndash_post_settings['uo_timer_idle'] ) ) {
						$value = $learndash_post_settings['uo_timer_idle'];
					}
				}

				$setting_option_fields['uo_timer_idle'] = [
					'name'      => 'uo_timer_idle',
					'label'     => esc_attr__( 'Idle Time(seconds)', 'uncanny-pro-toolkit' ),
					'type'      => 'text',
					'help_text' => esc_attr__( 'Set the time in seconds until a user is flagged as inactive for time tracking purposes', 'uncanny-pro-toolkit' ),

					'default' => '0',
					'value'   => $value,
				];
			}
		}

		return $setting_option_fields;

	}

	/**
	 * Save post metadata when a post is saved.
	 *
	 * @param int $post_id The post ID.
	 * @param post $post The post object.
	 * @param bool $update Whether this is an existing post being updated or
	 *     not.
	 */
	public static function save_timer_custom_meta( $settings_field_updates, $settings_metabox_key, $settings_screen_id ) {

		global $post;

		if ( in_array( $settings_metabox_key, self::$settings_metabox_key ) ) {
			// - Update the post's metadata. Nonce already verified by LearnDash
			if (
				filter_has_var( INPUT_POST, 'learndash-course-display-content-settings' ) &&
				isset( $_POST['learndash-course-display-content-settings']['uo_timer_idle'] )
			) {
				$auto_complete_setting_value = sanitize_text_field( $_POST['learndash-course-display-content-settings']['uo_timer_idle'] );
				learndash_update_setting( $post, 'uo_timer_idle', $auto_complete_setting_value );
			}

			if (
				filter_has_var( INPUT_POST, 'learndash-lesson-display-content-settings' ) &&
				isset( $_POST['learndash-lesson-display-content-settings']['uo_timer_idle'] )
			) {
				$auto_complete_setting_value = sanitize_text_field( $_POST['learndash-lesson-display-content-settings']['uo_timer_idle'] );
				learndash_update_setting( $post, 'uo_timer_idle', $auto_complete_setting_value );
			}

			if (
				filter_has_var( INPUT_POST, 'learndash-topic-display-content-settings' ) &&
				isset( $_POST['learndash-topic-display-content-settings']['uo_timer_idle'] )
			) {
				$auto_complete_setting_value = sanitize_text_field( $_POST['learndash-topic-display-content-settings']['uo_timer_idle'] );
				learndash_update_setting( $post, 'uo_timer_idle', $auto_complete_setting_value );
			}

			if (
				filter_has_var( INPUT_POST, 'learndash-quiz-display-content-settings' ) &&
				isset( $_POST['learndash-quiz-display-content-settings']['uo_timer_idle'] )
			) {
				$auto_complete_setting_value = sanitize_text_field( $_POST['learndash-quiz-display-content-settings']['uo_timer_idle'] );
				learndash_update_setting( $post, 'uo_timer_idle', $auto_complete_setting_value );
			}
		}

		return $settings_field_updates;
	}

	/**
	 * Add minimum course time to LeearnDash Options Meta Box
	 *
	 * @param $setting_option_fields
	 * @param $settings_metabox_key
	 *
	 * @return mixed
	 */
	public static function add_minimum_course_time_to_post_args( $setting_option_fields, $settings_metabox_key ) {

		if ( 'learndash-quiz-display-content-settings' === $settings_metabox_key ) {
			global $post;

			// add option on LD post type settings meta box

			if ( $post instanceof \WP_Post && in_array( $post->post_type, self::$timed_post_types ) ) {

				$learndash_post_settings = learndash_get_setting( $post, null );

				$value = '';
				if ( isset( $learndash_post_settings['minimum_course_time_before_quiz_access'] ) ) {
					if ( ! empty( $learndash_post_settings['minimum_course_time_before_quiz_access'] ) ) {
						$value = $learndash_post_settings['minimum_course_time_before_quiz_access'];
					}
				}

				$setting_option_fields['minimum_course_time_before_quiz_access'] = [
					'name'      => 'minimum_course_time_before_quiz_access',
					'label'     => esc_attr__( 'Minimum Course Time (in minutes)', 'uncanny-pro-toolkit' ),
					'type'      => 'text',
					'help_text' => esc_attr__( 'Only allow access to the quiz when the user\'s time spent in the course EQUALS OR EXCEEDS this value in minutes.', 'uncanny-pro-toolkit' ),

					'default' => '0',
					'value'   => $value,
				];
			}
		}

		return $setting_option_fields;

	}

	/**
	 * Save post metadata when a post is saved.
	 *
	 * @param int $post_id The post ID.
	 * @param post $post The post object.
	 * @param bool $update Whether this is an existing post being updated or
	 *     not.
	 */
	public static function save_minimum_course_time_custom_meta( $settings_field_updates, $settings_metabox_key, $settings_screen_id ) {

		global $post;

		if ( 'learndash-quiz-display-content-settings' === $settings_metabox_key ) {
			// - Update the post's metadata. Nonce already verified by LearnDash
			if (
				filter_has_var( INPUT_POST, 'learndash-quiz-display-content-settings' ) &&
				isset( $_POST['learndash-quiz-display-content-settings']['minimum_course_time_before_quiz_access'] )
			) {
				$auto_complete_setting_value = sanitize_text_field( $_POST['learndash-quiz-display-content-settings']['minimum_course_time_before_quiz_access'] );
				learndash_update_setting( $post, 'minimum_course_time_before_quiz_access', $auto_complete_setting_value );
			}
		}

		return $settings_field_updates;
	}

	/*
	 * Get the amount of time it took to complete a topic
	 *
	 * @param object $post_object A WP post object
	 * @param int $user_ID WP user ID
	 *
	 * return string Time spent on a topic formatted seconds
	 */

	/**
	 * @param $attributes
	 *
	 * @return mixed|string|null
	 */
	public static function shortcode_uo_time_topic( $attributes ) {

		$request = shortcode_atts( array(
			'user-id'  => '',
			'topic-id' => '',
		), $attributes );

		if ( '' === $request['user-id'] ) {
			$request['user-id'] = get_current_user_id();
		}

		$user_ID = absint( $request['user-id'] );
		$post_ID = absint( $request['topic-id'] );

		$topic_post = null;
		if ( '' == $request['topic-id'] ) {
			global $post;

			if ( ( ! is_404() && ! is_archive() && is_a( $post, 'WP_Post' ) ) ) {
				if ( 'sfwd-topic' == $post->post_type ) {
					$topic_post = $post;
				}
			}

		} elseif ( $post_ID ) {
			$requested_post = get_post( $post_ID );

			if ( is_a( $requested_post, 'WP_Post' ) ) {
				if ( 'sfwd-topic' == $requested_post->post_type ) {
					$topic_post = $requested_post;
				}
			}
		}

		if ( null !== $topic_post && $user_ID > 0 ) {
			$raw_time      = self::get_topic_time( $topic_post, $user_ID );
			$uo_time_topic = self::convert_second_to_time( $raw_time );

			return apply_filters( 'uo_time_topic', $uo_time_topic, $raw_time, $topic_post, $user_ID );
		}

		return '';
	}

	/**
	 * @param $topic_post
	 * @param $user_ID
	 *
	 * @return int|mixed|string|null
	 */
	protected static function get_topic_time( $topic_post, $user_ID ) {

		$uo_time = '';

		if ( is_a( $topic_post, 'WP_Post' ) ) {

			$uo_time = (int) self::get_uo_time( $topic_post, $user_ID, true );

			$topic_id  = $topic_post->ID;
			$course_id = learndash_get_course_id( $topic_id );
			$quizzes   = learndash_get_lesson_quiz_list( $topic_id, null, $course_id );
			if ( ! empty( $quizzes ) ) {
				foreach ( $quizzes as $quiz ) {
					$quiz_time = self::get_uo_time( $quiz['post'], $user_ID, true );
					if ( '' !== $quiz_time ) {
						$uo_time = $uo_time + $quiz_time;
					}
				}
			}
		}

		return $uo_time;
	}

	/*
	 * Get the amount of time it took to complete a lesson
	 *
	 * @param object $post_object A WP post object
	 * @param int $user_ID WP user ID
	 *
	 * return string Time spent on a lesson formatted seconds
	 */

	/**
	 * @param $attributes
	 *
	 * @return mixed|string|null
	 */
	public static function shortcode_uo_time_lesson( $attributes ) {

		$request = shortcode_atts( array(
			'user-id'   => '',
			'lesson-id' => '',
		), $attributes );

		if ( '' === $request['user-id'] ) {
			$request['user-id'] = get_current_user_id();
		}

		$user_ID     = absint( $request['user-id'] );
		$post_ID     = absint( $request['lesson-id'] );
		$raw_time    = '';
		$lesson_post = 0;
		if ( '' == $request['lesson-id'] ) {
			global $post;

			if ( ( ! is_404() && ! is_archive() && is_a( $post, 'WP_Post' ) ) ) {
				if ( 'sfwd-lessons' == $post->post_type ) {
					$lesson_post = $post;
				}
			}

		} elseif ( $post_ID ) {
			$requested_post = get_post( $post_ID );

			if ( is_a( $requested_post, 'WP_Post' ) ) {
				if ( 'sfwd-lessons' == $requested_post->post_type ) {
					$lesson_post = $requested_post;
				}
			}
		}

		if ( is_a( $lesson_post, 'WP_Post' ) && $user_ID > 0 ) {

			$raw_time = (int) self::get_uo_time( $lesson_post, $user_ID, true );

			$course_id = learndash_get_course_id( $lesson_post->ID );

			$quizzes = learndash_get_lesson_quiz_list( $lesson_post, null, $course_id );

			if ( ! empty( $quizzes ) ) {
				foreach ( $quizzes as $quiz ) {
					if ( is_a( $quiz['post'], 'WP_Post' ) ) {
						$quiz_time = self::get_uo_time( $quiz['post'], $user_ID, true );
						if ( '' !== $quiz_time ) {
							$raw_time = $raw_time + $quiz_time;
						}
					}
				}
			}

			$topics = learndash_course_get_topics(
				$course_id,
				$lesson_post->ID,
				array(
					'return_type' => 'WP_Post',
					'per_page'    => 0,
				)
			);

			if ( ! empty( $topics ) ) {
				foreach ( $topics as $topic ) {
					if ( is_a( $topic, 'WP_Post' ) ) {
						$topic_time = self::get_topic_time( $topic, $user_ID );
						if ( '' !== $topic_time ) {
							$raw_time = $raw_time + $topic_time;
						}
					}
				}
			}

			$uo_time_lesson = self::convert_second_to_time( $raw_time );

			return apply_filters( 'uo_time_lesson', $uo_time_lesson, $raw_time, $lesson_post, $user_ID );
		}

		return $raw_time;
	}

	/*
	 * Get the amount of time it took to complete a lesson
	 *
	 * @param object $post_object A WP post object
	 * @param int $user_ID WP user ID
	 *
	 * return string Time spent on a quiz formatted seconds
	 */

	/**
	 * @param $attributes
	 *
	 * @return mixed|string|null
	 */
	public static function shortcode_uo_time_quiz( $attributes ) {

		$request = shortcode_atts( array(
			'user-id' => '',
			'quiz-id' => '',
		), $attributes );

		if ( '' === $request['user-id'] ) {
			$request['user-id'] = get_current_user_id();
		}

		$user_ID = absint( $request['user-id'] );
		$post_ID = absint( $request['quiz-id'] );

		$quiz_post = null;

		if ( '' == $request['quiz-id'] ) {
			global $post;

			if ( ( ! is_404() && ! is_archive() && is_a( $post, 'WP_Post' ) ) ) {
				if ( 'sfwd-quiz' == $post->post_type ) {
					$quiz_post = $post;
				}
			}

		} elseif ( $post_ID ) {
			$requested_post = get_post( $post_ID );

			if ( is_a( $requested_post, 'WP_Post' ) ) {
				if ( 'sfwd-quiz' == $requested_post->post_type ) {
					$quiz_post = $requested_post;
				}
			}
		}

		if ( null !== $quiz_post && $user_ID > 0 ) {
			return apply_filters( 'uo_time_quiz', self::get_uo_time( $quiz_post, $user_ID ), $quiz_post, $user_ID );
		}

		return '';
	}

	/*
	 * Get the amount of total the user has spent across ALL courses on the site.
	 *
	 * @param object $post_object A WP post object
	 * @param int $user_ID WP user ID
	 *
	 * return string Time spent by user across everything.
	 */

	/**
	 * @param $attributes
	 *
	 * @return mixed|string|null
	 */
	public static function shortcode_uo_time_total( $attributes ) {

		$request = shortcode_atts( array(
			'user-id' => '',
		), $attributes );

		if ( '' === $request['user-id'] ) {
			$request['user-id'] = get_current_user_id();
		}

		$user_ID = absint( $request['user-id'] );
		if ( $user_ID > 0 ) {
			return apply_filters( 'uo_user_time_total', self::get_user_time_in_seconds( $user_ID ), $user_ID );
		} else {
			return '';
		}
	}

	/**
	 * @param $user_ID
	 *
	 * @return string
	 */
	private static function get_user_time_in_seconds( $user_ID ) {

		global $wpdb;

		$timer_results = '';

		if ( $user_ID > 0 ) {
			$meta_key      = 'uo_timer_%';
			$query_results = $wpdb->prepare( "SELECT SUM( meta_value ) FROM $wpdb->usermeta WHERE meta_key LIKE %s AND user_id = %d", $meta_key, $user_ID );
			$timer_results = $wpdb->get_var( $query_results );

			return self::convert_second_to_time( $timer_results );
		}

		return $timer_results;

	}

	public static function maybe_logout_user() {
		check_ajax_referer( 'uo_course_timer_logout_nonce', 'nonce' );

		$course_id = isset($_POST['courseID']) ? absint($_POST['courseID']) : 0;
		$do_logout = apply_filters( 'uo_course_timer_logout', true, $course_id );

		if( true === $do_logout && is_user_logged_in() && ! current_user_can( apply_filters('uo_time_enable_logout_mode_capability', 'manage_options', $course_id) ) ) {
			remove_all_actions( 'wp_logout' );
			wp_logout();
		}

		echo "1";
		exit;
	}

}
