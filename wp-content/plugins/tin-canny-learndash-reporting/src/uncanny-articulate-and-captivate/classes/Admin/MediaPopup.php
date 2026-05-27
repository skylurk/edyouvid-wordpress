<?php
/**
 * Media Popup
 *
 * @package    Tin Canny Reporting for LearnDash
 * @subpackage Embed Articulate Storyline and Adobe Captivate
 * @author     Uncanny Owl
 * @since      1.0.0
 * @todo       Button
 */

namespace TINCANNYSNC\Admin;

if ( ! defined( 'UO_ABS_PATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

class MediaPopup {
	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 * @since  1.0.0
	 */
	public function __construct() {
		// Media Upload Window
		add_action( 'media_upload_snc', array( $this, 'media_upload' ), 100 );

		// File Upload Ajax
		add_action( 'wp_ajax_SnC_Media_Upload', array( $this, 'ajax_upload' ) );
		// Generate Shortcode Ajax
		add_action( 'wp_ajax_SnC_Media_Embed', array( $this, 'ajax_embed' ) );
		// Delete Ajax
		add_action( 'wp_ajax_SnC_Media_Delete', array( $this, 'ajax_delete' ) );

		// Link File Path
		add_action( 'wp_ajax_SnC_Link_File_Path', array( $this, 'ajax_link_file_path' ) );
	}

	/**
	 * Thickbox Media Upload
	 *
	 * @trigger media_upload_snc action
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function media_upload() {
		// Modify Modal Uploader Tabs
		add_filter( 'media_upload_tabs', array( $this, 'media_upload_tab' ), 100 );
		$input_type = ultc_current_request_type();
		$tab        = ultc_get_filter_var( 'tab', '', $input_type );
		$class      = ! empty( $tab ) && strstr( $tab, 'snc-library' ) ? 'media_upload_library' : 'media_upload_form';
		wp_iframe( array( $this, $class ) );
	}

	/**
	 * Thickbox Media Upload Form
	 *
	 * @trigger wp_iframe()
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function media_upload_form() {
		$post = false;

		$glightbox_transitions = \TINCANNYSNC\Shortcode::$glightbox_transitions;

		$options = Options::get_options();
		$no_tab  = ultc_filter_has_var( 'no_tab' );
		if ( ! $no_tab ) {
			media_upload_header();
		}

		include_once SnC_PLUGIN_DIR . 'views/media_upload_file.php';

		if ( ! $no_tab ) {
			include_once SnC_PLUGIN_DIR . 'views/embed_information.php';
		}
	}

	/**
	 * Thickbox Media Upload Library
	 *
	 * @trigger wp_iframe()
	 * @since 0.0.1
	 * @access public
	 */
	public function media_upload_library() {
		$posts = \TINCANNYSNC\Module_CRUD::get_modules();

		$glightbox_transitions = \TINCANNYSNC\Shortcode::$glightbox_transitions;

		$options = Options::get_options();

		media_upload_header();
		include_once SnC_PLUGIN_DIR . 'views/content_library.php';
	}

	/**
	 * Thickbox Media Upload Tab
	 *
	 * @trigger media_upload_tabs filter
	 * @since 0.0.1
	 * @access public
	 */
	public function media_upload_tab( $tabs ) {
		return array(
			'upload'      => 'Upload File',
			'snc-library' => 'Content Library',
		);
	}

	/**
	 * Ajax Request for Media Upload
	 *
	 * @trigger wp_ajax_SnC_Media_Upload Action
	 * @since 0.0.1
	 * @access public
	 * @todo : insert DB add to file system
	 */
	public function ajax_upload() {
		check_ajax_referer( 'snc-media_upload_form', 'security' );

		$upload_file = isset( $_FILES['media_upload_file'] ) ? sanitize_file_name( wp_unslash( $_FILES['media_upload_file'] ) ) : false;
		$file        = $upload_file ? $upload_file['tmp_name'] : false;

		// No File bail.
		if ( ! $file ) {
			echo wp_json_encode(
				array(
					'id'      => 'error',
					'message' => __( 'File is not uploaded.', 'uncanny-learndash-reporting' ),
				)
			);
			die;
		}

		// get name & extension
		$title     = trim( $upload_file['name'] );
		$title     = explode( '.', $title );
		$extension = array_pop( $title );
		$title     = implode( '.', $title );

		// Not a zip bail.
		if ( 'zip' !== $extension ) {
			echo wp_json_encode(
				array(
					'id'      => 'error',
					'message' => __( 'File extension must be .zip.', 'uncanny-learndash-reporting' ),
				)
			);
			die;
		}

		$item_id = ultc_get_filter_var( 'content_id', false, INPUT_POST );

		if ( ! empty( $item_id ) ) {
			\TINCANNYSNC\Module_CRUD::update_item_title( $item_id, $title );
			$item_id .= '-temp';
		} else {
			$item_id = \TINCANNYSNC\Module_CRUD::add_item( $title );
		}

		if ( $item_id ) {
			$new_file = new \TINCANNYSNC\FileSystem\NewFile( $item_id, $file );

			if ( $new_file->get_upload_error() ) { // Uploading Error is set
					$message = array(
						'id'      => 'error',
						'message' => $new_file->get_upload_error(),
					);
			} elseif ( ! $new_file->get_type() ) { // Not Supported File
				$message = array(
					'id'        => 'not_supported',
					'message'   => __( 'This file type is not supported.', 'uncanny-learndash-reporting' ),
					'ajaxPath'  => admin_url( 'admin-ajax.php' ),
					'nonce'     => wp_create_nonce( 'snc-link-file-path-form' ),
					'title'     => $title,
					'structure' => wp_json_encode( $new_file->get_structure() ),
				);

			} elseif ( $new_file->get_uploaded() ) {
				// Success - return json response.
				echo $new_file->get_result_json( $title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				die;
			} else { // Something Wrong
				$message = array(
					'id'      => 'error',
					'message' => __( 'Something went wrong.', 'uncanny-learndash-reporting' ),
				);
			}
		} else { // Database Failure
			$message = array(
				'id'      => 'error',
				'message' => __( 'Something went wrong when setting up your database.', 'uncanny-learndash-reporting' ),
			);
		}

		echo wp_json_encode( $message );
		die;
	}

	/**
	 * Ajax Request for Shortcode Generate
	 *
	 * @trigger wp_ajax_SnC_Media_Embed Action
	 * @since 0.0.1
	 * @access public
	 */
	public function ajax_embed() {
		check_ajax_referer( 'snc-media_enbed_form', 'security' );
		$shortcode = \TINCANNYSNC\Shortcode::generate_shortcode( $_POST );

		if ( ! $shortcode ) {
			return false;
		}

		echo wp_json_encode(
			array(
				'shortcode' => $shortcode,
			)
		);

		die;
	}

	public function ajax_delete() {

		if ( ultc_get_filter_var( 'mode', '', INPUT_POST ) !== 'vc' ) {
			check_ajax_referer( 'snc-media_enbed_form', 'security' );
		}

		$module = \TINCANNYSNC\Module::get_module( ultc_get_filter_var( 'item_id', 0, INPUT_POST ) );
		$module->delete();

		die;
	}

	public function ajax_link_file_path() {
		check_ajax_referer( 'snc-link-file-path-form', 'security' );

		$title      = ultc_get_filter_var( 'title', '', INPUT_POST );
		$path       = ultc_get_filter_var( 'filePath', '', INPUT_POST );
		$item_id    = \TINCANNYSNC\Module_CRUD::add_item( $title );
		$new_file   = new \TINCANNYSNC\FileSystem\NewFile( $item_id, null, $path );
		$new_module = new \TINCANNYSNC\FileSystem\Module\UnknownType( $item_id );
		$db_data    = \TINCANNYSNC\Module_CRUD::get_item( $item_id );
		$new_module->set_url( $db_data['url'] );
		$new_module->add_nonce_block_code();

		echo $new_file->get_result_json( $title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		die;
	}

	private function format_bytes( $size, $precision = 2 ) {
		$base     = log( $size, 1024 );
		$suffixes = array( '', 'kB', 'MB', 'GB', 'TB' );
		$number   = number_format( round( pow( 1024, $base - floor( $base ) ), $precision ) );

		return $number . $suffixes[ floor( $base ) ];
	}
}
