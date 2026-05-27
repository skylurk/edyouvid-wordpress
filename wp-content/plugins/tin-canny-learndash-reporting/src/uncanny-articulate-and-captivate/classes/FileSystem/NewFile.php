<?php
/**
 * New File Controller
 *
 * @since      1.0.0
 * @author     Uncanny Owl
 * @package    Tin Canny Reporting for LearnDash
 * @subpackage Embed Articulate Storyline and Adobe Captivate
 */

namespace TINCANNYSNC\FileSystem;

use Dompdf\Exception;

if ( ! defined( 'UO_ABS_PATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

class NewFile {
	use traitModule;

	private $uploaded = true;
	private $upload_error = '';
	private $file = '';
	private $structure = array();

	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 * @since  1.0.0
	 */
	public function __construct( $item_id, $file, $rel_path = false ) {
		$this->set_item_id( $item_id );
		$this->file = $file;

		if ( ! $rel_path ) {
			$this->upload();
		} else {
			$this->link_file_path( $rel_path );
		}
	}

	public function get_file_location() {
		return $this->file;
	}

	public function get_upload_error() {
		return $this->upload_error;
	}

	public function get_uploaded() {
		return $this->uploaded;
	}

	public function get_structure() {
		return $this->structure;
	}

	private function upload() {
		try {
			$this->extract_zip();

			if ( file_exists( $this->get_target_dir() ) ) {
				$this->set_type( $this->get_file_type() );

				switch ( $this->get_type() ) {
					case 'Storyline' :
						$module = new Module\Storyline( $this->get_item_id() );
						if ( ! $module->register() ) {
							return $this->cancel_upload();
						}
						break;

					case 'Captivate' :
						$module = new Module\Captivate( $this->get_item_id() );

						if ( $this->get_subtype() == 'web' ) {
							$module->set_subtype( 'web' );
						}

						if ( ! $module->register() ) {
							return $this->cancel_upload();
						}
						break;

					case 'Captivate2017' :
						$module = new Module\Captivate2017( $this->get_item_id() );

						if ( ! $module->register() ) {
							return $this->cancel_upload();
						}
						break;

					case 'iSpring' :
						$module = new Module\iSpring( $this->get_item_id() );

						if ( $this->get_subtype() == 'web' ) {
							$module->set_subtype( 'web' );
						}

						if ( ! $module->register() ) {
							return $this->cancel_upload();
						}
						break;

					case 'ArticulateRise' :
						$module = new Module\ArticulateRise( $this->get_item_id() );
						if ( ! $module->register() ) {
							return $this->cancel_upload();
						}
						break;

					case 'AR2017' :
						$module = new Module\ArticulateRise2017( $this->get_item_id() );
						if ( ! $module->register() ) {
							return $this->cancel_upload();
						}
						break;
					
					case 'AR360':
						$module = new Module\ArticulateRise( $this->get_item_id() );
						if ( ! $module->register() ) {
							return $this->cancel_upload();
						}
						break;

					/* add Presenter360 tin can format */
					case 'Presenter360' :
						$module = new Module\Presenter360( $this->get_item_id() );

						if ( ! $module->register() ) {
							return $this->cancel_upload();
						}
						break;
					/* END Presenter360 */

					/* add Lectora tin can format */
					case 'Lectora' :
						$module = new Module\Lectora( $this->get_item_id() );

						if ( ! $module->register() ) {
							return $this->cancel_upload();
						}
						break;
					/* END Lectora */

					/* add Scorm tin can format */
					case 'Scorm' :
						$module = new Module\Scorm( $this->get_item_id() );

						if ( ! $module->register() ) {
							return $this->cancel_upload();
						}
						break;
					/* END Scorm */
					/* add xAPI tin can format */
					case 'Tincan' :
						$module = new Module\Xapi( $this->get_item_id() );

						if ( ! $module->register() ) {
							return $this->cancel_upload();
						}
						break;
					/* END Scorm */

					default:
						return $this->cancel_upload();
						break;
				}

			} else {
				$this->upload_error = 'The zip could not be extracted. Please contact your server administrator.';

				return $this->cancel_upload();
			}
		} catch ( \Exception $e ) {
			$this->upload_error = 'The zip could not be extracted. The following errors were encountered while trying to extract the uploaded zip file. Please contact your server administrator.';
			$this->upload_error .= $e->getMessage();

			return $this->cancel_upload();
		}
	}

	private function link_file_path( $rel_path ) {
		$this->file = $this->get_dir_path() . '/temp.zip';
		$this->extract_zip();

		\TINCANNYSNC\Module_CRUD::add_detail( $this->get_item_id(), 'unknown', $this->get_target_url() . '/' . $rel_path, null );
	}

	private function extract_zip() {
		/** @var \WP_Filesystem_Base $wp_filesystem */
		global $wp_filesystem;
		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		WP_Filesystem();

		$target = $this->get_target_dir();
		$r      = unzip_file( $this->file, $target );
		if ( is_wp_error( $r ) ) {
			wp_die( $r->get_error_message() );
		}

		// Check for improperly packed modules ( single sub directory ).
		$sub_dir_check = $this->dirToArray( $target, true );
		if ( 1 === count( $sub_dir_check ) ) {
			$this->remove_subdirectory( array_keys( $sub_dir_check)[0] );
		}

		$this->structure = $this->dirToArray( $target, false );
	}

	private function get_file_type() {

		/**
		 * Filters whether to check for Rustici SCORM packages before other formats.
		 *
		 * @param bool     $check_rustici_scorm Whether to prioritize Rustici SCORM detection. Default false.
		 * @param NewFile  $this                Current NewFile instance for context.
		 * @return bool True to check Rustici SCORM first, false otherwise.
		 */
		$check_rustici_scorm = apply_filters( 'uo_tincan_validate_rustici_scorm_first', false, $this );
		
		/* Check if it is Rustici SCORM */
		if ( true === $check_rustici_scorm && $this->is_rustici_scorm() ) {
			return 'Scorm';
		}
		/* END Rustici SCORM */

		if ( $this->is_storyline() ) {
			return 'Storyline';
		}

		if ( $this->is_captivate() ) {
			return 'Captivate';
		}

		if ( $this->is_ispring() ) {
			return 'iSpring';
		}

		if ( $this->is_articulate_rise() ) {
			return 'ArticulateRise';
		}

		if ( $this->is_ispring_web() ) {
			return 'iSpring';
		}

		if ( $this->is_captivate2017() ) {
			return 'Captivate2017';
		}

		if ( $this->is_articulate_rise_2017() ) {
			return 'AR2017';
		}

		if ( $this->is_articulate_rise_360() ) {
			return 'AR360';
		}

		/* add Presenter360 tin can format */
		if ( $this->is_presenter_360() ) {
			return 'Presenter360';
		}
		/* END Presenter360 */

		/* add Lectora tin can format */
		if ( $this->is_lectora() ) {
			return 'Lectora';
		}
		/* END Lectora */

		/* add SCORM tin can format */
		if ( $this->is_scorm() ) {
			return 'Scorm';
		}
		/* END SCORM */

		/* add XAPI tin can format */
		if ( $this->is_xapi() ) {
			return 'Tincan';
		}

		/* END XAPI */

		return false;
	}

	/**
	 * Check if the extracted content is a Rustici SCORM package.
	 *
	 * Rustici SCORM packages are identified by the presence of imsmanifest.xml
	 * and Rustici-specific scormdriver files.
	 *
	 * @since  X.X.X
	 * @access private
	 * @return bool True if Rustici SCORM package detected, false otherwise.
	 */
	private function is_rustici_scorm() {
		$target = $this->get_target_dir();
		
		return file_exists( $target . '/imsmanifest.xml' ) &&
			   ( file_exists( $target . '/scormdriver/indexAPI.html' ) || file_exists( $target . '/scormdriver/scormdriver.js' ) );
	}

	private function is_storyline() {
		$target = $this->get_target_dir();
		if ( file_exists( $target . '/story_content' ) ) {
			return true;
		}

		return false;
	}

	/* add Lectora tin can format */
	private function is_lectora() {
		$target = $this->get_target_dir();
		if ( file_exists( $target . '/a001index.html' ) ) {
			return true;
		}

		return false;
	}

	/* END Lectora */

	private function is_captivate() {
		$target = $this->get_target_dir();

		if ( file_exists( $target . '/project.txt' ) && ! file_exists( $target . '/scormdriver.js' ) ) {
			$this->set_subtype( 'web' );
		}

		if ( file_exists( $target . '/project.txt' ) ) {
			return true;
		}

		return false;
	}

	private function is_ispring() {
		$target = $this->get_target_dir();
		if ( file_exists( $target . '/res/index.html' ) ) {
			return true;
		}

		return false;
	}

	private function is_ispring_web() {
		$target = $this->get_target_dir();
		if ( file_exists( $target . '/data' ) && file_exists( $target . '/metainfo.xml' ) ) {
			$this->set_subtype( 'web' );

			return true;
		}

		return false;
	}


	private function is_articulate_rise() {
		$target = $this->get_target_dir();
		if ( file_exists( $target . '/scormcontent/lib/main.bundle.js' ) ) {
			return true;
		}

		return false;
	}

	private function is_articulate_rise_2017() {
		$target = $this->get_target_dir();
		if (
			file_exists( $target . '/index.html' ) &&
			file_exists( $target . '/tc-config.js' ) &&
			file_exists( $target . '/tincan.xml' ) &&
			file_exists( $target . '/lib/tincan.js' )
		) {
			return true;
		}

		return false;
	}

	private function is_articulate_rise_360() {
		$target = $this->get_target_dir();
		return file_exists( $target . '/scormcontent/lib/rise' );
	}

	private function is_captivate2017() {
		$target = $this->get_target_dir();
		if ( file_exists( $target . '/captivate.css' ) ) {
			return true;
		}

		return false;
	}

	private function is_scorm() {
		$target = $this->get_target_dir();
		if ( file_exists( $target . '/imsmanifest.xml' ) ) {
			return true;
		}

		return false;
	}

	private function is_xapi() {
		$target = $this->get_target_dir();
		if ( file_exists( $target . '/tincan.xml' ) ) {
			return true;
		}

		return false;
	}


	/* add Presenter360 tin can format */
	private function is_presenter_360() {
		$target = $this->get_target_dir();
		if ( file_exists( $target . '/presentation_content/user.js' ) ) {
			return true;
		}

		return false;
	}

	/* END Presenter360 */

	private function cancel_upload() {
		$target = $this->get_target_dir();

		\TINCANNYSNC\Module_CRUD::delete( $this->get_item_id() );
		$this->uploaded = false;

		if ( ! class_exists( 'WP_Filesystem_Base' ) || ! class_exists( 'WP_Filesystem_Direct' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		}
		$WP_Filesystem_Direct = new \WP_Filesystem_Direct( false );
		$WP_Filesystem_Direct->delete( $target, true, 'd' );


		if ( file_exists( $this->get_dir_path() . '/temp.zip' ) ) {
			unlink( $this->get_dir_path() . '/temp.zip' );
		}

		move_uploaded_file( $this->file, $this->get_dir_path() . '/temp.zip' );

		if ( empty( $this->upload_error ) ) {
			$this->upload_error = __( 'There has been an error registering the module.', "uncanny-learndash-reporting" );
		}

		return false;
	}

	public function get_result_json( $title ) {
		$array = array(
			'id'      => $this->get_item_id(),
			'message' => __( 'Uploaded! Pick Options Below.', "uncanny-learndash-reporting" ),
			'title'   => $title,
		);

		return json_encode( $array );
	}

	private function dirToArray( $dir, $all_files = false ) {
		$result = array();

		$cdir = scandir( $dir );

		foreach ( $cdir as $key => $value ) {
			if ( ! in_array( $value, array( ".", ".." ) ) ) {
				if ( is_dir( $dir . DIRECTORY_SEPARATOR . $value ) ) {
					$result[ $value ] = $this->dirToArray( $dir . DIRECTORY_SEPARATOR . $value, $all_files );
				} else {
					// Include all files for subdirectory check.
					if ( $all_files ) {
						$result[] = $value;
						continue;
					}

					// Only include html files.
					$is_html = substr( $value, ( - strlen( '.html' ) ) ) === '.html' || substr( $value, ( - strlen( '.html5' ) ) ) === '.html5';

					if ( $is_html ) {
						$result[] = $value;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Move all files from a subdirectory to the target directory and then remove the subdirectory.
	 *
	 * @param string $subdir
	 *
	 * @return void
	 */
	private function remove_subdirectory( $subdir ) {
		$target               = $this->get_target_dir();
		$sub_path             = $target . DIRECTORY_SEPARATOR . $subdir;
		$wp_filesystem_direct = new \WP_Filesystem_Direct( false );

		// Move all files from the subdirectory to the target directory
		$this->recursivly_move_files( $sub_path, $target, $wp_filesystem_direct );

		// Remove the subdirectory
		$wp_filesystem_direct->delete( $sub_path, true, 'd' );
	}

	/**
	 * Recursivly move files from one directory to another
	 *
	 * @param string $source
	 * @param string $target
	 * @param \WP_Filesystem_Direct $wp_filesystem_direct
	 *
	 * @return void
	 */
	private function recursivly_move_files( $source, $target, $wp_filesystem_direct ) {
		$dirlist = $wp_filesystem_direct->dirlist( $source );
		foreach ( $dirlist as $filename => $fileinfo ) {
			if ( 'f' == $fileinfo['type'] ) {
				$wp_filesystem_direct->move( $source . DIRECTORY_SEPARATOR . $filename, $target . DIRECTORY_SEPARATOR . $filename, true);
			} else if ( 'd' == $fileinfo['type'] ) {
				$wp_filesystem_direct->mkdir( $target . DIRECTORY_SEPARATOR . $filename );
				$this->recursivly_move_files( $source . DIRECTORY_SEPARATOR . $filename, $target . DIRECTORY_SEPARATOR . $filename, $wp_filesystem_direct );
			}
		}
	}

}

