<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GLD_Access {

	/**
	 * Returns true if the current user is a group leader or site admin.
	 */
	public static function is_leader(): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$user_id = get_current_user_id();
		return learndash_is_group_leader_user( $user_id ) || learndash_is_admin_user( $user_id );
	}

	/**
	 * Returns true if the current user leads (or administers) the given group.
	 */
	public static function leads_group( int $group_id ): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$user_id   = get_current_user_id();
		$group_ids = learndash_get_administrators_group_ids( $user_id );
		return in_array( $group_id, array_map( 'intval', $group_ids ), true );
	}

	/**
	 * Returns all group IDs the current user leads.
	 */
	public static function get_leader_group_ids(): array {
		if ( ! is_user_logged_in() ) {
			return array();
		}
		return array_map( 'intval', learndash_get_administrators_group_ids( get_current_user_id() ) );
	}

	/**
	 * WP REST permission callback: must be a group leader or admin.
	 */
	public static function rest_permission(): bool {
		return self::is_leader();
	}
}
