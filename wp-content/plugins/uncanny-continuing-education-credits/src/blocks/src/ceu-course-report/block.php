<?php

/**
 * Register Groups Reports Inerface
 * render it with a callback function
 */
register_block_type( 'uncanny-ceu/uo-ceu-course-report', [
	'attributes'      => array(
		'enhanced' => array(
			'type'    => 'string',
			'default' => null,
		),
		'columns'  => array(
			'type'    => 'string',
			'default' => null,
		),
		'mode'     => array(
			'type'    => 'string',
			'default' => null,
		),
		'add'      => array(
			'type'    => 'string',
			'default' => null,
		),
		'edit'     => array(
			'type'    => 'string',
			'default' => null,
		),
		'delete'   => array(
			'type'    => 'string',
			'default' => null,
		),
	),
	'render_callback' => 'render_uo_ceu_report_shortcode',
] );

/**
 * Render the shortcode for the block
 *
 * @param array $attributes
 *
 * @return string
 */
function render_uo_ceu_report_shortcode( $attributes ) {

	// Check if the class exists
	if ( ! class_exists( '\uncanny_ceu\FrontendCourseReport' ) ) {
		return '';
	}

	$params = array( 'enhanced', 'columns', 'mode', 'add', 'edit', 'delete' );
	$attrs  = array();
	foreach ( $params as $param ) {
		// Check if the attribute is set.
		if ( ! isset( $attributes[ $param ] ) ) {
			continue;
		}
		// Check if the attribute is empty.
		if ( empty( $attributes[ $param ] ) ) {
			continue;
		}

		// Skip off attributes.
		if ( 'off' === $attributes[ $param ] ) {
			continue;
		}

		// Set the attribute.
		$attrs[ $param ] = $attributes[ $param ];
	}

	// Start output
	ob_start();
	$class = \uncanny_ceu\Utilities::get_class_instance( 'FrontendCourseReport' );
	echo $class->uo_ceu_report_shortcode( $attrs );

	// Return output
	return ob_get_clean();
}
