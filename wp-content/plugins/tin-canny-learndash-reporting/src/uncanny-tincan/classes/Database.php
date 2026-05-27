<?php
/**
 * Database
 *
 * @since      1.0.0
 *
 * @author     Uncanny Owl
 * @package    Tin Canny Reporting for LearnDash
 * @subpackage TinCan Module
 *
 * To enable debug logging for TinCan state operations, add this to wp-config.php:
 * define( 'UOTC_DEBUG', true );
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
class Database {

	// Constants
	/**
	 *
	 */
	const TABLE_REPORTING = 'uotincan_reporting';
	/**
	 *
	 */
	const TABLE_QUIZ = 'uotincan_quiz';
	/**
	 *
	 */
	const TABLE_RESUME = 'uotincan_resume';

	/**
	 *
	 */
	const TABLE_REPORTING_USER_ID = 'tbl_reporting_api_user_id';

	/**
	 *
	 */
	const TABLE_SNC_FILE_INFO = 'snc_file_info';

	/**
	 *
	 */
	const TABLE_SNC_POST_RELATION = 'snc_post_relationship';

	// Static Values
	/**
	 * @var bool
	 */
	public static $upgraded = false; // Yes / No
	/**
	 * @var
	 */
	protected static $user_id;

	/**
	 * Debug logging utility
	 * Only logs when UOTC_DEBUG constant is defined and true
	 *
	 * @param string $message The message to log
	 * @param string $context Optional context prefix (e.g., 'State', 'Server')
	 *
	 * @return void
	 * @since  3.8
	 */
	public static function log( $message, $context = '' ) {
		if ( ! defined( 'UOTC_DEBUG' ) || ! UOTC_DEBUG ) {
			return;
		}

		$prefix = 'Tin Cannny';
		if ( ! empty( $context ) ) {
			$prefix .= ' ' . $context;
		}

		error_log( sprintf( '%s: %s', $prefix, $message ) );
	}

	/**
	 * @param $id
	 * @param $module_url
	 */
	public static function delete_bookmarks( $id, $module_url ) {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . self::TABLE_RESUME, array( 'module_id' => $id ), array( '%d' ) );
	}

	/**
	 * @param $id
	 * @param $module_url
	 */
	public static function delete_all_data( $id, $module_url ) {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . self::TABLE_REPORTING, array( 'module' => $module_url ), array( '%s' ) );
		$wpdb->delete( $wpdb->prefix . self::TABLE_RESUME, array( 'module_id' => $id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . self::TABLE_QUIZ, array( 'module' => $module_url ), array( '%s' ) );
	}

	/**
	 * @return void
	 */
	public static function upgrade() {
		$sql = self::get_schema();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Create tables
		$r = dbDelta( $sql );

		// Fix Constraints
		self::run_constraints();

		update_option( Init::TABLE_VERSION_KEY, UNCANNY_REPORTING_DB_VERSION, true );

		//if ( ! wp_next_scheduled( 'tincanny_run_activity_name_hash_migration_event' ) ) {
			//wp_schedule_event( time(), 'every_minute', 'tincanny_run_activity_name_hash_migration_event' );
		//}

		add_action( 'shutdown', array( __CLASS__, 'add_learndash_indexes' ) );
	}

	/**
	 * @return string
	 */
	public static function get_schema() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		return "CREATE TABLE {$wpdb->prefix}" . self::TABLE_REPORTING . " (
`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
`group_id` bigint(20) unsigned DEFAULT NULL,
`user_id` bigint(20) unsigned NOT NULL,
`course_id` bigint(20) unsigned DEFAULT NULL,
`lesson_id` bigint(20) unsigned DEFAULT NULL,
`module` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
`module_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
`target` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
`target_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
`verb` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
`result` int(7) DEFAULT NULL,
`minimum` int(7) DEFAULT NULL,
`completion` tinyint(1) DEFAULT NULL,
`xstored` datetime DEFAULT NULL,
`user_response` text NOT NULL,
`correct_response` text NOT NULL,
`available_responses` text NOT NULL,
`max_score` DECIMAL(4,2) NOT NULL,
`min_score` DECIMAL(4,2) NOT NULL,
`raw_score` DECIMAL(4,2) NOT NULL,
`scaled_score` DECIMAL(4,2) NOT NULL,
`duration` INT(8) NOT NULL,
PRIMARY KEY (`id`),
KEY `{$wpdb->prefix}idx_xstored_user_course` (`xstored`, `user_id`, `course_id`),
KEY `{$wpdb->prefix}_fk_TinCanUser` (`user_id`),
KEY `{$wpdb->prefix}_fk_TinCanGroup` (`group_id`),
KEY `{$wpdb->prefix}_fk_TinCanCourse` (`course_id`),
KEY `{$wpdb->prefix}_fk_TinCanLesson` (`lesson_id`),
KEY `verb` (`verb`)
) ENGINE=InnoDB $charset_collate;
CREATE TABLE {$wpdb->prefix}" . self::TABLE_RESUME . " (
`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
`user_id` bigint(20) unsigned NOT NULL,
`course_id` bigint(20) unsigned DEFAULT NULL,
`lesson_id` bigint(20) unsigned DEFAULT NULL,
`module_id` bigint(20) NOT NULL,
`state` varchar(50) DEFAULT NULL,
`value` text,
PRIMARY KEY (`id`),
KEY `{$wpdb->prefix}_fk_Resume_User` (`user_id`),
KEY `{$wpdb->prefix}_fk_Resume_Module` (`module_id`)
) ENGINE=InnoDB $charset_collate;
CREATE TABLE {$wpdb->prefix}" . self::TABLE_QUIZ . " (
`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
`group_id` bigint(20) unsigned DEFAULT NULL,
`user_id` bigint(20) unsigned NOT NULL,
`course_id` bigint(20) unsigned DEFAULT NULL,
`lesson_id` bigint(20) unsigned DEFAULT NULL,
`module` varchar(255) DEFAULT NULL,
`module_name` TEXT,
`activity_id` varchar(255) DEFAULT NULL,
`activity_name` TEXT,
`activity_name_hash` CHAR(32) DEFAULT NULL,
`result` int(7) DEFAULT NULL,
`max_score` decimal(4,2) unsigned DEFAULT '0.00',
`min_score` decimal(4,2) unsigned DEFAULT '0.00',
`raw_score` decimal(4,2) unsigned DEFAULT '0.00',
`scaled_score` decimal(4,2) unsigned DEFAULT '0.00',
`correct_response` varchar(255) DEFAULT NULL,
`available_responses` varchar(255) DEFAULT NULL,
`user_response` text COLLATE utf8_unicode_ci,
`xstored` datetime DEFAULT NULL,
`duration` int(8) NOT NULL,
PRIMARY KEY (`id`),
KEY `{$wpdb->prefix}_fk_TinCanQuizUser` (`user_id`),
KEY `{$wpdb->prefix}_fk_TinCanQuizGroup` (`group_id`),
KEY `{$wpdb->prefix}_fk_TinCanQuizCourse` (`course_id`),
KEY `{$wpdb->prefix}_fk_TinCanQuizLesson` (`lesson_id`)
) ENGINE=InnoDB $charset_collate;
CREATE TABLE {$wpdb->prefix}" . self::TABLE_REPORTING_USER_ID . " (
`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
`user_id` bigint(20) NOT NULL,
`group_leader_id` int(20) NOT NULL,
PRIMARY KEY (`id`),
KEY `idx_user_group` (`user_id`,`group_leader_id`)
) ENGINE=InnoDB $charset_collate;
CREATE TABLE {$wpdb->prefix}" . self::TABLE_SNC_FILE_INFO . " (
`ID` bigint(20) NOT NULL AUTO_INCREMENT,
`file_name` text NOT NULL,
`type` varchar(15) NOT NULL,
`url` longtext NOT NULL,
`subtype` varchar(15) NOT NULL,
`version` varchar(15) NOT NULL,
`size` bigint(20) NOT NULL,
`upload_date` datetime NOT NULL,
PRIMARY KEY (`ID`)
) ENGINE=InnoDB $charset_collate;
CREATE TABLE {$wpdb->prefix}" . self::TABLE_SNC_POST_RELATION . " (
`ID` bigint(20) NOT NULL AUTO_INCREMENT,
`snc_id` bigint(20) NOT NULL,
`post_id` bigint(20) UNSIGNED NOT NULL,
PRIMARY KEY (`ID`),
KEY `{$wpdb->prefix}_fk_TinCanny_Module_Relation_Module` (`snc_id`),
KEY `{$wpdb->prefix}_fk_TinCanny_Module_Relation_Post` (`post_id`)
) ENGINE=InnoDB $charset_collate;";
	}

	/**
	 * @return void
	 */
	public static function run_constraints() {
		global $wpdb;
		$constraints = self::get_constraints();
		// Create index on usermeta table
		$wpdb->query( "ALTER TABLE {$wpdb->usermeta} ADD INDEX idx_user_meta (user_id, meta_key(255));" );

		foreach ( $constraints as $constraint ) {
			// Extract the constraint name
			preg_match( '/ADD CONSTRAINT `([^`]+)`/', $constraint, $matches );
			if ( isset( $matches[1] ) ) {
				$constraint_name = $matches[1];

				// Check if the constraint already exists
				$table_name = self::get_table_name_from_constraint( $constraint );
				$query      = $wpdb->prepare(
					'SELECT COUNT(*)
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = %s
                AND TABLE_NAME = %s
                AND CONSTRAINT_NAME = %s',
					DB_NAME,
					$wpdb->prefix . $table_name,
					$constraint_name
				);

				$exists = $wpdb->get_var( $query );

				// Add constraint only if it does not exist
				if ( $exists == 0 ) {
					$wpdb->query( $constraint );
				}
			}
		}
	}

	/**
	 * dbDelta can't handle foreign key constraints, so we need to create them manually.
	 *
	 * @return array
	 */
	public static function get_constraints() {
		global $wpdb;

		return array(

			"ALTER TABLE {$wpdb->prefix}" . self::TABLE_REPORTING . "
        ADD CONSTRAINT `{$wpdb->prefix}_fk_TinCanUser` FOREIGN KEY (`user_id`) REFERENCES `{$wpdb->users}` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE",

			"ALTER TABLE {$wpdb->prefix}" . self::TABLE_REPORTING . "
        ADD CONSTRAINT `{$wpdb->prefix}_fk_TinCanGroup` FOREIGN KEY (`group_id`) REFERENCES `{$wpdb->posts}` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE",

			"ALTER TABLE {$wpdb->prefix}" . self::TABLE_REPORTING . "
        ADD CONSTRAINT `{$wpdb->prefix}_fk_TinCanCourse` FOREIGN KEY (`course_id`) REFERENCES `{$wpdb->posts}` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE",

			"ALTER TABLE {$wpdb->prefix}" . self::TABLE_REPORTING . "
        ADD CONSTRAINT `{$wpdb->prefix}_fk_TinCanLesson` FOREIGN KEY (`lesson_id`) REFERENCES `{$wpdb->posts}` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE",

			"ALTER TABLE {$wpdb->prefix}" . self::TABLE_RESUME . "
        ADD CONSTRAINT `{$wpdb->prefix}_fk_Resume_User` FOREIGN KEY (`user_id`) REFERENCES `{$wpdb->users}` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE",

			"ALTER TABLE {$wpdb->prefix}" . self::TABLE_RESUME . "
        ADD CONSTRAINT `{$wpdb->prefix}_fk_Resume_Module` FOREIGN KEY (`module_id`) REFERENCES `{$wpdb->prefix}" . self::TABLE_SNC_FILE_INFO . '` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE',

			"ALTER TABLE {$wpdb->prefix}" . self::TABLE_QUIZ . "
        ADD CONSTRAINT `{$wpdb->prefix}_fk_TinCanQuizUser` FOREIGN KEY (`user_id`) REFERENCES `{$wpdb->users}` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE",

			"ALTER TABLE {$wpdb->prefix}" . self::TABLE_QUIZ . "
        ADD CONSTRAINT `{$wpdb->prefix}_fk_TinCanQuizGroup` FOREIGN KEY (`group_id`) REFERENCES `{$wpdb->posts}` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE",

			"ALTER TABLE {$wpdb->prefix}" . self::TABLE_QUIZ . "
        ADD CONSTRAINT `{$wpdb->prefix}_fk_TinCanQuizCourse` FOREIGN KEY (`course_id`) REFERENCES `{$wpdb->posts}` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE",

			"ALTER TABLE {$wpdb->prefix}" . self::TABLE_QUIZ . "
        ADD CONSTRAINT `{$wpdb->prefix}_fk_TinCanQuizLesson` FOREIGN KEY (`lesson_id`) REFERENCES `{$wpdb->posts}` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE",

			"ALTER TABLE {$wpdb->prefix}" . self::TABLE_SNC_POST_RELATION . "
        CONSTRAINT `{$wpdb->prefix}_fk_TinCanny_Module_Relation_Module` FOREIGN KEY(`snc_id`)  REFERENCES $wpdb->prefix" . self::TABLE_SNC_FILE_INFO . ' (`ID`) ON UPDATE CASCADE ON DELETE CASCADE',

			"ALTER TABLE {$wpdb->prefix}" . self::TABLE_SNC_POST_RELATION . "
        CONSTRAINT `{$wpdb->prefix}_fk_TinCanny_Module_Relation_Post`   FOREIGN KEY(`post_id`) REFERENCES `{$wpdb->posts}`(`ID`) ON UPDATE CASCADE ON DELETE CASCADE",

			"ALTER TABLE {$wpdb->prefix}" . self::TABLE_REPORTING_USER_ID . "
DROP INDEX user_id;",
		);
	}

	/**
	 * @return void
	 */
	public static function add_learndash_indexes() {

		if ( ! class_exists( '\LDLMS_DB' ) ) {
			return;
		}

		global $wpdb;

		// Create index on learndash_user_activity table
		$wpdb->query( "ALTER TABLE " . \LDLMS_DB::get_table_name( 'user_activity' ) . "
	ADD INDEX idx_user_activity (user_id, activity_type, activity_completed, activity_started, activity_updated);" );

		// Create index on learndash_user_activity table
		$wpdb->query( "ALTER TABLE " . \LDLMS_DB::get_table_name( 'user_activity_meta' ) . "
	ADD INDEX idx_activity_meta (activity_id, activity_meta_key(191), activity_meta_value(191));" );

	}

	/**
	 * @param $constraint
	 *
	 * @return string
	 */
	private static function get_table_name_from_constraint( $constraint ) {
		if ( strpos( $constraint, self::TABLE_REPORTING ) !== false ) {
			return self::TABLE_REPORTING;
		} elseif ( strpos( $constraint, self::TABLE_RESUME ) !== false ) {
			return self::TABLE_RESUME;
		} elseif ( strpos( $constraint, self::TABLE_QUIZ ) !== false ) {
			return self::TABLE_QUIZ;
		}

		return '';
	}

	/**
	 * Set Report
	 *
	 * @access public
	 *
	 * @param mixed ...$args
	 *
	 * @return bool
	 * @since  1.0.0
	 */
	public function set_report( ...$args ) {

		list(
			$group_id,
			$course_id,
			$lesson_id,
			$module,
			$module_name,
			$target,
			$target_name,
			$verb,
			$result,
			$maximum,
			$completion,
			$user_id
			) = $args;

		if ( ! $this->set_user_id() ) {
			return false;
		}

		// Options - Restrict for group leader
		$show_tincan = get_option( 'show_tincan_reporting_tables', 'yes' );
		if ( 'no' === $show_tincan ) {
			return false;
		}
		global $wpdb;

		if ( ! $group_id ) {
			$group_id = 'NULL';
		} else {
			$group_id = absint( $group_id );
		}

		if ( ! $course_id ) {
			$course_id = 'NULL';
		} else {
			$course_id = absint( $course_id );
			// Even if course id is integer but need a valid post id.
			$course = get_post( $course_id );
			if ( $course === null ) {
				$course_id = 'NULL';
			}
		}

		if ( ! $lesson_id ) {
			$lesson_id = 'NULL';
		} else {
			$lesson_id = absint( $lesson_id );
			// Even if lesson id is integer but need a valid post id.
			$lesson = get_post( $lesson_id );
			if ( $lesson === null ) {
				$lesson_id = 'NULL';
			}
		}

		if ( $result === false ) {
			$result = 'NULL';
		} else {
			$result = (int) $result;
		}

		if ( $maximum === false ) {
			$maximum = 'NULL';
		} else {
			$maximum = (int) $maximum;
		}

		if ( $completion === false ) {
			$completion = 'NULL';
		} else {
			$completion = (string) $completion;
		}

		$table = self::TABLE_REPORTING;
		$now   = current_time( 'mysql' );

		$query = $wpdb->query(
			$wpdb->prepare(
				"
		INSERT INTO $wpdb->prefix{$table}
				( `group_id`, `user_id`, `course_id`, `lesson_id`, `module`, `module_name`, `target`, `target_name`, `verb`, `result`, `minimum`, `completion`, `xstored`)
				VALUES ( {$group_id}, %d, {$course_id}, {$lesson_id}, %s, %s, %s, %s, %s, {$result}, {$maximum}, '{$completion}', '{$now}' )
			",
				( $user_id ) ? absint($user_id) : absint(self::$user_id),
				$module,
				$module_name,
				$target,
				$target_name,
				$verb
			)
		);

		$user_id = ( $user_id ) ? $user_id : self::$user_id;

		$module_match = $this->get_slide_id_from_module( $module );
		if ( isset( $module_match[1] ) ) {
			$module_id = $module_match[1];
			do_action( 'tincanny_module_completed', $module_id, $user_id, $verb );
		}

		return true;
	}

	/**
	 * @param $url
	 *
	 * @return string[]
	 */
	function get_slide_id_from_module( $url ) {
		preg_match( '/\/uncanny-snc\/([0-9]+)\//', $url, $matches );

		return $matches;
	}

	/**
	 * Set Report
	 *
	 * @access public
	 *
	 * @param mixed ...$args
	 *
	 * @return bool
	 * @since  1.0.0
	 */
	public function set_quiz_data( ...$args ) {

		list(
			$group_id,
			$course_id,
			$lesson_id,
			$module,
			$module_name,
			$activity,
			$activity_name,
			$result,
			$user_id,
			$available_responses,
			$correct_response,
			$user_response,
			$max_score,
			$min_score,
			$raw_score,
			$scaled_score,
			$duration
			) = $args;

		if ( is_null( $user_id ) ) {
			$user_id = 0;
		}

		// Options - Restrict for group leader
		$show_tincan = get_option( 'show_tincan_quiz_tables', 'yes' );

		global $wpdb;

		if ( ! $group_id ) {
			$group_id = 'NULL';
		} else {
			$group_id = absint( $group_id );
		}

		if ( ! $course_id ) {
			$course_id = 'NULL';
		} else {
			$course_id = absint( $course_id );
			// Even if course id is integer but need a valid post id.
			$course = get_post( $course_id );
			if ( $course === null ) {
				$course_id = 'NULL';
			}
		}

		if ( ! $lesson_id ) {
			$lesson_id = 'NULL';
		} else {
			$lesson_id = absint( $lesson_id );
			// Even if lesson id is integer but need a valid post id.
			$lesson = get_post( $lesson_id );
			if ( $lesson === null ) {
				$lesson_id = 'NULL';
			}
		}

		if ( $result === false ) {
			$result = 'NULL';
		} else {
			$result = (int) $result;
		}

		if ( $min_score === false ) {
			$min_score = 'NULL';
		}

		if ( $max_score === false ) {
			$max_score = 0;
		}

		if ( $scaled_score === false ) {
			$scaled_score = 'NULL';
		}

		if ( $raw_score === false ) {
			$raw_score = 'NULL';
		}

		$table = self::TABLE_QUIZ;
		$now   = current_time( 'mysql' );

		$activity_name_hash = md5( sanitize_title( $activity_name ) );

		$query = $wpdb->query(
			$wpdb->prepare(
				"
		INSERT INTO $wpdb->prefix{$table}
				(
				 `group_id`,
				 `user_id`,
				 `course_id`,
				 `lesson_id`,
				 `module`,
				 `module_name`,
				 `activity_id`,
				 `activity_name`,
				 `result`,
				 `xstored`,
				 `user_response`,
				 `correct_response`,
				 `available_responses`,
				 `max_score`,
				 `min_score`,
				 `raw_score`,
				 `scaled_score`,
				 `duration`,
				 `activity_name_hash`
				 )
				VALUES (
				        {$group_id},
				        %d,
				        {$course_id},
				        {$lesson_id},
				        %s,
				        %s,
				        %s,
				        %s,
				        {$result},
				        '{$now}',
				        %s,
				        %s,
				        %s,
				        %s,
				        %s,
				        %s,
				        %s,
				        %s,
				        %s
				        )
			",
				( $user_id ) ? $user_id : self::$user_id,
				$module,
				$module_name,
				$activity,
				$activity_name,
				$user_response,
				$correct_response,
				$available_responses,
				$max_score,
				$min_score,
				$raw_score,
				$scaled_score,
				$duration,
				$activity_name_hash
			)
		);

		return true;
	}

	/**
	 * Set User ID
	 *
	 * @access protected
	 * @return int
	 * @since  1.0.0
	 */
	protected function set_user_id() {
		if ( ! self::$user_id ) {
			self::$user_id = get_current_user_id();
		}

		return self::$user_id;
	}

	public static function get_unique_verbs() {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_REPORTING;
		$query = "SELECT distinct(verb) FROM ".$table;
		$verbs = $wpdb->get_col( $query );
		return apply_filters( 'uotc_unique_verbs', $verbs );
	}
}
