<?php
/**
 * XSS Functions
 */

/**
 * Filter Input XSS.
 *
 * @param $field
 * @param $type
 *
 * @return mixed
 */
function ultc_filter_input( $field, $type = INPUT_GET ) {
	$input = filter_input( $type, $field );
	return is_null( $input ) ? null : sanitize_text_field( $input );
}

/**
 * Filter input array - get the $_POST/$_GET/$_REQUEST array variable
 *
 * @param $type
 * @param null $variable
 * @param $flags
 *
 * @return mixed null if not found, array if found
*/
function ultc_filter_input_array( $variable = null, $type = INPUT_GET, $flags = array() ) {
	if ( empty( $flags ) ) {
		$flags = array(
			'filter' => FILTER_UNSAFE_RAW,
			'flags'  => FILTER_REQUIRE_ARRAY,
		);
	}
	/*
	 * View input types: https://www.php.net/manual/en/function.filter-input.php
	 * View flags at: https://www.php.net/manual/en/filter.filters.sanitize.php
	 */
	$args = array( $variable => $flags );
	$val  = filter_input_array( $type, $args );

	return is_null( $val ) ? null : ( isset( $val[ $variable ] ) ? $val[ $variable ] : array() );
}

/**
 * Check if variable exists in input.
 *
 * @param $field
 * @param $type
 *
 * @return mixed
 */
function ultc_filter_has_var( $variable = null, $type = INPUT_GET ) {
	return filter_has_var( $type, $variable );
}

/**
 * Get variable from input if it exists or return default.
 *
 * @param string $field
 * @param mixed  $default
 * @param string $type
 *
 * @return mixed
 */
function ultc_get_filter_var( $variable = null, $default = null, $type = INPUT_GET ) {
	if ( ! ultc_filter_has_var( $variable, $type ) ) {
		return $default;
	}

	return ultc_filter_input( $variable, $type );
}

/**
 * Get current server request type constant.
 *
 * @return int
 */
function ultc_current_request_type() {
	$request_method = ultc_filter_input( 'REQUEST_METHOD', INPUT_SERVER );

	//Fallback - Detect REQUEST METHOD.
	if( empty($request_method) && isset($_SERVER['REQUEST_METHOD']) ) {
		$request_method = filter_var( $_SERVER['REQUEST_METHOD'], FILTER_SANITIZE_SPECIAL_CHARS );
	}

	// Ensure the request method is uppercase.
	$request_method = strtoupper( $request_method ); 

	switch ( $request_method ) {
		case 'GET':
			return INPUT_GET;
		case 'POST':
			return INPUT_POST;
		case 'PUT':
			return INPUT_PUT;
		case 'DELETE':
			return INPUT_DELETE;
		default:
			return INPUT_GET;
	}
}
