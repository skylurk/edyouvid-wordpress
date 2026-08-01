<?php
/**
 * CA Framework - Main Loader
 *
 * A lightweight, reusable framework for WordPress plugins.
 * Provides offers, popups, required/recommended plugins management.
 *
 * @package CA_Framework
 * @version 1.0.0
 * @author CodeAstrology Team
 *
 * Usage:
 *   require_once __DIR__ . '/framework/framework.php';
 *   CA_Framework::init( 'your-plugin-slug', __FILE__ );
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'CA_Framework' ) ) {

    /**
     * Main Framework Class
     */
    final class CA_Framework {

        /**
         * Framework version
         *
         * @var string
         */
        const VERSION = '1.0.0';

        /**
         * Initialized instances per plugin
         *
         * @var array
         */
        private static $instances = array();

        /**
         * Plugin slug
         *
         * @var string
         */
        private $plugin_slug;

        /**
         * Plugin base file
         *
         * @var string
         */
        private $plugin_file;

        /**
         * Framework directory path
         *
         * @var string
         */
        private $framework_dir;

        /**
         * Framework directory URL
         *
         * @var string
         */
        private $framework_url;

        /**
         * Whether assets have been enqueued
         *
         * @var bool
         */
        private static $assets_enqueued = false;

        /**
         * Initialize the framework for a plugin.
         *
         * @param string $plugin_slug Unique plugin identifier.
         * @param string $plugin_file Main plugin file path (__FILE__ from main plugin).
         * @return CA_Framework
         */
        public static function init( $plugin_slug, $plugin_file ) {
            if ( ! isset( self::$instances[ $plugin_slug ] ) ) {
                self::$instances[ $plugin_slug ] = new self( $plugin_slug, $plugin_file );
            }
            return self::$instances[ $plugin_slug ];
        }

        /**
         * Get framework instance for a plugin.
         *
         * @param string $plugin_slug Plugin identifier.
         * @return CA_Framework|null
         */
        public static function get_instance( $plugin_slug ) {
            return isset( self::$instances[ $plugin_slug ] ) ? self::$instances[ $plugin_slug ] : null;
        }

        /**
         * Constructor
         *
         * @param string $plugin_slug Plugin identifier.
         * @param string $plugin_file Main plugin file path.
         */
        private function __construct( $plugin_slug, $plugin_file ) {
            $this->plugin_slug   = sanitize_key( $plugin_slug );
            $this->plugin_file   = $plugin_file;
            $this->framework_dir = plugin_dir_path( __FILE__ );
            $this->framework_url = plugin_dir_url( __FILE__ );

            $this->load_classes();
            $this->register_hooks();
        }

        /**
         * Load framework class files.
         */
        private function load_classes() {
            require_once $this->framework_dir . 'classes/class-dismiss-handler.php';
            require_once $this->framework_dir . 'classes/class-offer.php';
            require_once $this->framework_dir . 'classes/class-popup.php';
            require_once $this->framework_dir . 'classes/class-required-plugin.php';
            require_once $this->framework_dir . 'classes/class-recommended-plugin.php';
        }

        /**
         * Register WordPress hooks.
         */
        private function register_hooks() {
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
            CA_Framework_Dismiss_Handler::register( $this->plugin_slug );
        }

        /**
         * Enqueue framework CSS and JS assets (once).
         */
        public function enqueue_assets() {
            if ( self::$assets_enqueued ) {
                return;
            }

            wp_enqueue_style(
                'ca-framework',
                $this->framework_url . 'assets/css/framework.css',
                array(),
                self::VERSION
            );

            wp_enqueue_script(
                'ca-framework',
                $this->framework_url . 'assets/js/framework.js',
                array( 'jquery', 'updates' ),
                self::VERSION,
                true
            );

            wp_localize_script( 'ca-framework', 'caFramework', array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'ca_framework_nonce' ),
            ) );

            self::$assets_enqueued = true;
        }

        /**
         * Create an offer notice.
         *
         * @param array $args Offer configuration.
         * @return CA_Framework_Offer
         */
        public function create_offer( $args = array() ) {
            $args['plugin_slug']   = $this->plugin_slug;
            $args['framework_dir'] = $this->framework_dir;
            return new CA_Framework_Offer( $args );
        }

        /**
         * Create a popup.
         *
         * @param array $args Popup configuration.
         * @return CA_Framework_Popup
         */
        public function create_popup( $args = array() ) {
            $args['plugin_slug']   = $this->plugin_slug;
            $args['framework_dir'] = $this->framework_dir;
            return new CA_Framework_Popup( $args );
        }

        /**
         * Register required plugins.
         *
         * @param array $plugins List of required plugin configurations.
         * @return CA_Framework_Required_Plugin
         */
        public function required_plugins( $plugins = array() ) {
            $plugin_basename = plugin_basename($this->plugin_file);
            return new CA_Framework_Required_Plugin( $this->plugin_slug, $plugins, $plugin_basename );
        }

        /**
         * Register recommended plugins.
         *
         * @param array $plugins List of recommended plugin configurations.
         * @return CA_Framework_Recommended_Plugin
         */
        public function recommended_plugins( $plugins = array(), $dismiss_id = '' ) {
            $dismiss_id = $dismiss_id ?: $this->plugin_slug . '_recommended';
            return new CA_Framework_Recommended_Plugin( $this->plugin_slug, $plugins, $dismiss_id );
        }

        /**
         * Get framework directory path.
         *
         * @return string
         */
        public function get_dir() {
            return $this->framework_dir;
        }

        /**
         * Get framework URL.
         *
         * @return string
         */
        public function get_url() {
            return $this->framework_url;
        }

        /**
         * Get plugin slug.
         *
         * @return string
         */
        public function get_slug() {
            return $this->plugin_slug;
        }
    }
}
