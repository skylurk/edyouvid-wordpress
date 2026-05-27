<?php

namespace uncanny_learndash_codes;

use Forminator_Field;
use Uncanny_Automator\Utilities;

/**
 * Class Forminator_Text
 *
 * @since 1.0
 */
class Forminator_Codes_Field extends Forminator_Field {

	/**
	 * @var string
	 */
	public $name = 'uncanny_codes';

	/**
	 * @var string
	 */
	public $slug = 'uncanny_codes';

	/**
	 * @var string
	 */
	public $element_id = 'uncanny_codes';

	/**
	 * @var string
	 */
	public $type = 'text';

	/**
	 * @var int
	 */
	public $position = 999;

	/**
	 * @var array
	 */
	public $options = array();

	/**
	 * @var string
	 */
	public $category = 'standard';

	/**
	 * @var bool
	 */
	public $is_input = true;

	/**
	 * @var bool
	 */
	public $has_counter = true;

	/**
	 * @var string
	 */
	public $icon = 'sui-icon-code';

	/**
	 * Forminator_Text constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		parent::__construct();

		$this->name = __( 'Uncanny Codes', 'uncanny-learndash-codes' );
	}

	/**
	 * Field defaults
	 *
	 * @return array
	 * @since 1.0
	 */
	public function defaults() {
		return array(
			'input_type'  => 'uncanny_codes',
			'limit_type'  => 'characters',
			'field_label' => __( 'Enter code', 'uncanny-learndash-codes' ),
			'placeholder' => __( 'Enter the code provided', 'uncanny-learndash-codes' ),
		);
	}

	/**
	 * Sanitize data
	 *
	 * @param array $field
	 * @param array|string $data - the data to be sanitized.
	 *
	 * @return array|string $data - the data after sanitization
	 * @since 1.0.2
	 *
	 */
	public function sanitize( $field, $data ) {
		$original_data = $data;
		// Sanitize.
		$data = forminator_sanitize_field( $data );

		return apply_filters( 'forminator_field_text_sanitize', $data, $field, $original_data );
	}
}
