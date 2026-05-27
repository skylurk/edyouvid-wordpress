<?php

namespace uncanny_learndash_codes;

/**
 * Class GenerateCodes
 *
 * @package uncanny_learndash_codes
 */
class GenerateCodes extends Config {

	/**
	 * @var array|null|object
	 */
	public static $courses;
	/**
	 * @var array|null|object
	 */
	public static $rejected_batch_codes = array();
	/**
	 * @var array|null|object
	 */
	public static $groups;

	/**
	 * @var array|null|object
	 */
	public static $num_coupons_added;
	/**
	 * @var array|null|object
	 */
	public static $num_codes_requested;
	/**
	 * @var
	 */
	public static $generation_type;
	/**
	 * @var mixed|void
	 */
	public static $chars;
	/**
	 * @var
	 */
	public static $manual_code_length;
	/**
	 * @var
	 */
	protected $table;

	/**
	 * GenerateCodes constructor.
	 */
	public function __construct() {
		self::$chars              = apply_filters( 'ulc_code_characters', 'abcdefghjkmnpqrstuvwxyz123456789ABCDEFGHJKLMNPQRSTUVWXYZ' );
		self::$manual_code_length = apply_filters( 'ulc_manual_code_length', 30 );

		if ( is_admin() ) {
			parent::__construct();
			self::$courses = LearnDash::get_courses();
			self::$groups  = LearnDash::get_groups();
		}
		add_action( 'admin_init', array( $this, 'process_submit' ), 9 );

		if ( SharedFunctionality::ulc_filter_has_var( 'msg' ) && 'success' === sanitize_text_field( SharedFunctionality::ulc_filter_input( 'msg' ) ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'success_notice' ) );
		}

		if ( SharedFunctionality::ulc_filter_has_var( 'msg' ) && 'failed' === SharedFunctionality::ulc_filter_input( 'msg' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'failed_notice' ) );
		}
	}

	/**
	 * @param     $args
	 * @param int $batch_code_amount
	 *
	 * @return array
	 */
	public static function get_unique_codes( $args, $batch_code_amount = 1 ) {
		$dashes         = $args['dashes'];
		$prefix         = $args['prefix'];
		$suffix         = $args['suffix'];
		$code_length    = absint( $args['code_length'] ) > 180 ? 165 - absint( strlen( $prefix ) ) - absint( strlen( $suffix ) ) : absint( $args['code_length'] );
		$character_type = $args['character_type'];
		$batch_codes    = array();

		if ( ! empty( $character_type ) ) {
			self::$chars = '';
			if ( in_array( 'numbers', $character_type ) ) {
				self::$chars .= '123456789';
			}
			if ( in_array( 'uppercase-letters', $character_type ) ) {
				self::$chars .= 'ABCDEFGHJKLMNPQRSTUVWXYZ';
			}
			if ( in_array( 'lowercase-letters', $character_type ) ) {
				self::$chars .= 'abcdefghjkmnpqrstuvwxyz';
			}
		}

		for ( $i = 0; $i < $batch_code_amount; $i ++ ) {
			$coupon          = $prefix . self::generate_random_string( $code_length ) . $suffix;
			$new_batch_codes = array();
			$pointer         = 0;
			if ( ! empty( $coupon ) ) {
				foreach ( $dashes as $dash ) {
					$dash = (int) $dash;
					if ( $dash ) {
						if ( strlen( $coupon ) < $pointer + $dash ) {
							$dash = strlen( $coupon ) - $pointer;
						}
						if ( $pointer < strlen( $coupon ) ) {
							$new_batch_codes[] = substr( $coupon, $pointer, $dash );
							$pointer           = $pointer + $dash;
						}
					}
				}

				if ( $pointer < strlen( $coupon ) ) {
					$new_batch_codes[] = substr( $coupon, $pointer );
				}
				$batch_codes[] = implode( '-', $new_batch_codes );
			}
		}

		return $batch_codes;
	}

	/**
	 * @param $length
	 *
	 * @return string
	 */
	private static function generate_random_string( $length ) {
		$string = '';
		for ( $i = 0; $i < $length; $i ++ ) {
			$string .= self::$chars[ mt_rand( 0, strlen( self::$chars ) - 1 ) ];
		}

		return $string;
	}

	/**
	 *
	 */
	public static function success_notice() {
		$codes_generated = '';
		if ( SharedFunctionality::ulc_filter_has_var( 'codes_generated' ) ) {
			$codes_generated = absint( SharedFunctionality::ulc_filter_input( 'codes_generated' ) );
		}

		$message = '<h4>' . sprintf( esc_html__( '%s Codes created listed below.', 'uncanny-learndash-codes' ), $codes_generated ) . '</h4>';
		if ( self::$num_coupons_added < self::$num_codes_requested && 'auto' === self::$generation_type ) {
			$message = '<h4>' . sprintf( esc_html__( 'Only %s unique codes were generated. Try increasing the number of characters to generate more codes.', 'uncanny-learndash-codes' ), self::$num_coupons_added ) . '</h4>';
		}

		if ( 'manual' === self::$generation_type && ! empty( self::$rejected_batch_codes ) ) {
			$message = '<h4>' . sprintf( __( 'Only %s unique codes were added out of %s codes. The following codes were rejected:<br />%s.<br /><a href="%s">Manage Codes</a>', 'uncanny-learndash-codes' ), self::$num_coupons_added, (int) self::$num_coupons_added + (int) count( self::$rejected_batch_codes ), join( '<br />', self::$rejected_batch_codes ), esc_attr( add_query_arg( 'page', 'uncanny-learndash-codes' ) ) ) . '</h4>';
		}

		if ( self::$num_coupons_added < self::$num_codes_requested && 'manual' === self::$generation_type && empty( self::$rejected_batch_codes ) ) {
			$message = '<h4>' . sprintf( __( 'Only %s unique codes were added out of %s codes. <a href="%s">Manage Codes</a>', 'uncanny-learndash-codes' ), self::$num_coupons_added, self::$num_codes_requested, esc_attr( add_query_arg( 'page', 'uncanny-learndash-codes' ) ) ) . '</h4>';
		}
		?>
		<div class="updated notice">
			<p><?php echo $message; ?></p>
		</div>
		<?php
	}

	/**
	 *
	 */
	public static function failed_notice() {
		$message = sprintf( '<h4>%s</h4>', esc_html__( '0 Codes Created! None of the generated codes were unique.', 'uncanny-learndash-codes' ) );
		if ( 'manual' === self::$generation_type && ! empty( self::$rejected_batch_codes ) ) {
			$message = sprintf( '<h4>%s</h4><br />%s<br />', esc_html__( 'The following code(s) were rejected:', 'uncanny-learndash-codes' ), join( '<br />', self::$rejected_batch_codes ) );
		}
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php echo $message; ?></p>
		</div>
		<?php
	}

	/**
	 *
	 */
	public function process_submit() {
		if ( SharedFunctionality::ulc_filter_has_var( '_custom_wpnonce', INPUT_POST ) && wp_verify_nonce( SharedFunctionality::ulc_filter_input( '_custom_wpnonce', INPUT_POST ), Config::get_project_name() ) ) {
			if ( SharedFunctionality::ulc_filter_has_var( 'edit_group', INPUT_POST ) && 'yes' === sanitize_text_field( SharedFunctionality::ulc_filter_input( 'edit_group', INPUT_POST ) ) ) {
				$this->process_form_edit();
			} else {
				$this->process_from_submit();
			}
		}
	}

	/**
	 * @param $form_data
	 */
	public function process_form_edit() {
		$group_id   = absint( SharedFunctionality::ulc_filter_input( 'group_id', INPUT_POST ) );
		$dependency = sanitize_text_field( SharedFunctionality::ulc_filter_input( 'dependency', INPUT_POST ) ); // learndash, automator.
		//LearnDash specific
		$ld_type = 'default';
		if ( SharedFunctionality::ulc_filter_has_var( 'learndash-content', INPUT_POST ) ) {
			if ( 'learndash-courses' === SharedFunctionality::ulc_filter_input( 'learndash-content', INPUT_POST ) ) {
				$ld_type = 'course';
			} elseif ( 'learndash-groups' === SharedFunctionality::ulc_filter_input( 'learndash-content', INPUT_POST ) ) {
				$ld_type = 'group';
			}
		}

		$coupon_for         = 'automator' === $dependency ? $dependency : $ld_type;
		$coupon_courses     = ! empty( SharedFunctionality::ulc_filter_input_array( 'learndash-courses', INPUT_POST ) ) ? (array) SharedFunctionality::ulc_filter_input_array( 'learndash-courses', INPUT_POST ) : array();
		$coupon_group       = ! empty( SharedFunctionality::ulc_filter_input_array( 'learndash-groups', INPUT_POST ) ) ? (array) SharedFunctionality::ulc_filter_input_array( 'learndash-groups', INPUT_POST ) : array();
		$coupon_paid_unpaid = SharedFunctionality::ulc_filter_has_var( 'learndash-code-type', INPUT_POST ) ? SharedFunctionality::ulc_filter_input( 'learndash-code-type', INPUT_POST ) : 'default'; // paid, unpaid, default.

		$coupon_max_usage = sanitize_text_field( SharedFunctionality::ulc_filter_input( 'coupon-max-usage', INPUT_POST ) );
		$expiry_date      = sanitize_text_field( SharedFunctionality::ulc_filter_input( 'expiry-date', INPUT_POST ) );
		$expiry_time      = sanitize_text_field( SharedFunctionality::ulc_filter_input( 'expiry-time', INPUT_POST ) );
		$expiry           = '0000-00-00 00:00:00';
		if ( ! empty( $expiry_date ) ) {
			if ( ! empty( $expiry_time ) ) {
				$expiry = date_i18n( 'Y-m-d H:i:s', strtotime( $expiry_date . ' ' . $expiry_time ) );
			} else {
				$expiry = date_i18n( 'Y-m-d 23:59:59', strtotime( $expiry_date ) );
			}
		}

		if ( 'course' === $coupon_for ) {
			$linked_to = (array) $coupon_courses;
		} elseif ( 'group' === $coupon_for ) {
			$linked_to = (array) $coupon_group;
		} else {
			$linked_to = array();
		}

		global $wpdb;

		if ( 'automator' === (string) $dependency ) {
			// If the batch is linked with a Woo Product, do not allow max usage to be changed.
			$product_id = SharedFunctionality::get_products_by_batch_id( $group_id );
			if ( ! empty( $product_id ) || 0 !== absint( $product_id ) ) {
				$coupon_max_usage = 1;
			}
		}

		$update_data   = array(
			'code_for'        => $coupon_for,
			'paid_unpaid'     => 'automator' === $dependency ? '' : $coupon_paid_unpaid,
			'expire_date'     => $expiry,
			'linked_to'       => maybe_serialize( $linked_to ),
			'issue_max_count' => $coupon_max_usage,
		);
		$update_sanity = array(
			//'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%d',
		);

		$wpdb->update( $wpdb->prefix . Config::$tbl_groups,
			$update_data,
			array( 'ID' => $group_id ),
			$update_sanity,
			array( '%d' ) );

		do_action( 'ulc_codes_group_modified', $group_id, $update_data );
		wp_safe_redirect( admin_url( 'admin.php?page=uncanny-learndash-codes&message=Codes+Modified' ) );
		exit();
	}

	/**
	 * @param $form_data
	 */
	public function process_from_submit() {
		$batch_name = sanitize_text_field( SharedFunctionality::ulc_filter_input( 'batch-name', INPUT_POST ) );
		$dependency = sanitize_text_field( SharedFunctionality::ulc_filter_input( 'dependency', INPUT_POST ) ); // learndash, automator.
		// LearnDash specific.
		$ld_type = 'default';
		if ( SharedFunctionality::ulc_filter_has_var( 'learndash-content', INPUT_POST ) ) {
			if ( 'learndash-courses' === SharedFunctionality::ulc_filter_input( 'learndash-content', INPUT_POST ) ) {
				$ld_type = 'course';
			} elseif ( 'learndash-groups' === SharedFunctionality::ulc_filter_input( 'learndash-content', INPUT_POST ) ) {
				$ld_type = 'group';
			}
		}
		$coupon_courses    = ! empty( SharedFunctionality::ulc_filter_input_array( 'learndash-courses', INPUT_POST ) ) ? (array) SharedFunctionality::ulc_filter_input_array( 'learndash-courses', INPUT_POST ) : array();
		$coupon_groups     = ! empty( SharedFunctionality::ulc_filter_input_array( 'learndash-groups', INPUT_POST ) ) ? (array) SharedFunctionality::ulc_filter_input_array( 'learndash-groups', INPUT_POST ) : array();
		$ld_code_paid_type = SharedFunctionality::ulc_filter_has_var( 'learndash-code-type', INPUT_POST ) ? SharedFunctionality::ulc_filter_input( 'learndash-code-type', INPUT_POST ) : 'default'; // paid, unpaid, default.
		// for every batch.
		$max_usage       = SharedFunctionality::ulc_filter_has_var( 'coupon-max-usage', INPUT_POST ) ? absint( SharedFunctionality::ulc_filter_input( 'coupon-max-usage', INPUT_POST ) ) : 1;
		$expire_date     = SharedFunctionality::ulc_filter_has_var( 'expiry-date', INPUT_POST ) ? sanitize_text_field( SharedFunctionality::ulc_filter_input( 'expiry-date', INPUT_POST ) ) : '';
		$expire_time     = SharedFunctionality::ulc_filter_has_var( 'expiry-time', INPUT_POST ) ? sanitize_text_field( SharedFunctionality::ulc_filter_input( 'expiry-time', INPUT_POST ) ) : '';
		$generation_type = SharedFunctionality::ulc_filter_has_var( 'generation-type', INPUT_POST ) ? sanitize_text_field( SharedFunctionality::ulc_filter_input( 'generation-type', INPUT_POST ) ) : 'auto'; // auto, manual.
		$prefix          = SharedFunctionality::ulc_filter_has_var( 'coupon-prefix', INPUT_POST ) ? sanitize_text_field( SharedFunctionality::ulc_filter_input( 'coupon-prefix', INPUT_POST ) ) : '';
		$suffix          = SharedFunctionality::ulc_filter_has_var( 'coupon-suffix', INPUT_POST ) ? sanitize_text_field( SharedFunctionality::ulc_filter_input( 'coupon-suffix', INPUT_POST ) ) : '';
		$coupon_amount   = SharedFunctionality::ulc_filter_has_var( 'coupon-amount', INPUT_POST ) ? sanitize_text_field( SharedFunctionality::ulc_filter_input( 'coupon-amount', INPUT_POST ) ) : 1;
		$code_length     = SharedFunctionality::ulc_filter_has_var( 'coupon-length', INPUT_POST ) ? sanitize_text_field( SharedFunctionality::ulc_filter_input( 'coupon-length', INPUT_POST ) ) - strlen( $prefix ) - strlen( $suffix ) : 20;
		$dash            = SharedFunctionality::ulc_filter_has_var( 'coupon-dash', INPUT_POST ) ? sanitize_text_field( SharedFunctionality::ulc_filter_input( 'coupon-dash', INPUT_POST ) ) : '4-4-4-4-4';
		$dashes          = explode( '-', $dash );
		$character_type  = ! empty( SharedFunctionality::ulc_filter_input_array( 'coupon-character-type', INPUT_POST, array(
			'filter' => FILTER_UNSAFE_RAW,
			'flags'  => FILTER_REQUIRE_ARRAY,
		) ) ) ? SharedFunctionality::ulc_filter_input_array( 'coupon-character-type', INPUT_POST, array(
			'filter' => FILTER_UNSAFE_RAW,
			'flags'  => FILTER_REQUIRE_ARRAY,
		) ) : array(
			'uppercase-letters',
			'numbers',
		);

		$custom_codes          = sanitize_textarea_field( SharedFunctionality::ulc_filter_input( 'manual-codes', INPUT_POST ) );
		self::$generation_type = $generation_type;
		$args                  = array(
			'generation_type' => $generation_type,
			'coupon_amount'   => $coupon_amount,
			'custom_codes'    => $custom_codes,
			'dashes'          => $dashes,
			'prefix'          => $prefix,
			'suffix'          => $suffix,
			'code_length'     => $code_length,
			'character_type'  => $character_type,
		);
		// Sanitize values
		$data = array(
			'coupon-amount'         => $coupon_amount,
			'coupon-prefix'         => $prefix,
			'coupon-suffix'         => $suffix,
			'coupon-dash'           => $dash,
			'coupon-length'         => $code_length,
			'generation-type'       => $generation_type,
			'manual-codes'          => $custom_codes,
			'dependency'            => isset( $dependency ) ? $dependency : 'general',
			'coupon-for'            => 'automator' === $dependency ? $dependency : $ld_type,
			'group-name'            => $batch_name,
			'coupon-courses'        => $coupon_courses,
			'coupon-group'          => $coupon_groups,
			'expiry-date'           => $expire_date,
			'expiry-time'           => $expire_time,
			'coupon-paid-unpaid'    => 'automator' === $dependency ? 'default' : $ld_code_paid_type,
			'coupon-max-usage'      => $max_usage,
			'coupon-character-type' => $character_type,
		);

		$codes = array();
		if ( 'manual' === $generation_type ) {
			$codes = self::validate_manual_codes( $args );
		} else {
			self::$num_codes_requested = $coupon_amount;
		}

		$group_id                = Database::add_code_group_batch( $data );
		$inserted                = Database::add_codes_to_batch( $group_id, $codes, $args );
		self::$num_coupons_added = $inserted;


		do_action( 'ulc_codes_group_generated', $group_id, $inserted );
		if ( (int) self::$num_coupons_added === (int) self::$num_codes_requested && empty( self::$rejected_batch_codes ) ) {
			$redirect_url = admin_url( 'admin.php?page=uncanny-learndash-codes' ) . "&msg=success&codes_generated={$inserted}&group_id={$group_id}";
			$redirect_url = apply_filters( 'ulc_codes_batch_redirect_url', $redirect_url, $group_id, $data );
			wp_safe_redirect( $redirect_url );
			exit();
		} else {
			add_action( 'admin_notices', array( __CLASS__, 'success_notice' ) );
		}

		if ( 0 === self::$num_coupons_added ) {
			add_action( 'admin_notices', array( __CLASS__, 'failed_notice' ) );
		}
	}

	/**
	 * @param $args
	 *
	 * @return array
	 */
	public static function validate_manual_codes( $args ) {
		$custom_codes       = $args['custom_codes'];
		$batch_codes        = array();
		$passed_codes       = array();
		$custom_batch_codes = explode( PHP_EOL, $custom_codes );
		if ( $custom_batch_codes ) {
			foreach ( $custom_batch_codes as $k => $code ) {
				if ( strlen( trim( $code ) ) >= 4 && strlen( trim( $code ) ) <= self::$manual_code_length ) {
					if ( empty( trim( $code ) ) ) {
						unset( $custom_batch_codes[ $k ] );
					} else {
						preg_match( '/[^A-Za-z0-9\-]/', trim( $code ), $matches );
						if ( empty( $matches ) && ! in_array( trim( $code ), $passed_codes ) ) {
							$passed_codes[] = sanitize_text_field( trim( $code ) );
						} else {
							self::$rejected_batch_codes[] = $code;
						}
					}
				} else {
					self::$rejected_batch_codes[] = $code;
				}
			}
		}
		$batch_code_amount         = count( $passed_codes );
		self::$num_codes_requested = $batch_code_amount;
		for ( $i = 0; $i < $batch_code_amount; $i ++ ) {
			$coupon = isset( $passed_codes[ $i ] ) ? $passed_codes[ $i ] : '';
			if ( ! empty( $coupon ) ) {
				// reverify.
				preg_match( '/[^A-Za-z0-9\-]/', trim( $coupon ), $matches );
				if ( empty( $matches ) && ! in_array( $coupon, $batch_codes ) ) {
					$batch_codes[] = $coupon;
				} elseif ( ! in_array( $coupon, self::$rejected_batch_codes ) ) {
					self::$rejected_batch_codes[] = $coupon;
				}
			}
		}

		return $batch_codes;
	}

}
