<?php
/**
 * Block Name: Tin Canny Lesson Report
 *
 * @package blocks
 */

/**
 * Register Tin Canny Lesson Report and
 * render it with a callback function
 */
register_block_type(
	'tincanny/lesson-report',
	array(
		'render_callback' => 'render_uotc_lesson_report',
	)
);

/**
 * Render Tin Canny Lesson Report
 *
 * @return string
 */
function render_uotc_lesson_report() {
	$output = '';
	if ( class_exists( '\uncanny_learndash_reporting\LessonTopicReports' ) ) {
		$output = new \uncanny_learndash_reporting\LessonTopicReports();
		$output = $output->lesson_report_shortcode();
	}
	return $output;
}
