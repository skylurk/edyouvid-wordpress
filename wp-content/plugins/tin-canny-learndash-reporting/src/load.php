<?php

namespace uncanny_learndash_reporting;

use uncanny_learndash_reporting\tincanny_reporting\Database;
use uncanny_learndash_reporting\tincanny_reporting\Purge;
use uncanny_learndash_reporting\tincanny_reporting\RestRoutes;

/**
 *
 */
class Load extends Config {
	/**
	 * Static flag to ensure hooks are only registered once
	 */
	private static $hooks_registered = false;

	/**
	 *
	 */
	public function __construct() {

		// Only register hooks once, even if class is instantiated multiple times
		if ( ! self::$hooks_registered ) {
			// Hook the migration function to the scheduled event
			add_action( 'run_activity_name_hash_migration_event', array( $this, 'run_activity_name_hash_migration' ) );
			add_action( 'admin_init', array( $this, 'schedule_activity_name_hash_migration' ) );
			
			self::$hooks_registered = true;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'my_enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'my_enqueue_scripts' ), 1 );

		add_action( 'init', array( $this, 'plugins_loaded' ), 1 );

		// Handle legacy scormdriver.js 404 requests
		add_filter( 'pre_handle_404', array( $this, 'handle_legacy_scormdriver_404' ), 10, 2 );

		// Check DB upgrade
		\UCTINCAN\Init::check_upgrade();

		// Require try automator module.
		if ( is_admin() ) {
			new \uncanny_learndash_reporting\Install_Automator();
		}
	}

	public function plugins_loaded() {
		global $uncanny_learndash_reporting;

		if ( ! isset( $uncanny_learndash_reporting ) ) {
			$uncanny_learndash_reporting = new \stdClass();
		}

		$uncanny_learndash_reporting->admin_menu               = new ReportingAdminMenu();
		$uncanny_learndash_reporting->tincanyny_shortcode      = new TinCannyShortcode();
		$uncanny_learndash_reporting->question_analysis_report = new QuestionAnalysisReport();
		$uncanny_learndash_reporting->tincanny_zip_uploader    = new TincannyZipUploader();
		$uncanny_learndash_reporting->cache                    = new Cache();

		// Tin Canny Reporting
		$uncanny_learndash_reporting->reporting                    = new \stdClass();
		$uncanny_learndash_reporting->reporting->purge             = new Purge();
		$uncanny_learndash_reporting->reporting->database          = new Database();
		$uncanny_learndash_reporting->reporting->rest_routes       = new RestRoutes();
		$uncanny_learndash_reporting->reporting->misc_functions    = new MiscFunctions();
		$uncanny_learndash_reporting->reporting->table_data        = new TableData();
		$uncanny_learndash_reporting->reporting->build_report_data = new BuildReportData();
		$uncanny_learndash_reporting->reporting->courses_overview  = new CourseData();
		$uncanny_learndash_reporting->reporting->users_overview    = new UserData();

		// Tin Canny LD Reporting
		$uncanny_learndash_reporting->reporting->learndash = new \stdClass();
		$uncanny_learndash_reporting->reporting->learndash->quiz_module_reports  = new QuizModuleReports();
		$uncanny_learndash_reporting->reporting->learndash->lesson_topic_reports = new LessonTopicReports();
		$uncanny_learndash_reporting->reporting->learndash->group_quiz_report    = new GroupQuizReport();
	}
	/**
	 * @return void
	 */
	public function my_enqueue_scripts() {
		wp_enqueue_script( 'tc_runtime', Config::get_admin_js( 'runtime' ), array(), null, true );
		wp_enqueue_script( 'tc_vendors', Config::get_admin_js( 'vendors' ), array( 'tc_runtime' ), null, true );
	}


	/**
	 * @return void
	 */
	public static function schedule_activity_name_hash_migration() {
		// Check if migration is already complete
		$migration_key = 'activity_name_hash_migration';
		if ( get_option( $migration_key ) ) {
			// Migration is complete, ensure the cron job is cleared
			wp_clear_scheduled_hook( 'run_activity_name_hash_migration_event' );
			return;
		}

		// Check if the cron job is already scheduled
		if ( ! wp_next_scheduled( 'run_activity_name_hash_migration_event' ) ) {
			wp_schedule_event( time(), 'hourly', 'run_activity_name_hash_migration_event' );
		}
	}

	/**
	 * @return void
	 */
	public function run_activity_name_hash_migration() {
		global $wpdb;

		// Table name
		$table_name = $wpdb->prefix . \UCTINCAN\Database\Admin::TABLE_QUIZ;

		// Check if the migration has already run
		$migration_key = 'activity_name_hash_migration';
		if ( get_option( $migration_key ) ) {
			// Unschedule the event if migration is complete
			wp_clear_scheduled_hook( 'run_activity_name_hash_migration_event' );

			return;
		}

		// Get the total number of rows to process
		$total_rows = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );

		if ( ! $total_rows ) {
			// No rows to process, mark migration as complete
			update_option( $migration_key, true );
			wp_clear_scheduled_hook( 'run_activity_name_hash_migration_event' );

			return;
		}

		// Process in batches for better performance
		$batch_size = 5000;
		$offset     = (int) get_option( 'activity_name_hash_migration_offset', 0 );

		// Fetch a batch of records
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, activity_name FROM {$table_name} LIMIT %d OFFSET %d",
			$batch_size,
			$offset
		) );

		if ( empty( $rows ) ) {
			// Mark migration as complete if no more rows to process
			update_option( $migration_key, true );
			wp_clear_scheduled_hook( 'run_activity_name_hash_migration_event' );

			return;
		}

		// Prepare updates
		foreach ( $rows as $row ) {
			$activity_name_hash = md5( sanitize_title( $row->activity_name ) );

			// Update each record
			$wpdb->update(
				$table_name,
				[ 'activity_name_hash' => $activity_name_hash ],
				[ 'id' => $row->id ],
				[ '%s' ],
				[ '%d' ]
			);
		}

		// Update the offset for the next batch
		$offset += $batch_size;
		update_option( 'activity_name_hash_migration_offset', $offset );
	}

	/**
	 * Handle legacy scormdriver.js 404 requests by redirecting to new dist location
	 * Proper fix is to re-upload the module and the new file path will be registered.
	 * This will avoid having to ask legacy customers to update their modules for now.
	 *
	 * @param bool|null $preempt Whether to short-circuit default 404 handling. Default null.
	 * @param \WP_Query $wp_query The query object for the current request.
	 * @return void
	 */
	public function handle_legacy_scormdriver_404($preempt, $wp_query) {

		$request_uri = $_SERVER['REQUEST_URI'] ?? '';

		// Check if the request is for the legacy scormdriver.js path
		if ( false !== strpos( $request_uri, '/src/assets/scripts/scormdriver.js' ) ) {
			$this->redirect_legacy_scormdriver( $request_uri, 'scormdriver.js' );
		}

		// Check if the request is for the legacy scormdriver-sync.js path
		if ( false !== strpos( $request_uri, '/src/assets/scripts/scormdriver-sync.js' ) ) {
			$this->redirect_legacy_scormdriver( $request_uri, 'scormdriver-sync.js' );
		}

		return $preempt;
	}

	/**
	 * Helper method to redirect legacy scormdriver files to new dist location
	 *
	 * @param string $request_uri The original request URI
	 * @param string $filename The filename to redirect to
	 *
	 * @return void - Redirects to the new scormdriver file.
	 */
	private function redirect_legacy_scormdriver( $request_uri, $filename ) {
		// Get query parameters
		$query_params = array();
		if ( false !== strpos( $request_uri, '?' ) ) {
			parse_str( substr( $request_uri, strpos( $request_uri, '?' ) + 1 ), $query_params );
		}

		// Build new URL with query parameters
		$new_url = add_query_arg( $query_params, plugins_url('/src/assets/dist/scripts/' . $filename, UO_REPORTING_FILE) );

		// Handle with redirect / cache will handle this the next request.
		wp_safe_redirect( $new_url, 301 );
		exit;
	}
}
