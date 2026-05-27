<?php

namespace uncanny_learndash_codes;

use FrmFieldType;

/**
 * @since 3.0
 */
class FrmFieldUncannyCode extends FrmFieldType {

	/**
	 * @var string
	 * @since 3.0
	 */
	protected $type = 'uncanny_code';

	/**
	 * @var bool
	 * @since 3.0
	 */
	protected $holds_email_values = true;

	/**
	 * @param array $args
	 * @param array $shortcode_atts
	 *
	 * @return string
	 */
	public function front_field_input( $args, $shortcode_atts ) {
		$input_html = $this->get_field_input_html_hook( $this->field );
		$this->add_aria_description( $args, $input_html );

		return '<input type="text" name="' . esc_attr( $args['field_name'] ) . '" id="' . esc_attr( $args['html_id'] ) . '" ' . $input_html . ' />';
	}

	/**
	 * @return array
	 */
	protected function field_settings_for_type() {
		return array(
			'size'           => true,
			'clear_on_focus' => true,
			'invalid'        => true,
			'default'        => false,
		);
	}
}
