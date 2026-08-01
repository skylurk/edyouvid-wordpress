<?php
/*
Plugin Name: New Users Monitor
Plugin URI: https://wpgear.xyz/new-users-monitor
Description: Ext Security. Automatic scanning of the Users list, and detect unauthorized addition to the DB. Informs immediately Admin by email. Informative Widget in the console. (It is convenient to use with the plugin "Users Login Monitor")
Version: 3.22
Text Domain: new-users-monitor
Domain Path: /languages
Author: WPGear
Author URI: https://wpgear.xyz
License: GPLv2
*/

	if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly
	}

	$NUM_plugin_url = plugin_dir_url( __FILE__ ); // со слэшем на конце
	
	$NUM_Setup_AdminOnly 	= get_option( 'num_option_adminonly', 1 );		// Админка не для всех.
	$NUM_Dashboard_NewUsers = get_option( 'num_dashboard_newusers', 10 );	// Количество новых пользователей в виджете консоли.	
	$NUM_Scan_NewUsers		= get_option( 'num_scan_newusers', 1 );			// Период проверки обнаружения новых Пользователей в Часах.
	$NUM_FirstRun			= get_option( 'num_first_run', 0 );				// Тригер первого запуска. Чтобы не считать имеющихся Пользователей как новых.
	$NUM_Disable_Login		= get_option( 'num_disable_login', 1 );			// Запрещаем Вход, если Пользователь не подтвержден.	
	
	$NUM_LocalePath = dirname (plugin_basename ( __FILE__ )) . '/languages/';

	include_once( __DIR__ .'/includes/functions.php' );
	include_once( __DIR__ .'/includes/admin/admin.php' );
	include_once( __DIR__ .'/includes/admin/widgets.php' );
	include_once( __DIR__ .'/includes/schedules.php' );
	
	/* Translate.
	----------------------------------------------------------------- */
	add_action ('init', 'NUM_Action_Init');
	function NUM_Action_Init() {
		global $NUM_LocalePath;
				
		$Result = load_plugin_textdomain ('new-users-monitor', false, $NUM_LocalePath);
	}
	
	/* Admin Console - Styles.
	----------------------------------------------------------------- */	
	add_action ('admin_enqueue_scripts', 'NUM_Action_admin_style' );
	function NUM_Action_admin_style ($hook) {
		$screen = get_current_screen();
		$screen_base = $screen->base;

		if ($screen_base == 'dashboard' || $screen_base == 'new-users-monitor/options') {
			global $NUM_plugin_url;			
		
			wp_enqueue_style ('num_admin-style', $NUM_plugin_url .'includes/admin/admin-style.css'); // phpcs:ignore
			// wp_enqueue_style ('font-awesome_4.7', "https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css");	 // phpcs:ignore 	
		}
	}
	
	/* Запрещаем Вход, если Пользователь не подтвержден.
	----------------------------------------------------------------- */	
	add_filter( 'wp_authenticate_user', 'NUM_Filter_wp_authenticate_user', 1 );	
	function NUM_Filter_wp_authenticate_user( $user ) {
		global $NUM_Disable_Login;
		
		if ($NUM_Disable_Login) {
			$User_ID = $user -> ID;
			
			if ($User_ID) {			
				$is_User_Confirmed = get_user_meta ($User_ID, 'num_confirm', true);	
				
				if (!$is_User_Confirmed) {						
					$error = new WP_Error ('no_confirm_user', __('Authentication Impossible.', 'new-users-monitor'));				
					
					return $error;
				}
			}		
		}

		return $user;	
	}