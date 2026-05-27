<?php

namespace uncanny_ceu;

/**
 * Class UserArchiveData
 *
 * @package uncanny_ceu
 */
class UserArchiveData {

	/**
	 * Get user's archived courses that don't have a matching activity record.
	 *
	 * @param int $user_id
	 * @param bool $unserialize
	 *
	 * @return array
	 */
	public static function get_user_archived_courses( $user_id, $unserialize = false ) {

		$courses = self::get_all_archived_courses_for_user( $user_id );

		if ( empty( $courses ) ) {
			return array();
		}

		$usermeta = get_user_meta( $user_id, '_sfwd-course_progress', true );

		// Filter out courses w/o progress that have a matching usermeta record.
		$filtered = array_filter(
			$courses,
			function( $course ) use ( $user_id, $usermeta ) {
				return ! empty( $course['progress'] ) && false === self::match_course_usermeta( $user_id, $usermeta, $course['course_id'], $course['date_completed'] );
			}
		);

		return ( $unserialize && ! empty( $filtered ) ) ? self::unserialize_data( $filtered, 'progress' ) : $filtered;
	}

	/**
	 * Get user's archived quizzes that don't have a matching activity record.
	 *
	 * @param int $user_id
	 * @param bool $unserialize
	 *
	 * @return array
	 */
	public static function get_user_archived_quizzes( $user_id, $unserialize = false ) {
		$quizzes = self::get_all_archived_quizzes_for_user( $user_id );

		if ( empty( $quizzes ) ) {
			return array();
		}

		// Filter out quizzes w/o data that have a matching activity record.
		$filtered = array_filter(
			$quizzes,
			function( $quiz ) use ( $user_id ) {
				return ! empty( $quiz['data'] ) && false === self::match_quiz( $user_id, $quiz['course_id'], $quiz['lesson_id'], $quiz['date_completed'] );
			}
		);

		return ( $unserialize && ! empty( $filtered ) ) ? self::unserialize_data( $filtered, 'data' ) : $filtered;
	}

	/**
	 * Unserialize data.
	 *
	 * @param array $data
	 * @param string $key
	 *
	 * @return array
	 */
	public static function unserialize_data( $data, $key ) {
		return array_map(
			function( $item ) use ( $key ) {
				$item[ $key ] = maybe_unserialize( $item[ $key ] );

				return $item;
			},
			$data
		);
	}

	/**
	 * @param $user_id
	 * @param $course_id
	 * @param $date_completed
	 *
	 * @return bool
	 */
	public static function match_course( $user_id, $course_id, $date_completed ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'learndash_user_activity';
		$query      = "SELECT * FROM {$table_name} WHERE `user_id` = %d AND `course_id` = %d AND `activity_completed` = %s";
		$matches    = $wpdb->get_results( $wpdb->prepare( $query, $user_id, $course_id, $date_completed ), ARRAY_A );

		return ! empty( $matches );
	}

	/**
	 * @param $user_id
	 * @param $meta
	 * @param $course_id
	 * @param $date_completed
	 * 
	 * @return bool
	 */
	public static function match_course_usermeta( $user_id, $meta, $course_id, $date_completed ) {
		if ( empty( $meta ) || ! is_array( $meta ) ) {
			return false;
		}

		if ( ! key_exists( $course_id, $meta ) ) {
			return false;
		}

		// Check the date from the course completed meta.
		$current_course_completed = get_user_meta( $user_id, 'course_completed_' . $course_id, true );

		if ( empty( $current_course_completed ) ) {
			return false;
		}

		return $date_completed === $current_course_completed;
	}

	/**
	 * @param $user_id
	 * @param $course_id
	 * @param $quiz_id
	 * @param $date_completed
	 *
	 * @return array
	 */
	public static function match_quiz( $user_id, $course_id, $quiz_id, $date_completed ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'learndash_user_activity';
		$query      = "SELECT * FROM {$table_name} WHERE `user_id` = %d AND `course_id` = %d AND `post_id` = %d AND `activity_completed` = %s";
		$matches    = $wpdb->get_results( $wpdb->prepare( $query, $user_id, $course_id, $quiz_id, $date_completed ), ARRAY_A );

		return ! empty( $matches );
	}

	/**
	 * Get all archived courses for a user.
	 *
	 * @param int $user_id
	 *
	 * @return array
	 */
	public static function get_all_archived_courses_for_user( $user_id = 0 ) {
		return self::get_user_data( $user_id, 'uo_ceu_historical_course_data' );
	}

	/**
	 * Get all archived quizzed for a user.
	 *
	 * @param int $user_id
	 *
	 * @return array
	 */
	public static function get_all_archived_quizzes_for_user( $user_id = 0 ) {
		return self::get_user_data( $user_id, 'uo_ceu_historical_quiz_data' );
	}

	/**
	 * Get user data.
	 *
	 * @param int $user_id
	 * @param string $table
	 *
	 * @return array
	 */
	public static function get_user_data( $user_id, $table ) {

		if ( ! $user_id ) {
			return array();
		}

		global $wpdb;
		$data = $wpdb->get_results( 
			$wpdb->prepare( 
				"SELECT * FROM {$wpdb->prefix}{$table} 
				WHERE `user_id` = %d 
				ORDER BY `date_completed`
				ASC",
				$user_id 
			),
			ARRAY_A 
		);

		return is_array( $data ) ? $data : array();
	}

	/**
	 * Get quiz question count.
	 *
	 * @param array $quiz
	 *
	 * @return int
	 */
	public static function get_quiz_question_count( $quiz ) {
		$data  = maybe_unserialize( $quiz['data'] );
		$count = is_array( $data ) && isset( $data['question_show_count'] ) 
			? $data['question_show_count'] : $quiz['count'];

		return (int) $count;
	}

	/**
	 * Get quiz percentage.
	 *
	 * @param array $quiz
	 *
	 * @return float|int
	 */
	public static function get_quiz_percentage( $quiz ) {
		$count = self::get_quiz_question_count( $quiz );
		$score = (int) $quiz['score'];

		if ( 0 !== $score && 0 !== $count ) {
			return self::format_percentage( $score / $count * 100 );
		}

		return 0;
	}

	/**
	 * Format a number to two decimal places if they exist, otherwise return the whole number.
	 *
	 * @param float $percentage The number to format.
	 * 
	 * @return float|int The formatted number.
	 */
	public static function format_percentage( $percentage ) {
		return ( $percentage == floor( $percentage ) ) ? (int) $percentage : round( $percentage, 2 );
	}

}
