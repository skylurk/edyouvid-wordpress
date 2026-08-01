<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( \uncanny_pro_toolkit\ImportLearndashUsersFromCsv::is_learndash_active() ) {
	$csv_sample_file_link = plugins_url( basename( dirname( UO_FILE ) ) ) . '/src/assets/legacy/backend/csv/import_user_sample.csv';
} else {
	$csv_sample_file_link = plugins_url( basename( dirname( UO_FILE ) ) ) . '/src/assets/legacy/backend/csv/import_user_sample_generic.csv';

}

?>

<div id="import-users-instructions">
	<p class="uncannyowl-text-neutral">
		<em>
			<?php
			if ( \uncanny_pro_toolkit\ImportLearndashUsersFromCsv::is_learndash_active() ) {
				esc_attr_e( 'This is a complex module that allows users to be added to courses and LearnDash Groups from a CSV file. The Options tab includes settings that control how updates to users are managed and bulk imported into specific courses and groups.', 'uncanny-pro-toolkit' );
			} else {
				esc_attr_e( 'This is a complex module that allows users to be added to your site from a CSV file. The Options tab includes settings that control how updates to users are managed and bulk imported.', 'uncanny-pro-toolkit' );
			}
			?>
			<?php esc_attr_e( 'The Emails tab includes settings that control whether to send notifications to imported users and the email templates. These settings should be reviewed before proceeding with the import. Download the sample CSV on this page to see examples of the fields that can be included.', 'uncanny-pro-toolkit' ); ?>
		</em>
	</p>

	<p class="uncannyowl-text-neutral mb-lg">
		<?php
		$kb_link = '<a href="https://www.uncannyowl.com/knowledge-base/import-learndash-users/" class="uncannyowl-text-primary">' . esc_attr__( 'Knowledge Base article', 'uncanny-pro-toolkit' ) . '</a>';
		echo sprintf( __( 'You should also review the %s which includes more detailed explanations and specific instructions for different use cases.', 'uncanny-pro-toolkit' ), $kb_link ); ?>
	</p>

 
	<div class="uncannyowl-admin-section">
		<div class="uncannyowl-admin-header">
			<div class="uncannyowl-admin-title">
				<span class="uncannyowl-avatar">1</span>
				<div class="steps-description"><?php esc_attr_e( 'Review Options', 'uncanny-pro-toolkit' ); ?></div>
			</div>
		</div>
		<div class="uncannyowl-admin-block">
			<div class="uncannyowl-admin-form">
				<div class="uncannyowl-admin-field">
					<p class="uncannyowl-text-neutral"><?php esc_attr_e( 'Use this tab to configure:', 'uncanny-pro-toolkit' ); ?></p>
					<ul class="import-user-list uncannyowl-list uncannyowl-list--bulleted">
						<li><?php esc_attr_e( 'Whether to update or ignore users that already exist on your website', 'uncanny-pro-toolkit' ); ?></li>
						<li><?php esc_attr_e( 'Role to assign to imported users', 'uncanny-pro-toolkit' ); ?></li>
						<?php
						if ( \uncanny_pro_toolkit\ImportLearndashUsersFromCsv::is_learndash_active() ) {
							?>
							<li><?php esc_attr_e( 'Course(s) to enroll imported users into', 'uncanny-pro-toolkit' ); ?></li>
							<li><?php esc_attr_e( 'LearnDash Group(s) to assign imported users to', 'uncanny-pro-toolkit' ); ?></li>
							<?php
						} ?>
					</ul> 
				</div>
				 
			</div>
		</div>
	</div>
  

<!-- start -->

	<div class="uncannyowl-admin-section">
		<div class="uncannyowl-admin-header">
			<div class="uncannyowl-admin-title">
				<span class="uncannyowl-avatar">2</span>
				<div class="steps-description"><?php esc_attr_e( 'Review Email Settings', 'uncanny-pro-toolkit' ); ?></div>
	 		</div>
		</div>
		<div class="uncannyowl-admin-block">
			<div class="uncannyowl-admin-form">
				<div class="uncannyowl-admin-field">
					<p class="uncannyowl-text-neutral"><?php esc_attr_e( 'Use this tab to:', 'uncanny-pro-toolkit' ); ?></p>
					<ul class="import-user-list uncannyowl-list uncannyowl-list--bulleted">
						<li><?php esc_attr_e( 'Enable email notifications to new and/or updated users', 'uncanny-pro-toolkit' ); ?></li>
						<li><?php esc_attr_e( 'Customize email templates', 'uncanny-pro-toolkit' ); ?></li>
					</ul>
				</div>
				 
			</div>
		</div>
	</div>
	<!-- end -->

<!-- start -->
<div class="uncannyowl-admin-section">
		<div class="uncannyowl-admin-header uncanny-admin-flex-header">
			<div class="uncannyowl-admin-title"> 
					<span class="uncannyowl-avatar">3</span>
					<?php esc_attr_e( 'Create a CSV File', 'uncanny-pro-toolkit' ); ?>
		 	</div>
			<div class="steps-description"> 
				<a class="options uncannyowl-btn uncannyowl-btn--secondary uncannyowl-btn--sm"
				href="<?php echo $csv_sample_file_link; ?>"><?php esc_attr_e( 'Download Sample CSV', 'uncanny-pro-toolkit' ); ?></a>
			</div>
		</div>
		<div class="uncannyowl-admin-block">
			<div class="uncannyowl-admin-form">
				<div class="uncannyowl-admin-field ">
					<p class="uncannyowl-text-neutral">
						<?php esc_attr_e( 'Your CSV file must be comma-delimited with a .csv extension. It requires user_login and user_email columns, and can include any number of optional meta fields below.', 'uncanny-pro-toolkit' ); ?>
					</p>
					<h2 class="uncannyowl-font-bold uncannyowl-text-primary"><?php esc_attr_e( 'Available Meta Fields', 'uncanny-pro-toolkit' ); ?></h2>
					<div class="uncannyowl-plain-card">
						<div class="uncannyowl-table-container">
							<div class="wptable-container">
							
								<table class="wp-list-table widefat fixed striped  uncannyowl-table">
									<thead>
									<tr>
										<th class="uncannyowl-font-bold uncannyowl-text-light uncannyowl-bg-secondary"><?php esc_attr_e( 'Column Heading', 'uncanny-pro-toolkit' ); ?></th>
										<th class="uncannyowl-font-bold uncannyowl-text-light uncannyowl-bg-secondary"><?php esc_attr_e( 'Description', 'uncanny-pro-toolkit' ); ?></th>
										<th class="uncannyowl-font-bold uncannyowl-text-light uncannyowl-bg-secondary"><?php esc_attr_e( 'Required/Optional', 'uncanny-pro-toolkit' ); ?></th>
									</tr>
									</thead>
									<tbody>
									<tr>
										<td><code>user_email</code></td>
										<td class="column-description"><?php esc_attr_e( 'The user\'s email', 'uncanny-pro-toolkit' ); ?></td>
										<td><span class="uncannyowl-badge uncannyowl-badge-error"><?php esc_attr_e( 'required', 'uncanny-pro-toolkit' ); ?></span></td>
									</tr>
									<tr>
										<td><code>user_login</code></td>
										<td  class="column-description"><?php esc_attr_e( 'The user\'s username', 'uncanny-pro-toolkit' ); ?></td>
										<td><span class="uncannyowl-badge uncannyowl-badge-primary"><?php esc_attr_e( 'optional', 'uncanny-pro-toolkit' ); ?></span></td>
									</tr>
									<tr>
										<td><code>user_pass</code></td>
										<td class="column-description"><?php esc_attr_e( 'The user\'s password', 'uncanny-pro-toolkit' ); ?></td>
										<td><span class="uncannyowl-badge uncannyowl-badge-primary"><?php esc_attr_e( 'optional', 'uncanny-pro-toolkit' ); ?></span></td>
									</tr>
									<?php if ( \uncanny_pro_toolkit\ImportLearndashUsersFromCsv::is_learndash_active() ) { ?>
										<tr>
											<td><code>learndash_courses</code></td>
											<td class="column-description">
												<?php esc_attr_e( 'One or more courses to enroll the user into, specified by course ID. If this column exists and cell is empty, course(s) in Options will be used. Multiple course IDs must be separated by semi-colons, e.g., 96;107;92', 'uncanny-pro-toolkit' ); ?>
											</td>
											<td><span class="uncannyowl-badge uncannyowl-badge-primary"><?php esc_attr_e( 'optional', 'uncanny-pro-toolkit' ); ?></span></td>
										</tr>
										<tr>
											<td><code>learndash_groups</code></td>
											<td class="column-description">
												<?php esc_attr_e( 'One or more LearnDash groups to enroll the user into, specified by group ID. If this column exists and cell is empty, group(s) in Options will be used. Multiple group IDs must be separated by semi-colons, e.g., 91;102;98', 'uncanny-pro-toolkit' ); ?>
											</td>
											<td><span class="uncannyowl-badge uncannyowl-badge-primary"><?php esc_attr_e( 'optional', 'uncanny-pro-toolkit' ); ?></span></td>
										</tr>
									<?php } ?>
									<tr>
										<td><code>wp_role</code></td>
										<td class="column-description">
											<?php esc_attr_e( 'Role to assign to the imported user, specified by role slug. If this column exists and cell is empty, the role in Options will be used.', 'uncanny-pro-toolkit' ); ?>
											<br> <strong><?php esc_attr_e( 'Available role slugs:', 'uncanny-pro-toolkit' ); ?></strong>
											<?php
											$rr = array();
											foreach ( get_editable_roles() as $role_name => $role_info ): ?>
												<?php $rr[] = $role_name; ?>
											<?php endforeach; ?>
											<code><?php echo join( ', ', $rr ) ?></code>
										</td>
										<td><span class="uncannyowl-badge uncannyowl-badge-primary"><?php esc_attr_e( 'optional', 'uncanny-pro-toolkit' ); ?></span></td>
									</tr>
									<tr>
										<td><code>first_name</code></td>
										<td class="column-description"><?php esc_attr_e( 'The user\'s first name', 'uncanny-pro-toolkit' ); ?></td>
										<td><span class="uncannyowl-badge uncannyowl-badge-primary"><?php esc_attr_e( 'optional', 'uncanny-pro-toolkit' ); ?></span></td>
									</tr>
									<tr>
										<td><code>last_name</code></td>
										<td class="column-description"><?php esc_attr_e( 'The user\'s last name', 'uncanny-pro-toolkit' ); ?></td>
										<td><span class="uncannyowl-badge uncannyowl-badge-primary"><?php esc_attr_e( 'optional', 'uncanny-pro-toolkit' ); ?></span></td>
									</tr>
									<tr>
										<td><code>display_name</code></td>
										<td class="column-description"><?php esc_attr_e( 'The user\'s display name', 'uncanny-pro-toolkit' ); ?></td>
										<td><span class="uncannyowl-badge uncannyowl-badge-primary"><?php esc_attr_e( 'optional', 'uncanny-pro-toolkit' ); ?></span></td>
									</tr>
									<?php if ( \uncanny_pro_toolkit\ImportLearndashUsersFromCsv::is_learndash_active() ) { ?>
										<tr>
											<td><code>group_leader</code></td>
											<td class="column-description">
												<?php esc_attr_e( 'Assign the associated user to a group as a Group Leader and add the Group Leader role. Separate multiple group IDs by semicolons, e.g., 91;102;98 if the user will be added to multiple groups as a Group Leader.', 'uncanny-pro-toolkit' ); ?>
											</td>
											<td><span class="uncannyowl-badge uncannyowl-badge-primary"><?php esc_attr_e( 'optional', 'uncanny-pro-toolkit' ); ?></span></td>
										</tr>
									<?php } ?>
									<tr>
										<td><code>**</code></td>
										<td class="column-description"><?php esc_attr_e( 'Any other meta column heading will be treated as a custom user meta value', 'uncanny-pro-toolkit' ); ?></td>
										<td><span class="uncannyowl-badge uncannyowl-badge-primary"><?php esc_attr_e( 'optional', 'uncanny-pro-toolkit' ); ?></span></td>
									</tr>
									</tbody>
								</table>
							</div> <!-- end wptable-container -->			
						</div>
						<!-- end uncannyowl-table-container -->
					</div>
						<h2 class="uncannyowl-font-bold uncannyowl-text-primary"><?php esc_attr_e( 'Notes:', 'uncanny-pro-toolkit' ); ?></h2>
						<ul class="import-user-list uncannyowl-list uncannyowl-list--bulleted">
							<li><?php esc_attr_e( 'If no password value is present for new users, a password will be auto-generated.', 'uncanny-pro-toolkit' ); ?></li>
							<li><?php esc_attr_e( 'Username and email address cannot be updated via import.', 'uncanny-pro-toolkit' ); ?></li>
						</ul>

				</div>
			</div>
		</div>
	</div> 
<!-- End -->
		  
	<div class="uncannyowl-admin-section">
		<div class="uncannyowl-admin-header">
			<div class="uncannyowl-admin-title">
				<span class="uncannyowl-avatar">4</span>
				<div class="steps-description"><?php esc_attr_e( 'Import Users', 'uncanny-pro-toolkit' ); ?></div>
			</div>
		</div>
		<div class="uncannyowl-admin-block">
			<div class="uncannyowl-admin-form">
				<div class="uncannyowl-admin-field">
				<p class="uncannyowl-text-neutral mt-0 mb-0"><?php esc_attr_e( 'Go to the Import Users tab once your CSV file is ready to begin the import.', 'uncanny-pro-toolkit' ); ?></p>
				</div>
			</div>
		</div>
	</div> 
</div>


