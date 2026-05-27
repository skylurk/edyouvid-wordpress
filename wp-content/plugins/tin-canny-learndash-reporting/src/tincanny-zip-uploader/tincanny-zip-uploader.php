<?php
namespace uncanny_learndash_reporting;

/**
 * Tincanny Zip Uploader
 *
 * @package    Tin Canny Reporting for LearnDash
 * @subpackage Tincanny Zip Uploader
 * @author     Uncanny Owl
 * @since      1.0.0
 */

if ( ! defined( 'UO_ABS_PATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 * Tincanny Zip Uploader
 *
 * @since 1.0.0
 */
class TincannyZipUploader {

	/**
	 * Assets already enqueued flag.
	 *
	 * @var bool
	 */
	private $assets_enqueued = false;

	/**
	 * Upload directory
	 *
	 * @var string
	 */
	private $upload_dir = false;

	/**
	 * Target directory
	 *
	 * @var string
	 */
	private $target_dir = false;

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_endpoint' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ), 100 );
	}

	/**
	 * Enqueue assets
	 *
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {

		if ( $this->assets_enqueued ) {
			return;
		}
		$this->assets_enqueued = true;

		$handle            = 'tincanny-zip-uploader';
		$path              = 'src/tincanny-zip-uploader/dist/';
		$url_path          = plugins_url( $path, UO_REPORTING_FILE );
		$dir_path          = plugin_dir_path( UO_REPORTING_FILE );
		$script_asset_path = $dir_path . $path . 'index.asset.php';

		if ( ! file_exists( $script_asset_path ) ) {
			throw new \Error(
				'You need to run `npm start` or `npm run build` first.'
			);
		}

		$assets = require_once $script_asset_path;

		wp_enqueue_script(
			$handle,
			$url_path . 'index.js',
			$assets['dependencies'],
			$assets['version'],
			true
		);

		// Debug Constant.
		! defined( 'UO_TINCANNY_UPLOADER_DEBUG' ) ? define( 'UO_TINCANNY_UPLOADER_DEBUG', false ) : '';

		$full_zip_max_size = wp_max_upload_size(); // Server max upload size.
		$files_max_size    = 1073741824 * 2; // 2 GB in bytes.
		// If server max upload size is greater than 2GB, set it as max.
		$files_max_size    = $full_zip_max_size > $files_max_size ? $full_zip_max_size : $files_max_size;

		wp_localize_script(
			$handle,
			'tincannyZipUploadData',
			array(
				'nonce'               => wp_create_nonce( 'tincanny-zip-uploader' ),
				'rest_nonce'          => wp_create_nonce( 'wp_rest' ),
				'rest_url'            => esc_url_raw( rest_url() ),
				'rest_namespace'      => 'tincanny/v1/handle_zip_uploads',
				'max_upload_size'     => array(
					'full_zip_upload' => $full_zip_max_size,
					'files_upload'    => apply_filters( 'uo_tincanny_uploader_max_zip_size', $files_max_size ),
					'chunk_upload'    => apply_filters( 'uo_tincanny_uploader_max_upload_size', 1024000 * 2 ), // 2 MB in bytes.
					'post_max_size'   => wp_convert_hr_to_bytes( ini_get( 'post_max_size' ) ),
				),
				'module_types'        => $this->module_config( true ),
				'invalid_files'       => $this->invalid_files(),
				'max_filename_length' => 255,
				'debug'               => UO_TINCANNY_UPLOADER_DEBUG ? 1 : 0,
				'i18n'                => array(
					'max_upload_size'    => sprintf(
						/* translators: %s: Maximum allowed file size. */
						__( 'Maximum upload file size: %s.' ),
						esc_html( size_format( $full_zip_max_size ) )
					),
					/* translators: %s formated max zip size */
					'file_too_large'     => esc_html_x( 'The zip file you attempted to upload exceeds the maximum allowed size of %s.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					'file_too_large_alt' => esc_html_x( 'Please cancel the upload and uncheck the "Upload entire zip file" checkbox, then try the upload again.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					/* translators: %s mime type */
					'invalid_file_type'  => esc_html_x( 'Selected file type (%s) is not supported. Please choose a .zip file.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					/* translators: %1$s filename,  %2$d character count, %3$d max character length */
					'filename_too_long'  => esc_html_x( 'File name: %1$s is %2$d characters long and exceeds the %3$d character maximum length.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					'scanning_file'      => esc_html_x( 'Scanning zip file', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					'no_file'            => esc_html_x( 'No file selected', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					'not_zip_file'       => esc_html_x( 'Selected file is not a zip file.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					/* translators: %1$s module type,  %2$s Zip File Name*/
					'module_type_name'   => esc_html_x( 'Uploading %1$s Module - %2$s', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					'unsupported_type'   => esc_html_x( 'Unsupported', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					'unkown'             => esc_html_x( 'Unknown', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					/* translators: %1$d number of files uploaded + 1,%2$d: total number of files */
					'uploading_files'    => esc_html_x( 'Uploading file %1$d of %2$d', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					/* translators: %1$d number of files uploaded,%2$d: total number of files */
					'uploaded_files'     => esc_html_x( 'Uploaded %1$d of %2$d files', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					/* translators: %1$s: number and size uploaded, %2$s number and size total */
					'bytes_processed'    => esc_html_x( '%1$s of %2$s', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					'current_file'       => esc_html_x( 'Current file:', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					'cancel_upload'      => esc_html_x( 'Cancel Upload', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					'chunk_failed'       => esc_html_x( 'Failed to upload chunk.', 'tincanny-zip-uploader' ),
					'upload_started'     => esc_html_x( 'Upload started', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					'upload_complete'    => esc_html_x( 'Upload complete', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					'upload_cancelled'   => esc_html_x( 'Upload cancelled', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					'upload_error'       => esc_html_x( 'Upload error', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					'loading'            => esc_html_x( 'Loading...', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					'errors'             => esc_html_x( 'Errors', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					'file'               => esc_html_x( 'File', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					'unexpected_error'   => esc_html_x( 'An unexpected response was received. Please check console error log by right clicking the page, choosing Inspect, and checking the Console tab.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					/* translators: %s: Full rest route URL */
					'cloudflare'         => esc_html_x( 'Cloudflare Security / Firewall may be blocking the request. Please whitelist the Tin Canny REST route %s', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					'no_server_response' => esc_html_x( 'No response received from the server.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					'payload_limit'      => esc_html_x( 'The server is unable to handle your request. Please contact your hosting administrator to confirm the allowed payload limit.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					'unsupported'        => array(
						'notice'       => esc_html_x( 'Unable to read .zip file. Please use the original zip file as exported from your elearning authoring tool.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
						'item_title'   => esc_html_x( 'Please note that any xAPI/SCORM statements sent by this module in its current state:', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
						'items'        => array(
							esc_html_x( 'will not be recorded', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
							esc_html_x( 'may display errors because the module cannot communicate with an LMS or LRS', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
						),
						'instructions' => esc_html_x( 'Select an .html file', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
						'description'  => esc_html_x( 'To use this module anyway, select the .html file that launches the module using the file browser below:', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
						'aria_label'   => esc_html_x( 'directory tree', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
					),
				),
			)
		);

		wp_enqueue_style(
			'tincanny-zip-uploader',
			$url_path . 'style-index.css',
			array( 'wp-components' ),
			$assets['version']
		);
	}

	/**
	 * Get module config
	 *
	 * @param bool $to_json
	 * @return array
	 */
	private function module_config( $to_json = true ) {
		$config = array(
			'Storyline'      => array(
				'name'    => 'Storyline',
				'checks'  => array( 'story_content/' ),
				'subtype' => false,
				'class'   => '\TINCANNYSNC\FileSystem\Module\Storyline',
			),
			'Captivate'      => array(
				'name'    => 'Captivate',
				'checks'  => array( 'project.txt' ),
				'subtype' => array(
					'checks'    => array( 'scormdriver.js' ),
					'condition' => 'doesNotExist',
					'type'      => 'web',
					'required'  => false,
				),
				'class'   => '\TINCANNYSNC\FileSystem\Module\Captivate',
			),
			'iSpring'        => array(
				'name'    => 'iSpring',
				'checks'  => array( 'res/index.html' ),
				'subtype' => false,
				'class'   => '\TINCANNYSNC\FileSystem\Module\iSpring',
			),
			'ArticulateRise' => array(
				'name'    => 'ArticulateRise',
				'checks'  => array( 'scormcontent/lib/main.bundle.js' ),
				'subtype' => false,
				'class'   => '\TINCANNYSNC\FileSystem\Module\ArticulateRise',
			),
			'iSpringWeb'     => array(
				'name'    => 'iSpringWeb', //ispringWeb
				'checks'  => array( 'data/', 'metainfo.xml' ),
				'subtype' => array(
					'checks'    => array( 'metainfo.xml' ),
					'condition' => 'exists',
					'type'      => 'web',
					'required'  => true,
				),
				'class'   => '\TINCANNYSNC\FileSystem\Module\iSpring',
			),
			'Captivate2017'  => array(
				'name'    => 'Captivate2017',
				'checks'  => array( 'captivate.css' ),
				'subtype' => false,
				'class'   => '\TINCANNYSNC\FileSystem\Module\Captivate2017',
			),
			'AR2017'         => array(
				'name'    => 'AR2017', //Articulate Rise 2017
				'checks'  => array(
					'index.html',
					'tc-config.js',
					'tincan.xml',
					'lib/tincan.js',
				),
				'subtype' => false,
				'class'   => '\TINCANNYSNC\FileSystem\Module\ArticulateRise2017',
			),
			'AR360'          => array(
				'name'    => 'ArticulateRise',
				'checks'  => array(
					'scormcontent/lib/rise/',
				),
				'subtype' => false,
				'class'   => '\TINCANNYSNC\FileSystem\Module\ArticulateRise',
			),
			'Presenter360'   => array(
				'name'    => 'Presenter360',
				'checks'  => array( 'presentation_content/user.js' ),
				'subtype' => false,
				'class'   => '\TINCANNYSNC\FileSystem\Module\Presenter360',
			),
			'Lectora'        => array(
				'name'    => 'Lectora',
				'checks'  => array( 'a001index.html' ),
				'subtype' => false,
				'class'   => '\TINCANNYSNC\FileSystem\Module\Lectora',
			),
			'Scorm'          => array(
				'name'    => 'Scorm',
				'checks'  => array( 'imsmanifest.xml' ),
				'subtype' => false,
				'class'   => '\TINCANNYSNC\FileSystem\Module\Scorm',
			),
			'Tincan'         => array(
				'name'    => 'Tincan',
				'checks'  => array( 'tincan.xml' ),
				'subtype' => false,
				'class'   => '\TINCANNYSNC\FileSystem\Module\Xapi',
			),
		);

		// Remove class from config.
		if ( $to_json ) {
			$config = array_map(
				function( $i ) {
					unset( $i['class'] );
					return $i;
				},
				$config
			);
			return array_values( $config );
		}

		return $config;
	}

	/**
	 * File Types to exclude from upload.
	 *
	 * @return array
	 */
	private function invalid_files() {
		return apply_filters(
			'uo_tincanny_uploader_exclude_files',
			array(
				'__MACOSX',
				'.DS_Store',
				'.git',
				'.gitignore',
				//'.htaccess',
				'.idea',
				'.sass-cache',
				'.vscode',
				'bower_components',
				'composer.lock',
				'composer.phar',
				'node_modules',
				'npm-debug.log',
				//'package-lock.json',
				//'package.json',
				'phpcs.xml',
				'phpunit.xml',
				'phpunit.xml.dist',
			)
		);
	}

	/**
	 * Register rest api endpoints
	 *
	 * @return void
	 */
	public function register_rest_endpoint() {
		register_rest_route(
			'tincanny/v1',
			'/handle_zip_uploads/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_handle_uploads' ),
				'permission_callback' => array( __CLASS__, 'rest_permissions' ),
			)
		);
	}

	/**
	 * Validate Rest API.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return bool|\WP_Error
	 */
	public static function rest_permissions( $request ) {

		// Check capabilities.
		$capability = apply_filters( 'tincanny_can_upload_content', 'manage_options' );
		if ( ! current_user_can( $capability ) ) {
			return new \WP_Error(
				'rest_forbidden',
				esc_html_x( 'You do not have permissions to access this endpoint.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
				array( 'status' => 401 )
			);
		}

		// Check nonce.
		$params = $request->get_params();
		if ( ! isset( $params['security'] ) || ! wp_verify_nonce( $params['security'], 'tincanny-zip-uploader' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				esc_html_x( 'You do not have permissions to access this endpoint.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
				array( 'status' => 401 )
			);
		}

		// Check action.
		$whitelisted_actions = array(
			'upload-tincanny-zip',
			'upload-tincanny-zip-entry',
			'finalize-tincanny-module-upload',
			'cancel-tincanny-module-upload',
		);

		if ( ! isset( $params['action'] ) || ! in_array( $params['action'], $whitelisted_actions, true ) ) {
			return new \WP_Error(
				'rest_forbidden',
				esc_html_x( 'Invalid action.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
				array( 'status' => 401 )
			);
		}

		return true;
	}

	/**
	 * REST Upload Handler.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_handle_uploads( $request ) {

		$params = $request->get_params();
		$action = $params['action'];

		// Handle action.
		switch ( $action ) {
			case 'upload-tincanny-zip':
				$results = $this->handle_full_zip_upload( $params, $request->get_file_params() );
				break;
			case 'upload-tincanny-zip-entry':
				$results = $this->handle_zip_entry( $params, $request->get_file_params() );
				break;
			case 'finalize-tincanny-module-upload':
				$results = $this->finalize_module_upload( $params );
				break;
			case 'cancel-tincanny-module-upload':
				$results = $this->cancel_module_upload( $params );
				break;
		}

		$response_code = 200;

		// Format error response.
		if ( is_wp_error( $results ) ) {
			$results       = array(
				'success' => false,
				'message' => $results->get_error_message(),
			);
			$response_code = 400;
		}

		return new \WP_REST_Response( $results, $response_code );
	}

	/**
	 * Handle Full Zip Uploads.
	 *
	 * @param array $params - WP_REST_Request params.
	 * @param array $files  - $_FILES params.
	 *
	 * @return mixed array || \WP_Error
	 */
	public function handle_full_zip_upload( $params, $files ) {

		if ( ! is_array( $files ) || empty( $files['file'] ) ) {
			return $this->error_response( __( 'No file uploaded.', 'uncanny-learndash-reporting' ) );
		}

		$file = $files['file']['tmp_name'];

		// get name & extension
		$title             = trim( $files['file']['name'] );
		$title             = explode( '.', $title );
		$extension         = array_pop( $title );
		$title             = implode( '.', $title );
		$response['title'] = $title;

		$item_id = false;
		if ( isset( $params['content_id'] ) && ! empty( $params['content_id'] ) ) {
			$item_id = $params['content_id'];
			\TINCANNYSNC\Module_CRUD::update_item_title( $item_id, $title );
			$item_id .= '-temp';
		} else {
			$item_id = \TINCANNYSNC\Module_CRUD::add_item( $title );
		}

		// Database error.
		if ( ! $item_id ) {
			return $this->error_response( __( 'Database error.', 'uncanny-learndash-reporting' ) );
		}

		// Unzip file and register.
		$new_file = new \TINCANNYSNC\FileSystem\NewFile( $item_id, $file );

		// Uploading Error is set.
		if ( $new_file->get_upload_error() ) {
			return $this->error_response( $new_file->get_upload_error() );
		}

		// Unsupported Module
		if ( ! $new_file->get_type() ) {

			// On some occassions the file type is not detected backend but it was frontend.
			// If we don't have a selected unsupported file selected we will return an error.
			if ( empty( $params['unsupported'] ) ) {
				return $this->error_response( __( 'Unsupported module type.', 'uncanny-learndash-reporting' ) );
			}

			$item_id     = \TINCANNYSNC\Module_CRUD::add_item( $title );
			$new_file    = new \TINCANNYSNC\FileSystem\NewFile( $item_id, null, $params['unsupported'] );
			$new_module  = new \TINCANNYSNC\FileSystem\Module\UnknownType( $item_id );
			$db_data     = \TINCANNYSNC\Module_CRUD::get_item( $item_id );
			$new_module->set_url( $db_data['url'] );
			$new_module->add_nonce_block_code();
		}

		$data = array(
			'action' => 'registered-tincanny-module',
			'id'     => $item_id,
			'title'  => $title,
		);

		return $this->success_response( __( 'File uploaded.', 'uncanny-learndash-reporting' ), $data );
	}

	/**
	 * Handle zip entry
	 *
	 * @param array $params - WP_REST_Request params.
	 * @param array $files  - WP_REST_Request files.
	 *
	 * @return mixed array || \WP_Error
	 */
	private function handle_zip_entry( $params, $files ) {

		// Validate Necessary Params.
		$title     = ! empty( $params['title'] ) ? $params['title'] : false;
		$directory = ! empty( $params['directory'] ) ? $params['directory'] : false;
		$filepath  = ! empty( $params['filepath'] ) ? $params['filepath'] : false;
		$filename  = ! empty( $params['filename'] ) ? $params['filename'] : false;
		$decode    = ! empty( $params['base64'] ) ? (int) $params['base64'] : 0;
		if ( ! $title || ! $directory || ! $filepath || ! $filename ) {
			return $this->error_response( esc_html_x( 'Invalid Data unable to upload file.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ) );
		}

		// Get / Set target directory.
		$target = $this->get_target_dir( $directory );

		// Check if filename has directories.
		if ( $filepath !== $filename ) {
			// Remove filename and trim slashes.
			$filepath = trim( str_replace( $filename, '', $filepath ), '/' );
			// Create directory structure.
			$target = $target . '/' . $filepath;
			if ( ! wp_mkdir_p( $target ) ) {
				// Try once more sometimes having issues when sub directory has files and more subdirectories.
				if ( ! wp_mkdir_p( $target ) ) {
					return $this->error_response(
						esc_html_x( 'Could not create directory structure', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' )
					);
				}
			}
		}

		// Handle Uploads.
		$path     = $target . '/' . $filename;
		$is_chunk = ! empty( $files['chunked'] );
		$file_key = $is_chunk ? 'chunked' : 'file';
		// Decode file.
		if ( empty( $decode ) ) {
			$file = ! empty( $files[ $file_key ]['tmp_name'] ) ? $files[ $file_key ]['tmp_name'] : false;
		}

		// Handle Chunks
		if ( $is_chunk ) {
			return $this->handle_chunked_upload( $file, $path );
		}

		// Handle Encoded File.
		$decoded = false;
		if ( ! empty( $decode ) ) {
			$file    = base64_decode( $params['file'] );
			$decoded = true;
		}

		return $this->handle_upload( $file, $path, $decoded );
	}

	/**
	 * Handle file upload.
	 *
	 * @param string $file
	 * @param string $path
	 * @param bool   $decoded
	 *
	 * @return mixed array || \WP_Error
	 */
	private function handle_upload( $file, $path, $decoded = false ) {

		if ( $file ) {
			if ( $decoded ) {
				$success = file_put_contents( $path, $file );
			} else {
				$success = move_uploaded_file( $file, $path );
			}
			if ( false !== $success ) {
				return $this->success_response( esc_html_x( 'File uploaded.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ) );
			}
		}

		return $this->error_response(
			esc_html_x( 'Error uploading file.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ),
			true
		);
	}

	/**
	 * Handle chunked upload
	 *
	 * @param string $file
	 * @param string $path
	 *
	 * @return mixed array || \WP_Error
	 */
	private function handle_chunked_upload( $file, $path ) {

		// Check if first chunk.
		if ( ! file_exists( $path ) ) {
			return $this->handle_upload( $file, $path );
		}

		// Open the chunk file handle in binary mode
		$chunk_handle = fopen( $file, 'rb' );// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		// Open the target file handle in append mode
		$target_handle = fopen( $path, 'ab' );// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen

		try {
			while ( ! feof( $chunk_handle ) ) {
				$buffer     = 8192;
				$chunk_data = fread( $chunk_handle, $buffer );// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fread
				if ( false === $chunk_data ) {
					throw new Exception( esc_html_x( 'Error reading chunk data.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ) );
				}
				$bytes_written = fwrite( $target_handle, $chunk_data );// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
				if ( false === $bytes_written ) {
					throw new Exception( esc_html_x( 'Error writing chunk data to target file.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ) );
				}
			}

			return $this->success_response( esc_html_x( 'File chunk uploaded.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ) );

		} catch ( Exception $e ) {
			// Handle the error
			return $this->error_response( $e->getMessage(), true );
		} finally {
			// Close the file handles
			if ( $chunk_handle ) {
				fclose( $chunk_handle );// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
			}
			if ( $target_handle ) {
				fclose( $target_handle );// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
			}
		}
	}

	/**
	 * Finalize module upload.
	 *
	 * @param array $params - WP_REST_Request params.
	 *
	 * @return mixed array || \WP_Error
	 */
	private function finalize_module_upload( $params ) {

		$title       = ! empty( $params['title'] ) ? $params['title'] : false;
		$directory   = ! empty( $params['directory'] ) ? $params['directory'] : false;
		$type        = ! empty( $params['type'] ) ? $params['type'] : false;
		$subtype     = ! empty( $params['subtype'] ) ? $params['subtype'] : '';
		$unsupported = ! empty( $params['unsupported'] ) ? $params['unsupported'] : false;
		$replace_id  = ! empty( $params['replace_id'] ) ? $params['replace_id'] : false;
		$size        = ! empty( $params['size'] ) ? $params['size'] : false;
		$upload_date = ! empty( $params['upload_date'] ) ? $params['upload_date'] : false;

		if ( ! $title || ! $directory || ! $type ) {
			return $this->error_response( esc_html_x( 'Invalid data.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ) );
		}

		// Get / Set target directory.
		$target = $this->get_dir_path();
		if ( ! file_exists( $target ) ) {
			return $this->error_response( esc_html_x( 'Directory does not exist.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ) );
		}

		// Check if we're replacing content.
		if ( ! empty( $replace_id ) ) {

			$database_id = $replace_id;

			// Remove old directory if it exists.
			if ( file_exists( "{$target}/{$database_id}" ) ) {
				if ( ! $this->delete_directory_tree( "{$target}/{$database_id}" ) ) {
					return $this->error_response( esc_html_x( 'Could not delete directory.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ) );
				}
			}

			// Update Title.
			\TINCANNYSNC\Module_CRUD::update_item_title( $database_id, $title );
			
		} else {

			// Save title to database to generate ID.
			$database_id = \TINCANNYSNC\Module_CRUD::add_item( $title );
			if ( ! $database_id ) {
				return $this->error_response( esc_html_x( 'Database Error.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ) );
			}
		}

		// Rename directory to item ID.
		if ( ! rename( "{$target}/{$directory}", "{$target}/{$database_id}" ) ) {
			return $this->error_response( esc_html_x( 'Could not rename directory.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ) );
		}

		return $this->register_module( $database_id, $title, $type, $subtype, $unsupported );
	}

	/**
	 * Cancel module upload.
	 *
	 * @param array $params - WP_REST_Request params.
	 *
	 * @return mixed array || \WP_Error
	 */
	private function cancel_module_upload( $params ) {

		$directory = ! empty( $params['directory'] ) ? $params['directory'] : false;
		$status    = ! empty( $params['status'] ) ? $params['status'] : false;
		$fullzip   = ! empty( $params['fullzip'] ) ? $params['fullzip'] : false;

		// Add long sleep to allow for any pending file operations to complete.
		// Javascript continues frontend this is just a cleanup method.
		sleep( 5 );

		if ( ! $directory ) {
			return $this->error_response( esc_html_x( 'Invalid data.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ) );
		}

		// Remove directory.
		$target = $this->get_target_dir( $directory );
		if ( ! file_exists( $target ) ) {
			return $this->error_response( esc_html_x( 'Directory does not exist.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ) );
		}

		if ( ! $this->delete_directory_tree( $target ) ) {
			return $this->error_response( esc_html_x( 'Could not delete directory.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ) );
		}

		// Clean up database record.
		if ( ! empty( $fullzip ) ) {
			\TINCANNYSNC\Module_CRUD::delete( $directory );
		}

		return $this->success_response( esc_html_x( 'Upload cancelled.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ) );
	}

	/**
	 * Register module
	 *
	 * @param int $database_id
	 * @param string $title
	 * @param string $type
	 * @param string $subtype
	 * @param mixed (string|bool) $unsupported_file
	 *
	 * @return mixed array || \WP_Error
	 */
	private function register_module( $database_id, $title, $type, $subtype, $unsupported_file = false ) {

		$data = array(
			'id'     => $database_id,
			'title'  => $title,
			'type'   => $type,
			'action' => 'registered-tincanny-module',
		);

		if ( ! empty( $unsupported_file ) ) {
			$module = new \TINCANNYSNC\FileSystem\Module\UnknownType( $database_id );
			$module->register_unknown( $unsupported_file );
			return $this->success_response( esc_html_x( 'Module registered!', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ), $data );
		}

		$config = $this->module_config( false );
		if ( ! isset( $config[ $type ] ) || ! class_exists( $config[ $type ]['class'] ) ) {
			return $this->error_response( esc_html_x( 'Invalid module type.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ) );
		}

		$module = new $config[ $type ]['class']( $database_id );
		if ( ! empty( $subtype ) ) {
			$module->set_subtype( $subtype );
		}
		if ( ! $module->register() ) {
			return $this->error_response( esc_html_x( 'There has been an error registering the module.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ) );
		}

		return $this->success_response( esc_html_x( 'Module registered!', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' ), $data );
	}

	/**
	 * Success response
	 *
	 * @param string $message
	 * @param array $data
	 * @return array
	 */
	private function success_response( $message, $data = array() ) {
		return array(
			'success' => true,
			'message' => $message,
			'data'    => $data,
		);
	}

	/**
	 * Error response
	 *
	 * @param string $message
	 * @param bool   $is_file_uploader
	 *
	 * @return \WP_Error
	 */
	private function error_response( $message, $is_file_uploader = false ) {

		if ( $is_file_uploader ) {
			$message .= " ";
			$message .= esc_html_x( 'There may be an issue with the current file or with your server file system permissions. Please cancel the current upload, check the "Upload entire zip file" checkbox and try again. If the issue persists, please send full details about what you were trying to do and what error you received to support@uncannyowl.com for further assistance.', 'Tin Canny Zip Uploader', 'uncanny-learndash-reporting' );
		}

		return new \WP_Error( 'tincanny_zip_uploader', $message );
	}

	/**
	 * Get main directory path.
	 *
	 * @return string
	 */
	private function get_dir_path() {
		if ( ! $this->upload_dir ) {
			$wp_upload_dir = wp_upload_dir();
			if ( ! defined( 'SnC_UPLOAD_DIR_NAME' ) ) { // phpcs:ignore Generic.NamingConventions
				// If it's not, then define it
				define( 'SnC_UPLOAD_DIR_NAME', 'uncanny-snc' ); // phpcs:ignore Generic.NamingConventions
			}
			$this->upload_dir = $wp_upload_dir['basedir'] . '/' . SnC_UPLOAD_DIR_NAME;

			if ( ! file_exists( $this->upload_dir ) ) {
				$this->create_upload_dir();
			}
		}

		return $this->upload_dir;
	}

	/**
	 * Create upload directory.
	 *
	 * @return void
	 */
	private function create_upload_dir() {
		$wp_filesystem = $this->get_wp_filesytem();
		$wp_filesystem->mkdir( $this->upload_dir, 0755 );
		$wp_filesystem->put_contents( $this->upload_dir . '/index.html', '' );
	}

	/**
	 * Get WordPress Filesystem.
	 */
	private function get_wp_filesytem() {
		// Initialize the WP_Filesystem
		global $wp_filesystem;
		if ( ! is_object( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}
		return $wp_filesystem;
	}

	/**
	 * Delete directory tree.
	 *
	 * @param string $dir
	 *
	 * @return bool
	 */
	private function delete_directory_tree( $dir ) {

		// Ensure directory is valid WP uploads and uncanny-snc directory.
		if ( ! $this->is_valid_upload_snc_path( $dir ) ) {
			return false;
		}

		if ( ! class_exists( 'WP_Filesystem_Base' ) || ! class_exists( 'WP_Filesystem_Direct' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		}
		$WP_Filesystem_Direct = new \WP_Filesystem_Direct( false );
		return $WP_Filesystem_Direct->delete( $dir, true, 'd' );
	}

	/**
	 * Check if path is valid WP uploads and uncanny-snc directory.
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	private function is_valid_upload_snc_path( $path ) {

		if ( empty( $path ) ) {
			return false;
		}

		// Get the WordPress upload directory base path
		$upload_dir_info = wp_upload_dir();
		$upload_base_dir = $upload_dir_info['basedir']; // Using 'basedir' for server paths.

		// Escape special characters for regex
		$escaped_upload_dir = preg_quote( $upload_base_dir, '/' );

		// Construct the regex pattern
		$pattern = "/^" . $escaped_upload_dir . ".*uncanny-snc.*$/";

		// Check if the path matches the pattern
		return preg_match( $pattern, $path ) === 1;
	}

	/**
	 * Get target directory.
	 *
	 * @param string $directory
	 *
	 * @return string
	 */
	protected function get_target_dir( $directory ) {

		if ( ! $this->target_dir ) {
			$target           = ( ! $this->upload_dir ) ? $this->get_dir_path() : $this->upload_dir;
			$this->target_dir = $target . '/' . $directory;
			// Create target directory if it doesn't exist.
			if ( ! file_exists( $this->target_dir ) ) {
				wp_mkdir_p( $this->target_dir );
			}
		}

		return $this->target_dir;
	}

}
