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
			<input type="hidden" id="action" name="action" value="save-ceu-setting"/>

			<?php

			// Add admin header and tabs
			$tab_active = 'uncanny-ceu';
			include Utilities::get_template( 'admin-header.php' );

			?>

			<!-- Messages -->
			<?php

			if ( '' !== AdminPage::$ceu_management_admin_page['text']['message'] ) {
				?>
				<div class="updated rest-message">
					<?php echo AdminPage::$ceu_management_admin_page['text']['message']; ?>
				</div>
				<?php
			}

			?>

			<!-- License Settings -->
			<div class="uo-admin-section">
				<div class="uo-admin-header">
					<div
						class="uo-admin-title"><?php echo __( 'License', 'uncanny-ceu' ); ?></div>
				</div>
				<?php
				AdminPage::$licensing_class->license_page();
				?>
			</div>
			<!-- LearnDash Group Settings -->
			<div class="uo-admin-section">
				<div class="uo-admin-header">
					<div
						class="uo-admin-title"><?php echo AdminPage::$ceu_management_admin_page['text']['page_settings']; ?></div>
				</div>
				<div class="uo-admin-block">
					<div class="uo-admin-form">

						<div class="uo-admin-field">
							<div
								class="uo-admin-label"><?php echo AdminPage::$ceu_management_admin_page['text']['credit_designation_label']; ?></div>

							<input class="uo-admin-input" type="text" name="credit_designation_label"
								   id="credit_designation_label"
								   value="<?php echo AdminPage::$ceu_management_admin_page['credit_designation_label']; ?>"/>
						</div>

						<div class="uo-admin-field">
							<div
								class="uo-admin-label"><?php echo AdminPage::$ceu_management_admin_page['text']['credit_designation_label_plural']; ?></div>

							<input class="uo-admin-input" type="text" name="credit_designation_label_plural"
								   id="credit_designation_label_plural"
								   value="<?php echo AdminPage::$ceu_management_admin_page['credit_designation_label_plural']; ?>"/>
						</div>

						<div class="uo-admin-field">
							<div
								class="uo-admin-label"><?php echo AdminPage::$ceu_management_admin_page['text']['ceu_rollover_date']; ?></div>

							<input class="uo-admin-input" type="text" id="ceu_rollover_date" name="ceu_rollover_date"
								   value="<?php echo AdminPage::$ceu_management_admin_page['ceu_rollover_date']; ?>"/>

							<script>
								jQuery(document).ready(function ($) {
									$('#ceu_rollover_date').datepicker({
										changeMonth: true,
										changeYear: true,
										showButtonPanel: true,
										dateFormat: 'dd/mm'
									}).on('change, blur', function () {
										if ('' !== $(this).val()) {
											var curDate = $(this).datepicker("getDate");
											var dateformat = /^(0?[1-9]|[12][0-9]|3[01])[\/](0?[1-9]|1[012])$/
											var result = dateformat.test($(this).val())
											if (!result) {
												$(this).datepicker("setDate", curDate);
											}
										}
									})
								})
							</script>
							<style>.ui-datepicker-year {
									display: none !important;
								}</style>
						</div>

						<div class="uo-admin-field uo-admin-field--error"></div>

						<div class="uo-admin-field">
							<button class="uo-admin-form-submit submit-rest-form"
									data-end-point="save_admin_settings"><?php echo AdminPage::$ceu_management_admin_page['text']['save_changes']; ?></button>
						</div>
					</div>
				</div>
			</div>

			<!-- Credit report settings -->
			<div class="uo-admin-section">
				<div class="uo-admin-header">
					<div class="uo-admin-title"><?php echo __( 'Credit Report Settings', 'uncanny-ceu' ); ?></div>
				</div>
				<div class="uo-admin-block">
					<div class="uo-admin-form">

						<div class="uo-admin-field">
							<label class="uo-checkbox">
								<input type="checkbox" name="ceu_show_avatar"
									   id="ceu_show_avatar" <?php echo 'on' === get_option( 'ceu_show_avatar', 'off' ) ? 'checked' : '' ?>/>
								<div class="uo-checkmark"></div>
								<span class="uo-label">
										<?php echo __( 'Show user avatars', 'uncanny-ceu' ); ?>
									</span>
							</label>
						</div>
						<div class="uo-admin-field">
							<h3><?php echo __( 'Report Mode', 'uncanny-ceu' ) ?>:</h3>
							<?php $mode = get_option( 'ceu_report_mode', 'meta' ); ?>
							<label class="uo-checkbox">
								<select name="ceu_report_mode" id="ceu_report_mode" class="uo-admin-select">
									<option
										value="legacy" <?php echo 'legacy' === $mode ? 'selected' : '' ?>>
										<?php echo __( 'Legacy', 'uncanny-ceu' ) ?>
									</option>
									<option value="meta" <?php echo 'meta' === $mode ? 'selected' : '' ?>>
										<?php echo __( 'Performance (Recommended)', 'uncanny-ceu' ) ?>
									</option>
									<!--									<option value="latest" -->
									<?php //echo 'latest' === $mode ? 'selected' : ''
									?><!-->-->
									<!--										--><?php //echo __( 'Latest (Beta)', 'uncanny-ceu' )
									?>
									<!--									</option>-->
									<?php //if ( ! defined( 'DISABLE_WP_CRON' ) || false === DISABLE_WP_CRON ) {
									?>
									<option value="json" <?php echo 'json' === $mode ? 'selected' : '' ?>>
										<?php echo __( 'Ludicrous (Fastest, report updates hourly)', 'uncanny-ceu' ) ?>
									</option>
									<?php //}
									?>
								</select>
							</label>
						</div>
						<div class="uo-admin-field">
							<h3><?php echo __( 'Default Date Range', 'uncanny-ceu' ) ?>:</h3>
							<?php $mode = get_option( 'ceu_default_date_range', 'half_year' ); ?>
							<label class="uo-checkbox">
								<select name="ceu_default_date_range" id="ceu_default_date_range"
										class="uo-admin-select">
									<option
										value="week" <?php echo 'week' === $mode ? 'selected' : '' ?>>
										<?php echo __( 'Last 7 Days', 'uncanny-ceu' ) ?>
									</option>
									<option
										value="month" <?php echo 'month' === $mode ? 'selected' : '' ?>>
										<?php echo __( 'Last 30 Days', 'uncanny-ceu' ) ?>
									</option>
									<option
										value="quarter" <?php echo 'quarter' === $mode ? 'selected' : '' ?>>
										<?php echo __( 'Last 90 Days', 'uncanny-ceu' ) ?>
									</option>
									<option
										value="half_year" <?php echo 'half_year' === $mode ? 'selected' : '' ?>>
										<?php echo __( 'Last 180 Days', 'uncanny-ceu' ) ?>
									</option>
									<option
										value="year" <?php echo 'year' === $mode ? 'selected' : '' ?>>
										<?php echo __( 'Last 365 Days', 'uncanny-ceu' ) ?>
									</option>
									<option
										value="all" <?php echo 'all' === $mode ? 'selected' : '' ?>>
										<?php echo __( 'All time', 'uncanny-ceu' ) ?>
									</option>
								</select>
							</label>
						</div>
						<div class="uo-admin-field">
							<h3><?php echo __( 'Default Page Length', 'uncanny-ceu' ) ?>:</h3>
							<?php $mode = (int) get_option( 'ceu_default_page_length', 50 ); ?>
							<label class="uo-checkbox">
								<select name="ceu_default_page_length" id="ceu_default_page_length"
										class="uo-admin-select">
									<option
										value="10" <?php echo 10 === $mode ? 'selected' : '' ?>>
										<?php echo __( '10', 'uncanny-ceu' ) ?>
									</option>
									<option
										value="25" <?php echo 25 === $mode ? 'selected' : '' ?>>
										<?php echo __( '25', 'uncanny-ceu' ) ?>
									</option>
									<option
										value="50" <?php echo 50 === $mode ? 'selected' : '' ?>>
										<?php echo __( '50', 'uncanny-ceu' ) ?>
									</option>
									<option
										value="100" <?php echo 100 === $mode ? 'selected' : '' ?>>
										<?php echo __( '100', 'uncanny-ceu' ) ?>
									</option>
									<option
										value="200" <?php echo 200 === $mode ? 'selected' : '' ?>>
										<?php echo __( '200', 'uncanny-ceu' ) ?>
									</option>
								</select>
							</label>
						</div>
						<?php if ( CourseReportAPI::is_groups_hierarchical_enabled() ) : ?>
							<div class="uo-admin-field">
								<label class="uo-checkbox">
									<input type="checkbox" name="ceu_credit_report_include_group_children"
										id="ceu_credit_report_include_group_children" <?php echo 'on' === get_option( 'ceu_credit_report_include_group_children', 'off' ) ? 'checked' : '' ?>/>
									<div class="uo-checkmark"></div>
									<span class="uo-label">
										<?php echo __( 'Include results for child groups when a parent group is selected', 'uncanny-ceu' ); ?>
										</span>
								</label>
							</div>
						<?php endif; ?>
						<div class="uo-admin-field uo-admin-field--error"></div>

						<div class="uo-admin-field">
							<button class="uo-admin-form-submit submit-rest-form"
									data-end-point="save_admin_settings"><?php echo AdminPage::$ceu_management_admin_page['text']['save_changes']; ?></button>
						</div>
					</div>
				</div>
			</div>

			<!-- Email Reminder Settings -->
			<?php if ( '' !== AdminPage::$ceu_management_admin_page['ceu_rollover_date'] ) { ?>

				<div class="uo-admin-section">
					<div class="uo-admin-header">
						<div
							class="uo-admin-title"><?php echo AdminPage::$ceu_management_admin_page['text']['email_reminders']; ?></div>
					</div>
					<div class="uo-admin-block">
						<div class="uo-admin-form">

							<div class="uo-admin-field">
								<label class="uo-checkbox">
									<input type="checkbox" name="send_email_reminders"
										   id="send_email_reminders" <?php echo AdminPage::$ceu_management_admin_page['send_email_reminders']; ?> />
									<div class="uo-checkmark"></div>
									<span class="uo-label">
										<?php echo AdminPage::$ceu_management_admin_page['text']['send_email_reminders']; ?>
									</span>
								</label>
							</div>

							<div class="uo-admin-field">
								<div
									class="uo-admin-label"><?php echo AdminPage::$ceu_management_admin_page['text']['days_before_rollover_date_for_reminder']; ?></div>

								<input class="uo-admin-input" type="text" name="days_before_rollover_date_for_reminder"
									   id="days_before_rollover_date_for_reminder"
									   value="<?php echo AdminPage::$ceu_management_admin_page['days_before_rollover_date_for_reminder']; ?>"/>
							</div>

							<div class="uo-admin-field">
								<div
									class="uo-admin-label"><?php echo AdminPage::$ceu_management_admin_page['text']['reminder_email_subject']; ?></div>

								<input class="uo-admin-input" type="text" name="reminder_email_subject"
									   id="reminder_email_subject"
									   value="<?php echo AdminPage::$ceu_management_admin_page['reminder_email_subject']; ?>"/>
							</div>

							<div class="uo-admin-field">
								<div
									class="uo-admin-label"><?php echo AdminPage::$ceu_management_admin_page['text']['reminder_email_body']; ?></div>

								<div class="uo-admin-tags">
									<div class="uo-admin-copy-to-clipboard">
										<span class="uo-admin-ctc-tooltip">Copy to clipboard</span>
										<input class="uo-admin-copy-to-clipboard-input s__w" value="#user_email"
											   readonly>
										<div class="uo-admin-copy-to-clipboard__render">#user_email</div>
									</div>

									<div class="uo-admin-copy-to-clipboard">
										<span class="uo-admin-ctc-tooltip">Copy to clipboard</span>
										<input class="uo-admin-copy-to-clipboard-input s__w" value="#first_name"
											   readonly>
										<div class="uo-admin-copy-to-clipboard__render">#first_name</div>
									</div>

									<div class="uo-admin-copy-to-clipboard">
										<span class="uo-admin-ctc-tooltip">Copy to clipboard</span>
										<input class="uo-admin-copy-to-clipboard-input s__w" value="#last_name"
											   readonly>
										<div class="uo-admin-copy-to-clipboard__render">#last_name</div>
									</div>

									<div class="uo-admin-copy-to-clipboard">
										<span class="uo-admin-ctc-tooltip">Copy to clipboard</span>
										<input class="uo-admin-copy-to-clipboard-input l__w" value="#credits_required"
											   readonly>
										<div class="uo-admin-copy-to-clipboard__render">#credits_required</div>
									</div>

									<div class="uo-admin-copy-to-clipboard">
										<span class="uo-admin-ctc-tooltip">Copy to clipboard</span>
										<input class="uo-admin-copy-to-clipboard-input m__w" value="#ceu_earned"
											   readonly>
										<div class="uo-admin-copy-to-clipboard__render">#ceu_earned</div>
									</div>

									<div class="uo-admin-copy-to-clipboard">
										<span class="uo-admin-ctc-tooltip">Copy to clipboard</span>
										<input class="uo-admin-copy-to-clipboard-input vl__w"
											   value="#earned_since_rollover" readonly>
										<div class="uo-admin-copy-to-clipboard__render">#earned_since_rollover</div>
									</div>

									<div class="uo-admin-copy-to-clipboard">
										<span class="uo-admin-ctc-tooltip">Copy to clipboard</span>
										<input class="uo-admin-copy-to-clipboard-input m__w" value="#rollover_date"
											   readonly>
										<div class="uo-admin-copy-to-clipboard__render">#rollover_date</div>
									</div>

									<div class="uo-admin-copy-to-clipboard">
										<span class="uo-admin-ctc-tooltip">Copy to clipboard</span>
										<input class="uo-admin-copy-to-clipboard-input s__w" value="#ceu_plural"
											   readonly>
										<div class="uo-admin-copy-to-clipboard__render">#ceu_plural</div>
									</div>

									<div class="uo-admin-copy-to-clipboard">
										<span class="uo-admin-ctc-tooltip">Copy to clipboard</span>
										<input class="uo-admin-copy-to-clipboard-input s__w" value="#ceu_single"
											   readonly>
										<div class="uo-admin-copy-to-clipboard__render">#ceu_single</div>
									</div>

								</div>

								<textarea class="uo-admin-textarea" name="reminder_email_body"
										  id="reminder_email_body"><?php echo AdminPage::$ceu_management_admin_page['reminder_email_body']; ?></textarea>
							</div>

							<div class="uo-admin-field uo-admin-field--error"></div>

							<div class="uo-admin-field">
								<button class="uo-admin-form-submit submit-rest-form"
										data-end-point="save_admin_settings">
									<?php echo AdminPage::$ceu_management_admin_page['text']['save_changes']; ?>
								</button>
							</div>

						</div>
					</div>
				</div>

			<?php } ?>
		</div>

		<form name="admin-ceu-email-settings" action="<?php echo admin_url( 'admin.php' ) ?>?page=uncanny-ceu"
			  method="post">
			<?php wp_nonce_field( 'uncanny-ceu', 'uncanny_email_settings_nonce' ); ?>

			<!-- LearnDash Group Settings -->
			<div class="uo-admin-section">
				<div class="uo-admin-header">
					<div class="uo-admin-title">Certificate Email Settings</div>
				</div>
				<div class="uo-admin-block">
					<div class="uo-admin-form">

						<div class="uo-admin-field">
							<label class="uo-checkbox">
								<input type="checkbox" name="uncanny_certificate_email_enabled"
									   id="uncanny_certificate_email_enabled" <?php if ( 'yes' === get_option( 'uncanny-ceu-multiple-certs-enabled', 'no' ) ) {
									echo ' checked="checked"';
								} ?> value="yes"/>
								<div class="uo-checkmark"></div>
								<span class="uo-label">
									Enable Multi Course certificate
								</span>
							</label>
						</div>

						<div class="uo-admin-field">
							<label class="uo-checkbox">
								<input type="checkbox" name="uncanny_ceu_certificate_email_enabled"
									   id="uncanny_ceu_certificate_email_enabled" <?php if ( 'yes' === get_option( 'uncanny-ceu-credits-cert-enabled', 'no' ) ) {
									echo ' checked="checked"';
								} ?> value="yes"/>
								<div class="uo-checkmark"></div>
								<span class="uo-label">
									Enable CEU Credits certificate
								</span>
							</label>
						</div>

						<div class="uo-admin-field">
							<div class="uo-admin-separator"></div>
						</div>

						<div class="uo-admin-field">
							<label class="uo-checkbox">
								<input type="checkbox" name="uncanny_certificate_do_not_store"
									   id="uncanny_certificate_do_not_store" <?php if ( 'yes' === get_option( 'uncanny-ceu-multiple-do-not-store-certs', 'no' ) ) {
									echo ' checked="checked"';
								} ?> value="yes"/>
								<div class="uo-checkmark"></div>
								<span class="uo-label">
									Do not store certificates on server
								</span>
							</label>

							<div class="uo-admin-description">By default, certificates are stored at: &lt;site root&gt;/wp-content/uploads/ceu-certs/</div>
						</div>

						<div class="uo-admin-field">
							<label class="uo-checkbox">
								<input type="checkbox" name="uncanny_certificate_notify_site_admin"
									   id="uncanny_certificate_notify_site_admin" <?php if ( 'yes' === get_option( 'uncanny-ceu-multiple-notify-admin', 'no' ) ) {
									echo ' checked="checked"';
								} ?> value="yes"/>
								<div class="uo-checkmark"></div>
								<span class="uo-label">
									Send E-mail notification to Site Admin
								</span>
							</label>
						</div>

						<div class="uo-admin-field">
							<label class="uo-checkbox">
								<input type="checkbox" name="uncanny_certificate_notify_group_leader"
									   id="uncanny_certificate_notify_group_leader" <?php if ( 'yes' === get_option( 'uncanny-ceu-multiple-notify-group-leader', 'no' ) ) {
									echo ' checked="checked"';
								} ?> value="yes"/>
								<div class="uo-checkmark"></div>
								<span class="uo-label">
									Send E-mail notification to Group Leader
								</span>
							</label>
						</div>

						<div class="uo-admin-field">
							<div class="uo-admin-label">E-mail Subject</div>

							<input class="uo-admin-input" type="text" name="uncanny_certificate_email_subject"
								   id="uncanny_certificate_email_subject"
								   value="<?php echo AdminPage::$ceu_management_admin_page['uncanny-ceu-multiple-certs-subject']; ?>"/>
						</div>

						<div class="uo-admin-field">
							<div class="uo-admin-label">E-mail Message</div>

							<div class="uo-admin-tags">
								<div class="uo-admin-copy-to-clipboard">
									<span class="uo-admin-ctc-tooltip">Copy to clipboard</span>
									<input class="uo-admin-copy-to-clipboard-input s__w" value="%User%" readonly>
									<div class="uo-admin-copy-to-clipboard__render">%User%</div>
								</div>

								<div class="uo-admin-copy-to-clipboard">
									<span class="uo-admin-ctc-tooltip">Copy to clipboard</span>
									<input class="uo-admin-copy-to-clipboard-input vl__w" value="%User First Name%"
										   readonly>
									<div class="uo-admin-copy-to-clipboard__render">%User First Name%</div>
								</div>

								<div class="uo-admin-copy-to-clipboard">
									<span class="uo-admin-ctc-tooltip">Copy to clipboard</span>
									<input class="uo-admin-copy-to-clipboard-input vl__w" value="%User Last Name%"
										   readonly>
									<div class="uo-admin-copy-to-clipboard__render">%User Last Name%</div>
								</div>

								<div class="uo-admin-copy-to-clipboard">
									<span class="uo-admin-ctc-tooltip">Copy to clipboard</span>
									<input class="uo-admin-copy-to-clipboard-input m__w" value="%User Email%" readonly>
									<div class="uo-admin-copy-to-clipboard__render">%User Email%</div>
								</div>

								<div class="uo-admin-copy-to-clipboard">
									<span class="uo-admin-ctc-tooltip">Copy to clipboard</span>
									<input class="uo-admin-copy-to-clipboard-input s__w" value="%Courses%" readonly>
									<div class="uo-admin-copy-to-clipboard__render">%Courses%</div>
								</div>
							</div>

							<textarea class="uo-admin-textarea"
									  name="uncanny_ceu_multiple_certs_message"><?php echo AdminPage::$ceu_management_admin_page['uncanny-ceu-multiple-certs-message']; ?></textarea>
						</div>

						<div class="uo-admin-field">
							<input type="hidden" id="action" name="action" value="save-ceu-email-setting"/>
							<input type="hidden" id="page" name="page" value="<?php echo $_GET['page'] ?>"/>

							<button id="btn-save_template" class="uo-admin-form-submit submit-rest-form"
									data-end-point="save_email_settings">Save Changes
							</button>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
