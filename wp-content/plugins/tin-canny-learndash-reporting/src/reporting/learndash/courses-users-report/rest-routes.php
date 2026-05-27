<?php
namespace uncanny_learndash_reporting\tincanny_reporting;

use WP_Error;

/**
 *
 */
class RestRoutes {
	/**
	 * Reporting REST Path
	 *
	 * @var string
	 */
	public static $rest_path = 'uncanny_reporting/v1';

	/**
	 *
	 */
	public function __construct() {
		//register api class
		add_action( 'rest_api_init', array( __CLASS__, 'reporting_api' ) );
	}

	/**
	 * @param $plugins
	 *
	 * @return array
	 */
	public static function disable_plugins_for_specific_rest_endpoint( $plugins ) {
		// List of plugins to keep active for this specific endpoint
		$active_plugins = array(
			'tin-canny-learndash-reporting/tin-canny-learndash-reporting.php',
			'sfwd-lms/sfwd_lms.php',
		);

		return array_intersect( $plugins, $active_plugins );
	}
	/**
	 * @return void
	 */
	public static function reporting_api() {

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			$current_endpoint = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
			if ( strpos( $current_endpoint, self::$rest_path ) !== false ) {
				add_filter( 'option_active_plugins', array( __CLASS__, 'disable_plugins_for_specific_rest_endpoint' ) );
			}
		}

		if ( ultc_filter_has_var( 'group_id' ) ) {
			\uncanny_learndash_reporting\CourseData::$isolated_group_id = absint( ultc_filter_input( 'group_id' ) );
		}

		//dashboard_data
		register_rest_route(
			self::$rest_path,
			'/dashboard_data/',
			array(
				'methods'             => 'GET',
				'callback'            => array( '\uncanny_learndash_reporting\BuildReportData', 'get_dashboard_data' ),
				'permission_callback' => array( __CLASS__, 'tincanny_permissions' ),

			)
		);

		// Call get all courses and general user data
		register_rest_route(
			self::$rest_path,
			'/courses_overview/',
			array(
				'methods'             => 'GET',
				'callback'            => array( '\uncanny_learndash_reporting\CourseData', 'get_courses_overview' ),
				'permission_callback' => array( __CLASS__, 'tincanny_permissions' ),
			)
		);

		register_rest_route(
			self::$rest_path,
			'/users_overview/',
			array(
				'methods'             => 'GET',
				'callback'            => array( '\uncanny_learndash_reporting\CourseData', 'get_users_overview' ),
				'permission_callback' => array( __CLASS__, 'tincanny_permissions' ),
			)
		);

		// Call get all courses and general user data
		register_rest_route(
			self::$rest_path,
			'/table_data/',
			array(
				'methods'             => 'POST',
				'callback'            => array( '\uncanny_learndash_reporting\TableData', 'get_table_data' ),
				'permission_callback' => array( __CLASS__, 'tincanny_permissions' ),
			)
		);

		register_rest_route(
			self::$rest_path,
			'/user_avatar/',
			array(
				'methods'             => 'POST',
				'callback'            => array( '\uncanny_learndash_reporting\UserData', 'get_user_avatar' ),
				'permission_callback' => array( __CLASS__, 'tincanny_permissions' ),
			)
		);

		register_rest_route(
			self::$rest_path,
			'/tincan_data/(?P<user_ID>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( '\uncanny_learndash_reporting\MiscFunctions', 'get_tincan_data' ),
				'permission_callback' => array( __CLASS__, 'tincanny_permissions' ),

			)
		);

		// Update it the user see the Tin Can tables
		register_rest_route(
			self::$rest_path,
			'/show_tincan/(?P<show_tincan>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( '\uncanny_learndash_reporting\MiscFunctions', 'show_tincan_tables' ),
				'permission_callback' => array( __CLASS__, 'tincanny_permissions' ),

			)
		);

		// Update it the user see the Tin Can tables
		register_rest_route(
			self::$rest_path,
			'/disable_mark_complete/(?P<disable_mark_complete>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( '\uncanny_learndash_reporting\MiscFunctions', 'disable_mark_complete' ),
				'permission_callback' => array( __CLASS__, 'tincanny_permissions' ),

			)
		);

		// Update it the user see the Tin Can tables
		register_rest_route(
			self::$rest_path,
			'/nonce_protection/(?P<nonce_protection>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( '\uncanny_learndash_reporting\MiscFunctions', 'nonce_protection' ),
				'permission_callback' => array( __CLASS__, 'tincanny_permissions' ),

			)
		);

		// Reset Tin Can Data
		register_rest_route(
			self::$rest_path,
			'/reset_tincan_data/',
			array(
				'methods'             => 'GET',
				'callback'            => array( '\uncanny_learndash_reporting\tincanny_reporting\Purge', 'reset_tincan_data' ),
				'permission_callback' => array( __CLASS__, 'tincanny_permissions' ),

			)
		);

		// Reset Quiz Data
		register_rest_route(
			self::$rest_path,
			'/reset_quiz_data/',
			array(
				'methods'             => 'GET',
				'callback'            => array( '\uncanny_learndash_reporting\tincanny_reporting\Purge', 'reset_quiz_data' ),
				'permission_callback' => array( __CLASS__, 'tincanny_permissions' ),

			)
		);

		register_rest_route(
			self::$rest_path,
			'/reset_bookmark_data/',
			array(
				'methods'             => 'GET',
				'callback'            => array( '\uncanny_learndash_reporting\tincanny_reporting\Purge', 'reset_bookmark_data' ),
				'permission_callback' => array( __CLASS__, 'tincanny_permissions' ),

			)
		);

		register_rest_route(
			self::$rest_path,
			'/purge_experienced/',
			array(
				'methods'             => 'GET',
				'callback'            => array( '\uncanny_learndash_reporting\tincanny_reporting\Purge', 'purge_experienced' ),
				'permission_callback' => array( __CLASS__, 'tincanny_permissions' ),

			)
		);

		register_rest_route(
			self::$rest_path,
			'/purge_answered/',
			array(
				'methods'             => 'GET',
				'callback'            => array( '\uncanny_learndash_reporting\tincanny_reporting\Purge', 'purge_answered' ),
				'permission_callback' => array( __CLASS__, 'tincanny_permissions' ),

			)
		);

		register_rest_route(
			self::$rest_path,
			'/purge_verb_statements/',
			array(
				'methods'             => 'POST',
				'callback'            => array( '\uncanny_learndash_reporting\tincanny_reporting\Purge', 'purge_verb_statements' ),
				'permission_callback' => array( __CLASS__, 'tincanny_permissions' ),

			)
		);
	}

	/**
	 * This is our callback function that allows access to tincanny data
	 *
	 * @return bool|\WP_Error
	 */
	public static function tincanny_permissions() {
		$capability = apply_filters( 'tincanny_can_get_data', 'manage_options' );

		// Restrict endpoint to only users who have the manage_options capability.
		if ( current_user_can( $capability ) ) {
			return true;
		}

		if ( current_user_can( 'group_leader' ) ) {
			return true;
		}

		return new WP_Error( 'rest_forbidden', esc_html__( 'You do not have the capability to view tincanny data.', 'uncanny-learndash-reporting' ) );
	}
}
