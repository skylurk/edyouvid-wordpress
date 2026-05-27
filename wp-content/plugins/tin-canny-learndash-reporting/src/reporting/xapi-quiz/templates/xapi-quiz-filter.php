<?php
namespace uncanny_learndash_reporting;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * @var \UCTINCAN\Database\Admin $tincan_database
 */
$groups  = array();
$courses = array();
$tc_results_filter         = strtolower( ultc_get_filter_var( 'tc_filter_results', '' ) );
$tc_quiz_filter            = ultc_get_filter_var( 'tc_filter_quiz', '' );
if ( ! is_admin() ) {
	$group_leader_id = get_current_user_id();
	$user_group_ids  = learndash_get_administrators_group_ids( $group_leader_id, true );
	$args            = array(
		'numberposts' => 9999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts
		'include'     => array_map( 'intval', $user_group_ids ),
		'post_type'   => 'groups',
		'orderby'     => 'title',
		'order'       => 'ASC',
	);

	$ld_groups_user = get_posts( $args );
	if ( ! empty( $ld_groups_user ) ) {
		foreach ( $ld_groups_user as $ld_group ) {
			$groups[] = array(
				'group_id'   => $ld_group->ID,
				'group_name' => $ld_group->post_title,
			);
		}
	}
	// Courses
	$get_filter_group_id = absint( ultc_get_filter_var( 'tc_filter_group', 0 ) );
	if ( ! empty( $get_filter_group_id ) ) {

		// check is user group
		if ( in_array( $get_filter_group_id, $user_group_ids, true ) ) {
			$course_ids = learndash_group_enrolled_courses( $get_filter_group_id );
			$args    = array(
				'numberposts' => 9999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts
				'include'     => array_map( 'intval', $course_ids ),
				'post_type'   => 'sfwd-courses',
				'orderby'     => 'title',
				'order'       => 'ASC',
			);

			$course_posts = get_posts( $args );
			foreach ( $course_posts as $course ) {
				$courses[] = array(
					'course_id'   => $course->ID,
					'course_name' => $course->post_title,
				);
			}
		}
	}

} else {

	// Group
	$groups = $tincan_database->get_groups();

	// Courses
	$courses = $tincan_database->get_courses();
}

// GET Filter variables.
$tc_order_by_filter        = ultc_get_filter_var( 'orderby', false );
$tc_order_by_filter        = ! empty( $tc_order_by_filter ) ? $tc_order_by_filter : 'date-time';
$tc_order_filter           = ultc_get_filter_var( 'order', false );
$tc_order_filter           = ! empty( $tc_order_filter ) ? $tc_order_filter : 'desc';
$tc_group_filter           = absint( ultc_get_filter_var( 'tc_filter_group', 0 ) );
$tc_course_filter          = absint( ultc_get_filter_var( 'tc_filter_course', 0 ) );
$tc_filter_date_range      = ultc_get_filter_var( 'tc_filter_date_range', 'last' );
$tc_filter_date_range_last = ultc_get_filter_var( 'tc_filter_date_range_last', '30days' );
?>

<div class="reporting-tincan-filters">
    <form action="<?php echo esc_attr( remove_query_arg( 'paged' ) ); ?>" id="tincan-filters-top">
        <div class="reporting-metabox">
            <div class="reporting-dashboard-col-heading" id="coursesOverviewTableHeading">
				<?php esc_html_e( 'Filters', 'uncanny-learndash-reporting' ); ?>
            </div>
            <div class="reporting-dashboard-col-content">
				<?php if ( is_admin() ) { ?>
                    <input type="hidden" name="page"
                           value="<?php echo esc_attr( ultc_get_filter_var( 'page', 1 ) ); ?>"/>
				<?php } ?>
                <input type="hidden" name="tc_filter_mode" value="list"/>
                <input type="hidden" name="tab" value="uncanny-tincanny-xapi-quiz-report"/>

<!--                <input type="hidden" name="orderby" value="--><?php //esc_attr( $tc_order_by_filter ); ?><!--"/>-->
<!--                <input type="hidden" name="order" value="--><?php //esc_attr( $tc_order_filter ); ?><!--"/>-->
                <div class="reporting-tincan-filters-columns">
                    <div class="reporting-tincan-filters-col reporting-tincan-filters-col--1">
                        <div class="reporting-tincan-section__title">
							<?php echo apply_filters( 'uo_tin_can_filter_section_heading', esc_html_x( 'User & Group', 'Tin Can Filter Group name', 'uncanny-learndash-reporting' ) ); ?>
                        </div>
                        <div class="reporting-tincan-section__content">
                            <div class="reporting-tincan-section__field">

                                <label for="tc_filter_group"><?php echo ucfirst( apply_filters( 'uo_tin_can_filter_group_label', esc_html_x( 'Groups', 'Tin Can Filter Group name', 'uncanny-learndash-reporting' ), true ) ); ?></label>
                                <select class="uo-admin-select" name="tc_filter_group" id="tc_filter_group">
                                    <option value="">
										<?php
										echo esc_html(
											sprintf(
											/* translators: %s: Group label */
												__( 'All %s', 'uncanny-learndash-reporting' ),
												apply_filters( 'uo_tin_can_filter_group_label', esc_html_x( 'Groups', 'Tin Can Filter Group name', 'uncanny-learndash-reporting' ), true)
											)
										);
										?>
                                    </option>
									<?php $groups = apply_filters( 'uo_tin_can_filter_groups', $groups ); ?>
									<?php if ( ! empty( $groups ) ) { ?>
										<?php foreach ( $groups as $group ) { ?>
											<?php $tc_group_selected = $tc_group_filter === (int) $group['group_id'] ? ' selected="selected"' : ''; ?>
                                            <option value="<?php echo esc_attr( $group['group_id'] ); ?>"<?php echo esc_attr( $tc_group_selected ); ?>>
												<?php echo esc_html( $group['group_name'] ); ?>
                                            </option>
										<?php } ?>
									<?php } ?>
                                </select>

                            </div>

                            <div class="reporting-tincan-section__field">
                                <label for="tc_filter_user"><?php esc_html_e( 'User', 'uncanny-learndash-reporting' ); ?></label>
                                <input class="uo-admin-input" name="tc_filter_user" id="tc_filter_user"
                                       placeholder="<?php esc_html_e( 'User', 'uncanny-learndash-reporting' ); ?>"
                                       value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_user', '' ) ); ?>"/>
                            </div>
                        </div>
                    </div>

                    <div class="reporting-tincan-filters-col reporting-tincan-filters-col--2">

                        <div class="reporting-tincan-section__title">
							<?php echo apply_filters( 'uo_tin_can_filter_content_label', esc_html_x( 'Content', 'Tin Can Filter Content label', 'uncanny-learndash-reporting' ) ); ?>
                        </div>
                        <div class="reporting-tincan-section__content">
                            <div class="reporting-tincan-section__field">
                                <label for="tc_filter_course"><?php echo ucfirst( apply_filters( 'uo_tin_can_filter_course_label', esc_html_x( 'Course', 'Tin Can Filter Group name', 'uncanny-learndash-reporting' ), false ) ); ?></label>
                                <select class="uo-admin-select" name="tc_filter_course" id="tc_filter_course">
                                    <option value="">
										<?php
										echo esc_html(
											sprintf(
											/* translators: %s: Course label */
												__( 'All %s', 'uncanny-learndash-reporting' ),
												apply_filters( 'uo_tin_can_filter_course_label', esc_html_x( 'Course', 'Tin Can Filter Group name', 'uncanny-learndash-reporting' ), true )
											)
										);
										?>
                                    </option>
	                                <?php $courses = apply_filters( 'uo_tin_can_filter_courses', $courses ); ?>
	                                <?php if ( ! empty( $courses ) ) { ?>
		                                <?php foreach ( $courses as $course ) { ?>
			                                <?php $tc_course_selected = ! empty( $tc_course_filter ) && $tc_course_filter === (int) $course['course_id'] ? ' selected="selected"' : ''; ?>
                                            <option value="<?php echo esc_attr( $course['course_id'] ); ?>"<?php echo esc_attr( $tc_course_selected ); ?>>
				                                <?php echo esc_html( $course['course_name'] ); ?>
                                            </option>
		                                <?php } ?>
	                                <?php } ?>
                                </select>
                            </div>
                            <div class="reporting-tincan-section__field">
                                <label for="tc_filter_module"><?php echo ucfirst( apply_filters( 'uo_tin_can_filter_module_label', esc_html_x( 'Module', 'Tin Can Filter Group name', 'uncanny-learndash-reporting' ), false ) ); ?></label>
                                <select class="uo-admin-select" name="tc_filter_module" id="tc_filter_module">
                                    <option value="">
		                                <?php
		                                echo esc_html(
			                                sprintf(
			                                /* translators: %s: Course label */
				                                __( 'All %s', 'uncanny-learndash-reporting' ),
				                                apply_filters( 'uo_tin_can_filter_module_label', esc_html_x( 'modules', 'Tin Can Filter Group name', 'uncanny-learndash-reporting' ), true )
			                                )
		                                );
		                                ?>
                                    </option>
									<?php echo $tincan_database->print_modules_form_from_url_parameter(); ?>
                                </select>
                            </div>
                        </div>

                    </div>

                    <div class="reporting-tincan-filters-col reporting-tincan-filters-col--3">

                        <div class="reporting-tincan-section__title">
							<?php echo ucfirst( apply_filters( 'uo_tin_can_filter_activity_label', esc_html_x( 'Quiz', 'Tin Can Filter Activity name', 'uncanny-learndash-reporting' ), false ) ); ?>
                        </div>
                        <div class="reporting-tincan-section__content">
                            <div class="reporting-tincan-section__field">
								<label for="tc_filter_quiz"><?php esc_html_e( 'Question', 'uncanny-learndash-reporting' ); ?></label>
								<select class="uo-admin-select" name="tc_filter_quiz" id="tc_filter_quiz">
									<?php if ( ! empty( $tc_quiz_filter ) ) { ?>
										<option value="<?php echo esc_attr( $tc_quiz_filter ); ?>" selected="selected">
											<?php
											$database          = new \UCTINCAN\Database\Admin();
											$question_name = $database->get_question_by_hash( $tc_quiz_filter );
											$question_name = ReportingAdminMenu::limit_text( sanitize_text_field( $question_name ), 8 );
											$question_name = ucfirst( $question_name );
											?>
											<?php echo esc_html( $question_name ); ?>
										</option>
									<?php }  ?>
								</select>
							</div>
						</div>
						<div class="reporting-tincan-section__content">
							<div class="reporting-tincan-section__field">
								<label for="tc_filter_results"><?php esc_html_e( 'Result', 'uncanny-learndash-reporting' ); ?></label>
								<select class="uo-admin-select" name="tc_filter_results" id="tc_filter_results">
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
							<?php apply_filters( 'uo_tin_can_filter_date_range_label', esc_html_x( 'Date Range', 'Tin Can Filter Date Range name', 'uncanny-learndash-reporting' ) ); ?>
                        </div>
                        <div class="reporting-tincan-section__content">
                            <div class="reporting-tincan-section__field">
                                <label>
                                    <input class="uo-admin-radio" name="tc_filter_date_range" value="last"
                                           type="radio" <?php echo esc_attr( empty( $tc_filter_date_range ) || 'last' === $tc_filter_date_range ? 'checked="checked"' : '' ); ?> />
									<?php esc_html_e( 'View', 'uncanny-learndash-reporting' ); ?>
                                </label>

                                <select class="uo-admin-select" name="tc_filter_date_range_last" id="tc_filter_date_range_last">
                                    <option value="all" <?php echo esc_attr( ! empty( $tc_filter_date_range ) && 'all' === $tc_filter_date_range_last ? 'selected="selected"' : '' ); ?>>
										<?php esc_html_e( 'All Dates', 'uncanny-learndash-reporting' ); ?>
                                    </option>
                                    <option value="week" <?php echo esc_attr( ! empty( $tc_filter_date_range ) && 'week' === $tc_filter_date_range_last ? 'selected="selected"' : '' ); ?>>
										<?php esc_html_e( 'Last Week', 'uncanny-learndash-reporting' ); ?>
                                    </option>
                                    <option value="month" <?php echo esc_attr( ! empty( $tc_filter_date_range ) && 'month' === $tc_filter_date_range_last ? 'selected="selected"' : '' ); ?>>
										<?php esc_html_e( 'Last Month', 'uncanny-learndash-reporting' ); ?>
                                    </option>
                                    <option value="30days" <?php echo esc_attr( ! empty( $tc_filter_date_range ) && '30days' === $tc_filter_date_range_last ? 'selected="selected"' : '' ); ?>>
										<?php esc_html_e( 'Last 30 Days', 'uncanny-learndash-reporting' ); ?>
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
									<option value="1year" <?php echo esc_attr( ! empty( $tc_filter_date_range ) && '1year' === $tc_filter_date_range_last ? 'selected="selected"' : '' ); ?>>
										<?php esc_html_e( 'Last 1 Year', 'uncanny-learndash-reporting' ); ?>
									</option>
                                </select>
                            </div>

                            <div class="reporting-tincan-section__field">
                                <label>
                                    <input class="uo-admin-radio" name="tc_filter_date_range" value="from"
                                           type="radio" <?php echo esc_attr( ! empty( $tc_filter_date_range ) && 'from' === $tc_filter_date_range ? 'checked="checked"' : '' ); ?> />
									<?php esc_html_e( 'From', 'uncanny-learndash-reporting' ); ?>
                                </label>

                                <input class="datepicker uo-admin-input" name="tc_filter_start"
                                       placeholder="<?php esc_html_e( 'Start Date', 'uncanny-learndash-reporting' ); ?>"
                                       value="<?php echo ultc_get_filter_var( 'tc_filter_start', '' ); ?>"/>

                                <input class="datepicker uo-admin-input" name="tc_filter_end"
                                       placeholder="<?php esc_html_e( 'End Date', 'uncanny-learndash-reporting' ); ?>"
                                       value="<?php echo esc_attr( ultc_get_filter_var( 'tc_filter_end', '' ) ); ?>"/>

                            </div>
                        </div>
                    </div>

                </div>

                <div class="reporting-tincan-footer">
					<?php
					if ( ! function_exists( 'submit_button' ) ) {
						require_once ABSPATH . 'wp-admin/includes/template.php';
					}
					\submit_button(
						__( 'Search', 'uncanny-learndash-reporting' ),
						'primary',
						'',
						false,
						array(
							'id'  => 'do_tc_filter',
							'tab' => 'xapi-tincan',
						)
					);

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
                    <a href="<?php echo esc_attr( $reset_link ); ?>"
                       class="tclr-reporting-button"><?php esc_html_e( 'Reset', 'uncanny-learndash-reporting' ); ?></a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
	jQuery(document).ready(function ($) {
		$('.datepicker').datepicker({
			'dateFormat': 'yy-mm-dd'
		});

		$('.dashicons-calendar-alt').click(function () {
			$(this).prev().focus();
		});
	});
</script>
