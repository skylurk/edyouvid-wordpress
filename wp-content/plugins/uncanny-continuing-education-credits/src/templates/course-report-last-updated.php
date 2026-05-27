<?php
/**
 * Template to display the Course Report JSON Last Updated Details.
 *
 * @var CourseReport $course_report - The current instance of the uncanny_ceu/CourseReport class.
*/

namespace uncanny_ceu;

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<div class="ucec-report-last-updated">
	<p>
		<?php echo esc_html( $course_report->report_last_generated() ); ?>
		<?php
			printf(
				'<a href="%1$s" title="%2$s">%3$s</a>',
				esc_url( $course_report->get_refresh_url() ),
				esc_attr( __( 'Refresh report', 'uncanny-ceu' ) ),
				'<span class="dashicons dashicons-update-alt"></span>'
			);
			?>
	</p>
</div><!-- .ucec-report-last-updated -->
