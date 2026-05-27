<?php

namespace uncanny_ceu;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class AdminMenu
 *
 * @package uncanny_learndash_group_management
 */
class AdminPage {

	/**
	 * The name of the plugin
	 *     *
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private $root_path = 'ceu/v1';

	/**
	 * An array of localized and filtered strings that are used in templates
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array
	 */
	static $ceu_management_admin_page = array();

	/**
	 * @var
	 */
	static $licensing_class;

	/**
	 * class constructor
	 */
	public function __construct() {

		// Setup menu and page options in admin
		if ( is_admin() ) {

			// Create Plugin admin menus, pages, and sub-pages
			add_action( 'admin_menu', array( $this, 'create_admin_area' ), 30 );

			include_once Utilities::get_include( 'licensing.php' );
		}

		//register api class to save settings
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );

	}

	/**
	 * Create Plugin admin menus, pages, and sub-pages
	 *
	 * @since 1.0.0
	 */
	public function create_admin_area() {

		$page_title = esc_html__( 'Uncanny CEUs', 'uncanny-ceu' );
		$menu_title = esc_html__( 'Uncanny CEUs', 'uncanny-ceu' );
		$capability = 'manage_options';
		$menu_slug  = 'uncanny-ceu';
		$function   = array( $this, 'options_output' );

		// Create a link the main page when the menu expands
		add_submenu_page( 'uncanny-ceu-report', __( 'Settings', 'uncanny-ceu' ), __( 'Settings', 'uncanny-ceu' ), $capability, $menu_slug, $function );

		// Enqueue page specific styles
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

	}

	/**
	 * Create Theme Options page
	 *
	 * @since 1.0.0
	 */
	public function options_output() {

		$this->localize_filter_globalize_text();

		include Utilities::get_template( 'admin-settings.php' );

	}

	/**
	 * Filter and localize all text, then set as global for use in template file
	 *
	 * @since 1.0.0
	 */
	public function localize_filter_globalize_text() {

		// Page message set by rest call (localized during rest call)
		if ( isset( $_GET['message'] ) ) {
			self::$ceu_management_admin_page['text']['message'] = $_GET['message'];
		} else {
			self::$ceu_management_admin_page['text']['message'] = '';
		}

		// Credit Designation Label
		self::$ceu_management_admin_page['text']['credit_designation_label'] = __( 'Credit Designation Label', 'uncanny-ceu' );
		$credit_designation_label                                    = stripslashes( get_option( 'credit_designation_label', __( 'CEU', 'uncanny-ceu' ) ) );
		self::$ceu_management_admin_page['credit_designation_label'] = $credit_designation_label;

		// Credit Designation Label Plural
		self::$ceu_management_admin_page['text']['credit_designation_label_plural'] = __( 'Credit Designation Label Plural', 'uncanny-ceu' );
		$credit_designation_label_plural                                    = stripslashes( get_option( 'credit_designation_label_plural', __( 'CEUs', 'uncanny-ceu' ) ) );
		self::$ceu_management_admin_page['credit_designation_label_plural'] = $credit_designation_label_plural;

		// Credit Rollover Date
		self::$ceu_management_admin_page['text']['ceu_rollover_date'] = __( 'Credit Rollover Date (dd/mm)', 'uncanny-ceu' );
		$ceu_rollover_date                                    = get_option( 'ceu_rollover_date', '' );
		self::$ceu_management_admin_page['ceu_rollover_date'] = $ceu_rollover_date;

		self::$ceu_management_admin_page['text']['page_settings'] = __( 'General Settings', 'uncanny-ceu' );

		self::$ceu_management_admin_page['text']['email_reminders'] = __( 'Email Reminder Settings', 'uncanny-ceu' );

		self::$ceu_management_admin_page['text']['send_email_reminders'] = __( 'Send Email Reminders', 'uncanny-ceu' );
		$send_email_reminders = get_option( 'send_email_reminders', 'off' );
		if ( 'on' === $send_email_reminders ) {
			self::$ceu_management_admin_page['send_email_reminders'] = 'checked="checked"';
		} else {
			self::$ceu_management_admin_page['send_email_reminders'] = '';
		}

		self::$ceu_management_admin_page['text']['days_before_rollover_date_for_reminder'] = __( 'Days Before Rollover Date for Reminder', 'uncanny-ceu' );
		$days_before_rollover_date_for_reminder                                    = get_option( 'days_before_rollover_date_for_reminder', 0 );
		self::$ceu_management_admin_page['days_before_rollover_date_for_reminder'] = $days_before_rollover_date_for_reminder;

		self::$ceu_management_admin_page['text']['reminder_email_subject'] = __( 'Reminder Email Subject', 'uncanny-ceu' );
		$default_subject = __( 'You Have Not Earned Enough #ceu_plural', 'uncanny-ceu' );
		if ( empty( get_option( 'reminder_email_subject', '' ) ) ) {
			self::$ceu_management_admin_page['reminder_email_subject'] = $default_subject;
		} else {
			self::$ceu_management_admin_page['reminder_email_subject'] = stripslashes( get_option( 'reminder_email_subject', '' ) );
		}

		self::$ceu_management_admin_page['text']['reminder_email_body'] = __( 'Reminder Email Body', 'uncanny-ceu' );
		$default_body = __( "Hi #first_name,\r\nThis is a friendly reminder that you must earn #credits_required #ceu_plural before #rollover_date. You currently have #earned_since_rollover #ceu_plural.", 'uncanny-ceu' );
		if ( empty( get_option( 'reminder_email_body', '' ) ) ) {
			self::$ceu_management_admin_page['reminder_email_body'] = $default_body;
		} else {
			self::$ceu_management_admin_page['reminder_email_body'] = stripslashes( get_option( 'reminder_email_body', '' ) );
		}

		self::$ceu_management_admin_page['text']['save_changes'] = __( 'Save Changes', 'uncanny-ceu' );

		$default_subject = __( 'You earned a Certificate', 'uncanny-ceu' );
		if ( empty( get_option( 'uncanny-ceu-multiple-certs-subject', '' ) ) ) {
			self::$ceu_management_admin_page['uncanny-ceu-multiple-certs-subject'] = $default_subject;
		} else {
			self::$ceu_management_admin_page['uncanny-ceu-multiple-certs-subject'] = stripslashes( get_option( 'uncanny-ceu-multiple-certs-subject', '' ) );
		}

		$default_body = __( "Congratulations, you earned a certificate!\r\nYour certificate is attached to this email.", 'uncanny-ceu' );
		if ( empty( get_option( 'uncanny-ceu-multiple-certs-message', '' ) ) ) {
			self::$ceu_management_admin_page['uncanny-ceu-multiple-certs-message'] = $default_body;
		} else {
			self::$ceu_management_admin_page['uncanny-ceu-multiple-certs-message'] = stripslashes( get_option( 'uncanny-ceu-multiple-certs-message', '' ) );
		}

	}

	/**
	 * Load Scripts that are specific to the admin page
	 *
	 * @param string $hook Admin page being loaded
	 *
	 * @since 1.0
	 */
	public function admin_scripts( $hook ) {
		/*
		* Only load styles if the page hook contains the pages slug
		*
		* Hook can be either the toplevel_page_{page_slug} if its a parent  page OR
		* it can be {parent_slug}_pag_{page_slug} if it is a sub page.
		* Lets just check if our page slug is in the hook.
		*/

		// Target group management page
		if ( strpos( $hook, 'uncanny-ceu' ) !== false ) {
			wp_register_script(
				'ucec-admin',
				Utilities::get_asset( 'backend', 'bundle.min.js' ),
				array(
					'jquery',
					'jquery-ui-datepicker',
				),
				Utilities::get_version(),
				true
			);
			wp_register_style( 'ucec-admin', Utilities::get_asset( 'backend', 'bundle.min.css' ), array(), Utilities::get_version() );

			// API data
			$api_setup = array(
				'api'  => array(
					'root'  => esc_url_raw( rest_url() . 'ceu/v1' ),
					'nonce' => \wp_create_nonce( 'wp_rest' ),
				),
				'l10n' => Utilities::get_date_range_translations(),
			);

			wp_localize_script( 'ucec-admin', 'UO_CEUs', $api_setup );

			// Load wp date picker
			wp_enqueue_script( 'jquery-ui-datepicker' );

			// Load try-automator.js
			wp_enqueue_script( 'try-automator', plugin_dir_url( __DIR__ ) . '/install-automator/assets/js/try-automator.js', array( 'jquery' ), '1.0', true );

			// Loaded config.
			$api_setup = array(
				'root'  => esc_url_raw( rest_url() . 'uncanny_ceu/v1/' ),
				'nonce' => \wp_create_nonce( 'wp_rest' ),
			);

			wp_localize_script( 'try-automator', 'ulgmRestApiSetup', $api_setup );

			wp_enqueue_style( 'try-automator-css', plugin_dir_url( __DIR__ ) . '/install-automator/assets/css/try-automator.css', '1.0', false );
			//src/
			wp_enqueue_style( 'ucec-admin' );
			wp_enqueue_script( 'ucec-admin' );
		}
	}

	/**
	 * Rest API Custom Endpoints
	 *
	 * @since 1.0
	 */
	public function register_routes() {

		register_rest_route(
			$this->root_path,
			'/save_admin_settings/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_admin_settings' ),
				'permission_callback' => function () {
					if ( ! is_user_logged_in() ) {
						return new \WP_Error( 'ucec_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'uncanny-ceu' ), array( 'status' => rest_authorization_required_code() ) );
					}

					return current_user_can( 'manage_options' );

				},
			)
		);

		register_rest_route(
			$this->root_path,
			'/save_quiz_settings/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_quiz_settings' ),
				'permission_callback' => function () {
					if ( ! is_user_logged_in() ) {
						return new \WP_Error( 'ucec_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'uncanny-ceu' ), array( 'status' => rest_authorization_required_code() ) );
					}

					return current_user_can( 'manage_options' );

				},
			)
		);

		register_rest_route(
			$this->root_path,
			'/save_course_settings/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_course_settings' ),
				'permission_callback' => function () {
					if ( ! is_user_logged_in() ) {
						return new \WP_Error( 'ucec_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'uncanny-ceu' ), array( 'status' => rest_authorization_required_code() ) );
					}

					return current_user_can( 'manage_options' );

				},
			)
		);

	}

	/**
	 * Create new cron job for importing quiz records.
	 *
	 * @since 3.4.0
	 */
	public function save_course_settings() {

		// Actions permitted by the api call (collected from input element with name action )
		$permitted_actions = array(
			'save-ceu-setting',
			'populate-historical-credits',
		);

		// Was an action received, and is the actions allowed
		if ( isset( $_POST['action'] ) && in_array( $_POST['action'], $permitted_actions ) ) {

			$action = (string) $_POST['action'];
			if ( 'populate-historical-credits' === $action ) {

				if ( ! wp_next_scheduled( 'uo_ceu_archive_course' ) ) {
					wp_schedule_single_event( time() + 60, 'uo_ceu_archive_course' );
					$data['message'] = __( 'Course completions are scheduled to migrate in the background.', 'uncanny-ceu' );
					wp_send_json_success( $data );
				} else {
					$data['message'] = __( 'Course completions are already migrating.', 'uncanny-ceu' );
					wp_send_json_success( $data );
				}
			}
		} else {
			$data['message'] = __( 'Something went wrong! Please try again.', 'uncanny-ceu' );
			wp_send_json_error( $data );
		}

	}

	/**
	 * Create new cron job for importing quiz records.
	 *
	 * @since 3.4.0
	 */
	public function save_quiz_settings() {

		// Actions permitted by the api call (collected from input element with name action )
		$permitted_actions = array(
			'save-ceu-setting',
			'populate-historical-credits',
		);

		// Was an action received, and is the actions allowed
		if ( isset( $_POST['action'] ) && in_array( $_POST['action'], $permitted_actions ) ) {

			$action = (string) $_POST['action'];
			if ( 'populate-historical-credits' === $action ) {

				if ( ! wp_next_scheduled( 'uo_ceu_archive_quiz' ) ) {
					wp_schedule_single_event( time() + 60, 'uo_ceu_archive_quiz' );
					$data['message'] = __( 'Quiz results are scheduled to migrate in the background.', 'uncanny-ceu' );
					wp_send_json_success( $data );
				} else {
					$data['message'] = __( 'Quiz results are already migrating.', 'uncanny-ceu' );
					wp_send_json_success( $data );
				}
			}
		} else {
			$data['message'] = __( 'Something went wrong! Please try again.', 'uncanny-ceu' );
			wp_send_json_error( $data );
		}

	}

	/**
	 * Save email templates for user redemption email, user welcome email and
	 * group leader welcome email
	 *
	 * @since 1.0.0
	 */
	public function save_admin_settings() {

		// Actions permitted by the api call (collected from input element with name action )
		$permitted_actions = array(
			'save-ceu-setting',
			'populate-historical-credits',
		);

		// Was an action received, and is the actions allowed
		if ( isset( $_POST['action'] ) && in_array( $_POST['action'], $permitted_actions ) ) {

			$action = (string) $_POST['action'];

			if ( 'populate-historical-credits' === $action ) {

				$data = HistoricalCompletionsCredits::populate_historical_credits( absint( $_POST['courses'] ) );

				if ( false === $data['success'] ) {
					wp_send_json_error( array( 'message' => $data['message'] ) );
				}
				wp_send_json_success( $data );
			}
		} else {
			$action          = '';
			$data['message'] = __( 'Select an action.', 'uncanny-ceu' );
			wp_send_json_error( $data );
		}

		// Does the current user have permission
		$permission = apply_filters( 'save_ceu_setting_update_permission', 'manage_options' );
		if ( ! current_user_can( $permission ) ) {
			$data['message'] = __( 'You do not have permission to save settings.', 'uncanny-ceu' );
			wp_send_json_error( $data );
		}

		if ( isset( $_POST['credit_designation_label'] ) ) {
			update_option( 'credit_designation_label', $_POST['credit_designation_label'] );
		}

		if ( isset( $_POST['credit_designation_label_plural'] ) ) {
			update_option( 'credit_designation_label_plural', $_POST['credit_designation_label_plural'] );
		}

		if ( isset( $_POST['ceu_rollover_date'] ) ) {
			update_option( 'ceu_rollover_date', $_POST['ceu_rollover_date'] );
		}

		if ( isset( $_POST['send_email_reminders'] ) ) {
			update_option( 'send_email_reminders', $_POST['send_email_reminders'] );
		} else {
			update_option( 'send_email_reminders', 'off' );
		}

		if ( isset( $_POST['days_before_rollover_date_for_reminder'] ) ) {
			update_option( 'days_before_rollover_date_for_reminder', $_POST['days_before_rollover_date_for_reminder'] );
		}

		if ( isset( $_POST['reminder_email_subject'] ) ) {
			if ( '' === $_POST['reminder_email_subject'] ) {
				delete_option( 'reminder_email_subject' );
			} else {
				update_option( 'reminder_email_subject', $_POST['reminder_email_subject'] );
			}
		}

		if ( isset( $_POST['reminder_email_body'] ) ) {
			if ( '' === $_POST['reminder_email_subject'] ) {
				delete_option( 'reminder_email_body' );
			} else {
				update_option( 'reminder_email_body', $_POST['reminder_email_body'] );
			}
		}

		// Show avatar
		if ( ! isset( $_POST['ceu_show_avatar'] ) ) {
			delete_option( 'ceu_show_avatar' );
		} else {
			update_option( 'ceu_show_avatar', $_POST['ceu_show_avatar'] );
		}

		if ( isset( $_POST['ceu_report_mode'] ) ) {
			if ( '' === $_POST['ceu_report_mode'] ) {
				delete_option( 'ceu_report_mode' );
			} else {
				update_option( 'ceu_report_mode', $_POST['ceu_report_mode'] );
				self::setup_report_mode( $_POST['ceu_report_mode'] );
			}
		}

		if ( isset( $_POST['ceu_default_date_range'] ) ) {
			if ( '' === $_POST['ceu_default_date_range'] ) {
				delete_option( 'ceu_default_date_range' );
			} else {
				update_option( 'ceu_default_date_range', $_POST['ceu_default_date_range'] );
			}
		}

		if ( isset( $_POST['ceu_default_page_length'] ) ) {
			if ( '' === $_POST['ceu_default_page_length'] ) {
				delete_option( 'ceu_default_page_length' );
			} else {
				update_option( 'ceu_default_page_length', $_POST['ceu_default_page_length'] );
			}
		}
		
		$k                      = 'ceu_credit_report_include_group_children';
		$include_group_children = isset( $_POST[ $k ] ) ? $_POST[ $k ] : '';
		if ( ! empty( $include_group_children ) && CourseReportAPI::is_groups_hierarchical_enabled() ) {
			update_option( $k, 'on' );
		} else {
			delete_option( $k );
		}

		$data['message'] = __( 'Settings have been saved.', 'uncanny-ceu' );
		wp_send_json_success( $data );
	}

	/**
	 * @param $mode
	 *
	 * @return void
	 */
	public static function setup_report_mode( $mode ) {

		// It's the latest mode, make sure that the data is migrated!
		if ( 'latest' === $mode ) {
			wp_schedule_single_event( time() + 7, 'migrate_ceu_records_from_usermeta' );

			return;
		}

		if ( 'json' === $mode ) {
			if ( ! wp_get_schedule( 'generate_ceu_records_hourly' ) ) {
				wp_schedule_event( time() + 5, 'hourly', 'generate_ceu_records_hourly' );
			}
		}
	}
}
