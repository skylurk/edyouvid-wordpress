<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Handling all the AJAX calls in LoginPress Pro.
 *
 * @since 3.1.3
 * @version 6.1.0
 * @package LoginPress Pro
 * @class LoginPress_Pro_Ajax
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LoginPress_Pro_Ajax' ) ) {

	/**
	 * LoginPress_Pro_Ajax
	 */
	class LoginPress_Pro_Ajax {

		/**
		 * Constructor.
		 *
		 * @return void
		 * @since 3.1.3
		 * @version 6.1.0
		 */
		public function __construct() {
			$this->init();
		}

		/**
		 * Initialize the class, and hook into WordPress.
		 *
		 * @return void
		 * @since 3.1.3
		 * @version 6.1.0
		 */
		public function init() {

			$ajax_calls = array(
				'search_users' => false,
			);

			foreach ( $ajax_calls as $ajax_call => $no_priv ) {

				add_action( 'wp_ajax_loginpress_' . $ajax_call, array( $this, $ajax_call ) );

				if ( $no_priv ) {
					add_action( 'wp_ajax_nopriv_loginpress_' . $ajax_call, array( $this, $ajax_call ) );
				}
			}
		}

		/**
		 * Search users for auto login & login redirects.
		 *
		 * @return void
		 * @since 3.1.3
		 * @version 6.1.0
		 */
		public function search_users() {

			check_ajax_referer( 'loginpress_autocomplete_search_nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'No cheating, huh!', 'loginpress-pro' ) );
			}
			$loginpress_setting = get_option( 'loginpress_setting' );
			$user_verification  = isset( $loginpress_setting['enable_user_verification'] ) ? $loginpress_setting['enable_user_verification'] : 'off';
			$search_for         = isset( $_POST['search_for'] ) ? sanitize_text_field( wp_unslash( $_POST['search_for'] ) ) : '';
			$search             = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

			if ( 'on' === $user_verification && 'loginpress_autologin' === $search_for ) {
				$args = array(
					'meta_query' => array(  // @codingStandardsIgnoreLine.
						'relation' => 'OR',
						array(
							'key'     => 'loginpress_user_verification',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => 'loginpress_user_verification',
							'value'   => 'inactive',
							'compare' => '!=',
						),
					),
					'search'         => $search . '*',
					'search_columns' => array( 'user_login' ),
					'number'         => 10,
					'fields'         => array(
						'ID',
						'user_login',
						'user_email',
					),
				);
			} else {
				$args = array(
					'search'         => $search . '*',
					'number'         => 10,
					'search_columns' => array( 'user_login' ),
					'fields'         => array(
						'ID',
						'user_login',
						'user_email',
					),
				);
			}

			$user_query = new WP_User_Query( $args );
			$users      = $user_query->get_results();
			$user_data  = array();
			foreach ( $users as $user ) {
				$user_data[] = array(
					'id'       => $user->ID,
					'username' => $user->user_login,
					'email'    => $user->user_email,
				);
			}

			wp_send_json( $user_data );
			wp_die();
		}
	}
}
new LoginPress_Pro_AJAX();
