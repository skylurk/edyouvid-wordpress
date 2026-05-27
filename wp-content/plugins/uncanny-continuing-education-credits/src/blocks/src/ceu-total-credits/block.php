<?php

/**
 * Register Blocks
 * render it with a callback function
 */

// Shortcode for CEU value earned cumulatively
register_block_type( 'uncanny-ceu/uo-ceu-total', [
	'attributes'      => [
		'userId' => [
			'type'    => 'string',
			'default' => '',
		],
	],
	'render_callback' => 'render_uo_ceu_total',
] );

function render_uo_ceu_total( $attributes ) {

	$user_id = $attributes['userId'];

	// Start output
	ob_start();

	// Check if the class exists
	if ( class_exists( '\uncanny_ceu\CeuShortcodes' ) ) {

		$class = \uncanny_ceu\Utilities::get_class_instance( 'CeuShortcodes' );
		// Check if the course ID is empty
		echo $class->uo_ceu_total( [
			'user-id' => $user_id,
		] );
	}

	// Get output
	$output = ob_get_clean();

	// Return output
	return $output;
}