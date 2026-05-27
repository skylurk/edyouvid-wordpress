<?php

namespace uncanny_ceu;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class CeuMigrateUserRecords
 */
class CeuTincannyQuestionAnalysis {
	/**
	 * @var int|mixed
	 */
	static $quiz_pro = 0;
	/**
	 * @var string
	 */
	public static $historical_course_table;
	/**
	 * @var string
	 */
	public static $historical_quiz_table;
	/**
	 * @var string
	 */
	public static $historical_statistic_ref;
	/**
	 * @var string
	 */
	public static $historical_statistic;

	/**
	 *
	 */
	public function __construct() {


		global $wpdb;
		self::$historical_course_table  = $wpdb->prefix . 'uo_ceu_historical_course_data';
		self::$historical_quiz_table    = $wpdb->prefix . 'uo_ceu_historical_quiz_data';
		self::$historical_statistic_ref = $wpdb->prefix . 'uo_ceu_ld_pro_quiz_statistic_ref';
		self::$historical_statistic     = $wpdb->prefix . 'uo_ceu_ld_pro_quiz_statistic';
		$quiz_id                        = ( isset( $_GET['quiz-id'] ) && 0 !== $_GET['quiz-id'] ) ? absint( $_GET['quiz-id'] ) : 0;
		self::$quiz_pro                 = get_post_meta( $quiz_id, 'quiz_pro_id', true );

		add_action( 'plugins_loaded', array( $this, 'multiple_filters' ), 99 );
	}

	/**
	 * @return void
	 */
	public function multiple_filters() {
		if ( false === apply_filters( 'uo_tincanny_reporting_do_ceu_override', true ) ) {
			return;
		}

		$quiz_id = ( isset( $_GET['quiz-id'] ) && 0 !== $_GET['quiz-id'] ) ? absint( $_GET['quiz-id'] ) : 0;

		$skip_quiz_ids = apply_filters( 'uo_tincanny_reporting_skip_ceu_override_quiz_ids', array(), $quiz_id );

		if ( ! empty( $skip_quiz_ids ) ) {
			$skip_quiz_ids = array_map( 'absint', $skip_quiz_ids );
		}

		if ( in_array( $quiz_id, $skip_quiz_ids, true ) ) {
			return;
		}
		// Answered correctly
		add_filter(
			'uo_tincanny_reporting_questions_get_answered_correctly',
			function ( $qry, $question_id, $table ) {
				global $wpdb;
				$ld_ref_tbl  = self::$historical_statistic_ref;
				$table       = self::$historical_statistic;
				$pro_quiz_id = self::$quiz_pro;
				$date_ranges = self::validate_date_range();

				return $wpdb->prepare(
					"SELECT COUNT(*)
FROM $table s
INNER JOIN $ld_ref_tbl ref
ON ref.statistic_ref_id = s.statistic_ref_id AND ref.quiz_id = $pro_quiz_id
WHERE s.question_id = %d
AND s.correct_count = 1 $date_ranges",
					$question_id
				);
			},
			9999,
			3
		);

		add_filter(
			'uo_tincanny_reporting_questions_get_times_question_asked',
			function ( $qry, $question_id, $table ) {
				global $wpdb;
				$ld_ref_tbl  = self::$historical_statistic_ref;
				$table       = self::$historical_statistic;
				$pro_quiz_id = self::$quiz_pro;
				$date_ranges = self::validate_date_range();

				return $wpdb->prepare(
					"SELECT COUNT(*)
FROM $table s
INNER JOIN $ld_ref_tbl ref
ON ref.statistic_ref_id = s.statistic_ref_id AND ref.quiz_id = $pro_quiz_id
WHERE s.question_id = %d
$date_ranges",
					$question_id
				);
			},
			9999,
			3
		);

		add_filter(
			'uo_tincanny_reporting_questions_get_times_this_answer_selected',
			function ( $qry, $question_id, $question_position, $table ) {
				$ld_ref_tbl  = self::$historical_statistic_ref;
				$table       = self::$historical_statistic;
				$pro_quiz_id = self::$quiz_pro;
				$date_ranges = self::validate_date_range();

				global $wpdb;

				return $wpdb->prepare(
					"SELECT COUNT(*)
FROM $table s
INNER JOIN $ld_ref_tbl ref
ON ref.statistic_ref_id = s.statistic_ref_id AND ref.quiz_id = $pro_quiz_id
WHERE s.question_id = %d
AND s.answer_data LIKE %s AND s.incorrect_count = 1 $date_ranges",
					$question_id,
					$question_position
				);
			},
			9999,
			4
		);

		add_filter(
			'uo_tincanny_reporting_questions_get_avg_time',
			function ( $qry, $question_id, $table ) {
				$ld_ref_tbl  = self::$historical_statistic_ref;
				$table       = self::$historical_statistic;
				$pro_quiz_id = self::$quiz_pro;
				$date_ranges = self::validate_date_range();
				global $wpdb;

				return $wpdb->prepare(
					"SELECT AVG(s.question_time)
FROM $table s
INNER JOIN $ld_ref_tbl ref
ON ref.statistic_ref_id = s.statistic_ref_id AND ref.quiz_id = $pro_quiz_id
WHERE s.question_id = %d
$date_ranges",
					$question_id
				);
			},
			9999,
			3
		);

		add_filter(
			'uo_tincanny_reporting_questions_get_question_total_percent',
			function ( $avg, $question_id, $answered_correctly, $total, $percent ) {
				if ( 0 === absint( $answered_correctly ) || 0 === absint( $total ) ) {
					return 0;
				}

				return number_format( ( $answered_correctly / $total ) * 100, 2 );
			},
			9999,
			5
		);

	}


	/**
	 * @return string
	 */
	private static function validate_date_range() {
		if ( ! isset( $_GET['start_date'] ) && ! isset( $_GET['end_date'] ) ) {
			return '';
		}
		if ( empty( $_GET['start_date'] ) ) {
			return '';
		}
		$start_date = sanitize_text_field( $_GET['start_date'] );
		$end_date   = sanitize_text_field( $_GET['end_date'] );
		if ( empty( $end_date ) ) {
			$end_date = date( 'Y-m-d' );
		}
		$start_date_timestamp = strtotime( $start_date );
		$end_date_timestamp   = strtotime( $end_date );

		return " AND ref.create_time BETWEEN $start_date_timestamp AND $end_date_timestamp ";
	}
}
