<?php
/**
 * Uninstall file, which would delete all user metadata and configuration settings
 *
 * @since 1.0
 */
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit();

global $wpdb;

$wpdb->query('DELETE FROM $wpdb->options WHERE option_name ="ld_retake_quiz_options";');
$wpdb->query('DELETE FROM $wpdb->options WHERE option_name ="ld_retake_quiz_version";');

$wpdb->query('DELETE FROM $wpdb->options WHERE option_name ="ld_retakequiz_course";');
$wpdb->query('DELETE FROM $wpdb->options WHERE option_name ="ld_retakequiz_lesson";');
$wpdb->query('DELETE FROM $wpdb->options WHERE option_name ="ld_retakequiz_topic";');
$wpdb->query('DELETE FROM $wpdb->options WHERE option_name ="ld_retakequiz_quizes";');
$wpdb->query('DELETE FROM $wpdb->options WHERE option_name ="ld_retakequiz_categories";');
$wpdb->query('DELETE FROM $wpdb->options WHERE option_name ="ld_retakequiz_tags";');
$wpdb->query('DELETE FROM $wpdb->options WHERE option_name ="ld_retakequiz_exclude_quizzes";');
$wpdb->query('DELETE FROM $wpdb->options WHERE option_name ="ld_retakequiz_exclude_users";');
$wpdb->query('DELETE FROM $wpdb->options WHERE option_name ="ld_retakequiz_delay";');
$wpdb->query('DELETE FROM $wpdb->options WHERE option_name ="ld_retakequiz_delay_type";');
$wpdb->query('DELETE FROM $wpdb->options WHERE option_name ="ld_retake_negative_marking";');
