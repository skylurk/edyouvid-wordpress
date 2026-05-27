<?php

namespace uncanny_ceu;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class CeusColumnUoProTranscript
 *
 * @package uncanny_ceu
 */
class CeusColumnUoProTranscript {

	/**
	 * Array of user's CEU completions.
	 *
	 * @var array
	 */
	private $ceu_completions = array();

	/**
	 * class constructor
	 */
	function __construct() {
		add_filter( 'uo_pro_transcript', array( $this, 'uo_filter_transcript_table' ), 10, 5 );
		add_filter(
			'uo_pro_transcript_cumulative_column',
			array(
				$this,
				'uo_pro_transcript_cumulative_column',
			),
			10,
			3
		);
	}

	/**
	 * Add CEUs headling to UO Transcript
	 *
	 * @param $table
	 *
	 * @return mixed
	 */

	/**
	 * Add CEUs heading and row values to UO Transcript
	 *
	 * @param $transcript
	 * @param \WP_User $current_user
	 * @param \uncanny_pro_toolkit\LearnDashTranscript $learndash_transcript
	 * @param $data
	 * @param $request
	 *
	 * @return mixed
	 */
	public function uo_filter_transcript_table( $transcript, $current_user, $learndash_transcript, $data, $request ) {

		$update_summary = false;

		if ( 'on' !== $learndash_transcript::get_settings_value( 'uncanny-disable-ceus-col', $learndash_transcript ) ) {
			$transcript     = $this->add_ceus_to_transcript( $transcript, $current_user, $learndash_transcript, $data, $request );
			$update_summary = true;
		}

		// Show historical CEU course in transcript.
		if ( 'on' === $learndash_transcript::get_settings_value( 'uncanny-enable-ceus-archived-courses', $learndash_transcript ) ) {
			$transcript     = $this->add_archived_courses_to_transcript( $transcript, $current_user, $learndash_transcript, $data, $request );
			$update_summary = true;
		}

		if ( $update_summary ) {
			if ( isset( $transcript->summary->status_completed ) && isset( $transcript->summary->status_enrolled ) ) {
				// re-set the total courses completed header summary column
				$transcript->summary->status = sprintf(
					esc_attr__( '%1$s / %2$s courses completed', 'uncanny-ceu' ),
					$transcript->summary->status_completed,
					$transcript->summary->status_enrolled
				);
			}
		}

		return $transcript;
	}

	/**
	 * Add cumulative value for the CEU column
	 *
	 * @param $column
	 * @param $key
	 * @param $transcript
	 *
	 * @return array
	 */
	public function uo_pro_transcript_cumulative_column( $column, $key, $transcript ) {

		if ( 'ceu' === $key ) {
			$total_ceus = 0;
			foreach ( $transcript->table->rows as $course_id => $row ) {
				if ( isset( $row->ceu ) ) {
					$total_ceus += $row->ceu;
				}
			}

			$credit_designation_label_plural = Utilities::credit_designation_label( 'plural', __( 'CEUs', 'uncanny-ceu' ) );

			$title = sprintf(
			// Translators: %1$s credit designation label plural
				__( 'Total %1$s Earned', 'uncanny-ceu' ),
				$credit_designation_label_plural
			);

			$column[ $key ]['title'] = $title;
			$column[ $key ]['value'] = $total_ceus;

			return $column;
		};

		return $column;
	}

	/**
	 * Add CEUs columns to the transcript
	 *
	 * @param $transcript
	 * @param $current_user
	 * @param $learndash_transcript
	 * @param $data
	 * @param $request
	 *
	 * @return mixed
	 */
	public function add_ceus_to_transcript( $transcript, $current_user, $learndash_transcript, $data, $request ) {

		$credit_designation_label_plural = Utilities::credit_designation_label( 'plural', __( 'CEUs', 'uncanny-ceu' ) );
		$transcript->table->heading->ceu = $credit_designation_label_plural;

		// Add CEUs to each course row.
		$ceu_complettions                = $this->get_user_ceu_completions( $current_user->ID );
		$credit_designation_label_plural = Utilities::credit_designation_label( 'plural', __( 'CEUs', 'uncanny-ceu' ) );
		$table['headings'][]             = $credit_designation_label_plural;
		$total_ceus                      = 0;

		// Check if we're showing archived courses.
		$show_archived = 'on' === $learndash_transcript::get_settings_value( 'uncanny-enable-ceus-archived-courses', $learndash_transcript );

		foreach ( $transcript->table->rows as $course_id => &$row ) {
			// Default value
			$ceus = __( '0', 'uncanny-ceu' );

			// Check course status starts with 'Completed'.
			$is_completed = false !== strpos( $row->course_status, esc_html__( 'Completed', 'learndash' ) );

			// Get course completion date.
			$completed = $is_completed ? get_user_meta( $current_user->ID, 'course_completed_' . $course_id, true ) : false;

			// Course not completed bail.
			if ( ! $completed ) {
				// Set CEU to 0 and continue to the next row.
				$row->ceu = $ceus;
				continue;
			}

			// Get index key for CEU completions.
			$course_key = $show_archived
				// Set unique key for CEU completion if we're showing archived courses {timestamp}_{course ID}
				? $completed . '_' . $course_id
				// Otherwise, use the course ID.
				: $course_id;
			if ( isset( $ceu_complettions[ $course_key ] ) ) {
				$ceus = $ceu_complettions[ $course_key ];
			}

			$total_ceus += $ceus;
			$row->ceu   = $ceus;
		}

		// Show CEUs in transcript
		if ( 'on' === $learndash_transcript::get_settings_value( 'uncanny-enable-ceus-rows', $learndash_transcript ) ) {

			$ceu_completions = array();

			$user_meta = get_user_meta( $current_user->ID );

			foreach ( $user_meta as $meta_key => $meta_value ) {

				if ( false !== strpos( $meta_key, 'ceu_' ) ) {

					$key        = explode( '_', $meta_key );
					$collection = array( 'title', 'date', 'course', 'earned' );

					if ( ! in_array( $key[1], $collection ) ) {
						continue;
					}

					$unique_key = $key[2] . '_' . $key[3] . '_' . $current_user->ID; //unique identifier for ceu completion...  {timestamp}_{course ID}
					$value_key  = $key[0] . '_' . $key[1]; // name of data stored... ceu_title  ceu_date  ceu_course  ceu_earned

					if ( 'ceu_earned' === $value_key ) {
						$total_ceus += (float) $meta_value[0];
					}

					if ( ! isset( $ceu_completions[ $unique_key ] ) ) {
						$ceu_completions[ $unique_key ]              = array();
						$ceu_completions[ $unique_key ]['course_id'] = $key[3];
						$ceu_completions[ $unique_key ]['course']    = $key[3];
						$ceu_completions[ $unique_key ]['total']     = 0;
					}

					$ceu_completions[ $unique_key ][ $value_key ] = $meta_value[0];

					if ( 'ceu_earned' === $value_key ) {
						$ceu_completions[ $unique_key ]['total'] += $total_ceus;
					}
				}
			}

			$empty_row_obj = $this->get_new_table_row_object( $transcript->table );
			foreach ( $ceu_completions as $key => $completion ) {

				if ( 'manual-ceu' !== $completion['ceu_course'] ) {
					continue;
				}

				// Add row data
				$_row                = clone $empty_row_obj;
				$_row->course_title  = $completion['ceu_title'];
				$_row->course_date   = $completion['ceu_date'];
				$_row->course_status = esc_html__( 'Completed', 'learndash' );

				$_row->ceu = (float) $completion['ceu_earned'];

				// add created row to the transcript
				$transcript->table->rows[ $key ] = $_row;

				// Increment the CEU value total
				$total_ceus += $_row->ceu;

				// add courses completed to summary
				if ( isset( $transcript->summary->status_completed ) ) {
					$transcript->summary->status_completed ++;
				}
				if ( isset( $transcript->summary->status_enrolled ) ) {
					$transcript->summary->status_enrolled ++;
				}
			}
		}

		// re-set the total CEUs footer column
		$transcript->final_ceus = $total_ceus;
		return $transcript;
	}

	/**
	 * Add archived courses to the transcript
	 *
	 * @param $transcript
	 * @param $current_user
	 * @param $learndash_transcript
	 * @param $data
	 * @param $request
	 *
	 * @return mixed
	 */
	public function add_archived_courses_to_transcript( $transcript, $current_user, $learndash_transcript, $data, $request ) {

		// Get user's archived courses data.
		$archived_courses = UserArchiveData::get_user_archived_courses( $current_user->ID, true );

		// If no archived courses, return transcript as is.
		if ( empty( $archived_courses ) ) {
			return $transcript;
		}

		// Filter out archived courses.
		foreach ( $archived_courses as $key => $course ) {
			// Category filter
			if ( ! $learndash_transcript::validate_course_in_cat( $course['course_id'], $data, $request ) ) {
				unset( $archived_courses[ $key ] );
				continue;
			}
		}

		// If no archived courses left, return transcript as is.
		if ( empty( $archived_courses ) ) {
			return $transcript;
		}

		// Add settings values to variables.
		$show_ceu_colum     = 'on' !== $learndash_transcript::get_settings_value( 'uncanny-disable-ceus-col', $learndash_transcript );
		$show_ceu_row       = 'on' === $learndash_transcript::get_settings_value( 'uncanny-enable-ceus-rows', $learndash_transcript );
		$show_steps         = 'on' !== $learndash_transcript::get_settings_value( 'uncanny-disable-transcript-stepscompleted-col', $learndash_transcript );
		$show_certs         = 'on' !== $learndash_transcript::get_settings_value( 'uncanny-disable-certificate-col', $learndash_transcript );
		$show_average       = 'on' !== $learndash_transcript::get_settings_value( 'uncanny-disable-transcript-avgquizscore-col', $learndash_transcript );
		$show_final_average = 'on' !== $learndash_transcript::get_settings_value( 'uncanny-disable-transcript-finalquizscore-col', $learndash_transcript );

		// Get all user data for quizzes and CEUs.
		$archived_quizzes = array();
		if ( $show_average || $show_final_average ) {
			$archived_quizzes = UserArchiveData::get_user_archived_quizzes( $current_user->ID, true );
		}

		$ceu_completions = array();
		if ( $show_ceu_colum || $show_ceu_row ) {
			$ceu_completions = $this->get_user_ceu_completions( $current_user->ID );
		}

		// Create an empty row object for the loop.
		$empty_row_obj = $this->get_new_table_row_object( $transcript->table );

		// Loop through each archived course and add it to the transcript.
		foreach ( $archived_courses as $course ) {
			$course_id = (int) $course['course_id'];
			$progress  = $course['progress'];
			$enrolled  = (int) $course['date_enrolled'];
			$completed = (int) $course['date_completed'];

			// Filter out quizzes that are for our specific course record.
			$course_quizzes = array_filter(
				$archived_quizzes,
				function( $quiz ) use ( $course_id, $enrolled, $completed ) {
					$date = (int) $quiz['date_completed'];
					return ( (int) $quiz['course_id'] === (int) $course_id ) && // Filter out matching course IDs.
						   ( $date > 0 && $date >= (int) $enrolled && $date <= (int) $completed ); // Filter quizzes completed within the course dates.
				}
			);

			// Generate row data.
			$row                = clone $empty_row_obj;
			$row->course_title  = get_the_title( $course_id );
			$row->course_date   = $completed;
			$row->course_status = __( 'Completed', 'learndash' );
			$row->ceu           = 0;

			if ( $show_ceu_colum || $show_ceu_row ) {
				$unique_key = $completed . '_' . $course_id; // {timestamp}_{course ID}
				$row->ceu   = isset( $ceu_completions[ $unique_key ] ) ? $ceu_completions[ $unique_key ] : 0;
			}

			if ( $show_steps ) {
				/* Translators: 1. number of lessons completed 2. number of total lessons */
				$row->lessons = sprintf( esc_attr__( '%1$s / %2$s', 'uncanny-pro-toolkit' ), $progress['completed'], $progress['total'] );
			}

			if ( $show_certs ) {
				$row->certificate_link = $learndash_transcript::generate_certificate_link( $course['certificate'] );
			}

			if ( $show_average ) {
				$row->avg_score = esc_attr__( '0%', 'uncanny-pro-toolkit' );
				$quiz_average   = $this->get_archived_quiz_average_percentage( $course_quizzes );
				if ( ! empty( $quiz_average ) ) {
					$transcript->calculations->avg_quizzes_completed ++;
					$transcript->calculations->sum_course_average_percentage_quizzes_score += $quiz_average;
					/* Translators: %1$s is the average quiz score */
					$row->avg_score = sprintf( esc_attr__( '%1$s%%', 'uncanny-pro-toolkit' ), $quiz_average );
				}
			}

			if ( $show_final_average ) {
				$row->final_score = esc_attr__( '0%', 'uncanny-pro-toolkit' );
				$final_score      = $this->get_archived_final_quiz_percentage( $course_quizzes, $course_id, $current_user->ID );
				if ( ! empty( $final_score ) ) {
					$transcript->calculations->final_quizzes_completed ++;
					$transcript->calculations->sum_course_final_percentage_quizzes_score += $final_score;
					/* Translators: %1$s is the final quiz score */
					$row->final_score = sprintf( esc_attr__( '%1$s%%', 'uncanny-pro-toolkit' ), $final_score );
				}
			}

			// add created row to the transcript
			$transcript->table->rows[ $course_id . '-' . $completed ] = $row;

			// Increment the CEU value total
			if ( $show_ceu_colum ) {
				$transcript->final_ceus += $row->ceu;
			}

			// add courses completed to summary
			if ( isset( $transcript->summary->status_completed ) ) {
				$transcript->summary->status_completed ++;
			}
			if ( isset( $transcript->summary->status_enrolled ) ) {
				$transcript->summary->status_enrolled ++;
			}
		}

		// Maybe add Quiz score totals.
		return $learndash_transcript::maybe_add_quiz_scores( $transcript );
	}

	/**
	 * Generate empty table row object
	 *
	 * @param object $table
	 *
	 * @return object
	 */
	public function get_new_table_row_object( $table ) {
		// Get the first row element's key if it exists.
		$index = ! empty( $table->rows ) ? key( $table->rows ) : null;
		// Get the properties of the first row element or the heading if no rows exist.
		$array = ! is_null( $index ) ? $table->rows[ $index ] : $table->heading;
		// Get the keys of the object properties
		$props = array_keys( get_object_vars( $array ) );
		// Create an object with all the expected keys with empty values
		return (object) array_fill_keys( $props, '' );
	}

	/**
	 * Get all CEU completions for a user.
	 *
	 * @param $user_id
	 *
	 * @return array
	 */
	public function get_user_ceu_completions( $user_id ) {

		if ( isset( $this->ceu_completions[ $user_id ] ) ) {
			return $this->ceu_completions[ $user_id ];
		}

		global $wpdb;

		$completions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_key, meta_value FROM {$wpdb->usermeta} WHERE meta_key LIKE %s AND user_id = %d",
				'ceu_earned_%',
				$user_id
			),
			ARRAY_A
		);

		$ceu_completions = array();
		foreach ( $completions as $completion ) {
			$results                       = explode( '_', $completion['meta_key'] );
			$course_id                     = $results[3];
			$ceu_completions[ $course_id ] = $completion['meta_value'];
			// Stash the unique key for archived records.
			$unique_key                     = $results[2] . '_' . $results[3]; // {timestamp}_{course ID}
			$ceu_completions[ $unique_key ] = $completion['meta_value'];
		}

		$this->ceu_completions[ $user_id ] = $ceu_completions;

		return $ceu_completions;
	}

	/**
	 * Get the average quiz score for a course.
	 *
	 * @param array $quizzes - Pre-filtered by course and date.
	 *
	 * @return int
	 */
	private function get_archived_quiz_average_percentage( $quizzes ) {

		// If no quizzes for this course, return 0%.
		if ( empty( $quizzes ) ) {
			return 0;
		}

		// Get the percentage from each quiz and average them.
		$average = array_reduce(
			$quizzes,
			function( $carry, $quiz ) {
				// Get percentage from quiz
				$percentage = UserArchiveData::get_quiz_percentage( $quiz );
				// Add calculated percentage to the carry
				return $carry + $percentage;
			},
			0
		);

		// Avoid division by zero if there are no quizzes
		$percentage = count( $quizzes ) > 0 ? $average / count( $quizzes ) : 0;
		return UserArchiveData::format_percentage( $percentage );
	}

	/**
	 * Get the final quiz percentage for a course.
	 *
	 * @param array $quizzes - Pre-filtered by course and date.
	 * @param int $course_id
	 * @param int $user_id
	 *
	 * @return int
	 */
	private function get_archived_final_quiz_percentage( $quizzes, $course_id, $user_id ) {

		// No quizzes for this course, return 0%.
		if ( empty( $quizzes ) ) {
			return 0;
		}

		// If only one quiz, return the percentage.
		if ( 1 === count( $quizzes ) ) {
			$quiz       = reset( $quizzes );
			return UserArchiveData::get_quiz_percentage( $quiz );
		}

		// Determine the final quiz for the course.
		$course_quiz_list = learndash_get_course_quiz_list( $course_id );
		$last_quiz        = ! empty( $course_quiz_list ) ? end( $course_quiz_list ) : null;

		// No quiz found for course check for last lesson quiz.
		if ( empty( $last_quiz ) ) {
			$course_lesson_list = learndash_get_lesson_list( $course_id );
			if ( empty( $course_lesson_list ) ) {
				return 0;
			}
			$lesson_quizzes = learndash_get_lesson_quiz_list( end( $course_lesson_list ), $user_id, $course_id );
			if ( empty( $lesson_quizzes ) ) {
				return 0;
			}
			$last_quiz = end( $lesson_quizzes );
		}

		$last_quiz_id = $last_quiz['post']->ID;

		// Filter out final quizzes.
		$final_quizzes = array_filter(
			$quizzes,
			function( $quiz ) use ( $last_quiz_id ) {
				return (int) $quiz['quiz_id'] === (int) $last_quiz_id;
			}
		);

		// No final quizzes found.
		if ( empty( $final_quizzes ) ) {
			return 0;
		}

		// Get the highest percentage from the final quizzes.
		$final_percentage = 0;
		foreach ( $final_quizzes as $quiz ) {
			// Ensure score and count are set and count is not zero to avoid division by zero.
			if ( isset( $quiz['score'], $quiz['count'] ) && (int) $quiz['count'] !== 0 ) {
				$percentage = UserArchiveData::get_quiz_percentage( $quiz );
				// Update final_percentage if this quiz's percentage is higher.
				if ( $percentage > $final_percentage ) {
					$final_percentage = $percentage;
				}
			}
		}

		return UserArchiveData::format_percentage( $final_percentage );
	}

}
