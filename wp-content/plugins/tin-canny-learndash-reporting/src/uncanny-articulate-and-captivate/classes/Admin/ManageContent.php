<?php
/**
 * Admin Manage Content Controller
 *
 * @package    Tin Canny Reporting for LearnDash
 * @subpackage Embed Articulate Storyline and Adobe Captivate
 * @author     Uncanny Owl
 * @since      3.0.0
 */

namespace TINCANNYSNC\Admin;

if ( ! defined( 'UO_ABS_PATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

class ManageContent {

	private static $tincan_database;
	private static $tincan_per_pages;

	/**
	 * Initialize
	 *
	 * @access  public
	 * @return  void
	 * @since   3.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 20 );
		add_action( 'wp_ajax_SnC_Content_Delete', array( $this, 'ajax_delete' ) );
		add_action( 'wp_ajax_SnC_Content_Bookmark_Delete', array( $this, 'ajax_delete_bookmarks_only' ) );
		add_action( 'wp_ajax_SnC_Content_Delete_All', array( $this, 'ajax_delete_all_data' ) );

		// Add shutdown hook to update folder information
		add_action( 'shutdown', array( $this, 'update_folder_info_in_db' ) );
		
		// Add body class for manage content page
		add_filter( 'admin_body_class', array( $this, 'add_manage_content_body_class' ) );
	}

	/**
	 * Register Admin Menu
	 *
	 * @trigger admin_menu Action
	 * @access  public
	 * @return  void
	 * @since   3.0.0
	 */
	public function register_admin_menu() {

		add_submenu_page(
			'uncanny-learnDash-reporting',
			'Manage Content',
			'Manage Content',
			apply_filters( 'tc_manage_content_cap', 'manage_options' ),
			'manage-content',
			array(
				$this,
				'view_content_page',
			)
		);
	}

	/**
	 * Page loaded from admin_menu
	 *
	 * @trigger view_content_page
	 * @access  public
	 * @return  void
	 * @since   3.0.0
	 */
	public function view_content_page() {

		include_once dirname( UO_REPORTING_FILE ) . '/src/includes/TinCan_Content_List_Table.php';
		$tincan_content_table = new \TinCan_Content_List_Table();
		$columns              = array(
			'ID'      => __( 'Module ID', 'uncanny-learndash-reporting' ),
			'content' => __( 'Content', 'uncanny-learndash-reporting' ),
			'type'    => __( 'Type', 'uncanny-learndash-reporting' ),
			'upload_date'    => __( 'Uploaded', 'uncanny-learndash-reporting' ),
			'size'    => __( 'Size', 'uncanny-learndash-reporting' ),
			'actions' => __( '', 'uncanny-learndash-reporting' ),
		);

		$tincan_content_table->column = $columns;
		unset( $columns['actions'] );
		$tincan_content_table->sortable_columns = $columns;
		$tincan_content_table->prepare_items();
		$tincan_content_table->views();

		include_once SnC_PLUGIN_DIR . 'views/manage_content.php';
	}

	/**
	 * Ajax delete module
	 *
	 * @return void
	 */
	public function ajax_delete() {

		$id     = $this->get_posted_item_id();
		$module = \TINCANNYSNC\Module::get_module( $id );
		$module->delete();

		die;
	}

	/**
	 * Ajax delete bookmarks
	 *
	 * @return void
	 */
	public function ajax_delete_bookmarks_only() {

		$id     = $this->get_posted_item_id();
		$module = \TINCANNYSNC\Module::get_module( $id );
		$url    = str_replace( site_url(), '', $module->get_url() );
		\UCTINCAN\Database::delete_bookmarks( $id, $url );

		die;
	}

	/**
	 * Ajax delete all data
	 *
	 * @return void
	 */
	public function ajax_delete_all_data() {

		$id     = $this->get_posted_item_id();
		$module = \TINCANNYSNC\Module::get_module( $id );
		$url    = str_replace( site_url(), '', $module->get_url() );

		\UCTINCAN\Database::delete_all_data( $id, $url );

		die;
	}


	/**
	 * Get Item ID from POST
	 *
	 * @return mixed int | dies
	 */
	private function get_posted_item_id() {

		if ( ultc_get_filter_var( 'mode', '', INPUT_POST ) !== 'vc' ) {
			check_ajax_referer( 'snc-media_enbed_form', 'security' );
		}

		$id = ultc_get_filter_var( 'item_id', 0, INPUT_POST );
		if ( empty( $id ) || ! is_numeric( $id ) ) {
			die;
		}

		return $id;
	}


	/**
	 * Update folder information in database
	 */
	public function update_folder_info_in_db() {
		// Check if we've run this recently
		$last_run = get_option( 'tincanny_folder_info_last_update' );
		if ( ! empty( $last_run ) ) {
			return;
		}

		global $wpdb;
		
		// Get the upload directory
		$upload_dir = wp_upload_dir();
		$snc_dir = $upload_dir['basedir'] . '/uncanny-snc';
		
		// Initialize WordPress filesystem
		global $wp_filesystem;
		if ( ! function_exists( '\WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		
		// Initialize the filesystem if not already initialized
		if ( ! $wp_filesystem ) {
			\WP_Filesystem();
		}
		
		// Get list of directories
		$dir_list = $wp_filesystem->dirlist( $snc_dir, false );
		if ( empty( $dir_list ) ) {
			return;
		}
		
		// Process each directory
		foreach ( $dir_list as $name => $dir ) {
			if ( 'd' !== $dir['type'] ) {
				continue;
			}
			
			// Get folder size
			$size = $this->get_directory_size( $snc_dir . '/' . $name );
			
			// Get modified date
			$modified_date = $wp_filesystem->mtime( $snc_dir . '/' . $name );
			$modified_date = date( 'Y-m-d H:i:s', $modified_date );
			
			// Update database
			$wpdb->update(
				$wpdb->prefix . \UCTINCAN\Database::TABLE_SNC_FILE_INFO,
				array(
					'size' => $size,
					'upload_date' => $modified_date,
				),
				array( 'ID' => $name ),
				array( '%d', '%s' ),
				array( '%d' )
			);
		}

		// Save that we've run this update
		update_option( 'tincanny_folder_info_last_update', time() );
	}

	/**
	 * Get size of a directory recursively
	 *
	 * @param string $dir Directory path
	 * @return int Total size in bytes
	 */
	private function get_directory_size( $dir ) {
		global $wp_filesystem;
		$size = 0;
		
		$file_list = $wp_filesystem->dirlist( $dir, false );
		if ( ! empty( $file_list ) ) {
			foreach ( $file_list as $name => $file ) {
				if ( 'f' === $file['type'] ) {
					$size += $file['size'];
				} elseif ( 'd' === $file['type'] ) {
					$size += $this->get_directory_size( $dir . '/' . $name );
				}
			}
		}
		
		return $size;
	}

	/**
	 * Add manage content body class
	 *
	 * @param string $classes Current admin body classes
	 * @return string Modified admin body classes
	 */
	public function add_manage_content_body_class( $classes ) {
		if ( get_current_screen()->id === 'uncanny-learnDash_page_manage-content' ) {
			$classes .= ' manage-content-page';
		}
		return $classes;
	}
}
