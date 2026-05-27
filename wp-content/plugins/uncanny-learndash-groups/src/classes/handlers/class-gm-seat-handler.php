<?php


namespace uncanny_learndash_groups;

/**
 * Class Group_Codes_Handler
 *
 * @package uncanny_learndash_groups
 */
class Group_Management_Seat_Handler {

	/**
	 * @var
	 */
	public static $instance;

	/**
	 * @return Group_Management_Seat_Handler
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @param int $count
	 *
	 * @return false|mixed|void
	 */
	public function get_per_seat_text( $count = 1 ) {
		$seats_label_singular = get_option( 'ulgm_per_seat_text', __( 'Seat', 'uncanny-learndash-groups' ) );
		$seats_label_plural   = get_option( 'ulgm_per_seat_text_plural', __( 'Seats', 'uncanny-learndash-groups' ) );

		return 1 === absint( $count ) ? $seats_label_singular : $seats_label_plural;
	}

	/**
	 * @param $user_id
	 * @param $group_id
	 *
	 * @return string|null
	 */
	public function if_user_redeemed_code( $user_id, $group_id ) {
		global $wpdb;
		$code_group_id = $wpdb->get_var( $wpdb->prepare( 'SELECT ID FROM ' . $wpdb->prefix . ulgm()->db->tbl_group_details . ' WHERE ld_group_id = %d', $group_id ) );
		if ( empty( $code_group_id ) ) {
			return null;
		}

		return $wpdb->get_var( $wpdb->prepare( 'SELECT ID FROM ' . $wpdb->prefix . ulgm()->db->tbl_group_codes . ' WHERE student_id = %d AND group_id = %d LIMIT 0,1', $user_id, $code_group_id ) );
	}

	/**
	 * @param $ld_group_id
	 *
	 * @return int
	 */
	public function total_seats( $ld_group_id ) {
		global $wpdb;
		$group_id = $this->get_code_group_id( $ld_group_id );

		if ( empty( $group_id ) || 0 === absint( $group_id ) ) {
			return 0;
		}

		$total_seats   = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->prefix}" . ulgm()->db->tbl_group_codes . ' WHERE group_id = %d', $group_id ) );
		$deleted_seats = 0;
		if ( true === apply_filters( 'ulgm_add_deleted_seats_to_tota_count', true, $ld_group_id ) ) {
			$deleted_seats = $this->deleted_seats( $ld_group_id );
		}

		return absint( $total_seats ) + $deleted_seats;
	}

	/**
	 * @param $ld_group_id
	 *
	 * @return string|null
	 */
	public function get_code_group_id( $ld_group_id ) {
		global $wpdb;
		$ld_group_id = $this->get_real_ld_group_id( $ld_group_id );

		return $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->prefix}" . ulgm()->db->tbl_group_details . ' WHERE ld_group_id = %d', $ld_group_id ) );
	}

	/**
	 * @param $ld_group_id
	 *
	 * @return false|mixed|void
	 */
	public function get_real_ld_group_id( $ld_group_id ) {
		$original_ld_id = $ld_group_id;

		if ( SharedFunctions::is_a_parent_group( $ld_group_id ) ) {
			return $ld_group_id;
		}

		// check if enabled
		$ancestors = get_post_ancestors( $ld_group_id );
		if ( empty( $ancestors ) ) {
			return $ld_group_id;
		}

		foreach ( $ancestors as $ancestor ) {
			if ( ! SharedFunctions::is_a_parent_group( $ancestor ) ) {
				continue;
			}

			// Parent group found
			$ld_group_id = $ancestor;
			break;
		}

		// global setting is enabled
		if ( true === SharedFunctions::is_pool_seats_enabled_for_all_groups() ) {
			return $ld_group_id;
		}

		//check if individual parent group has setting
		if ( true === SharedFunctions::is_pool_seats_enabled_for_current_parent_group( $ld_group_id, false ) ) {
			return $ld_group_id;
		}

		// yes, return new parent ID
		return $original_ld_id;
	}

	/**
	 * @param $ld_group_id
	 *
	 * @return int
	 */
	public function available_seats( $ld_group_id ) {
		return $this->remaining_seats( $ld_group_id );
	}

	/**
	 * @param $ld_group_id
	 *
	 * @return int
	 */
	public function remaining_seats( $ld_group_id ) {

		$seats_remaining = 0;

		$ld_group_id = absint( $ld_group_id );
		if ( empty( $ld_group_id ) ) {
			return $seats_remaining;
		}

		$group_id = $this->get_code_group_id( $ld_group_id );

		if ( empty( $group_id ) || 0 === absint( $group_id ) ) {
			return $seats_remaining;
		}

		// If group leaders don't use seats, calculate remaining seats based on user count instead of database
		if ( 'yes' === (string) get_option( 'group_leaders_dont_use_seats', 'no' ) ) {
			$total_seats     = $this->total_seats( $ld_group_id );
			$enrolled_seats  = ulgm()->group_management->count_users_enrolled_in_group_for_seats( $ld_group_id ) + ulgm()->group_management->users_invited_in_group( $ld_group_id );
			$seats_remaining = max( 0, $total_seats - $enrolled_seats );
		} else {
			// Use database count when group leaders do use seats
			global $wpdb;
			$qry             = $wpdb->prepare( 'SELECT COUNT(ID) AS remaining FROM ' . $wpdb->prefix . ulgm()->db->tbl_group_codes . ' WHERE student_id IS NULL AND user_email IS NULL AND group_id = %d', $group_id );
			$seats_remaining = $wpdb->get_var( $qry );
		}

		return absint( $seats_remaining );
	}

	/**
	 * @param $ld_group_id
	 *
	 * @return int
	 */
	public function deleted_seats( $ld_group_id ) {

		$deleted_seats = 0;

		$ld_group_id = absint( $ld_group_id );
		if ( empty( $ld_group_id ) ) {
			return $deleted_seats;
		}
		$ld_group_id = $this->get_real_ld_group_id( $ld_group_id );
		global $wpdb;
		$qry = $wpdb->prepare( "SELECT COUNT(post_id) AS deleted FROM $wpdb->postmeta WHERE post_id = %d AND meta_key LIKE 'user-id-removed-completed-status-%%%'", $ld_group_id );

		return absint( $wpdb->get_var( $qry ) );
	}

	/**
	 * @param $to_remove
	 * @param $ld_group_id
	 *
	 * @return array
	 */
	public function remove_seats( $to_remove, $ld_group_id ) {

		$existing_seats = (int) $this->total_seats( $ld_group_id );

		$group_title = get_the_title( $ld_group_id );

		$per_seat_text = strtolower( SharedFunctions::get_per_seat_text( $to_remove ) );

		global $wpdb;

		// Check if there's key group
		$code_group_id = $this->get_code_group_id( $ld_group_id );

		if ( empty( $code_group_id ) ) {
			return array(
				'result'  => false,
				'message' => sprintf( __( 'Uncanny Groups &mdash; Unable to remove %1$s. Group %2$s is not linked to this order.', 'uncanny-learndash-groups' ), $per_seat_text, $group_title ),
			);
		}

		$fetch_code_count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(code) AS available FROM ' . $wpdb->prefix . SharedFunctions::$db_group_codes_tbl . ' WHERE group_id = %d AND student_id IS NULL LIMIT %d', $code_group_id, $to_remove ) );

		// If to remove is more than available seats OR total seats != to remove (full refund)
		if ( empty( $fetch_code_count ) || ( $fetch_code_count < $to_remove ) && absint( $to_remove ) !== absint( $existing_seats ) ) {
			return array(
				'result'  => false,
				'message' => sprintf( __( 'Uncanny Groups &mdash; Unable to remove %1$s from the %2$s. Available count is less than %3$d.', 'uncanny-learndash-groups' ), $per_seat_text, $group_title, $to_remove ),
			);
		}

		//difference seats are empty, lets delete them;
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $wpdb->prefix . SharedFunctions::$db_group_codes_tbl . ' WHERE group_id = %d AND student_id IS NULL LIMIT %d', $code_group_id, $to_remove ) );

		update_post_meta( $ld_group_id, '_ulgm_total_seats', $existing_seats - $to_remove );

		do_action( 'ulgm_seats_removed', $to_remove, $ld_group_id, $code_group_id );

		return array(
			'result'  => true,
			'message' => sprintf( __( 'Uncanny Groups &mdash; Successfully removed %1$d %2$s from %3$s.', 'uncanny-learndash-groups' ), $to_remove, $per_seat_text, $group_title ),
		);
	}
}
