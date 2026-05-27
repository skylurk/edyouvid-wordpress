<?php

/**
 * Register uo time
 * render it with a callback function
 */

use uncanny_learndash_codes\Shortcodes;

register_block_type( 'uncanny-learndash-codes/uo-user-redeem-code', [
	'attributes'      => [
		'redirect' => [
			'type'    => 'string',
			'default' => '',
		],
	],
	'render_callback' => 'render_user_redeem_code',
] );

function render_user_redeem_code( $attributes ) {

	// Start output.
	ob_start();

	// Check if the class exists.
	if ( class_exists( '\uncanny_learndash_codes\Shortcodes' ) ) {
		// Check if the course ID is empty.
		echo Shortcodes::user_redeem_code_callback( [
			'redirect' => $attributes['redirect'],
		] );
	}

	// Get output.
	$output = ob_get_clean();

	// Return output.
	return $output;
}
