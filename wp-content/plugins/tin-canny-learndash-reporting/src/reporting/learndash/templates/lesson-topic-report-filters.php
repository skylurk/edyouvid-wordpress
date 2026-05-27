<?php
/**
 * Lesson/Topic Report Filters
 *
 * @package Uncanny_Reporting
 */

namespace uncanny_learndash_reporting;

if ( ! defined( 'WPINC' ) ) {
	die;
}

$group_leader_one_group = ! empty( $data['group_id'] ) && 1 === count( $data['group_data'] );
$group_select_disabled  = $group_leader_one_group ? ' disabled' : '';
$group_label            = \LearnDash_Custom_Label::get_label( 'group' );
$course_label           = \LearnDash_Custom_Label::get_label( 'course' );
$step_label             = \LearnDash_Custom_Label::get_label( $data['type'] );
?>

<div class="uotc-report__filters">
	<div class="uotc-report__select_wrap" data-type="group">
		<label>
			<span class="uotc-report__label_text">
				<?php
				if ( $group_leader_one_group ) {
					echo $group_label;
				} else {
                    printf(
                        /* translators: %s is the group label */
                        esc_html__( '%s ( Optional )', 'uncanny-learndash-reporting' ),
                        $group_label
                    );
				}
				?>
			</span>
			<select class = 'uotc-report__select uotc-report__select__group'<?php echo esc_attr( $group_select_disabled ); ?>>
				<?php if ( ! $group_leader_one_group ) { ?>
					<option value="">
                    <?php
                        printf(
                            /* translators: %s is the group label */
                            esc_html__( 'Select a %s', 'uncanny-learndash-reporting' ),
                            strtolower( $group_label )
                        ); ?>
				<?php } ?>
				<?php
				foreach ( $data['group_data'] as $group ) {
					$selected = $group['id'] === $data['group_id'] ? ' selected' : '';
					?>
					<option value="<?php echo esc_attr( $group['id'] ); ?>"<?php esc_attr( $selected ); ?>>
					<?php echo esc_html( $group['name'] ); ?>
					</option>
					<?php
				}
				?>
			</select>
		</label>
	</div>

	<div class="uotc-report__select_wrap" data-type="course">
		<label>
			<span class="uotc-report__label_text">
				<?php echo $course_label; ?>
			</span>
			<select class="uotc-report__select uotc-report__select__course">
				<option value="">
                    <?php printf(
                        /* translators: %s is the course label */
                        esc_html__( 'Select a %s', 'uncanny-learndash-reporting' ),
                        $course_label
                    ); ?>
                </option>
				<?php
				foreach ( $data['course_data'] as $course ) {
					?>
					<option value="<?php echo esc_attr( $course['id'] ); ?>">
						<?php echo esc_html( $course['name'] ); ?>
					</option>
					<?php
				}
				?>
			</select>
		</label>
	</div>

	<div class="uotc-report__select_wrap" data-type="<?php echo esc_attr( $data['type'] ); ?>">
		<label>
			<span class="uotc-report__label_text">
				<?php echo esc_html( $step_label ); ?>
			</span>
			<select class="uotc-report__select uotc-report__select__course__step">
				<option value="">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s is the type of report (lesson or topic) */
						__( 'Select a %s', 'uncanny-learndash-reporting' ),
						strtolower($step_label)
					)
				);
				?>
				</option>
			</select>
		</label>
	</div>
</div>
