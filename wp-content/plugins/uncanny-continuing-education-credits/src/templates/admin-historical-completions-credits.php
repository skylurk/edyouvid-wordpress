<?php

namespace uncanny_ceu;

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<div class="wrap">
	<div class="ucec ucec-admin">
		<div class="rest-form">
			<!-- Action -->
			<input type="hidden" id="action" name="action" value="populate-historical-credits"/>

			<?php

			// Add admin header and tabs
			$tab_active = 'uncanny-historical-completions-credits';
			include Utilities::get_template( 'admin-header.php' );

			?>


			<div style="<?php if( ! isset($_GET['message'])){esc_attr_e('display:none;');} ?>" class="updated rest-message"><?php if(isset($_GET['message'])){esc_html_e($_GET['message']);} ?></div>
			<div style="display:none;" class="error rest-message"></div>

			<!-- LearnDash Group Settings -->
			<div class="uo-admin-section">
				<div class="uo-admin-header">
					<div class="uo-admin-title"><?php esc_html_e( '' ); ?></div>
				</div>
				<div class="uo-admin-block">
					<div class="uo-admin-form">

						<div class="uo-admin-field">
							<div class="uo-admin-label"><?php _e( 'Add credits to historical course completions', 'uncanny-ceu' ); ?></div>

							<div class="ucec-report-filter ucec-report-filter--courses" style="width:300px!important">
								<select name="courses" id="ucec-courses"
										class="ucec-report-select ucec-report-filter__select">
									<?php echo HistoricalCompletionsCredits::get_courses_dropdown(); ?>
								</select>
							</div>
						</div>

						<div class="uo-admin-field uo-admin-field--error"><?php _e('Choose a LearnDash course from the list above to generate missing historical records with credits. When a course is processed, the system will look up all users with completion records for the course that do not have associated credits in this plugin. This will only work for courses that have a credit value assigned, and there is no way to undo credit records once processed. Please verify that the credit value assigned to the course is correct before you proceed.','uncanny-ceu'); ?></div>

						<div class="uo-admin-field">
							<button class="uo-admin-form-submit submit-rest-form"
									data-end-point="save_admin_settings"><?php _e( 'Add missing records', 'uncanny-ceu' ); ?></button>
						</div>
					</div>
				</div>
				<div class="uo-admin-block">
					<div class="uo-admin-form">

						<div class="uo-admin-field">
							<div class="uo-admin-label"><?php _e( 'Generate historical course records', 'uncanny-ceu' ); ?></div>
						</div>

						<div class="uo-admin-field uo-admin-field--error"><?php _e('Create an archive of current course records that will restore progress records. The archive will copy course completions, enrolled and completion dates to a table that will survive progress resets. Any existing records in the archive will not be overridden, so it is safe to run this process more than once.','uncanny-ceu'); ?></div>

						<div class="uo-admin-field">
							<button class="uo-admin-form-submit submit-course-form"
									data-end-point="save_course_settings"><?php _e( 'Add course records', 'uncanny-ceu' ); ?></button>
						</div>
					</div>
				</div>
				<div class="uo-admin-block">
					<div class="uo-admin-form">

						<div class="uo-admin-field">
							<div class="uo-admin-label"><?php _e( 'Generate historical quiz records', 'uncanny-ceu' ); ?></div>
						</div>

						<div class="uo-admin-field uo-admin-field--error"><?php _e('Create an archive of current quiz records that will restore progress records. The archive will copy quiz scores, dates and multiple choice answers to a table that will survive progress resets. Any existing records in the archive will not be overridden, so it is safe to run this process more than once.','uncanny-ceu'); ?></div>

						<div class="uo-admin-field">
							<button class="uo-admin-form-submit submit-quiz-form"
									data-end-point="save_quiz_settings"><?php _e( 'Add quiz records', 'uncanny-ceu' ); ?></button>
						</div>
					</div>
				</div>
			</div>
		</div>


	</div>
</div>
