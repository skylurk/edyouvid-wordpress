<?php

/**
 * License Class.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LRTQ_License {
    private $license_key_field = null;

    /**
     * @var LRTQ_License_Handler
     */
    private $license_handler = null;

    public function __construct() {

        $this->license_key_field = 'wn_lrtq_license_key';

        add_action( 'init', [ $this, 'plugin_init' ] );
        add_action( 'admin_notices', [ $this, 'show_license_expire_or_invalid' ], 20 );

        /**
         * Enable these for local testing
         */
         # add_filter( 'edd_sl_api_request_verify_ssl', '__return_false', 10, 2 );
         # add_filter( 'https_ssl_verify', '__return_false' );
         # add_filter( 'http_request_host_is_external', '__return_true', 10, 3 );
    }

    public function plugin_init() {
        if ( ! current_user_can( 'manage_options' ) || ! is_admin() )
            return;

        if( !function_exists('get_plugin_data') ){
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        $plugin_data = get_plugin_data( LEARNDASH_RETAKEQUIZ_DIR_FILE );
        $this->license_handler = new LRTQ_License_Handler( LEARNDASH_RETAKEQUIZ_DIR_FILE, $plugin_data['Name'], $plugin_data['Version'], $plugin_data['AuthorName'], $this->license_key_field );
    }

    public function show_license_expire_or_invalid() {
        if ( ! isset( $this->license_handler ) )
            return;

        $license_setting_url = add_query_arg( array( 'page' => 'ld-retake-quiz-options' ), admin_url( 'admin.php' ) );
        $error_msg = '';
        $success_msg = '';
        $submission = isset( $_POST['lrtq_activate_license'] ) || isset( $_POST['lrtq_deactivate_license'] );
	    $invalid_license_err = __( 'You\'ve entered an invalid license key for <strong>LearnDash Retake Quiz</strong>. Click <a href="' . esc_attr( $license_setting_url ) . '">here</a> to update your license key or renew it <a href="https://wooninjas.com/wn-products/learndash-retake-quiz/" target="_blank">here</a>.', 'ld_retake_quiz' );
	    $expired_license_err = __( 'Your license for <strong>LearnDash Retake Quiz</strong> is expired. Click <a href="https://wooninjas.com/wn-products/learndash-retake-quiz/" target="_blank">here</a> to renew your license key and receive latest add-on updates automatically.', 'ld_retake_quiz' );

        if( $submission ) {
            if( $this->license_handler->is_active() ) {
                $success_msg = __( 'License Activated!', 'ld_retake_quiz' );
            } else if( $this->license_handler->is_expired() ) {
                $error_msg = $expired_license_err;
            } else if( $this->license_handler->last_err() ) {
                $error_msg = $invalid_license_err;
            } else if( !$this->license_handler->is_active() ) {
                $success_msg = __( 'License Deactivated!', 'ld_retake_quiz' );
            }
        } else {
            if ( $this->license_handler->is_expired() ) {
                $error_msg = $expired_license_err;
            } else if( !$this->license_handler->is_active() ) {
                $error_msg = $invalid_license_err;
            }
        }

        if( $success_msg ) { ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo $success_msg; ?></p>
            </div>
            <?php
        } else if( $error_msg ) { ?>
            <div class="error notice">
                <p><?php echo $error_msg; ?></p>
            </div>
            <?php
        }
    }

	/**
	 * @return LRTQ_License_Handler
	 */
    public function get_license_handler() {
        return $this->license_handler;
    }

	/**
	 * @return string
	 */
	public function get_license_key_field() {
		return $this->license_key_field;
	}
}
