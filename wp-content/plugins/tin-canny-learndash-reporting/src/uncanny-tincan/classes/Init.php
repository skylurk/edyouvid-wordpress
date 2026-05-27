<?php
/**
 * Initializing
 *
 * @since      1.0.0
 *
 * @TODO       Admin Table Header RWD
 * @TODO       Mark Complete Hooks
 * @author     Uncanny Owl
 * @package    Tin Canny Reporting for LearnDash
 * @subpackage TinCan Module
 */

namespace UCTINCAN;

if ( ! defined( 'UO_ABS_PATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 *
 */
class Init {
	// Constants
	/**
	 *
	 */
	const TINCAN_URL_KEY = 'ucTinCan';
	/**
	 *
	 */
	const TABLE_VERSION_KEY = 'uncanny_tincanny_db_version';

	// Instances
	/**
	 * @var
	 */
	public static $TinCan; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	// Endpoint URL
	/**
	 * @var
	 */
	public static $endpint_url;

	// Upgraded Commited
	/**
	 * @var bool
	 */
	private static $done_upgraded = false;

	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 * @since  1.0.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'reporting_api' ) );

		$this->set_objects();
		$this->create_hooks();
		$this->purge_user_records();
	}

	/**
	 * Purge User Records actions
	 */
	public function purge_user_records() {
		add_action( 'show_user_profile', array( $this, 'show_user_profile' ), 10 );
		add_action( 'edit_user_profile', array( $this, 'show_user_profile' ), 10 );
		add_action( 'personal_options_update', array( $this, 'save_user_profile' ), 10 );
		add_action( 'edit_user_profile_update', array( $this, 'save_user_profile' ), 10 );
	}

	/**
	 * Show user profile
	 */
	public function show_user_profile( $profileuser ) {

		if ( current_user_can( 'manage_options' ) ) {

			$args    = array(
				'numberposts' => 9999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts
				'post_type'   => 'sfwd-courses',
				'orderby'     => 'title',
				'order'       => 'ASC',
			);

			$ld_courses = get_posts( $args );

			?>
			<h2><?php esc_html_e( 'Permanently Delete Tin Can Data', 'uncanny-learndash-reporting' ); ?></h2>
			<p>
				<input type="checkbox" id="purge_tincanny_records" name="purge_tincanny_records"/>
				<label for="purge_tincanny_records">
					<?php
					// translators: %1$s is "Update", and %2$s is "tin can data"
					printf( _x( 'Check and click %1$s to delete all %2$s for this user.', '%1$s is "Update", and %2$s is "tin can data"', 'uncanny-learndash-reporting' ), sprintf( '<em>%s</em>', esc_html__( 'Update', 'uncanny-learndash-reporting' ) ), sprintf( '<strong>%s</strong>', esc_html__( 'tin can data', 'uncanny-learndash-reporting' ) ) );
					?>

					<strong><?php esc_html_e( 'This cannot be undone.', 'uncanny-learndash-reporting' ); ?></strong>
				</label>
			</p>
			<p>
				<input type="checkbox" id="purge_resume_records" name="purge_resume_records"/>
				<label for="purge_resume_records">
					<?php
					// translators: %1$s is "Update", and %2$s is "bookmark data"
					printf( esc_html_x( 'Check and click %1$s to delete all %2$s for this user.', '%1$s is "Update", and %2$s is "bookmark data"', 'uncanny-learndash-reporting' ), sprintf( '<em>%s</em>', esc_html__( 'Update', 'uncanny-learndash-reporting' ) ), sprintf( '<strong>%s</strong>', esc_html__( 'bookmark data', 'uncanny-learndash-reporting' ) ) );
					?>
					<strong><?php esc_html_e( 'This cannot be undone.', 'uncanny-learndash-reporting' ); ?></strong>
				</label>
			</p>
			<p>
				<label for="purge_course_resume_records">
					<?php
					// translators: %1$s is "Update", and %2$s is "bookmark data"
					printf( esc_html_x( 'Choose a course and click %1$s to delete all %2$s for the selected course for this user.', '%1$s is "Update", and %2$s is "bookmark data"', 'uncanny-learndash-reporting' ), sprintf( '<em>%s</em>', esc_html__( 'Update', 'uncanny-learndash-reporting' ) ), sprintf( '<strong>%s</strong>', esc_html__( 'bookmark data', 'uncanny-learndash-reporting' ) ) );
					?>
					<strong><?php esc_html_e( 'This cannot be undone.', 'uncanny-learndash-reporting' ); ?></strong>

					<div class="purge_course_resume_wrapper">
						<?php if( ! empty($ld_courses) ) : ?>
						<select class="uo_settings_single" id="purge_course_resume_records" name="purge_course_resume_records">
							<option value='' selected><?php echo esc_html__( '-No Action-', 'uncanny-learndash-reporting' ); ?></option>
							<?php foreach ( $ld_courses as $course ) { ?>
								<option value="<?php echo esc_attr( $course->ID ); ?>">
									<?php echo esc_html( $course->post_title ); ?>
								</option>
							<?php } // foreach( $ld_courses ) ?>
						</select>
						<?php else : ?>
						<select disabled class="uo_settings_single" id="purge_course_resume_records" name=>
							<option value='' disabled selected><?php echo esc_html__( 'No courses found.', 'uncanny-learndash-reporting' ); ?></option>
						</select>
						<?php endif; ?>
					</div>
				</label>

			</p>
			<?php
			// Action for additional user information.
			do_action( 'tincanny_additional_user_profile', $profileuser );
		}

	}

	/**
	 * Save user profile
	 */
	public function save_user_profile( $user_id ) {
		if ( current_user_can( 'manage_options' ) && class_exists( '\UCTINCAN\Database\Admin' ) ) {

			$type = array();

			if ( 'on' === ultc_get_filter_var( 'purge_tincanny_records', '', INPUT_POST ) ) {
				$type[] = 'reporting';
			}

			if ( 'on' === ultc_get_filter_var( 'purge_resume_records', '', INPUT_POST ) ) {
				$type[] = 'resume';
			}

			if ( ! empty( $type ) ) {
				$database = new \UCTINCAN\Database\Admin();
				$database->reset_user( $user_id, $type );
			}

			if ( 0 < ( $course_id = absint( ultc_get_filter_var( 'purge_course_resume_records', '', INPUT_POST ) ) ) ) {
				$database = new \UCTINCAN\Database\Admin();
				$database->reset_user_course( $user_id, $course_id );
			}
		}
	}

	/**
	 * Upgrade
	 *
	 * @access private
	 * @return void
	 * @since  1.3.9
	 */
	public static function check_upgrade() {
		if ( self::$done_upgraded ) {
			return;
		}

		// If Option doesn't Exists
		if ( UNCANNY_REPORTING_DB_VERSION !== get_option( self::TABLE_VERSION_KEY, '' ) ) {
			$database = new Database();
			$database->upgrade();
			// Delete old version key option
			delete_option( 'UncannyOwl TinCanny DB Version' );
		}

		self::$done_upgraded = true;
	}

	/**
	 * Set Objects
	 *
	 * @access private
	 * @return void
	 * @since  1.0.0
	 */
	private function set_objects() {
		new Server();
		new Services();

		// For Edge
		if ( ! headers_sent() ) {
			$header = apply_filters( 'tincanny_content_security_policy', "Content-Security-Policy: script-src * 'self' 'unsafe-inline' 'unsafe-eval' wistia.com youtube.com blob:" );
			@header( $header ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		if ( is_admin() ) {
			// moved to init action for dynamic post types
			new Admin\WP_UserProfile();
		}
	}

	/**
	 * Create Hooks
	 *
	 * @access private
	 * @return void
	 * @since  1.0.0
	 */
	private function create_hooks() {
		add_action( 'init', array( $this, 'set_objects_on_init' ), 100 );
		add_action( 'init', array( $this, 'activate_h5p_xapi' ), 110 );

		// Admin Ajax
		add_action( 'wp_ajax_uncanny_tincanny_report_get_modules', array( $this, 'print_modules_form_from_url_parameter' ) );
		add_action( 'wp_ajax_uncanny_tincanny_xapi_report_get_questions', array( $this, 'print_questions_list' ) );
		add_action( 'wp_ajax_uncanny_tincanny_report_get_courses', array( $this, 'group_courses' ) );

		// Filter removed because of loading sequence conflict...
		add_action( 'wp_ajax_process-xapi-statement', array( $this, 'process_xapi_statement' ) );
		add_action( 'wp_ajax_nopriv_process-xapi-statement', array( $this, 'process_xapi_statement' ) );

	}

	/**
	 * Set Objects on init Hooking Point
	 *
	 * @access public
	 * @return void
	 * @since  1.0.0
	 */
	public function set_objects_on_init() {
		$permalink = get_option( 'permalink_structure' );
		$pathinfo  = '';

		if ( strstr( $permalink, 'index.php' ) ) {
			$pathinfo = 'index.php/';
		}
		if ( is_admin() ) {
			new Admin\Metabox();
		}
		self::$endpint_url = get_bloginfo( 'wpurl' ) . '/' . $pathinfo . self::TINCAN_URL_KEY;
		self::$TinCan      = new \TinCan\RemoteLRS( self::$endpint_url, '1.0.1', 0, 0 ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * @return void
	 */
	public function reporting_api() {
		$controller = new RestEndpoint();
		$controller->register_routes();
	}

	/**
	 * Set H5P xAPI if doesn't exist
	 *
	 * @access public
	 * @return void
	 * @since  1.0.0
	 */
	public function activate_h5p_xapi() {
		if ( has_action( 'admin_menu', 'h5pxapi_admin_menu' ) ) {
			return;
		}

		include_once dirname( dirname( dirname( __FILE__ ) ) ) . '/h5p-xapi/wp-h5p-xapi.php';
		remove_action( 'admin_menu', 'h5pxapi_admin_menu' );

		$endpoint_url = get_option( 'h5pxapi_endpoint_url' );
		$username     = get_option( 'h5pxapi_username' );
		$password     = get_option( 'h5pxapi_password' );

		if ( $endpoint_url !== self::$endpint_url ) {
			update_option( 'h5pxapi_endpoint_url', self::$endpint_url );
		}

		if ( empty( $username ) ) {
			update_option( 'h5pxapi_username', 1 );
		}

		if ( empty( $password ) ) {
			update_option( 'h5pxapi_password', 1 );
		}
	}

	/**
	 * Ajax Callback For Admin Module <option>s
	 *
	 * @access public
	 * @return void
	 * @since  1.0.0
	 */
	public function print_modules_form_from_url_parameter() {
		$database = new Database\Admin();
		$database->print_modules_form_from_url_parameter();

		wp_die();
	}

	/**
	 * Ajax Callback For Admin Questions
	 *
	 * @access public
	 * @return void
	 * @since  1.0.0
	 */
	public function print_questions_list() {
		$database          = new Database\Admin();
		$q                 = ultc_get_filter_var( 'q', false, INPUT_POST );
		$q                 = empty( $q ) ? false : $q;
		$ld_actions        = $database->get_questions( $q );
		$json              = array( 'results' );
		$json['results'][] = array(
			'id'   => '',
			'text' => __( 'All Questions', 'uncanny-learndash-reporting' ),
		);
		if ( ! empty( $ld_actions ) ) {
			foreach ( $ld_actions as $action ) {
				$json['results'][] = array(
					'id'   => $action['activity_name_hash'],
					'text' => $action['activity_name'],
				);
			}
		}
		echo wp_json_encode( $json );

		wp_die();
	}

	/**
	 * @param $string
	 *
	 * @return bool
	 */
	private function is_json( $string ) {
		json_decode( $string );

		return json_last_error() === JSON_ERROR_NONE;
	}

	/**
	 * Ajax Callback For Admin Questions
	 *
	 * @access public
	 * @return void
	 * @since  3.2.3
	 */
	public function process_xapi_statement() {
		// Get Request Type.
		$request_type = ultc_current_request_type();

		// Get Statement.
		$statement = ultc_filter_has_var( 'statement', $request_type ) ? ultc_filter_input( 'statement', $request_type ) : array();
		$statement = ! empty( $statement ) && $this->is_json( $statement ) ? json_decode( stripslashes( $statement ), true ) : array();

		if ( isset( $statement['context'] ) && isset( $statement['context']['extensions'] ) && ! $statement['context']['extensions'] ) {
			unset( $statement['context']['extensions'] );
		}

		if ( has_filter( 'h5p-xapi-pre-save' ) ) {
			$statement = apply_filters( 'h5p-xapi-pre-save', $statement ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

			if ( ! $statement ) {
				echo wp_json_encode(
					array(
						'ok'      => 1,
						'message' => null,
					)
				);
				exit;
			}
		}

		$tin_can_h5p = new \UCTINCAN\TinCanRequest\H5P( $statement );
		$res         = $tin_can_h5p->get_completion();
		if ( $res ) {
			$response = array(
				'ok'      => 1,
				'message' => 'true',
				'code'    => 200,
			);
		} else {
			$response = array(
				'ok'      => 1,
				'message' => 'false',
				'code'    => 200,
			);
		}

		echo wp_json_encode( $response );
		exit();
	}

	/**
	 * Ajax Callback For Admin Module <option>s
	 *
	 * @access public
	 * @return void
	 * @since  1.0.0
	 */
	public function group_courses() {

		$tc_filter_group_id = absint( ultc_get_filter_var( 'tc_filter_group', 0, INPUT_POST ) );
		if ( empty( $tc_filter_group_id ) ) {
			wp_die();
		}

		$group_leader_id = get_current_user_id();
		$user_group_ids  = learndash_get_administrators_group_ids( $group_leader_id, false );
		// check is user group
		if ( in_array( $tc_filter_group_id, $user_group_ids, true ) ) {
			$courses = learndash_group_enrolled_courses( $tc_filter_group_id );
			$args    = array(
				'numberposts' => 9999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts
				'include'     => array_map( 'intval', $courses ),
				'post_type'   => 'sfwd-courses',
				'orderby'     => 'title',
				'order'       => 'ASC',
			);

			$courses = get_posts( $args );
			foreach ( $courses as $course ) {
				printf( '<option value="%s">%s</option>', esc_attr( $course->ID ), esc_html( $course->post_title ) );
			}
		}
		wp_die();
	}
}
