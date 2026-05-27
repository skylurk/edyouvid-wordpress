<?php

namespace uncanny_learndash_reporting;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class AdminMenu
 *
 * @package uncanny_custom_reporting
 */
class ReportingAdminMenu extends Boot {

	/**
	 * Class constructor
	 */
	public function __construct() {

		add_action( 'admin_menu', array( __CLASS__, 'register_options_menu_page' ), 10 );
		add_action( 'admin_init', array( __CLASS__, 'register_options_menu_page_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'scripts' ), 99999 );
		add_action( 'admin_init', array( __CLASS__, 'maybe_redirect_with_group_id' ) );

		//self::csv_export();

		//add_action( 'wp_enqueue_scripts', array( __CLASS__, 'scripts' ) );
		add_action( 'init', array( __CLASS__, 'render_callback' ) );
	}

	/**
	 * @return void
	 */
	public static function render_callback() {
		if ( function_exists( 'register_block_type' ) ) {
			register_block_type(
				'tincanny-learndash-reporting/frontend-course-reports',
				array(
					'render_callback' => array( __CLASS__, 'frontend_tincanny_block' ),
				)
			);
		}
	}

	/**
	 * Create Plugin options menu
	 */
	public static function register_options_menu_page() {

		$page_title = esc_html_x( 'Tin Canny Reporting for LearnDash', 'uncanny-learndash-reporting', 'uncanny-learndash-reporting' );
		$menu_title = esc_html_x( 'Tin Canny Reporting', 'uncanny-learndash-reporting', 'uncanny-learndash-reporting' );

		$capability = apply_filters( 'uo_tincanny_reporting_capability', 'tincanny_reporting' );

		$menu_slug = 'uncanny-learnDash-reporting';
		$function  = array( TinCannyShortcode::class, 'course_report_page' );

		$icon_url = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz48c3ZnIGVuYWJsZS1iYWNrZ3JvdW5kPSJuZXcgMCAwIDU4MSA2NDAiIHZlcnNpb249IjEuMSIgdmlld0JveD0iMCAwIDU4MSA2NDAiIHhtbDpzcGFjZT0icHJlc2VydmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0ibTUyNi40IDM0LjFjMC42IDUgMSAxMC4xIDEuMyAxNS4xIDAuNSAxMC4zIDEuMiAyMC42IDAuOCAzMC45LTAuNSAxMS41LTEgMjMtMi4xIDM0LjQtMi42IDI2LjctNy44IDUzLjMtMTYuNSA3OC43LTcuMyAyMS4zLTE3LjEgNDEuOC0yOS45IDYwLjQtMTIgMTcuNS0yNi44IDMzLTQzLjggNDUuOS0xNy4yIDEzLTM2LjcgMjMtNTcuMSAyOS45LTI1LjEgOC41LTUxLjUgMTIuNy03Ny45IDEzLjggNzAuMyAyNS4zIDEwNi45IDEwMi44IDgxLjYgMTczLjEtMTguOSA1Mi42LTY4LjEgODguMS0xMjQgODkuNWgtNi4xYy0xMS4xLTAuMi0yMi4xLTEuOC0zMi45LTQuNy0yOS40LTcuOS01NS45LTI2LjMtNzMuNy01MC45LTI5LjItNDAuMi0zNC4xLTkzLjEtMTIuNi0xMzgtMjUgMjUuMS00NC41IDU1LjMtNTkuMSA4Ny40LTguOCAxOS43LTE2LjEgNDAuMS0yMC44IDYxLjEtMS4yLTE0LjMtMS4yLTI4LjYtMC42LTQyLjkgMS4zLTI2LjYgNS4xLTUzLjIgMTIuMi03OC45IDUuOC0yMS4yIDEzLjktNDEuOCAyNC43LTYwLjlzMjQuNC0zNi42IDQwLjYtNTEuM2MxNy4zLTE1LjcgMzcuMy0yOC4xIDU5LjEtMzYuOCAyNC41LTkuOSA1MC42LTE1LjIgNzYuOC0xNy4yIDEzLjMtMS4xIDI2LjctMC44IDQwLjEtMi4zIDI0LjUtMi40IDQ4LjgtOC40IDcxLjMtMTguMyAyMS05LjIgNDAuNC0yMS44IDU3LjUtMzcuMiAxNi41LTE0LjkgMzAuOC0zMi4xIDQyLjgtNTAuOCAxMy0yMC4yIDIzLjQtNDIuMSAzMS42LTY0LjcgNy42LTIxLjEgMTMuNC00Mi45IDE2LjctNjUuM3ptLTI3OS40IDMyOS41Yy0xOC42IDEuOC0zNi4yIDguOC01MC45IDIwLjQtMTcuMSAxMy40LTI5LjggMzIuMi0zNi4yIDUyLjktNy40IDIzLjktNi44IDQ5LjUgMS43IDczIDcuMSAxOS42IDE5LjkgMzcuMiAzNi44IDQ5LjYgMTQuMSAxMC41IDMwLjkgMTYuOSA0OC40IDE4LjZzMzUuMi0xLjYgNTEtOS40YzEzLjUtNi43IDI1LjQtMTYuMyAzNC44LTI4LjEgMTAuNi0xMy40IDE3LjktMjkgMjEuNS00NS43IDQuOC0yMi40IDIuOC00NS43LTUuOC02Ni45LTguMS0yMC0yMi4yLTM3LjYtNDAuMy00OS4zLTE4LTExLjctMzkuNS0xNy02MS0xNS4xeiIgZmlsbD0iIzgyODc4QyIvPjxwYXRoIGQ9Im0yNDIuNiA0MDIuNmM2LjItMS4zIDEyLjYtMS44IDE4LjktMS41LTExLjQgMTEuNC0xMi4yIDI5LjctMS44IDQyIDExLjIgMTMuMyAzMS4xIDE1LjEgNDQuNCAzLjkgNS4zLTQuNCA4LjktMTAuNCAxMC41LTE3LjEgMTIuNCAxNi44IDE2LjYgMzkuNCAxMSA1OS41LTUgMTguNS0xOCAzNC42LTM1IDQzLjUtMzQuNSAxOC4yLTc3LjMgNS4xLTk1LjUtMjkuNS0xLTItMi00LTIuOS02LjEtOC4xLTE5LjYtNi41LTQzIDQuMi02MS4zIDEwLTE3IDI2LjgtMjkuMiA0Ni4yLTMzLjR6IiBmaWxsPSIjODI4NzhDIi8+PC9zdmc+';

		$position = 81; // 81 - Above Settings Menu
		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );

		add_submenu_page(
			'uncanny-learnDash-reporting',
			esc_html_x( 'Tin Canny Reporting for LearnDash', 'uncanny-learndash-reporting', 'uncanny-learndash-reporting' ),
			esc_html_x( 'Course Report', 'uncanny-learndash-reporting', 'uncanny-learndash-reporting' ),
			$capability,
			'uncanny-learnDash-reporting',
			array( TinCannyShortcode::class, 'course_report_page' )
		);

		self::add_tincanny_reporting_menu_items( $capability );
	}

	/**
	 * @param $capability
	 *
	 * @return void
	 */
	public static function add_tincanny_reporting_menu_items( $capability ) {

//		// User Report
		add_submenu_page(
			'uncanny-learnDash-reporting',
			__( 'User Report', 'uncanny-learndash-reporting' ),
			__( 'User Report', 'uncanny-learndash-reporting' ),
			$capability,
			'uncanny-tincanny-user-report',
			array(
				__CLASS__,
				'user_report_page',
			)
		);


		$tincanny_settings = \TINCANNYSNC\Admin\Options::get_options();

		if ( '1' === (string) $tincanny_settings['tinCanActivation'] ) {
			// Tin Can Report
			add_submenu_page(
				'uncanny-learnDash-reporting',
				__( 'Tin Can Report', 'uncanny-learndash-reporting' ),
				__( 'Tin Can Report', 'uncanny-learndash-reporting' ),
				$capability,
				'uncanny-tincanny-tin-can-report',
				array(
					__CLASS__,
					'tin_can_report_page',
				)
			);
			// xAPI Quiz Report
			add_submenu_page(
				'uncanny-learnDash-reporting',
				__( 'xAPI Quiz Report', 'uncanny-learndash-reporting' ),
				__( 'xAPI Quiz Report', 'uncanny-learndash-reporting' ),
				$capability,
				'uncanny-tincanny-xapi-quiz-report',
				array(
					__CLASS__,
					'xapi_quiz_report_page',
				)
			);
		}
	}

	/**
	 * @return void
	 */
	public static function user_report_page() {

		if ( ! is_user_logged_in() ) {
			echo esc_html__( 'You must be logged in to view this report.', 'uncanny-learndash-reporting' );

			return;
		}

		if ( ! current_user_can( apply_filters( 'uo_tincanny_reporting_capability', 'tincanny_reporting' ) ) ) {
			echo esc_html__( 'You do not have access to this report', 'uncanny-learndash-reporting' );

			return;
		}

		TinCannyShortcode::$current_report_tab = 'uncanny-tincanny-user-report';

		TinCannyShortcode::course_report_page();
	}

	/**
	 * @return void
	 */
	public static function tin_can_report_page() {

		if ( ! is_user_logged_in() ) {
			echo esc_html__( 'You must be logged in to view this report.', 'uncanny-learndash-reporting' );

			return;
		}

		if ( ! current_user_can( apply_filters( 'uo_tincanny_reporting_capability', 'tincanny_reporting' ) ) ) {
			echo esc_html__( 'You do not have access to this report', 'uncanny-learndash-reporting' );

			return;
		}

		TinCannyShortcode::$current_report_tab = 'uncanny-tincanny-tin-can-report';

		TinCannyShortcode::tincan_report_page();
	}

	/**
	 * @return void
	 */
	public static function xapi_quiz_report_page() {

		if ( ! is_user_logged_in() ) {
			echo esc_html__( 'You must be logged in to view this report.', 'uncanny-learndash-reporting' );

			return;
		}

		if ( ! current_user_can( apply_filters( 'uo_tincanny_reporting_capability', 'tincanny_reporting' ) ) ) {
			echo esc_html__( 'You do not have access to this report', 'uncanny-learndash-reporting' );

			return;
		}

		TinCannyShortcode::$current_report_tab = 'uncanny-tincanny-xapi-quiz-report';

		TinCannyShortcode::xapi_report_page();
	}

	/**
	 * @return void
	 */
	public static function register_options_menu_page_settings() {
		register_setting( 'uncanny_learndash_reporting-group', 'uncanny_reporting_active_classes' );
	}

	/**
	 * @param $hook
	 *
	 * @return void
	 */
	public static function scripts( $hook ) {

		if ( ! current_user_can( apply_filters( 'uo_tincanny_reporting_capability', 'tincanny_reporting' ) ) ) {
			return;
		}

		if ( ! wp_script_is( 'wp-hooks', 'enqueued' ) ) {
			wp_enqueue_script( 'wp-hooks' );
		}

		// Only enqueue scripts on the dashboard page for the widget
		if ( is_admin() ) {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : '';
			if ( ! empty( $screen ) && 'dashboard' === $screen->id ) {

				self::enqueue_wp_admin_dashboard_scripts();

				return;

			}
		}
		
		// All the settings pages except reporting
		if ( preg_match( '/tin-canny-reporting/', $hook ) ) {
			self::enqueue_common_scripts();
			self::enqueue_wp_admin_reporting_scripts();

			return;
		}

		// All the reporting pages
		if ( preg_match( '/uncanny-(tincanny-user-report|learnDash-reporting|tincanny-tin-can-report|tincanny-xapi-quiz-report)$/', $hook ) ) {
			self::enqueue_wp_admin_reporting_scripts();
		}

	}

	/**
	 * @return void
	 */
	public static function enqueue_wp_admin_dashboard_scripts() {
		$disable_dash_widget = get_option( 'tincanny_disableDashWidget', 'no' );

		if ( 'no' !== $disable_dash_widget ) {
			return;
		}
		// WP Dashboard Reporting UI Files
		self::enqueue_common_scripts();

		// Get Tin Canny settings
		$tincanny_settings = \TINCANNYSNC\Admin\Options::get_options();

		wp_register_script(
			'reporting_js_handle',
			Config::get_admin_js( 'tc-reporting-dashboard' ),
			array(
				'jquery',
				'wp-hooks',
				'wp-i18n',
				'tc_runtime',
				'tc_vendors',
			),
			UNCANNY_REPORTING_VERSION,
			true
		);

		$reporting_api_setup = array(
			'root'             => esc_url_raw( rest_url() . 'uncanny_reporting/v1/' ),
			'nonce'            => \wp_create_nonce( 'wp_rest' ),
			'learnDashLabels'  => MiscFunctions::get_labels(),
			'page'             => 'dashboard',
			'localizedStrings' => Translations::get_js_localized_strings(),
			'optimized_build'  => '1',
			'showTinCanTab'    => 1 === (int) $tincanny_settings['tinCanActivation'] ? '1' : '0',
		);
		$reporting_api_setup['current_tab'] = TinCannyShortcode::$current_report_tab;

		// Add custom colors to use them in the JS
		$ui = self::get_ui_data();
		wp_localize_script( 'reporting_js_handle', 'TincannyUI', $ui );

		// Add Tin Canny data
		wp_localize_script( 'reporting_js_handle', 'TincannyData', self::get_script_data() );

		wp_localize_script( 'reporting_js_handle', 'reportingApiSetup', $reporting_api_setup );
		wp_enqueue_script( 'reporting_js_handle' );
	}

	/**
	 * @return void
	 */
	public static function enqueue_common_scripts() {
		// Load Styles for WP Dashboard Reporting  page located in general plugin styles
		wp_enqueue_style( 'tclr-icons', Config::get_admin_css( 'icons.css' ), array(), UNCANNY_REPORTING_VERSION );
		wp_enqueue_style( 'tclr-select2', Config::get_admin_css( 'select2.min.css' ), array(), UNCANNY_REPORTING_VERSION );
		wp_enqueue_style( 'tclr-data-tables', Config::get_admin_css( 'datatables.min.css' ), array(), UNCANNY_REPORTING_VERSION );

		wp_register_style( 'reporting-admin', Config::get_admin_css( 'admin-style.css' ), array(), UNCANNY_REPORTING_VERSION );

		$dynamic_css = self::get_dynamic_css();

		wp_add_inline_style( 'reporting-admin', $dynamic_css );

		wp_enqueue_style( 'reporting-admin' );
	}

	/**
	 * @return array
	 */
	public static function get_script_data() {

		$roles = array();
		if ( is_user_logged_in() ) {
			$user  = wp_get_current_user();
			$roles = (array) $user->roles;
		}

		$administrator_view = false;
		if ( in_array( 'administrator', $roles, true ) ) {
			$administrator_view = true;
		}

		return apply_filters(
			'uo_tincanny_reporting_tincanny_data',
			array(
				'url'               => array(
					'updateData' => admin_url( 'admin.php?page=learndash_data_upgrades' ),
				),
				'i18n'              => Translations::get_i18n_strings(),
				'administratorView' => $administrator_view,
				'pageLength'        => get_option( 'tincanny_user_report_default_page_length', 50 ),
			)
		);
	}

	/**
	 * Get the frontend reporting links
	 *
	 * @return array
	 */
	public static function get_frontend_reporting_links() {
		if ( is_admin() ) {
			$base_url = admin_url( 'admin.php' );
			return array(
				'courseReport' => $base_url . '?page=uncanny-learnDash-reporting',
				'userReport'   => $base_url . '?page=uncanny-tincanny-user-report',
				'tinCanReport' => $base_url . '?page=uncanny-tincanny-tin-can-report',
				'xapiReport'   => $base_url . '?page=uncanny-tincanny-xapi-quiz-report',
			);
		}

		$base_url = self::get_base_url();
		
		return  apply_filters(
			'uo_tincanny_reporting_frontend_reporting_links',
			array(
				'courseReport' => $base_url,
				'userReport'   => $base_url . '/?tab=uncanny-tincanny-user-report',
				'tinCanReport' => $base_url . '/?tab=uncanny-tincanny-tin-can-report',
				'xapiReport'   => $base_url . '/?tab=uncanny-tincanny-xapi-quiz-report',
			), 
			$base_url
		);
	}

	/**
	 * Load Scripts
	 * @paras string $hook Admin page being loaded
	 * 
	 * @return void
	 */
	public static function enqueue_wp_admin_reporting_scripts() {

		self::enqueue_common_scripts();

		if ( ultc_filter_has_var( 'page' ) && in_array(ultc_filter_input( 'page' ), array('manage-content','snc_options'), true) ) {
			return;
		}

		// Admin JS
		wp_register_script(
			'reporting_js_handle',
			Config::get_admin_js( 'reporting' ),
			array(
				'jquery',
				'wp-hooks',
				'wp-i18n',
				'tc_runtime',
				'tc_vendors',
			),
			UNCANNY_REPORTING_VERSION,
			true
		);

		// Add custom colors to use them in the JS
		$ui = self::get_ui_data();
		wp_localize_script( 'reporting_js_handle', 'TincannyUI', $ui );

		// Add Tin Canny data
		wp_localize_script( 'reporting_js_handle', 'TincannyData', self::get_script_data() );
		$isolated_group_id = absint( ultc_get_filter_var( 'group_id', 0 ) );

		// Get Tin Canny settings
		$tincanny_settings = \TINCANNYSNC\Admin\Options::get_options();

		// API data
		$reporting_api_setup = array(
			'root'                          => esc_url_raw( rest_url() . 'uncanny_reporting/v1/' ),
			'nonce'                         => \wp_create_nonce( 'wp_rest' ),
			'learnDashLabels'               => MiscFunctions::get_labels(),
			'isolated_group_id'             => $isolated_group_id,
			'isAdmin'                       => is_admin(),
			'editUsers'                     => current_user_can( 'edit_users' ),
			'localizedStrings'              => Translations::get_js_localized_strings(),
			'optimized_build'               => '1',
			'page'                          => 'reporting',
			'showTinCanTab'                 => isset( $tincanny_settings['tinCanActivation'] ) && 1 === (int) $tincanny_settings['tinCanActivation'] ? '1' : '0',
			'disablePerformanceEnhancments' => isset( $tincanny_settings['disablePerformanceEnhancments'] ) && 1 === (int) $tincanny_settings['disablePerformanceEnhancments'] ? '1' : '0',
			'userIdentifierDisplayName'     => isset( $tincanny_settings['userIdentifierDisplayName'] ) && 1 === (int) $tincanny_settings['userIdentifierDisplayName'] ? '1' : '0',
			'userIdentifierFirstName'       => isset( $tincanny_settings['userIdentifierFirstName'] ) && 1 === (int) $tincanny_settings['userIdentifierFirstName'] ? '1' : '0',
			'userIdentifierLastName'        => isset( $tincanny_settings['userIdentifierLastName'] ) && 1 === (int) $tincanny_settings['userIdentifierLastName'] ? '1' : '0',
			'userIdentifierUsername'        => isset( $tincanny_settings['userIdentifierUsername'] ) && 1 === (int) $tincanny_settings['userIdentifierUsername'] ? '1' : '0',
			'userIdentifierEmail'           => isset( $tincanny_settings['userIdentifierEmail'] ) && 1 === (int) $tincanny_settings['userIdentifierEmail'] ? '1' : '0',
			'ajaxurl'                       => admin_url( 'admin-ajax.php' ),
			'base_url'                      => self::get_base_url(),
			'reportingLinks'                => self::get_frontend_reporting_links(),
		);

		// TinCan Report Columns
		if ( ultc_filter_has_var( 'page' ) && 'uncanny-tincanny-tin-can-report' === ultc_filter_input( 'page' ) ) {
			$reporting_api_setup['tinCanReportColumns'] = TinCannyShortcode::$tin_can_table_columns;
			TinCannyShortcode::$current_report_tab = 'uncanny-tincanny-tin-can-report';
		}

		// xAPI Report Columns
		if ( ultc_filter_has_var( 'page' ) && 'uncanny-tincanny-xapi-quiz-report' === ultc_filter_input( 'page' ) ) {
			$reporting_api_setup['xAPIReportColumns'] = TinCannyShortcode::$xapi_report_columns;
			TinCannyShortcode::$current_report_tab = 'uncanny-tincanny-xapi-quiz-report';
		}

		$reporting_api_setup['current_tab'] = TinCannyShortcode::$current_report_tab;

		wp_localize_script( 'reporting_js_handle', 'reportingApiSetup', $reporting_api_setup );
		wp_enqueue_script( 'reporting_js_handle' );

		// TinCan
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'jquery-style', Config::get_admin_css( 'jquery-ui.min.css' ), array(), UNCANNY_REPORTING_VERSION );

		wp_enqueue_style( 'tincanny-admin-tincanny-report-tab', Config::get_src_assets_dist_css_url( 'admin.tincan.report.tab' ), array(), UNCANNY_REPORTING_VERSION );
	}

	/**
	 * @return string|null
	 */
	public static function get_base_url() {
		if ( is_admin() ) {
			return admin_url( 'admin.php?page=uncanny-learnDash-reporting' );
		} else {
			global $wp;

			return home_url( add_query_arg( array(), $wp->request ) );
		}
	}

	/**
	 * Add tincany via shortcode on the frontend
	 *
	 * @return string
	 */
	public static function frontend_tincanny_block() {
		self::enqueue_common_scripts();

		// Admin JS
		wp_register_script(
			'reporting_js_handle',
			Config::get_admin_js( 'reporting' ),
			array(
				'jquery',
				'wp-hooks',
				'wp-i18n',
				'tc_runtime',
				'tc_vendors',
			),
			UNCANNY_REPORTING_VERSION,
			false
		);

		// Add custom colors to use them in the JS
		$ui = self::get_ui_data();
		wp_localize_script( 'reporting_js_handle', 'TincannyUI', $ui );

		// Add Tin Canny data
		wp_localize_script( 'reporting_js_handle', 'TincannyData', self::get_script_data() );
		$isolated_group_id = absint( ultc_get_filter_var( 'group_id', 0 ) );

		// Get Tin Canny settings
		$tincanny_settings = \TINCANNYSNC\Admin\Options::get_options();

		// API data
		$reporting_api_setup = array(
			'root'                      => esc_url_raw( rest_url() . 'uncanny_reporting/v1/' ),
			'nonce'                     => \wp_create_nonce( 'wp_rest' ),
			'learnDashLabels'           => MiscFunctions::get_labels(),
			'isolated_group_id'         => $isolated_group_id,
			'isAdmin'                   => is_admin(),
			'editUsers'                 => current_user_can( 'edit_users' ),
			'localizedStrings'          => Translations::get_js_localized_strings(),
			'page'                      => 'frontend',
			'optimized_build'           => '1',
			'showTinCanTab'             => 1 === (int) $tincanny_settings['tinCanActivation'] ? '1' : '0',
			'userIdentifierDisplayName' => isset( $tincanny_settings['userIdentifierDisplayName'] ) && 1 === (int) $tincanny_settings['userIdentifierDisplayName'] ? '1' : '0',
			'userIdentifierFirstName'   => isset( $tincanny_settings['userIdentifierFirstName'] ) && 1 === (int) $tincanny_settings['userIdentifierFirstName'] ? '1' : '0',
			'userIdentifierLastName'    => isset( $tincanny_settings['userIdentifierLastName'] ) && 1 === (int) $tincanny_settings['userIdentifierLastName'] ? '1' : '0',
			'userIdentifierUsername'    => isset( $tincanny_settings['userIdentifierUsername'] ) && 1 === (int) $tincanny_settings['userIdentifierUsername'] ? '1' : '0',
			'userIdentifierEmail'       => isset( $tincanny_settings['userIdentifierEmail'] ) && 1 === (int) $tincanny_settings['userIdentifierEmail'] ? '1' : '0',
			'ajaxurl'                   => admin_url( 'admin-ajax.php' ),
			'base_url'                  => self::get_base_url(),
			'reportingLinks'            => self::get_frontend_reporting_links(),
		);

		$reporting_api_setup['current_tab'] = TinCannyShortcode::$current_report_tab;

		wp_localize_script( 'reporting_js_handle', 'reportingApiSetup', $reporting_api_setup );
		wp_enqueue_script( 'reporting_js_handle' );

		// TinCan
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'jquery-style', Config::get_admin_css( 'jquery-ui.min.css' ), array(), UNCANNY_REPORTING_VERSION );

		wp_enqueue_style( 'tincanny-admin-tincanny-report-tab', Config::get_src_assets_dist_css_url( 'admin.tincan.report.tab' ), array(), UNCANNY_REPORTING_VERSION );
		//wp_enqueue_script( 'tincanny-admin-tincanny-report-tab-js', Config::get_src_assets_dist_js_url( 'admin.tincan.report.tab' ), array(), UNCANNY_REPORTING_VERSION, false );

//		ob_start();

//		TinCannyShortcode::options_menu_page_output();

//		return ob_get_clean();
	}

	/**
	 * @param $text
	 * @param $limit
	 *
	 * @return mixed|string
	 */
	public static function limit_text( $text, $limit ) {
		if ( str_word_count( $text, 0 ) > $limit ) {
			$words = str_word_count( $text, 2 );
			$pos   = array_keys( $words );
			$text  = substr( $text, 0, $pos[ $limit ] ) . '...';
		}

		return $text;
	}

	/**
	 * @return void
	 * @deprecated 5.0
	 */
	private static function csv_export() {
		if ( 'csv' === ultc_get_filter_var( 'tc_filter_mode', '' ) ) {
			add_action( 'init', array( __CLASS__, 'execute_csv_export' ) );
		}
		if ( 'csv-xapi' === ultc_get_filter_var( 'tc_filter_mode', '' ) ) {
			add_action( 'init', array( __CLASS__, 'execute_csv_export_xapi' ) );
		}
	}

	/**
	 * @return false|string
	 */
	private static function get_dynamic_css() {
		// Get colors
		$ui = self::get_ui_data();

		// Start output
		ob_start();

		?>

		/* Main font */

		.tclr.wrap,
		.tclr-select2 .select2-dropdown {
		font-family: <?php echo esc_attr( $ui['mainFont'] ); ?>
		}

		/* Primary color */

		.reporting-dashboard-status--loading .reporting-dashboard-status__icon {
		background: <?php echo esc_attr( $ui['colors']['primary'] ); ?>;
		}

		.reporting-datatable__search .dataTables_filter input:focus {
		border-color: <?php echo esc_attr( $ui['colors']['primary'] ); ?>;
		}

		.reporting-table-see-details,
		.reporting-breadcrumbs-item__link {
		color: <?php echo esc_attr( $ui['colors']['primary'] ); ?>;
		}

		.reporting-single-course-progress-tabs__item.reporting-single-course-progress-tabs__item--selected {
		box-shadow: inset 3px 0 0 0 <?php echo esc_attr( $ui['colors']['primary'] ); ?>;
		}

		/* Secondary color */

		.reporting-dashboard-quick-links__icon {
		color: <?php echo esc_attr( $ui['colors']['secondary'] ); ?>;
		}

		/* Notice */

		.reporting-dashboard-status--warning .reporting-dashboard-status__icon {
		background:
		<?php
		echo esc_attr( $ui['colors']['notice'] );
		?>
		;
		}

		<?php

		// Get output
		$dynamic_css = ob_get_clean();

		// Return output
		return $dynamic_css;
	}

	/**
	 * @return mixed|null
	 */
	private static function get_ui_data() {
		// Define default font
		$font = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif';

		// Get colors from DB
		$primary   = '';
		$secondary = '';
		$notice    = '';

		$not_started = '';
		$in_progress = '';
		$completed   = '';

		// Define default values
		$primary   = empty( $primary ) ? '#0290c2' : $primary;
		$secondary = empty( $secondary ) ? '#d52c82' : $secondary;
		$notice    = empty( $notice ) ? '#f5ba05' : $notice;

		$completed   = empty( $completed ) ? '#02c219' : $completed;
		$in_progress = empty( $in_progress ) ? '#FF9E01' : $in_progress;
		$not_started = empty( $not_started ) ? '#e3e3e3' : $not_started;

		return apply_filters(
			'tincanny_ui',
			array(
				'mainFont' => $font,
				'colors'   => array(
					'primary'   => $primary,
					'secondary' => $secondary,
					'notice'    => $notice,
					'status'    => array(
						'completed'  => $completed,
						'inProgress' => $in_progress,
						'notStarted' => $not_started,
					),
				),
				'show'     => array(
					'tinCanData' => 'no' !== get_option( 'show_tincan_reporting_tables' ),
				),
			)
		);
	}

	/**
	 * Ensures group_id parameter is present in report page URLs
	 * Redirects to add default group_id if not present
	 * @return void
	 */
	public static function maybe_redirect_with_group_id() {
		// Array of our report page slugs
		$report_pages = array(
			'uncanny-learnDash-reporting',
			'uncanny-tincanny-user-report',
//			'uncanny-tincanny-tin-can-report',
//			'uncanny-tincanny-xapi-quiz-report'
		);

		// Check if we're on one of our report pages
		if ( isset( $_GET['page'] ) && in_array( $_GET['page'], $report_pages ) ) {
			// Only proceed if group_id is not set
			if ( ! isset( $_GET['group_id'] ) ) {
				$mode = get_option( 'tincanny_user_report_default_group', 'all' );
				
				// Get current URL parameters
				$params = $_GET;
				// Add our group_id
				$params['group_id'] = $mode;
				
				// Build the redirect URL
				$redirect_url = add_query_arg( $params, admin_url( 'admin.php' ) );
				
				wp_safe_redirect( $redirect_url );
				exit;
			}
		}
	}
}
