<?php
/**
 * CA Framework - Dismiss Handler
 *
 * Handles AJAX dismiss requests for offers, popups, and notices.
 *
 * @package CA_Framework
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'CA_Framework_Dismiss_Handler' ) ) {

    /**
     * Dismiss Handler Class
     */
    class CA_Framework_Dismiss_Handler {

        /**
         * Whether AJAX handler is registered
         *
         * @var bool
         */
        private static $registered = false;

        /**
         * Register AJAX hooks (once).
         *
         * @param string $plugin_slug Plugin identifier.
         */
        public static function register( $plugin_slug ) {
            if ( self::$registered ) {
                return;
            }

            add_action( 'wp_ajax_ca_framework_dismiss', array( __CLASS__, 'handle_dismiss' ) );
            add_action( 'wp_ajax_ca_framework_activate_plugin', array( __CLASS__, 'handle_activate_plugin' ) );
            self::$registered = true;
        }

        /**
         * Handle AJAX dismiss request.
         */
        public static function handle_dismiss() {
            check_ajax_referer( 'ca_framework_nonce', 'nonce' );

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_send_json_error( 'Unauthorized' );
            }

            $dismiss_id   = isset( $_POST['dismiss_id'] ) ? sanitize_key( $_POST['dismiss_id'] ) : '';
            $dismiss_type = isset( $_POST['dismiss_type'] ) ? sanitize_key( $_POST['dismiss_type'] ) : 'permanent';
            $reshow_after = isset( $_POST['reshow_after'] ) ? absint( $_POST['reshow_after'] ) : 0;
            $reshow_unit  = isset( $_POST['reshow_unit'] ) ? sanitize_key( $_POST['reshow_unit'] ) : 'days';

            if ( empty( $dismiss_id ) ) {
                wp_send_json_error( 'Invalid dismiss ID' );
            }

            $user_id = get_current_user_id();

            if ( 'permanent' === $dismiss_type ) {
                update_user_meta( $user_id, 'ca_fw_dismissed_' . $dismiss_id, 'permanent' );
            } elseif ( 'temporary' === $dismiss_type && $reshow_after > 0 ) {
                $multiplier  = DAY_IN_SECONDS;// ( 'hours' === $reshow_unit ) ? HOUR_IN_SECONDS : DAY_IN_SECONDS;

                switch( $reshow_unit ) {
                    case 'seconds':
                        $multiplier = 1;
                        break;
                    case 'minutes':
                        $multiplier = MINUTE_IN_SECONDS;
                        break;
                    case 'hours':
                        $multiplier = HOUR_IN_SECONDS;
                        break;
                    case 'days':
                    default:
                        $multiplier = DAY_IN_SECONDS;
                        break;
                }

                $reshow_time = time() + ( $reshow_after * $multiplier );
                update_user_meta( $user_id, 'ca_fw_dismissed_' . $dismiss_id, $reshow_time );
            }

            wp_send_json_success();
        }

        /**
         * Check if a notice/offer/popup is dismissed for the current user.
         *
         * @param string $dismiss_id Unique dismiss identifier.
         * @return bool True if currently dismissed.
         */
        public static function is_dismissed( $dismiss_id ) {
            $user_id = get_current_user_id();
            $value   = get_user_meta( $user_id, 'ca_fw_dismissed_' . $dismiss_id, true );

            if ( empty( $value ) ) {
                return false;
            }

            if ( 'permanent' === $value ) {
                return true;
            }

            // Temporary dismiss: check if reshow time has passed
            if ( is_numeric( $value ) && time() < (int) $value ) {
                return true;
            }

            // Time has passed, remove the meta and show again
            if ( is_numeric( $value ) && time() >= (int) $value ) {
                delete_user_meta( $user_id, 'ca_fw_dismissed_' . $dismiss_id );
                return false;
            }

            return false;
        }

        /**
         * Handle AJAX plugin activation request.
         */
        public static function handle_activate_plugin() {
            check_ajax_referer( 'ca_framework_nonce', 'nonce' );

            if ( ! current_user_can( 'activate_plugins' ) ) {
                wp_send_json_error( 'Unauthorized' );
            }

            $plugin_path = isset( $_POST['plugin_path'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_path'] ) ) : '';

            if ( empty( $plugin_path ) ) {
                wp_send_json_error( 'Invalid plugin path' );
            }

            if ( ! function_exists( 'activate_plugin' ) ) {
                include_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            $result = activate_plugin( $plugin_path );

            if ( is_wp_error( $result ) ) {
                wp_send_json_error( $result->get_error_message() );
            }

            wp_send_json_success();
        }
    }
}
