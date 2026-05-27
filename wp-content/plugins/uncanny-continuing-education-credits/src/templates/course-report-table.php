<?php
/**
 * Template to display the Course Report Table.
 *
 * @var CourseReport $course_report - The current instance of the uncanny_ceu/CourseReport class.
*/

namespace uncanny_ceu;

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<div class="ucec-report-results">

	<table id="ucec-course-report" class="ucec-report-results__table"
		data-csvfilename="<?php echo esc_attr( $course_report->get_csv_filename() ); ?>">
		<thead class="ucec-report-results-table__thead">

			<tr class="ucec-report-results-table__tr">
				<?php $report_columns = $course_report->get_report_columns(); ?>
				<?php foreach ( $report_columns as $k => $config ) : ?>
					<th class="ucec-report-header-cell ucec-report-header-cell--<?php echo esc_attr( $config['name'] ); ?>">
						<?php echo esc_html( $config['title'] ); ?>
					</th>
				<?php endforeach; ?>
			</tr>

		</thead><!-- .ucec-report-results-table__thead -->

		<tbody class="ucec-report-results-table__tbody">
		</tbody><!-- .ucec-report-results-table__tbody -->

	</table><!-- #ucec-course-report -->

</div><!-- .ucec-report-results -->
