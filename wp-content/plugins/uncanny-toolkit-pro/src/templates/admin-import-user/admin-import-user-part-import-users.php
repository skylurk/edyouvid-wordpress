<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}


$option_keys = array(
	'uo_import_add_to_group',
	'uo_import_enrol_in_courses',
	array( 'uo_import_existing_user_data', 'update' ),
	'uo_import_set_roles',
);

$options = array();

foreach ( $option_keys as $meta_key ) {

	if ( is_array( $meta_key ) ) {
		$option = get_option( $meta_key[0], $meta_key[1] );
	} else {
		$option = get_option( $meta_key );
	}

	// all meta value have comma separated values from an array implode except uo_import_existing_user_data
	if ( is_array( $meta_key ) && $meta_key[0] === 'uo_import_existing_user_data' ) {
		$options[ $meta_key[0] ] = $option;
	} else {
		$options[ $meta_key ] = explode( ',', $option );
	}
}

// New user template variables
$uo_import_users_send_new_user_email = ( get_option( 'uo_import_users_send_new_user_email' ) === 'true' ) ?
	__( 'New users WILL be sent an email.', 'uncanny-pro-toolkit' ) :
	__( 'New users WILL NOT be sent an email.', 'uncanny-pro-toolkit' );

// Updated user template variables
$uo_import_users_send_updated_user_email = ( get_option( 'uo_import_users_send_updated_user_email' ) === 'true' ) ?
	__( 'Updated users WILL be sent an email.', 'uncanny-pro-toolkit' ) :
	__( 'Updated users WILL NOT be sent an email.', 'uncanny-pro-toolkit' );

$options_link         = admin_url( 'users.php?page=learndash-toolkit-import-user&tab=options' );
$emails_link          = admin_url( 'users.php?page=learndash-toolkit-import-user&tab=emails' );
$csv_sample_file_link = plugins_url( basename( dirname( UO_FILE ) ) ) . '/src/assets/legacy/backend/csv/import_user_sample.csv';

?>

<div id="import-users-upload" class="uncannyowl-form">
 	<div class="uncannyowl-alert uncannyowl-alert-info">
			<strong class="pr-xs"><?php esc_attr_e( 'Current Settings', 'uncanny-pro-toolkit' ); ?></strong>
			<?php if ( \uncanny_pro_toolkit\ImportLearndashUsersFromCsv::is_learndash_active() ) { ?>
				<?php esc_attr_e( 'Review your enrollment defaults and email options. Make any necessary changes before proceeding to Import LearnDash Users.', 'uncanny-pro-toolkit' ); ?>
			<?php } else {
				esc_attr_e( 'Review your defaults and email options. Make any necessary changes before proceeding to Import Users.', 'uncanny-pro-toolkit' );
			} ?>
	</div>

	<?php if ( \uncanny_pro_toolkit\ImportLearndashUsersFromCsv::is_learndash_active() ) { ?>
		<div class="uncannyowl-admin-section">
			<div class="uncannyowl-admin-header">
				<div class="uncannyowl-admin-title"><?php esc_attr_e( 'Default Course(s)', 'uncanny-pro-toolkit' ); ?></div>
			</div>
			<div class="uncannyowl-admin-block">
				<div class="uncannyowl-admin-form">
					<div class="uncannyowl-admin-field">
						<div class="uncannyowl-form-field p-0">
							<p class="uncannyowl-text-neutral mt-0"><?php esc_attr_e( 'Users without valid course IDs in the CSV will be enrolled in the following courses:', 'uncanny-pro-toolkit' ); ?></p>
							<ul class="import-user-list uncannyowl-list uncannyowl-list--bulleted">
								<?php

								$args = array(
									'post_type'      => 'sfwd-courses',
									'post_status'    => 'publish',
									'posts_per_page' => 1000,

								);

								// the query
								$the_query = new WP_Query( $args );

								if ( $the_query->have_posts() ) {

									while ( $the_query->have_posts() ) {
										$the_query->the_post();
										$meta = get_post_meta( get_the_ID(), '_sfwd-courses', true );

										if ( isset( $meta['sfwd-courses_course_price_type'] ) ) {
											if ( 'open' == $meta['sfwd-courses_course_price_type'] ) {
												echo '<li>' . get_the_title() . '<span class="uncannyowl-text-success"> ' . esc_attr__( '( Course is Open to all )', 'uncanny-pro-toolkit' ) . '</span></li>';
												continue;
											}
										}

										if ( in_array( get_the_ID(), $options['uo_import_enrol_in_courses'] ) ) {
											echo '<li>' . get_the_title() . '</li>';
										}

									};

									wp_reset_postdata();


								} else {
									?>
									<li class="uncannyowl-alert uncannyowl-alert-info"><?php echo esc_attr__( 'No Courses Published', 'uncanny-pro-toolkit' );?></li>
								<?php }
								?>
							</ul>
							<div class="uncannyowl-form-actions">
								<a href="?page=learndash-toolkit-import-user&tab=options"
								class="uncannyowl-btn uncannyowl-btn--secondary uncannyowl-btn--md"><?php esc_attr_e( 'Edit Options', 'uncanny-pro-toolkit' ); ?></a>
							</div>
						</div>
					</div>
				</div>	
			</div>
		</div>
		<!-- end -->

		<div class="uncannyowl-admin-section">
			<div class="uncannyowl-admin-header">
				<div class="uncannyowl-admin-title"> <?php esc_attr_e( 'Default Groups(s)', 'uncanny-pro-toolkit' ); ?></div>
			</div>
			<div class="uncannyowl-admin-block">
				<div class="uncannyowl-admin-form">						
					<div class="uncannyowl-form-field p-0 mb-0"> 
						<p class="uncannyowl-text-neutral mt-0"><?php esc_attr_e( 'Users without valid group IDs in the CSV will be added to the following groups:', 'uncanny-pro-toolkit' ); ?></p>
						<ul class="import-user-list uncannyowl-list uncannyowl-list--bulleted">
							<?php

							$args = array(
								'post_type'      => 'groups',
								'post_status'    => 'publish',
								'posts_per_page' => 9999,
								'orderby'        => 'post_title',
								'order'          => 'ASC',
							);

							// the query
							$groups_query = new WP_Query( $args );

							if ( $groups_query->have_posts() ) {


								while ( $groups_query->have_posts() ) {

									$groups_query->the_post();

									if ( in_array( get_the_ID(), $options['uo_import_add_to_group'] ) ) {
										echo '<li>' . get_the_title() . '</li>';
									}
								}

								wp_reset_postdata();

							} else { ?>
							<li class='uncannyowl-alert uncannyowl-alert-info'> <?php	echo esc_attr__( 'No Groups Published' );?></li>
							<?php }
							?>
						</ul>
						<div class="uncannyowl-form-actions">
							<a href="?page=learndash-toolkit-import-user&tab=options"
							class="uncannyowl-btn uncannyowl-btn--secondary uncannyowl-btn--md"><?php esc_attr_e( 'Edit Options', 'uncanny-pro-toolkit' ); ?></a>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php } ?>

	<div class="uncannyowl-admin-section">
		<div class="uncannyowl-admin-header">
			<div class="uncannyowl-admin-title"><?php esc_attr_e( 'Default Role(s)', 'uncanny-pro-toolkit' ); ?></div>
		</div>
		<div class="uncannyowl-admin-block">
			<div class="uncannyowl-admin-form">
				<div class="uncannyowl-admin-field">	  
		 			<div class="uncannyowl-form-field p-0">
						<p class="uncannyowl-text-neutral mt-0"><?php esc_attr_e( 'Users without a valid role in the CSV will be set to the following role:', 'uncanny-pro-toolkit' ); ?></p>
						<ul class="import-user-list uncannyowl-list uncannyowl-list--bulleted">
							<?php
							$editable_roles = get_editable_roles();
							foreach ( $editable_roles as $role => $details ) {

								if ( in_array( esc_attr( $role ), $options['uo_import_set_roles'] ) ) {
									echo '<li>' . $details['name'] . '</li>';
								}
							}
							?>
						</ul>
						<div class="uncannyowl-form-actions">
							<a href="?page=learndash-toolkit-import-user&tab=options"
							class="uncannyowl-btn uncannyowl-btn--secondary uncannyowl-btn--md"><?php esc_attr_e( 'Edit Options', 'uncanny-pro-toolkit' ); ?></a>
						</div>
					</div>
				</div>
			</div>	
		</div>
	</div>

	<div class="uncannyowl-admin-section">
		<div class="uncannyowl-admin-header">
			<div class="uncannyowl-admin-title"><?php esc_attr_e( 'Email Options', 'uncanny-pro-toolkit' ); ?></div>
		</div>
		<div class="uncannyowl-admin-block">
			<div class="uncannyowl-admin-field">
				<div class="uncannyowl-form-field p-0">
					<div class="uncannyowl-alert uncannyowl-alert-warning">
                            <?php echo $uo_import_users_send_new_user_email; ?>
                    </div>
					<div class="uncannyowl-alert uncannyowl-alert-warning">
					<?php echo $uo_import_users_send_updated_user_email; ?>
					</div>	
					<div  class="uncannyowl-form-actions pt-sm">
							<a href="?page=learndash-toolkit-import-user&tab=emails" class="uncannyowl-btn uncannyowl-btn--secondary uncannyowl-btn--md">
							<?php esc_attr_e( 'Edit Email Settings', 'uncanny-pro-toolkit' ); ?>
							</a>
					</div>
				</div>
			</div> 
		</div>  
	</div> <!-- end uncannyowl-admin-section -->
	 
	<div class="uncannyowl-admin-section">
		<div class="uncannyowl-admin-header">
			<div class="uncannyowl-admin-title"><?php esc_attr_e( 'Import Users', 'uncanny-pro-toolkit' ); ?></div>
		</div>
		<div class="uncannyowl-admin-block">
			<div class="uncannyowl-admin-form">
				<div class="uncannyowl-admin-field">	 
					<div class="uncannyowl-form-field">
						<div class="uncannyowl-file-input-wrapper">
							<form action="<?php echo admin_url( 'admin-ajax.php' ) ?>" id="file-upload" enctype="multipart/form-data">
								<div class="uncannyowl-file-input-wrapper">
									<div class="uncannyowl-form-input-group">
										<input type="file" name="csv" id="csv-file" class=" uncannyowl-file-input" accept=".csv"/>
									</div>
									<label for="csv-file" class="uncannyowl-file-input-label">
										<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M10 4V16M10 4L6 8M10 4L14 8M4 16H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
										 
										<span class="uncannyowl-file-input-text"> <?php esc_attr_e( 'Upload the CSV file and review verification results.', 'uncanny-pro-toolkit' ); ?></span>
									
									</label>
									<span class="uncannyowl-file-input-filename"></span>
								</div>
								<div class="uncannyowl-form-actions">
									<input type="submit" class="uncannyowl-btn uncannyowl-btn--primary"
										value="<?php esc_attr_e( 'Upload file and verify records', 'uncanny-pro-toolkit' ); ?>"/>
								</div>
							</form>
						</div>
					</div> 
				</div>
			</div>
		</div>

	</div>

</div>

<div id="import-users-validation">

	<div class="uncannyowl-admin-section">
		<div class="uncannyowl-admin-header">
			<div class="uncannyowl-admin-title"> <?php esc_attr_e( 'Validation Results', 'uncanny-pro-toolkit' ); ?></div>
		</div>
		<div class="uncannyowl-admin-block">
			<div class="uncannyowl-admin-form">
				<div class="uncannyowl-admin-field">	 
					<div class="uncannyowl-form-field p-0">
						<div class="uncannyowl-alert uncannyowl-alert-info">
							<strong><?php esc_attr_e( 'Total number of users found in CSV', 'uncanny-pro-toolkit' ); ?>:</strong>
							<span id="total-rows"></span>
						</div>
						
						<div class="uncannyowl-alert uncannyowl-alert-success">
							<strong><?php esc_attr_e( 'New users that will be created', 'uncanny-pro-toolkit' ); ?>:</strong>
							<span id="new-emails"></span>
						</div>
						
						<div class="uncannyowl-alert uncannyowl-alert-warning">
							<strong>
								<?php
								if ( 'update' === $options['uo_import_existing_user_data'] ) {
									_e( 'Existing users that WILL be updated', 'uncanny-pro-toolkit' );
								} else {
									_e( 'Existing users that WILL NOT be updated', 'uncanny-pro-toolkit' );
								}
								?>:
							</strong>
							<span id="existing-emails"></span>
						</div>
						
						<div class="uncannyowl-alert uncannyowl-alert-error">
							<strong><?php esc_attr_e( 'Users with malformed email addresses', 'uncanny-pro-toolkit' ); ?>:</strong>
							<span id="malformed-emails"></span>
						</div>
						
						<?php if ( \uncanny_pro_toolkit\ImportLearndashUsersFromCsv::is_learndash_active() ) { ?>
							<div class="uncannyowl-alert uncannyowl-alert-error">
								<strong><?php esc_attr_e( 'Users with invalid course IDs', 'uncanny-pro-toolkit' ); ?>:</strong>
								<span id="invalid-courses"></span>
							</div>
							
							<div class="uncannyowl-alert uncannyowl-alert-error">
								<strong><?php esc_attr_e( 'Users with invalid group IDs', 'uncanny-pro-toolkit' ); ?>:</strong>
								<span id="invalid-groups"></span>
							</div>
							
							<div class="uncannyowl-alert uncannyowl-alert-error">
								<strong><?php esc_attr_e( 'Users with invalid group IDs for the Group Leader', 'uncanny-pro-toolkit' ); ?>:</strong>
								<span id="invalid-groupleaders"></span>
							</div>
						<?php } ?>
						</div>
				</div>
				<div class="uncannyowl-admin-field">
                    <div class="uncannyowl-card">
						<div class="uncannyowl-form-field p-0">
							<h2 class="options-header-container"><?php esc_attr_e( 'Skipped Users with Existing Emails', 'uncanny-pro-toolkit' ); ?></h2>
							<p class="uncannyowl-text-neutral">
								<?php esc_attr_e( 'The emails listed below were found in the CSV, but are assigned to users that already exist in WordPress. WordPress requires that each email be unique.', 'uncanny-pro-toolkit' ); ?>
							</p>
							<span class="uncannyowl-alert uncannyowl-alert-info"><?php esc_attr_e( 'These records will be ignored.', 'uncanny-pro-toolkit' ); ?></span>
							<div class="uncannyowl-table-container">
								<table id="existing-user-email-table" class="wp-list-table widefat fixed striped posts uncannyowl-table">
									<thead>
									<tr>
										<th>
											<a href="#">
												<span>
												<?php esc_attr_e( 'CSV Row', 'uncanny-pro-toolkit' ); ?>
												</span>
											</a>
										</th>
										<th>
											<a href="#">
												<span>
												<?php esc_attr_e( 'Email', 'uncanny-pro-toolkit' ); ?>
												</span>
											</a>
										</th>
										<th>
											<a href="#">
												<span>
												<?php esc_attr_e( 'Conflicting User', 'uncanny-pro-toolkit' ); ?>	
												</span>
											</a>
										</th> 
									</tr>
									</thead>
									<tbody>
										<!-- <tr>
											<td>fa</td>
											<td>fa</td>
											<td>fa</td>
										</tr>  -->
									</tbody>
								</table>
							</div>
						</div>
					</div>	 
				</div>
				 
				
				<div class="uncannyowl-admin-field">
					<div class="uncannyowl-card">		
						<div class="uncannyowl-form-field p-0">
							<?php if ( \uncanny_pro_toolkit\ImportLearndashUsersFromCsv::is_learndash_active() ) { ?>
								<h2 class="options-header-container "><?php esc_attr_e( 'Invalid Course IDs', 'uncanny-pro-toolkit' ); ?></h2>
								<p class="uncannyowl-text-neutral">
									<?php esc_attr_e( 'The course IDs below were found in the CSV but do not correspond to an existing LearnDash course. If you proceed, they will be ignored.', 'uncanny-pro-toolkit' ); ?>
								</p>
								<span class="uncannyowl-alert uncannyowl-alert-info"><?php esc_attr_e( 'These course IDs will be ignored.', 'uncanny-pro-toolkit' ); ?></span>
								<div class="uncannyowl-table-container">
									<table id="invalid-courses-table" class="wp-list-table widefat fixed striped posts uncannyowl-table">
										<thead>
										<tr>
											<th class="uncannyowl-font-bold uncannyowl-text-light uncannyowl-bg-secondary"><?php esc_attr_e( 'CSV Row', 'uncanny-pro-toolkit' ); ?></th>
											<th class="uncannyowl-font-bold uncannyowl-text-light uncannyowl-bg-secondary"><?php esc_attr_e( 'Invalid Course IDs', 'uncanny-pro-toolkit' ); ?></th>
										</tr>
										</thead>
										<tbody>
										</tbody>
									</table>
								</div>	
						</div>	
					</div>
				</div>
				 
				
				<div class="uncannyowl-admin-field">
					<div class="uncannyowl-card">
						<div class="uncannyowl-form-field p-0">
								<h2 class="options-header-container "><?php esc_attr_e( 'Invalid Group IDs', 'uncanny-pro-toolkit' ); ?></h2>
								<p class="uncannyowl-text-neutral">
									<?php esc_attr_e( 'The group IDs below were found in the CSV but do not correspond to an existing LearnDash group. If you proceed, they will be ignored.', 'uncanny-pro-toolkit' ); ?>
								</p>
								<span class="uncannyowl-alert uncannyowl-alert-info"><?php esc_attr_e( 'These group IDs will be ignored.', 'uncanny-pro-toolkit' ); ?></span>
								<div class="uncannyowl-table-container">
									<table id="invalid-groups-table" class="wp-list-table widefat fixed striped posts uncannyowl-table">
										<thead>
										<tr>
											<th class="uncannyowl-font-bold uncannyowl-text-light uncannyowl-bg-secondary"><?php esc_attr_e( 'CSV Row', 'uncanny-pro-toolkit' ); ?></th>
											<th class="uncannyowl-font-bold uncannyowl-text-light uncannyowl-bg-secondary"><?php esc_attr_e( 'Invalid Group IDs', 'uncanny-pro-toolkit' ); ?></th>
										</tr>
										</thead>
										<tbody>
										</tbody>
									</table>
								</div>
						</div>
					</div>		
				</div>
				 
				
				<div class="uncannyowl-admin-field">
					<div class="uncannyowl-card">
						<div class="uncannyowl-form-field p-0">			
							<span class="uncannyowl-alert uncannyowl-alert-info"><?php esc_attr_e( 'These group IDs will be ignored when adding the user as a Group Leader.', 'uncanny-pro-toolkit' ); ?></span>
							<div class="uncannyowl-table-container">	
							<table id="invalid-groupleaders-table" class="wp-list-table widefat fixed striped posts uncannyowl-table">
									<thead>
									<tr>
										<th class="uncannyowl-font-bold uncannyowl-text-light uncannyowl-bg-secondary"><?php esc_attr_e( 'CSV Row', 'uncanny-pro-toolkit' ); ?></th>
										<th class="uncannyowl-font-bold uncannyowl-text-light uncannyowl-bg-secondary"><?php esc_attr_e( 'Invalid Group IDs', 'uncanny-pro-toolkit' ); ?></th>
									</tr>
									</thead>
									<tbody>
									</tbody>
								</table>
							</div>
							<?php } ?>
						</div>
					</div>		
				</div>
				 
				
				<div class="uncannyowl-admin-field">
					<div class="uncannyowl-card">	
						<div class="uncannyowl-form-field p-0">
							<h2 class="options-header-container"><?php esc_attr_e( "Let's Add Some Users!", 'uncanny-pro-toolkit' ); ?></h2>
							<div class="perform-import-users">
								<div class="uncannyowl-btn-group mb-lg">
									<button class="uncannyowl-btn uncannyowl-btn--secondary uncannyowl-btn--md mr-md"
											id="abort-import-users"><?php esc_attr_e( 'Abort Import Process', 'uncanny-pro-toolkit' ); ?></button>
									<button class="uncannyowl-btn uncannyowl-btn--primary  uncannyowl-btn--md"
											id="perform-import-users"><?php esc_attr_e( 'Perform Import', 'uncanny-pro-toolkit' ); ?></button>
								</div> 
								 
								<div id="perform-import-users-text"  class="uncannyowl-alert uncannyowl-alert-warning">
								<?php esc_attr_e( 'It is not possible to cancel the import process once it has begun.', 'uncanny-pro-toolkit' ); ?>
							 	</div>

								<div class="uncannyowl-btn-group mb-lg">
									<button id="perform-import-users-review" class="uncannyowl-btn uncannyowl-btn--secondary  mr-md  uncannyowl-btn--sm">
										<?php esc_attr_e( 'Let me review the validation results again', 'uncanny-pro-toolkit' ); ?>
									</button>
									<button id="perform-import-users-ready" class="uncannyowl-btn uncannyowl-btn--primary  uncannyowl-btn--sm">
										<?php esc_attr_e( 'I\'m ready to import', 'uncanny-pro-toolkit' ); ?>
									</button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		
	</div>
</div>

	<div id="import-users-progress">
		<div class="uncannyowl-admin-section">
			<div class="uncannyowl-admin-header">
				<div class="uncannyowl-admin-title"><?php esc_attr_e( 'Import', 'uncanny-pro-toolkit' ); ?></div>
			</div>
			<div class="uncannyowl-admin-block">
				<div class="uncannyowl-admin-form">
					<div class="uncannyowl-admin-field">
						<h4 class="options-header-container mt-0"><?php esc_attr_e( 'Import Progress', 'uncanny-pro-toolkit' ); ?></h4>
						<div class="uncannyowl-progress">
							<div class="uncannyowl-progress-bar"></div>
						</div>
						<p id="import-progress-message" class="uncannyowl-alert uncannyowl-alert-info"><?php esc_attr_e( 'Do not reload this page while import is in progress', 'uncanny-pro-toolkit' ); ?></p>

					</div>
				</div>
				<div class="uncannyowl-admin-field">
					<div class="uncannyowl-card">
						<div class="uncannyowl-form-field">
								<div id="import-users-results">
									<h4 class="options-header-container mt-0"><?php esc_attr_e( 'Import Results', 'uncanny-pro-toolkit' ); ?></h4>
									<div class="uncannyowl-table-container">
										<table id="import-users-results-table" class="wp-list-table widefat fixed striped posts uncannyowl-table">
											<tbody>
												<tr>
													<td><?php esc_attr_e( 'New Users Created', 'uncanny-pro-toolkit' ); ?></td>
													<td id="import-users-results-new-users"></td>
												</tr>
												<tr>
													<td><?php esc_attr_e( 'Existing Users Updated', 'uncanny-pro-toolkit' ); ?></td>
													<td id="import-users-results-updated-users"></td>
												</tr>
												<tr>
													<td><?php esc_attr_e( 'Emails Sent', 'uncanny-pro-toolkit' ); ?></td>
													<td id="import-users-results-emails-sent"></td>
												</tr>
												<tr>
													<td><?php esc_attr_e( 'Rows Ignored', 'uncanny-pro-toolkit' ); ?></td>
													<td id="import-users-results-rows-ignored"></td>
												</tr>
											</tbody>
										</table>
									</div>
									<h4 class="options-header-container pt-lg"><?php esc_attr_e( 'The following rows in the CSV were skipped:', 'uncanny-pro-toolkit' ); ?></h4>
									<div class="uncannyowl-table-container">
										<table id="import-users-ignored-table" class="wp-list-table widefat fixed striped posts uncannyowl-table">
											<thead>
											<tr>
												<th class="uncannyowl-font-bold uncannyowl-text-light uncannyowl-bg-secondary"><?php esc_attr_e( 'CSV Row', 'uncanny-pro-toolkit' ); ?></th>
												<th class="uncannyowl-font-bold uncannyowl-text-light uncannyowl-bg-secondary"><?php esc_attr_e( 'Issue', 'uncanny-pro-toolkit' ); ?></th>
											</tr>
											</thead>
											<tbody>
											</tbody>
										</table>
									</div>	
								</div>
						</div>
					</div>
				</div>			

			</div>
							
		</div>		
		<!-- uncannyowl-admin-section				 -->
		 

		
	</div>
	<!-- End #import-users-progress -->