<?php

namespace uncanny_learndash_codes;

use DateTime;

/**
 * Class Database
 *
 * @package uncanny_learndash_codes
 */
class Database extends Config {

	/**
	 * Database constructor.
	 */
	public function __construct() {
		parent::__construct();
		add_action(
			'uncanny_codes_migrate_users',
			array(
				__CLASS__,
				'migrate_user_ids',
			)
		);
	}

	/**
	 *
	 */
	public static function reset() {
		global $wpdb;
		$wpdb->query( "DROP TABLE {$wpdb->prefix}" . Config::$tbl_codes );
		$wpdb->query( "DROP TABLE {$wpdb->prefix}" . Config::$tbl_groups );
		$wpdb->query( "DROP TABLE {$wpdb->prefix}" . Config::$tbl_codes_usage );
	}

	/**
	 *
	 */
	public static function reset_data() {
		global $wpdb;
		$wpdb->query( "TRUNCATE {$wpdb->prefix}" . Config::$tbl_codes );
		$wpdb->query( "TRUNCATE {$wpdb->prefix}" . Config::$tbl_groups );
		$wpdb->query( "TRUNCATE {$wpdb->prefix}" . Config::$tbl_codes_usage );
	}

	/**
	 *
	 */
	public static function fix_unique_code_issues() {
		global $wpdb;
		$tbl_codes = $wpdb->prefix . Config::$tbl_codes;
		$results   = $wpdb->get_results( "SELECT `code`, COUNT(`code`) AS `count` FROM {$tbl_codes} GROUP BY `code` HAVING `count` > 1" );
		if ( $results ) {
			// found more than 1 unique code.
			foreach ( $results as $r ) {
				$code = $r->code;
				if ( $r->count > 1 ) {
					$all_codes = $wpdb->get_col( $wpdb->prepare( "SELECT `ID` FROM $tbl_codes WHERE `code` LIKE %s ORDER BY ID ASC", $code ) );
					// remove last element as the highest ID.
					array_pop( $all_codes );
					// remove all other codes.

					if ( ! empty( $all_codes ) && is_array( $all_codes ) ) {
						$wpdb->query( "DELETE FROM $tbl_codes WHERE ID IN (" . join( ',', $all_codes ) . ')' );
					}
				}
			}
		}
		update_option( 'ulc_unique_code_fixes', UNCANNY_LEARNDASH_CODES_DB_VERSION );

		return true;
	}

	/**
	 * @param $group
	 * @param $coupons
	 *
	 * @return int
	 */
	public static function add_code_group_batch( $group ) {
		global $wpdb;

		$now         = current_time( 'mysql' );
		$expiry_date = '0000-00-00 00:00:00';
		$linked_to   = array();
		if ( 'course' === (string) $group['coupon-for'] ) {
			$linked_to = (array) $group['coupon-courses'];
		} elseif ( 'group' === (string) $group['coupon-for'] ) {
			$linked_to = (array) $group['coupon-group'];
		}
		if ( ! empty( $group['expiry-date'] ) ) {
			if ( ! empty( $group['expiry-time'] ) ) {
				$expiry_date = date( 'Y-m-d H:i:s', strtotime( $group['expiry-date'] . ' ' . $group['expiry-time'] ) );
			} else {
				$expiry_date = date( 'Y-m-d 23:59:59', strtotime( $group['expiry-date'] ) );
			}
		}
		if ( ! isset( $group['coupon-character-type'] ) || empty( $group['coupon-character-type'] ) ) {
			$character_types = array( 'uppercase-letters', 'numbers' );
		} else {
			$character_types = $group['coupon-character-type'];
		}
		$insert_array  = array(
			'name'            => $group['group-name'],
			'code_for'        => $group['coupon-for'],
			'paid_unpaid'     => $group['coupon-paid-unpaid'],
			'prefix'          => $group['coupon-prefix'],
			'suffix'          => $group['coupon-suffix'],
			'issue_date'      => $now,
			'linked_to'       => serialize( $linked_to ),
			'character_type'  => serialize( $character_types ),
			'issue_count'     => intval( $group['coupon-amount'] ),
			'issue_max_count' => intval( $group['coupon-max-usage'] ),
			'dash'            => $group['coupon-dash'],
			'expire_date'     => $expiry_date,
		);
		$insert_sanity = array(
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%d',
			'%d',
			'%s',
			'%s',
		);

		$wpdb->insert(
			$wpdb->prefix . Config::$tbl_groups,
			$insert_array,
			$insert_sanity
		);

		return $wpdb->insert_id;
	}

	/**
	 * @return false|int
	 */
	public static function is_codes_table_exists() {
		global $wpdb;
		$qry = "SHOW TABLES LIKE '{$wpdb->prefix}" . Config::$tbl_codes . "';";

		return $wpdb->query( $qry );
	}

	/**
	 * @return false|int
	 */
	public static function is_batch_table_exists() {
		global $wpdb;
		$qry = "SHOW TABLES LIKE '{$wpdb->prefix}" . Config::$tbl_groups . "';";

		return $wpdb->query( $qry );
	}

	/**
	 * @return false|int
	 */
	public static function is_usage_table_exists() {
		global $wpdb;
		$qry = "SHOW TABLES LIKE '{$wpdb->prefix}" . Config::$tbl_codes_usage . "';";

		return $wpdb->query( $qry );
	}

	/**
	 * @param bool $override
	 *
	 * @return bool|void
	 */
	public static function create_tables( $override = false ) {
		$db_version = get_option( 'ulc_database_version', '1.0' );
		if ( ! $override && null !== $db_version && (string) UNCANNY_LEARNDASH_CODES_DB_VERSION === (string) $db_version ) {
			// bail. No db upgrade needed!
			return;
		}

		global $wpdb;

		$tbl_codes       = Config::$tbl_codes;
		$tbl_groups      = Config::$tbl_groups;
		$tbl_usage       = Config::$tbl_codes_usage;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$wpdb->prefix}{$tbl_codes} (
`ID` bigint unsigned NOT NULL auto_increment,
`code_group` bigint unsigned NOT NULL,
`code` varchar(180) NOT NULL,
`used_date` datetime,
`user_id` LONGTEXT,
`order_id` bigint unsigned DEFAULT 0 NOT NULL,
`migrated` tinyint(1) NOT NULL DEFAULT '0',
`is_active` tinyint(1) NOT NULL DEFAULT '1',
`expire_date` datetime,
PRIMARY KEY (ID),
UNIQUE KEY `code` (`code`),
KEY `order_id` (`order_id`),
KEY `code_group` (`code_group`)
) ENGINE=InnoDB {$charset_collate};
CREATE TABLE {$wpdb->prefix}{$tbl_usage} (
`ID` bigint unsigned NOT NULL auto_increment,
`code_id` bigint unsigned NOT NULL,
`user_id` bigint signed NOT NULL,
`date_redeemed` datetime,
PRIMARY KEY (ID),
KEY `code_id` (`code_id`),
KEY `user_id` (`user_id`),
KEY `code_id_date_redeemed` (`code_id`, `date_redeemed`)
) ENGINE=InnoDB {$charset_collate};
CREATE TABLE {$wpdb->prefix}{$tbl_groups} (
`ID` bigint unsigned NOT NULL auto_increment,
`name` varchar(200),
`code_for` varchar(30) DEFAULT '' NOT NULL,
`paid_unpaid` varchar(20) DEFAULT 'default' NOT NULL,
`prefix` varchar(20) DEFAULT '' NOT NULL,
`suffix` varchar(20) DEFAULT '' NOT NULL,
`issue_date` datetime,
`expire_date` datetime,
`linked_to` LONGTEXT,
`character_type` varchar(100) DEFAULT '' NOT NULL,
`issue_count` bigint unsigned NOT NULL,
`issue_max_count` bigint unsigned NOT NULL,
`dash` varchar(30) DEFAULT '' NOT NULL,
`product_id` bigint unsigned NOT NULL DEFAULT '0',
PRIMARY KEY (ID),
KEY `code_for` (code_for(5)),
KEY `name` (name(10)),
KEY `prefix` (prefix(4)),
KEY `suffix` (suffix(4)),
KEY `product_id` (`product_id`)
); ENGINE=InnoDB {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		wp_schedule_single_event( time() + 60, 'uncanny_codes_migrate_users' );
		update_option( 'ulc_database_version', UNCANNY_LEARNDASH_CODES_DB_VERSION );
	}

	// Login Coupon Methods.

	/**
	 *
	 */
	public static function migrate_user_ids() {
		$users_migrated = get_option( 'ulc_users_migrated', 0 );
		if ( 1 === absint( $users_migrated ) ) {
			// bail. No db upgrade needed!
			return;
		}
		global $wpdb;
		$tbl_codes = $wpdb->prefix . Config::$tbl_codes;
		$wpdb->query( $wpdb->prepare( "UPDATE {$tbl_codes} SET migrated=%d WHERE user_id IS NULL", 1 ) );
		$results = $wpdb->get_results( $wpdb->prepare( "SELECT ID, user_id FROM {$tbl_codes} WHERE migrated = %d LIMIT 0, 200", 0 ) );
		if ( $results ) {
			foreach ( $results as $result ) {
				$code_id = $result->ID;
				$users   = maybe_unserialize( $result->user_id );
				if ( $users ) {
					foreach ( $users as $user ) {
						$user_id = $user['user'];
						$date    = date( 'Y-m-d H:i:s', $user['redeemed'] );
						$wpdb->insert(
							$wpdb->prefix . Config::$tbl_codes_usage,
							array(
								'code_id'       => $code_id,
								'user_id'       => $user_id,
								'date_redeemed' => $date,
							),
							array(
								'%d',
								'%d',
								'%s',
							)
						);
						// Added usermeta as a backup to store the User ID usermeta before removing from codes table.
						update_user_meta( $user_id, 'ulc_migration_' . $code_id, $date );
					}
				}
				$wpdb->query( $wpdb->prepare( "UPDATE {$tbl_codes} SET migrated=%d WHERE ID=%d", 1, $code_id ) );
			}
		}
		$results = $wpdb->get_var( $wpdb->prepare( "SELECT count(ID) FROM {$tbl_codes} WHERE migrated = %d", 0 ) );
		if ( $results ) {
			// Migrate more codes on next load.
			wp_schedule_single_event( time() + 60, 'uncanny_codes_migrate_users' );
		} else {
			update_option( 'ulc_users_migrated', 1 );
		}
	}

	/**
	 * @param        $group_id
	 * @param        $codes
	 * @param        $args
	 *
	 * @return string|null
	 */
	public static function add_codes_to_batch( $group_id, $codes, $args ) {
		$method = (string) $args['generation_type'];
		if ( 'manual' === $method ) {
			return self::handle_manual_codes( $group_id, $codes, $args );
		}

		$total     = absint( $args['coupon_amount'] );
		$num_codes = self::number_of_codes_per_batch( $total );
		$inserted  = self::handle_auto_code( $group_id, $args, $num_codes );
		if ( absint( $inserted ) === absint( $total ) ) {
			// requested codes added, return count.
			return $inserted;
		}
	}

	/**
	 * @param       $group_id
	 * @param array $codes
	 * @param array $args
	 *
	 * @return int|mixed|string|null
	 */
	public static function handle_manual_codes( $group_id, $codes = array(), $args = array() ) {
		if ( ! self::is_codes_table_exists() ) {
			self::create_tables( true );
		}
		if ( empty( $codes ) ) {
			return 0;
		}
		$total = count( $codes );
		if ( count( $codes ) > 1000 ) {
			$chunks = array_chunk( $codes, 1000 );
		} else {
			$chunks = array( $codes );
		}
		global $wpdb;
		$tbl_codes = $wpdb->prefix . Config::$tbl_codes;
		if ( empty( $chunks ) ) {
			return 0;
		}

		$chunk_no = 1;
		foreach ( $chunks as $chunk ) {
			$chunk_no ++;
			$insert = self::build_insert_query( $group_id, $chunk );
			$query  = "INSERT IGNORE INTO $tbl_codes (ID, code_group, code) VALUES $insert";
			$wpdb->query( $query );

		}

		$inserted = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM $tbl_codes WHERE code_group=%d", $group_id ) );
		if ( absint( $inserted ) === absint( $total ) ) {
			// requested codes added, return count.
			return $inserted;
		}

		if ( absint( $inserted ) !== absint( $total ) ) {
			// return insert if codes were pasted manually.
			$added = $wpdb->get_col( $wpdb->prepare( "SELECT code FROM $tbl_codes WHERE code_group = %d", $group_id ) );
			$diff  = array_diff( $codes, $added );
			if ( ! empty( GenerateCodes::$rejected_batch_codes ) ) {
				GenerateCodes::$rejected_batch_codes = array_unique( array_merge( (array) $diff, (array) GenerateCodes::$rejected_batch_codes ) );
			} else {
				GenerateCodes::$rejected_batch_codes = array_unique( (array) $diff );
			}
			if ( 0 === $inserted ) {
				// roll back group.
				$tbl_groups = $wpdb->prefix . Config::$tbl_groups;
				$wpdb->query( $wpdb->prepare( "DELETE FROM $tbl_groups WHERE ID = %d", $group_id ) );
			}

			return $inserted;
		}
	}

	/**
	 * @param $group_id
	 * @param $codes
	 *
	 * @return string|void
	 */
	public static function build_insert_query( $group_id, $codes ) {
		if ( empty( $codes ) ) {
			return;
		}
		$raw = array();
		foreach ( $codes as $code ) {
			$raw[] = "(NULL, $group_id, '$code')";
		}

		return join( ',', $raw );
	}

	/**
	 * @param $number
	 *
	 * @return array
	 */
	private static function number_of_codes_per_batch( $number ) {
		$ar = array();
		if ( $number <= 1000 ) {
			$ar[] = $number;
		} else {
			while ( $number > 1000 ) {
				$ar[]   = 1000;
				$number = $number - 1000;
			}
			$ar[] = $number;
		}

		return $ar;
	}

	/**
	 * @param $group_id
	 * @param $args
	 * @param $num_codes
	 *
	 * @return string|null
	 */
	public static function handle_auto_code( $group_id, $args, $num_codes ) {
		$total = absint( $args['coupon_amount'] );
		global $wpdb;
		$tbl_codes = $wpdb->prefix . Config::$tbl_codes;
		if ( $num_codes ) {
			foreach ( $num_codes as $k => $to_generate ) {
				$codes  = GenerateCodes::get_unique_codes( $args, $to_generate );
				$insert = self::build_insert_query( $group_id, $codes );
				$query  = "INSERT IGNORE INTO $tbl_codes (`ID`, `code_group`, `code`) VALUES $insert";
				$wpdb->query( $query );
			}
		}

		$inserted = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM $tbl_codes WHERE code_group=%d", $group_id ) );
		if ( absint( $inserted ) === absint( $total ) ) {
			// requested codes added, return count.
			return $inserted;
		}
		// May be there were duplicates or failed to add. Repeat until total = inserted.
		$diff = $total - $inserted;
		if ( $diff > 0 ) {
			self::handle_auto_code( $group_id, $args, array( $diff ) );
		}
	}

	/**
	 * @param int $paged
	 * @param string $orderby
	 * @param string $order
	 *
	 * @param string $search
	 *
	 * @return array|null|object
	 */
	public static function get_groups( $paged = 1, $orderby = 'issue_date', $order = 'DESC', $search = '' ) {
		global $wpdb;
		$limit = ( $paged - 1 ) * 100;
		switch ( $orderby ) {
			case 'issue_date':
				$orderby = 'issue_date';
				break;
		}
		$order      = ( $orderby ) ? ' ORDER BY ' . $orderby . ' ' . $order : '';
		$tbl_groups = $wpdb->prefix . Config::$tbl_groups;
		$tbl_codes  = $wpdb->prefix . Config::$tbl_codes;
		if ( ! empty( $search ) ) {
			$sql = $wpdb->prepare(
				"SELECT g.* FROM {$tbl_groups} g
    LEFT JOIN {$tbl_codes} c
        ON g.ID = c.code_group
WHERE ( g.prefix LIKE '%%%s%%' OR g.suffix LIKE '%%%s%%' OR g.name LIKE '%%%s%%' OR c.code LIKE '%%%s%%' )
GROUP BY g.ID {$order} LIMIT {$limit}, 500",
				$search,
				$search,
				$search,
				$search
			);
		} else {
			$sql = "SELECT * FROM {$tbl_groups} {$order} LIMIT {$limit}, 500";
		}

		return $wpdb->get_results( $sql );
	}

	/**
	 * @param string $group
	 * @param int $paged
	 * @param string $orderby
	 * @param string $order
	 *
	 * @return array|null|object
	 */
	public static function get_coupons( $group = 'all', $paged = 1, $orderby = '', $order = 'DESC', $search = '' ) {
		global $wpdb;
		$limit = $where = '';

		if ( 'all' !== $group ) {
			$where = 'WHERE c.code_group = ' . $group;
		}
		if ( ! empty( $search ) ) {
			$where .= ( $where == '' ? 'WHERE' : ' AND ' ) . " ( c.code LIKE '%{$search}%' )";
		}
		switch ( $orderby ) {
			case 'used_date':
				$orderby = 'used_date';
				break;
		}
		$order = ( $orderby ) ? ' ORDER BY ' . $orderby . ' ' . $order : '';

		$limit = ( $paged - 1 ) * 100;
		$limit = "LIMIT {$limit}, 100";

		return $wpdb->get_results( "SELECT c.*, g.expire_date, c.expire_date AS code_expire_date FROM {$wpdb->prefix}" . Config::$tbl_codes . " c LEFT JOIN {$wpdb->prefix}" . Config::$tbl_groups . " g ON g.ID=c.code_group {$where} {$order} {$limit}" );
	}

	/**
	 * @return null|string
	 */
	public static function get_num_groups( $search = '' ) {
		global $wpdb;
		$sql = '';
		if ( ! empty( $search ) ) {
			$sql = $wpdb->prepare( "SELECT COUNT(DISTINCT g.ID) FROM {$wpdb->prefix}" . Config::$tbl_groups . " g LEFT JOIN {$wpdb->prefix}" . Config::$tbl_codes . " c ON g.ID = c.code_group WHERE ( g.prefix LIKE '%%%s%%' OR g.suffix LIKE '%%%s%%' OR c.code LIKE '%%%s%%' ) ", $search, $search, $search );
		} else {
			$sql = "SELECT COUNT(DISTINCT g.ID) FROM {$wpdb->prefix}" . Config::$tbl_groups . ' g ';
		}

		return $wpdb->get_var( $sql );
	}

	/**
	 * @param string $group
	 *
	 * @param string $search
	 *
	 * @return null|string
	 */
	public static function get_num_coupons( $group = 'all', $search = '' ) {
		global $wpdb;

		$where = '';
		if ( $group !== 'all' ) {
			$where = 'WHERE c.code_group = ' . $group;
		}
		if ( ! empty( $search ) ) {
			$where .= ( $where == '' ? 'WHERE' : ' AND ' ) . " ( c.code LIKE '%{$search}%' )";
		}

		return $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}" . Config::$tbl_codes . ' c ' . $where );
	}

	/**
	 * @param $group_id
	 */
	public static function delete_coupon( $group_id ) {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . Config::$tbl_groups, array( 'ID' => $group_id ) );
		$wpdb->delete( $wpdb->prefix . Config::$tbl_codes, array( 'code_group' => $group_id ) );
	}

	/**
	 * @param      $coupon
	 * @param null $user_id
	 *
	 * @return array|int
	 */
	public static function is_coupon_available( $coupon, $user_id = null ) {
		global $wpdb;
		if ( is_null( $user_id ) ) {
			$user_id = wp_get_current_user()->ID;
		}

		$qry     = $wpdb->prepare(
			"SELECT g.ID AS codes_group_ID, g.code_for, c.ID as coupon_id, c.code, c.code_group, g.issue_max_count AS max_count, g.expire_date as batch_expiry_date, g.linked_to, c.expire_date as code_expiry_date
										FROM {$wpdb->prefix}" . Config::$tbl_groups . " g
										LEFT JOIN {$wpdb->prefix}" . Config::$tbl_codes . ' c
										ON g.ID = c.code_group
										WHERE c.code LIKE %s',
			trim( $coupon )
		);
		$results = $wpdb->get_row( $qry );
		if ( ! $results ) {
			return array(
				'result' => 'failed',
				'error'  => 'invalid',
			);
		}

		$coupon_id      = absint( $results->coupon_id );
		$codes_batch_id = absint( $results->code_group );
		$user_id        = absint( $user_id );
		$users          = self::get_users_of_code( $coupon_id, true, true );
		$max_times      = (int) $results->max_count;
		if ( 1 === $max_times && 'automator' === (string) $results->code_for ) {
			$max_times = apply_filters( 'ulc_code_max_usage', $max_times, $results->code_for, $results->codes_group_ID );
		}

		$current_time      = strtotime( date_i18n( 'Y-m-d H:i:s' ) );
		$code_expiry_date  = $results->code_expiry_date !== '0000-00-00 00:00:00' ? strtotime( $results->code_expiry_date ) : null;
		$batch_expiry_date = $results->batch_expiry_date !== '0000-00-00 00:00:00' ? strtotime( $results->batch_expiry_date ) : null;

		if ( $code_expiry_date && $current_time > $code_expiry_date ) {
			return array(
				'result' => 'failed',
				'error'  => 'expired',
			);
		} elseif ( $batch_expiry_date && $current_time > $batch_expiry_date ) {
			return array(
				'result' => 'failed',
				'error'  => 'expired',
			);
		}

		// No one has used the code so far.
		if ( empty( $users ) ) {
			return self::maybe_check_course_group_linkage( $results, $user_id, $coupon_id );
		}

		// User has already redeemed the code
		if ( in_array( $user_id, $users, true ) && ( 0 !== $user_id || empty( $user_id ) || null !== $user_id ) ) {
			// Check if the user has previously redeemed code and repeated usage is allowed
			if ( false === self::is_multiple_redemption_allowed( $codes_batch_id ) ) {
				// Multiple re-use are not allowed. Bail
				return array(
					'result' => 'failed',
					'error'  => 'existing', //User has used the code before
				);
			}
			// Check if the user has redeemed the number of times set in the setting or via filter
			$status = self::has_redeemed_maximum_times( $codes_batch_id, $coupon_id, $user_id );
			if ( false === $status ) {
				return array(
					'result' => 'failed',
					'error'  => 'max', // Maximum times redeemed
				);
			}
			// If allowed to reuse code, return the Code ID.
			if ( true === self::is_multiple_redemption_allowed( $codes_batch_id ) ) {
				return self::maybe_check_course_group_linkage( $results, $user_id, $coupon_id );
			}

			// fallback
			return array(
				'result' => 'failed',
				'error'  => 'invalid',
			);
		}
		// User has not redeemed the code previously. Check if the code
		// has been used the maximum number of times
		if ( count( array_unique( $users ) ) >= intval( $max_times ) ) {
			return array(
				'result' => 'failed',
				'error'  => 'max',
			);
		}

		return self::maybe_check_course_group_linkage( $results, $user_id, $coupon_id );
	}

	/**
	 * Check if the coupon is linked to a course or group that the user is already enrolled in.
	 *
	 * @param object $coupon_row The coupon row object containing coupon data.
	 * @param int $user_id The user ID.
	 * @param int $coupon_id The coupon ID.
	 *
	 * @return mixed The coupon ID if the user is not enrolled in any of the linked courses/groups, or an array with 'result' => 'failed' and 'error' => 'existing_course' or 'existing_group' if the user is already enrolled.
	 */
	private static function maybe_check_course_group_linkage( $coupon_row, $user_id, $coupon_id ) {
		$linked_to = ! empty( $coupon_row->linked_to ) ? maybe_unserialize( $coupon_row->linked_to ) : '';
		$code_for  = (string) $coupon_row->code_for;
		if ( is_array( $linked_to ) && ! empty( $linked_to ) && 0 < $user_id ) {
			if ( defined( 'LEARNDASH_VERSION' ) ) {
				if ( 'course' === $code_for ) {
					// Fetch the enrolled course IDs for the user
					$user_courses = learndash_user_get_enrolled_courses( $user_id, array(), true );
					if ( ! empty( $user_courses ) ) {
						// Find elements in $linked_to that are not in $user_courses
						$diff = array_diff( $linked_to, $user_courses );

						if ( empty( $diff ) ) {
							return array(
								'result' => 'failed',
								'error'  => 'existing_course',
							);
						}
					}
				} elseif ( 'group' === $code_for ) {
					$user_groups = learndash_get_users_group_ids( $user_id, true );
					if ( ! empty( $user_groups ) ) {
						// Find elements in $linked_to that are not in $user_groups
						$diff = array_diff( $linked_to, $user_groups );
						if ( empty( $diff ) ) {
							return array(
								'result' => 'failed',
								'error'  => 'existing_group',
							);
						}
					}
				}
			}
		}

		return $coupon_id;
	}


	/**
	 * @param      $code_id
	 * @param bool $user_ids
	 *
	 * @return array
	 */
	public static function get_users_of_code( $code_id, $user_ids = true, $filter_user_id = false ) {
		// Static cache to prevent duplicate queries within the same request
		static $cache = array();

		// Create cache key based on parameters
		$cache_key = $code_id . '|' . (int) $user_ids . '|' . (int) $filter_user_id;

		// Return cached result if available
		if ( isset( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

		global $wpdb;
		$tbl_usage = $wpdb->prefix . Config::$tbl_codes_usage;
		$sql       = "SELECT user_id, date_redeemed FROM {$tbl_usage}  WHERE code_id = %d ";
		if ( $filter_user_id ) {
			$sql .= ' AND user_id > 0 ';
		}
		$sql  .= ' GROUP BY user_id ORDER BY date_redeemed DESC ';
		$data = $wpdb->get_results( $wpdb->prepare( $sql, $code_id ) );
		if ( $data && $user_ids ) {
			$data = array_column( $data, 'user_id' );
			$data = array_map( 'absint', $data );
		}

		// Cache the result for this request
		$cache[ $cache_key ] = $data;

		return $data;
	}

	/**
	 * @param $coupon
	 *
	 * @return bool
	 */
	public static function is_coupon_paid( $coupon ) {
		global $wpdb;
		$is_automator = self::is_automator_code( $coupon );
		if ( true === $is_automator ) {
			return false;
		}
		$prepare = $wpdb->prepare(
			"
		SELECT c.ID FROM {$wpdb->prefix}" . Config::$tbl_codes . " c
		LEFT JOIN {$wpdb->prefix}" . Config::$tbl_groups . ' cg
		ON c.code_group = cg.ID
		WHERE c.code = %s
		AND cg.paid_unpaid = %s',
			trim( $coupon ),
			'paid'
		);
		if ( ! empty( $wpdb->get_var( $prepare ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param $coupon
	 *
	 * @return bool
	 */
	public static function is_coupon_active( $coupon ) {
		global $wpdb;

		if ( ! empty( $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->prefix}" . Config::$tbl_codes . ' WHERE `code` = %s AND `is_active` = %d', trim( $coupon ), 1 ) ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param $coupon
	 *
	 * @return bool
	 */
	public static function is_default_code( $coupon ) {
		global $wpdb;
		$is_automator = self::is_automator_code( $coupon );
		if ( true === $is_automator ) {
			return true;
		}
		$prepare = $wpdb->prepare(
			"
		SELECT c.ID FROM {$wpdb->prefix}" . Config::$tbl_codes . " c
		LEFT JOIN {$wpdb->prefix}" . Config::$tbl_groups . ' cg
		ON c.code_group = cg.ID
		WHERE c.code = %s
		AND cg.paid_unpaid = %s',
			trim( $coupon ),
			'default'
		);
		if ( ! empty( $wpdb->get_var( $prepare ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param $coupon
	 *
	 * @return bool
	 */
	public static function is_automator_code( $coupon ) {
		global $wpdb;
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT cg.code_for FROM {$wpdb->prefix}" . Config::$tbl_codes . " c
		LEFT JOIN {$wpdb->prefix}" . Config::$tbl_groups . ' cg
		ON c.code_group = cg.ID
		WHERE c.code = %s',
				$coupon
			)
		);

		if ( ! empty( $result ) && 'automator' === (string) $result ) {
			return true;
		}

		return false;
	}

	/**
	 * @param $group_id
	 *
	 * @return int
	 */
	public static function get_group_redeemed_count( $group_id ) {
		global $wpdb;

		$prepare = $wpdb->prepare(
			"SELECT COUNT(`usage`.ID) AS code_count
FROM $wpdb->prefix" . Config::$tbl_codes . " AS codes
JOIN  $wpdb->prefix" . Config::$tbl_codes_usage . ' AS `usage` ON codes.ID = `usage`.code_id
WHERE codes.code_group = %d',
			$group_id
		);

		return $wpdb->get_var( $prepare );
	}

	/**
	 * @param $group_id
	 *
	 * @return int
	 */
	public static function get_group_cancelled_count( $group_id ) {
		global $wpdb;
		$prepare = $wpdb->prepare(
			"SELECT is_active
FROM $wpdb->prefix" . Config::$tbl_codes . '
WHERE code_group = %d
AND is_active = 0',
			$group_id
		);

		$result = $wpdb->get_results( $prepare );

		return count( $result );
	}

	/**
	 * @param $user_id
	 * @param $coupon_id
	 *
	 * @return array|bool
	 */
	public static function set_user_to_coupon( $user_id, $coupon_id ) {
		global $wpdb;

		$tbl_groups         = $wpdb->prefix . Config::$tbl_groups;
		$tbl_codes          = $wpdb->prefix . Config::$tbl_codes;
		$tbl_code_usage     = $wpdb->prefix . Config::$tbl_codes_usage;
		$get_coupon_details = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT g.ID as group_id, g.prefix, g.code_for, g.linked_to, c.ID AS coupon_id, c.code, g.issue_max_count AS max_count
										FROM {$tbl_groups} g
										LEFT JOIN {$tbl_codes} c
										ON g.ID = c.code_group
										WHERE c.ID = %d",
				$coupon_id
			)
		);

		if ( $get_coupon_details ) {
			$wpdb->insert(
				$tbl_code_usage,
				array(
					'code_id'       => $coupon_id,
					'user_id'       => $user_id,
					'date_redeemed' => current_time( 'mysql' ),
				),
				array(
					'%d',
					'%d',
					'%s',
				)
			);

			update_user_meta( $user_id, Config::$uncanny_codes_user_prefix_meta, $get_coupon_details->prefix );
			// Add usermeta to store which code ID was used at time (just for backup).
			add_user_meta( $user_id, 'ulc_code_' . $coupon_id, current_time( 'mysql' ) );

			$coupon_for = $get_coupon_details->code_for;
			$linked_to  = maybe_unserialize( $get_coupon_details->linked_to );
			$data       = array(
				'for'  => $coupon_for,
				'data' => $linked_to,
			);

			return $data;
		}

		return array();
	}


	// Generate CSV.

	/**
	 * @param string $group
	 *
	 * @return array
	 */
	public static function get_coupons_csv( $group = 'all' ) {
		global $wpdb;
		$where = '';
		$array = array();

		if ( $group !== 'all' ) {
			$where = 'WHERE c.code_group = ' . $group;
		}

		$qry = "SELECT
	c.ID as Coupon_ID,
	c.code AS Code,
	c.order_id,
	c.is_active,
	c.expire_date AS code_expire_date,
	g.code_for AS `Code For`,
	g.prefix AS Prefix,
	g.suffix AS Suffix,
	g.linked_to AS `Linked To`,
	g.expire_date,
	g.issue_date,
	g.product_id
FROM {$wpdb->prefix}" . Config::$tbl_groups . " g
LEFT JOIN {$wpdb->prefix}" . Config::$tbl_codes . " c
ON g.ID = c.code_group
$where";

		$results = $wpdb->get_results( $qry );

		if ( empty( $results ) ) {
			return array();
		}

		$date_format       = apply_filters( 'ulc_download_date_format', get_option( 'date_format' ) );
		$time_format       = apply_filters( 'ulc_download_time_format', get_option( 'time_format' ) );
		$num_times_allowed = 1;
		if ( is_numeric( $group ) ) {
			$num_times_allowed = self::number_of_times_a_user_allowed_to_redeem( $group );
		}

		foreach ( $results as $result ) {
			$val       = (array) $result;
			$dd        = array();
			$linked_to = maybe_unserialize( $val['Linked To'] );

			foreach ( $linked_to as $d ) {
				$dd[] = get_the_title( $d );
			}

			$linked_to = join( '|', $dd );

			$code           = $val['Code'];
			$status         = 1 === (int) $val['is_active'] ? 'Active' : 'Cancelled';
			$code_for       = $val['Code For'];
			$prefix         = $val['Prefix'];
			$suffix         = $val['Suffix'];
			$issue_date     = ! empty( $val['issue_date'] ) ? date_i18n( $date_format, strtotime( $val['issue_date'] ) ) : '';
			$redeemed_date  = '';
			$redeemed_user  = '';
			$linked_product = ! empty( $val['product_id'] ) ? get_the_title( $val['product_id'] ) : '';
			$order_id       = ! empty( $val['order_id'] ) ? $val['order_id'] : '';
			$expiry_date    = 'Unlimited';

			if ( ! empty( $val['code_expire_date'] ) && $val['code_expire_date'] !== '0000-00-00 00:00:00' ) {
				$code_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $val['code_expire_date'] );
				if ( $code_date !== false ) {
					$expiry_date = $code_date->format( $date_format . ' ' . $time_format );
				}
			} elseif ( ! empty( $val['expire_date'] ) && $val['expire_date'] !== '0000-00-00 00:00:00' ) {
				$_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $val['expire_date'] );
				if ( $_date !== false ) {
					$expiry_date = $_date->format( $date_format . ' ' . $time_format );
				}
			}

			$users = self::get_users_of_code( $result->Coupon_ID, false );

			if ( ! empty( $users ) ) {
				if ( ! function_exists( 'get_user_by' ) ) {
					include ABSPATH . 'wp-includes/pluggable.php';
				}

				foreach ( $users as $u ) {
					$user_email = '';
					$user       = get_user_by( 'ID', $u->user_id );

					if ( $user ) {
						$num_times_allowed = self::get_usage_count_of_user_by_code( $val['Coupon_ID'], $u->user_id );
						$user_email        = $user->user_email;
					}

					if ( 0 < (int) $u->user_id && '' === $user_email ) {
						$user_email        = sprintf( __( 'Deleted user (ID: %d)', 'uncanny-learndash-codes' ), (int) $u->user_id );
						$num_times_allowed = '';
					}

					$array[] = (object) array(
						'Code'           => $code,
						'Status'         => $status,
						'Code For'       => $code_for,
						'Prefix'         => $prefix,
						'Suffix'         => $suffix,
						'Linked To'      => $linked_to,
						'Issue Date'     => $issue_date,
						'Redeemed Date'  => ! empty( $u->date_redeemed ) ? date_i18n( $date_format, strtotime( $u->date_redeemed ) ) : '',
						'Redeemed User'  => $user_email,
						'Redeemed Times' => $num_times_allowed,
						'Expiry Date'    => $expiry_date,
						'Linked Product' => $linked_product,
						'Order ID'       => $order_id,
					);
				}
			} else {

				$array[] = (object) array(
					'Code'           => $code,
					'Status'         => $status,
					'Code For'       => $code_for,
					'Prefix'         => $prefix,
					'Suffix'         => $suffix,
					'Linked To'      => $linked_to,
					'Issue Date'     => $issue_date,
					'Redeemed Date'  => $redeemed_date,
					'Redeemed User'  => $redeemed_user,
					'Redeemed Times' => '',
					'Expiry Date'    => $expiry_date,
					'Linked Product' => $linked_product,
					'Order ID'       => $order_id,
				);
			}
		}

		return $array;
	}

	/**
	 * Get CSV data for selected codes by their IDs
	 *
	 * @param array $code_ids Array of code IDs to export
	 * @return array
	 */
	public static function get_selected_codes_csv( $code_ids = array() ) {
		global $wpdb;
		$array = array();

		if ( empty( $code_ids ) ) {
			return array();
		}

		// Sanitize code IDs
		$code_ids = array_map( 'absint', $code_ids );
		$code_ids = array_filter( $code_ids );

		if ( empty( $code_ids ) ) {
			return array();
		}

		$code_ids_placeholders = implode( ',', array_fill( 0, count( $code_ids ), '%d' ) );

		$qry = $wpdb->prepare(
			"SELECT
	c.ID as Coupon_ID,
	c.code AS Code,
	c.order_id,
	c.is_active,
	c.expire_date AS code_expire_date,
	g.code_for AS `Code For`,
	g.prefix AS Prefix,
	g.suffix AS Suffix,
	g.linked_to AS `Linked To`,
	g.expire_date,
	g.issue_date,
	g.product_id
FROM {$wpdb->prefix}" . Config::$tbl_groups . " g
LEFT JOIN {$wpdb->prefix}" . Config::$tbl_codes . " c
ON g.ID = c.code_group
WHERE c.ID IN ($code_ids_placeholders)",
			...$code_ids
		);

		$results = $wpdb->get_results( $qry );

		if ( empty( $results ) ) {
			return array();
		}

		$date_format = apply_filters( 'ulc_download_date_format', get_option( 'date_format' ) );
		$time_format = apply_filters( 'ulc_download_time_format', get_option( 'time_format' ) );

		foreach ( $results as $result ) {
			$val       = (array) $result;
			$dd        = array();
			$linked_to = maybe_unserialize( $val['Linked To'] );

			foreach ( $linked_to as $d ) {
				$dd[] = get_the_title( $d );
			}

			$linked_to = join( '|', $dd );

			$code           = $val['Code'];
			$status         = 1 === (int) $val['is_active'] ? 'Active' : 'Cancelled';
			$code_for       = $val['Code For'];
			$prefix         = $val['Prefix'];
			$suffix         = $val['Suffix'];
			$issue_date     = ! empty( $val['issue_date'] ) ? date_i18n( $date_format, strtotime( $val['issue_date'] ) ) : '';
			$redeemed_date  = '';
			$redeemed_user  = '';
			$linked_product = ! empty( $val['product_id'] ) ? get_the_title( $val['product_id'] ) : '';
			$order_id       = ! empty( $val['order_id'] ) ? $val['order_id'] : '';
			$expiry_date    = 'Unlimited';

			if ( ! empty( $val['code_expire_date'] ) && $val['code_expire_date'] !== '0000-00-00 00:00:00' ) {
				$code_date = \DateTime::createFromFormat( 'Y-m-d H:i:s', $val['code_expire_date'] );
				if ( $code_date !== false ) {
					$expiry_date = $code_date->format( $date_format . ' ' . $time_format );
				}
			} elseif ( ! empty( $val['expire_date'] ) && $val['expire_date'] !== '0000-00-00 00:00:00' ) {
				$_date = \DateTime::createFromFormat( 'Y-m-d H:i:s', $val['expire_date'] );
				if ( $_date !== false ) {
					$expiry_date = $_date->format( $date_format . ' ' . $time_format );
				}
			}

			$users = self::get_users_of_code( $result->Coupon_ID, false );

			if ( ! empty( $users ) ) {
				if ( ! function_exists( 'get_user_by' ) ) {
					include ABSPATH . 'wp-includes/pluggable.php';
				}

				foreach ( $users as $u ) {
					$user_email = '';
					$user       = get_user_by( 'ID', $u->user_id );

					if ( $user ) {
						$num_times_allowed = self::get_usage_count_of_user_by_code( $val['Coupon_ID'], $u->user_id );
						$user_email        = $user->user_email;
					}

					if ( 0 < (int) $u->user_id && '' === $user_email ) {
						$user_email        = sprintf( __( 'Deleted user (ID: %d)', 'uncanny-learndash-codes' ), (int) $u->user_id );
						$num_times_allowed = '';
					}

					$array[] = (object) array(
						'Code'           => $code,
						'Status'         => $status,
						'Code For'       => $code_for,
						'Prefix'         => $prefix,
						'Suffix'         => $suffix,
						'Linked To'      => $linked_to,
						'Issue Date'     => $issue_date,
						'Redeemed Date'  => ! empty( $u->date_redeemed ) ? date_i18n( $date_format, strtotime( $u->date_redeemed ) ) : '',
						'Redeemed User'  => $user_email,
						'Redeemed Times' => $num_times_allowed,
						'Expiry Date'    => $expiry_date,
						'Linked Product' => $linked_product,
						'Order ID'       => $order_id,
					);
				}
			} else {

				$array[] = (object) array(
					'Code'           => $code,
					'Status'         => $status,
					'Code For'       => $code_for,
					'Prefix'         => $prefix,
					'Suffix'         => $suffix,
					'Linked To'      => $linked_to,
					'Issue Date'     => $issue_date,
					'Redeemed Date'  => $redeemed_date,
					'Redeemed User'  => $redeemed_user,
					'Redeemed Times' => '',
					'Expiry Date'    => $expiry_date,
					'Linked Product' => $linked_product,
					'Order ID'       => $order_id,
				);
			}
		}

		return $array;
	}

	/**
	 * @param        $code_length
	 * @param string $type
	 *
	 * @return array
	 */
	public static function get_coupon_codes( $code_length, $type = 'auto' ) {
		global $wpdb;
		$codes   = array();
		$prepare = "SELECT code FROM {$wpdb->prefix}" . Config::$tbl_codes;

		$results = $wpdb->get_results( $prepare );
		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				if ( 'auto' === $type ) {
					$code = str_replace( '-', '', $result->code );
					if ( strlen( $code ) == trim( $code_length ) ) {
						$codes[] = $code;
					}
				} else {
					$codes[] = $result->code;
				}
			}
		}

		return $codes;
	}

	/**
	 * @param       $order_id
	 * @param false $is_my_account
	 *
	 * @return array|object|null
	 */
	public static function get_codes_usage_by_order_id( $order_id, $is_my_account = false ) {
		global $wpdb;
		$table1 = $wpdb->prefix . Config::$tbl_codes;
		$table2 = $wpdb->prefix . Config::$tbl_groups;
		$table3 = $wpdb->prefix . Config::$tbl_codes_usage;
		$limit  = 'LIMIT 0, 200';
		if ( $is_my_account ) {
			$limit = '';
		}
		$sql = $wpdb->prepare(
			"SELECT c.ID, c.code, g.product_id, u.date_redeemed AS used_date, u.user_id
		FROM {$table1} c
		LEFT JOIN {$table2} g
		ON c.code_group = g.ID
		LEFT JOIN {$table3} u
		ON c.ID = u.code_id
		WHERE c.order_id=%d {$limit}",
			$order_id
		);

		return $wpdb->get_results( $sql );
	}

	/**
	 * @param $codes_batch_id
	 *
	 * @return mixed|void
	 */
	public static function is_multiple_redemption_allowed( $codes_batch_id = '' ) {
		$is_allowed = AdminMenu::get_settings_value( Config::$uncanny_codes_same_code_user_redemption, 0, is_multisite() );

		return apply_filters( 'ulc_codes_multiple_redemption_allowed', boolval( $is_allowed ), $codes_batch_id );
	}

	/**
	 * @param $codes_batch_id
	 *
	 * @return mixed|void
	 */
	public static function number_of_times_a_user_allowed_to_redeem( $codes_batch_id = '' ) {
		if ( ! self::is_multiple_redemption_allowed( $codes_batch_id ) ) {
			return apply_filters( 'ulc_codes_times_user_allowed_to_redeem', 1, $codes_batch_id );
		}
		$number_times = AdminMenu::get_settings_value( Config::$uncanny_codes_times_code_can_be_reused, 1, is_multisite() );

		return apply_filters( 'ulc_codes_times_user_allowed_to_redeem', $number_times, $codes_batch_id );
	}

	/**
	 * @param string $codes_batch_id
	 * @param string $code_id
	 * @param string $user_id
	 *
	 * @return bool
	 */
	public static function has_redeemed_maximum_times( $codes_batch_id = '', $code_id = '', $user_id = '' ) {
		$number_of_times = self::number_of_times_a_user_allowed_to_redeem( $codes_batch_id );

		$redeemed_times = self::get_usage_count_of_user_by_code( $code_id, $user_id );
		if ( $redeemed_times < $number_of_times ) {
			return true;
		}

		return false;
	}

	/**
	 * @param $code_id
	 * @param $user_id
	 *
	 * @return string|null
	 */
	public static function get_usage_count_of_user_by_code( $code_id, $user_id ) {
		global $wpdb;
		$tbl_usage = $wpdb->prefix . Config::$tbl_codes_usage;

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(user_id) FROM {$tbl_usage}  WHERE code_id = %d AND user_id = %d", $code_id, $user_id ) );
	}

	/**
	 * @param $code_id
	 * @param $user_id
	 *
	 * @return array
	 */
	public static function get_used_dates_of_user_by_code( $code_id, $user_id ) {
		global $wpdb;
		$tbl_usage = $wpdb->prefix . Config::$tbl_codes_usage;

		return $wpdb->get_col( $wpdb->prepare( "SELECT date_redeemed FROM {$tbl_usage}  WHERE code_id = %d AND user_id = %d", $code_id, $user_id ) );
	}

	/**
	 * @param $code
	 *
	 * @return array|object|\stdClass|void|null
	 */
	public static function is_coupon_valid( $code ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . Config::$tbl_codes . ' WHERE code = %s', trim( $code ) ) );
	}

	/**
	 * @param $code_id
	 *
	 * @return array|object|\stdClass|null
	 */
	public static function get_coupon_details( $code_id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . Config::$tbl_codes . ' WHERE ID = %s', $code_id ) );
	}

	/**
	 * @param $code_id
	 * @param $status
	 *
	 * @return bool|int|\mysqli_result|resource|void|null
	 */
	public static function update_coupon_status( $code_id, $status = 1 ) {
		global $wpdb;
		if ( null === $code_id ) {
			return;
		}

		return $wpdb->update(
			$wpdb->prefix . Config::$tbl_codes,
			array(
				'is_active' => $status,
			),
			array(
				'ID' => $code_id,
			),
			array(
				'%d',
			),
			array(
				'%d',
			)
		);
	}

	/**
	 * @param $code_id
	 * @param $group_id
	 *
	 * @return true|void
	 */
	public static function replace_code( $code_id, $group_id ) {

		$group_details = self::get_group_details( $group_id );

		$code_details = self::get_coupon_details( $code_id );

		if ( empty( $code_details ) ) {
			wp_die( 'Invalid code or code ID provided. Please try again or contact support.' );
		}

		if ( (int) $code_details->code_group !== (int) $group_id ) {
			wp_die( 'Invalid group or code group provided. Please try again.' );
		}

		$length = strlen( str_replace( '-', '', $code_details->code ) );

		$length = $length - strlen( $group_details['prefix'] ) - strlen( $group_details['suffix'] );

		$group_details['code_length'] = $length;

		$new_code = GenerateCodes::get_unique_codes( $group_details, 1 );

		if ( empty( $new_code ) ) {
			wp_die( 'Failed to generate a new code. Please try again or contact support.' );
		}

		$new_code = array_shift( $new_code );

		global $wpdb;
		$result = $wpdb->update(
			$wpdb->prefix . Config::$tbl_codes,
			array(
				'code' => $new_code,
			),
			array(
				'ID'         => $code_id,
				'code_group' => $group_id,
			),
			array(
				'%s',
			),
			array(
				'%d',
				'%d',
			)
		);

		if ( $result ) {
			return true;
		}

		wp_die( 'Something went wrong.' . $result );
	}

	/**
	 * @param $code_id
	 * @param $group_id
	 *
	 * @return true|void
	 */
	public static function cancel_code( $code_id, $group_id ) {

		$code_details = self::get_coupon_details( $code_id );

		if ( empty( $code_details ) ) {
			wp_die( 'Invalid code or code ID provided. Please try again or contact support.' );
		}

		if ( (int) $code_details->code_group !== (int) $group_id ) {
			wp_die( 'Invalid group or code group provided. Please try again.' );
		}

		global $wpdb;
		$result = $wpdb->update(
			$wpdb->prefix . Config::$tbl_codes,
			array(
				'is_active' => 0,
			),
			array(
				'ID'         => $code_id,
				'code_group' => $group_id,
			),
			array(
				'%s',
			),
			array(
				'%d',
				'%d',
			)
		);

		if ( $result ) {
			return true;
		}

		wp_die( 'Something went wrong.' . $result );
	}

	/**
	 * @param $code_id
	 * @param $group_id
	 *
	 * @return true|void
	 */
	public static function delete_code( $code_id, $group_id ) {

		$code_details = self::get_coupon_details( $code_id );

		if ( empty( $code_details ) ) {
			wp_die( 'Invalid code or code ID provided. Please try again or contact support.' );
		}

		if ( (int) $code_details->code_group !== (int) $group_id ) {
			wp_die( 'Invalid group or code group provided. Please try again.' );
		}

		global $wpdb;
		$result = $wpdb->delete(
			$wpdb->prefix . Config::$tbl_codes,
			array(
				'ID'         => $code_id,
				'code_group' => $group_id,
			),
			array(
				'%d',
				'%d',
			)
		);

		if ( $result ) {
			return true;
		}

		wp_die( 'Something went wrong.' . $result );
	}

	/**
	 * @param $group_id
	 *
	 * @return array
	 */
	public static function get_group_details( $group_id = null ) {
		if ( null === $group_id ) {
			return array();
		}

		global $wpdb;
		$group = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->prefix" . Config::$tbl_groups . ' WHERE ID = %d', $group_id ) );
		if ( empty( $group ) ) {
			return array();
		}

		$args = array(
			'generation_type' => 'auto',
			'dashes'          => explode( '-', $group->dash ),
			'prefix'          => $group->prefix,
			'suffix'          => $group->suffix,
			'character_type'  => maybe_unserialize( $group->character_type ),
		);

		return $args;
	}


	/**
	 * Check if a coupon is expired.
	 *
	 * @param object $coupon
	 *
	 * @return bool
	 */
	public static function is_coupon_expired( $coupon ) {
		$current_time      = new DateTime();
		$code_expiry_date  = ! empty( $coupon->code_expire_date ) && $coupon->code_expire_date !== '0000-00-00 00:00:00' ? new DateTime( $coupon->code_expire_date ) : null;
		$batch_expiry_date = ! empty( $coupon->expire_date ) && $coupon->expire_date !== '0000-00-00 00:00:00' ? new DateTime( $coupon->expire_date ) : null;

		if ( $code_expiry_date && $current_time > $code_expiry_date ) {
			return true;
		} elseif ( $batch_expiry_date && $current_time > $batch_expiry_date ) {
			return true;
		}

		return false;
	}
}
