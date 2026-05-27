<?php

namespace uncanny_learndash_codes;

use uncanny_learndash_groups\SharedFunctions;
use WP_Post;

/**
 * Class Config
 *
 * @package uncanny_learndash_codes
 */
class Config {

	/**
	 * @var $__instance
	 */
	public static $__instance;
	/**
	 * @var string
	 */
	public static $tbl_groups = 'uncanny_codes_groups';
	/**
	 * @var string
	 */
	public static $tbl_codes = 'uncanny_codes_codes';
	/**
	 * @var string
	 */
	public static $tbl_codes_usage = 'uncanny_codes_usage';
	/**
	 * @var string
	 */
	public static $invalid_code;
	/**
	 * @var string
	 */
	public static $expired_code;
	/**
	 * @var string
	 */
	public static $already_redeemed;
	/**
	 * @var string
	 */
	public static $redeemed_maximum;
	/**
	 * @var string
	 */
	public static $successfully_redeemed;
	/**
	 * @var string
	 */
	public static $allow_multiple_groups;
	/**
	 * @var string
	 */
	public static $uncanny_codes_settings_custom_messages = 'uncanny-learndash-codes-setting-custom-messages';
	/**
	 * @var string
	 */
	public static $uncanny_codes_settings_term_conditions = 'uncanny-learndash-codes-setting-term-conditions';
	/**
	 * @var string
	 */
	public static $uncanny_codes_settings_multiple_groups = 'uncanny-learndash-codes-setting-group-settings';
	/**
	 * @var string
	 */
	public static $uncanny_codes_settings_gravity_forms = 'uncanny-learndash-codes-setting-form-id';
	/**
	 * @var string
	 */
	public static $uncanny_codes_settings_gravity_forms_mandatory = 'uncanny-learndash-codes-setting-form-field-mandatory';
	/**
	 * @var string
	 */
	public static $uncanny_codes_settings_gravity_forms_label = 'uncanny-learndash-codes-setting-form-field-label';
	/**
	 * @var string
	 */
	public static $uncanny_codes_settings_gravity_forms_error = 'uncanny-learndash-codes-setting-form-field-error';
	/**
	 * @var string
	 */
	public static $uncanny_codes_settings_gravity_forms_placeholder = 'uncanny-learndash-codes-setting-form-field-placeholder';
	/**
	 * @var string
	 */
	public static $uncanny_codes_user_prefix_meta = 'uncanny-learndash-codes-prefix';
	/**
	 * @var string
	 */
	public static $uncanny_codes_settings_autocomplete = 'uncanny-learndash-codes-autocomplete-orders';
	/**
	 * @var string
	 */
	public static $uncanny_codes_tml_template_override = 'uncanny-learndash-codes-tml-override';
	/**
	 * @var string
	 */
	public static $uncanny_codes_tml_codes_required_field = 'uncanny-learndash-codes-tml-required-field';
	/**
	 * @var string
	 */
	public static $uncanny_codes_tracking = 'uncanny-learndash-codes-tracking';

	/**
	 * @var string
	 */
	public static $uncanny_codes_same_code_user_redemption = 'uncanny-learndash-codes-same-code-user-redemption';
	/**
	 * @var string
	 */
	public static $uncanny_codes_times_code_can_be_reused = 'uncanny-learndash-codes-times_code_can_be_reused';
	/**
	 * @var string
	 */
	public static $unpaid_error;

	/**
	 * @var string
	 */
	public static $cancelled_code;

	/**
	 * The Rest-API route
	 *
	 * The v2 means we are using version 2 of the wp rest api
	 *
	 * @var      string
	 * @since    {plugin_version}
	 * @access   public
	 */
	public static $root_path = 'uncanny-codes/v2';

	/**
	 * Config constructor.
	 */
	public function __construct() {

		$load_settings = self::load_plugin_settings();
		if ( is_admin() || $load_settings ) {
			self::load_default_messages();
		}
	}

	/**
	 * @param bool $override
	 *
	 * @return bool
	 */
	public static function load_plugin_settings( $override = false ) {
		if ( $override ) {
			return true;
		}
		$load_settings = false;
		global $post;
		if ( $post instanceof WP_Post &&
			 ( has_shortcode( $post->post_content, 'gravityform' ) ||
			   has_shortcode( $post->post_content, 'wpforms' ) ||
			   has_shortcode( $post->post_content, 'theme-my-login' ) ||
			   has_shortcode( $post->post_content, 'uo_user_redeem_code' ) ||
			   has_shortcode( $post->post_content, 'uo_self_remove_access' ) ||
			   has_shortcode( $post->post_content, 'uo_code_registration' ) ) ) {
			$load_settings = true;
		} elseif ( SharedFunctionality::ulc_filter_has_var( 'code_registration', INPUT_POST ) ) {
			// TML Registration Field.
			$load_settings = true;
		} elseif ( SharedFunctionality::ulc_filter_has_var( 'gform_submit', INPUT_POST ) ) {
			// GF Registration Field.
			$load_settings = true;
		} elseif ( SharedFunctionality::ulc_filter_has_var( 'uncanny-learndash-codes-code_registration', INPUT_POST ) ) {
			// Native Registration Field.
			$load_settings = true;
		} elseif ( SharedFunctionality::ulc_filter_has_var( 'coupon_code_only', INPUT_POST ) ) {
			// Code Only Field.
			$load_settings = true;
		} elseif ( function_exists( 'WC' ) && ( is_checkout() || is_cart() || is_woocommerce() ) ) {
			// Woocommerce pages only.
			$load_settings = true;
		} elseif ( SharedFunctionality::ulc_filter_has_var( 'wc-ajax' ) ) {
			// Woocommerce pages only.
			$load_settings = true;
		}

		return apply_filters( 'ulc_load_settings', $load_settings, $post );
	}

	/**
	 *
	 */
	public static function load_default_messages() {
		if ( is_multisite() ) {
			$messages = get_blog_option( get_current_blog_id(), self::$uncanny_codes_settings_custom_messages, '' );
		} else {
			$messages = get_option( self::$uncanny_codes_settings_custom_messages, array() );
		}

		if ( ! empty( $messages ) ) {
			if ( ! empty( $messages['invalid-code'] ) ) {
				self::$invalid_code = $messages['invalid-code'];
			} else {
				self::$invalid_code = esc_html__( 'Sorry, the code you entered is not valid.', 'uncanny-learndash-codes' );
			}
			if ( ! empty( $messages['expired-code'] ) ) {
				self::$expired_code = $messages['expired-code'];
			} else {
				self::$expired_code = esc_html__( 'Sorry, the code you entered has expired.', 'uncanny-learndash-codes' );
			}
			if ( ! empty( $messages['already-redeemed'] ) ) {
				self::$already_redeemed = $messages['already-redeemed'];
			} else {
				self::$already_redeemed = esc_html__( 'Sorry, the code you entered has already been redeemed.', 'uncanny-learndash-codes' );
			}
			if ( ! empty( $messages['redeemed-maximum'] ) ) {
				self::$redeemed_maximum = $messages['redeemed-maximum'];
			} else {
				self::$redeemed_maximum = esc_html__( 'Sorry, the code you entered has already been redeemed the maximum number of times.', 'uncanny-learndash-codes' );
			}
			if ( ! empty( $messages['successfully-redeemed'] ) ) {
				self::$successfully_redeemed = $messages['successfully-redeemed'];
			} else {
				self::$successfully_redeemed = esc_html__( 'Congratulations, the code you entered has been successfully redeemed.', 'uncanny-learndash-codes' );
			}
			if ( ! empty( $messages['unpaid-error'] ) ) {
				self::$unpaid_error = $messages['unpaid-error'];
			} else {
				self::$unpaid_error = esc_html__( 'To use this code, enter it on the checkout page and complete your purchase.', 'uncanny-learndash-codes' );
			}
			if ( ! empty( $messages['cancelled-code'] ) ) {
				self::$cancelled_code = $messages['cancelled-code'];
			} else {
				self::$cancelled_code = esc_html__( 'Sorry, the code you entered is cancelled.', 'uncanny-learndash-codes' );
			}
		} else {
			self::$invalid_code          = esc_html__( 'Sorry, the code you entered is not valid.', 'uncanny-learndash-codes' );
			self::$expired_code          = esc_html__( 'Sorry, the code you entered has expired.', 'uncanny-learndash-codes' );
			self::$already_redeemed      = esc_html__( 'Sorry, the code you entered has already been redeemed.', 'uncanny-learndash-codes' );
			self::$redeemed_maximum      = esc_html__( 'Sorry, the code you entered has already been redeemed the maximum number of times.', 'uncanny-learndash-codes' );
			self::$successfully_redeemed = esc_html__( 'Congratulations, the code you entered has successfully been redeemed.', 'uncanny-learndash-codes' );
			self::$unpaid_error          = esc_html__( 'To use this code, enter it on the checkout page and complete your purchase.', 'uncanny-learndash-codes' );
			self::$cancelled_code        = esc_html__( 'Sorry, the code you entered is cancelled.', 'uncanny-learndash-codes' );
		}
		if ( is_multisite() ) {
			$group_settings = get_blog_option( get_current_blog_id(), self::$uncanny_codes_settings_multiple_groups, 0 );
		} else {
			$group_settings = get_option( self::$uncanny_codes_settings_multiple_groups, 0 );
		}
		self::$allow_multiple_groups = $group_settings;
	}

	/**
	 * @return Config
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
	 * @param string $source
	 * @param string $file_name
	 *
	 * @return string
	 */
	public static function get_asset( $source = 'frontend', $file_name = '' ) {
		$asset_url = plugins_url( 'assets/' . $source . '/dist/' . $file_name, __FILE__ );

		return $asset_url;
	}

	/**
	 * @param string $file_name
	 *
	 * @return string
	 */
	public static function get_vendor( $file_name ) {
		$asset_url = plugins_url( 'assets/vendor/' . $file_name, __FILE__ );

		return $asset_url;
	}

	/**
	 * @param string $file_name File name must be prefixed with a \ (foreword
	 *     slash)
	 * @param mixed $file (false || __FILE__ )
	 *
	 * @return string
	 */
	public static function get_template( $file_name, $file = false ) {

		$template_path = apply_filters( 'uncanny_codes_template_path', 'uncanny-codes' . DIRECTORY_SEPARATOR );
		$asset_uri     = self::locate_template( $template_path . $file_name );

		if ( empty( $asset_uri ) ) {

			if ( false === $file ) {
				$file = __FILE__;
			}

			$asset_uri = dirname( $file ) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $file_name;
		}

		return $asset_uri;
	}

	/**
	 * Retrieve the name of the highest priority template file that exists.
	 *
	 * Searches in the STYLESHEETPATH before TEMPLATEPATH and
	 * wp-includes/theme-compat so that themes which inherit from a parent
	 * theme can just overload one file.
	 *
	 * @param string|array $template_names Template file(s) to search for, in
	 *     order.
	 *
	 * @since 3.1
	 *
	 * @return string The template filename if one is located.
	 */
	public static function locate_template( $template_names ) {
		$located = '';
		foreach ( (array) $template_names as $template_name ) {
			if ( ! $template_name ) {
				continue;
			}

			if ( file_exists( get_stylesheet_directory() . DIRECTORY_SEPARATOR . $template_name ) ) {
				$located = get_stylesheet_directory() . DIRECTORY_SEPARATOR . $template_name;
				break;
			} elseif ( file_exists( get_template_directory() . DIRECTORY_SEPARATOR . $template_name ) ) {
				$located = get_template_directory() . DIRECTORY_SEPARATOR . $template_name;
				break;
			} elseif ( file_exists( ABSPATH . WPINC . DIRECTORY_SEPARATOR . 'theme-compat' . DIRECTORY_SEPARATOR . $template_name ) ) {
				$located = ABSPATH . WPINC . DIRECTORY_SEPARATOR . 'theme-compat' . DIRECTORY_SEPARATOR . $template_name;
				break;
			}
		}

		return $located;
	}

	/**
	 * @param string $file_name File name
	 * @param mixed $file (false || __FILE__ )
	 *
	 * @return string
	 */
	public static function get_include( $file_name, $file = false, $directory = 'includes' ) {

		if ( false === $file ) {
			$file = __FILE__;
		}

		$asset_uri = dirname( $file ) . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $file_name;

		return $asset_uri;
	}

	/**
	 * @return string
	 */
	public static function get_project_name() {
		return 'uncanny_learndash_codes';
	}

	/**
	 * @return string
	 */
	public static function get_version() {
		return UNCANNY_LEARNDASH_CODES_VERSION;
	}

	/**
	 * @return string
	 */
	public static function get_rest_api_root_path() {
		return self::$root_path;
	}

	/**
	 * Create and store logs @
	 * wp-content/{plugin_folder_name}/uo-{$file_name}.log
	 *
	 * @param string $trace_message The message logged
	 * @param string $trace_heading The heading of the current trace
	 * @param bool $force_log Create log even if debug mode is off
	 * @param string $file_name The file name of the log file
	 *
	 * @since    1.0.0
	 *
	 * @return bool $error_log Was the log successfully created
	 */
	public static function log( $trace_message = '', $trace_heading = '', $force_log = false, $file_name = 'logs' ) {
		$timestamp         = date_i18n( 'F j, Y' );
		$current_page_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$trace_start       = "\n===========================<<<< $timestamp >>>>===========================\n";
		$trace_heading     = "* Heading: $trace_heading \n";
		$trace_heading     .= "* Current Page: $current_page_link \n";
		$trace_heading     .= '* Plugin Initialized: ' . date_i18n( 'F j, Y g:i a', current_time( 'timestamp' ) ) . "\n";
		$trace_end         = "\n===========================<<<< TRACE END >>>>===========================\n\n";
		$trace_message     = print_r( $trace_message, true );
		$file              = WP_CONTENT_DIR . '/uo-' . $file_name . '.log';
		error_log( $trace_start . $trace_heading . $trace_message . $trace_end, 3, $file );
	}

}
