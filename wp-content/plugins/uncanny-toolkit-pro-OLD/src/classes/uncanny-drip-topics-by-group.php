<?php

namespace uncanny_pro_toolkit;

use DateTime;
use DateTimeZone;
use Exception;
use LearnDash_Settings_Section;
use LearnDash_Theme_Register;
use SFWD_LMS;
use uncanny_learndash_toolkit as toolkit;
use WP_Error;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class UncannyDripTopicsByGroup
 *
 * @package uncanny_pro_toolkit
 */
class UncannyDripTopicsByGroup extends toolkit\Config implements toolkit\RequiredFunctions {

	/**
	 * @var string[]
	 */
	public static $learndash_post_types = array( 'sfwd-topic' );

	/**
	 * @var string
	 */
	public static $access_metabox_key = 'learndash-topic-access-settings';

	/**
	 * @var string
	 */
	public static $hook_name = 'uo_learndash_notifications_drip_group_topic';

	/**
	 * @var string
	 */
	public static $trigger_name = 'group_topic_available';

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( __CLASS__, 'run_frontend_hooks' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'rest_api_init' ) );
	}

	/*
	 * Initialize frontend actions and filters
	 */
	/**
	 *
	 */
	public static function run_frontend_hooks() {
		if ( true === self::dependants_exist() ) {

			self::run_backwards_compatibility_update();

			add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_enqueue_scripts_func' ] );
			add_action( 'admin_enqueue_scripts', [ __CLASS__, 'add_notifications_strings' ], 11 );

			// Legacy - group access settings
			add_filter( 'learndash_post_args', array( __CLASS__, 'add_group_access_to_post_args_legacy' ) );

			// 3.0+ - Save custom topic settings field
			add_filter( 'learndash_metabox_save_fields', array( __CLASS__, 'save_topic_custom_meta' ), 60, 3 );

			# Change again when the option is called on "Edit Topic" page
			add_filter( 'sfwd-topic_display_settings', array( __CLASS__, 'change_topic_setting' ) );

			# Change shortcodes and hooks to show the topic because there is no hooking point to control it, so I change entire screen
			add_action( 'after_setup_theme', array( __CLASS__, 'change_hooks_and_shortcodes' ), 1 );

			#Convert String DateTime to UnixTimeStamp
			add_action( 'admin_init', array( __CLASS__, 'reformat_date_to_unix' ), 999 );

			// Add filter for LD Notifications
			add_filter( 'ld_lesson_access_from', array( __CLASS__, 'ld_topic_access_from_func' ), 999999, 3 );

			include dirname( dirname( __FILE__ ) ) . '/includes/learn-dash-settings-metabox-topic-group-drip-settings.php';
			add_filter( 'learndash_header_tab_menu', [ __CLASS__, 'learndash_header_tab_menu_custom' ], 999, 3 );

			//////
			/// Addition to make group drip more consistent!
			///

			//add_action( 'ld_removed_group_access', array( __CLASS__, 'group_access_removed' ), 200, 2 );
			//add_action( 'ld_added_group_access', array( __CLASS__, 'group_access_added' ), 200, 2 );


			// LearnDash Notifications 1.5+ changes
			//add_action( 'save_post', array( __CLASS__, 'save_course_id_topic_id_notifications' ), 99, 1 );
			//add_action( self::$hook_name, array( __CLASS__, 'handle_cron' ), 99, 4 );

			//add_filter( 'learndash_notifications_triggers', array( __CLASS__, 'add_group_topic_available' ), 99, 1 );
			// add_filter( 'learndash_notifications_admin_scripts', array(
			// 		__CLASS__,
			// 		'group_topic_available_strings',
			// ), 99, 1 );
			// add_filter( 'learndash_notification_settings', array(
			// 		__CLASS__,
			// 		'group_topic_available_settings',
			// ), 99, 1 );
			// add_filter( 'learndash_notifications_shortcodes_instructions', array(
			// 		__CLASS__,
			// 		'group_topic_available_shortcodes',
			// ), 99, 1 );

		}

	}

	/**
	 * Does the plugin rely on another function or plugin
	 *
	 * @return boolean || string Return either true or name of function or plugin
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
	 * Description of class in Admin View
	 *
	 * @return array
	 */
	public static function get_details() {
		$module_id         = 'drip-topics-by-group';
		$class_title       = esc_html__( 'Drip Topics by Group', 'uncanny-pro-toolkit' );
		$kb_link           = 'http://www.uncannyowl.com/knowledge-base/drip-topics-by-ldgroup/';
		$class_description = esc_html__( 'Unlock access to LearnDash topics by setting dates for LearnDash Groups rather than for all enrolled users.', 'uncanny-pro-toolkit' );
		$class_icon        = '<i class="uo_icon_pro_fa uo_icon_fa fa fa-user-times"></i><span class="uo_pro_text">PRO</span>';
		$category          = 'learndash';
		$type              = 'pro';

		return array(
			'id'               => $module_id,
			'title'            => $class_title,
			'type'             => $type,
			'category'         => $category,
			'kb_link'          => $kb_link,
			'description'      => $class_description,
			'dependants_exist' => self::dependants_exist(),
			'settings'         => false, // OR
			'icon'             => $class_icon,
		);

	}

	/**
	 *
	 */
	public static function run_backwards_compatibility_update() {
		// script only run once, check option
		$run_once = get_option( 'uo_drip_compatibility', 'no' );


		if ( 'yes' !== $run_once ) {

			// Get all topic post types
			$post_list = get_posts( array(
					'numberposts' => - 1,
					'post_type'   => 'sfwd-topic',
			) );

			// loop through post types
			foreach ( $post_list as $post ) {

				// script done, only run once, set option
				$all_other_users_date = get_post_meta( $post->ID, stripslashes( __CLASS__ ) . '-all', true );

				if ( $all_other_users_date ) {

					// We need to change the data to a timestamp if a array is returned
					if ( is_array( $all_other_users_date ) ) {
						$all_other_users_date = self::reformat_date( $all_other_users_date );
						$all_other_users_date = learndash_adjust_date_time_display( $all_other_users_date );
					}

					if ( self::is_timestamp( $all_other_users_date ) ) {

						// Get the topics options
						$original_option = get_post_meta( $post->ID, '_sfwd-topic', true );

						// Set the native drip date to the all users drip date since that will be the default and we are not using the -all users custom meta data anymore
						$original_option['sfwd-topic_visible_after_specific_date'] = $all_other_users_date;

						delete_post_meta( $post->ID, stripslashes( __CLASS__ ) . '-all' );
						update_post_meta( $post->ID, '_sfwd-topic', $original_option );
					}
				}
			}

			// script done, only run once, set option
			update_option( 'uo_drip_compatibility', 'yes', true );
		}
	}

	/**
	 * @param $date
	 *
	 * @return array|false|int
	 */
	public static function reformat_date( $date ) {
		if ( is_array( $date ) ) {
			if ( isset( $date['aa'] ) ) {
				$date['aa'] = intval( $date['aa'] );
			} else {
				$date['aa'] = 0;
			}

			if ( isset( $date['mm'] ) ) {
				$date['mm'] = intval( $date['mm'] );
			} else {
				$date['mm'] = 0;
			}

			if ( isset( $date['jj'] ) ) {
				$date['jj'] = intval( $date['jj'] );
			} else {
				$date['jj'] = 0;
			}

			if ( isset( $date['hh'] ) ) {
				$date['hh'] = intval( $date['hh'] );
			} else {
				$date['hh'] = 0;
			}

			if ( isset( $date['mn'] ) ) {
				$date['mn'] = intval( $date['mn'] );
			} else {
				$date['mn'] = 0;
			}

			if ( ( ! empty( $date['aa'] ) ) && ( ! empty( $date['mm'] ) ) && ( ! empty( $date['jj'] ) ) ) {

				$date_string = sprintf( '%04d-%02d-%02d %02d:%02d:00', intval( $date['aa'] ), intval( $date['mm'] ), intval( $date['jj'] ), intval( $date['hh'] ), intval( $date['mn'] ) );
				$gmt_offset  = get_option( 'gmt_offset' );
				if ( empty( $gmt_offset ) ) {
					$gmt_offset = 0;
				}

				return strtotime( get_gmt_from_date( date( 'Y-m-d H:i:s', strtotime( $date_string ) ), 'Y-m-d H:i:s' ) );

				//get ms difference for time offset from GMT
				//could be +ve or -ve depending on timezone
				//If GMT offset is +ve, subtract from time to get time in GMT since user is ahead of GMT
				//If GMT offset is -ve, add time to get GMT time since user is behind GMT
				//-1 is the logic to add/subtract offset time to implement above two line logic
				//$offset      = ( $gmt_offset * ( 60 * 60 ) ) * - 1; //MS difference for time offset
				//$local_dt = new \DateTime();
				//$local_dt->setTimezone( wp_timezone() );
				//$offset        = $local_dt->getOffset();
				//$offset        = $offset * - 1;
				//$gmt_date_time = new \DateTime( $date_string );
				//$gmt_date_time->setTimezone( new DateTimeZone( 'UTC' ) );
				//$return_time = $gmt_date_time->getTimestamp() + $offset;

				//return $return_time;
			} else {
				return 0;
			}
		} else {
			return $date;
		}
	}

	/**
	 * @param $timestamp
	 *
	 * @return bool
	 */
	public static function is_timestamp( $timestamp ) {
		if ( is_numeric( $timestamp ) && strtotime( date( 'd-m-Y H:i:s', $timestamp ) ) === (int) $timestamp ) {
			return $timestamp;
		} else {
			return false;
		}
	}

	/**
	 *
	 */
	public static function rest_api_init() {

		// Call to store date in DB
		register_rest_route( 'uo_pro/v1', '/update_drip_date_topic/', array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'update_drip_date_topic' ),
				'permission_callback' => function () {
					if ( current_user_can( 'edit_posts' ) ) {
						return true;
					}

					return new WP_Error( 'rest_forbidden', esc_attr__( 'You are not allowed to modify drip date.', 'uncanny-pro-toolkit' ) );

				},
		) );

		// Call to fetch course IDs of a Group
		register_rest_route( 'uo_pro/v1', '/get_group_courses/', array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'get_group_courses' ),
				'permission_callback' => function () {
					if ( current_user_can( 'edit_posts' ) ) {
						return true;
					}

					return new WP_Error( 'rest_forbidden', esc_attr__( 'You are not allowed to modify drip date.', 'uncanny-pro-toolkit' ) );

				},
		) );

	}

	/**
	 * @param $data
	 */
	public static function get_group_courses( $data ) {
		if ( ! isset( $data['group_id'] ) || empty( $data['group_id'] ) ) {
			wp_send_json_error( [
					'success' => false,
					'message' => __( 'Please select a group', 'uncanny-pro-toolkit' ),
				]
			);
		}

		$courses  = array();
		$group_id = 'all' === esc_attr( $data['group_id'] ) ? 'all' : absint( $data['group_id'] );
		if ( 'all' === $group_id ) {
			$posts = get_posts( array(
					'post_type'      => 'sfwd-courses',
					'posts_per_page' => 9999,
					'post_status'    => 'publish',
					'orderby'        => 'post_title',
					'order'          => 'ASC',
			) );
			if ( $posts ) {
				foreach ( $posts as $post ) {
					$courses[ $post->ID ] = $post->post_title;
				}
			}
			wp_send_json_success( $courses );
			die();
		}
		$group_course_ids = learndash_group_enrolled_courses( $group_id );

		if ( $group_course_ids ) {
			foreach ( $group_course_ids as $course_id ) {
				$courses[ $course_id ] = get_the_title( $course_id );
			}
		}

		wp_send_json_success( $courses );
		die();
	}

	/**
	 * @param $data
	 *
	 * @throws Exception
	 */
	public static function update_drip_date_topic( $data ) {
		// validate inputs
		$course_id = absint( $data['course_id'] );
		$group_id  = absint( $data['group_id'] );
		$post_id   = absint( $data['post_id'] );
		$action    = $data['action'];

		if ( ! empty( $group_id ) && ! empty( $post_id ) ) {
			if ( 'remove' === (string) $action ) {
				delete_post_meta( $post_id, stripslashes( __CLASS__ ) . '-' . $group_id );
				self::unset_notifications( $post_id, $course_id, $group_id );

				wp_send_json_success( [ 'success' => true ] );
				die();
			} else {

				$month  = absint( $data['month'] );
				$day    = absint( $data['day'] );
				$year   = absint( $data['year'] );
				$hour   = absint( $data['hour'] );
				$minute = absint( $data['minute'] );

				if ( 0 === $month || 0 === $day || 0 === $year ) {
					wp_send_json_error( [
							'success' => false,
							'message' => __( 'Invalid date', 'uncanny-pro-toolkit' ),
						]
					);
				}

				// Format to test agianst
				$format = 'Y-m-d H:i:s';

				// Add leading zero to single digit dates
				$_month  = str_pad( (string) $month, 2, '0', STR_PAD_LEFT );
				$_day    = str_pad( (string) $day, 2, '0', STR_PAD_LEFT );
				$_hour   = str_pad( (string) $hour, 2, '0', STR_PAD_LEFT );
				$_minute = str_pad( (string) $minute, 2, '0', STR_PAD_LEFT );

				$formatted_date = (string) $year . '-' . $_month . '-' . $_day . ' ' . $_hour . ':' . $_minute . ':00';
				$_date          = DateTime::createFromFormat( $format, $formatted_date );

				// Check if the date is valid
				if ( $_date && $_date->format( $format ) === $formatted_date ) {
					$complete_date = [
						'aa' => $year,
						'mm' => $month,
						'jj' => $day,
						'hh' => $hour,
						'mn' => $minute,
					];

					$date = self::reformat_date( $complete_date );

					update_post_meta( $post_id, stripslashes( __CLASS__ ) . '-' . $group_id, $date );
					self::set_notifications( $post_id, $course_id, $group_id, $date );

					wp_send_json_success( [ 'success' => true ] );
					die();
				} else {
					wp_send_json_error( [
							'success' => false,
							'message' => __( 'Invalid date', 'uncanny-pro-toolkit' ),
						]
					);
				}
			}
		}

		wp_send_json_error( [
				'success' => false,
				'message' => __( 'Data not received', 'uncanny-pro-toolkit' ),
			]
		);
		die();
	}

	/**
	 * @param     $topic_id
	 *
	 * @param int $course_id
	 * @param int $group_id
	 */
	public static function unset_notifications( $topic_id, $course_id = 0, $group_id = 0 ) {
		if ( ! class_exists( 'LearnDash_Notifications' ) ) {
			return;
		}

		if ( empty( $course_id ) || 0 === $course_id ) {
			$course_id = learndash_get_course_id( $topic_id );
			//Logic for course builder
			if ( 0 === (int) $course_id && ( isset( $_REQUEST['course_id'] ) ) && ( ! empty( $_REQUEST['course_id'] ) ) ) {
				$course_id = intval( filter_input( INPUT_GET, 'course_id' ) );
			}

			if ( 0 === (int) $course_id && isset( $_REQUEST['ld-course-switcher'] ) ) {
				preg_match( "/course_id=[^&]*/", $_REQUEST['ld-course-switcher'], $parse_query );
				if ( $parse_query ) {
					$course_id = (int) str_replace( 'course_id=', '', $parse_query[0] );
				}
			}
		}

		$users = learndash_get_groups_user_ids( $group_id );
		if ( ! $users ) {
			return;
		}

		foreach ( $users as $user_id ) {
			self::delete_delayed_email_data( $user_id, $group_id, $course_id, $topic_id );
			delete_user_meta( $user_id, 'ld_sent_notification_topic_available_' . $topic_id );
			delete_user_meta( $user_id, 'uo_ld_sent_notification_topic_available_' . $topic_id );
		}

		// For 3.7+ Pro toolkit.
		$hook_name     = self::$hook_name;
		$notifications = self::get_raw_notifications( $group_id, $course_id, $topic_id );
		if ( ! $notifications ) {
			return;
		}
		self::notification_log( __( '====Remove notifications: Start====', 'uncanny-pro-toolkit' ) );
		foreach ( $notifications as $notification ) {
			if ( wp_next_scheduled( $hook_name, [ $notification->ID, $group_id, $course_id, $topic_id ] ) ) {
				wp_clear_scheduled_hook( $hook_name, [ $notification->ID, $group_id, $course_id, $topic_id ] );
				self::notification_log( sprintf( __( 'Notification: %d removed for Group: %d, Course: %d, Lesson: %d', 'uncanny-pro-toolkit' ), $notification->ID, $group_id, $course_id, $topic_id ) );
			}
		}
		self::notification_log( __( '====Remove notifications: End====', 'uncanny-pro-toolkit' ) );
	}

	/**
	 * @param        $topic_id
	 *
	 * @param int    $course_id
	 * @param int    $group_id
	 * @param string $topic_access_from
	 */
	public static function set_notifications( $topic_id, $course_id = 0, $group_id = 0, $topic_access_from = '' ) {
		if ( ! class_exists( 'LearnDash_Notifications' ) ) {
			return;
		}

		if ( empty( $topic_access_from ) ) {
			return;
		}

		if ( empty( $course_id ) || 0 === $course_id ) {
			$course_id = learndash_get_course_id( $topic_id );
			//Logic for course builder
			if ( 0 === (int) $course_id && ( isset( $_REQUEST['course_id'] ) ) && ( ! empty( $_REQUEST['course_id'] ) ) ) {
				$course_id = intval( filter_input( INPUT_GET, 'course_id' ) );
			}

			if ( 0 === (int) $course_id && isset( $_REQUEST['ld-course-switcher'] ) ) {
				preg_match( "/course_id=[^&]*/", $_REQUEST['ld-course-switcher'], $parse_query );
				if ( $parse_query ) {
					$course_id = (int) str_replace( 'course_id=', '', $parse_query[0] );
				}
			}
		}

		$users = learndash_get_groups_user_ids( $group_id );
		if ( ! $users ) {
			return;
		}

		foreach ( $users as $user_id ) {
			self::delete_delayed_email_data( $user_id, $group_id, $course_id, $topic_id );

			delete_user_meta( $user_id, 'ld_sent_notification_topic_available_' . $topic_id );
			delete_user_meta( $user_id, 'uo_ld_sent_notification_topic_available_' . $topic_id );
		}

		self::manually_set_notification( $group_id, $course_id, $topic_id, $topic_access_from );
	}

	/**
	 * @param $user_id
	 * @param $group_id
	 * @param $course_id
	 * @param $topic_id
	 */
	public static function delete_delayed_email_data( $user_id, $group_id, $course_id, $topic_id ) {
		learndash_notifications_delete_delayed_emails_by_multiple_shortcode_data_key( array(
				'user_id'  => $user_id,
				'topic_id' => $topic_id,
		) );

	}

	/**
	 * @param int    $topic_id
	 * @param int    $user_id
	 * @param bool   $course_id
	 * @param string $access_from
	 * @param bool   $return_timestamp
	 *
	 * @return bool|mixed|string
	 */
	private static function ld_topic_access_group( $topic_id, $user_id, $course_id = false, $access_from = '', $return_timestamp = false ) {
		if ( false === $course_id ) {
			$course_id = learndash_get_course_id( $topic_id );
		}
		$user_groups = learndash_get_users_group_ids( $user_id );

		//No group found, assumption: Available
		if ( empty( $user_groups ) ) {
			$default = get_post_meta( $topic_id, stripslashes( __CLASS__ ) . '-all', true );
			if ( ! empty( $default ) ) {
				if ( ! self::is_timestamp( $default ) ) {
					return strtotime( $default );
				}

				return $default;
			} else {

				return $access_from;
			}
		}

		$group_dates = array();

		$uncanny_toolkit_active_classes = get_option( 'uncanny_toolkit_active_classes', true );

		if ( in_array( 'uncanny_pro_toolkit\UncannyDripLessonsByGroup', $uncanny_toolkit_active_classes ) ) {
			foreach ( $user_groups as $group_id ) {
				$date = get_post_meta( $topic_id, 'uncanny_pro_toolkitUncannyDripLessonsByGroup' . '-' . $group_id, true );
				if ( ! empty( $date ) ) {
					if ( self::is_timestamp( $date ) ) {
						$group_dates[ $group_id ] = $date;
					} else {
						$group_dates[ $group_id ] = strtotime( $date );
					}
				}
			}
		}

		foreach ( $user_groups as $group_id ) {
			$date = get_post_meta( $topic_id, stripslashes( __CLASS__ ) . '-' . $group_id, true );
			if ( ! empty( $date ) ) {
				if ( self::is_timestamp( $date ) ) {
					$group_dates[ $group_id ] = $date;
				} else {
					$group_dates[ $group_id ] = strtotime( $date );
				}
			}
		}

		//Array contains Group Dates!
		asort( $group_dates );
		$gmt_date_time = new DateTime();
		$gmt_date_time->setTimezone( new DateTimeZone( 'GMT' ) );
		$time_now = strtotime( $gmt_date_time->format( 'Y-m-d H:i:s' ) );
		$return   = false;
		if ( ! empty( $group_dates ) ) {
			foreach ( $user_groups as $group_id ) {

				if ( ! empty( $group_dates[ $group_id ] ) && learndash_group_has_course( $group_id, $course_id ) ) {

					if ( absint( $time_now ) < absint( $group_dates[ $group_id ] ) ) {
						$return = false;
					} elseif ( absint( $time_now ) >= absint( $group_dates[ $group_id ] ) ) {

						$ld_access         = self::ld_topic_access_from_inherited_from_ld( $topic_id, $user_id, $course_id );
						$args              = array(
							'lesson_id'      => $topic_id,
							'course_id'      => $course_id,
							'ld_access_from' => $ld_access,
							'user_id'        => $user_id,
							'group_drip'     => $group_dates[ $group_id ],
						);
						$allow_ld_override = apply_filters( 'uo_drip_ignore_learndash_release_date', false, $args );
						if ( self::is_timestamp( $ld_access ) && false === $allow_ld_override ) {
							$return = $ld_access;
						} else {
							if ( $return_timestamp ) {
								return $group_dates[ $group_id ];
							}

							$return = __( 'Available', 'uncanny-pro-toolkit' );
						}
					}
				}
			}
		} else {
			//No Group Dates found
			$default = get_post_meta( $topic_id, stripslashes( __CLASS__ ) . '-all', true );
			if ( ! empty( $default ) ) {
				if ( ! self::is_timestamp( $default ) ) {
					return strtotime( $default );
				}

				return $default;
			}

			$ld_access = self::ld_topic_access_from_inherited_from_ld( $topic_id, $user_id, $course_id );
			if ( self::is_timestamp( $ld_access ) ) {
				$return = $ld_access;
			} else {
				$return = __( 'Available', 'uncanny-pro-toolkit' );
			}
		}

		if ( false === $return ) {
			foreach ( $group_dates as $group_id => $date ) {
				if ( learndash_group_has_course( $group_id, $course_id ) ) {
					return $date;
				}
			}
		}

		return $return;
	}

	/**
	 * Get timestamp of when user has access to topic
	 *
	 * @param int $topic_id
	 * @param int $user_id
	 *
	 * @return int  timestamp
	 * @since 2.1.0
	 *
	 */
	public static function ld_topic_access_from_inherited_from_ld( $topic_id, $user_id, $course_id = null ) {
		$return = null;

		if ( is_null( $course_id ) ) {
			$course_id = learndash_get_course_id( $topic_id );
		}

		$courses_access_from = ld_course_access_from( $course_id, $user_id );
		if ( empty( $courses_access_from ) ) {
			$courses_access_from = learndash_user_group_enrolled_to_course_from( $user_id, $course_id );
		}

		$visible_after = learndash_get_setting( $topic_id, 'visible_after' );
		if ( $visible_after > 0 ) {

			// Adjust the Course acces from by the number of days. Use abs() to ensure no negative days.
			$topic_access_from = $courses_access_from + abs( $visible_after ) * 24 * 60 * 60;
			$topic_access_from = apply_filters( 'ld_topic_access_from__visible_after', $topic_access_from, $topic_id, $user_id );

			$current_timestamp = time();
			if ( $current_timestamp < $topic_access_from ) {
				$return = $topic_access_from;
			}

		} else {
			$visible_after_specific_date = learndash_get_setting( $topic_id, 'visible_after_specific_date' );
			if ( ! empty( $visible_after_specific_date ) ) {
				if ( ! is_numeric( $visible_after_specific_date ) ) {
					// If we a non-numberic value like a date stamp Y-m-d hh:mm:ss we want to convert it to a GMT timestamp
					$visible_after_specific_date = learndash_get_timestamp_from_date_string( $visible_after_specific_date, true );
				}

				$current_time = time();

				if ( $current_time < $visible_after_specific_date ) {
					$return = apply_filters( 'ld_topic_access_from__visible_after_specific_date', $visible_after_specific_date, $topic_id, $user_id );
				}
			}
		}

		return $return;
	}


	# Change the shortcode

	/**
	 * @param $user_id
	 * @param $group_id
	 * @param $course_id
	 * @param $topic_id
	 * @param $topic_access_from
	 */
	public static function manually_set_notification( $group_id, $course_id, $topic_id, $topic_access_from ) {
		$notifications = self::get_raw_notifications( $group_id, $course_id, $topic_id );
		if ( ! $notifications ) {
			return;
		}
		self::notification_log( __( '====Add notifications: Start====', 'uncanny-pro-toolkit' ) );
		foreach ( $notifications as $notification ) {
			self::set_notification_schedule( $notification, $group_id, $course_id, $topic_id, $topic_access_from );
		}
		self::notification_log( __( '====Add notifications: End====', 'uncanny-pro-toolkit' ) );
	}

	/**
	 * @param $group_id
	 * @param $course_id
	 * @param $topic_id
	 *
	 * @return array
	 */
	public static function get_raw_notifications( $group_id, $course_id, $topic_id ) {
		$return        = array();
		$notifications = learndash_notifications_get_notifications( self::$trigger_name );
		if ( empty( $notifications ) ) {
			return $return;
		}
		foreach ( $notifications as $notification ) {
			// match course and topic ID before notification is set for the topic
			$n_group_id  = get_post_meta( $notification->ID, '_ld_notifications_group_id', true );
			$n_course_id = get_post_meta( $notification->ID, '_ld_notifications_course_id', true );
			$n_topic_id  = get_post_meta( $notification->ID, '_ld_notifications_topic_id', true );
			if ( absint( $group_id ) !== absint( $n_group_id ) && 'all' !== $n_group_id ) {
				// course ID is not matched.. continue
				continue;
			}
			if ( absint( $course_id ) !== absint( $n_course_id ) && 'all' !== $n_course_id ) {
				// course ID is not matched.. continue
				continue;
			}
			if ( absint( $topic_id ) !== absint( $n_topic_id ) && 'all' !== $n_topic_id ) {
				// topic ID is not matched.. continue
				continue;
			}
			$return[] = $notification;
		}

		return $return;
	}

	/**
	 * @param $notification
	 * @param $group_id
	 * @param $course_id
	 * @param $topic_id
	 * @param $topic_access_from
	 */
	public static function set_notification_schedule( $notification, $group_id, $course_id, $topic_id, $topic_access_from ) {

		// Get recipient
		$recipients = learndash_notifications_get_recipients( $notification->ID );

		// If notification doesn't have recipient, exit
		if ( empty( $recipients ) ) {
			return;
		}

		$gmt_date_time = new DateTime();
		$gmt_date_time->setTimezone( new DateTimeZone( 'UTC' ) );
		$current_time = $gmt_date_time->getTimestamp();
		$local_dt     = new \DateTime();
		$local_dt->setTimezone( wp_timezone() );
		$offset = $local_dt->getOffset();

		if ( isset( $topic_access_from ) && $topic_access_from > $current_time ) {

			$sent_on   = $topic_access_from;
			$hook_name = self::$hook_name;
			if ( wp_next_scheduled( $hook_name, [ $notification->ID, $group_id, $course_id, $topic_id ] ) ) {
				wp_clear_scheduled_hook( $hook_name, [ $notification->ID, $group_id, $course_id, $topic_id ] );
			}
			wp_schedule_single_event( $sent_on, $hook_name, [ $notification->ID, $group_id, $course_id, $topic_id ] );
			self::notification_log( sprintf( __( 'Notification: %d added for Group: %d, Course: %d, Lesson: %d to run at: %s', 'uncanny-pro-toolkit' ), $notification->ID, $group_id, $course_id, $topic_id, date_i18n( 'Y-m-d H:i:s', $topic_access_from + $offset ) ) );
		}

	}

	/**
	 *
	 */
	public static function admin_enqueue_scripts_func() {
		global $post;

		if ( empty( $post ) ) {
			return;
		}

		if ( 'sfwd-topic' === $post->post_type ) {
			wp_enqueue_style( 'dataTables', 'https://cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css' );
			wp_enqueue_script( 'dataTables', 'https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js', array( 'jquery' ), '1.0.0', true );
		}
	}

	/**
	 * @param $tabs
	 * @param $menu_tab_key
	 * @param $screen_post_type
	 *
	 * @return mixed
	 */
	public static function learndash_header_tab_menu_custom( $tabs, $menu_tab_key, $screen_post_type ) {

		if ( $tabs ) {
			foreach ( $tabs as $k => $tab ) {
				if ( 'sfwd-topic-settings' === $tab['id'] ) {
					$tabs[ $k ]['metaboxes'][] = 'learndash-topic-group-drip-settings';
					break;
				}
			}
		}

		return $tabs;
	}

	/**
	 * @param $access_from
	 * @param $topic_id
	 * @param $user_id
	 *
	 * @return bool|int|mixed|string
	 * @throws Exception
	 */
	public static function ld_topic_access_from_func( $access_from, $topic_id, $user_id ) {
		if ( ! is_admin() || wp_doing_ajax() ) {
			$course_id        = learndash_get_course_id( $topic_id );
			$has_group_access = learndash_user_group_enrolled_to_course( $user_id, $course_id );
			if ( $has_group_access ) {
				if ( is_object( $topic_id ) ) {
					$topic_id = $topic_id->ID;
				}

				$group_access = self::ld_topic_access_group( $topic_id, $user_id, $course_id, $access_from );


				if ( is_numeric( $group_access ) && $group_access >= time() ) {
					$access_from = $group_access;
				}
			}
		}

		return $access_from;
	}

	/**
	 * @param $time
	 *
	 * @return int
	 */
	public static function adjust_for_timezone_difference( $time ) {

		return strtotime( get_date_from_gmt( date( 'Y-m-d H:i:s', $time ) ) );

		//$gmt_offset = get_option( 'gmt_offset' );
		//if ( empty( $gmt_offset ) ) {
		//$gmt_offset = 0;
		//}
		//get ms difference for time offset from GMT
		//could be +ve of -ve depending on timezone
		//$offset = $gmt_offset * ( 60 * 60 );

		//return (int) $time + $offset;
	}

	/**
	 * Save post metadata when a post is saved.
	 *
	 * @param $settings_field_updates
	 * @param $settings_metabox_key
	 * @param $settings_screen_id
	 *
	 * @return mixed
	 */
	public static function save_topic_custom_meta( $settings_field_updates, $settings_metabox_key, $settings_screen_id ) {

		global $post;

		if ( self::$access_metabox_key === $settings_metabox_key ) {

			// - Update the post's metadata. Nonce already verified by LearnDash
			if (
				filter_has_var( INPUT_POST, 'learndash-topic-access-settings' ) &&
				isset( $_POST['learndash-topic-access-settings']['set_groups_for_dates'] )
			) {
				// if group was set, save it
				if ( isset( $_POST['learndash-topic-access-settings']['set_groups_for_dates'] ) ) {

					$group_id = absint( $_POST['learndash-topic-access-settings']['set_groups_for_dates'] );
					if ( ! empty( $group_id ) ) {

						$date      = self::reformat_date( $_POST['learndash-topic-access-settings']['visible_after_specific_date'] );
						$course_id = filter_has_var( INPUT_GET, 'course_id' ) ? absint( filter_input( INPUT_GET, 'course_id' ) ) : learndash_get_course_id( $post->ID );

						if ( 0 === $date ) {
							delete_post_meta( $post->ID, stripslashes( __CLASS__ ) . '-' . $group_id );
							self::unset_notifications( $post->ID, $course_id, $group_id );
						} else {
							update_post_meta( $post->ID, stripslashes( __CLASS__ ) . '-' . $group_id, $date );
							self::set_notifications( $post->ID, $course_id, $group_id, $date );
						}
					}
				}
			}

			// get original options and reset it
			$original_option                                    = get_post_meta( $post->ID, '_sfwd-topic', true );
			$original_date                                      = get_post_meta( $post->ID, stripslashes( __CLASS__ ) . '-all', true );
			$original_option['sfwd-topic_set_groups_for_dates'] = '';
			$original_option['sfwd-topic_visible_after_specific_date'] = $original_date;

			update_post_meta( $post->ID, '_sfwd-topic', $original_option );
		}

		return $settings_field_updates;

	}

	/**
	 *
	 */
	public static function change_hooks_and_shortcodes() {
		# Replace the function
		remove_filter( 'learndash_content', 'topic_visible_after', 1 );
		add_filter( 'learndash_content', array( __CLASS__, 'topic_visible_after' ), 999, 2 );

		if ( defined( 'LEARNDASH_ELEMENTOR_VERSION' ) ) {
			add_filter( 'the_content', array( __CLASS__, 'topic_visible_the_content' ), 99, 1 );
		}
		//add_filter( 'learndash_template', array( __CLASS__, 'learndash_template' ), 1, 5 );
	}

	/**
	 * @param $post_args
	 *
	 * @return array
	 */
	public static function add_group_access_to_post_args_legacy( $post_args ) {

		if ( class_exists( 'LearnDash_Theme_Register' ) ) {
			return $post_args;
		}

		// Get all groups
		if ( ! is_user_logged_in() ) {
			return $post_args;
		}

		$groups = get_posts( [
				'post_type'      => 'groups',
				'posts_per_page' => 999,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
		] );


		// If any group is not exists, this option will be disabled
		if ( ! $groups ) {
			return $post_args;
		}

		// group_selection
		$group_selection = array(
			0     => 'Select a LearnDash Group',
			'all' => 'All Other Users',
		);

		# TODO Show only groups that have access to this topic
		# Current code is inefficient and will have issues when a lot of groups are set up
		# try recursive to get courses of the topics and then groups of the courses

		foreach ( $groups as $group ) {
			if ( $group && is_object( $group ) ) {
				$group_selection[ $group->ID ] = $group->post_title;
			}
		}

		$new_post_args = array();


		foreach ( $post_args as $key => $val ) {
			// add option on topics setting
			if ( in_array( $val['post_type'], self::$learndash_post_types, true ) ) {
				$new_post_args[ $key ]           = $val;
				$new_post_args[ $key ]['fields'] = array();

				foreach ( $post_args[ $key ]['fields'] as $key_topics => $val_topics ) {
					$new_post_args[ $key ]['fields'][ $key_topics ] = $val_topics;

					if ( 'visible_after' === $key_topics ) {
						$new_post_args[ $key ]['fields']['set_groups_for_dates'] = array(
							'name'            => 'LearnDash Group',
							'type'            => 'select',
							'help_text'       => 'Choose a group for a custom drip date',
							'initial_options' => $group_selection,
						);
					}
				}
			} else {
				$new_post_args[ $key ] = $val;
			}
		}

		return $new_post_args;
	}

	# Change the template as one in template dir of this plugin

	/**
	 * @param $setting
	 *
	 * @return mixed
	 */
	public static function change_topic_setting( $setting ) {
		// Get the post which are modifying
		global $post;

		foreach ( $setting['sfwd-topic_set_groups_for_dates']['initial_options'] as $group_id => &$group_name ) {

			if ( ! $group_id ) {
				continue;
			}
			$date = get_post_meta( $post->ID, stripslashes( __CLASS__ ) . '-' . $group_id, true );
			// Add tha ( date ) after group name on selection if exists

			if ( $date ) {
				if ( is_array( $date ) ) {
					$date = self::reformat_date( $date );
					$date = learndash_adjust_date_time_display( $date );
				}
				if ( self::is_timestamp( $date ) ) {
					$date_format = get_option( 'date_format' );
					$time_format = get_option( 'time_format' );
					$date        = self::adjust_for_timezone_difference( $date );
					$date        = date_i18n( "$date_format $time_format", $date );
				}
				$group_name = $group_name . ' &mdash; (' . $date . ')';
			}
		}

		return $setting;
	}

	# Access Permission for user's group

	/**
	 * @param $filepath
	 * @param $name
	 * @param $args
	 * @param $echo
	 * @param $return_file_path
	 *
	 * @return string
	 */
	public static function learndash_template( $filepath, $name, $args, $echo, $return_file_path ) {

		if ( 'course' === $name ) {
			if ( ! class_exists( 'LearnDash_Theme_Register' ) ||
				 (
					 class_exists( 'LearnDash_Theme_Register' ) &&
					 'legacy' === LearnDash_Theme_Register::get_active_theme_key()
				 )
			) {
				$filepath = self::get_template( 'drip-topic-template_legacy.php', dirname( dirname( __FILE__ ) ) . '/src' );
				$filepath = apply_filters( 'uo_drip_template', $filepath );
			}


		}

		if ( 'learndash_course_topic_not_available' === $name ) {

			if ( ! class_exists( 'LearnDash_Theme_Register' ) ||
				 (
					 class_exists( 'LearnDash_Theme_Register' ) &&
					 'legacy' === LearnDash_Theme_Register::get_active_theme_key()
				 )
			) {
				$filepath = self::get_template( 'learndash_course_topic_not_available_legacy.php', dirname( dirname( __FILE__ ) ) . '/src' );
				$filepath = apply_filters( 'uo_learndash_course_topic_not_available', $filepath );

			}

		}

		return $filepath;
	}

	# It will use in the course template so I put this on here as public method

	/**
	 * @param $content
	 * @param $post
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function topic_visible_after( $content, $post ) {
		if ( empty( $post->post_type ) ) {
			return $content;
		}

		$user = wp_get_current_user();
		if ( in_array( 'administrator', $user->roles ) ) {
			return $content;
		}

		$uncanny_active_classes = get_option( 'uncanny_toolkit_active_classes', '' );
		if ( ! empty( $uncanny_active_classes ) ) {
			if ( key_exists( 'uncanny_pro_toolkit\GroupLeaderAccess', $uncanny_active_classes ) ) {
				$course_id         = learndash_get_course_id( $post->ID );
				$get_course_groups = learndash_get_course_groups( $course_id );
				$groups_of_leader  = learndash_get_administrators_group_ids( $user->ID );
				$matching          = array_intersect( $groups_of_leader, $get_course_groups );
				if ( in_array( 'group_leader', $user->roles ) && ! empty( $matching ) ) {
					return $content;
				}
			}
		}

		if ( 'sfwd-topic' === (string) $post->post_type ) {
			$topic_id = $post->ID;
		} elseif ( 'sfwd-quiz' === (string) $post->post_type || 'sfwd-lessons' === (string) $post->post_type ) {
			if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) ) {
				$course_id = learndash_get_course_id( $post );
				$topic_id  = learndash_course_get_single_parent_step( $course_id, $post->ID );
			} else {
				$topic_id = learndash_get_setting( $post, 'topic' );
			}
		} else {
			return $content;
		}

		if ( empty( $topic_id ) ) {
			return $content;
		}
		// Compare Two of Dates and return minimum value
		$topic_access_from = self::get_topic_access_from( $topic_id, $user->ID );
		if ( __( 'Available', 'uncanny-pro-toolkit' ) === (string) $topic_access_from || empty( $topic_access_from ) ) {
			return $content;
		}


		if ( $topic_access_from > time() ) {

			$course_id = learndash_get_course_id( $topic_id );
			$content   = SFWD_LMS::get_template(
				'learndash_course_lesson_not_available',
				array(
					'user_id'                 => $user->ID,
					'course_id'               => $course_id,
					'lesson_id'               => $topic_id,
					'lesson_access_from_int'  => $topic_access_from,
					'lesson_access_from_date' => learndash_adjust_date_time_display( $topic_access_from ),
					'context'                 => 'topic',
				),
				false
			);

			if ( $content ) {
				return $content;
			} else {
				$content     = self::learndash_topic_available_from_text( $content, get_post( $topic_id ), $topic_access_from ) . '<br><br>';
				$course_link = get_permalink( $course_id );
				$content    .= '<a href="' . esc_url( $course_link ) . '">' . esc_html__( 'Return to Course Overview', 'uncanny-pro-toolkit' ) . '</a>';

				return '<div class=\'notavailable_message\'>' . apply_filters( 'learndash_topic_available_from_text', $content, $post, $topic_access_from ) . '</div>';
			}
		}

		return $content;
	}

	/**
	 * @param $topic_id
	 * @param $user_id
	 *
	 * @return bool|int|mixed|string
	 * @throws Exception
	 */
	public static function get_topic_access_from( $topic_id, $user_id ) {
		// $topic_access_from = ld_topic_access_from( $topic_id, $user_id );
		// Check Group Access As Well
		$topic_access_group = self::ld_topic_access_group( $topic_id, $user_id );
		$return             = __( 'Available', 'uncanny-pro-toolkit' );
		if ( ! empty( $topic_access_group ) && __( 'Available', 'uncanny-pro-toolkit' ) !== (string) $topic_access_group ) {
			if ( $topic_access_group > time() ) {
				$return = $topic_access_group;
			}
		}

		// Compare Two of Them without null, and return maximum value
		// if ( ! empty( $topic_access_from ) ) {
		// $return = $topic_access_from;
		// }

		return $return;
	}

	/**
	 * @param $message
	 * @param $post
	 * @param $topic_access_from_int
	 *
	 * @return bool|int|mixed|string
	 */
	public static function learndash_topic_available_from_text( $message, $post, $topic_access_from_int ) {

		if ( ! is_admin() ) {
			if ( is_object( $post ) && isset( $post->post_type ) && 'sfwd-topic' === $post->post_type ) {
				if ( is_numeric( $topic_access_from_int ) && $topic_access_from_int >= time() ) {
					$access_from = $topic_access_from_int;
					$date_format = get_option( 'date_format' );
					$time_format = get_option( 'time_format' );
					$date        = self::adjust_for_timezone_difference( $access_from );
					$date        = date_i18n( "$date_format $time_format", $date );

					$message = sprintf( wp_kses_post( esc_attr__( '<span class="ld-display-label">Available on:</span> <span class="ld-display-date">%s</span>', 'learndash' ) ), $date );
				}
			}
		}

		return $message;
	}

	/**
	 *
	 */
	public static function reformat_date_to_unix() {
		if ( 'no' === get_option( 'group_drip_date_modified_to_unix', 'no' ) ) {
			global $wpdb;
			$groups = $wpdb->get_results( "SELECT * FROM {$wpdb->postmeta} WHERE meta_key LIKE '" . stripslashes( __CLASS__ ) . "%'" );
			// If any group is not exists, this option will be disabled
			if ( ! empty( $groups ) ) {
				// group_selection
				foreach ( $groups as $group ) {
					$post_id      = $group->post_id;
					$key          = $group->meta_key;
					$current_date = $group->meta_value;
					if ( ! empty( $current_date ) && 0 !== $current_date ) {
						if ( false === self::is_timestamp( $current_date ) ) {
							//attempt to convert to unix timestamp
							if ( is_array( maybe_unserialize( $current_date ) ) ) {
								$date_format  = get_option( 'date_format' );
								$time_format  = get_option( 'time_format' );
								$current_date = date( "$date_format $time_format", self::reformat_date( $current_date ) );
							}
							$unix_time = self::attempt_to_unix( $current_date );
							if ( false !== $unix_time ) {
								//DateTime was able to convert it to unix time, all good
								update_post_meta( $post_id, $key, $unix_time );
								$bak = str_replace( stripslashes( __CLASS__ ), 'bak-UncannyDripTopicsByGroup', $key );
								update_post_meta( $post_id, $bak, $current_date ); //keep a backup, Just-in-case
							}
						}
					}
				}
			}
			update_option( 'group_drip_date_modified_to_unix', 'yes' );
		}
	}

	/**
	 * @param $date
	 *
	 * @return bool
	 */
	public static function attempt_to_unix( $date ) {
		try {
			$date = new DateTime( $date );

			return $date->getTimestamp();
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Remove Group drip topic notification if user is removed from the group
	 * IF LearnDash notification is active
	 *
	 * @param $user_id
	 * @param $group_id
	 *
	 * @since  3.5.8
	 * @author Saad S.
	 */
//	public static function group_access_removed( $user_id, $group_id ) {
//		if ( ! class_exists( 'LearnDash_Notifications' ) ) {
//			return;
//		}
	//
//		$drips = self::get_drip_topics_by_group_id( $user_id, $group_id );
//		if ( empty( $drips ) ) {
//			return;
//		}
	//
//		foreach ( $drips as $topic_id => $drip_date ) {
//			$date = $drip_date['drip'];
//			if ( empty( $date ) ) {
//				continue;
//			}
	//
//			learndash_notifications_delete_delayed_emails_by_user_id_topic_id( $user_id, $topic_id );
//			delete_user_meta( $user_id, 'ld_sent_notification_topic_available_' . $topic_id );
//			delete_user_meta( $user_id, 'uo_ld_sent_notification_topic_available_' . $topic_id );
//		}
//	}


	/**
	 * Add Group drip topic notification if user is removed from the group
	 * IF LearnDash notification is active
	 *
	 * @param $user_id
	 * @param $group_id
	 *
	 * @since  3.5.8
	 * @author Saad S.
	 */
//	public static function group_access_added( $user_id, $group_id ) {
//		if ( ! class_exists( 'LearnDash_Notifications' ) ) {
//			return;
//		}
	//
//		$drips = self::get_drip_topics_by_group_id( $user_id, $group_id );
	//
//		if ( empty( $drips ) ) {
//			return;
//		}
	//
//		foreach ( $drips as $topic_id => $drip_date ) {
//			$topic_access_from = $drip_date['drip'];
	//
//			if ( empty( $topic_access_from ) ) {
//				continue;
//			}
	//
//			if ( time() > $topic_access_from ) {
//				continue;
//			}
	//
//			learndash_notifications_delete_delayed_emails_by_user_id_topic_id( $user_id, $topic_id );
	//
//			delete_user_meta( $user_id, 'ld_sent_notification_topic_available_' . $topic_id );
//			delete_user_meta( $user_id, 'uo_ld_sent_notification_topic_available_' . $topic_id );
//			$course_id = absint( $drip_date['course_id'] );
//			self::manually_set_notification( $user_id, $group_id, $course_id, $topic_id, $topic_access_from );
//			update_user_meta( $user_id, 'uo_ld_sent_notification_topic_available_' . $topic_id, $topic_access_from );
//		}
//	}

	/**
	 * @param $user_id
	 * @param $group_id
	 *
	 * @return array
	 */
	public static function get_drip_topics_by_group_id( $user_id, $group_id ) {
		$drip_topics   = array();
		$group_courses = learndash_get_group_courses_list( $group_id );

		if ( ! $group_courses ) {
			return array();
		}

		foreach ( $group_courses as $course_id ) {
			$topic_ids = self::get_course_topic_ids( $course_id );

			if ( empty( $topic_ids ) ) {
				continue;
			}
			foreach ( $topic_ids as $topic_id ) {
				$key  = stripslashes( __CLASS__ ) . '-' . $group_id;
				$date = get_post_meta( $topic_id, $key, true );

				if ( ! empty( $date ) ) {
					if ( self::is_timestamp( $date ) ) {
						$drip_topics[ $topic_id ] = array( 'course_id' => $course_id, 'drip' => $date );
					} else {
						$drip_topics[ $topic_id ] = array( 'course_id' => $course_id, 'drip' => strtotime( $date ) );
					}
				}
			}
		}

		return $drip_topics;
	}

	/**
	 * @param $course_id
	 *
	 * @return array
	 */
	public static function get_course_topic_ids( $course_id ) {
		$topics    = learndash_get_course_topics_list( $course_id, null, array( 'per_page' => 888 ) );
		$topic_ids = array();

		if ( empty( $topics ) ) {
			return $topic_ids;
		}

		foreach ( $topics as $topic ) {
			if ( isset( $topic['post'] ) ) {
				$topic_ids[] = $topic['post']->ID;
			}
		}

		return $topic_ids;
	}

	/**
	 *
	 */
	public static function add_notifications_strings() {
		global $post, $post_type;

		if ( ! is_admin() || 'ld-notification' !== (string) $post_type ) {
			return;
		}
		$group_id = get_post_meta( $post->ID, '_ld_notifications_group_id', true );
		wp_localize_script( 'learndash_notifications_admin_scripts', 'UO_LD_Notifications_String', array(
				'select_group_first'         => __( '-- Select Group First --', 'uncanny-pro-toolkit' ),
				'all_courses'                => __( 'Any Course', 'uncanny-pro-toolkit' ),
				'select_course'              => __( '-- Select Course --', 'uncanny-pro-toolkit' ),
				'_ld_notifications_group_id' => empty( $group_id ) ? 0 : $group_id,
		) );
	}

	/**
	 * @param $triggers
	 *
	 * @return mixed
	 */
	public static function add_group_topic_available( $triggers ) {
		$modified = array();
		if ( $triggers ) {
			foreach ( $triggers as $t => $v ) {
				if ( 'lesson_available' !== (string) $t ) {
					$modified[ $t ] = $v;
				} else {
					$modified[ $t ]                  = $v;
					$modified[ self::$trigger_name ] = __( 'A scheduled lesson is available to user with Uncanny Drip by Group', 'uncanny-pro-toolkit' );
				}
			}
			$triggers = $modified;
		}

		return $triggers;
	}

	/**
	 * @param $settings
	 *
	 * @return mixed
	 */
	public static function group_topic_available_settings( $settings ) {

		$settings['group_id']['parent'][]    = self::$trigger_name;
		$settings['course_id']['parent'][]   = self::$trigger_name;
		$settings['topic_id']['parent'][]    = self::$trigger_name;
		$settings['delay']['hide_on'][]      = self::$trigger_name;
		$settings['delay_unit']['hide_on'][] = self::$trigger_name;

		return $settings;
	}

	/**
	 * @param $instructions
	 *
	 * @return mixed
	 */
	public static function group_topic_available_shortcodes( $instructions ) {
		$instructions[ self::$trigger_name ] = $instructions['lesson_available'] + $instructions['enroll_group'];

		return $instructions;
	}

	/**
	 * @param $notification_id
	 */
	public static function save_course_id_topic_id_notifications( $notification_id ) {

		$notification = get_post( $notification_id );

		if ( ! filter_has_var( INPUT_POST, 'learndash_notifications_nonce' ) ) {
			return;
		}

		if ( 'ld-notification' !== $notification->post_type || ! check_admin_referer( 'learndash_notifications_meta_box', 'learndash_notifications_nonce' ) ) {
			return;
		}

		if ( self::$trigger_name === (string) filter_input( INPUT_POST, '_ld_notifications_trigger' ) ) {
			$group_id  = 'all' === sanitize_text_field( filter_input( INPUT_POST, '_ld_notifications_group_id' ) ) ? 'all' : (int) filter_input( INPUT_POST, '_ld_notifications_group_id' );
			$course_id = 'all' === sanitize_text_field( filter_input( INPUT_POST, '_ld_notifications_course_id' ) ) ? 'all' : (int) filter_input( INPUT_POST, '_ld_notifications_course_id' );
			$topic_id  = 'all' === sanitize_text_field( filter_input( INPUT_POST, '_ld_notifications_topic_id' ) ) ? 'all' : (int) filter_input( INPUT_POST, '_ld_notifications_topic_id' );

			update_post_meta( $notification_id, '_ld_notifications_group_id', $group_id );
			update_post_meta( $notification_id, '_ld_notifications_course_id', $course_id );
			update_post_meta( $notification_id, '_ld_notifications_topic_id', $topic_id );
		}
	}

	/**
	 * @param $notification_id
	 * @param $group_id
	 * @param $course_id
	 * @param $topic_id
	 */
	public static function handle_cron( $notification_id, $group_id, $course_id, $topic_id ) {
		if ( ! class_exists( 'LearnDash_Notifications' ) ) {
			return;
		}

		$notifications = self::get_notifications( $notification_id, $group_id, $course_id, $topic_id );

		if ( empty( $notifications ) ) {
			return;
		}

		$group_users = learndash_get_groups_users( $group_id, true );
		if ( empty( $group_users ) ) {
			return;
		}

		$user_ids = array_column( $group_users, 'ID' );
		if ( empty( $user_ids ) ) {
			return;
		}

		self::notification_log( __( '====Cron Start====', 'uncanny-pro-toolkit' ) );
		foreach ( $user_ids as $user_id ) {
			$user_id     = absint( $user_id );
			$should_send = self::should_send( $user_id, $topic_id, $course_id, $group_id );
			if ( ! $should_send ) {
				continue;
			}

			$current   = current_time( 'timestamp' );
			$timestamp = self::ld_topic_access_group( $topic_id, $user_id, $course_id, '', true );
			$timestamp = self::adjust_for_timezone_difference( $timestamp );

			self::notification_log( sprintf( __( 'Expected to send a notification for the topic %d at %s for the user %d', 'uncanny-pro-toolkit' ), $topic_id, date_i18n( 'Y-m-d H:i:s', $timestamp ), $user_id ) );
			//if timestamp is empty, then the user can access
			if ( ! empty( $timestamp ) && $current < $timestamp ) {
				self::notification_log( sprintf( __( 'Cron was triggered too early. Current time: %s, Cron time: %s', 'uncanny-pro-toolkit' ), date_i18n( 'Y-m-d H:i:s', $current ), date_i18n( 'Y-m-d H:i:s', $timestamp ) ) );
				continue;
			}
			self::notification_log( sprintf( __( 'Current time: %s, Cron time: %s', 'uncanny-pro-toolkit' ), date_i18n( 'Y-m-d H:i:s', $current ), date_i18n( 'Y-m-d H:i:s', $timestamp ) ) );
			foreach ( $notifications as $notification ) {
				$is_sent = self::is_sent( $user_id, $notification->ID, $group_id, $course_id, $topic_id, $timestamp );
				if ( $is_sent ) {
					continue;
				}

				$emails = self::gather_emails( $notification->ID, $user_id, $group_id );
				$args   = array(
					'user_id'   => $user_id,
					'group_id'  => $group_id,
					'course_id' => $course_id,
					'topic_id'  => $topic_id,
				);

				self::send( $emails, $notification, $args );
				self::mark_sent( $notification->ID, $user_id, $group_id, $course_id, $topic_id );
				wp_clear_scheduled_hook( self::$hook_name, array(
						$notification->ID,
						$group_id,
						$course_id,
						$topic_id,
				) );
			}
		}
		self::notification_log( __( 'All sent', 'uncanny-pro-toolkit' ) );
		self::notification_log( __( '====Cron End====', 'uncanny-pro-toolkit' ) );
	}

	/**
	 * @param $emails
	 * @param $notification
	 * @param $args
	 */
	public static function send( $emails, $notification, $args ) {
		global $ld_notifications_shortcode_data;
		$args['notification_id']         = $notification->ID;
		$ld_notifications_shortcode_data = $args;

		$subject = apply_filters( 'learndash_notifications_email_subject', do_shortcode( $notification->post_title ), $notification->ID );
		$content = do_shortcode( $notification->post_content );
		$content = wpautop( $content );
		if ( ! strstr( $content, '<!DOCTYPE' ) && ! strstr( $content, '<p' ) && ! strstr( $content, '<div' ) ) {
			$content = wpautop( $content );
		}
		$content = trim( $content );
		$content = apply_filters( 'learndash_notifications_email_content', $content, $notification->ID );
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		self::notification_log( sprintf( __( 'About to send the email to %s, data: %s', 'uncanny-pro-toolkit' ), join( PHP_EOL, $emails ), self::array_to_text( $args ) ) );
		foreach ( $emails as $email ) {
			$user    = get_user_by( 'email', $email );
			$is_send = true;
			if ( is_object( $user ) ) {
				//check the subscription
				$list = get_user_meta( $user->ID, 'learndash_notifications_subscription', true );
				if ( isset( $list[ self::$trigger_name ] ) && absint( $list[ self::$trigger_name ] ) === 0 ) {
					self::notification_log( sprintf( __( 'Email %s excluded', 'uncanny-pro-toolkit' ), $email ) );
					$is_send = false;
				}
			}
			if ( $is_send ) {
				add_action( 'wp_mail_failed', [ __CLASS__, 'debug_email_fail' ] );
				$ret = wp_mail( $email, $subject, $content, $headers );
				self::notification_log( sprintf( __( 'Send to %s. Status: %s', 'uncanny-pro-toolkit' ), $email, $ret === true ? __( 'sent', 'uncanny-pro-toolkit' ) : __( 'fail', 'uncanny-pro-toolkit' ) ) );
				remove_action( 'wp_mail_failed', [ __CLASS__, 'debug_email_fail' ] );
			}
		}
	}

	/**
	 * @param $notification_id
	 * @param $user_id
	 * @param $group_id
	 * @param $course_id
	 * @param $topic_id
	 *
	 * @return bool|int
	 */
	public static function mark_sent( $notification_id, $user_id, $group_id, $course_id, $topic_id ) {
		$meta = "uo_sent_group_topic_available_{$notification_id}_{$group_id}_{$course_id}_{$topic_id}";

		return update_user_meta( $user_id, $meta, current_time( 'timestamp' ) );
	}

	/**
	 * Debug email fail if system error
	 *
	 * @param \WP_Error $error
	 */
	public static function debug_email_fail( \WP_Error $error ) {
		self::notification_log( sprintf( __( 'Email error status: %s', 'uncanny-pro-toolkit' ), $error->get_error_message() ) );
	}

	/**
	 * @param $args
	 *
	 * @return string
	 */
	protected static function array_to_text( $args ) {
		$text = array();
		foreach ( $args as $key => $val ) {
			$text[] = sanitize_text_field( "$key:$val" );
		}

		return implode( ', ', $text );
	}

	/**
	 * @param $notification_id
	 * @param $group_id
	 * @param $course_id
	 * @param $topic_id
	 *
	 * @return array
	 */
	protected static function get_notifications( $notification_id, $group_id, $course_id, $topic_id ) {
		$args = array(
			'meta_key'    => '_ld_notifications_trigger',
			'meta_value'  => self::$trigger_name,
			'post_type'   => 'ld-notification',
			'post_status' => 'publish',
			'post__in'    => array( $notification_id ),
		);

		$notifications = array();
		$posts         = get_posts( $args );
		if ( empty( $posts ) ) {
			return $notifications;
		}

		foreach ( $posts as $notification ) {
			// match course and topic ID before notification is set for the topic
			$n_group_id  = get_post_meta( $notification->ID, '_ld_notifications_group_id', true );
			$n_course_id = get_post_meta( $notification->ID, '_ld_notifications_course_id', true );
			$n_topic_id  = get_post_meta( $notification->ID, '_ld_notifications_topic_id', true );
			if ( absint( $group_id ) !== absint( $n_group_id ) && 'all' !== $n_group_id ) {
				// group ID is not matched.. continue
				continue;
			}
			if ( absint( $course_id ) !== absint( $n_course_id ) && 'all' !== $n_course_id ) {
				// course ID is not matched.. continue
				continue;
			}
			if ( absint( $topic_id ) !== absint( $n_topic_id ) && 'all' !== $n_topic_id ) {
				// topic ID is not matched.. continue
				continue;
			}
			$notifications[] = $notification;
		}

		return $notifications;
	}

	/**
	 * @param $user_id
	 * @param $notification_id
	 * @param $group_id
	 * @param $course_id
	 * @param $topic_id
	 * @param $timestamp
	 *
	 * @return mixed
	 */
	public static function is_sent( $user_id, $notification_id, $group_id, $course_id, $topic_id, $timestamp ) {
		$meta = "uo_sent_group_topic_available_{$notification_id}_{$group_id}_{$course_id}_{$topic_id}";
		$sent = get_user_meta( $user_id, $meta, true );
		if ( ! empty( $sent ) ) {
			if ( $timestamp > $sent ) {
				$sent = false;
			} else {
				$sent = true;
			}
		} else {
			$sent = false;
		}

		//if it was sent, then should be a timestamp
		return $sent;
	}

	/**
	 * @param $user_id
	 * @param $topic_id
	 * @param $course_id
	 * @param $group_id
	 *
	 * @return bool
	 */
	public static function should_send( $user_id, $topic_id, $course_id, $group_id ) {
		$topic_complete = learndash_is_topic_complete( $user_id, $topic_id );
		if ( $topic_complete ) {
			//the user already finish this, do nothing
			self::notification_log( sprintf( __( 'Error: Topic %s already completed in Course %s by %s', 'uncanny-pro-toolkit' ), $topic_id, $course_id, $user_id ) );

			return false;
		}
		$check_course_access = ld_course_check_user_access( $course_id, $user_id );
		$group_course        = learndash_group_has_course( $group_id, $course_id );
		if ( ! $check_course_access && ! $group_course ) {
			self::notification_log( sprintf( __( 'Error: User %s access issue for Course %s', 'uncanny-pro-toolkit' ), $user_id, $course_id ) );

			return false;
		}

		return true;
	}

	/**
	 * @param      $notification_id
	 * @param      $user_id
	 * @param null $course_id
	 * @param null $group_id
	 *
	 * @return false|string[]
	 */
	public static function gather_emails( $notification_id, $user_id, $group_id = null ) {
		$addition_recipients = get_post_meta( $notification_id, '_ld_notifications_bcc', true );
		$emails              = explode( ',', $addition_recipients );
		$recipients          = get_post_meta( $notification_id, '_ld_notifications_recipient', true );
		foreach ( $recipients as $recipient ) {
			switch ( $recipient ) {
				case 'user':
					$user = get_user_by( 'id', $user_id );
					if ( is_object( $user ) ) {
						$emails[] = $user->user_email;
					}
					break;
				case 'group_leader':
					/**
					 * In this context, a group leaders should be the leader of this user, if any
					 */
					$group_ids = array();
					if ( ! is_null( $group_id ) ) {
						$group_ids[] = absint( $group_id );
					}
					$group_ids = array_unique( $group_ids );
					$group_ids = array_filter( $group_ids );
					foreach ( $group_ids as $group_id ) {
						$user_ids = learndash_get_groups_administrator_ids( $group_id );
						foreach ( $user_ids as $user_id ) {
							$user = get_user_by( 'id', $user_id );
							if ( is_object( $user ) ) {
								$emails[] = $user->user_email;
							}
						}
					}
					break;
				case 'admin':
					$users = get_users( array( 'role' => 'administrator' ) );
					foreach ( $users as $user ) {
						$emails[] = $user->user_email;
					}
					break;
			}
		}
		$emails = array_unique( $emails );
		$emails = array_filter( $emails );
		//have to validate the emails as it user input
		foreach ( $emails as $key => $email ) {
			if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
				unset( $emails[ $key ] );
			}
		}

		return $emails;
	}


	/**
	 * @param $message
	 * @param $category
	 */
	public static function notification_log( $message, $category = '' ) {
		if ( empty( $category ) ) {
			$category = self::$trigger_name;
		}
		$log_dir = wp_upload_dir( null, true );
		$log_dir = $log_dir['basedir'] . DIRECTORY_SEPARATOR . 'learndash-notifications' . DIRECTORY_SEPARATOR;
		if ( ! is_dir( $log_dir ) ) {
			wp_mkdir_p( $log_dir );
		}

		//put an index.html, index.php there
		if ( ! file_exists( $log_dir . 'index.html' ) ) {
			file_put_contents( $log_dir . 'index.html', '' );
		}

		if ( ! file_exists( $log_dir . 'index.php' ) ) {
			file_put_contents( $log_dir . 'index.php', '<?php' );
		}
		$format    = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		$message   = sprintf( date( $format, current_time( 'timestamp' ) ) . ': %s', $message );
		$file_name = hash( 'sha256', sanitize_file_name( $category ) . AUTH_SALT );
		file_put_contents( $log_dir . $file_name, $message . PHP_EOL, FILE_APPEND );
	}

	public static function topic_visible_the_content( $content ) {
		if ( ! is_singular( learndash_get_post_type_slug( 'topic' ) ) ) {
			return $content;
		}

		global $post;

		return self::topic_visible_after( $content, $post );
	}
}

