<?php
namespace uncanny_learndash_reporting;

if ( ! defined( 'WPINC' ) ) {
	die;
}

$tc_group_filter           = absint( ultc_get_filter_var( 'tc_filter_group', 0 ) );
$tc_course_filter          = absint( ultc_get_filter_var( 'tc_filter_course', 0 ) );
$tc_quiz_filter            = ultc_get_filter_var( 'tc_filter_quiz', '' );
$tc_results_filter         = strtolower( ultc_get_filter_var( 'tc_filter_results', '' ) );
$tc_filter_date_range      = ultc_get_filter_var( 'tc_filter_date_range', '' );
$tc_filter_date_range_last = ultc_get_filter_var( 'tc_filter_date_range_last', '' );

?>

<div class="reporting-tincan-filters">
	<form action="<?php echo esc_attr( remove_query_arg( 'paged' ) ); ?>" id="xapi-filters-top">
		<div class="reporting-metabox">
			<div class="reporting-dashboard-col-heading" id="coursesOverviewTableHeading">
				<?php esc_html_e( 'Filters', 'uncanny-learndash-reporting' ); ?>
			</div>
			<div class="reporting-dashboard-col-content">
				<?php if ( is_admin() ) { ?>
					<input type="hidden" name="page"
						value="<?php echo esc_attr( ! empty( ultc_get_filter_var( 'page', '' ) ) ? ultc_filter_input( 'page' ) : 1 ); ?>"/>
				<?php } ?>
				<input type="hidden" name="tc_filter_mode" value="list"/>
				<input type="hidden" name="tab" value="xapi-tincan"/>

				<input type="hidden" name="orderby"
					value="<?php echo esc_attr( ! empty( ultc_get_filter_var( 'orderby', '' ) ) ? ultc_filter_input( 'orderby' ) : 'date-time' ); ?>"/>
				<input type="hidden" name="order"
					value="<?php echo esc_attr( ! empty( ultc_get_filter_var( 'order', '' ) ) ? ultc_filter_input( 'order' ) : 'desc' ); ?>"/>

				<div class="reporting-tincan-filters-columns">
					<div class="reporting-tincan-filters-col reporting-tincan-filters-col--1">
						<div class="reporting-tincan-section__title">
							<?php esc_html_e( 'User & Group', 'uncanny-learndash-reporting' ); ?>
						</div>
						<div class="reporting-tincan-section__content">
							<div class="reporting-tincan-section__field">
								<label for="tcx_filter_group"><?php echo esc_html( \LearnDash_Custom_Label::get_label( 'group' ) ); ?></label>
								<select name="tc_filter_group" id="tcx_filter_group">
									<option value="">
									<?php
										echo esc_html(
											sprintf(
												/* translators: %s: Group label */
												__( 'All %s', 'uncanny-learndash-reporting' ),
												\LearnDash_Custom_Label::get_label( 'groups' )
											)
										);
										?>
									</option>
									<?php foreach ( $ld_groups as $group ) { ?>
										<?php $tc_group__selected = ! empty( $tc_group_filter ) && $tc_group_filter === (int) $group['group_id'] ? ' selected="selected"' : ''; ?>
										<option value="<?php echo esc_attr( $group['group_id'] ); ?>"<?php echo esc_attr( $tc_group__selected ); ?>>
											<?php echo esc_html( $group['group_name'] ); ?>
										</option>
									<?php } // foreach( $ld_groups ) ?>
								</select>

							</div>

							<div class="reporting-tincan-section__field">
								<label for="tcx_filter_user"><?php esc_html_e( 'User', 'uncanny-learndash-reporting' ); ?></label>
								<input name="tc_filter_user" id="tcx_filter_user"
									placeholder="<?php esc_html_e( 'User', 'uncanny-learndash-reporting' ); ?>"
									value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_user', '' ) ); ?>"/>
							</div>
						</div>
					</div>

					<div class="reporting-tincan-filters-col reporting-tincan-filters-col--2">

						<div class="reporting-tincan-section__title">
							<?php esc_html_e( 'Content', 'uncanny-learndash-reporting' ); ?>
						</div>
						<div class="reporting-tincan-section__content">
							<div class="reporting-tincan-section__field">
								<label for="tcx_filter_course"><?php echo esc_html( \LearnDash_Custom_Label::get_label( 'course' ) ); ?></label>
								<select name="tc_filter_course" id="tcx_filter_course">
									<option value="">
										<?php
										echo esc_html(
											sprintf(
												/* translators: %s: Courseslabel */
												__( 'All %s', 'uncanny-learndash-reporting' ),
												\LearnDash_Custom_Label::get_label( 'courses' )
											)
										);
										?>
									<?php foreach ( $ld_courses as $course ) { ?>
										<?php $tc_course__selected = ! empty( $tc_course_filter ) && $tc_course_filter === (int) $course['course_id'] ? ' selected="selected"' : ''; ?>
										<option value="<?php echo esc_attr( $course['course_id'] ); ?>"<?php echo esc_attr( $tc_course__selected ); ?>>
											<?php echo esc_html( $course['course_name'] ); ?>
										</option>
									<?php } // foreach( $ld_courses ) ?>
								</select>
							</div>
							<div class="reporting-tincan-section__field">
								<label for="tcx_filter_module"><?php esc_html_e( 'Module', 'uncanny-learndash-reporting' ); ?></label>
								<select name="tc_filter_module" id="tcx_filter_module">
									<option value=""><?php esc_html_e( 'All Modules', 'uncanny-learndash-reporting' ); ?></option>
									<?php self::$tincan_database->print_modules_form_from_url_parameter( 'quiz' ); ?>
								</select>
							</div>
						</div>

					</div>

					<div class="reporting-tincan-filters-col reporting-tincan-filters-col--3">

						<div class="reporting-tincan-section__title">
							<?php esc_html_e( 'Quiz', 'uncanny-learndash-reporting' ); ?>
						</div>
						<div class="reporting-tincan-section__content">
							<div class="reporting-tincan-section__field">
								<label for="tc_filter_quiz"><?php esc_html_e( 'Question', 'uncanny-learndash-reporting' ); ?></label>
								<select name="tc_filter_quiz" id="tc_filter_quiz">
									<?php if ( ! empty( $tc_quiz_filter ) ) { ?>
										<option value="<?php echo esc_attr( $tc_quiz_filter ); ?>" selected="selected">
											<?php echo esc_html( ucfirst( ReportingAdminMenu::limit_text( sanitize_text_field( $tc_quiz_filter ), 8 ) ) ); ?>
										</option>
									<?php } // foreach( $ld_groups ) ?>
								</select>
							</div>
						</div>

						<div class="reporting-tincan-section__content">
							<div class="reporting-tincan-section__field">
								<label for="tc_filter_results"><?php esc_html_e( 'Result', 'uncanny-learndash-reporting' ); ?></label>
								<select name="tc_filter_results" id="tc_filter_results">
									<option value=""<?php echo esc_attr( '' === $tc_results_filter ? ' selected="selected"' : '' ); ?>>
										<?php esc_html_e( 'All Results', 'uncanny-learndash-reporting' ); ?>
									</option>
									<option value="1"<?php echo esc_attr( 1 === (int) $tc_results_filter ? ' selected="selected"' : '' ); ?>>
										<?php esc_html_e( 'Correct', 'uncanny-learndash-reporting' ); ?>
									</option>
									<option value="-1"<?php echo esc_attr( '-1' === $tc_results_filter ? ' selected="selected"' : '' ); ?>>
										<?php esc_html_e( 'Incorrect', 'uncanny-learndash-reporting' ); ?>
									</option>
								</select>
							</div>
						</div>

					</div>

					<div class="reporting-tincan-filters-col reporting-tincan-filters-col--4">
						<div class="reporting-tincan-section__title">
							<?php esc_html_e( 'Date Range', 'uncanny-learndash-reporting' ); ?>
						</div>
						<div class="reporting-tincan-section__content">
							<div class="reporting-tincan-section__field">
								<label>
									<input name="tc_filter_date_range" value="last"
										type="radio" <?php echo esc_attr( empty( $tc_filter_date_range ) || 'last' === $tc_filter_date_range ? 'checked="checked"' : '' ); ?> />
									<?php esc_html_e( 'View', 'uncanny-learndash-reporting' ); ?>
								</label>

								<select name="tc_filter_date_range_last" id="tcx_filter_date_range_last">
									<option value="all" <?php echo esc_attr( ! empty( $tc_filter_date_range ) && 'all' === $tc_filter_date_range_last ? 'selected="selected"' : '' ); ?>>
										<?php esc_html_e( 'All Dates', 'uncanny-learndash-reporting' ); ?>
									</option>
									<option value="week" <?php echo esc_attr( ! empty( $tc_filter_date_range ) && 'week' === $tc_filter_date_range_last ? 'selected="selected"' : '' ); ?>>
										<?php esc_html_e( 'Last Week', 'uncanny-learndash-reporting' ); ?>
									</option>
									<option value="month" <?php echo esc_attr( ! empty( $tc_filter_date_range ) && 'month' === $tc_filter_date_range_last ? 'selected="selected"' : '' ); ?>>
										<?php esc_html_e( 'Last Month', 'uncanny-learndash-reporting' ); ?>
									</option>
									<option value="90days" <?php echo esc_attr( ! empty( $tc_filter_date_range ) && '90days' === $tc_filter_date_range_last ? 'selected="selected"' : '' ); ?>>
										<?php esc_html_e( 'Last 90 Days', 'uncanny-learndash-reporting' ); ?>
									</option>
									<option value="3months" <?php echo esc_attr( ! empty( $tc_filter_date_range ) && '3months' === $tc_filter_date_range_last ? 'selected="selected"' : '' ); ?>>
										<?php esc_html_e( 'Last 3 Months', 'uncanny-learndash-reporting' ); ?>
									</option>
									<option value="6months" <?php echo esc_attr( ! empty( $tc_filter_date_range ) && '6months' === $tc_filter_date_range_last ? 'selected="selected"' : '' ); ?>>
										<?php esc_html_e( 'Last 6 Months', 'uncanny-learndash-reporting' ); ?>
									</option>
								</select>
							</div>

							<div class="reporting-tincan-section__field">
								<label>
									<input name="tc_filter_date_range" value="from"
										type="radio" <?php echo esc_attr( ! empty( $tc_filter_date_range ) && 'from' === $tc_filter_date_range ? 'checked="checked"' : '' ); ?> />
									<?php esc_html_e( 'From', 'uncanny-learndash-reporting' ); ?>
								</label>

								<input class="datepicker" name="tc_filter_start"
									placeholder="<?php esc_attr( esc_html_e( 'Start Date', 'uncanny-learndash-reporting' ) ); ?>"
									value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_start', '' ) ); ?>"/>

								<input class="datepicker" name="tc_filter_end"
									placeholder="<?php esc_attr( esc_html_e( 'End Date', 'uncanny-learndash-reporting' ) ); ?>"
									value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_end', '' ) ); ?>"/>

							</div>
						</div>
					</div>

				</div>

				<div class="reporting-tincan-footer">
					<?php
					submit_button(
						__( 'Search' ),
						'primary',
						'',
						false,
						array(
							'id'  => 'do_tcx_filter',
							'tab' => 'tin-can',
						)
					);
					?>

					<?php

					$reset_link = remove_query_arg(
						array(
							'paged',
							'tc_filter_mode',
							'tc_filter_group',
							'tc_filter_user',
							'tc_filter_course',
							'tc_filter_lesson',
							'tc_filter_module',
							'tc_filter_action',
							'tc_filter_quiz',
							'tc_filter_results',
							'tc_filter_date_range',
							'tc_filter_date_range_last',
							'tc_filter_start',
							'tc_filter_end',
							'orderby',
							'order',
						)
					);

					if ( false === strpos( $reset_link, 'tab' ) ) {
						$reset_link .= '&tab=xapi-tincan';
					}

					?>
					<a href="<?php echo esc_attr( $reset_link ); ?>" class="tclr-reporting-button">
						<?php esc_html_e( 'Reset', 'uncanny-learndash-reporting' ); ?>
					</a>
				</div>
			</div>
		</div>
	</form>
</div>
