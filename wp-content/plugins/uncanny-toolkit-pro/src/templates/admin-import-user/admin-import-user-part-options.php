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

?>

<form id="uo_import_save_options" method="post" action="options.php" class="uncannyowl-form">

	<div class="uncannyowl-admin-section">
		<div class="uncannyowl-admin-header">
				<div class="uncannyowl-admin-title"><?php esc_attr_e( 'Existing Users', 'uncanny-pro-toolkit' ); ?></div>
		</div>
		<div class="uncannyowl-admin-block">
			<div class="uncannyowl-admin-form">
				<div class="uncannyowl-admin-field">
					<div class="uncannyowl-form-field p-0">
						<label for="example-input"><?php esc_attr_e( 'For imported existing users,', 'uncanny-pro-toolkit' ); ?></label>
						<select name="uo_import_existing_user_data" id="uo_import_existing_user_data" class="uncannyowl-select">
							<option
								value="update" <?php echo ( 'update' === $options['uo_import_existing_user_data'] ) ? 'selected="selected"' : ''; ?>>
								<?php esc_attr_e( 'Update (Default)', 'uncanny-pro-toolkit' ); ?>
							</option>
							<option
								value="ignore" <?php echo ( 'ignore' === $options['uo_import_existing_user_data'] ) ? 'selected="selected"' : ''; ?>>
								<?php esc_attr_e( 'Ignore', 'uncanny-pro-toolkit' ); ?>
							</option>
						</select>
						<span class="uncannyowl-form-help-text"><?php esc_attr_e( 'user data.', 'uncanny-pro-toolkit' ); ?></span>
			 		</div>
				</div> 
			</div>
		</div>
	</div> 
	<div class="uncannyowl-admin-section">
		<div class="uncannyowl-admin-header">
			<div class="uncannyowl-admin-title"><?php esc_attr_e( 'User Roles', 'uncanny-pro-toolkit' ); ?></div>
		</div>
		<div class="uncannyowl-admin-block">
			<div class="uncannyowl-admin-form">
				<div class="uncannyowl-admin-field">
					<label class="uncannyowl-form-label">
						<?php esc_attr_e( 'Set the Role for imported users', 'uncanny-pro-toolkit' ); ?>
					</label>
					<div class="uncannyowl-form-help">
						<ul class="uncannyowl-list uncannyowl-list--bulleted">
							<li><?php esc_attr_e( 'Users with no role specified in the CSV will receive the selected role.', 'uncanny-pro-toolkit' ); ?></li>
							<li><?php esc_attr_e( 'If no role is selected and no role specified for the user in the CSV, those users will be assigned the \'Subscriber\' role.', 'uncanny-pro-toolkit' ); ?></li>
						</ul>
					</div>
					<div class="uncannyowl-form-input-group">
						<?php
						$editable_roles = get_editable_roles();
						foreach ( $editable_roles as $role => $details ) {
							if ( ! current_user_can( 'manage_options' ) && 'administrator' === $role ) {
								continue;
							}
							?>
							<label class="uncannyowl-radio">
								<input type="radio" name="uo_import_set_roles"
									value="<?php echo esc_attr( $role ); ?>"
									<?php echo ( in_array( esc_attr( $role ), $options['uo_import_set_roles'] ) ) ? 'checked="checked"' : ''; ?>
								/>
								<span class="uncannyowl-radio-label"><?php echo $details['name']; ?></span>
							</label>
							<?php
						}
						?>
					</div>
				</div>		
			</div>
		</div>
		 
	</div>  
 
	<?php if ( \uncanny_pro_toolkit\ImportLearndashUsersFromCsv::is_learndash_active() ) { ?>
		<div class="uncannyowl-admin-section">
			<div class="uncannyowl-admin-header">
				<div class="uncannyowl-admin-title"><?php echo \LearnDash_Custom_Label::get_label( 'courses' ); ?></div>
			</div>
			<div class="uncannyowl-admin-block">
				<div class="uncannyowl-admin-form">
					<div class="uncannyowl-admin-field">
						<div class="uncannyowl-form-field  p-0">
							<label class="uncannyowl-form-label">
								<?php esc_attr_e( 'Enroll users in course(s)', 'uncanny-pro-toolkit' ); ?>
							</label>
							<div class="uncannyowl-form-help">
								<ul class="uncannyowl-list uncannyowl-list--bulleted">
									<li><?php esc_attr_e( 'Users with no courses specified in the CSV will be enrolled in the specified courses.', 'uncanny-pro-toolkit' ); ?></li>
									<li><?php esc_attr_e( 'Note: Courses that are set to Type \'Open\'  do not appear here, as all users are automatically enrolled in Open courses.', 'uncanny-pro-toolkit' ); ?></li>
								</ul>
							</div>
							<div class="uncannyowl-form-input-group width50" >
								<?php
								$args = array(
									'post_type'      => 'sfwd-courses',
									'post_status'    => 'publish',
									'posts_per_page' => 1000,
									'orderby'        => 'post_title',
									'order'          => 'ASC',
								);

								// the query
								$the_query = new WP_Query( $args ); ?>

								<?php if ( $the_query->have_posts() ) : ?>
									<select class="import_user_pillbox uncannyowl-select" name="uo_import_enrol_in_courses[]" multiple="multiple"  >
										<?php while ( $the_query->have_posts() ) : $the_query->the_post();
											$meta = get_post_meta( get_the_ID(), '_sfwd-courses', true );

											if ( isset( $meta['sfwd-courses_course_price_type'] ) ) {
												if ( 'open' == $meta['sfwd-courses_course_price_type'] ) {
													continue;
												}
											} else {
												continue;
											}
											?>
											<option <?php echo ( in_array( get_the_ID(), $options['uo_import_enrol_in_courses'] ) ) ? 'selected="selected"' : ''; ?>
												value="<?php echo get_the_ID() ?>"><?php the_title(); ?></option>
										<?php endwhile; ?>
									</select>
								<?php else : ?>
									<p class="uncannyowl-alert uncannyowl-alert-info"><?php esc_attr_e( 'No Courses Published', 'uncanny-pro-toolkit' ); ?></p>
								<?php endif; ?>
								<?php wp_reset_postdata(); ?>
							</div>
						</div>
					</div>		
					
				</div>
			</div>
		</div>

 
		<div class="uncannyowl-admin-section">
			<div class="uncannyowl-admin-header">
				<div class="uncannyowl-admin-title"><?php esc_attr_e( 'Groups', 'uncanny-pro-toolkit' ); ?></div>
			</div> 
			<div class="uncannyowl-admin-block">
				<div class="uncannyowl-admin-form">
					<div class="uncannyowl-form-field  p-0 mb-0">
						<label class="uncannyowl-form-label">
							<?php esc_attr_e( 'Add users to group(s)', 'uncanny-pro-toolkit' ); ?>
						</label>
						<div class="uncannyowl-form-help">
							<ul class="uncannyowl-list uncannyowl-list--bulleted">
								<li><?php esc_attr_e( 'Users with no groups specified in the CSV will be enrolled in the selected groups.', 'uncanny-pro-toolkit' ); ?></li>
							</ul>
						</div>
					 
							<div class="uncannyowl-form-input-group"  style="width: 50%">
								<?php
								$args = array(
									'post_type'      => 'groups',
									'post_status'    => 'publish',
									'posts_per_page' => 9999,
									'orderby'        => 'post_title',
									'order'          => 'ASC',
								);

								// the query
								$the_query = new WP_Query( $args ); ?>

								<?php if ( $the_query->have_posts() ) : ?>
									<select class="import_user_pillbox uncannyowl-select" name="uo_import_add_to_group[]" multiple="multiple" >
										<?php while ( $the_query->have_posts() ) : $the_query->the_post(); ?>
											<option <?php echo ( in_array( get_the_ID(), $options['uo_import_add_to_group'] ) ) ? 'selected="selected"' : ''; ?>
												value="<?php echo get_the_ID() ?>"><?php the_title(); ?></option>
										<?php endwhile; ?>
									</select>
								<?php else : ?>
									<p class="uncannyowl-alert uncannyowl-alert-info"><?php esc_attr_e( 'No Groups Published', 'uncanny-pro-toolkit' ); ?></p>
								<?php endif; ?>
								<?php wp_reset_postdata(); ?>
							</div>
					</div>	
				</div> 
			</div>	
			
		</div>
	<?php } ?>

	<div class="uncannyowl-form-actions">
		<input type="submit" id="btn-save_options" class="uncannyowl-btn uncannyowl-btn--primary"
			   value="<?php esc_attr_e( 'Save Changes', 'uncanny-pro-toolkit' ); ?>"/>
	</div>

</form>
