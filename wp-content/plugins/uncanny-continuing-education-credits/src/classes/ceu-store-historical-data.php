<?php

namespace uncanny_ceu;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * This class is used to store historical data.
 *
 */
class CeuStoreHistoricalData {

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
	 * Ceu_Store_Historical_Data constructor.
	 */
	public function __construct() {
		self::$historical_course_table  = 'uo_ceu_historical_course_data';
		self::$historical_quiz_table    = 'uo_ceu_historical_quiz_data';
		self::$historical_statistic_ref = 'uo_ceu_ld_pro_quiz_statistic_ref';
		self::$historical_statistic     = 'uo_ceu_ld_pro_quiz_statistic';
		//Actions on which to save data
		add_action( 'plugin_loaded', array( $this, 'plugins_loaded' ), 999 );
	}

	/**
	 *
	 */
	public function plugins_loaded() {
		add_action(
			'learndash_course_completed',
			array(
				$this,
				'learndash_course_completed_func',
			),
			9
		);
		add_action(
			'learndash_quiz_completed',
			array(
				$this,
				'learndash_quiz_completed_func',
			),
			9,
			2
		);
		add_action(
			'uo_course_certificate_pdf_url',
			array(
				$this,
				'save_course_pdf_url_for_historical_record',
			),
			99,
			4
		);
	}

	/**
	 * @param $data
	 */
	public function learndash_course_completed_func( $data ) {
		$user     = $data['user'];
		$course   = $data['course'];
		$progress = $data['progress'];
		$date     = $data['course_completed'];

		$since = ld_course_access_from( $course->ID, $user->ID );
		if ( empty( $since ) ) {
			$since = learndash_user_group_enrolled_to_course_from( $user->ID, $course->ID );
		}

		global $wpdb;
		$insert_data = array(
			'user_id'        => $user->ID,
			'course_id'      => $course->ID,
			'progress'       => maybe_serialize( $progress[ $course->ID ] ),
			'certificate'    => '',
			'date_enrolled'  => $since,
			'date_completed' => $date,
		);
		$wpdb->insert(
			$wpdb->prefix . self::$historical_course_table,
			$insert_data,
			array(
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);
		do_action( 'uo_ceu_historical_course_recorded', $insert_data );
	}

	/**
	 * @param $quiz_data
	 * @param $user
	 */
	public function learndash_quiz_completed_func( $quiz_data, $user ) {

		$statistic_ref_id = $quiz_data['statistic_ref_id'];
		$quiz             = $quiz_data['quiz'];
		$course           = $quiz_data['course'];
		$lesson           = $quiz_data['lesson'];
		$topic            = $quiz_data['topic'];
		$pass             = $quiz_data['pass'];
		$score            = $quiz_data['score'];
		$count            = $quiz_data['count'];
		$time             = $quiz_data['time'];
		$store            = $quiz_data;

		unset( $store['quiz'] );
		unset( $store['course'] );
		unset( $store['lesson'] );
		unset( $store['topic'] );
		unset( $store['questions'] );

		$course_id = is_object( $course ) ? $course->ID : $course;
		$since     = ld_course_access_from( $course_id, $user->ID );
		if ( empty( $since ) ) {
			$since = learndash_user_group_enrolled_to_course_from( $user->ID, $course_id );
		}

		$lesson_id   = is_object( $lesson ) ? $lesson->ID : $lesson;
		$topic_id    = is_object( $topic ) ? $topic->ID : $topic;
		$quiz_id     = is_object( $quiz ) ? $quiz->ID : $quiz;
		$insert_data = array(
			'user_id'          => $user->ID,
			'statistic_ref_id' => $statistic_ref_id,
			'course_id'        => $course_id,
			'lesson_id'        => ! empty( $lesson_id ) ? $lesson_id : 0,
			'topic_id'         => ! empty( $topic_id ) ? $topic_id : 0,
			'quiz_id'          => ! empty( $quiz_id ) ? $quiz_id : 0,
			'passed'           => $pass,
			'score'            => $score,
			'count'            => $count,
			'data'             => maybe_serialize( $store ),
			'certificate'      => '',
			'date_enrolled'    => $since,
			'date_completed'   => $time,
		);
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . self::$historical_quiz_table,
			$insert_data,
			array(
				'%d',
				'%d',
				'%d',
				'%d',
				'%d',
				'%d',
				'%d',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);
		self::copy_stat_records( $statistic_ref_id );
		do_action( 'uo_ceu_historical_quiz_recorded', $insert_data );
	}

	/**
	 * @param $statistic_ref_id
	 *
	 * @return void
	 */
	public static function copy_stat_records( $statistic_ref_id ) {
		if ( 0 === $statistic_ref_id ) {
			return;
		}
		global $wpdb;
		if ( 0 === absint( $statistic_ref_id ) ) {
			return;
		}
		global $wpdb;
		$tbl_1 = $wpdb->prefix . self::$historical_statistic_ref;
		$tbl_2 = \LDLMS_DB::get_table_name( 'quiz_statistic_ref' );
		$wpdb->query( "INSERT INTO  $tbl_1 SELECT * FROM $tbl_2 WHERE statistic_ref_id = $statistic_ref_id;" );

		$tbl_1 = $wpdb->prefix . self::$historical_statistic;
		$tbl_2 = \LDLMS_DB::get_table_name( 'quiz_statistic' );
		$wpdb->query( "INSERT INTO  $tbl_1 SELECT * FROM $tbl_2 WHERE statistic_ref_id = $statistic_ref_id;" );
	}

	/**
	 * @param $pdf_url
	 * @param $course_id
	 * @param $completion_time
	 * @param $user_id
	 */
	public function save_course_pdf_url_for_historical_record( $pdf_url, $course_id, $completion_time, $user_id ) {

		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . self::$historical_course_table,
			array( 'certificate' => $pdf_url ),
			array(
				'course_id'      => $course_id,
				'user_id'        => $user_id,
				'date_completed' => $completion_time,
			),
			array( '%s' ),
			array(
				'%d',
				'%d',
				'%s',
			)
		);
	}
}

