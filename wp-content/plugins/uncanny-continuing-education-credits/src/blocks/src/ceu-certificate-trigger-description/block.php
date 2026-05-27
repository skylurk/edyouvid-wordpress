<?php

/**
 * Register Blocks
 * render it with a callback function
 */

// Retrieve Certificate CEU trigger description
register_block_type( 'uncanny-ceu/uo-ceu-certificate-description', [
	'attributes'      => [
		'certificateId' => [
			'type'    => 'string',
			'default' => '',
		],
	],
	'render_callback' => 'render_uo_ceu_certificate_description',
] );

function render_uo_ceu_certificate_description( $attributes ) {

	$certificate_id = $attributes['certificateId'];

	// Start output
	ob_start();

	// Check if the class exists
	if ( class_exists( '\uncanny_ceu\CeuShortcodes' ) ) {

		$class = \uncanny_ceu\Utilities::get_class_instance( 'CeuShortcodes' );
		// Check if the course ID is empty
		echo $class->uo_ceu_certificate_description( [
			'certificate-id' => $certificate_id,
		] );
	}

	// Get output
	$output = ob_get_clean();

	// Return output
	return $output;
}