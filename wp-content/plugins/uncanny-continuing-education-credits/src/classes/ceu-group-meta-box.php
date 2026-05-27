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
class CeuGroupMetaBox {


	/**
	 * class constructor
	 *
	 */
	function __construct() {

		$roll_over_date = get_option( 'ceu_rollover_date', '' );

		if ( '' !== $roll_over_date ) {
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
			'group_credit_required', // metabox ID
			sprintf( 'Required %1$s', Utilities::credit_designation_label('plural', __( 'CEUs', 'uncanny-ceu' ) ) ), // metabox Title
			array( $this, 'group_credit_required_callback' ), // callback function
			'groups', // custom posttype page
			'side', // on page location
			'high' // priority
		);
	}

	/**
 * Prints the metabox content.
 *
 * @param WP_Post $post The object for the current post/page.
 */
	function group_credit_required_callback( $post ) {

		// Add a nonce field so we can check for it later.
		wp_nonce_field( 'group_credit_required_save_meta_box_data', 'group_credit_required_meta_box_nonce' );

		/*
		 * Use get_post_meta() to retrieve an existing value
		 * from the database and use the value for the form.
		 */
		$group_credit_required = (string) get_post_meta( $post->ID, 'group_credit_required', true );

		$meta_box_format = '<input type="text" id="ceu_cert_after_earned" name="group_credit_required" value="' . $group_credit_required . '" size="25" />';

		echo sprintf( $meta_box_format, Utilities::credit_designation_label('plural', __( 'CEUs', 'uncanny-ceu' ) ) );

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
		if ( ! isset( $_POST['group_credit_required_meta_box_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['group_credit_required_meta_box_nonce'], 'group_credit_required_save_meta_box_data' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions.
		if ( isset( $_POST['post_type'] ) && 'sfwd-groups' == $_POST['post_type'] ) {

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
		if ( isset( $_POST['group_credit_required'] ) ) {
			// Sanitize user input.
			$group_credit_required = sanitize_text_field( $_POST['group_credit_required'] );

			// Update the meta field in the database.
			update_post_meta( $post_id, 'group_credit_required', $group_credit_required );
		}
	}


}