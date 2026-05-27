<?php
/*
 * WPGear. New Users Monitor
 * uninstall.php
*/

	// if uninstall.php is not called by WordPress, die
	if (!defined('WP_UNINSTALL_PLUGIN')) {
		die;
	}
	
	$debug_process = 'uninstall';
	
	// Удаляем настройки Плагина
	delete_option('num_dashboard_newusers');
	delete_option('num_scan_newusers');
	delete_option('num_first_run');
	delete_option('num_option_adminonly');
	delete_option('num_disable_login');	
	
	// Удаляем метаполя Плагина у всех Пользователей
	global $wpdb;
	
	$num_usermeta_table = $wpdb -> prefix .'usermeta';
	$meta_key = 'num_confirm';

	// prepare for Delete - not need.
	$Query = "DELETE FROM $num_usermeta_table WHERE meta_key = $meta_key";
	NUM_Debugger ($Query, '$Query', $debug_process, __FUNCTION__, __LINE__);
	
	$wpdb -> query( $Query );  // phpcs:ignore 