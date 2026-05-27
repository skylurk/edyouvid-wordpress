<?php

namespace uncanny_ceu;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * This class stores helper functions that can be used statically in all of WP after plugins loaded hook
 *
 * Use the Utilites::get_% function to retrieve the variable. The following is a list of calls
 *
 * @author     Uncanny Owl
 * @subpackage uncanny_ceu/config
 * @package    uncanny_ceu
 */
class Utilities {

	/**
	 * The name of the plugin
	 *
	 * @use      get_plugin_name()
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private static $plugin_name;

	/**
	 * The prefix of this plugin that is set in the config class
	 *
	 * @use      get_version()
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private static $prefix;

	/**
	 * The plugins version number
	 *
	 * @use      get_version()
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private static $version;

	/**
	 * The main plugin file path
	 *
	 * @use      get_plugin_file()
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private static $plugin_file;

	/**
	 * The references to autoloaded class instances
	 *
	 * @use      get_autoloaded_class_instance()
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private static $class_instances = array();

	/**
	 * The plugin specific debug mode
	 *
	 * @use      get_debug_mode()
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      bool
	 */
	private static $debug_mode;

	/**
	 * The plugin date and time format
	 *
	 * @use      get_date_time_format()
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      bool
	 */
	private static $date_time_format;

	/**
	 * The plugin date format
	 *
	 * @use      get_date_format()
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      bool
	 */
	private static $date_format;

	/**
	 * The plugin time format
	 *
	 * @use      get_time_format()
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      bool
	 */
	private static $time_format;

	/**
	 * The server time when the plugin was initialized
	 *
	 * @use      get_time_format()
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      bool
	 */
	private static $plugin_initialization;

	public static $ceu_courses = array();

	/**
	 * Set the name of the plugin
	 *
	 * @param string $plugin_name The name of the plugin
	 *
	 * @return string
	 * @since    1.0.0
	 */
	public static function set_plugin_name( $plugin_name ) {
		if ( null === self::$prefix ) {
			self::$plugin_name = $plugin_name;
		}

		return self::$plugin_name;
	}

	/**
	 * Get the name of the plugin
	 *
	 * @return string
	 * @since    1.0.0
	 */
	public static function get_plugin_name() {
		return self::$plugin_name;
	}

	/**
	 * Set the prefix for the plugin
	 *
	 * @param string $prefix Variable used to prefix filters and actions
	 *
	 * @return string
	 * @since    1.0.0
	 */
	public static function set_prefix( $prefix ) {
		if ( null === self::$prefix ) {
			self::$prefix = $prefix;
		}

		return self::$prefix;
	}

	/**
	 * Get the prefix for the plugin
	 *
	 * @return string
	 * @since    1.0.0
	 */
	public static function get_prefix() {
		return self::$prefix;
	}

	/**
	 * Set the version for the plugin
	 *
	 * @param string $version Variable used to prefix filters and actions
	 *
	 * @return string
	 * @since    1.0.0
	 */
	public static function set_version( $version ) {
		if ( null === self::$version ) {
			self::$version = $version;
		}

		return self::$version;
	}

	/**
	 * Get the version for the plugin
	 *
	 * @return string
	 * @since    1.0.0
	 */
	public static function get_version() {
		return self::$version;
	}


	/**
	 * Set the main plugin file path
	 *
	 * @param string $plugin_file The main plugin file path
	 *
	 * @return string
	 * @since    1.0.0
	 */
	public static function set_plugin_file( $plugin_file ) {
		if ( null === self::$plugin_file ) {
			self::$plugin_file = $plugin_file;
		}

		return self::$plugin_file;
	}

	/**
	 * Get the version for the plugin
	 *
	 * @return string
	 * @since    1.0.0
	 */
	public static function get_plugin_file() {
		return self::$plugin_file;
	}

	/**
	 * Set the main plugin file path
	 *
	 * @param string $class_name The name of the class instance
	 * @param object $class_instance The reference to the class instance
	 *
	 * @since    1.0.0
	 */
	public static function set_class_instance( $class_name, $class_instance ) {

		self::$class_instances[ $class_name ] = $class_instance;

	}

	/**
	 * Get the version for the plugin
	 *
	 * @param string $class_name The name of the class instance
	 *
	 * @return string
	 * @since    1.0.0
	 */
	public static function get_class_instance( $class_name ) {
		return self::$class_instances[ $class_name ];
	}

	/**
	 * Set the default date and time format
	 *
	 * @param string $date Date format
	 * @param string $time Time format
	 * @param string $separator The separator between the date and time format
	 *
	 * @return bool
	 * @since    1.0.0
	 */
	public static function set_date_time_format( $date = 'F j, Y', $time = ' g:i a', $separator = ' ' ) {

		$date      = apply_filters( self::$prefix . '_date_time_format', $date );
		$time      = apply_filters( self::$prefix . '_date_time_format', $time );
		$separator = apply_filters( self::$prefix . '_date_time_format', $separator );

		if ( null === self::$date_time_format ) {
			self::$date_time_format = $date . $separator . $time;
		}

		if ( null === self::$date_format ) {
			self::$date_format = $date;
		}

		if ( null === self::$time_format ) {
			self::$time_format = $time;
		}

		return self::$date_time_format;
	}

	/**
	 * Get the date and time format for the plugin
	 *
	 * @return string
	 * @since    1.0.0
	 */
	public static function get_date_time_format() {
		return self::$date_time_format;
	}

	/**
	 * Get the date format for the plugin
	 *
	 * @return string
	 * @since    1.0.0
	 */
	public static function get_date_format() {
		return self::$date_time_format;
	}

	/**
	 * Get the time format for the plugin
	 *
	 * @return string
	 * @since    1.0.0
	 */
	public static function get_time_format() {
		return self::$date_time_format;
	}

	/**
	 * Set the main plugin file path
	 *
	 * @param bool $debug_mode The main plugin file path
	 *
	 * @return bool
	 * @since    1.0.0
	 */
	public static function set_debug_mode( $debug_mode ) {

		if ( null === self::$debug_mode ) {

			self::$debug_mode = $debug_mode;
		}

		return self::$debug_mode;
	}

	/**
	 * Set the version for the plugin
	 *
	 * @return bool
	 * @since    1.0.0
	 */
	public static function get_debug_mode() {
		return self::$debug_mode;
	}

	/**
	 * Set the server time when the plugin was initialized
	 *
	 * @param int $time Timestamp
	 *
	 * @return int
	 * @since    1.0.0
	 */
	public static function set_plugin_initialization( $time ) {

		if ( null === self::$plugin_initialization ) {
			self::$plugin_initialization = $time;
		}

		return self::$plugin_initialization;
	}

	/**
	 * Get the server time when the plugin was initialized
	 *
	 * @return int Timestamp
	 * @since    1.0.0
	 */
	public static function get_plugin_initialization() {
		return self::$plugin_initialization;
	}

	/**
	 * Returns the full URL for the passed asset file
	 *
	 * @param string $source The source of the file we want to get. Valid values are "frontend" and "backend"
	 * @param string $file_name The name of the file
	 *
	 * @return string full URL of the file
	 * @since  3.0
	 */

	public static function get_asset( $source = 'frontend', $file_name = null ) {
		return plugins_url( 'assets/' . $source . '/dist/' . $file_name, __FILE__ );
	}

	/**
	 * Returns the full URL for the passed vendor file
	 *
	 * @param string $path The path of the file, including the name file
	 *
	 * @return string full URL of the file
	 * @since  3.0
	 */

	public static function get_vendor( $path ) {
		return plugins_url( 'assets/vendor/' . $path, __FILE__ );
	}

	/**
	 * Returns the full url for the passed media file
	 *
	 * @param string $file_name
	 *
	 * @return string $asset_url
	 * @since    1.0.0
	 */
	public static function get_media( $source = 'backend', $file_name = null ) {
		$asset_url = plugins_url( 'assets/' . $source . '/dist/img/' . $file_name, __FILE__ );

		return $asset_url;
	}

	/**
	 * Returns the full server path for the passed template file
	 *
	 * @param string $file_name
	 *
	 * @return string
	 */
	public static function get_template( $file_name ) {

		$templates_directory = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;

		/**
		 * Filters the director path to the template file
		 *
		 * This can be used for template overrides by modifying the path to go to a directory in the theme or another plugin.
		 *
		 * @param string $templates_directory Path to the plugins template folder
		 * @param string $file_name The file name of the template file
		 *
		 * @since 1.0.0
		 */
		$templates_directory = apply_filters( self::get_prefix() . '_template_path', $templates_directory, $file_name );

		$asset_path = $templates_directory . $file_name;

		return $asset_path;
	}

	/**
	 * Returns the full server path for the passed include file
	 *
	 * @param string $file_name
	 *
	 * @return string
	 */
	public static function get_include( $file_name ) {

		$includes_directory = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;

		/**
		 * Filters the director path to the include file
		 *
		 * This can be used for template overrides by modifying the path to go to a directory in the theme or another plugin.
		 *
		 * @param string $templates_directory Path to the plugins template folder
		 * @param string $file_name The file name of the template file
		 *
		 * @since 1.0.0
		 */
		$includes_directory = apply_filters( self::get_prefix() . '_includes_path_to', $includes_directory, $file_name );

		$asset_path = $includes_directory . $file_name;

		return $asset_path;
	}

	/**
	 * Create and store logs @ wp-content/{plugin_folder_name}/uo-{$file_name}.log
	 *
	 * @param string $trace_message The message logged
	 * @param string $trace_heading The heading of the current trace
	 * @param bool $force_log Create log even if debug mode is off
	 * @param string $file_name The file name of the log file
	 *
	 * @return bool $error_log Was the log successfully created
	 * @since    1.0.0
	 */
	public static function log( $trace_message = '', $trace_heading = '', $force_log = false, $file_name = 'logs' ) {

		// Only return log if debug mode is on OR if log is forced
		if ( ! $force_log ) {

			if ( ! self::get_debug_mode() ) {
				return false;
			}
		}

		$timestamp = date( self::get_date_time_format() );

		$current_page_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		$trace_start = "\n===========================<<<< $timestamp >>>>===========================\n";

		$trace_heading = "* Heading: $trace_heading \n";

		$trace_heading .= "* Current Page: $current_page_link \n";

		$trace_heading .= '* Plugin Initialized: ' . date( self::get_date_time_format(), self::get_plugin_initialization() ) . "\n";

		$trace_end = "\n===========================<<<< TRACE END >>>>===========================\n\n";

		$trace_message = print_r( $trace_message, true );

		//$file = dirname( self::get_plugin_file() ) . '/uo-' . $file_name . '.log';
		$file = WP_CONTENT_DIR . '/uo-' . $file_name . '.log';

		$error_log = error_log( $trace_start . $trace_heading . $trace_message . $trace_end, 3, $file );

		return $error_log;

	}


	/**
	 * @return \DateTime|false
	 */
	public static function rollover_date_as_unix() {

		$roll_over_date = get_option( 'ceu_rollover_date', false );

		if ( $roll_over_date ) {

			$roll_over_date = \DateTime::createFromFormat( 'd\/m', $roll_over_date );

			if ( $roll_over_date ) {
				$current_date = new \DateTime();

				if ( $roll_over_date->format( 'U' ) > $current_date->format( 'U' ) ) {
					$year = $roll_over_date->format( 'Y' ) - 1;
				} else {
					$year = $roll_over_date->format( 'Y' );
				}

				$month = $roll_over_date->format( 'm' );
				$day   = $roll_over_date->format( 'd' );
				$roll_over_date->setDate( $year, $month, $day );
				$roll_over_date->setTime( 0, 0, 0 );

				return $roll_over_date;

			}
		}

		return false;
	}

	/**
	 * @param string $type plural or singular
	 * @param string $default
	 * @param bool $to_lower
	 *
	 * @return string
	 */
	public static function credit_designation_label( $type = 'plural', $default = '', $to_lower = false ) {

		if ( 'plural' === $type ) {

			if ( empty( $default ) ) {
				$default = __( 'CEUs', 'uncanny-ceu' );
			}

			$credit_designation_label_plural = get_option( 'credit_designation_label_plural', $default );

			if ( ! empty( $credit_designation_label_plural ) ) {
				return $credit_designation_label_plural;
			}

			return $default;
		}

		if ( 'singular' === $type ) {

			if ( empty( $default ) ) {
				$default = __( 'CEU', 'uncanny-ceu' );
			}

			$credit_designation_label_singular = get_option( 'credit_designation_label', $default );

			if ( ! empty( $credit_designation_label_singular ) ) {
				return $credit_designation_label_singular;
			}

			return $default;

		}

		return __( 'CEUs', 'uncanny-ceu' );
	}

	/** Get a list of courses that have ceu values
	 *
	 * @return array
	 */
	public static function get_courses_with_credits() {
		global $wpdb;

		if ( ! empty( self::$ceu_courses ) ) {
			return self::$ceu_courses;
		}

		$results = $wpdb->get_results(
			"SELECT pm.post_id AS ID, p.post_title AS post_title
FROM $wpdb->postmeta pm
JOIN $wpdb->posts p
ON pm.post_id = p.ID
WHERE pm.meta_key = 'ceu_value'
AND pm.meta_value != 0
ORDER BY p.post_title ASC;"
		);

		self::$ceu_courses = $results;

		return $results;
	}

	/**
	 * Check if a variable is set
	 *
	 * @param string $variable
	 * @param string $type
	 *
	 * @return bool
	 */
	public static function ceu_filter_has_var( $variable = null, $type = INPUT_GET ) {
		return filter_has_var( $type, $variable );
	}

	/**
	 * Retrieve a variable from the request
	 *
	 * @param string $variable
	 * @param string $type
	 * @param int $flags
	 *
	 * @return string
	 */
	public static function ceu_filter_input( $variable = null, $type = INPUT_GET, $flags = FILTER_UNSAFE_RAW ) {
		return sanitize_text_field( filter_input( $type, $variable, $flags ) );
	}

	/**
	 * Retrieve an array of variables from the request
	 *
	 * @param string $variable
	 * @param string $type
	 * @param array $flags
	 *
	 * @return array
	 */
	public static function ceu_filter_input_array( $variable = null, $type = INPUT_GET, $flags = array() ) {
		if ( empty( $flags ) ) {
			$flags = array(
				'filter' => FILTER_UNSAFE_RAW,
				'flags'  => FILTER_REQUIRE_ARRAY,
			);
		}

		$args = array( $variable => $flags );
		$val  = filter_input_array( $type, $args );

		return isset( $val[ $variable ] ) ? $val[ $variable ] : array();
	}

	/**
	 * Generate select options from an array
	 *
	 * @param array $options
	 * @param string $selected
	 *
	 * @return string - HTML select options
	 */
	public static function generate_select_options( $options, $selected = null ) {
		$html = '';

		foreach ( $options as $option ) {
			$html .= sprintf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $option['value'] ),
				$selected === $option['value'] ? ' selected' : '',
				esc_html( $option['text'] )
			);
		}

		return $html;
	}

	/**
	 * Get the date range translations
	 *
	 * @return array
	 */
	public static function get_date_range_translations() {
		return array(
			'report' => array(
				'filter' => array(
					'date' => array(
						'saveButton'            => __( 'Set range', 'uncanny-ceu' ),
						'startFieldPlaceholder' => __( 'Start', 'uncanny-ceu' ),
						'endFieldPlaceholder'   => __( 'End', 'uncanny-ceu' ),
						'fieldTemplate'         => _x( '%1$s - %2$s', '%1$s and %2$s are dates', 'uncanny-ceu' ),
					),
				),
			),
		);
	}

}
