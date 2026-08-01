<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}
// New user template variables
$uo_import_users_send_new_user_email    = ( get_option( 'uo_import_users_send_new_user_email' ) === 'true' ) ? 'checked' : '';
$uo_import_users_new_user_email_subject = get_option( 'uo_import_users_new_user_email_subject', '' );
$uo_import_users_new_user_email_body    = get_option( 'uo_import_users_new_user_email_body', '' );

// Updated user template variables
$uo_import_users_send_updated_user_email    = ( get_option( 'uo_import_users_send_updated_user_email' ) === 'true' ) ? 'checked' : '';
$uo_import_users_updated_user_email_subject = get_option( 'uo_import_users_updated_user_email_subject', '' );
$uo_import_users_updated_user_email_body    = get_option( 'uo_import_users_updated_user_email_body', '' );

?>

<form method="post" action="options.php" class="uncannyowl-form">

	<div class="uncannyowl-admin-section"> 
		<div class="uncannyowl-admin-header">
			<div class="uncannyowl-admin-title"><?php esc_attr_e( 'New User Email Template', 'uncanny-pro-toolkit' ); ?></div>
		</div>
		<div class="uncannyowl-admin-block">
			<div class="uncannyowl-admin-form">
				<div class="uncannyowl-admin-field">
					<div class="uncannyowl-form-field  pt-0">
						<label class="uncannyowl-form-label">
							<?php esc_attr_e( 'Email to New Users', 'uncanny-pro-toolkit' ); ?>
						</label>
						<div class="uncannyowl-form-input-group">
							<label class="uncannyowl-checkbox">
								<input type="checkbox" name="uo_import_email_send_new_users"
									id="uo_import_email_send_new_users" <?php echo $uo_import_users_send_new_user_email ?> />
								<span class="uncannyowl-checkbox-label"><?php esc_attr_e( 'Send Email', 'uncanny-pro-toolkit' ); ?></span>
							</label>
						</div>
					</div> 
				</div>

				<div class="uncannyowl-admin-field">
					<div class="uncannyowl-form-field  pt-0"> 
						<label class="uncannyowl-form-label">
							<?php esc_attr_e( 'Subject', 'uncanny-pro-toolkit' ); ?>
						</label>
						<div class="uncannyowl-form-input-group">
							<input type="text" name="uo_import_email_new_users_subject"
								id="uo_import_email_new_users_subject" class="uncannyowl-input"
								value="<?php echo $uo_import_users_new_user_email_subject; ?>"/>
						</div>
					</div>
				</div>

				<div class="uncannyowl-admin-field">
					<div class="uncannyowl-form-field  pt-0">
						<label class="uncannyowl-form-label">
							<?php esc_attr_e( 'Body', 'uncanny-pro-toolkit' ); ?>
						</label>
						<div class="uncannyowl-form-input-group" style="display: flex; gap: 20px; align-items: flex-start;">
							<div style="flex: 0 0 200px;">
								<h5 class="uncannyowl-font-medium uncannyowl-text-muted"><?php esc_attr_e( 'Variables', 'uncanny-pro-toolkit' ); ?></h5>
								<ul class="import-user-list uncannyowl-list uncannyowl-list--bulleted">
									<li>%Site URL%</li>
									<li>%Login URL%</li>
									<li>%Email%</li>
									<li>%Username%</li>
									<li>%First Name%</li>
									<li>%Last Name%</li>
									<li>%Display Name%</li>
									<li>%Password%</li>
									<li>%Reset Password Link%</li>
									<li>%Password Reset URL%</li>
									<?php if ( \uncanny_pro_toolkit\ImportLearndashUsersFromCsv::is_learndash_active() ) { ?>
										<li>%LD Courses%</li>
										<li>%LD Groups%</li>
									<?php } ?>
								</ul>
							</div>
							<div style="flex: 1;">
								<?php wp_editor( $uo_import_users_new_user_email_body, 'uo_import_users_new_user_email_body' ); ?>
							</div>
						</div>
					</div>					
				</div>

				<div class="uncannyowl-admin-field">
					<div class="uncannyowl-form-field  pt-0">
						<label class="uncannyowl-form-label">
							<?php esc_attr_e( 'Test Email Address:', 'uncanny-pro-toolkit' ); ?>
						</label>
						<div class="uncannyowl-form-input-group">
							<input type="text" name="uo_import_email_new_users_test_email"
								id="uo_import_email_new_users_test_email" class="uncannyowl-input"/>
							<input type="submit" id="btn-test_new_user_template" class="uncannyowl-btn uncannyowl-btn--secondary uncannyowl-btn--sm"
								value="<?php esc_attr_e( 'Send Test Email', 'uncanny-pro-toolkit' ); ?>"/>
						</div>
					</div>	
				</div>
			</div>	
		</div>	
	</div>
	<!-- End uncannyowl-admin-section -->

	<div class="uncannyowl-admin-section">
		 <div class="uncannyowl-admin-header">
			<div class="uncannyowl-admin-title"><?php esc_attr_e( 'Updated User Email Template', 'uncanny-pro-toolkit' ); ?></div>
		</div>
		<div class="uncannyowl-admin-block">
			<div class="uncannyowl-admin-form">
				<div class="uncannyowl-admin-field">
					<div class="uncannyowl-form-field  pt-0">
						<label class="uncannyowl-form-label">
							<?php esc_attr_e( 'Email to Updated Users', 'uncanny-pro-toolkit' ); ?>
						</label>
						<div class="uncannyowl-form-input-group">
							<label class="uncannyowl-checkbox">
								<input type="checkbox" name="uo_import_email_send_updated_users"
									id="uo_import_email_send_updated_users" <?php echo $uo_import_users_send_updated_user_email; ?> />
								<span class="uncannyowl-checkbox-label"><?php esc_attr_e( 'Send Email', 'uncanny-pro-toolkit' ); ?></span>
							</label>
						</div>
					</div>	
				</div>

				<div class="uncannyowl-admin-field">
					<div class="uncannyowl-form-field  pt-0">
						<label class="uncannyowl-form-label">
							<?php esc_attr_e( 'Subject', 'uncanny-pro-toolkit' ); ?>
						</label>
						<div class="uncannyowl-form-input-group">
							<input type="text" name="uo_import_email_updated_users_subject"
								id="uo_import_email_updated_users_subject" class="uncannyowl-input"
								value="<?php echo $uo_import_users_updated_user_email_subject; ?>"/>
						</div>
					</div>	
				</div>

				<div class="uncannyowl-admin-field">
					<div class="uncannyowl-form-field  pt-0">
						<label class="uncannyowl-form-label">
							<?php esc_attr_e( 'Body', 'uncanny-pro-toolkit' ); ?>
						</label>
						<div class="uncannyowl-form-input-group" style="display: flex; gap: 20px; align-items: flex-start;">
							<div style="flex: 0 0 200px;">
								<h5 class="uncannyowl-font-medium uncannyowl-text-muted"><?php esc_attr_e( 'Variables', 'uncanny-pro-toolkit' ); ?></h5>
								<ul class="import-user-list uncannyowl-list uncannyowl-list--bulleted">
									<li>%Site URL%</li>
									<li>%Login URL%</li>
									<li>%Email%</li>
									<li>%Username%</li>
									<li>%First Name%</li>
									<li>%Last Name%</li>
									<li>%Display Name%</li>
									<li>%Password%</li>
									<li>%Reset Password Link%</li>
									<li>%Password Reset URL%</li>
									<?php if ( \uncanny_pro_toolkit\ImportLearndashUsersFromCsv::is_learndash_active() ) { ?>
										<li>%LD Courses%</li>
										<li>%LD Groups%</li>
									<?php } ?>
								</ul>
							</div>
							<div style="flex: 1;">
								<?php wp_editor( $uo_import_users_updated_user_email_body, 'uo_import_users_updated_user_email_body' ); ?>
							</div>
						</div>
					</div>
				</div>

				<div class="uncannyowl-admin-field">
					<div class="uncannyowl-form-field pt-0">
						<label class="uncannyowl-form-label">
							<?php esc_attr_e( 'Test Email Address:', 'uncanny-pro-toolkit' ); ?>
						</label>
						<div class="uncannyowl-form-input-group">
							<input type="text" name="uo_import_email_updated_users_test_email"
								id="uo_import_email_updated_users_test_email" class="uncannyowl-input"/>
							<input type="submit" id="btn-test_updated_user_template" class="uncannyowl-btn uncannyowl-btn--secondary uncannyowl-btn--sm"
								value="<?php esc_attr_e( 'Send Test Email', 'uncanny-pro-toolkit' ); ?>"/>
						</div>
					</div>	
				</div>
			</div>						
		</div>						
	</div>
<!-- End uncannyowl-admin-section -->

	<div class="uncannyowl-form-actions">
		<input type="submit" id="btn-save_template" class="uncannyowl-btn uncannyowl-btn--primary"
			   value="<?php esc_attr_e( 'Save Changes', 'uncanny-pro-toolkit' ); ?>"/>
	</div>

</form>
