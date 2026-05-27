<?php
namespace uncanny_learndash_reporting\tincanny_reporting;
use \uncanny_learndash_reporting\Cache;
/**
 *
 */
class Database {

	/**
	 * Temporary Table
	 *
	 * @var string
	 */
	public static $tbl_reporting_api_user_id = 'tbl_reporting_api_user_id';

	/**
	 * Get User Data
	 *
	 * @param $leader_id
	 *
	 * @return array|object|\stdClass[]|null
	 */
	public static function get_user_data( $leader_id ) {
		$cache = Cache::get( __FUNCTION__ . $leader_id );

		if ( ! empty( $cache ) ) {
			return $cache;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . self::$tbl_reporting_api_user_id;

		// phpcs:disable WordPress.DB.PreparedSQL
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT u.ID, u.display_name, u.user_email, u.user_login, m1.meta_value AS first_name, m2.meta_value AS last_name, m3.meta_value AS user_roles
				FROM {$wpdb->users} u
				JOIN {$table_name} t
				ON t.user_id = u.ID AND t.group_leader_id = %d
				LEFT JOIN {$wpdb->usermeta} m1
				ON m1.user_id = t.user_id AND m1.meta_key = 'first_name'
				LEFT JOIN {$wpdb->usermeta} m2
				ON m2.user_id = t.user_id AND m2.meta_key = 'last_name'
				LEFT JOIN {$wpdb->usermeta} m3
				ON m3.user_id = t.user_id AND m3.meta_key = %s",
				$leader_id,
				$wpdb->prefix . 'capabilities'
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL
		Cache::create( __FUNCTION__ . $leader_id, $results );

		return $results;
	}

	/**
	 * Remove user IDs by role exclusion
	 *
	 * @param $filtered_user_ids
	 * @param $leader_id
	 *
	 * @return void
	 */
	public static function remove_user_id_by_role_exclusion( $filtered_user_ids, $leader_id ) {
		global $wpdb;
		// phpcs:disable WordPress.DB.PreparedSQL
		$temp_table = $wpdb->prefix . self::$tbl_reporting_api_user_id;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$temp_table} WHERE group_leader_id = %d AND user_id IN (%s)",
				$leader_id,
				join( ', ', $filtered_user_ids )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL
	}

	/**
	 * Drop temporary table
	 *
	 * @return bool|int
	 */
	public static function truncate_reporting_api_user_id_table() {
		global $wpdb;
		$tbl_reporting_api_user_id = $wpdb->prefix . self::$tbl_reporting_api_user_id;

		// phpcs:disable WordPress.DB.PreparedSQL
		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$tbl_reporting_api_user_id} WHERE group_leader_id = %d;",
				wp_get_current_user()->ID
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL
	}

	/**
	 * Insert user ids in temp table
	 *
	 * @param $user_ids
	 */
	public static function insert_in_tbl_reporting_api_user_ids( $user_ids ) {
		global $wpdb;
		// phpcs:disable WordPress.DB.PreparedSQL
		$temp_table = $wpdb->prefix . self::$tbl_reporting_api_user_id;
		$chunk      = 500;
		$chunks = array_chunk( $user_ids, $chunk );

		if ( $chunks ) {
			$add_user_ids = array();
			foreach ( $chunks as $chunk ) {
				foreach ( $chunk as $chunk_values ) {
					$add_user_ids[] = '(' . implode( ', ', $chunk_values ) . ')';
				}

				if ( $add_user_ids ) {
					$implode = implode( ',', $add_user_ids );
					$r       = $wpdb->query( "INSERT INTO {$temp_table} (`user_id`, `group_leader_id`) VALUES $implode" );
				}
				$add_user_ids = array();
			}
		}
		// phpcs:enable WordPress.DB.PreparedSQL

	}

	/**
	 * Get all user ids
	 *
	 * @return array
	 * @deprecated
	 */
	public static function get_all_user_ids() {
		global $wpdb;
		$temp_table = $wpdb->prefix . self::$tbl_reporting_api_user_id;

		// phpcs:disable WordPress.DB.PreparedSQL
		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_id FROM {$temp_table} WHERE group_leader_id = %d",
				wp_get_current_user()->ID
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL
	}

	/**
	 * Get all users data
	 *
	 * @return array|object|\stdClass[]|null
	 * @deprecated
	 */
	public static function get_all_users_data() {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT temp.user_id AS ID, u.display_name, u.user_email, u.user_login, um1.meta_value AS 'first_name', um2.meta_value AS 'last_name'
FROM {$wpdb->prefix}`tbl_reporting_api_user_id` temp
JOIN {$wpdb->users} u
ON temp.user_id = u.ID AND temp.group_leader_id = %d
JOIN {$wpdb->usermeta} um1
ON temp.user_id = um1.user_id AND um1.meta_key = %s
JOIN {$wpdb->usermeta} um2
ON temp.user_id = um2.user_id AND um2.meta_key = %s
GROUP BY u.ID;",
				wp_get_current_user()->ID,
				'first_name',
				'last_name'
			)
		);
	}

	/**
	 * Get Completions
	 *
	 * @param $leader_id
	 *
	 * @return array|object|\stdClass[]|null
	 */
	public static function get_completions( $leader_id ) {
	    $cache = Cache::get( __FUNCTION__ . $leader_id );
	    if ( ! empty( $cache ) ) {
	        return $cache;
	    }

	    global $wpdb;
	    $temp_table = $wpdb->prefix . self::$tbl_reporting_api_user_id;

	    // Step 1: Get user IDs for the leader
	    $user_ids = $wpdb->get_col(
	        $wpdb->prepare(
	            "SELECT user_id
	             FROM {$temp_table}
	             WHERE group_leader_id = %d",
	            $leader_id
	        )
	    );

	    if ( empty( $user_ids ) ) {
	        Cache::create( __FUNCTION__ . $leader_id, [] );
	        return [];
	    }

	    // Step 2: Get completions for those users (in chunks if too many IDs)
	    $results = [];
	    $chunks  = array_chunk( $user_ids, 500 ); // avoid huge IN()
	    foreach ( $chunks as $chunk ) {
	        $placeholders = implode( ',', array_fill( 0, count( $chunk ), '%d' ) );
	        $query = $wpdb->prepare(
	            "SELECT post_id as course_id, user_id, activity_completed
	             FROM {$wpdb->prefix}learndash_user_activity
	             WHERE activity_type = 'course'
	             AND activity_completed IS NOT NULL
	             AND activity_completed <> 0
	             AND user_id IN ($placeholders)",
	            ...$chunk
	        );
	        $chunk_results = $wpdb->get_results( $query );
	        if ( ! empty( $chunk_results ) ) {
	            $results = array_merge( $results, $chunk_results );
	        }
	    }

	    Cache::create( __FUNCTION__ . $leader_id, $results );
	    return $results;
	}
}
