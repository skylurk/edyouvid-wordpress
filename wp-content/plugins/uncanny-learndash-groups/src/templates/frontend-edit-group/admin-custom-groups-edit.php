<?php

namespace uncanny_learndash_groups;

if ( ! defined( 'WPINC' ) ) {
	die;
}
global $post;
$group_id     = (int) ulgm_filter_input( 'group-id' );
$redirect_url = SharedFunctions::get_group_management_page_id( true );
$redirect_url = add_query_arg( 'group-id', $group_id, $redirect_url );
?>
<div class="wrap">
	<div class="ulgm">
		<div id="ulg-create-group">
			<div class="ulgm-admin-content">
				<div class="uo-ulgm-front form-table group-management-form">

					<form name="edit-group-wizard" method="post" action="<?php echo $redirect_url; ?>">
						<input type="hidden" value="<?php echo $redirect_url; ?>" name="redirect_to"/>
						<input type="hidden" value="yes" name="is_front_end"/>
						<input type="hidden" value="<?php echo get_permalink( $post->ID ); ?>"
								name="edit_group_page_id"/>
						<?php

						wp_nonce_field( 'ulgm_nonce', 'is_custom_group_edit_nonce' );
						?>

						<!-- Group Details -->
						<div class="uo-edit-group-section uo-edit-group-section--first">
							<div class="uo-edit-group-header">
								<h3
									class="uo-edit-group-title"><?php echo __( 'Group details', 'uncanny-learndash-groups' ); ?></h3>
							</div>
							<div class="uo-edit-group-block">
								<div class="uo-edit-group-form">

									<input class="uo-edit-group-input" name="ulgm_group_id"
											id="ulgm_group_id" type="hidden"
											value="<?php echo $group_id; ?>"/>

									<!-- Group Parent -->
									<?php
									if ( $parent_group && learndash_is_groups_hierarchical_enabled() ) {
										require_once Utilities::get_include( 'class-walker-group-dropdown.php' );
										$walker        = new \Walker_GroupDropdown();
										$dropdown_args = array(
											'selected'     => wp_get_post_parent_id( $group_id ),
											'sort_column'  => 'post_title',
											'hierarchical' => true,
										);
										?>
										<div
											class="uo-edit-group-field uo-edit-group-field--group-name">
											<div
												class="uo-edit-group-label"><?php echo __( 'Group parent', 'uncanny-learndash-groups' ); ?></div>
											<select name="parent_group_id" id="parent_group_id">
												<option
													value="0"><?php echo esc_attr__( 'No parent', 'uncanny-groups' ); ?></option>
												<?php
												echo $walker->walk( SharedFunctions::get_group_leader_groups( wp_get_current_user()->ID, array( $group_id ) ), 0, $dropdown_args );
												?>
											</select>
										</div>
									<?php } ?>

									<!-- Group Name -->
									<?php if ( $group_name ) { ?>
										<div
											class="uo-edit-group-field uo-edit-group-field--group-name">
											<div
												class="uo-edit-group-label"><?php echo __( 'Group name', 'uncanny-learndash-groups' ); ?></div>
											<input class="uo-edit-group-input" name="ulgm_group_name"
													id="ulgm_group_name" type="text" required="required"
													value="<?php echo( $group_id ? get_the_title( $group_id ) : '' ); ?>"/>
											<?php if ( ! empty( $group_id ) && ulgm_filter_has_var( 'group-name-error' ) ) { ?>
												<p
													style="color:red;"><?php echo __( 'Group name is required.', 'uncanny-learndash-groups' ); ?></p>
											<?php } ?>
										</div>
									<?php } ?>

									<!-- Total Seats -->
									<?php
									if ( $total_seats ) {
										?>
										<?php $existing_seats = (int) ulgm()->group_management->seat->total_seats( $group_id ); ?>
										<div
											class="uo-edit-group-field uo-edit-group-field--total-seats">
											<div
												class="uo-edit-group-label"><?php echo __( 'Total seats', 'uncanny-learndash-groups' ); ?></div>
											<input <?php echo $disabled; ?>
												class="uo-edit-group-input"
												name="ulgm_group_total_seats"
												id="ulgm_group_total_seats" type="number"
												required="required"
												value="<?php echo $existing_seats; ?>"
												placeholder="<?php echo __( 'Ex. 10', 'uncanny-learndash-groups' ); ?>"
												min="1"/>
											<?php if ( ! empty( $group_id ) && ulgm_filter_has_var( 'seat-available-error' ) ) { ?>
												<p
													style="color:red;"><?php echo __( 'The "Total seats" value is lower than the number of students in the group. Please increase the seat count.', 'uncanny-learndash-groups' ); ?></p>
											<?php } ?>
										</div>
										<?php
									}
									?>

									<!-- Group Courses -->
									<?php
									if ( $group_courses ) {
										$learndash_group_enrolled_courses = learndash_group_enrolled_courses( $group_id );
										?>
										<div
											class="uo-edit-group-field uo-edit-group-field--group-courses">
											<div
												class="uo-edit-group-label"><?php printf( _x( 'Group %s', 'Group courses', 'uncanny-learndash-groups' ), \LearnDash_Custom_Label::get_label( 'courses' ) ); ?></div>

											<?php
											if ( false === $is_editable_woo ) {
												?>
												<ul>
													<?php foreach ( $learndash_group_enrolled_courses as $ld_course ) { ?>
														<li><?php echo get_the_title( $ld_course ); ?></li>
													<?php } ?>
												</ul>
												<?php
											} else {
												$args = array(
													'post_type'      => 'sfwd-courses',
													'posts_per_page' => 9999,
													'post_status'    => 'publish',
													'orderby'        => 'title',
													'order'          => 'ASC',
												);

												if ( ! empty( $atts['category'] ) ) {
													$tax_query = array(
														'relation' => 'OR',
														array(
															'taxonomy' => 'category',
															'field'    => 'slug',
															'terms'    => array_map(
																'trim',
																explode( ',', $atts['category'] )
															),
														),
													);
												}
												if ( ! empty( $atts['course_category'] ) ) {
													$tax_query[] = array(
														'taxonomy' => 'ld_course_category',
														'field'    => 'slug',
														'terms'    => array_map(
															'trim',
															explode(
																',',
																$atts['course_category']
															)
														),
													);
												}
												if ( ! empty( $tax_query ) ) {
													$args['tax_query'] = $tax_query;
												}
												$courses = get_posts( $args );
												?>
												<select <?php //echo $disabled; ?>
													class="uo-edit-group-select"
													id="ulg-create-group__courses-list"
													multiple="multiple" name="ulgm_group_courses[]"
													size="10">
													<?php
													if ( $learndash_group_enrolled_courses ) {
														foreach ( $learndash_group_enrolled_courses as $ld_courses ) {
															?>
															<option selected="selected"
																	value="<?php echo esc_attr( $ld_courses ); ?>
																"><?php echo get_the_title( $ld_courses ); ?></option>
															<?php
														}
													}
													if ( $courses ) {
														foreach ( $courses as $course ) {
															if ( in_array( $course->ID, $learndash_group_enrolled_courses ) ) {
																continue;
															}
															?>
															<option
																value="<?php echo esc_attr( $course->ID ); ?>"><?php echo esc_attr( $course->post_title ); ?></option>
															<?php
														}
													}
													?>
												</select>

												<div
													class="uo-edit-group-description"><?php printf( __( 'Press Ctrl to select multiple %s.', 'uncanny-learndash-groups' ), \LearnDash_Custom_Label::get_label( 'courses' ) ); ?></div>
											<?php } ?>

										</div>
									<?php } ?>

									<!-- Group image -->
									<?php

									//if ( $group_image && current_user_can( 'manage_options' ) ) {
									// Disabling this feature for now.
									if ( false !== false ) {

										$group_image_id = get_post_meta( $group_id, '_thumbnail_id', true );
										$image          = wp_get_attachment_image_src( $group_image_id );
										?>
										<div
											class="uo-edit-group-field uo-edit-group-field--group-image">
											<div
												class="uo-edit-group-label"><?php echo __( 'Group image', 'uncanny-learndash-groups' ); ?></div>
											<?php if ( ! empty( $image[0] ) ) { ?>
												<div class='image-wrapper'>
													<img src="<?php echo $image[0]; ?>"
														id='ulgm_group_edit_image' width='100'
														height='100'
														style='max-height: 100px; width: 100px;'>
												</div>
											<?php } ?>
											<div class='image-preview-wrapper'>
												<img id='ulgm_group_edit_image_preview' src=''
													width='100' height='100'
													style='max-height: 100px; width: 100px;display: none'>
											</div>
											<input id="ulgm_group_edit_upload_image_button"
													type="button" class="button"
													value="<?php echo __( 'Upload image', 'uncanny-learndash-groups' ); ?>"/>
											<input type='hidden'
													name='ulgm_group_edit_image_attachment_id'
													id='ulgm_group_edit_image_attachment_id' value=''>
										</div>

									<?php } ?>

								</div>
							</div>
						</div>

						<!-- Submit -->
						<div class="uo-edit-group-section uo-edit-group-section-without-box">
							<div class="uo-edit-group-block">
								<div class="uo-edit-group-form">
									<div
										class="uo-edit-group-field uo-edit-group-field--submit uo-edit-group-no-space">
										<input type="submit" name="submit" id="submit"
												class="uo-edit-group-form-submit btn btn-primary button button-primary"
												value="<?php _e( 'Update group', 'uncanny-learndash-groups' ); ?>">
									</div>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
