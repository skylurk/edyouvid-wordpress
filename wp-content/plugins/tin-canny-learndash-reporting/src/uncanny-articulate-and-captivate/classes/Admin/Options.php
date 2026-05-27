<?php
/**
 * Admin Options Controller
 *
 * @package    Tin Canny Reporting for LearnDash
 * @subpackage Embed Articulate Storyline and Adobe Captivate
 * @author     Uncanny Owl
 * @since      1.0.0
 */

namespace TINCANNYSNC\Admin;

if ( ! defined( 'UO_ABS_PATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

class Options {

	private static $DEFAULT_OPTION
		= array(
			'default-lightbox-style'          => 'colorbox',
			'colorbox-transition'             => 'elastic',
			'colorbox-theme'                  => 'default',
			'nivo-transition'                 => 'fade',
			'height'                          => 90,
			'height_type'                     => 'vh',
			'width'                           => 90,
			'width_type'                      => 'vw',
			'tinCanActivation'                => '1',
			'disableMarkComplete'             => '1',
			'labelMarkComplete'               => '',
			'nonceProtection'                 => '1',
			'disableDashWidget'               => '0',
			'disablePerformanceEnhancments'   => '0',
			'userIdentifierDisplayName'       => '1',
			'userIdentifierFirstName'         => '0',
			'userIdentifierLastName'          => '0',
			'userIdentifierUsername'          => '0',
			'userIdentifierEmail'             => '1',
			'enableTinCanReportFrontEnd'      => '0',
			'enablexapiReportFrontEnd'        => '0',
			'autocompleLessonsTopicsTincanny' => '0',
			'methodMarkCompleteForTincan'     => '0',
			'tinCanScormDriverCompatibility'  => 'async',
		);

	public static $OPTION = array();

	/**
	 * Initialize
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 30 );
	}

	/**
	 * Return Options
	 *
	 * @access  public static
	 * @return  void
	 * @since   1.0.0
	 */
	public static function get_options() {
		self::$OPTION = get_option( SnC_TEXTDOMAIN );

		if ( ! self::$OPTION ) {
			// Set Default Option
			self::$OPTION = self::$DEFAULT_OPTION;
			update_option( SnC_TEXTDOMAIN, self::$DEFAULT_OPTION );
		}
		self::$OPTION = shortcode_atts( self::$DEFAULT_OPTION, self::$OPTION );

		return self::$OPTION;
	}

	/**
	 * Get Option with default
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public static function get_option( $key, $default = '' ) {
		$options = self::get_options();
		return isset( $options[ $key ] ) ? $options[ $key ] : $default;
	}

	/**
	 * Register Admin Menu
	 *
	 * @trigger admin_menu Action
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function register_admin_menu() {
		add_submenu_page(
			'uncanny-learnDash-reporting',
			'Settings',
			'Settings',
			'manage_options',
			'snc_options',
			array(
				$this,
				'view_options_page',
			)
		);
	}

	/**
	 * Render page from admin_menu
	 *
	 * @trigger add_options_page
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function view_options_page() {
		$security = ultc_get_filter_var( 'security', false, INPUT_POST );
		if ( $security && wp_verify_nonce( $security, 'snc-options' ) ) {

			unset( $_POST['security'] );
			unset( $_POST['submit'] );

			// set unchecked checkboxes
			( ! isset( $_POST['userIdentifierDisplayName'] ) ) ? $_POST['userIdentifierDisplayName'] = '0' : null;
			( ! isset( $_POST['userIdentifierFirstName'] ) ) ? $_POST['userIdentifierFirstName']     = '0' : null;
			( ! isset( $_POST['userIdentifierLastName'] ) ) ? $_POST['userIdentifierLastName']       = '0' : null;
			( ! isset( $_POST['userIdentifierUsername'] ) ) ? $_POST['userIdentifierUsername']       = '0' : null;
			( ! isset( $_POST['userIdentifierEmail'] ) ) ? $_POST['userIdentifierEmail']             = '0' : null;

			// Set default mark complete button options when capture data is off.
			$show_tincan_tables = absint( ultc_get_filter_var( 'tinCanActivation', 0, INPUT_POST ) );
			if ( 0 === $show_tincan_tables ) {
				$_POST['disableMarkComplete']             = '0';
				$_POST['autocompleLessonsTopicsTincanny'] = '1';
			}

			// Loop through each $_POST field and sanitize
			$sanitized_post_data = array();
			foreach ($_POST as $key => $value) {
			    if (is_array($value)) {
			        $sanitized_post_data[$key] = array_map('sanitize_text_field', $value);
			    } else {
			        $sanitized_post_data[$key] = sanitize_text_field($value);
			    }
			}

			self::$OPTION = $sanitized_post_data;

			update_option( SnC_TEXTDOMAIN, self::$OPTION );

			/////////////
			// Store data for other uses
			////////////

			// Show TinCan Tables
			$show_tincan_tables_value = 1 === $show_tincan_tables ? 'yes' : 'no';
			if ( current_user_can( 'manage_options' ) ) {
				update_option( 'show_tincan_reporting_tables', $show_tincan_tables_value );
			}

			// Disable mark complete
			$disable_mark_complete = absint( ultc_get_filter_var( 'disableMarkComplete', 0, INPUT_POST ) );
			if ( 1 === $disable_mark_complete ) {
				$disable_mark_complete_value = 'yes';
			}
			if ( 0 === $disable_mark_complete ) {
				$disable_mark_complete_value = 'no';
			}
			if ( 3 === $disable_mark_complete ) {
				$disable_mark_complete_value = 'hide';
			}
			if ( 4 === $disable_mark_complete ) {
				$disable_mark_complete_value = 'remove';
			}
			if ( 5 === $disable_mark_complete ) {
				$disable_mark_complete_value = 'autoadvance';
			}

			if ( current_user_can( 'manage_options' ) ) {
				update_option( 'disable_mark_complete_for_tincan', $disable_mark_complete_value );
			}

			// Disable mark complete
			$label_mark_complete = trim( ultc_get_filter_var( 'labelMarkComplete', '', INPUT_POST ) );
			if ( current_user_can( 'manage_options' ) ) {
				update_option( 'label_mark_complete_for_tincan', $label_mark_complete );
			}

			// Enable nonce protection
			$nonce_protection = absint( ultc_get_filter_var( 'nonceProtection', 0, INPUT_POST ) ) ? 'yes' : 'no';
			if ( current_user_can( 'manage_options' ) ) {
				update_option( 'tincanny_nonce_protection', $nonce_protection );

				if ( 'yes' === $nonce_protection ) {
					// Check if the user chose to protect the content.
					\uncanny_learndash_reporting\Boot::create_protection_htaccess();
				} else {
					// Check if the user chose not to protect the content.
					\uncanny_learndash_reporting\Boot::delete_protection_htaccess();
				}
			}

			// Autocomplete Lessons and Topics even if Tin Canny content on page (Uncanny Toolkit Pro)
			$autocomple_lessons_topics_tincanny = absint( ultc_get_filter_var( 'autocompleLessonsTopicsTincanny', 0, INPUT_POST ) ) ? 'yes' : 'no';
			if ( current_user_can( 'manage_options' ) ) {
				update_option( 'autocomple_lessons_topics_tincanny', $autocomple_lessons_topics_tincanny );
			}

			///////////////////////////////
			// Disable admin dashboard
			$disable_dash_widget = absint( ultc_get_filter_var( 'disableDashWidget', 0, INPUT_POST ) ) ? 'yes' : 'no';
			if ( current_user_can( 'manage_options' ) ) {
				update_option( 'tincanny_disableDashWidget', $disable_dash_widget );
			}

			///////////////////////////////
			// Disable performance enhancments
			$disable_performance_enhancments = absint( ultc_get_filter_var( 'disablePerformanceEnhancments', 0, INPUT_POST ) ) ? 'yes' : 'no';
			if ( current_user_can( 'manage_options' ) ) {
				update_option( 'tincanny_disablePerformanceEnhancments', $disable_performance_enhancments );
			}

			///////////////////////////////
			// Disable/enable Front-end reports
			$enable_frontend_report = absint( ultc_get_filter_var( 'enableTinCanReportFrontEnd', 0, INPUT_POST ) ) ? 'yes' : 'no';
			if ( current_user_can( 'manage_options' ) ) {
				update_option( 'tincanny_enableTinCanReportFrontEnd', $enable_frontend_report );
			}

			///////////////////////////////
			// Disable/enable Front-end xAPI reports
			$enable_frontend_xapi = absint( ultc_get_filter_var( 'enablexapiReportFrontEnd', 0, INPUT_POST ) ) ? 'yes' : 'no';
			if ( current_user_can( 'manage_options' ) ) {
				update_option( 'tincanny_enablexapiReportFrontEnd', $enable_frontend_xapi );
			}

			///////////////////////////////
			// Select which user identifier(s) are shown in reports
			if ( current_user_can( 'manage_options' ) ) {
				$user_display_name = absint( ultc_get_filter_var( 'userIdentifierDisplayName', 0, INPUT_POST ) ) ? 'yes' : 'no';
				update_option( 'tincanny_userIdentifierDisplayName', $user_display_name );

				$user_first_name = absint( ultc_get_filter_var( 'userIdentifierFirstName', 0, INPUT_POST ) ) ? 'yes' : 'no';
				update_option( 'tincanny_userIdentifierFirstName', $user_first_name );

				$user_last_name = absint( ultc_get_filter_var( 'userIdentifierLastName', 0, INPUT_POST ) ) ? 'yes' : 'no';
				update_option( 'tincanny_userIdentifierLastName', $user_last_name );

				$user_name = absint( ultc_get_filter_var( 'userIdentifierUsername', 0, INPUT_POST ) ) ? 'yes' : 'no';
				update_option( 'tincanny_userIdentifierUsername', $user_name );

				$user_email = absint( ultc_get_filter_var( 'userIdentifierEmail', 0, INPUT_POST ) ) ? 'yes' : 'no';
				update_option( 'tincanny_userIdentifierEmail', $user_email );
			}

			///////////////////////////////
			// Enable compatibility mode
			$mark_complete_method = absint( ultc_get_filter_var( 'methodMarkCompleteForTincan', 0, INPUT_POST ) ) ? 'old' : 'new';
			if ( current_user_can( 'manage_options' ) ) {
				update_option( 'method_mark_complete_for_tincan', $mark_complete_method );
			}

			// Report Mode
			update_option( 'tincanny_user_report_report_mode', ultc_get_filter_var( 'tincanny_user_report_report_mode', 'cached', INPUT_POST ) );
			// Report Mode
			update_option( 'tincanny_user_report_default_group', ultc_get_filter_var( 'tincanny_user_report_default_group', 'all', INPUT_POST ) );
			// Report Mode
			update_option( 'tincanny_user_report_default_page_length', ultc_get_filter_var( 'tincanny_user_report_default_page_length', 50, INPUT_POST ) );

			// Delete Tin Canny Data on Uninstall
			$delete_data_on_uninstall = absint( ultc_get_filter_var( 'delete_tincanny_data', 0, INPUT_POST ) ) ? 'yes' : 'no';
			if ( current_user_can( 'manage_options' ) ) {
				update_option( 'tincanny_delete_data_on_uninstall', $delete_data_on_uninstall );
			}
		} else {
			self::$OPTION = self::get_options();
		}

		$glightbox_transitions = \TINCANNYSNC\Shortcode::$glightbox_transitions;
		
		$statement_verbs = \UCTINCAN\Database::get_unique_verbs();

		include_once SnC_PLUGIN_DIR . 'views/admin_options.php';
	}
}
