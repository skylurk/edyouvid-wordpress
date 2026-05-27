<?php

namespace uncanny_ceu;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class CourseReport
 *
 * @package uncanny_ceu
 */
class CourseReportAPI {

	/**
	 * Root path for the rest routes.
	 *
	 * @var string
	 */
	private $root_path = 'ceu/v2';

	/**
	 * Quiz list IDs.
	 *
	 * @var array
	 */
	private $quiz_list = array();

	/**
	 * Requires JSON data refresh.
	 *
	 * @var bool
	 */
	private $requires_json_refresh = false;

	/**
	 * CourseReportRest constructor.
	 */
	public function __construct() {
		// Register the REST routes.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Listen for credit form 'refresh' submissions.
		add_action( 'init', array( $this, 'maybe_handle_report_submissions' ) );
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			$this->root_path,
			'/modify_table',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'modify_table' ),
				'permission_callback' => array( $this, 'rest_permissions' ),
			)
		);

		register_rest_route(
			$this->root_path,
			'/get_select2_users',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_select2_users' ),
				'permission_callback' => array( $this, 'rest_permissions' ),
			)
		);
	}

	/**
	 * REST permissions.
	 *
	 * @return bool
	 */
	public function rest_permissions() {

		if ( is_user_logged_in() ) {

			// Can user manage options? - Admins.
			if ( current_user_can( 'manage_options' ) ) {
				return true;
			}

			// Is the user a LearnDash group leader?
			if ( learndash_is_group_leader_user( get_current_user_id() ) ) {
				return true;
			}

			// @CURT - TODO REVIEW - Do we want a filter here?
		}

		return new \WP_Error(
			'ucec_rest_cannot_view',
			__( 'Sorry, you cannot view this resource.', 'uncanny-ceu' ),
			array(
				'status' =>
				rest_authorization_required_code(),
			)
		);
	}

	/**
	 * Handle report submissions.
	 *
	 * @return void
	 */
	public function maybe_handle_report_submissions() {

		// Check if the request is for the credit report.
		if ( ! Utilities::ceu_filter_has_var( 'ceu_credit_report_action' ) ) {
			return;
		}

		// Validate action.
		$action    = Utilities::ceu_filter_input( 'ceu_credit_report_action' );
		$whitelist = array( 'refresh' );
		if ( ! in_array( $action, $whitelist, true ) ) {
			return;
		}

		// Validate nonce.
		$nonce = Utilities::ceu_filter_has_var( '_wpnonce' ) ? Utilities::ceu_filter_input( '_wpnonce' ) : false;
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'ceu_credit_report_submission' ) ) {
			return;
		}

		// Handle the refresh action.
		if ( 'refresh' === $action ) {
			$this->requires_json_refresh = true;
			$this->refresh_json_data();

			// Get redirect URL
			$redirect_url = Utilities::ceu_filter_has_var( 'redirect' ) ? Utilities::ceu_filter_input( 'redirect' ) : '';
			if ( empty( $redirect_url ) ) {
				$redirect_url = admin_url( 'admin.php?page=uncanny-ceu-report' );
			}

			wp_safe_redirect( $redirect_url );
			exit;
		}

	}

	/**
	 * Modyfiy the table (create, remove)
	 *
	 * @param $request - The request object.
	 *
	 * @return void
	 */
	public function modify_table( $request ) {
		// TODO: Add a common check with filter.
		if ( ! current_user_can( 'manage_options' ) && ! learndash_is_group_leader_user( get_current_user_id() ) ) {
			$this->send_field_editor( 'caps', __( "You do not have permission to modify this user's data", 'uncanny-ceu' ) );
		}

		$action = Utilities::ceu_filter_has_var( 'action', INPUT_POST ) ? Utilities::ceu_filter_input( 'action', INPUT_POST ) : '';

		$whitelist = array( 'create', 'edit', 'remove' );
		if ( ! in_array( $action, $whitelist, true ) ) {
			$this->send_field_editor( 'action', __( 'Invalid action', 'uncanny-ceu' ) );
		}

		$post_data = Utilities::ceu_filter_has_var( 'data', INPUT_POST ) ? Utilities::ceu_filter_input_array( 'data', INPUT_POST ) : '';
		if ( empty( $post_data ) || ! is_array( $post_data ) ) {
			$this->send_field_editor( 'unkown_error', 'The rest data provided does not have the correct structure.' );
		}

		// Check if report mode is JSON
		$first_key   = array_key_first( $post_data );
		$report_mode = $post_data[ $first_key ]['report_mode'] ?? '';
		// Set refresh property.
		$this->requires_json_refresh = 'json' === $report_mode;

		// Remove the ceus
		if ( 'remove' === $action ) {

			$total_entries   = 0;
			$deleted_entries = 0;
			$skipped_entries = 0;
			$field_errors    = array();
			$testing         = array();

			foreach ( $post_data as $row ) {

				$total_entries ++;

				$error_create_on_row = false;
				$user_id             = absint( $row['user'] );
				if ( 0 === $user_id ) {
					$field_errors[]      = (object) array(
						'name'   => 'user',
						'status' => __( 'A user is required', 'uncanny-ceu' ),
					);
					$error_create_on_row = true;
				}

				$course = esc_sql( $row['course'] );
				if ( empty( $course ) ) {
					$field_errors[]      = (object) array(
						'name'   => 'course',
						'status' => sprintf(
							// translators: %s: course label
							__( 'A %s is required', 'uncanny-ceu' ),
							\LearnDash_Custom_Label::get_label( 'course' )
						),
					);
					$error_create_on_row = true;
				}

				$date = absint( $row['date'] );
				if ( 0 === $date ) {
					$field_errors[]      = (object) array(
						'name'   => 'date',
						'status' => __( 'A date is required', 'uncanny-ceu' ),
					);
					$error_create_on_row = true;
				}

				if ( $error_create_on_row ) {
					$skipped_entries ++;
					continue;
				}

				global $wpdb;

				// Find all user meta rows with the key.
				$query        = $wpdb->prepare(
					"DELETE FROM $wpdb->usermeta 
					WHERE user_id = %d 
					AND meta_key LIKE %s",
					$user_id,
					'%' . $date . '_' . $course
				);
				$deleted_rows = $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				if ( $deleted_rows ) {
					$testing[] = array( $query, $deleted_rows, $deleted_entries );
					//Success
					$deleted_entries ++;
				} else {
					$skipped_entries ++;
					$field_errors[] = (object) array(
						'name'   => 'ceus',
						'status' => sprintf(
							// translators: %s: plural credit designation label
							__( '%s already removed. Please refresh list.', 'uncanny-ceu' ),
							Utilities::credit_designation_label( 'plural', __( 'CEUs', 'uncanny-ceu' ) )
						),
					);
				}
			}

			if ( 0 === $skipped_entries ) {

				$this->send_row_action_response( array( $testing, $post_data, $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

			} else {

				array_unshift(
					$field_errors,
					(object) array(
						'name'   => 'ceus',
						'status' => sprintf(
							// translators: %1$s: deleted entries, %2$s: total entries
							__( '%1$s of %2$s rows could not be removed, possibly because they were previously deleted. Please refresh the page or check the Javascript Console in your browser for troubleshooting information.', 'uncanny-ceu' ),
							$deleted_entries,
							$total_entries
						),
					)
				);

				$this->send_field_editor_multiple( $field_errors );
			}
		}

		// Create the ceus
		if ( 'create' === $action ) {

			$create_data = isset( $post_data[0] ) ? $post_data[0] : '';
			if ( empty( $create_data ) || ! is_array( $create_data ) ) {
				$this->send_field_editor( 'unkown_error', 'The rest data provided does not have the correct structure.' );
			}

			$user_id = absint( $create_data['user'] );
			if ( 0 === $user_id ) {
				$this->send_field_editor( 'user', __( 'A user is required', 'uncanny-ceu' ) );
			}

			$user = new \WP_User( $user_id );
			if ( ! $user->exists() ) {
				$this->send_field_editor( 'user', __( 'This user no longer exists', 'uncanny-ceu' ) );
			}

			$course_id       = absint( $create_data['course'] );
			$custom_course   = esc_html( $create_data['customCourse'] );
			$complete_course = absint( $create_data['completeCourse'] );
			if ( 0 === $course_id && empty( $custom_course ) ) {
				$this->send_field_editor( 'customCourse', __( 'A title is required', 'uncanny-ceu' ) );
			}

			if ( 0 === $course_id && ! empty( $custom_course ) ) {
				$course = null;
			} else {
				$course = get_post( $course_id );

				if ( empty( $course ) ) {
					$this->send_field_editor( 'course', sprintf( __( 'This % no longer exists', 'uncanny-ceu' ), \LearnDash_Custom_Label::get_label( 'course' ) ) );
				}

				if ( 1 === $complete_course ) {
					$is_completed = learndash_course_completed( $user->ID, $course_id );
					if ( true === $is_completed ) {
						$this->send_field_editor(
							'course',
							sprintf(
								// translators: %s: course label
								__( 'This user already completed this %s', 'uncanny-ceu' ),
								\LearnDash_Custom_Label::get_label( 'course' )
							)
						);
					} else {
						$return = $this->mark_completes_a_course( $user->ID, $course_id );
						$this->send_row_action_response(
							array(
								$user->ID,
								$course_id,
								$return,
								'completed_course',
								$is_completed,
							)
						);
					}
				}
			}

			$date = esc_html( $create_data['date'] );
			if ( empty( $date ) ) {
				$this->send_field_editor( 'date', __( 'A date is required', 'uncanny-ceu' ) );
			}

			$ceus = filter_var( $create_data['ceus'], FILTER_VALIDATE_FLOAT );

			if ( null === $course && ( false === $ceus || 0 > $ceus ) ) {
				$this->send_field_editor(
					'ceus',
					sprintf(
						// translators: %s: plural credit designation label
						__( 'A valid %s value is required', 'uncanny-ceu' ),
						get_option( 'credit_designation_label_plural' )
					)
				);
			}

			$data = array(
				'user'             => $user,
				'course'           => $course,
				'course_completed' => 0,
				'custom_course'    => $custom_course,
				'custom_date'      => $date,
				'custom_ceus'      => $ceus,
				'custom_creation'  => true,
			);

			// The class contains all ceu creation code
			$award_cert_class = Utilities::get_class_instance( 'AwardCertificate' );
			$returned_data    = $award_cert_class->learndash_course_completed( $data );

			if ( isset( $returned_data->success ) && false === $returned_data->success ) {
				$this->send_field_editor( 'course', $returned_data->error );
			}

			$this->send_row_action_response( $returned_data );
		}

		// Edit the ceus
		if ( 'edit' === $action ) {

			// Get row ID.
			$ceu_row_id = Utilities::ceu_filter_has_var( 'ceu_row_id', INPUT_POST ) ? Utilities::ceu_filter_input( 'ceu_row_id', INPUT_POST ) : '';
			if ( empty( $ceu_row_id ) ) {
				$this->send_field_editor( 'unkown_error', __( 'Invalid request.', 'uncanny-ceu' ) );
			}

			// Get row data.
			$ceu_row_data = isset( $post_data[ $ceu_row_id ] ) ? $post_data[ $ceu_row_id ] : '';
			if ( empty( $ceu_row_data ) || ! is_array( $ceu_row_data ) ) {
				$this->send_field_editor( 'unkown_error', __( 'Invalid request.', 'uncanny-ceu' ) );
			}

			// Get user ID.
			$user_id = isset( $ceu_row_data['user'] ) ? absint( $ceu_row_data['user'] ) : 0;
			if ( 0 === $user_id ) {
				$user_id = Utilities::ceu_filter_has_var( 'row_user', INPUT_POST ) ? absint( Utilities::ceu_filter_input( 'row_user', INPUT_POST ) ) : 0;
			}
			if ( 0 === $user_id ) {
				$this->send_field_editor( 'user', __( 'A user is required', 'uncanny-ceu' ) );
			}

			$user = new \WP_User( $user_id );
			if ( ! $user->exists() ) {
				$this->send_field_editor( 'user', __( 'This user no longer exists', 'uncanny-ceu' ) );
			}

			// POST Data.
			$course_id = isset( $ceu_row_data['course'] ) ? absint( $ceu_row_data['course'] ) : 0;
			if ( 0 === $course_id ) {
				$course_id = Utilities::ceu_filter_has_var( 'row_course_id', INPUT_POST ) ? absint( Utilities::ceu_filter_input( 'row_course_id', INPUT_POST ) ) : 0;
			}
			$custom_course = isset( $ceu_row_data['customCourse'] ) ? esc_html( $ceu_row_data['customCourse'] ) : '';
			if ( empty( $custom_course ) ) {
				$custom_course = Utilities::ceu_filter_has_var( 'row_course', INPUT_POST ) ? esc_html( Utilities::ceu_filter_input( 'row_course', INPUT_POST ) ) : '';
			}

			$complete_course = isset( $ceu_row_data['completeCourse'] ) ? absint( $ceu_row_data['completeCourse'] ) : '';
			$date            = isset( $ceu_row_data['date'] ) ? esc_html( $ceu_row_data['date'] ) : '';
			$ceu_value       = isset( $ceu_row_data['ceus'] ) ? filter_var( $ceu_row_data['ceus'], FILTER_VALIDATE_FLOAT ) : '';

			// Prepare CEU meta key.
			$unique_key = explode( '_', $ceu_row_id );
			array_pop( $unique_key ); // Remove the last element i.e. user id.
			$ceu_meta_key = implode( '_', $unique_key ); // Implode it back with '_'.

			// CEU meta keys.
			$ceu_date_key   = 'ceu_date_' . $ceu_meta_key;
			$ceu_title_key  = 'ceu_title_' . $ceu_meta_key;
			$ceu_earned_key = 'ceu_earned_' . $ceu_meta_key;

			// CEU_CRUD object.
			$ceu_crud     = CEU_CRUD::get_instance();
			$data_updated = array();
			if ( 0 < $course_id && empty( $custom_course ) ) {
				$course = get_post( $course_id );
				if ( empty( $course ) ) {
					$this->send_field_editor( 'course', sprintf( __( 'This % no longer exists', 'uncanny-ceu' ), \LearnDash_Custom_Label::get_label( 'course' ) ) );
				}

				if ( 1 === $completeCourse ) {
					$is_completed = learndash_course_completed( $user->ID, $course_id );
					if ( true === $is_completed ) {
						$this->send_field_editor( 'course', sprintf( __( 'This user already completed this %s', 'uncanny-ceu' ), \LearnDash_Custom_Label::get_label( 'course' ) ) );
					} else {
						$return = $this->mark_completes_a_course( $user->ID, $course_id );
						$this->row_updated(
							array(
								$user->ID,
								$course_id,
								$return,
								'completed_course',
								$is_completed,
							)
						);
					}
				}
			}

			if ( ! empty( $date ) ) {
				$check_date = \DateTime::createFromFormat( 'F d Y, g:i:s a', $date );
				if ( $check_date ) {
					$completion_date = $check_date->format( 'U' );
					$ceu_crud->update_usermeta( $user_id, $ceu_date_key, $completion_date );
					$data_updated['custom_date'] = $date;
				}
			}

			if ( 0 === $course_id && ! empty( $custom_course ) && strstr( $ceu_title_key, '_manual-ceu-' ) ) {
				$ceu_crud->update_usermeta( $user_id, $ceu_title_key, $custom_course );
				$data_updated['custom_course'] = $custom_course;
			}

			if ( ! empty( $ceu_value ) && false !== $ceu_value && $ceu_value >= 0 ) {
				$ceu_crud->update_usermeta( $user_id, $ceu_earned_key, $ceu_value );
				$data_updated['custom_ceus'] = $ceu_value;
			}

			if ( ! empty( $data_updated ) ) {
				$data_updated['user'] = $user;
				$this->row_updated( $data_updated );
			} else {
				$this->send_field_editor( 'unkown_error', __( 'Nothing to update.', 'uncanny-ceu' ) );
			}
		}

	}

	/**
	 * Get array with the list of all users from the site
	 * Each array element will have a "value" element, which will contain the user id,
	 * and also will have a "text" element, which will contain the text to be shown in the option
	 *
	 * @return Array
	 * @since  2.5
	 */
	public function get_select2_users( $request ) {

		// Create array that will contain the option data.
		$options = array();

		// Sanitize user search
		$user_search = $request->get_param( 'search' );
		$user_search = ! empty( $user_search ) ? sanitize_text_field( $user_search ) : '';

		if ( empty( $user_search ) ) {
			$options = apply_filters( 'ucec_course_report_select2_users', $options );
			return $options;
		}

		// Group leader users.
		$filtered_ids = null;
		if ( $request->has_param( 'group_leader_users' ) ) {
			$filtered_ids = $request->get_param( 'group_leader_users' );
			$filtered_ids = ! empty( $filtered_ids ) ? array_map( 'absint', $filtered_ids ) : array();
		}

		// Bail if group leader has no filtered user IDs.
		if ( is_array( $filtered_ids ) && empty( $filtered_ids ) ) {
			return $options;
		}

		// Get users
		global $wpdb;
		$user_search = '%' . $wpdb->esc_like( $user_search ) . '%';
		$query       = $wpdb->prepare(
			"SELECT
				u.ID,
				u.user_login,
				u.user_email,
				u.display_name,
				MAX(CASE WHEN um.meta_key = 'first_name' THEN um.meta_value ELSE NULL END) AS 'first_name',
				MAX(CASE WHEN um.meta_key = 'last_name' THEN um.meta_value ELSE NULL END) AS 'last_name'
			FROM $wpdb->users u
			LEFT JOIN $wpdb->usermeta um ON (um.user_id = u.ID)
			WHERE u.user_login LIKE %s
			OR u.user_email LIKE %s
			OR (um.meta_key = 'first_name' AND um.meta_value LIKE %s)
			OR (um.meta_key = 'last_name' AND um.meta_value LIKE %s)
			GROUP BY u.ID",
			$user_search,
			$user_search,
			$user_search,
			$user_search
		);

		$users = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( empty( $users ) ) {
			return $options;
		}

		// Filter users by group leader users.
		if ( ! is_null( $filtered_ids ) ) {
			$users = array_filter(
				$users,
				function ( $user ) use ( $filtered_ids ) {
					return in_array( absint( $user['ID'] ), $filtered_ids, true );
				}
			);
		}

		// Iterate users and create main array
		foreach ( $users as $user ) {
			// Add option
			$options[] = array(
				'id'   => $user['ID'],
				'text' => sprintf(
					'%s::%s',
					wp_strip_all_tags( $user['display_name'] ),
					wp_strip_all_tags( $user['user_email'] )
				),
			);
		}

		// Apply filters and return
		return apply_filters( 'ucec_course_report_select2_users', $options ); // TODO : proper rest response.
	}

	/**
	 * Return a json response for the field editor.
	 *
	 * @param $name
	 * @param $status
	 *
	 * @return void
	 */
	private function send_field_editor( $name, $status ) {
		$response = (object) array(
			'fieldErrors' => array(
				(object) array(
					'name'   => $name,
					'status' => $status,
				),
			),
			'success'     => false,
			'reload'      => false,
		);

		wp_send_json( $response );
	}

	/**
	 * Return a json response for multiple fields editor.
	 *
	 * @param $field_errors
	 *
	 * @return void
	 */
	private function send_field_editor_multiple( $field_errors ) {

		// Maybe refresh the JSON data.
		$this->refresh_json_data();

		// Send the response.
		$response = (object) array(
			'fieldErrors' => $field_errors,
			'success'     => false,
			'reload'      => true,
		);

		wp_send_json( $response );
	}

	/**
	 * Send json response for row action ( create, remove )
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	private function send_row_action_response( $data = array() ) {

		// Maybe refresh the JSON data.
		$this->refresh_json_data();

		// Send the response.
		$response = (object) array(
			'success' => true,
			'reload'  => true,
			'testing' => $data,
		);

		wp_send_json( $response );
	}

	/**
	 * Returns a json response for row updated action.
	 *
	 * @param $data
	 *
	 * @return void
	 */
	private function row_updated( $data = array() ) {

		// Maybe refresh the JSON data.
		$this->refresh_json_data();

		// Send the response.
		$response = (object) array(
			'success' => true,
			'reload'  => true,
			'updated' => true,
			'testing' => $data,
		);

		wp_send_json( $response );
	}

	/**
	 * Refresh the JSON data.
	 *
	 * @return void
	 */
	private function refresh_json_data() {
		if ( $this->requires_json_refresh ) {
			$course_report = Utilities::get_class_instance( 'CourseReport' );
			$course_report->generate_ceu_records_json();
		}
	}

	/**
	 * Mark a course complete.
	 *
	 * @param $user_id
	 * @param $course_id
	 *
	 * @return bool
	 */
	private function mark_completes_a_course( $user_id, $course_id ) {
		$this->mark_steps_done( $user_id, $course_id );

		//all steps done.. mark course complete
		return learndash_process_mark_complete( $user_id, $course_id );
	}

	/**
	 * Mark a course steps done.
	 *
	 * @param int $user_id
	 * @param int $course_id
	 */
	private function mark_steps_done( $user_id, $course_id ) {
		$lessons = learndash_get_lesson_list( $course_id, array( 'num' => 0 ) );
		foreach ( $lessons as $lesson ) {
			$this->mark_topics_done( $user_id, $lesson->ID, $course_id );
			$lesson_quiz_list = learndash_get_lesson_quiz_list( $lesson->ID, $user_id, $course_id );

			if ( $lesson_quiz_list ) {
				foreach ( $lesson_quiz_list as $ql ) {
					$this->quiz_list[ $ql['post']->ID ] = 0;
				}
			}

			learndash_process_mark_complete( $user_id, $lesson->ID, false, $course_id );
		}

		$this->mark_quiz_complete( $user_id, $course_id );
	}

	/**
	 * Mark topics done.
	 *
	 * @param $user_id
	 * @param $lesson_id
	 * @param $course_id
	 */
	private function mark_topics_done( $user_id, $lesson_id, $course_id ) {
		$topic_list = learndash_get_topic_list( $lesson_id, $course_id );
		if ( $topic_list ) {
			foreach ( $topic_list as $topic ) {
				learndash_process_mark_complete( $user_id, $topic->ID, false, $course_id );
				$topic_quiz_list = learndash_get_lesson_quiz_list( $topic->ID, $user_id, $course_id );
				if ( $topic_quiz_list ) {
					foreach ( $topic_quiz_list as $ql ) {
						$this->quiz_list[ $ql['post']->ID ] = 0;
					}
				}
			}
		}
	}

	/**
	 * Mark Quizzes complete.
	 *
	 * @param      $user_id
	 * @param null $course_id
	 */
	private function mark_quiz_complete( $user_id, $course_id = null ) {
		$quizzes = learndash_get_course_quiz_list( $course_id, $user_id );
		if ( $quizzes ) {
			foreach ( $quizzes as $quiz ) {
				$this->quiz_list[ $quiz['post']->ID ] = 0;
			}
		}
		$quizz_progress = array();
		if ( ! empty( $this->quiz_list ) ) {
			$usermeta       = get_user_meta( $user_id, '_sfwd-quizzes', true );
			$quizz_progress = empty( $usermeta ) ? array() : $usermeta;

			foreach ( $this->quiz_list as $quiz_id => $quiz ) {
				$quiz_meta = get_post_meta( $quiz_id, '_sfwd-quiz', true );

				if ( learndash_is_quiz_complete( $user_id, $quiz_id, $course_id ) ) {
					continue;
				}

				$quizdata = array(
					'quiz'             => $quiz_id,
					'score'            => 0,
					'count'            => 0,
					'pass'             => true,
					'rank'             => '-',
					'time'             => time(),
					'pro_quizid'       => $quiz_meta['sfwd-quiz_quiz_pro'],
					'course'           => $course_id,
					'points'           => 0,
					'total_points'     => 0,
					'percentage'       => 100,
					'timespent'        => 0,
					'has_graded'       => false,
					'statistic_ref_id' => 0,
					'm_edit_by'        => 9999999, // Manual Edit By ID.
					'm_edit_time'      => time(),  // Manual Edit timestamp.
				);

				$quizz_progress[] = $quizdata;
				$quizdata_pass    = filter_var( $quizdata['pass'], FILTER_VALIDATE_BOOLEAN );

				// Then we add the quiz entry to the activity database.
				learndash_update_user_activity(
					array(
						'course_id'          => $course_id,
						'user_id'            => $user_id,
						'post_id'            => $quiz_id,
						'activity_type'      => 'quiz',
						'activity_action'    => 'insert',
						'activity_status'    => true,
						'activity_started'   => $quizdata['time'],
						'activity_completed' => $quizdata['time'],
						'activity_meta'      => $quizdata,
					)
				);

			}
		}

		if ( ! empty( $quizz_progress ) ) {
			update_user_meta( $user_id, '_sfwd-quizzes', $quizz_progress );
		}
	}

	/**
	 * Check if learndash hierarchical groups is enabled.
	 *
	 * @return bool
	 */
	public static function is_groups_hierarchical_enabled() {
		return function_exists( 'learndash_is_groups_hierarchical_enabled' ) && learndash_is_groups_hierarchical_enabled();
	}

	/**
	 * Get hierarchical group data.
	 *
	 * @param int $group_id - The group ID.
	 *
	 * @return array
	 */
	public static function get_learndash_hierarchical_group_data( $group_id ) {
		static $group_data = array();
		$group_id          = absint( $group_id );
		if ( empty( $group_id ) ) {
			return array();
		}

		if ( isset( $group_data[ $group_id ] ) ) {
			return $group_data[ $group_id ];
		}

		$group_ids               = self::maybe_collect_group_children_ids( array( $group_id ) );
		$group_data[ $group_id ] = self::generate_group_data( $group_ids );

		return $group_data[ $group_id ];
	}

	/**
	 * Generate data for a group. ( children, courses, and users )
	 *
	 * @param int $group_id
	 *
	 * @return array
	 */
	public static function generate_group_data( $group_ids ) {
		$data = array(
			'groups'         => array(),
			'all_group_ids'  => array(),
			'all_course_ids' => array(),
			'all_user_ids'   => array(),
		);

		if ( empty( $group_ids ) ) {
			return $data;
		}

		// Generate data for each group.
		foreach ( $group_ids as $group_id ) {
			$group_id = absint( $group_id );
			$courses  = learndash_group_enrolled_courses( $group_id );
			$courses  = ! empty( $courses ) ? array_map( 'intval', $courses ) : array();
			$users    = self::get_learndash_group_user_ids( $group_id );

			$data['groups'][ $group_id ] = array(
				'group_id' => $group_id,
				'children' => self::get_learndash_group_children_ids_by_parent_id( $group_id ),
				'courses'  => $courses,
				'users'    => $users,
			);
			// Keep a tally for easy access to all group, course, and user IDs.
			$data['all_group_ids'][] = $group_id;
			$data['all_course_ids']  = array_merge( $data['all_course_ids'], $courses );
			$data['all_user_ids']    = array_merge( $data['all_user_ids'], $users );
		}

		// Ensure all_course_ids and all_user_ids are unique and sorted.
		$data['all_course_ids'] = array_values( array_unique( $data['all_course_ids'], SORT_NUMERIC ) );
		$data['all_user_ids']   = array_values( array_unique( $data['all_user_ids'], SORT_NUMERIC ) );

		return $data;
	}

	/**
	 * Get data for the group leader.
	 *
	 * @param int $leader_id - The leader's User ID.
	 *
	 * @return array - The group leader groups, associated courses, and users.
	 */
	public static function get_group_leader_data( $leader_id ) {
		static $leader_data = array();
		if ( isset( $leader_data[ $leader_id ] ) ) {
			return $leader_data[ $leader_id ];
		}

		$group_ids                 = self::get_group_leader_group_ids( $leader_id );
		$leader_data[ $leader_id ] = self::generate_group_data( $group_ids );

		return $leader_data[ $leader_id ];
	}

	/**
	 * Get all group IDs for a group leader.
	 *
	 * @param int $leader_id - The leader's User ID.
	 *
	 * @return int
	 */
	public static function get_group_leader_group_ids( $leader_id ) {
		static $leader_group_ids = array();
		if ( isset( $leader_group_ids[ $leader_id ] ) ) {
			return $leader_group_ids[ $leader_id ];
		}

		// Get leaders group IDs from usermeta.
		global $wpdb;
		$group_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT meta_value 
				FROM $wpdb->usermeta 
				WHERE meta_key LIKE %s AND user_id = %d",
				'learndash_group_leaders_%%',
				$leader_id
			)
		);
		$group_ids = ! empty( $group_ids ) ? array_unique( $group_ids, SORT_NUMERIC ) : array();
		$group_ids = self::maybe_collect_group_children_ids( $group_ids );

		// Cache the result.
		$leader_group_ids[ $leader_id ] = $group_ids;

		return $leader_group_ids[ $leader_id ];
	}

	/**
	 * Maybe collect group children IDs.
	 *
	 * @param array $group_ids
	 *
	 * @return array
	 */
	public static function maybe_collect_group_children_ids( $group_ids ) {
		if ( ! is_array( $group_ids ) || empty( $group_ids ) || ! self::is_groups_hierarchical_enabled() ) {
			return array();
		}

		$all_children = self::get_all_learndash_group_child_posts_ids();
		if ( ! empty( $all_children ) ) {
			foreach ( $group_ids as $group_id ) {
				$children_ids = self::get_learndash_group_children_ids_by_parent_id( $group_id, $all_children );
				if ( ! empty( $children_ids ) ) {
					$group_ids = array_merge( $group_ids, $children_ids );
				}
			}
			$group_ids = array_unique( $group_ids, SORT_NUMERIC );
		}

		return $group_ids;
	}

	/**
	 * Get all child group IDs for a parent group.
	 *
	 * @param int $group_id
	 * @param array|null $all_children
	 *
	 * @return array
	 */
	private static function get_learndash_group_children_ids_by_parent_id( $group_id, $all_children = null ) {

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
		foreach ( $all_children as $child_id => $parent_id ) {
			if ( $parent_id === $group_id ) {
				$children[] = $child_id;
				$children2  = self::get_learndash_group_children_ids_by_parent_id( $child_id, $all_children );
				if ( ! empty( $children2 ) ) {
					$children = array_merge( $children, $children2 );
				}
			}
		}

		return ! empty( $children ) ? array_unique( $children, SORT_NUMERIC ) : array();
	}

	/**
	 * Get all child group IDs => parent relationships.
	 *
	 * @return array
	 */
	private static function get_all_learndash_group_child_posts_ids() {
		static $all_children = null;
		if ( is_null( $all_children ) ) {
			$all_children = get_posts(
				array(
					'post_type'           => learndash_get_post_type_slug( 'group' ),
					'post_parent__not_in' => array( 0 ), // Only retrieve child posts
					'fields'              => 'id=>parent',
					'posts_per_page'      => 99999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
				)
			);
		}

		return $all_children;
	}

	/**
	 * LearnDash get groups user IDs
	 *
	 * @param $group_id
	 *
	 * @return array
	 */
	public static function get_learndash_group_user_ids( $group_id ) {

		static $group_user_ids = array();
		if ( isset( $group_user_ids[ $group_id ] ) ) {
			return $group_user_ids[ $group_id ];
		}

		global $wpdb;

		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_id 
				FROM $wpdb->usermeta 
				WHERE meta_key = %s",
				'learndash_group_users_' . $group_id
			)
		);

		$group_user_ids[ $group_id ] = ! empty( $results ) ? array_map( 'absint', $results ) : array();

		return $group_user_ids[ $group_id ];
	}
}
