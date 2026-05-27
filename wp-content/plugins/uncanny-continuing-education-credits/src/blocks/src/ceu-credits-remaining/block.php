<?php

/**
 * Register Blocks
 * render it with a callback function
 */

// Retrieve the difference between required credits and earned credits
register_block_type( 'uncanny-ceu/uo-ceu-credits-remaining', [
	'attributes'      => [
		'userId' => [
			'type'    => 'string',
			'default' => '',
		],
	],
	'render_callback' => 'render_uo_ceu_credits_remaining',
] );

function render_uo_ceu_credits_remaining( $attributes ) {

	$user_id = $attributes['userId'];

	// Start output
	ob_start();

	// Check if the class exists
	if ( class_exists( '\uncanny_ceu\CeuShortcodes' ) ) {

		$class = \uncanny_ceu\Utilities::get_class_instance( 'CeuShortcodes' );
		// Check if the course ID is empty
		echo $class->uo_ceu_credits_remaining( [
			'user-id' => $user_id,
		] );
	}

	// Get output
	$output = ob_get_clean();

	// Return output
	return $output;
}
