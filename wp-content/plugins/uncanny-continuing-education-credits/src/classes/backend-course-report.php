<?php

namespace uncanny_ceu;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class UncannyCeuCompletionStatus
 *
 * @package uncanny_ceu
 */
class BackendCourseReport {

	/**
	 * Reference to the CourseReport class
	 *
	 * @var CourseReport
	 */
	protected $course_report = null;

	/**
	 * UncannyCeuCompletionStatus constructor.
	 */
	public function __construct() {

		add_action( 'admin_menu', array( $this, 'completion_status_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_register_scripts' ) );

		add_action( 'generate_ceu_records_hourly', array( $this, 'generate_ceu_records_hourly' ) );

	}

	/**
	 * Add the admin menu.
	 *
	 * @return void
	 */
	public function completion_status_admin_menu() {

		$page_title  = __( 'Uncanny CEUs', 'uncanny-ceu' );
		$menu_title  = __( 'Credit Report', 'uncanny-ceu' );
		$capability  = 'manage_options';
		$menu_slug   = 'uncanny-ceu-report';
		$parent_slug = 'uncanny-ceu-report';
		$function    = array( $this, 'options_menu_page_output' );

		// Menu Icon blends into sidebar when the default admin color scheme is used
		$icon_url = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz48c3ZnIGVuYWJsZS1iYWNrZ3JvdW5kPSJuZXcgMCAwIDU4MSA2NDAiIHZlcnNpb249IjEuMSIgdmlld0JveD0iMCAwIDU4MSA2NDAiIHhtbDpzcGFjZT0icHJlc2VydmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0ibTUyNi40IDM0LjFjMC42IDUgMSAxMC4xIDEuMyAxNS4xIDAuNSAxMC4zIDEuMiAyMC42IDAuOCAzMC45LTAuNSAxMS41LTEgMjMtMi4xIDM0LjQtMi42IDI2LjctNy44IDUzLjMtMTYuNSA3OC43LTcuMyAyMS4zLTE3LjEgNDEuOC0yOS45IDYwLjQtMTIgMTcuNS0yNi44IDMzLTQzLjggNDUuOS0xNy4yIDEzLTM2LjcgMjMtNTcuMSAyOS45LTI1LjEgOC41LTUxLjUgMTIuNy03Ny45IDEzLjggNzAuMyAyNS4zIDEwNi45IDEwMi44IDgxLjYgMTczLjEtMTguOSA1Mi42LTY4LjEgODguMS0xMjQgODkuNWgtNi4xYy0xMS4xLTAuMi0yMi4xLTEuOC0zMi45LTQuNy0yOS40LTcuOS01NS45LTI2LjMtNzMuNy01MC45LTI5LjItNDAuMi0zNC4xLTkzLjEtMTIuNi0xMzgtMjUgMjUuMS00NC41IDU1LjMtNTkuMSA4Ny40LTguOCAxOS43LTE2LjEgNDAuMS0yMC44IDYxLjEtMS4yLTE0LjMtMS4yLTI4LjYtMC42LTQyLjkgMS4zLTI2LjYgNS4xLTUzLjIgMTIuMi03OC45IDUuOC0yMS4yIDEzLjktNDEuOCAyNC43LTYwLjlzMjQuNC0zNi42IDQwLjYtNTEuM2MxNy4zLTE1LjcgMzcuMy0yOC4xIDU5LjEtMzYuOCAyNC41LTkuOSA1MC42LTE1LjIgNzYuOC0xNy4yIDEzLjMtMS4xIDI2LjctMC44IDQwLjEtMi4zIDI0LjUtMi40IDQ4LjgtOC40IDcxLjMtMTguMyAyMS05LjIgNDAuNC0yMS44IDU3LjUtMzcuMiAxNi41LTE0LjkgMzAuOC0zMi4xIDQyLjgtNTAuOCAxMy0yMC4yIDIzLjQtNDIuMSAzMS42LTY0LjcgNy42LTIxLjEgMTMuNC00Mi45IDE2LjctNjUuM3ptLTI3OS40IDMyOS41Yy0xOC42IDEuOC0zNi4yIDguOC01MC45IDIwLjQtMTcuMSAxMy40LTI5LjggMzIuMi0zNi4yIDUyLjktNy40IDIzLjktNi44IDQ5LjUgMS43IDczIDcuMSAxOS42IDE5LjkgMzcuMiAzNi44IDQ5LjYgMTQuMSAxMC41IDMwLjkgMTYuOSA0OC40IDE4LjZzMzUuMi0xLjYgNTEtOS40YzEzLjUtNi43IDI1LjQtMTYuMyAzNC44LTI4LjEgMTAuNi0xMy40IDE3LjktMjkgMjEuNS00NS43IDQuOC0yMi40IDIuOC00NS43LTUuOC02Ni45LTguMS0yMC0yMi4yLTM3LjYtNDAuMy00OS4zLTE4LTExLjctMzkuNS0xNy02MS0xNS4xeiIgZmlsbD0iIzgyODc4QyIvPjxwYXRoIGQ9Im0yNDIuNiA0MDIuNmM2LjItMS4zIDEyLjYtMS44IDE4LjktMS41LTExLjQgMTEuNC0xMi4yIDI5LjctMS44IDQyIDExLjIgMTMuMyAzMS4xIDE1LjEgNDQuNCAzLjkgNS4zLTQuNCA4LjktMTAuNCAxMC41LTE3LjEgMTIuNCAxNi44IDE2LjYgMzkuNCAxMSA1OS41LTUgMTguNS0xOCAzNC42LTM1IDQzLjUtMzQuNSAxOC4yLTc3LjMgNS4xLTk1LjUtMjkuNS0xLTItMi00LTIuOS02LjEtOC4xLTE5LjYtNi41LTQzIDQuMi02MS4zIDEwLTE3IDI2LjgtMjkuMiA0Ni4yLTMzLjR6IiBmaWxsPSIjODI4NzhDIi8+PC9zdmc+';

		$position = 82; // 81 - Above Settings Menu

		// Create the main page
		add_menu_page( $page_title, $page_title, $capability, $menu_slug, $function, $icon_url, $position );

		add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
	}

	/**
	 * Maybe register scripts
	 *
	 * @param $hook
	 *
	 * @return void
	 */
	public function maybe_register_scripts( $hook ) {

		// Target Licensing page
		if ( ! strpos( $hook, 'uncanny-ceu-report' ) ) {
			return;
		}

		// Register and enqueue course report scripts.
		$this->course_report_instance()->register_scripts();
		$this->course_report_instance()->enqueue_scripts( 'admin' );

	}

	/**
	 * Output the options page
	 *
	 * @return string - HTML output
	 */
	public function options_menu_page_output() {
		$course_report = $this->course_report_instance();
		include Utilities::get_template( 'admin-course-report.php' );
	}

	/**
	 * Hourly cron job to generate CEU records
	 *
	 * @return void
	 */
	public function generate_ceu_records_hourly() {
		$this->course_report_instance()->generate_ceu_records_json();
	}

	/**
	 * Get the CourseReport instance
	 *
	 * @return CourseReport
	 */
	public function course_report_instance() {
		if ( null === $this->course_report ) {
			$this->course_report = Utilities::get_class_instance( 'CourseReport' );
		}
		return $this->course_report;
	}

	//////////////////////////////
	// TODO : REVIEW Following methods to see if they are still needed.
	// TODO : If not, remove them.
	//////////////////////////////

	/**
	 * Get the total credits for each user
	 *
	 * @return array
	 */
	public static function get_total_credits() {
		global $wpdb;
		$results = $wpdb->get_results( "SELECT user_id, SUM(credits) as total FROM `{$wpdb->prefix}uo_ceu_records` GROUP BY user_id;" );

		if ( empty( $results ) ) {
			return array();
		}

		$total_ceus = array();
		foreach ( $results as $r ) {
			$total_ceus[ $r->user_id ] = $r->total;
		}

		return $total_ceus;
	}

	/**
	 * Get array with the list of all users from the site
	 * Each array element will have a "value" element, which will contain the user id,
	 * and also will have a "text" element, which will contain the text to be shown in the option
	 *
	 * @return Array
	 * @since  2.5
	 */
	public static function get_users() {
		// Get list of all users
		$users = get_users();

		// Create array that will contain the optiosn
		$options = array();

		// Iterate users and create main array
		foreach ( $users as $user ) {
			// Add option
			$options[] = array(
				'value' => $user->ID,
				'text'  => sprintf(
					'%s::%s',
					wp_strip_all_tags( $user->display_name ),
					wp_strip_all_tags( $user->user_email )
				),
			);
		}

		// Apply filters and return
		return apply_filters( 'ucec_course_report_users', $options );
	}

}
