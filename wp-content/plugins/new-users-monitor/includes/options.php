<?php
/*
 * WPGear. New Users Monitor
 * options.php
 */
	$debug_process = 'options';
	
	$NUM_Nonce = 'Update_Options_New_Users_Monitor';
	$nonce = wp_create_nonce ($NUM_Nonce);

	$Search_NUM	= isset($_REQUEST['s']) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : null;
	$Action 	= isset($_REQUEST['action']) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : null;
	$WP_Nonce 	= isset($_REQUEST['_wpnonce']) ? sanitize_text_field (wp_unslash($_REQUEST['_wpnonce'])) : 'none';
	
	NUM_Debugger ($_REQUEST, '$_REQUEST', $debug_process, __FUNCTION__, __LINE__); // phpcs:ignore	
	
	if ($Action == 'Update') {
		
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
			
			exit;
		}		
		
		$NUM_Setup_AdminOnly 	= isset( $_REQUEST['num_option_adminonly'] ) ? 1 : 0;
		$NUM_Disable_Login 		= isset( $_REQUEST['num_disable_login'] ) ? 1 : 0;
		$NUM_Dashboard_NewUsers = (isset( $_REQUEST['dashboard_newusers'] ) && $_REQUEST['dashboard_newusers'] != '' ) ? sanitize_text_field( wp_unslash( $_REQUEST['dashboard_newusers'] ) ) : 10;
		$NUM_Scan_NewUsers 		= (isset( $_REQUEST['scan_newusers'] ) && $_REQUEST['scan_newusers'] != '' ) ? sanitize_text_field( wp_unslash( $_REQUEST['scan_newusers'] ) ) : 1;
		
		update_option( 'num_option_adminonly', $NUM_Setup_AdminOnly ); // phpcs:ignore			
		update_option( 'num_disable_login', $NUM_Disable_Login ); // phpcs:ignore		
		update_option( 'num_dashboard_newusers', $NUM_Dashboard_NewUsers ); // phpcs:ignore		
		update_option( 'num_scan_newusers', $NUM_Scan_NewUsers ); // phpcs:ignore		
	}
	
	$NUM_Setup_AdminOnly 	= get_option( 'num_option_adminonly', 1 );
	$NUM_Dashboard_NewUsers = get_option( 'num_dashboard_newusers', 10 );
	$NUM_Scan_NewUsers		= get_option( 'num_scan_newusers', 1 );	
	$NUM_Disable_Login		= get_option( 'num_disable_login', 1 );
	
	if ($NUM_Setup_AdminOnly) {
		if (!current_user_can('edit_dashboard')) {
			?>
			<div class="num_warning" style="margin: 40px;">				
				<?php echo esc_html( __('Sorry, you are not allowed to view this page.', 'new-users-monitor') ); ?>
			</div>
			<?php
			
			return;
		}		
	}		

	if( ! class_exists( 'WP_List_Table' ) ) {
		// Возможно, что надо подключать этот класс (если его нет) при инициализации Плагина.
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	}
	
	$NUM_Users_ListTable = new NUM_ListTable();
	$NUM_Users_ListTable -> prepare_items();	
?>
	<div class="wrap">
		<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
		<hr>
		
		<div class="num_addnew_box">
			<form name="form_NUM_Options" method="post" style="margin-top: 20px;">
				<div style="margin-left: 38px; margin-bottom: 10px; ">	
					<div style="margin-top: 10px;">
						<label for="num_option_adminonly" title="On/Off">
							<?php echo esc_html( __('Enable this Page for Admin only', 'new-users-monitor') ); ?>
						</label>
						<input id="num_option_adminonly" name="num_option_adminonly" type="checkbox" <?php if($NUM_Setup_AdminOnly) {echo 'checked';} ?>>
					</div>
					
					<div style="margin-top: 15px;">
						<label for="num_disable_login" title="On/Off">
							<?php echo esc_html( __('Disable Login for Non-Confirmed User', 'new-users-monitor') ); ?>
						</label>
						<input id="num_disable_login" name="num_disable_login" type="checkbox" <?php if($NUM_Disable_Login) {echo 'checked';} ?>>
					</div>						
				
					<div style="margin-top: 10px;">
						<label for="dashboard_newusers" title="How many New Users to display in the console widget"><?php echo esc_html( __('Number of new users in the widget:', 'new-users-monitor') ); ?> </label>
						<input id="dashboard_newusers" name="dashboard_newusers" type="text" style="width: 40px; text-align: center;" value="<?php echo esc_attr( $NUM_Dashboard_NewUsers ); ?>">
					</div>
					
					<div style="margin-top: 10px;">
						<label for="scan_newusers" title="New users can appear as a result of hacking or malicious intent. It would be good to know about this as quickly as possible!"><?php echo esc_html( __('DB scanning period for finding new users (Hour):', 'new-users-monitor') ); ?> </label>
						<input id="scan_newusers" name="scan_newusers" type="text" style="width: 40px; text-align: center;" value="<?php echo esc_attr( $NUM_Scan_NewUsers ); ?>">
						<span style="color: grey;"><?php echo esc_html( __('(notification will be sent to the Administrator automatically)', 'new-users-monitor') ); ?></span>
					</div>				
				</div>
				
				<hr>

				<div style="margin-top: 10px; margin-bottom: 5px; text-align: right;">
					<span id="save_options_processing" style="display: none; margin-right: 5px;">
						<i class="fa fa-refresh fa-spin fa-fw fa-2x" aria-hidden="true" style="vertical-align: baseline;"></i>
					</span>
					<input id="btn_options_save" type="submit" class="button button-primary" style="margin-right: 5px;" value="<?php echo esc_html( __('Save', 'new-users-monitor') ); ?>">
				</div>
				<input id="action" name="action" type="hidden" value="Update">
				<input id="_wpnonce" name="_wpnonce" type="hidden" value="<?php echo esc_attr($nonce); ?>">	
			</form>
		</div>

		<div>
			<form method="post">
				<?php 
				$NUM_Users_ListTable -> search_box( 'Search', 'search_id' );
				$NUM_Users_ListTable -> display(); 
				?>
			</form>
		</div>		
	</div>
	
	<?php	
	class NUM_ListTable extends WP_List_Table {			
	
		public function prepare_items() {
			$columns = $this->get_columns();
			$hidden = $this->get_hidden_columns();
			$sortable = $this->get_sortable_columns();
			$data = $this->table_data();
			usort( $data, array( &$this, 'sort_data' ) );
			$perPage = 20;
			$currentPage = $this->get_pagenum();
			$totalItems = count($data);
			$this->set_pagination_args( array(
				'total_items' => $totalItems,
				'per_page'    => $perPage
			) );
			$data = array_slice($data,(($currentPage-1)*$perPage),$perPage);
			$this->_column_headers = array($columns, $hidden, $sortable);
			$this->items = $data;			
		}		

		public function get_columns() {
			$columns = array(
				'cb'          			=> '<input type="checkbox" />',
				'user_registered'		=> 'Date reg.',
				'user_nicename'       	=> 'Login',
				'display_name'       	=> 'NickName',
				'user_email'			=> 'E-mail',
				'user_role'				=> 'Role',
				'controls'				=> '',				
			);
			return $columns;
		}
		
		public function get_hidden_columns() {
			return array();
		}	

		public function get_sortable_columns() {
			$sortable_columns = array(
				'display_name' => array('display_name', false),
				'user_nicename' => array('user_nicename', false),
				'user_registered' => array('user_registered', false),
				'user_email' => array('user_email', false),
				'user_role' => array('user_role', false),
			  );
			return $sortable_columns;
		}		
		
		function extra_tablenav( $which ){
			echo '<div class="alignleft actions">[ Unconfirmed Users only ]</div>';
		}
		
		private function table_data() {
			$debug_process = 'options-table_data';
			
			global $wpdb;
			global $Search_NUM;

			$num_users_table 	= $wpdb -> prefix .'users';
			$num_usermeta_table = $wpdb -> prefix .'usermeta';			
			
			// Поиск по критерию. 
			if ($Search_NUM) {
				$like = '%' .$wpdb->esc_like($Search_NUM) .'%';
				$Query = "
					SELECT 
						users.ID, users.user_registered, users.user_nicename, users.display_name, users.user_email,
						confirm.meta_value AS confirm
					FROM " .$num_users_table ." users
					LEFT JOIN $num_usermeta_table confirm ON (confirm.user_id = users.ID AND confirm.meta_key = 'num_confirm')
					WHERE 
						(users.user_nicename LIKE %s OR
						users.user_email LIKE %s OR
						users.display_name LIKE %s OR
						CAST(users.user_registered AS CHAR) LIKE %s
						) AND 
						confirm.meta_value != '1'";
				
				$Prepare = $wpdb -> prepare ($Query, $like, $like, $like, $like); // phpcs:ignore		
				NUM_Debugger ($Prepare, '$Prepare', $debug_process, __FUNCTION__, __LINE__);
				
				$targets = $wpdb -> get_results ($Prepare, ARRAY_A); // phpcs:ignore		
			} else {				
				$Query = "
					SELECT 
						users.ID, users.user_registered, users.user_nicename, users.display_name, users.user_email,
						confirm.meta_value AS confirm
					FROM " .$num_users_table ." users
					LEFT JOIN $num_usermeta_table confirm ON (confirm.user_id = users.ID AND confirm.meta_key = 'num_confirm')
					WHERE confirm.meta_value = %d";
				
				$Prepare = $wpdb -> prepare ($Query, 0); // phpcs:ignore		
				NUM_Debugger ($Prepare, '$Prepare', $debug_process, __FUNCTION__, __LINE__);
				
				$targets = $wpdb -> get_results ($Prepare, ARRAY_A); // phpcs:ignore		
			}
			
			return $targets;
		}
		
		public function column_default( $item, $column_name ) {
			$debug_process = 'NUM_options_column_default';
			// NUM_Debugger ($item, '$item', $debug_process, __FUNCTION__, __LINE__);
			// NUM_Debugger ($column_name, '$column_name', $debug_process, __FUNCTION__, __LINE__);
			
			switch( $column_name ) {
				case 'user_registered':
				case 'user_nicename':
				case 'display_name':
				case 'user_email':
				case 'user_role':
					return $item[ $column_name ];
				default:
					return '---' ;
			}
		}
		
		private function sort_data( $a, $b ) {
			$orderby 	= isset($_GET['orderby']) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'user_registered';	// phpcs:ignore 
			$order 		= isset($_GET['order']) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'DESC';	// phpcs:ignore 

			$result = strcmp ($a[$orderby], $b[$orderby]);
			if ($order === 'asc') {
				return $result;
			} else {
				return -$result;
			}
		}
		
		// заполнение колонки cb
		function column_cb( $item ){
			echo '<input type="checkbox" name="licids[]" id="cb-select-'. esc_html ( $item['ID'] ) .'" value="'. esc_html( $item['ID'] ) .'" />';
		}	

		// заполнение колонки User_Role
		function column_user_role( $item ){
			$user_info 	= get_userdata($item['ID']); 
			$roles		= $user_info -> roles;			
			echo esc_html( implode(', ', $roles) );
		}		
		
		// заполнение колонки controls
		function column_controls( $item ){	
			$Item_ID = $item['ID'];
			$Link = get_edit_user_link( $Item_ID );

			echo "<div class='num_column_controls_row'><a href='" .esc_url( $Link ) ."' title='Edit'>Edit</a></div>";
		}			
	}		
	