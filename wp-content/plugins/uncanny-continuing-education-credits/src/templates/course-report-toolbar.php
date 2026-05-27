<?php
/**
 * Template to display the Course Report Toolbar.
 *
 * @var CourseReport $course_report - The current instance of the uncanny_ceu/CourseReport class.
*/

namespace uncanny_ceu;

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<div class="ucec-report-toolbar">

	<div class="ucec-report-filters">

		<form method="GET">

			<?php if ( ! empty( $course_report->get_url_variable( 'page' ) ) ) : ?>
				<input type="hidden" value="<?php echo esc_attr( $course_report->get_url_variable( 'page' ) ); ?>" name="page">
			<?php endif; ?>

			<div class="ucec-report-filter ucec-report-filter--dates">
				<input type="hidden" name="start_date" id="ucec-date-range-custom-start"
					value="<?php echo esc_attr( $course_report->get_url_variable( 'start_date' ) ); ?>">
				<input type="hidden" name="end_date" id="ucec-date-range-custom-end"
					value="<?php echo esc_attr( $course_report->get_url_variable( 'end_date' ) ); ?>">
				<select name="date" id="ucec-date-range" class="ucec-report-select ucec-report-filter__select">
					<?php echo wp_kses( $course_report->get_date_dropdown(), $course_report->get_allowed_select_html() ); ?>
				</select>
			</div><!-- .ucec-report-filter.ucec-report-filter--dates -->

			<div class="ucec-report-filter ucec-report-filter--users">
				<select name="user" id="ucec-users" class="ucec-report-select ucec-report-filter__select">
				<?php echo wp_kses( $course_report->get_users_dropdown(), $course_report->get_allowed_select_html() ); ?>
				</select>
			</div><!-- .ucec-report-filter.ucec-report-filter--users -->

			<div class="ucec-report-filter ucec-report-filter--groups">
				<?php echo wp_kses( $course_report->get_groups_dropdown(), $course_report->get_allowed_select_html() ); ?>
			</div><!-- .ucec-report-filter.ucec-report-filter--groups -->

			<div class="ucec-report-filter ucec-report-filter--courses">
				<select name="course" id="ucec-courses" class="ucec-report-select ucec-report-filter__select">
					<?php echo wp_kses( $course_report->get_courses_dropdown(), $course_report->get_allowed_select_html() ); ?>
				</select>
			</div><!-- .ucec-report-filter.ucec-report-filter--courses -->

			<div class="ucec-report-filter ucec-report-filter--submit">
				<button type="submit" class="ucec-btn ucec-btn--primary">
					<?php esc_html_e( 'Filter', 'uncanny-ceu' ); ?>
				</button>
				<button onclick="location.href='<?php echo esc_url( $course_report->get_url() ); ?>'"
						type="button" class="ucec-btn ucec-btn--secondary">
					<?php esc_html_e( 'Reset', 'uncanny-ceu' ); ?>
				</button>
			</div><!-- .ucec-report-filter.ucec-report-filter--submit -->

		</form><!-- .ucec-report-filters__form -->

	</div><!-- .ucec-report-filters -->

	<div class="ucec-report-actions">
		<div id="ucec-report-export-csv" class="ucec-btn ucec-btn--primary">
			<?php esc_html_e( 'Export CSV', 'uncanny-ceu' ); ?>
		</div>
		<div id="ucec-report-export-excel" class="ucec-btn ucec-btn--primary">
			<?php esc_html_e( 'Export Excel', 'uncanny-ceu' ); ?>
		</div>
	</div><!-- .ucec-report-actions -->

</div><!-- .ucec-report-toolbar -->
