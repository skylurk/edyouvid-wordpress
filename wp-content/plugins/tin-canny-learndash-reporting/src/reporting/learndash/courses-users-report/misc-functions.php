<?php
namespace uncanny_learndash_reporting;

use LearnDash_Custom_Label;
use UCTINCAN\Database\Admin;

/**
 *
 */
class MiscFunctions {

	/**
	 * @return array
	 */
	public static function get_labels() {
		$labels['course']  = LearnDash_Custom_Label::get_label( 'course' );
		$labels['courses'] = LearnDash_Custom_Label::get_label( 'courses' );

		$labels['lesson']  = LearnDash_Custom_Label::get_label( 'lesson' );
		$labels['lessons'] = LearnDash_Custom_Label::get_label( 'lessons' );

		$labels['topic']  = LearnDash_Custom_Label::get_label( 'topic' );
		$labels['topics'] = LearnDash_Custom_Label::get_label( 'topics' );

		$labels['quiz']    = LearnDash_Custom_Label::get_label( 'quiz' );
		$labels['quizzes'] = LearnDash_Custom_Label::get_label( 'quizzes' );

		return $labels;
	}


	/**
	 * @return array
	 */
	public static function get_links() {
		$labels = array();

		$labels['profile']    = admin_url( 'user-edit.php' );
		$labels['assignment'] = admin_url( 'post.php' );

		return $labels;
	}

	/**
	 * @param $data
	 *
	 * @return array
	 */
	public static function get_tincan_data( $data ) {
		$return_object = array();

		if ( ! current_user_can( apply_filters( 'uo_tincanny_reporting_capability', 'tincanny_reporting' ) ) ) {
			$return_object['message'] = __( 'Current User doesn\'t have permissions to Tin Can report data', 'uncanny-learndash-reporting' );
			$return_object['user_ID'] = get_current_user_id();

			return $return_object;
		}

		// validate inputs
		$user_id = absint( $data['user_ID'] );

		// if any of the values are 0 then they didn't validate, storage is not possible
		if ( 0 === $user_id ) {
			$return_object['message'] = 'invalid user id supplied';
			$return_object['user_ID'] = $data['user_ID'];

			return $return_object;
		}

		//      global $wpdb;
		$group_course_ids = array();
		$leader_groups    = UserData::get_administrators_group_ids();
		if ( ! empty( $leader_groups ) ) {
			foreach ( $leader_groups as $group_id ) {
				$__courses = learndash_group_enrolled_courses( $group_id );
				if ( ! empty( $__courses ) ) {
					foreach ( $__courses as $__course_id ) {
						$group_course_ids[] = $__course_id;
					}
				}
			}
		}

		if ( empty( $group_course_ids ) ) {
			return array();
		}
		$group_course_ids = array_map( 'absint', $group_course_ids );
		$group_course_ids = array_unique( $group_course_ids );

		$tin_can_data = null;
		if ( class_exists( '\UCTINCAN\Database\Admin' ) ) {
			$database          = new Admin();
			$database->user_id = $user_id;
			$tin_can_data      = $database->get_data();
		}

		if ( null !== $tin_can_data && ! empty( $tin_can_data ) ) {

			$data = array();
			//$sample = array();
			//$sample['All'] = $tin_can_data;
			foreach ( $tin_can_data as $user_single_tin_can_object ) {

				$tc_course_id = (int) $user_single_tin_can_object['course_id'];
				$tc_lesson_id = (int) $user_single_tin_can_object['lesson_id'];

				if ( 'group_leader' === UserData::get_user_role() || uotc_is_current_user_admin() ) {
					if ( ! in_array( $tc_course_id, $group_course_ids, true ) ) {
						continue;
					}
				}

				if ( $user_single_tin_can_object['lesson_id'] && $user_single_tin_can_object['course_id'] ) {

					if ( ! isset( $data[ $tc_course_id ] ) ) {
						$data[ $tc_course_id ] = array();
					}
					if ( ! isset( $data[ $tc_course_id ][ $tc_lesson_id ] ) ) {
						$data[ $tc_course_id ][ $tc_lesson_id ] = array();
					}
					$tc_course_id = (int) $user_single_tin_can_object['course_id'];
					$tc_lesson_id = (int) $user_single_tin_can_object['lesson_id'];
					array_push( $data[ $tc_course_id ][ $tc_lesson_id ], $user_single_tin_can_object );

				}
			}

			return array(
				'user_ID'          => $user_id,
				'tinCanStatements' => $data,
			);

		}

		return array();
	}

	/**
	 * @param $data
	 *
	 * @return string
	 */
	public static function show_tincan_tables( $data ) {
		if ( ! uotc_is_current_user_admin() ) {
			return 'no permissions';
		}

		$show_tincan_tables = absint( $data['show_tincan'] );
		$value              = 1 === $show_tincan_tables ? 'yes' : 'no';
		$updated            = update_option( 'show_tincan_reporting_tables', $value );

		return $value;
	}

	/**
	 * @param $data
	 *
	 * @return string
	 */
	public static function disable_mark_complete( $data ) {
		if ( ! uotc_is_current_user_admin() ) {
			return 'no permissions';
		}

		$disable_mark_complete = absint( $data['disable_mark_complete'] );

		if ( 1 === $disable_mark_complete ) {
			$value = 'yes';
		}
		if ( 0 === $disable_mark_complete ) {
			$value = 'no';
		}
		if ( 3 === $disable_mark_complete ) {
			$value = 'hide';
		}
		if ( 4 === $disable_mark_complete ) {
			$value = 'remove';
		}
		if ( 5 === $disable_mark_complete ) {
			$value = 'autoadvance';
		}

		$updated = update_option( 'disable_mark_complete_for_tincan', $value );

		return $value;
	}

	/**
	 * @param $data
	 *
	 * @return mixed|string
	 */
	public static function nonce_protection( $data ) {
		if ( ! uotc_is_current_user_admin() ) {
			return 'no permissions';
		}

		$nonce_protection = absint( $data['nonce_protection'] );
		$value            = 1 === $nonce_protection ? 'yes' : 'no';
		$updated          = update_option( 'tincanny_nonce_protection', $value );

		// Check if the user chose not to protect the content.
		if ( 'no' === $value ) {
			Boot::delete_protection_htaccess();
		}

		return $value;
	}
}
