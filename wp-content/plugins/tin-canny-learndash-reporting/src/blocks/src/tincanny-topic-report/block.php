<?php
/**
 * Block Name: Tin Canny Topic Report
 *
 * @package blocks
 */

/**
 * Register Tin Canny Topic Report and
 * render it with a callback function
 */
register_block_type(
	'tincanny/topic-report',
	array(
		'render_callback' => 'render_uotc_topic_report',
	)
);

/**
 * Render Tin Canny Topic Report
 *
 * @return string
 */
function render_uotc_topic_report() {
	$output = '';

	if ( class_exists( '\uncanny_learndash_reporting\LessonTopicReports' ) ) {
		$output = new uncanny_learndash_reporting\LessonTopicReports();
		$output = $output->topic_report_shortcode();
	}

	return $output;
}
