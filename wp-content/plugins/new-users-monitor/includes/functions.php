<?php
/*
 * WPGear. New Users Monitor
 * functions.php
 */
 
	/* Get Unconfirmed Users. 
	----------------------------------------------------------------- */ 
	function NUM_Get_UnconfirmedUsers () {
		$debug_process = 'NUM_Get_UnconfirmedUsers';	

		global $wpdb;

		$num_users_table 	= $wpdb -> prefix .'users';
		$num_usermeta_table = $wpdb->prefix .'usermeta';
		// $meta_key = 'num_confirm';
		
		// $Query = "
			// SELECT user_id 
			// FROM " .$num_usermeta_table ."
			// WHERE meta_key = 'num_confirm' AND meta_value = %d
		// ";		
		
		$Query = "
			SELECT 
				users.ID, users.user_registered, users.user_nicename, users.display_name, users.user_email,
				confirm.meta_value AS confirm
			FROM " .$num_users_table ." users
			LEFT JOIN $num_usermeta_table confirm ON (confirm.user_id = users.ID AND confirm.meta_key = 'num_confirm')
			WHERE confirm.meta_value = %d";		

		$Query = $wpdb -> prepare( $Query, 0);   // phpcs:ignore 
		NUM_Debugger ($Query, '$Query', $debug_process, __FUNCTION__, __LINE__);
		
		$UnconfirmedUsers = $wpdb -> get_results ($Query, ARRAY_A);  // phpcs:ignore 
		NUM_Debugger ($UnconfirmedUsers, '$UnconfirmedUsers', $debug_process, __FUNCTION__, __LINE__);
		
		return $UnconfirmedUsers;
	}
	
	/* Get Count Unconfirmed Users. 
	----------------------------------------------------------------- */ 	
	function NUM_Get_Count_UnconfirmedUsers () {
		$debug_process = 'NUM_Get_Count_UnconfirmedUsers';
		
		$UnconfirmedUsers = NUM_Get_UnconfirmedUsers ();
		
		$Count_UnconfirmedUsers = count($UnconfirmedUsers);
		NUM_Debugger ($Count_UnconfirmedUsers, '$Count_UnconfirmedUsers', $debug_process, __FUNCTION__, __LINE__);
		
		return $Count_UnconfirmedUsers;
	}
 
	/* Debugger. 
	----------------------------------------------------------------- */
	function NUM_Debugger ($Content, $Subject = null, $Process = null, $Function = '', $Line = '') {
		if (function_exists( 'WPGear_Debugger' )) {
			$Source = 'NUM';
			$Description = 'Plugin: New Users Monitor';
			
			$TimeStamp = true;
			
			$Parameters = array(
				'source' => $Source,
				'description' => $Description,
				'content' => $Content,
				'subject' => esc_html( $Subject ),
				'process' => esc_html( $Process ),
				'function' => esc_html( $Function ),
				'timestamp' => $TimeStamp,
				'line' => esc_html( $Line ),
			);
			
			WPGear_Debugger ($Parameters);
		}
	}	
