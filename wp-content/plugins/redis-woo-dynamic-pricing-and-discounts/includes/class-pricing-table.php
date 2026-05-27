<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VIREDIS_Pricing_Table {
	protected static $table_name = 'viredis_product_pricing';
	/**
	 * Create table
	 */
	public static function create_table() {
		global $wpdb;
		$table = $wpdb->prefix . self::$table_name;
		$query = "CREATE TABLE IF NOT EXISTS {$table} (
                             `id` bigint(20) NOT NULL AUTO_INCREMENT,
                             `pd_id` bigint(20) NOT NULL ,
                             `parent_id` bigint(20)  ,
                             `prices` LONGTEXT,
                             `time_conditions` LONGTEXT,
                             `pd_conditions` LONGTEXT,
                             `cart_conditions` LONGTEXT,
                             `user_conditions` LONGTEXT,
                             PRIMARY KEY  (`id`)
                             )";
		$wpdb->query( $query );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
	}
	/**Insert data to table
	 * @return int|bool
	 */
	public static function insert( $pd_id, $rules = array() ) {
		if ( ! $pd_id ) {
			return false;
		}
		if ( ! isset( $rules['pd_id'] ) ) {
			$rules['pd_id'] = $pd_id;
		}
		if ( ! isset( $rules['parent_id'] ) ) {
			$rules['parent_id'] = wp_get_post_parent_id( $pd_id ) ?: 0;
		}
		if ( ! isset( $rules['prices'] ) ) {
			$rules['prices'] = wp_json_encode( array() );
		}
		if ( ! isset( $rules['time_conditions'] ) ) {
			$rules['time_conditions'] = wp_json_encode( array() );
		}
		if ( ! isset( $rules['pd_conditions'] ) ) {
			$rules['pd_conditions'] = wp_json_encode( array() );
		}
		if ( ! isset( $rules['cart_conditions'] ) ) {
			$rules['cart_conditions'] = wp_json_encode( array() );
		}
		if ( ! isset( $rules['user_conditions'] ) ) {
			$rules['user_conditions'] = wp_json_encode( array() );
		}
		global $wpdb;
		$table = $wpdb->prefix . self::$table_name;
		$wpdb->insert( $table, $rules, array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' ) );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}
	/**Update data to table
	 * @return int|bool
	 */
	public static function update( $pd_id, $rules = array() ) {
		if ( ! $pd_id ) {
			return false;
		}
		global $wpdb;
		$table = $wpdb->prefix . self::$table_name;
		if ( ! isset( $rules['prices'] ) ) {
			$rules['prices'] = wp_json_encode( array() );
		}
		if ( ! isset( $rules['time_conditions'] ) ) {
			$rules['time_conditions'] = wp_json_encode( array() );
		}
		if ( ! isset( $rules['pd_conditions'] ) ) {
			$rules['pd_conditions'] = wp_json_encode( array() );
		}
		if ( ! isset( $rules['cart_conditions'] ) ) {
			$rules['cart_conditions'] = wp_json_encode( array() );
		}
		if ( ! isset( $rules['user_conditions'] ) ) {
			$rules['user_conditions'] = wp_json_encode( array() );
		}
		$wpdb->update( $table, $rules, array( 'pd_id' => $pd_id ) );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}
	/**Get row by pd_id
	 *
	 * @param $id
	 *
	 * @return array|null|object
	 */
	public static function get_rule_by_pd_id( $pd_id ) {
		if ( ! $pd_id ) {
			return false;
		}
		global $wpdb;
		$table = $wpdb->prefix . self::$table_name;
		$query = "SELECT * FROM {$table} WHERE pd_id=%d";
		return $wpdb->get_row( $wpdb->prepare( $query, $pd_id ), ARRAY_A );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
	}
	/**Delete row
	 * @return false|int
	 */
	public static function delete( $pd_id ) {
		if ( ! $pd_id ) {
			return false;
		}
		global $wpdb;
		$table  = $wpdb->prefix . self::$table_name;
		$delete = $wpdb->delete( $table, array( 'pd_id' => $pd_id ) );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $delete;
	}
	/**Delete row
	 * @return false|int
	 */
	public static function delete_by_parent_id( $id ) {
		if ( ! $id ) {
			return false;
		}
		global $wpdb;
		$table  = $wpdb->prefix . self::$table_name;
		$delete = $wpdb->delete( $table, array( 'parent_id' => $id ) );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $delete;
	}
	/**delete all row
	 */
	public static function delete_all() {
		global $wpdb;
		$table = $wpdb->prefix . self::$table_name;
		$wpdb->query( "TRUNCATE TABLE {$table}" );// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}