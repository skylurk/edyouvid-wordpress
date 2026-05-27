<?php
/**
 * Template to display the Admin Course Report Page.
 *
 * @var CourseReport $course_report - The current instance of the uncanny_ceu/CourseReport class.
*/

namespace uncanny_ceu;

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<div class="wrap">

	<div class="ucec ucec-report">
		<?php
		// Add admin header and tabs.
		$tab_active = 'uncanny-ceu-report';
		require Utilities::get_template( 'admin-header.php' );

		// Add Toolbar
		require Utilities::get_template( 'course-report-toolbar.php' );

		// Add Last Updated details.
		if ( 'json' === $course_report->get_mode() ) {
			require Utilities::get_template( 'course-report-last-updated.php' );
		}

		// Add Results Table.
		require Utilities::get_template( 'course-report-table.php' );
		?>
	</div><!-- .ucec.ucec-report -->

</div><!-- .wrap -->
