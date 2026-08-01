<?php
/**
 * Database Controller
 *
 * @package    Tin Canny Reporting for LearnDash
 * @subpackage Embed Articulate Storyline and Adobe Captivate
 * @author     Uncanny Owl
 * @since      1.0.0
 */

namespace TINCANNYSNC;

if ( ! defined( 'UO_ABS_PATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 *
 */
class Module_CRUD {
	/**
	 *
	 */
	const TBL_MODULES = 'snc_file_info';

	/**
	 * @param $name
	 *
	 * @return int
	 */
	public static function add_item( $name ) {

		global $wpdb;
		$wpdb->insert( $wpdb->prefix . \UCTINCAN\Database::TABLE_SNC_FILE_INFO, array( 'file_name' => $name ) );
		return $wpdb->insert_id;
	}

	/**
	 * Add Detail to ID
	 *
	 * @since 0.0.1
	 * @access public
	 *
	 * @changed 1.3.7 Add Subtype
	 */
	public static function add_detail( $item_id, $type, $url, $subtype, $version = UNCANNY_REPORTING_VERSION ) {
		global $wpdb;
		
		$wpdb->update(
			$wpdb->prefix . \UCTINCAN\Database::TABLE_SNC_FILE_INFO,
			array(
				'type'    => $type,
				'subtype' => $subtype,
				'url'     => $url,
				'version' => $version,
				'upload_date' => current_time( 'mysql' ),
			),
			array( 'ID' => $item_id )
		);

		// Schedule a single event to calculate size in 10 seconds
		if( ! wp_next_scheduled( 'tincanny_calculate_folder_size', array( $item_id ) ) ) {
			wp_schedule_single_event( time() + 2, 'tincanny_calculate_folder_size', array( $item_id ) );
		}
	}

	/**
	 * @param $id
	 *
	 * @return void
	 */
	public static function delete( $id ) {

		global $wpdb;
		$wpdb->delete( $wpdb->prefix . \UCTINCAN\Database::TABLE_SNC_FILE_INFO, array( 'ID' => $id ) );
	}

	/**
	 * @param $where
	 *
	 * @return array|object|\stdClass[]|null
	 */
	public static function get_modules( $where = '' ) {

		global $wpdb;
		return $wpdb->get_results( sprintf( 'SELECT * FROM %s %s ORDER BY `ID` DESC', $wpdb->prefix . \UCTINCAN\Database::TABLE_SNC_FILE_INFO, $where ), OBJECT );
	}

	/**
	 * @param $item_id
	 *
	 * @return array|false|object|\stdClass|null
	 */
	public static function get_item( $item_id ) {
		if ( ! $item_id ) {
			return false;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . \UCTINCAN\Database::TABLE_SNC_FILE_INFO;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE ID = %s;", $item_id ), ARRAY_A );
	}

	/**
	 * @param $id
	 * @param $title
	 *
	 * @return mixed
	 */
	public static function change_name_from_id( $id, $title ) {

		global $wpdb;
		$table_name    = $wpdb->prefix . \UCTINCAN\Database::TABLE_SNC_FILE_INFO;
		$title_from_db = $wpdb->get_var( $wpdb->prepare( "SELECT file_name FROM {$table_name} WHERE id = %s", $id ) );

		if ( $title_from_db != $title ) {
			$wpdb->query( $wpdb->prepare( "UPDATE {$table_name} SET file_name = '%s' WHERE id = %s", $title, $id ) );
		}

		return $title;
	}

	/**
	 * @param $search
	 * @param $limit
	 * @param $order_by
	 *
	 * @return array|object|\stdClass[]|null
	 */
	public static function get_contents( $search = '', $limit = '', $order_by = '' ) {

		global $wpdb;
		$table_name = $wpdb->prefix . \UCTINCAN\Database::TABLE_SNC_FILE_INFO;
		$where      = '';

		if ( ! empty( $search ) ) {
			$where = " WHERE file_name LIKE '%{$search}%' OR type LIKE '%{$search}%' ";
		}

		if ( empty( $order_by ) ) {
			$order_by = 'ORDER BY `ID` DESC';
		}

		return $wpdb->get_results( sprintf( "SELECT *, file_name as content FROM {$table_name} %s %s %s ", $where, $order_by, $limit ), OBJECT );
	}

	/**
	 * @param $search
	 *
	 * @return string|null
	 */
	public static function get_contents_count( $search = '' ) {

		global $wpdb;
		$table_name = $wpdb->prefix . \UCTINCAN\Database::TABLE_SNC_FILE_INFO;
		$where      = '';

		if ( ! empty( $search ) ) {
			$where = " WHERE file_name LIKE '%{$search}%' OR type LIKE '%{$search}%' ";
		}

		return $wpdb->get_var( sprintf( "SELECT COUNT(*) FROM {$table_name} %s ", $where ) );
	}

	/**
	 * Update item title by ID
	 *
	 * @since 3.2
	 * @access public
	 *
	 */
	public static function update_item_title( $item_id, $title, $version = UNCANNY_REPORTING_VERSION ) {

		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . \UCTINCAN\Database::TABLE_SNC_FILE_INFO,
			array(
				'file_name' => $title,
				'version'   => $version,
				'size'      => 0,
			),
			array( 'ID' => $item_id )
		);

		// Schedule a single event to calculate size in 2 seconds
		if( ! wp_next_scheduled( 'tincanny_calculate_folder_size', array( $item_id ) ) ) {
			wp_schedule_single_event( time() + 60, 'tincanny_calculate_folder_size', array( $item_id ) );
		}
	}

	/**
	 * Cache-bust query param appended to relative JS/CSS URLs in module HTML after replace.
	 */
	const ASSET_CACHE_PARAM = 'uo_sncv';

	const ASSET_CACHE_MAX_HTML_FILES = 100;

	const ASSET_CACHE_MAX_DIRECTORY_DEPTH = 15;

	const ASSET_CACHE_MAX_HTML_BYTES = 5242880; // 5 MB.

	/**
	 * Whether register() is replacing existing content (not a first-time upload).
	 *
	 * Full-zip replace uses a {id}-temp folder. Other replaces reuse an ID that
	 * already has a launch URL from a previous successful registration.
	 *
	 * @param int|string $item_id Module item ID (may include `-temp` during full-zip replace).
	 *
	 * @return bool
	 */
	public static function is_replace_registration( $item_id ) {
		if ( is_string( $item_id ) && false !== strpos( $item_id, '-temp' ) ) {
			return true;
		}

		$item_id = absint( $item_id );
		if ( ! $item_id ) {
			return false;
		}

		$item = self::get_item( $item_id );

		return is_array( $item ) && ! empty( $item['url'] );
	}

	/**
	 * Patch module HTML so browsers load fresh JS/CSS after a replace (not fresh uploads).
	 *
	 * @param string|false $module_directory Absolute path to the module upload folder.
	 *
	 * @return void
	 */
	public static function bust_asset_cache_in_html_files( $module_directory ) {
		$module_dir = self::get_validated_module_directory( $module_directory );
		if ( ! $module_dir ) {
			return;
		}

		$filesystem = self::get_wp_filesystem_for_cache_bust();
		if ( ! $filesystem ) {
			return;
		}

		$version    = (string) time();
		$html_files = self::collect_module_html_files( $filesystem, $module_dir, $module_dir, 0 );
		$patched    = 0;

		foreach ( $html_files as $file_path ) {
			if ( $patched >= self::ASSET_CACHE_MAX_HTML_FILES ) {
				break;
			}

			if ( self::patch_module_html_file_cache_busters( $filesystem, $module_dir, $file_path, $version ) ) {
				++$patched;
			}
		}
	}

	/**
	 * @param string|false $target Normalized absolute module directory path candidate.
	 *
	 * @return string|false Normalized absolute module directory path.
	 */
	private static function get_validated_module_directory( $target ) {
		if ( ! $target ) {
			return false;
		}

		$target = wp_normalize_path( $target );

		if ( ! defined( 'SnC_UPLOAD_DIR_NAME' ) ) { // phpcs:ignore Generic.NamingConventions
			define( 'SnC_UPLOAD_DIR_NAME', 'uncanny-snc' ); // phpcs:ignore Generic.NamingConventions
		}

		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			return false;
		}

		$snc_base    = wp_normalize_path( trailingslashit( $upload_dir['basedir'] ) . SnC_UPLOAD_DIR_NAME );
		$snc_real    = realpath( $snc_base );
		$module_real = realpath( $target );

		if ( false === $snc_real || false === $module_real || ! is_dir( $module_real ) ) {
			return false;
		}

		$snc_real    = wp_normalize_path( $snc_real );
		$module_real = wp_normalize_path( $module_real );

		if ( 0 !== strpos( $module_real, trailingslashit( $snc_real ) ) ) {
			return false;
		}

		return $module_real;
	}

	/**
	 * @return \WP_Filesystem_Base|false
	 */
	private static function get_wp_filesystem_for_cache_bust() {
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! is_object( $wp_filesystem ) ) {
			if ( ! WP_Filesystem() ) {
				return false;
			}
		}

		if ( ! $wp_filesystem instanceof \WP_Filesystem_Base ) {
			return false;
		}

		return $wp_filesystem;
	}

	/**
	 * @param \WP_Filesystem_Base $filesystem  WordPress filesystem.
	 * @param string              $module_dir  Validated module root path.
	 * @param string              $directory   Directory to scan.
	 * @param int                 $depth       Recursion depth.
	 *
	 * @return string[]
	 */
	private static function collect_module_html_files( $filesystem, $module_dir, $directory, $depth ) {
		if ( $depth > self::ASSET_CACHE_MAX_DIRECTORY_DEPTH ) {
			return array();
		}

		if ( ! self::is_path_inside_module_directory( $module_dir, $directory ) || ! $filesystem->is_dir( $directory ) ) {
			return array();
		}

		$listing = $filesystem->dirlist( $directory, true, false );
		if ( ! is_array( $listing ) ) {
			return array();
		}

		$files = array();

		foreach ( $listing as $info ) {
			if ( count( $files ) >= self::ASSET_CACHE_MAX_HTML_FILES ) {
				break;
			}

			if ( ! is_array( $info ) || empty( $info['name'] ) ) {
				continue;
			}

			$entry_path = wp_normalize_path( trailingslashit( $directory ) . $info['name'] );

			if ( ! self::is_path_inside_module_directory( $module_dir, $entry_path ) ) {
				continue;
			}

			if ( isset( $info['type'] ) && 'd' === $info['type'] ) {
				$files = array_merge( $files, self::collect_module_html_files( $filesystem, $module_dir, $entry_path, $depth + 1 ) );
				continue;
			}

			if ( self::is_allowed_module_html_file( $filesystem, $entry_path ) ) {
				$files[] = $entry_path;
			}
		}

		return $files;
	}

	/**
	 * @param string $module_dir Module root path.
	 * @param string $path       Path to validate.
	 *
	 * @return bool
	 */
	private static function is_path_inside_module_directory( $module_dir, $path ) {
		$module_real = realpath( $module_dir );
		$entry_real  = realpath( wp_normalize_path( $path ) );

		if ( false === $module_real || false === $entry_real ) {
			return false;
		}

		$module_real = wp_normalize_path( $module_real );
		$entry_real  = wp_normalize_path( $entry_real );

		if ( $entry_real === $module_real ) {
			return true;
		}

		return 0 === strpos( $entry_real, trailingslashit( $module_real ) );
	}

	/**
	 * @param \WP_Filesystem_Base $filesystem WordPress filesystem.
	 * @param string              $file_path  File path.
	 *
	 * @return bool
	 */
	private static function is_allowed_module_html_file( $filesystem, $file_path ) {
		if ( ! $filesystem->is_file( $file_path ) ) {
			return false;
		}

		$extension = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );

		return in_array( $extension, array( 'html', 'htm' ), true );
	}

	/**
	 * @param \WP_Filesystem_Base $filesystem WordPress filesystem.
	 * @param string              $module_dir Validated module root.
	 * @param string              $file_path  HTML file path.
	 * @param string              $version    Cache-buster version.
	 *
	 * @return bool
	 */
	private static function patch_module_html_file_cache_busters( $filesystem, $module_dir, $file_path, $version ) {
		if ( ! self::is_path_inside_module_directory( $module_dir, $file_path ) || ! self::is_allowed_module_html_file( $filesystem, $file_path ) ) {
			return false;
		}

		$filesize = $filesystem->size( $file_path );
		if ( false === $filesize || $filesize < 1 || $filesize > self::ASSET_CACHE_MAX_HTML_BYTES ) {
			return false;
		}

		$content = $filesystem->get_contents( $file_path );
		if ( false === $content || '' === $content ) {
			return false;
		}

		$updated = self::apply_asset_cache_content_patches( $content, $version );
		if ( null === $updated || $updated === $content ) {
			return false;
		}

		return $filesystem->put_contents( $file_path, $updated, FS_CHMOD_FILE );
	}

	/**
	 * @param string $url     Relative asset URL.
	 * @param string $version Numeric cache-buster.
	 *
	 * @return string
	 */
	private static function append_asset_cache_param_to_url( $url, $version ) {
		$version = (string) absint( $version );
		$param   = self::ASSET_CACHE_PARAM;

		if ( ! $version || ! is_string( $url ) || '' === $url ) {
			return $url;
		}

		if ( false !== strpos( $url, $param . '=' ) ) {
			return $url;
		}

		$hash          = '';
		$hash_position = strpos( $url, '#' );
		if ( false !== $hash_position ) {
			$hash = substr( $url, $hash_position );
			$url  = substr( $url, 0, $hash_position );
		}

		$separator = ( false !== strpos( $url, '?' ) ) ? '&' : '?';

		return $url . $separator . $param . '=' . $version . $hash;
	}

	/**
	 * @param string $version Numeric cache-buster.
	 *
	 * @return string
	 */
	private static function get_dynamic_asset_script_src_expression( $version ) {
		$version = (string) absint( $version );
		$param   = self::ASSET_CACHE_PARAM;

		return '(function(u){var h="",i=u.indexOf("#");if(i>-1){h=u.slice(i);u=u.slice(0,i);}if(u.indexOf("http")===0||u.indexOf("//")===0)return u;if(u.indexOf("' . $param . '=")>-1)return u+h;return u+(u.indexOf("?")>-1?"&":"?")+"' . $param . '=' . $version . '"+h;})(src)';
	}

	/**
	 * @param string $content HTML contents.
	 * @param string $version Cache-buster version.
	 *
	 * @return string
	 */
	private static function apply_asset_cache_content_patches( $content, $version ) {
		$version = (string) absint( $version );
		if ( ! $version || ! is_string( $content ) || '' === $content ) {
			return $content;
		}

		$patched_content = preg_replace_callback(
			'/\b(src|href)\s*=\s*(["\'])(?!https?:|\/\/|data:)([^"\']+\.(?:js|mjs|css)(?:\?[^"\']*)?)\2/i',
			function ( $matches ) use ( $version ) {
				$patched = self::append_asset_cache_param_to_url( $matches[3], $version );

				return $matches[1] . '=' . $matches[2] . $patched . $matches[2];
			},
			$content
		);

		if ( null === $patched_content ) {
			return $content;
		}

		$content = $patched_content;

		$dynamic_src = 'script.src = ' . self::get_dynamic_asset_script_src_expression( $version ) . ';';

		$dynamic_patched = preg_replace( '/script\.src\s*=\s*src\s*;/', $dynamic_src, $content );

		if ( null !== $dynamic_patched ) {
			$content = $dynamic_patched;
		}

		return $content;
	}
}
