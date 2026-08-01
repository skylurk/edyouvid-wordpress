<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GLD_Api_Analytics {

	public function register_routes(): void {
		register_rest_route( 'gl-dashboard/v1', '/groups/(?P<id>\d+)/analytics', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_analytics' ),
			'permission_callback' => array( 'GLD_Access', 'rest_permission' ),
			'args'                => array(
				// Wrap is_numeric in a closure — PHP 8 is_numeric() only accepts 1 arg
				// but WP REST passes 3 ($value, $request, $key).
				'id' => array( 'validate_callback' => function( $v ) { return is_numeric( $v ); } ),
			),
		) );
	}

	public function get_analytics( WP_REST_Request $request ): WP_REST_Response {
		$group_id = (int) $request['id'];

		if ( ! GLD_Access::leads_group( $group_id ) ) {
			return new WP_REST_Response( array( 'message' => 'Forbidden' ), 403 );
		}

		$user_ids   = learndash_get_groups_user_ids( $group_id );
		$course_ids = array_diff( learndash_group_enrolled_courses( $group_id ), GLD_Course_Visibility::get_hidden_ids_for_group( $group_id ) );

		// Single bulk query for all user progress — replaces N get_user_meta() calls.
		$all_progress = $this->bulk_load_progress( $user_ids );
		$all_quizzes  = $this->bulk_load_quizzes( $user_ids );

		return new WP_REST_Response( array(
			'summary'             => $this->get_summary( $user_ids, $course_ids, $all_progress ),
			'completions_by_week' => $this->get_completions_by_week( $user_ids ),
			'per_course'          => $this->get_per_course_stats( $user_ids, $course_ids, $all_progress ),
			'quiz_scores'         => $this->get_quiz_scores( $course_ids, $all_quizzes ),
		), 200 );
	}

	/**
	 * Load _sfwd-course_progress for all users in one SQL query.
	 * Returns array keyed by user_id.
	 */
	private function bulk_load_progress( array $user_ids ): array {
		if ( empty( $user_ids ) ) {
			return array();
		}
		global $wpdb;
		$placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT user_id, meta_value FROM {$wpdb->usermeta}
			 WHERE meta_key = '_sfwd-course_progress' AND user_id IN ({$placeholders})",
			$user_ids
		) );
		$map = array();
		foreach ( $rows as $row ) {
			$val = maybe_unserialize( $row->meta_value );
			$map[ (int) $row->user_id ] = is_array( $val ) ? $val : array();
		}
		return $map;
	}

	/**
	 * Load _sfwd-quizzes for all users in one SQL query.
	 * Returns array keyed by user_id.
	 */
	private function bulk_load_quizzes( array $user_ids ): array {
		if ( empty( $user_ids ) ) {
			return array();
		}
		global $wpdb;
		$placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT user_id, meta_value FROM {$wpdb->usermeta}
			 WHERE meta_key = '_sfwd-quizzes' AND user_id IN ({$placeholders})",
			$user_ids
		) );
		$map = array();
		foreach ( $rows as $row ) {
			$val = maybe_unserialize( $row->meta_value );
			$map[ (int) $row->user_id ] = is_array( $val ) ? $val : array();
		}
		return $map;
	}

	private function get_summary( array $user_ids, array $course_ids, array $all_progress ): array {
		if ( empty( $user_ids ) ) {
			return array( 'total_users' => 0, 'avg_completion' => 0, 'total_completions' => 0, 'active_this_week' => 0 );
		}

		$total_percent = 0;
		$count         = 0;
		$completions   = 0;

		foreach ( $user_ids as $user_id ) {
			$progress = $all_progress[ $user_id ] ?? array();
			foreach ( $course_ids as $course_id ) {
				$count++;
				if ( ! isset( $progress[ $course_id ] ) ) {
					continue;
				}
				$p    = $progress[ $course_id ];
				$done = count( array_filter( $p['lessons'] ?? array() ) )
				      + count( array_filter( $p['topics'] ?? array() ) );
				$total = max( 1, (int) ( $p['total'] ?? 1 ) );
				$total_percent += min( 100, round( ( $done / $total ) * 100 ) );
				if ( ! empty( $p['completed'] ) ) {
					$completions++;
				}
			}
		}

		global $wpdb;
		$table        = LDLMS_DB::get_table_name( 'user_activity' );
		$placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
		$active_count = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT user_id) FROM {$table}
			 WHERE user_id IN ({$placeholders}) AND activity_updated > %d",
			array_merge( $user_ids, array( strtotime( '-7 days' ) ) )
		) );

		return array(
			'total_users'       => count( $user_ids ),
			'avg_completion'    => $count > 0 ? round( $total_percent / $count, 1 ) : 0,
			'total_completions' => $completions,
			'active_this_week'  => $active_count,
		);
	}

	private function get_completions_by_week( array $user_ids ): array {
		if ( empty( $user_ids ) ) {
			return array();
		}
		global $wpdb;
		$table        = LDLMS_DB::get_table_name( 'user_activity' );
		$placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
		$twelve_weeks = strtotime( '-84 days' );

		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT activity_completed as ts
			 FROM {$table}
			 WHERE user_id IN ({$placeholders})
			   AND activity_type = 'course'
			   AND activity_completed > %d
			 ORDER BY activity_completed ASC",
			array_merge( $user_ids, array( $twelve_weeks ) )
		) );

		$weeks = array();
		foreach ( $rows as $row ) {
			$label           = date( 'M d', (int) $row->ts );
			$weeks[ $label ] = ( $weeks[ $label ] ?? 0 ) + 1;
		}
		$result = array();
		foreach ( $weeks as $label => $n ) {
			$result[] = array( 'week' => $label, 'completions' => $n );
		}
		return $result;
	}

	private function get_per_course_stats( array $user_ids, array $course_ids, array $all_progress ): array {
		$stats = array();
		foreach ( $course_ids as $course_id ) {
			$enrolled = $completed = $in_progress = $total_pct = 0;
			foreach ( $user_ids as $user_id ) {
				$progress = $all_progress[ $user_id ] ?? array();
				if ( ! isset( $progress[ $course_id ] ) ) {
					continue;
				}
				$enrolled++;
				$p    = $progress[ $course_id ];
				$done = count( array_filter( $p['lessons'] ?? array() ) )
				      + count( array_filter( $p['topics'] ?? array() ) );
				$total    = max( 1, (int) ( $p['total'] ?? 1 ) );
				$pct      = min( 100, round( ( $done / $total ) * 100 ) );
				$total_pct += $pct;
				if ( ! empty( $p['completed'] ) ) {
					$completed++;
				} elseif ( $done > 0 ) {
					$in_progress++;
				}
			}
			$stats[] = array(
				'id'             => $course_id,
				'title'          => get_the_title( $course_id ),
				'enrolled'       => $enrolled,
				'completed'      => $completed,
				'in_progress'    => $in_progress,
				'not_started'    => max( 0, $enrolled - $completed - $in_progress ),
				'avg_completion' => $enrolled > 0 ? round( $total_pct / $enrolled, 1 ) : 0,
			);
		}
		return $stats;
	}

	private function get_quiz_scores( array $course_ids, array $all_quizzes ): array {
		$scores = array();
		foreach ( $all_quizzes as $quiz_meta ) {
			foreach ( $quiz_meta as $attempt ) {
				if ( empty( $attempt['course'] ) || ! in_array( (int) $attempt['course'], $course_ids, true ) ) {
					continue;
				}
				if ( isset( $attempt['percentage'] ) ) {
					$scores[] = (float) $attempt['percentage'];
				}
			}
		}

		if ( empty( $scores ) ) {
			return array( 'avg' => 0, 'distribution' => array() );
		}

		$buckets = array_fill( 0, 10, 0 );
		foreach ( $scores as $s ) {
			$buckets[ min( 9, (int) floor( $s / 10 ) ) ]++;
		}
		$distribution = array();
		foreach ( $buckets as $i => $n ) {
			$low            = $i * 10;
			$high           = $i === 9 ? 100 : $low + 9;
			$distribution[] = array( 'range' => "{$low}-{$high}%", 'count' => $n );
		}
		return array(
			'avg'          => round( array_sum( $scores ) / count( $scores ), 1 ),
			'distribution' => $distribution,
		);
	}
}
