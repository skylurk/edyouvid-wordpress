<?php

namespace uncanny_learndash_reporting;

/**
 *
 */
class Config {
	public static $profile_results = [];
	/**
	 * Version of the plugin
	 *
	 * @var string
	 */
	private static $version;
	/**
	 * Project name
	 *
	 * @var string
	 */
	private static $project_name;

	/**
	 * Get admin css url
	 *
	 * @param string $file_name
	 *
	 * @return string
	 */
	public static function get_admin_css( $file_name ) {
		$asset_url = plugins_url( 'assets/admin/css/' . $file_name, __FILE__ );

		return $asset_url;
	}

	/**
	 * Get admin js url
	 *
	 * @param string $file_name
	 *
	 * @return string
	 */
	public static function get_admin_js( $file_name, $suffix = null ) {

		if ( null === $suffix ) {
			$file_name_suffix = '.min.js';

			if ( true === UO_REPORTING_DEBUG ) {
				$file_name_suffix = '.js';
			}
		} else {
			$file_name_suffix = $suffix;
		}

		$asset_url = plugins_url( 'assets/admin/js/dist/' . $file_name . $file_name_suffix, __FILE__ );

		return $asset_url;
	}

	/**
	 * @param $file_name
	 *
	 * @return string
	 */
	public static function get_src_assets_dist_css_url( $file_name ) {
		$file_name_suffix = '.min.css';

		if ( true === UO_REPORTING_DEBUG ) {
			$file_name_suffix = '.css';
		}

		$asset_url = plugins_url( 'assets/dist/css/' . $file_name . $file_name_suffix, __FILE__ );

		return $asset_url;
	}

	/**
	 * @param $file_name
	 *
	 * @return string
	 */
	public static function get_src_assets_dist_js_url( $file_name ) {
		$file_name_suffix = '.min.js';

		if ( true === UO_REPORTING_DEBUG ) {
			$file_name_suffix = '.js';
		}

		$asset_url = plugins_url( 'assets/dist/scripts/' . $file_name . $file_name_suffix, __FILE__ );

		return $asset_url;
	}

	/**
	 * Get template path
	 *
	 * @param string $file_name File name must be prefixed with a \ (foreword slash)
	 * @param mixed $file (false || __FILE__ )
	 *
	 * @return string
	 */
	public static function get_template( $file_name, $file = false ) {

		if ( false === $file ) {
			$file = __FILE__;
		}

		$asset_uri = dirname( $file ) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $file_name;

		return $asset_uri;
	}

	/**
	 * Get includes path
	 *
	 * @param string $file_name File name must be prefixed with a \ (foreword slash)
	 * @param mixed $file (false || __FILE__ )
	 *
	 * @return string
	 */
	public static function get_include( $file_name, $file = false ) {

		if ( false === $file ) {
			$file = __FILE__;
		}

		$asset_uri = dirname( $file ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $file_name;

		return $asset_uri;
	}

	/**
	 * Get plugin prefix
	 *
	 * @return string
	 */
	public static function get_prefix() {
		return self::get_project_name() . '_';
	}

	/**
	 * Get plugin name
	 *
	 * @return string
	 */
	public static function get_project_name() {
		if ( null === self::$project_name ) {
			self::$project_name = 'uncanny_learndash_reporting';
		}

		return self::$project_name;
	}

	/**
	 * Get plugin version
	 *
	 * @return string
	 */
	public static function get_version() {
		if ( null === self::$version ) {
			self::$version = '1.2.8';
		}

		return self::$version;
	}

	/**
	 * Create and store logs @ wp-content/{plugin_folder_name}/uo-{$file_name}.log
	 *
	 * @since    1.0.0
	 *
	 * @param string $trace_message The message logged
	 * @param string $trace_heading The heading of the current trace
	 * @param bool $force_log Create log even if debug mode is off
	 * @param string $file_name The file name of the log file
	 *
	 * @return bool $error_log Was the log successfully created
	 */
	public static function log( $trace_message = '', $trace_heading = '', $force_log = false, $file_name = 'logs' ) {

		// Only return log if debug mode is on OR if log is forced
		if ( ! $force_log ) {

			if ( ! UO_REPORTING_DEBUG ) {
				return false;
			}
		}

		$timestamp = date( 'Y-m-d, g:i a' ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

		$current_page_link = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		$trace_start = "\n===========================<<<< $timestamp >>>>===========================\n";

		$trace_heading = "* Heading: $trace_heading \n";

		$trace_heading .= "* Current Page: $current_page_link \n";

		$trace_end = "\n===========================<<<< TRACE END >>>>===========================\n\n";

		$trace_message = print_r( $trace_message, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

		//$file = dirname( self::get_plugin_file() ) . '/uo-' . $file_name . '.log';
		$file = WP_CONTENT_DIR . '/uo-' . $file_name . '.log';

		error_log( $trace_start . $trace_heading . $trace_message . $trace_end, 3, $file ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

	/**
	 * @return void
	 */
	public static function log_profile_results() {
		usort(self::$profile_results, function ($a, $b) {
			return $b['time'] <=> $a['time'];
		});

		if (defined('TINCANNY_ENABLE_LOGGING') && TINCANNY_ENABLE_LOGGING) {
			foreach ( self::$profile_results as $result ) {
				$log_message = "[" . date( "Y-m-d H:i:s" ) . "] Execution time of {$result['function']} is {$result['human_readable_time']}\n";

				if ( defined( 'TINCANNY_LOG_FILE_PATH' ) ) {
					error_log( $log_message, 3, TINCANNY_LOG_FILE_PATH );
				}
			}
			if ( ! empty( $log_message ) ) {
				error_log( PHP_EOL . '----' . PHP_EOL, 3, TINCANNY_LOG_FILE_PATH );
			}
		}
	}

	/**
	 * @param $callable
	 * @param mixed ...$args
	 *
	 * @return mixed
	 */
	public static function profile_function($callable, ...$args) {
		$start_time = microtime(true);
		$result = call_user_func_array($callable, $args);
		$end_time = microtime(true);
		$execution_time = $end_time - $start_time;

		$human_readable_time = self::format_execution_time($execution_time);
		$function_name = is_array($callable) ? $callable[1] : $callable;

		self::$profile_results[] = [
			'function' => $function_name,
			'time' => $execution_time,
			'human_readable_time' => $human_readable_time,
		];

		return $result;
	}

	/**
	 * @param $time_in_seconds
	 *
	 * @return string
	 */
	public static function format_execution_time($time_in_seconds) {
		if ($time_in_seconds >= 1) {
			return number_format($time_in_seconds, 2) . ' seconds';
		} elseif ($time_in_seconds >= 0.001) {
			return number_format($time_in_seconds * 1000, 2) . ' milliseconds';
		} elseif ($time_in_seconds >= 0.000001) {
			return number_format($time_in_seconds * 1000000, 2) . ' microseconds';
		} else {
			return number_format($time_in_seconds * 1000000000, 2) . ' nanoseconds';
		}
	}
}

// Ensure the profile results are logged at the end of the script execution
register_shutdown_function(array('\uncanny_learndash_reporting\Config', 'log_profile_results'));

