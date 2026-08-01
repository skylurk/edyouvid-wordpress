<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GLD_Course_Visibility {

	public static function register(): void {
		add_filter( 'learndash_ld_course_list_query_args', array( __CLASS__, 'filter_course_list_query' ), 10, 1 );
		add_action( 'pre_get_posts', array( __CLASS__, 'filter_course_archive' ) );
	}

	// ── Per-group hidden course list ─────────────────────────────────────────

	public static function get_hidden_ids_for_group( int $group_id ): array {
		$ids = get_post_meta( $group_id, '_gld_hidden_courses', true );
		return is_array( $ids ) ? array_map( 'intval', $ids ) : array();
	}

	public static function set_hidden_ids_for_group( int $group_id, array $course_ids ): void {
		update_post_meta( $group_id, '_gld_hidden_courses', array_values( array_unique( array_map( 'intval', $course_ids ) ) ) );
	}

	// ── Per-user hidden course list (grandfathers pre-existing members) ──────

	public static function get_hidden_ids_for_user( int $user_id ): array {
		if ( ! $user_id ) {
			return array();
		}

		$hidden = array();
		foreach ( learndash_get_users_group_ids( $user_id ) as $group_id ) {
			if ( self::is_legacy_member( (int) $group_id, $user_id ) ) {
				continue;
			}
			$hidden = array_merge( $hidden, self::get_hidden_ids_for_group( (int) $group_id ) );
		}
		return array_values( array_unique( $hidden ) );
	}

	private static function is_legacy_member( int $group_id, int $user_id ): bool {
		$legacy = get_post_meta( $group_id, '_gld_legacy_members', true );
		return is_array( $legacy ) && in_array( $user_id, array_map( 'intval', $legacy ), true );
	}

	// ── One-time migration: grandfather everyone already in a group ──────────

	public static function snapshot_legacy_members(): void {
		$groups = get_posts( array(
			'post_type'      => 'groups',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		) );

		foreach ( $groups as $group_id ) {
			if ( get_post_meta( $group_id, '_gld_legacy_members', true ) !== '' ) {
				continue; // already snapshotted
			}
			$member_ids = learndash_get_groups_user_ids( $group_id );
			update_post_meta( $group_id, '_gld_legacy_members', array_map( 'intval', $member_ids ) );
		}
	}

	// ── Catalog filtering hooks ───────────────────────────────────────────────

	public static function filter_course_list_query( array $filter ): array {
		$hidden = self::get_hidden_ids_for_user( get_current_user_id() );
		if ( empty( $hidden ) ) {
			return $filter;
		}
		$existing = isset( $filter['post__not_in'] ) && is_array( $filter['post__not_in'] ) ? $filter['post__not_in'] : array();
		$filter['post__not_in'] = array_values( array_unique( array_merge( $existing, $hidden ) ) );
		return $filter;
	}

	public static function filter_course_archive( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( 'sfwd-courses' ) ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			return;
		}

		$hidden = self::get_hidden_ids_for_user( get_current_user_id() );
		if ( empty( $hidden ) ) {
			return;
		}

		$existing = (array) $query->get( 'post__not_in' );
		$query->set( 'post__not_in', array_values( array_unique( array_merge( $existing, $hidden ) ) ) );
	}
}
