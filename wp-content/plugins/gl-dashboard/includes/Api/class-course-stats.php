<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GLD_Api_CourseStats {

	public function register_routes(): void {
		register_rest_route( 'gl-dashboard/v1', '/groups/(?P<id>\d+)/course-stats', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_course_stats' ),
			'permission_callback' => array( 'GLD_Access', 'rest_permission' ),
			'args'                => array(
				'id' => array( 'validate_callback' => function( $v ) { return is_numeric( $v ); } ),
			),
		) );
	}

	public function get_course_stats( WP_REST_Request $request ): WP_REST_Response {
		$group_id = (int) $request['id'];

		if ( ! GLD_Access::leads_group( $group_id ) ) {
			return new WP_REST_Response( array( 'message' => 'Forbidden' ), 403 );
		}

		$user_ids   = learndash_get_groups_user_ids( $group_id );
		$course_ids = array_diff( learndash_group_enrolled_courses( $group_id ), GLD_Course_Visibility::get_hidden_ids_for_group( $group_id ) );

		if ( empty( $course_ids ) ) {
			return new WP_REST_Response( array(), 200 );
		}

		$all_progress = $this->bulk_load_progress( $user_ids );
		$all_quizzes  = $this->bulk_load_quizzes( $user_ids );

		$total_members  = count( $user_ids );
		$quiz_by_course = $this->index_quizzes_by_course( $all_quizzes );
		$stats          = array();

		foreach ( $course_ids as $course_id ) {
			$completed   = 0;
			$in_progress = 0;
			$not_started = 0;
			$total_pct   = 0;

			foreach ( $user_ids as $user_id ) {
				$progress = $all_progress[ $user_id ][ $course_id ] ?? null;

				if ( ! $progress ) {
					$not_started++;
					continue;
				}

				$done  = count( array_filter( $progress['lessons'] ?? array() ) )
				       + count( array_filter( $progress['topics'] ?? array() ) );
				$total = max( 1, (int) ( $progress['total'] ?? 1 ) );
				$pct   = min( 100, round( ( $done / $total ) * 100 ) );
				$total_pct += $pct;

				if ( ! empty( $progress['completed'] ) ) {
					$completed++;
				} elseif ( $done > 0 ) {
					$in_progress++;
				} else {
					$not_started++;
				}
			}

			$started        = $completed + $in_progress;
			$avg_completion = $total_members > 0 ? round( $total_pct / $total_members, 1 ) : 0;
			$avg_quiz       = $this->avg_quiz_for_course( $quiz_by_course, $course_id );

			$stats[] = array(
				'id'             => $course_id,
				'title'          => get_the_title( $course_id ),
				'total_members'  => $total_members,
				'started'        => $started,
				'in_progress'    => $in_progress,
				'completed'      => $completed,
				'not_started'    => $not_started,
				'avg_completion' => $avg_completion,
				'avg_quiz_score' => $avg_quiz,
			);
		}

		return new WP_REST_Response( $stats, 200 );
	}

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

	// Flatten all quiz attempts into a map: course_id => [score, score, ...]
	private function index_quizzes_by_course( array $all_quizzes ): array {
		$map = array();
		foreach ( $all_quizzes as $quiz_meta ) {
			foreach ( $quiz_meta as $attempt ) {
				if ( empty( $attempt['course'] ) || ! isset( $attempt['percentage'] ) ) {
					continue;
				}
				$cid          = (int) $attempt['course'];
				$map[ $cid ][] = (float) $attempt['percentage'];
			}
		}
		return $map;
	}

	private function avg_quiz_for_course( array $quiz_by_course, int $course_id ): float {
		if ( empty( $quiz_by_course[ $course_id ] ) ) {
			return 0.0;
		}
		$scores = $quiz_by_course[ $course_id ];
		return round( array_sum( $scores ) / count( $scores ), 1 );
	}
}
