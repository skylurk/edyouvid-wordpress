<?php
/**
 * CA Framework - WPT Required Plugin Handler
 *
 * Checks if required plugins (WooCommerce) are active.
 * Used by the main plugin to gate functionality.
 *
 * @package CA_Framework
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once dirname( __FILE__ ) . '/framework.php';

if ( ! class_exists( 'WPT_Required' ) ) {

    /**
     * WPT Required Plugin Check
     */
    class WPT_Required {

        /**
         * Check if required plugins are missing.
         *
         * @return bool True if requirements are NOT met (fail).
         */
        public static function fail() {
            // dd(! class_exists( 'WooCommerce' ),self::class . '::' . __FUNCTION__ . ' called');
            // WooCommerce is required
            if ( ! class_exists( 'WooCommerce' ) ) {
                $framework = CA_Framework::init( 'woo-product-table', WPT_PLUGIN_FILE_NAME );
                $framework->required_plugins( array(
                    array(
                        'name'        => 'WooCommerce',
                        'slug'        => 'woocommerce',
                        'path'        => 'woocommerce/woocommerce.php',
                        'description' => 'WooCommerce is required for Product Table to work. Please install and activate WooCommerce.',
                        'icon'        => 'https://ps.w.org/woocommerce/assets/icon.svg?rev=3234504',
                    ),
                ) )->show();

                return true;
            }

            return false;
        }
    }
}
