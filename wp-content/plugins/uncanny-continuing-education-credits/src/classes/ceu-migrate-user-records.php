<?php

namespace uncanny_ceu;

/**
 * Class CeuMigrateUserRecords
 */
class CeuMigrateUserRecords {

	/**
	 * @var mixed|void
	 */
	public static $initial_move;

	/**
	 * @var string
	 */
	public static $migration_hook = 'migrate_ceu_records_from_usermeta';

	/**
	 * @var array
	 */
	public static $records = array();


	/**
	 *
	 */
	public function __construct() {
		self::$initial_move = get_option( 'uo-has-ld-data-not-moved-to-archive', true );
		add_filter( 'cron_schedules', array( $this, 'ceu_cron_schedules' ) );
		add_action( 'uo_ceu_archive_course', array( $this, 'run_course_archive' ) );
		add_action( 'uo_ceu_archive_quiz', array( $this, 'run_quiz_archive' ) );

		add_action( 'ceu_activation_after', array( $this, 'migrate_ceu_records' ) );

		add_action( self::$migration_hook, array( __CLASS__, 'migrate_usermeta_records' ) );
		// For manually running it
		//self::manually_run_usermeta_migration();
	}

	public static function manually_run_usermeta_migration() {
		add_action( 'init', array( __CLASS__, 'migrate_usermeta_records' ) );
	}

	/**
	 * @return void
	 */
	public function migrate_ceu_records() {

		if ( 'yes' === get_option( 'ceu_records_migrated_from_usermeta', 'no' ) ) {
			return;
		}

		if ( wp_get_schedule( self::$migration_hook ) ) {
			return;
		}

		wp_schedule_single_event( time() + 10, self::$migration_hook );
	}

	/**
	 * @return void
	 */
	public static function migrate_usermeta_records( $force = false ) {
		// If data is migrated, return
		if ( false === $force && 'yes' === get_option( 'ceu_records_migrated_from_usermeta', 'no' ) ) {
			return;
		}

		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->usermeta WHERE meta_key LIKE %s", '%%ceu%%'
			)
		);

		if ( empty( $results ) ) {
			add_option( 'ceu_records_migrated_from_usermeta', 'yes' );

			return;
		}

		if ( self::clean_up_record( $results ) ) {

			if ( empty( self::$records ) ) {
				add_option( 'ceu_records_migrated_from_usermeta', 'yes' );

				return;
			}
		}

		global $wpdb;
		foreach ( self::$records as $user_id => $completion_record ) {
			foreach ( $completion_record as $completion_time => $record ) {

				$data = array(
					'user_id'          => $user_id,
					'course_id'        => ! empty( $record['course_id'] ) ? $record['course_id'] : 0,
					'custom_course_id' => ! empty( $record['custom_course_id'] ) ? $record['custom_course_id'] : '',
					'credits'          => ! empty( $record['credits'] ) ? $record['credits'] : 0,
					'course_title'     => ! empty( $record['course_title'] ) ? $record['course_title'] : '',
					//'certificate_ids'  => isset( $record['certificate_ids'] ) ? maybe_serialize( $record['certificate_ids'] ) : '',
					'date_earned'      => wp_date( 'Y-m-d H:i:s', $completion_time ),
					'earned_timestamp' => $completion_time,
					'is_manual'        => isset( $record['is_manual'] ) ?? 0,
					//'usermeta_key'     => isset( $record['usermeta_key'] ) ? maybe_serialize( $record['usermeta_key'] ) : '',
				);

				$wpdb->insert(
					"{$wpdb->prefix}uo_ceu_records",
					$data
				);

				$ceu_ID = $wpdb->insert_id;

				if ( is_numeric( $ceu_ID ) ) {

					if ( ! empty( $record['certificate_ids'] ) ) {
						foreach ( $record['certificate_ids'] as $certs ) {
							foreach ( $certs as $cert_id ) {
								$data = array(
									'ceu_ID'         => $ceu_ID,
									'certificate_id' => $cert_id,
									'date_earned'    => $completion_time,
								);

								$wpdb->insert(
									"{$wpdb->prefix}uo_ceu_records_certs",
									$data
								);
							}
						}

					}

					if ( ! empty( $record['usermeta_key'] ) ) {
						foreach ( $record['usermeta_key'] as $metas ) {
							foreach ( $metas as $meta_key => $meta_value ) {
								$data = array(
									'ceu_ID'     => $ceu_ID,
									'meta_key'   => $meta_key,
									'meta_value' => $meta_value,
								);

								$wpdb->insert(
									"{$wpdb->prefix}uo_ceu_records_usermeta",
									$data
								);
							}
						}
					}
				}
			}
		}

		add_option( 'ceu_records_migrated_from_usermeta', 'yes' );
	}

	/**
	 * @param $results
	 *
	 * @return true
	 * @todo Add ceu_multicert_
	 */
	public static function clean_up_record( $results ) {
		foreach ( $results as $record ) {
			$user_id = $record->user_id;

			if ( ! isset( self::$records[ $user_id ] ) ) {
				self::$records[ $user_id ] = array();
			}

			// ceu_course_1646172964_331
			// ceu_course_1646174211_manual-ceu-test-ceus
			if ( 'ceu_course_' === substr( $record->meta_key, 0, 11 ) ) {
				if ( ! strpos( $record->meta_key, '_manual-' ) ) {
					$cleaned   = explode( '_', str_replace( 'ceu_course_', '', $record->meta_key ) );
					$time      = $cleaned[0];
					$course_id = $cleaned[1];

					self::$records[ $user_id ][ $time ]['course_id'] = $course_id;

				} else {
					// ceu_course_1646174211_manual-ceu-test-ceus
					$cleaned = explode( '_', str_replace(
						array(
							'ceu_course_',
							'manual-',
						), '', $record->meta_key ) );

					$course_id = $cleaned[1];
					$time      = $cleaned[0];

					self::$records[ $user_id ][ $time ]['course_id']        = 0;
					self::$records[ $user_id ][ $time ]['custom_course_id'] = $course_id;
					self::$records[ $user_id ][ $time ]['is_manual']        = 1;
				}

				self::add_meta_key( $record->umeta_id, $record->meta_key, $record->meta_value, $user_id, $time );
			}

			//ceu_cert_earned_317
			if ( 'ceu_cert_earned_' === substr( $record->meta_key, 0, 16 ) ) {
				$cleaned = explode( '_', str_replace( 'ceu_cert_earned_', '', $record->meta_key ) );
				$time    = $record->meta_value;

				self::$records[ $user_id ][ $time ]['certificate_ids'][] = $cleaned;

				self::add_meta_key( $record->umeta_id, $record->meta_key, $record->meta_value, $user_id, $time );
			}

			// ceu_title_1646172964_331
			if ( 'ceu_title_' === substr( $record->meta_key, 0, 10 ) ) {
				$cleaned = explode( '_', str_replace( 'ceu_title_', '', $record->meta_key ) );
				$time    = $cleaned[0];
				$title   = $record->meta_value;

				self::$records[ $user_id ][ $time ]['course_title'] = $title;

				self::add_meta_key( $record->umeta_id, $record->meta_key, $record->meta_value, $user_id, $time );
			}

			// ceu_earned_1646172964_331
			if ( 'ceu_earned_' === substr( $record->meta_key, 0, 11 ) ) {
				$cleaned = explode( '_', str_replace( 'ceu_earned_', '', $record->meta_key ) );
				//$course_id = $cleaned[1];
				$time   = $cleaned[0];
				$earned = $record->meta_value;

				self::$records[ $user_id ][ $time ]['credits'] = $earned;

				self::add_meta_key( $record->umeta_id, $record->meta_key, $record->meta_value, $user_id, $time );
			}

			// ceu_date_1531146336_87
			if ( 'ceu_date_' === substr( $record->meta_key, 0, 9 ) ) {
				$cleaned = explode( '_', str_replace( 'ceu_date_', '', $record->meta_key ) );
				$time    = $cleaned[0];

				self::$records[ $user_id ][ $time ]['completion_time'] = $time;

				self::add_meta_key( $record->umeta_id, $record->meta_key, $record->meta_value, $user_id, $time );
			}
		}

		return true;
	}

	/**
	 * Keep record of existing data
	 *
	 * @param $meta_id
	 * @param $key
	 * @param $value
	 * @param $user_id
	 * @param $time
	 *
	 * @return void
	 */
	private static function add_meta_key( $meta_id, $key, $value, $user_id, $time ) {
		self::$records[ $user_id ][ $time ]['usermeta_key'][ $meta_id ] = array(
			$key => $value,
		);
	}

	/**
	 *
	 */
	public function run_quiz_archive( $start = 0, $end = 500 ) {
		global $wpdb;
		$history_quiz_data = $wpdb->prefix . 'uo_ceu_historical_quiz_data';
		$get_user_data     = $wpdb->get_results( $wpdb->prepare( "SELECT *
FROM {$wpdb->usermeta}
WHERE meta_key LIKE '_sfwd-quizzes'
ORDER BY user_id ASC
LIMIT %d, %d", $start, $end ) );

		if ( empty( $get_user_data ) ) {
			wp_clear_scheduled_hook( 'uo_ceu_archive_quiz' );

			return;
		}

		foreach ( $get_user_data as $data ) {
			$user_id    = $data->user_id;
			$meta_key   = $data->meta_key;
			$meta_value = maybe_unserialize( $data->meta_value );
			if ( is_array( $meta_value ) && ! empty( $meta_key ) ) {
				foreach ( $meta_value as $quiz_data ) {
					$quiz             = $quiz_data['quiz'];
					$statistic_ref_id = $quiz_data['statistic_ref_id'];
					$course           = $quiz_data['course'];
					$lesson           = $quiz_data['lesson'];
					$topic            = $quiz_data['topic'];
					$pass             = $quiz_data['pass'];
					$score            = $quiz_data['score'];
					$count            = $quiz_data['count'];
					$time             = $quiz_data['time'];
					$store            = $quiz_data;
					$since            = ld_course_access_from( is_object( $course ) ? $course->ID : $course, $user_id );
					if ( empty( $since ) ) {
						$since = learndash_user_group_enrolled_to_course_from( $user_id, is_object( $course ) ? $course->ID : $course );
					}

					if ( isset( $store['quiz'] ) ) {
						unset( $store['quiz'] );
					}
					if ( isset( $store['course'] ) ) {
						unset( $store['course'] );
					}
					if ( isset( $store['lesson'] ) ) {
						unset( $store['lesson'] );
					}
					if ( isset( $store['topic'] ) ) {
						unset( $store['topic'] );
					}
					if ( isset( $store['questions'] ) ) {
						unset( $store['questions'] );
					}
					$insert = array(
						'user_id'          => $user_id,
						'statistic_ref_id' => $statistic_ref_id,
						'course_id'        => is_object( $course ) ? $course->ID : $course,
						'lesson_id'        => is_object( $lesson ) ? $lesson->ID : $lesson,
						'topic_id'         => is_object( $topic ) ? $topic->ID : $topic,
						'quiz_id'          => is_object( $quiz ) ? $quiz->ID : $quiz,
						'passed'           => $pass,
						'score'            => $score,
						'count'            => $count,
						'data'             => maybe_serialize( $store ),
						'certificate'      => '',
						'date_completed'   => $time,
						'date_enrolled'    => $since,
					);
					$flag   = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$history_quiz_data} WHERE `date_completed` = '%s' AND `user_id` = %d", $time, $user_id ) );
					if ( $flag > 0 ) {
						continue;
					}
					$wpdb->insert(
						$history_quiz_data,
						$insert,
						array(
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
							'%s',
							'%s',
						)
					);
					CeuStoreHistoricalData::copy_stat_records( $statistic_ref_id );
					do_action( 'uo_ceu_historical_quiz_recorded', $insert );

				}
			}
		}

		update_option( 'uo-ceu-quize-archived', date( 'Y-m-d H:i', current_time( 'timestamp' ) ) );
		$next = $start + $end;
		wp_schedule_single_event( time() + 60, 'uo_ceu_archive_quiz', array( $next, $end ) );
	}

	/**
	 *
	 */
	public function run_course_archive( $start = 0, $end = 2500 ) {
		global $wpdb;
		$history_courses_data = $wpdb->prefix . 'uo_ceu_historical_course_data';
		$get_user_data        = $wpdb->get_results( $wpdb->prepare( "SELECT *
FROM $wpdb->usermeta
WHERE meta_key LIKE '_sfwd-course_progress'
OR meta_key LIKE '%%_access_from'
OR meta_key LIKE 'course_completed_%%'
OR meta_key LIKE '%%-cert-%%'
ORDER BY user_id ASC
LIMIT %d, %d", $start, $end ) );

		if ( empty( $get_user_data ) ) {
			wp_clear_scheduled_hook( 'uo_ceu_archive_course' );

			return;
		}

		$user_ids        = array_column( $get_user_data, 'user_id' );
		$course_progress = $this->normalize_course_data( $get_user_data );

		if ( empty( $course_progress ) ) {
			wp_clear_scheduled_hook( 'uo_ceu_archive_course' );

			return;
		}

		foreach ( $user_ids as $user_id ) {
			$courses = $course_progress[ $user_id ];
			if ( is_array( $courses ) ) {
				foreach ( $courses as $course_id => $c_p ) {
					$since = isset( $c_p['access_from'] ) ? $c_p['access_from'] : '';
					if ( empty( $since ) ) {
						$since = ld_course_access_from( $course_id, $user_id );
						if ( empty( $since ) ) {
							$since = learndash_user_group_enrolled_to_course_from( $user_id, $course_id );
						}
					}
					if ( ! array_key_exists( 'completed', $c_p ) ) {
						continue;
					}
					$flag = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$history_courses_data} WHERE `date_completed` = '%s' AND `user_id` = %d", $c_p['completed'], $user_id ) );
					if ( $flag > 0 ) {
						continue;
					}
					$counter ++;
					$insert = array(
						'user_id'        => $user_id,
						'course_id'      => $course_id,
						'progress'       => isset( $c_p['progress'] ) ? $c_p['progress'] : '',
						'certificate'    => isset( $c_p['cert'] ) ? $c_p['cert'] : '',
						'date_enrolled'  => $since,
						'date_completed' => isset( $c_p['completed'] ) ? $c_p['completed'] : '',
					);
					$wpdb->insert(
						$history_courses_data,
						$insert,
						array(
							'%d',
							'%d',
							'%s',
							'%s',
							'%s',
							'%s',
						)
					);
					do_action( 'uo_ceu_historical_course_recorded', $insert );
				}
			}
		}

		update_option( 'uo-ceu-course-archived', date( 'Y-m-d H:i', current_time( 'timestamp' ) ) );
		$next = $start + $end;
		wp_schedule_single_event( time() + 60, 'uo_ceu_archive_course', array( $next, $end ) );
	}

	/**
	 * @param $results
	 *
	 * @return array
	 */
	public function normalize_course_data( $results ) {
		$return = array();
		if ( $results ) {
			//Add course progress
			foreach ( $results as $r ) {
				$user_id    = $r->user_id;
				$meta_key   = $r->meta_key;
				$meta_value = maybe_unserialize( $r->meta_value );

				if ( '_sfwd-course_progress' !== (string) $meta_key ) {
					continue;
				}
				$course_progress = $meta_value;
				if ( $course_progress ) {
					foreach ( $course_progress as $course_id => $progress ) {
						if ( (int) $progress['completed'] === (int) $progress['total'] ) {
							$return[ $user_id ][ $course_id ]             = array();
							$return[ $user_id ][ $course_id ]['progress'] = maybe_serialize( $progress );
						}
					}
				}
			}

			//Add course access from
			foreach ( $results as $r ) {
				$user_id    = $r->user_id;
				$meta_key   = $r->meta_key;
				$meta_value = maybe_unserialize( $r->meta_value );
				if ( strpos( $meta_key, '_access_from' ) ) {
					$course_id = (int) str_replace( 'course_', '', str_replace( '_access_from', '', $meta_key ) );
					if ( isset( $return[ $user_id ][ $course_id ] ) ) {
						$return[ $user_id ][ $course_id ]['access_from'] = $meta_value;
					} else {
						$return[ $user_id ][ $course_id ]['access_from'] = learndash_user_group_enrolled_to_course_from( $user_id, $course_id );
					}
				}
			}

			//Add course completed
			foreach ( $results as $r ) {
				$user_id    = $r->user_id;
				$meta_key   = $r->meta_key;
				$meta_value = maybe_unserialize( $r->meta_value );
				if ( strpos( $meta_key, '_completed_' ) ) {
					$course_id = (int) str_replace( 'course_completed_', '', $meta_key );
					if ( isset( $return[ $user_id ][ $course_id ] ) ) {
						$return[ $user_id ][ $course_id ]['completed'] = $meta_value;
					}
				}
			}

			//Add course certs
			foreach ( $results as $r ) {
				$user_id    = $r->user_id;
				$meta_key   = $r->meta_key;
				$meta_value = maybe_unserialize( $r->meta_value );
				if ( strpos( $meta_key, '-cert-' ) ) {
					$course_id = (int) str_replace( '_uo-course-cert-', '', $meta_key );
					if ( isset( $return[ $user_id ][ $course_id ]['completed'] ) ) {
						$completed = $return[ $user_id ][ $course_id ]['completed'];
						if ( is_array( $meta_value ) ) {
							foreach ( $meta_value as $certs ) {
								if ( key_exists( $completed, $certs ) ) {
									$return[ $user_id ][ $course_id ]['cert'] = $certs[ $completed ];
								}
							}
						}
					}
				}
			}
		}

		return $return;
	}

	/**
	 * @param $schedules
	 *
	 * @return mixed
	 */
	public function ceu_cron_schedules( $schedules ) {

		if ( ! isset( $schedules['per_minute'] ) ) {
			$schedules['per_minute'] = array(
				'interval' => 60,
				'display'  => __( 'Every one minute', 'uncanny-ceu' ),
			);
		}

		if ( ! isset( $schedules['every_two_minutes'] ) ) {
			$schedules['every_two_minutes'] = array(
				'interval' => 120,
				'display'  => __( 'Every two minutes', 'uncanny-ceu' ),
			);
		}

		return $schedules;
	}
}
