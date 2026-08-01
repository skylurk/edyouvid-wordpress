<?php

namespace uncanny_learndash_groups;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Orphaned Seat Code Migration
 *
 * On the first admin page load after the plugin is updated, runs a single
 * SQL UPDATE that resets every wp_ulgm_group_codes row whose student_id
 * references a WordPress user that no longer exists in wp_users.
 *
 * The entire operation is handled by MySQL in one query — no PHP batching
 * or looping is required. After it runs once, a WP option is set to 'done'
 * and the hook never fires again.
 *
 * Option: ulgm_orphan_migration_v1
 *   – not set / false : not yet run
 *   – 'done'          : complete; will never run again
 */
class Orphan_Seat_Migration {

	const OPTION_KEY = 'ulgm_orphan_migration_v1';

	public function __construct() {
		add_action( 'admin_init', array( $this, 'maybe_run' ) );
	}

	/**
	 * Fires on admin_init.
	 * Runs the cleanup once if it has not been done yet.
	 */
	public function maybe_run(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( 'done' === get_option( self::OPTION_KEY ) ) {
			return;
		}

		$affected = Group_Management_Helpers::purge_orphaned_codes();

		if ( false === $affected ) {
			return; // DB error — will retry on next load.
		}

		update_option( self::OPTION_KEY, 'done', false );
	}

	/**
	 * Reset the migration state so it runs again on the next admin page load.
	 * Useful after a bulk import that may have introduced new orphaned rows,
	 * or during testing.
	 */
	public static function reset(): void {
		delete_option( self::OPTION_KEY );
	}
}
