<?php

namespace uncanny_ceu;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * Class CeuCapabilities
 * @package uncanny_ceu
 */
class CeuCertificateMetaBox {


	/**
	 * class constructor
	 *
	 */
	function __construct() {


		if ( 'yes' === get_option( 'uncanny-ceu-credits-cert-enabled', 'no' ) ) {
			// Add CEU metabox
			add_action( 'add_meta_boxes', array( $this, 'add_ceu_value_metabox' ) );

			// Save CEU Metabox
			add_action( 'save_post', array( $this, 'ceu_value_save_meta_box_data' ) );
		}


	}

	/**
	 *  Add Metabox
	 *
	 */
	function add_ceu_value_metabox() {

		add_meta_box(
			'ceu_award_certificate_earned', // metabox ID
			sprintf( ' Award Certificate for %1$s Earned', Utilities::credit_designation_label( 'singular', __( 'CEU', 'uncanny-ceu' ) ) ), // metabox Title
			array( $this, 'ceu_award_certificate_meta_box_callback' ), // callback function
			'sfwd-certificates', // custom posttype page
			'side', // on page location
			'high' // priority
		);

		add_meta_box(
			'ceu_certificate_description', // metabox ID
			__('Certificate Trigger Description', 'uncanny-ceu' ), // metabox Title
			array( $this, 'ceu_certificate_description_meta_box_callback' ), // callback function
			'sfwd-certificates', // custom posttype page
			'advanced', // on page location
			'high' // priority
		);
	}

	/**
 * Prints the metabox content.
 *
 * @param WP_Post $post The object for the current post/page.
 */
	function ceu_award_certificate_meta_box_callback( $post ) {

		// Add a nonce field so we can check for it later.
		wp_nonce_field( 'ceu_award_certificate_save_meta_box_data', 'ceu_award_certificate_meta_box_nonce' );

		/*
		 * Use get_post_meta() to retrieve an existing value
		 * from the database and use the value for the form.
		 */
		$ceu_cert_after_earned = (string) get_post_meta( $post->ID, 'ceu_cert_after_earned', true );

		$meta_label      = __( 'Amount of %s needed to award the certificate', 'uncanny-ceu' );
		$meta_box_format = '<label for="ceu_cert_after_earned">' . $meta_label . '</label><input type="text" id="ceu_cert_after_earned" name="ceu_cert_after_earned" value="' . $ceu_cert_after_earned . '" size="25" />';

		echo sprintf( $meta_box_format, Utilities::credit_designation_label('plural', __( 'CEUs', 'uncanny-ceu' ) ) );

	}

	/**
	 * Prints the metabox content.
	 *
	 * @param WP_Post $post The object for the current post/page.
	 */
	function ceu_certificate_description_meta_box_callback( $post ) {

		// Add a nonce field so we can check for it later.
		wp_nonce_field( 'ceu_award_certificate_save_meta_box_data', 'ceu_award_certificate_meta_box_nonce' );

		/*
		 * Use get_post_meta() to retrieve an existing value
		 * from the database and use the value for the form.
		 */
		$ceu_cert_description = get_post_meta( $post->ID, 'ceu_cert_description', true );

		echo '<textarea style="width: 100%;" id="ceu_cert_description" name="ceu_cert_description">' . $ceu_cert_description . '</textarea>';

	}

	/**
	 * When the post is saved, saves our custom data.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	function ceu_value_save_meta_box_data( $post_id ) {

		/*
		 * We need to verify this came from our screen and with proper authorization,
		 * because the save_post action can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST['ceu_award_certificate_meta_box_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['ceu_award_certificate_meta_box_nonce'], 'ceu_award_certificate_save_meta_box_data' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions.
		if ( isset( $_POST['post_type'] ) && 'sfwd-certificates' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}

		} else {

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}

		/* OK, it's safe for us to save the data now. */

		// Make sure that it is set.
		if ( isset( $_POST['ceu_cert_after_earned'] ) ) {
			// Sanitize user input.
			$ceu_cert_after_earned = $_POST['ceu_cert_after_earned'];

			// Update the meta field in the database.
			update_post_meta( $post_id, 'ceu_cert_after_earned', $ceu_cert_after_earned );
		}

		// Make sure that it is set.
		if ( isset( $_POST['ceu_cert_after_course_completions'] ) ) {
			// Sanitize user input.
			$ceu_cert_after_course_completions = (string) json_encode( $_POST['ceu_cert_after_course_completions'] );

			// Update the meta field in the database.
			update_post_meta( $post_id, 'ceu_cert_after_course_completions', $ceu_cert_after_course_completions );
		}

		// Make sure that it is set.
		if ( isset( $_POST['ceu_cert_description'] ) ) {
			// Sanitize user input.
			$ceu_cert_description = (string)$_POST['ceu_cert_description'];

			// Update the meta field in the database.
			update_post_meta( $post_id, 'ceu_cert_description', $ceu_cert_description );
		}

	}


}