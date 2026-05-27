<?php
/**
 * Prevent direct access.
 *
 * @package LoginPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LoginPress Limit Login Attempts Ajax Trait.
 *
 * Handles Some Ajax callbacks from class-loginpress-limit-login-attempts.php file.
 *
 * @package   LoginPress
 * @subpackage Traits\LimitLogin
 * @since 6.1.0
 */

if ( ! trait_exists( 'LoginPress_LimitLogin_Ajax_Trait' ) ) {
	/**
	 * LoginPress Limit Login Attempts Ajax Trait.
	 *
	 * Handles Some Ajax callbacks from class-loginpress-limit-login-attempts.php file.
	 *
	 * @package   LoginPress
	 * @subpackage Traits\LimitLogin
	 * @since 6.1.0
	 */
	trait LoginPress_LimitLogin_Ajax_Trait {

		/**
		 * Load limit login tabs.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public function loginpress_load_limit_login_tabs() {
			check_ajax_referer( 'loginpress-user-llla-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No cheating, huh!' );
			}
			$html = '<div id="loginpress_limit_logs">
			<div class="loginpress_llla_loader_inner"><img src="' . esc_url( LOGINPRESS_DIR_URL . 'img/loginpress-sniper.gif' ) . '" /></div>
			<div class="loginpress_limit_login_log_def">
				<div class="loginpress_limit_login_log_definition"><span class="loginpress-attempts-unlock"></span><p>' . __( 'Unlock/Deletes certain IP address from the database.', 'loginpress-pro' ) . '</p></div>
				<div class="loginpress_limit_login_log_definition"><span class="loginpress-attempts-whitelist"></span><p>' . __( 'Move certain IP address to whitelist so Login Attempts are not applied on them.', 'loginpress-pro' ) . '</p></div>
				<div class="loginpress_limit_login_log_definition"><span class="loginpress-attempts-blacklist"></span><p>' . __( 'Move certain IP address to blacklist so a certain IP address couldn\'t access your login page.', 'loginpress-pro' ) . '</p></div>
			</div>
			<div class="bulk_option_wrapper">
				<div class="bulk_option">
					<select id="loginpress_limit_bulk_blacklist">
						<option value="">' . __( 'Bulk Action', 'loginpress-pro' ) . '</option>
						<option value="unlock">' . __( 'Unlock', 'loginpress-pro' ) . '</option>
						<option value="white_list">' . __( 'White List', 'loginpress-pro' ) . '</option>
						<option value="black_list">' . __( 'Black List', 'loginpress-pro' ) . '</option>
					</select>
					<button id="loginpress_limit_bulk_blacklist_submit" type="button" class="button">' . esc_html__( 'submit', 'loginpress-pro' ) . '</button>
				</div>
				<div class="bulk_clear">
					<button id="loginpress_limit_bulk_attempts_submit" type="button" class="button">' . esc_html__( 'Clear All', 'loginpress-pro' ) . '</button>
				</div>
			</div>
			<div class="loginpress-edit-attempts-popup-containers llla_remove_all_popup">
				<div class="loginpress-edit-overlay"></div>
				<div class="loginpress-edit-popup loginpress-link-duration-popup loginpress-llla-popup">
					<div class="llla_popup_heading"><img src="' . LOGINPRESS_LIMIT_LOGIN_DIR_URL . 'assets/img/llla_confirm.svg"><h3>' . esc_html__( 'Are you sure to delete all the entries?', 'loginpress-pro' ) . '</h3></div>
					<div class="loginpress-llla-duration-buttons">
						<button type="button" class="button button-primary loginpress_confirm_remove_all_attempts">' . esc_html__( 'Yes', 'loginpress-pro' ) . '</button>
						<button type="button" class="button button-primary limit-login-attempts-close-popup">' . esc_html__( 'No', 'loginpress-pro' ) . '</button>
					</div>
				</div>
			</div>
			<div class="row-per-page">
				<span>' . __( 'Show Entries', 'loginpress-pro' ) . '</span> 
				<select id="loginpress_limit_login_log_select" class="selectbox" style="width: 100px; height: 50px; margin: 5px;">
					<option value="10">10</option>
					<option value="25">25</option>
					<option value="50">50</option>
				</select>
			</div>
		</div>';

			// **Whitelist Tab Content**
			$html .= '<div id="loginpress_limit_login_whitelist_wrapper2">
		<div class="loginpress-edit-white-popup-containers llla_remove_all_popup">
		<div class="loginpress-edit-overlay"></div><div class="loginpress-edit-popup loginpress-link-duration-popup loginpress-llla-popup">
			<div class="llla_popup_heading"><img src="' . LOGINPRESS_LIMIT_LOGIN_DIR_URL . 'assets/img/llla_confirm.svg"><h3>' . esc_html__( 'Are you sure to delete all the entries?', 'loginpress-pro' ) . '</h3></div>
			<div class="loginpress-llla-duration-buttons">
				<button type="button" class="button button-primary loginpress_confirm_remove_all_whitelist">' . esc_html__( 'Yes', 'loginpress-pro' ) . '</button>
				<button type="button" class="button button-primary limit-login-attempts-close-popup">' . esc_html__( 'No', 'loginpress-pro' ) . '</button>
			</div>
		</div>
		</div>
		<div class="row-per-page"><span>' . __( 'Show Entries', 'loginpress-pro' ) . '</span> <select id="loginpress_limit_login_whitelist_select" class="selectbox" style="width: 100px; height: 50px; margin: 5px;"><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select>
		<div class="bulk_option_wrapper">
			<button type="button" id="loginpress_limit_bulk_whitelists_submit" class="button">' . esc_html__( 'Clear All', 'loginpress-pro' ) . '</button>
		</div>
		</div>
		</div>';

			// **Blacklist Tab Content**
			$html .= '<div id="loginpress_limit_login_blacklist_wrapper2">
		<div class="loginpress-edit-black-popup-containers llla_remove_all_popup">
		<div class="loginpress-edit-overlay"></div><div class="loginpress-edit-popup loginpress-link-duration-popup loginpress-llla-popup">
			<div class="llla_popup_heading"><img src="' . LOGINPRESS_LIMIT_LOGIN_DIR_URL . 'assets/img/llla_confirm.svg"><h3>' . esc_html__( 'Are you sure to delete all the entries?', 'loginpress-pro' ) . '</h3></div>
			<div class="loginpress-llla-duration-buttons">
				<button type="button" class="button button-primary loginpress_confirm_remove_all_blacklist">' . esc_html__( 'Yes', 'loginpress-pro' ) . '</button>
				<button type="button" class="button button-primary limit-login-attempts-close-popup">' . esc_html__( 'No', 'loginpress-pro' ) . '</button>
			</div>
		</div>
		</div>
		<div class="row-per-page"><span>' . __( 'Show Entries', 'loginpress-pro' ) . '</span> <select id="loginpress_limit_login_blacklist_select" class="selectbox" style="width: 100px; height: 50px; margin: 5px;"><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select>
		<div class="bulk_option_wrapper">
		<button type="button" id="loginpress_limit_bulk_blacklists_submit" class="button">' . esc_html__( 'Clear All', 'loginpress-pro' ) . '</button>
	</div>
	</div>';

			echo $html; // phpcs:ignore
			wp_die();
		}

		/**
		 * Callback for blacklist tab.
		 *
		 * @since 3.0.0
		 * @version 3.3.0
		 */
		public function loginpress_limit_login_attempts_blacklist_callback() {

			check_ajax_referer( 'loginpress-user-llla-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No cheating, huh!' );
			}

			global $wpdb;

			$myblacklist = $wpdb->get_results( $wpdb->prepare( 'SELECT DISTINCT ip,blacklist FROM %1s WHERE `blacklist` = %d ORDER BY `datentime` DESC LIMIT 50', $this->llla_table, 1 ) );  // @codingStandardsIgnoreLine.
			$html        = '';
			if ( $myblacklist ) {

				foreach ( $myblacklist as $blacklist ) {
					$html .= '<tr>';
					$html .= '<td class="loginpress_limit_login_blacklist_ips" data-blacklist-ip="' . esc_attr( $blacklist->ip ) . '"><div class="lp-tbody-cell">' . esc_html( $blacklist->ip ) . '</div></td>';
					$html .= '<td class="loginpress_limit_login_blacklist_actions"><div class="lp-tbody-cell"><button class="loginpress-blacklist-clear button button-primary" type="button" value="Clear" ></button></div></td>';
					$html .= '</tr>';
				}
			} else {
				$html .= ''; // <h2>Not Found</h2>.
			}
			echo $html;  // @codingStandardsIgnoreLine.
			wp_die();
		}

		/**
		 * Callback for Whitelist tab.
		 *
		 * @since 3.0.0
		 * @version 6.1.0
		 */
		public function loginpress_limit_login_attempts_whitelist_callback() {

			check_ajax_referer( 'loginpress-user-llla-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No cheating, huh!' );
			}

			global $wpdb;
			$my_whitelist = $wpdb->get_results( $wpdb->prepare( 'SELECT DISTINCT ip,whitelist FROM %1s WHERE `whitelist` = %d ORDER BY `datentime` DESC LIMIT 50', $this->llla_table, 1 ) );  // @codingStandardsIgnoreLine.
			$html         = '';
			if ( $my_whitelist ) {

				foreach ( $my_whitelist as $whitelist ) {
					$html .= '<tr>';
					$html .= '<td class="loginpress_limit_login_whitelist_ips" data-whitelist-ip="' . esc_attr( $whitelist->ip ) . '"><div class="lp-tbody-cell">' . esc_html( $whitelist->ip ) . '</div></td>';
					$html .= '<td class="loginpress_limit_login_whitelist_actions"><div class="lp-tbody-cell"><button class="loginpress-whitelist-clear button button-primary" type="button" value="' . esc_attr__( 'Clear', 'loginpress-pro' ) . '" /></button></div></td>';
					$html .= '</tr>';
				}
			} else {
				$html .= ''; // <h2>Not Found</h2>.
			}
			echo $html; // @codingStandardsIgnoreLine.
			wp_die();
		}

		/**
		 * Callback for Attempts log Tab.
		 *
		 * @since 3.0.0
		 * @version 6.1.0
		 */
		public function loginpress_limit_login_attempts_log_callback() {

			check_ajax_referer( 'loginpress-user-llla-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No cheating, huh!' );
			}

			global $wpdb;
			// Get result from $this->llla_table where IP's aren't blaclisted or whitelisted.
			$my_result = $wpdb->get_results( $wpdb->prepare( "SELECT *, (whitelist+blacklist) as list FROM `{$this->llla_table}` HAVING list = %d ORDER BY `datentime` DESC LIMIT 50", 0 ) ); // @codingStandardsIgnoreLine.
			$html      = '';
			if ( ! empty( $my_result ) ) {

				foreach ( $my_result as $result ) {
					$html .= '<tr id="loginpress_attempts_id_' . esc_attr( $result->id ) . '" data-login-attempt-user="' . esc_attr( $result->id ) . '" data-ip="' . esc_attr( $result->ip ) . '">';
					$html .= '<th></th><td class="lg_attempts_ip"><div class="lp-tbody-cell">' . esc_html( $result->ip ) . '</div></td>';
					$html .= '<td class="loginpress_limit_login_log_dates"><div class="lp-tbody-cell">' . esc_html( gmdate( 'm/d/Y H:i:s', (int) $result->datentime ) ) . '</div></td>';
					$html .= '<td class="loginpress_limit_login_log_usernames"><div class="lp-tbody-cell"><span class="attempts-sniper"><img src="' . esc_url( LOGINPRESS_DIR_URL . 'img/loginpress-sniper.gif' ) . '" /></span>' . esc_html( $result->username ) . '</div></td>';
					$html .= '<td class="loginpress_limit_login_log_gateways"><div class="lp-tbody-cell">' . esc_html( $result->gateway ) . '</div></td>';
					$html .= '<td class="loginpress_limit_login_log_actions"><div class="lp-tbody-cell"> <div class="loginpress-attempts-unlock-wrapper"><input class="loginpress-attempts-unlock button button-primary" type="button" value="' . esc_attr__( 'Unlock', 'loginpress-pro' ) . '" /></div> <div class="loginpress-attempts-whitelist-wrapper"><input class="loginpress-attempts-whitelist button" type="button" value="' . esc_attr__( 'Whitelist', 'loginpress-pro' ) . '" /></div> <div class="loginpress-attempts-blacklist-wrapper"><input class="loginpress-attempts-blacklist button" type="button" value="' . esc_attr__( 'Blacklist', 'loginpress-pro' ) . '" /></div></div></td>';
					$html .= '</tr>';

				}
			} else {
				$html .= ''; // <h2>Not Found</h2>.
			}
			echo $html; // @codingStandardsIgnoreLine.
			wp_die();
		}

		/**
		 * Callback for Attempts log Tab.
		 *
		 * @since 3.0.0
		 */
		public function loginpress_limit_login_attempts_log_content() {

			$html  = '<div id="loginpress_limit_logs">
			<div class="loginpress_llla_loader_inner"><img src="' . esc_url( LOGINPRESS_DIR_URL . 'img/loginpress-sniper.gif' ) . '" /></div>
			<div class="loginpress_limit_login_log_def">
				<div class="loginpress_limit_login_log_definition"><span class="loginpress-attempts-unlock"></span><p>' . __( 'Unlock/Deletes certain IP address from the database.', 'loginpress-pro' ) . '</p></div>
				<div class="loginpress_limit_login_log_definition"><span class="loginpress-attempts-whitelist"></span><p>' . __( 'Move certain IP address to whitelist so Login Attempts are not applied on them.', 'loginpress-pro' ) . '</p></div>
				<div class="loginpress_limit_login_log_definition"><span class="loginpress-attempts-blacklist"></span><p>' . __( 'Move certain IP address to blacklist so a certain IP address couldn\'t access your login page.', 'loginpress-pro' ) . '</p></div>
			</div>
				<div class="bulk_option_wrapper">
					<div class="bulk_option">
						<select id="loginpress_limit_bulk_blacklist">
							<option value="">' . __( 'Bulk Action', 'loginpress-pro' ) . '</option>
							<option value="unlock">' . __( 'Unlock', 'loginpress-pro' ) . '</option>
							<option value="white_list">' . __( 'White List', 'loginpress-pro' ) . '</option>
							<option value="black_list">' . __( 'Black List', 'loginpress-pro' ) . '</option>
						</select>
						<button id="loginpress_limit_bulk_blacklist_submit" class="button">' . esc_html__( 'submit', 'loginpress-pro' ) . '</button>
					</div>
					<div class="bulk_clear">
						<button id="loginpress_limit_bulk_attempts_submit" class="button">' . esc_html__( 'Clear All', 'loginpress-pro' ) . '</button>
					</div>
				</div>
				<div class="loginpress-edit-attempts-popup-containers llla_remove_all_popup">
				<div class="loginpress-edit-overlay"></div><div class="loginpress-edit-popup loginpress-link-duration-popup loginpress-llla-popup">
				<div class="llla_popup_heading"><img src="' . LOGINPRESS_LIMIT_LOGIN_DIR_URL . 'assets/img/llla_confirm.svg"><h3>' . esc_html__( 'Are you sure to delete all the entries?', 'loginpress-pro' ) . '</h3></div>
				<div class="loginpress-llla-duration-buttons">
					<button class="button button-primary loginpress_confirm_remove_all_attempts">' . esc_html__( 'Yes', 'loginpress-pro' ) . '</button>
					<button class="button button-primary limit-login-attempts-close-popup">' . esc_html__( 'No', 'loginpress-pro' ) . '</button>
				</div>
			</div>
				</div>
				<div class="row-per-page"><span>' . __( 'Show Entries', 'loginpress-pro' ) . '</span> <select id="loginpress_limit_login_log_select" class="selectbox"><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select></div>
				<table id="loginpress_limit_login_log" class="display nowrap" cellspacing="0" width="100%">
        			<thead>
						<tr>
							<th><input type="checkbox" name="select_all" value="1" class="lla-select-all"></th>
							<th data-priority="1">' . __( 'IP', 'loginpress-pro' ) . '</th>
							<th>' . __( 'Date & Time', 'loginpress-pro' ) . '</th>
							<th>' . __( 'Username', 'loginpress-pro' ) . '</th>
							<th>' . __( 'Gateway', 'loginpress-pro' ) . '</th>
							<th data-priority="2">' . __( 'Action', 'loginpress-pro' ) . '</th>
						</tr>
          			</thead>
          			<tfoot>
						<tr>
							<th><input type="checkbox" name="select_all" value="1" class="lla-select-all"></th>
							<th>' . __( 'IP', 'loginpress-pro' ) . '</th>
							<th>' . __( 'Date & Time', 'loginpress-pro' ) . '</th>
							<th>' . __( 'Username', 'loginpress-pro' ) . '</th>
							<th>' . __( 'Gateway', 'loginpress-pro' ) . '</th>
							<th>' . __( 'Action', 'loginpress-pro' ) . '</th>
						</tr>
					</tfoot>
        			<tbody>';
			$html .= '</tbody>
      			</table>
			</div>';
			echo $html; // @codingStandardsIgnoreLine.
		}

		/**
		 * Callback for blacklist tab.
		 *
		 * @since 3.0.0
		 */
		public function loginpress_limit_login_attempts_blacklist_content() {
			$html  = '<div id="loginpress_limit_login_blacklist_wrapper2">
			<div class="loginpress-edit-black-popup-containers llla_remove_all_popup">
			<div class="loginpress-edit-overlay"></div><div class="loginpress-edit-popup loginpress-link-duration-popup loginpress-llla-popup">
				<div class="llla_popup_heading"><img src="' . LOGINPRESS_LIMIT_LOGIN_DIR_URL . 'assets/img/llla_confirm.svg"><h3>' . esc_html__( 'Are you sure to delete all the entries?', 'loginpress-pro' ) . '</h3></div>
				<div class="loginpress-llla-duration-buttons">
					<button class="button button-primary loginpress_confirm_remove_all_blacklist">' . esc_html__( 'Yes', 'loginpress-pro' ) . '</button>
					<button class="button button-primary limit-login-attempts-close-popup">' . esc_html__( 'No', 'loginpress-pro' ) . '</button>
				</div>
			</div>
			</div>
			<div class="row-per-page"><span>' . __( 'Show Entries', 'loginpress-pro' ) . '</span> <select id="loginpress_limit_login_blacklist_select" class="selectbox"><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select>
			<div class="bulk_option_wrapper">
				<button id="loginpress_limit_bulk_blacklists_submit" class="button">' . esc_html__( 'Clear All', 'loginpress-pro' ) . '</button>
			</div>
			</div>
			<table id="loginpress_limit_login_blacklist" class="display" cellspacing="0" width="100%">
			
				<thead>
					<tr>
						<th>' . __( 'IP', 'loginpress-pro' ) . '</th>
						<th>' . __( 'Action', 'loginpress-pro' ) . '</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th>' . __( 'IP', 'loginpress-pro' ) . '</th>
						<th>' . __( 'Action', 'loginpress-pro' ) . '</th>
					</tr>
				</tfoot>
				<tbody>';
			$html .= '</tbody>
			</table></div>';
			echo $html;  // @codingStandardsIgnoreLine.
		}

		/**
		 * Callback for Whitelist tab.
		 *
		 * @since 3.0.0
		 */
		public function loginpress_limit_login_attempts_whitelist_content() {

			$html      = '<div id="loginpress_limit_login_whitelist_wrapper2">
			<div class="loginpress-edit-white-popup-containers llla_remove_all_popup">
			<div class="loginpress-edit-overlay"></div><div class="loginpress-edit-popup loginpress-link-duration-popup loginpress-llla-popup">
				<div class="llla_popup_heading"><img src="' . LOGINPRESS_LIMIT_LOGIN_DIR_URL . 'assets/img/llla_confirm.svg"><h3>' . esc_html__( 'Are you sure to delete all the entries?', 'loginpress-pro' ) . '</h3></div>
				<div class="loginpress-llla-duration-buttons">
					<button class="button button-primary loginpress_confirm_remove_all_whitelist">' . esc_html__( 'Yes', 'loginpress-pro' ) . '</button>
					<button class="button button-primary limit-login-attempts-close-popup">' . esc_html__( 'No', 'loginpress-pro' ) . '</button>
				</div>
			</div>
			</div>
			<div class="row-per-page"><span>' . __( 'Show Entries', 'loginpress-pro' ) . '</span> <select id="loginpress_limit_login_whitelist_select" class="selectbox"><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select>
			<div class="bulk_option_wrapper">
				<button id="loginpress_limit_bulk_whitelists_submit" class="button">' . esc_html__( 'Clear All', 'loginpress-pro' ) . '</button>
			</div>
			</div><table id="loginpress_limit_login_whitelist" class="display" cellspacing="0" width="100%">
				<thead>
					<tr>
						<th>' . __( 'IP', 'loginpress-pro' ) . '</th>
						<th>' . __( 'Action', 'loginpress-pro' ) . '</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th>' . __( 'IP', 'loginpress-pro' ) . '</th>
						<th>' . __( 'Action', 'loginpress-pro' ) . '</th>
					</tr>
				</tfoot>
				<tbody>';
				$html .= '</tbody>
			</table></div>';
			echo $html; // @codingStandardsIgnoreLine.
		}
	}
}
