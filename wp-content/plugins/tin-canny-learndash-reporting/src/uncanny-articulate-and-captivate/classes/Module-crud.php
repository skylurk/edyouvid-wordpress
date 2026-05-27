<?php
/**
 * Database Controller
 *
 * @package    Tin Canny Reporting for LearnDash
 * @subpackage Embed Articulate Storyline and Adobe Captivate
 * @author     Uncanny Owl
 * @since      1.0.0
 */

namespace TINCANNYSNC;

if ( ! defined( 'UO_ABS_PATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 *
 */
class Module_CRUD {
	/**
	 *
	 */
	const TBL_MODULES = 'snc_file_info';

	/**
	 * @param $name
	 *
	 * @return int
	 */
	public static function add_item( $name ) {

		global $wpdb;
		$wpdb->insert( $wpdb->prefix . \UCTINCAN\Database::TABLE_SNC_FILE_INFO, array( 'file_name' => $name ) );
		return $wpdb->insert_id;
	}

	/**
	 * Add Detail to ID
	 *
	 * @since 0.0.1
	 * @access public
	 *
	 * @changed 1.3.7 Add Subtype
	 */
	public static function add_detail( $item_id, $type, $url, $subtype, $version = UNCANNY_REPORTING_VERSION ) {
		global $wpdb;
		
		$wpdb->update(
			$wpdb->prefix . \UCTINCAN\Database::TABLE_SNC_FILE_INFO,
			array(
				'type'    => $type,
				'subtype' => $subtype,
				'url'     => $url,
				'version' => $version,
				'upload_date' => current_time( 'mysql' ),
			),
			array( 'ID' => $item_id )
		);

		// Schedule a single event to calculate size in 10 seconds
		if( ! wp_next_scheduled( 'tincanny_calculate_folder_size', array( $item_id ) ) ) {
			wp_schedule_single_event( time() + 2, 'tincanny_calculate_folder_size', array( $item_id ) );
		}
	}

	/**
	 * @param $id
	 *
	 * @return void
	 */
	public static function delete( $id ) {

		global $wpdb;
		$wpdb->delete( $wpdb->prefix . \UCTINCAN\Database::TABLE_SNC_FILE_INFO, array( 'ID' => $id ) );
	}

	/**
	 * @param $where
	 *
	 * @return array|object|\stdClass[]|null
	 */
	public static function get_modules( $where = '' ) {

		global $wpdb;
		return $wpdb->get_results( sprintf( 'SELECT * FROM %s %s ORDER BY `ID` DESC', $wpdb->prefix . \UCTINCAN\Database::TABLE_SNC_FILE_INFO, $where ), OBJECT );
	}

	/**
	 * @param $item_id
	 *
	 * @return array|false|object|\stdClass|null
	 */
	public static function get_item( $item_id ) {
		if ( ! $item_id ) {
			return false;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . \UCTINCAN\Database::TABLE_SNC_FILE_INFO;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE ID = %s;", $item_id ), ARRAY_A );
	}

	/**
	 * @param $id
	 * @param $title
	 *
	 * @return mixed
	 */
	public static function change_name_from_id( $id, $title ) {

		global $wpdb;
		$table_name    = $wpdb->prefix . \UCTINCAN\Database::TABLE_SNC_FILE_INFO;
		$title_from_db = $wpdb->get_var( $wpdb->prepare( "SELECT file_name FROM {$table_name} WHERE id = %s", $id ) );

		if ( $title_from_db != $title ) {
			$wpdb->query( $wpdb->prepare( "UPDATE {$table_name} SET file_name = '%s' WHERE id = %s", $title, $id ) );
		}

		return $title;
	}

	/**
	 * @param $search
	 * @param $limit
	 * @param $order_by
	 *
	 * @return array|object|\stdClass[]|null
	 */
	public static function get_contents( $search = '', $limit = '', $order_by = '' ) {

		global $wpdb;
		$table_name = $wpdb->prefix . \UCTINCAN\Database::TABLE_SNC_FILE_INFO;
		$where      = '';

		if ( ! empty( $search ) ) {
			$where = " WHERE file_name LIKE '%{$search}%' OR type LIKE '%{$search}%' ";
		}

		if ( empty( $order_by ) ) {
			$order_by = 'ORDER BY `ID` DESC';
		}

		return $wpdb->get_results( sprintf( "SELECT *, file_name as content FROM {$table_name} %s %s %s ", $where, $order_by, $limit ), OBJECT );
	}

	/**
	 * @param $search
	 *
	 * @return string|null
	 */
	public static function get_contents_count( $search = '' ) {

		global $wpdb;
		$table_name = $wpdb->prefix . \UCTINCAN\Database::TABLE_SNC_FILE_INFO;
		$where      = '';

		if ( ! empty( $search ) ) {
			$where = " WHERE file_name LIKE '%{$search}%' OR type LIKE '%{$search}%' ";
		}

		return $wpdb->get_var( sprintf( "SELECT COUNT(*) FROM {$table_name} %s ", $where ) );
	}

	/**
	 * Update item title by ID
	 *
	 * @since 3.2
	 * @access public
	 *
	 */
	public static function update_item_title( $item_id, $title, $version = UNCANNY_REPORTING_VERSION ) {

		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . \UCTINCAN\Database::TABLE_SNC_FILE_INFO,
			array(
				'file_name' => $title,
				'version'   => $version,
				'size'      => 0,
			),
			array( 'ID' => $item_id )
		);

		// Schedule a single event to calculate size in 2 seconds
		if( ! wp_next_scheduled( 'tincanny_calculate_folder_size', array( $item_id ) ) ) {
			wp_schedule_single_event( time() + 60, 'tincanny_calculate_folder_size', array( $item_id ) );
		}
	}
}
