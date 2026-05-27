<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GLD_Api_Progress {

	const PER_PAGE = 50;
	const TTL      = 600; // 10 minutes — outlasts the 5-min cron cycle.

	public function register_routes(): void {
		register_rest_route( 'gl-dashboard/v1', '/groups/(?P<id>\d+)/progress', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_progress' ),
			'permission_callback' => array( 'GLD_Access', 'rest_permission' ),
			'args'                => array(
				'id'   => array( 'validate_callback' => function( $v ) { return is_numeric( $v ); } ),
				'page' => array(
					'default'           => 1,
					'sanitize_callback' => 'absint',
				),
			),
		) );
	}

	// ── REST handler ────────────────────────────────────────────────────────

	public function get_progress( WP_REST_Request $request ): WP_REST_Response {
		$group_id = (int) $request['id'];
		$page     = max( 1, (int) $request['page'] );

		if ( ! GLD_Access::leads_group( $group_id ) ) {
			return new WP_REST_Response( array( 'message' => 'Forbidden' ), 403 );
		}

		$cache_key = self::page_cache_key( $group_id, $page );
		$cached    = get_transient( $cache_key );
		if ( $cached !== false ) {
			return new WP_REST_Response( $cached, 200 );
		}

		$data = $this->build_page_data( $group_id, $page );
		set_transient( $cache_key, $data, self::TTL );

		return new WP_REST_Response( $data, 200 );
	}

	// ── Cache warmer (called by cron) ────────────────────────────────────────
	// Builds and stores every page for a group. Skips pages that are already warm.

	public static function warm_group( int $group_id ): void {
		$instance    = new self();
		$user_ids    = learndash_get_groups_user_ids( $group_id );
		$total       = count( $user_ids );
		$total_pages = max( 1, (int) ceil( $total / self::PER_PAGE ) );

		// Store total so cache-busting knows how many page keys to delete.
		set_transient( 'gld_progress_' . $group_id . '_total', $total, self::TTL );

		for ( $p = 1; $p <= $total_pages; $p++ ) {
			$key = self::page_cache_key( $group_id, $p );
			if ( get_transient( $key ) !== false ) {
				continue; // already warm, skip
			}
			$data = $instance->build_page_data( $group_id, $p );
			set_transient( $key, $data, self::TTL );
		}
	}

	// ── Cache key helpers ────────────────────────────────────────────────────

	public static function page_cache_key( int $group_id, int $page ): string {
		return 'gld_progress_' . $group_id . '_p' . $page;
	}

	public static function total_cache_key( int $group_id ): string {
		return 'gld_progress_' . $group_id . '_total';
	}

	// ── Core data builder ────────────────────────────────────────────────────
	// Single source of truth used by both the REST handler and the cron warmer.

	private function build_page_data( int $group_id, int $page ): array {
		$all_user_ids = learndash_get_groups_user_ids( $group_id );
		$total_users  = count( $all_user_ids );
		$total_pages  = max( 1, (int) ceil( $total_users / self::PER_PAGE ) );

		set_transient( self::total_cache_key( $group_id ), $total_users, self::TTL );

		$user_ids   = array_slice( $all_user_ids, ( $page - 1 ) * self::PER_PAGE, self::PER_PAGE );
		$course_ids = learndash_group_enrolled_courses( $group_id );

		// Course metadata is small — returned on every page so the client stays simple.
		$courses = array();
		foreach ( $course_ids as $course_id ) {
			$courses[ $course_id ] = array(
				'id'          => $course_id,
				'title'       => get_the_title( $course_id ),
				'total_steps' => $this->get_course_total_steps( $course_id ),
			);
		}

		$all_progress    = $this->bulk_load_progress( $user_ids );
		$all_last_active = $this->bulk_load_last_active( $user_ids );

		$users = array();
		foreach ( $user_ids as $user_id ) {
			$user = get_userdata( $user_id );
			if ( ! $user ) {
				continue;
			}

			$raw_progress    = $all_progress[ $user_id ] ?? array();
			$course_progress = array();
			foreach ( $course_ids as $course_id ) {
				$course_progress[ $course_id ] = $this->format_course_progress(
					$raw_progress[ $course_id ] ?? array(),
					$courses[ $course_id ]['total_steps']
				);
			}

			$users[] = array(
				'id'          => $user_id,
				'name'        => $user->display_name,
				'email'       => $user->user_email,
				'avatar'      => $this->get_cached_avatar_url( $user_id ),
				'last_active' => $all_last_active[ $user_id ] ?? null,
				'courses'     => $course_progress,
			);
		}

		return array(
			'group'       => array( 'id' => $group_id, 'name' => get_the_title( $group_id ) ),
			'courses'     => array_values( $courses ),
			'users'       => $users,
			'total_users' => $total_users,
			'total_pages' => $total_pages,
			'page'        => $page,
		);
	}

	// ── Private helpers ──────────────────────────────────────────────────────

	private function format_course_progress( array $p, int $total_steps ): array {
		if ( empty( $p ) ) {
			return array( 'completed_steps' => 0, 'total_steps' => $total_steps, 'percent' => 0, 'status' => 'not_started' );
		}

		$done = count( array_filter( $p['lessons'] ?? array() ) )
		      + count( array_filter( $p['topics'] ?? array() ) );

		$steps   = $total_steps > 0 ? $total_steps : max( 1, (int) ( $p['total'] ?? 1 ) );
		$percent = min( 100, (int) round( ( $done / $steps ) * 100 ) );

		if ( ! empty( $p['completed'] ) ) {
			$status  = 'completed';
			$percent = 100;
		} elseif ( $done > 0 ) {
			$status = 'in_progress';
		} else {
			$status = 'not_started';
		}

		return array(
			'completed_steps' => $done,
			'total_steps'     => $steps,
			'percent'         => $percent,
			'status'          => $status,
		);
	}

	private function get_course_total_steps( int $course_id ): int {
		$steps = learndash_get_course_steps( $course_id );
		return is_array( $steps ) ? count( $steps ) : 0;
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

	private function bulk_load_last_active( array $user_ids ): array {
		if ( empty( $user_ids ) ) {
			return array();
		}
		global $wpdb;
		$table        = LDLMS_DB::get_table_name( 'user_activity' );
		$placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
		$rows         = $wpdb->get_results( $wpdb->prepare(
			"SELECT user_id, MAX(activity_updated) AS last_active
			 FROM {$table}
			 WHERE user_id IN ({$placeholders})
			 GROUP BY user_id",
			$user_ids
		) );
		$map = array();
		foreach ( $rows as $row ) {
			$map[ (int) $row->user_id ] = date( 'Y-m-d', (int) $row->last_active );
		}
		return $map;
	}

	private function get_cached_avatar_url( int $user_id ): string {
		$cache_key = 'gld_avatar_' . $user_id;
		$cached    = get_transient( $cache_key );
		if ( $cached !== false ) {
			return $cached;
		}
		$url = get_avatar_url( $user_id, array( 'size' => 40 ) );
		set_transient( $cache_key, $url, DAY_IN_SECONDS );
		return $url;
	}
}
