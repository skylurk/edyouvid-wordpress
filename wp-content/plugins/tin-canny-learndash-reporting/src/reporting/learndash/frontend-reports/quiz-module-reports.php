<?php

namespace uncanny_learndash_reporting;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class QuizModuleReports
 *
 * @package uncanny_learndash_reporting
 */
class QuizModuleReports extends Config {

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_shortcode( 'uo_individual_quiz_report', array( $this, 'user_quiz_report' ) );
		add_shortcode( 'uo_group_quiz_report', array( $this, 'group_quiz_report' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'report_scripts' ) );

		//register api class
		add_action( 'rest_api_init', array( $this, 'uo_api' ) );
	}

	/*
	 * Register rest api endpoints
	 *
	 */
	public function uo_api() {

		register_rest_route(
			'tincanny/v1',
			'/get_user_course_data/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_user_course_data' ),
				'permission_callback' => array( __CLASS__, 'tincanny_permissions' ),
			)
		);

		register_rest_route(
			'tincanny/v1',
			'/get_user_essay/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_user_essay' ),
				'permission_callback' => array( __CLASS__, 'tincanny_essay_permissions' ),
			)
		);
	}

	/**
	 * Check if user has permission to view tincanny data
	 *
	 * @return bool|\WP_Error
	 */
	public static function tincanny_permissions() {
		$capability = apply_filters( 'tincanny_can_get_data', 'manage_options' );

		// Restrict endpoint to only users who have the manage_options capability.
		if ( current_user_can( $capability ) ) {
			return true;
		}

		if ( current_user_can( 'group_leader' ) ) {
			return true;

		}

		return new \WP_Error( 'rest_forbidden', esc_html__( 'You do not have the capability to view tincanny data.', 'uncanny-learndash-reporting' ) );
	}

	/**
	 * Check if user has permission to view tincanny data
	 *
	 * @return bool|\WP_Error
	 */
	public static function tincanny_essay_permissions() {

		if ( is_user_logged_in() ) {
			return true;
		}

		return new \WP_Error( 'rest_forbidden', esc_html__( 'You do not have the capability to view tincanny data.', 'uncanny-learndash-reporting' ) );
	}

	/**
	 * Get user essay
	 *
	 * @return array|string|void
	 */
	public static function get_user_essay() {

		$return_object            = array();
		$return_object['success'] = false;
		$essay_url                = esc_url( ultc_get_filter_var( 'essayUrl', '', INPUT_POST ) );
		$essay_user_id            = absint( ultc_get_filter_var( 'userId', '', INPUT_POST ) );

		// Make sure we receive data
		if ( empty( $essay_url ) ) {
			$return_object['message']  = __( 'Essay Link Not Found', 'uncanny-learndash-reporting' );
			$return_object['essayUrl'] = $essay_url;

			return $return_object;
		}

		// Make sure we receive data
		if ( empty( $essay_user_id ) ) {
			$return_object['message']  = __( 'User ID Not Found', 'uncanny-learndash-reporting' );
			$return_object['essayUrl'] = $essay_url;

			return $return_object;
		}

		$user_id       = get_current_user_id();
		$can_view_file = false;

		$slug = explode( 'essay/', $essay_url );//substr( $essay_url, strrpos( $essay_url, '/' ) + 1 );
		$slug = str_replace( '/', '', $slug[1] );

		global $wpdb;
		$essay_post = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->posts} WHERE post_author = %d AND post_name = %s",
				$essay_user_id,
				$slug
			)
		);

		if ( empty( $essay_post ) ) {
			return __( 'Essay answer not found.', 'uncanny-learndash-reporting' );
		}

		if ( ( learndash_is_admin_user( $user_id ) ) || ( absint( $essay_post->post_author ) === absint( $essay_user_id ) ) ) {
			$can_view_file = true;
		} elseif ( ( learndash_is_group_leader_user( $user_id ) ) && ( learndash_is_group_leader_of_user( $user_id, $essay_user_id ) ) ) {
			$can_view_file = true;
		}

		if ( true === $can_view_file ) {
			$uploaded_file = get_post_meta( $essay_post->ID, 'upload', true );

			if ( ( ! empty( $uploaded_file ) ) && ( ! strstr( $essay_post->post_content, $uploaded_file ) ) ) {
				$essay_post->post_content .= apply_filters( 'learndash-quiz-essay-upload-link', '<p><a target="_blank" href="' . $uploaded_file . '">' . __( 'View uploaded file', 'learndash' ) . '</a></p>' ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
			}

			return wpautop( $essay_post->post_content );

		} else {
			return __( 'Sorry, Access Denied', 'uncanny-learndash-reporting' );
		}

	}

	/**
	 * Get User Course Data
	 *
	 * @return \WP_REST_Response
	 */
	public function get_user_course_data() {

		$course_label = \LearnDash_Custom_Label::get_label( 'course' );
		$group_id     = absint( ultc_get_filter_var( 'groupId', 0, INPUT_POST ) );
		$course_id    = absint( ultc_get_filter_var( 'courseId', 0, INPUT_POST ) );

		if( empty( $group_id ) ) {
			return new \WP_REST_Response(
				array(
					'message' => __( 'Group ID not found.', 'uncanny-learndash-reporting' ),
					'success' => false,
				),
				200
			);
		}

		if( empty( $course_id ) ) {
			return new \WP_REST_Response(
				array(
					'message' => __( 'Course ID not found.', 'uncanny-learndash-reporting' ),
					'success' => false,
				),
				200
			);
		}

		$post         = $_POST; // phpcs:ignore WordPress.Security

		// Generate cache key based on specific group+course combination
		$data_cache_key = 'tincanny_user_course_data_' . $group_id . '_' . $course_id;

		// Try to get the processed data from cache first
		$cached_data = $this->get_cache( $data_cache_key );
		if ( false !== $cached_data ) {
			// Return cached data directly if available
			return new \WP_REST_Response(
				array(
					'message' => '',
					'success' => true,
					'data'    => $cached_data,
					'cached'  => true,
				),
				200
			);
		}

		if ( empty( $group_id ) ) {
			return new \WP_REST_Response(
				array(
					'message' => __( 'Group ID not found.', 'uncanny-learndash-reporting' ),
					'success' => false,
					'post'    => $post,
				),
				200
			);
		}

		if ( empty( $course_id ) ) {
			return new \WP_REST_Response(
				array(
					// translators: %s is the "Course" label
					'message' => sprintf( _x( '%s ID not found.', '%s is the "Course" label', 'uncanny-learndash-reporting' ), $course_label ),
					'success' => false,
					'post'    => $post,
				),
				200
			);
		}

		$data = array();

		// Check if we have a cached version of group users
		$group_users = $this->get_cache( 'tincanny_group_users_' . $group_id );

		if ( false === $group_users ) {
			// Cache miss, get fresh data
			$group_users = learndash_get_groups_users( $group_id, true );

			// Cache for 1 hour
			$this->set_cache( 'tincanny_group_users_' . $group_id, $group_users, 6 * HOUR_IN_SECONDS );
		}

		$user_names = array();

		$group_user_ids = array();

		// Get user names and meta data including quiz data in a single query
		$users_meta = $this->get_users_with_meta(
			array( 'first_name', 'last_name', '_sfwd-quizzes' ),
			array(),
			array_map( function( $user ) { return $user->data->ID; }, $group_users )
		);

		foreach ( $users_meta['results'] as $user ) {
			$user_id = $user['ID'];

			$first_name = $user['first_name'];
			$last_name  = $user['last_name'];

			if ( ! empty( $first_name ) && ! empty( $last_name ) ) {
				$name = $first_name . ' ' . $last_name;
			} elseif ( ! empty( $first_name ) ) {
				$name = $first_name;
			} else {
				$name = $user['display_name'];
			}

			$user_names[ $user_id ] = $name;
		}

		// get all tincanny h5p modules for all user in group ID for course ID
		foreach ( $group_users as $user_data ) {
			$user_id = absint( $user_data->data->ID );
			$user_courses = array( $course_id );
			$group_user_ids[] = $user_id;

			// Use the quiz data from the bulk query instead of individual get_user_meta calls
			$quiz_attempts_meta = false;
			if ( isset( $users_meta['results'] ) ) {
				foreach ( $users_meta['results'] as $user_meta ) {
					if ( (int) $user_meta['ID'] === (int) $user_id && ! empty( $user_meta['_sfwd-quizzes'] ) ) {
						$quiz_attempts_meta = maybe_unserialize( $user_meta['_sfwd-quizzes'] );
						break;
					}
				}
			}

			// Cache key for quiz attempts
			$quiz_attempts_cache_key = 'tincanny_quiz_attempts_' . $group_id . '_' . $course_id . '_' . $user_id;

			// Check if we have a cached version of quiz attempts
			$quiz_attempts = $this->get_cache( $quiz_attempts_cache_key );

			if ( false === $quiz_attempts ) {
				$quiz_attempts = array();

				if ( ! empty( $quiz_attempts_meta ) ) {

					foreach ( $quiz_attempts_meta as $quiz_attempt ) {
						$c                          = learndash_certificate_details( $quiz_attempt['quiz'], $user_id );
						$quiz_attempt['post']       = get_post( $quiz_attempt['quiz'] );
						$quiz_attempt['percentage'] = ! empty( $quiz_attempt['percentage'] ) ? $quiz_attempt['percentage'] : ( ! empty( $quiz_attempt['count'] ) ? $quiz_attempt['score'] * 100 / $quiz_attempt['count'] : 0 );

						if ( get_current_user_id() === $user_id && ! empty( $c['certificateLink'] ) && ( ( isset( $quiz_attempt['percentage'] ) && $quiz_attempt['percentage'] >= $c['certificate_threshold'] * 100 ) ) ) {
							$quiz_attempt['certificate'] = $c;
						}

						$course_id = absint( $quiz_attempt['course'] );

						$quiz_attempts[ $course_id ][] = $quiz_attempt;

					}
				}
			}

			// Generate a cache key for courses
			$courses_cache_key = 'tincanny_all_courses';

			// Try to get courses from cache
			$all_courses = $this->get_cache( $courses_cache_key );

			// If not in cache, fetch from database
			if ( false === $all_courses ) {
				$args = array(
					'numberposts' => 500, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts
					'post_type'   => 'sfwd-courses',
				);

				$all_courses = get_posts( $args );

				// Store in cache for future use
				$this->set_cache( $courses_cache_key, $all_courses );
			}

			$courses     = array();

			foreach ( $all_courses as $course ) {
				$courses[ $course->ID ] = $course;
			}

			if ( ! empty( $user_courses ) ) {

				foreach ( $user_courses as $course_id ) {

					if ( ! empty( $quiz_attempts[ $course_id ] ) ) {

						foreach ( $quiz_attempts[ $course_id ] as $k => $quiz_attempt ) {

							$score      = '';
							$modal_link = '';

							$quiz_title = ! empty( $quiz_attempt['post']->post_title )
								? $quiz_attempt['post']->post_title
								: ( isset( $quiz_attempt['quiz_title'] ) ? $quiz_attempt['quiz_title'] : '' );

							$quiz_attempt_post_id = ( isset($quiz_attempt['post']) && isset($quiz_attempt['post']->ID) ) ? $quiz_attempt['post']->ID : 0;

							if ( ! empty( $quiz_title ) ) {
								if ( ( isset( $quiz_attempt['has_graded'] ) ) && ( true === $quiz_attempt['has_graded'] ) && ( true === \LD_QuizPro::quiz_attempt_has_ungraded_question( $quiz_attempt ) ) ) {
									$score = _x( 'Pending', 'Pending Certificate Status Label', 'learndash' );
								} else {
									$score = round( $quiz_attempt['percentage'], 2 ) . '%';
								}
							}

							if ( ( get_current_user_id() === $user_id ) || ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) {

								if ( ( ! isset( $quiz_attempt['statistic_ref_id'] ) ) || ( empty( $quiz_attempt['statistic_ref_id'] ) ) ) {
									$quiz_attempt['statistic_ref_id'] = learndash_get_quiz_statistics_ref_for_quiz_attempt( $user_id, $quiz_attempt );
								}

								if ( ( isset( $quiz_attempt['statistic_ref_id'] ) ) && ( ! empty( $quiz_attempt['statistic_ref_id'] ) ) ) {
									/**
									 * Show user profile quiz statistics filter
									 *
									 * @since 2.3
									 * See snippet on use of this filter https://bitbucket.org/snippets/learndash/5o78q
									 */
									if ( $quiz_attempt_post_id && apply_filters(
										'show_user_profile_quiz_statistics',
										get_post_meta( $quiz_attempt_post_id, '_viewProfileStatistics', true ),
										$user_id,
										$quiz_attempt,
										basename( __FILE__ )
									) ) {

										$modal_link = '<a class="user_statistic"
									     data-statistic_nonce="' . wp_create_nonce( 'statistic_nonce_' . $quiz_attempt['statistic_ref_id'] . '_' . get_current_user_id() . '_' . $user_id ) . '"
									     data-user_id="' . $user_id . '"
									     data-quiz_id="' . $quiz_attempt['pro_quizid'] . '"
									     data-ref_id="' . intval( $quiz_attempt['statistic_ref_id'] ) . '"
									     data-nonce="' . wp_create_nonce( 'wpProQuiz_nonce' ) . '"
									     href="#">';
										$modal_link .= '<div class="statistic_icon"></div>';
										$modal_link .= '</a>';
									}
								}
							}

							$date = learndash_adjust_date_time_display( $quiz_attempt['time'] );

							if ( isset( $data[ sanitize_title( $course_id . $quiz_title . $user_id ) ] ) ) {
								if ( absint( $data[ sanitize_title( $course_id . $quiz_title . $user_id ) ]->quiz_score ) >= absint( $score ) ) {
									continue;
								}
							}

							$data[ sanitize_title( $course_id . $quiz_title . $user_id ) ] = (object) array(
								'user_name'  => $user_names[ (int) $user_id ],
								'user_id'    => $user_id,
								'quiz_name'  => $quiz_title,
								'quiz_score' => $score,
								'quiz_modal' => $modal_link,
								'quiz_date'  => array(
									'display'   => $date,
									'timestamp' => $quiz_attempt['time'],
								),
							);

						}
					}
				}
			}
		}

		// get all quiz results for all users in group for the course ID

		global $wpdb;
		$user_ids_string = ! empty( $group_user_ids ) ? join( "','", $group_user_ids ) : '';
		$user_id_query   = '';
		if ( ! empty( $user_ids_string ) ) {
			$user_id_query = "AND user_id IN ('$user_ids_string')";
		}

		// Cache key for quiz results
		$data_cache_key = 'tincanny_quiz_results_' . $group_id . '_' . $course_id;

		// Check if we have a cached version of quiz results
		$tc_quizzes = $this->get_cache( $data_cache_key );

		if ( false === $tc_quizzes ) {
			// Get all the H5P modules that have been completed
			$q = "SELECT user_id, module_name, module, result, minimum, xstored
			FROM {$wpdb->prefix}uotincan_reporting
			WHERE course_id = $course_id
			$user_id_query
			AND verb IN ( 'failed','passed', 'completed' )
		AND `result` IS NOT NULL";

			$tc_quizzes = $wpdb->get_results( $q );

			// Lets cache the results
			$this->set_cache( $data_cache_key, $tc_quizzes );
		}

		$display_format = apply_filters( 'learndash_date_time_formats', get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );

		$keep_report_first_entry = apply_filters( 'uo_tincanny_quiz_report_module_first_entry', false );

		// Loop through H5P completed and answered modules
		foreach ( $tc_quizzes as $_quiz ) {

			// Sanity check that the result is valid
			$score = 0 === absint( $_quiz->result ) ? '' : $_quiz->result . '%';

			$unique_module_path = preg_replace( '/[^ \w]+/', '', $_quiz->module );

			if ( isset( $data[ $unique_module_path . $_quiz->user_id ] ) ) {

				if ( $keep_report_first_entry ) {
					continue;
				}

				// Only overwrite data on duplicate H5P modules if the score if high
				if ( absint( $data[ $unique_module_path ]->quiz_score ) >= absint( $score ) ) {
					continue;
				}
			}

			if ( 0 !== strtotime( $_quiz->xstored ) ) {
				$date_time_display = date_i18n( $display_format, strtotime( $_quiz->xstored ) );
			} else {
				$date_time_display = '';
			}

			$data[ $unique_module_path . $_quiz->user_id ] = (object) array(
				'user_name'  => $user_names[ (int) $_quiz->user_id ],
				'user_id'    => (int) $_quiz->user_id,
				'quiz_name'  => $_quiz->module_name,
				'quiz_score' => $score,
				'quiz_modal' => '',
				'quiz_date'  => array(
					'display'   => $date_time_display,
					'timestamp' => strtotime( $_quiz->xstored ),
				),
			);

		}

		$rows = array();
		foreach ( $data as $row ) {
			$rows[] = $row;
		}

		// Cache the processed data
		$this->set_cache( $data_cache_key, $rows );

		return new \WP_REST_Response(
			array(
				'message' => '',
				'success' => true,
				'data'    => $rows,
				'cached'  => false,
			),
			200
		);
	}

	/**
	 * Group Quiz Report
	 *
	 * @return string
	 */
	public function group_quiz_report( $atts ) {

		// Is the user logged in
		if ( ! is_user_logged_in() ) {
			return __( 'Please Log in to view the report.', 'uncanny-learndash-reporting' );
		}

		$atts = shortcode_atts(
			array(
				'user_report_url' => '',
			),
			$atts,
			'group_quiz_report'
		);

		$user_report_url = $atts['user_report_url'];
		$site_url        = get_bloginfo( 'url' );

		if ( false === strstr( $user_report_url, $site_url ) ) {
			// support relative urls that start with or without a slash at the beginning.
			$user_report_url = implode( '/', array_filter( explode( '/', $user_report_url ) ) );
			$user_report_url = trailingslashit( $site_url ) . $user_report_url;
		}

		$current_user_id = get_current_user_id();

		// Check if user is an administrator
		if ( uotc_is_current_user_admin() ) {

			// if GET user_id is set in url the lets view a that group leaders report
			if ( ultc_filter_has_var( 'user_id' ) ) {

				$current_user_id = absint( ultc_filter_input( 'user_id' ) );
				$current_user    = get_user_by( 'ID', $current_user_id );

				if ( ! $current_user ) {
					return __( 'Invalid user set in address bar.', 'uncanny-learndash-reporting' );
				}
			}
		} else {
			$current_user = get_user_by( 'ID', $current_user_id );
		}

		if ( uotc_is_current_user_admin() ) {
			$group_ids = array();
		} elseif ( learndash_is_group_leader_user( $current_user ) ) {
			$group_ids = learndash_get_administrators_group_ids( $current_user_id );
		} else {
			return __( 'This report is only accessible by group leader and administrators.', 'uncanny-learndash-reporting' );
		}

		if ( empty( $group_ids ) && ! uotc_is_current_user_admin() ) {
			return __( 'Group Leader has no groups assigned.', 'uncanny-learndash-reporting' );
		} else {
			$groups = get_posts(
				array(
					'numberposts' => 500, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts
					'include'     => $group_ids,
					'post_type'   => 'groups',
				)
			);
		}

		$drop_down     = '<option value="">' . __( 'Select a Group', 'uncanny-learndash-reporting' ) . '</option>';
		$group_courses = array();
		foreach ( $groups as $group ) {
			$group_courses[ $group->ID ] = learndash_group_enrolled_courses( $group->ID );
			if ( ! empty( $group_courses[ $group->ID ] ) ) {
				$drop_down .= '<option value="' . $group->ID . '">' . $group->post_title . '</option>';
			}
		}

		$args = array(
			'numberposts' => 500, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts
			'post_type'   => 'sfwd-courses',
		);

		$all_courses = get_posts( $args );
		$courses     = array();

		foreach ( $all_courses as $course ) {
			$courses[ $course->ID ]['ID']         = $course->ID;
			$courses[ $course->ID ]['post_title'] = $course->post_title;
		}

		$course_label = \LearnDash_Custom_Label::get_label( 'course' );

		$course_drop = '<option value="">' . sprintf(
			// translators: %s is the "Course" label
				_x( 'Select a %s', '%s is the "Course" label', 'uncanny-learndash-reporting' ),
				$course_label
			) . '</option>';

		if ( 1 === count( $groups ) ) {
			foreach ( $group_courses[ $groups[0]->ID ] as $course ) {
				$course_drop .= '<option value="' . $courses[ (int) $course ]['ID'] . '">' . $courses[ (int) $course ]['post_title'] . '</option>';
			}
		}

		ob_start();

		$filepath   = Config::get_admin_js( 'learndash_template_script' );
		if ( ! empty( $filepath ) ) {

			wp_dequeue_script( 'learndash_template_script_js' );
			$assets_url = $filepath;

			wp_enqueue_script( 'learndash_template_script_js_2', $filepath, array( 'jquery' ), LEARNDASH_VERSION, true );

			$data                  = array();
			$data['ajaxurl']       = admin_url( 'admin-ajax.php' );
			$data['courses']       = $courses;
			$data['groupCourses']  = $group_courses;
			$data['userReportUrl'] = apply_filters( 'uo_tincanny_quiz_report_user_report_url', $user_report_url );
			$data                  = array(
				'json' => wp_json_encode( $data ),
			);
			wp_localize_script( 'learndash_template_script_js_2', 'sfwd_data', $data );

		}

		if ( defined( 'LEARNDASH_LMS_PLUGIN_URL' ) && function_exists( 'learndash_is_active_theme' ) && learndash_is_active_theme( 'ld30' ) ) {
			$icon = LEARNDASH_LMS_PLUGIN_URL . 'themes/legacy/templates/images/statistics-icon-small.png';
			?>
			<style>
				.statistic_icon {
					background: url(<?php echo esc_attr( $icon ); ?>) no-repeat scroll 0 0 transparent;
					width: 23px;
					height: 23px;
					margin: auto;
					background-size: 23px;
				}
			</style>
			<?php
		}

		\LD_QuizPro::showModalWindow();
		?>

		<div class="uotc-report" id="uotc-group-report">
			<div class="uotc-report__header">
				<div class="uotc-report__selects">
					<?php if ( 1 === count( $groups ) ) { ?>

						<?php foreach ( $groups as $group ) { ?>

							<div id="uo-group-report-selections"
								 data-group-courses="<?php echo wp_json_encode( $group_courses[ $group->ID ] ); ?>"
								 data-group-id="<?php echo esc_attr( $group->ID ); ?>">
								<?php echo esc_html( $group->post_title ); ?>
							</div>

						<?php } ?>

					<?php } else { ?>

						<div id="uo-group-report-selections">
							<select id="uo-group-report-group">
								<?php echo $drop_down; ?>
							</select>
						</div>

					<?php } ?>

					<div id="uo-group-report-course-selections">
						<select id="uo-group-report-courses">
							<?php echo $course_drop; ?>
						</select>
					</div>
				</div>
				<div class="uotc-report__buttons">
					<div class="uotc-report__btn uotc-report__btn--csv">CSV</div>
					<!-- #uotc-group-report-table--hidden .buttons-csv -->
					<div class="uotc-report__btn uotc-report__btn--pdf">PDF</div>
					<!-- #uotc-group-report-table--hidden .buttons-pdf -->
				</div>
			</div>

			<div class="uotc-report__table">
				<table id="uotc-group-report-table" class="display responsive" cellspacing="0" width="100%">
					<thead>
					<tr>
						<th><?php echo esc_html__( 'Name', 'uncanny-learndash-reporting' ); ?></th>
						<th><?php echo esc_html__( 'Activity', 'uncanny-learndash-reporting' ); ?></th>
						<th><?php echo esc_html__( 'Score', 'uncanny-learndash-reporting' ); ?></th>
						<th><?php echo esc_html__( 'Detailed Report', 'uncanny-learndash-reporting' ); ?></th>
						<th><?php echo esc_html__( 'Date', 'uncanny-learndash-reporting' ); ?></th>
					</tr>
					</thead>
				</table>
			</div>

			<div class="uotc-report__table uotc-report__table--hidden" id="uotc-group-report-container--hidden">
				<table id="uotc-group-report-table--hidden" class="display responsive" cellspacing="0" width="100%">
					<thead>
					<tr>
						<th><?php echo esc_html__( 'Name', 'uncanny-learndash-reporting' ); ?></th>
						<th><?php echo esc_html__( 'Activity', 'uncanny-learndash-reporting' ); ?></th>
						<th><?php echo esc_html__( 'Score', 'uncanny-learndash-reporting' ); ?></th>
						<th><?php echo esc_html__( 'Date', 'uncanny-learndash-reporting' ); ?></th>
					</tr>
					</thead>
				</table>
			</div>
		</div>

		<?php

		return ob_get_clean();
	}

	/**
	 * User Quiz Report
	 *
	 * @param array $atts
	 *
	 * @return string
	 */
	public function user_quiz_report( $atts = array() ) {

		// Check if user is an administrator
		if ( uotc_is_current_user_admin() || learndash_is_group_leader_user() ) {

			// if user id is set in url the lets view a that group leaders report
			if ( ultc_filter_has_var( 'user_id' ) ) {

				$user_id = absint( ultc_filter_input( 'user_id' ) );
				$user    = get_user_by( 'ID', $user_id );

				// Check if non admin has user in a one of the groups they are administrating
				if ( ! uotc_is_current_user_admin() ) {
					$group_leaders_users = learndash_get_group_leader_groups_users();

					if ( ! in_array( $user_id, $group_leaders_users, true ) ) {
						return __( 'Invalid user set in address bar.', 'uncanny-learndash-reporting' );
					}
				}

				if ( ! $user ) {
					return __( 'Invalid user set in address bar.', 'uncanny-learndash-reporting' );
				}
			} else {
				$user_id = get_current_user_id();
			}
		} else {
			$user_id = get_current_user_id();
		}

		// Is the user logged in
		if ( ! $user_id ) {
			return __( 'Please Log in to view the report.', 'uncanny-learndash-reporting' );
		}

		// Default to include non-course associated quizzes unless 'yes' is passed.
		$atts = shortcode_atts(
			array(
			'exclude_non_course_quizzes' => 'no',
			),
			$atts
		);

		$exclude_non_course_quizzes = 'yes' === strtolower( trim( $atts['exclude_non_course_quizzes'] ) );

		ob_start();

		$filepath   = Config::get_admin_js( 'learndash_template_script' );
		$assets_url = $filepath;
		if ( ! empty( $filepath ) ) {

			wp_dequeue_script( 'learndash_template_script_js' );
			wp_enqueue_script( 'learndash_template_script_js_2', $filepath, array( 'jquery' ), LEARNDASH_VERSION, true );

			$user_info = get_userdata( $user_id );
			$nicename  = $user_info->user_nicename;

			$data              = array();
			$data['ajaxurl']   = admin_url( 'admin-ajax.php' );
			$data['user_name'] = $nicename;
			$data              = array( 'json' => wp_json_encode( $data ) );
			wp_localize_script( 'learndash_template_script_js_2', 'sfwd_data', $data );
		}

		if ( defined( 'LEARNDASH_LMS_PLUGIN_URL' ) && function_exists( 'learndash_is_active_theme' ) && learndash_is_active_theme( 'ld30' ) ) {
			$icon = LEARNDASH_LMS_PLUGIN_URL . 'themes/legacy/templates/images/statistics-icon-small.png';
			?>
			<style>
				.statistic_icon {
					background: url(<?php echo esc_attr( $icon ); ?>) no-repeat scroll 0 0 transparent;
					width: 23px;
					height: 23px;
					margin: auto;
					background-size: 23px;
				}
			</style>
			<?php
		}

		\LD_QuizPro::showModalWindow();

		if ( learndash_is_group_leader_user() ) {
			$user_courses = learndash_get_group_leader_groups_courses();
		} else {
			$atts         = array(
				'user_id'            => $user_id,
				'order'              => 'DESC',
				'orderby'            => 'ID',
				'course_points_user' => 'yes',
			);
			$user_courses = ld_get_mycourses( $user_id, $atts );
		}

		$usermeta           = get_user_meta( $user_id, '_sfwd-quizzes', true );
		$quiz_attempts_meta = empty( $usermeta ) ? false : $usermeta;
		$quiz_attempts      = array();

		if ( ! empty( $quiz_attempts_meta ) ) {

			foreach ( $quiz_attempts_meta as $quiz_attempt ) {

				$c                          = learndash_certificate_details( $quiz_attempt['quiz'], $user_id );
				$quiz_attempt['post']       = get_post( $quiz_attempt['quiz'] );
				$quiz_attempt['percentage'] = ! empty( $quiz_attempt['percentage'] ) ? $quiz_attempt['percentage'] : ( ! empty( $quiz_attempt['count'] ) ? $quiz_attempt['score'] * 100 / $quiz_attempt['count'] : 0 );

				if ( get_current_user_id() === $user_id && ! empty( $c['certificateLink'] ) && ( ( isset( $quiz_attempt['percentage'] ) && $quiz_attempt['percentage'] >= $c['certificate_threshold'] * 100 ) ) ) {
					$quiz_attempt['certificate'] = $c;
				}

				$course_id = absint( $quiz_attempt['course'] );

				$quiz_attempts[ $course_id ][] = $quiz_attempt;
			}
		}

		$args = array(
			'numberposts' => 500, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts
			'post_type'   => 'sfwd-courses',
		);

		$all_courses = get_posts( $args );
		$courses     = array();

		foreach ( $all_courses as $course ) {
			$courses[ $course->ID ] = $course;
		}

		$data = array();

		if ( ! empty( $user_courses ) ) {

			foreach ( $user_courses as $course_id ) {

				if ( ! empty( $quiz_attempts[ $course_id ] ) ) {

					foreach ( $quiz_attempts[ $course_id ] as $k => $quiz_attempt ) {

						$score      = '';
						$modal_link = '';
						$quiz_title = ! empty( $quiz_attempt['post']->post_title )
							? $quiz_attempt['post']->post_title
							: ( isset( $quiz_attempt['quiz_title'] ) ? $quiz_attempt['quiz_title'] : '' );

						$quiz_attempt_post_id = ( isset($quiz_attempt['post']) && isset($quiz_attempt['post']->ID) ) ? $quiz_attempt['post']->ID : 0;

						if ( ! empty( $quiz_title ) ) {

							if ( ( isset( $quiz_attempt['has_graded'] ) ) && ( true === $quiz_attempt['has_graded'] ) && ( true === \LD_QuizPro::quiz_attempt_has_ungraded_question( $quiz_attempt ) ) ) {
								$score = _x( 'Pending', 'Pending Certificate Status Label', 'learndash' );
							} else {
								$score = round( $quiz_attempt['percentage'], 2 ) . '%';
							}
						}

						if ( ( get_current_user_id() === $user_id ) || ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) {

							if ( ( ! isset( $quiz_attempt['statistic_ref_id'] ) ) || ( empty( $quiz_attempt['statistic_ref_id'] ) ) ) {
								$quiz_attempt['statistic_ref_id'] = learndash_get_quiz_statistics_ref_for_quiz_attempt( $user_id, $quiz_attempt );
							}

							if ( ( isset( $quiz_attempt['statistic_ref_id'] ) ) && ( ! empty( $quiz_attempt['statistic_ref_id'] ) ) ) {
								/**
								 * Show user profile quiz statistics filter
								 *
								 * @since 2.3
								 * See snippet on use of this filter https://bitbucket.org/snippets/learndash/5o78q
								 */
								if ( $quiz_attempt_post_id && apply_filters(
									'show_user_profile_quiz_statistics',
									get_post_meta( $quiz_attempt_post_id, '_viewProfileStatistics', true ),
									$user_id,
									$quiz_attempt,
									basename( __FILE__ )
								) ) {

									$modal_link = '<a class="user_statistic"
									     data-statistic_nonce="' . wp_create_nonce( 'statistic_nonce_' . $quiz_attempt['statistic_ref_id'] . '_' . get_current_user_id() . '_' . $user_id ) . '"
									     data-user_id="' . $user_id . '"
									     data-quiz_id="' . $quiz_attempt['pro_quizid'] . '"
									     data-ref_id="' . intval( $quiz_attempt['statistic_ref_id'] ) . '"
									     data-nonce="' . wp_create_nonce( 'wpProQuiz_nonce' ) . '"
									     href="#">';
									$modal_link .= '<div class="statistic_icon"></div>';
									$modal_link .= '</a>';
								}
							}
						}

						$show_multiple_quiz_attempts = apply_filters( 'uo_tincanny_quiz_report_show_multiple_attempts', false, $course_id, $quiz_attempt_post_id );

						$date = learndash_adjust_date_time_display( $quiz_attempt['time'] );

						$data_key = sanitize_title( $course_id . $quiz_title );
						if ( $show_multiple_quiz_attempts ) {
							$data_key = sanitize_title( $course_id . $quiz_title . $k );
						}

						if ( isset( $data[ $data_key ] ) && false === $show_multiple_quiz_attempts ) {
							if ( absint( $data[ sanitize_title( $course_id . $quiz_title ) ]->quiz_score ) >= absint( $score ) ) {
								continue;
							}
						}

						$data[ $data_key ] = (object) array(
							'course_name' => $courses[ $course_id ]->post_title,
							'quiz_name'   => $quiz_title,
							'quiz_score'  => $score,
							'quiz_modal'  => $modal_link,
							'quiz_date'   => $date,
							'u_quiz_date' => $quiz_attempt['time'],
						);

					}
				}
			}
		}

		global $wpdb;

		$course_ids_string = implode( ',', $user_courses );

		$course_id_query = '';
		if ( ! empty( $course_ids_string ) ) {
			$course_id_query = "AND course_id IN ($course_ids_string)";
		}

		// Get all the H5P modules that have been completed
		$q = "SELECT course_id, module_name, module, result, minimum, xstored
		FROM {$wpdb->prefix}uotincan_reporting
		WHERE user_id = $user_id
		$course_id_query
		AND verb IN ( 'failed','passed', 'completed' )
		AND `result` IS NOT NULL";

		$tc_quizzes = $wpdb->get_results( $q );

		$display_format = apply_filters( 'learndash_date_time_formats', get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );

		$keep_report_first_entry = apply_filters( 'uo_tincanny_quiz_report_module_first_entry', false );

		// Loop through H5P completed and answered modules
		foreach ( $tc_quizzes as $mk => $_quiz ) {

			// Sanity check that the result is valid
			$score = 0 === absint( $_quiz->result ) ? '' : (int) $_quiz->result;

			$show_multiple_module_attempts = apply_filters( 'uo_tincanny_quiz_report_show_multiple_module_attempts', false, $_quiz->module );

			$unique_module_path = preg_replace( '/[^ \w]+/', '', $_quiz->module );
			if ( $show_multiple_module_attempts ) {
				$unique_module_path = $unique_module_path . $mk;
			}

			if ( isset( $data[ $unique_module_path ] ) ) {

				if ( $keep_report_first_entry ) {
					continue;
				}

				// Only overwrite data on duplicate H5P modules if the score if high
				if ( absint( $data[ $unique_module_path ]->quiz_score ) >= absint( $score ) ) {
					continue;
				}
			}

			if ( 0 !== strtotime( $_quiz->xstored ) ) {
				$date_time_display = date_i18n( $display_format, strtotime( $_quiz->xstored ) );
			} else {
				$date_time_display = '';
			}

			$data[ $unique_module_path ] = (object) array(
				'course_name' => $courses[ $_quiz->course_id ]->post_title,
				'quiz_name'   => $_quiz->module_name,
				'quiz_score'  => $score . '%',
				'quiz_modal'  => '',
				'quiz_date'   => $date_time_display,
				'u_quiz_date' => strtotime( $_quiz->xstored ),
			);
		}
		/**
		 * Ticket 27891. Show non-course associated quizzes
		 * Ticket 73705. Added the 'exclude_non_course_quizzes' attribute to the shortcode to override this behavior.
		 *
		 * @since 4.0
		 */
		$_user_activity_tbl = \LDLMS_DB::get_table_name( 'user_activity' );
		$non_associated     = $exclude_non_course_quizzes
			? false
			: $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$_user_activity_tbl} WHERE `user_id` = %d AND `course_id` = 0",
					$user_id
				)
			);
		if ( $non_associated ) {

			$_activity_meta_tbl = \LDLMS_DB::get_table_name( 'user_activity_meta' );

			foreach ( $non_associated as $n_a ) {
				$activity_id       = $n_a->activity_id;
				$_quiz             = $n_a->post_id;
				$_statistic_ref_id = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT activity_meta_value FROM {$_activity_meta_tbl} WHERE activity_meta_key = 'statistic_ref_id' AND activity_id = %d",
						$activity_id
					)
				);
				$pro_quizid        = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT activity_meta_value FROM {$_activity_meta_tbl} WHERE activity_meta_key = 'pro_quizid' AND activity_id = %d",
						$activity_id
					)
				);
				$percentage        = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT activity_meta_value FROM {$_activity_meta_tbl} WHERE activity_meta_key = 'percentage' AND activity_id = %d",
						$activity_id
					)
				);
				$modal_link        = '';
				if ( ! empty( $_statistic_ref_id ) ) {
					$modal_link = '<a class="user_statistic"
									     data-statistic_nonce="' . wp_create_nonce( 'statistic_nonce_' . $_statistic_ref_id . '_' . get_current_user_id() . '_' . $user_id ) . '"
									     data-user_id="' . $user_id . '"
									     data-quiz_id="' . $pro_quizid . '"
									     data-ref_id="' . intval( $_statistic_ref_id ) . '"
									     data-nonce="' . wp_create_nonce( 'wpProQuiz_nonce' ) . '"
									     href="#">';
					$modal_link .= '<div class="statistic_icon"></div>';
					$modal_link .= '</a>';
				}
				if ( ! empty( $n_a->activity_completed ) ) {
					$data[] = (object) array(
						'course_name' => 'N/A',
						'quiz_name'   => get_the_title( $_quiz ),
						'quiz_score'  => ! empty( $percentage ) ? "$percentage%" : 'N/A',
						'quiz_modal'  => $modal_link,
						'quiz_date'   => learndash_adjust_date_time_display( $n_a->activity_completed, $display_format ),
						'u_quiz_date' => $n_a->activity_completed,
					);
				}
			}
		}

		$user_info    = get_userdata( $user_id );
		$display_name = $user_info->display_name;
		$first_name   = $user_info->first_name;
		$last_name    = $user_info->last_name;

		if ( ! empty( $first_name ) && ! empty( $last_name ) ) {
			$name = $first_name . ' ' . $last_name;
		} elseif ( ! empty( $first_name ) ) {
			$name = $first_name;
		} else {
			$name = $display_name;
		}

		$course_label = \LearnDash_Custom_Label::get_label( 'course' );
		?>

		<h2>
			<?php
			echo esc_html(
				sprintf(
				// translators: %s is the user's name
					__( 'Activity Report for %s', 'uncanny-learndash-reporting' ),
					$name
				)
			);
			?>
		</h2>
		<div class="uotc-report" id="uotc-user-report">
			<div class="uotc-report__header">
				<div class="uotc-report__selects"></div>
				<div class="uotc-report__buttons">
					<div class="uotc-report__btn uotc-report__btn--csv"><?php echo esc_html__( 'CSV', 'uncanny-learndash-reporting' ); ?></div>
					<div class="uotc-report__btn uotc-report__btn--pdf"><?php echo esc_html__( 'PDF', 'uncanny-learndash-reporting' ); ?></div>
				</div>
			</div>

			<div class="uotc-report__table">
				<table id="uo-quiz-report-table" class="display responsive no-wrap" cellspacing="0" width="100%">
					<thead>
					<tr>
						<th><?php echo esc_html( $course_label ); ?></th>
						<th><?php echo esc_html__( 'Activity', 'uncanny-learndash-reporting' ); ?></th>
						<th><?php echo esc_html__( 'Score', 'uncanny-learndash-reporting' ); ?></th>
						<th><?php echo esc_html__( 'Detailed Report', 'uncanny-learndash-reporting' ); ?></th>
						<th><?php echo esc_html__( 'Date', 'uncanny-learndash-reporting' ); ?></th>
					</tr>
					</thead>
					<tbody>
					<?php foreach ( $data as $quiz ) { ?>
						<tr>
							<td><?php echo esc_html( $quiz->course_name ); ?></td>
							<td><?php echo esc_html( $quiz->quiz_name ); ?></td>
							<td><?php echo esc_html( $quiz->quiz_score ); ?></td>
							<td><?php echo wp_kses_post( $quiz->quiz_modal ); ?></td>
							<td data-order="<?php echo esc_attr( $quiz->u_quiz_date ); ?>"><?php echo esc_html( $quiz->quiz_date ); ?></td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
			</div>

			<div class="uotc-report__table uotc-report__table--hidden" id="uotc-user-report-container--hidden">
				<table id="uo-quiz-report-table-hidden" class="display responsive no-wrap" cellspacing="0" width="100%">
					<thead>
					<tr>
						<th><?php echo esc_html( $course_label ); ?></th>
						<th><?php echo esc_html__( 'Activity', 'uncanny-learndash-reporting' ); ?></th>
						<th><?php echo esc_html__( 'Score', 'uncanny-learndash-reporting' ); ?></th>
						<th><?php echo esc_html__( 'Date', 'uncanny-learndash-reporting' ); ?></th>
					</tr>
					</thead>
					<tbody>
					<?php
					foreach ( $data as $quiz ) {
						?>
						<tr>
							<td><?php echo esc_html( $quiz->course_name ); ?></td>
							<td><?php echo esc_html( $quiz->quiz_name ); ?></td>
							<td><?php echo esc_html( $quiz->quiz_score ); ?></td>
							<td data-order="<?php echo esc_attr( $quiz->u_quiz_date ); ?>"><?php echo esc_html( $quiz->quiz_date ); ?></td>
						</tr>
						<?php
					}
					?>
					</tbody>
					<tfoot>
					<tr>
						<th><?php echo esc_html( $course_label ); ?></th>
						<th><?php echo esc_html__( 'Activity', 'uncanny-learndash-reporting' ); ?></th>
						<th><?php echo esc_html__( 'Score', 'uncanny-learndash-reporting' ); ?></th>
						<th><?php echo esc_html__( 'Date', 'uncanny-learndash-reporting' ); ?></th>
					</tr>
					</tfoot>
				</table>
			</div>
		</div>

		<?php

		return ob_get_clean();

	}

	/**
	 * Report Scripts
	 */
	public function report_scripts() {
		global $post;

		$block_is_on_page = false;
		if ( is_a( $post, 'WP_Post' ) && function_exists( 'parse_blocks' ) ) {
			$blocks = parse_blocks( $post->post_content );
			foreach ( $blocks as $block ) {
				if ( 'tincanny/user-quiz-report' === $block['blockName'] || 'tincanny/group-quiz-report' === $block['blockName'] ) {
					$block_is_on_page = true;
				}
			}
		}

		if ( is_a( $post, 'WP_Post' ) && (
				$block_is_on_page ||
				has_shortcode( $post->post_content, 'uo_individual_quiz_report' ) ||
				has_shortcode( $post->post_content, 'uo_group_quiz_report' )
			) ) {

			// Front End Questionnaire JS
			wp_register_script( 'quiz-module-reports-js', self::get_admin_js( 'tincanny-reports' ), array( 'jquery', 'tc_runtime', 'tc_vendors' ), '1.0', true );

			// Course label
			$course_label  = \LearnDash_Custom_Label::get_label( 'course' );
			$courses_label = \LearnDash_Custom_Label::get_label( 'courses' );

			// Attach API data to custom-toolkit-js
			$api_setup = array(
				'root'        => esc_url_raw( rest_url() . 'tincanny/v1/' ),
				'nonce'       => \wp_create_nonce( 'wp_rest' ),
				'currentUser' => get_current_user_id(),
				'i18n'        => array(
					'emptyTable'                       => __( 'No data available in table', 'uncanny-learndash-reporting' ),
					'info'                             => sprintf(
					// translators: %1$s is the start number, %2$s is the end number, and %3$s is the total number of entries
						_x( 'Showing %1$s to %2$s of %3$s entries', '%1$s is the start number, %2$s is the end number, and %3$s is the total number of entries', 'uncanny-learndash-reporting' ),
						'_START_',
						'_END_',
						'_TOTAL_'
					),
					'infoEmpty'                        => __( 'Showing 0 to 0 of 0 entries', 'uncanny-learndash-reporting' ),
					'infoFiltered'                     => sprintf(
					// translators: %s is the total number of entries
						_x( '(filtered from %s total entries)', '%s is a number', 'uncanny-learndash-reporting' ),
						'_MAX_'
					),
					'loadingRecords'                   => __( 'Loading...', 'uncanny-learndash-reporting' ),
					'processing'                       => __( 'Processing...', 'uncanny-learndash-reporting' ),
					'searchPlaceholder'                => __( 'Search...', 'uncanny-learndash-reporting' ),
					'zeroRecords'                      => __( 'No matching records found', 'uncanny-learndash-reporting' ),
					'paginate'                         => array(
						'first'    => __( 'First', 'uncanny-learndash-reporting' ),
						'last'     => __( 'Last', 'uncanny-learndash-reporting' ),
						'next'     => __( 'Next', 'uncanny-learndash-reporting' ),
						'previous' => __( 'Previous', 'uncanny-learndash-reporting' ),
					),
					'aria'                             => array(
						'sortAscending'  => sprintf( ': %s', __( 'activate to sort column ascending', 'uncanny-learndash-reporting' ) ),
						'sortDescending' => sprintf( ': %s', __( 'activate to sort column descending', 'uncanny-learndash-reporting' ) ),
					),
					'selectStudentDropdownPlaceholder' => __( 'Select a student', 'uncanny-learndash-reporting' ),
					'selectStudentDropdownNoStudents'  => __( 'No students available', 'uncanny-learndash-reporting' ),
					'selectCourseDropdownPlaceholder'  => sprintf(
					// translators: %s is the "Course" label
						_x( 'Select a %s', '%s is the "Course" label', 'uncanny-learndash-reporting' ),
						$course_label
					),
					'selectCourseDropdownNoCourses'    => sprintf(
					// translators: %s is the "Courses" label
						_x( 'No %s available', '%s is the "Courses" label', 'uncanny-learndash-reporting' ),
						$courses_label
					),
				),
			);

			wp_localize_script( 'quiz-module-reports-js', 'groupLeaderCourseReportSetup', $api_setup );

			wp_enqueue_script( 'quiz-module-reports-js' );

			wp_enqueue_style( 'quiz-module-reports-css', self::get_admin_css( 'quiz-module-reports.css' ) );
			//wp_enqueue_style( 'datatables-styles', self::get_admin_css( 'datatables.min.css' ) );

			// Admin JS
			wp_enqueue_script(
				'tc_pdfmake',
				Config::get_admin_js( 'vendor/pdfmake' ),
				array(
					'tc_runtime',
				),
				UNCANNY_REPORTING_VERSION,
				false
			);

			wp_enqueue_script(
				'tc_pdfmake_vfs_fonts',
				Config::get_admin_js( 'vendor/vfs_fonts', '.js' ),
				array(
					'tc_runtime',
				),
				UNCANNY_REPORTING_VERSION,
				false
			);


			ReportingAdminMenu::enqueue_common_scripts();
		}
	}

	/**
	 * Get users with meta
	 *
	 * @param array $exact_meta_keys    Exact meta keys to retrieve.
	 * @param array $fuzzy_meta_keys    Fuzzy meta keys to retrieve.
	 * @param array $included_user_ids  Included user IDs.
	 *
	 * @return array
	 */
	public function get_users_with_meta( $exact_meta_keys = array(), $fuzzy_meta_keys = array(), $included_user_ids = array() ) {
		// Generate a unique transient key based on function parameters.
		$cache_key = 'tincanny_users_with_meta_' . md5( serialize( array(
			'exact_meta_keys'    => $exact_meta_keys,
			'fuzzy_meta_keys'    => $fuzzy_meta_keys,
			'included_user_ids'  => $included_user_ids,
		) ) );

		// Try to get cached result.
		$cached_result = $this->get_cache( $cache_key );

		if ( false !== $cached_result ) {
			return $cached_result;
		}

		global $wpdb;

		// Collect all possible meta_key values
		$keys = $wpdb->get_col( "SELECT distinct meta_key FROM $wpdb->usermeta" );

		//then prepare the meta keys query as fields which we'll join to the user table fields
		$meta_columns = '';
		foreach ( $keys as $key ) {

			// Collect exact matches
			if ( ! empty( $exact_meta_keys ) ) {
				if ( in_array( $key, $exact_meta_keys, true ) ) {
					$meta_columns .= " MAX(CASE WHEN um.meta_key = '$key' THEN um.meta_value ELSE '' END) AS '$key', \n";
					continue;
				}
			}

			// Collect fuzzy matches ... ex. "example" would match "example_947"
			// ToDo allow for SQL "LIKE" syntax ... ex "example%947"
			// ToDo allow for regex
			if ( ! empty( $fuzzy_meta_keys ) ) {
				foreach ( $fuzzy_meta_keys as $fuzzy_key ) {
					if ( false !== strpos( $key, $fuzzy_key ) ) {
						$meta_columns .= " MAX(CASE WHEN um.meta_key = '$key' THEN um.meta_value ELSE NULL END) AS '$key', \n";
					}
				}
			}
		}

		$user_ids = '';

		if ( ! empty( $include_user_ids ) ) {
			$user_ids = 'WHERE u.ID IN (\'' . implode( ',', $include_user_ids ) . '\')';
		}
		//then write the main query with all of the regular fields and use a simple left join on user users.ID and usermeta.user_id
		$query = '
SELECT
    u.ID,
    u.user_login,
    u.user_pass,
    u.user_nicename,
    u.user_email,
    u.user_url,
    u.user_registered,
    u.user_activation_key,
    u.user_status,
    u.display_name,
    ' . rtrim( $meta_columns, ", \n" ) . "
FROM
    $wpdb->users u
LEFT JOIN
    $wpdb->usermeta um ON (um.user_id = u.ID)
$user_ids
GROUP BY
    u.ID";

		$users = $wpdb->get_results( $query, ARRAY_A );

		$result = array(
			'query'   => $query,
			'results' => $users,
		);

		// Cache for 2 hour.
		$this->set_cache( $cache_key, $result, 2 * HOUR_IN_SECONDS );

		return $result;
	}

	/**
	 * Get data from cache using the best available method
	 *
	 * @param string $cache_key   The unique key for the cache
	 * @param string $group       Optional. Cache group
	 * @return mixed The cached value or false on failure
	 */
	private function get_cache( $cache_key, $group = 'tincanny_reporting' ) {
			$cached_data = wp_cache_get( $cache_key, $group );
			if ( false !== $cached_data ) {
				return $cached_data;
			}

			return false;
	}

	/**
	 * Store data in cache using the best available method
	 *
	 * @param string $cache_key   The unique key for the cache
	 * @param mixed  $data        The data to cache
	 * @param int    $expiration  Time until expiration in seconds
	 * @param string $group       Optional. Cache group
	 * @return bool True on success, false on failure
	 */
	private function set_cache( $cache_key, $data, $expiration = HOUR_IN_SECONDS, $group = 'tincanny_reporting' ) {
			return wp_cache_set( $cache_key, $data, $group, $expiration );
	}

}