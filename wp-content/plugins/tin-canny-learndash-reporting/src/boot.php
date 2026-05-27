<?php

namespace uncanny_learndash_reporting;

/**
 * Class Boot
 */
class Boot extends Config {

	/**
	 * Class constructor
	 */
	public function __construct() {

		// Load licensing detail
		self::load_licensing_detail();

		add_action( 'admin_init', array( __CLASS__, 'uo_reporting_register_option' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_help_submenu' ), 31 );
		add_action( 'admin_menu', array( __CLASS__, 'add_checkpage_submenu' ), 33 );
		add_action( 'admin_menu', array( __CLASS__, 'add_uncanny_plugins_page' ), 32 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_external_scripts' ) );
		add_action( 'admin_init', array( __CLASS__, 'uo_admin_help_process' ) );
		add_action( 'admin_head', [$this, 'hide_admin_notices'] );

		// Check if the protection is enabled
		if ( get_option( 'tincanny_nonce_protection', 'yes' ) === 'yes' ) {
			self::create_protection_htaccess();
		}

		if ( get_option( 'tincanny_snc_dir_permissions', 'no' ) === 'no' ) {
			self::update_snc_dir_permissions();
		}

		// Register cron hook for folder size calculation
		add_action( 'tincanny_calculate_folder_size', array( __CLASS__, 'update_folder_size' ), 10, 1 );

		// Clean up broken cron events (one-time only)
		add_action( 'admin_init', array( __CLASS__, 'cleanup_broken_cron_events' ) );

		//add_action( 'init', array( __CLASS__, 'maybe_redirect_report_to_group_id' ) );
	}

	/**
     * Hide WordPress admin notices for specific plugin report pages.
     *
     * This method checks the current `page` query parameter and removes
     * all `admin_notices` and `all_admin_notices` actions if the page matches
     * one of the defined reporting pages.
     *
     * @return void
     */
    public function hide_admin_notices() {
        if ( ! isset($_GET['page']) ) {
            return;
        }

        $pages_to_hide = [
            'uncanny-learnDash-reporting',
            'uncanny-tincanny-user-report',
            'uncanny-tincanny-tin-can-report',
            'uncanny-tincanny-xapi-quiz-report',
        ];

        if (in_array($_GET['page'], $pages_to_hide, true)) {
            remove_all_actions( 'admin_notices' );
            remove_all_actions( 'all_admin_notices' );
        }
    }

	/**
	 * @return void
	 */
	public static function load_licensing_detail() {

		// URL of store powering the plugin
		define( 'UO_REPORTING_STORE_URL', 'https://licensing.uncannyowl.com/' ); // you should use your own CONSTANT name, and be sure to replace it throughout this file

		// Store download name/title
		define( 'UO_REPORTING_ITEM_NAME', 'Tin Canny LearnDash Reporting' ); // you should use your own CONSTANT name, and be sure to replace it throughout this file
		define( 'UO_REPORTING_ITEM_ID', 4113 ); // you should use your own CONSTANT name, and be sure to replace it throughout this file


		add_action( 'admin_head', array( __CLASS__, 'maybe_filter_non_tincanny_admin_notices' ), PHP_INT_MAX );
		add_action( 'admin_print_scripts', array( __CLASS__, 'maybe_filter_non_tincanny_admin_notices' ), PHP_INT_MAX );

		/* Licensing */
		// Setup menu and page options in admin
		if ( is_admin() ) {

			// Licensing is not autoloaded, load manually
			include_once self::get_include( 'licensing.php' );

			// Create a new instance of EDD Liccensing
			$licensing = new Licensing();

			// Create sub-page for EDD licensing
			$licensing->page_name   = 'Uncanny Reporting License Activation';
			$licensing->page_slug   = 'uncanny-reporting-license-activation';
			$licensing->parent_slug = 'uncanny-learnDash-reporting';
			$licensing->store_url   = UO_REPORTING_STORE_URL;
			$licensing->item_name   = UO_REPORTING_ITEM_NAME;
			$licensing->item_id     = UO_REPORTING_ITEM_ID;
			$licensing->author      = 'Uncanny Owl';
			$licensing->add_licensing();

		}
	}

	/**
	 * Create .htaccess file in the uncanny-snc folder
	 *
	 * @return void
	 */
	public static function create_protection_htaccess() {
		// Check if the constant with the name of the Tin Canny folder is defined
		if ( ! defined( 'SnC_UPLOAD_DIR_NAME' ) ) { // phpcs:ignore Generic.NamingConventions
			// If it's not, then define it
			define( 'SnC_UPLOAD_DIR_NAME', 'uncanny-snc' ); // phpcs:ignore Generic.NamingConventions
		}

		$wp_upload_dir = wp_upload_dir();
		$upload_dir    = $wp_upload_dir['basedir'] . '/' . SnC_UPLOAD_DIR_NAME;

		if ( file_exists( $upload_dir ) ) {
			if ( ! file_exists( $upload_dir . '/.htaccess' ) ) {
				if ( defined( 'UO_ABS_PATH' ) ) {

					require_once ABSPATH . 'wp-admin/includes/file.php';
					global $wp_filesystem;
					\WP_Filesystem();

					$slashed_home = trailingslashit( get_option( 'home' ) );
					$base         = wp_parse_url( $slashed_home, PHP_URL_PATH );

					$htaccess_file = <<<EOF
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase {$base}
RewriteRule ^index\.php$ - [L]
RewriteRule ^(?:|(?:\/|\\\\))([0-9]{1,})((?:.*(?:\/|\\\\))|.*\.(?:(?:html|htm)(?:|.*)))$ {$base}index.php?tincanny_content_id=$1&tincanny_file_path=$2 [QSA,L,NE]
</IfModule>
EOF;

					$wp_filesystem->put_contents( $upload_dir . '/.htaccess', $htaccess_file );
				}
			}
		}
	}

	/**
	 * Update the permissions of the uncanny-snc folder
	 *
	 * @return void
	 */
	public static function update_snc_dir_permissions() {
		// Check if the constant with the name of the Tin Canny folder is defined
		if ( ! defined( 'SnC_UPLOAD_DIR_NAME' ) ) { // phpcs:ignore Generic.NamingConventions
			// If it's not, then define it
			define( 'SnC_UPLOAD_DIR_NAME', 'uncanny-snc' ); // phpcs:ignore Generic.NamingConventions
		}

		// Get the upload directory (uncanny-snc folder)
		$wp_upload_dir = wp_upload_dir();
		$upload_dir    = $wp_upload_dir['basedir'] . '/' . SnC_UPLOAD_DIR_NAME;

		if ( file_exists( $upload_dir ) ) {
			// Check current permissions of the directory.
			$dir_perms = substr( sprintf( '%o', fileperms( $upload_dir ) ), - 4 );
			// If the permissions are not 0755, change them.
			if ( '0755' !== $dir_perms ) {
				$result = @chmod( $upload_dir, 0755 );
			}
		}
		update_option( 'tincanny_snc_dir_permissions', 'yes' );
	}

//	public static function maybe_redirect_report_to_group_id() {
//
//		if( ! is_admin() ){
//			return;
//		}
//
//		if( !isset( $_GET['page'] ) || 'uncanny-learnDash-reporting' !== $_GET['page'] ){
//			return;
//		}
//
//		$mode = get_option( 'tincanny_user_report_default_group', 'all' );
//		$query_param = array();
//
//		if( ! isset( $_GET['tab'] ) ){
//			$query_param['tab'] = 'courseReportTab';
//		}
//
//		if ( isset( $_GET['tab'] ) && 'tin-can' === $_GET['tab'] && 'xapi-tincan' === $_GET['tab'] ) {
//			return;
//		}
//
//		if ( ! isset( $_GET['group_id'] ) ) {
//			$query_param['group_id'] = $mode;
//		}
//
//		if( empty( $query_param ) ){
//			return;
//		}
//
//		wp_safe_redirect( add_query_arg($query_param ) );
//
//		return;
//	}

	/**
	 * Delete .htaccess file in the uncanny-snc folder
	 *
	 * @return void
	 */
	public static function delete_protection_htaccess() {
		// Check if the constant with the name of the Tin Canny folder is defined
		if ( ! defined( 'SnC_UPLOAD_DIR_NAME' ) ) { // phpcs:ignore Generic.NamingConventions
			// If it's not, then define it
			define( 'SnC_UPLOAD_DIR_NAME', 'uncanny-snc' ); // phpcs:ignore Generic.NamingConventions
		}

		// Get the upload directory (uncanny-snc folder)
		$wp_upload_dir = wp_upload_dir();
		$upload_dir    = $wp_upload_dir['basedir'] . '/' . SnC_UPLOAD_DIR_NAME;

		// Check if the folder exists
		if ( file_exists( $upload_dir ) ) {
			// Check if the .htaccess was created in the uncanny-snc folder
			if ( file_exists( $upload_dir . '/.htaccess' ) ) {
				// Require file.php. Use require_once to avoid including it again
				// if it's already there
				require_once ABSPATH . 'wp-admin/includes/file.php';
				// Get global wp_filesystem
				global $wp_filesystem;
				// Create instance of WP_Filesystem
				\WP_Filesystem();

				// Remove the file
				$wp_filesystem->delete( $upload_dir . '/.htaccess' );
			}
		}
	}

	/**
	 * Add "Help" submenu
	 */
	public static function add_help_submenu() {
		add_submenu_page(
			'uncanny-learnDash-reporting',
			__( 'Tin Canny Reporting for LearnDash Support', 'uncanny-learndash-reporting' ),
			__( 'Help', 'uncanny-learndash-reporting' ),
			'manage_options',
			'uncanny-tincanny-kb',
			array( __CLASS__, 'include_help_page' )
		);
	}

	/**
	 * Create "Uncanny Plugins" submenu
	 */
	public static function add_uncanny_plugins_page() {
		add_submenu_page(
			'uncanny-learnDash-reporting',
			__( 'Uncanny LearnDash Plugins', 'uncanny-learndash-reporting' ),
			__( 'LearnDash Plugins', 'uncanny-learndash-reporting' ),
			'manage_options',
			'uncanny-tincanny-plugins',
			array( __CLASS__, 'include_learndash_plugins_page' )
		);
	}

	/**
	 * Add "Check Page" submenu
	 */
	public static function add_checkpage_submenu() {
		add_submenu_page(
			'uncanny-learnDash-reporting',
			__( 'Tin Canny Reporting for LearnDash Support', 'uncanny-learndash-reporting' ),
			__( 'Site check', 'uncanny-learndash-reporting' ),
			'manage_options',
			'uncanny-tincanny-site-check',
			array( __CLASS__, 'include_site_check_page' )
		);
	}

	/**
	 * Include "Help" template
	 */
	public static function include_help_page() {
		include 'templates/admin-help.php';
	}

	/**
	 * Include "Help" template
	 */
	public static function include_site_check_page() {
		include 'templates/admin-site-check.php';
	}

	/**
	 * Include "LearnDash Plugins" template
	 */
	public static function include_learndash_plugins_page() {
		include 'templates/admin-learndash-plugins.php';
	}

	/**
	 * Enqueue external scripts from uncannyowl.com
	 */
	public static function enqueue_external_scripts() {
		$pages_to_include = array( 'uncanny-tincanny-plugins', 'uncanny-tincanny-kb', 'uncanny-tincanny-site-check' );
		$page             = ultc_filter_has_var( 'page' ) ? ultc_filter_input( 'page' ) : false;
		if ( $page && in_array( $page, $pages_to_include, true ) ) {
			wp_enqueue_style( 'uncannyowl-core', 'https://uncannyowl.com/wp-content/mu-plugins/uncanny-plugins-core/dist/bundle.min.css', array(), Config::get_version() );
			wp_enqueue_script( 'uncannyowl-core', 'https://uncannyowl.com/wp-content/mu-plugins/uncanny-plugins-core/dist/bundle.min.js', array( 'jquery' ), Config::get_version(), false );

			wp_enqueue_style( 'tclr-icons', Config::get_admin_css( 'icons.css' ), array(), UNCANNY_REPORTING_VERSION );
			wp_enqueue_style( 'tclr-select2', Config::get_admin_css( 'select2.min.css' ), array(), UNCANNY_REPORTING_VERSION );
			wp_enqueue_style( 'reporting-admin', Config::get_admin_css( 'admin-style.css' ), array(), UNCANNY_REPORTING_VERSION );
		}

		if ( $page && 'uncanny-tincanny-site-check' === $page ) {
			// Get Tin Canny settings
			$tincanny_settings = \TINCANNYSNC\Admin\Options::get_options();

			// API data
			$reporting_api_setup = array(
				'root'            => home_url(),
				'nonce'           => \wp_create_nonce( 'tincanny-module' ),
				'isAdmin'         => is_admin(),
				'editUsers'       => current_user_can( 'edit_users' ),
				'optimized_build' => '1',
				'test_user_email' => wp_get_current_user()->user_email,
				'page'            => 'reporting',
				'showTinCanTab'   => 1 === (int) $tincanny_settings['tinCanActivation'] ? '1' : '0',
			);

			wp_localize_script( 'uncannyowl-core', 'reportingApiSetup', $reporting_api_setup );
			wp_enqueue_script( 'uncannyowl-core' );
		}
	}

	/**
	 * Submit ticket
	 */
	public static function uo_admin_help_process() {
		if ( ultc_filter_has_var( 'tclr-send-ticket', INPUT_POST ) && check_admin_referer( 'uncanny0w1', 'tclr-send-ticket' ) ) {
			$name        = esc_html( ultc_filter_input( 'fullname', INPUT_POST ) );
			$email       = esc_html( ultc_filter_input( 'email', INPUT_POST ) );
			$website     = esc_url_raw( ultc_filter_input( 'website', INPUT_POST ) );
			$license_key = esc_html( ultc_filter_input( 'license_key', INPUT_POST ) );
			$message     = esc_html( ultc_filter_input( 'message', INPUT_POST ) );
			$siteinfo    = ultc_filter_has_var( 'siteinfo', INPUT_POST ) ? stripslashes( $_POST['siteinfo'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$message     = '<h3>Message:</h3><br/>' . wpautop( $message );
			if ( ! empty( $website ) ) {
				$message .= '<hr /><strong>Website:</strong> ' . $website;
			}
			if ( ! empty( $license_key ) ) {
				$message .= '<hr /><strong>License:</strong> <a href="https://www.uncannyowl.com/wp-admin/edit.php?post_type=download&page=edd-licenses&s=' . $license_key . '" target="_blank">' . $license_key . '</a>';
			}
			if ( ultc_filter_has_var( 'site-data', INPUT_POST ) && 'yes' === sanitize_text_field( ultc_filter_input( 'site-data', INPUT_POST ) ) ) {
				$message = "$message<hr /><h3>User Site Information:</h3><br />{$siteinfo}";
			}

			$to        = 'support.41077.bb1dda3d33afb598@helpscout.net';
			$subject   = esc_html( ultc_filter_input( 'subject', INPUT_POST ) );
			$headers   = array( 'Content-Type: text/html; charset=UTF-8' );
			$headers[] = 'From: ' . $name . ' <' . $email . '>';
			$headers[] = 'Reply-To:' . $name . ' <' . $email . '>';
			wp_mail( $to, $subject, $message, $headers );
			if ( ultc_filter_has_var( 'page', INPUT_POST ) ) {
				$url = admin_url( 'admin.php' ) . '?page=' . esc_html( ultc_filter_input( 'page', INPUT_POST ) ) . '&sent=true&wpnonce=' . wp_create_nonce();
				wp_safe_redirect( $url );
				exit;
			}
		}
	}

	/**
	 * Register reporting license option
	 *
	 * @return void
	 */
	public static function uo_reporting_register_option() {
		// creates our settings in the options table
		register_setting(
			'uo_reporting_license',
			'uo_reporting_license_key',
			array(
				__CLASS__,
				'uo_reporting_sanitize_license',
			)
		);
	}

	/**
	 * Sanitize the license key
	 *
	 * @param $new
	 *
	 * @return mixed
	 */
	public static function uo_reporting_sanitize_license( $new ) {
		$old = get_option( 'uo_reporting_license_key' );
		if ( $old && $old !== $new ) {
			delete_option( 'uo_reporting_license_status' ); // new license has been entered, so must reactivate
		}

		return $new;
	}

	/**
	 * Maybe apply notification filters to Tin Canny pages.
	 *
	 * @return void
	 */
	public static function maybe_filter_non_tincanny_admin_notices() {

		$tincanny_pages = array(
			'uncanny-reporting-license-activation',
			'uncanny-learndash-reporting',
			'manage-content',
			'snc_options',
			'uncanny-tincanny-kb',
			'uncanny-tincanny-plugins',
			'uncanny-tincanny-site-check',
		);

		// Bail if we're not on a Tin Canny screen.
		if ( empty( $_REQUEST['page'] ) || ! in_array( strtolower( $_REQUEST['page'] ), $tincanny_pages, true ) ) {
			return;
		}

		// Run filter on all admin notices.
		self::filter_non_tincanny_admin_notices( 'user_admin_notices' );
		self::filter_non_tincanny_admin_notices( 'admin_notices' );
		self::filter_non_tincanny_admin_notices( 'all_admin_notices' );
	}

	/**
	 * Filter out all notices that are not from Tin Canny.
	 *
	 * @param string $notice_type The type of notice to filter.
	 *
	 * @return void
	 */
	public static function filter_non_tincanny_admin_notices( $notice_type ) {
		global $wp_filter;

		if ( empty( $wp_filter[ $notice_type ] ) ) {
			return;
		}

		if ( ! is_array( $wp_filter[ $notice_type ]->callbacks ) ) {
			return;
		}

		// All Tin Canny lowercased namespaces.
		$allowed_sources = array(
			'uncanny_learndash_reporting',
			'uo_reporting_learndash_version_notice',
			'uncanny_owl',
			'tincannysnc',
			'uctincan',
			'tincan',
		);

		foreach ( $wp_filter[ $notice_type ]->callbacks as $priority => $hooks ) {
			foreach ( $hooks as $name => $arr ) {
				if ( is_object( $arr['function'] ) && $arr['function'] instanceof \Closure ) {
					unset( $wp_filter[ $notice_type ]->callbacks[ $priority ][ $name ] );
					continue;
				}

				// Determine the source of the notice
                $source = '';
				if ( isset( $arr['function'] ) && is_array( $arr['function'] ) && ! empty( $arr['function'][0] ) && is_object( $arr['function'][0] ) ) {
                    $source = strtolower( get_class( $arr['function'][0] ) );
                } elseif ( ! empty( $name ) ) {
                    $source = strtolower( $name );
                }

				// Remove the notice if its source is not in the list of allowed sources
                $allowed = false;
                foreach ( $allowed_sources as $allowed_source ) {
                    if ( strpos( $source, $allowed_source ) !== false) {
                        $allowed = true;
                        break;
                    }
                }

                if ( ! $allowed ) {
                    unset( $wp_filter[ $notice_type ]->callbacks[ $priority ][ $name ] );
                }
			}
		}
	}

	/**
	 * Clean up broken cron events (one-time only)
	 * 
	 * @return void
	 */
	public static function cleanup_broken_cron_events() {
		// Only run for administrators
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		// Check if cleanup has already been done
		if ( get_option( 'tincanny_cron_cleanup_done' ) ) {
			return;
		}
		
		// Clear all instances of the broken cron hook
		wp_clear_scheduled_hook( 'tincanny_run_activity_name_hash_migration_event' );
		
		// Mark cleanup as done to prevent running again
		update_option( 'tincanny_cron_cleanup_done', true );
	}

	/**
	 * Update folder size in database
	 *
	 * @param int $folder_id The folder ID
	 */
	public static function update_folder_size( $folder_id ) {
		global $wpdb;

		$size = self::calculate_folder_size( $folder_id );

		$wpdb->update(
			$wpdb->prefix . \UCTINCAN\Database::TABLE_SNC_FILE_INFO,
			array( 'size' => $size ),
			array( 'ID' => $folder_id ),
			array( '%d' ),
			array( '%d' )
		);
	}


	/**
	 * Calculate folder size for a given folder ID
	 *
	 * @param int $folder_id The folder ID
	 * @return int Size in bytes
	 */
	private static function calculate_folder_size( $folder_id ) {
		// Get the upload directory
		$upload_dir = wp_upload_dir();
		$folder_path = $upload_dir['basedir'] . '/uncanny-snc/' . $folder_id;

		if( ! is_dir( $folder_path ) ) {
			return 0;
		}

		// Initialize WordPress filesystem
		global $wp_filesystem;
		if ( ! function_exists( '\WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Initialize the filesystem if not already initialized
		if ( ! $wp_filesystem ) {
			\WP_Filesystem();
		}

		// Get list of files in directory
		$file_list = $wp_filesystem->dirlist( $folder_path, false );

		if ( empty( $file_list ) ) {
			return 0;
		}

		$size = 0;
		foreach ( $file_list as $name => $file ) {
			if ( 'f' === $file['type'] ) {
				$size += $file['size'];
			} elseif ( 'd' === $file['type'] ) {
				$size += self::calculate_folder_size( $folder_id . '/' . $name );
			}
		}

		return $size;
	}
}
