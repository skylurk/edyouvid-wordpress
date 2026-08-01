<?php

namespace uncanny_learndash_codes;

/**
 * Class SharedFunctionality
 * @package uncanny_learndash_codes
 */
class SharedFunctionality {
	/**
	 * @var $__instance
	 */
	public static $__instance;

	/**
	 * SharedFunctions constructor.
	 */
	public function __construct() {
	}

	/**
	 * @return SharedFunctionality
	 */
	public static function get_instance() {
		// check if instance is available.
		if ( null === self::$__instance ) {
			// create new instance if not.
			self::$__instance = new self();
		}

		return self::$__instance;
	}

	/**
	 * @param string $plugin
	 *
	 * @return bool
	 */
	public static function is_active( $plugin = 'learndash' ) {

		switch ( $plugin ) {
			case 'woocommerce':
				if ( class_exists( 'WooCommerce' ) ) {
					return true;
				}
				if ( function_exists( 'WC' ) ) {
					return true;
				}
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
				if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
					return true;
				} elseif ( is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) {
					return true;
				}
				break;
			case 'learndash':
				global $learndash_post_types;

				if ( isset( $learndash_post_types ) ) {
					return true;
				}
				break;
			case 'automator':
				if ( class_exists( '\Uncanny_Automator\InitializePlugin' ) ) {
					return true;
				}
				break;
			default:
				return true;
				break;
		}
	}

	/**
	 * @param      $product_id
	 * @param null $codes_group
	 *
	 * @return string|null
	 */
	public static function get_available_codes_by_group_id( $product_id, $codes_group = null ) {
		if ( null == $codes_group ) {
			$codes_group = self::get_codes_group_id_by_product( $product_id );
		}
		global $wpdb;
		$table1 = $wpdb->prefix . Config::$tbl_codes;
		$table2 = $wpdb->prefix . Config::$tbl_codes_usage;
		$sql    = $wpdb->prepare( "SELECT count(ID) FROM {$table1} WHERE code_group=%d AND order_id=%d AND ID NOT IN (SELECT code_id FROM {$table2})", $codes_group, 0 );

		return $wpdb->get_var( $sql );
	}

	/**
	 * @param $product_id
	 *
	 * @return int
	 */
	public static function get_codes_group_id_by_product( $product_id ) {
		return $current_codes_group = absint( get_post_meta( $product_id, 'codes_group_name', true ) );

	}

	/**
	 * @param null $codes_group
	 *
	 * @return string|null
	 */
	public static function get_products_by_batch_id( $codes_group = null ) {
		if ( null == $codes_group ) {
			return null;
		}

		global $wpdb;
		$table = $wpdb->prefix . Config::$tbl_groups;
		$sql   = $wpdb->prepare( "SELECT product_id FROM {$table} WHERE ID=%d", $codes_group );

		return $wpdb->get_var( $sql );
	}

	/**
	 * @param string $type
	 * @param null $variable
	 * @param string $flags
	 *
	 * @return mixed
	 */
	public static function ulc_filter_input( $variable = null, $type = INPUT_GET, $flags = FILTER_UNSAFE_RAW ) {
		/*
		 * View input types: https://www.php.net/manual/en/function.filter-input.php
		 * View flags at: https://www.php.net/manual/en/filter.filters.sanitize.php
		 */
		return filter_input( $type, $variable, $flags );
	}


	/**
	 * @param string $type
	 * @param null $variable
	 * @param string $flags
	 *
	 * @return mixed
	 */
	public static function ulc_filter_has_var( $variable = null, $type = INPUT_GET ) {
		return filter_has_var( $type, $variable );
	}

	/**
	 * @param string $type
	 * @param null $variable
	 * @param string $flags
	 *
	 * @return mixed
	 */
	public static function ulc_filter_input_array( $variable = null, $type = INPUT_GET, $flags = array() ) {
		if ( empty( $flags ) ) {
			$flags = array(
				'filter' => FILTER_VALIDATE_INT,
				'flags'  => FILTER_REQUIRE_ARRAY,
			);
		}
		/*
		 * View input types: https://www.php.net/manual/en/function.filter-input.php
		 * View flags at: https://www.php.net/manual/en/filter.filters.sanitize.php
		 */
		$args = array( $variable => $flags );
		$val  = filter_input_array( $type, $args );

		return isset( $val[ $variable ] ) ? $val[ $variable ] : array();
	}

	/**
	 * @param $code_group
	 *
	 * @return string|null
	 */
	public static function ulc_get_issue_count( $code_group ) {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->prefix" . Config::$tbl_codes . ' WHERE code_group = %d', $code_group ) );
	}

	/**
	 * @param $code
	 *
	 * @return int|string|null
	 */
	public static function maybe_validate_coupon_code( $code ) {
		$coupon_id  = Database::is_coupon_available( $code );
		$is_paid    = Database::is_coupon_paid( $code );
		$is_default = Database::is_default_code( $code );
		$is_active  = Database::is_coupon_active( $code );
		$return     = null;
		if ( false === $is_active && 'invalid' !== (string) $coupon_id['error'] ) {
			return Config::$cancelled_code;
		}
		if ( is_array( $coupon_id ) ) {
			switch ( $coupon_id['error'] ) {
				case 'max';
					$return = Config::$redeemed_maximum;
					break;
				case 'expired';
					$return = Config::$expired_code;
					break;
				case 'existing';
					$return = Config::$already_redeemed;
					break;
				case 'existing_course';
					$return = Config::$already_redeemed_existing_course;
					break;
				case 'existing_group';
					$return = Config::$already_redeemed_existing_group;
					break;
				default;
				case 'invalid';
					$return = Config::$invalid_code;
					break;
			}

			return $return;
		}
		if ( ! $is_paid && ! $is_default ) {
			return Config::$unpaid_error;
		}
		if ( is_numeric( $coupon_id ) ) {
			$return = intval( $coupon_id );
		}

		return $return;
	}

	/**
	 * Fetch Uncanny Owl SVG icon and convert to data URI
	 *
	 * @param int $timeout Request timeout in seconds (default: 5)
	 *
	 * @return string Data URI string or empty string on failure
	 */
	public static function get_svg_data_uri( $timeout = 5 ) {
		// Build the SVG URL using the static assets URL
		if ( ! defined( 'UNCANNY_OWL_ASSETS_STATIC_URL' ) ) {
			return '';
		}

		$svg_url = UNCANNY_OWL_ASSETS_STATIC_URL . '/img/uncannyowl-eye-mono.svg';

		// Determine if we should verify SSL based on filter or constant
		$sslverify = self::should_verify_ssl();

		$svg_content = wp_remote_get(
			$svg_url,
			array(
				'timeout'   => $timeout,
				'sslverify' => $sslverify,
			)
		);

		if ( is_wp_error( $svg_content ) ) {
			return '';
		}

		$svg_body = wp_remote_retrieve_body( $svg_content );
		if ( empty( $svg_body ) ) {
			return '';
		}

		return 'data:image/svg+xml;base64,' . base64_encode( $svg_body );
	}

	/**
	 * Determine if SSL should be verified based on filter or constant
	 *
	 * @return bool True to verify SSL, false to skip verification
	 */
	public static function should_verify_ssl() {
		// Allow filtering via WordPress filter (universal across all plugins)
		$sslverify = apply_filters( 'uncanny_owl_disable_ssl_verify', null );
		if ( null !== $sslverify ) {
			return ! $sslverify; // Filter returns true to disable, so invert
		}

		// Check if explicitly disabled via wp-config.php constant (universal across all plugins)
		if ( defined( 'UNCANNY_OWL_DISABLE_SSL_VERIFY' ) && true === \UNCANNY_OWL_DISABLE_SSL_VERIFY ) {
			return false;
		}

		// Default to verifying SSL for production environments
		return true;
	}
}
