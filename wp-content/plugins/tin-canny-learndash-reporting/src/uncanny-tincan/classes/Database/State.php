<?php
/**
 * Database\Completion
 *
 * @package    Tin Canny Reporting for LearnDash
 * @subpackage TinCan Module
 * @author     Uncanny Owl
 * @since      1.3.6
 */

namespace UCTINCAN\Database;

if ( ! defined( 'UO_ABS_PATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 *
 */
class State extends \UCTINCAN\Database {
	use \UCTINCAN\Modules;

	//phpcs:disable WordPress.DB.PreparedSQL
	/**
	 * @var int|mixed|string
	 */
	public $lesson_id;

	/**
	 * Get State Data
	 *
	 * @access public
	 * @return string
	 * @since  1.3.6
	 */
	public function get_state( $url, $state_id ) {
		global $wpdb;

		if ( ! $this->set_user_id() ) {
			return false;
		}

		// Sync table columns
		$this->add_course_lesson_columns();
		// Get course and lesson id from request
		list( $course_id, $lesson_id ) = $this->fetch_course_lesson_ids();

		$module_id       = $this->get_slide_id_from_url( $url );
		$table_name      = $wpdb->prefix . self::TABLE_RESUME;
		$module_id_value = isset( $module_id[1] ) ? $module_id[1] : '';

		// SELECT with course and lesson ids
		$query = $wpdb->prepare(
			"SELECT `value` FROM {$table_name}
				WHERE
					`user_id`   = %s AND
					`module_id` = %s AND
					`course_id` = %s AND
					`lesson_id` = %s AND
					`state`     = %s
			LIMIT 1
			",
			self::$user_id,
			$module_id_value,
			$course_id,
			$lesson_id,
			$state_id
		);

		$return = $wpdb->get_var( $query );

		if ( ! empty( $return ) ) {
			if ( 'suspend_data' === $state_id ) {
				$return = apply_filters( 'uo_tincanny_reporting_sanitize_suspend_data', $return, $module_id_value, 'GET' );
			}
			return $return;
		}

		$fallback_query = apply_filters( 'uo_tincanny_reporting_get_state_fallback_query', true, $module_id_value );

		if ( true === $fallback_query ) {
			$query = $wpdb->prepare(
				"SELECT `value` FROM {$table_name}
					WHERE
						`user_id`   = %s AND
						`module_id` = %s AND
						`state`     = %s
				LIMIT 1
				",
				self::$user_id,
				$module_id_value,
				$state_id
			);

			$return = $wpdb->get_var( $query );

			if ( ! empty( $return ) ) {
				if ( 'suspend_data' === $state_id ) {
					$return = apply_filters( 'uo_tincanny_reporting_sanitize_suspend_data', $return, $module_id_value, 'GET' );
				}
				return $return;
			}
		}

		return null;
	}

	/**
	 * Save State Data
	 *
	 * @access public
	 * @return void
	 * @since  1.3.6
	 */
	public function save_state( $url, $state_id, $content ) {

		if ( ! $this->set_user_id() ) {
			self::log( 'Save FAILED: User ID not set', 'State' );
			return false;
		}
		
		self::log( sprintf( 'Save: user_id=%s, state_id=%s, url=%s', self::$user_id, $state_id, $url ), 'State' );
		
		// Sync table columns
		$this->add_course_lesson_columns();

		// Get course and lesson id from request
		list( $course_id, $lesson_id ) = $this->fetch_course_lesson_ids();
		self::log( sprintf( 'Save: course_id=%s, lesson_id=%s', $course_id, $lesson_id ), 'State' );

		if ( 'suspend_data' === $state_id ) {
			$module_id = $this->get_slide_id_from_url( $url );
			$module_id = isset( $module_id[1] ) ? $module_id[1] : '';
			$content   = apply_filters( 'uo_tincanny_reporting_sanitize_suspend_data', $content, $module_id, 'PUT' );
			if ( empty( $content ) ) {
				self::log( 'Save FAILED: Content empty after sanitize filter', 'State' );
				return false;
			}
		}

		$existing_state = $this->get_state( $url, $state_id );
		if ( $existing_state !== null ) {
			self::log( 'Save: Updating existing state', 'State' );
			$this->update_state( $url, $state_id, $content, $course_id, $lesson_id );
			return true;
		}

		self::log( 'Save: Inserting new state', 'State' );
		$this->insert_state( $url, $state_id, $content, $course_id, $lesson_id );
	}

	/**
	 * Update State Data
	 *
	 * @access private
	 * @return void
	 * @since  1.3.6
	 */
	private function update_state( $url, $state_id, $content, $course_id = 0, $lesson_id = 0 ) {
		global $wpdb;
		$module_id  = $this->get_slide_id_from_url( $url );
		$table_name = $wpdb->prefix . self::TABLE_RESUME;
		
		self::log( sprintf( 'update_state: module_id=%s', print_r( $module_id, true ) ), 'State' );
		
		if ( ! get_option( $wpdb->prefix . self::TABLE_RESUME . '_primary_key' ) ) {
			$wpdb->query( ' ALTER TABLE ' . $wpdb->prefix . self::TABLE_RESUME . ' ADD `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`);' );
			update_option( $wpdb->prefix . self::TABLE_RESUME . '_primary_key', true );
		}

		// if module id is not detected - bailout.
		if ( ! isset($module_id[0]) ) {
			self::log( 'update_state FAILED: module_id[0] not set', 'State' );
			return;
		}

		self::log( sprintf( 'update_state: Attempting UPDATE with user_id=%s, module_id=%s, state=%s, course_id=%s, lesson_id=%s', 
			self::$user_id, $module_id[1], $state_id, $course_id, $lesson_id ), 'State' );

		$query = $wpdb->prepare(
			"UPDATE {$table_name}
				SET `value` = %s
				WHERE
					`user_id`   = %s AND
					`module_id` = %s AND
					`course_id` = %s AND
					`lesson_id` = %s AND
					`state`     = %s
			",
			$content,
			self::$user_id,
			$module_id[1],
			$course_id,
			$lesson_id,
			$state_id
		);

		$result = $wpdb->query( $query );
		
		if ( $wpdb->last_error ) {
			self::log( sprintf( 'update_state DB ERROR: %s', $wpdb->last_error ), 'State' );
		}
		
		// try for old data
		if ( ! $result ) {
			self::log( 'update_state: First UPDATE affected 0 rows, trying fallback query without course/lesson', 'State' );
			$query = $wpdb->prepare(
				"UPDATE {$table_name}
					SET `value` = %s,
					`course_id` = %s,
					`lesson_id` = %s
					WHERE
						`user_id`   = %s AND
						`module_id` = %s AND
						`state`     = %s
				",
				$content,
				$course_id,
				$lesson_id,
				self::$user_id,
				$module_id[1],
				$state_id
			);
			$result = $wpdb->query( $query );
			
			if ( $wpdb->last_error ) {
				self::log( sprintf( 'update_state DB ERROR (fallback): %s', $wpdb->last_error ), 'State' );
			} else {
				self::log( sprintf( 'update_state SUCCESS (fallback): Updated %d row(s)', $result ), 'State' );
			}
		} else {
			self::log( sprintf( 'update_state SUCCESS: Updated %d row(s)', $result ), 'State' );
		}
	}

	/**
	 * Insert State Data
	 *
	 * @access private
	 * @return void
	 * @since  1.3.6
	 */
	private function insert_state( $url, $state_id, $content, $course_id = 0, $lesson_id = 0 ) {
		global $wpdb;
		$module_id  = $this->get_slide_id_from_url( $url );
		$table_name = $wpdb->prefix . self::TABLE_RESUME;

		self::log( sprintf( 'insert_state: module_id=%s', print_r( $module_id, true ) ), 'State' );

		if ( ! self::$user_id ) {
			self::log( 'insert_state FAILED: No user_id', 'State' );
			return;
		}
		
		if ( ! isset( $module_id[1] ) || empty( $module_id[1] ) ) {
			self::log( 'insert_state FAILED: module_id[1] not set or empty', 'State' );
			return;
		}
		
		if ( ! get_option( $wpdb->prefix . self::TABLE_RESUME . '_primary_key' ) ) {
			$wpdb->query( ' ALTER TABLE ' . $wpdb->prefix . self::TABLE_RESUME . ' ADD `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`);' );
			update_option( $wpdb->prefix . self::TABLE_RESUME . '_primary_key', true );
		}
		
		self::log( sprintf( 'insert_state: Attempting INSERT with user_id=%s, module_id=%s, state=%s, course_id=%s, lesson_id=%s, content_length=%d', 
			self::$user_id, $module_id[1], $state_id, $course_id, $lesson_id, strlen( $content ) ), 'State' );
		
		$query = $wpdb->prepare(
			"INSERT INTO {$table_name} ( `user_id`, `module_id`, `state`, `value`, `course_id`, `lesson_id` ) VALUES ( %s, %s, %s, %s, %s, %s ); ",
			self::$user_id,
			$module_id[1],
			$state_id,
			$content,
			$course_id,
			$lesson_id
		);

		$result = $wpdb->query( $query );
		
		if ( $wpdb->last_error ) {
			self::log( sprintf( 'insert_state DB ERROR: %s', $wpdb->last_error ), 'State' );
			
			if ( ! get_option( $wpdb->prefix . self::TABLE_RESUME . '_constraints' ) ) {
				self::log( 'insert_state: Attempting to update constraints and retry', 'State' );
				self::update_constraints( self::TABLE_RESUME );
				$query = $wpdb->prepare(
					"INSERT INTO {$table_name} ( `user_id`, `module_id`, `state`, `value`, `course_id`, `lesson_id` ) VALUES ( %s, %s, %s, %s, %s, %s ); ",
					self::$user_id,
					$module_id[1],
					$state_id,
					$content,
					$course_id,
					$lesson_id
				);
				$result = $wpdb->query( $query );
				update_option( $wpdb->prefix . self::TABLE_RESUME . '_constraints', true );
				
				if ( $wpdb->last_error ) {
					self::log( sprintf( 'insert_state DB ERROR (retry): %s', $wpdb->last_error ), 'State' );
				} else {
					self::log( sprintf( 'insert_state SUCCESS (retry): Inserted %d row(s)', $result ), 'State' );
				}
			}
		} else {
			self::log( sprintf( 'insert_state SUCCESS: Inserted %d row(s)', $result ), 'State' );
		}
	}

	/**
	 * @return void
	 */
	private function add_course_lesson_columns() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_RESUME;
		
		if ( ! get_option( $table_name . '_course_lesson_columns', false ) ) {
			
			// Check if course_id column exists
			$column_exists = $wpdb->get_results( "SHOW COLUMNS FROM `$table_name` LIKE 'course_id'" );
			if ( empty( $column_exists ) ) {
				$wpdb->query( "ALTER TABLE `$table_name` ADD `course_id` BIGINT(20) UNSIGNED NULL DEFAULT 0;" );
			}
	
			// Check if lesson_id column exists
			$column_exists = $wpdb->get_results( "SHOW COLUMNS FROM `$table_name` LIKE 'lesson_id'" );
			if ( empty( $column_exists ) ) {
				$wpdb->query( "ALTER TABLE `$table_name` ADD `lesson_id` BIGINT(20) UNSIGNED NULL DEFAULT 0;" );
			}
			
			update_option( $table_name . '_course_lesson_columns', true );
		}
	}

	/**
	 * @return array
	 */
	private function fetch_course_lesson_ids() {
		// Get course and lesson id from request
		$auth      = null;
		$course_id = 0;
		$lesson_id = 0;
		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			parse_str( $_SERVER['HTTP_REFERER'], $referer );
			if ( strstr( $_SERVER['HTTP_REFERER'], '&client=' ) !== false ) {
				if ( ! empty( $referer['auth'] ) ) {
					$auth = $referer['auth'];
				}
			}
		}

		if ( empty( $auth ) ) {
			// Try to read all headers first.
			if ( function_exists( 'getallheaders' ) ) {
				$all_headers = getallheaders();
				if ( isset( $all_headers['Authorization'] ) ) {
					$auth = $all_headers['Authorization'];
				}
			}
		}

		if ( empty( $auth ) ) {
			$contents = file_get_contents( 'php://input' );
			$decoded  = json_decode( $contents, true );
			if ( ! is_array( $decoded ) ) {
				parse_str( $contents, $decoded_2 );
			}
			if ( isset( $decoded_2['Authorization'] ) ) {
				$auth = $decoded_2['Authorization'];
			}
		}

		if ( ! empty( $auth ) ) {
			$lesson_id = substr( $auth, 11 );
		}

		if ( empty( $lesson_id ) ) {
			$lesson_id = get_user_meta( self::$user_id, 'tincan_last_known_ld_module', true );
		}

		$this->lesson_id = $lesson_id;
		$course_id       = ultc_get_filter_var( 'course_id', '' );
		if ( empty( $course_id ) ) {
			$course_id = get_user_meta( self::$user_id, 'tincan_last_known_ld_course', true );
		}

		return array( $course_id, $lesson_id );
	}

	//phpcs:enable WordPress.DB.PreparedSQL
}
