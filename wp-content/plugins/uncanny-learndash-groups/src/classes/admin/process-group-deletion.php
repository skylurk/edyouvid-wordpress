<?php

namespace uncanny_learndash_groups;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class ProcessManualGroup
 * @package uncanny_learndash_groups
 */
class ProcessGroupDeletion {
	/**
	 * ProcessManualGroup constructor.
	 */
	public function __construct() {
		//remove group related data from custom tables
		add_action( 'deleted_post', array( __CLASS__, 'remove_related_groups_data' ), PHP_INT_MIN, 2 );
	}

	/**
	 * @param $post_id
	 * @param null $post
	 *
	 * @return void
	 */
	public static function remove_related_groups_data( $post_id, $post = null ) {
		if ( ! $post_id ) {
			return;
		}

		// Get the post type
		$post_type = get_post_type( $post_id );

		if ( 'groups' !== $post_type ) {
			return;
		}

		global $wpdb;

		$group_detail_id = ulgm()->group_management->seat->get_code_group_id( $post_id );
		if ( ! is_numeric( $group_detail_id ) ) {
			return;
		}

		// Delete from group details table
		$wpdb->delete(
			$wpdb->prefix . ulgm()->db->tbl_group_details,
			array(
				'ID' => $group_detail_id,
			),
			array( '%d' )
		);

		// Delete from group codes table
		$wpdb->delete(
			$wpdb->prefix . ulgm()->db->tbl_group_codes,
			array(
				'group_id' => $group_detail_id,
			),
			array( '%d' )
		);
	}
}
