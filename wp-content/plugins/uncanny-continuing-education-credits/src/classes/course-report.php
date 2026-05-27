<?php

namespace uncanny_ceu;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class CourseReport
 *
 * @package uncanny_ceu
 */
class CourseReport {

	/**
	 * Script handles.
	 *
	 * @var array
	 */
	private $scripts = array(
		'datepicker'            => 'jquery-ui-datepicker',
		'select2_js'            => 'ucec-course-report-select2',
		'select2_css'           => 'ucec-course-report-select2',
		'datatables_js'         => 'ucec-course-report-datatables',
		'datatables_editor_js'  => 'ucec-course-report-datatables-editor',
		'datatables_buttons_js' => 'ucec-course-report-datatables-buttons',
		'datatables_select_js'  => 'ucec-course-report-datatables-select',
		'moment_js'             => 'ucec-course-report-moment',
		'datatables_css'        => 'ucec-course-report-datatables',
		'datatables_editor_css' => 'ucec-course-report-datatables-editor-css',
		'global_js'             => 'ucec-course-report',
		'global_css'            => 'ucec-course-report',
	);

	/**
	 * Users CEUs.
	 *
	 * @var string
	 */
	private $root_path = 'ceu/v2';

	/**
	 * API data for localizaton.
	 *
	 * @var array
	 */
	private $api_data = array();

	/**
	 * User ID.
	 *
	 * @var int
	 */
	protected $user = 0;

	/**
	 * Date filter type ( week, month, quarter, half_year, year, all, custom )
	 *
	 * @var string
	 */
	protected $date = '';

	/**
	 * Unix start date
	 *
	 * @var string
	 */
	protected $unix_start = '';

	/**
	 * Unix end date
	 *
	 * @var string
	 */
	protected $unix_end = '';

	/**
	 * Filter start date
	 *
	 * @var bool
	 */
	protected $start_date = false;

	/**
	 * Filter end date
	 *
	 * @var bool
	 */
	protected $end_date = false;

	/**
	 * Users ceus - array users CEU data.
	 *
	 * @var array
	 */
	protected $users_ceus = array();

	/**
	 * The role of the current user viewing the report.
	 *
	 * @var mixed (String||Null)
	 */
	protected $user_role = null;

	/**
	 * Group leader data.
	 *
	 * @var mixed (Array||Null)
	 */
	protected $group_leader_data = null;

	/**
	 * Group ID.
	 *
	 * @var int
	 */
	protected $group = 0;

	/**
	 * Group users - array of group user IDs.
	 *
	 * @var array
	 */
	protected $group_users = array();

	/**
	 * Group courses - array of group course IDs.
	 *
	 * @var int
	 */
	protected $group_courses = array();

	/**
	 * Course ID.
	 *
	 * @var int
	 */
	protected $course = 0;

	/**
	 * Course links.
	 *
	 * @var array
	 */
	protected $course_links = array();

	/**
	 * Report mode.
	 *
	 * @var string
	 */
	protected $mode = '';

	/**
	 * Table columns.
	 *
	 * @return void
	 */
	protected $columns = array();

	/**
	 * Table buttons.
	 *
	 * @return void
	 */
	protected $buttons = array(
		'add'    => 'on',
		'edit'   => 'on',
		'delete' => 'on',
	);

	/**
	 * CourseReport constructor.
	 *
	 * @return void
	 */
	public function __construct() {
	}

	/**
	 * Register scripts so they'll be available for admin or frontend.
	 *
	 * @return void
	 */
	public function register_scripts() {

		// Common dependencies.
		$dependencies = array(
			'jquery',
			$this->scripts['datatables_js'],
		);

		// Select2
		wp_register_script(
			$this->scripts['select2_js'],
			Utilities::get_vendor( 'select2/select2.min.js' ),
			array( 'jquery' ),
			Utilities::get_version(),
			true
		);
		wp_register_style(
			$this->scripts['select2_css'],
			Utilities::get_vendor( 'select2/select2.min.css' ),
			array(),
			Utilities::get_version()
		);

		// DataTables
		wp_register_script(
			$this->scripts['datatables_js'],
			Utilities::get_vendor( 'datatables/datatables.min.js' ),
			array( 'jquery' ),
			Utilities::get_version(),
			true
		);
		wp_register_script(
			$this->scripts['datatables_editor_js'],
			Utilities::get_vendor( 'datatables/dataTables.editor.min.js' ),
			$dependencies,
			Utilities::get_version(),
			true
		);
		wp_register_script(
			$this->scripts['datatables_buttons_js'],
			Utilities::get_vendor( 'datatables/dataTables.buttons.min.js' ),
			$dependencies,
			Utilities::get_version(),
			true
		);
		wp_register_script(
			$this->scripts['datatables_select_js'],
			Utilities::get_vendor( 'datatables/dataTables.select.min.js' ),
			$dependencies,
			Utilities::get_version(),
			true
		);
		wp_register_script(
			$this->scripts['moment_js'],
			Utilities::get_vendor( 'datatables/moment.min.js' ),
			$dependencies,
			Utilities::get_version(),
			true
		);

		wp_register_style(
			$this->scripts['datatables_css'],
			Utilities::get_vendor( 'datatables/datatables.min.css' ),
			array(),
			Utilities::get_version()
		);

		wp_register_style(
			$this->scripts['datatables_editor_css'],
			Utilities::get_vendor( 'datatables/editor.dataTables.min.css' ),
			array(),
			Utilities::get_version()
		);

		// Add global script dependencies.
		$dependencies[] = $this->scripts['select2_js'];
		$dependencies[] = $this->scripts['datepicker'];

		// Global assets.
		wp_register_script(
			$this->scripts['global_js'],
			Utilities::get_asset( 'credit-report', 'bundle.min.js' ),
			$dependencies,
			Utilities::get_version(),
			true
		);

		wp_register_style(
			$this->scripts['global_css'],
			Utilities::get_asset( 'credit-report', 'bundle.min.css' ),
			array(
				$this->scripts['datatables_css'],
				$this->scripts['select2_css'],
			),
			Utilities::get_version()
		);
	}

	/**
	 * Enqueue scripts.
	 *
	 * @param string $context - 'admin' or 'frontend'.
	 *
	 * @return void
	 */
	public function enqueue_scripts( $context ) {

		// todo review - maybe we allow frontend shortcode to filter out un-needed scripts

		// Date Picker.
		wp_enqueue_script( $this->scripts['datepicker'] );

		// Select2
		wp_enqueue_script( $this->scripts['select2_js'] );
		wp_enqueue_style( $this->scripts['select2_css'] );

		// DataTables
		wp_enqueue_script( $this->scripts['datatables_js'] );
		wp_enqueue_script( $this->scripts['datatables_editor_js'] );
		wp_enqueue_script( $this->scripts['datatables_buttons_js'] );
		wp_enqueue_script( $this->scripts['datatables_select_js'] );
		wp_enqueue_script( $this->scripts['moment_js'] );
		wp_enqueue_style( $this->scripts['datatables_css'] );
		wp_enqueue_style( $this->scripts['datatables_editor_css'] );

		// Set up report data.
		$this->set_up_course_report();

		// Localize script.
		wp_localize_script(
			$this->scripts['global_js'],
			'UO_CEU_EDITOR',
			$this->get_api_data()
		);

		// Localize date range filter translations for the frontend.
		if ( 'frontend' === $context ) {
			wp_localize_script(
				$this->scripts['global_js'],
				'UO_CEUs',
				array(
					'l10n' => Utilities::get_date_range_translations(),
				)
			);
		}

		// Global assets
		wp_enqueue_script( $this->scripts['global_js'] );
		wp_enqueue_style( $this->scripts['global_css'] );
	}

	/**
	 * API data for localization.
	 *
	 * @return void
	 */
	public function get_api_data() {
		// API data
		$api_setup = array(
			'api'               => array(
				'root'  => esc_url_raw( rest_url() . $this->root_path ),
				'nonce' => \wp_create_nonce( 'wp_rest' ),
			),
			'columns'           => $this->get_report_columns(),
			'buttons'           => $this->get_buttons(),
			'data'              => apply_filters( 'uo_ceu_credit_report_table_data', $this->users_ceus, $this ),
			'report_mode'       => $this->get_mode(),
			'ceu_courses'       => $this->get_ceu_courses(),
			'group_leader_data' => $this->get_group_leader_data(),
			'page_length'       => (int) get_option( 'ceu_default_page_length', 50 ),
			'l10n'              => array(
				'report'  => array(
					'create' => array(
						'button' => __( 'Add credits', 'uncanny-ceu' ),
						'title'  => __( 'Add credits', 'uncanny-ceu' ),
						'submit' => __( 'Add credits', 'uncanny-ceu' ),
					),
					'edit'   => array(
						'button' => __( 'Edit credits', 'uncanny-ceu' ),
						'title'  => __( 'Edit credits', 'uncanny-ceu' ),
						'submit' => __( 'Update credits', 'uncanny-ceu' ),
					),
					'remove' => array(
						'button'   => __( 'Delete credits', 'uncanny-ceu' ),
						'title'    => __( 'Delete credits', 'uncanny-ceu' ),
						'submit'   => __( 'Delete credits', 'uncanny-ceu' ),
						// translators: %d: number of credits
						'confirm_' => __( 'Are you sure you wish to delete %d credits?', 'uncanny-ceu' ),
						'confirm1' => __( 'Are you sure you wish to delete 1 credit?', 'uncanny-ceu' ),
					),
					'form'   => array(
						'custom'               => __( 'No LearnDash association', 'uncanny-ceu' ),
						'customTitle'          => __( 'Title', 'uncanny-ceu' ),
						'user'                 => __( 'User', 'uncanny-ceu' ),
						'course'               => __( 'LearnDash course', 'uncanny-ceu' ),
						// translators: %s: CEUs label.
						'ceus'                 => sprintf( __( 'Number of %s' ), Utilities::credit_designation_label( 'plural', __( 'CEUs', 'uncanny-ceu' ) ) ),
						'date'                 => __( 'Date and time', 'uncanny-ceu' ),
						'button'               => __( 'Create', 'uncanny-ceu' ),
						// translators: %s: Course label.
						'completeCourse'       => sprintf( __( 'Complete %s', 'uncanny-ceu' ), class_exists( '\LearnDash_Custom_Label' ) ? \LearnDash_Custom_Label::get_label( 'course' ) : 'course' ),
						'yes'                  => __( 'Yes', 'uncanny-ceu' ),
						'no'                   => __( 'No', 'uncanny-ceu' ),
						'generateCertificates' => __( 'Generate Certificates', 'uncanny-ceu' ),
						'refreshPage'          => __( 'Refresh Page', 'uncanny-ceu' ),

					),
					'table'  => array(
						// translators: %s: User's first name.
						'usersAvatar' => __( "%s's avatar", 'uncanny-ceu' ),
					),
				),
				'filters' => array(
					'allUsers' => __( 'All users', 'uncanny-ceu' ),
				),
			),
		);

		return apply_filters( 'uo_ceu_credit_report_api_data', $api_setup, $this );
	}

	////////////////////////////////////////
	//
	// Property setter functions
	//
	////////////////////////////////////////

	/**
	 * Set up course report filter checks.
	 *
	 * @return void
	 */
	public function set_up_course_report() {
		$date_var = $this->get_url_variable( 'date' );
		if ( ! empty( $date_var ) ) {
			$date    = trim( strtolower( $date_var ) );
			$options = wp_list_pluck( $this->get_date_options(), 'value' );
			if ( in_array( $date, $options, true ) ) {
				$this->date = $date;
			}
		} else {
			// Set default
			$this->date   = get_option( 'ceu_default_date_range', 'half_year' );
			$_GET['date'] = $this->date;
		}

		$user_var = $this->get_url_variable( 'user' );
		if ( ! empty( $user_var ) ) {
			$this->user = absint( $user_var );
		}

		$group_var = $this->get_url_variable( 'group' );
		if ( ! empty( $group_var ) ) {
			$this->group = absint( $group_var );
		}

		$course_var = $this->get_url_variable( 'course' );
		if ( ! empty( $course_var ) ) {
			$this->course = absint( $course_var );
		}

		$timezone       = $this->get_timezone();
		$start_date_var = $this->get_url_variable( 'start_date' );
		if ( ! empty( $start_date_var ) ) {
			$date             = \DateTime::createFromFormat( 'j M, y', $start_date_var, $timezone );
			$this->start_date = $date ? $date : \DateTime::createFromFormat( 'j M, y', '1 Jan, 00', $timezone );
		}

		$end_date_var = $this->get_url_variable( 'end_date' );
		if ( ! empty( $end_date_var ) ) {
			$date           = \DateTime::createFromFormat( 'j M, y', $end_date_var, $timezone );
			$this->end_date = $date ? $date : new \DateTime( 'NOW', $timezone );
		}

		if ( $this->is_current_user_group_leader() ) {
			$this->set_group_leader_data();
		}

		$this->set_user_ceus_data();
	}

	/**
	 * Set the mode.
	 *
	 * @return string
	 */
	public function set_mode( $mode ) {

		// Valid modes.
		$valid_modes = array( 'meta', 'json', 'tables', 'latest', 'legacy' );
		if ( ! in_array( $mode, $valid_modes, true ) ) {
			return;
		}

		$this->mode = $mode;
	}

	/**
	 * Set the current user's role.
	 *
	 * @return string
	 */
	public function set_user_role() {
		// Default value
		$this->user_role = 'unknown';

		// is it an administrator
		if ( current_user_can( 'manage_options' ) ) {
			$this->user_role = 'administrator';
		} elseif ( learndash_is_group_leader_user( get_current_user_id() ) ) {
			// Is it a group leader
			$this->user_role = 'group_leader';
		}
	}

	/**
	 * Set group leader data ( Groups, associated courses and users ).
	 *
	 * @return void
	 */
	public function set_group_leader_data() {
		if ( $this->is_current_user_group_leader() ) {
			$this->group_leader_data = CourseReportAPI::get_group_leader_data( get_current_user_id() );
			return;
		}
		$this->group_leader_data = false;
	}

	/**
	 * Set filtered columns.
	 *
	 * @param mixed (String||Array) $columns - Columns to display in the report.
	 * String Example - 'checkbox||,user||User,course||Title,date||Date,ceus_earned||CEUs,total||Total'
	 *
	 * @return void
	 */
	public function set_columns( $columns = array() ) {

		if ( empty( $columns ) ) {
			$columns = $this->get_report_columns();
		}

		// Shortcode columns are passed as a string, so we need to parse them into an array.
		if ( is_string( $columns ) ) {
			$columns_array  = explode( ',', $columns );
			$parsed_columns = array();
			foreach ( $columns_array as $column ) {
				list( $key, $label )            = explode( '||', $column );
				$parsed_columns[ trim( $key ) ] = trim( $label );
			}
			$columns = $parsed_columns;
			$this->set_column_config( $columns );
			$columns = $this->columns;
		}

		$this->columns = $columns;
	}

	/**
	 * Set the column configuration for the datatable.
	 *
	 * @param array $columns - Array of columns key => Title to display in the report.
	 */
	public function set_column_config( $columns ) {

		// Default render functions for each default column.
		$render_functions = array(
			'user'        => 'renderUser',
			'course'      => 'renderCourse',
			'date'        => 'renderDate',
			'ceus_earned' => 'renderCeusEarned',
			'total'       => 'renderTotal',
		);

		// Add Checkbox as default column.
		$this->columns = array(
			'checkbox' => array(
				'name'      => 'checkbox',
				'title'     => '',
				'data'      => null,
				'orderable' => false,
				'render'    => 'renderCheckbox',
				'export'    => false,
			),
		);
		foreach ( $columns as $key => $label ) {
			if ( 'checkbox' === $key ) {
				continue;
			}

			$data_key = key_exists( $key, $render_functions ) && 'total' !== $key
				? null
				: $key;

			$this->columns[ $key ] = array(
				'name'      => $key, // column name
				'title'     => $label, // column title
				'data'      => $data_key, // data key
				'orderable' => true, // orderable
				'export'    => true, // exportable
				'visible'   => true, // visible
			);
			// Set render function if available.
			if ( isset( $render_functions[ $key ] ) ) {
				$this->columns[ $key ]['render'] = $render_functions[ $key ];
			}
		}

		// Add columns for export data.
		$this->columns = $this->maybe_insert_export_columns( $this->columns );

		// Allow configuration filtering.
		$this->columns = apply_filters( 'uo_ceu_credit_report_column_config', $this->columns, $this );

		// Re-index the columns array for JS.
		$this->columns = array_values( $this->columns );
	}

	/**
	 * Set hidden columns for exports.
	 *
	 * @param array $columns - Array of columns to display in the report.
	 *
	 * @return array
	 */
	public function maybe_insert_export_columns( $columns ) {
		// Check if we have the user column defined.
		if ( ! isset( $columns['user'] ) ) {
			return $columns;
		}

		// Check if export columns have already been included.
		if ( isset( $columns['export_user_name'] ) ) {
			return $columns;
		}

		// Set user export to false.
		$columns['user']['export'] = false;

		// Insert export columns.
		$config = array();
		foreach ( $columns as $column ) {
			$config[] = $column;
			if ( 'user' === $column['name'] ) {
				// Add export user name column.
				$config['export_user_name'] = array(
					'name'      => 'export_user_name',
					'title'     => __( 'User', 'uncanny-ceu' ),
					'data'      => 'full_name',
					'orderable' => false,
					'export'    => true,
					'visible'   => false,
				);
				// Add export email column.
				$config['export_email'] = array(
					'name'      => 'export_email',
					'title'     => __( 'Email', 'uncanny-ceu' ),
					'data'      => 'email',
					'orderable' => false,
					'export'    => true,
					'visible'   => false,
				);
			}
		}

		return $config;
	}

	/**
	 * Set the buttons property to enable / disable buttons.
	 *
	 * @param array $buttons - Array of buttons to display in the report.
	 *
	 * @return void
	 */
	public function set_buttons( $buttons ) {
		$this->buttons = $buttons;
	}

	/**
	 * Set user CEUs data.
	 *
	 * @return void
	 */
	private function set_user_ceus_data() {

		if ( ! empty( $this->users_ceus ) ) {
			return;
		}

		switch ( $this->get_mode() ) {
			case 'meta':
				$this->latest_mode_with_usermeta();
				break;
			case 'json':
				$this->latest_mode_with_json();
				break;
			case 'tables':
			case 'latest':
				$this->latest_mode_with_tables();
				break;
			case 'legacy':
			default:
				$this->legacy_mode();
				break;
		}
	}

	/**
	 * Sets users_ceus data using the latest default mode w/ usermeta.
	 *
	 * @param $force_avatar
	 *
	 * @return bool
	 */
	public function latest_mode_with_usermeta( $force_avatar = false ) {
		global $wpdb;

		$query = "SELECT DISTINCT user_id FROM $wpdb->usermeta WHERE meta_key LIKE 'ceu_date_%'";

		if ( is_multisite() ) {

			$blog_id               = (int) get_current_blog_id();
			$base_capabilities_key = $wpdb->base_prefix . 'capabilities';
			$site_capabilities_key = $wpdb->base_prefix . $blog_id . '_capabilities';
			$meta_key              = 1 === $blog_id ? $base_capabilities_key : $site_capabilities_key;

			$query = "SELECT DISTINCT meta.user_id as user_id
				FROM $wpdb->usermeta meta
				JOIN $wpdb->usermeta meta_blog
				    ON meta.user_id = meta_blog.user_id
				WHERE meta.meta_key LIKE 'ceu_date_%'
			AND meta_blog.meta_key = '" . $meta_key . "'";

		}

		// Filter by user ID if available
		if ( 0 !== absint( $this->user ) ) {
			$user  = $this->user;
			$query .= is_multisite() ? " AND meta.user_id = $user" : " AND user_id = $user";
		}

		// Set date range filters.
		$this->set_date_range_filters();
		$unix_start_date = $this->unix_start;
		$unix_end_date   = $this->unix_end;
		$has_unix_dates  = ! empty( $unix_start_date ) && ! empty( $unix_end_date );

		if ( $has_unix_dates ) {
			if ( ! is_multisite() ) {
				$query .= " AND ( meta_value > $unix_start_date AND meta_value < $unix_end_date ) ";
			} else {
				$query .= " AND ( meta.meta_value > $unix_start_date AND meta.meta_value < $unix_end_date ) ";
			}
		}

		$users = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// If no users found, return false.
		if ( empty( $users ) ) {
			$this->users_ceus = array();
			return false;
		}

		// Filter by Groups if available
		$this->maybe_set_group_filters();

		$user_metas      = $this->pre_fetch_user_meta( false, $this->user );
		$ceu_completions = array();
		$ceus_data       = $this->get_ceu_data( $users );

		foreach ( $users as $user ) {
			$user_id   = $user->user_id;
			$user_meta = isset( $ceus_data[ $user_id ] ) ? $ceus_data[ $user_id ] : array();
			if ( empty( $user_meta ) ) {
				continue;
			}

			// If the group filter is set then only collection user within the group
			if ( 0 !== absint( $this->group ) && ! $this->is_user_in_group( $user_id ) ) {
				continue;
			}

			$avatar     = $this->do_show_avatar( $force_avatar ) ? get_avatar_url( $user_id ) : '';
			$email      = strtolower( $user_metas[ $user_id ]['user_email'] );
			$first_name = $user_metas[ $user_id ]['first_name'];
			$last_name  = $user_metas[ $user_id ]['last_name'];
			$total_ceus = 0;

			foreach ( $user_meta as $values ) {
				$meta_key   = $values['meta_key'];
				$meta_value = $values['meta_value'];
				$key        = explode( '_', $meta_key );
				$collection = array( 'title', 'date', 'course', 'earned' );
				if ( ! in_array( $key[1], $collection, true ) ) {
					continue;
				}

				// If the date filter is set then only collect users within the date range.
				if ( 'date' === $key[1] && $has_unix_dates ) {
					if ( $meta_value <= $unix_start_date || $meta_value >= $unix_end_date ) {
						continue;
					}
				}

				// If the group is set, check if course is not in the group, continue
				if ( 0 !== absint( $this->group ) && ! $this->is_user_in_group_has_course_access( (int) $key[3], $this->group ) ) {
					continue;
				}

				// If the course filter is set then only collect user within the course
				if ( 0 !== absint( $this->course ) && $this->course !== (int) $key[3] ) {
					continue;
				}

				if ( is_multisite() ) {
					switch_to_blog( get_current_blog_id() );
					$course_id   = (int) $key[3];
					$course_post = get_post( $course_id );
					restore_current_blog();

					if ( null === $course_post ) {
						continue;
					}
				}

				$unique_key = $key[2] . '_' . $key[3] . '_' . $user_id; //unique identifier for ceu completion...  {timestamp}_{course ID}
				$value_key  = $key[0] . '_' . $key[1]; // name of data stored... ceu_title  ceu_date  ceu_course  ceu_earned

				if ( 'ceu_earned' === $value_key ) {
					$total_ceus += (float) $meta_value;
				}

				if ( ! isset( $ceu_completions[ $unique_key ] ) ) {
					$ceu_completions[ $unique_key ]                 = array();
					$ceu_completions[ $unique_key ]['user_id']      = (int) $user_id;
					$ceu_completions[ $unique_key ]['user']         = (int) $user_id;
					$ceu_completions[ $unique_key ]['course_id']    = $key[3];
					$ceu_completions[ $unique_key ]['course_link']  = '';
					$ceu_completions[ $unique_key ]['user_profile'] = admin_url( 'user-edit.php?user_id=' . $user_id ); //get_edit_user_link( (int) $user_id );
					$ceu_completions[ $unique_key ]['course']       = $key[3];
					$ceu_completions[ $unique_key ]['avatar']       = $avatar;
					$ceu_completions[ $unique_key ]['first_name']   = $first_name;
					$ceu_completions[ $unique_key ]['last_name']    = $last_name;
					$ceu_completions[ $unique_key ]['full_name']    = $first_name . ' ' . $last_name;
					$ceu_completions[ $unique_key ]['email']        = $email;
					$ceu_completions[ $unique_key ]['total']        = 0;

					if ( absint( $key[3] ) ) {
						if ( ! isset( $this->course_links[ absint( $key[3] ) ] ) ) {
							$this->course_links[ absint( $key[3] ) ] = get_permalink( absint( $key[3] ) );
						}
						$ceu_completions[ $unique_key ]['course_link'] = $this->course_links[ absint( $key[3] ) ];
					}
				}

				$ceu_completions[ $unique_key ][ $value_key ] = $meta_value;

				if ( 'ceu_earned' === $value_key ) {
					$ceu_completions[ $unique_key ]['total'] += $total_ceus;
				}
			}
		}

		foreach ( $ceu_completions as $key => $completion ) {
			if ( isset( $completion['ceu_date'] ) && absint( $completion['ceu_date'] ) ) {
				$completion['date']     = learndash_adjust_date_time_display( $completion['ceu_date'], 'F d, Y' );
				$completion['time']     = learndash_adjust_date_time_display( $completion['ceu_date'], 'g:i A' );
				$completion['DT_RowId'] = $key;
				$this->users_ceus[]     = $completion;
			}
		}

		return true;
	}

	/**
	 * Sets users_ceus data using the latest default mode from JSON file.
	 *
	 * @return void
	 */
	public function latest_mode_with_json() {

		$data = $this->get_data_from_json_file();

		// Maybe filter by group leader data.
		$data = $this->maybe_filter_group_leader_data( $data, 'user_id', 'all_user_ids' );
		$data = $this->maybe_filter_group_leader_data( $data, 'course_id', 'all_course_ids' );

		if ( empty( $data ) ) {
			$this->users_ceus = array();
			return;
		}

		// Filter by date ranges
		$this->set_date_range_filters();
		$unix_start_date = (int) $this->unix_start;
		$unix_end_date   = (int) $this->unix_end;

		// Filter by Groups if available
		$this->maybe_set_group_filters();

		foreach ( $data as $record ) {
			$user_id = (int) $record['user_id'];

			if ( ! $this->do_show_avatar() ) {
				$record['avatar'] = '';
			}

			if ( 0 !== absint( $this->user ) && $user_id !== (int) $this->user ) {
				continue;
			}

			if ( ! empty( $unix_start_date ) && ! empty( $unix_end_date ) ) {
				$ceu_date = (int) $record['ceu_date'];
				if ( $ceu_date <= $unix_start_date || $ceu_date >= $unix_end_date ) {
					continue;
				}
			}

			// If the group filter is set then only collection user within the group
			if ( 0 !== absint( $this->group ) && ! $this->is_user_in_group( $user_id ) ) {
				continue;
			}

			// If the group is set, check if course is not in the group, continue
			if ( 0 !== absint( $this->group ) && ! $this->is_user_in_group_has_course_access( $record['course_id'], $this->group ) ) {
				continue;
			}

			// If the course filter is set then only collect user within the course
			if ( 0 !== absint( $this->course ) && $this->course !== (int) $record['course_id'] ) {
				continue;
			}

			$record['full_name'] = $record['first_name'] . ' ' . $record['last_name'];
			$this->users_ceus[]  = $record;
		}
	}

	/**
	 * Sets users_ceus data using the latest mode from DB table.
	 *
	 * @return void
	 * @todo Make it work for Multisites
	 */
	public function latest_mode_with_tables() {
		global $wpdb;

		$query = "SELECT user_id, course_id, custom_course_id, course_title, SUM(credits) as credits, earned_timestamp FROM `{$wpdb->prefix}uo_ceu_records` WHERE 1=1";

		// Filter by user ID if available
		if ( 0 !== absint( $this->user ) ) {
			$user  = $this->user;
			$query .= " AND user_id = $user";
		}

		if ( 0 !== absint( $this->course ) ) {
			$course_id = $this->course;
			$query     .= " AND course_id = $course_id";
		}

		// Filter by date ranges
		$this->set_date_range_filters();
		$unix_start_date = $this->unix_start;
		$unix_end_date   = $this->unix_end;
		if ( ! empty( $unix_start_date ) && ! empty( $unix_end_date ) ) {
			$query .= " AND earned_timestamp BETWEEN {$unix_start_date} AND {$unix_end_date}";
		}

		$query .= ' GROUP BY user_id, course_id, custom_course_id, course_title, earned_timestamp';

		$results = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Maybe filter by group leader data.
		$results = $this->maybe_filter_group_leader_data( $results, 'user_id', 'all_user_ids' );
		$results = $this->maybe_filter_group_leader_data( $results, 'course_id', 'all_course_ids' );
		// @todo - review how does custom_course_id work here?

		// Bail if no results
		if ( empty( $results ) ) {
			$this->users_ceus = array();
			return;
		}

		// Filter by Groups if available
		$this->maybe_set_group_filters();

		$user_metas      = $this->pre_fetch_user_meta( true );
		$user_total      = array();
		$ceu_completions = array();

		foreach ( $results as $result ) {
			$user_id = $result->user_id;

			// If the group filter is set then only collection user within the group
			if ( 0 !== absint( $this->group ) && ! $this->is_user_in_group( $user_id ) ) {
				continue;
			}

			// Calculate the total CEus of a user
			if ( ! isset( $user_total[ $user_id ] ) ) {
				$user_total[ $user_id ] = 0;
			}

			$avatar     = $this->do_show_avatar() ? get_avatar_url( $user_id ) : '';
			$email      = strtolower( $user_metas[ $user_id ]['user_email'] );
			$first_name = $user_metas[ $user_id ]['first_name'];
			$last_name  = $user_metas[ $user_id ]['last_name'];
			$_course_id = 0 !== (int) $result->course_id ? $result->course_id : $result->custom_course_id;

			// If the group is set, check if course is not in the group, continue
			if ( 0 !== absint( $this->group ) && ! self::is_user_in_group_has_course_access( $_course_id, $this->group ) ) {
				continue;
			}

			$unique_key             = $result->earned_timestamp . '_' . $_course_id . '_' . $user_id; //unique identifier for ceu completion...  {timestamp}_{course ID}
			$user_total[ $user_id ] = $user_total[ $user_id ] + (float) $result->credits;
			$ceu_completions[]      = array(
				'user_id'      => (int) $user_id,
				'user'         => (int) $user_id,
				'course_id'    => $_course_id,
				'course_link'  => (int) $user_id,
				'user_profile' => admin_url( 'user-edit.php?user_id=' . $user_id ),
				'course'       => $_course_id,
				'avatar'       => $avatar,
				'first_name'   => $first_name,
				'last_name'    => $last_name,
				'full_name'    => $first_name . ' ' . $last_name,
				'email'        => $email,
				'total'        => $user_total[ $user_id ],
				'ceu_earned'   => (float) $result->credits,
				'ceu_date'     => $result->earned_timestamp,
				'ceu_title'    => $result->course_title,
				'ceu_course'   => $_course_id,
				'date'         => learndash_adjust_date_time_display( $result->earned_timestamp, 'F d, Y' ),
				'time'         => learndash_adjust_date_time_display( $result->earned_timestamp, 'g:i A' ),
				'DT_RowId'     => $unique_key,
			);
		}

		$this->users_ceus = $ceu_completions;
	}

	/**
	 * Sets users_ceus data using the legacy mode.
	 *
	 * @return void
	 */
	public function legacy_mode() {
		global $wpdb;

		// Build the query.
		$query = "SELECT DISTINCT user.ID, user.user_email FROM $wpdb->usermeta meta JOIN $wpdb->users user ON user.ID = meta.user_id WHERE meta.meta_key LIKE 'ceu_date_%'";

		if ( is_multisite() ) {
			// Ref: https://developer.wordpress.org/reference/functions/is_user_member_of_blog/
			$blog_id  = (int) get_current_blog_id();
			$meta_key = ( 1 === $blog_id )
				? "{$wpdb->base_prefix}capabilities"
				: "{$wpdb->base_prefix}{$blog_id}_capabilities";

			$query = "SELECT DISTINCT user.ID, user.user_email
				FROM $wpdb->usermeta meta
				JOIN $wpdb->users user ON user.ID = meta.user_id
				JOIN $wpdb->usermeta meta_blog ON user.ID = meta_blog.user_id
				WHERE meta.meta_key LIKE 'ceu_date_%'
			AND meta_blog.meta_key = '" . $meta_key . "'";
		}

		if ( 0 !== absint( $this->user ) ) {
			$user  = $this->user;
			$query .= " AND user.ID = $user";
		}

		// Filter by group if available.
		$this->maybe_set_group_filters();

		// Set date range filters.
		$this->set_date_range_filters();
		$unix_start_date = $this->unix_start;
		$unix_end_date   = $this->unix_end;

		// Get the user data.
		$users = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Maybe filter group leader users.
		$users = $this->maybe_filter_group_leader_data( $users, 'ID', 'all_user_ids' );

		// If no users found set empty array and return.
		if ( empty( $users ) ) {
			$this->users_ceus = array();
			return;
		}

		$ceu_completions = array();

		foreach ( $users as $user ) {
			$user_id = $user->ID;

			// If the group filter is set then only collection user within the group
			if ( 0 !== absint( $this->group ) && ! $this->is_user_in_group( $user_id ) ) {
				continue;
			}

			$user_meta  = get_user_meta( $user_id );
			$avatar     = $this->do_show_avatar() ? get_avatar_url( $user_id ) : '';
			$first_name = $user_meta['first_name'][0];
			$last_name  = $user_meta['last_name'][0];
			$email      = strtolower( $user->user_email );
			$total_ceus = 0;

			foreach ( $user_meta as $meta_key => $meta_value ) {

				if ( false === strpos( $meta_key, 'ceu_' ) ) {
					continue;
				}

				$key        = explode( '_', $meta_key );
				$collection = array( 'title', 'date', 'course', 'earned' );

				if ( ! in_array( $key[1], $collection, true ) ) {
					continue;
				}

				// If the group is set, check if course is not in the group, continue
				if ( 0 !== absint( $this->group ) && ! $this->is_user_in_group_has_course_access( (int) $key[3], $this->group ) ) {
					continue;
				}

				// If the course filter is set then only collect user within the course
				if ( 0 !== absint( $this->course ) && $this->course !== (int) $key[3] ) {
					continue;
				}

				if ( is_multisite() ) {
					switch_to_blog( get_current_blog_id() );
					$course_id   = (int) $key[3];
					$course_post = get_post( $course_id );
					restore_current_blog();
					if ( null === $course_post ) {
						continue;
					}
				}

				$unique_key = $key[2] . '_' . $key[3] . '_' . $user_id; //unique identifier for ceu completion...  {timestamp}_{course ID}
				$value_key  = $key[0] . '_' . $key[1]; // name of data stored... ceu_title  ceu_date  ceu_course  ceu_earned

				if ( 'ceu_earned' === $value_key ) {
					$total_ceus += (float) $meta_value[0];
				}

				if ( 'date' === $key[1] && '' !== $unix_start_date && '' !== $unix_end_date ) {
					if ( $meta_value[0] <= $unix_start_date || $meta_value[0] >= $unix_end_date ) {
						continue;
					}
				}

				if ( ! isset( $ceu_completions[ $unique_key ] ) ) {
					$ceu_completions[ $unique_key ]                 = array();
					$ceu_completions[ $unique_key ]['user_id']      = (int) $user_id;
					$ceu_completions[ $unique_key ]['user']         = (int) $user_id;
					$ceu_completions[ $unique_key ]['course_id']    = $key[3];
					$ceu_completions[ $unique_key ]['course_link']  = '';
					$ceu_completions[ $unique_key ]['user_profile'] = admin_url( 'user-edit.php?user_id=' . $user_id ); //get_edit_user_link( (int) $user_id );
					$ceu_completions[ $unique_key ]['course']       = $key[3];
					$ceu_completions[ $unique_key ]['avatar']       = $avatar;
					$ceu_completions[ $unique_key ]['first_name']   = $first_name;
					$ceu_completions[ $unique_key ]['last_name']    = $last_name;
					$ceu_completions[ $unique_key ]['full_name']    = $first_name . ' ' . $last_name;
					$ceu_completions[ $unique_key ]['email']        = $email;
					$ceu_completions[ $unique_key ]['total']        = 0;

					if ( absint( $key[3] ) ) {
						if ( ! isset( $this->course_links[ absint( $key[3] ) ] ) ) {
							$this->course_links[ absint( $key[3] ) ] = get_permalink( absint( $key[3] ) );
						}
						$ceu_completions[ $unique_key ]['course_link'] = $this->course_links[ absint( $key[3] ) ];
					}
				}

				$ceu_completions[ $unique_key ][ $value_key ] = $meta_value[0];

				if ( 'ceu_earned' === $value_key ) {
					$ceu_completions[ $unique_key ]['total'] += $total_ceus;
				}
			}
		}

		foreach ( $ceu_completions as $key => $completion ) {
			if ( isset( $completion['ceu_date'] ) && absint( $completion['ceu_date'] ) ) {
				$completion['date']     = learndash_adjust_date_time_display( $completion['ceu_date'], 'F d, Y' );
				$completion['time']     = learndash_adjust_date_time_display( $completion['ceu_date'], 'g:i A' );
				$completion['DT_RowId'] = $key;
				$this->users_ceus[]     = $completion;
			}
		}
	}

	/**
	 * Set unix date range filter properties.
	 *
	 * @return void
	 */
	public function set_date_range_filters() {
		if ( empty( $this->date ) ) {
			return;
		}

		// Clear unix range if 'all' is selected.
		if ( 'all' === $this->date ) {
			$this->unix_start = '';
			$this->unix_end   = '';
			return;
		}

		if ( 'custom' !== $this->date ) {
			$timezone     = $this->get_timezone();
			$current_date = time();
			// Set default $start_date
			$start_date = new \DateTime( 'now', $timezone );
			$start_date->setTimestamp( $current_date );
			// Set default $end_date
			$end_date = new \DateTime( 'now', $timezone );
			$end_date->setTimestamp( $current_date );
		}

		switch ( $this->date ) {
			case 'week':
				$start_date->modify( '-7 days' );
				break;
			case 'month':
				$start_date->modify( '-30 days' );
				break;
			case 'quarter':
				$start_date->modify( '-90 days' );
				break;
			case 'half_year':
				$start_date->modify( '-180 days' );
				break;
			case 'year':
				$start_date->modify( '-365 days' );
				break;
			case 'custom':
				$start_date = $this->start_date;
				$end_date   = $this->end_date;
				break;
		}

		// Set start time to 00:00:00 and end time to 23:59:59.
		$start_date->setTime( 0, 0, 0 );
		$end_date->setTime( 23, 59, 59 );

		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$this->unix_start = $start_date->format( 'U' );
			$this->unix_end   = $end_date->format( 'U' );
		}
	}

	/**
	 * Maybe set group filters
	 *
	 * @return void
	 */
	public function maybe_set_group_filters() {
		if ( 0 !== absint( $this->group ) ) {
			$group_id = absint( $this->group );
			$this->set_group_users( $group_id );
			if ( true === apply_filters( 'uo_ceu_include_group_courses', true, $group_id ) ) {
				$this->set_group_courses( $group_id );
			}
		}
	}

	/**
	 * Set group_users property
	 *
	 * @param int $group_id
	 *
	 * @return void
	 */
	public function set_group_users( $group_id ) {
		$this->group_users = CourseReportAPI::get_learndash_group_user_ids( $group_id );
	}

	/**
	 * Set group_courses property
	 *
	 * @param int $group_id
	 *
	 * @return void
	 */
	public function set_group_courses( $group_id ) {
		$courses             = learndash_group_enrolled_courses( $group_id );
		$courses             = apply_filters( 'uo_ceu_learndash_group_enrolled_courses', $courses, $group_id );
		$courses             = ! empty( $courses ) ? array_map( 'absint', $courses ) : array();
		$this->group_courses = $courses;
	}

	////////////////////////////////////////
	//
	// Get functions
	//
	////////////////////////////////////////

	/**
	 * Get User Role
	 *
	 * @return string|null
	 */
	public function get_user_role() {
		if ( is_null( $this->user_role ) ) {
			$this->set_user_role();
		}
		return $this->user_role;
	}

	/**
	 * Get Group Leader Data.
	 *
	 * @param string $key - Optional key to return specific data.
	 *
	 * @return array
	 */
	public function get_group_leader_data( $key = '' ) {
		if ( is_null( $this->group_leader_data ) ) {
			$this->set_group_leader_data();
		}

		// Return specific key value if set.
		if ( ! empty( $key ) ) {
			return isset( $this->group_leader_data[ $key ] ) ? $this->group_leader_data[ $key ] : array();
		}

		// Return all group leader data.
		return $this->group_leader_data;
	}

	/**
	 * Get report columns.
	 *
	 * @return array
	 */
	public function get_report_columns() {
		if ( empty( $this->columns ) || ! is_array( $this->columns ) ) {
			$columns = apply_filters(
				'uo_ceu_credit_report_column_titles',
				array(
					'user'        => __( 'User', 'uncanny-ceu' ),
					'course'      => __( 'Title', 'uncanny-ceu' ),
					'date'        => __( 'Date', 'uncanny-ceu' ),
					'ceus_earned' => Utilities::credit_designation_label( 'plural', __( 'CEUs', 'uncanny-ceu' ) ),
					'total'       => __( 'Total', 'uncanny-ceu' ),
				)
			);
			$this->set_column_config( $columns );
		}

		return $this->columns;
	}

	/**
	 * Get report action buttons.
	 *
	 * @return array
	 */
	public function get_buttons() {
		return apply_filters(
			'uo_ceu_credit_report_action_buttons',
			$this->buttons,
			$this
		);
	}

	/**
	 * Get $_GET variable from URL if set.
	 *
	 * @param string $variable
	 *
	 * @return string
	 */
	public function get_url_variable( $variable ) {
		return Utilities::ceu_filter_has_var( $variable )
			? Utilities::ceu_filter_input( $variable )
			: '';
	}

	/**
	 * Get URL
	 *
	 * @return string
	 */
	public function get_url() {

		// Return the admin URL for reports run via the admin page
		if ( is_admin() ) {
			return admin_url( 'admin.php?page=uncanny-ceu-report' );
		}

		// Get the current page URL for reports run via shortcode
		$current_url = home_url( add_query_arg( null, null ) );
		$remove      = array( 'start_date', 'end_date', 'date', 'user', 'group', 'course' );

		return remove_query_arg( $remove, $current_url );
	}

	/**
	 * Get the mode
	 *
	 * @return string
	 */
	public function get_mode() {
		if ( empty( $this->mode ) ) {
			$this->mode = apply_filters( 'uo_ceu_credit_report_data_mode', get_option( 'ceu_report_mode', 'meta' ) );
		}
		return $this->mode;
	}

	/**
	 * Get the URL to trigger the data refresh.
	 *
	 * @return string
	 */
	public function get_refresh_url() {
		$args = array(
			'ceu_credit_report_action' => 'refresh',
			'_wpnonce'                 => wp_create_nonce( 'ceu_credit_report_submission' ),
			'redirect'                 => rawurlencode( $this->get_url() ),
		);
		return add_query_arg( $args, $this->get_url() );
	}

	/**
	 * Get CSV filename
	 *
	 * @return string
	 */
	public function get_csv_filename() {
		return sprintf(
			'%s - %s',
			__( 'Credit Report', 'uncanny-ceu' ),
			wp_date( 'Y-m-j g:i A' )
		);
	}

	/**
	 * Pre fetch user meta data
	 *
	 * @param bool $filter_by_records - true for uo_ceu_records table
	 * @param int $user_id            - specific user ID
	 *
	 * @return array
	 */
	private function pre_fetch_user_meta( $filter_by_records = false, $user_id = 0 ) {

		global $wpdb;
		$user_info      = array();
		$user_id        = absint( $user_id );
		$filter_by_user = ! empty( $user_id );

		// Build SQL for user meta data based on arguments.
		$sql = "SELECT user_id, meta_key, meta_value FROM $wpdb->usermeta WHERE (meta_key = 'first_name' OR meta_key = 'last_name')";
		if ( $filter_by_records ) {
			$sql .= " AND user_id IN (SELECT DISTINCT user_id FROM {$wpdb->prefix}uo_ceu_records)";
		}
		if ( $filter_by_user ) {
			$sql .= ' AND user_id = ' . $user_id;
		}
		$sql .= ' ORDER BY user_id ASC';

		// Query user meta data.
		$metas = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Build user info array.
		if ( ! empty( $metas ) && is_array( $metas ) ) {
			foreach ( $metas as $meta ) {
				// Add user to array if not already there.
				if ( ! isset( $user_info[ $meta->user_id ] ) ) {
					$user_info[ $meta->user_id ] = array(
						'first_name' => '',
						'last_name'  => '',
					);
				}
				// Add first and last name to user array.
				if ( 'first_name' === $meta->meta_key || 'last_name' === $meta->meta_key ) {
					$user_info[ $meta->user_id ][ $meta->meta_key ] = $meta->meta_value;
				}
			}
		}

		if ( empty( $user_info ) ) {
			return array();
		}

		// Build SQL for user email data based on arguments.
		$sql = "SELECT ID, user_email FROM $wpdb->users WHERE 1=1";
		if ( $filter_by_records ) {
			$sql .= " AND ID IN (SELECT DISTINCT user_id FROM {$wpdb->prefix}uo_ceu_records)";
		}
		if ( $filter_by_user ) {
			$sql .= ' AND ID = ' . $user_id;
		}
		$sql .= ' ORDER BY ID ASC';

		// Query user email data.
		$emails = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		// Add user email to user info array.
		if ( ! empty( $emails ) && is_array( $emails ) ) {
			foreach ( $emails as $email ) {
				if ( ! isset( $user_info[ $email->ID ] ) ) {
					continue;
				}
				$user_info[ $email->ID ]['user_email'] = $email->user_email;
			}
		}

		return $user_info;
	}

	/**
	 * Get CEU data for users.
	 *
	 * @param array $users
	 *
	 * @return array
	 */
	public function get_ceu_data( $users ) {

		$ceus = array();

		// Filter users by group leader users.
		$users = $this->maybe_filter_group_leader_data( $users, 'user_id', 'all_user_ids' );

		// Make sure we have an array of user IDs.
		$user_ids = array_map( 'absint', array_column( $users, 'user_id' ) );

		global $wpdb;
		$user_meta = $wpdb->get_results(
			"SELECT user_id, meta_key, meta_value FROM $wpdb->usermeta WHERE meta_key LIKE 'ceu_%'"
		);

		if ( ! empty( $user_meta ) ) {
			foreach ( $user_meta as $meta ) {
				if ( ! in_array( absint( $meta->user_id ), $user_ids, true ) ) {
					continue;
				}
				if ( ! isset( $ceus[ $meta->user_id ] ) ) {
					$ceus[ $meta->user_id ] = array();
				}
				$ceus[ $meta->user_id ][] = array(
					'meta_key'   => $meta->meta_key,
					'meta_value' => $meta->meta_value,
				);
			}
		}

		return $ceus;
	}

	/**
	 * Get CEU courses for select.
	 *
	 * @return array
	 */
	public function get_ceu_courses() {
		$options    = array();
		$options[0] = __( 'No LearnDash association', 'uncanny-ceu' );

		$ceu_courses = Utilities::get_courses_with_credits();

		// Filter out group leader courses.
		$ceu_courses = $this->maybe_filter_group_leader_data( $ceu_courses, 'ID', 'all_course_ids' );

		if ( empty( $ceu_courses ) ) {
			return $options;
		}

		foreach ( $ceu_courses as $course ) {
			$options[ $course->ID ] = $course->post_title;
		}

		return $options;
	}

	/**
	 * Get or generate JSON file.
	 *
	 * @return mixed|null
	 */
	public function get_data_from_json_file() {

		// Get the filename from options table
		$last_filename = get_option( 'ceu_report_file_name', '' );
		$filename      = "{$last_filename}.json";
		$base_path     = dirname( InitializePlugin::$path_to_plugin_file ) . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR;
		$path          = $base_path . $filename;

		// In case JSON doesn't exist
		if ( ! file_exists( $path ) ) {
			// Generate new JSON file
			$filename = $this->generate_ceu_records_json();
			// Bail if we can't couldn't generate the file.
			if ( empty( $filename ) ) {
				return null;
			}
			$path = $base_path . $filename;
		}

		return wp_json_file_decode( $path, array( 'associative' => true ) );
	}

	/**
	 * Get date options.
	 *
	 * @return array
	 */
	public function get_date_options() {
		return array(
			array(
				'value' => 'week',
				'text'  => __( 'Last 7 days', 'uncanny-ceu' ),
			),
			array(
				'value' => 'month',
				'text'  => __( 'Last 30 days', 'uncanny-ceu' ),
			),
			array(
				'value' => 'quarter',
				'text'  => __( 'Last 90 days', 'uncanny-ceu' ),
			),
			array(
				'value' => 'half_year',
				'text'  => __( 'Last 180 days', 'uncanny-ceu' ),
			),
			array(
				'value' => 'year',
				'text'  => __( 'Last 365 days', 'uncanny-ceu' ),
			),
			array(
				'value' => 'all',
				'text'  => __( 'All time', 'uncanny-ceu' ),
			),
			array(
				'value' => 'custom',
				'text'  => __( 'Custom range', 'uncanny-ceu' ),
			),
		);
	}

	/**
	 * Get date dropdown options html.
	 *
	 * @return string - html
	 */
	public function get_date_dropdown() {
		$options  = $this->get_date_options();
		$selected = $this->date;
		return Utilities::generate_select_options( $options, $selected );
	}

	/**
	 * Get user dropdown options html.
	 *
	 * @return string - html
	 */
	public function get_users_dropdown() {

		if ( 0 === $this->user ) {
			$user_var = $this->get_url_variable( 'user' );
			if ( ! empty( $user_var ) ) {
				$this->user = absint( $user_var );
			}
		}

		// Get options
		$options   = array();
		$options[] = array(
			'value' => '0',
			'text'  => __( 'All users', 'uncanny-ceu' ),
		);
		$selected  = 0;
		if ( 0 !== $this->user ) {
			// Check if the selected user exists
			$user = get_userdata( $this->user );
			if ( $user && isset( $user->ID, $user->user_email ) ) {
				$selected  = $user->ID;
				$options[] = array(
					'value' => $user->ID,
					'text'  => sprintf(
						'%s::%s',
						wp_strip_all_tags( $user->display_name ),
						wp_strip_all_tags( $user->user_email )
					),
				);
			}
		}

		return Utilities::generate_select_options( $options, $selected );
	}

	/**
	 * Get group select html.
	 *
	 * @return string - html
	 */
	public function get_groups_dropdown() {

		if ( 0 === $this->group ) {
			$group_var = $this->get_url_variable( 'group' );
			if ( ! empty( $group_var ) ) {
				$this->group = absint( $group_var );
			}
		}

		// Check if Hierarchical Groups are enabled.
		if ( CourseReportAPI::is_groups_hierarchical_enabled() ) {

			// Let WordPress build Hierarchy for us
			$args = array(
				'echo'              => false,
				'post_type'         => 'groups',
				'name'              => 'group',
				'id'                => 'ucec-groups',
				'class'             => 'ucec-report-select ucec-report-filter__select',
				'show_option_none'  => esc_attr__( 'All groups', 'uncanny-ceu' ),
				'option_none_value' => 0,
				'selected'          => esc_attr( $this->group ),
				'sort_column'       => 'menu_order, post_title',
			);

			if ( $this->is_current_user_group_leader() ) {
				$args['include'] = $this->get_group_leader_data( 'all_group_ids' );
			}

			return wp_dropdown_pages( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		// Build options.
		$options = array(
			array(
				'value' => '0',
				'text'  => __( 'All groups', 'uncanny-ceu' ),
			),
		);

		// Get group posts.
		$args   = array(
			'post_type'              => 'groups',
			'posts_per_page'         => 99999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
			'orderby'                => 'title',
			'order'                  => 'DESC',
			'cache_results'          => false,
			'update_post_meta_cache' => false,
			'update_term_meta_cache' => false,
		);
		$groups = get_posts( $args );

		// Maybe filter group leader groups.
		$groups = $this->maybe_filter_group_leader_data( $groups, 'ID', 'all_group_ids' );

		$selected = 0;
		if ( ! empty( $groups ) ) {
			foreach ( $groups as $group ) {
				$options[] = array(
					'value' => (int) $group->ID,
					'text'  => $group->post_title,
				);
				if ( (int) $this->group === (int) $group->ID ) {
					$selected = (int) $group->ID;
				}
			}
		}

		// Generate HTML.
		$drop_down = '<select name="group" id="ucec-groups" class="ucec-report-select ucec-report-filter__select">';
		$drop_down .= Utilities::generate_select_options( $options, $selected );
		$drop_down .= '</select>';

		return $drop_down;
	}

	/**
	 * Get "Course" select options html.
	 *
	 * @return string - html
	 */
	public function get_courses_dropdown() {

		// Check if course is set.
		if ( 0 === $this->course ) {
			$course_var = $this->get_url_variable( 'course' );
			if ( ! empty( $course_var ) ) {
				$this->course = absint( $course_var );
			}
		}

		// Get course posts.
		$args = array(
			'post_type'              => 'sfwd-courses',
			'posts_per_page'         => 99999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'cache_results'          => false,
			'update_post_meta_cache' => false,
			'update_term_meta_cache' => false,
		);

		$courses = get_posts( $args );

		// Maybe filter group leader courses.
		$courses = $this->maybe_filter_group_leader_data( $courses, 'ID', 'all_course_ids' );

		// Build options.
		$options  = array(
			array(
				'value' => '0',
				'text'  => __( 'All courses', 'uncanny-ceu' ),
			),
		);
		$selected = 0;
		if ( ! empty( $courses ) ) {
			foreach ( $courses as $course ) {
				$options[] = array(
					'value' => (int) $course->ID,
					'text'  => sprintf(
						// translators: %s: Course title, %s Post ID.
						'%s (ID: %d)',
						$course->post_title,
						$course->ID
					),
				);
				if ( (int) $this->course === (int) $course->ID ) {
					$selected = (int) $course->ID;
				}
			}
		}

		// Generate HTML.
		$options = Utilities::generate_select_options( $options, $selected );

		// Allow custom courses
		return apply_filters( 'uo_ceu_credit_report_custom_courses', $options, $this->course, $args );
	}

	/**
	 * Get allowed html array for wpkses.
	 *
	 * @return array
	 */
	public function get_allowed_select_html() {
		return array(
			'select' => array(
				'name'            => array(),
				'id'              => array(),
				'class'           => array(),
				'data-select2-id' => array(),
				'tabindex'        => array(),
				'aria-hidden'     => array(),
			),
			'option' => array(
				'value'    => array(),
				'selected' => array(),
			),
		);
	}

	/**
	 * Report last generated string.
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function report_last_generated() {
		$date_format  = get_option( 'date_format' );
		$time_format  = get_option( 'time_format' );
		$last_updated = get_option( 'ceu_report_last_updated' );
		$wp_date      = wp_date( "{$date_format} {$time_format}", $last_updated );
		$datetime1    = new \DateTime( $wp_date, wp_timezone() );
		$datetime2    = new \DateTime( 'now', wp_timezone() );
		$interval     = $datetime1->diff( $datetime2 );
		$ago          = '';

		if ( 0 !== $interval->d ) {
			$ago .= sprintf( '%d %s', $interval->d, _n( 'day', 'days', $interval->d, 'uncanny-ceu' ) );
			$ago .= ' ';
		}

		if ( 0 === $interval->d && 0 !== $interval->h ) {
			$ago .= sprintf( '%d %s', $interval->h, _n( 'hour', 'hours', $interval->h, 'uncanny-ceu' ) );
			$ago .= ' ';
		}

		if ( 0 === $interval->h && 0 !== $interval->i ) {
			$ago .= sprintf( '%d %s', $interval->i, _n( 'minute', 'minutes', $interval->i, 'uncanny-ceu' ) );
			$ago .= ' ';
		}

		if ( 0 === $interval->h && 0 === $interval->i ) {
			$ago = __( 'Less than a minute', 'uncanny-ceu' );
		}

		return sprintf( '%s: %s %s', __( 'Last updated', 'uncanny-ceu' ), $ago, __( 'ago', 'uncanny-ceu' ) );
	}

	/**
	 * Get report script handle by key.
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function get_script_handle( $key ) {
		return isset( $this->scripts[ $key ] ) ? $this->scripts[ $key ] : '';
	}

	////////////////////////////////////////
	//
	// Helper functions
	//
	////////////////////////////////////////

	/**
	 * Check if user is group leader.
	 *
	 * @return bool
	 */
	public function is_current_user_group_leader() {
		$role = $this->get_user_role();
		return 'group_leader' === $role;
	}

	/**
	 * Maybe filter data by group leader IDs.
	 *
	 * @param array $data - data to filter (users, courses, etc.)
	 * @param string $id_key - key to use for ID
	 * @param string $leader_key - key to use for leader data
	 *
	 * @return array
	 */
	public function maybe_filter_group_leader_data( $data, $id_key, $leader_key ) {
		if ( ! $this->is_current_user_group_leader() || empty( $data ) ) {
			return $data;
		}

		$group_leader_ids = $this->get_group_leader_data( $leader_key );

		return array_filter(
			$data,
			function( $item ) use ( $group_leader_ids, $id_key ) {
				$id = is_array( $item ) ? $item[ $id_key ] : $item->{$id_key};
				return in_array( absint( $id ), $group_leader_ids, true );
			}
		);
	}

	/**
	 * Check if user ID is in class properties.
	 *
	 * @param $user_id
	 *
	 * @return bool
	 */
	public function is_user_in_group( $user_id ) {
		if ( empty( $this->group ) ) {
			return true;
		}

		if ( is_array( $this->group_users ) && in_array( (int) $user_id, $this->group_users, true ) ) {
			return true;
		}

		// Check if user is in child group.
		if ( $this->do_include_group_children() ) {
			$groups = $this->get_group_data( $this->group );
			return $this->is_id_in_group_or_children( 'users', $user_id, $this->group, $groups );
		}

		return false;
	}

	/**
	 * Check if user is in group and has course access.
	 *
	 * @param $course_id
	 * @param $group_id
	 *
	 * @return bool
	 */
	public function is_user_in_group_has_course_access( $course_id, $group_id ) {

		// Disable course in group check.
		if ( false === apply_filters( 'uo_ceu_include_group_courses', true, $group_id ) ) {
			return true;
		}

		// Check if course is in group.
		if ( is_array( $this->group_courses ) && in_array( (int) $course_id, $this->group_courses, true ) ) {
			return true;
		}

		// Check if course is in child group.
		if ( $this->do_include_group_children() ) {
			$groups = $this->get_group_data( $this->group );
			return $this->is_id_in_group_or_children( 'courses', $course_id, $group_id, $groups );
		}

		return false;
	}

	/**
	 * Get group data.
	 *
	 * @param int $group_id
	 *
	 * @return array
	 */
	private function get_group_data( $group_id ) {
		// Check if group leader.
		if ( $this->is_current_user_group_leader() ) {
			// return existing group data.
			return $this->get_group_leader_data( 'groups' );
		}

		// Get group data from CourseReportAPI.
		$groups_data = CourseReportAPI::get_learndash_hierarchical_group_data( $group_id );
		return isset( $groups_data['groups'] ) ? $groups_data['groups'] : array();
	}

	/**
	 * Check if we should be including group children.
	 *
	 * @return bool
	 */
	public function do_include_group_children() {
		static $include_group_children = null;
		$include_group_children        = is_null( $include_group_children )
			? 'on' === get_option( 'ceu_credit_report_include_group_children', 'off' )
			: $include_group_children;
		return $include_group_children;
	}

	/**
	 * Recursively check if ID is in group data or children of the group.
	 *
	 * @param int $user_id
	 * @param int $group_id
	 * @param array $groups
	 *
	 * @return bool
	 */
	private function is_id_in_group_or_children( $data_key, $id, $group_id, $groups ) {
		$group_id = (int) $group_id;
		$id       = (int) $id;
		$group    = isset( $groups[ $group_id ] ) ? $groups[ $group_id ] : array();
		$data     = isset( $group[ $data_key ] ) ? $group[ $data_key ] : array();

		if ( in_array( (int) $id, $data, true ) ) {
			return true;
		}
		$children = isset( $group['children'] ) ? $group['children'] : array();
		if ( ! empty( $children ) ) {
			foreach ( $children as $child_id ) {
				if ( $this->is_id_in_group_or_children( $data_key, $id, $child_id, $groups ) ) {
					return true;
				}
			}
		}

		// ID not found in group or children.
		return false;
	}

	/**
	 * Check if avatar setting is enabled.
	 *
	 * @param $override - force avatar to show
	 *
	 * @return bool
	 */
	public function do_show_avatar( $override = false ) {
		static $show_avatar = null;
		$show_avatar        = is_null( $show_avatar ) ? 'on' === get_option( 'ceu_show_avatar', 'off' ) : $show_avatar;
		return true === $override || $show_avatar;
	}

	/**
	 * Get the timezone object from WordPress settings.
	 *
	 * @return \DateTimeZone
	 */
	private function get_timezone() {
		// Cache timezone object.
		static $timezone = null;
		if ( ! is_null( $timezone ) ) {
			return $timezone;
		}

		$timezone_string = get_option( 'timezone_string' );
		$gmt_offset      = get_option( 'gmt_offset' );

		// If timezone_string is empty, construct a timezone string from gmt_offset
		if ( empty( $timezone_string ) ) {
			$timezone_string = absint( $gmt_offset ) === 0
				? 'UTC'
				: sprintf( 'Etc/GMT%+d', -$gmt_offset );
		}
		try {
			$timezone = new \DateTimeZone( $timezone_string );
		} catch ( \Exception $e ) {
			// Fallback to UTC
			$timezone = new \DateTimeZone( 'UTC' );
		}

		return $timezone;
	}

	/**
	 * Generate CEU records.
	 *
	 * @return string|bool - filename or false if no users_ceus.
	 */
	public function generate_ceu_records_json() {

		if ( $this->latest_mode_with_usermeta( true ) ) {

			// New file name w/ timestamp.
			$filename = wp_create_nonce( 'credit-report-' . time() );
			$fullname = "{$filename}.json";

			// Write JSON file.
			$this->write_json_file( $this->users_ceus, $fullname );

			// Update filename in DB.
			update_option( 'ceu_report_file_name', $filename );
			// Update last upated time.
			update_option( 'ceu_report_last_updated', time() );

			return $fullname;
		}

		return false;
	}

	/**
	 * Write JSON file.
	 *
	 * @param array $data
	 * @param string $filename
	 *
	 * @return void
	 */
	public function write_json_file( $data, $filename = 'credit-report.json' ) {
		if ( empty( $data ) ) {
			return;
		}

		$base_path = dirname( InitializePlugin::$path_to_plugin_file ) . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR;

		// Get the filename from options table
		$last_filename = get_option( 'ceu_report_file_name', '' );
		// unlink last file if exists
		$old_path = $base_path . "{$last_filename}.json";

		if ( file_exists( $old_path ) ) {
			unlink( $old_path );
		}

		// Create new file
		$path = $base_path . $filename;

		// Write in the file
		$fp = fopen( $path, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		fwrite( $fp, wp_json_encode( $data ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
		fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
	}
}
