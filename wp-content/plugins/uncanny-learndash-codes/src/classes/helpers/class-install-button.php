<?php

namespace uncanny_learndash_codes;

/**
 * Class Shortcodes
 * @package uncanny_learndash_codes
 */
class Install_Button extends Config {
	private static $coupon_id;

	/**
	 * Shortcodes constructor.
	 */
	public function __construct() {
		require_once self::get_include( 'class-auto-plugin-install.php', false, 'auto-plugin-install' );
		$woocommerce_activate = new Auto_Plugin_Install();
		$woocommerce_activate->create_ajax();
	}
}