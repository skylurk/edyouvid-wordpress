<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VIREDIS_Admin_Admin {
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_filter(
			'plugin_action_links_redis-woo-dynamic-pricing-and-discounts/redis-woo-dynamic-pricing-and-discounts.php', array(
				$this,
				'settings_link'
			)
		);
	}
	public function settings_link( $links ) {
		$settings_link = sprintf( '<a href="%s?page=viredis-product_pricing" title="%s">%s</a>', esc_url( admin_url( 'admin.php' ) ),
			esc_attr__( 'Settings', 'redis-woo-dynamic-pricing-and-discounts' ),
			esc_html__( 'Settings', 'redis-woo-dynamic-pricing-and-discounts' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}
	public function init() {
		$this->load_plugin_textdomain();
		if ( class_exists( 'VillaTheme_Support' ) ) {
			new VillaTheme_Support(
				array(
					'support'   => 'https://wordpress.org/support/plugin/redis-woo-dynamic-pricing-and-discounts/',
					'docs'      => 'https://docs.villatheme.com/?item=redis',
					'review'    => 'https://wordpress.org/support/plugin/redis-woo-dynamic-pricing-and-discounts/reviews/?rate=5#rate-response',
					'pro_url'   => '',
					'css'       => VIREDIS_CSS,
					'image'     => VIREDIS_IMAGES,
					'slug'      => 'redis-woo-dynamic-pricing-and-discounts',
					'menu_slug' => 'viredis-product_pricing',
					'survey_url' => 'https://script.google.com/macros/s/AKfycbxc20tHs8NzmbB5PYXEkUAJY-mB7OsEPwcnQ1C6AmeMz_4Yupn2VdQziEEglurg3-5E/exec',
					'version'   => VIREDIS_VERSION
				)
			);
		}
	}
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'redis-woo-dynamic-pricing-and-discounts' );
		load_textdomain( 'redis-woo-dynamic-pricing-and-discounts', VIREDIS_LANGUAGES . "redis-woo-dynamic-pricing-and-discounts-$locale.mo" );
		load_plugin_textdomain( 'redis-woo-dynamic-pricing-and-discounts', false, VIREDIS_LANGUAGES );
	}
}