<?php
/**
 * CA Framework - Popup Class
 *
 * Handles popup offers shown on specific plugin pages
 * with dismiss, re-show, and countdown functionality.
 *
 * @package CA_Framework
 * @version 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'CA_Framework_Popup' ) ) {

    /**
     * Popup Class
     */
    class CA_Framework_Popup {

        /**
         * Popup configuration
         *
         * @var array
         */
        private $args = array();

        /**
         * Default configuration
         *
         * @var array
         */
        private $defaults = array(
            'id'             => '',
            'plugin_slug'    => '',
            'framework_dir'  => '',
            'title'          => '',
            'description'    => '',
            'start_date'     => '',
            'end_date'       => '',
            'buttons'        => array(),
            'badge_text'     => '',
            'image_url'      => '',
            'show_countdown' => false,
            'dismiss_type'   => 'temporary',
            'reshow_after'   => 7,
            'reshow_unit'    => 'days',
            'pages'          => array(),
            'capability'     => 'manage_options',
            'width'          => '520px',
            'overlay'        => true,
            'randomize'     => 30
        );

        /**
         * Constructor
         *
         * @param array $args Popup configuration.
         */
        public function __construct( $args = array() ) {
            $this->args = wp_parse_args( $args, $this->defaults );

            if ( empty( $this->args['id'] ) ) {
                $this->args['id'] = $this->args['plugin_slug'] . '_popup_' . md5( $this->args['title'] );
            }
        }

        /**
         * Display the popup by hooking into WordPress.
         *
         * @return $this
         */
        public function show() {
            if ( wp_rand( 1, 100 ) <= $this->args['randomize'] ) {
                add_action( 'admin_footer', array( $this, 'render' ) );
            }
            return $this;
        }

        /**
         * Check if the popup should be displayed.
         *
         * @return bool
         */
        public function should_display() {
            // Check user capability
            if ( ! current_user_can( $this->args['capability'] ) ) {
                return false;
            }

            // Check if dismissed
            if ( CA_Framework_Dismiss_Handler::is_dismissed( $this->args['id'] ) ) {
                return false;
            }

            // Check date range
            $now = current_time( 'timestamp' );

            if ( ! empty( $this->args['start_date'] ) ) {
                $start = strtotime( $this->args['start_date'] );
                if ( $start && $now < $start ) {
                    return false;
                }
            }

            if ( ! empty( $this->args['end_date'] ) ) {
                $end = strtotime( $this->args['end_date'] );
                if ( $end && $now > $end ) {
                    return false;
                }
            }

            // Check page restriction
            if ( ! empty( $this->args['pages'] ) ) {
                $screen = get_current_screen();
                //I would like to check using strpos instead of in_array to allow partial matches for screen IDs
                if ( $screen && ! array_filter( $this->args['pages'], fn( $page ) => strpos( $screen->id, $page ) !== false ) ) {
                    return false;
                }
            }

            // Check page exclude restriction
            if ( ! empty( $this->args['pages_exclude'] ) && is_array( $this->args['pages_exclude'] ) ) {
                $screen = get_current_screen();
                //I would like to check using strpos instead of in_array to allow partial matches for screen IDs
                if ( $screen && array_filter( $this->args['pages_exclude'], fn( $page ) => strpos( $screen->id, $page ) !== false ) ) {
                    return false;
                }
            }

            return true;
        }

        /**
         * Render the popup.
         */
        public function render() {
            if ( ! $this->should_display() ) {
                return;
            }

            $popup = $this->args;
            include $this->args['framework_dir'] . 'templates/popup.php';
        }
    }
}
