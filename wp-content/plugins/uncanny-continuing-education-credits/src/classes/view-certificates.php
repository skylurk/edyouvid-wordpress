<?php

namespace uncanny_ceu;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * Class ViewCertificates
 * @package uncanny_ceu
 */
class ViewCertificates {

	/**
	 * class constructor
	 *
	 */
	function __construct() {

		add_filter( 'uo_certificate_list_shortcode', array( $this, 'certificate_list_shortcode' ), 10, 3 );
		add_filter( 'certificate_list_widget', array( $this, 'certificate_list_widget' ), 10, 1 );

	}

	/**
	 * Append CEU certificates to Uncanny Toolkit certificate shortcode
	 *
	 * @param string $certificate_list
	 *
	 * @return string
	 */
	function certificate_list_shortcode( $certificate_list, $courses, $quiz_attempts ) {

		global $wpdb;

		$user_id      = get_current_user_id();
		$query        = "SELECT  meta_key, meta_value FROM $wpdb->usermeta WHERE user_id = $user_id AND meta_key LIKE 'ceu_cert_earned_%'";
		$certificates = $wpdb->get_results( $query );

		foreach ( $certificates as $certificate ) {

			$certificate_id = absint( str_replace( 'ceu_cert_earned_', '', $certificate->meta_key ) );
			$certificate    = get_post( $certificate_id );

			if ( $certificate ) {

				$certificate_file_paths = CEU_CRUD::get_instance()->get_usermeta( $user_id, 'uo-ceu-cert-' . $certificate_id );

				// Skip if certificate file paths is empty.
				if ( empty( $certificate_file_paths ) ) {
					continue;
				}

				foreach ( $certificate_file_paths as $certificate_file_path ) {

					// Check if certificate file actually exists.
					if ( file_exists( $certificate_file_path ) ) {

						$certificate_file = basename( $certificate_file_path );
						// Construct the upload directory to uploads/ceu-certs/
						$uploads_dir      = sprintf( '%s%sceu-certs', wp_get_upload_dir()['baseurl'], DIRECTORY_SEPARATOR );
						$certificate_link = trailingslashit( $uploads_dir ) . $certificate_file;

						$certificate_title = $certificate->post_title;
						$certificate_list .= '<a target="_blank" href="' . $certificate_link . '">' . $certificate_title . '</a><br>';

					}
				}
			}
		}

		return $certificate_list;
	}

	function certificate_list_widget( $certificate_list ) {

		global $wpdb;

		$user_id      = get_current_user_id();
		$query        = "SELECT  meta_key, meta_value FROM $wpdb->usermeta WHERE user_id = $user_id AND meta_key LIKE 'ceu_cert_earned_%'";
		$certificates = $wpdb->get_results( $query );

		foreach ( $certificates as $certificate ) {

			$certificate_id = absint( str_replace( 'ceu_cert_earned_', '', $certificate->meta_key ) );
			$certificate    = get_post( $certificate_id );

			if ( $certificate ) {
				$certificate_key   = urlencode( SharedFunctions::encrypt( $user_id . '-ceu_cert_earned', wp_salt() ) );
				$certificate_link  = get_permalink( $certificate_id ) . '?ceu_access_key=' . $certificate_key;
				$certificate_title = $certificate->post_title;

				$certificate_list .= sprintf(
					'<li><a target="_blank" href="%s" title="%s %s" >%s</a></li>',
					esc_url( $certificate_link ),
					esc_html__( 'Your certificate for :', 'uncanny-learndash-toolkit' ),
					$certificate_title,
					esc_html( $certificate_title )
				);

			}
		}

		return $certificate_list;

	}

}
