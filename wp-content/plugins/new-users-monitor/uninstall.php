<?php
/*
 * WPGear. New Users Monitor
 * uninstall.php
*/

	if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly
	}
	
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
	
	$NUM_usermeta_table = $wpdb -> prefix .'usermeta';
	$NUM_meta_key = 'num_confirm';

	// prepare for Delete - not need.
	$NUM_Query = "DELETE FROM $NUM_usermeta_table WHERE meta_key = $NUM_meta_key";
	NUM_Debugger ($NUM_Query, '$NUM_Query', $debug_process, __FUNCTION__, __LINE__);
	
	$wpdb -> query( $NUM_Query );  // phpcs:ignore 