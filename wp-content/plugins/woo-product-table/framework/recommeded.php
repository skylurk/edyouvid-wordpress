<?php
/**
 * CA Framework - WPT Recommended Plugin Handler
 *
 * Shows recommended plugins for the Product Table plugin.
 *
 * @package CA_Framework
 * @version 1.0.0
 */

namespace WOO_Product_Table\Framework;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( __NAMESPACE__ . '\Recommeded' ) ) {

    /**
     * Recommended Plugins Check
     */
    class Recommeded {

        /**
         * Check and display recommended plugins.
         */
        public static function check() {
            $framework = \CA_Framework::init( 'woo-product-table', WPT_PLUGIN_FILE_NAME );

            $recommended_plugins = apply_filters( 'wpt_recommended_plugins', array() );

            if ( ! empty( $recommended_plugins ) ) {
                $framework->recommended_plugins( $recommended_plugins )->show();
            }
        }
    }
}
