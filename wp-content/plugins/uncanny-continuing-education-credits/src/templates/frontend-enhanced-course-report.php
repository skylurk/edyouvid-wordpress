<?php
/**
 * Template to display the Frontend Enhanced Course Report Shortcode.
 *
 * @var CourseReport $course_report - The current instance of the uncanny_ceu/CourseReport class.
*/

namespace uncanny_ceu;

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<div class="ucec ucec-report ucec-enhanced-course-report">
	<?php
	// Add Toolbar
	require Utilities::get_template( 'course-report-toolbar.php' );

	// Add Last Updated details.
	if ( 'json' === $course_report->get_mode() ) {
		require Utilities::get_template( 'course-report-last-updated.php' );
	}

	// Add Table
	require Utilities::get_template( 'course-report-table.php' );
	?>
</div><!-- .ucec.ucec-report -->

