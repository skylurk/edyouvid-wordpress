<?php

namespace uncanny_pro_toolkit;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 *
 */
class Uncanny_Shortcodes_Parser extends Boot {


	/**
	 *
	 */
	public function __construct() {
		add_filter( 'uo_generate_course_certificate_content', array( $this, 'inject_user_id_in_cert_content' ), 33, 3 );
		add_filter( 'uo_generate_group_certificate_content', array( $this, 'inject_user_id_in_cert_content' ), 33, 3 );
	}

	/**
	 * @param $cert_content
	 * @param $user_id
	 * @param $ignore_this
	 *
	 * @return array|string|string[]|null
	 */
	public function inject_user_id_in_cert_content( $cert_content, $user_id, $ignore_this ) {
		$cert_content = preg_replace( '/(\[uo_ceu_total_rollover)/', '[uo_ceu_total_rollover user-id="' . $user_id . '"', $cert_content );
		$cert_content = preg_replace( '/(\[uo_ceu_total)/', '[uo_ceu_total user-id="' . $user_id . '"', $cert_content );

		return $cert_content;
	}

}

new Uncanny_Shortcodes_Parser();