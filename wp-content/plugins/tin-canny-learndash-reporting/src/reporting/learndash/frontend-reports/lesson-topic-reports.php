<?php
/**
 * Lesson Topic Reports
 *
 * Handles the Lesson and Topic Reports Shortcodes and Block Wrappers
 *
 * @package uncanny_learndash_reporting
 */

namespace uncanny_learndash_reporting;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class LessonTopicReports
 *
 * @package uncanny_learndash_reporting
 */
class LessonTopicReports extends Config {
	
	/**
	 * @var array $to_json - Array of data to be passed to JS.
	 */
	private $to_json = array();

	/**
	 * @var int $shortcode_instance_count - Unique Identifier Instance counter.
	 */
	private static $shortcode_instance_count = 0;

	/**
	 * Class constructor
	 */
	public function __construct() {

		add_shortcode(
			'uotc_lesson_report',
			array( $this, 'lesson_report_shortcode' )
		);
		add_shortcode(
			'uotc_topic_report',
			array( $this, 'topic_report_shortcode' )
		);
		add_action(
			'wp_enqueue_scripts',
			array( $this, 'register_assets' )
		);
		add_action(
			'wp_footer',
			array( $this, 'print_scripts' )
		);
		add_action(
			'rest_api_init',
			array( $this, 'register_rest_endpoint' )
		);
	}

	/**
	 * Lesson Report Shortcode Callback.
	 * [uotc_lesson_report]
	 *
	 * @return string
	 */
	public function lesson_report_shortcode() {

		return $this->report_shortcode( 'lesson' );
	}

	/**
	 * Topic Report Shortcode Callback.
	 * [uotc_topic_report]
	 *
	 * @return string
	 */
	public function topic_report_shortcode() {

		return $this->report_shortcode( 'topic' );
	}

	/**
	 * Report Shortcode Callback.
	 *
	 * @param string $type - Type of report ( topic || lesson ).
	 */
	private function report_shortcode( $type ) {

		$is_allowed = self::tincanny_permissions();
		if ( is_wp_error( $is_allowed ) ) {
			return $is_allowed->get_error_message();
		}

		$user_id                  = get_current_user_id();
		$shortcode_instance_count = self::$shortcode_instance_count;
		++$shortcode_instance_count;
		self::$shortcode_instance_count = $shortcode_instance_count;

		$data = array(
			'type'        => $type,
			'user_id'     => $user_id,
			'group_data'  => array(),
			'group_id'    => 0,
			'course_data' => array(),
			'step_data'   => array(),
			'results'     => array(),
		);

		$courses = array();

		if ( learndash_is_group_leader_user( $user_id ) ) {
			$group_ids = learndash_get_administrators_group_ids( $user_id );
			if ( count( $group_ids ) === 1 ) {
				$data['group_id'] = $group_ids[0];
			}
			$course_ids = learndash_get_groups_courses_ids( $user_id, $group_ids );
		} else {
			$group_ids  = array();
			$course_ids = array();
		}

		// Get all groups.
		$group_args  = array(
			'post_type'      => 'groups',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post__in'       => $group_ids,
		);
		$group_query = new \WP_Query( $group_args );
		$groups      = $group_query->have_posts() ? $group_query->posts : array();
		foreach ( $groups as $group ) {
			$data['group_data'][] = array(
				'name'   => $group->post_title,
				'id'     => $group->ID,
				'parent' => $group->post_parent,
			);
		}

		// Get all courses.
		$course_args  = array(
			'post_type'      => 'sfwd-courses',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post__in'       => $course_ids,
		);
		$course_query = new \WP_Query( $course_args );
		$courses      = $course_query->have_posts() ? $course_query->posts : array();
		if ( ! empty( $courses ) ) {
			foreach ( $courses as $course ) {
				$data['course_data'][] = array(
					'name' => $course->post_title,
					'id'   => $course->ID,
				);
			}
		}

		wp_reset_postdata();

		// Return Output.
		$return = sprintf(
			'<div id="uotc-%1$s-report-%2$d" class="uotc-%1$s-report uotc-report">',
			$data['type'],
			$shortcode_instance_count
		);

		ob_start();
		include __DIR__ . '/../templates/lesson-topic-report-filters.php';
		$return .= ob_get_clean();
		$return .= '<table class="uotc-report__table display responsive no-wrap dataTable no-footer dtr-inline" cellspacing="0" width="100%"></table>';
		$return .= '</div>';

		$this->set_json_data();

		return $return;
	}

	/**
	 * Check if the Toolkit Pro Course Timer module is activated
	 *
	 * @return bool
	 */
	public function is_toolkit_pro_timer_activated() {
		static $timer_activated = null;
		if ( null !== $timer_activated ) {
			return $timer_activated;
		}
		$timer_activated = false;
		if ( defined( 'UNCANNY_TOOLKIT_PRO_VERSION' ) ) {
			$active_classes = get_option( 'uncanny_toolkit_active_classes', array() );
			if ( is_array( $active_classes ) && key_exists( 'uncanny_pro_toolkit\CourseTimer', $active_classes ) ) {
				$timer_activated = true;
			}
		}
		return $timer_activated;
	}

	/**
	 * Set the data to be passed to the JS.
	 */
	public function set_json_data() {
		if ( ! empty( $this->to_json ) ) {
			return;
		}

		// Build the columns array.
		$columns = array(
			'username'       => __( 'Username', 'uncanny-learndash-reporting' ),
			'first_name'     => __( 'First Name', 'uncanny-learndash-reporting' ),
			'last_name'      => __( 'Last Name', 'uncanny-learndash-reporting' ),
			'email'          => __( 'Email', 'uncanny-learndash-reporting' ),
			'step_status'    => __( 'Status', 'uncanny-learndash-reporting' ),
			'completed_date' => __( 'Completed', 'uncanny-learndash-reporting' ),
		);

		// Check for active Toolkit Pro Course Timer module.
		if ( $this->is_toolkit_pro_timer_activated() ) {
			$columns['time'] = __( 'Time', 'uncanny-learndash-reporting' );
		}

		$group_label   = \LearnDash_Custom_Label::get_label( 'group' );
		$course_label  = \LearnDash_Custom_Label::get_label( 'course' );
		$courses_label = \LearnDash_Custom_Label::get_label( 'courses' );
		$lesson_label  = \LearnDash_Custom_Label::get_label( 'lesson' );
		$lessons_label = \LearnDash_Custom_Label::get_label( 'lessons' );
		$topic_label   = \LearnDash_Custom_Label::get_label( 'topic' );
		$topics_label  = \LearnDash_Custom_Label::get_label( 'topics' );

		// Set Json Data.
		$this->to_json = array(
			'nonce'            => wp_create_nonce( 'wp_rest' ),
			'root'             => esc_url_raw( rest_url() . 'tincanny/v1/' ),
			'currentUser'      => get_current_user_id(),
			'i18n'             => array(
				'table_language'     => array(
					'info'              => sprintf(
					/* translators: %1$s is the start number, %2$s is the end number, and %3$s is the total number of entries */
						_x( 'Showing %1$s to %2$s of %3$s entries', '%1$s is the start number, %2$s is the end number, and %3$s is the total number of entries', 'uncanny-learndash-reporting' ),
						'_START_',
						'_END_',
						'_TOTAL_'
					),
					'infoEmpty'         => __( 'Showing 0 to 0 of 0 entries', 'uncanny-learndash-reporting' ),
					'infoFiltered'      => sprintf(
					/* translators: %s is a number */
						_x( '(filtered from %s total entries)', '%s is a number', 'uncanny-learndash-reporting' ),
						'_MAX_'
					),
					'lengthMenu'        => sprintf(
					/* translators: %s is a number */
						_x( 'Show %s entries', 'Table', 'uncanny-learndash-reporting' ),
						'_MENU_'
					),
					'loadingRecords'    => __( 'Loading...', 'uncanny-learndash-reporting' ),
					'processing'        => __( 'Processing...', 'uncanny-learndash-reporting' ),
					'searchPlaceholder' => __( 'Search by username, name, email or status', 'uncanny-learndash-reporting' ),
					'zeroRecords'       => __( 'No matching records found', 'uncanny-learndash-reporting' ),
					'paginate'          => array(
						'first'    => __( 'First', 'uncanny-learndash-reporting' ),
						'last'     => __( 'Last', 'uncanny-learndash-reporting' ),
						'next'     => __( 'Next', 'uncanny-learndash-reporting' ),
						'previous' => __( 'Previous', 'uncanny-learndash-reporting' ),
					),
					'aria'              => array(
						'sortAscending'  => sprintf( ': %s', __( 'activate to sort column ascending', 'uncanny-learndash-reporting' ) ),
						'sortDescending' => sprintf( ': %s', __( 'activate to sort column descending', 'uncanny-learndash-reporting' ) ),
					),
				),
				'buttons'            => array(
					'csv'         => __( 'CSV', 'uncanny-learndash-reporting' ),
					'exportCSV'   => __( 'CSV export', 'uncanny-learndash-reporting' ),
					'excel'       => __( 'Excel', 'uncanny-learndash-reporting' ),
					'exportExcel' => __( 'Excel export', 'uncanny-learndash-reporting' ),
				),
				'status'             => array(
					'completed'  => __( 'Completed', 'uncanny-learndash-reporting' ),
					'incomplete' => __( 'Incomplete', 'uncanny-learndash-reporting' ),
				),
				'filters'            => array(
					'course' => array(
						'default' => sprintf(
						/* translators: %s is the course label */
							__( 'Select a %s', 'uncanny-learndash-reporting' ),
							strtolower( $course_label )
						),
						'group'   => sprintf(
						/* translators: %1$s is the group label, %2$s is the courses label */
							__( 'Selected %1$s has no %2$s.', 'uncanny-learndash-reporting' ),
							$group_label,
							strtolower( $courses_label )
						),
					),
					'lesson' => array(
						'default' => sprintf(
						/* translators: %s is the lesson label */
							__( 'Select a %s', 'uncanny-learndash-reporting' ),
							strtolower( $lesson_label )
						),
						'course'  => sprintf(
						/* translators: %1$s is the course label, %2$s is the lessons label */
							__( 'The selected %1$s has no %2$s.', 'uncanny-learndash-reporting' ),
							strtolower( $course_label ),
							strtolower( $lessons_label )
						),
					),
					'topic'  => array(
						'default' => sprintf(
						/* translators: %s is the topic label */
							__( 'Select a %s', 'uncanny-learndash-reporting' ),
							strtolower( $topic_label )
						),
						'course'  => sprintf(
						/* translators: %1$s is the course label, %2$s is the topics label */
							__( 'The selected %1$s has no %2$s.', 'uncanny-learndash-reporting' ),
							strtolower( $course_label ),
							strtolower( $topics_label )
						),
					),
				),
				'emptyTable'         => array(
					'defaults'  => array(
						'lesson' => sprintf(
						/* translators: %1$s is the course label, %2$s is the lesson label */
							__( 'Select a %1$s and %2$s for results', 'uncanny-learndash-reporting' ),
							$course_label,
							$lesson_label
						),
						'topic'  => sprintf(
						/* translators: %1$s is the course label, %2$s is the topic label */
							__( 'Select a %1$s and %2$s for results', 'uncanny-learndash-reporting' ),
							$course_label,
							$topic_label
						),
					),
					'course'    => sprintf(
					/* translators: %1$s is the courses label, %2$s is the group label */
						__( 'There are no %1$s associated with the selected %2$s.', 'uncanny-learndash-reporting' ),
						strtolower( $courses_label ),
						strtolower( $group_label )
					),
					'lesson'    => sprintf(
					/* translators: %1$s is the lessons label, %2$s is the course label */
						__( 'There are no %1$s associated with the selected %2$s.', 'uncanny-learndash-reporting' ),
						strtolower( $lessons_label ),
						strtolower( $course_label )
					),
					'topic'     => sprintf(
					/* translators: %1$s is the topics label, %2$s is the course label */
						__( 'There are no %1$s associated with the selected %2$s.', 'uncanny-learndash-reporting' ),
						strtolower( $topics_label ),
						strtolower( $course_label )
					),
					'noResults' => sprintf(
					/* translators: %s is the course label */
						__( 'There are no students enrolled in the selected %s.', 'uncanny-learndash-reporting' ),
						strtolower( $course_label )
					),
				),
				'customColumnLabels' => array(
					'customizeColumns'     => __( 'Customize columns', 'uncanny-learndash-reporting' ),
					'hideCustomizeColumns' => __( 'Hide customize columns', 'uncanny-learndash-reporting' ),
				),
			),
			'columns'          => $columns,
			'loadingAnimation' => $this->loading_animation(),
			'pageLength'	   => apply_filters( 'uo_tincanny_uotc_lesson_report_page_length', 10 ),
		);
	}

	/**
	 * Get the HTML for the loading animation.
	 *
	 * @return string
	 */
	public function loading_animation() {
		$html  = '<div class="reporting-status-loading-animation-wrap">';
		$html .= '<div class="reporting-status reporting-status--loading">';
		$html .= '<div class="reporting-status__icon"></div>';
		$html .= '<div class="reporting-status__text">';
		$html .= __( 'Loading', 'uncanny-learndash-reporting' );
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		return $html;
	}

	/**
	 * Register JS & CSS assets.
	 */
	public function register_assets() {

		global $post;

		$block_is_on_page = false;
		if ( is_a( $post, 'WP_Post' ) && function_exists( 'parse_blocks' ) ) {
			$blocks = parse_blocks( $post->post_content );
			foreach ( $blocks as $block ) {
				if ( 'tincanny/lesson-report' === $block['blockName'] || 'tincanny/topic-report' === $block['blockName'] ) {
					$block_is_on_page = true;
				}
			}
		}

		if ( is_a( $post, 'WP_Post' ) && (
				$block_is_on_page ||
				has_shortcode( $post->post_content, 'uotc_lesson_report' ) ||
				has_shortcode( $post->post_content, 'uotc_topic_report' )
			) ) {

			wp_enqueue_script(
				'uotc-topic-lesson-report',
				Config::get_admin_js( 'tincanny-reports' ),
				array( 'jquery', 'tc_runtime', 'tc_vendors' ),
				UNCANNY_REPORTING_VERSION,
				true
			);

			wp_enqueue_style(
				'uotc-topic-lesson-report',
				Config::get_admin_css( 'lesson-topic-reports-module.css' ),
				array(),
				UNCANNY_REPORTING_VERSION,
				'all'
			);

			ReportingAdminMenu::enqueue_common_scripts();
		}
	}

	/**
	 * Enqueue JS scripts.
	 */
	public function print_scripts() {
		if ( ! empty( $this->to_json ) ) {
			wp_enqueue_script( 'uotc-topic-lesson-report' );
			wp_enqueue_style( 'uotc-topic-lesson-report' );
			wp_enqueue_style( 'datatables-styles' );
			wp_localize_script( 'uotc-topic-lesson-report', 'uotcTopicLessonReports', $this->to_json );
		}
	}

	/**
	 * Register rest api endpoints
	 */
	public function register_rest_endpoint() {
		register_rest_route(
			'tincanny/v1',
			'/get_course_step_data/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_get_course_step_data' ),
				'permission_callback' => array( __CLASS__, 'tincanny_permissions' ),
			)
		);
	}

	/**
	 * @return bool|\WP_Error
	 */
	public static function tincanny_permissions() {

		$user = wp_get_current_user();
		// REVIEW: Do we want a filter here?
		$allowed_roles = array( 'administrator', 'group_leader' );
		if ( array_intersect( $allowed_roles, $user->roles ) ) {
			// Check Group Leader has access to at least one group.
			if ( in_array( 'group_leader', $user->roles, true ) ) {
				$group_ids = learndash_get_administrators_group_ids( $user->ID );
				if ( ! empty( $group_ids ) ) {
					return true;
				}
				// Admins may always access.
			} else {
				return true;
			}
		}

		return new \WP_Error( 'rest_forbidden', esc_html__( 'You must be a Group Leader or Administrator with access to at least one group to use this report.', 'uncanny-learndash-reporting' ) );
	}

	/**
	 * Get course step data.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response
	 */
	public function rest_get_course_step_data( $request ) {

		$params     = $request->get_params();
		$group_id   = (int) $params['group_id'];
		$course_id  = (int) $params['course_id'];
		$step_id    = (int) $params['course_step_id'];
		$step_type  = $params['step_type'];
		$user_id    = (int) $params['user_id'];
		$course_ids = false;
		$response   = array(
			'group_id'  => 0,
			'course_id' => 0,
			'step_id'   => 0,
			'results'   => array(),
		);

		// Check if group id is valid and the course is in the group.
		if ( $group_id > 0 ) {
			$response['group_id']   = $group_id;
			$course_ids             = learndash_group_enrolled_courses( $group_id );
			$response['course_ids'] = $course_ids;
			if ( $course_id > 0 ) {
				if ( ! in_array( $course_id, $course_ids, true ) ) {
					$course_id = 0;
				}
			}
		}

		// Check if course id is valid and build the step data for ( lesson || topic ).
		if ( $course_id > 0 ) {
			$response['course_id'] = $course_id;
			if ( empty( $step_id ) ) {
				$response['step_data'] = $this->get_course_step_data( $course_id, $step_type );
			}
		}

		// If we have a valid course id and step id, get the user data.
		if ( $course_id > 0 && $step_id > 0 ) {
			$response['step_id'] = $step_id;
			$response['results'] = $this->get_step_users_data( $group_id, $course_id, $step_id, $step_type );
		}

		return new \WP_REST_Response( $response, 200 );
	}

	/**
	 * Get course step data.
	 *
	 * @param int    $course_id   Step ID.
	 * @param string $step_type   Step type ( topic || lesson ).
	 *
	 * @return array
	 */
	private function get_course_step_data( $course_id, $step_type ) {

		$results = array();

		if ( ! in_array( $step_type, array( 'lesson', 'topic' ), true ) || empty( $course_id ) ) {
			return $results;
		}

		$slug     = learndash_get_post_type_slug( $step_type );
		$step_ids = learndash_get_course_steps( $course_id, array( $slug ) );
		if ( ! empty( $step_ids ) ) {
			$step_args = array(
				'post_type'      => $slug,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'post__in',
				'order'          => 'ASC',
				'post__in'       => $step_ids,
			);

			$step_query = new \WP_Query( $step_args );
			if ( $step_query->have_posts() ) {
				foreach ( $step_query->posts as $step ) {
					$results[] = array(
						'id'   => $step->ID,
						'text' => $step->post_title,
					);
				}
			}
			wp_reset_postdata();
		}

		return $results;
	}

	/**
	 * Get step users data.
	 *
	 * @param int    $group_id    Group ID.
	 * @param int    $course_id   Course ID.
	 * @param int    $step_id     Step ID.
	 * @param string $step_type   Step type ( topic || lesson ).
	 *
	 * @return array
	 */
	private function get_step_users_data( $group_id, $course_id, $step_id, $step_type ) {

		$results = array();

		// Check if step type is valid.
		if ( ! in_array( $step_type, array( 'lesson', 'topic' ), true ) ) {
			return $results;
		}

		// Ensure IDs are integers && Set.
		$group_id  = (int) $group_id;
		$course_id = learndash_get_course_id( $course_id );
		$step_id   = (int) $step_id;
		if ( empty( $course_id ) || empty( $step_id ) ) {
			return $results;
		}

		$users = array();

		if ( $group_id > 0 ) {
			// If group id is set, get the users for the group.
			$users = learndash_get_groups_users( $group_id );
		} else {
			// Get the users for the course.
			$user_query = learndash_get_users_for_course( $course_id, array( 'fields' => 'all' ) );
			if ( $user_query instanceof \WP_User_Query ) {
				$users = $user_query->get_results();
			}
		}

		// Bail if no users found.
		if ( empty( $users ) ) {
			return $results;
		}

		// Check if the Toolkit Pro Timer is activated.
		$include_time = $this->is_toolkit_pro_timer_activated();
		$timed_ids    = array();
		if ( $include_time ) {
			$timed_ids = $this->get_timed_course_step_ids( $step_id, $step_type, $course_id );
		}

		// Get the step progress for each user.
		foreach ( $users as $key => $user ) {
			$user_id         = $user->ID;
			$results[ $key ] = array(
				'ID'             => $user_id,
				'username'       => $user->data->user_login,
				'email'          => $user->data->user_email,
				'first_name'     => get_user_meta( $user_id, 'first_name', true ),
				'last_name'      => get_user_meta( $user_id, 'last_name', true ),
				'step_status'    => 0,
				'completed_date' => 'n/a',
				'time'           => 'n/a',
			);

			// Get Activity Status & Completion Date.
			$activity_args = array(
				'user_id'       => $user_id,
				'course_id'     => $course_id,
				'post_id'       => $step_id,
				'activity_type' => $step_type,
			);
			$activity      = learndash_get_user_activity( $activity_args, false );
			if ( ! empty( $activity ) ) {
				$results[ $key ]['step_status'] = (int) $activity->activity_status;
				if ( ! empty( $activity->activity_completed ) ) {
					// Add hidden data for sorting and display formatted date.
					$results[ $key ]['completed_date'] = sprintf(
						'<span class="uotc-hidden-data" style="display: none;">%d</span>%s',
						$activity->activity_completed,
						learndash_adjust_date_time_display( $activity->activity_completed )
					);
				}
			}

			if ( $include_time && ! empty( $timed_ids ) ) {
				$results[ $key ]['time'] = 0;
				foreach ( $timed_ids as $timed_id ) {
					$meta_key                 = "uo_timer_{$course_id}_{$timed_id}";
					$results[ $key ]['time'] += (int) get_user_meta( $user_id, $meta_key, true );
				}
				$results[ $key ]['time'] = $this->seconds_to_hours( $results[ $key ]['time'] );
			}
		}

		return $results;
	}

	/**
	 * SQL Get step users data.
	 * Left in for code review
	 */
	/*
    private function sql_get_step_users_data( $group_id, $course_id, $step_id, $step_type ) {

		$results = array();

		// Check if step type is valid.
		if ( ! in_array( $step_type, array( 'lesson', 'topic' ), true ) ) {
			return $results;
		}

		// Ensure IDs are integers && Set.
		$group_id  = (int) $group_id;
		$course_id = learndash_get_course_id( $course_id );
		$step_id   = (int) $step_id;
		if ( empty( $course_id ) || empty( $step_id ) ) {
			return $results;
		}

		// Build User ID array from courses.
		$user_ids     = array();
		$course_users = learndash_get_users_for_course( $course_id );
		if ( ! empty( $course_users ) ) {
			if ( $course_users instanceof \WP_User_Query ) {
				$user_ids = $course_users->get_results();
			}
		}

		if ( $group_id > 0 ) {
			// Get group user IDs that are not enrolled in the course.
			$group_user_ids = learndash_get_groups_user_ids( $group_id );
			if ( ! empty( $group_user_ids ) ) {
				$user_ids = array_intersect( $user_ids, $group_user_ids );
			}
		}

		// If no users, return.
		if ( empty( $user_ids ) ) {
			return $results;
		}

		// Build SQL for Query.
		global $wpdb;

		$sql_select = 'SELECT
            u.ID,
            u.user_login AS username,
            u.user_email AS email,
            um1.meta_value AS first_name,
            um2.meta_value AS last_name,
            am.activity_status AS step_status,
            am.activity_completed AS completed_date';

		$sql_from_joins = " FROM `{$wpdb->users}` AS u
            LEFT JOIN `{$wpdb->usermeta}` AS um1
                ON um1.user_id = u.ID
                AND um1.meta_key = 'first_name'
            LEFT JOIN `{$wpdb->usermeta}` AS um2
                ON um2.user_id = u.ID
                AND um2.meta_key = 'last_name'
            LEFT JOIN `{$wpdb->prefix}learndash_user_activity` AS am
                ON am.user_id = u.ID
                AND am.course_id = '{$course_id}'
                AND am.post_id = '{$step_id}'
                AND am.activity_type = '{$step_type}'";

		// If the Toolkit Pro Timer is activated, we need to add the user meta keys for the timer.
		$include_time = $this->is_toolkit_pro_timer_activated();
		if ( $include_time ) {
			$timed_ids = $this->get_timed_course_step_ids( $step_id, $step_type, $course_id );

			// Add to SQL if we have timed ids.
			if ( ! empty( $timed_ids ) ) {
				$um_count = 3;
				foreach ( $timed_ids as $timed_id ) {
					// Add Selects.
					$sql_select .= ", um{$um_count}.meta_value AS timer_{$timed_id}";
					// Add Joins.
					$sql_from_joins .= "LEFT JOIN `{$wpdb->usermeta}` AS um{$um_count}
						ON um{$um_count}.user_id = u.ID
						AND um{$um_count}.meta_key = 'uo_timer_{$course_id}_{$timed_id}'";
					// Update Count.
					++$um_count;
				}
			}
		}

		// Break Large User Counts into Smaller Batches.
		$batch_size = 1000;
		$batches    = array_chunk( $user_ids, $batch_size );
		foreach ( $batches as $batch ) {
			$user_id_batch = implode( ',', $batch );
			$sql           = $sql_select . $sql_from_joins . " WHERE u.ID IN ($user_id_batch) GROUP BY u.ID";
			$batch_results = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$results       = array_merge( $results, $batch_results );
		}

		if ( ! empty( $results ) ) {
			foreach ( $results as $key => $result ) {
				// Format Date.
				if ( ! empty( $result['completed_date'] ) ) {
                    // Add hidden data for sorting and display formatted date.
                    $results[ $key ]['completed_date'] = sprintf(
                        '<span class="uotc-hidden-data" style="display: none;">%d</span>%s',
                        $activity->activity_completed,
                        learndash_adjust_date_time_display( $activity->activity_completed )
                    );
				} else {
					$results[ $key ]['completed_date'] = 'n/a';
				}
				// Tally up the time.
				if ( $include_time ) {
					$results[ $key ]['time'] = 0;
					if ( ! empty( $timed_ids ) ) {
						foreach ( $timed_ids as $timed_id ) {
							$results[ $key ]['time'] += (int) $result[ "timer_{$timed_id}" ];
						}
					}
					$results[ $key ]['time'] = $this->seconds_to_hours( $results[ $key ]['time'] );
				}
			}
		}

		return $results;
	}
    */


	/**
	 * Get the ids of the timed course steps.
	 *
	 * @param int    $step_id    The step id.
	 * @param string $step_type  The step type.
	 * @param int    $course_id  The course id.
	 *
	 * @return array
	 */
	private function get_timed_course_step_ids( $step_id, $step_type, $course_id ) {

		// Include Step Id.
		$timed_ids = array( $step_id );

		// Collect all the quiz ids for the step.
		$quizzes = learndash_get_lesson_quiz_list( $step_id, null, $course_id );
		if ( ! empty( $quizzes ) ) {
			foreach ( $quizzes as $quiz ) {
				$timed_ids[] = $quiz['post']->ID;
			}
		}

		// If lesson collect all the topic ids for the step.
		if ( 'lesson' === $step_type ) {
			$topics = learndash_get_topic_list( $step_id, $course_id );
			if ( ! empty( $topics ) ) {
				foreach ( $topics as $topic ) {
					$timed_ids[] = $topic->ID;
					// Collect all the quiz ids for the topic.
					$quizzes = learndash_get_lesson_quiz_list( $topic->ID, null, $course_id );
					if ( ! empty( $quizzes ) ) {
						foreach ( $quizzes as $quiz ) {
							$timed_ids[] = $quiz['post']->ID;
						}
					}
				}
			}
		}

		return $timed_ids;
	}


	/**
	 * Convert seconds to hours.
	 *
	 * @param int $seconds Seconds to convert.
	 * @return string Hours in HH:MM:SS format.
	 */
	private function seconds_to_hours( $seconds ) {

		if ( ! is_numeric( $seconds ) || $seconds <= 0 ) {
			return '00:00:00';
		}
		$seconds = (int) $seconds;
		$hours   = floor( $seconds / 3600 );
		$hours   = str_pad( $hours, 2, '0', STR_PAD_LEFT );
		$minutes = floor( ( (int)($seconds / 60) ) % 60 );
		$minutes = str_pad( $minutes, 2, '0', STR_PAD_LEFT );
		$seconds = $seconds % 60;
		$seconds = str_pad( $seconds, 2, '0', STR_PAD_LEFT );

		return "{$hours}:{$minutes}:{$seconds}";
	}
}
