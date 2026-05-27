<?php

namespace uncanny_learndash_reporting;

use LDLMS_DB;
use LearnDash_Custom_Label;

/**
 *
 */
class TableData {

	/**
	 * This endpoint is used in both Course and User Reports. It returns the data for the table when the user clicks "See details".
	 *
	 * @return array|mixed|null
	 */
	public static function get_table_data() {

		$table_type = ultc_get_filter_var( 'tableType', '', INPUT_POST );

		if ( 'courseSingleTable' === $table_type ) {
			return self::course_single_table();
		}

		if ( 'userSingleCoursesOverviewTable' === $table_type ) {
			return self::user_single_courses_overview_table();
		}

		if ( 'userSingleCourseProgressSummaryTable' === $table_type ) {
			return self::user_single_course_progress_summary_table();
		}

		return array(
			'message' => 'tableType not set',
			'success' => false,
			'data'    => array(),
		);
	}

	/**
	 * @return array|mixed|null
	 */
	public static function course_single_table() {
		$json_return = array(
			'message' => '',
			'success' => false,
			'data'    => array(),
		);

		//      $json_return['success'] = false;
		//      $json_return['data']    = $_POST; // phpcs:ignore WordPress.Security

		if ( ! ultc_filter_has_var( 'courseId', INPUT_POST ) && ! ultc_filter_has_var( 'rows', INPUT_POST ) ) {
			$json_return['message'] = 'courseId or rowsIds not set';
			return $json_return;
		}

			$course_id = absint( ultc_filter_input( 'courseId', INPUT_POST ) );
			$rows      = array();
			$post_rows = array();

			// phpcs:disable WordPress.Security
		if ( is_string( $_POST['rows'] ) ) {
			$post_rows = json_decode( stripslashes( ultc_filter_input( 'rows', INPUT_POST ) ), true );
		} elseif ( is_array( $_POST['rows'] ) ) {
			$post_rows = ultc_filter_input_array( 'rows', INPUT_POST );
		}

		if ( ! empty( $post_rows ) ) {
			foreach ( $post_rows as $row ) {
				if ( isset( $row['rowId'], $row['ID'] ) ) {
					$rows[ absint( $row['rowId'] ) ] = absint( $row['ID'] );
				}
			}
		}
			// phpcs:enable WordPress.Security

			$json_return['success'] = true;
			$json_return['data']    = self::get_course_single_overview( $course_id, $rows );

			return apply_filters( 'tc_api_get_courseSingleTable', $json_return, $course_id, $rows ); // phpcs:ignore WordPress.NamingConventions.ValidHookName
	}

	/**
	 * Get course single overview, Get called from self::get_table_data()
	 *
	 * @param $course_id
	 * @param $user_ids
	 *
	 * @return array
	 */
	private static function get_course_single_overview( $course_id, $user_ids ) {

		$user_ids_rearranged = array();
		foreach ( $user_ids as $row_id => $user_id ) {
			$user_ids_rearranged[ $user_id ]             = array();
			$user_ids_rearranged[ $user_id ]['progress'] = 0;
			$user_ids_rearranged[ $user_id ]['date']     = array(
				'display'   => '',
				'timestamp' => '0',
			);
		}

		global $wpdb;

		$complete_key = "course_completed_{$course_id}";
		$user_data    = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id, meta_key, meta_value FROM {$wpdb->usermeta} WHERE meta_key = '_sfwd-course_progress' OR meta_key = %s",
				$complete_key
			)
		);

		foreach ( $user_data as $data ) {
			$user_id = $data->user_id;

			if ( ! isset( $user_ids_rearranged[ $user_id ] ) ) {
				continue;
			}

			$meta_key   = $data->meta_key;
			$meta_value = $data->meta_value;

			if ( $complete_key === $meta_key ) {
				if ( absint( $meta_value ) ) {
					$user_ids_rearranged[ $user_id ]['date'] = array(
						'display'   => learndash_adjust_date_time_display( $meta_value ),
						'timestamp' => (string) $meta_value,
					);
				}
			} elseif ( '_sfwd-course_progress' === $meta_key ) {
				$progress = maybe_unserialize( $meta_value );
				if ( ! empty( $progress ) && ! empty( $progress[ $course_id ] ) && ! empty( $progress[ $course_id ]['total'] ) ) {
					$completed = intVal( $progress[ $course_id ]['completed'] );
					$total     = intVal( $progress[ $course_id ]['total'] );
					if ( $total > 0 ) {
						$percentage                                  = intval( $completed * 100 / $total );
						$percentage                                  = ( $percentage > 100 ) ? 100 : $percentage;
						$user_ids_rearranged[ $user_id ]['progress'] = $percentage;
					}
				}
			}
		}

		// Fallback: If progress is still 0, try learndash_course_progress() for each user
		foreach ( $user_ids_rearranged as $user_id => $user_data ) {
			if ( $user_data['progress'] === 0 && function_exists('learndash_course_progress') ) {
				$course_progress = learndash_course_progress(
					array(
						'course_id' => $course_id,
						'user_id'   => $user_id,
						'array'     => true,
					)
				);

				if ( ! empty( $course_progress ) && isset( $course_progress['completed'] ) && isset( $course_progress['total'] ) ) {
					$completed = intVal( $course_progress['completed'] );
					$total     = intVal( $course_progress['total'] );
					if ( $total > 0 ) {
						$percentage = intval( $completed * 100 / $total );
						$percentage = ( $percentage > 100 ) ? 100 : $percentage;
						$user_ids_rearranged[ $user_id ]['progress'] = $percentage;
					}
				}
			}
		}

		$quiz_averages = self::get_course_quiz_average_by_user( $course_id, $user_ids );

		$rows = array();
		foreach ( $user_ids as $row_id => $user_id ) {

			$rows[ $row_id ]['user_id']        = $user_id;
			$rows[ $row_id ]['completed_date'] = $user_ids_rearranged[ $user_id ]['date'];
			$rows[ $row_id ]['progress']       = $user_ids_rearranged[ $user_id ]['progress'];

			if ( isset( $quiz_averages[ $user_id ] ) ) {
				$rows[ $row_id ]['quiz_average'] = $quiz_averages[ $user_id ];
			} else {
				$rows[ $row_id ]['quiz_average'] = '';
			}
		}

		return $rows;
	}

	/**
	 * Get Course Quiz Average By User
	 *
	 * @param $course_id
	 * @param $user_ids
	 *
	 * @return array
	 */
	private static function get_course_quiz_average_by_user( $course_id, $user_ids ) {

		global $wpdb;

		$user_ids_rearranged = array();
		foreach ( $user_ids as $user_id ) {
			$user_ids_rearranged[ $user_id ] = $user_id;
		}

		$quiz_results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.course_id, a.post_id, m.activity_meta_value as activity_percentage, a.user_id
				FROM {$wpdb->prefix}learndash_user_activity a
				LEFT JOIN {$wpdb->prefix}learndash_user_activity_meta m ON a.activity_id = m.activity_id
				WHERE a.activity_type = 'quiz'
				AND a.course_id = %d
				AND m.activity_meta_key = 'percentage'",
				$course_id
			)
		);

		$quiz_scores = array();

		foreach ( $quiz_results as $activity ) {

			if ( isset( $user_ids_rearranged[ (int) $activity->user_id ] ) ) {

				if ( ! isset( $quiz_scores[ $activity->user_id ] ) ) {
					$quiz_scores[ $activity->user_id ] = array();
				}

				if ( ! isset( $quiz_scores[ $activity->user_id ][ $activity->post_id ] ) ) {
					$quiz_scores[ $activity->user_id ][ $activity->post_id ] = $activity->activity_percentage;
				} elseif ( $quiz_scores[ $activity->user_id ][ $activity->post_id ] < $activity->activity_percentage ) {
					$quiz_scores[ $activity->user_id ][ $activity->post_id ] = $activity->activity_percentage;
				}
			}
		}

		$averages = array();
		if ( 0 !== count( $quiz_scores ) ) {
			foreach ( $quiz_scores as $user_id => $scores ) {
				$averages[ $user_id ] = absint( array_sum( $scores ) / count( $scores ) );
			}
		}

		return $averages;
	}

	/**
	 * @return array|mixed|null
	 */
	public static function user_single_courses_overview_table() {
		$json_return = array(
			'message' => '',
			'success' => false,
			'data'    => array(),
		);

		if ( ! ultc_filter_has_var( 'userId', INPUT_POST ) ) {
			$json_return['message'] = 'userId or rowsIds not set';
			return $json_return;
		}

		$user_id     = absint( ultc_filter_input( 'userId', INPUT_POST ) );
		$rows        = array();
		$posted_rows = ultc_filter_input_array( 'rows', INPUT_POST );
		if ( is_null( $posted_rows ) || false === $posted_rows ) {
			$posted_rows = json_decode( stripslashes( ultc_filter_input( 'rows', INPUT_POST ) ), true );
		}

		if ( is_array( $posted_rows ) ) {
			foreach ( $posted_rows as $row ) {
				$rows[ absint( $row['rowId'] ) ] = absint( $row['ID'] );
			}
		}
		$json_return['success'] = true;
		$json_return['user_id'] = $user_id;
		$json_return['data']    = self::get_user_single_overview( $user_id, $rows );

		return apply_filters( 'tc_api_get_userSingleCoursesOverviewTable', $json_return, $user_id, $rows ); // phpcs:ignore WordPress.NamingConventions.ValidHookName
	}

	/**
	 * Get user single overview. Get called from self::get_table_data()
	 *
	 * @param $user_id
	 * @param $course_ids
	 *
	 * @return array
	 */
	private static function get_user_single_overview( $user_id, $course_ids ) {

		$rows = array();

		// quiz scores
		global $wpdb;

		$user_activities = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.course_id, a.post_id, m.activity_meta_value as activity_percentage, a.activity_status
				FROM {$wpdb->prefix}learndash_user_activity a
				LEFT JOIN {$wpdb->prefix}learndash_user_activity_meta m ON a.activity_id = m.activity_id
				WHERE a.user_id = %d
				AND a.activity_type = 'quiz'
				AND m.activity_meta_key = 'percentage'",
				$user_id
			)
		);

		$progress              = get_user_meta( $user_id, '_sfwd-course_progress', true );
		$course_ids_rearranged = array();

		foreach ( $course_ids as $row_id => $course_id ) {
			$course_ids_rearranged[ $course_id ] = array(
				'progress' => 0,
				'date' => array(
					'display'   => '',
					'timestamp' => '0',
				)
			);
			if ( ! empty( $progress ) && ! empty( $progress[ $course_id ] ) && ! empty( $progress[ $course_id ]['total'] ) ) {
				$completed = intVal( $progress[ $course_id ]['completed'] );
				$total     = intVal( $progress[ $course_id ]['total'] );
				if ( $total > 0 ) {
					$percentage                                      = intval( $completed * 100 / $total );
					$percentage                                      = ( $percentage > 100 ) ? 100 : $percentage;
					$course_ids_rearranged[ $course_id ]['progress'] = $percentage;
				}
			} elseif( function_exists('learndash_course_progress') ) {
				// Lets try LD course progress method to get the progress.
				$course_progress = learndash_course_progress(
					array(
						'course_id' => $course_id,
						'user_id'   => $user_id,
						'array'     => true,
					)
				);

				if ( ! empty( $course_progress ) && isset( $course_progress['completed'] ) && isset( $course_progress['total'] ) ) {
					$completed = intVal( $course_progress['completed'] );
					$total     = intVal( $course_progress['total'] );
					if ( $total > 0 ) {
						$percentage                                      = intval( $completed * 100 / $total );
						$percentage                                      = ( $percentage > 100 ) ? 100 : $percentage;
						$course_ids_rearranged[ $course_id ]['progress'] = $percentage;
					}
				}
			}

		}

		$user_data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id, meta_key, meta_value FROM {$wpdb->usermeta} WHERE meta_key LIKE %s AND user_id = %d",
				$wpdb->esc_like( 'course_completed_' ) . '%',
				$user_id
			)
		);

		foreach ( $user_data as $data ) {
			$x_meta_key = explode( '_', $data->meta_key );
			$course_id  = $x_meta_key[2];

			$meta_value = $data->meta_value;

			$course_ids_rearranged[ $course_id ]['date'] = array(
				'display'   => learndash_adjust_date_time_display( $meta_value ),
				'timestamp' => (string) $meta_value,
			);
		}

		foreach ( $course_ids as $row_id => $course_id ) {

			$rows[ $row_id ]['course_id']      = $course_id;
			$rows[ $row_id ]['completed_date'] = $course_ids_rearranged[ $course_id ]['date'];
			$rows[ $row_id ]['progress']       = $course_ids_rearranged[ $course_id ]['progress'];

			// Column Quiz Average
			$course_quiz_average = self::get_avergae_quiz_result( $course_id, $user_activities );

			$avg_score = '';

			if ( $course_quiz_average ) {
				/* Translators: 1. number percentage */
				$avg_score = sprintf( __( '%1$s%%', 'uncanny-learndash-reporting' ), $course_quiz_average );
			}

			$rows[ $row_id ]['avg_score'] = $avg_score;
		}

		return $rows;
	}

	/**
	 * Get Average Quiz Result
	 *
	 * @param $course_id
	 * @param $user_activities
	 *
	 * @return false|int
	 */
	private static function get_avergae_quiz_result( $course_id, $user_activities ) {

		$quiz_scores = array();

		foreach ( $user_activities as $activity ) {

			if ( (int) $course_id === (int) $activity->course_id ) {

				if ( ! isset( $quiz_scores[ $activity->post_id ] ) ) {

					$quiz_scores[ $activity->post_id ] = $activity->activity_percentage;
				} elseif ( $quiz_scores[ $activity->post_id ] < $activity->activity_percentage ) {

					$quiz_scores[ $activity->post_id ] = $activity->activity_percentage;
				}
			}
		}

		if ( 0 !== count( $quiz_scores ) ) {
			$average = absint( array_sum( $quiz_scores ) / count( $quiz_scores ) );
		} else {
			$average = false;
		}

		return $average;
	}

	/**
	 * @return array|mixed|null
	 */
	public static function user_single_course_progress_summary_table() {
		$json_return = array(
			'message' => '',
			'success' => false,
			'data'    => array(),
		);

		//      $json_return['success'] = false;
		//      $json_return['data']    = $_POST;// phpcs:ignore WordPress.Security

		if ( ! ultc_filter_has_var( 'userId', INPUT_POST ) && ! ultc_filter_has_var( 'courseId', INPUT_POST ) ) {
				  $json_return['message'] = 'userId or courseId not set';
			return $json_return;
		}
			$user_id   = absint( ultc_filter_input( 'userId', INPUT_POST ) );
			$course_id = absint( ultc_filter_input( 'courseId', INPUT_POST ) );

			$json_return['success'] = true;
			$json_return['data']    = self::get_user_single_course_overview( $user_id, $course_id );

			return apply_filters( 'tc_api_get_userSingleCourseProgressSummaryTable', $json_return, $user_id, $course_id ); // phpcs:ignore WordPress.NamingConventions.ValidHookName
	}

	/**
	 * Get user single course overview. Get called from self::get_table_data()
	 *
	 * @param $user_id
	 * @param $course_id
	 *
	 * @return array
	 */
	private static function get_user_single_course_overview( $user_id, $course_id ) {

		$status_values                 = array();
		$status_values['completed']    = __( 'Completed', 'uncanny-learndash-reporting' );
		$status_values['notcompleted'] = __( 'Not Completed', 'uncanny-learndash-reporting' );

		// Get Lessons
		$lessons_list       = learndash_get_course_lessons_list( $course_id, $user_id, array( 'per_page' => - 1 ) );
		$course_quiz_list   = array();
		$course_quiz_list[] = learndash_get_course_quiz_list( $course_id );

		$lessons      = array();
		$topics       = array();
		$lesson_names = array();
		$topic_names  = array();
		$quiz_names   = array();

		$lesson_order = 0;
		$topic_order  = 0;
		foreach ( $lessons_list as $lesson ) {

			$lesson_names[ $lesson['post']->ID ] = $lesson['post']->post_title;
			$lessons[ $lesson_order ]            = array(
				'name'   => $lesson['post']->post_title,
				'status' => $status_values[ $lesson['status'] ],
			);

			// Get lesson completion time
			$lesson_activity = learndash_get_user_activity(
				array(
					'user_id'       => $user_id,
					'course_id'     => $course_id,
					'post_id'       => $lesson['post']->ID,
					'activity_type' => 'lesson',
				)
			);
			if ( ! empty( $lesson_activity ) ) {
				$lessons[ $lesson_order ]['completed_date'] = learndash_adjust_date_time_display( $lesson_activity->activity_completed );
			}

			$course_quiz_list[] = learndash_get_lesson_quiz_list( $lesson['post']->ID, $user_id, $course_id );
			$lesson_topics      = learndash_get_topic_list( $lesson['post']->ID, $course_id );

			foreach ( $lesson_topics as $topic ) {

				$course_quiz_list[] = learndash_get_lesson_quiz_list( $topic->ID, $user_id, $course_id );

				$topic_progress = learndash_get_course_progress( $user_id, $topic->ID, $course_id );

				$topic_names[ $topic->ID ] = $topic->post_title;

				$topics[ $topic_order ] = array(
					'name'              => $topic->post_title,
					'status'            => $status_values['notcompleted'],
					'associated_lesson' => $lesson['post']->post_title,
				);

				// Get topic completion time
				$topic_activity = learndash_get_user_activity(
					array(
						'user_id'       => $user_id,
						'course_id'     => $course_id,
						'post_id'       => $topic->ID,
						'activity_type' => 'topic',
					)
				);
				if ( ! empty( $topic_activity ) ) {
					$topics[ $topic_order ]['completed_date'] = learndash_adjust_date_time_display( $topic_activity->activity_completed );
				}

				if ( ( isset( $topic_progress['posts'] ) ) && ( ! empty( $topic_progress['posts'] ) ) ) {
					foreach ( $topic_progress['posts'] as $topic_progress ) {

						if ( $topic->ID !== $topic_progress->ID ) {
							continue;
						}

						if ( 1 === $topic_progress->completed ) {
							$topics[ $topic_order ]['status'] = $status_values['completed'];
						}
					}
				}
				$topic_order ++;
			}
			$lesson_order ++;
		}

		global $wpdb;

		// Assignments
		$assignments            = array();
		$assignment_data_object = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post.ID, post.post_title, post.post_date, postmeta.meta_key, postmeta.meta_value
				FROM {$wpdb->posts} post
				JOIN {$wpdb->postmeta} postmeta ON post.ID = postmeta.post_id
				WHERE post.post_status = 'publish' AND post.post_type = 'sfwd-assignment'
				AND post.post_author = %d
				AND ( postmeta.meta_key = 'approval_status' OR postmeta.meta_key = 'course_id' OR postmeta.meta_key LIKE %s )",
				$user_id,
				$wpdb->esc_like( 'ld_course_' ) . '%'
			)
		);

		foreach ( $assignment_data_object as $assignment ) {

			// Assignment List
			$data               = array();
			$data['ID']         = $assignment->ID;
			$data['post_title'] = $assignment->post_title;

			$assignment_id                                = (int) $assignment->ID;
			$rearranged_assignment_list[ $assignment_id ] = $data;

			// User Assignment Data
			$assignment_id = (int) $assignment->ID;
			$meta_key      = $assignment->meta_key;
			$meta_value    = (int) $assignment->meta_value;

			$date = learndash_adjust_date_time_display( strtotime( $assignment->post_date ) );

			$assignments[ $assignment_id ]['name']           = '<a target="_blank" href="' . get_edit_post_link( $assignment->ID ) . '">' . $assignment->post_title . '</a>';
			$assignments[ $assignment_id ]['completed_date'] = $date;
			$assignments[ $assignment_id ][ $meta_key ]      = $meta_value;

		}

		foreach ( $assignments as $assignment_id => &$assignment ) {
			if ( isset( $assignment['course_id'] ) && $course_id !== (int) $assignment['course_id'] ) {
				unset( $assignments[ $assignment_id ] );
			} else {
				if ( isset( $assignment['approval_status'] ) && 1 === (int) $assignment['approval_status'] ) {
					$assignment['approval_status'] = __( 'Approved', 'uncanny-learndash-reporting' );
				} else {
					$assignment['approval_status'] = __( 'Not Approved', 'uncanny-learndash-reporting' );
				}
			}
		}

		// Quizzes Scores Avg
		global $wpdb;

		$user_activities = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.activity_id, a.course_id, a.post_id, a.activity_status, a.activity_completed, m.activity_meta_value as activity_percentage
				FROM {$wpdb->prefix}learndash_user_activity a
				LEFT JOIN {$wpdb->prefix}learndash_user_activity_meta m ON a.activity_id = m.activity_id
				WHERE a.user_id = %d
				AND a.course_id = %d
				AND a.activity_type = 'quiz'
				AND m.activity_meta_key = 'percentage'",
				$user_id,
				$course_id
			)
		);

		// Quizzes
		$quizzes = array();

		foreach ( $course_quiz_list as $module_quiz_list ) {
			if ( empty( $module_quiz_list ) ) {
				continue;
			}

			foreach ( $module_quiz_list as $quiz ) {

				if ( isset( $quiz['post'] ) ) {

					$quiz_names[ $quiz['post']->ID ] = $quiz['post']->post_title;
					$certificate_link                = '';
					$certificate                     = learndash_certificate_details( $quiz['post']->ID, $user_id );
					if ( ! empty( $certificate ) && isset( $certificate['certificateLink'] ) ) {
						$certificate_link = $certificate['certificateLink'];
					}

					foreach ( $user_activities as $activity ) {

						if ( (int) $activity->post_id === (int) $quiz['post']->ID ) {

							$pro_quiz_id = learndash_get_user_activity_meta( $activity->activity_id, 'pro_quizid', true );
							if ( empty( $pro_quiz_id ) ) {
								// LD is starting to deprecated pro quiz IDs from LD activity Tables. This is a back up if its not there
								$pro_quiz_id = absint( get_post_meta( $quiz['post']->ID, 'quiz_pro_id', true ) );
							}

							$statistic_ref_id = learndash_get_user_activity_meta( $activity->activity_id, 'statistic_ref_id', true );
							if ( empty( $statistic_ref_id ) ) {

								if ( class_exists( '\LDLMS_DB' ) ) {
									$pro_quiz_master_table   = LDLMS_DB::get_table_name( 'quiz_master' );
									$pro_quiz_stat_ref_table = LDLMS_DB::get_table_name( 'quiz_statistic_ref' );
								} else {
									$pro_quiz_master_table   = $wpdb->prefix . 'wp_pro_quiz_master';
									$pro_quiz_stat_ref_table = $wpdb->prefix . 'wp_pro_quiz_statistic_ref';
								}

								// LD is starting to deprecated pro quiz IDs from LD activity Tables. This is a back up if its not there
								// phpcs:disable WordPress.DB.PreparedSQL
								$statistic_ref_id = $wpdb->get_var(
									$wpdb->prepare(
										"SELECT statistic_ref_id FROM {$pro_quiz_stat_ref_table} as stat
										INNER JOIN {$pro_quiz_master_table} as master ON stat.quiz_id=master.id
										WHERE  user_id = %d AND quiz_id = %d AND create_time = %d AND master.statistics_on=1
										LIMIT 1",
										$user_id,
										$pro_quiz_id,
										$activity->activity_completed
									)
								);
								// phpcs:enable WordPress.DB.PreparedSQL
							}

							$modal_link = '';

							if ( empty( $statistic_ref_id ) || empty( $pro_quiz_id ) ) {
								if ( ! empty( $statistic_ref_id ) ) {
									$modal_link = '<a class="user_statistic"
									     data-statistic_nonce="' . wp_create_nonce( 'statistic_nonce_' . $statistic_ref_id . '_' . get_current_user_id() . '_' . $user_id ) . '"
									     data-user_id="' . esc_attr( $user_id ) . '"
									     data-quiz_id="' . esc_attr( $pro_quiz_id ) . '"
									     data-ref_id="' . esc_attr( intval( $statistic_ref_id ) ) . '"
									     data-uo-pro-quiz-id="' . esc_attr( intval( $pro_quiz_id ) ) . '"
									     data-uo-quiz-id="' . esc_attr( intval( $activity->post_id ) ) . '"
									     data-nonce="' . esc_attr( wp_create_nonce( 'wpProQuiz_nonce' ) ) . '"
									     href="#"> </a>';
								}
							} else {
								if ( ! empty( $statistic_ref_id ) ) {
									$modal_link = '<a class="user_statistic"
									     data-statistic_nonce="' . esc_attr( wp_create_nonce( 'statistic_nonce_' . $statistic_ref_id . '_' . get_current_user_id() . '_' . $user_id ) ) . '"
									     data-user_id="' . esc_attr( $user_id ) . '"
									     data-quiz_id="' . esc_attr( $pro_quiz_id ) . '"
									     data-ref_id="' . esc_attr( intval( $statistic_ref_id ) ) . '"
									     data-uo-pro-quiz-id="' . esc_attr( intval( $pro_quiz_id ) ) . '"
									     data-uo-quiz-id="' . esc_attr( intval( $activity->post_id ) ) . '"
									     data-nonce="' . esc_attr( wp_create_nonce( 'wpProQuiz_nonce' ) ) . '"
									     href="#">';
									$modal_link .= '<div class="statistic_icon"></div>';
									$modal_link .= '</a>';
								}
							}

							$quizzes[] = array(
								'name'             => $quiz['post']->post_title,
								'score'            => $activity->activity_percentage,
								'detailed_report'  => $modal_link,
								'completed_date'   => array(
									'display'   => learndash_adjust_date_time_display( $activity->activity_completed ),
									'timestamp' => $activity->activity_completed,
								),
								'certificate_link' => $certificate_link,
							);
						}
					}
				}
			}
		}

		$progress = learndash_course_progress(
			array(
				'course_id' => $course_id,
				'user_id'   => $user_id,
				'array'     => true,
			)
		);

		$started_date = ld_course_access_from( $course_id, $user_id );
		$started_date = ! empty( $started_date ) ? learndash_adjust_date_time_display( $started_date ) : '-';
		$completed_date = '';
		$status = '';

		if ( 100 <= $progress['percentage'] ) {
			$progress_percentage = $progress['percentage'];
			$completed_timestamp = learndash_user_get_course_completed_date( $user_id, $course_id );
			if ( absint( $completed_timestamp ) ) {
				$completed_date = learndash_adjust_date_time_display( learndash_user_get_course_completed_date( $user_id, $course_id ) );
				$status         = __( 'Completed', 'uncanny-learndash-reporting' );
			} else {
				$status = __( 'In Progress', 'uncanny-learndash-reporting' );
			}
		} else {
			// Division by zero causing fatals.
			$completed = isset( $progress['completed'] ) ? absint( $progress['completed'] ) : 0;
			$total     = isset( $progress['total'] ) ? absint( $progress['total'] ) : 0;
			if ( $total > 0 ) {
				$progress_percentage = absint( $completed / $total * 100 );
				$status              = __( 'In Progress', 'uncanny-learndash-reporting' );
			} else {
				$progress_percentage = 0;
			}
		}

		if ( 0 === $progress_percentage ) {
			$progress_percentage = '';
		} else {
			$progress_percentage = $progress_percentage . __( '%', 'uncanny-learndash-reporting' );
		}

		// Column Quiz Average
		$course_quiz_average = self::get_avergae_quiz_result( $course_id, $user_activities );
		$avg_score           = '';
		if ( $course_quiz_average ) {
			/* Translators: 1. number percentage */
			$avg_score = sprintf( __( '%1$s%%', 'uncanny-learndash-reporting' ), $course_quiz_average );
		}

		// TinCanny
		global $wpdb;
		$statements_list = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT lesson_id as post_id, module_name, target_name, verb as action, result, xstored
				FROM {$wpdb->prefix}uotincan_reporting
				WHERE user_id = %d AND course_id = %d",
				$user_id,
				$course_id
			)
		);
		$statements      = array();
		foreach ( $statements_list as $statement ) {

			if ( isset( $quiz_names[ (int) $statement->post_id ] ) ) {
				$related_post_name = $quiz_names[ (int) $statement->post_id ];
			} elseif ( isset( $topic_names[ (int) $statement->post_id ] ) ) {
				$related_post_name = $topic_names[ (int) $statement->post_id ];
			} elseif ( isset( $lesson_names[ (int) $statement->post_id ] ) ) {
				$related_post_name = $lesson_names[ (int) $statement->post_id ];
			} elseif ( (int) $statement->post_id === $course_id ) {
				$related_post_name = get_the_title( $course_id );
			} else {
				$tmp_post = get_post( $statement->post_id );

				if ( $tmp_post ) {
					$related_post_name = $tmp_post->post_title;
					$tmp_post          = null;
				} else {
					$related_post_name = __( 'Not Found: ', 'uncanny-learndash-reporting' ) . $statement->post_id;
				}
			}

			$date = $statement->xstored;

			$statements[] = array(
				'related_post' => $related_post_name,
				'module'       => $statement->module_name,
				'target'       => $statement->target_name,
				'action'       => $statement->action,
				'result'       => $statement->result,
				'date'         => $date,
			);

		}

		return array(
			'started_date'        => $started_date,
			'completed_date'      => $completed_date,
			'progress_percentage' => $progress_percentage,
			'avg_score'           => $avg_score,
			'status'              => $status,
			'lessons'             => $lessons,
			'topics'              => $topics,
			'quizzes'             => $quizzes,
			'assigments'          => $assignments,
			'statements'          => $statements,
			'course_certificate'  => learndash_get_course_certificate_link( $course_id, $user_id ),
		);
	}
}
