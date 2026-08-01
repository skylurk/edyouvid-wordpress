<?php
/**
 * Plugin Name: REDIS - WooCommerce Dynamic Pricing and Discounts
 * Plugin URI: https://villatheme.com/extensions/redis-woocommerce-dynamic-pricing-and-discounts/
 * Description: REDIS - WooCommerce Dynamic Pricing and Discounts help you easily set up bulk discounts for products or add discounts to the cart in various scenarios.
 * Version: 1.0.23
 * Author: VillaTheme
 * Author URI: https://villatheme.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: redis-woo-dynamic-pricing-and-discounts
 * Domain Path: /languages
 * Copyright 2021 - 2026 VillaTheme.com. All rights reserved.
 * Requires Plugins: woocommerce
 * Requires PHP: 7.0
 * Requires at least: 5.0
 * Tested up to: 7.0
 * WC requires at least: 7.0
 * WC tested up to: 10.8
 **/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if(!defined('VIREDIS_VERSION')) {
	define( 'VIREDIS_VERSION', '1.0.23' );
	define( 'VIREDIS_DIR', plugin_dir_path( __FILE__ ) );
	define( 'VIREDIS_LANGUAGES', VIREDIS_DIR . "languages" . DIRECTORY_SEPARATOR );
	define( 'VIREDIS_INCLUDES', VIREDIS_DIR . "includes" . DIRECTORY_SEPARATOR );
	define( 'VIREDIS_ADMIN', VIREDIS_INCLUDES . "admin" . DIRECTORY_SEPARATOR );
	define( 'VIREDIS_FRONTEND', VIREDIS_INCLUDES . "frontend" . DIRECTORY_SEPARATOR );
	define( 'VIREDIS_TEMPLATES', VIREDIS_INCLUDES . "templates" . DIRECTORY_SEPARATOR );
	$plugin_url = plugins_url( 'assets/', __FILE__ );
	define( 'VIREDIS_CSS', $plugin_url . "css/" );
	define( 'VIREDIS_JS', $plugin_url . "js/" );
	define( 'VIREDIS_IMAGES', $plugin_url . "images/" );
}
/**
 * Class VIREDIS_DYNAMIC_PRICING_AND_DISCOUNTS
 */
class VIREDIS_DYNAMIC_PRICING_AND_DISCOUNTS {
	public function __construct() {
		//compatible with 'High-Performance order storage (COT)'
		add_action( 'before_woocommerce_init', array( $this, 'before_woocommerce_init' ) );
		add_action( 'activated_plugin', array( $this, 'activated_plugin' ),10,2 );
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}
	public function init() {
		$include_dir = plugin_dir_path( __FILE__ ) . 'includes/';
		if ( ! class_exists( 'VillaTheme_Require_Environment' ) ) {
			include_once $include_dir . 'support.php';
		}

		$environment = new VillaTheme_Require_Environment( [
				'plugin_name'     => 'REDIS - WooCommerce Dynamic Pricing and Discounts',
				'php_version'     => '7.0',
				'wp_version'      => '5.0',
				'require_plugins' => [
					[
						'slug' => 'woocommerce',
						'name' => 'WooCommerce',
						'defined_version' => 'WC_VERSION',
						'version' => '7.0',
					]
				]
			]
		);

		if ( $environment->has_error() ) {
			return;
		}

		$this->includes();
	}
	public function before_woocommerce_init() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}

	protected function includes() {
		$files = array(
			VIREDIS_INCLUDES . 'class-pricing-table.php',
			VIREDIS_INCLUDES . 'data.php',
			VIREDIS_INCLUDES . 'functions.php',
			VIREDIS_INCLUDES . 'support.php',
		);
		foreach ( $files as $file ) {
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
		villatheme_include_folder( VIREDIS_ADMIN, 'VIREDIS_Admin_' );
		if ( ! is_admin() || wp_doing_ajax() ) {
			villatheme_include_folder( VIREDIS_FRONTEND, 'VIREDIS_Frontend_' );
		}
	}

	function activated_plugin( $plugin, $network_wide  ) {
		if ( $plugin !== 'redis-woo-dynamic-pricing-and-discounts/redis-woo-dynamic-pricing-and-discounts.php' ) {
			return;
		}
		if (!class_exists('VIREDIS_Pricing_Table')){
			require_once VIREDIS_INCLUDES . 'class-pricing-table.php';
		}
		if ( $network_wide && function_exists( 'is_multisite' ) && is_multisite() ) {
			global $wpdb;
			$current_blog = $wpdb->blogid;
			$blogs        = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			foreach ( $blogs as $blog ) {
				switch_to_blog( $blog );
				VIREDIS_Pricing_Table::create_table();
			}
			switch_to_blog( $current_blog );
		}else{
			VIREDIS_Pricing_Table::create_table();
		}
	}
}

new VIREDIS_DYNAMIC_PRICING_AND_DISCOUNTS();