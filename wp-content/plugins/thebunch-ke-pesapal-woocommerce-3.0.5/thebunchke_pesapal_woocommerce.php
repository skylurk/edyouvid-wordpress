<?php
    /*
    Plugin Name: TheBunch KE Pesapal Woocommerce
    Description: Add PesaPal payment gateway to your Woocommerce plugin
    Version:3.0.5
    Author: rixeo
    Contributor: PesaPal Devs
    Author URI: http://thebunch.co.ke/
    Plugin URI: https://developer.pesapal.com/official-extensions?download=8:woocommerce
    */
    
    if ( ! defined( 'ABSPATH' ) ) 
    	exit; // Exit if accessed directly
    
    //Define constants
    define( 'THEBUNCHKE_PESAPAL_WOO_PLUGIN_DIR', dirname(__FILE__).'/' );
    define( 'THEBUNCHKE_PESAPAL_WOO_PLUGIN_URL', plugin_dir_url(__FILE__));
    	
    function thebunchke_pesapal_woo_init(){
    	//Load PesaPal OAuth Library
    	// 
    	require_once(THEBUNCHKE_PESAPAL_WOO_PLUGIN_DIR . 'lib/pesapalV30Helper.php');
    
    	add_filter( 'woocommerce_locate_template', 'woo_adon_plugin_template', 1, 3 );
    	function woo_adon_plugin_template( $template, $template_name, $template_path ) { 
    		global $woocommerce;
    		$_template = $template;
    		if ( ! $template_path ) {
    			$template_path = $woocommerce->template_url;
    		}
    		
    		$plugin_path  = dirname(__FILE__);//untrailingslashit( plugin_dir_path( __FILE__ ) )  . '/template/woocommerce/';
    		$plugin_template = $plugin_path.'/templates/'.$template_name;
    		
    		if( file_exists( $plugin_template ) ){
    			$template = $plugin_template;
    		}
    
    		if ( ! $template ){
    			$template = $_template;
    		}
    
    		return $template;
    	}
    	
    	add_filter('woocommerce_payment_gateways', 'add_pesapal_gateway_class' );
    	function add_pesapal_gateway_class( $methods ) {
    		$methods[] = 'WC_TheBunchKE_PesaPal_Pay_Gateway'; 
    		return $methods;
    	}
    	
    	/**
    	 * Add Currencies
    	 *
    	 */
    	add_filter( 'woocommerce_currencies', 'thebunchke_pesapal_woo_add_shilling' );
    	function thebunchke_pesapal_woo_add_shilling( $currencies ) {
    		if( !isset( $currencies['KES'] ) ||!isset( $currencies['KSH'] ) ) {
    			$currencies['KES'] = __( 'Kenyan Shilling', 'woocommerce' );
    			$currencies['TZS'] = __( 'Tanzanian Shilling', 'woocommerce' );
    			$currencies['UGX'] = __( 'Ugandan Shilling', 'woocommerce' );
    			return $currencies;
    		}
    	}
    
    	/**
    	 * Add Currency Symbols
    	 *
    	 */
    	add_filter('woocommerce_currency_symbol', 'thebunchke_pesapal_woo_add_shilling_symbol', 10, 2);
    	function thebunchke_pesapal_woo_add_shilling_symbol( $currency_symbol, $currency ) {
    		switch( $currency ) {
    			case 'KES': 
    				$currency_symbol = 'KShs'; 
    			break;
    			case 'TZS': 
    				$currency_symbol = 'TZs'; 
    			break;
    			case 'UGX': 
    				$currency_symbol = 'UShs'; 
    			break;
    		}
    		return $currency_symbol;
    	}
    	
    	if(class_exists('WC_Payment_Gateway')){
    		if(!class_exists('WC_TheBunchKE_PesaPal_Pay_Gateway')){
    			class WC_TheBunchKE_PesaPal_Pay_Gateway extends WC_Payment_Gateway{
    				function __construct(){
    					add_action('woocommerce_receipt_'.$this->id, array(&$this, 'payment_page'));
    
    					//Settings
    					$this->id = 'pesapal';
    					$this->method_title = 'Pesapal';
    					$this->has_fields = false;
    					$this->testmode = ($this->get_option('testmode') === 'yes') ? true : false;
    					$this->debug = $this->get_option( 'debug' ); 
    					$this->title = $this->get_option('title'); 
    					$this->description = $this->get_option('description');
    					// $this->apiVersion = (int) $this->get_option('apiversion');
    					$this->orderstatus = $this->get_option('orderstatus');
    					$this->paymentsoptionspageloader = $this->get_option('paymentsoptionspageloader');
    					$this->loadjquery = $this->get_option('loadjquery'); 
    					
    					//Set up logging
						global $woocommerce;
    					if ( 'yes' == $this->debug ) {
    						if ( class_exists('WC_Logger') ) {
    							$this->log = new WC_Logger();
    						} else {
    							$this->log = $woocommerce->logger();
    						}
    					}
    
    					if( $this->testmode ) {
    						$this->consumer_key = $this->get_option('testconsumerkey');
    						$this->consumer_secret = $this->get_option('testsecretkey');
    					} else {
    						$this->consumer_key = $this->get_option('consumerkey');
    						$this->consumer_secret = $this->get_option('secretkey');
    					}

						// Load API 3
						$this->notification_id = ( $this->testmode ) ? $this->get_option('testnotification_id') : $this->get_option('notification_id');
						$this->apimode = ( $this->testmode ) ? "demo" : "live"; 
						$this->pesapalV30Helper = new pesapalV30Helper($this->apimode);
    					
    					
    					//IPN URL
    					$this->notify_url = add_query_arg( 'wc-api', 'WC_Pesapal_Gateway', home_url( '/' ) );
                        $this->cron_url = add_query_arg( 'wc-api', 'WC_Pesapal_Cron', home_url( '/' ) );
    					
    					$this->create_pesapal_table();
    					$this->init_form_fields();
    					$this->init_settings();
    					
    					if (is_admin()){
                            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    					}
    					
    					add_action('woocommerce_receipt_'.$this->id, array(&$this, 'payment_page'));
    					add_action('woocommerce_api_wc_'.$this->id.'_gateway', array( $this, 'ipn_response' ) );
                        add_action('woocommerce_api_wc_'.$this->id.'_cron', array( $this, 'pesapalCron' ) );
                        add_action('woocommerce_api_wc_'.$this->id.'_stkstatus', array( $this, 'pesapalSTKStatus' ) );
    					add_action('woocommerce_thankyou_pesapal_'.$this->id, array( $this, 'update_order_status'), 1, 1);
    				}
    				
    				/**
    				 * Get gateway icon.
    				 *
    				 * @return string
    				 */
    				function get_icon() {
    					// We need a base country for the link to work, bail if in the unlikely event no country is set.
    					$base_country = WC()->countries->get_base_country();
    
    					$icon_html = "<img style='max-width:33px; float: left; padding:0; margin: 0 4px 0 0;' src='http://payments.pesapal.com/images/pesapal/visa.svg' alt='Visa' />";
    					$icon_html .= "<img style='max-width:33px; float: left; padding:0; margin: 0 4px 0 0;' src='http://payments.pesapal.com/images/pesapal/mastercard.svg' alt='MasterCard' />";
    					$icon_html .= "<img style='max-width:33px; float: left; padding:0; margin: 0 4px 0 0;' src='http://payments.pesapal.com/images/pesapal/amex.svg' alt='Amex' />";
    
    					if($base_country=="KE"){
    						$icon_html .= "<img style='max-width:33px; float: left; background:#FFF; padding:3px; margin: 0 4px 0 0;' src='http://payments.pesapal.com/images/pesapal/mpesa.png' alt='Mpesa - KE' />";
    						$icon_html .= "<img style='max-width:33px; float: left; background:#FFF; padding:3px; margin: 0 4px 0 0;' src='http://payments.pesapal.com/images/pesapal/airtel.png' alt='Airtel Money - KE' />";
    						$icon_html .= "<img style='max-width:33px; float: left; background:#FFF; padding:3px; margin: 0 4px 0 0;' src='http://payments.pesapal.com/images/pesapal/mvisal.png' alt='Mvisa - KE' />";
    					}else if($base_country=="UG"){
    						$icon_html .= "<img style='max-width:33px; float: left; background:#FFF; padding:0; margin: 0 4px 0 0;' src='http://payments.pesapal.com/images/pesapal/mtn.jpg' alt='MTN Mobile Money - UG' />";
    						$icon_html .= "<img style='max-width:33px; float: left; background:#FFF; padding:3px; margin: 0 4px 0 0;' src='http://payments.pesapal.com/images/pesapal/airtel.png' alt='Airtel Money - UG' />";
    						$icon_html .= "<img style='max-width:33px; float: left; background:#FFF; padding:5px 3px; margin: 0 4px 0 0;' src='http://payments.pesapal.com/images/pesapal/EZEEMONEY_s.png' alt='Eazzy' />";
    					} else if($base_country=="TZ"){
    						$icon_html .= "<img style='max-width:33px; float: left; background:#FFF; padding:0; margin: 0 4px 0 0;' src='http://payments.pesapal.com/images/pesapal/mpesa_tz.jpg' alt='Mpesa - TZ' />";
    						$icon_html .= "<img style='max-width:33px; float: left; background:#FFF; padding:5px 3px; margin: 0 4px 0 0;' src='http://payments.pesapal.com/images/pesapal/TigoPesa_s.png' alt='TigoPesa - TZ' />";
    					}
    					
    					$icon_html .= "<img style='max-width:33px; float: left; background:#FFF; padding:3px; margin: 0;' src='http://payments.pesapal.com/images/pesapal/ewallet.png' alt='Pesapal E-wallet' />"; 
    
    					return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
    				}
    				
    				function create_pesapal_table() {
    					global $wpdb;
    
    					$installSQL = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}woocommerce_pesapal_order_tracking_data` (
    						`id` int(11) NOT NULL AUTO_INCREMENT,
    						`order_id` int(11) NOT NULL,
    						`pesapal_tracking_id` varchar(100) NOT NULL,
    						`order_tracking_id` VARCHAR(100) NOT NULL,
    						`redirect_url` VARCHAR(255) NOT NULL,
    						`date_created` timestamp NOT NULL,
    						PRIMARY KEY (`id`),
    						UNIQUE KEY `tracking_url` (`pesapal_tracking_id`,`order_tracking_id`,`redirect_url`)
    					)"; $wpdb->query($installSQL);
    
    					$installSQL = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}woocommerce_pesapal_mobile_payments` (
    						`id` int(11) NOT NULL AUTO_INCREMENT,
    						`created_at` datetime DEFAULT NULL,
    						`order_id` int(11) NOT NULL,
    						`phone` varchar(20) DEFAULT NULL,
    						`reference` varchar(30) DEFAULT NULL,
    						`transaction_id` int(11) NOT NULL,
    						`request_code` varchar(20) DEFAULT NULL,
    						`payment_status` int(11) NOT NULL,
    						`status` varchar(10) NOT NULL,
    						`confirmation_code` varchar(20) DEFAULT NULL,
    						`modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    						`call_back_received` int(11) NOT NULL,
    						`is_active` int(11) NOT NULL,
    						PRIMARY KEY (`id`) 
    					)"; $wpdb->query($installSQL);
    				}
    
    				function insert_order_tracking_data($orderId, $pesapalTrackingId = null, $orderTrackingId = null, $redirectURL = null) {
    					global $wpdb;
    					$insertSQL = "INSERT IGNORE INTO `{$wpdb->prefix}woocommerce_pesapal_order_tracking_data` (`order_id`,`pesapal_tracking_id`,`order_tracking_id`,`redirect_url`) ";
    					$insertSQL .= "values ('".$orderId."','".$pesapalTrackingId."','".$orderTrackingId."','".$redirectURL."')";
    					$wpdb->query($insertSQL);
    				}
    
    				function createMpesaSTKRequest($object,$order_id,$phone){
    					global $wpdb;
    					$insertSQL = "INSERT IGNORE INTO `{$wpdb->prefix}woocommerce_pesapal_mobile_payments` (`order_id`, `phone`, `reference`, `transaction_id`, `request_code`, `payment_status`, `status`, `confirmation_code`, `created_at`, `call_back_received`, `is_active`) ";
    					$insertSQL .= "values ('".$order_id."','".$phone."','".$object->merchant_reference."','".$object->transaction_id."','".$object->request_code."','".$object->payment_status."','".$object->status."','".$object->confirmation_code."','".date("Y-m-d h:i:s")."',0,0)";
    					$wpdb->query($insertSQL);
    				 }
    
    				function get_order_tracking_data($orderId,$orderTrackingId = null) {
    					global $wpdb; 
    					$order_tracking_data = null;
    					if ($orderId || $orderTrackingId) {
    						$sql = "SELECT order_id,pesapal_tracking_id,order_tracking_id,redirect_url FROM {$wpdb->prefix}woocommerce_pesapal_order_tracking_data WHERE ";
    						$sql .= ($orderTrackingId) ? "order_tracking_id = '".$orderTrackingId."'" : "order_id = '".$orderId."'";
    						$order_tracking_data = $wpdb->get_row($sql);
    					} 
    
    					return $order_tracking_data;
    				}
    				
    				function update_order_status($order_id) {
    					$status = "";
    					$statusResponseJson = "";
                        $order = wc_get_order($order_id); 
    					$paymethod = $order->payment_method;
    					$orderTrackingId = $_REQUEST['OrderTrackingId'];
    					if($orderTrackingId){
    						$tokenResponse = $this->pesapalV30Helper->getAccessToken($this->consumer_key,$this->consumer_secret);
							

							$access_token = $tokenResponse->token;
    						$response = $this->pesapalV30Helper->getTransactionStatus($orderTrackingId,$access_token);
    						$statusResponseJson = json_encode($response);
    						if(isset($response->payment_status_description) && $response->payment_status_description){
    							$status = strtoupper($response->payment_status_description);
    						}
    					}
    
                        if(count($_REQUEST)) $statusResponseJson .= " | ".json_encode($_REQUEST);
    					if($status=="COMPLETED"){
    						$order->update_status($this->orderstatus, '<strong>Order Update: Completed</strong>.<br><br>You can now deliver the goods or services<br><br>'.$statusResponseJson.'<br><br>');
    						$order->payment_complete();
    					}else if($status=="FAILED"){
    						$order->update_status( 'wc-failed', '<strong>Order Update:  Failed</strong><br><br>'.$statusResponseJson.'<br><br>' );
    					}else if($status=="REVERSED" ){
    						$order->update_status( 'wc-refunded', '<strong>Order Update:  Reversed</strong><br><br>'.$statusResponseJson.'<br><br>' );
    					}
                    }
    				
    				function init_form_fields() {
                        $ppPrefix = substr(str_shuffle(str_repeat("ABCDEFGHJKMNPQRSTUVWXYZ", 4)), 0, 4);
    					$this->form_fields = array(
    						'enabled' => array(
    							'title' => __( 'Enable / Disable', 'woothemes' ), 
    							'type' => 'checkbox',
    							'label' => __( 'Enable Pesapal Payment', 'woothemes' ),
    							'default' => 'no'
    						),
    						'title' => array(
    							'title' => __( 'Title', 'woothemes' ),
    							'type' => 'text',
    							'description' => __( 'This controls the title which the user sees during checkout.', 'woothemes' ),
    							'default' => __( 'PesaPal (Mobile Money & Card payments)', 'woothemes' )
    						),
    						'ppprefix' => array(
    							'title' => __( 'Order Prefix', 'woothemes' ),
    							'type' => 'text',
    							'description' => __( 'This is the prefix appended to all order to ensure you do not have duplicate pesapal merchant references generated by other systems connected to your Pesapal account.', 'woothemes' ),
    							'default' => __('', 'woothemes' )
    						),
    						'description' => array(
    							'title' => __( 'Description', 'woocommerce' ),
    							'type' => 'textarea',
    							'description' => __( 'This is the description which the user sees during checkout.', 'woocommerce' ), 
    							'default' => __("Pay using PesaPal Gateway, you can pay by either credit/debit card or use mobile money payment option such as Mpesa, AirtelMoney, MTN Money...", 'woocommerce')
    						),
							'customessage' => array(
								'title' => __( 'Custom Message', 'woocommerce' ),
								'type' => 'textarea',
								'description' => __( 'Message to be displayed to the user on checkout', 'woocommerce' ),
							),
    						'testmode' => array(
    							'title' => __( 'Use Demo Gateway', 'woothemes' ),
    							'type' => 'checkbox',
    							'label' => __( 'Use Demo Gateway', 'woothemes' ),
    							'description' => __( 'Click <a href="https://developer.pesapal.com/api3-demo-keys.txt">here</a> for pesapal test credentials.', 'woothemes' ),
    							'default' => 'no'
    						),
    						'orderstatus' => array(
    							'title'    => esc_html__( 'Update Paid Orders To', 'woothemes' ),
    							'type'     => 'select',
    							'desc_tip' => esc_html__( 'PROCESSING - Payment received (paid) and stock has been reduced; order is awaiting fulfillment. | COMPLETED - Order fulfilled and complete – requires no further action.', 'woothemes' ),
    							'default'  => 'wp-processing',
    							'options'  => array(
    								'wc-processing' => esc_html_x( 'Processing',  'Payment received (paid) and stock has been reduced; order is awaiting fulfillment.', 'woothemes' ),
    								'wc-completed' => esc_html_x( 'Completed', 'Order fulfilled and complete – requires no further action.', 'woothemes' ),
    							)
							),  
    						'paymentsoptionspageloader' => array(
    							'title'    => esc_html__( 'Payments Page Loader', 'woothemes' ),
    							'type'     => 'select',
    							'desc_tip' => esc_html__( 'Select style you wish to load your payments page using', 'woothemes' ),
    							'default'  => 1,
    							'options'  => array(
    								1 => esc_html_x( 'Iframe',  'Iframe', 'woothemes' ),
    								2 => esc_html_x( 'Pop Up Box', 'Pop Up Box', 'woothemes' ),
    								3 => esc_html_x( 'Redirect', 'Redirect - New Tab', 'woothemes' )
    							)
    						),
    						'loadjquery' => array(
    							'title'    => esc_html__( 'Use Jquery Loader', 'woothemes' ),
    							'type'     => 'select',
    							'desc_tip' => esc_html__( 'Use Jquery Loader', 'woothemes' ),
    							'default'  => 1,
    							'options'  => array(
    								0 => esc_html_x( 'No',  'No', 'woothemes' ),
    								1 => esc_html_x( 'Yes', 'Yes', 'woothemes' )
    							)
    						),
    						'consumerkey' => array(
    							'title' => __( 'Consumer Key', 'woothemes' ),
    							'type' => 'text',
    							'description' => __( 'Your Pesapal consumer key which should have been emailed to you.', 'woothemes' ),
    							'default' => ''
    						),
    						'secretkey' => array(
    							'title' => __( 'Consumer Secret', 'woothemes' ),
    							'type' => 'text',
    							'description' => __( 'Your Pesapal consumer secret which should have been emailed to you.', 'woothemes' ),
    							'default' => ''
    						),
    						'notification_id' => array(
    							'title' => __( 'IPN Notification Id', 'woothemes' ),
    							'type' => 'text',
								'desc_tip' => esc_html__( 'The ID of the notification URL to be triggered on status change.', 'woothemes' ),
    							'description' => __( '<a href="https://pay.pesapal.com/iframe/PesapalIframe3/IpnRegistration" target="_blank">Register Here</a> generate your IPN Id. Find your IPN URL below.', 'woothemes' ),
    							'default' => ''
    						),
    						'testconsumerkey' => array(
    							'title' => __( 'Demo Consumer Key', 'woothemes' ),
    							'type' => 'text',
    							'description' => __( 'Your demo Pesapal consumer key which can be seen at demo.pesapal.com.', 'woothemes' ),
    							'default' => ''
    						),
    						'testsecretkey' => array(
    							'title' => __( 'Demo Consumer Secret', 'woothemes' ),
    							'type' => 'text',
    							'description' => __( 'Your demo Pesapal consumer secret which can be seen at demo.pesapal.com.', 'woothemes' ),
    							'default' => ''
    						),
    						'testnotification_id' => array(
    							'title' => __( 'Demo IPN Notification Id', 'woothemes' ),
    							'type' => 'text',
    							'desc_tip' => esc_html__( 'The demo ID of the notification URL to be triggered on status change.', 'woothemes' ),
    							'description' => __( 'Please use 00000000-0000-0000-0000-000000000000 for your test Notification Id.', 'woothemes' ),
    							'default' => '00000000-0000-0000-0000-000000000000'
    						),
    						'surcharge' => array (
    							'title' => __('Surcharge', 'woothemes'),
    							'type' => 'checkbox',
    							'label' => __('Enable Surcharge ( % )'),
    							'description' => __('Enable a surchage fee on all client Transactions', 'woothemes'),
    							'default' => 'no'
    						),
    						'surcharge_rate' => array(
    							'title' => __('Surcharge Rate', 'woothemes'),
    							'type' => 'decimal',
    							'label' => __('Enter the Surchage rate in Percentage','woothemes'),
    							'description' => __('Enter the Surchage Rate in Percentage ( % ) , Eg: 3.5','woothemes'),
    							'default' => '0.0'
    						),
    						'debug' => array(
    							'title' => __( 'Debug Log', 'woocommerce' ),
    							'type' => 'checkbox',
    							'label' => __( 'Enable logging', 'woocommerce' ),
    							'default' => 'no',
    							'description' => sprintf( __( 'Log PesaPal events, such as IPN requests, inside <code>woocommerce/logs/pesapal-%s.txt</code>', 'woocommerce' ), sanitize_file_name( wp_hash( 'pesapal' ) ) ),
    						),
    						'ipnemails' => array(
    							'title' => __( 'Send IPN Email logs', 'woothemes' ),
    							'type' => 'checkbox',
    							'label' => __( 'Send IPN Email logs', 'woothemes' ),
    							'description' => __( 'Test whether IPN triggered by pesapal hits your server. If IPN is called, email will be set to the email list you will share below', 'woothemes' ),
    							'default' => 'no'
    						),
    						'ipnemaillist' => array(
    							'title' => __( 'Emails to receive IPN alerts (comma seperated)', 'woothemes' ),
    							'type' => 'text',
    							'description' => __( 'List emails you wish to receive emails each time IPN hits your server.', 'woothemes' ),
    							'default' => ''
    						)
    					);
    				}
    				
    				function admin_options() { ?>
    					<h3><?php _e('Pesapal', 'woothemes'); ?></h3>
    					<table class="form-table"><?php $this->generate_settings_html(); ?></table>
    					<h3>Webhook Endpoints</h3>
    					<p>
    						<?php _e('Use the following URL as your IPN URL '); ?> 
    						<strong><?php echo str_replace("https://","http://",$this->notify_url); ?></strong><?php _e('.'); ?>
							<?php _e('<strong><a href="https://pay.pesapal.com/iframe/PesapalIframe3/IpnRegistration" target="_blank">Register here</a></strong> to obtain your <strong>IPN Notification ID</strong>.')?>
    						<?php _e('This will enable you receive notifications upon status change.'); ?>
    					</p>
    					<p>Should you experience IPN related issues, please click <a target="_blank" href="https://developer.pesapal.com/forum/6-announcements/2327-troubleshooting-ipn--status-update-failures">here</a> to read on causes of IPN failures and how to resolve them. If none of the solutions listed work, you can set-up a cron job on your server that will be a back-up process to ensure transactions are marked as COMPLETED/FAILED. Set the cron to run every 5 mins. However, it's advicable to ensure IPN is up and running. Use the command below for your cron job (copy paste the entire string as the cron job command)<br>
    					<strong>wget --quiet --delete-after "<?php echo $this->cron_url; ?>"</strong></p>
    					<p>For more details on how to set-up a cron jobs, have a look at the following video <a target="_blank" href="https://www.youtube.com/watch?v=YwpUjz1tMbA">https://www.youtube.com/watch?v=YwpUjz1tMbA</a></p>
    					<p><strong>NB:</strong> Use CRON only when IPN has fails hitting your server.</p>
    					<script type="text/javascript">
    						jQuery(function(){
    							var testMode = jQuery("#woocommerce_pesapal_testmode");
    							var live_consumer = jQuery("#woocommerce_pesapal_consumerkey");
    							var live_secret = jQuery("#woocommerce_pesapal_secretkey");
    							var live_notification_id = jQuery("#woocommerce_pesapal_notification_id");
    							var test_consumer = jQuery("#woocommerce_pesapal_testconsumerkey");
    							var test_secret = jQuery("#woocommerce_pesapal_testsecretkey");
    							var test_notification_id = jQuery("#woocommerce_pesapal_testnotification_id");
    							var loaderType = jQuery("#woocommerce_pesapal_paymentsoptionspageloader").val();
    							var loadjquery = jQuery("#woocommerce_pesapal_loadjquery");
    							var ipnemails = jQuery("#woocommerce_pesapal_ipnemails");
    							var ipnemaillist = jQuery("#woocommerce_pesapal_ipnemaillist");
    							var surcharge = jQuery("#woocommerce_pesapal_surcharge");
    							var surcharge_rate = jQuery("#woocommerce_pesapal_surcharge_rate");
    							// var api_version_select = jQuery("#woocommerce_pesapal_apiversion");
    							// var api_version = api_version_select.val();
    
    							if(testMode.is(":not(:checked)")){
    								test_consumer.parents("tr").hide();
    								test_secret.parents("tr").hide();
    								test_notification_id.parents("tr").hide();
    								
    								live_consumer.parents("tr").show();
    								live_secret.parents("tr").show();
    								live_notification_id.parents("tr").show();

									
    							}else {
    								live_notification_id.parents("tr").hide();
									live_consumer.parents("tr").hide();
    								live_secret.parents("tr").hide();
    								
									test_notification_id.parents("tr").show();
									test_consumer.parents("tr").show();
									test_secret.parents("tr").show();
    							}

    
    							if(loaderType=="1" || loaderType=="2"){
    								loadjquery.parents("tr").show();
    							} else {
    								loadjquery.parents("tr").hide();
    							} 
    
    							if (ipnemails.is(":not(:checked)")){
    								ipnemaillist.parents("tr").hide();
    								ipnemaillist.parents("tr").hide();
    							}
    
    							testMode.click(function(){            
    								// If checked
    								if (testMode.is(":checked")) {
    									test_consumer.parents("tr").show("fast");
    									test_secret.parents("tr").show("fast");
    									test_notification_id.parents("tr").show();
    									
    									live_consumer.parents("tr").hide("fast");
    									live_secret.parents("tr").hide("fast");
    									live_notification_id.parents("tr").hide("fast");
    								} else {
    									test_consumer.parents("tr").hide("fast");
    									test_secret.parents("tr").hide("fast");
    									test_notification_id.parents("tr").hide("fast");
    
    									live_consumer.parents("tr").show("fast");
    									live_secret.parents("tr").show("fast");
    									live_notification_id.parents("tr").show();
    								} 
    							});
    
    							ipnemails.click(function(){
    								// If checked
    								if (ipnemails.is(":checked")) {
    									//show the hidden div
    									ipnemaillist.parents("tr").show("fast");
    									ipnemaillist.parents("tr").show("fast");
    								} else {
    									//otherwise, hide it
    									ipnemaillist.parents("tr").hide("fast");
    									ipnemaillist.parents("tr").hide("fast");
    								}
    							});
    							
    							//hide or unhide the surcharge rate option
    							if (surcharge.is(":not(:checked)")){
    								surcharge_rate.parents("tr").hide();
    								document.getElementById("woocommerce_pesapal_surcharge_rate").setAttribute('value','0.0');
    
    							}
    							surcharge.click(function(){
    								//if surcharge is checked
    								if (surcharge.is(":checked")){
    									surcharge_rate.parents("tr").show("fast");
    								}else {
    									surcharge_rate.parents("tr").hide("fast");
    									document.getElementById("woocommerce_pesapal_surcharge_rate").setAttribute('value','0.0');
    								}
    							});
    						});
    					</script>
    					<?php
    				}
    				
    				function process_payment( $order_id ) {
    					global $woocommerce;
    				
    					$order = wc_get_order( $order_id );
    					if($order->get_status() === 'completed'){
    						//Redirect to payment page
    						return array(
    							'result'    => 'success',
    							'redirect'  => $this->get_return_url( $order )
    						);
    					}else{
    						return array(
    							'result'    => 'success',
    							'redirect'  => $order->get_checkout_payment_url(true)
    						);
    					} 
    				}
    				
    				//Create Payment Page
					function payment_page($order_id){
						$order = wc_get_order( $order_id );
						$url = $this->create_url($order_id); 
						echo '<br>';
						echo '<p><strong>';
							echo $this->get_option('customessage');
						echo '</strong></p>';
					
						if($this->paymentsoptionspageloader==3){ 
							$linkID = $this->paymentsoptionspageloader*time(); ?>
							<div class="pesapal_container" style="position:relative;">
								<p><img class="pesapal_loading_preloader" src="<?php echo THEBUNCHKE_PESAPAL_WOO_PLUGIN_URL; ?>/assets/img/loader.gif" alt="loading" /></p><br />
								<p>Loading payment options... </p><br />
								<p>Please click <a id="click-<?php echo $linkID; ?>" href="<?php echo $url; ?>" target="_new">here</a> should you have trouble loading the payments options.</p><br />
							</div>
							<script type="text/javascript">
								jQuery(document).ready(function () {
									var newTab = window.open('<?php echo $url; ?>', '_new');
									newTab.location;
								});
							</script> <?php
						}else if($this->paymentsoptionspageloader==2){ ?>
							<button data-target="PesaPalpaymentOptions" data-toggle="modal">Make Payment...!</button>
							<link href="https://www.cssscript.com/demo/simplest-modal-component-pure-javascript/modal.css" rel="stylesheet">
							<script src="https://www.cssscript.com/demo/simplest-modal-component-pure-javascript/modal.js"></script>
							<div id="PesaPalpaymentOptions" class="modal">
								<div class="modal-window small">
									<span class="close" data-dismiss="modal">×</span>
									<?php
										$ch = curl_init();
										curl_setopt($ch, CURLOPT_HEADER, 1);
										curl_setopt($ch, CURLOPT_VERBOSE, 0);
										curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
										curl_setopt($ch, CURLOPT_URL, urlencode($url));
										curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
										curl_setopt($ch, CURLOPT_HTTPGET, 1);
										curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false );
										curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 2 );

										$response = curl_exec($ch);
										if(curl_exec($ch) === false){
											echo 'Curl error: ' . curl_error($ch);
										}else{
											echo $response;
										}
										curl_close($ch);
									?>
								</div>
							</div>
						<?php }else{  ?>
							<div id="pesapal-iframe-holder">
								<div id="pesapal-iframe-container">
									<?php if($this->loadjquery){ ?>
										<div id="pesapal-iframe-loader-msg" class="text-center">
											<div class="block"><img src="https://payments.pesapal.com/images/loader_1.gif" id="loader-img"></div>
											<div id="loader-text">Loading Payment Options...</div>
										</div>
									<?php } ?>

									<iframe id="pesapal-iframe" class="pesapal_loading_frame" src="<?php echo $url; ?>" width="100%" height="900px" scrolling="yes" frameBorder="0">
										<p><?php _e('Browser unable to load iFrame', 'woothemes'); ?></p>
									</iframe>
									
								</div>
								<?php if($this->loadjquery){ ?>
									<div class="pp-preloader" style="display: none; "></div>
								<?php } ?>
							</div> 
							
							<?php if($this->loadjquery){ ?>
								<style type="text/css">
									#pesapal-iframe{ opacity: 0; }
									#pesapal-iframe-container{position:relative;}
									#pesapal-iframe-loader-msg{position:absolute;width:100%;top: 10%; text-align: center}
									#pesapal-iframe-container h5, #pesapal-iframe-container .btn-link{ width: 100%; text-align: left; border: none!important; color: #333!important; text-decoration: none!important; background: none; font-size: 22px; line-height: 22px; outline: none; }
									#pesapal-iframe-container .card{ margin-bottom: 5px; }
									#pesapal-iframe-container .btn.mpesabtn, #pesapal-iframe-container .btn.btn-complete{ background: #333; font-size: 18px; color: #FFF!important; border: none!important;  }
									#loader-text{ margin: 10px; }
									.block{ display: block; }
									.item-fade-in{ vertical-align: top; transition: opacity 3s; -webkit-transition: opacity 3s; opacity: 1!important;}
									.item-fade-out{ vertical-align: top; transition: opacity 1s; -webkit-transition: opacity 1s; opacity: 0!important; z-index:-10; transition: 1s; }
									.woocommerce{ width: 100%; max-width: 1080px; }
									.mobilenote{ background: #FBFBFB; padding: 10px; }
									.accordion dt { margin-top: 5px; }
									.accordion dt a { line-height: 40px; width: 100%; display: block; padding: 5px 15px; border: 1px solid #DEDEDE; color: #333; margin-bottom: -1px; background: #EFEFEF; }
									.accordion dd { margin: 0 0 10px 0;  padding: 15px; border: 1px solid #DEDEDE; border-top: 0;  font-size: 12px; }
									.mobileinstr .input-group-prepend { display: inline-block; float: left; padding: 4px 10px 3px 10px; background: #DEDEDE; width: 40px; }
									.mobileinstr #stkphone{ float: left; width: calc(100% - 140px);}
									.mobileinstr .mpesabtn { float: left; width: 100px; }
								</style>
								<script type="text/javascript">
									jQuery(document).ready(function() {
										var iframeLoaderMsg = jQuery("#pesapal-iframe-loader-msg");
										var pesapalIframe = jQuery("#pesapal-iframe");
										//pesapalIframe.load(function () {
											iframeLoaderMsg.addClass("item-fade-out");
											pesapalIframe.addClass("item-fade-in");
										//});

										var reference = '<?php echo $order->pesapalMerchantReference; ?>';
										jQuery(document).on('click', '.mpesabtn', function () {
											jQuery.ajax({
												type: 'POST',
												data: {or:reference,phone:jQuery('#stkphone').val()},
												url: '<?php echo get_site_url(); ?>/?wc-api=WC_Pesapal_Stk',
												beforeSend: function(){
													jQuery(".mpesabtn").addClass("disabled").html('Sending...');
												},
												success: function(msg){
													msg = jQuery.parseJSON(msg);
													if (msg.status==true){
														jQuery('.mobilenote').html('<p><strong>If you did not receive the MPESA PIN Request </strong></p>'+msg.instructions.fallback);
														jQuery('.mobileinstr').html(msg.instructions.message);
													}else {
														jQuery('.mobilenote').html('<p>There was an error . Please Try again <br/><strong>'+ msg.message + '</strong></p>');
														jQuery(".mpesabtn").removeClass("disabled").html('Send');
													}
												},
												complete:function(msg){

												},
												error:function(err){
													jQuery('.mobilenote').html('<p><strong>There was an error . Please try again later</strong></p>');
													jQuery(".mpesabtn").removeClass("disabled").html('Send');
												}

											});
										});


										var allPanels = jQuery('.accordion > dd');
										jQuery('.accordion > dt > a').click(function() {
											allPanels.slideUp();
											jQuery(this).parent().next().slideDown();
											
											return false;
										});
										jQuery('.accordion > dd.cardsdata').hide();
										jQuery('.accordion > dd.mpesadata').slideDown();
									});
								</script> <?php 
							}
						}
					}
					/**
    				 * Create iframe URL
    				 */
    				function create_url($order_id){
    					$order = wc_get_order($order_id);
						$url = "";
						$tokenResponse = $this->pesapalV30Helper->getAccessToken($this->consumer_key,$this->consumer_secret);

						$access_token = $tokenResponse->token;
						if($access_token){
							$order_tracking_data = $this->get_order_tracking_data($order_id);
							$url = (isset($order_tracking_data->redirect_url) && $order_tracking_data->redirect_url) ? $order_tracking_data->redirect_url : "";
							if(!$url){
								$order->amount = $order->get_total();
								$order->callback_url =  $this->get_return_url($order);
								$order->notification_id = $this->notification_id;
								$order->billing_address_1 = str_replace(' ', '', $order->billing_address_1);
								if(!is_numeric($order->billing_address_1)) $order->billing_address_1 = "";
								$order->billing_phone = preg_replace("/[^0-9]/", "", str_replace(' ', '', $order->billing_phone));
								$order->pesapalMerchantReference = ($this->get_option('ppprefix')) ? strtoupper(str_replace(" ","",$this->get_option('ppprefix')))."-".$order->id : $order->id;

								$order->billing_first_name = ucfirst($order->billing_first_name);
								$order->billing_middle_name = ucfirst($order->billing_middle_name);
								$order->billing_last_name = ucfirst($order->billing_last_name);
								$get_bloginfo = (get_bloginfo('name')) ? " at".get_bloginfo('name') : "";
								$order->pesapalDescription = "Order".$get_bloginfo." from ".$order->billing_first_name." ".$order->billing_last_name." | ".$order->billing_email." | ".$order->billing_phone;
								$order->pesapalDescription = trim(urldecode(html_entity_decode(strip_tags($order->pesapalDescription))));
								$order->pesapalDescription = str_replace(array( '(', ')' ), '', htmlentities(substr($order->pesapalDescription,0,99)));
								
								$order->billing_zipcode = "";
								$order->billing_state = "";
								
								$response = $this->pesapalV30Helper->getMerchertOrderURL($order,$access_token);
							}
						}else{ 
							echo '<div class="alert alert-danger" role="alert">';
								echo '<h3>TOKEN: '.str_replace("_"," ",strtoupper($tokenResponse->error->error_type)).'</h3>';
								echo str_replace("_"," ",ucfirst($tokenResponse->error->code));
								if($tokenResponse->error->message) { echo ". ".$tokenResponse->error->message; }
							echo '</div>'; exit; 
						}

						if(!$url && $response->status=="200"){
							$url = $response->redirect_url; 
							$this->insert_order_tracking_data($order_id,null,$response->order_tracking_id,$url);
						}else if(!$url){
							echo '<div class="alert alert-danger" role="alert">';
								echo '<h3>ORDER: '.str_replace("_"," ",strtoupper($response->error->error_type)).'</h3>';
								echo str_replace("_"," ",ucfirst($response->error->code));
								if($response->error->message) { echo ". ".$response->error->message; }
							echo '</div>'; exit; 
						}
    					
    					return $url;
    				}

				
    				function pesapalSTKStatus($orderId = null){
    					global $wpdb;
    					
    					if(isset($_REQUEST['or']) && $_REQUEST['or']){
    						$or = $_REQUEST['or'];
    						$pesapal_refs = explode("-", $or);
    						$orderId = $pesapal_refs[1];
    
    						$requestType = "STKCallback";
    					}else{
    						$requestType = "STKCron";
    					}
    
    					$order = wc_get_order($orderId);
    					if(isset($order->id)){
    						$sql = "SELECT request_code,reference FROM {$wpdb->prefix}woocommerce_pesapal_mobile_payments WHERE order_id = '".$order->id."'";
						    $orders_data = $wpdb->get_results($sql);
						    foreach($orders_data as $order_data){
    						    $request = array();
        						$request["merchant_reference"] = $order_data->reference;
        						$request["request_code"] = $order_data->request_code;
        						
        						$tokenResponse = $this->pesapalV25Helper->getToken();
        						$transaction = $this->pesapalV25Helper->checkTransactionStatus($tokenResponse->token,$request);
        						if(isset($transaction->payment_status_decription) && ($transaction->payment_status_decription=="Completed" || $transaction->payment_status_decription== "Reversed")){
        						    break;
        						}
						    }
						    
    						if($requestType == "STKCron"){
    							return $transaction;
    						}
    
    						// We are here so lets check status and do actions
    						$statusResponseJson = json_encode($transaction);
    						$status = strtoupper($transaction->payment_status_decription);
    						switch ($status) {
    							case 'COMPLETED' :
    								$order->update_status($this->orderstatus, '<strong>Payment Completed</strong>.<br><br>You can now deliver the goods or services.');
    								$order->add_order_note( __( '<strong>'.$requestType.' Request</strong><br>'.$statusResponseJson, 'woocommerce' ) ); 	
    								$order->payment_complete();
    							break;
    							case 'PENDING' :
    								// Check order not already completed
    								if ( $order->get_status() == 'completed' ) {
    									if ( 'yes' == $this->debug ){
    										$this->log->add( 'pesapal', 'Aborting, Order #' . $order->id . ' is already complete.' );
    									}
    								}
    
    								$order->update_status( 'wc-on-hold', sprintf( __( 'Payment pending: %s', 'woocommerce' ), 'Waiting PesaPal confirmation' ) );
    		
    								if ( 'yes' == $this->debug ){
    									$this->log->add( 'pesapal', 'Payment complete.' );
    								}
    							break;
    							case 'FAILED' :
    								// Order failed
    								$order->update_status( 'wc-failed', '<strong>Payment Failed.</strong>' );
    								$order->add_order_note( __( '<strong>'.$requestType.' Request</strong><br>'.$statusResponseJson, 'woocommerce' ) ); 	
    							break;
    							case 'REVERSED' :
    								// Order failed
    								$order->update_status( 'wc-refunded', '<strong>Payment Reversed.</strong>' );
    								$order->add_order_note( __( '<strong>'.$requestType.' Request</strong><br>'.$statusResponseJson, 'woocommerce' ) ); 	
    							break;
    							default :
    								// No action
    							break;
    						}
    						$orderURL =  get_site_url()."/checkout/order-received/".$orderId."/?key=".$order->order_key;
    					}else{
    						$orderURL =  get_site_url()."/checkout/order-pay/".$orderId;
    					}
    
    					wp_redirect( $orderURL );
    					exit;
    				}
    
    				function pesapalCron(){
    					$orderId = $_REQUEST['orderId']; 
    					if($orderId){
    						$orders[] = wc_get_order($orderId);
    					}else{
    						$fetch = array('processing','pending','on-hold','failed');
    						$args = array(
    							'status' => $fetch,
    							'date_created' => '>' . ( time() - HOUR_IN_SECONDS ),
    							'return' => 'ids',
    						);
    
    						$orders = wc_get_orders($args);
    					} 
    				
    					foreach($orders as $orderId){
    						$status = "";
    						$order = wc_get_order( $orderId );
    						$order_tracking_data = $this->get_order_tracking_data($order->id);
    						
							$now = new DateTime();
							$date = new DateTime($order->date_created);
							if($date->diff($now)->format("%i") > 3){
								echo "<br>Order ".$order->id;
								
								if(isset($order_tracking_data->order_tracking_id) && $order_tracking_data->order_tracking_id){
									$tokenResponse = $this->pesapalV30Helper->getAccessToken($this->consumer_key,$this->consumer_secret);

									$access_token = $tokenResponse->token;
									$response = $this->pesapalV30Helper->getTransactionStatus($order_tracking_data->order_tracking_id,$access_token);
									$statusResponseJson = "API3 | ".json_encode($response);
									if(isset($response->payment_status_description) && $response->payment_status_description){
										$status = strtoupper($response->payment_status_description);
									}

                                    if(!$status || $status=="INVALID" || $status=="FAILED" || $status=="PENDING"){
										$transaction = $this->pesapalSTKStatus($order->id);
										$statusResponseJson .= " | STATUS CHECK 2: ".json_encode($transaction);
										$status = strtoupper($transaction->payment_status_decription);	
									}
								}

								if(count($_REQUEST)) $statusResponseJson .= " | ".json_encode($_REQUEST);
								echo "<br>Status: ".$status;
								echo "<br>".$statusResponseJson; 
								
								if($_REQUEST['exit']){
									echo "<br>T RESPONSE: <pre>"; var_dump($transaction);
									exit;
								}
		
								if($status=="COMPLETED"){
									$order->update_status($this->orderstatus, '<strong>Payment Completed</strong>.<br><br>You can now deliver the goods or services.');
									$order->add_order_note( __( '<strong>Cron Job</strong><br>'.$statusResponseJson, 'woocommerce' ) );
									$order->payment_complete();
								} else if ($status=="FAILED"){
									$order->update_status( 'wc-failed', '<strong>Payment Failed.</strong>');
									$order->add_order_note( __( '<strong>Cron Job</strong><br>'.$statusResponseJson, 'woocommerce' ) );
								}else if ($status=="REVERSED" ){
									$order->update_status( 'wc-refunded', '<strong>Payment Reversed.</strong>');
									$order->add_order_note( __( '<strong>Cron Job</strong><br>'.$statusResponseJson, 'woocommerce' ) );
								}
								echo "<br>------<br>";
							}
    
    					} echo "<br><br><br><br> --- END Of Cron Job --- ";exit;
    				} 
            
    				/**
    				 * IPN Response
    				 * @return null
    				 **/
    				function ipn_response(){
    					$orderTrackingId = '';
    					$pesapalTrackingId = '';
    					$pesapalRequestCode = '';
    					$pesapalNotification = '';
    					$orderNotificationType = '';
    					$orderMerchantReference = '';
    					$pesapalMerchantReference = '';
    					if(isset($_REQUEST['pesapal_notification_type'])){
    						$pesapalNotification = $_REQUEST['pesapal_notification_type'];
    					}
    					
    					if(isset($_REQUEST['OrderTrackingId'])){
    						$orderTrackingId = $pesapalTrackingId = $_REQUEST['OrderTrackingId'];
    						$orderNotificationType = $pesapalNotification = $_REQUEST['OrderNotificationType'];
    						$orderMerchantReference = $pesapalMerchantReference = $_REQUEST['OrderMerchantReference'];
    					}  
    
    					if(isset($_REQUEST['pesapal_merchant_reference'])){
    						$pesapalMerchantReference = $_REQUEST['pesapal_merchant_reference'];
    					}
    						
    					if(isset($_REQUEST['pesapal_transaction_tracking_id'])){
    						$pesapalTrackingId = $_REQUEST['pesapal_transaction_tracking_id'];
    					}
    					
    					if(!$pesapalMerchantReference){
                            $postData = file_get_contents('php://input');
                            $postData = json_decode($postData);
    						if(isset($postData->request_code)){
    							$pesapalNotification = "STKIPNCHANGE";
        						$pesapalRequestCode = $postData->request_code;
    							$pesapalMerchantReference = $postData->merchant_reference;
        					}
    					}
    
    					//test if IPN runs on status change
    					if($this->get_option('ipnemails')){
    						$actual_link = home_url( '/' );
    						$to = str_replace(" ","",$this->get_option('ipnemaillist'));
    						$subject = 'IPN CALLED: '.$pesapalNotification." ".time();;
    						$message = '<b>Link: </b>'.$actual_link.'<br> ';
    						if($pesapalMerchantReference) $message .= '<b>Merchant Reference: </b>'.$pesapalMerchantReference.'<br> ';
    						if($pesapalTrackingId) $message .= '<b>Pesapal Tracking ID: </b>'.$pesapalTrackingId.'<br> ';
    						if($pesapalRequestCode) $message .= '<b>Mpesa Confirmation Code: </b>'.$pesapalRequestCode.'<br> ';
    
    						if($pesapalMerchantReference) {
    							$message .= '<strong>This emails confirms IPN works. ';
    							$message .= 'If orders are not updated, we are facing a plugin order update issue and not a PesaPal IPN trigger issue.<strong>';
    						}
    
    						$headers = array('Content-Type: text/html; charset=UTF-8');
    						$response = wp_mail( $to, $subject, $message, $headers);
    					}
    					
    					$status = "";
    					$statusResponseJson = "";
    					if($orderTrackingId){
    						$order_tracking_data = $this->get_order_tracking_data(null,$orderTrackingId);
    						$orderId = (isset($order_tracking_data->order_id) && $order_tracking_data->order_id) ? $order_tracking_data->order_id : "";
    						if(!$orderId){
    							if(!$status) { echo "End Of IPN Call! No Order Id retrieved".json_encode($_REQUEST); exit; }
    						}
    
    						$order = wc_get_order( $orderId );
    						$tokenResponse = $this->pesapalV30Helper->getAccessToken($this->consumer_key,$this->consumer_secret);

							$access_token = $tokenResponse->token;
    						$response = $this->pesapalV30Helper->getTransactionStatus($orderTrackingId,$access_token);
    						$statusResponseJson = json_encode($response);
    						if(isset($response->payment_status_description) && $response->payment_status_description){
    							$status = strtoupper($response->payment_status_description);
    						} 
    						
    						if(!$status || $status=="INVALID" || $status=="FAILED" || $status=="PENDING"){
								$transaction = $this->pesapalSTKStatus($order->id);
								$statusResponseJson .= " | STATUS CHECK 2: ".json_encode($transaction);
								$status = strtoupper($transaction->payment_status_decription);	
							}
    					}

                        if(count($_REQUEST)) $statusResponseJson .= " | ".json_encode($_REQUEST);
						else if(count($postData)) $statusResponseJson .= " | ".json_encode($postData);
    					
    					// We are here so lets check status and do actions
    					switch ($status) {
    						case 'COMPLETED' :
    							$order->update_status($this->orderstatus, '<strong>Payment Completed</strong>.<br><br>You can now deliver the goods or services.');
    							$order->add_order_note( __( '<strong>IPN Request</strong><br>'.$statusResponseJson, 'woocommerce' ) ); 	
    							$order->payment_complete();
    
    							break;
    						case 'PENDING' :
    							// Check order not already completed
    							if ( $order->get_status() == 'completed' ) {
    								 if ( 'yes' == $this->debug ){
    									$this->log->add( 'pesapal', 'Aborting, Order #' . $order->id . ' is already complete.' );
    								 }
    								 exit;
    							}
    
    							$order->update_status( 'wc-on-hold', sprintf( __( 'Payment pending: %s', 'woocommerce' ), 'Waiting PesaPal confirmation' ) );
    							
    							if ( 'yes' == $this->debug ){
    								$this->log->add( 'pesapal', 'Payment complete.' );
    							}
    
    							break;
    						case 'FAILED' :
    							// Order failed
    							$order->update_status( 'wc-failed', '<strong>Payment Failed.</strong>' );
    							$order->add_order_note( __( '<strong>IPN Request</strong><br>'.$statusResponseJson, 'woocommerce' ) ); 	
    							break;
    						case 'REVERSED' :
    							// Order failed
    							$order->update_status( 'wc-refunded', '<strong>Payment Reversed.</strong>' );
    							$order->add_order_note( __( '<strong>IPN Request</strong><br>'.$statusResponseJson, 'woocommerce' ) ); 	
    							break;
    
    						default :
    							// No action
    						break;
    					}
    
    					if($pesapalNotification == "CHANGE" || $pesapalNotification =="STKIPNCHANGE" || $orderNotificationType == "IPNCHANGE"){  
    						if($orderTrackingId){  
    							$respObjct = new  stdClass();
    							$respObjct->OrderNotificationType = $orderNotificationType;
    							$respObjct->OrderTrackingId = $orderTrackingId;
    							$respObjct->OrderMerchantReference = $orderMerchantReference;
    							$respObjct->status = "200";
    							$resp = json_encode($respObjct);
    						}else if($pesapalNotification =="STKIPNCHANGE"){  
    							$respObjct = new  stdClass();
    							$respObjct->pesapalRequestCode = $pesapalRequestCode;
    							$respObjct->pesapalMerchantReference = $pesapalMerchantReference;
    							$respObjct->status = "200";
    							$resp = json_encode($respObjct);
    						}else if($status != "PENDING"){  
    							$resp = "pesapal_notification_type=$pesapalNotification".		
    								"&pesapal_transaction_tracking_id=$pesapalTrackingId".
    								"&pesapal_merchant_reference=$pesapalMerchantReference";
    						} 
                                                
    						ob_start();
    						echo $resp;
    						ob_flush();
    						exit;
    					}
    					
    					if(!$status) { 
    						echo "End Of IPN Call! ";
							if(count($_REQUEST)) echo json_encode($_REQUEST); 
							else if(count($postData)) echo json_encode($postData); 
							exit; 
    					}
    				}
    			}
    		}
    	}
    }


	//Add a custom description
	function add_custom_description($custom_message, $combined_description){
		global $woocommerce;
		
		//get an instance of pesapal payment gateway
		$class_get_customessage = new WC_TheBunchKE_PesaPal_Pay_Gateway();
		$chosen_payment_method = WC()->session->get('chosen_payment_method');
		if ($chosen_payment_method == 'pesapal') {
			ob_start();

			echo '<div>';
				$description = $class_get_customessage->settings['description'];
				$custom_message = $class_get_customessage->settings['customessage'];

				printf("<p>".$description."</p>"."<p style='margin-top: 12px'>".$custom_message."</p>");
			
			echo '</div>';

			$combined_description .= ob_get_clean();
			return $combined_description;
		};

	};
    
    function woocommerce_pesapal_surcharge() {			
    	global $woocommerce;
    	
    	//get an instance of pesapal payment gateway
    	
    	$class_get_surchargerate = new WC_TheBunchKE_PesaPal_Pay_Gateway();
    	
    
    	if ( is_admin() &&  !defined( 'DOING_AJAX' ) )
    		return;
    	
    	if ( ! ( is_checkout() && ! is_wc_endpoint_url() ) )
            return; //only on checkout page
    	
    	$chosen_payment_method = WC()->session->get('chosen_payment_method');
    	if ($chosen_payment_method == 'pesapal') {
			if($class_get_surchargerate->settings['surcharge_rate']){
				$percentage = $class_get_surchargerate->settings['surcharge_rate']/100;
				$surcharge = (( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) / (1-$percentage)) - ( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total );
				
				WC()->cart->add_fee(__('Surcharge Fee', 'txtdomain'), $surcharge);
			}
    	}
    }
    
    //Calculate the surcharge
    add_action( 'woocommerce_cart_calculate_fees', 'woocommerce_pesapal_surcharge' );
    
    //Initialize the plugin
    add_action('plugins_loaded', 'thebunchke_pesapal_woo_init', 0);

	//Add the custom description
	add_filter( 'woocommerce_gateway_description', 'add_custom_description', 20, 2 );
    
    //refresh the checkout when another payment method is selected
    add_action('woocommerce_review_order_before_payment', function() {
    ?><script type="text/javascript">
    	(function($){
    			$('form.checkout').on('change', 'input[name^="payment_method"]', function() {
    			$('body').trigger('update_checkout');
    		});
    	})(jQuery);
    </script><?php
    });
?>