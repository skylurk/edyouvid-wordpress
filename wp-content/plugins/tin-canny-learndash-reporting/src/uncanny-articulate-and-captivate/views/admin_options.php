<?php
namespace uncanny_learndash_reporting;

?>
<div class="uo-tclr-admin wrap" id="snc_options">
	<?php

	// Add admin header and tabs
	$tab_active = 'snc_options';
	require Config::get_template( 'admin-header.php' );
	$dimension_opts = array( 'px', '%', 'vw', 'vh' );

	?>

	<div class="tclr__admin-content">
		<form enctype="multipart/form-data" id="snc_options_form" method="POST">
			<input type="hidden" name="security" value="<?php echo wp_create_nonce( 'snc-options' ); ?>"/>

			<!-- Reports settings -->
			<div class="uo-admin-section">
				<div class="uo-admin-header">
					<div class="uo-admin-title"><?php esc_html_e( 'Reports', 'uncanny-learndash-reporting' ); ?></div>
				</div>
				<div class="uo-admin-block">
					<div class="uo-admin-form">

						<!-- Disable wp-admin dashboard widget -->
						<div class="uo-admin-field">
							<div
								class="uo-admin-label"><?php esc_html_e( 'Disable wp-admin dashboard widget', 'uncanny-learndash-reporting' ); ?></div>

							<label class="uo-radio">
								<input
								<?php
								if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['disableDashWidget'] ) && \TINCANNYSNC\Admin\Options::$OPTION['disableDashWidget'] === '1' ) {
									echo ' checked="checked"';
								}
								?>
								type="radio" name="disableDashWidget" value="1">
								<div class="uo-checkmark"></div>
								<span class="uo-label"><?php esc_html_e( 'Yes', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<label class="uo-radio">
								<input
								<?php
								if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['disableDashWidget'] ) && \TINCANNYSNC\Admin\Options::$OPTION['disableDashWidget'] === '0' ) {
									echo ' checked="checked"';
								}
								?>
								type="radio" name="disableDashWidget" value="0">
								<div class="uo-checkmark"></div>
								<span class="uo-label"><?php esc_html_e( 'No', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<div
								class="uo-admin-description"><?php esc_html_e( 'This settings allows the wp-admin dashboard(admin home screen) reporting widget to be disabled.', 'uncanny-learndash-reporting' ); ?></div>
						</div>

						<!-- Suppress loading page rows. Load all rows. -->
						<div class="uo-admin-field">
							<div
								class="uo-admin-label"><?php esc_html_e( 'Enable sorting by % complete', 'uncanny-learndash-reporting' ); ?></div>

							<label class="uo-radio">
								<input
								<?php
								if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['disablePerformanceEnhancments'] ) && \TINCANNYSNC\Admin\Options::$OPTION['disablePerformanceEnhancments'] === '1' ) {
									echo ' checked="checked"';
								}
								?>
								type="radio" name="disablePerformanceEnhancments" value="1">
								<div class="uo-checkmark"></div>
								<span class="uo-label"><?php esc_html_e( 'Yes', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<label class="uo-radio">
								<input
								<?php
								if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['disablePerformanceEnhancments'] ) && \TINCANNYSNC\Admin\Options::$OPTION['disablePerformanceEnhancments'] === '0' ) {
									echo ' checked="checked"';
								}
								?>
								type="radio" name="disablePerformanceEnhancments" value="0">
								<div class="uo-checkmark"></div>
								<span class="uo-label"><?php esc_html_e( 'No', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<div
								class="uo-admin-description"><?php esc_html_e( 'The ability to sort by % Complete requires a very large amount of data be requested from the server, which will fail or cause poor performance on sites with many users. Disable this setting to improve the responsiveness of the reports.', 'uncanny-learndash-reporting' ); ?></div>
						</div>

						<!-- Enable reporting on front-end. -->
						<div class="uo-admin-field">
							<div
								class="uo-admin-label"><?php esc_html_e( 'Enable Tin Can Report on front end', 'uncanny-learndash-reporting' ); ?></div>

							<label class="uo-radio">
								<input
								<?php
								if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['enableTinCanReportFrontEnd'] ) && \TINCANNYSNC\Admin\Options::$OPTION['enableTinCanReportFrontEnd'] === '1' ) {
									echo ' checked="checked"';
								}
								?>
								type="radio" name="enableTinCanReportFrontEnd" value="1">
								<div class="uo-checkmark"></div>
								<span class="uo-label"><?php esc_html_e( 'Yes', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<label class="uo-radio">
								<input
								<?php
								if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['enableTinCanReportFrontEnd'] ) && \TINCANNYSNC\Admin\Options::$OPTION['enableTinCanReportFrontEnd'] === '0' ) {
									echo ' checked="checked"';
								}
								?>
								type="radio" name="enableTinCanReportFrontEnd" value="0">
								<div class="uo-checkmark"></div>
								<span class="uo-label"><?php esc_html_e( 'No', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<div
								class="uo-admin-description"><?php esc_html_e( 'This setting enables the Tin Can Report tab when using the [tincanny] shortcode to display reports.', 'uncanny-learndash-reporting' ); ?></div>
						</div>

						<!-- Enable xAPI reporting on front-end. -->
						<div class="uo-admin-field">
							<div
								class="uo-admin-label"><?php esc_html_e( 'Enable xAPI Quiz Report on front end', 'uncanny-learndash-reporting' ); ?></div>

							<label class="uo-radio">
								<input
								<?php
								if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['enablexapiReportFrontEnd'] ) && \TINCANNYSNC\Admin\Options::$OPTION['enablexapiReportFrontEnd'] === '1' ) {
									echo ' checked="checked"';
								}
								?>
								type="radio" name="enablexapiReportFrontEnd" value="1">
								<div class="uo-checkmark"></div>
								<span class="uo-label"><?php esc_html_e( 'Yes', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<label class="uo-radio">
								<input
								<?php
								if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['enablexapiReportFrontEnd'] ) && \TINCANNYSNC\Admin\Options::$OPTION['enablexapiReportFrontEnd'] === '0' ) {
									echo ' checked="checked"';
								}
								?>
								type="radio" name="enablexapiReportFrontEnd" value="0">
								<div class="uo-checkmark"></div>
								<span class="uo-label"><?php esc_html_e( 'No', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<div
								class="uo-admin-description"><?php esc_html_e( 'This setting enables the xAPI Quiz Report tab when using the [tincanny] shortcode to display reports.', 'uncanny-learndash-reporting' ); ?></div>

						</div>

						<!-- Select which user identifier(s) are shown in reports -->
						<div class="uo-admin-field">
							<div
								class="uo-admin-label"><?php esc_html_e( 'User identifier(s)', 'uncanny-learndash-reporting' ); ?></div>

							<label class="uo-checkbox">
								<input
								<?php
								if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['userIdentifierDisplayName'] ) && \TINCANNYSNC\Admin\Options::$OPTION['userIdentifierDisplayName'] === '1' ) {
									echo ' checked="checked"';
								}
								?>
								type="checkbox" name="userIdentifierDisplayName" value="1">
								<div class="uo-checkmark"></div>
								<span class="uo-label"><?php esc_html_e( 'Display Name', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<label class="uo-checkbox">
								<input
								<?php
								if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['userIdentifierFirstName'] ) && \TINCANNYSNC\Admin\Options::$OPTION['userIdentifierFirstName'] === '1' ) {
									echo ' checked="checked"';
								}
								?>
								type="checkbox" name="userIdentifierFirstName" value="1">
								<div class="uo-checkmark"></div>
								<span class="uo-label"><?php esc_html_e( 'First Name', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<label class="uo-checkbox">
								<input
								<?php
								if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['userIdentifierLastName'] ) && \TINCANNYSNC\Admin\Options::$OPTION['userIdentifierLastName'] === '1' ) {
									echo ' checked="checked"';
								}
								?>
								type="checkbox" name="userIdentifierLastName" value="1">
								<div class="uo-checkmark"></div>
								<span class="uo-label"><?php esc_html_e( 'Last Name', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<label class="uo-checkbox">
								<input
								<?php
								if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['userIdentifierUsername'] ) && \TINCANNYSNC\Admin\Options::$OPTION['userIdentifierUsername'] === '1' ) {
									echo ' checked="checked"';
								}
								?>
								type="checkbox" name="userIdentifierUsername" value="1">
								<div class="uo-checkmark"></div>
								<span class="uo-label"><?php esc_html_e( 'Username', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<label class="uo-checkbox">
								<input
								<?php
								if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['userIdentifierEmail'] ) && \TINCANNYSNC\Admin\Options::$OPTION['userIdentifierEmail'] === '1' ) {
									echo ' checked="checked"';
								}
								?>
								type="checkbox" name="userIdentifierEmail" value="1">
								<div class="uo-checkmark"></div>
								<span class="uo-label"><?php esc_html_e( 'Email Address', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<div
								class="uo-admin-description"><?php esc_html_e( 'These options will show or hide columns in the user list of the Course report and the User reports.', 'uncanny-learndash-reporting' ); ?></div>
						</div>

						<!-- Submit -->
						<div class="uo-admin-field uo-admin-extra-space">
							<input type="submit" name="submit" id="submit" class="uo-admin-form-submit"
								   value="<?php esc_html_e( 'Save Changes', 'uncanny-learndash-reporting' ); ?>">
						</div>
					</div>
				</div>
			</div>

			<!-- Course Report -->

			<div class="uo-admin-section">
				<div class="uo-admin-header">
					<div class="uo-admin-title"><?php echo __( 'Course/User Report Settings', 'uncanny-learndash-reporting' ); ?></div>
				</div>
				<div class="uo-admin-block">
					<div class="uo-admin-form">

						<!-- <div class="uo-admin-field">
							<div class="uo-admin-label"><?php echo __( 'Report Mode', 'uncanny-learndash-reporting' ); ?></div>
							<?php $mode = get_option( 'tincanny_user_report_report_mode', 'cached' ); ?>
							<label class="uo-checkbox">
								<select name="tincanny_user_report_report_mode" id="tincanny_user_report_report_mode" class="uo-admin-select">
									<option
										value="legacy" <?php echo 'legacy' === $mode ? 'selected' : ''; ?>>
										<?php echo __( 'Legacy', 'uncanny-learndash-reporting' ); ?>
									</option>
									<option value="cached" <?php echo 'cached' === $mode ? 'selected' : ''; ?>>
										<?php echo __( 'Performance (Recommended)', 'uncanny-learndash-reporting' ); ?>
									</option>
									<option value="json" <?php echo 'json' === $mode ? 'selected' : ''; ?>>
										<?php echo __( 'Ludicrous (Fastest, report updates hourly)', 'uncanny-learndash-reporting' ); ?>
									</option>
								</select>
							</label>
						</div> -->
						<div class="uo-admin-field">
							<div class="uo-admin-label"><?php echo __( 'Default group for Reports', 'uncanny-learndash-reporting' ); ?></div>
							<?php $mode = get_option( 'tincanny_user_report_default_group', 'all' ); ?>
							<label class="uo-checkbox">
								<select name="tincanny_user_report_default_group" id="tincanny_user_report_default_group"
										class="uo-admin-select">
									<option
										value="all" <?php echo 'all' === $mode ? 'selected' : ''; ?>>
										<?php echo __( 'All Users', 'uncanny-learndash-reporting' ); ?>
									</option>
									<?php
										$groups = get_posts(
											array(
												'post_type' => 'groups',
												'posts_per_page' => 99999,
												'post_status' => 'publish',
												'orderby' => 'title',
												'order'   => 'ASC',
											)
										);
										foreach ( $groups as $group ) {
											$group_id   = $group->ID;
											$group_name = $group->post_title;
												echo '<option value="' . $group_id . '" ' . ( absint( $group_id ) === absint( $mode ) ? 'selected' : '' ) . '>' . $group_name . '</option>';
										}
										?>

								</select>
							</label>
							<p class="uo-admin-description">
								<?php echo __( 'Filter reports by group to improve loading times with large datasets. You can switch between groups anytime in reports.', 'uncanny-learndash-reporting' ); ?>
							</p>
						</div>
						<div class="uo-admin-field">
							<div class="uo-admin-label"><?php echo __( 'Default page length', 'uncanny-learndash-reporting' ); ?></div>
							<?php $mode = (int) get_option( 'tincanny_user_report_default_page_length', 50 ); ?>
							<label class="uo-checkbox">
								<select name="tincanny_user_report_default_page_length" id="tincanny_user_report_default_page_length"
										class="uo-admin-select">
									<option
										value="10" <?php echo 10 === $mode ? 'selected' : ''; ?>>
										<?php echo __( '10', 'uncanny-learndash-reporting' ); ?>
									</option>
									<option
										value="25" <?php echo 25 === $mode ? 'selected' : ''; ?>>
										<?php echo __( '25', 'uncanny-learndash-reporting' ); ?>
									</option>
									<option
										value="50" <?php echo 50 === $mode ? 'selected' : ''; ?>>
										<?php echo __( '50', 'uncanny-learndash-reporting' ); ?>
									</option>
									<option
										value="100" <?php echo 100 === $mode ? 'selected' : ''; ?>>
										<?php echo __( '100', 'uncanny-learndash-reporting' ); ?>
									</option>
									<option
										value="200" <?php echo 200 === $mode ? 'selected' : ''; ?>>
										<?php echo __( '200', 'uncanny-learndash-reporting' ); ?>
									</option>
								</select>
							</label>
							<p class="uo-admin-description">
								<?php echo __( 'Number of entries per page in reports. Lower numbers load faster.', 'uncanny-learndash-reporting' ); ?>
							</p>
						</div>
						<div class="uo-admin-field uo-admin-field--error"></div>

						<!-- Submit -->
						<div class="uo-admin-field uo-admin-extra-space">
							<input type="submit" name="submit" id="submit" class="uo-admin-form-submit"
								   value="<?php esc_html_e( 'Save Changes', 'uncanny-learndash-reporting' ); ?>">
						</div>
					</div>
				</div>
			</div>

			<!-- Settings -->
			<div class="uo-admin-section">
				<div class="uo-admin-header">
					<div class="uo-admin-title"><?php esc_html_e( 'Tin Can/SCORM', 'uncanny-learndash-reporting' ); ?></div>
				</div>
				<div class="uo-admin-block">
					<div class="uo-admin-form">

						<!-- Do you want to capture Tin Can Data? -->
						<div class="uo-admin-field">
							<div
								class="uo-admin-label"><?php esc_html_e( 'Capture Tin Can and SCORM data', 'uncanny-learndash-reporting' ); ?></div>

							<label class="uo-radio">
								<input
								<?php
								if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['tinCanActivation'] ) && \TINCANNYSNC\Admin\Options::$OPTION['tinCanActivation'] === '1' ) {
									echo ' checked="checked"';
								}
								?>
								type="radio" name="tinCanActivation" value="1">
								<div class="uo-checkmark"></div>
								<span class="uo-label"><?php esc_html_e( 'Yes', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<label class="uo-radio">
								<input
								<?php
								if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['tinCanActivation'] ) && \TINCANNYSNC\Admin\Options::$OPTION['tinCanActivation'] === '0' ) {
									echo ' checked="checked"';
								}
								?>
								type="radio" name="tinCanActivation" value="0">
								<div class="uo-checkmark"></div>
								<span class="uo-label"><?php esc_html_e( 'No', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<div
								class="uo-admin-description"><?php esc_html_e( 'If you are uploading modules from a supported authoring tool (e.g. Storyline, Captivate) and want to capture data, turn this on.', 'uncanny-learndash-reporting' ); ?></div>
						</div>


						<!-- Protect SCORM/Tin Can Modules? -->
						<div class="uo-admin-field">
							<div
								class="uo-admin-label"><?php esc_html_e( 'Protect SCORM/Tin Can modules', 'uncanny-learndash-reporting' ); ?></div>

							<label class="uo-radio">
								<input
								<?php
								if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['nonceProtection'] ) && \TINCANNYSNC\Admin\Options::$OPTION['nonceProtection'] === '1' ) {
									echo ' checked="checked"';
								}
								?>
								type="radio" name="nonceProtection" value="1">
								<div class="uo-checkmark"></div>
								<span class="uo-label"><?php esc_html_e( 'Yes', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<label class="uo-radio">
								<input
								<?php
								if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['nonceProtection'] ) && \TINCANNYSNC\Admin\Options::$OPTION['nonceProtection'] === '0' ) {
									echo ' checked="checked"';
								}
								?>
								type="radio" name="nonceProtection" value="0">
								<div class="uo-checkmark"></div>
								<span class="uo-label"><?php esc_html_e( 'No', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<div
								class="uo-admin-description"><?php esc_html_e( 'This setting adds a layer of basic protection to your modules to discourage users from attempting to access them directly (outside of your WordPress site). Disable this if you are experiencing module loading issues.', 'uncanny-learndash-reporting' ); ?>
								<strong><?php esc_html_e( 'This feature is only supported on hosts that support mod_rewrite.', 'uncanny-learndash-reporting' ); ?></strong>
							</div>
						</div>

						<!-- Scormdriver.js compatibility mode? -->
						<?php
							$tinCanScormDriverCompatibility = isset( \TINCANNYSNC\Admin\Options::$OPTION['tinCanScormDriverCompatibility'] ) ? \TINCANNYSNC\Admin\Options::$OPTION['tinCanScormDriverCompatibility'] : 'async';
						?>
						<div class="uo-admin-field">
							<div
								class="uo-admin-label"><?php esc_html_e( 'SCORM Driver Compatibility', 'uncanny-learndash-reporting' ); ?></div>

							<label class="uo-radio">
								<input <?php if ( 'async' === $tinCanScormDriverCompatibility ) {
									echo ' checked="checked"';
								} ?> type="radio" name="tinCanScormDriverCompatibility" value="async">
								<div class="uo-checkmark"></div>
								<span class="uo-label"><?php esc_html_e( 'Async (default)', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<label class="uo-radio">
								<input <?php if ( 'sync' === $tinCanScormDriverCompatibility ) {
									echo ' checked="checked"';
								} ?> type="radio" name="tinCanScormDriverCompatibility" value="sync">
								<div class="uo-checkmark"></div>
								<span class="uo-label"><?php esc_html_e( 'Sync', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<div
								class="uo-admin-description"><?php esc_html_e( "Use the 'Sync' option if SCORM and xAPI records aren't being tracked as expected in your environment. This setting is applied as files are uploaded, so changing the setting for a module will require uploading the module again.", 'uncanny-learndash-reporting' ); ?></div>
						</div>

						<!-- Submit -->
						<div class="uo-admin-field uo-admin-extra-space">
							<input type="submit" name="submit" id="submit" class="uo-admin-form-submit"
								   value="<?php esc_html_e( 'Save Changes', 'uncanny-learndash-reporting' ); ?>">
						</div>
					</div>
				</div>
			</div>

			<!-- MARK COMPLETE BUTTON -->
			<div class="uo-admin-section" id="mark_complete_button_box" style="
			<?php
			if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['tinCanActivation'] ) && \TINCANNYSNC\Admin\Options::$OPTION['tinCanActivation'] !== '1' ) {
				echo 'display: none';}
			?>
			">
				<div class="uo-admin-header">
					<div
						class="uo-admin-title"><?php esc_html_e( 'Mark Complete button', 'uncanny-learndash-reporting' ); ?></div>
				</div>
				<div class="uo-admin-block">
					<div class="uo-admin-form">

						<!-- Disable LearnDash Mark Complete button until the learner completes all Tin Can modules in the Lesson/Topic? -->
						<div class="uo-admin-field">
							<div class="uo-admin-label"><?php esc_html_e( 'Behavior', 'uncanny-learndash-reporting' ); ?></div>
							<div
								class="uo-admin-description"><?php esc_html_e( 'Use these options to set the default behavior of the Mark Complete button in lessons and topics that contain embedded Tin Canny content. This setting can be overridden at the individual lesson/topic level.', 'uncanny-learndash-reporting' ); ?></div>

							<label class="uo-radio">
								<input
								<?php
								if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['disableMarkComplete'] ) && \TINCANNYSNC\Admin\Options::$OPTION['disableMarkComplete'] === '0' ) {
									echo ' checked="checked"';
								}
								?>
								type="radio" name="disableMarkComplete" value="0">
								<div class="uo-checkmark"></div>
								<span
									class="uo-label"><?php _e( '<strong>Always Enabled:</strong> Mark Complete button is always enabled.', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<label class="uo-radio">
								<input
								<?php
								if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['disableMarkComplete'] ) && \TINCANNYSNC\Admin\Options::$OPTION['disableMarkComplete'] === '1' ) {
									echo ' checked="checked"';
								}
								?>
								type="radio" name="disableMarkComplete" value="1">
								<div class="uo-checkmark"></div>
								<span
									class="uo-label"><?php _e( '<strong>Disabled until complete:</strong> Mark Complete button is disabled until the learner completes the Tin Can module.', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<label class="uo-radio">
								<input
								<?php
								if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['disableMarkComplete'] ) && \TINCANNYSNC\Admin\Options::$OPTION['disableMarkComplete'] === '3' ) {
									echo ' checked="checked"';
								}
								?>
								type="radio" name="disableMarkComplete" value="3">
								<div class="uo-checkmark"></div>
								<span
									class="uo-label"><?php _e( '<strong>Hidden until complete:</strong> Mark Complete button is hidden until the learner completes the Tin Can module.', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<label class="uo-radio">
								<input
								<?php
								if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['disableMarkComplete'] ) && \TINCANNYSNC\Admin\Options::$OPTION['disableMarkComplete'] === '4' ) {
									echo ' checked="checked"';
								}
								?>
								type="radio" name="disableMarkComplete" value="4">
								<div class="uo-checkmark"></div>
								<span
									class="uo-label"><?php _e( '<strong>Hidden and autocomplete:</strong> Mark Complete button is hidden and the lesson/topic is automatically marked complete when the learner completes the Tin Can module. <b>Note:</b> With this option, you will need to provide a way for the user to progress to the next lesson or topic when the module has been completed.', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<label class="uo-radio">
								<input
								<?php
								if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['disableMarkComplete'] ) && \TINCANNYSNC\Admin\Options::$OPTION['disableMarkComplete'] === '5' ) {
									echo ' checked="checked"';
								}
								?>
								type="radio" name="disableMarkComplete" value="5">
								<div class="uo-checkmark"></div>
								<span
									class="uo-label"><?php _e( '<strong>Hidden and autoadvance:</strong> Mark Complete button is hidden, the lesson/topic is automatically marked complete and the learner is automatically advanced to the next lesson or topic when they complete the Tin Can module.', 'uncanny-learndash-reporting' ); ?></span>
							</label>


						</div>

						<!-- Enable compatibility mode -->
						<div class="uo-admin-field">
							<div class="uo-admin-label"><?php esc_html_e( 'Enable compatibility mode', 'uncanny-learndash-reporting' ); ?></div>

							<label class="uo-radio">
								<input
								<?php
								if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['methodMarkCompleteForTincan'] ) && \TINCANNYSNC\Admin\Options::$OPTION['methodMarkCompleteForTincan'] === '1' ) {
									echo ' checked="checked"';}
								?>
								type="radio" name="methodMarkCompleteForTincan" value="1">
								<div class="uo-checkmark"></div>
								<span class="uo-label"><?php esc_html_e( 'Yes', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<label class="uo-radio">
								<input
								<?php
								if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['methodMarkCompleteForTincan'] ) && \TINCANNYSNC\Admin\Options::$OPTION['methodMarkCompleteForTincan'] === '0' ) {
									echo ' checked="checked"';}
								?>
								type="radio" name="methodMarkCompleteForTincan" value="0">
								<div class="uo-checkmark"></div>
								<span class="uo-label"><?php esc_html_e( 'No', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<div class="uo-admin-description"><?php esc_html_e( 'Enables a slower method of recording xAPI statements and unlocking Mark Complete behaviors that works in a greater range of situations, including when statements are sent simultaneously with module closure.', 'uncanny-learndash-reporting' ); ?> </div>
						</div>

						<!-- Custom Label -->
						<div class="uo-admin-field">
							<div
								class="uo-admin-label"><?php esc_html_e( 'Custom label', 'uncanny-learndash-reporting' ); ?></div>
							<div
								class="uo-admin-description"><?php esc_html_e( 'Set a custom label on the Mark Complete button when a Tin Canny module is embedded in the lesson/topic.', 'uncanny-learndash-reporting' ); ?></div>
							<input class="uo-admin-input" type="text" name="labelMarkComplete" id="labelMarkComplete"
								   value="<?php echo \TINCANNYSNC\Admin\Options::$OPTION['labelMarkComplete']; ?>"/>
						</div>

						<!-- Autocomplete Lessons and Topics even if Tin Canny content on page (Uncanny Toolkit Pro) -->
						<div class="uo-admin-field">
							<div
								class="uo-admin-label"><?php esc_html_e( 'Autocomplete Lessons and Topics even if Tin Canny content on page (Uncanny Toolkit Pro)', 'uncanny-learndash-reporting' ); ?></div>

							<label class="uo-radio">
								<input
								<?php
								if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['autocompleLessonsTopicsTincanny'] ) && \TINCANNYSNC\Admin\Options::$OPTION['autocompleLessonsTopicsTincanny'] === '1' ) {
									echo ' checked="checked"';
								}
								?>
								type="radio" name="autocompleLessonsTopicsTincanny" value="1">
								<div class="uo-checkmark"></div>
								<span class="uo-label"><?php esc_html_e( 'Yes', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<label class="uo-radio">
								<input
								<?php
								if ( isset( \TINCANNYSNC\Admin\Options::$OPTION['autocompleLessonsTopicsTincanny'] ) && \TINCANNYSNC\Admin\Options::$OPTION['autocompleLessonsTopicsTincanny'] === '0' ) {
									echo ' checked="checked"';
								}
								?>
								type="radio" name="autocompleLessonsTopicsTincanny" value="0">
								<div class="uo-checkmark"></div>
								<span class="uo-label"><?php esc_html_e( 'No', 'uncanny-learndash-reporting' ); ?></span>
							</label>

							<div
								class="uo-admin-description"><?php esc_html_e( 'When set to Yes, lessons and topics will be marked complete immediately upon page load, even when a Tin Canny module is present on the page. (Requires Uncanny LearnDash Toolkit Pro with Autocomplete Lessons and Topics module activated)', 'uncanny-learndash-reporting' ); ?></div>
						</div>

						<!-- Submit -->
						<div class="uo-admin-field uo-admin-extra-space">
							<input type="submit" name="submit" id="submit" class="uo-admin-form-submit"
								   value="<?php esc_html_e( 'Save Changes', 'uncanny-learndash-reporting' ); ?>">
						</div>

					</div>
				</div>
			</div>

			<!-- Lightbox -->
			<div class="uo-admin-section">
				<div class="uo-admin-header">
					<div class="uo-admin-title"><?php esc_html_e( 'Lightbox', 'uncanny-learndash-reporting' ); ?></div>
				</div>
				<div class="uo-admin-block">
					<div class="uo-admin-form">

						<!-- glightbox -->
						<div class="uo-admin-field nivo">
							<div
								class="uo-admin-label"><?php esc_html_e( 'Transition', 'uncanny-learndash-reporting' ); ?></div>
							<select class="uo-admin-select" name="nivo-transition" id="nivo-transition">
								<?php foreach ( $glightbox_transitions as $key => $glightbox_transition ) : ?>
									<option
										value="<?php echo $glightbox_transition; ?>"
														  <?php
															if ( \TINCANNYSNC\Admin\Options::$OPTION['nivo-transition'] === $glightbox_transition ) {
																echo ' selected="selected"';
															}
															?>
									><?php echo $key; ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<!-- Lightbox size -->
						<div class="uo-admin-field">
							<div
								class="uo-admin-label"><?php esc_html_e( 'Default lightbox size', 'uncanny-learndash-reporting' ); ?></div>

							<div class="uo-admin-field uo-admin-field-inline">

								<div class="uo-admin-field-inline-row">
									<div class="uo-admin-field-part">
										<div
											class="uo-admin-label"><?php esc_html_e( 'Width', 'uncanny-learndash-reporting' ); ?></div>
									</div>
									<div class="uo-admin-field-part">
										<input class="uo-admin-input" type="text" name="width" id="width"
											   value="<?php echo \TINCANNYSNC\Admin\Options::$OPTION['width']; ?>"/>
									</div>
									<div class="uo-admin-field-part">
										<select class="uo-admin-select" name="width_type" id="width_type">
											<?php
											foreach ( $dimension_opts as $dimension_opt ) {
												if ( $dimension_opt === 'vh' ) {
													continue;
												}
												?>
												<option
													value="<?php echo $dimension_opt; ?>"
													<?php
													if ( \TINCANNYSNC\Admin\Options::$OPTION['width_type'] === $dimension_opt ) {
														echo ' selected="selected"';
													}
													?>
												><?php echo $dimension_opt; ?></option>
											<?php } // End foreach $dimension_opts ?>
										</select>
									</div>
								</div>

								<div class="uo-admin-field-inline-row">
									<div class="uo-admin-field-part">
										<div
											class="uo-admin-label"><?php esc_html_e( 'Height', 'uncanny-learndash-reporting' ); ?></div>
									</div>
									<div class="uo-admin-field-part">
										<input class="uo-admin-input" type="text" name="height" id="height"
											   value="<?php echo \TINCANNYSNC\Admin\Options::$OPTION['height']; ?>"/>
									</div>
									<div class="uo-admin-field-part">
										<select class="uo-admin-select" name="height_type" id="height_type">
										<?php
										foreach ( $dimension_opts as $dimension_opt ) {
											if ( $dimension_opt === 'vw' ) {
												continue;
											}
											?>
												<option
													value="<?php echo $dimension_opt; ?>"
													<?php
													if ( \TINCANNYSNC\Admin\Options::$OPTION['height_type'] === $dimension_opt ) {
														echo ' selected="selected"';
													}
													?>
												><?php echo $dimension_opt; ?></option>
										<?php } // End foreach $dimension_opts ?>
										</select>
									</div>
								</div>

							</div>
						</div>

						<!-- Submit -->
						<div class="uo-admin-field uo-admin-extra-space">
							<input type="submit" name="submit" id="submit" class="uo-admin-form-submit"
								   value="<?php esc_html_e( 'Save Changes', 'uncanny-learndash-reporting' ); ?>">
						</div>

					</div>
				</div>
			</div>

		<!-- Reset database -->
		<div class="uo-admin-section">
			<div class="uo-admin-header">
				<div class="uo-admin-title"><?php esc_html_e( 'Reset data', 'uncanny-learndash-reporting' ); ?></div>
			</div>
			<div class="uo-admin-block">
				<div class="uo-admin-form">
					<div class="uo-admin-field">
						<div
							class="uo-admin-label"><?php esc_html_e( 'Reset Tin Can data', 'uncanny-learndash-reporting' ); ?></div>
						<button class="uo-admin-form-submit uo-admin-form-submit-danger"
								id="btnResetTinCanData"><?php esc_html_e( 'Reset data', 'uncanny-learndash-reporting' ); ?></button>
						<div
							class="uo-admin-description"><?php esc_html_e( 'This will delete all Tin Can data. Use with caution!', 'uncanny-learndash-reporting' ); ?></div>
					</div>

					<div class="uo-admin-field">
						<div
							class="uo-admin-label"><?php esc_html_e( 'Reset xAPI Quiz data', 'uncanny-learndash-reporting' ); ?></div>
						<button class="uo-admin-form-submit uo-admin-form-submit-danger"
								id="btnResetQuizData"><?php esc_html_e( 'Reset Quiz Data', 'uncanny-learndash-reporting' ); ?></button>
						<div
							class="uo-admin-description"><?php esc_html_e( 'This will delete all xAPI Quiz data.  Use with caution!', 'uncanny-learndash-reporting' ); ?></div>
					</div>

					<div class="uo-admin-field">

						<div class="uo-admin-label"><?php esc_html_e( 'Reset bookmark data', 'uncanny-learndash-reporting' ); ?></div>
						<button class="uo-admin-form-submit uo-admin-form-submit-danger"
								id="btnResetBookmarkData"><?php esc_html_e( 'Reset bookmark data', 'uncanny-learndash-reporting' ); ?></button>
						<div
							class="uo-admin-description"><?php esc_html_e( 'This will delete all saved resume data for uploaded modules, forcing users to restart modules from the beginning.', 'uncanny-learndash-reporting' ); ?></div>
					</div>

					<div class="uo-admin-field">
						<div class="uo-admin-label"><?php _e( 'Purge statements', 'uncanny-learndash-reporting' ); ?></div>
						<?php if( is_array($statement_verbs) && ! empty($statement_verbs) ) : ?>
						<select id="uotc_purge_verb" class="uo-admin-select">
							<option value='' disabled selected><?php echo esc_html__( 'Choose a verb', 'uncanny-learndash-reporting' ); ?></option>
							<?php foreach ( $statement_verbs as $statement_verb ) : ?>
							<option value="<?php echo esc_attr( $statement_verb ); ?>"><?php echo esc_attr( ucfirst($statement_verb) ); ?></option>
							<?php endforeach; ?>
						</select>
						<br /><br /><button class="uo-admin-form-submit uo-admin-form-submit-danger"
								id="btnPurgeVerbStatements"><?php _e( 'Purge statements', 'uncanny-learndash-reporting' ); ?></button>
						<div
							class="uo-admin-description"><?php _e( 'This will delete all saved SCORM and xAPI statements with the selected verb.', 'uncanny-learndash-reporting' ); ?></div>
						<?php else: ?>
							<div
							class="uo-admin-description"><?php _e( 'No statements found in database.', 'uncanny-learndash-reporting' ); ?></div>
						<?php endif; ?>
					</div>

					<div class="uo-admin-field">
						<div class="uo-admin-label"><?php esc_html_e( 'Purge Answered statements', 'uncanny-learndash-reporting' ); ?></div>
						<button class="uo-admin-form-submit uo-admin-form-submit-danger"
								id="btnPurgeAnswered"><?php esc_html_e( 'Purge Answered statements', 'uncanny-learndash-reporting' ); ?></button>
						<div
							class="uo-admin-description"><?php esc_html_e( 'This will delete all saved xAPI statements with the "Answered" verb.', 'uncanny-learndash-reporting' ); ?></div>
					</div>
				</div>
			</div>
		</div>


		<!-- Delete Tin Canny Data -->
		<div class="uo-admin-section">
			<div class="uo-admin-header">
				<div class="uo-admin-title"><?php esc_html_e( 'Delete Tin Canny Data', 'uncanny-learndash-reporting' ); ?></div>
			</div>
			<div class="uo-admin-block">
				<div class="uo-admin-form">
					<div class="uo-admin-field">
						<div class="uo-admin-label"><?php esc_html_e( 'Delete all Tin Canny data on uninstall', 'uncanny-learndash-reporting' ); ?></div>
						<label class="uo-checkbox">
							<input type="checkbox" name="delete_tincanny_data" id="delete_tincanny_data" value="1" <?php checked( 'yes' === get_option( 'tincanny_delete_data_on_uninstall', 'no' ) ); ?>>
							<div class="uo-checkmark"></div>
							<span class="uo-label"><?php esc_html_e( 'Delete all data on uninstall', 'uncanny-learndash-reporting' ); ?></span>
						</label>
						<div class="uo-admin-description">
							<?php esc_html_e( 'WARNING: If you enable this option and uninstall the plugin, ALL Tin Canny data will be permanently deleted from your database. This includes all SCORM/xAPI statements, quiz data, uploaded modules, module bookmarks, plugin settings and other related data. This action cannot be undone!', 'uncanny-learndash-reporting' ); ?>
						</div>
					</div>

					<!-- Submit -->
					<div class="uo-admin-field uo-admin-extra-space">
						<input type="submit" name="submit" id="submit" class="uo-admin-form-submit uo-admin-form-submit-danger"
								value="<?php esc_html_e( 'Save Settings', 'uncanny-learndash-reporting' ); ?>">
					</div>

				</div>
			</div>
		</div>
		</form>

	</div>
</div>
<script>
	jQuery(document).ready(function () {
		jQuery('input:radio[name="tinCanActivation"]').change(
			function(){
				if (this.checked && this.value == '1') {
					jQuery('#mark_complete_button_box').show();
				} else {
					jQuery('#mark_complete_button_box').hide();
				}
			});
	});
</script>
