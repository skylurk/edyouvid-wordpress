<?php

namespace uncanny_learndash_reporting;

/**
 *
 */
class TinCannyShortcode {
	/**
	 * @var
	 */
	public static $is_course_report;
	/**
	 * @var
	 */
	public static $is_user_report;
	/**
	 * @var
	 */
	public static $is_tincan_report;
	/**
	 * @var
	 */
	public static $is_xapi_report;
	/**
	 * Per page option
	 *
	 * @var array
	 */
	public static $template_data = array();
	/**
	 * Style CSS string
	 *
	 * @var string
	 */
	public static $tincan_show = '';
	/**
	 * Queried Groups
	 *
	 * @var array
	 */
	public static $groups_query;
	/**
	 * Current Group
	 *
	 * @var int
	 */
	public static $isolated_group = 0;
	/**
	 * Database Class
	 *
	 * @var \UCTINCAN\Database\Admin
	 */
	public static $tincan_database;
	/**
	 * @var array|mixed|null
	 */
	public static $tin_can_table_columns = array();
	/**
	 * Per page option
	 *
	 * @var int
	 */
	public static $tincan_opt_per_pages;
	/**
	 * XAPI Report Columns
	 *
	 * @var array
	 */
	public static $xapi_report_columns = array();

	/**
	 * @var mixed|string|null
	 */
	public static $current_report_tab = 'uncanny-learnDash-reporting';

	/**
	 * @var bool
	 */
	public static $is_independent_shortcode = true;

	/**
	 *
	 */
	public function __construct() {

		self::$tin_can_table_columns = apply_filters(
			'tincan_table_columns',
			array(
				array(
					'title'    => __( 'Group', 'uncanny-learndash-reporting' ),
					'data'     => 'group_name',
					'sortable' => false,
					'className' => 'tclr-group-name'
				),
				array(
					'title' => __( 'User', 'uncanny-learndash-reporting' ),
					'data' => 'user_name',
					'className' => 'tclr-user-name'
				),
				array(
					'title'    => __( 'Course', 'uncanny-learndash-reporting' ),
					'data'     => 'course_name',
					'sortable' => false,
					'className' => 'tclr-course-name'
				),
				array(
					'title' => __( 'Module', 'uncanny-learndash-reporting' ),
					'data' => 'module_name',
					'className' => 'tclr-module-name'
				),
				array(
					'title' => __( 'Target', 'uncanny-learndash-reporting' ),
					'data' => 'target_name',
					'className' => 'tclr-target-name'
				),
				array(
					'title' => __( 'Action', 'uncanny-learndash-reporting' ),
					'data' => 'verb',
					'className' => 'tclr-action'
				),
				array(
					'title' => __( 'Result', 'uncanny-learndash-reporting' ),
					'data' => 'result',
					'className' => 'tclr-result'
				),
				array(
					'title' => __( 'Success', 'uncanny-learndash-reporting' ),
					'data' => 'completion',
					'className' => 'tclr-success'
				),
				array(
					'title' => __( 'Date Time', 'uncanny-learndash-reporting' ),
					'data' => 'xstored',
					'className' => 'tclr-date-time'
				),
			)
		);

		self::$xapi_report_columns = apply_filters(
			'xapi_quiz_table_columns',
			array(
				array(
					'title'    => __( 'Group', 'uncanny-learndash-reporting' ),
					'data'     => 'group_name',
					'sortable' => false,
					'className' => 'tclr-group-name'
				),
				array(
					'title' => __( 'User', 'uncanny-learndash-reporting' ),
					'data' => 'user_name',
					'className' => 'tclr-user-name'
				),
				array(
					'title'    => __( 'Course', 'uncanny-learndash-reporting' ),
					'data'     => 'course_name',
					'sortable' => false,
					'className' => 'tclr-course-name'
				),
				array(
					'title' => __( 'Module', 'uncanny-learndash-reporting' ),
					'data' => 'module_name',
					'className' => 'tclr-module-name'
				),
				array(
					'title' => __( 'Question', 'uncanny-learndash-reporting' ),
					'data' => 'question',
					'className' => 'tclr-question'
				),
				array(
					'title'    => __( 'Result', 'uncanny-learndash-reporting' ),
					'data'     => 'result',
					'sortable' => false,
					'className' => 'tclr-result'
				),
				array(
					'title'    => __( 'Score', 'uncanny-learndash-reporting' ),
					'data'     => 'score',
					'sortable' => false,
					'className' => 'tclr-score'
				),
				array(
					'title' => __( 'Date Time', 'uncanny-learndash-reporting' ),
					'data' => 'xstored',
					'className' => 'tclr-date-time'
				),
				array(
					'title'    => __( 'Choices', 'uncanny-learndash-reporting' ),
					'data'     => 'choices',
					'sortable' => false,
					'className' => 'tclr-choices'
				),
				array(
					'title'    => __( 'Correct Response', 'uncanny-learndash-reporting' ),
					'data'     => 'correct_response',
					'sortable' => false,
					'className' => 'tclr-correct-response'
				),
				array(
					'title'    => __( 'User Response', 'uncanny-learndash-reporting' ),
					'data'     => 'user_response',
					'sortable' => false,
					'className' => 'tclr-user-response'
				),
			)
		);

		add_action(
			'init',
			function () {
				if ( ultc_filter_has_var( 'page' ) && strpos( ultc_filter_input( 'page' ), 'tincanny' ) !== false ) {
					self::$current_report_tab = ultc_get_filter_var( 'page' );
				} elseif ( ultc_filter_has_var( 'tab' ) && strpos( ultc_filter_input( 'tab' ), 'tincanny' ) !== false ) {
					self::$current_report_tab = ultc_get_filter_var( 'tab' );
				}
			}
		);

		add_filter(
			'uo_tincanny_reporting_tincanny_data',
			array(
				__CLASS__,
				'filter_tincanny_reporting_tincanny_data_filter',
			),
			99,
			1
		);

		add_filter(
			'tincanny_reporting_tabs', array(
			__CLASS__,
			'add_course_user_tabs',
		),
			1
		);

		add_filter(
			'tincanny_reporting_tabs', array(
				__CLASS__,
				'add_tincan_xapi_tabs',
			)
		);

		add_action(
			'tincanny_reporting_wrapper_after_tabs', array(
				__CLASS__,
				'add_admin_social_icons',
			)
		);

		// Main shortcode to display all the reports
		add_shortcode( 'tincanny', array( __CLASS__, 'frontend_tincanny' ) );
		// Shortcodes for Course report
		add_shortcode( 'tincanny_course_report', array( __CLASS__, 'tincanny_course_report_func' ) );
		// Shortcodes for User report
		add_shortcode( 'tincanny_user_report', array( __CLASS__, 'tincanny_user_report_func' ) );
		// Shortcodes for Tin Can Report
		add_shortcode( 'tincanny_tin_can_report', array( __CLASS__, 'tincanny_tin_can_report_func' ) );
		// Shortcodes for XAPI Quiz Report
		add_shortcode( 'tincanny_xapi_quiz_report', array( __CLASS__, 'tincanny_xapi_quiz_report_func' ) );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'scripts' ), 99999 );

		if ( 'uncanny-learnDash-reporting' === ultc_get_filter_var( 'page', '' ) ) {
			add_filter( 'set_screen_option_xapi_report_columns', array( $this, 'filter__set_screen_option' ), 10, 3 );
		}

		add_action( 'init', array( __CLASS__, 'tincan_change_per_page' ) );

		// load columns settings from user meta.
		add_filter( 'screen_options_show_screen', '__return_true' );
		add_filter( 'screen_settings', array( $this, 'filter__screen_settings' ), 10, 2 );

		add_action( 'rest_api_init', array( __CLASS__, 'tincanny_tin_can_xapi_report_rest_api_init' ) );
	}

	/**
	 * @return void
	 */
	public static function scripts() {
		if ( self::has_tincanny_shortcode() ) {
			ReportingAdminMenu::frontend_tincanny_block();
		}
	}

	/**
	 * @param $atts
	 *
	 * @return array|false|string|string[]
	 */
	public static function tincanny_course_report_func( $atts = array() ) {
		$atts = shortcode_atts( array(), $atts, 'tincanny_course_report' );

		ob_start();

		if ( false === self::frontend_reports_access_check() ) {
			return ob_get_clean();
		}

		self::$current_report_tab = 'uncanny-learnDash-reporting';

		self::course_report_page();

		$output = ob_get_clean();

		return str_replace( "id='module'", '', $output );
	}

	/**
	 * @param $atts
	 *
	 * @return array|false|string|string[]
	 */
	public static function tincanny_user_report_func( $atts = array() ) {
		$atts = shortcode_atts( array(), $atts, 'tincanny_user_report' );

		ob_start();

		if ( false === self::frontend_reports_access_check() ) {
			return ob_get_clean();
		}

		self::$current_report_tab = 'uncanny-tincanny-user-report';

		self::course_report_page();

		$output = ob_get_clean();

		return str_replace( "id='module'", '', $output );
	}

	/**
	 * @param $atts
	 *
	 * @return array|false|string|string[]
	 */
	public static function tincanny_tin_can_report_func( $atts = array() ) {
		$atts = shortcode_atts( array(), $atts, 'tincanny_tin_can_report' );

		ob_start();

		if ( false === self::frontend_reports_access_check() ) {
			return ob_get_clean();
		}

		self::$current_report_tab = 'uncanny-tincanny-tin-can-report';

		self::tincan_report_page();

		$output = ob_get_clean();

		return str_replace( "id='module'", '', $output );
	}

	/**
	 * @param $atts
	 *
	 * @return array|false|string|string[]
	 */
	public static function tincanny_xapi_quiz_report_func( $atts = array() ) {
		$atts = shortcode_atts( array(), $atts, 'tincanny_xapi_quiz_report' );

		ob_start();

		if ( false === self::frontend_reports_access_check() ) {
			return ob_get_clean();
		}

		self::$current_report_tab = 'uncanny-tincanny-xapi-quiz-report';

		self::xapi_report_page();

		$output = ob_get_clean();

		return str_replace( "id='module'", '', $output );
	}

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	public static function filter_tincanny_reporting_tincanny_data_filter( $data ) {

		$data['tinCanReportColumns'] = self::$tin_can_table_columns;
		$data['xAPIReportColumns']   = self::$xapi_report_columns;

		return $data;
	}

	/**
	 * @return void
	 */
	public static function tincanny_tin_can_xapi_report_rest_api_init() {
		register_rest_route(
			\uncanny_learndash_reporting\tincanny_reporting\RestRoutes::$rest_path,
			'/tin_can_report_data/',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'tincanny_tin_can_report_get_tincan_report_data' ),
				'permission_callback' => array(
					'\uncanny_learndash_reporting\tincanny_reporting\RestRoutes',
					'tincanny_permissions',
				),
			)
		);
		register_rest_route(
			\uncanny_learndash_reporting\tincanny_reporting\RestRoutes::$rest_path,
			'/xapi_quiz_report_data/',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'tincanny_xapi_quiz_report_get_report_data' ),
				'permission_callback' => array(
					'\uncanny_learndash_reporting\tincanny_reporting\RestRoutes',
					'tincanny_permissions',
				),
			)
		);
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public static function tincanny_tin_can_report_get_tincan_report_data( \WP_REST_Request $request ) {
		$params = $request->get_params();

		// Extract pagination parameters from request
		$start  = isset( $params['start'] ) ? intval( $params['start'] ) : 0;
		$length = isset( $params['length'] ) ? intval( $params['length'] ) : 10; // Default to 10 if not set

		self::$tincan_database = new \UCTINCAN\Database\Admin();
		self::set_tin_can_filters( $request );

		$results = self::$tincan_database->get_tincan_data( $start, $length, false );

		// Total records in the database
		$recordsTotal = self::$tincan_database->get_tincan_data( null, null, true );

		// Simulate filtered results (in a real app, apply search filters here)
		//$recordsFiltered = count($results);

		// Return paginated results to DataTables
		return new \WP_REST_Response(
			array(
				'draw'            => intval( $params['draw'] ),
				'recordsTotal'    => (int) 20000000,
				'recordsFiltered' => (int) $recordsTotal,
				'data'            => $results, // Return only the requested page
			),
			200
		);
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public static function tincanny_xapi_quiz_report_get_report_data( \WP_REST_Request $request ) {
		$params = $request->get_params();

		// Extract pagination parameters from request
		$start  = isset( $params['start'] ) ? intval( $params['start'] ) : 0;
		$length = isset( $params['length'] ) ? intval( $params['length'] ) : 10; // Default to 10 if not set

		self::$tincan_database = new \UCTINCAN\Database\Admin();

		self::set_tin_can_filters( $request );

		$results = self::$tincan_database->get_xapi_data( $start, $length, false );

		// Total records in the database
		$recordsTotal = self::$tincan_database->get_xapi_data( null, null, true );

		// Simulate filtered results (in a real app, apply search filters here)
		//$recordsFiltered = count($results);

		// Return paginated results to DataTables
		return new \WP_REST_Response(
			array(
				'draw'            => intval( $params['draw'] ),
				'recordsTotal'    => (int) 20000000,
				'recordsFiltered' => (int) $recordsTotal,
				'data'            => $results, // Return only the requested page
			),
			200
		);
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return void
	 */
	public static function set_tin_can_filters( \WP_REST_Request $request ) {
		// Order
		if ( $request->has_param( 'order' ) && ! empty( $request->get_param( 'order' ) ) ) {
			$order_array                    = $request->get_param( 'order' );
			$order_array                    = is_array( $order_array ) ? array_shift( $order_array ) : array();
			self::$tincan_database->orderby = $order_array['column'] ?? 'xstored';
			self::$tincan_database->order   = $order_array['dir'] ?? 'desc';
		}

		// Group
		if ( $request->has_param( 'tc_filter_group' ) && ! empty( $request->get_param( 'tc_filter_group' ) ) ) {
			self::$tincan_database->group = sanitize_text_field( $request->get_param( 'tc_filter_group' ) );
		}

		// Downloading CSV
		if ( $request->has_param( 'is_csv' ) && 1 === absint( $request->get_param( 'is_csv' ) ) ) {
			self::$tincan_database->is_csv = 'yes';
		}

		// Actor
		if ( $request->has_param( 'tc_filter_user' ) && ! empty( $request->get_param( 'tc_filter_user' ) ) ) {
			self::$tincan_database->actor = sanitize_text_field( $request->get_param( 'tc_filter_user' ) );
		}

		// Course
		if ( $request->has_param( 'tc_filter_course' ) && ! empty( $request->get_param( 'tc_filter_course' ) ) ) {
			self::$tincan_database->course = sanitize_text_field( $request->get_param( 'tc_filter_course' ) );
		}

		// Lesson
		if ( $request->has_param( 'tc_filter_lesson' ) && ! empty( $request->get_param( 'tc_filter_lesson' ) ) ) {
			self::$tincan_database->lesson = sanitize_text_field( $request->get_param( 'tc_filter_lesson' ) );
		}

		// Module
		if ( $request->has_param( 'tc_filter_module' ) && ! empty( $request->get_param( 'tc_filter_module' ) ) ) {
			self::$tincan_database->module = sanitize_text_field( $request->get_param( 'tc_filter_module' ) );
		}

		// Action
		if ( $request->has_param( 'tc_filter_action' ) && ! empty( $request->get_param( 'tc_filter_action' ) ) ) {
			self::$tincan_database->verb = strtolower( sanitize_text_field( $request->get_param( 'tc_filter_action' ) ) );
		}

		// Verb/Questions
		if ( $request->has_param( 'tc_filter_quiz' ) && ! empty( $request->get_param( 'tc_filter_quiz' ) ) ) {
			self::$tincan_database->question = strtolower( sanitize_text_field( $request->get_param( 'tc_filter_quiz' ) ) );
		}

		// Result
		if ( $request->has_param( 'tc_filter_results' ) && ! empty( $request->get_param( 'tc_filter_results' ) ) ) {
			self::$tincan_database->results = strtolower( sanitize_text_field( $request->get_param( 'tc_filter_results' ) ) );
		}

		// Date
		if ( $request->has_param( 'tc_filter_date_range' ) && ! empty( $request->get_param( 'tc_filter_date_range' ) ) ) {
			$date_range   = apply_filters( 'uo_tincanny_tincan_xapi_report_date_range_type', sanitize_text_field( $request->get_param( 'tc_filter_date_range' ) ) );
			$current_time = current_time( 'timestamp' ); // Current timestamp

			switch ( $date_range ) {
				case 'last':
					if ( $request->has_param( 'tc_filter_date_range_last' ) && ! empty( $request->get_param( 'tc_filter_date_range_last' ) ) ) {
						$date_range_last = apply_filters( 'uo_tincanny_tincan_xapi_report_date_range_last_type', sanitize_text_field( $request->get_param( 'tc_filter_date_range_last' ) ) );
						switch ( $date_range_last ) {
							case 'week':
								self::$tincan_database->dateStart = date( 'Y-m-d 00:00:00', strtotime( 'last week Monday', $current_time ) );
								self::$tincan_database->dateEnd   = date( 'Y-m-d 23:59:59', strtotime( 'last week Sunday', $current_time ) );
								break;
							case 'month':
								self::$tincan_database->dateStart = date( 'Y-m-d 00:00:00', strtotime( 'first day of last month', $current_time ) );
								self::$tincan_database->dateEnd   = date( 'Y-m-d 23:59:59', strtotime( 'last day of last month', $current_time ) );
								break;
							case '30days':
								self::$tincan_database->dateStart = date( 'Y-m-d 00:00:00', strtotime( '-30 days', $current_time ) );
								self::$tincan_database->dateEnd   = date( 'Y-m-d 23:59:59', $current_time );
								break;
							case '3months':
								self::$tincan_database->dateStart = date( 'Y-m-d 00:00:00', strtotime( '-3 months', $current_time ) );
								self::$tincan_database->dateEnd   = date( 'Y-m-d 23:59:59', $current_time );
								break;
							case '6months':
								self::$tincan_database->dateStart = date( 'Y-m-d 00:00:00', strtotime( '-6 months', $current_time ) );
								self::$tincan_database->dateEnd   = date( 'Y-m-d 23:59:59', $current_time );
								break;
							case '1year':
								self::$tincan_database->dateStart = date( 'Y-m-d 00:00:00', strtotime( '-1 year', $current_time ) );
								self::$tincan_database->dateEnd   = date( 'Y-m-d 23:59:59', $current_time );
								break;
						}
					}
					break;

				case 'from':
					if ( $request->has_param( 'tc_filter_start' ) && ! empty( $request->get_param( 'tc_filter_start' ) ) ) {
						self::$tincan_database->dateStart = sanitize_text_field( $request->get_param( 'tc_filter_start' ) );
					}

					if ( $request->has_param( 'tc_filter_end' ) && ! empty( $request->get_param( 'tc_filter_end' ) ) ) {
						self::$tincan_database->dateEnd = sanitize_text_field( $request->get_param( 'tc_filter_end' ) ) . ' 23:59:59';
					}
					break;
				case 'custom':
					self::$tincan_database->dateStart = apply_filters( 'uo_tincanny_tincan_xapi_report_date_start', sanitize_text_field( $request->get_param( 'tc_filter_start' ) ) );
					self::$tincan_database->dateEnd   = apply_filters( 'uo_tincanny_tincan_xapi_report_date_end', sanitize_text_field( $request->get_param( 'tc_filter_end' ) ) );
					break;
			}
		}
	}

	/**
	 * @return void
	 */
	public static function add_admin_social_icons() {
		if ( is_admin() ) { ?>

			<div class="tclr-admin-nav-social-icons">
				<a href="https://www.facebook.com/UncannyOwl/" target="_blank"
				   class="tclr-admin-nav-social-icon tclr-admin-nav-social-icon--facebook"
				   tclr-tooltip-admin="<?php esc_html_e( 'Follow us on Facebook', 'uncanny-learndash-reporting' ); ?>">
					<span class="tincanny-icon tincanny-icon-facebook"></span>
				</a>
				<a href="https://twitter.com/UncannyOwl" target="_blank"
				   class="tclr-admin-nav-social-icon tclr-admin-nav-social-icon--twitter"
				   tclr-tooltip-admin="<?php esc_html_e( 'Follow us on Twitter', 'uncanny-learndash-reporting' ); ?>">
					<span class="tincanny-icon tincanny-icon-twitter"></span>
				</a>
				<a href="https://www.linkedin.com/company/uncannyowl" target="_blank"
				   class="tclr-admin-nav-social-icon tclr-admin-nav-social-icon--linkedin"
				   tclr-tooltip-admin="<?php esc_html_e( 'Follow us on LinkedIn', 'uncanny-learndash-reporting' ); ?>">
					<span class="tincanny-icon tincanny-icon-linkedin"></span>
				</a>
			</div>

		<?php }
	}

	/**
	 * Get file part path
	 *
	 * @param string $file_name File name must be prefixed with a \ (foreword slash)
	 *
	 * @return string
	 */
	public static function get_part( $file_name ) {

		$asset_uri = dirname( UO_REPORTING_FILE ) . '/src/reporting/learndash/templates/' . $file_name;
		$asset_uri = apply_filters( 'tinccanny_get_part_path', $asset_uri, $file_name );

		return $asset_uri;
	}

	/**
	 * @param $tabs
	 *
	 * @return object[]
	 */
	public static function add_course_user_tabs( $tabs ) {
		// Get "course" label
		$course_label = esc_attr_x( 'Course', 'Reporting tab', 'uncanny-learndash-reporting' );

		if ( class_exists( '\LearnDash_Custom_Label' ) ) {
			$course_label = \LearnDash_Custom_Label::get_label( 'course' );
		}

		$base_url = ReportingAdminMenu::get_base_url();

		$mode        = get_option( 'tincanny_user_report_default_group', 'all' );
		$query_param = array();

		if ( ! isset( $_GET['group_id'] ) ) {
			$query_param['group_id'] = $mode;
		} else {
			$query_param['group_id'] = $_GET['group_id'];
		}

		$base_url = $base_url . '&' . http_build_query( $query_param );

		// phpcs:disable WordPress.WP.GlobalVariablesOverride
		$tabs = array(
			(object) array(
				'id'         => 'courseReportTab',
				'page'       => 'uncanny-learnDash-reporting',
				'href'       => "$base_url&tab=courseReportTab",
				'href_front' => "?tab=courseReportTab&group_id=all",
				'name'       => sprintf(
				// translators: %s is the "Course" label
					_x( '%s Report', '%s is the "Course" label', 'uncanny-learndash-reporting' ),
					$course_label
				),
			),
			(object) array(
				'id'         => 'userReportTab',
				'page'       => 'uncanny-tincanny-user-report',
				'href'       => admin_url( 'admin.php?page=uncanny-tincanny-user-report&' ) . http_build_query( $query_param ),
				'href_front' => "?tab=uncanny-tincanny-user-report&group_id=all",
				'name'       => __( 'User Report', 'uncanny-learndash-reporting' ),
			),
		);

		return $tabs;
	}

	/**
	 * @return void
	 * @deprecated 5.0
	 */
	public static function add_report_specific_templates() {
		// Check if the parameter exists and has a valid value
		if (
			ultc_filter_has_var( 'tab' ) &&
			in_array(
				ultc_filter_input( 'tab' ),
				array(
					'userReportTab',
					'tin-can',
					'xapi-tincan',
				),
				true
			)
		) {
			// Set current tab
			$current_tab = ultc_filter_input( 'tab' );
		} else {
			// If the tab parameter is not defined, or it is, but it's not one of the values in
			// the in_array function, then use the default tab
			$current_tab = 'courseReportTab';
		}

		self::$is_course_report = 'courseReportTab' === $current_tab;
		self::$is_user_report   = 'userReportTab' === $current_tab;
		self::$is_tincan_report = 'tin-can' === $current_tab;
		self::$is_xapi_report   = 'xapi-tincan' === $current_tab;

		// Add Course report tab content.
		if ( self::$is_course_report || self::$is_user_report ) {
			include self::get_part( 'course-report-tab.php' );
		}
		// Add User report tab content.
		if ( self::$is_user_report ) {
			include self::get_part( 'user-report-tab.php' );
		}
	}

	/**
	 * @return void
	 */
	public static function add_header_and_tabs() {

		$tabs = apply_filters( 'tincanny_reporting_tabs', array() );
		// Add admin header and tabs

		// Check for the "page" get parameter to activate the correct tab
		foreach ( $tabs as $tab ) {
			if ( self::$current_report_tab === $tab->page ) {
				$tab_active = $tab->id;
				break;
			}
		}

		// Define classes
		$css_classes   = array();
		$css_classes[] = is_admin() ? 'tclr-header--admin' : 'tclr-header--frontend';

		include __DIR__ . '/header.php';
	}

	/**
	 * @param $tabs
	 *
	 * @return void
	 */
	public static function add_tincan_xapi_tabs( $tabs ) {
		// Get Tin Canny settings
		$tincanny_settings = \TINCANNYSNC\Admin\Options::get_options();
		$base_url          = ReportingAdminMenu::get_base_url();
		$mode              = get_option( 'tincanny_user_report_default_group', 'all' );
		$query_param       = array();

		if ( ! isset( $_GET['group_id'] ) ) {
			$query_param['group_id'] = $mode;
		} else {
			$query_param['group_id'] = $_GET['group_id'];
		}

		$query_params = http_build_query( $query_param );

		// Add "Tin Can Report" only in the admin section
		if ( '1' === (string) $tincanny_settings['tinCanActivation'] ) {
			$tabs[] = (object) array(
				'id'         => 'tin-can',
				'page'       => 'uncanny-tincanny-tin-can-report',
				'href'       => admin_url( "admin.php?page=uncanny-tincanny-tin-can-report&$query_params" ),
				'href_front' => "?tab=uncanny-tincanny-tin-can-report",
				'name'       => __( 'Tin Can Report', 'uncanny-learndash-reporting' ),
			);
			if ( ! is_admin() && ( ! isset( $tincanny_settings['enableTinCanReportFrontEnd'] ) || 1 !== (int) $tincanny_settings['enableTinCanReportFrontEnd'] ) ) {
				array_pop( $tabs );
			}
			$tabs[] = (object) array(
				'id'         => 'xapi-tincan',
				'page'       => 'uncanny-tincanny-xapi-quiz-report',
				'href'       => admin_url( "admin.php?page=uncanny-tincanny-xapi-quiz-report&$query_params" ),
				'href_front' => "?tab=uncanny-tincanny-xapi-quiz-report",
				'name'       => __( 'xAPI Quiz Report', 'uncanny-learndash-reporting' ),
			);
			if ( ! is_admin() && ( ! isset( $tincanny_settings['enablexapiReportFrontEnd'] ) || 1 !== (int) $tincanny_settings['enablexapiReportFrontEnd'] ) ) {
				array_pop( $tabs );
			}

		}

		return $tabs;
	}

	/**
	 * @return void
	 * @depecated 5.0
	 */
	public static function admin_reporting_content() {
		?>

		<div class="uo-admin-reporting-tab-single" id="tin-can"
			 style="display: <?php echo self::$is_tincan_report ? 'block' : 'none'; ?>">

			<?php
			// Add Tin Can tab content.
			if ( self::$is_tincan_report ) {
				do_action( 'tincanny_reporting_tin_can_after_begin' );

				include dirname( UO_REPORTING_FILE ) . '/src/reporting/tin-can/templates/tc-tincan-filter.php';

				do_action( 'tincanny_reporting_tin_can_before_end' );
			}
			?>

		</div>

		<div class="uo-admin-reporting-tab-single" id="xapi-tincan"
			 style="display: <?php echo self::$is_xapi_report ? 'block' : 'none'; ?>">

			<?php
			// Add xAPI tab content.
			if ( self::$is_xapi_report ) {
				do_action( 'tincanny_reporting_xtin_quiz_after_begin' );

				self::show_tincan_list_table( 'xapi-tincan' );

				do_action( 'tincanny_reporting_xtin_quiz_before_end' );
			}
			?>

		</div>

		<?php
	}

	/**
	 * Only limited to tin can report and xapi tin can report
	 * @return void
	 */
//	public static function tincanny_reporting_content() {
//		if ( is_admin() ) {
//			self::admin_reporting_content();
//
//			return;
//		}
//
//		self::frontend_reporting_content();
//	}

	/**
	 * @return void
	 * @depecated 5.0
	 */
	public static function frontend_reporting_content() {
		// Get Tin Canny settings
		$tincanny_settings = \TINCANNYSNC\Admin\Options::get_options();

		if ( isset( $tincanny_settings['enableTinCanReportFrontEnd'] ) ) {
			if ( 1 === (int) $tincanny_settings['enableTinCanReportFrontEnd'] ) {
				?>
				<div class="uo-admin-reporting-tab-single" id="tin-can"
					 style="display: <?php echo self::$is_tincan_report ? 'block' : 'none'; ?>">

					<?php
					// Add Tin Can tab content.
					if ( self::$is_tincan_report ) {

						do_action( 'tincanny_reporting_tin_can_after_begin' );
						?>

						<div class="reporting-datatable__table">
							<?php self::show_tincan_list_table( 'tin-can' ); ?>
						</div>

						<?php
						do_action( 'tincanny_reporting_tin_can_before_end' );
					}
					?>

				</div>

				<?php
			}
		}
		if ( isset( $tincanny_settings['enablexapiReportFrontEnd'] ) ) {
			if ( 1 === (int) $tincanny_settings['enablexapiReportFrontEnd'] ) {
				?>
				<div class="uo-admin-reporting-tab-single" id="xapi-tincan"
					 style="display: <?php echo self::$is_xapi_report ? 'block' : 'none'; ?>">

					<?php
					// Add xAPI tab content.
					if ( self::$is_xapi_report ) {
						do_action( 'tincanny_reporting_xtin_quiz_after_begin' );
						?>

						<div class="reporting-datatable__table">
							<?php self::show_tincan_list_table( 'xapi-tincan' ); ?>
						</div>

						<?php
						do_action( 'tincanny_reporting_tin_can_before_end' );
					}
					?>

				</div>
				<?php
			}
		}
	}

	/**
	 * @return void
	 */
	public static function tincanny_reporting_wrapper_ld_course_info() {
		if ( ! defined( 'LEARNDASH_VERSION' ) ) {
			return;

		}
		if ( function_exists( 'learndash_is_active_theme' ) && learndash_is_active_theme( 'ld30' ) ) {
			$icon = LEARNDASH_LMS_PLUGIN_URL . 'themes/legacy/templates/images/statistics-icon-small.png';
			?>

			<style>
				.statistic_icon {
					background: url(<?php echo esc_attr( $icon ); ?>) no-repeat scroll 0 0 transparent;
					width: 23px;
					height: 23px;
					margin: auto;
					background-size: 23px;
				}
			</style>

			<?php
		}

		$filepath = \SFWD_LMS::get_template( 'learndash_template_script.js', null, null, true );

		if ( ! empty( $filepath ) ) {

			wp_register_script( 'uo_learndash_template_script_js', learndash_template_url_from_path( $filepath ), array(), '1.0', true );

			$data            = array();
			$data['ajaxurl'] = admin_url( 'admin-ajax.php' );
			$data            = array( 'json' => wp_json_encode( $data ) );
			wp_localize_script( 'uo_learndash_template_script_js', 'sfwd_data', $data );

			wp_enqueue_script( 'uo_learndash_template_script_js' );
		}

		\LD_QuizPro::showModalWindow();

		self::$template_data['labels'] = array(
			'course'      => \LearnDash_Custom_Label::get_label( 'course' ),
			'courses'     => \LearnDash_Custom_Label::get_label( 'courses' ),
			'lessons'     => \LearnDash_Custom_Label::get_label( 'lessons' ),
			'topics'      => \LearnDash_Custom_Label::get_label( 'topics' ),
			'quizzes'     => \LearnDash_Custom_Label::get_label( 'quizzes' ),
			'assignments' => __( 'Assignments', 'learndash' ),
		);

		?>
		<script>

			// Move the statistics container just before the closing body tag
			jQuery(document).on('click', 'a.user_statistic', function (event) {
				// Move user overlay
				jQuery('#wpProQuiz_user_overlay').appendTo(jQuery('body'));
			});

		</script>
		<?php
	}

	/**
	 * Add tincany via shortcode on the frontend
	 *
	 * @return string
	 */
	public static function frontend_tincanny() {
		self::$is_independent_shortcode = false;

		$output = '';

		switch ( self::$current_report_tab ) {
			case 'uncanny-learnDash-reporting':
				$output = self::tincanny_course_report_func();
				break;
			case 'uncanny-tincanny-user-report':
				$output = self::tincanny_user_report_func();
				break;
			case 'uncanny-tincanny-tin-can-report':
				$output = self::tincanny_tin_can_report_func();
				break;
			case 'uncanny-tincanny-xapi-quiz-report':
				$output = self::tincanny_xapi_quiz_report_func();
				break;
		}

		return str_replace( "id='module'", '', $output );
	}

	/**
	 * @return void
	 */
	public static function course_report_page() {

		$user_can_view_all_reports = apply_filters( 'tincanny_view_all_reports_permission', uotc_is_current_user_admin() );

		$is_group_leader = learndash_is_group_leader_user( get_current_user_id() );

		if ( $user_can_view_all_reports ) {
			$group_ids = array();
		} elseif ( $is_group_leader ) {
			$group_ids = learndash_get_administrators_group_ids( get_current_user_id() );
		} else {
			echo esc_html( __( 'This report is only accessible by group leaders and administrators.', 'uncanny-learndash-reporting' ) );

			return;
		}

		if ( empty( $group_ids ) && ! $user_can_view_all_reports ) {
			echo esc_html( __( 'Group Leader has no groups assigned.', 'uncanny-learndash-reporting' ) );

			return;
		} else {
			$groups = get_posts(
				array(
					'numberposts' => 99999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts
					'include'     => $group_ids,
					'post_type'   => 'groups',
					'orderby'     => 'title',
					'order'       => 'ASC',
				)
			);
			if ( $groups && ! $user_can_view_all_reports && ! $is_group_leader ) {

				$gl_ids = array();
				foreach ( $groups as $__group ) {
					$gl__users              = learndash_get_groups_administrators( $__group->ID );
					$gl_ids[ $__group->ID ] = array();
					foreach ( $gl__users as $rr ) {
						$gl_ids[ $__group->ID ][] = $rr->ID;
					}
				}

				foreach ( $groups as $key => $__groups ) {
					if ( ! in_array( get_current_user_id(), $gl_ids[ $__groups->ID ], true ) ) {
						unset( $groups[ $key ] );
					}
				}
			}
		}

		self::$groups_query = $groups;

		if ( ultc_filter_has_var( 'group_id' ) ) {
			self::$isolated_group = absint( ultc_filter_input( 'group_id' ) );
		}

		$context = 'frontend';

		if ( is_admin() ) {
			$context = 'uncanny-learnDash-reporting' === ultc_get_filter_var( 'page', '' ) ? 'plugin' : 'dashboard';
		}


		// Add CSS classes to main container
		$css_classes   = array();
		$css_classes[] = sprintf( 'uo-reporting--%s', $context );

		include dirname( UO_REPORTING_FILE ) . '/src/reporting/learndash/templates/course-user-wrapper.php';
	}

	/**
	 * @return void
	 */
	public static function tincan_report_page() {

		// Options - Restrict for group leader
		$show_tincan = get_option( 'show_tincan_reporting_tables', 'yes' );

		if ( 'no' === $show_tincan ) {
			return;
		}

		$user_can_view_all_reports = apply_filters( 'tincanny_view_all_reports_permission', uotc_is_current_user_admin() );

		$is_group_leader = learndash_is_group_leader_user( get_current_user_id() );

		if ( $user_can_view_all_reports ) {
			$group_ids = array();
		} elseif ( $is_group_leader ) {
			$group_ids = learndash_get_administrators_group_ids( get_current_user_id() );
		} else {
			echo esc_html( __( 'This report is only accessible by group leaders and administrators.', 'uncanny-learndash-reporting' ) );

			return;
		};
		include dirname( UO_REPORTING_FILE ) . '/src/reporting/tin-can/tin-can-report.php';
	}

	/**
	 * @return void
	 */
	public static function xapi_report_page() {

		// Options - Restrict for group leader
		$show_tincan = get_option( 'show_tincan_reporting_tables', 'yes' );

		if ( 'no' === $show_tincan ) {
			return;
		}

		$user_can_view_all_reports = apply_filters( 'tincanny_view_all_reports_permission', uotc_is_current_user_admin() );

		$is_group_leader = learndash_is_group_leader_user( get_current_user_id() );

		if ( $user_can_view_all_reports ) {
			$group_ids = array();
		} elseif ( $is_group_leader ) {
			$group_ids = learndash_get_administrators_group_ids( get_current_user_id() );
		} else {
			echo esc_html( __( 'This report is only accessible by group leaders and administrators.', 'uncanny-learndash-reporting' ) );

			return;
		};

		include dirname( UO_REPORTING_FILE ) . '/src/reporting/xapi-quiz/xapi-quiz-report.php';
	}

	/**
	 * @return mixed|null
	 */
	public static function frontend_reports_access_check() {

		if ( ! is_user_logged_in() ) {
			echo esc_html__( 'You must be logged in to view this report.', 'uncanny-learndash-reporting' );

			return apply_filters( 'uo_tincanny_reporting_access_check', false );
		}

		if ( ! current_user_can( apply_filters( 'uo_tincanny_reporting_capability', 'tincanny_reporting' ) ) ) {
			echo esc_html__( 'You do not have access to this report', 'uncanny-learndash-reporting' );

			return apply_filters( 'uo_tincanny_reporting_access_check', false );
		}

		return apply_filters( 'uo_tincanny_reporting_access_check', true );
	}

	/**
	 * Create Theme Options page
	 * @depecated 5.0
	 */
	public static function options_menu_page_output() {

//		if ( ! is_user_logged_in() ) {
//			echo esc_html__( 'You must be logged in to view this report.', 'uncanny-learndash-reporting' );
//
//			return;
//		}
//
//		if ( ! current_user_can( apply_filters( 'uo_tincanny_reporting_capability', 'tincanny_reporting' ) ) ) {
//			echo esc_html__( 'You do not have access to this report', 'uncanny-learndash-reporting' );
//
//			return;
//		}


		// Get context
		// Values:
		// - dashboard:  WP Admin main page
		// - plugin:     The Tin Canny Dashboard page
		// - frontend:   Frontend
//		$context = 'frontend';
//
//		if ( is_admin() ) {
//			$context = 'uncanny-learnDash-reporting' === ultc_get_filter_var( 'page', '' ) ? 'plugin' : 'dashboard';
//		}
//
//		// Add CSS classes to main container
//		$css_classes   = array();
//		$css_classes[] = sprintf( 'uo-reporting--%s', $context );

//		include __DIR__ . '/frontend.php';

		// Get Tin Canny settings
//		$tincanny_settings = \TINCANNYSNC\Admin\Options::get_options();
//		// Check if the parameter exists and has a valid value
//		if ( ultc_filter_has_var( 'tab' ) && in_array( ultc_filter_input( 'tab' ), array(
//				'userReportTab',
//				'tin-can',
//				'xapi-tincan',
//			), true ) ) {
//			// Set current tab
//			$current_tab = ultc_filter_input( 'tab' );
//		} else {
//			// If the tab parameter is not defined, or it is, but it's not one of the values in
//			// the in_array function, then use the default tab
//			$current_tab = 'courseReportTab';
//		}
//
//		$is_course_report = 'courseReportTab' === $current_tab;
//		$is_user_report   = 'userReportTab' === $current_tab;
//		$is_tincan_report = 'tin-can' === $current_tab;
//		$is_xapi_report   = 'xapi-tincan' === $current_tab;


//		$user_can_view_all_reports = apply_filters( 'tincanny_view_all_reports_permission', uotc_is_current_user_admin() );
//		$is_group_leader           = learndash_is_group_leader_user( get_current_user_id() );
//		if ( $user_can_view_all_reports ) {
//			$group_ids = array();
//		} elseif ( $is_group_leader ) {
//			$group_ids = learndash_get_administrators_group_ids( get_current_user_id() );
//		} else {
//			echo esc_html( __( 'This report is only accessible by group leaders and administrators.', 'uncanny-learndash-reporting' ) );
//
//			return;
//		}
//
//		if ( empty( $group_ids ) && ! $user_can_view_all_reports ) {
//			echo esc_html( __( 'Group Leader has no groups assigned.', 'uncanny-learndash-reporting' ) );
//
//			return;
//		} else {
//			$groups = get_posts(
//				array(
//					'numberposts' => 9999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts
//					'include'     => $group_ids,
//					'post_type'   => 'groups',
//					'orderby'     => 'title',
//					'order'       => 'ASC',
//				)
//			);
//			if ( $groups && ! $user_can_view_all_reports && ! $is_group_leader ) {
//
//				$gl_ids = array();
//				foreach ( $groups as $__group ) {
//					$gl__users              = learndash_get_groups_administrators( $__group->ID );
//					$gl_ids[ $__group->ID ] = array();
//					foreach ( $gl__users as $rr ) {
//						$gl_ids[ $__group->ID ][] = $rr->ID;
//					}
//				}
//
//				foreach ( $groups as $key => $__groups ) {
//					if ( ! in_array( get_current_user_id(), $gl_ids[ $__groups->ID ], true ) ) {
//						unset( $groups[ $key ] );
//					}
//				}
//			}
//		}
//
//		self::$groups_query = $groups;
//
//		if ( ultc_filter_has_var( 'group_id' ) ) {
//			self::$isolated_group = absint( ultc_filter_input( 'group_id' ) );
//		}

	}

	/**
	 * @return array|bool
	 * @depecated 5.0
	 */
	public static function patchData_xapi() {
//		$data = array();
//		if ( 'xapi-tincan' !== ultc_get_filter_var( 'tab', '' ) ) {
//			return $data;
//		}
//		if ( ! is_admin() ) {
//			// phpcs:disable WordPress.Security.NonceVerification.Recommended
//			if ( ! isset( $_REQUEST['paged'] ) ) {
//				$_REQUEST['paged'] = explode( '/page/', sanitize_text_field( $_SERVER['REQUEST_URI'] ), 2 );
//				if ( isset( $_REQUEST['paged'][1] ) ) {
//					list( $_REQUEST['paged'], ) = explode( '/', sanitize_text_field( $_REQUEST['paged'][1] ), 2 );
//				}
//				if ( isset( $_REQUEST['paged'] ) && $_REQUEST['paged'] != '' ) {
//					$_REQUEST['paged'] = intval( $_REQUEST['paged'] );
//					if ( $_REQUEST['paged'] < 2 ) {
//						$_REQUEST['paged'] = '';
//					}
//				} else {
//					$_REQUEST['paged'] = '';
//				}
//			}
//			self::$tincan_database->paged = isset( $_REQUEST['paged'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['paged'] ) ) : 1;
//			// phpcs:enable WordPress.Security.NonceVerification.Recommended
//		} else {
//			self::$tincan_database->paged = absint( ultc_get_filter_var( 'paged', 1 ) );
//		}
//		$tincan_post_types = array(
//			'sfwd-courses',
//			'sfwd-lessons',
//			'sfwd-topic',
//			'sfwd-quiz',
//			'sfwd-certificates',
//			'sfwd-assignment',
//			'groups',
//		);
//
//		self::SetOrder();
//
//		if ( 'list' === ultc_get_filter_var( 'tc_filter_mode', '' ) ) {
//			self::SetTcFilters();
//
//			$data = self::$tincan_database->get_xapi_data( self::$tincan_opt_per_pages );
//		}
//
//		foreach ( $data as &$row ) {
//			$lesson = get_post( $row['lesson_id'] );
//			if ( ! empty( $lesson ) && in_array( $lesson->post_type, $tincan_post_types, true ) ) {
//				$group_link = admin_url( "post.php?post={$row[ 'group_id' ]}&action=edit" );
//				$group_name = $row['group_name'];
//				$group      = sprintf( '<a href="%s">%s</a>', $group_link, $group_name );
//
//				$course_link = admin_url( "post.php?post={$row[ 'course_id' ]}&action=edit" );
//				$course_name = $row['course_name'];
//				$course      = sprintf( '<a href="%s">%s</a>', $course_link, $course_name );
//			} else {
//				$group  = __( 'n/a', 'uncanny-learndash-reporting' );
//				$course = __( 'n/a', 'uncanny-learndash-reporting' );
//			}
//
//			$row['group']  = $group;
//			$row['user']   = sprintf( '<a href="%s">%s</a>', admin_url( "user-edit.php?user_id={$row[ 'user_id' ]}" ), $row['user_name'] );
//			$row['course'] = $course;
//			$row['module'] = sprintf( '<a href="%s">%s</a>', self::make_absolute( $row['module'], site_url() ), $row['module_name'] );
//			//$row[' Target '] = sprintf( '<a href="%s">%s</a>', self::make_absolute( $row['target'], site_url() ), $row['target_name'] );
//			$row['question'] = ucfirst( $row['activity_name'] );
//
//			$result     = $row['result'];
//			$completion = false;
//
//			if ( ! is_null( $row['result'] ) ) {
//				$completion = ( $row['result'] > 0 ) ? 'Correct' : 'Incorrect';
//			} else {
//				$completion = 'Incorrect';
//			}
//
//			$result = $row['result'];
//
//			if ( isset( $row['minimum'] ) ) {
//				if ( ! is_null( $row['result'] ) && $row['minimum'] ) {
//					$result = $row['result'] . ' / ' . $row['minimum'];
//				}
//			}
//
//			$row['score']            = (int) $result;
//			$row['result']           = $completion;
//			$row['success']          = $completion;
//			$row['more-info']        = '<a href="javascript::void(0);" onclick="jQuery(\'#other_details_' . $row['id'] . '\').show();">Show details</a><p style="display: none" id="other_details_' . $row['id'] . '"><strong>Choices:</strong> ' . $row['available_responses'] . '<br/><strong>Correct Answer:</strong> ' . $row['correct_response'] . '<br/><strong>User\'s Answer:</strong> ' . $row['user_response'] . '</p>';
//			$row['date-time']        = $row['xstored'];
//			$row['choices']          = $row['available_responses'];
//			$row['correct-response'] = $row['correct_response'];
//			$row['user-response']    = $row['user_response'];
//			$row                     = apply_filters( 'tincanny_row_data', $row );
//		}
//
//		return $data;
	}

	/**
	 * @return false|int
	 * @deprecated 5.0
	 */
	public static function patchNumRows_xapi() {
		if ( 'list' === ultc_get_filter_var( 'tc_filter_mode', '' ) ) {
			return self::$tincan_database->get_count_xapi();
		}

		return 0;
	}

	/**
	 * @return false|int
	 */
//	public static function patchNumRows() {
//		if ( 'list' === ultc_get_filter_var( 'tc_filter_mode', '' ) ) {
//			return self::$tincan_database->get_count();
//		}
//
//		return 0;
//	}

	/**
	 * @param $which
	 *
	 * @return void
	 * @depecated 5.0
	 */
	public static function ExtraTableNav_xapi( $which ) {
		switch ( $which ) {
			case 'top':
				self::ExtraTableNavTop_xapi();
				break;
			case 'bottom':
				self::ExtraTableNavBottom_xapi();
				break;
		}
	}

	//! Search Box

	/**
	 * @param $which
	 *
	 * @return void
	 * @depecated 5.0
	 */
//	public static function ExtraTableNav( $which ) {
//		switch ( $which ) {
//			case 'top':
//				self::ExtraTableNavTop();
//				break;
//			case 'bottom':
//				self::ExtraTableNavBottom();
//				break;
//		}
//	}

	/**
	 * @return void
	 * @deprecated 5.0
	 */
	public static function execute_csv_export() {
		include_once dirname( __FILE__ ) . '/uncanny-tincan/uncanny-tincan.php';

		self::$tincan_database = new \UCTINCAN\Database\Admin();

		self::SetTcFilters();
		self::SetOrder();

		$data = self::$tincan_database->get_data( 0, 'csv' );

		new \UCTINCAN\Admin\CSV( $data );
	}


	//! Search Box XAPI

	/**
	 * @return array|bool
	 * @depecated 5.0
	 */
//	public static function patchData() {
//		$data = array();
//		if ( 'tin-can' !== ultc_get_filter_var( 'tab', '' ) ) {
//			return $data;
//		}
//		if ( ! is_admin() ) {
//			// phpcs:disable WordPress.Security.NonceVerification.Recommended
//			if ( ! isset( $_REQUEST['paged'] ) ) {
//				$_REQUEST['paged'] = explode( '/page/', sanitize_text_field( $_SERVER['REQUEST_URI'] ), 2 );
//				if ( isset( $_REQUEST['paged'][1] ) ) {
//					list( $_REQUEST['paged'], ) = explode( '/', sanitize_text_field( $_REQUEST['paged'][1] ), 2 );
//				}
//				if ( isset( $_REQUEST['paged'] ) && $_REQUEST['paged'] != '' ) {
//					$_REQUEST['paged'] = intval( $_REQUEST['paged'] );
//					if ( $_REQUEST['paged'] < 2 ) {
//						$_REQUEST['paged'] = '';
//					}
//				} else {
//					$_REQUEST['paged'] = '';
//				}
//			}
//			self::$tincan_database->paged = isset( $_REQUEST['paged'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['paged'] ) ) : 1;
//			// phpcs:enable WordPress.Security.NonceVerification.Recommended
//		} else {
//			self::$tincan_database->paged = ultc_get_filter_var( 'paged', 1 );
//		}
//		$tincan_post_types = array(
//			'sfwd-courses',
//			'sfwd-lessons',
//			'sfwd-topic',
//			'sfwd-quiz',
//			'sfwd-certificates',
//			'sfwd-assignment',
//			'groups',
//		);
//
//		self::SetOrder();
//
//		if ( 'list' === ultc_get_filter_var( 'tc_filter_mode', '' ) ) {
//			self::SetTcFilters();
//
//			$data = self::$tincan_database->get_data( self::$tincan_opt_per_pages );
//		}
//
//		foreach ( $data as &$row ) {
//			$lesson = get_post( $row['lesson_id'] );
//
//			if ( is_object( $lesson ) && in_array( $lesson->post_type, $tincan_post_types, true ) ) {
//				$group_link = admin_url( "post.php?post={$row[ 'group_id' ]}&action=edit" );
//				$group_name = $row['group_name'];
//				$group      = sprintf( '<a href="%s">%s</a>', $group_link, $group_name );
//
//				$course_link = admin_url( "post.php?post={$row[ 'course_id' ]}&action=edit" );
//				$course_name = $row['course_name'];
//				$course      = sprintf( '<a href="%s">%s</a>', $course_link, $course_name );
//			} else {
//
//				$group  = __( 'n/a', 'uncanny-learndash-reporting' );
//				$course = __( 'n/a', 'uncanny-learndash-reporting' );
//
//				if ( $row['course_id'] > 0 && '' !== $row['course_name'] ) {
//					$course_link = admin_url( "post.php?post={$row[ 'course_id' ]}&action=edit" );
//					$course_name = $row['course_name'];
//					$course      = sprintf( '<a href="%s">%s</a>', $course_link, $course_name );
//				}
//			}
//
//			$row['group']  = $group;
//			$row['user']   = sprintf( '<a href="%s">%s</a>', admin_url( "user-edit.php?user_id={$row[ 'user_id' ]}" ), $row['user_name'] );
//			$row['course'] = $course;
//			$row['module'] = sprintf( '<a href="%s">%s</a>', self::make_absolute( $row['module'], site_url() ), $row['module_name'] );
//			$row['target'] = sprintf( '<a href="%s">%s</a>', self::make_absolute( $row['target'], site_url() ), $row['target_name'] );
//			$row['action'] = ucfirst( $row['verb'] );
//
//			$result = $row['result'];
//
//			if ( ! is_null( $row['result'] ) && $row['minimum'] ) {
//				$result = $row['result'] . ' / ' . $row['minimum'];
//			}
//
//			$completion = false;
//
//			if ( ! is_null( $row['completion'] ) ) {
//				$completion = ( $row['completion'] ) ? '<span class="dashicons dashicons-yes"></span>' : '<span class="dashicons dashicons-no"></span>';
//			}
//
//			$row['result']    = '<span class="tclr-reporting-datatable__no-wrap">' . $result . '</span>';
//			$row['success']   = $completion;
//			$row['date-time'] = $row['xstored'];
//			$row              = apply_filters( 'tincanny_row_data', $row );
//		}
//
//		return $data;
//	}

	/**
	 * @return void
	 * @depecated 5.0
	 */
	public static function execute_csv_export_xapi() {
//		include_once dirname( __FILE__ ) . '/uncanny-tincan/uncanny-tincan.php';
//
//		self::$tincan_database = new \UCTINCAN\Database\Admin();
//
//		self::SetTcFilters();
//		self::SetOrder();
//
//		$data = self::$tincan_database->get_xapi_data( 0, 'csv-xapi' );
//
//		new \UCTINCAN\Admin\CSV( $data );
	}

	/**
	 * @return void
	 */
	public static function tincan_change_per_page() {
		$per_page = ultc_get_filter_var( 'per_page', '' );
		if ( ! empty( $per_page ) ) {
			update_user_meta( get_current_user_id(), 'ucTinCan_per_page', $per_page );
		}
	}

	/**
	 * Remove 'Success' column.
	 *
	 * @return array
	 */
	public static function tincan_remove_success_column( $columns ) {

		if ( is_array( $columns ) && ! empty( $columns ) ) {
			$key = array_search( 'Success', $columns, false ); // phpcs:ignore WordPress.PHP.StrictInArray.FoundNonStrictFalse
			if ( false !== $key ) {
				unset( $columns[ $key ] );
			}
		}

		return $columns;
	}



	// Number of Data

	/**
	 * @param $url
	 * @param $base
	 *
	 * @return string
	 * @depecated 5.0
	 */
	public static function make_absolute( $url, $base ) {
		// Return base if no url
		if ( ! $url ) {
			return $base;
		}

		// Return if already absolute URL
		if ( ! empty( wp_parse_url( $url, PHP_URL_SCHEME ) ) ) {
			return $url;
		}

		// Urls only containing query or anchor
		if ( '#' === $url[0] || '?' === $url[0] ) {
			return $base . $url;
		}

		// Parse base URL and convert to local variables: $scheme, $host, $path, $port
		$parsed_url = wp_parse_url( $base );
		// If no path, use /
		$path   = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '/';
		$host   = isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';
		$scheme = isset( $parsed_url['scheme'] ) ? $parsed_url['scheme'] : '';
		$port   = isset( $parsed_url['port'] ) ? ':' . $parsed_url['port'] : ''; // Include port if it exists

		// Dirty absolute URL
		$abs = "$host$port$path/$url";

		// Replace '//' or '/./' or '/foo/../' with '/'
		$re  = array( '#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#' );
		$abs = preg_replace( $re, '/', $abs, - 1, $n );

		// Absolute URL is ready!
		return $scheme . '://' . str_replace( '//', '/', $abs );
	}

	/**
	 * Check if any of the Tincanny shortcodes exist on the page.
	 *
	 * @return bool
	 */
	private static function has_tincanny_shortcode() {
		global $post;

		if ( ! is_a( $post, 'WP_Post' ) ) {
			return false;
		}

		$shortcodes = array(
			'tincanny',
			'tincanny_course_report',
			'tincanny_user_report',
			'tincanny_tin_can_report',
			'tincanny_xapi_quiz_report',
		);

		foreach ( $shortcodes as $shortcode ) {
			if ( has_shortcode( $post->post_content, $shortcode ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param $current_tab
	 * @param $column_settings
	 *
	 * @return void
	 * @depecated 5.0
	 */
	private static function show_tincan_list_table( $current_tab = 'tin-can', $column_settings = array() ) {

		self::$tincan_database      = new \UCTINCAN\Database\Admin();
		self::$tincan_opt_per_pages = get_user_meta( get_current_user_id(), 'ucTinCan_per_page', true );
		self::$tincan_opt_per_pages = ( self::$tincan_opt_per_pages ) ? self::$tincan_opt_per_pages : 25;

		if ( ! is_admin() ) {
			// @todo REVIEW this setup
			// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
			global $hook_suffix;
			$hook_suffix = '';
			if ( isset( $page_hook ) ) {
				$hook_suffix = $page_hook;
			} elseif ( isset( $plugin_page ) ) {
				$hook_suffix = $plugin_page;
			} elseif ( isset( $pagenow ) ) {
				$hook_suffix = $pagenow;
			}
			// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited

			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
			require_once ABSPATH . 'wp-admin/includes/screen.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
			require_once ABSPATH . 'wp-admin/includes/template.php';

		}

		include_once dirname( UO_REPORTING_FILE ) . '/src/includes/TinCan_List_Table.php';

		$tincan_list_table = new \TinCan_List_Table();
		$columns           = array();
		if ( 'xapi-tincan' === $current_tab ) {
			$user_settings   = get_user_meta( get_current_user_id(), 'xapi_report_columns', true );
			$column_settings = wp_parse_args( $user_settings, self::$xapi_report_columns );

			foreach ( $column_settings as $key => $column ) {
				if ( ! is_admin() ) {
					$columns[] = $column['label'];
				} else {
					if ( true === $column['value'] ) {
						$columns[] = $column['label'];
					}
				}
			}

			$columns = apply_filters( 'tincan_xapi_table_columns', $columns );

		} else {

			add_filter( 'tincan_table_columns', array( __CLASS__, 'tincan_remove_success_column' ), 10 );

			$columns = apply_filters(
				'tincan_table_columns',
				array(
					__( 'Group', 'uncanny-learndash-reporting' ),
					__( 'User', 'uncanny-learndash-reporting' ),
					__( 'Course', 'uncanny-learndash-reporting' ),
					__( 'Module', 'uncanny-learndash-reporting' ),
					__( 'Target', 'uncanny-learndash-reporting' ),
					__( 'Action', 'uncanny-learndash-reporting' ),
					__( 'Result', 'uncanny-learndash-reporting' ),
					__( 'Success', 'uncanny-learndash-reporting' ),
					__( 'Date Time', 'uncanny-learndash-reporting' ),
				)
			);

		}

		$tincan_list_table->sortable_columns = $columns;
		$tincan_list_table->__set( 'column', $columns );//           = $coulmns;

		if ( 'xapi-tincan' === $current_tab ) {
			$tincan_list_table->data           = array( __CLASS__, 'patchData_xapi' );
			$tincan_list_table->count          = array( __CLASS__, 'patchNumRows_xapi' );
			$tincan_list_table->per_page       = self::$tincan_opt_per_pages;
			$tincan_list_table->extra_tablenav = array( __CLASS__, 'ExtraTableNav_xapi' );
		}
		//else {
		//$tincan_list_table->data           = array( __CLASS__, 'patchData' );
		//$tincan_list_table->count          = array( __CLASS__, 'patchNumRows' );
		//$tincan_list_table->per_page       = self::$tincan_opt_per_pages;
		//$tincan_list_table->extra_tablenav = array( __CLASS__, 'ExtraTableNav' );
		//}

		$tincan_list_table->prepare_items();
		$tincan_list_table->views();

		$tincan_list_table->display();
	}

	/**
	 * @return void
	 * @deprecated 5.0
	 */
	private static function SetOrder() {
		self::$tincan_database->orderby = 'xstored';
		self::$tincan_database->order   = 'desc';
		$order_by                       = ultc_get_filter_var( 'order_by', '' );
		if ( ! empty( $order_by ) ) {
			switch ( $order_by ) {
				case 'group':
				case 'user':
				case 'course':
					self::$tincan_database->orderby = $order_by . '_id';
					break;

				case 'action':
					self::$tincan_database->orderby = 'verb';
					break;

				case 'date-time':
					self::$tincan_database->orderby = 'xstored';
					break;
			}

			self::$tincan_database->order = ultc_get_filter_var( 'order', 'desc' );
		}
	}

	/**
	 * @return void
	 * @deprecated 5.0
	 */
	private static function SetTcFilters() {
		// Group
		if ( ! empty( ultc_get_filter_var( 'tc_filter_group', '' ) ) ) {
			self::$tincan_database->group = ultc_filter_input( 'tc_filter_group' );
		}

		// Actor
		if ( ! empty( ultc_get_filter_var( 'tc_filter_user', '' ) ) ) {
			self::$tincan_database->actor = ultc_filter_input( 'tc_filter_user' );
		}

		// Course
		if ( ! empty( ultc_get_filter_var( 'tc_filter_course', '' ) ) ) {
			self::$tincan_database->course = ultc_filter_input( 'tc_filter_course' );
		}

		// Lesson
		if ( ! empty( ultc_get_filter_var( 'tc_filter_lesson', '' ) ) ) {
			self::$tincan_database->lesson = ultc_filter_input( 'tc_filter_lesson' );
		}

		// Module
		if ( ! empty( ultc_get_filter_var( 'tc_filter_module', '' ) ) ) {
			self::$tincan_database->module = ultc_filter_input( 'tc_filter_module' );
		}

		// Verb
		if ( ! empty( ultc_get_filter_var( 'tc_filter_action', '' ) ) ) {
			self::$tincan_database->verb = strtolower( ultc_filter_input( 'tc_filter_action' ) );
		}

		// Questions
		if ( ! empty( ultc_get_filter_var( 'tc_filter_quiz', '' ) ) ) {
			self::$tincan_database->question = strtolower( ultc_filter_input( 'tc_filter_quiz' ) );
		}

		// Result
		if ( ! empty( ultc_get_filter_var( 'tc_filter_results', '' ) ) ) {
			self::$tincan_database->results = strtolower( ultc_filter_input( 'tc_filter_results' ) );
		}

		// Date
		if ( ! empty( ultc_get_filter_var( 'tc_filter_date_range', '' ) ) ) {
			// phpcs:disable WordPress.NamingConventions.ValidVariableName
			switch ( ultc_filter_input( 'tc_filter_date_range' ) ) {
				case 'last':
					$date_range_last = ultc_get_filter_var( 'tc_filter_date_range_last', '' );
					if ( ! empty( $date_range_last ) ) {
						$current_time = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
						// phpcs:disable WordPress.DateTime.RestrictedFunctions.date_date
						switch ( $date_range_last ) {
							case 'week':
								self::$tincan_database->dateEnd   = date( 'Y-m-d ', $current_time ) . '23:59:59';
								$dateStart                        = strtotime( 'last week', $current_time );
								self::$tincan_database->dateStart = date( 'Y-m-d ', $dateStart );
								break;
							case 'month':
								self::$tincan_database->dateEnd   = date( 'Y-m-d ', $current_time ) . '23:59:59';
								$dateStart                        = strtotime( 'first day of last month', $current_time );
								self::$tincan_database->dateStart = date( 'Y-m-d ', $dateStart );
								break;

							case '90days':
								self::$tincan_database->dateEnd   = date( 'Y-m-d ', $current_time ) . '23:59:59';
								$dateStart                        = strtotime( '-90 days', $current_time );
								self::$tincan_database->dateStart = date( 'Y-m-d ', $dateStart );
								break;
							case '3months':
								self::$tincan_database->dateEnd   = date( 'Y-m-d ', $current_time ) . '23:59:59';
								$dateStart                        = strtotime( '-3 months', $current_time );
								self::$tincan_database->dateStart = date( 'Y-m-d ', $dateStart );
								break;
							case '6months':
								self::$tincan_database->dateEnd   = date( 'Y-m-d ', $current_time ) . '23:59:59';
								$dateStart                        = strtotime( '-6 months', $current_time );
								self::$tincan_database->dateStart = date( 'Y-m-d ', $dateStart );
								break;
						}
						// phpcs:enable WordPress.DateTime.RestrictedFunctions.date_date
					}
					break;
				case 'from':
					if ( ! empty( ultc_get_filter_var( 'tc_filter_start', '' ) ) ) {
						self::$tincan_database->dateStart = ultc_filter_input( 'tc_filter_start' );
					}

					if ( ! empty( ultc_get_filter_var( 'tc_filter_end', '' ) ) ) {
						self::$tincan_database->dateEnd = ultc_filter_input( 'tc_filter_end' ) . ' 23:59:59';
					}
					break;
			}
			// phpcs:enable WordPress.NamingConventions.ValidVariableName
		}
	}

	/**
	 * @return void
	 * @depecated 5.0
	 */
	private static function ExtraTableNavTop() {
		$ld_groups  = array();
		$ld_courses = array();
		if ( ! is_admin() ) {
			$group_leader_id = get_current_user_id();
			$user_group_ids  = learndash_get_administrators_group_ids( $group_leader_id, true );
			$args            = array(
				'numberposts' => 9999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts
				'include'     => array_map( 'intval', $user_group_ids ),
				'post_type'   => 'groups',
				'orderby'     => 'title',
				'order'       => 'ASC',
			);

			$ld_groups_user = get_posts( $args );
			if ( ! empty( $ld_groups_user ) ) {
				foreach ( $ld_groups_user as $ld_group ) {
					$ld_groups[] = array(
						'group_id'   => $ld_group->ID,
						'group_name' => $ld_group->post_title,
					);
				}
			}
			// Courses
			$get_filter_group_id = absint( ultc_get_filter_var( 'tc_filter_group', 0 ) );
			if ( ! empty( $get_filter_group_id ) ) {

				// check is user group
				if ( in_array( $get_filter_group_id, $user_group_ids, true ) ) {
					$courses = learndash_group_enrolled_courses( $get_filter_group_id );
					$args    = array(
						'numberposts' => 9999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts
						'include'     => array_map( 'intval', $courses ),
						'post_type'   => 'sfwd-courses',
						'orderby'     => 'title',
						'order'       => 'ASC',
					);

					$courses = get_posts( $args );
					foreach ( $courses as $course ) {
						$ld_courses[] = array(
							'course_id'   => $course->ID,
							'course_name' => $course->post_title,
						);
					}
				}
			}
			// Actions
			$ld_actions = self::$tincan_database->get_actions();

		} else {

			// Group
			$ld_groups = self::$tincan_database->get_groups();

			// Courses
			$ld_courses = self::$tincan_database->get_courses();

			// Actions
			$ld_actions = self::$tincan_database->get_actions();
		}

		include self::get_part( 'tc-tincan-filter.php' );

		?>

		<script>
			jQuery(document).ready(function ($) {
				$('.datepicker').datepicker({
					'dateFormat': 'yy-mm-dd'
				});

				$('.dashicons-calendar-alt').click(function () {
					$(this).prev().focus();
				});
			});
		</script>
		<?php
	}

	/**
	 * @return void
	 * @deprecated 5.0
	 */
	private static function ExtraTableNavBottom() {
		$per_pages = array(
			10,
			25,
			50,
			100,
			200,
			500,
			self::$tincan_opt_per_pages,
		);

		$per_pages = array_unique( $per_pages );
		asort( $per_pages );

		?>
		<div id="tincan-filters-per_page">
			<select>
				<?php foreach ( $per_pages as $per_page ) { ?>
					<option
						value="<?php echo esc_attr( $per_page ); ?>" <?php echo esc_attr( ( (int) self::$tincan_opt_per_pages === (int) $per_page ) ? 'selected="selected"' : '' ); ?>><?php echo esc_html( $per_page ); ?></option>
				<?php } // foreach( $ld_groups ) ?>
			</select>

			<?php esc_html_e( 'Per Page', 'uncanny-learndash-reporting' ); ?>
		</div>

		<div id="tincan-filters-export">
			<form action="
			<?php
			echo esc_attr(
				remove_query_arg(
					array(
						'paged',
						'tc_filter_mode',
						'tc_filter_group',
						'tc_filter_user',
						'tc_filter_course',
						'tc_filter_lesson',
						'tc_filter_module',
						'tc_filter_action',
						'tc_filter_date_range',
						'tc_filter_date_range_last',
						'tc_filter_start',
						'tc_filter_end',
						'orderby',
						'order',
					)
				)
			);
			?>
			" method="get" id="tincan-filters-bottom">
				<input type="hidden" name="tc_filter_mode" value="csv"/>
				<input type="hidden" name="tab" value="tin-can"/>

				<input type="hidden" name="tc_filter_group"
					   value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_group', '' ) ); ?>"/>
				<input type="hidden" name="tc_filter_user"
					   value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_user', '' ) ); ?>"/>
				<input type="hidden" name="tc_filter_course"
					   value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_course', '' ) ); ?>"/>
				<input type="hidden" name="tc_filter_lesson"
					   value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_lesson', '' ) ); ?>"/>
				<input type="hidden" name="tc_filter_module"
					   value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_module', '' ) ); ?>"/>
				<input type="hidden" name="tc_filter_action"
					   value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_action', '' ) ); ?>"/>
				<input type="hidden" name="tc_filter_date_range"
					   value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_date_range', '' ) ); ?>"/>
				<input type="hidden" name="tc_filter_date_range_last"
					   value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_date_range_last', '' ) ); ?>"/>
				<input type="hidden" name="tc_filter_start"
					   value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_start', '' ) ); ?>"/>
				<input type="hidden" name="tc_filter_end"
					   value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_end', '' ) ); ?>"/>
				<input type="hidden" name="orderby"
					   value="<?php echo esc_attr( ultc_get_filter_var( 'orderby', '' ) ); ?>"/>
				<input type="hidden" name="order"
					   value="<?php echo esc_attr( ultc_get_filter_var( 'order', '' ) ); ?>"/>

				<?php submit_button( __( 'Export To CSV', 'uncanny-learndash-reporting' ), 'action', '', false, array( 'id' => 'do_tc_export_csv' ) ); ?>
			</form>
		</div>

		<?php
	}

	/**
	 * @return void
	 * @deprecated 5.0
	 */
	private static function ExtraTableNavTop_xapi() {
		$ld_groups  = array();
		$ld_courses = array();
		if ( ! is_admin() ) {
			$group_leader_id = get_current_user_id();
			$user_group_ids  = learndash_get_administrators_group_ids( $group_leader_id, true );
			$args            = array(
				'numberposts' => 9999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts
				'include'     => array_map( 'intval', $user_group_ids ),
				'post_type'   => 'groups',
				'orderby'     => 'title',
				'order'       => 'ASC',
			);

			$ld_groups_user = get_posts( $args );
			if ( ! empty( $ld_groups_user ) ) {
				foreach ( $ld_groups_user as $ld_group ) {
					$ld_groups[] = array(
						'group_id'   => $ld_group->ID,
						'group_name' => $ld_group->post_title,
					);
				}
			}
			// Courses
			$get_filter_group_id = absint( ultc_get_filter_var( 'tc_filter_group', 0 ) );
			if ( ! empty( $get_filter_group_id ) ) {

				// check is user group
				if ( in_array( $get_filter_group_id, $user_group_ids, true ) ) {
					$courses = learndash_group_enrolled_courses( $get_filter_group_id );
					$args    = array(
						'numberposts' => 9999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts
						'include'     => array_map( 'intval', $courses ),
						'post_type'   => 'sfwd-courses',
						'orderby'     => 'title',
						'order'       => 'ASC',
					);

					$courses = get_posts( $args );
					foreach ( $courses as $course ) {
						$ld_courses[] = array(
							'course_id'   => $course->ID,
							'course_name' => $course->post_title,
						);
					}
				}
			}
		} else {

			// Group
			$ld_groups = self::$tincan_database->get_groups( 'quiz' );

			// Courses
			$ld_courses = self::$tincan_database->get_courses( 'quiz' );

			// Actions
			//$ld_actions = self::$tincan_database->get_questions();
		}

		include self::get_part( 'tc-xapi-filter.php' );

		?>

		<?php
		if ( 'list' === ultc_get_filter_var( 'tc_filter_mode', '' ) ) {
			if ( is_admin() ) {
				?>

				<div class="reporting-table-info">
					<?php esc_html_e( 'To customize the columns that are displayed, use the Screen Options tab in the top right.', 'uncanny-learndash-reporting' ); ?>
				</div>

				<?php
			}
		}
		?>

		<script>
			jQuery(document).ready(function ($) {
				$('.datepicker').datepicker({
					'dateFormat': 'yy-mm-dd'
				});

				$('.dashicons-calendar-alt').click(function () {
					$(this).prev().focus();
				});
			});
		</script>
		<?php
	}

	/**
	 * @return void
	 * @deprecated 5.0
	 */
	private static function ExtraTableNavBottom_xapi() {
		$per_pages = array(
			10,
			25,
			50,
			100,
			200,
			500,
			self::$tincan_opt_per_pages,
		);

		$per_pages = array_unique( $per_pages );
		asort( $per_pages );

		?>
		<div id="tincan-filters-per_page">
			<select>
				<?php foreach ( $per_pages as $per_page ) { ?>
					<option
						value="<?php echo esc_attr( $per_page ); ?>" <?php echo esc_attr( ( (int) self::$tincan_opt_per_pages === (int) $per_page ) ? 'selected="selected"' : '' ); ?>><?php echo esc_html( $per_page ); ?></option>
				<?php } // foreach( $ld_groups ) ?>
			</select>

			<?php esc_html_e( 'Per Page', 'uncanny-learndash-reporting' ); ?>
		</div>

		<div id="tincan-filters-export">
			<form action="
			<?php
			echo esc_attr(
				remove_query_arg(
					array(
						'paged',
						'tc_filter_mode',
						'tc_filter_group',
						'tc_filter_user',
						'tc_filter_course',
						'tc_filter_lesson',
						'tc_filter_module',
						'tc_filter_action',
						'tc_filter_quiz',
						'tc_filter_results',
						'tc_filter_date_range',
						'tc_filter_date_range_last',
						'tc_filter_start',
						'tc_filter_end',
						'orderby',
						'order',
					)
				)
			);
			?>
			" method="get" id="xapi-filters-bottom">
				<input type="hidden" name="tc_filter_mode" value="csv-xapi"/>
				<input type="hidden" name="tc_filter_group"
					   value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_group', '' ) ); ?>"/>
				<input type="hidden" name="tc_filter_user"
					   value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_user', '' ) ); ?>"/>
				<input type="hidden" name="tc_filter_course"
					   value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_course', '' ) ); ?>"/>
				<input type="hidden" name="tc_filter_lesson"
					   value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_lesson', '' ) ); ?>"/>
				<input type="hidden" name="tc_filter_module"
					   value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_module', '' ) ); ?>"/>
				<input type="hidden" name="tc_filter_action"
					   value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_action', '' ) ); ?>"/>
				<input type="hidden" name="tc_filter_quiz"
					   value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_quiz', '' ) ); ?>"/>
				<input type="hidden" name="tc_filter_results"
					   value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_results', '' ) ); ?>"/>
				<input type="hidden" name="tc_filter_date_range"
					   value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_date_range', '' ) ); ?>"/>
				<input type="hidden" name="tc_filter_date_range_last"
					   value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_date_range_last', '' ) ); ?>"/>
				<input type="hidden" name="tc_filter_start"
					   value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_start', '' ) ); ?>"/>
				<input type="hidden" name="tc_filter_end"
					   value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_end', '' ) ); ?>"/>
				<input type="hidden" name="orderby"
					   value="<?php echo esc_attr( ultc_get_filter_var( 'orderby', '' ) ); ?>"/>
				<input type="hidden" name="order"
					   value="<?php echo esc_attr( ultc_get_filter_var( 'order', '' ) ); ?>"/>

				<?php submit_button( __( 'Export To CSV', 'uncanny-learndash-reporting' ), 'action', '', false, array( 'id' => 'do_tc_export_csv_xapi' ) ); ?>
			</form>
		</div>

		<?php
	}

	/**
	 * @param $status
	 * @param $option
	 * @param $values
	 *
	 * @return array|array[]
	 * @deprecated 5.0
	 */
	public function filter__set_screen_option( $status, $option, $values ) {
		if ( 'xapi_report_columns' === $option ) {
			// This class owns the option.
			if ( is_array( $values ) ) {
				foreach ( self::$xapi_report_columns as $option => $details ) {
					self::$xapi_report_columns[ $option ]['value'] = false;
					if ( isset( $values[ $option ] ) ) {
						self::$xapi_report_columns[ $option ]['value'] = true;
					}
				}

				return self::$xapi_report_columns;
			}
		}

		return $status;
	}

	/**
	 * @param $screen_settings
	 * @param $screen
	 *
	 * @return mixed|string
	 * @deprecated 5.0
	 */
	public function filter__screen_settings( $screen_settings, $screen ) {
		if ( 'uncanny-learnDash-reporting' !== $screen->parent_base ) {
			return $screen_settings;
		}
		$user_settings = get_user_meta( get_current_user_id(), 'xapi_report_columns', true );

		self::$xapi_report_columns = wp_parse_args( $user_settings, self::$xapi_report_columns );

		$out = '';
		foreach ( self::$xapi_report_columns as $option => $args ) {
			$label   = isset( $args['label'] ) && ! empty( $args['label'] ) ? $args['label'] : $option;
			$default = $args['default'] ?? '';
			$type    = $args['type'] ?? '';
			switch ( $type ) {
				default:
					$value = self::$xapi_report_columns[ $option ]['value'] ?? null;
					if ( is_null( $value ) ) {
						$value = $default;
					}
					$out .= sprintf( '<label for="%1$s"> <input id="%1$s" name="wp_screen_options[value][%1$s]" value="1" type="checkbox" class="screen-per-page" %3$s />%2$s</label>', esc_attr( $option ), $label, true === $value ? 'checked="checked"' : '' );
			}
		}
		if ( $out ) {
			$screen_settings .= sprintf(
				'
				<fieldset class="metabox-prefs">
				<legend>xAPI Quiz Report</legend>
				<input type="hidden" name="wp_screen_options[option]" value="%s" />
				%s%s</fieldset>',
				'xapi_report_columns',
				$out,
				get_submit_button( __( 'Apply' ), 'button', 'screen-options-apply', false )
			);
		}

		return $screen_settings;
	}
}
