<?php
/**
 * External methods which were used in LD Retake Quiz's "WpProQuiz_Controller_Statistics" version
 */

/**
 * Get points from LD AQ
 *
 * @param $statistic_ref_id
 * @param $statistic WpProQuiz_Model_Statistic
 * @return string
 */
if(!function_exists('ld_aq_get_statistics_points')) {
	function ld_aq_get_statistics_points( $statistic_ref_id, $pro_question_id ) {

		$ld_retake_quiz_settings = get_option( 'lrtq_enable_disable_features', array() );

		//If retake quiz negative points enabled
		$negative_marking_enabled = ( isset( $ld_retake_quiz_settings['enable_allow_negative_marking'] ) && $ld_retake_quiz_settings['enable_allow_negative_marking'] == 'on' ) ? true : false;

		if ( $negative_marking_enabled ) {
			if ( class_exists( 'Learndash_Advanced_Quiz' ) ) {
				//If advanced quiz negative points enabled
				$negative_marking_enabled = get_option( 'ld_retake_negative_marking', 'no' ) == 'yes';
			}
		}

		global $wpdb;

		$quiz_statistic_table = LDLMS_DB::get_table_name( 'quiz_statistic' );

		$query = "SELECT * FROM {$quiz_statistic_table} WHERE statistic_ref_id=%d AND question_id=%d";

		$row    = $wpdb->get_row( $wpdb->prepare( $query, $statistic_ref_id, $pro_question_id ), ARRAY_A );
		$points = intval( $row['points'] );

		if ( $negative_marking_enabled ) {
			if ( isset( $row['ld_aq_points'] ) ) {
				$ld_aq_points = intval( $row['ld_aq_points'] );
			}

			if ( ! empty( $ld_aq_points ) ) {
				$points = $ld_aq_points;
			}
		}

		return $points;
	}
}

/**
 * Save points ld_aq_points column, to be used with negative marking
 *
 * @param $statistic_ref_id
 * @param $statistic_models
 */
if(!function_exists('ld_aq_save_statistics_points')) {
	function ld_aq_save_statistics_points( $statistic_ref_id, $statistic_models ) {

		global $wpdb;

		foreach ( $statistic_models as $statistic ) {
			$table       = LDLMS_DB::get_table_name( 'quiz_statistic' );
			$question_id = $statistic->getQuestionId();
			$points      = $statistic->getPoints();

			$wpdb->update( $table, array(
				'ld_aq_points' => $points
			), array(
				'statistic_ref_id' => $statistic_ref_id,
				'question_id'      => $question_id
			), array(
				'%d'
			), array(
				'%d',
				'%d'
			) );
		}
	}
}

if(!function_exists('get_server_information')) {
function get_server_information() {
		
	return array(
        'PHP Version' 			=> phpversion(),
        'PHP OS' 				=> PHP_OS,
        'PHP OS Family' 		=> php_uname('s'),
        'max_execution_time' 	=> ini_get('max_execution_time'),
        'max_file_uploads' 		=> ini_get('max_file_uploads'),
        'max_input_time' 		=> ini_get('max_input_time'),
        'max_input_vars' 		=> ini_get('max_input_vars'),
        'post_max_size' 		=> ini_get('post_max_size'),
        'upload_max_filesize' 	=> ini_get('upload_max_filesize'),
        'curl Version' 			=> curl_version()['version'],
        'SSL Version' 			=> curl_version()['ssl_version'],
        'Libz Version' 			=> curl_version()['libz_version'],
        'Protocols' 			=> implode(', ', curl_version()['protocols']),
        'mbstring' 				=> extension_loaded('mbstring') ? 'Yes' : 'No',
        'WordPress Version' 	=> get_bloginfo('version'),
        'WordPress Home URL' 	=> home_url(),
        'WordPress Site URL' 	=> site_url(),
        'Is Multisite' 			=> is_multisite() ? 'Yes' : 'No',
        'Site Language' 		=> get_locale(),
        'Object Cache' 			=> defined('WP_CACHE') && WP_CACHE ? 'Yes' : 'No',
        'DISABLE_WP_CRON' 		=> defined('DISABLE_WP_CRON') && DISABLE_WP_CRON ? 'Yes' : 'No',
        'WP_DEBUG' 				=> defined('WP_DEBUG') && WP_DEBUG ? 'Yes' : 'No',
        'WP_DEBUG_DISPLAY' 		=> defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY ? 'Yes' : 'No',
        'SCRIPT_DEBUG' 			=> defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? 'Yes' : 'No',
        'WP_DEBUG_LOG' 			=> defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'Yes' : 'No',
        'WP_PLUGIN_DIR' 		=> WP_PLUGIN_DIR,
        'WP_MAX_MEMORY_LIMIT' 	=> WP_MAX_MEMORY_LIMIT,
        'WP_MEMORY_LIMIT' 		=> WP_MEMORY_LIMIT,
    );	
}
}