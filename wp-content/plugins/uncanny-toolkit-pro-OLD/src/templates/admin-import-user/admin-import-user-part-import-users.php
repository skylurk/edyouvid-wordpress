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

<div id="import-users-upload">

	<h2 class="options-header-container">
		<?php esc_attr_e( 'Current Settings', 'uncanny-pro-toolkit' ); ?>
	</h2>
	<p>
		<?php if ( \uncanny_pro_toolkit\ImportLearndashUsersFromCsv::is_learndash_active() ) { ?>
			<?php esc_attr_e( 'Review your enrollment defaults and email options. Make any necessary changes before proceeding to Import LearnDash Users.', 'uncanny-pro-toolkit' ); ?>
		<?php } else {
			esc_attr_e( 'Review your defaults and email options. Make any necessary changes before proceeding to Import Users.', 'uncanny-pro-toolkit' );
		} ?>
	</p>
	<?php if ( \uncanny_pro_toolkit\ImportLearndashUsersFromCsv::is_learndash_active() ) { ?>
		<h2><?php esc_attr_e( 'Default Course(s)', 'uncanny-pro-toolkit' ); ?></h2>
		<p><?php esc_attr_e( 'Users without valid course IDs in the CSV will be enrolled in the following courses:', 'uncanny-pro-toolkit' ); ?></p>
		<ul class="import-user-list">
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
							echo '<li>' . get_the_title() . '<span style="color:green;"> ' . esc_attr__( '( Course is Open to all )', 'uncanny-pro-toolkit' ) . '</span></li>';
							continue;
						}
					}

					if ( in_array( get_the_ID(), $options['uo_import_enrol_in_courses'] ) ) {
						echo '<li>' . get_the_title() . '</li>';
					}

				};

				wp_reset_postdata();


			} else {
				echo esc_attr__( 'No Courses Published', 'uncanny-pro-toolkit' );
			}
			?>
		</ul>
		<a href="?page=learndash-toolkit-import-user&tab=options"
		   class="button button-secondary"><?php esc_attr_e( 'Edit Options', 'uncanny-pro-toolkit' ); ?></a>

		<h2><?php esc_attr_e( 'Default Groups(s)', 'uncanny-pro-toolkit' ); ?></h2>
		<p><?php esc_attr_e( 'Users without valid group IDs in the CSV will be added to the following groups:', 'uncanny-pro-toolkit' ); ?></p>
		<ul class="import-user-list">
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

			} else {
				echo esc_attr__( 'No Groups Published' );
			}
			?>
		</ul>
		<a href="?page=learndash-toolkit-import-user&tab=options"
		   class="button button-secondary"><?php esc_attr_e( 'Edit Options', 'uncanny-pro-toolkit' ); ?></a>
	<?php } ?>
	<h2><?php esc_attr_e( 'Default Role(s)', 'uncanny-pro-toolkit' ); ?></h2>
	<p><?php esc_attr_e( 'Users without a valid role in the CSV will be set to the following role:', 'uncanny-pro-toolkit' ); ?></p>
	<ul class="import-user-list">
		<?php
		$editable_roles = get_editable_roles();
		foreach ( $editable_roles as $role => $details ) {

			if ( in_array( esc_attr( $role ), $options['uo_import_set_roles'] ) ) {
				echo '<li>' . $details['name'] . '</li>';
			}
		}
		?>
	</ul>
	<a href="?page=learndash-toolkit-import-user&tab=options"
	   class="button button-secondary"><?php esc_attr_e( 'Edit Options', 'uncanny-pro-toolkit' ); ?></a>

	<h2><?php esc_attr_e( 'Email Options', 'uncanny-pro-toolkit' ); ?></h2>
	<p><?php echo $uo_import_users_send_new_user_email; ?></p>
	<p><?php echo $uo_import_users_send_updated_user_email; ?></p>
	<a href="?page=learndash-toolkit-import-user&tab=emails"
	   class="button button-secondary"><?php esc_attr_e( 'Edit Email Settings', 'uncanny-pro-toolkit' ); ?></a>

	<h2 class="options-header-container"><?php esc_attr_e( 'Import Users', 'uncanny-pro-toolkit' ); ?></h2>

	<p><?php esc_attr_e( 'Upload the CSV file and review verification results.', 'uncanny-pro-toolkit' ); ?></p>

	<form action="<?php echo admin_url( 'admin-ajax.php' ) ?>" id="file-upload" enctype="multipart/form-data">
		<input type="file" name="csv" id="csv-file"/>
		<br>
		<input type="submit" class="button button-primary"
			   value="<?php esc_attr_e( 'Upload file and verify records', 'uncanny-pro-toolkit' ); ?>"/>
	</form>

</div>

<div id="import-users-validation">

	<h2 class="options-header-container"><?php esc_attr_e( 'Validation Results', 'uncanny-pro-toolkit' ); ?></h2>

	<table class="wp-list-table widefat fixed striped posts">
		<thead>
		<tr>
			<td><?php esc_attr_e( 'Type', 'uncanny-pro-toolkit' ); ?></td>
			<td><?php esc_attr_e( 'Number', 'uncanny-pro-toolkit' ); ?></td>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><?php esc_attr_e( 'Total number of users found in CSV', 'uncanny-pro-toolkit' ); ?></td>
			<td id="total-rows"></td>
		</tr>
		<tr>
			<td><?php esc_attr_e( 'New users that will be created', 'uncanny-pro-toolkit' ); ?></td>
			<td id="new-emails"></td>
		</tr>
		<tr>
			<td>
				<?php
				if ( 'update' === $options['uo_import_existing_user_data'] ) {
					_e( 'Existing users that WILL be updated', 'uncanny-pro-toolkit' );
				} else {
					_e( 'Existing users that WILL NOT be updated', 'uncanny-pro-toolkit' );
				}
				?>
			</td>
			<td id="existing-emails"></td>
		</tr>
		<tr>
			<td><?php esc_attr_e( 'Users with malformed email addresses', 'uncanny-pro-toolkit' ); ?></td>
			<td id="malformed-emails"></td>
		</tr>
		<?php if ( \uncanny_pro_toolkit\ImportLearndashUsersFromCsv::is_learndash_active() ) { ?>
			<tr>
				<td><?php esc_attr_e( 'Users with invalid course IDs', 'uncanny-pro-toolkit' ); ?></td>
				<td id="invalid-courses"></td>
			</tr>
			<tr>
				<td><?php esc_attr_e( 'Users with invalid group IDs', 'uncanny-pro-toolkit' ); ?></td>
				<td id="invalid-groups"></td>
			</tr>
			<tr>
				<td><?php esc_attr_e( 'Users with invalid group IDs for the Group Leader', 'uncanny-pro-toolkit' ); ?></td>
				<td id="invalid-groupleaders"></td>
			</tr>
		<?php } ?>
		</tbody>
	</table>

	<h2 class="options-header-container"><?php esc_attr_e( 'Skipped Users with Existing Emails', 'uncanny-pro-toolkit' ); ?></h2>
	<p>
		<?php esc_attr_e( 'The emails listed below were found in the CSV, but are assigned to users that already exist in WordPress. WordPress requires that each email be unique.', 'uncanny-pro-toolkit' ); ?>
	</p>
	<h4><?php esc_attr_e( 'These records will be ignored.', 'uncanny-pro-toolkit' ); ?></h4>
	<table id="existing-user-email-table" class="wp-list-table widefat fixed striped posts">
		<thead>
		<tr>
			<td><?php esc_attr_e( 'CSV Row', 'uncanny-pro-toolkit' ); ?></td>
			<td><?php esc_attr_e( 'Email', 'uncanny-pro-toolkit' ); ?></td>
			<td><?php esc_attr_e( 'Conflicting User', 'uncanny-pro-toolkit' ); ?></td>
		</tr>
		</thead>
		<tbody>
		</tbody>
	</table>
	<?php if ( \uncanny_pro_toolkit\ImportLearndashUsersFromCsv::is_learndash_active() ) { ?>

		<h2 class="options-header-container"><?php esc_attr_e( 'Invalid Course IDs', 'uncanny-pro-toolkit' ); ?></h2>
		<p>
			<?php esc_attr_e( 'The course IDs below were found in the CSV but do not correspond to an existing LearnDash course. If you proceed, they will be ignored.', 'uncanny-pro-toolkit' ); ?>
		</p>
		<h4><?php esc_attr_e( 'These course IDs will be ignored.', 'uncanny-pro-toolkit' ); ?></h4>
		<table id="invalid-courses-table" class="wp-list-table widefat fixed striped posts">
			<thead>
			<tr>
				<td><?php esc_attr_e( 'CSV Row', 'uncanny-pro-toolkit' ); ?></td>
				<td><?php esc_attr_e( 'Invalid Course IDs', 'uncanny-pro-toolkit' ); ?></td>
			</tr>
			</thead>
			<tbody>
			</tbody>
		</table>

		<h2 class="options-header-container"><?php esc_attr_e( 'Invalid Group IDs', 'uncanny-pro-toolkit' ); ?></h2>
		<p>
			<?php esc_attr_e( 'The group IDs below were found in the CSV but do not correspond to an existing LearnDash group. If you proceed, they will be ignored.', 'uncanny-pro-toolkit' ); ?>
		</p>
		<h4><?php esc_attr_e( 'These group IDs will be ignored.', 'uncanny-pro-toolkit' ); ?></h4>
		<table id="invalid-groups-table" class="wp-list-table widefat fixed striped posts">
			<thead>
			<tr>
				<td><?php esc_attr_e( 'CSV Row', 'uncanny-pro-toolkit' ); ?></td>
				<td><?php esc_attr_e( 'Invalid Group IDs', 'uncanny-pro-toolkit' ); ?></td>
			</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
		<h4><?php esc_attr_e( 'These group IDs will be ignored when adding the user as a Group Leader.', 'uncanny-pro-toolkit' ); ?></h4>
		<table id="invalid-groupleaders-table" class="wp-list-table widefat fixed striped posts">
			<thead>
			<tr>
				<td><?php esc_attr_e( 'CSV Row', 'uncanny-pro-toolkit' ); ?></td>
				<td><?php esc_attr_e( 'Invalid Group IDs', 'uncanny-pro-toolkit' ); ?></td>
			</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
	<?php } ?>
	<h2 class="options-header-container"><?php esc_attr_e( "Let's Add Some Users!", 'uncanny-pro-toolkit' ); ?></h2>
	<div class="perform-import-users">
		<button class="button  button-large"
				id="abort-import-users"><?php esc_attr_e( 'Abort Import Process', 'uncanny-pro-toolkit' ); ?></button>
		<button class="button button-primary button-large"
				id="perform-import-users"><?php esc_attr_e( 'Perform Import', 'uncanny-pro-toolkit' ); ?></button>
		<h3 id="perform-import-users-text">
			<?php esc_attr_e( 'It is not possible to cancel the import process once it has	begun.', 'uncanny-pro-toolkit' ); ?>
		</h3>
		<button id="perform-import-users-review" class="button button-large">
			<?php esc_attr_e( 'Let me review the validation results again', 'uncanny-pro-toolkit' ); ?>
		</button>
		<button id="perform-import-users-ready" class="button button-primary button-large">
			<?php esc_attr_e( 'I\'m ready to import', 'uncanny-pro-toolkit' ); ?>
		</button>
	</div>

</div>

<div id="import-users-progress">

	<h2 class="options-header-container"><?php esc_attr_e( 'Import Progress', 'uncanny-pro-toolkit' ); ?></h2>

	<div class="import-progress-bar">
		<img src="<?php echo site_url(); ?>/wp-includes/js/thickbox/loadingAnimation.gif"
			 data-lazy-src="<?php echo site_url(); ?>/wp-includes/js/thickbox/loadingAnimation.gif" class="lazyloaded"
			 scale="0">
		<div class="import-progress-bar-overlay"></div>
	</div>

	<h3 class="import_notice"><?php esc_attr_e( 'Do not reload this page while import is in progress', 'uncanny-pro-toolkit' ); ?></h3>

	<div id="import-users-results">

		<h2 class="options-header-container"><?php esc_attr_e( 'Import Results', 'uncanny-pro-toolkit' ); ?></h2>

		<table id="import-users-results-table" class="wp-list-table widefat fixed striped posts">
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

		<h2 class="options-header-container"><?php esc_attr_e( 'The following rows in the CSV were skipped:', 'uncanny-pro-toolkit' ); ?></h2>

		<table id="import-users-ignored-table" class="wp-list-table widefat fixed striped posts">
			<thead>
			<tr>
				<td><?php esc_attr_e( 'CSV Row', 'uncanny-pro-toolkit' ); ?></td>
				<td><?php esc_attr_e( 'Issue', 'uncanny-pro-toolkit' ); ?></td>
			</tr>
			</thead>
			<tbody>
			</tbody>
		</table>

	</div>
</div>
