<?php
namespace uncanny_learndash_reporting\tincanny_reporting;

/**
 *
 */
class Purge {

	/**
	 * Reset Tin Can Data
	 *
	 * @return bool
	 */
	public static function reset_tincan_data() {

		if ( uotc_is_current_user_admin() ) {

			if ( class_exists( '\UCTINCAN\Database\Admin' ) ) {
				$database = new \UCTINCAN\Database\Admin();
				$database->reset();

				return true;
			}
		}

		return false;
	}

	/**
	 * Reset Quiz Data
	 *
	 * @return bool
	 */
	public static function reset_quiz_data() {

		if ( uotc_is_current_user_admin() ) {

			if ( class_exists( '\UCTINCAN\Database\Admin' ) ) {
				$database = new \UCTINCAN\Database\Admin();
				$database->reset_quiz();

				return true;
			}
		}

		return false;
	}

	/**
	 * Reset Bookmark Data
	 *
	 * @return bool
	 */
	public static function reset_bookmark_data() {

		if ( uotc_is_current_user_admin() ) {

			if ( class_exists( '\UCTINCAN\Database\Admin' ) ) {
				$database = new \UCTINCAN\Database\Admin();
				$database->reset_bookmark_data();

				return true;
			}
		}

		return false;
	}


	/**
	 * Purge Experienced
	 *
	 * @return bool
	 */
	public static function purge_experienced() {

		if ( uotc_is_current_user_admin() ) {

			if ( class_exists( '\UCTINCAN\Database\Admin' ) ) {
				// Run query
				global $wpdb;
				$wpdb->query( "DELETE FROM {$wpdb->prefix}uotincan_reporting WHERE verb = 'experienced'" );

				return true;
			}
		}

		return false;
	}

	/**
	 * Purge Answered
	 *
	 * @return bool
	 */
	public static function purge_answered() {

		if ( uotc_is_current_user_admin() ) {

			if ( class_exists( '\UCTINCAN\Database\Admin' ) ) {
				// Run query
				global $wpdb;
				$wpdb->query( "DELETE FROM {$wpdb->prefix}uotincan_reporting  WHERE verb = 'answered'" );

				return true;
			}
		}

		return false;
	}

	/**
	 * Purge Verb Statements
	 *
	 * @return bool
	 */
	public static function purge_verb_statements() {

		if ( uotc_is_current_user_admin() && isset( $_POST['uotc_verb'] ) && class_exists( '\UCTINCAN\Database\Admin' ) ) {
			// Run query
			global $wpdb;
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}uotincan_reporting WHERE verb = %s", $_POST['uotc_verb'] ) );
			return true;
		}

		return false;
	}

}
