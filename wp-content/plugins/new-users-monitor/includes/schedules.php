<?php
/*
 * WPGear. New Users Monitor
 * schedules.php
 */
	
	if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly
	}
	
	// Шедуллер. Клиринг при деактивации.
	register_deactivation_hook( __FILE__, 'num_deactivation');
	function num_deactivation() {
		wp_clear_scheduled_hook('num_scan_newusers');
	}	
	
	// Шедуллер. Регистрируем интервал/
	add_filter( 'cron_schedules', 'cron_add_num_interval' );
	function cron_add_num_interval( $schedules ) {
		
		// Сканирование обнаружения Новых Пользователей.
		global $NUM_Scan_NewUsers;
		$schedules['num_scan_newusers_interval'] = array(
			'interval' => 3600 * $NUM_Scan_NewUsers,
			'display'  => "Every $NUM_Scan_NewUsers H"
		);		
		
		return $schedules;
	}

	// Шедуллер. Инициализация.
	register_activation_hook( __FILE__, 'num_activation' );	
	function num_activation() {
		// удалим на всякий случай все такие же задачи cron, чтобы добавить новые с "чистого листа"
		wp_clear_scheduled_hook('num_scan_newusers');

		// Сканирование обнаружения Новых Пользователей.
		wp_schedule_event(time(), 'num_scan_newusers_interval', 'num_scan_newusers');
	}	
	
	// Шедуллер. Сканирование обнаружения Новых Пользователей.
	add_action('num_scan_newusers', 'do_num_scan_newusers');
	if(!function_exists('do_num_scan_newusers')){
		function do_num_scan_newusers() {
			$debug_process = 'do_num_scan_newusers';
			
			global $wpdb, $NUM_FirstRun;
			global $NUM_Dashboard_NewUsers, $NUM_Scan_NewUsers;

			$num_users_table 	= $wpdb->prefix .'users';
			$num_usermeta_table = $wpdb->prefix .'usermeta';

			$Query = $wpdb -> prepare( "SELECT * FROM %s WHERE %d", $num_users_table, 1 );
			NUM_Debugger ($Query, '$Query', $debug_process, __FUNCTION__, __LINE__);
			
			$Users = $wpdb -> get_results ($Query);  // phpcs:ignore 
			
			$meta_key = 'num_confirm';			
			
			if ($NUM_FirstRun == 1) {
				// Рабочий процесс
				$Site_title = get_bloginfo('name');
				$subject = "$Site_title | __('New Users Monitor. Attention! A new User has been detected.', 'new-users-monitor')";				

				$admin_email = get_option('admin_email');
				$from = $admin_email;
				
				$headers[] = "From: New Users Monitor <$from>";
				$headers[] = "Content-Type: text/html";
				$headers[] = "charset=UTF-8";
				
				$to = $admin_email;				
				
				foreach ($Users as $User) {
					$User_ID 	= $User->ID;
					$User_Login = $User->user_login;
					
					$NUM_Confirm = get_user_meta ($User_ID, $meta_key, true);
					if ($NUM_Confirm == '' || $NUM_Confirm == ' ') {
						// Новый неподтвержденный Пользователь. Необходимо уведомить Админа.
						$message = "Attention!\r\n";
						$message .= "In the DB ('users' table), a new record was found. \r\n";
						$message .= "ID: $User_ID\r\n";
						$message .= "Login: $User_Login\r\n";				
						
						// формируем HTML контент вместо Text, т.к почему-то нарушается форматирование текста. Переводы строки не работают ((
						$message = wpautop($message);
						wp_mail($to, $subject, $message, $headers);					
						
						
						// Делаем метку, что этот Пользователь уже обнаружен. 
						$meta_value = '0';
						update_user_meta( $User_ID, $meta_key, $meta_value );
						
						
						// Делаем отметку в Журнале.
						// ...
					}		
				}
			} else {
				// Первый запуск.
				$NUM_FirstRun = 1;			
				
				foreach ($Users as $User) {
					$User_ID 	= $User->ID;
					$User_Login = $User->user_login;
					
					// Делаем метку, что этот Пользователь уже обнаружен и Подтвержден. 
					$meta_value = '1';
					update_user_meta ($User_ID, $meta_key, $meta_value);
				}			

				update_option('num_dashboard_newusers', $NUM_Dashboard_NewUsers);
				update_option('num_scan_newusers', $NUM_Scan_NewUsers);				
				update_option('num_first_run', '1');
			}
		}		
	}	