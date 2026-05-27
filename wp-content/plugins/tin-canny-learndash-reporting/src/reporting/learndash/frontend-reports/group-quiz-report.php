<?php
/**
 * Quiz Report
 *
 * Handles the Quiz Report Shortcodes and Block Wrappers
 *
 * @package uncanny_learndash_reporting
 */

namespace uncanny_learndash_reporting;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class GroupQuizReport
 *
 * @package uncanny_learndash_reporting
 */
class GroupQuizReport extends Config {

	/**
	 * Rest API root path
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private $root_path = 'tincanny/v1';

	/**
	 * Module Version for Assets
	 *
	 * @var string $module_version
	 */
	private $module_version = '0.0.1';

	/**
	 * Array of data to be passed to JS.
	 *
	 * @var array $to_json
	 */
	private $to_json = array();

	/**
	 * Unique Identifier Instance counter.
	 *
	 * @var int $shortcode_instance_count
	 */
	private static $shortcode_instance_count = 0;

	/**
	 * Class constructor
	 */
	public function __construct() {

		$this->run_frontend_hooks();
		add_action( 'rest_api_init', array( $this, 'uo_api' ) );

	}

	/**
	 * Initialize frontend actions and filters
	 *
	 * @return void
	 */
	public function run_frontend_hooks() {
		add_shortcode( 'uotc_ld_quiz_report', array( $this, 'display_quiz_report' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'wp_footer', array( $this, 'print_scripts' ) );
	}

	/**
	 * Display the shortcode
	 *
	 * @param $request
	 *
	 * @return false|string|void
	 */
	public function display_quiz_report( $request ) {

		// Validate the user.
		$user    = wp_get_current_user();
		$user_id = $user->ID;

		// Is the user logged in.
		if ( ! $user_id ) {
			return __( 'Please log in to view the report.', 'uncanny-learndash-reporting' );
		}

		// Does the user have proper access to the report.
		$user_access_role = $this->user_access_role( $user );
		if ( empty( $user_access_role ) ) {
			return __( 'Sorry, you do not have permission to view this report.', 'uncanny-learndash-reporting' );
		}

		// Increment the shortcode instance count.
		$shortcode_instance_count = self::$shortcode_instance_count;
		++$shortcode_instance_count;
		self::$shortcode_instance_count = $shortcode_instance_count;

		$allowed_columns = $this->table_columns_config();

		// Parse the request.
		$request = shortcode_atts(
			array(
				'quiz-orderby'   => 'title',
				'quiz-order'     => 'ASC',
				'columns'        => implode( ',', array_keys( $allowed_columns ) ),
				'score-type'     => 'percent', //percent|points
				'orderby_column' => esc_attr__( 'Date', 'uncanny-learndash-reporting' ), // The ID of the column used to sort
				'order_column'   => 'desc', // Designates the ascending or descending order of the ‘orderby‘ parameter
			),
			$request
		);

		// Validate the request params.
		$score_type = $request['score-type'];
		if ( ! in_array( $score_type, array( 'percent', 'points' ), true ) ) {
			$score_type = 'percent';
		}

		$quiz_orderby = $request['quiz-orderby'];
		if ( ! in_array( $quiz_orderby, array( 'ID', 'title', 'date', 'menu_order' ), true ) ) {
			$quiz_orderby = 'title';
		}

		$quiz_order = $request['quiz-order'];
		if ( ! in_array( $quiz_order, array( 'ASC', 'DESC' ), true ) ) {
			$quiz_order = 'ASC';
		}

		// Columns that will be displayed in the table
		$table_columns = array();

		// Set column visibility
		if ( isset( $request['columns'] ) && ! empty( $request['columns'] ) ) {

			// Columns that the shortcode requested to show
			$columns = explode( ',', $request['columns'] );
			$columns = array_filter( array_map( 'trim', $columns ) );

			if ( ! empty( $columns ) ) {
				foreach ( $columns as $column ) {
					if ( array_key_exists( $column, $allowed_columns ) ) {
						$table_columns[] = $column;
					}
				}
			}
		}

		if ( empty( $table_columns ) ) {
			$table_columns = array_keys( $allowed_columns );
		}

		// Load Selection options for group
		$group_options = $this->get_group_options( $user_id, $user_access_role );
		$html          = $this->generate_quiz_report_html( $shortcode_instance_count, $group_options );

		// Load config for JS
		$this->to_json[] = array(
			'instance'  => $shortcode_instance_count,
			'orderBy'   => $quiz_orderby,
			'order'     => $quiz_order,
			'scoreType' => $score_type,
			'columns'   => $table_columns,
		);

		return $html;
	}

	/**
	 * Register JS and CSS for the Quiz Report
	 *
	 * @return void
	 */
	public function register_assets() {

		global $post;

		if ( empty( $post ) ) {
			return;
		}

		wp_enqueue_style(
			'datatables-styles',
			self::get_admin_css( 'datatables.min.css' ),
			array(),
			$this->module_version
		);

		$dependencies = array( 'jquery' );

		// Register JS and CSS for LearnDash
		$filepath = \SFWD_LMS::get_template( 'learndash_template_script.js', null, null, true );
		if ( ! empty( $filepath ) ) {
			global $learndash_assets_loaded;
			wp_register_script( 'uo_learndash_template_script_js', learndash_template_url_from_path( $filepath ), array(), '1.0', true );
			$learndash_assets_loaded['scripts']['learndash_template_script_js'] = __FUNCTION__;
			$dependencies[] = 'uo_learndash_template_script_js';
		}

		// Register JS and CSS for Group Report
		wp_register_script(
			'uotc-group-quiz-report',
			self::get_admin_js( 'tincanny-reports' ),
			array( 'jquery', 'tc_runtime', 'tc_vendors' ),
			$dependencies,
			true
		);

		wp_enqueue_style(
			'uotc-group-quiz-report',
			self::get_admin_css( 'group-quiz-report-module.css' ),
			array( 'datatables-styles' ),
			$this->module_version
		);

	}

	/**
	 * Load JS and CSS for the Quiz Report
	 *
	 * @return mixed void || HTML - JS
	 */
	public function print_scripts() {

		if ( empty( $this->to_json ) ) {
			return;
		}

		// Load the JS and CSS for DataTables
		wp_enqueue_style( 'datatables-styles' );

		// Load the JS and CSS for Report
		wp_enqueue_style( 'uotc-group-quiz-report' );
		wp_enqueue_script( 'uotc-group-quiz-report' );

		// Load JS for LearnDash quiz modal
		$data = array( 'json' => json_encode( array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) ) );
		wp_enqueue_script( 'uo_learndash_template_script_js' );
		wp_localize_script( 'uo_learndash_template_script_js', 'sfwd_data', $data );

		// Localize the JS
		$data = array(
			'root'             => esc_url_raw( rest_url() . 'tincanny/v1' . '/' ), // phpcs:ignore Generic.Strings.UnnecessaryStringConcat.Found
			'nonce'            => \wp_create_nonce( 'wp_rest' ),
			'currentUser'      => get_current_user_id(),
			'i18n'             => $this->get_frontend_localized_strings(),
			'statistic_action' => version_compare( LEARNDASH_VERSION, '3.2', '>=' ) ? 'wp_pro_quiz_admin_ajax_statistic_load_user' : 'wp_pro_quiz_admin_ajax',
			'instances'        => $this->to_json,
			'columns'          => $this->table_columns_config(),
			'loadingAnimation' => $this->loading_animation(),
		);

		wp_localize_script( 'uotc-group-quiz-report', 'uotcGroupQuizReport', $data );

		$this->show_modal_window();
	}

	/**
	 * Register rest api endpoints
	 *
	 * @return void
	 */
	public function uo_api() {

		register_rest_route(
			$this->root_path,
			'/get-quiz-report-data/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_get_quiz_report_data' ),
				'permission_callback' => array( $this, 'tincanny_permissions' ),
			)
		);

		register_rest_route(
			$this->root_path,
			'/get-quiz-report-quiz-options/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_get_quiz_options' ),
				'permission_callback' => array( $this, 'tincanny_permissions' ),
			)
		);
	}

	/**
	 * Get the quiz dropdown options
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return array|mixed|null|WP_REST_Response
	 */
	public function rest_get_quiz_options( \WP_REST_Request $request ) {

		$group_id = $request->has_param( 'group_id' ) ? (int) $request->get_param( 'group_id' ) : 0;

		if ( empty( $group_id ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => sprintf(
					// translators: %s is the group label
						__( 'invalid %s id supplied', 'uncanny-learndash-reporting' ),
						\LearnDash_Custom_Label::get_label( 'group' )
					),
					'options' => array(
						array(
							'value' => '',
							'text'  => __( 'No results', 'uncanny-learndash-reporting' ),
						),
					),
				),
				200
			);
		}

		$user             = wp_get_current_user();
		$user_access_role = $this->user_access_role( $user );
		$options          = $this->get_quiz_options( $group_id, $user->ID, $user_access_role );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => '',
				'options' => $options,
			),
			200
		);
	}

	/**
	 * Get quiz report data
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return array|mixed|null|WP_REST_Response
	 */
	public function rest_get_quiz_report_data( \WP_REST_Request $request ) {

		global $learndash_shortcode_used;

		// Get the request data.
		$group_id   = $request->has_param( 'group_id' ) ? (int) $request->get_param( 'group_id' ) : 0;
		$quiz_id    = $request->has_param( 'quiz_id' ) ? (int) $request->get_param( 'quiz_id' ) : 0;
		$score_type = $request->has_param( 'score_type' ) ? sanitize_text_field( $request->get_param( 'score_type' ) ) : 'percent';
		$score_type = in_array( $score_type, array( 'percent', 'points' ), true ) ? $score_type : 'percent';

		// Validate inputs.
		if ( empty( $group_id ) || empty( $quiz_id ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => sprintf(
					// translators: %s is the label of the invalid parameter
						__( 'invalid %s id supplied', 'uncanny-learndash-reporting' ),
						empty( $group_id ) ? \LearnDash_Custom_Label::get_label( 'group' ) : \LearnDash_Custom_Label::get_label( 'quiz' )
					),
					'results' => array(),
				),
				200
			);
		}

		// Get the user.
		$user            = wp_get_current_user();
		$user_group_data = $this->get_user_group_data( $user->ID );
		$group_user_ids  = array();

		// Validate the course_ids for the quiz.
		$course_ids = $this->get_course_ids_for_quiz( $quiz_id );
		if ( empty( $course_ids ) ) {
			return $this->no_users_found_response();
		}

		// Get group user IDs.
		if ( -1 === $group_id || -2 === $group_id ) {
			// Admin or Group Leader.
			foreach ( $user_group_data as $user_group_id => $group_data ) {
				// Check if group has course with quiz.
				if ( ! empty( array_intersect( $group_data['groups_course_access'], $course_ids ) ) ) {
					$group_user_ids = array_merge( $group_user_ids, $group_data['groups_user'] );
				} else {
					// Remove group from list.
					unset( $user_group_data[ $user_group_id ] );
				}
			}

			// Admins can see all users even if they are not in a group.
			if ( intval( '-1' ) === intval( $group_id ) ) {
				$users_not_in_group = $this->get_non_group_course_users( $course_ids, $group_user_ids );
				if ( ! empty( $users_not_in_group ) ) {
					// Add course users not in a group.
					$group_user_ids = array_merge( $group_user_ids, $users_not_in_group );
					// Add a fake group to the user group data for filtering below.
					$user_group_data[ '-1' ] = array(
						'groups_user'          => $users_not_in_group,
						'groups_course_access' => $course_ids,
					);
				}
			}

		} else {
			// Valid Group ID.
			if ( array_key_exists( $group_id, $user_group_data ) ) {
				// Single Group.
				$user_group_data = array( $group_id => $user_group_data[ $group_id ] );
				if ( ! empty( array_intersect( $user_group_data[ $group_id ]['groups_course_access'], $course_ids ) ) ) {
					$group_user_ids = $user_group_data[ $group_id ]['groups_user'];
				}
			}
		}

		$all_user_data = array();
		if ( ! empty( $group_user_ids ) ) {
			$group_user_ids = array_unique( $group_user_ids );
			// Split the user IDs into chunks of 1000
			$chunks = array_chunk( $group_user_ids, 1000 );
			foreach ( $chunks as $chunk ) {
				$data = $this->get_report_user_data( $chunk );
				if ( ! empty( $data ) ) {
					$all_user_data = $all_user_data + $data;
				}
			}
		}

		// No users found.
		if ( empty( $all_user_data ) ) {
			return $this->no_users_found_response();
		}

		$users_added = array();
		$no_attempts = array();
		$results     = array();
		foreach ( $user_group_data as $user_group_id => $group_data ) {
			$group_user_ids         = $group_data['groups_user'];
			$group_courses          = $group_data['groups_course_access'];
			$hide_unattempted_users = apply_filters( 'uotc_quiz_report_hide_unattempted_users', false, $quiz_id, $user_group_id );
			foreach ( $group_user_ids as $group_user_id ) {
				$user_data = $all_user_data[ $group_user_id ];
				$quiz_data = is_null( $user_data->quizzes ) ? array() : maybe_unserialize( $user_data->quizzes );
				if ( empty( $quiz_data ) && $hide_unattempted_users ) {
					continue;
				}
				$user_attempt_added = false;
				foreach ( $quiz_data as $quiz ) {

					$user_quiz_id = is_array( $quiz ) && isset( $quiz['quiz'] ) ? (int) $quiz['quiz'] : false;

					// Validate the quiz ID.
					if ( $user_quiz_id !== $quiz_id ) {
						continue;
					}
					// Validate the course ID is in the group.
					if ( ! empty( $group_courses ) && ! in_array( $quiz['course'], $group_courses, true ) ) {
						continue;
					}

					// Maybe populate the quiz percentage.
					$quiz['percentage'] = ! empty( $quiz['percentage'] ) ? $quiz['percentage'] : ( ! empty( $quiz['count'] ) ? $quiz['score'] * 100 / $quiz['count'] : 0 );

					// Add the quiz data to the results.
					$results[] = array(
						'ID'         => $user_data->ID,
						'user_name'  => $user_data->user_login,
						'user_email' => $user_data->user_email,
						'first_name' => $user_data->first_name,
						'last_name'  => $user_data->last_name,
						'quiz_score' => apply_filters( 'uotc_quiz_report_user_score', $this->get_quiz_score( $quiz, $score_type ), $user_data, $quiz, $score_type ),
						'quiz_modal' => $this->get_quiz_modal_link( $user_data->ID, $quiz ),
						'quiz_date'  => $this->get_quiz_date_column( $quiz ),
					);

					$user_attempt_added = true;
				}

				if ( $user_attempt_added ) {
					$users_added[] = $user_data->ID;
				} elseif ( ! $hide_unattempted_users ) {
					$no_attempts[] = $user_data->ID;
				}
			} // End foreach() User
		} // End foreach() Group

		// Maybe add users with no attempts.
		if ( ! empty( $no_attempts ) ) {
			$no_attempts = array_unique( $no_attempts );
			$no_attempts = array_diff( $no_attempts, $users_added );
			foreach ( $no_attempts as $user_id ) {
				$user_data = $all_user_data[ $user_id ];
				$results[] = array(
					'ID'         => $user_data->ID,
					'user_name'  => $user_data->user_login,
					'user_email' => $user_data->user_email,
					'first_name' => $user_data->first_name,
					'last_name'  => $user_data->last_name,
					'quiz_score' => apply_filters( 'uotc_quiz_report_quiz_score_not_attempted_label', __( 'Not Attempted', 'uncanny-learndash-reporting' ), $user_data ),
					'quiz_modal' => apply_filters( 'uotc_quiz_report_quiz_modal_not_attempted_label', __( 'Not Attempted', 'uncanny-learndash-reporting' ), $user_data ),
					'quiz_date'  => apply_filters( 'uotc_quiz_report_quiz_date_not_attempted_label', __( 'Not Attempted', 'uncanny-learndash-reporting' ), $user_data ),
				);
			}
		}

		$results = apply_filters(
			'uotc_rest_api_get_quiz_data',
			$results,
			array(
				'group_id'   => $group_id,
				'quiz_id'    => $quiz_id,
				'score_type' => $score_type,
			)
		);

		// No users found.
		if ( empty( $results ) ) {
			return $this->no_users_found_response();
		}

		$learndash_shortcode_used = true;

		// Return the results.
		return new \WP_REST_Response(
			array(
				'success' => true,
				// count results
				'message' => sprintf(
				// translators: %1$s is the number of results, %2$s is the quiz label
					_n( '%1$s user found for selected %2$s', '%1$s users found for selected %2$s', count( $results ), 'uncanny-learndash-reporting' ),
					count( $results ),
					\LearnDash_Custom_Label::get_label( 'quiz' )
				),
				'results' => $results,
			),
			200
		);
	}

	/**
	 * REST Response for no users found
	 *
	 * @return \WP_REST_Response
	 */
	private function no_users_found_response() {
		return new \WP_REST_Response(
			array(
				'success' => false,
				'message' => sprintf(
				// translators: %s is the quiz label
					__( 'No users found for selected %s', 'uncanny-learndash-reporting' ),
					\LearnDash_Custom_Label::get_label( 'quiz' )
				),
				'results' => array(),
			),
			200
		);
	}

	/**
	 * Table Columns Config
	 *
	 * @return array
	 */
	private function table_columns_config() {
		return array(
			'user_name'  => __( 'Username', 'uncanny-learndash-reporting' ),
			'first_name' => __( 'First Name', 'uncanny-learndash-reporting' ),
			'last_name'  => __( 'Last Name', 'uncanny-learndash-reporting' ),
			'user_email' => __( 'Email', 'uncanny-learndash-reporting' ),
			'quiz_score' => sprintf(
			// translators: %s is a quiz label
				_x( '%s score', 'Quiz score', 'uncanny-learndash-reporting' ),
				\LearnDash_Custom_Label::get_label( 'quiz' )
			),
			'quiz_modal' => __( 'Detailed report', 'uncanny-learndash-reporting' ),
			'quiz_date'  => __( 'Date', 'uncanny-learndash-reporting' ),
		);
	}

	/**
	 * Get localized strings for the frontend
	 *
	 * @return mixed|void
	 */
	private function get_frontend_localized_strings() {

		$i18n = array(
			'table_language'     => array(
				'info'              => sprintf(
				/* translators: %1$s is the start number, %2$s is the end number, and %3$s is the total number of entries */
					_x( 'Showing %1$s to %2$s of %3$s entries', '%1$s is the start number, %2$s is the end number, and %3$s is the total number of entries', 'uncanny-learndash-reporting' ),
					'_START_',
					'_END_',
					'_TOTAL_'
				),
				'infoEmpty'         => __( 'Showing 0 to 0 of 0 entries', 'uncanny-learndash-reporting' ),
				'infoFiltered'      => sprintf(
				/* translators: %s is a number */
					_x( '(filtered from %s total entries)', '%s is a number', 'uncanny-learndash-reporting' ),
					'_MAX_'
				),
				'infoPostFix'       => '',
				'lengthMenu'        => sprintf(
				/* translators: %s is a number */
					_x( 'Show %s entries', 'Table', 'uncanny-learndash-reporting' ),
					'_MENU_'
				),
				'loadingRecords'    => __( 'Loading...', 'uncanny-learndash-reporting' ),
				'processing'        => __( 'Processing...', 'uncanny-learndash-reporting' ),
				'search'            => '_INPUT_',
				'searchPlaceholder' => __( 'Search by username, name, email, date or score', 'uncanny-learndash-reporting' ),
				'zeroRecords'       => __( 'No quiz results found', 'uncanny-learndash-reporting' ),
				'paginate'          => array(
					'first'    => __( 'First', 'uncanny-learndash-reporting' ),
					'last'     => __( 'Last', 'uncanny-learndash-reporting' ),
					'next'     => __( 'Next', 'uncanny-learndash-reporting' ),
					'previous' => __( 'Previous', 'uncanny-learndash-reporting' ),
				),
				'aria'              => array(
					'sortAscending'  => sprintf( ': %s', __( 'activate to sort column ascending', 'uncanny-learndash-reporting' ) ),
					'sortDescending' => sprintf( ': %s', __( 'activate to sort column descending', 'uncanny-learndash-reporting' ) ),
				),
			),
			'customColumnLabels' => array(
				'customizeColumns'     => __( 'Customize columns', 'uncanny-learndash-reporting' ),
				'hideCustomizeColumns' => __( 'Hide customize columns', 'uncanny-learndash-reporting' ),
			),
			'buttons'            => array(
				'csv'         => __( 'CSV', 'uncanny-learndash-reporting' ),
				'exportCSV'   => __( 'CSV export', 'uncanny-learndash-reporting' ),
				'excel'       => __( 'Excel', 'uncanny-learndash-reporting' ),
				'exportExcel' => __( 'Excel export', 'uncanny-learndash-reporting' ),
			),
			'all'                => __( 'All', 'uncanny-learndash-reporting' ),
			'noQuizResults'      => __( 'No results', 'uncanny-learndash-reporting' ),
			'cssSelectors'       => array(
				'labelLoading' => 'label-loading',
				'tableLoading' => 'reporting-status-loading-animation-wrap',
			),
		);

		return apply_filters( 'quiz_report_table_strings', $i18n );
	}

	/**
	 * Generate the HTML for the quiz report
	 *
	 * @return string - Rendered HTML or error message
	 */
	private function generate_quiz_report_html( $shortcode_instance_count, $group_options ) {

		// Default Quiz Options.
		$quiz_disabled = 'disabled';
		$quiz_options = array(
			array(
				'value' => '',
				'text'  => __( 'No results', 'uncanny-learndash-reporting' ),
			),
		);

		// Check for single admin any group no group option.
		if ( 1 === count( $group_options ) && intval( '-1' ) === intval( $group_options[0]['value'] ) ) {
			$quiz_disabled = '';
			$quiz_options  = $this->get_quiz_options( '-1', get_current_user_id(), 'administrator' );
		}

		ob_start();
		?>

		<div class="uotc-report uo-group-quiz-report" data-instance="<?php echo esc_attr( $shortcode_instance_count ); ?>">
			<div class="uo-row uotc-report-section uotc-report-selection">
				<div class="group-management-form">
					<div class="uotc-report-select-filters">
						<div class="uo-row uotc-report-select-filter">
							<div class="uo-select">
								<label><?php echo esc_html( ucfirst( \LearnDash_Custom_Label::get_label( 'group' ) ) ); ?></label>
								<select class="uo-group-quiz-report-group change-group-management-form">
									<?php foreach ( $group_options as $group ) { ?>
										<option value="<?php echo esc_attr( $group['value'] ); ?>"><?php echo esc_html( $group['text'] ); ?></option>
									<?php } ?>
								</select>
							</div>
						</div>
						<div class="uo-row uotc-report-select-filter">
							<div class="uo-select">
								<label class="uo-group-quiz-report-quizzes-label"><?php echo esc_html( ucfirst( \LearnDash_Custom_Label::get_label( 'quiz' ) ) ); ?></label>
								<select class="uo-group-quiz-report-quizzes change-group-management-form uotc-report-select-filter-select--empty" <?php echo esc_attr($quiz_disabled); ?>>
									<?php foreach ( $quiz_options as $quiz ) { ?>
										<option value="<?php echo esc_attr( $quiz['value'] ); ?>"><?php echo esc_html( $quiz['text'] ); ?></option>
									<?php } ?>
								</select>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="uotc-report__table">
				<table id="uo-group-quiz-report-table" class="display responsive no-wrap dataTable no-footer dtr-inline" cellspacing="0" width="100%">
				</table>
			</div>
		</div>

		<?php
		return ob_get_clean();
	}

	/**
	 * Get the HTML for the loading animation.
	 *
	 * @return string
	 */
	private function loading_animation() {
		$html = '<div class="reporting-status-loading-animation-wrap">';
		$html .= '<div class="reporting-status reporting-status--loading">';
		$html .= '<div class="reporting-status__icon"></div>';
		$html .= '<div class="reporting-status__text">';
		$html .= __( 'Loading', 'uncanny-learndash-reporting' );
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		return $html;
	}

	/**
	 * Get Group options data
	 *
	 * @param int $user_id - The user id
	 * @param string $user_role - The role that granted access to the report
	 *
	 * @return bool|string|void
	 */
	private function get_group_options( $user_id, $user_role ) {

		$user_groups  = $this->get_user_group_data( $user_id );
		$group_label  = \LearnDash_Custom_Label::get_label( 'group' );
		$groups_label = \LearnDash_Custom_Label::get_label( 'groups' );
		$options      = array();
		$admin_option = false;

		// Generate admin option to be used in the dropdown.
		if ( 'administrator' === $user_role ) {
			$admin_option = array(
				'value' => '-1',
				'text'  => sprintf(
				// translators: %1$s: group Label
					__( 'Any %1$s, including no %1$s', 'uncanny-learndash-reporting' ),
					strtolower( $group_label )
				),
			);
		}

		// Groups found.
		if ( ! empty( $user_groups ) ) {

			// Add placeholders.
			if ( count( $user_groups ) > 1 ) {
				$options[] = array(
					'value' => 0,
					'text'  => sprintf(
					// translators: %s: Group Label
						__( 'Select a %s', 'uncanny-learndash-reporting' ),
						strtolower( $group_label )
					),
				);
			}

			// Allow Admin Any Group including No group.
			if ( $admin_option ) {
				$options[] = $admin_option;
			}

			// Allow Group Leader All Groups.
			if ( 'group_leader' === $user_role ) {
				$options[] = array(
					'value' => -2,
					'text'  => sprintf(
					// translators: %s: Plural Groups Label
						__( 'All %s', 'uncanny-learndash-reporting' ),
						strtolower( $groups_label )
					),
				);
			}

			foreach ( $user_groups as $group ) {
				$options[] = array(
					'value' => $group['ID'],
					'text'  => $group['post_title'],
				);
			}
		}

		// No groups found.
		if ( empty( $user_groups ) ) {
			// If Admin, show Any Group including No group.
			if ( $admin_option ) {
				$options[] = $admin_option;
			} else {
				// No groups found.
				$options[] = array(
					'value' => 0,
					'text'  => sprintf(
					// translators: %s: Plural Groups Label
						__( 'No %s', 'uncanny-learndash-reporting' ),
						strtolower( $groups_label )
					),
				);
			}
		}

		return apply_filters( 'uotc_quiz_report_group_options', $options, $user_id, $user_role );
	}

	/**
	 * Get Quiz options data
	 *
	 * @param int $group_id - The group id
	 * @param int $user_id - The user id
	 * @param string $user_role - The role that granted access to the report
	 *
	 * @return array
	 */
	private function get_quiz_options( $group_id, $user_id, $user_role ) {

		$options    = array();
		$course_ids = false;

		// Build Quiz Query Args
		$args = array(
			'post_type' => 'sfwd-quiz',
			'nopaging'  => true,
			'orderby'   => 'title',
			'order'     => 'ASC',
			'fields'    => 'ids',
		);

		switch ( $group_id ) {
			// Admin may filter by any groups.
			case -1:
				if ( 'administrator' === $user_role ) {
					$user_group_data = $this->get_user_group_data( $user_id );
					$course_ids      = 'all';
				}
				break;
			// Group Leader may filter by any groups they lead.
			case -2:
				if ( 'group_leader' === $user_role ) {
					$user_group_data = $this->get_user_group_data( $user_id );
					$course_ids      = array();
					foreach ( $user_group_data as $group_id => $group_data ) {
						$course_ids = array_merge( $course_ids, $group_data['groups_course_access'] );
					}
				}
				break;
			default:
				// Valid Group ID
				if ( $group_id > 0 ) {
					$user_group_data = $this->get_user_group_data( $user_id );
					if ( array_key_exists( $group_id, $user_group_data ) ) {
						$course_ids = $user_group_data[ $group_id ]['groups_course_access'];
					}
				}
				break;
		}

		$filter_shared = false;

		// Build Array of Course IDs
		if ( ! empty( $course_ids ) ) {

			// Add Meta Query to Query Args
			if ( is_array( $course_ids ) ) {
				// Check if shared steps is enabled.
				$filter_shared = $this->is_course_shared_steps_enabled();
				// No shared steps run normal query.
				if ( ! $filter_shared ) {
					$args['meta_query'] = array(
						array(
							'key'     => 'course_id',
							'compare' => 'IN',
							'value'   => $course_ids,
						),
					);
				}
			}

			// Allow Query Args to be filtered
			$args    = apply_filters( 'uotc_group_quiz_query_args', $args, $group_id, $user_id, $user_role );
			$quizzes = get_posts( $args );

			// Filter shared steps quizzes.
			if ( $filter_shared ) {
				$quizzes = $this->filter_shared_steps_quizzes( $quizzes, $course_ids );
			}

			if ( ! empty( $quizzes ) ) {
				foreach ( $quizzes as $quiz_id ) {
					$options[] = array(
						'value' => $quiz_id,
						'text'  => get_the_title( $quiz_id ),
					);
				}
			}
		}

		// No quizzes found.
		if ( empty( $options ) ) {
			$options[] = array(
				'value' => '',
				'text'  => sprintf(
				// translators: %s is plural quizzes label
					__( 'No %s available', 'uncanny-learndash-reporting' ),
					\LearnDash_Custom_Label::get_label( 'quizzes' )
				),
			);
		} else {
			// Add placeholder.
			$options = array_merge(
				array(
					array(
						'value' => '',
						'text'  => sprintf(
						// translators: %s is plural quizzes label
							__( 'Select a %s', 'uncanny-learndash-reporting' ),
							\LearnDash_Custom_Label::get_label( 'quiz' )
						),
					),
				),
				$options
			);
		}

		return apply_filters( 'uotc_quiz_report_quiz_options', $options, $group_id, $user_id, $user_role );
	}

	/**
	 * Filter shared steps quizzes.
	 *
	 * @return bool
	 */
	private function filter_shared_steps_quizzes( $quizzes, $course_ids ) {
		$filtered_quizzes = array();
		foreach ( $quizzes as $quiz_id ) {
			$courses = $this->get_course_ids_for_quiz( $quiz_id );
			if ( ! empty( array_intersect( $courses, $course_ids ) ) ) {
				$filtered_quizzes[] = $quiz_id;
			}
		}
		return $filtered_quizzes;
	}

	/**
	 * Get the report user data
	 *
	 * @param array $user_ids - Array of user IDs
	 *
	 * @return array
	 */
	private function get_report_user_data( $user_ids ) {

		if ( empty( $user_ids ) ) {
			return array();
		}

		$user_ids = implode( ',', array_map( 'intval', $user_ids ) );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		global $wpdb;
		$query = "SELECT
		    u.ID,
		    u.user_login,
		    u.user_email,
			MAX(CASE WHEN um1.meta_key = 'first_name' THEN um1.meta_value ELSE NULL END) AS first_name,
			MAX(CASE WHEN um1.meta_key = 'last_name' THEN um1.meta_value ELSE NULL END) AS last_name,
			MAX(CASE WHEN um1.meta_key = '_sfwd-quizzes' THEN um1.meta_value ELSE NULL END) AS quizzes
		FROM
		    $wpdb->users u
		LEFT JOIN
		    $wpdb->usermeta um1 ON (um1.user_id = u.ID)
		WHERE 1=1
		AND u.ID IN ($user_ids)
		GROUP BY
		    u.ID";

		return $wpdb->get_results( $query, OBJECT_K );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Get the quiz modal link
	 *
	 * @param int $user_id
	 * @param array $quiz - Single Quiz attempt from _swfd-quizzes meta
	 */
	private function get_quiz_modal_link( $user_id, $quiz ) {
		$ref = isset( $quiz['statistic_ref_id'] ) ? intval( $quiz['statistic_ref_id'] ) : false;
		if ( $ref ) {
			$html = '<a class="user_statistic"
						 data-statistic_nonce="' . esc_attr( wp_create_nonce( 'statistic_nonce_' . $ref . '_' . get_current_user_id() . '_' . $user_id ) ) . '"
						 data-user_id="' . esc_attr( $user_id ) . '"
						 data-quiz_id="' . esc_attr( $quiz['pro_quizid'] ) . '"
						 data-ref_id="' . esc_attr( $ref ) . '"
						 data-nonce="' . esc_attr( wp_create_nonce( 'wpProQuiz_nonce' ) ) . '"
						 href="#">';
			$html .= '<div class="statistic_icon"></div>';
			$html .= '</a>';
		} else {
			$html = __( 'No stats recorded', 'uncanny-learndash-reporting' );
		}
		return $html;
	}

	/**
	 * Get the quiz score
	 *
	 * @param array $quiz - Single Quiz attempt from _swfd-quizzes meta
	 * @param string $score_type - The score type
	 *
	 * @return string
	 */
	private function get_quiz_score( $quiz, $score_type ) {
		// Check if the quiz requires grading and has been graded.
		if ( isset( $quiz['has_graded'] ) && true === $quiz['has_graded'] && true === \LD_QuizPro::quiz_attempt_has_ungraded_question( $quiz ) ) {
			$score = _x( 'Pending', 'Pending Certificate Status Label', 'learndash' );
		} else {
			if ( 'percent' === $score_type ) {
				$score = round( $quiz['percentage'], 2 ) . '%';
			} elseif ( 'points' === $score_type ) {
				$score = sprintf( '%d/%d', $quiz['points'], $quiz['total_points'] );
			}
		}
		return $score;
	}

	/**
	 * Get the quiz date column
	 *
	 * @param array $quiz - Single Quiz attempt from _swfd-quizzes meta
	 *
	 * @return string
	 */
	private function get_quiz_date_column( $quiz ) {
		$date = learndash_adjust_date_time_display( $quiz['time'] );
		return ! empty( $date ) ? '<span class="ulg-hidden-data" style="display: none;">' . $quiz['time'] . '</span>' . $date : '';
	}

	/**
	 * Show the modal window
	 *
	 * @return string - HTML
	 */
	private function show_modal_window() {
		$icon = false;
		if ( defined( 'LEARNDASH_LMS_PLUGIN_URL' ) && function_exists( 'learndash_is_active_theme' ) && learndash_is_active_theme( 'ld30' ) ) {
			$icon = LEARNDASH_LMS_PLUGIN_URL . 'themes/legacy/templates/images/statistics-icon-small.png';
		}
		// Add CSS for the modal window.
		if ( $icon ) {
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

		// Load the modal window.
		\LD_QuizPro::showModalWindow();
	}

	/**
	 * Extract all possible course IDs for a quiz
	 *
	 * @param int $quiz_id
	 *
	 * @return array
	 */
	private function get_course_ids_for_quiz( $quiz_id ) {

		$course_ids = array();
		global $wpdb;

		// Get post meta course_id for quiz
		$course_id = get_post_meta( $quiz_id, 'course_id', true );
		if ( ! empty( $course_id ) ) {
			$course_ids[] = absint( $course_id );
		}

		// Run like query on ld_course_% meta_key
		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT meta_value FROM $wpdb->postmeta WHERE post_id = %d AND meta_key LIKE %s",
				$quiz_id,
				'ld_course_%'
			)
		);

		// Add course_ids to array
		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				$course_ids[] = absint( $result );
			}
		}

		return array_unique( $course_ids );
	}

	/**
	 * Get current user group data
	 *
	 * @param $user_id
	 *
	 * @return array
	 */
	private function get_user_group_data( $user_id ) {
		static $group_data = array();
		if ( ! isset( $group_data[ $user_id ] ) ) {
			$group_data[ $user_id ] = array();
			$access_role = $this->user_access_role( get_user_by( 'ID', $user_id ) );
			// Leader or Admin.
			if ( ! empty( $access_role ) ) {
				$admin_access = 'group_leader' === $access_role ? false : true;
				$group_data[ $user_id ] = CourseData::get_groups_list( $user_id, $admin_access );
			}
		}
		return $group_data[ $user_id ];
	}

	/**
	 * Get course users not in any group
	 *
	 * @param array $course_ids
	 * @param array $group_user_ids
	 *
	 * @return array
	 */
	private function get_non_group_course_users( $course_ids, $group_user_ids ) {

		if ( empty( $course_ids ) ) {
			return array();
		}

		$course_user_ids = array();
		foreach ( $course_ids as $course_id ) {
			$user_ids = CourseData::course_user_access( $course_id );
			if ( ! empty( $user_ids ) ) {
				$course_user_ids = array_merge( $course_user_ids, $user_ids );
			}
		}

		if ( empty( $course_user_ids ) ) {
			return array();
		}

		// Remove IDs that are already in a group.
		$course_user_ids = array_diff( $course_user_ids, $group_user_ids );

		return array_unique( $course_user_ids );
	}

	/**
	 * Validate permissions for the rest api
	 *
	 * @return bool|\WP_Error
	 */
	public function tincanny_permissions() {

		$user             = wp_get_current_user();
		$user_access_role = $this->user_access_role( $user );
		if ( empty ( $user_access_role ) ) {
			return new \WP_Error( 'rest_forbidden', esc_html__( 'Sorry, you do not have permission to view this report.', 'uncanny-learndash-reporting' ) );
		}

		return true;
	}

	/**
	 * Check if the user has access to the quiz report
	 *
	 * @param WP_User $user
	 *
	 * @return mixed bool false | User role
	 */
	private function user_access_role( $user ) {

		if ( ! $user instanceof \WP_User ) {
			return false;
		}

		// Allow role to be filtered
		$allowed_roles = apply_filters(
			'uotc_ld_allowed_roles',
			array(
				'administrator',
				'group_leader',
			)
		);

		// Check if the user is an administrator
		if ( in_array( 'administrator', $user->roles, true ) ) {
			return 'administrator';
		}

		// Check if the user is a group leader
		if ( in_array( 'group_leader', $user->roles, true ) ) {
			return 'group_leader';
		}

		// Check if user has allowed role
		foreach ( $allowed_roles as $role ) {
			if ( in_array( $role, $user->roles, true ) ) {
				return 'administrator'; // Return admin role for custom roles.
			}
		}

		return false;
	}

	/**
	 * Check if course shared steps is enabled
	 *
	 * @return bool
	 */
	private function is_course_shared_steps_enabled() {
		if ( function_exists( 'learndash_is_course_shared_steps_enabled' ) ) {
			return learndash_is_course_shared_steps_enabled();
		}
		return false;
	}
}
