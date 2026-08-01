<?php
/**
 * CA Framework - Offer Class
 *
 * Handles date-based promotional offers with templates,
 * custom buttons, countdown timer, and dismiss functionality.
 *
 * @package CA_Framework
 * @version 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'CA_Framework_Offer' ) ) {

    /**
     * Offer Class
     */
    class CA_Framework_Offer {

        /**
         * Offer configuration
         *
         * @var array
         */
        private $args = array();

        /**
         * Whether dismiss is disabled (for show_on_hook rendering)
         *
         * @var bool
         */
        private $no_dismiss = false;

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
            'template'       => 'starter',
            'buttons'        => array(),
            'badge_text'     => '',
            'highlight_text' => '',
            'image_url'      => '',
            'show_countdown' => false,
            'dismiss_type'   => 'permanent',
            'reshow_after'   => 0,
            'reshow_unit'    => 'days',
            'hook'           => 'admin_notices',
            'priority'       => 10,
            'pages'          => array(),
            'capability'     => 'manage_options',
            'randomize'     => 100,
        );

        /**
         * Constructor
         *
         * @param array $args Offer configuration.
         */
        public function __construct( $args = array() ) {
            $this->args = wp_parse_args( $args, $this->defaults );

            if ( empty( $this->args['id'] ) ) {
                $this->args['id'] = $this->args['plugin_slug'] . '_offer_' . md5( $this->args['title'] );
            }
        }

        /**
         * Display the offer by hooking into WordPress admin_notices (with dismiss).
         *
         * @return $this
         */
        public function show() {
            //I want to show 30% time excution add_action( $this->args['hook'], array( $this, 'render' ), $this->args['priority'] );
            if(wp_rand(1, 100) <= $this->args['randomize']) {
                add_action( $this->args['hook'], array( $this, 'render' ), $this->args['priority'] );
            }


            return $this;
        }

        /**
         * Display the offer on a specific action hook WITHOUT dismiss button.
         *
         * Can be chained: ->show_on_hook('hook_a')->show_on_hook('hook_b')
         *
         * @param string $hook    Action hook name.
         * @param int    $priority Hook priority.
         * @return $this
         */
        public function show_on_hook( $hook, $priority = 10 ) {
            add_action( $hook, array( $this, 'render_show_on_hook' ), $priority );
            return $this;
        }

        /**
         * Check if the offer should be displayed.
         *
         * @param bool $skip_dismiss Whether to skip dismiss check.
         * @return bool
         */
        public function should_display( $skip_dismiss = false ) {
            // Check user capability
            if ( ! current_user_can( $this->args['capability'] ) ) {
                return false;
            }

            // Check if dismissed (skip for show_on_hook)
            if ( ! $skip_dismiss && CA_Framework_Dismiss_Handler::is_dismissed( $this->args['id'] ) ) {
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
            if ( ! empty( $this->args['pages'] ) && is_array( $this->args['pages'] ) ) {
                $screen = get_current_screen();

                //I would like to check using strpos
                if ( $screen && ! array_filter( $this->args['pages'], fn( $page ) => strpos( $screen->id, $page ) !== false ) ) {
                    return false;
                }
            }

            // Check page exclude restriction
            if ( ! empty( $this->args['pages_exclude'] ) && is_array( $this->args['pages_exclude'] ) ) {
                $screen = get_current_screen();

                //I would like to check using strpos
                if ( $screen && array_filter( $this->args['pages_exclude'], fn( $page ) => strpos( $screen->id, $page ) !== false ) ) {
                    return false;
                }
            }

            return true;
        }

        /**
         * Render the offer (with dismiss button).
         */
        public function render() {
            if ( ! $this->should_display() ) {
                return;
            }

            $this->no_dismiss = false;
            $this->render_template();
        }

        /**
         * Render the offer WITHOUT dismiss button (for show_on_hook).
         */
        public function render_show_on_hook() {
            if ( ! $this->should_display( true ) ) {
                return;
            }

            $this->no_dismiss = true;
            $this->render_template(true);
        }

        /**
         * Render the offer template.
         */
        private function render_template($show_on_hook = false) {
            $template_file = $this->args['framework_dir'] . 'templates/offer-' . $this->args['template'] . '.php';

            if ( ! file_exists( $template_file ) ) {
                $template_file = $this->args['framework_dir'] . 'templates/offer-starter.php';
            }

            $offer      = $this->args;
            $no_dismiss = $this->no_dismiss;
            if(!$show_on_hook) echo '<div class="notice ca-fw-notice">';
            include $template_file;
            if(!$show_on_hook) echo '</div>';
        }

        /**
         * Render offer buttons HTML.
         *
         * @param array $buttons Button configurations.
         * @return string
         */
        public static function render_buttons( $buttons = array() ) {
            if ( empty( $buttons ) ) {
                return '';
            }

            $html = '<div class="ca-fw-offer-buttons">';
            foreach ( $buttons as $button ) {
                $btn = wp_parse_args( $button, array(
                    'text'   => '',
                    'url'    => '#',
                    'class'  => 'ca-fw-btn-primary',
                    'target' => '_blank',
                    'icon'   => '',
                ) );

                $icon_html = '';
                if ( ! empty( $btn['icon'] ) ) {
                    $icon_html = '<span class="dashicons ' . esc_attr( $btn['icon'] ) . '"></span> ';
                }

                $html .= sprintf(
                    '<a href="%s" class="ca-fw-btn %s" target="%s">%s%s</a>',
                    esc_url( $btn['url'] ),
                    esc_attr( $btn['class'] ),
                    esc_attr( $btn['target'] ),
                    $icon_html,
                    esc_html( $btn['text'] )
                );
            }
            $html .= '</div>';

            return $html;
        }

        /**
         * Render countdown HTML if enabled and end_date is set.
         *
         * @param array $config Offer or popup configuration array.
         * @return string
         */
        public static function render_countdown( $config = array() ) {
            if ( empty( $config['show_countdown'] ) || empty( $config['end_date'] ) ) {
                return '';
            }

            $end_timestamp = strtotime( $config['end_date'] );
            if ( ! $end_timestamp ) {
                return '';
            }

            $end_iso = gmdate( 'Y-m-d\TH:i:s', $end_timestamp );

            return '<div class="ca-fw-countdown" data-end-date="' . esc_attr( $end_iso ) . '">
                <div class="ca-fw-countdown-item">
                    <span class="ca-fw-countdown-number" data-days>00</span>
                    <span class="ca-fw-countdown-label">' . esc_html__( 'Days', 'flavor-jelee' ) . '</span>
                </div>
                <div class="ca-fw-countdown-sep">:</div>
                <div class="ca-fw-countdown-item">
                    <span class="ca-fw-countdown-number" data-hours>00</span>
                    <span class="ca-fw-countdown-label">' . esc_html__( 'Hours', 'flavor-jelee' ) . '</span>
                </div>
                <div class="ca-fw-countdown-sep">:</div>
                <div class="ca-fw-countdown-item">
                    <span class="ca-fw-countdown-number" data-minutes>00</span>
                    <span class="ca-fw-countdown-label">' . esc_html__( 'Min', 'flavor-jelee' ) . '</span>
                </div>
                <div class="ca-fw-countdown-sep">:</div>
                <div class="ca-fw-countdown-item">
                    <span class="ca-fw-countdown-number" data-seconds>00</span>
                    <span class="ca-fw-countdown-label">' . esc_html__( 'Sec', 'flavor-jelee' ) . '</span>
                </div>
            </div>';
        }
    }
}
