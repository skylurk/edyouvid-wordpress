<?php

namespace uncanny_pro_toolkit;

use uncanny_ceu\Utilities;
use uncanny_learndash_toolkit as toolkit;
use WP_User;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Sample
 *
 * @package uncanny_pro_toolkit
 */
class EnhancedLearndashReport extends toolkit\Config implements toolkit\RequiredFunctions {

	private static $all_assignments = array();

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action(
			'plugins_loaded',
			array(
				__CLASS__,
				'run_frontend_hooks',
			)
		);
	}

	public static function get_assignments_details( $author_id, $course_id ) {

		$args = array(
			'post_type'      => 'sfwd-assignment',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'author'         => $author_id,
			'order'          => 'ASC',
			'orderby'        => 'date',
			'fields'         => 'ids',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => 'course_id',
					'value'   => $course_id,
					'compare' => '=',
				),
				array(
					'key'     => 'user_id',
					'value'   => $author_id,
					'compare' => '=',
				),
			),
		);

		return get_posts( $args );
	}

	/**
	 * Initialize frontend actions and filters
	 */
	public static function run_frontend_hooks() {

		if ( true === self::dependants_exist() ) {

			// Filter Report Headers which sets up the data values
			add_filter(
				'learndash_data_reports_headers',
				array(
					__CLASS__,
					'uo_learndash_export_upgraded_headers_filter',
				),
				10,
				2
			);

			// Validate meta key inputs
			add_action(
				'toolkit_settings_save_validation',
				array(
					__CLASS__,
					'uo_learndash_export_settings_validation',
				),
				10,
				2
			);

		}

	}

	/**
	 * Does the plugin rely on another function or plugin
	 *
	 * @return boolean || string Return either true or name of function or
	 *     plugin
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
		$module_id = 'enhanced-learnDash-csv-reports';

		$class_title = esc_html__( 'Enhanced LearnDash CSV Reports', 'uncanny-pro-toolkit' );

		$kb_link = 'http://www.uncannyowl.com/knowledge-base/enhanced-learnDash-reports/';

		/* Sample Simple Description with shortcode */
		$class_description = esc_html__( 'This module will add additional columns to the LearnDash csv reports (course and quiz).', 'uncanny-pro-toolkit' );

		/* Icon as fontawesome icon */
		$class_icon = '<i class="uo_icon_pro_fa uo_icon_fa fa fa-table "></i><span class="uo_pro_text">PRO</span>';
		$category   = 'learndash';
		$type       = 'pro';

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

		// Create options
		$options = array();

		$options[] = array(
			'type'       => 'html',
			'inner_html' => '<h2>' . esc_attr__( 'Select Columns', 'uncanny-pro-toolkit' ) . '</h2>',
		);

		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Username', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-username',
		);

		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'First Name', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-firstname',
		);

		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Last Name', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-lastname',
		);

		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Nickname', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-nickname',
		);

		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Display Name', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-displayname',
		);

		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Role(s) (comma separated list of readable role names)', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-roles',
		);

		$activated_plugins = get_option( 'active_plugins' );
		$plugin            = 'uncanny-continuing-education-credits/uncanny-continuing-education-credits.php';

		if ( in_array( $plugin, $activated_plugins ) ) {
			$options[] = array(
				'type'        => 'checkbox',
				'label'       => esc_html__( 'Uncanny CEUs', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-learndash-ceus',
			);
		}

		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Group(s) (comma separate list of readable group names)', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-groups',
		);

		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Course Start Date', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-course-start-date',
		);

		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Language', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-language',
		);

		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Website', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-website',
		);

		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Biographical info', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-biographicalinfo',
		);

		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Usermeta Key 1', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-usermeta-1',
		);

		$options[] = array(
			'type'        => 'text',
			'label'       => esc_attr__( '', 'uncanny-pro-toolkit' ),
			'placeholder' => esc_attr__( 'Usermeta Key 1', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-usermetakey-1',
		);

		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Usermeta Key 2', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-usermeta-2',
		);

		$options[] = array(
			'type'        => 'text',
			'label'       => esc_attr__( '', 'uncanny-pro-toolkit' ),
			'placeholder' => esc_attr__( 'Usermeta Key 2', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-usermetakey-2',
		);

		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Usermeta Key 3', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-usermeta-3',
		);

		$options[] = array(
			'type'        => 'text',
			'label'       => esc_attr__( '', 'uncanny-pro-toolkit' ),
			'placeholder' => esc_attr__( 'Usermeta Key 3', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-usermetakey-3',
		);

		// Field meta key 4 start.
		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Usermeta Key 4', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-usermeta-4',
		);

		$options[] = array(
			'type'        => 'text',
			'label'       => esc_attr__( '', 'uncanny-pro-toolkit' ),
			'placeholder' => esc_attr__( 'Usermeta Key 4', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-usermetakey-4',
		);
		// Field meta key 4 end.

		// Field meta key 5 start.
		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Usermeta Key 5', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-usermeta-5',
		);

		$options[] = array(
			'type'        => 'text',
			'label'       => esc_attr__( '', 'uncanny-pro-toolkit' ),
			'placeholder' => esc_attr__( 'Usermeta Key 5', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-usermetakey-5',
		);
		// Field meta key 5 end.

		// Field meta key 6 start.
		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Usermeta Key 6', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-usermeta-6',
		);

		$options[] = array(
			'type'        => 'text',
			'label'       => esc_attr__( '', 'uncanny-pro-toolkit' ),
			'placeholder' => esc_attr__( 'Usermeta Key 6', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-usermetakey-6',
		);
		// Field meta key 6 end.

		// Field meta key 7 start.
		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Usermeta Key 7', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-usermeta-7',
		);

		$options[] = array(
			'type'        => 'text',
			'label'       => esc_attr__( '', 'uncanny-pro-toolkit' ),
			'placeholder' => esc_attr__( 'Usermeta Key 7', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-usermetakey-7',
		);
		// Field meta key 7 end.

		// Field meta key 8 start.
		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Usermeta Key 8', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-usermeta-8',
		);

		$options[] = array(
			'type'        => 'text',
			'label'       => esc_attr__( '', 'uncanny-pro-toolkit' ),
			'placeholder' => esc_attr__( 'Usermeta Key 8', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-usermetakey-8',
		);
		// Field meta key 8 end.

		// Field meta key 9 start.
		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Usermeta Key 9', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-usermeta-9',
		);

		$options[] = array(
			'type'        => 'text',
			'label'       => esc_attr__( '', 'uncanny-pro-toolkit' ),
			'placeholder' => esc_attr__( 'Usermeta Key 9', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-usermetakey-9',
		);
		// Field meta key 9 end.

		// Field meta key 10 start.
		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Usermeta Key 10', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-usermeta-10',
		);

		$options[] = array(
			'type'        => 'text',
			'label'       => esc_attr__( '', 'uncanny-pro-toolkit' ),
			'placeholder' => esc_attr__( 'Usermeta Key 10', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-usermetakey-10',
		);
		// Field meta key 10 end.

		// Field Course meta key 1 start.
		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Course Meta Key 1', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-coursemeta-1',
		);

		$options[] = array(
			'type'        => 'text',
			'label'       => esc_attr__( '', 'uncanny-pro-toolkit' ),
			'placeholder' => esc_attr__( 'Course Meta Key 1', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-coursemetakey-1',
		);
		// Field Course meta key 1 end.

		// Field Course meta key 2 start.
		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Course Meta Key 2', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-coursemeta-2',
		);

		$options[] = array(
			'type'        => 'text',
			'label'       => esc_attr__( '', 'uncanny-pro-toolkit' ),
			'placeholder' => esc_attr__( 'Course Meta Key 2', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-coursemetakey-2',
		);
		// Field Course meta key 2 end.

		// Field Course meta key 3 start.
		$options[] = array(
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Course Meta Key 3', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-coursemeta-3',
		);

		$options[] = array(
			'type'        => 'text',
			'label'       => esc_attr__( '', 'uncanny-pro-toolkit' ),
			'placeholder' => esc_attr__( 'Course Meta Key 3', 'uncanny-pro-toolkit' ),
			'option_name' => 'uncanny-learndash-report-coursemetakey-3',
		);
		// Field Course meta key 3 end.

		$total_columns = apply_filters( 'uo_assignments_columns', 3 );
		for ( $loop = 1; $loop <= $total_columns; $loop ++ ) {
			$options[] = array(
				'type'        => 'checkbox',
				'label'       => sprintf( esc_html__( 'Assignment info #%d', 'uncanny-pro-toolkit' ), $loop ),
				'option_name' => 'uncanny-learndash-report-assignmentsinfo-' . $loop,
			);
		}

		// Build html
		$html = self::settings_output(
			array(
				'class'   => __CLASS__,
				'title'   => $class_title,
				'options' => $options,
			)
		);

		return $html;
	}

	/**
	 * Filter Headers Before LearnDash CVS is loaded
	 *
	 * The header filter does to things. It creates the CSV heading and defined
	 * the function that will return the value of the column row.
	 *
	 * @param array $data_headers column definitions
	 * @param string $data_slug The report type
	 *
	 * @return array $data_headers
	 */
	public static function uo_learndash_export_upgraded_headers_filter( $data_headers, $data_slug ) {

		if ( 'on' === self::get_settings_value( 'uncanny-learndash-ceus', __CLASS__ ) ) {
			if ( ! isset( $data_headers['uncanny_ceus'] ) ) {
				$data_headers['uncanny_ceus'] = array(
					'label'   => get_option( 'credit_designation_label_plural', esc_attr__( 'CEUs', 'uncanny-ceu' ) ),
					'default' => '',
					'display' => array( __CLASS__, 'uo_report_column' ),
				);
			}
		}

		if ( 'on' === self::get_settings_value( 'uncanny-learndash-report-username', __CLASS__ ) ) {
			if ( ! isset( $data_headers['username'] ) ) {
				$data_headers['username'] = array(
					'label'   => 'Username',
					'default' => '',
					'display' => array( __CLASS__, 'uo_report_column' ),
				);
			}
		}

		if ( 'on' === self::get_settings_value( 'uncanny-learndash-report-firstname', __CLASS__ ) ) {
			// Add First Name
			if ( ! isset( $data_headers['first_name'] ) ) {
				$data_headers['first_name'] = array(
					'label'   => 'First Name',
					'default' => '',
					'display' => array( __CLASS__, 'uo_report_column' ),
				);
			}
		}

		if ( 'on' === self::get_settings_value( 'uncanny-learndash-report-lastname', __CLASS__ ) ) {
			// Add Last Name
			if ( ! isset( $data_headers['last_name'] ) ) {
				$data_headers['last_name'] = array(
					'label'   => 'Last Name',
					'default' => '',
					'display' => array( __CLASS__, 'uo_report_column' ),
				);
			}
		}

		if ( 'on' === self::get_settings_value( 'uncanny-learndash-report-nickname', __CLASS__ ) ) {
			// Add Nick Name
			if ( ! isset( $data_headers['nick_name'] ) ) {
				$data_headers['nick_name'] = array(
					'label'   => 'Nick Name',
					'default' => '',
					'display' => array( __CLASS__, 'uo_report_column' ),
				);
			}
		}

		if ( 'on' === self::get_settings_value( 'uncanny-learndash-report-displayname', __CLASS__ ) ) {
			// Add Display Name
			if ( ! isset( $data_headers['display_name'] ) ) {
				$data_headers['display_name'] = array(
					'label'   => 'Display Name',
					'default' => '',
					'display' => array( __CLASS__, 'uo_report_column' ),
				);
			}
		}

		if ( 'on' === self::get_settings_value( 'uncanny-learndash-report-roles', __CLASS__ ) ) {
			// Add Roles
			if ( ! isset( $data_headers['roles'] ) ) {
				$data_headers['roles'] = array(
					'label'   => 'Role(s)',
					'default' => '',
					'display' => array( __CLASS__, 'uo_report_column' ),
				);
			}
		}

		if ( 'on' === self::get_settings_value( 'uncanny-learndash-report-groups', __CLASS__ ) ) {
			// Add Groups
			if ( ! isset( $data_headers['groups'] ) ) {
				$data_headers['groups'] = array(
					'label'   => 'Group(s)',
					'default' => '',
					'display' => array( __CLASS__, 'uo_report_column' ),
				);
			}
		}
		if ( 'on' === self::get_settings_value( 'uncanny-learndash-report-course-start-date', __CLASS__ ) ) {
			// Add Course Start Date
			if ( ! isset( $data_headers['course_start_date'] ) ) {
				$data_headers['course_start_date'] = array(
					'label'   => 'Course Start Date',
					'default' => '',
					'display' => array( __CLASS__, 'uo_report_column' ),
				);
			}
		}

		if ( 'on' === self::get_settings_value( 'uncanny-learndash-report-language', __CLASS__ ) ) {
			// Add Language
			if ( ! isset( $data_headers['language'] ) ) {
				$data_headers['language'] = array(
					'label'   => 'Language',
					'default' => '',
					'display' => array( __CLASS__, 'uo_report_column' ),
				);
			}
		}

		if ( 'on' === self::get_settings_value( 'uncanny-learndash-report-website', __CLASS__ ) ) {
			// Add Website
			if ( ! isset( $data_headers['website'] ) ) {
				$data_headers['website'] = array(
					'label'   => 'Website',
					'default' => '',
					'display' => array( __CLASS__, 'uo_report_column' ),
				);
			}
		}

		if ( 'on' === self::get_settings_value( 'uncanny-learndash-report-biographicalinfo', __CLASS__ ) ) {
			// Add Biographical Info
			if ( ! isset( $data_headers['biographicalinfo'] ) ) {
				$data_headers['biographicalinfo'] = array(
					'label'   => 'Biographical Info',
					'default' => '',
					'display' => array( __CLASS__, 'uo_report_column' ),
				);
			}
		}

		$custom_course_meta_keys = range( 1, 3 );
		foreach ( $custom_course_meta_keys as $course_meta_key_index ) {
			if ( 'on' === self::get_settings_value( 'uncanny-learndash-report-coursemeta-' . $course_meta_key_index, __CLASS__ ) ) {
				// Add meta field
				$heading = self::get_settings_value( 'uncanny-learndash-report-coursemetakey-' . $course_meta_key_index, __CLASS__ );
				if ( ! isset( $data_headers[ $heading ] ) ) {
					$data_headers[ $heading ] = array(
						'label'   => $heading,
						'default' => '',
						'display' => array( __CLASS__, 'uo_report_column' ),
					);
				}
			}
		}

		// Add Assignments Info
		if ( ! isset( $data_headers['assignmentsinfo'] ) ) {
			$total_columns = apply_filters( 'uo_assignments_columns', 3 );
			for ( $loop = 1; $loop <= $total_columns; $loop ++ ) {
				if ( 'on' === self::get_settings_value( 'uncanny-learndash-report-assignmentsinfo-' . $loop, __CLASS__ ) ) {
					$data_headers[ 'assignmentsinfo_' . $loop ] = array(
						'label'   => 'Assignment (ID - Status) #' . $loop,
						'default' => '',
						'display' => array( __CLASS__, 'uo_report_column' ),
					);
				}
			}
		}

		$custom_user_meta_keys = range( 1, 10 );
		foreach ( $custom_user_meta_keys as $meta_key_index ) {
			if ( 'on' === self::get_settings_value( 'uncanny-learndash-report-usermeta-' . $meta_key_index, __CLASS__ ) ) {
				// Add meta field
				$heading = self::get_settings_value( 'uncanny-learndash-report-usermetakey-' . $meta_key_index, __CLASS__ );
				if ( ! isset( $data_headers[ $heading ] ) ) {
					$data_headers[ $heading ] = array(
						'label'   => $heading,
						'default' => '',
						'display' => array( __CLASS__, 'uo_report_column' ),
					);
				}
			}
		}

		return $data_headers;
	}

	/**
	 * This function defines the content value
	 *
	 * The header filter does to things. It creates the CSV heading and defined
	 * the function that will return the value of the column row.
	 *
	 * @param string $column_value The value of the column
	 * @param string $column_key The key set by $data_headers['sample_key'] @
	 *     course_export_upgraded_headers_filter()
	 * @param object $report_item The LD activity object
	 * @param object $report_user WP_User object
	 *
	 * @return string $column_value
	 */
	public static function uo_report_column( $column_value = '', $column_key = '', $report_item = '', $report_user = '' ) {

		global $wpdb;
		switch ( $column_key ) {
			case 'username':
				if ( $report_user instanceof WP_User ) {
					$column_value = $report_user->user_login;
				}
				break;
			case 'first_name':
				if ( $report_user instanceof WP_User ) {
					$column_value = get_user_meta( $report_user->ID, 'first_name', true );
				}
				break;
			case 'last_name':
				if ( $report_user instanceof WP_User ) {
					$column_value = get_user_meta( $report_user->ID, 'last_name', true );
				}
				break;
			case 'nick_name':
				if ( $report_user instanceof WP_User ) {
					$column_value = get_user_meta( $report_user->ID, 'nickname', true );
				}
				break;
			case 'display_name':
				if ( $report_user instanceof WP_User ) {
					$column_value = $report_user->display_name;
				}
				break;
			case 'roles':
				if ( $report_user instanceof WP_User ) {
					$column_value = self::get_user_role( $report_user );
				}
				break;
			case 'groups':
				if ( $report_user instanceof WP_User ) {
					$column_value = self::get_user_groups( $report_user );
				}
				break;
			case 'course_start_date':
				if ( $report_user instanceof WP_User ) {
					$column_value = self::get_course_start_date( $report_user, $report_item );
				}
				break;
			case 'language':
				if ( $report_user instanceof WP_User ) {
					$column_value = get_user_locale( $report_user );
				}
				break;
			case 'website':
				if ( $report_user instanceof WP_User ) {
					$column_value = $report_user->user_url;
				}
				break;
			case 'biographicalinfo':
				if ( $report_user instanceof WP_User ) {
					$column_value = get_user_meta( $report_user->ID, 'description', true );
				}
				break;

			case 'uncanny_ceus':
				if ( $report_user instanceof WP_User ) {
					$course_id     = learndash_get_course_id( $report_item->post_id );
					$earned_points = do_shortcode( "[uo_ceu_earned user-id='$report_user->ID' course-id='$report_item->post_id']" );
					if ( ! empty( $earned_points ) && $earned_points > 0 ) {
						$column_value = $earned_points;
					} else {
						$column_value = 0;
					}
				}
				break;

			default:
				if ( $report_user instanceof WP_User ) {

					$course_keys   = array();
					$course_keys[] = self::get_settings_value( 'uncanny-learndash-report-coursemetakey-1', __CLASS__ );
					$course_keys[] = self::get_settings_value( 'uncanny-learndash-report-coursemetakey-2', __CLASS__ );
					$course_keys[] = self::get_settings_value( 'uncanny-learndash-report-coursemetakey-3', __CLASS__ );

					$total_columns     = apply_filters( 'uo_assignments_columns', 3 );
					$assignment_fields = array();
					for ( $loop = 0; $loop <= $total_columns; $loop++ ) {
						$assignment_fields[] = 'assignmentsinfo_' . $loop;
					}
					if ( in_array( $column_key, $course_keys ) ) {
						$column_value = get_post_meta( $report_item->post_id, $column_key, true );
					} elseif ( in_array( $column_key, $assignment_fields ) ) {
						$column_value = self::get_assignments_info( $report_user, $report_item, absint( end( explode( '_', $column_key ) ) - 1 ) );
					} else {
						$column_value = get_user_meta( $report_user->ID, $column_key, true );
					}
				}
				break;
		}

		return apply_filters( 'uo_csv_report_column_value', $column_value, $column_key, $report_item, $report_user );
	}


	/**
	 * Method is used to get value of specific assignment.
	 *
	 * @param $report_user
	 * @param $report_item
	 * @param $index
	 *
	 * @return string|void
	 */
	public static function get_assignments_info( $report_user, $report_item, $index ) {
		if ( $report_user instanceof WP_User ) {
			$course_id   = learndash_get_course_id( $report_item->post_id );
			$assignments = self::get_assignments_details( $report_user->ID, $course_id );
			if ( isset( $assignments[ $index ] ) ) {
				$meta_value   = get_post_meta( $assignments[ $index ], 'approval_status', true );
				$column_value = ( '1' === (string) $meta_value ) ? $assignments[ $index ] . ' - Approved' : $assignments[ $index ] . ' - Not approved';
			}

			return $column_value;
		}
	}

	/**
	 * Get user info
	 *
	 * @param $user
	 *
	 * @return string
	 */
	public static function get_user_role( $user ) {
		global $wp_roles;

		$roles = array();

		if ( ! empty( $user->roles ) && is_array( $user->roles ) ) {
			foreach ( $user->roles as $role ) {
				if ( isset( $wp_roles->role_names[ $role ] ) ) {
					$roles[] .= translate_user_role( $wp_roles->role_names[ $role ] );
				}
			}
		}

		return implode( ', ', $roles );
	}

	/**
	 * Get user groups
	 *
	 * @param $user
	 *
	 * @return string
	 */
	public static function get_user_groups( $user ) {
		$groups          = array();
		$user_groups     = learndash_get_users_group_ids( $user->ID );
		$has_user_groups = ! empty( $user_groups ) && is_array( $user_groups ) && ! empty( $user_groups[0] );
		if ( $has_user_groups ) {
			foreach ( $user_groups as $group_id ) {
				if ( ! empty( $group_id ) ) {
					$group = get_post( $group_id );
					if ( ( $group ) && ( is_a( $group, 'WP_Post' ) ) ) {
						$groups[] = $group->post_title;
					}
				}
			}
		}

		return implode( ', ', $groups );
	}

	/**
	 * Get Course start date
	 *
	 * @param $user
	 *
	 * @return string
	 */
	public static function get_course_start_date( $user, $item ) {
		$course_id     = learndash_get_course_id( $item->post_id );
		$user_id       = $user->ID;

		$course_args = array(
			'course_id'     => $course_id,
			'user_id'       => $user_id,
			'post_id'       => $course_id,
			'activity_type' => 'course',
		);
		$course_activity =  learndash_get_user_activity( $course_args );
		if ( isset( $course_activity->course_id ) && isset( $course_activity->user_id ) && ! empty( $course_activity->activity_started ) && $course_activity->course_id == $course_id ) {
			$user_course_start_time = learndash_adjust_date_time_display( $course_activity->activity_started );
		}else{
			$user_course_start_time = '';
		}
		return $user_course_start_time;
	}

	/**
	 * Settings fields validation.
	 *
	 * @param $class
	 * @param $options
	 */
	public static function uo_learndash_export_settings_validation( $class, $options ) {
		if ( 'uncanny_pro_toolkitEnhancedLearndashReport' === $class ) {
			$metakey_checked = array();
			$response        = array(
				'error'   => true,
				'message' => '',
			);
			if ( ! empty( $options ) ) {
				foreach ( $options as $option ) {
					if ( 'uncanny-learndash-report-usermeta-1' === $option['name'] && 'on' === $option['value'] ) {
						$metakey_checked['uncanny-learndash-report-usermetakey-1'] = 'uncanny-learndash-report-usermetakey-1';
					}
					if ( 'uncanny-learndash-report-usermetakey-1' === $option['name'] && ! empty( $option['value'] ) ) {
						unset( $metakey_checked['uncanny-learndash-report-usermetakey-1'] );
					}
					if ( 'uncanny-learndash-report-usermeta-2' === $option['name'] && 'on' === $option['value'] ) {
						$metakey_checked['uncanny-learndash-report-usermetakey-2'] = 'uncanny-learndash-report-usermetakey-2';
					}
					if ( 'uncanny-learndash-report-usermetakey-2' === $option['name'] && ! empty( $option['value'] ) ) {
						unset( $metakey_checked['uncanny-learndash-report-usermetakey-2'] );
					}
					if ( 'uncanny-learndash-report-usermeta-3' === $option['name'] && 'on' === $option['value'] ) {
						$metakey_checked['uncanny-learndash-report-usermetakey-3'] = 'uncanny-learndash-report-usermetakey-3';
					}
					if ( 'uncanny-learndash-report-usermetakey-3' === $option['name'] && ! empty( $option['value'] ) ) {
						unset( $metakey_checked['uncanny-learndash-report-usermetakey-3'] );
					}
					if ( 'uncanny-learndash-report-usermeta-4' === $option['name'] && 'on' === $option['value'] ) {
						$metakey_checked['uncanny-learndash-report-usermetakey-4'] = 'uncanny-learndash-report-usermetakey-4';
					}
					if ( 'uncanny-learndash-report-usermetakey-4' === $option['name'] && ! empty( $option['value'] ) ) {
						unset( $metakey_checked['uncanny-learndash-report-usermetakey-4'] );
					}
					if ( 'uncanny-learndash-report-usermeta-5' === $option['name'] && 'on' === $option['value'] ) {
						$metakey_checked['uncanny-learndash-report-usermetakey-5'] = 'uncanny-learndash-report-usermetakey-5';
					}
					if ( 'uncanny-learndash-report-usermetakey-5' === $option['name'] && ! empty( $option['value'] ) ) {
						unset( $metakey_checked['uncanny-learndash-report-usermetakey-5'] );
					}
					if ( 'uncanny-learndash-report-usermeta-6' === $option['name'] && 'on' === $option['value'] ) {
						$metakey_checked['uncanny-learndash-report-usermetakey-6'] = 'uncanny-learndash-report-usermetakey-6';
					}
					if ( 'uncanny-learndash-report-usermetakey-6' === $option['name'] && ! empty( $option['value'] ) ) {
						unset( $metakey_checked['uncanny-learndash-report-usermetakey-6'] );
					}
					if ( 'uncanny-learndash-report-usermeta-7' === $option['name'] && 'on' === $option['value'] ) {
						$metakey_checked['uncanny-learndash-report-usermetakey-7'] = 'uncanny-learndash-report-usermetakey-7';
					}
					if ( 'uncanny-learndash-report-usermetakey-7' === $option['name'] && ! empty( $option['value'] ) ) {
						unset( $metakey_checked['uncanny-learndash-report-usermetakey-7'] );
					}
					if ( 'uncanny-learndash-report-usermeta-8' === $option['name'] && 'on' === $option['value'] ) {
						$metakey_checked['uncanny-learndash-report-usermetakey-8'] = 'uncanny-learndash-report-usermetakey-8';
					}
					if ( 'uncanny-learndash-report-usermetakey-8' === $option['name'] && ! empty( $option['value'] ) ) {
						unset( $metakey_checked['uncanny-learndash-report-usermetakey-8'] );
					}
					if ( 'uncanny-learndash-report-usermeta-9' === $option['name'] && 'on' === $option['value'] ) {
						$metakey_checked['uncanny-learndash-report-usermetakey-9'] = 'uncanny-learndash-report-usermetakey-9';
					}
					if ( 'uncanny-learndash-report-usermetakey-9' === $option['name'] && ! empty( $option['value'] ) ) {
						unset( $metakey_checked['uncanny-learndash-report-usermetakey-9'] );
					}
					if ( 'uncanny-learndash-report-usermeta-10' === $option['name'] && 'on' === $option['value'] ) {
						$metakey_checked['uncanny-learndash-report-usermetakey-10'] = 'uncanny-learndash-report-usermetakey-10';
					}
					if ( 'uncanny-learndash-report-usermetakey-10' === $option['name'] && ! empty( $option['value'] ) ) {
						unset( $metakey_checked['uncanny-learndash-report-usermetakey-10'] );
					}

					if ( 'uncanny-learndash-report-coursemeta-1' === $option['name'] && 'on' === $option['value'] ) {
						$metakey_checked['uncanny-learndash-report-coursemetakey-1'] = 'uncanny-learndash-report-coursemetakey-1';
					}
					if ( 'uncanny-learndash-report-coursemetakey-1' === $option['name'] && ! empty( $option['value'] ) ) {
						unset( $metakey_checked['uncanny-learndash-report-coursemetakey-1'] );
					}
					if ( 'uncanny-learndash-report-coursemeta-2' === $option['name'] && 'on' === $option['value'] ) {
						$metakey_checked['uncanny-learndash-report-coursemetakey-2'] = 'uncanny-learndash-report-coursemetakey-2';
					}
					if ( 'uncanny-learndash-report-coursemetakey-2' === $option['name'] && ! empty( $option['value'] ) ) {
						unset( $metakey_checked['uncanny-learndash-report-coursemetakey-2'] );
					}
					if ( 'uncanny-learndash-report-coursemeta-3' === $option['name'] && 'on' === $option['value'] ) {
						$metakey_checked['uncanny-learndash-report-coursemetakey-3'] = 'uncanny-learndash-report-coursemetakey-3';
					}
					if ( 'uncanny-learndash-report-coursemetakey-3' === $option['name'] && ! empty( $option['value'] ) ) {
						unset( $metakey_checked['uncanny-learndash-report-coursemetakey-3'] );
					}
				}
			}

			if ( ! empty( $metakey_checked ) ) {
				$response['message'] = __( 'Usermeta key values must be populated for them to be included in the report.', 'uncanny-learndash-toolkit' );
				echo wp_json_encode( $response );

				wp_die();
			}
		}
	}

}
