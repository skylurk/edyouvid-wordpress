<?php

/**
 * Register Blocks
 * render it with a callback function
 */

// Shortcode for CEU value that cab be earned for course
register_block_type( 'uncanny-ceu/uo-ceu-available', [
	'attributes'      => [
		'courseId' => [
			'type'    => 'string',
			'default' => '',
		],
	],
	'render_callback' => 'render_uo_ceu_available',
] );

function render_uo_ceu_available( $attributes ) {

	$course_id = $attributes['courseId'];

	// Start output
	ob_start();

	// Check if the class exists
	if ( class_exists( '\uncanny_ceu\CeuShortcodes' ) ) {

		$class = \uncanny_ceu\Utilities::get_class_instance( 'CeuShortcodes' );
		// Check if the course ID is empty
		echo $class->uo_ceu_available( [
			'course-id' => $course_id,
		] );
	}

	// Get output
	$output = ob_get_clean();

	// Return output
	return $output;
}