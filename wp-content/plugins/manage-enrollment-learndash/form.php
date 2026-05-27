<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
		<div id="manage_enrollment_learndash" class="manage_enrollment_learndash">
			<h2>
				<img style="margin-right: 10px;" src="<?php echo esc_url(plugin_dir_url(__FILE__)."img/icon_30x30.png"); ?>"/>
				<?php _e("Manage Enrollment for LearnDash", "manage-enrollment-learndash"); ?>
			</h2>
			<hr>
			<?php
			if(!empty($upload_error)) {
				?>
				<div style="color: red">
					<?php echo wp_kses($upload_error, "post"); ?>
				</div>
				<?php
			}
			?>
			<table>
				<tr>
					<td style="width: 50%;">
						<table style="width: 90%;">
							<tr id="course">
								<td style="min-width: 100px"><?php _e("Course", "manage-enrollment-learndash"); ?></td>
								<td style="min-width: 400px">
									<select class="en_select2" id="course_id" name="course_id" onchange="manage_enrollment_learndash_course_selected(this)">
										<option value=""><?php _e("-- SELECT --", "manage-enrollment-learndash"); ?></option>
										<?php foreach ($courses as $key => $course): ?>
											<option value="<?php echo intVal($course->ID); ?>"><?php echo sanitize_text_field($course->post_title); ?></option>
										<?php endforeach ?>
									</select>
								</td>
								<td><button onclick="manage_enrollment_learndash_get_enrolled_users()" class="button"><?php _e("Get Users", "manage-enrollment-learndash"); ?></button></td>
							</tr>
							<tr id="group">
								<td style="min-width: 100px"><?php _e("Group", "manage-enrollment-learndash"); ?></td>
								<td style="min-width: 400px">
									<select class="en_select2" id="group_id" name="group_id" onchange="manage_enrollment_learndash_group_selected(this)">
										<option value=""><?php _e("-- SELECT --", "manage-enrollment-learndash"); ?></option>
										<?php foreach ($groups as $key => $group): ?>
											<option value="<?php echo intVal($group->ID); ?>"><?php echo sanitize_text_field($group->post_title); ?></option>
										<?php endforeach ?>
									</select>
								</td>
								<td><button onclick="manage_enrollment_learndash_get_enrolled_users()" class="button"><?php _e("Get Users", 'manage-enrollment-learndash'); ?></button></td>
							</tr>
							<tr id="users" style="display: none;">
								<td><?php _e("Users", "manage-enrollment-learndash"); ?></td>
								<td colspan="2">
									<input role="searchbox" value="" placeholder="<?php _e("Search User", "manage-enrollment-learndash"); ?>"/>
									<select id="user_ids" name="user_ids" onchange="manage_enrollment_learndash_users_selected(this)">
										<option value=""><?php _e("-- Select a User --", "manage-enrollment-learndash"); ?></option>
										<?php foreach ($users as $user) {
												$name = $user->ID.". ".$user->display_name;

												$additional_info = array();
												if($user->display_name != $user->user_login)
													$additional_info[] = $user->user_login;

												if($user->display_name != $user->user_email && $user->user_login != $user->user_email)
													$additional_info[] = $user->user_email;

												if(!empty($additional_info))
												$name = $name." (".implode(", ", $additional_info).")";
											?>
											<option value="<?php echo $user->ID; ?>" data-user_login="<?php echo strtolower( $user->user_login ); ?>" data-user_email="<?php echo strtolower( $user->user_email ); ?>"><?php echo sanitize_text_field($name); ?></option>
										<?php } ?>
									</select>
									(<?php _e("Select Users, or, enter comma separated or space separated User ID, Username or Email. You can even copy/paste from CSV. Hit SPACE BAR after pasting.", "manage-enrollment-learndash"); ?>)
								</td>

							</tr>
						</table>
					</td>
					<td>
						<form  method="post" enctype="multipart/form-data">
						<table id="upload_csv">
							<tr> <td colspan="2"> <b><?php _e("Alternative: CSV Upload", "manage-enrollment-learndash"); ?></b></td> </tr>
							<tr>
								<td style="min-width: 100px"><?php _e("File", "manage-enrollment-learndash"); ?></td>
								<td><input type="file" name="enrollments_file"><br>
									<div>
										<?php _e("Upload a CSV file (expected columns: user_id, user_email, user_login, course_id, group_id). One of user_id, user_email or user_login is required to identify the user.", "manage-enrollment-learndash"); ?>
										<br><br>
									</div>
								</td>
							</tr>
							<tr>
								<td><?php _e("Create Users:", "manage-enrollment-learndash"); ?></td>
								<td>Use <a href="<?php echo $import_user_from_csv_link; ?>" target="_blank">Import User from CSV</a><?php /* <input type="checkbox" name="create_users"> */ ?></td>
							</tr>
							<tr>
								<td></td>
								<td><input type="submit" name="manage_enrollment_learndash" value="Upload"></td>
							</tr>
						</table>
						</form>
					</td>

				</tr>
			</table>
		</div>
		<div id="manage_enrollment_learndash_table" class="manage_enrollment_learndash">
			<div class="button-secondary" id="process_enrollments" onclick="manage_enrollment_learndash_enroll()"><?php _e("Enroll Selected", "manage-enrollment-learndash"); ?> <span class="count"></span></div>
			<div class="button-secondary" id="process_unenrollments" onclick="manage_enrollment_learndash_unenroll()"><?php _e("Un-enroll Selected", "manage-enrollment-learndash"); ?> <span class="count"></span></div>
			<div class="button-secondary" id="check_enrollments" onclick="manage_enrollment_learndash_check_enrollment()"><?php _e("Check Enrollment Status", "manage-enrollment-learndash"); ?> <span class="count"></span></div>
			<span id="list_count"><?php echo sprintf( __("Total %s rows", "manage-enrollment-learndash"), '<span class="count">0</span>'); ?> </span>
			<br>

			<table class="grassblade_table" style="width: 100%">
				<tr class="header"><th><input type="checkbox" id="select_all"></th><th><?php _e("S.No", "manage-enrollment-learndash"); ?></th><th><?php _e("User", "manage-enrollment-learndash"); ?></th><th><?php _e("Course", "manage-enrollment-learndash"); ?></th><th><?php _e("Group", "manage-enrollment-learndash"); ?></th><th><?php _e("Actions", "manage-enrollment-learndash"); ?></th><th><?php _e("Status", "manage-enrollment-learndash"); ?></th></tr>
			</table>
		</div>