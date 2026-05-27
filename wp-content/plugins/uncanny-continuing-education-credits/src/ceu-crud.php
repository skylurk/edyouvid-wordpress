<?php

namespace uncanny_ceu;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 *
 */
class CEU_CRUD {


	/**
	 * @var null
	 */
	private static $instance = null;

	/**
	 * @var string
	 */
	public $tbl = 'uo_ceu_records';
	/**
	 * @var string
	 */
	public $tbl_certs = 'uo_ceu_records_certs';
	/**
	 * @var string
	 */
	public $tbl_usermeta = 'uo_ceu_records_usermeta';

	/**
	 * @return self|null
	 */
	public static function get_instance() {

		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 *
	 */
	public function __construct() {

	}

	/**
	 * @param $data
	 *
	 * @return int
	 */
	public function insert( $data ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . $this->tbl,
			$data
		);

		return $wpdb->insert_id;
	}

	/**
	 * @param $ceu_ID
	 * @param $cert_id
	 * @param $time
	 * @param $is_multi
	 * @param $course_id
	 *
	 * @return int
	 */
	public function insert_cert( $ceu_ID, $cert_id = 0, $time = 0, $is_multi = 0, $course_id = 0 ) {
		global $wpdb;

		$data = array(
			'ceu_ID'         => $ceu_ID,
			'course_id'      => $course_id,
			'certificate_id' => $cert_id,
			'is_multi_cert'  => $is_multi,
			'date_earned'    => 0 !== $time ? $time : time(),
		);

		$wpdb->insert(
			$wpdb->prefix . $this->tbl_certs,
			$data
		);

		return $wpdb->insert_id;
	}

	/**
	 * @param $ceu_ID
	 * @param $meta_key
	 * @param $meta_value
	 *
	 * @return int
	 */
	public function insert_usermeta( $ceu_ID, $meta_key = '', $meta_value = '' ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . $this->tbl_usermeta,
			array(
				'ceu_ID'     => $ceu_ID,
				'meta_key'   => $meta_key,
				'meta_value' => $meta_value,
			)
		);

		return $wpdb->insert_id;
	}


	public function delete() {

	}

	public function update() {

	}

	public function select() {

	}

	/**
	 * @param $user_id
	 * @param $key
	 * @param $value
	 *
	 * @return void
	 */
	public function add_usermeta( $user_id, $key, $value ) {
		add_user_meta( $user_id, $key, $value );
	}

	/**
	 * @param $user_id
	 * @param $key
	 * @param $value
	 *
	 * @return void
	 */
	public function update_usermeta( $user_id, $key, $value ) {
		update_user_meta( $user_id, $key, $value );
	}

	/**
	 * @param $user_id
	 * @param $key
	 *
	 * @return void
	 */
	public function delete_usermeta( $user_id, $key ) {
		delete_user_meta( $user_id, $key );
	}

	/**
	 * @param $user_id
	 * @param $key
	 * @param $single
	 *
	 * @return mixed
	 */
	public function get_usermeta( $user_id, $key, $single = true ) {
		return get_user_meta( $user_id, $key, $single );
	}

	/**
	 * @param $user_id
	 * @param $time
	 * @param $course_id
	 *
	 * @return string|null
	 */
	public function get_ceu_id( $user_id, $time, $course_id = 0 ) {
		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->prefix}{$this->tbl} WHERE user_id = %d AND course_id = %d AND earned_timestamp = %d",
				$user_id,
				$course_id,
				$time
			)
		);
	}
}




