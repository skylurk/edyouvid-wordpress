<?php

namespace uncanny_pro_toolkit;

use uncanny_learndash_toolkit as toolkit;
use uncanny_pro_toolkit\CertificateBuilder;
use \ZipArchive;
use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;
use WP_Query;

/**
 * Class GroupCompletionCertificate
 *
 * @package uncanny_pro_toolkit
 */
class DownloadCertificatesInBulk extends toolkit\Config implements toolkit\RequiredFunctions {

	/**
	 * Current timestamp var
	 *
	 * @var
	 */
	public static $current_time_stamp;

	/**
	 * PDF file name var
	 *
	 * @var
	 */
	public static $pdf_filename;

	/**
	 * Pagination batch var
	 *
	 * @var int
	 */
	public static $batch = 100;

	/**
	 * @var int|mixed
	 */
	public static $current_user_id = 0;

	/**
	 * @var string
	 */
	private static $capability = 'manage_options';


	/**
	 * Class constructor
	 */
	public function __construct() {

		add_action( 'plugins_loaded', array( __CLASS__, 'run_frontend_hooks' ) );

		self::$capability = apply_filters( 'uo_bulk_downlad_cert_capability', 'manage_options' );

	}

	/*
	 * Initialize frontend actions and filters
	 *
	 * @return void
	 */
	/**
	 * @return void
	 */
	public static function run_frontend_hooks() {

		if ( true === self::dependants_exist() ) {

			require_once Boot::get_pro_include( 'certificate-builder.php', UO_FILE );
			require_once Boot::get_pro_include( 'tcpdf-certificate-code.php', UO_FILE );
			require_once Boot::get_pro_include( 'uncanny-shortcodes-parser.php', UO_FILE );

			// Download certificates shortcode.
			add_shortcode(
				'uo_download_certificates',
				array(
					__CLASS__,
					'shortcode_uo_download_certificates_in_bulk',
				)
			);

			// Register the endpoint to generate dropdowns
			add_action( 'rest_api_init', array( __CLASS__, 'uo_register_rest_endpoints' ) );

			/* ADD FILTERS ACTIONS FUNCTION */
			add_action(
				'wp_enqueue_scripts',
				array(
					__CLASS__,
					'add_certificates_scripts',
				)
			);

			add_filter(
				'cron_schedules',
				array(
					__CLASS__,
					'register_cron_schedule',
				)
			);

			add_action(
				'uo_scheduled_generate_certificates_in_bulk',
				array(
					__CLASS__,
					'uo_cron_execution_method',
				),
				1
			);

			add_action(
				'uo_delete_zip_file',
				array(
					__CLASS__,
					'uo_delete_zip_file',
				),
				1
			);

		}

	}

	/**
	 * @param $file_expiry_time
	 *
	 * @return mixed|null
	 */
	public static function uo_file_expiry_time( $file_expiry_time = 86400 ) {
		return apply_filters( 'uo_file_expiry_time', $file_expiry_time );
	}


	/**
	 * Add certificates scripts and styles.
	 *
	 * @return void
	 */
	public static function add_certificates_scripts() {
		global $post;

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		if (
			! has_shortcode( $post->post_content, 'uo_download_certificates' ) &&
			! has_block( 'uncanny-toolkit-pro/download-certificates-bulk', $post )
		) {
			return;
		}

		self::enqueue_assets();
	}

	/**
	 * Loads the static assets without performing any validation.
	 * Site owners can use this method you can manually load the assets anywhere.
	 *
	 * @return void
	 */
	public static function enqueue_assets() {
		$localize_data_array = array(
			'rest_url'           => esc_url_raw( rest_url( 'uo_pro/v1/' ) ),
			'nonce'              => \wp_create_nonce( 'wp_rest' ),
			'groups_label'       => esc_html__( 'Select group', 'uncanny-pro-toolkit' ),
			'course_label'       => esc_html__( 'Select course', 'uncanny-pro-toolkit' ),
			'any_course_label'   => esc_html__( 'Any course', 'uncanny-pro-toolkit' ),
			'quiz_label'         => esc_html__( 'Select quiz', 'uncanny-pro-toolkit' ),
			'any_quiz_label'     => esc_html__( 'All quizzes in the course', 'uncanny-pro-toolkit' ),
			'uo_success_msg'     => esc_html__( "Certificates are now being generated. Since this process can take several minutes, we will send you an email (to your profile's email address) with a link to the zip file when it is available to download.", 'uncanny-pro-toolkit' ),
			'uo_failure_msg'     => esc_html__( 'Something went wrong! Please try again.', 'uncanny-pro-toolkit' ),
			'uo_current_user_id' => get_current_user_id(),
			'i18n'               => array(
				'empty' => esc_html__( 'Empty', 'uncanny-pro-toolkit' ),
			),
		);

		if (
			method_exists( '\uncanny_learndash_toolkit\Config', 'get_vendor' )
		) {
			wp_enqueue_style(
				'ult-select2',
				\uncanny_learndash_toolkit\Config::get_vendor( 'select2/css/select2.min.css' ),
				array(),
				UNCANNY_TOOLKIT_PRO_VERSION
			);

			wp_enqueue_script(
				'ult-select2',
				\uncanny_learndash_toolkit\Config::get_vendor( 'select2/js/select2.min.js' ),
				array( 'jquery' ),
				UNCANNY_TOOLKIT_PRO_VERSION,
				true
			);
		}

		$assets_url = plugins_url( basename( dirname( UO_FILE ) ) ) . '/src/assets/dist/';

		wp_register_script( 'ultp-frontend', $assets_url . 'frontend/bundle.min.js', array(
			'jquery',
			'ult-select2',
		), UNCANNY_TOOLKIT_PRO_VERSION );
		wp_localize_script( 'ultp-frontend', 'uoCertificates', $localize_data_array );
		wp_enqueue_script( 'ultp-frontend' );

		wp_enqueue_style( 'ultp-frontend', $assets_url . 'frontend/bundle.min.css', array( 'ult-select2' ), UNCANNY_TOOLKIT_PRO_VERSION );
	}

	/**
	 * Registering cron schedule.
	 *
	 * @param $schedules
	 *
	 * @return mixed
	 */
	public static function register_cron_schedule( $schedules ) {
		$schedules['every_60_seconds'] = array(
			'interval' => 60,
			'display'  => 'Once every 60 seconds',
		);

		$schedules['every_5_minutes'] = array(
			'interval' => 300,
			'display'  => 'Once every 5 minutes',
		);

		return $schedules;
	}

	/**
	 * Registers a REST API endpoint to change the visibility of the
	 * "Try Automator" item
	 *
	 * @since 3.5.4
	 */
	public static function uo_register_rest_endpoints() {
		// get groups dropdown options
		register_rest_route(
			'uo_pro/v1',
			'/get-groups-dropdown/',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'uo_generate_groups_dropdowns' ),
				'permission_callback' => function () {
					return true;
				},
			)
		);

		// get courses dropdown options
		register_rest_route(
			'uo_pro/v1',
			'/get-courses-dropdown/',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'uo_generate_courses_dropdowns' ),
				'permission_callback' => function () {
					return true;
				},
			)
		);

		// get quiz dropdown options
		register_rest_route(
			'uo_pro/v1',
			'/get-quiz-dropdown/',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'uo_generate_quizzes_dropdowns' ),
				'permission_callback' => function () {
					return true;
				},
			)
		);

		// set cron job to generate certificates.
		register_rest_route(
			'uo_pro/v1',
			'/set-cron-job/',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'uo_set_cron_job' ),
				'permission_callback' => function () {
					return array( __CLASS__, 'get_custom_users_data' );
				},
			)
		);

		// set cron job to clean up certificates.
		register_rest_route(
			'uo_pro/v1',
			'/uo-cleanup-certificates/',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'uo_cleanup_certificates' ),
				'permission_callback' => function () {
					return array( __CLASS__, 'get_custom_users_permissions' );
				},
			)
		);

		// Clear Previous Runs REST Handler.
		register_rest_route(
			'uo_pro/v1',
			'/uo-clear-certificates-previous-runs/',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'uo_clear_previous_cron_batches' ),
				'permission_callback' => function () {
					return array( __CLASS__, 'get_custom_users_permissions' );
				},
			)
		);
	}


	/**
	 * Checking if user is logged in or not.
	 *
	 * @return false|\WP_User
	 */
	public static function get_custom_users_data() {
		return get_user_by( 'id', $GLOBALS['user_id'] );
	}

	/**
	 * Checking current user rights
	 *
	 * @return false|\WP_User
	 */
	public static function get_custom_users_permissions() {
		return current_user_can( 'manage_options' ) || current_user_can( self::$capability );
	}

	/**
	 * Generating groups dropdown.
	 *
	 * @param $request
	 *
	 * @return \WP_REST_Response
	 */
	public static function uo_generate_groups_dropdowns( $request ) {
		// check if its a valid request.
		$data = $request->get_params();

		if ( isset( $data['action'] ) && 'get_uo_groups' === $data['action'] ) {
			if ( ! self::validate_user_id( $data ) ) {
				return new \WP_REST_Response(
					array(
						'msg'     => __( 'You do not have sufficient permission to perform this action.', 'uncanny-pro-toolkit' ),
						'success' => false,
					),
					200
				);
			}

			$results           = self::ld_get_group_list( $data['certificates'], $data['uo_current_user'], $data );
			$results           = apply_filters( 'uo_download_certificates_in_bulk_group_data', $results, $data );
			$return            = array();
			$return['success'] = false;
			if ( ! empty( $results ) ) {
				$return['success'] = true;
				$return['data']    = $results;
			} else {
				$return['msg'] = __( 'Sorry, no groups are available for you to manage.', 'uncanny-pro-toolkit' );
			}

			return new \WP_REST_Response(
				$return,
				200
			);
		}

		return new \WP_REST_Response(
			array(
				'msg'     => 'Certificate ID or groups not found.',
				'success' => false,
			),
			200
		);
	}


	/**
	 * Generating Courses dropdown.
	 *
	 * @param $request
	 *
	 * @return \WP_REST_Response
	 */
	public static function uo_generate_courses_dropdowns( $request ) {
		// check if its a valid request.
		$data = $request->get_params();
		if ( isset( $data['action'] ) && 'get_uo_courses' === $data['action'] ) {
			if ( ! self::validate_user_id( $data ) ) {
				return new \WP_REST_Response(
					array(
						'msg'     => __( 'You do not have sufficient permission to perform this action.', 'uncanny-pro-toolkit' ),
						'success' => false,
					),
					200
				);
			}

			$results           = self::ld_get_courses_list( $data['group_id'], $data['certificates'], $data );
			$results           = apply_filters( 'uo_download_certificates_in_bulk_course_data', $results, $data );
			$return            = array();
			$return['success'] = false;
			if ( ! empty( $results ) ) {
				$return['success'] = true;
				$return['data']    = $results;
			} else {
				$return['msg'] = __( 'Sorry, no certificates were found for courses in the selected group. Please choose another group.', 'uncanny-pro-toolkit' );
			}

			return new \WP_REST_Response(
				$return,
				200
			);
		}

		return new \WP_REST_Response(
			array(
				'msg'     => 'No group ID passed or courses not found.',
				'success' => false,
			),
			200
		);
	}

	/**
	 * Generating create dropdown.
	 *
	 * @param $request
	 *
	 * @return \WP_REST_Response
	 */
	public static function uo_generate_quizzes_dropdowns( $request ) {
		// check if its a valid request.
		$data = $request->get_params();

		if ( isset( $data['action'] ) && 'get_uo_quizzes' === $data['action'] ) {
			if ( ! self::validate_user_id( $data ) ) {
				return new \WP_REST_Response(
					array(
						'msg'     => __( 'You do not have sufficient permission to perform this action.', 'uncanny-pro-toolkit' ),
						'success' => false,
					),
					200
				);
			}
			$results           = self::ld_get_quizzes_list( absint( $data['course_id'] ), $data['certificates'], $data );
			$results           = apply_filters( 'uo_download_certificates_in_bulk_quiz_data', $results, $data );
			$return            = array();
			$return['success'] = false;
			if ( ! empty( $results ) ) {
				$return['success'] = true;
				$return['data']    = $results;
			} else {
				$return['msg'] = sprintf( __( 'Sorry, no %s with certificates were found for the selected course. Please choose another %s.', 'uncanny-pro-toolkit' ), \LearnDash_Custom_Label::get_label( 'quizzes' ), \LearnDash_Custom_Label::get_label( 'course' ) );
			}

			return new \WP_REST_Response(
				$return,
				200
			);
		}

		return new \WP_REST_Response(
			array(
				'msg'     => 'Course ID not passed or no courses found.',
				'success' => false,
			),
			200
		);
	}

	/**
	 * @param $data
	 *
	 * @return bool
	 */
	public static function validate_user_id( $data ) {
		if ( ! isset( $data['uo_current_user'] ) ) {
			return false;
		}
		if ( user_can( $data['uo_current_user'], 'manage_options' ) ) {
			return true; //Make sure admin get desired access.
		}
		if ( ! user_can( $data['uo_current_user'], self::$capability ) && ! user_can( $data['uo_current_user'], 'group_leader' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Does the plugin rely on another function or plugin
	 *
	 * @return boolean || string Return either true or name of function or plugin
	 */
	public static function dependants_exist() {

		/* Checks for LearnDash */
		global $learndash_post_types;
		if ( ! isset( $learndash_post_types ) ) {
			return 'Plugin: LearnDash';
		}

		// Return true if no dependency or dependency is available
		return true;
	}

	/**
	 * Description of class in Admin View
	 *
	 * @return array
	 */
	public static function get_details() {
		$module_id = 'download-certificates-in-bulk';

		$class_title = esc_attr__( 'Download Certificates In Bulk', 'uncanny-pro-toolkit' );

		//set to null or remove to disable the link to KB
		$kb_link = 'https://www.uncannyowl.com/knowledge-base/download-certificates-in-bulk/';

		/* Sample Simple Description with shortcode */
		$class_description = esc_html__( 'Download course, quiz and group certificates in bulk.', 'uncanny-pro-toolkit' );

		/* Icon as fontawesome icon */
		$class_icon = '<i class="uo_icon_pro_fa uo_icon_fa fa fa-file-pdf-o"></i><span class="uo_pro_text">PRO</span>';

		$category = 'learndash';
		$type     = 'pro';

		return array(
			'id'               => $module_id,
			'title'            => $class_title,
			'type'             => $type,
			'category'         => $category,
			'kb_link'          => $kb_link, // OR set as null not to display
			'description'      => $class_description,
			'dependants_exist' => self::dependants_exist(),
			'settings'         => self::get_class_settings( $class_title ),
			'icon'             => $class_icon,
		);

	}

	/**
	 * HTML for modal to create settings
	 *
	 * @static
	 *
	 * @param $class_title
	 *
	 * @return array
	 */
	public static function get_class_settings( $class_title ) {

		$all_pages    = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => 99999,
			)
		);
		$page_options = array();
		$index        = 0;
		foreach ( $all_pages as $page ) {
			$page_options[ $index ]['value'] = $page->ID;
			$page_options[ $index ]['text']  = $page->post_title;
			$index ++;
		}

		$message = __( 'By default, zip files are stored at: &lt;site root&gt;/wp-content', 'uncanny-pro-toolkit' ) . apply_filters( 'uo_download_certificates_in_bulk_folder', '/uploads/bulk-certificates' ) . '. ';

		if ( ! class_exists( 'ZipArchive' ) ) {
			$message .= '<a href="https://www.php.net/manual/en/class.ziparchive.php">ZipArchive</a> class is not available. Contact your hosting provider to enable the zip module.';
		}


		// Create options
		$options = array(
			array(
				'type'       => 'html',
				'inner_html' => $message,
			),
			array(
				'type'        => 'number',
				'placeholder' => 'Minimum storage space required to generate certificates.',
				'label'       => esc_attr__( 'Minimum free space required (in GB)', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-group-certificate-in-bulk-minimum-storage',
				'default'     => 2,
			),
			array(
				'type'        => 'text',
				'placeholder' => get_bloginfo( 'name' ),
				'label'       => esc_attr__( 'From Name', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-group-certificate-in-bulk-from-name',
			),
			array(
				'type'        => 'text',
				'placeholder' => 'From email',
				'label'       => esc_attr__( 'From Email', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-group-certificate-in-bulk-from-email',
			),
			array(
				'type'        => 'text',
				'placeholder' => __( 'Your certificates for %Certificates for% are ready for download!', 'uncanny-pro-tookit' ),
				'label'       => esc_attr__( 'Email Subject', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-group-certificate-in-bulk-user-subject-line',
			),
			array(
				'type'        => 'textarea',
				'default'     => 'Hi %User First Name%,
The zip file that includes your requested certificates is now available for download.

Requested details:
Certificates for: %Certificates for%
Time requested: %Time requested%

Click this link to retrieve the file: %Link%
This link will expire in ' . date_i18n( 'H', self::uo_file_expiry_time() ) . ' hours.',
				'label'       => esc_attr__( 'Email Body', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-group-certificate-in-bulk-email-body',
			),
			array(
				'type'       => 'html',
				'inner_html' => '<strong>Available variables for email subject & body</strong><br /><ul><li><strong>%User First Name%</strong> &mdash; Prints User\'s First Name</li><li><strong>%Link%</strong> &mdash; Prints File Link</li><li><strong>%Certificates for%</strong> &mdash; Prints Group/Course/Quiz title</li><li><strong>%Time requested%</strong> &mdash; Prints requested time</li></ul>',
			),
			array(
				'type'       => 'html',
				'inner_html' => '<a class="ult-modal-action__btn uo_certificates_cleanup_btn" href="javascript:void(0)">Delete certificate zip files from the server</a>',
			),
			array(
				'type'       => 'html',
				'inner_html' => '<span id="uo-download-cert-in-bulk-success">' . __( 'Certificate zip files were deleted from the server.', 'uncanny-pro-toolkit' ) . '</span>',
			),
			array(
				'type'       => 'html',
				'inner_html' => '<span id="uo-download-cert-in-bulk-warning">' . __( 'No certificate zip files are on the server.', 'uncanny-pro-toolkit' ) . '</span>',
			),
			array(
				'type'       => 'html',
				'inner_html' => '<a class="ult-modal-action__btn uo_certificates_clear_btn" href="javascript:void(0)">Clear previous runs</a>',
			),
			array(
				'type'       => 'html',
				'inner_html' => '<span id="uo-cert-in-bulk-clear-success">' . __( 'Previous runs have been cleared.', 'uncanny-pro-toolkit' ) . '</span>',
			),
			array(
				'type'       => 'html',
				'inner_html' => '<span id="uo-cert-in-bulk-clear-warning">' . __( 'No previous runs to clear.', 'uncanny-pro-toolkit' ) . '</span>',
			),
		);

		// Build html.
		return self::settings_output(
			array(
				'class'   => __CLASS__,
				'title'   => $class_title,
				'options' => $options,
			)
		);

	}


	/**
	 * Generate group certificates method.
	 *
	 * @param $user_id
	 * @param $object_id
	 * @param $batch_id
	 * @param $type
	 *
	 * @return void
	 */
	public static function generate_group_certificate( $user_id, $object_id, $batch_id, $type = 'group' ) {

		$user = new \WP_User( $user_id );

		$setup_parameters = self::setup_parameters( $object_id, $user_id, $type );
		if ( 1 !== (int) $setup_parameters['print-certificate'] ) {
			return;
		}

		self::$current_time_stamp = time();
		$new_folder               = '/bulk_export_' . $type . '_' . date_i18n( 'Y-m-d' ) . '_' . $batch_id . '/';

		$certificate_post = $setup_parameters['certificate-post'];
		/* Save Path on Server under Upload & allow overwrite */
		$save_path = WP_CONTENT_DIR . apply_filters( 'uo_download_certificates_in_bulk_folder', '/uploads/bulk-certificates' ) . $new_folder;

		$uo_dir = WP_CONTENT_DIR . apply_filters( 'uo_download_certificates_in_bulk_folder', '/uploads/bulk-certificates' );
		if ( ! is_dir( $uo_dir ) ) {
			mkdir( $uo_dir, 0755 );
		}

		/**
		 * New filter added so that arguments can be passed. Adding arguments
		 * to previous filter above might break sites since
		 * there might be no argument supplied with override function
		 *
		 * @since  3.6.4
		 * @author Saad
		 * @var $save_path
		 */
		$save_path = apply_filters( 'uo_download_certificates_in_bulk_upload_dir', $save_path, $user, $object_id, $certificate_post, self::$current_time_stamp );

		/* Creating a fileName that is going to be stored on the server. Certificate-QUIZID-USERID-NONCE_String */
		$file_name = sanitize_title( $user->user_email . '-' . $object_id . '-' . $certificate_post . '-' . date_i18n( 'Y-m-d', self::$current_time_stamp ) . '-' . wp_create_nonce( self::$current_time_stamp ) );

		//Allow overwrite of custom filename
		$file_name = apply_filters( 'uo_download_certificates_in_bulk_filename', $file_name, $user, $object_id, $certificate_post, self::$current_time_stamp );
		if ( ! file_exists( $save_path ) && ! mkdir( $save_path, 0755 ) && ! is_dir( $save_path ) ) { // phpcs:ignore
			throw new \RuntimeException( sprintf( 'Directory "%s" was not created', $save_path ) );
		}

		$full_path          = $save_path . $file_name;
		self::$pdf_filename = $full_path;

		//Allow PDF args to be modified
		$generate_pdf_args = apply_filters(
			'uo_download_certificates_in_bulk_pdf_args',
			array(
				'certificate_post' => $certificate_post,
				'save_path'        => $save_path,
				'user'             => $user,
				'file_name'        => $file_name,
				'parameters'       => $setup_parameters,
				'type'             => $type,
				'bulk_generator'   => 'yes',
			),
			$object_id,
			$user_id
		);
		self::generate_pdf( $generate_pdf_args, $type );
	}


	/**
	 * Setup parameters.
	 *
	 * @param $object_id
	 * @param $user_id
	 * @param $type
	 *
	 * @return array|mixed|void
	 */
	public static function setup_parameters( $object_id, $user_id, $type = '' ) {

		$meta = get_post_meta( $object_id, '_ld_certificate', true );

		$setup_parameters = array();

		$setup_parameters['userID']            = $user_id;
		$setup_parameters['current_user']      = get_user_by( 'ID', $user_id );
		$setup_parameters['group-id']          = absint( $object_id );
		$setup_parameters['group-name']        = html_entity_decode( get_the_title( $object_id ) );
		$setup_parameters['print-certificate'] = 0;

		if ( 'quiz' === $type ) {
			$setup_parameters['quiz-id'] = absint( $object_id );
		}

		if ( 'course' === $type ) {
			$setup_parameters['course-id'] = absint( $object_id );
		}

		if ( ! empty( $meta ) ) {
			//Setting Certificate Post ID
			$setup_parameters['certificate-post'] = absint( $meta );
		}

		if ( empty( $setup_parameters['certificate-post'] ) ) {
			return $setup_parameters;
		}

		$setup_parameters['print-certificate'] = 1;

		return apply_filters( 'uo_group_completion_setup_parameters', $setup_parameters, $object_id, $user_id, $setup_parameters['certificate-post'] );
	}


	/**
	 * Generate pdf method.
	 *
	 * @param $args
	 * @param $type
	 *
	 * @return string
	 */
	public static function generate_pdf( $args, $type ) {
		$builder = new CertificateBuilder();

		if ( $builder->created_with_builder( $args['certificate_post'] ) ) {
			$pdf = $builder->generate_pdf( $args, $args['certificate_post'] );
		} else {
			// Swap the data for some LD functions
			self::filters( 'add', $args['parameters']['userID'] );

			$pdf = Tcpdf_Certificate_Code::generate_pdf( $args, $type, 'uo_cron' );

			// Clean up the filters
			self::filters( 'remove', $args['parameters']['userID'] );
		}

		return $pdf;
	}


	/**
	 * Set mail content type.
	 *
	 * @return string
	 */
	public static function mail_content_type() {
		return 'text/html';
	}

	/**
	 * Get groups list for admin and group leaders.
	 *
	 * @param string $certificate
	 * @param int $uo_current_user
	 * @param array $def_args
	 *
	 * @return int[]|\WP_Post[]
	 */
	public static function ld_get_group_list( $certificate = 'no', $uo_current_user = 0, $def_args = array() ): array {
		$group_ids  = learndash_get_administrators_group_ids( $uo_current_user );
		$all_groups = array();
		if ( $group_ids ) {
			foreach ( $group_ids as $group_id ) {
				// Check if certificate is added
				if ( 'yes' === $certificate && empty( get_post_meta( $group_id, '_ld_certificate', true ) ) ) {
					continue;
				}
				$all_groups[] = array(
					'value' => $group_id,
					'text'  => get_the_title( $group_id ),
				);
			}
		}

		$is_admin        = user_can( $uo_current_user, 'administrator' ) || user_can( $uo_current_user, self::$capability );
		$is_group_leader = user_can( $uo_current_user, 'group_leader' );
		$any_option      = array();

		if ( 'group' !== $def_args['uo_cert_type'] ) {
			if ( $is_admin && ( empty( $all_groups ) || count( $all_groups ) > 1 ) ) {
				$any_option = array(
					'value' => 'any',
					'text'  => sprintf( __( 'Any %s', 'uncanny-pro-toolkit' ), \LearnDash_Custom_Label::get_label( 'group' ) ),
				);
			} elseif ( $is_group_leader && count( $all_groups ) > 1 ) {
				$any_option = array(
					'value' => 'all',
					'text'  => sprintf( __( 'All %s', 'uncanny-pro-toolkit' ), \LearnDash_Custom_Label::get_label( 'groups' ) ),
				);
			}
			if ( ! empty( $any_option ) ) {
				array_unshift( $all_groups, $any_option );
			}
		}

		if ( ! isset( $def_args['uo_cert_type'] ) ) {
			return $all_groups;
		}

		if ( 'group' === $def_args['uo_cert_type'] ) {
			return $all_groups;
		}

		return $all_groups;
	}

	/**
	 * @param int $user_id
	 *
	 * @return array
	 */
	public static function ld_get_all_group_list( $user_id = 0 ) {
		if ( 0 !== absint( $user_id ) ) {
			$all_groups = array();
			$groups     = learndash_get_administrators_group_ids( $user_id );
			if ( $groups ) {
				foreach ( $groups as $group ) {
					if ( empty( get_post_meta( $group, '', true ) ) ) {
						continue;
					}
					$all_groups[] = $group;
				}
			}

			return $all_groups;
		}
		$args = array(
			'posts_per_page'   => 99999,
			'post_type'        => 'groups',
			'post_status'      => 'publish',
			'suppress_filters' => false,
			'orderby'          => 'title',
			'order'            => 'ASC',
		);

		$args['meta_query'] = array(
			array(
				'key'     => '_ld_certificate',
				'value'   => array( '' ),
				'compare' => 'NOT IN',
			),
		);

		$all_groups_query = new WP_Query( $args );
		$all_groups       = array();
		if ( $all_groups_query->have_posts() ) :
			while ( $all_groups_query->have_posts() ) :
				$all_groups_query->the_post();
				$all_groups[] = get_the_ID();
			endwhile;
		endif;

		return $all_groups;
	}

	/**
	 * Get group list for dropdowns.
	 *
	 * @param string $group_id
	 * @param string $certificate
	 * @param array $def_args
	 *
	 * @return array
	 */
	public static function ld_get_courses_list( $group_id = 'any', $certificate = 'no', $def_args = array() ) {
		$current_user_id = isset( $def_args['uo_current_user'] ) ? absint( $def_args['uo_current_user'] ) : 0;
		$args            = array(
			'post_type'        => function_exists( 'learndash_get_post_type_slug' ) ? learndash_get_post_type_slug( 'course' ) : 'sfwd-course',
			'posts_per_page'   => 9999,
			'suppress_filters' => false,
			'orderby'          => 'title',
			'order'            => 'ASC',
		);
		if ( 'yes' === $certificate ) {
			$args['meta_query'] = array(
				array(
					'key'     => '_ld_certificate',
					'compare' => 'EXISTS',
				),
			);
		}
		$courses = self::uo_build_course_list( $args );
		if ( empty( $courses ) ) {
			return $courses;
		}

		$course_ids      = array_column( $courses, 'ID' );
		$is_admin        = user_can( $current_user_id, 'administrator' ) || user_can( $current_user_id, self::$capability );
		$is_group_leader = user_can( $current_user_id, 'group_leader' );

		// Administrator is logged in and can request "Any group" with a course
		if ( $is_admin && 'any' === (string) $group_id ) {
			return $courses;
		}
		// A group leader is logged in AND "All groups" option is selected
		if ( $is_group_leader && 'all' === (string) $group_id ) {
			$final_courses = array();
			$gl_course_ids = learndash_get_group_leader_groups_courses( $current_user_id );
			$gl_course_ids = array_map( 'absint', $gl_course_ids );
			$common        = array_intersect( $course_ids, $gl_course_ids );
			if ( empty( $common ) ) {
				return array();
			}
			foreach ( $courses as $k => $v ) {
				if ( ! in_array( absint( $v['ID'] ), $gl_course_ids, true ) ) {
					// Only show courses that ARE in the group and has a valid certificate
					//unset( $courses[ $k ] );
					continue;
				}
				$final_courses[] = $v;
			}

			return $final_courses;
		}
		// A group ID was selected
		if ( is_numeric( $group_id ) && 0 !== $group_id ) {
			$final_courses = array();
			$group_courses = learndash_group_enrolled_courses( $group_id );
			$group_courses = array_map( 'absint', $group_courses );
			$common        = array_intersect( $course_ids, $group_courses );
			if ( empty( $common ) ) {
				return $common;
			}
			foreach ( $courses as $k => $v ) {
				// Only show courses that ARE in the group and has a valid certificate
				if ( ! in_array( absint( $v['ID'] ), $group_courses, true ) ) {
					//unset( $courses[ $k ] );
					continue;
				}
				$final_courses[] = $v;
			}

			return $final_courses;
		}

		return $courses;
	}

	/**
	 * @param $args
	 *
	 * @return array
	 */
	public static function uo_build_course_list( $args ) {
		$all_courses_query = get_posts( $args );
		$all_courses       = array();
		if ( empty( $all_courses_query ) ) {
			return $all_courses;
		}
		foreach ( $all_courses_query as $___post ) {
			$all_courses[] = array(
				'ID'           => $___post->ID,
				'course_title' => $___post->post_title,
			);
		}

		return $all_courses;
	}

	/**
	 * Get quizzes list for dropdowns.
	 *
	 * @param int $course_id
	 * @param string $certificate
	 * @param array $def_args
	 *
	 * @return array
	 */
	public static function ld_get_quizzes_list( $course_id = 0, $certificate = 'no', $def_args = array() ) {

		$quizzes = self::uo_learndash_get_course_quizzes( $course_id );
		if ( empty( $quizzes ) ) {
			return array();
		}
		$include_quizzes = array();
		foreach ( $quizzes as $quiz ) {
			$include_quizzes[] = $quiz->ID;
		}

		$args = array(
			'posts_per_page'   => 99999,
			'post_type'        => function_exists( 'learndash_get_post_type_slug' ) ? learndash_get_post_type_slug( 'quiz' ) : 'sfwd-quiz',
			'post_status'      => 'publish',
			'post__in'         => $include_quizzes,
			'suppress_filters' => false,
			'orderby'          => 'title',
			'order'            => 'ASC',
		);
		if ( 'yes' === $certificate ) {
			$args['meta_query'] = array(
				array(
					'key'     => '_ld_certificate',
					'value'   => array( '' ),
					'compare' => 'NOT IN',
				),
			);
		}

		$all_quizzes_query = new WP_Query( $args );
		$all_quizzes       = array();

		if ( $all_quizzes_query->have_posts() ) {
			while ( $all_quizzes_query->have_posts() ) {
				$all_quizzes_query->the_post();
				$all_quizzes[] = array(
					'ID'         => get_the_ID(),
					'quiz_title' => get_the_title(),
				);
			}
		}

		return $all_quizzes;
	}


	/**
	 * Shortcode method to generate html block.
	 *
	 * @param array $atts
	 * 
	 * @return false|string
	 */
	public static function shortcode_uo_download_certificates_in_bulk( $atts ) {
		ob_start();
		$user = wp_get_current_user();
		// Used to identify during file generation (or download zip link)
		$uo_token = ( filter_has_var( INPUT_GET, 'uo-token' ) ) ? sanitize_text_field( wp_unslash( filter_input( INPUT_GET, 'uo-token' ) ) ) : '';

		$atts = shortcode_atts(
			array(
				'allow-clear' => 'no',
			),
			$atts
		);

		if ( ! empty( $uo_token ) ) { ?>

			<div class="ultp-download-certificates">

				<div id="uo_download_certificates" class='learndash '>
					<div class="uo-ultp-grid-container uo-ultp-grid-container">
						<div class="uo-grid-wrapper">
							<?php
							$file_url = get_transient( $uo_token );
							if ( ! empty( $file_url ) ) {
								?>
								<div class="uo-certificates-success">
									<div class="ultp-download-certificates-download">
										<div class="ultp-download-certificates-download-text">
											<?php esc_html_e( 'Your certificate bundle is ready! Click the button below to download the certificate zip file.', 'uncanny-pro-toolkit' ); ?>
										</div>

										<a
											class="ultp-download-certificates-download-button"
											target="_blank"
											href="<?php echo esc_attr( get_home_url() ) . '/wp-content/uploads/bulk-certificates/' . esc_html( $file_url ); ?>"
										>
											<?php esc_html_e( 'Download', 'uncanny-pro-toolkit' ); ?>
										</a>
									</div>
								</div>
							<?php } else { ?>
								<div class="uo-certificates-error">
									<?php esc_html_e( 'Sorry, you do not have permission to download the certificate file or the download link has expired.', 'uncanny-pro-toolkit' ); ?>
								</div>
							<?php } ?>
						</div>
					</div>
				</div>

			</div>

			<?php

		} else {

			// Check if the user can request the certificates
			$allowed_roles = apply_filters( 'uo_bulk_downlad_cert_allowed_roles', array(
				'administrator',
				'group_leader'
			));

			$can_download_modules = false;
			foreach( $allowed_roles as $allowed_role ) {
				$can_download = in_array( $allowed_role, (array) $user->roles, true );
				if( $can_download ) {
					$can_download_modules = true;
					break;
				}
			}

			$can_download_modules = apply_filters( 'uo_download_certificates_user_can_download', $can_download_modules, $user );

			// Check if it has enough space
			$does_has_enough_space = self::uo_check_disk_space() >= 2;

			// Create error message
			$error_message = '';
			if ( ! $can_download_modules ) {
				$error_message = esc_html__( 'Sorry, you do not have permission to access this page.', 'uncanny-pro-toolkit' );
			} elseif ( ! $does_has_enough_space ) {
				$error_message = esc_html__( 'Certificates cannot be generated with less than 2 GB of free space available to your site. Please increase available storage and try again.', 'uncanny-pro-toolkit' );
			}

			?>

			<div class="ultp-download-certificates">

				<?php if ( ! empty( $error_message ) ) { ?>

					<div class="ultp-download-certificates-error">
						<?php echo $error_message; ?>
					</div>

				<?php } else { ?>

					<?php // Dynamic labels.
						$group_label  = \LearnDash_Custom_Label::get_label( 'group' );
						$course_label = \LearnDash_Custom_Label::get_label( 'course' );
						$quiz_label   = \LearnDash_Custom_Label::get_label( 'quiz' );

						// Add Allow clear previous runs checkbox
						$allow_clear = 'yes' === strtolower( $atts['allow-clear'] ) ? 'yes' : 'no';
						$allow_clear = apply_filters( 'uo_download_certificates_allow_clear', $allow_clear );
					?>

					<div id="uo_download_certificates">
						<div class="uo-grid-wrapper" id="uo_certificatesdropdown">
							<div class="ultp-download-certificates-filter uo-dropdowns-certificates"
								 id="ultp-download-certificates-filter-type">
								<label
									for="uo_certificatesdropdown_select"><?php esc_html_e( 'Certificate type', 'uncanny-pro-toolkit' ); ?></label><br/>

								<?php
								$valid_certificate_types = array( 'group', 'course', 'quiz' );
								$certificate_types       = apply_filters( 'uo_download_certificate_types',
									array(
										'group'  => sprintf( esc_html__( '%s certificates', 'uncanny-pro-toolkit' ), $group_label ),
										'course' => sprintf( esc_html__( '%s certificates', 'uncanny-pro-toolkit' ), $course_label ),
										'quiz'   => sprintf( esc_html__( '%s certificates', 'uncanny-pro-toolkit' ), $quiz_label ),
									)
								);
								?>
								<select id="uo_cert_type" name="uo_cert_type">
									<option
										value="-"><?php esc_html_e( 'Type of certificates to download', 'uncanny-pro-toolkit' ); ?></option>

									<?php
									if ( is_array( $certificate_types ) && ! empty( $certificate_types ) ) :
										foreach ( $certificate_types as $certificate_key => $certificate_type ) :
											// Only display valid certificate types.
											if ( ! in_array( $certificate_key, $valid_certificate_types, true ) ) {
												continue;
											}
											?>
											<option
												value="<?php echo esc_attr( $certificate_key ); ?>"><?php echo esc_html( $certificate_type ); ?></option>
										<?php endforeach; ?>
									<?php endif; ?>
								</select>
								<div class="ultp-download-certificates-dropdowns-error"></div>
							</div>

							<div
								class="ultp-download-certificates-filter ultp-download-certificates-filter--hidden uo-groups-dropdowns-certificates"
								id="ultp-download-certificates-filter-group">
								<label
									for="uo_certificatesdropdown_select"><?php echo $group_label; ?></label><br/>
								<select id="uo_groups" name="uo_groups">
									<option><?php echo sprintf( esc_html__( 'Select %s', 'uncanny-pro-toolkit' ), $group_label ); ?></option>
								</select>
								<div class="ultp-download-certificates-dropdowns-error"></div>
							</div>

							<div
								class="ultp-download-certificates-filter ultp-download-certificates-filter--hidden uo-courses-dropdowns-certificates"
								id="ultp-download-certificates-filter-course">
								<label
									for="uo_certificatesdropdown_select"><?php echo $course_label; ?></label><br/>
								<select id="uo_courses" name="uo_courses">
									<option><?php echo sprintf( esc_html__( 'Select %s', 'uncanny-pro-toolkit' ), $course_label ); ?></option>
								</select>
								<div class="ultp-download-certificates-dropdowns-error"></div>
							</div>

							<div
								class="ultp-download-certificates-filter ultp-download-certificates-filter--hidden uo-quizzes-dropdowns-certificates">
								<label
									for="uo_certificatesdropdown_select"><?php echo $quiz_label; ?></label><br/>
								<select id="uo_quizzes" name="uo_quizzes">
									<option><?php echo sprintf( esc_html__( 'Select %s', 'uncanny-pro-toolkit' ), $quiz_label ); ?></option>
								</select>
								<div class="ultp-download-certificates-dropdowns-error"></div>
							</div>

							<div
								class="ultp-download-certificates-filter ultp-download-certificates-filter--hidden uo-generate-certificates-btn">
								<input type="hidden" id="uo_current_user"
									   value="<?php echo esc_attr( get_current_user_id() ); ?>">
								<input type="hidden" id="val_uo_cert_type" value="">
								<input type="hidden" id="val_uo_group" value="">
								<input type="hidden" id="val_uo_course" value="">
								<input type="hidden" id="val_uo_quiz" value="">
								<input type="hidden" id="uo_download_url"
									   value="<?php echo get_the_permalink( get_the_ID() ); ?>">
								<button
									id="uo_generate_certificates_submit"><?php esc_html_e( 'Generate certificates', 'uncanny-pro-toolkit' ); ?></button>
								<?php 
								// Allow clear previous runs checkbox.
								if ( 'yes' === $allow_clear ) {
									?>
									<div class="ultp-download-certificates-clear">
										<label>
											<input type="checkbox" id="uo_clear_previous_runs" name="uo_clear_previous_runs" value="yes">
											<?php esc_html_e( 'Clear previous runs', 'uncanny-pro-toolkit' ); ?>
										</label>
									</div>
									<?php
								}
								?>
							</div>
						</div>
					</div>

				<?php } ?>

			</div>

			<?php
		}

		return ob_get_clean();
	}


	/**
	 * Method to check storage space.
	 *
	 * @return float|int
	 */
	public static function uo_check_disk_space() {
		$size = 2;
		// If function does not exist, assume and return 2 GB
		if ( ! function_exists( 'disk_free_space' ) ) {
			return apply_filters( 'uo_download_certificates_available_disk_space', $size ); // Function does not exist. Bail, return 0 space.
		}

		if ( function_exists( 'disk_free_space' ) ) {
			$size = disk_free_space( WP_CONTENT_DIR );
			if ( ! empty( $size ) ) {
				$size = round( $size / 1024 / 1024 / 1024, 4 );
			}
		}

		$size = empty( $size ) ? 2 : $size; // If function return empty or null, return 2 as default

		return apply_filters( 'uo_download_certificates_available_disk_space', $size ); // Function does not exist. Bail, return 0 space.
	}


	/**
	 * Method to set cron job to generate pdfs.
	 *
	 * @param $request
	 *
	 * @return \WP_REST_Response
	 */
	public static function uo_set_cron_job( $request ) {
		// check if its a valid request.
		$data = $request->get_params();

		if ( isset( $data['action'] ) && 'set_cron_job' === $data['action'] && self::uo_auth_data( $data ) ) {

			$type           = sanitize_text_field( $data['certificate_type'] );
			$group_id       = sanitize_text_field( $data['group_id'] );
			$user_id        = sanitize_text_field( $data['user_id'] );
			$course_id      = sanitize_text_field( $data['course'] );
			$quiz           = sanitize_text_field( $data['quiz'] );
			$download_url   = sanitize_url( $data['download_url'] );
			$clear_previous = sanitize_text_field( $data['clear_previous_runs'] );

			if ( 'yes' === $clear_previous ) {
				self::uo_clear_previous_cron_batches( 'bool' );
			}

			$users = array();

			if ( 'any' === $group_id ) {
				$users      = learndash_get_course_users_access_from_meta( absint( $course_id ) );
				$all_groups = self::ld_get_all_group_list( $user_id );
				foreach ( $all_groups as $group ) {
					$users = array_unique( array_merge( $users, learndash_get_groups_user_ids( $group ) ) );
				}
			} elseif ( 'all' === $group_id ) {
				$all_groups = self::ld_get_all_group_list( $user_id );
				foreach ( $all_groups as $group ) {
					$users = array_unique( array_merge( $users, learndash_get_groups_user_ids( $group ) ) );
				}
			} else {
				$users = learndash_get_groups_user_ids( $group_id );
			}

			if ( 'any' === $quiz ) {
				$users_list = learndash_get_course_users_access_from_meta( absint( $course_id ) );
				if ( is_array( $users ) && count( $users ) > 0 ) {
					$users = array_merge( $users, $users_list );
				}
			}

			$batch_id        = time() . '_' . $group_id . '_' . $user_id;
			$subject_replace = '';
			$body_replace    = '';
			$requested_time  = current_time( 'timestamp' );
			$pass_args       = array(
				'user_id'      => absint( $user_id ),
				'group_id'     => $group_id,
				'course_id'    => $course_id,
				'quiz_id'      => $quiz,
				'total'        => count( $users ),
				'all_users'    => $users,
				'batch'        => self::$batch,
				'type'         => $type,
				'download_url' => $download_url,
			);

			if ( 'group' === $type ) {
				if ( 0 !== absint( $group_id ) ) {
					$subject_replace = get_the_title( $group_id );
					$body_replace    = get_the_title( $group_id );
				} else {
					$subject_replace = _x( 'All groups', 'Download certificates', 'uncanny-pro-toolkit' );
					$body_replace    = _x( 'All groups', 'Download certificates', 'uncanny-pro-toolkit' );
				}
			}
			if ( 'course' === $type ) {
				$pass_args['course_id'] = absint( $course_id );
				$batch_id               = time() . '_' . $group_id . '_' . $course_id . '_' . $user_id;
				if ( 0 !== absint( $course_id ) ) {
					$subject_replace = get_the_title( $course_id );
					$body_replace    = get_the_title( $course_id );
				} else {
					$subject_replace = _x( 'All courses', 'Download certificates', 'uncanny-pro-toolkit' );
					$body_replace    = _x( 'All courses', 'Download certificates', 'uncanny-pro-toolkit' );
				}
			}

			if ( 'quiz' === $type ) {
				$course_id              = sanitize_text_field( $data['course'] );
				$pass_args['course_id'] = absint( $course_id );
				$quiz_id                = sanitize_text_field( $data['quiz'] );
				$pass_args['quiz_id']   = absint( $quiz_id );
				$batch_id               = time() . '_' . $group_id . '_' . $quiz_id . '_' . $user_id;
				if ( 0 !== absint( $quiz_id ) ) {
					$subject_replace = sprintf( '%s in %s', get_the_title( $quiz_id ), get_the_title( $course_id ) );
					$body_replace    = sprintf( '%s in %s', get_the_title( $quiz_id ), get_the_title( $course_id ) );
				} else {
					$subject_replace = sprintf( '%s in %s', _x( 'All quizzes', 'Download certificates', 'uncanny-pro-toolkit' ), get_the_title( $course_id ) );
					$body_replace    = sprintf( '%s in %s', _x( 'All quizzes', 'Download certificates', 'uncanny-pro-toolkit' ), get_the_title( $course_id ) );
				}
			}
			$email_vars = array(
				'subject_var' => $subject_replace,
				'body_var'    => $body_replace,
				'time'        => $requested_time,
			);
			$batch_id   = wp_create_nonce( $batch_id );
			update_option( "{$batch_id}_email_vars", $email_vars, false );
			$pass_args['batch_id'] = $batch_id;

			$data                = array( $batch_id => $pass_args );
			$batch_data_opt      = 'uo_gen_certificates_batch_data';
			$batch_data_opt_data = get_option( $batch_data_opt );
			if ( ! empty( $batch_data_opt_data ) ) {
				$existing_data = $batch_data_opt_data;
				if ( ! isset( $existing_data[ $batch_id ] ) ) {
					$dd = array_merge( $existing_data, $data );
					update_option( $batch_data_opt, $dd, false );
				}
			} else {
				update_option( $batch_data_opt, $data, false );
			}

			$batch_opt      = 'uo_cron_batch';
			$batch_opt_data = get_option( $batch_opt );
			if ( ! empty( $batch_opt_data ) ) {
				$existing_batch_opt = $batch_opt_data;
				if ( ! isset( $existing_batch_opt[ $batch_id ] ) ) {
					$dd = array_merge( $existing_batch_opt, array( $batch_id ) );
					update_option( $batch_opt, $dd, false );
				}
			} else {
				update_option( $batch_opt, array( $batch_id ), false );
			}

			$hook = 'uo_scheduled_generate_certificates_in_bulk';
			// First, we need to check if the schedule event is not already created, then we create it.
			if ( ! wp_get_schedule( $hook ) ) { // "scheduled_api_pulling"
				wp_schedule_event( time() + 60, 'every_60_seconds', $hook );
			}

			return new \WP_REST_Response(
				array(
					'success' => true,
					'data'    => 'cron-set-successfully.',
				),
				200
			);
		}

		return new \WP_REST_Response(
			array(
				'msg'     => __( 'Something went wrong please make your request again.', 'uncanny-pro-toolkit' ),
				'success' => false,
			),
			200
		);
	}


	/**
	 * Validating submitted data
	 */
	public static function uo_auth_data( $params = array() ) {

		if ( empty( $params ) ) {
			return false;
		}

		$cert_type = sanitize_text_field( $params['certificate_type'] );
		$group_id  = absint( sanitize_text_field( $params['group_id'] ) );
		$course_id = absint( sanitize_text_field( $params['course'] ) );
		$quiz_id   = absint( sanitize_text_field( $params['quiz'] ) );

		$val_cert_type = sanitize_text_field( $params['val_cert_type'] );
		$val_group_id  = absint( sanitize_text_field( $params['val_group_id'] ) );
		$val_course_id = absint( sanitize_text_field( $params['val_course'] ) );
		$val_quiz_id   = absint( sanitize_text_field( $params['val_quiz'] ) );

		if ( 'group' === $cert_type ) {
			if ( $cert_type === $val_cert_type && $group_id === $val_group_id ) {
				return true;
			}
		} elseif ( 'course' === $cert_type ) {
			if ( $cert_type === $val_cert_type && $group_id === $val_group_id && $course_id === $val_course_id ) {
				return true;
			}
		} elseif ( 'quiz' === $cert_type ) {
			if ( $cert_type === $val_cert_type && $group_id === $val_group_id && $course_id === $val_course_id && $quiz_id === $val_quiz_id ) {
				return true;
			}
		} else {
			return false;
		}

	}

	/**
	 * Method for cron execution.
	 *
	 * @return false|void
	 */
	public static function uo_cron_execution_method() {

		$options = get_option( 'uo_gen_certificates_batch_data', 0 );

		if ( 0 === absint( $options ) || empty( $options ) ) {
			wp_unschedule_hook( 'uo_scheduled_generate_certificates_in_bulk' );

			return;
		}

		$batch_id     = 0;
		$batch_option = get_option( 'uo_cron_batch' );
		if ( is_array( $batch_option ) ) {
			$batch_id = current( $batch_option );
		}

		$batch_data      = $options[ $batch_id ];
		$user_id         = ( isset( $batch_data['user_id'] ) ) ? absint( $batch_data['user_id'] ) : '';
		$group_leader_id = $user_id;
		$group_id        = ( isset( $batch_data['group_id'] ) ) ? absint( $batch_data['group_id'] ) : 0;
		$course_id       = ( isset( $batch_data['course_id'] ) ) ? absint( $batch_data['course_id'] ) : 0;
		$quiz_id         = ( isset( $batch_data['quiz_id'] ) && 0 !== $batch_data['quiz_id'] ) ? absint( $batch_data['quiz_id'] ) : 0;
		$all_users       = ( is_array( $batch_data['all_users'] ) ) ? $batch_data['all_users'] : '';
		$batch           = ( isset( $batch_data['batch'] ) ) ? absint( $batch_data['batch'] ) : self::$batch;
		$type            = ( isset( $batch_data['type'] ) ) ? sanitize_text_field( $batch_data['type'] ) : 'group_certificates';
		$download_url    = ( isset( $batch_data['download_url'] ) ) ? sanitize_url( $batch_data['download_url'] ) : '';
		$min_disk_space  = self::get_settings_value( 'uncanny-group-certificate-in-bulk-minimum-storage', __CLASS__ );
		if ( ! empty( $all_users ) && is_array( $all_users ) ) {

			$remaining_users = 0;
			$last_batch      = false;
			if ( $batch <= count( $all_users ) ) {
				$users           = array_slice( $all_users, 0, $batch );
				$remaining_users = array_slice( $all_users, $batch );
			} else {
				$users      = $all_users;
				$last_batch = true;
			}

			$all_groups = self::ld_get_all_group_list( $group_leader_id );

			foreach ( $users as $user ) {

				if ( absint( $min_disk_space ) >= self::uo_check_disk_space() ) {
					self::uo_send_email_not_enough_storage( $group_leader_id );

					return false;
				}

				if ( 'group' === $type ) {
					if ( 0 === $group_id ) {
						foreach ( $all_groups as $group ) {
							$group_progress = learndash_get_user_group_progress( $group, $user );
							if ( isset( $group_progress['percentage'] ) && 100 === absint( $group_progress['percentage'] ) ) {
								self::generate_group_certificate( $user, $group, $batch_id, $type );
							}
						}
					} else {
						$group_progress = learndash_get_user_group_progress( $group_id, $user );
						if ( isset( $group_progress['percentage'] ) && 100 === absint( $group_progress['percentage'] ) ) {
							self::generate_group_certificate( $user, $group_id, $batch_id, $type );
						}
					}
				} elseif ( 'course' === $type && learndash_course_completed( $user, $course_id ) ) {
					self::generate_group_certificate( $user, $course_id, $batch_id, $type );
				} elseif ( 'quiz' === $type ) {
					if ( 0 === absint( $quiz_id ) ) {
						$all_quizzes = self::uo_learndash_get_course_quizzes( $course_id );
						if ( ! empty( $all_quizzes ) ) {
							foreach ( $all_quizzes as $quiz ) {
								if ( true === learndash_is_quiz_complete( absint( $user ), absint( $quiz->ID ), $course_id ) ) {
									self::generate_group_certificate( absint( $user ), absint( $quiz->ID ), $batch_id, $type );
								}
							}
						}
					} else {
						if ( true === learndash_is_quiz_complete( absint( $user ), absint( $quiz_id ), $course_id ) ) {
							self::generate_group_certificate( absint( $user ), absint( $quiz_id ), $batch_id, $type );
						}
					}
				}
			}

			$new_options = $options;
			if ( false === $last_batch ) {
				if ( absint( $remaining_users ) > 0 ) {
					$pass_args                = array(
						'user_id'      => $user_id,
						'group_id'     => $group_id,
						'course_id'    => $course_id,
						'quiz_id'      => $quiz_id,
						'total'        => count( $all_users ),
						'all_users'    => $remaining_users,
						'batch'        => $batch,
						'batch_id'     => $batch_id,
						'type'         => $type,
						'download_url' => $download_url,
					);
					$new_options[ $batch_id ] = $pass_args;
				} else {
					unset( $new_options[ $batch_id ] );
				}
				update_option( 'uo_gen_certificates_batch_data', $new_options, false );
				wp_unschedule_hook( 'uo_scheduled_generate_certificates_in_bulk' );
				$next_run = strtotime( '+60 second' );
				wp_schedule_event( $next_run, 'every_60_seconds', 'uo_scheduled_generate_certificates_in_bulk' );
			} else {

				unset( $new_options[ $batch_id ] );
				update_option( 'uo_gen_certificates_batch_data', $new_options, false );

				$cron_options = get_option( 'uo_cron_batch' );
				$diff_options = array_diff( $cron_options, array( $batch_id ) );
				update_option( 'uo_cron_batch', $diff_options, false );

				$cert_folder = apply_filters( 'uo_download_certificates_in_bulk_folder', '/uploads/bulk-certificates' );
				$new_folder  = '/bulk_export_' . $type . '_' . date_i18n( 'Y-m-d' ) . '_' . $batch_id . '/';
				$new_file    = WP_CONTENT_DIR . $cert_folder . '/bulk_export_' . $type . '_' . date_i18n( 'Y-m-d' ) . '_' . $batch_id . '.zip';
				$folder_name = WP_CONTENT_DIR . $cert_folder . $new_folder;

				// Get real path for our folder
				$root_path = realpath( $folder_name );
				$user      = new \WP_User( $user_id );

				if ( ! empty( $root_path ) ) {
					self::create_zip_file( $root_path, $new_file, true );
				}
				$file_size = 0;
				if ( file_exists( $new_file ) ) {
					$file_size = filesize( $new_file ) / 1024;
				}
				if ( 0 === $file_size ) {
					self::uo_send_email_for_empty_files( $user, $batch_id );

					return false;
				}

				$file_delete_hook = 'uo_delete_zip_file';
				if ( ! wp_get_schedule( $file_delete_hook ) ) {
					$next_run = strtotime( '+' . self::uo_file_expiry_time() . ' second' );
					wp_schedule_single_event( $next_run, $file_delete_hook, array( 'file' => $new_file ) );
				}

				$file_token = self::uo_generate_token( 16 );
				set_transient( $file_token, 'bulk_export_' . $type . '_' . date_i18n( 'Y-m-d' ) . '_' . $batch_id . '.zip', self::uo_file_expiry_time() );
				self::uo_send_email( $user, $file_token, $group_id, $course_id, $quiz_id, $download_url, $batch_id );
			}
		}
	}

	/**
	 * @param $course_id
	 *
	 * @return int[]|void|\WP_Post[]
	 */
	public static function uo_learndash_get_course_quizzes( $course_id = 0 ) {
		$course_quiz = learndash_course_get_quizzes( $course_id, $course_id );
		$children    = learndash_get_course_steps( $course_id );
		if ( $children ) {
			foreach ( $children as $child ) {
				$step_quizzes = learndash_get_lesson_quiz_list( $child, null, $course_id );

				if ( $step_quizzes ) {
					foreach ( $step_quizzes as $step_quiz ) {
						$course_quiz[ $step_quiz['id'] ] = $step_quiz['post'];
					}
				}
			}
		}

		return $course_quiz;
	}

	/**
	 * Method to set send email.
	 *
	 * @param $user
	 * @param $batch_id
	 *
	 * @return void
	 */
	public static function uo_send_email_for_empty_files( $user, $batch_id ) {
		$batch_id     = str_replace( 'batchid_', '', $batch_id );
		$emails_vars  = get_option( "{$batch_id}_email_vars", array() );
		$email_params = array(
			'msg'     => '',
			'subject' => '',
			'email'   => '',
		);

		$subject_replace = isset( $emails_vars['subject_var'] ) ? $emails_vars['subject_var'] : '';
		$body_replace    = isset( $emails_vars['body_var'] ) ? $emails_vars['body_var'] : '';
		$requested_time  = isset( $emails_vars['time'] ) ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $emails_vars['time'] ) : '';

		$email_params['email'] = $user->user_email;
		$email_subject         = sprintf( _x( 'Your certificates for %s could not be generated', 'Bulk certificates not generated', 'uncanny-pro-toolkit' ), $subject_replace );
		$email_message         = __( 'Hi %User First Name%,<br /><br />The recent attempt to generate %Certificates for% certificates at %Time requested% resulted in no matches, so a zip file was not created.<br />Please change your certificate filters and try again.', 'uncanny-pro-toolkit' ); // phpcs:ignore

		$from_name  = self::get_settings_value( 'uncanny-group-certificate-in-bulk-from-name', __CLASS__ );
		$from_email = self::get_settings_value( 'uncanny-group-certificate-in-bulk-from-email', __CLASS__ );
		$from_name  = empty( $from_name ) ? get_bloginfo( 'name' ) : $from_name;
		$from_email = empty( $from_email ) ? get_bloginfo( 'admin_email' ) : $from_email;

		$headers = array( "From:{$from_name} <{$from_email}>" );

		$email_message = str_ireplace( '%User First Name%', html_entity_decode( $user->first_name ), $email_message );
		$email_message = str_ireplace( '%Certificates for%', $body_replace, $email_message );
		$email_message = str_ireplace( '%Time requested%', $requested_time, $email_message );

		$email_message .= "\n\n";

		$email_subject = str_ireplace( '%User First Name%', html_entity_decode( $user->first_name ), $email_subject );
		$email_subject = do_shortcode( stripslashes( $email_subject ) );

		$email_message = do_shortcode( stripslashes( $email_message ) );
		$email_message = wpautop( $email_message );

		$email_params['msg'] = $email_message;

		$email_params['subject'] = stripslashes( $email_subject );

		//Sending Email To User!
		$change_content_type = apply_filters( 'uo_apply_wp_mail_content_type', true );
		if ( $change_content_type ) {
			add_filter( 'wp_mail_content_type', array( __CLASS__, 'mail_content_type' ) );
		}

		wp_mail( $email_params['email'], $email_params['subject'], $email_params['msg'], $headers );
	}


	/**
	 * Method to set send email.
	 *
	 * @param $user
	 * @param $file_token
	 * @param int $group
	 * @param int $course
	 * @param int $quiz
	 * @param string $download_url
	 * @param string $batch_id
	 *
	 * @return void
	 */
	public static function uo_send_email( $user, $file_token, $group = 0, $course = 0, $quiz = 0, $download_url = '', $batch_id = '' ) {
		$batch_id    = str_replace( 'batchid_', '', $batch_id );
		$emails_vars = get_option( "{$batch_id}_email_vars", array() );

		$subject_replace = isset( $emails_vars['subject_var'] ) ? $emails_vars['subject_var'] : '';
		$body_replace    = isset( $emails_vars['body_var'] ) ? $emails_vars['body_var'] : '';
		$requested_time  = isset( $emails_vars['time'] ) ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $emails_vars['time'] ) : '';

		$email_params = array();

		$email_params['email'] = $user->user_email;
		$email_subject         = self::get_settings_value( 'uncanny-group-certificate-in-bulk-subject-line', __CLASS__ );
		$email_message         = self::get_settings_value( 'uncanny-group-certificate-in-bulk-email-body', __CLASS__ );

		$from_name  = self::get_settings_value( 'uncanny-group-certificate-in-bulk-from-name', __CLASS__ );
		$from_email = self::get_settings_value( 'uncanny-group-certificate-in-bulk-from-email', __CLASS__ );
		$from_name  = empty( $from_name ) ? get_bloginfo( 'name' ) : $from_name;
		$from_email = empty( $from_email ) ? get_bloginfo( 'admin_email' ) : $from_email;

		$headers = array( "From:{$from_name} <{$from_email}>" );

		if ( empty( $email_message ) ) {
			$email_message = 'Hi %User First Name%,<br /><br />
									The zip file that includes your requested certificates is now available for download.<br /><br />

									Requested details:<br />
									Certificates for: %Certificates for%<br />
									Time requested: %Time requested%<br />
									<br /><br />
									Click this link to retrieve the file: %Link%<br /><br />
									This link will expire in ' . date_i18n( 'H', self::uo_file_expiry_time() ) . ' hours.';
		}

		if ( empty( $email_subject ) ) {
			$email_subject = sprintf( _x( 'Your certificates for %s are ready for download!', 'Bulk certificates', 'uncanny-pro-toolkit' ), $subject_replace );
		}

		$email_message = str_ireplace( '%User First Name%', html_entity_decode( $user->first_name ), $email_message );
		$email_message = str_ireplace( '%Link%', '<a href="' . $download_url . '?uo-token=' . $file_token . '" target="_blank"> Click here to download </a>', $email_message );
		$email_message = str_ireplace( '%Certificates for%', $body_replace, $email_message );
		$email_message = str_ireplace( '%Time requested%', $requested_time, $email_message );

		$email_message .= "\n\n";

		$email_subject = str_ireplace( '%User First Name%', html_entity_decode( $user->first_name ), $email_subject );
		$email_subject = str_ireplace( '%User Email%', $user->user_email, $email_subject );
		$email_subject = do_shortcode( stripslashes( $email_subject ) );

		$email_message = do_shortcode( stripslashes( $email_message ) );
		$email_message = wpautop( $email_message );

		$email_params['msg'] = $email_message;

		$email_params['subject'] = stripslashes( $email_subject );

		//Sending Email To User!
		$change_content_type = apply_filters( 'uo_apply_wp_mail_content_type', true );
		if ( $change_content_type ) {
			add_filter( 'wp_mail_content_type', array( __CLASS__, 'mail_content_type' ) );
		}

		wp_mail( $email_params['email'], $email_params['subject'], $email_params['msg'], $headers );

	}

	/**
	 * Method to create zip file.
	 *
	 * @param $root_path
	 * @param $new_file
	 * @param $remove_files
	 *
	 * @return void
	 */
	public static function create_zip_file( $root_path, $new_file, $remove_files = false ) {

		if ( class_exists( 'ZipArchive' ) ) {

			if ( empty( $root_path ) ) {
				return;
			}

			// Initialize archive object
			$zip = new \ZipArchive();
			$zip->open( $new_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE );

			// Create recursive directory iterator
			/** Files info var @var \SplFileInfo[] $files */
			$files = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $root_path ),
				\RecursiveIteratorIterator::LEAVES_ONLY
			);

			foreach ( $files as $name => $file ) {
				// Skip directories (they would be added automatically)
				if ( ! $file->isDir() ) {
					// Get real and relative path for current file
					$file_path     = $file->getRealPath();
					$relative_path = substr( $file_path, strlen( $root_path ) + 1 );

					// Add current file to archive
					$zip->addFile( $file_path, $relative_path );
				}
			}

			// Zip archive will be created only after closing object
			$zip->close();

			if ( true === $remove_files ) {
				$it    = new RecursiveDirectoryIterator( $root_path, RecursiveDirectoryIterator::SKIP_DOTS );
				$files = new RecursiveIteratorIterator(
					$it,
					RecursiveIteratorIterator::CHILD_FIRST
				);

				foreach ( $files as $file ) {
					if ( $file->isDir() ) {
						rmdir( $file->getRealPath() );
					} else {
						unlink( $file->getRealPath() );
					}
				}
				rmdir( $root_path );
			}
		}
	}

	/**
	 * Method to send email to user for insufficient space.
	 *
	 * @param $user
	 *
	 * @return void
	 */
	public static function uo_send_email_not_enough_storage( $user ) {

		$user                  = new \WP_User( $user );
		$email_params['email'] = $user->user_email;
		$email_message         = __( 'Not enough storage space to generate certificates . ', 'uncanny-pro-toolkit' );

		$from_name  = self::get_settings_value( 'uncanny-group-certificate-in-bulk-from-name', __CLASS__ );
		$from_email = self::get_settings_value( 'uncanny-group-certificate-in-bulk-from-email', __CLASS__ );
		$from_name  = empty( $from_name ) ? get_bloginfo( 'name' ) : $from_name;
		$from_email = empty( $from_email ) ? get_bloginfo( 'admin_email' ) : $from_email;

		$headers = array( "From:{$from_name} <{$from_email}>" );

		$email_subject = __( 'Not enough space to generate certificates', 'uncanny-pro-toolkit' );

		$email_message = do_shortcode( stripslashes( $email_message ) );
		$email_message = wpautop( $email_message );

		$email_params['msg'] .= $email_message;

		$email_params['subject'] = stripslashes( $email_subject );

		//Sending Email To User!
		$change_content_type = apply_filters( 'uo_apply_wp_mail_content_type', true );
		if ( $change_content_type ) {
			add_filter( 'wp_mail_content_type', array( __CLASS__, 'mail_content_type' ) );
		}

		if ( wp_mail( $email_params['email'], $email_params['subject'], $email_params['msg'], $headers ) ) {
			delete_option( 'uo_cron_batch' );
			delete_option( 'uo_gen_certificates_batch_data' );
		}

	}

	/**
	 * Method to create zip file.
	 *
	 * @param $file
	 *
	 * @return bool|void
	 */
	public static function uo_delete_zip_file( $file ) {
		if ( ! empty( $file ) && file_exists( $file ) ) {
			return unlink( $file );
		}
	}

	/**
	 * Method to generate token.
	 *
	 * @param $length
	 *
	 * @return false|string
	 */
	public static function uo_generate_token( $length = 16 ) {
		return substr( str_shuffle( str_repeat( $x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil( $length / strlen( $x ) ) ) ), 1, $length ); // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found
	}

	/**
	 * Method to cleanup old certificates from the directory.
	 *
	 * @return void
	 */
	public static function uo_cleanup_certificates() {

		$cert_folder = apply_filters( 'uo_download_certificates_in_bulk_folder', '/uploads/bulk-certificates' );
		$folder_name = WP_CONTENT_DIR . $cert_folder;

		// Get real path for our folder
		$root_path = realpath( $folder_name );

		if ( ! is_dir( $folder_name ) ) {
			wp_send_json_error( array( 'msg' => __( 'No files found.', 'uncanny-pro-toolkit' ) ) );
		}

		$it    = new \RecursiveDirectoryIterator( $root_path, RecursiveDirectoryIterator::SKIP_DOTS );
		$files = new \RecursiveIteratorIterator(
			$it,
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $files as $file ) {
			if ( $file->isDir() ) {
				rmdir( $file->getRealPath() );
			} else {
				unlink( $file->getRealPath() );
			}
		}
		rmdir( $root_path );

		wp_send_json_success( array( 'msg' => __( 'Files removed successfully . ', 'uncanny-toolkit-pro' ) ) );
	}

	/**
	 * Clear previous cron batches.
	 * 
	 * @param string $return_type 
	 *
	 * @return mixed - JSON response or bool
	 */
	public static function uo_clear_previous_cron_batches( $return_type = 'json' ) {

		$has_batch   = get_option( 'uo_cron_batch', false );
		$has_data    = get_option( 'uo_gen_certificates_batch_data', false );
		$return_json = 'json' === $return_type;

		if ( ! $has_batch || ! $has_data ) {
			if ( $return_json ) {
				wp_send_json_error();
			} else {
				return false;
			}
		}

		delete_option( 'uo_cron_batch' );
		delete_option( 'uo_gen_certificates_batch_data' );
		if ( $return_json ) {
			wp_send_json_success();
		} else {
			return true;
		}
	}

	/**
	 * Add/Remove filters.
	 *
	 * @param string $add
	 *
	 * @return void
	 */
	private static function filters( $add, $user_id ) {
		if ( 'add' === $add ) {

			// Store the current use ID in case the action is performed by an admin.
			self::$current_user_id = $user_id;
			add_filter( 'learndash_shortcode_atts', array( __CLASS__, 'inject_shortcode_atts' ), 1, 2 );

			return;
		}

		remove_filter( 'learndash_shortcode_atts', array( __CLASS__, 'inject_shortcode_atts' ), 1 );
	}

	/**
	 * Inject missing shortcode attributes.
	 *
	 * @param array $shortcode_atts
	 * @param string $shortcode_slug
	 *
	 * @return array
	 */
	public static function inject_shortcode_atts( $shortcode_atts, $shortcode_slug ) {
		$shortcode_atts['user_id'] = self::$current_user_id;

		return $shortcode_atts;
	}
}
