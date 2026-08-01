<?php
/*
 * WPGear. New Users Monitor.
 * admin.php
 */
 
	if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly
	}

	/* Create plugin SubMenu
	----------------------------------------------------------------- */	
	add_action( 'admin_menu', 'NUM_create_menu' );
	function NUM_create_menu() {
		$debug_process = 'admin_menu';
		
		add_users_page(
			__('New Users Monitor', 'new-users-monitor'),
			__('New Users Monitor', 'new-users-monitor'),
			'edit_dashboard',
			'new-users-monitor/includes/admin/options.php',
			''
		);
	}
	
	/* Admin Console - Add Settings to Plugin Actions.
	----------------------------------------------------------------- */
	add_filter ('plugin_action_links_new-users-monitor/new-users-monitor.php', 'NUM_Filter_plugin_action_links');
	function NUM_Filter_plugin_action_links( $Links ) {
	   $debug_process = 'plugin_action_links';
	   
	   NUM_Debugger ($Links, '$Links', $debug_process, __FUNCTION__, __LINE__);
	   
	   $Links[] = '<a href="'. esc_url( get_admin_url(null, 'users.php?page=new-users-monitor/includes/admin/options.php') ) .'">Settings</a>';
	   
	   return $Links;
	}
	
	/* User Profile Page. Add CheckBox option.
	----------------------------------------------------------------- */	
	add_action('edit_user_profile', 'NUM_Action_edit_user_profile');
	function NUM_Action_edit_user_profile ($user) {
		$debug_process = 'NUM_Action_edit_user_profile';		
		
		$User_ID = $user -> ID;
		
		$meta_key = 'num_confirm';
		$meta_value = true;
		
		$NUM_Confirm = get_user_meta( $User_ID, $meta_key, true );
		NUM_Debugger ($NUM_Confirm, '$NUM_Confirm', $debug_process, __FUNCTION__, __LINE__);
		
		if ($NUM_Confirm !== '1') {
			$meta_value = false;
		} ?>
		<table class="form-table">
			<tbody>
				<tr id="box_bso_confirm">
					<th <?php if( $meta_value == false ) {echo esc_attr( 'style="color: red;"' );} ?>><?php echo esc_html( __('Confirmation (new User)', 'new-users-monitor') ); ?></th>
					<td>
						<label for="num_confirm">
						<input name="num_confirm" type="checkbox" id="num_confirm" <?php if( $meta_value ) {echo esc_attr( "checked" );} ?>> <?php echo esc_html( __('Confirm the credentials of this User', 'new-users-monitor') ); ?></label>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}	
	
	/* User Profile Page. Save CheckBox option.
	----------------------------------------------------------------- */	
	add_action( 'edit_user_profile_update', 'NUM_Action_edit_user_profile_update' );
	function NUM_Action_edit_user_profile_update($User_ID) {
		if (!current_user_can( 'edit_user', $User_ID )) {
			return false;
		}

		$debug_process = 'NUM_Action_edit_user_profile_update';
		
		$NUM_Nonce = 'update-user_' .$User_ID;

		$WP_Nonce = isset($_POST['_wpnonce']) ? sanitize_text_field (wp_unslash($_POST['_wpnonce'])) : 'none';
		NUM_Debugger ($WP_Nonce, '$WP_Nonce', $debug_process, __FUNCTION__, __LINE__);

		if (!wp_verify_nonce($WP_Nonce, $NUM_Nonce)) {
			?>
				<div class="wrap">
					<h2><?php echo esc_html( get_admin_page_title() );?></h2>
					<hr>
					<div class="wdpq_options_box">						
						<?php echo esc_html( __('Warning! Data Incorrect. Update Disable.', 'new-users-monitor') ); ?>
					</div>
				</div>
			<?php
			
			NUM_Debugger ($NUM_Nonce, 'NUM_Nonce != WP_Nonce', $debug_process, __FUNCTION__, __LINE__);
			exit;
		}
		
		$meta_key = 'num_confirm';
		$meta_value = isset( $_POST['num_confirm'] ) ? '1' : '0';
		NUM_Debugger ($meta_value, '$meta_value: num_confirm', $debug_process, __FUNCTION__, __LINE__);
		
		$NUM_Confirm_Last = get_user_meta ( $User_ID, $meta_key, true );
		NUM_Debugger ($NUM_Confirm_Last, 'NUM_Confirm_Last', $debug_process, __FUNCTION__, __LINE__);
		
		if ($NUM_Confirm_Last != $meta_value) {
			// Надо обновить
			update_user_meta ($User_ID, $meta_key, $meta_value); // phpcs:ignore
			
			// Делаем отметку в Журнале (как изменение Подтверждения Полномочий).
			// ...
		}
	}
	
	// Users. Добавляем новые колонки.
	add_filter( 'manage_users_columns', 'NUM_Filter_manage_users_columns' );
	function NUM_Filter_manage_users_columns ($column) {
		$column['confirm'] = 'Confirm';

		return $column;
	}
	
	// Users. Формируем новые колонки.
	add_filter ('manage_users_custom_column', 'NUM_Filter_manage_users_custom_column', 10, 3);
	function NUM_Filter_manage_users_custom_column ($output, $column_name, $user_id) {
		// Confirm
		if ($column_name == 'confirm') {
			$meta_key = 'num_confirm';
			$NUM_Confirm = get_user_meta ($user_id, $meta_key, true);
			
			$Field_Style = '';
			$Field_Title = '';
			$Field_Content = 'ON';				
			
			if ($NUM_Confirm != 1) {
				$Field_Style = 'color: red;';
				$Field_Title = __('User Profile - No Confirm!', 'new-users-monitor');
				
				$Field_Content = 'OFF';
			} 
			
			$output = "<span style='" .esc_html( $Field_Style ) ."' title='" .esc_html( $Field_Title ) ."'>" .esc_html( $Field_Content ) ."</span>";
		}
		
		return $output;
	}

	// Users. Делаем новые колонки сортируемыми.
	add_filter ('manage_users_sortable_columns', 'NUM_Filter_manage_user_column_sortable');
	function NUM_Filter_manage_user_column_sortable ($sortable_columns) {
		$sortable_columns['confirm'] = 'confirm';
		
		return $sortable_columns;
	}

	// Users. Делаем сортировку новых колонок.
	add_filter ('pre_user_query', 'NUM_Filter_pre_user_query');
	function NUM_Filter_pre_user_query ($user_query) {
		global $current_screen;
		
		$Screen_ID = isset ($current_screen -> id) ? $current_screen -> id : null;
		
		if ($Screen_ID != 'users') return;
		
		global $wpdb;
		
		$users_table 	= $wpdb -> prefix .'users';
		$usermeta_table = $wpdb -> prefix .'usermeta';
		
		$vars = $user_query->query_vars;	

		// "Confirm"
		if ($vars['orderby'] == 'confirm') {
			$user_query -> query_orderby = " ORDER BY $usermeta_table.meta_value ". $vars['order'];
			$user_query -> query_from = " FROM $users_table INNER JOIN $usermeta_table ON ($users_table.ID = $usermeta_table.user_id)";
			$user_query -> query_where = " WHERE $usermeta_table.meta_key = 'num_confirm'";
		}

		return $user_query;
	}