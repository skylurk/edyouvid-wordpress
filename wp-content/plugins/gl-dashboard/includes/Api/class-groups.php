<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GLD_Api_Groups {

	public function register_routes(): void {
		register_rest_route( 'gl-dashboard/v1', '/groups', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_groups' ),
			'permission_callback' => array( 'GLD_Access', 'rest_permission' ),
		) );
	}

	public function get_groups( WP_REST_Request $request ): WP_REST_Response {
		$group_ids = GLD_Access::get_leader_group_ids();

		if ( empty( $group_ids ) ) {
			return new WP_REST_Response( array(), 200 );
		}

		$groups = array();

		foreach ( $group_ids as $group_id ) {
			$post = get_post( $group_id );
			if ( ! $post || ! in_array( $post->post_type, array( 'groups', 'sfwd-groups' ), true ) ) {
				continue;
			}

			$user_ids   = learndash_get_groups_user_ids( $group_id );
			$course_ids = learndash_group_enrolled_courses( $group_id );

			$groups[] = array(
				'id'           => $group_id,
				'name'         => $post->post_title,
				'user_count'   => count( $user_ids ),
				'course_count' => count( $course_ids ),
				'avg_completion' => $this->calc_avg_completion( $user_ids, $course_ids ),
			);
		}

		return new WP_REST_Response( $groups, 200 );
	}

	private function calc_avg_completion( array $user_ids, array $course_ids ): float {
		if ( empty( $user_ids ) || empty( $course_ids ) ) {
			return 0.0;
		}

		$total   = 0;
		$count   = 0;

		foreach ( $user_ids as $user_id ) {
			$progress = get_user_meta( $user_id, '_sfwd-course_progress', true );
			if ( ! is_array( $progress ) ) {
				$progress = array();
			}

			foreach ( $course_ids as $course_id ) {
				$count++;
				if ( isset( $progress[ $course_id ] ) ) {
					$p     = $progress[ $course_id ];
					$steps = max( 1, (int) ( $p['total'] ?? 1 ) );
					$done  = (int) count( array_filter( $p['lessons'] ?? array() ) );
					$done += (int) count( array_filter( $p['topics'] ?? array() ) );
					$total += min( 100, round( ( $done / $steps ) * 100 ) );
				}
			}
		}

		return $count > 0 ? round( $total / $count, 1 ) : 0.0;
	}
}
