<?php

/**
 * Register Blocks
 * render it with a callback function
 */

// Shortcode for CEU value earned cumulatively on or after roll over date
register_block_type( 'uncanny-ceu/uo-ceu-total-rollover', [
	'attributes'      => [
		'userId'   => [
			'type'    => 'string',
			'default' => '',
		],
		'rollover' => [
			'type'    => 'string',
			'default' => 'after',
		],
	],
	'render_callback' => 'render_uo_ceu_total_rollover',
] );

function render_uo_ceu_total_rollover( $attributes ) {

	$user_id  = $attributes['userId'];
	$rollover = $attributes['rollover'];

	// Start output
	ob_start();

	// Check if the class exists
	if ( class_exists( '\uncanny_ceu\CeuShortcodes' ) ) {

		$class = \uncanny_ceu\Utilities::get_class_instance( 'CeuShortcodes' );
		// Check if the course ID is empty
		echo $class->uo_ceu_total_rollover( [
			'user-id'  => $user_id,
			'rollover' => $rollover,
		] );
	}

	// Get output
	$output = ob_get_clean();

	// Return output
	return $output;
}