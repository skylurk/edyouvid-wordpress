<?php

/**
 * Register Blocks
 * render it with a callback function
 */

// Shortcode for CEU value earned per course
register_block_type( 'uncanny-ceu/uo-ceu-earned', [
	'attributes'      => [
		'courseId'      => [
			'type'    => 'string',
			'default' => '',
		],
		'userId'        => [
			'type'    => 'string',
			'default' => '',
		],
		'sinceRollover' => [
			'type'    => 'string',
			'default' => 'yes',
		],
	],
	'render_callback' => 'render_uo_ceu_earned',
] );

function render_uo_ceu_earned( $attributes ) {

	$course_id      = $attributes['courseId'];
	$user_id        = $attributes['userId'];
	$since_rollover = $attributes['sinceRollover'];

	// Start output
	ob_start();

	// Check if the class exists
	if ( class_exists( '\uncanny_ceu\CeuShortcodes' ) ) {

		$class = \uncanny_ceu\Utilities::get_class_instance( 'CeuShortcodes' );
		// Check if the course ID is empty
		echo $class->uo_ceu_earned( [
			'course-id'      => $course_id,
			'user-id'        => $user_id,
			'since-rollover' => $since_rollover,
		] );
	}

	// Get output
	$output = ob_get_clean();

	// Return output
	return $output;
}