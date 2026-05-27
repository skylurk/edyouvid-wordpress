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
class CeuCourseMetaBox {


    /**
     * class constructor
     *
     */
    function __construct() {

        // Add CEU metabox
        add_action( 'add_meta_boxes', array( $this, 'add_ceu_value_metabox' ) );

        // Save CEU Metabox
        add_action( 'save_post', array( $this, 'ceu_value_save_meta_box_data' ) );

    }

    /**
     *  Add Metabox
     *
     */
    function add_ceu_value_metabox() {

        add_meta_box(
            'ceu_value', // metabox ID
			sprintf( __('%s Value', 'uncanny-ceu'), Utilities::credit_designation_label( 'singular', __( 'CEU', 'uncanny-ceu' ) )),
            array( $this, 'ceu_value_meta_box_callback'), // callback function
            'sfwd-courses', // custom posttype page
            'side', // on page location
            'high' // priority
        );
    }

    /**
     * Prints the metabox content.
     *
     * @param \WP_Post $post The object for the current post/page.
     */
     function ceu_value_meta_box_callback( $post ) {

        // Add a nonce field so we can check for it later.
        wp_nonce_field( 'ceu_value_save_meta_box_data', 'ceu_value_meta_box_nonce' );

        /*
         * Use get_post_meta() to retrieve an existing value
         * from the database and use the value for the form.
         */
        $value = (string)get_post_meta( $post->ID, 'ceu_value', true );

        $meta_label = __( 'Enter a number', 'uncanny-ceu' );
        $meta_box_format = '<label for="ceu_value">%s</label><input type="text" id="ceu_value" name="ceu_value" value="%s" size="25" />';

        echo sprintf( $meta_box_format, $meta_label, $value);

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
        if ( ! isset( $_POST['ceu_value_meta_box_nonce'] ) ) {
            return;
        }

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $_POST['ceu_value_meta_box_nonce'], 'ceu_value_save_meta_box_data' ) ) {
            return;
        }

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check the user's permissions.
        if ( isset( $_POST['post_type'] ) && 'sfwd-courses' == $_POST['post_type'] ) {

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
        if ( ! isset( $_POST['ceu_value'] ) ) {
            return;
        }

        // Sanitize user input.
        $my_data = sanitize_text_field( $_POST['ceu_value'] );

        // Update the meta field in the database.
        update_post_meta( $post_id, 'ceu_value', (float)$my_data );
    }


}
