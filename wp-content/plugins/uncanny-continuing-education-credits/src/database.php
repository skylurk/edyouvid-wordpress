<?php

namespace uncanny_ceu;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Database
 *
 * @package uncanny_ceu
 */
class Database {

	/**
	 * @return void
	 */
	public static function create_tables() {
		$sql = self::get_schema();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		update_option( 'ceu_database_version', (string) InitializePlugin::$ceu_db_version );
	}

	/**
	 * @return string
	 */
	public static function get_schema() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$tbl_course      = 'uo_ceu_historical_course_data';
		$tbl_quiz        = 'uo_ceu_historical_quiz_data';
		$tbl_static_ref  = 'uo_ceu_ld_pro_quiz_statistic_ref';
		$tbl_static      = 'uo_ceu_ld_pro_quiz_statistic';

		$tbl_ceus         = 'uo_ceu_records';
		$tbl_ceu_certs    = 'uo_ceu_records_certs';
		$tbl_ceu_usermeta = 'uo_ceu_records_usermeta';

		return "CREATE TABLE `{$wpdb->prefix}{$tbl_course}` (`ID` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11)  DEFAULT NULL,
`course_id` int(11)  DEFAULT NULL,
`progress` text  DEFAULT NULL,
`certificate` text  DEFAULT NULL,
`date_enrolled` varchar(15)  DEFAULT NULL,
`date_completed` varchar(15)  DEFAULT NULL,
`record_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`ID`),
KEY `user_id` (`user_id`),
KEY `course_id` (`course_id`)
) $charset_collate;
CREATE TABLE `{$wpdb->prefix}{$tbl_quiz}` (
`ID` int(11) NOT NULL AUTO_INCREMENT,
`user_id` int(11)  DEFAULT NULL,
`statistic_ref_id` int(11)  DEFAULT 0,
`course_id` int(11) DEFAULT NULL,
`lesson_id` int(11) DEFAULT NULL,
`topic_id` int(11)  DEFAULT NULL,
`quiz_id` int(11)  DEFAULT NULL,
`passed` int(1)  DEFAULT NULL,
`score` int(4)  DEFAULT NULL,
`count` int(4)  DEFAULT NULL,
`data` text  DEFAULT NULL,
`certificate` text  DEFAULT NULL,
`date_enrolled` varchar(15)  DEFAULT NULL,
`date_completed` varchar(15)  DEFAULT NULL,
`record_updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`ID`),
KEY `statistic_ref_id` (`statistic_ref_id`),
KEY `user_id` (`user_id`),
KEY `course_id` (`course_id`),
KEY `quiz_id` (`quiz_id`)
) $charset_collate;
CREATE TABLE `{$wpdb->prefix}{$tbl_static_ref}` (
`statistic_ref_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`quiz_id` int(11) NOT NULL,
`user_id` bigint(20) unsigned NOT NULL,
`create_time` int(11) NOT NULL,
`is_old` tinyint(1) unsigned NOT NULL,
`form_data` text COLLATE utf8mb4_unicode_520_ci,
`quiz_post_id` int(11) NOT NULL,
`course_post_id` int(11) NOT NULL,
PRIMARY KEY (`statistic_ref_id`),
KEY `quiz_id` (`quiz_id`,`user_id`),
KEY `time` (`create_time`)
)$charset_collate;
CREATE TABLE `{$wpdb->prefix}{$tbl_static}` (
`statistic_ref_id` int(10) unsigned NOT NULL,
`question_id` int(11) NOT NULL,
`correct_count` int(10) unsigned NOT NULL,
`incorrect_count` int(10) unsigned NOT NULL,
`hint_count` int(10) unsigned NOT NULL,
`points` int(10) unsigned NOT NULL,
`question_time` int(10) unsigned NOT NULL,
`answer_data` text COLLATE utf8mb4_unicode_520_ci,
`question_post_id` int(11) NOT NULL,
PRIMARY KEY (`statistic_ref_id`,`question_id`)
) $charset_collate;
CREATE TABLE `{$wpdb->prefix}{$tbl_ceus}` (
`ID` bigint(10) unsigned NOT NULL AUTO_INCREMENT,
`user_id` int(11) NOT NULL,
`course_id` int(10) NOT NULL,
`custom_course_id` varchar(200) DEFAULT NULL,
`credits` float NOT NULL DEFAULT '0',
`course_title` tinytext,
`date_earned` timestamp NOT NULL,
`earned_timestamp` int(11) NOT NULL,
`date_updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
`is_manual` int(1) NOT NULL DEFAULT '0',
PRIMARY KEY (`ID`),
KEY `course_id` (`course_id`),
KEY `user_id` (`user_id`),
KEY `earned_timestamp` (`earned_timestamp`),
KEY `custom_course_id` (`custom_course_id`),
KEY `course_title` (`course_title`(20)),
KEY `date_earned` (`date_earned`)
) $charset_collate;
CREATE TABLE `{$wpdb->prefix}{$tbl_ceu_certs}` (
`ID` int(10) NOT NULL AUTO_INCREMENT,
`ceu_ID` int(10) DEFAULT NULL,
`course_id` int(10) DEFAULT NULL,
`certificate_id` int(10) DEFAULT NULL,
`is_multi_cert` int(1) NOT NULL DEFAULT '0',
`date_earned` int(11) DEFAULT NULL,
PRIMARY KEY (`ID`),
KEY `ceu_ID` (`ceu_ID`),
KEY `course_id` (`course_id`)
) $charset_collate;
CREATE TABLE `{$wpdb->prefix}{$tbl_ceu_usermeta}` (
`ID` int(11) NOT NULL AUTO_INCREMENT,
`ceu_ID` int(10) DEFAULT NULL,
`meta_key` varchar(150) DEFAULT NULL,
`meta_value` text,
`date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`ID`),
KEY `ceu_ID` (`ceu_ID`)
) $charset_collate;";
	}
}




