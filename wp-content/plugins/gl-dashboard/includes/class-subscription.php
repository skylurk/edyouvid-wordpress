<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GLD_Subscription {

	const TABLE      = 'gld_subscriptions';
	const DB_VERSION = '1.3';

	public static function install(): void {
		global $wpdb;
		$table   = $wpdb->prefix . self::TABLE;
		$charset = $wpdb->get_charset_collate();

		// dbDelta adds new columns safely — existing rows are not lost.
		$sql = "CREATE TABLE IF NOT EXISTS {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			group_id bigint(20) unsigned NOT NULL,
			leader_id bigint(20) unsigned NOT NULL,
			order_id bigint(20) unsigned NOT NULL DEFAULT 0,
			product_id bigint(20) unsigned NOT NULL DEFAULT 0,
			bundle_name varchar(255) NOT NULL DEFAULT '',
			course_ids text NOT NULL,
			start_date date NOT NULL,
			expiry_date date NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			notifications_sent varchar(100) NOT NULL DEFAULT '',
			per_seat_price decimal(10,2) NOT NULL DEFAULT 0.00,
			credit_balance decimal(10,2) NOT NULL DEFAULT 0.00,
			PRIMARY KEY (id),
			UNIQUE KEY group_id (group_id),
			KEY expiry_date (expiry_date),
			KEY status (status)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// dbDelta doesn't reliably add columns to existing tables — do it explicitly.
		$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$table}", 0 );
		if ( ! in_array( 'bundle_name', $cols, true ) ) {
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN bundle_name varchar(255) NOT NULL DEFAULT '' AFTER notifications_sent" );
		}
		if ( ! in_array( 'course_ids', $cols, true ) ) {
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN course_ids text NOT NULL AFTER bundle_name" );
		}
		if ( ! in_array( 'per_seat_price', $cols, true ) ) {
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN per_seat_price decimal(10,2) NOT NULL DEFAULT 0.00" );
		}
		if ( ! in_array( 'credit_balance', $cols, true ) ) {
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN credit_balance decimal(10,2) NOT NULL DEFAULT 0.00" );
		}

		update_option( 'gld_db_version', self::DB_VERSION );
	}

	public static function get_for_group( int $group_id ): ?object {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE group_id = %d LIMIT 1",
			$group_id
		) ) ?: null;
	}

	/**
	 * Human-facing status: 'none' (no subscription row), 'expired', 'expiring'
	 * (14 days or less remaining), or 'active'.
	 */
	public static function status_label( ?object $sub ): string {
		if ( ! $sub ) {
			return 'none';
		}

		$today     = new DateTime( date( 'Y-m-d' ) );
		$expiry    = new DateTime( $sub->expiry_date );
		$diff      = $today->diff( $expiry );
		$days_left = ( $expiry >= $today ) ? (int) $diff->days : -(int) $diff->days;

		if ( $sub->status === 'expired' || $days_left < 0 ) {
			return 'expired';
		}
		if ( $days_left <= 14 ) {
			return 'expiring';
		}
		return 'active';
	}

	public static function get_all_active(): array {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results( "SELECT * FROM {$table} WHERE status = 'active'" ) ?: array();
	}

	public static function upsert(
		int $group_id,
		int $leader_id,
		int $order_id,
		int $product_id,
		string $bundle_name,
		array $course_ids,
		string $start,
		string $expiry,
		float $per_seat_price = 0.0
	): void {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		$existing_id = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE group_id = %d LIMIT 1",
			$group_id
		) );

		$data   = array(
			'leader_id'          => $leader_id,
			'order_id'           => $order_id,
			'product_id'         => $product_id,
			'bundle_name'        => $bundle_name,
			'course_ids'         => wp_json_encode( $course_ids ),
			'start_date'         => $start,
			'expiry_date'        => $expiry,
			'status'             => 'active',
			'notifications_sent' => '',
			'per_seat_price'     => $per_seat_price,
		);
		$format = array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%f' );

		if ( $existing_id ) {
			$wpdb->update( $table, $data, array( 'id' => $existing_id ), $format, array( '%d' ) );
		} else {
			$wpdb->insert(
				$table,
				array_merge( array( 'group_id' => $group_id ), $data ),
				array_merge( array( '%d' ), $format )
			);
		}
	}

	public static function set_status( int $sub_id, string $status ): void {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		$wpdb->update( $table, array( 'status' => $status ), array( 'id' => $sub_id ), array( '%s' ), array( '%d' ) );
	}

	public static function mark_notified( int $sub_id, string $flag ): void {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		$row   = $wpdb->get_row( $wpdb->prepare(
			"SELECT notifications_sent FROM {$table} WHERE id = %d",
			$sub_id
		) );
		if ( ! $row ) {
			return;
		}
		$flags = array_filter( explode( ',', $row->notifications_sent ) );
		if ( ! in_array( $flag, $flags, true ) ) {
			$flags[] = $flag;
		}
		$wpdb->update( $table, array( 'notifications_sent' => implode( ',', $flags ) ), array( 'id' => $sub_id ), array( '%s' ), array( '%d' ) );
	}

	// Snapshot current group members into post meta, then remove them from the group.
	public static function revoke_group_access( int $group_id ): void {
		$user_ids = learndash_get_groups_user_ids( $group_id );
		update_post_meta( $group_id, '_gld_suspended_members', $user_ids );
		update_post_meta( $group_id, '_gld_access_suspended', 1 );
		foreach ( $user_ids as $uid ) {
			ld_update_group_access( (int) $uid, $group_id, true );
		}
	}

	// Re-add previously suspended members and clear the suspension flag.
	public static function grant_group_access( int $group_id ): void {
		$suspended = get_post_meta( $group_id, '_gld_suspended_members', true );
		if ( is_array( $suspended ) && ! empty( $suspended ) ) {
			foreach ( $suspended as $uid ) {
				ld_update_group_access( (int) $uid, $group_id, false );
			}
			delete_post_meta( $group_id, '_gld_suspended_members' );
		}
		delete_post_meta( $group_id, '_gld_access_suspended' );
	}
}
