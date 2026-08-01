<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GLD_Seat_Charges_DB {

	const TABLE      = 'gld_seat_charges';
	const DB_VERSION = '1.0';

	public static function install(): void {
		global $wpdb;
		$table   = $wpdb->prefix . self::TABLE;
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			group_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			leader_id bigint(20) unsigned NOT NULL,
			amount decimal(10,2) NOT NULL DEFAULT 0.00,
			currency varchar(10) NOT NULL DEFAULT 'KES',
			period_start date NOT NULL,
			period_end date NOT NULL,
			flw_txn_ref varchar(100) NOT NULL DEFAULT '',
			flw_txn_id varchar(100) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'completed',
			charged_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY group_id (group_id),
			KEY user_id (user_id),
			KEY leader_id (leader_id),
			KEY status (status)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public static function insert( array $data ): int {
		global $wpdb;
		$wpdb->insert( $wpdb->prefix . self::TABLE, $data );
		return (int) $wpdb->insert_id;
	}

	public static function get_for_group( int $group_id, int $limit = 50, int $offset = 0 ): array {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT sc.*, u.display_name AS user_name, u.user_email
			 FROM {$table} sc
			 LEFT JOIN {$wpdb->users} u ON u.ID = sc.user_id
			 WHERE sc.group_id = %d
			 ORDER BY sc.charged_at DESC
			 LIMIT %d OFFSET %d",
			$group_id, $limit, $offset
		) ) ?: array();
	}

	public static function get_for_user_in_group( int $user_id, int $group_id ): ?object {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table}
			 WHERE user_id = %d AND group_id = %d AND status = 'completed'
			 ORDER BY charged_at DESC LIMIT 1",
			$user_id, $group_id
		) ) ?: null;
	}

	public static function count_for_group( int $group_id ): int {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE group_id = %d",
			$group_id
		) );
	}
}
