<?php

namespace uncanny_ceu;

/**
 *
 */
class CeuArchivedQuizResults {

	/**
	 *
	 */
	public function __construct() {

		//Add Additional field on User Settings page for Archived quiz results.
		add_action(
			'show_user_profile',
			array(
				$this,
				'add_additional_archive_fields',
			)
		);
		add_action(
			'edit_user_profile',
			array(
				$this,
				'add_additional_archive_fields',
			)
		);

	}

	/**
	 * Displays users course information at bottom of profile
	 * called by show_user_profile().
	 *
	 * @param WP_User $user wp user object.
	 */
	public function add_additional_archive_fields( $user ) {

		// Courses
		$archived_courses = UserArchiveData::get_user_archived_courses( $user->ID );

		if ( ! empty( $archived_courses ) ) {

			echo '<h3 style="margin-top:30px;">' . esc_html__( 'Archived course results:', 'uncanny-ceu' ) . '</h3>';

			echo '<div class="ceu-archived-course-content-container">';
			foreach ( $archived_courses as $course ) {
				echo '<p id="ceu-quiz">';
				echo '<strong><a href="' . get_permalink( $course['course_id'] ) . '">' . get_the_title( $course['course_id'] ) . '</a></strong> - ';
				echo sprintf( __( 'Completed on: %1$s', 'uncanny-ceu' ), learndash_adjust_date_time_display( $course['date_completed'] ) );
				echo '</p>';
			}
			echo '</div>';
		}

		// Quizzes
		$archived_quizzes = UserArchiveData::get_user_archived_quizzes( $user->ID );
		if ( ! empty( $archived_quizzes ) ) {

			echo '<h3 style="margin-top:30px;">' . esc_html__( 'Archived quiz results:', 'uncanny-ceu' ) . '</h3>';

			echo '<div class="ceu-archived-quiz-content-container">';
			foreach ( $archived_quizzes as $quiz ) {
				if ( is_array( $quiz ) ) {
					$count      = UserArchiveData::get_quiz_question_count( $quiz );
					$percentage = UserArchiveData::get_quiz_percentage( $quiz );
					$css        = (int) $quiz['passed'] ? 'green' : 'red';
					echo '<p id="ceu-quiz">';
					echo '<strong><a href="' . get_permalink( $quiz['quiz_id'] ) . '">' . get_the_title( $quiz['quiz_id'] ) . '</a></strong> - ';
					echo '<span style="color:' . $css . '">' . number_format( $percentage, 2 ) . '%</span><br>';
					echo sprintf( __( 'Score %1$s out of %2$s question(s) . Points: %3$s on %4$s', 'uncanny-ceu' ), $quiz['score'], $count, $quiz['score'] . '/' . $count, learndash_adjust_date_time_display( $quiz['date_completed'] ) );
					echo '</p>';
				}
			}
			echo '</div>';
		}
	}
}