<?php
/*
 * WPGear. New Users Monitor
 * widgets.php
 */
	
	/* Create New-Users DashboardWidget
	----------------------------------------------------------------- */	
	add_action( 'wp_dashboard_setup', 'NUM_Dashboard_Widgets_NewUsers' );
	function NUM_Dashboard_Widgets_NewUsers() {
		if (current_user_can( 'edit_dashboard' )) {
			global $wp_meta_boxes;
			
			wp_add_dashboard_widget( 'num_newuser_widget', 'New Users Monitor', 'NUM_Dashboard_NewUsers' );			
		}
	}	
	
	/* New-Users DashboardWidget
	----------------------------------------------------------------- */	
	function NUM_Dashboard_NewUsers() {
		$debug_process = 'NUM_Dashboard_NewUsers';
		
		global $wpdb;
		global $NUM_Dashboard_NewUsers;
		
		$num_users_table = $wpdb -> prefix .'users';
		NUM_Debugger ($num_users_table, '$num_users_table', $debug_process, __FUNCTION__, __LINE__);

		$Query = "
			SELECT * FROM " .$num_users_table ."
			WHERE user_status = %d 
			ORDER BY ID DESC LIMIT %d";			
			
		$Query = $wpdb -> prepare( $Query, 0, $NUM_Dashboard_NewUsers ); // phpcs:ignore 
		NUM_Debugger ($Query, '$Query', $debug_process, __FUNCTION__, __LINE__);
			
		$users = $wpdb -> get_results ($Query);  // phpcs:ignore 
		
		$Count_UnconfirmedUsers = NUM_Get_Count_UnconfirmedUsers();
		NUM_Debugger ($Count_UnconfirmedUsers, '$Count_UnconfirmedUsers', $debug_process, __FUNCTION__, __LINE__);			

		?>
		<table style="width: 100%">
			<tbody style="text-align: left;">
				<th><h3>Date reg.</h3></th>
				<th><h3>Login</h3></th>
				<th><h3>Email</h3></th>
				<th><h3>Role</h3></th>
				<?php 
				
				foreach ($users as $user) {
					$User_ID 	= $user->ID;
					$reg_date 	= $user->user_registered;
					$nicename	= $user->user_nicename;
					$user_email	= $user->user_email;
					
					$user_info 	= get_userdata($User_ID); 
					$roles		= $user_info->roles;					
					
					// Проверка подтверждения Нового Пользователя.
					$meta_key = 'num_confirm';
					$NUM_Confirm = get_user_meta( $User_ID, $meta_key, true );
					
					if ($NUM_Confirm == '') {
						// Если у Пользователя еще нет метаполей (Пользователь появился до следующего запуска сканирования), то формируем поле со значением ' '
						$meta_value = ' ';
						update_user_meta( $User_ID, $meta_key, $meta_value );						
					}
					
					$Style = "";
					$Title = "";
					
					if ($NUM_Confirm !== '1') {
						$Style = "color:red;";
						$Title = __('Unconfirmed User!', 'new-users-monitor');
					}

					?>
					<tr style="<?php echo esc_attr( $Style ); ?>" title="<?php echo esc_attr( $Title ); ?>" >
						<td>
							<?php  echo esc_html( gmdate('Y-m-d H:i', strtotime($reg_date)) );?>
						</td>
						
						<td>
							<a href="<?php echo esc_html( get_edit_user_link( $User_ID ) ); ?>"><?php echo esc_html( $nicename ); ?></a>
						</td>
						
						<td>
							<?php echo esc_html( $user_email ); ?>
						</td>
						
						<td>
							<?php echo  esc_html( implode(', ', $roles) );?>
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
		
		<script>
			var Count_UnconfirmedUsers = <?php echo esc_html( $Count_UnconfirmedUsers ); ?>;
			var NUM_Widget = document.getElementById("num_newuser_widget");
			var NUM_Widget_Header = NUM_Widget.getElementsByTagName("h2")[0];
			
			if (Count_UnconfirmedUsers > 0) {
				var NUM_Widget_Count_UnconfirmedUsersCaption = "<?php echo esc_html( __('Count of Unconfirmed Users', 'new-users-monitor') ); ?>";
				var NUM_Widget_Count_UnconfirmedUsers = "<span title='" + NUM_Widget_Count_UnconfirmedUsersCaption + "'; style='color:red; cursor:help;'> (" + Count_UnconfirmedUsers + ")";
			} else {
				var NUM_Widget_Count_UnconfirmedUsers = "";
			}
			
			var NUM_Widget_LinkTitle 	= "<?php echo esc_html( __('Click to open Setup-Option Page!', 'new-users-monitor') ); ?>";			
			var NUM_Widget_LinkCaption 	= "<?php echo esc_html( __('New Users Monitor', 'new-users-monitor') ); ?>";
			
			NUM_Widget_Header.innerHTML = '<span title="' + NUM_Widget_LinkTitle + '"><a href="/wp-admin/users.php?page=new-users-monitor/includes/options.php" class="ulm_dashboard_widget_header">' + NUM_Widget_LinkCaption + '</a>' + NUM_Widget_Count_UnconfirmedUsers + '</span>';
		</script>		
	<?php }	