<?php

namespace uncanny_pro_toolkit;

use TCPDF;
use uncanny_pro_toolkit\CertificateBuilder;
use uncanny_learndash_toolkit as toolkit;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class CertificatePreview
 * @package uncanny_pro_toolkit
 */
class CertificatePreview extends toolkit\Config implements toolkit\RequiredFunctions {

	/**
	 * @var
	 */
	public static $current_time_stamp;

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( __CLASS__, 'run_frontend_hooks' ) );
	}

	/*
	 * Initialize frontend actions and filters
	 */
	/**
	 *
	 */
	public static function run_frontend_hooks() {

		if ( true === self::dependants_exist() ) {
			require_once Boot::get_pro_include( 'certificate-builder.php', UO_FILE );
			require_once Boot::get_pro_include( 'tcpdf-certificate-code.php', UO_FILE );

			add_action( 'add_meta_boxes', array( __CLASS__, 'preview_certificate_add_meta_box' ) );
			add_action( 'admin_init', array( __CLASS__, 'display_preview_of_certificate' ), 999 );
		}

	}

	/**
	 * Does the plugin rely on another function or plugin
	 *
	 * @return boolean || string Return either true or name of function or plugin
	 *
	 */
	public static function dependants_exist() {

		/* Checks for LearnDash */
		global $learndash_post_types;
		if ( ! isset( $learndash_post_types ) ) {
			return 'Plugin: LearnDash';
		}

		// Return true if no dependency or dependency is available
		return true;
	}

	/**
	 * Description of class in Admin View
	 *
	 * @return array
	 */
	public static function get_details() {
		$module_id = 'certificate-preview';

		$class_title = esc_attr__( 'Certificate Preview', 'uncanny-pro-toolkit' );

		//set to null or remove to disable the link to KB
		$kb_link = 'https://www.uncannyowl.com/knowledge-base/certificate-preview/';

		/* Sample Simple Description with shortcode */
		$class_description = esc_html__( 'Get a preview of your quiz or course certificate without leaving the editor.', 'uncanny-pro-toolkit' );

		/* Icon as fontawesome icon */
		$class_icon = '<i class="uo_icon_pro_fa uo_icon_fa fa fa-file-pdf-o"></i><span class="uo_pro_text">PRO</span>';

		$category = 'learndash';
		$type     = 'pro';

		return array(
			'id'               => $module_id,
			'title'            => $class_title,
			'type'             => $type,
			'category'         => $category,
			'kb_link'          => $kb_link, // OR set as null not to display
			'description'      => $class_description,
			'dependants_exist' => self::dependants_exist(),
			'settings'         => null,
			'icon'             => $class_icon,
		);

	}

	/**
	 *
	 */
	public static function preview_certificate_add_meta_box() {
		add_meta_box(
			'preview_certificate-preview-certificate',
			__( 'Certificate Preview', 'uncanny-pro-toolkit' ),
			array( __CLASS__, 'preview_certificate_html' ),
			'sfwd-certificates',
			'side',
			'high'
		);
	}

	/**
	 * @param $post
	 */
	public static function preview_certificate_html( $post ) {
		$shortcodes_notice       = '<p>' . esc_attr__( 'Note: Learndash shortcodes will be replaced with static preview values. Non-LearnDash shortcodes are not supported.', 'uncanny-pro-toolkit' ) . '</p>';
		$builder_disabled_notice = '<p>' . esc_attr__( 'Certificates created with the Certificate Builder must have the Builder plugin active.', 'uncanny-pro-toolkit' ) . '</p>';
		$save_certificate_notice = '<p>' . esc_attr__( 'Save or Update Certificate before previewing.', 'uncanny-pro-toolkit' ) . '</p>';
		$button_text             = esc_attr__( 'Certificate Preview', 'uncanny-pro-toolkit' );
		$button_template         = '<a href="%s" target="_blank" class="button">%s</a>';
		$output                  = '<div style="display: table;"><p>';

		$builder = new CertificateBuilder();

		if ( $builder->created_with_builder( $post->ID ) ) {
			if ( $builder->builder_active() ) {
				$url    = get_preview_post_link( $post );
				$button = sprintf( $button_template, $url, $button_text );
				$output .= $save_certificate_notice;
			} else {
				$button = $builder_disabled_notice;
			}

			$output .= $button;

		} else {
			$url = admin_url( 'admin.php' ) . '?certificate_id=' . $post->ID . '&certificate_preview=true&wpnonce=' . wp_create_nonce( time() );

			if ( filter_has_var( INPUT_GET, 'lang' ) ) {
				$url .= '&lang=' . esc_attr( filter_input( INPUT_GET, 'lang' ) );
			}

			$button = sprintf( $button_template, $url, $button_text );

			$output .= $save_certificate_notice;
			$output .= $button;
			$output .= $shortcodes_notice;
		}

		$output .= '</p></div>';

		echo $output;
	}

	/**
	 *
	 */
	public static function display_preview_of_certificate() {
		if ( filter_has_var( INPUT_GET, 'certificate_preview' ) && filter_has_var( INPUT_GET, 'certificate_id' ) ) {
			$setup_parameters  = self::setup_parameters( filter_input( INPUT_GET, 'certificate_id' ), 0, wp_get_current_user()->ID );
			$generate_pdf_args = array(
				'certificate_post' => absint( esc_attr( filter_input( INPUT_GET, 'certificate_id' ) ) ),
				'save_path'        => null,
				'user'             => wp_get_current_user(),
				'file_name'        => 'preview_certificate',
				'parameters'       => $setup_parameters,
			);

			self::generate_pdf( $generate_pdf_args );
		}
	}

	/**
	 * @param $certificate_id
	 * @param $course_id
	 * @param $user_id
	 *
	 * @return array
	 */
	public static function setup_parameters( $certificate_id, $course_id, $user_id ) {
		$setup_parameters                      = array();
		$setup_parameters['userID']            = $user_id;
		$setup_parameters['user']              = get_user_by( 'ID', $user_id );
		$setup_parameters['course-id']         = $course_id;
		$setup_parameters['course-name']       = esc_attr__( 'Certificate Preview', 'uncanny-pro-toolkit' );
		$setup_parameters['print-certificate'] = 0;
		$setup_parameters['certificate-post']  = $certificate_id;
		$setup_parameters['print-certificate'] = 1;

		return $setup_parameters;
	}

	/**
	 * @param $args
	 *
	 * @return string
	 */
	public static function generate_pdf( $args ) {
		Tcpdf_Certificate_Code::generate_pdf( $args, 'preview' );
	}


}
