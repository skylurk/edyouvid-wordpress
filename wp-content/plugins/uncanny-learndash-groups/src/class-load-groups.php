<?php

namespace uncanny_learndash_groups;

/**
 * Class Boot
 *
 * @package uncanny_learndash_groups
 */
class Load_Groups {

	/**
	 * The instance of the class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Load_Groups
	 */
	private static $instance = null;

	/**
	 * The directories that are auto loaded and initialized
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array
	 */
	private static $auto_loaded_classes = null;
	/**
	 * @var
	 */
	public static $class_instances;

	/**
	 * @return Load_Groups|null
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {

			// Lets boot up!
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * class constructor
	 */
	private function __construct() {

		// Load text domain on init with earliest priority.
		add_action(
			'init',
			array(
				$this,
				'uncanny_learndash_groups_text_domain',
			),
			0
		);

		$this->initialize_core();

		// Import Gutenberg Blocks
		require_once ULGM_ABSPATH . 'src/blocks/blocks.php';
		new Blocks();

		// Add group management role
		$this->groups_capabilities();

		// Add settings link on plugin page
		$uncanny_learndash_groups_plugin_basename = plugin_basename( UNCANNY_GROUPS_PLUGIN_FILE );
		add_filter(
			'plugin_action_links_' . $uncanny_learndash_groups_plugin_basename,
			array(
				__CLASS__,
				'uncanny_learndash_groups_plugin_settings_link',
			),
			22
		);
		// Add feature image support to LD Groups
		add_filter(
			'register_post_type_args',
			array(
				__CLASS__,
				'uo_groups_add_support',
			),
			99,
			2
		);

		add_action( 'admin_init', array( $this, 'update_ld_group_id_of_new_col' ) );
		add_action( 'admin_init', array( $this, 'update_email_options_autoload' ) );
		//add_action( 'admin_init', array( $this, 'update_ld_group_id_of_new_col_for_enrollment_keys' ) );
		add_action( 'admin_init', array( $this, 'cleanup_duplicate_group_enrollments' ) );
		add_action( 'migrate_ld_group_id_of_new_col', array( $this, 'migrate_ld_group_id_of_new_col' ) );
		add_action(
			'upgrader_enrollment_keys_update_event',
			array(
				$this,
				'migrate_ld_group_id_of_new_col_for_enrollment_keys',
			)
		);
		add_action( 'upgrader_process_complete', array( $this, 'flag_last_updated' ), 10, 2 );
		add_action( 'upgrader_db_upgrade_event', array( $this, 'upgrader_db_upgrade' ) );

		add_action( 'admin_print_scripts', array( $this, 'hide_nonuncanny_groups_warnings' ) );
		add_action( 'admin_head', array( $this, 'hide_nonuncanny_groups_warnings' ), PHP_INT_MAX );
	}

	/**
	 * @return void
	 */
	public function update_ld_group_id_of_new_col() {
		if ( ! empty( get_option( 'ulgm_ld_group_id_updated', '' ) ) ) {
			return;
		}
		wp_schedule_single_event( time() + 5, 'migrate_ld_group_id_of_new_col' );
	}

	/**
	 * @return void
	 */
	public function update_ld_group_id_of_new_col_for_enrollment_keys() {
		if ( ! empty( get_option( 'ulgm_ld_group_id_updated_for_enrollment_keys', '' ) ) ) {
			return;
		}
		wp_schedule_single_event( time() + 10, 'upgrader_enrollment_keys_update_event' );
	}

	/**
	 * @return void
	 */
	public function migrate_ld_group_id_of_new_col() {
		global $wpdb;
		$tbl_groups   = SharedFunctions::$db_group_tbl;
		$ld_group_ids = $wpdb->get_results( "SELECT `ID`, `ld_group_id` FROM $wpdb->prefix{$tbl_groups}" );
		if ( empty( $ld_group_ids ) ) {
			update_option( 'ulgm_ld_group_id_updated', time() );

			return;
		}
		$tbl_codes = SharedFunctions::$db_group_codes_tbl;
		foreach ( $ld_group_ids as $_ld_group_id ) {
			$ld_group_id = (int) $_ld_group_id->ld_group_id;
			$group_id    = (int) $_ld_group_id->ID;

			$sql = $wpdb->prepare( "UPDATE $wpdb->prefix{$tbl_codes} SET ld_group_id = %d WHERE student_id IS NOT NULL AND group_id = %d", $ld_group_id, $group_id );
			$wpdb->query( $sql );
		}

		update_option( 'ulgm_ld_group_id_updated', time() );
	}

	/**
	 * @return void
	 */
	public function migrate_ld_group_id_of_new_col_for_enrollment_keys() {
		global $wpdb;
		$tbl_groups   = SharedFunctions::$db_group_tbl;
		$ld_group_ids = $wpdb->get_results( "SELECT `ID`, `ld_group_id` FROM $wpdb->prefix{$tbl_groups}" );
		if ( empty( $ld_group_ids ) ) {
			update_option( 'ulgm_ld_group_id_updated_for_enrollment_keys', time() );

			return;
		}

		$tbl_codes = SharedFunctions::$db_group_codes_tbl;

		foreach ( $ld_group_ids as $_ld_group_id ) {
			$ld_group_id = (int) $_ld_group_id->ld_group_id;
			$group_id    = (int) $_ld_group_id->ID;

			// Update enrolled user codes (if ld_group_id is null)
			$sql = $wpdb->prepare( "UPDATE $wpdb->prefix{$tbl_codes} SET ld_group_id = %d WHERE student_id IS NOT NULL AND group_id = %d AND ld_group_id IS NULL", $ld_group_id, $group_id );
			$wpdb->query( $sql );

			// Update invited user codes
			$sql = $wpdb->prepare( "UPDATE $wpdb->prefix{$tbl_codes} SET ld_group_id = %d WHERE student_id IS NULL AND user_email IS NOT NULL AND code_status = %s AND group_id = %d", $ld_group_id, 'not redeemed', $group_id );
			$wpdb->query( $sql );
		}

		update_option( 'ulgm_ld_group_id_updated_for_enrollment_keys', time() );
	}

	/**
	 * Load plugin text domain.
	 *
	 * @since 1.0.0
	 */
	public function uncanny_learndash_groups_text_domain() {
		// Only load if not already loaded
		if ( ! is_textdomain_loaded( 'uncanny-learndash-groups' ) ) {
			load_plugin_textdomain( 'uncanny-learndash-groups', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}
	}

	/**
	 *
	 */
	public function initialize_core() {

		// Load Utilities
		$this->initialize_utilities();

		// Load Configuration
		$this->initialize_config();

		// Statically load Groups file to speed up plugin
		$this->require_class_files();
		add_action(
			'woocommerce_loaded',
			array(
				$this,
				'load_woo_functions',
			),
			9
		);
	}

	/**
	 *
	 */
	public function load_woo_functions() {
		include_once __DIR__ . '/global-woo-functions.php';
		\Uncanny_Groups_Woo::get_instance();
	}

	/**
	 * Initialize Static singleton class that has shared function and variables
	 * that can be used anywhere in WP
	 *
	 * @since 1.0.0
	 */
	private function initialize_utilities() {
		include_once ULGM_ABSPATH . 'src/classes/class-utilities.php';
		Utilities::get_instance();
	}

	/**
	 * Initialize Static singleton class that configures all constants,
	 * utilities variables and handles activation/deactivation
	 *
	 * @since 1.0.0
	 */
	private function initialize_config() {

		include_once ULGM_ABSPATH . 'src/classes/class-config.php';
		$config_instance = Config::get_instance();

		$plugin_name = apply_filters( 'ulgm_plugin_name', 'Uncanny Groups for LearnDash' );

		$config_instance->configure_plugin_before_boot( $plugin_name, 'ulgm', UNCANNY_GROUPS_VERSION, UNCANNY_GROUPS_PLUGIN_FILE, false );
	}

	/**
	 *
	 */
	public function require_class_files() {
		if ( is_admin() ) {
			self::$auto_loaded_classes['AdminMenu']             = __DIR__ . '/classes/admin/class-admin-menu.php';
			self::$auto_loaded_classes['AdminPage']             = __DIR__ . '/classes/admin/class-admin-page.php';
			self::$auto_loaded_classes['Admin_Support']         = __DIR__ . '/classes/admin/class-admin-support.php';
			self::$auto_loaded_classes['AdminGroupEmailFields'] = __DIR__ . '/classes/admin/class-admin-group-email-fields.php';
			self::$auto_loaded_classes['Tools']                 = __DIR__ . '/classes/admin/tools/class-tools.php';

			// Licensing
			self::$auto_loaded_classes['Licensing'] = __DIR__ . '/classes/admin/licensing/licensing.php';

			// Install Automator.
			self::$auto_loaded_classes['Install_Automator'] = __DIR__ . '/classes/admin/install-automator/install-automator.php';

			// LearnDash Group is deleted
			self::$auto_loaded_classes['ProcessGroupDeletion'] = __DIR__ . '/classes/admin/process-group-deletion.php';

			// LearnDash modifications
			self::$auto_loaded_classes['LearnDash_Modifications'] = __DIR__ . '/classes/learndash/class-learndash-modifications.php';
		}

		self::$auto_loaded_classes['AdminCreateGroup'] = __DIR__ . '/classes/admin/class-admin-create-group.php';

		// Global / Helpers
		self::$auto_loaded_classes['Admin_Rest_API']           = __DIR__ . '/classes/admin/class-admin-rest-api.php';
		self::$auto_loaded_classes['Group_Management_Helpers'] = __DIR__ . '/classes/group-management/class-group-management-helpers.php';
		self::$auto_loaded_classes['RestApiEndPoints']         = __DIR__ . '/classes/helpers/rest-api-end-points.php';
		self::$auto_loaded_classes['SharedFunctions']          = __DIR__ . '/classes/helpers/shared-functions.php';
		self::$auto_loaded_classes['SharedVariables']          = __DIR__ . '/classes/helpers/shared-variables.php';
		// Migrate LearnDash groups
		self::$auto_loaded_classes['MigrateLearndashGroups'] = __DIR__ . '/classes/admin/migrate-learndash-groups.php';

		// Includes
		self::$auto_loaded_classes['Database'] = __DIR__ . '/includes/database.php';

		if ( Utilities::if_woocommerce_active() ) {

			add_action(
				'before_woocommerce_init',
				function () {
					if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
						\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', UNCANNY_GROUPS_PLUGIN_FILE, false );
					}
				}
			);

			// Bulk discount
			self::$auto_loaded_classes['WoocommerceBulkDiscount']             = __DIR__ . '/classes/woocommerce/woocommerce-bulk-discount.php';
			self::$auto_loaded_classes['WoocommerceBuyCourses']               = __DIR__ . '/classes/woocommerce/woocommerce-buy-courses.php';
			self::$auto_loaded_classes['WoocommerceCourses']                  = __DIR__ . '/classes/woocommerce/woocommerce-courses.php';
			self::$auto_loaded_classes['WooCommerceLearndashGroups']          = __DIR__ . '/classes/woocommerce/woocommerce-learndash-groups.php';
			self::$auto_loaded_classes['WoocommerceLicense']                  = __DIR__ . '/classes/woocommerce/woocommerce-license.php';
			self::$auto_loaded_classes['WoocommerceModifyGroup']              = __DIR__ . '/classes/woocommerce/woocommerce-modify-group.php';
			self::$auto_loaded_classes['Woo_Product_Visibility']              = __DIR__ . '/classes/woocommerce/woocommerce-product-visibility.php';
			self::$auto_loaded_classes['WoocommercePaymentComplete']          = __DIR__ . '/classes/woocommerce/woocommerce-payment-complete.php';
			self::$auto_loaded_classes['WoocommerceGroupLicenseSwapProducts'] = __DIR__ . '/classes/woocommerce/woocommerce-group-license-swap.php';
			self::$auto_loaded_classes['WoocommerceMinMaxQuantity']           = __DIR__ . '/classes/woocommerce/woocommerce-min-max-quantity.php';
			self::$auto_loaded_classes['WoocommerceLicenseRefund']            = __DIR__ . '/classes/woocommerce/woocommerce-license-refund.php';
		}

		if ( Utilities::if_woocommerce_active() && Utilities::if_woocommerce_subscription_active() ) {
			self::$auto_loaded_classes['WoocommerceLicenseSubscription'] = __DIR__ . '/classes/woocommerce/woocommerce-license-subscription.php';
		}

		self::$auto_loaded_classes['ProcessManualGroup'] = __DIR__ . '/classes/helpers/process-manual-group.php';
		self::$auto_loaded_classes['UserCodeRedemption'] = __DIR__ . '/classes/helpers/user-code-redemption.php';

		// Shortcode
		self::$auto_loaded_classes['DataShortcodes']       = __DIR__ . '/classes/shortcodes/data-shortcodes.php';
		self::$auto_loaded_classes['MemberStyleShortcode'] = __DIR__ . '/classes/shortcodes/member-style-shortcode.php';

		// LearnDash
		self::$auto_loaded_classes['LearndashFunctionOverrides']       = __DIR__ . '/classes/learndash/learndash-function-overrides.php';
		self::$auto_loaded_classes['LearndashGroupsPostEditAdditions'] = __DIR__ . '/classes/learndash/learndash-groups-post-edit-additions.php';

		// BuddyBoss
		if ( defined( 'BP_PLATFORM_VERSION' ) ) {
			self::$auto_loaded_classes['BuddyBossSync'] = __DIR__ . '/classes/buddyboss/buddy-boss-sync.php';
		}

		// Reports
		self::$auto_loaded_classes['LearnDashProgressReport'] = __DIR__ . '/classes/reports/learndash-progress-report.php';
		self::$auto_loaded_classes['GroupAssignments']        = __DIR__ . '/classes/reports/group-assignments.php';
		self::$auto_loaded_classes['GroupEssays']             = __DIR__ . '/classes/reports/group-essays.php';
		self::$auto_loaded_classes['GroupQuizReport']         = __DIR__ . '/classes/reports/group-quiz-report.php';
		self::$auto_loaded_classes['GroupReportsInterface']   = __DIR__ . '/classes/reports/group-reports-interface.php';

		// Gravity Forms
		if ( Utilities::if_gravity_forms_active() ) {
			self::$auto_loaded_classes['GravityFormsSupport']   = __DIR__ . '/classes/gravity-forms/gravity-forms-support.php';
			self::$auto_loaded_classes['GravityFormsCodeField'] = __DIR__ . '/classes/gravity-forms/gravity-forms-code-field.php';
		}

		// Formidable Forms
		if ( Utilities::if_formidable_active() ) {
			self::$auto_loaded_classes['Formidable'] = __DIR__ . '/classes/formidable/class-formidable.php';
		}

		// Formidable Forms
		if ( Utilities::if_wpforms_active() ) {
			self::$auto_loaded_classes['WPForms'] = __DIR__ . '/classes/wpforms/class-wpforms.php';
		}

		// Theme My Login
		if ( Utilities::if_tml_active() ) {
			self::$auto_loaded_classes['ThemeMyLoginSupport'] = __DIR__ . '/classes/theme-my-login/theme-my-login-support.php';
		}

		// Yoast SEO
		self::$auto_loaded_classes['YoastOverrides'] = __DIR__ . '/classes/yoast-seo/class-yoast-overrides.php';

		// Group Management
		self::$auto_loaded_classes['GroupManagementInterface']     = __DIR__ . '/classes/group-management/group-management-interface.php';
		self::$auto_loaded_classes['EditGroupWizard']              = __DIR__ . '/classes/group-management/class-edit-group-wizard.php';
		self::$auto_loaded_classes['GroupManagementRegistration']  = __DIR__ . '/classes/group-management/group-management-registration.php';
		self::$auto_loaded_classes['ManagementGroupMultiAddUsers'] = __DIR__ . '/classes/group-management/management-group-multi-add-users.php';

		$this->auto_initialize_classes();
	}

	/**
	 * Looks through all defined directories and modifies file name to create
	 * new class instance.
	 *
	 * @since 1.0.0
	 */
	private function auto_initialize_classes() {
		if ( empty( self::$auto_loaded_classes ) ) {
			return;
		}
		// Check each directory
		foreach ( self::$auto_loaded_classes as $class_name => $file ) {
			require $file;
			$class = __NAMESPACE__ . '\\' . $class_name;

			$obj = new $class();
			Utilities::set_class_instance( $class_name, $obj );
			self::$class_instances[ $class_name ] = $obj;
		}
	}

	/**
	 *
	 */
	public function groups_capabilities() {

		// Set which roles will need access
		$set_role_capabilities = array(
			'group_leader'  => array( 'ulgm_group_management' ),
			'administrator' => array( 'ulgm_group_management' ),
		);

		/**
		 * Filters role based capabilities before being added
		 *
		 * @param string $set_role_capabilities Path to the plugins template folder
		 *
		 * @since 1.0.0
		 */
		$set_role_capabilities = apply_filters( 'ulgm_add_role_capabilities', $set_role_capabilities );

		include_once __DIR__ . '/includes/capabilities.php';
		$capabilities = new Capabilities( $set_role_capabilities );
		$capabilities->add_capabilities();
	}

	/**
	 * @param $links
	 *
	 * @return mixed
	 */
	public static function uncanny_learndash_groups_plugin_settings_link( $links ) {
		$settings_link = '<a href="' . admin_url( 'admin.php?page=uncanny-groups' ) . '">Licensing</a>';
		array_unshift( $links, $settings_link );
		$settings_link = '<a href="' . admin_url( 'admin.php?page=uncanny-groups' ) . '">Settings</a>';
		array_unshift( $links, $settings_link );

		return $links;
	}


	/**
	 * Add support to LearnDash Groups for feature image.
	 *
	 * @param array $args LD group post type.
	 * @param $post_type
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public static function uo_groups_add_support( $args, $post_type ) {
		if ( 'groups' !== $post_type ) {
			return $args;
		}

		if ( isset( $args['supports'] ) ) {
			$args['supports'][] = 'thumbnail';
		} else {
			$args['supports'] = array( 'thumbnail' );
		}

		return $args;
	}

	/**
	 * @param $upgrader_object
	 * @param $options
	 *
	 * @return void
	 */
	public function flag_last_updated( $upgrader_object, $options ) {

		// Bail if the options are not set.
		if ( ! isset( $options['action'] ) || ! isset( $options['type'] ) ) {
			return;
		}

		// Check if it's a plugin update.
		if ( 'update' !== $options['action'] || 'plugin' !== $options['type'] ) {
			return;
		}

		// The plugins being updated are stored in the 'plugins' key of the options array.
		if ( ! isset( $options['plugins'] ) || ! is_array( $options['plugins'] ) ) {
			return;
		}

		// The path to the specific plugin to check for, relative to the wp-content/plugins directory.
		$specific_plugin_path = plugin_basename( UNCANNY_GROUPS_PLUGIN_FILE );

		foreach ( $options['plugins'] as $plugin_path ) {
			if ( $plugin_path !== $specific_plugin_path ) {
				continue;
			}

			wp_schedule_single_event( time() + 5, 'upgrader_db_upgrade_event' );
			wp_schedule_single_event( time() + 15, 'upgrader_enrollment_keys_update_event' );
		}
	}

	/**
	 * @return void
	 */
	public function upgrader_db_upgrade() {
		ulgm()->db->create_tables();
	}

	/**
	 * @return void
	 */
	public function hide_nonuncanny_groups_warnings() {
		$groups_pages = array(
			'uncanny-groups-create-group',
			'uncanny-learndash-groups-bulk-discount',
			'uncanny-groups-kb',
			'uncanny-groups',
			'uncanny-groups-email-settings',
			'uncanny-groups-tools-tools',
			'uncanny-groups-plugins',
			'uncanny-groups',
			'groups-install-automator',
		);

		// Bail if we're not on a uncanny-groups screen.
		if ( empty( $_REQUEST['page'] ) || ! in_array( strtolower( $_REQUEST['page'] ), $groups_pages, true ) ) {
			return;
		}

		global $wp_filter;
		if ( ! empty( $wp_filter['user_admin_notices']->callbacks ) && is_array( $wp_filter['user_admin_notices']->callbacks ) ) {
			foreach ( $wp_filter['user_admin_notices']->callbacks as $priority => $hooks ) {
				foreach ( $hooks as $name => $arr ) {
					if ( is_object( $arr['function'] ) && $arr['function'] instanceof \Closure ) {
						unset( $wp_filter['user_admin_notices']->callbacks[ $priority ][ $name ] );
						continue;
					}
					if ( ! empty( $arr['function'][0] ) && is_object( $arr['function'][0] ) && strpos( strtolower( get_class( $arr['function'][0] ) ), 'uncanny' ) !== false ) {
						continue;
					}
					if ( ! empty( $name ) && strpos( $name, 'uncanny_groups' ) === false ) {
						unset( $wp_filter['user_admin_notices']->callbacks[ $priority ][ $name ] );
					}
				}
			}
		}

		if ( ! empty( $wp_filter['admin_notices']->callbacks ) && is_array( $wp_filter['admin_notices']->callbacks ) ) {
			foreach ( $wp_filter['admin_notices']->callbacks as $priority => $hooks ) {
				foreach ( $hooks as $name => $arr ) {
					if ( is_object( $arr['function'] ) && $arr['function'] instanceof \Closure ) {
						unset( $wp_filter['admin_notices']->callbacks[ $priority ][ $name ] );
						continue;
					}
					if ( is_array( $arr['function'] ) && ! empty( $arr['function'][0] ) && is_object( $arr['function'][0] ) && strpos( strtolower( get_class( $arr['function'][0] ) ), 'uncanny_groups' ) !== false ) {
						continue;
					}
					if ( ! empty( $name ) && strpos( $name, 'uncanny_groups' ) === false ) {
						unset( $wp_filter['admin_notices']->callbacks[ $priority ][ $name ] );
					}
				}
			}
		}

		if ( ! empty( $wp_filter['all_admin_notices']->callbacks ) && is_array( $wp_filter['all_admin_notices']->callbacks ) ) {
			foreach ( $wp_filter['all_admin_notices']->callbacks as $priority => $hooks ) {
				foreach ( $hooks as $name => $arr ) {
					if ( is_object( $arr['function'] ) && $arr['function'] instanceof \Closure ) {
						unset( $wp_filter['all_admin_notices']->callbacks[ $priority ][ $name ] );
						continue;
					}
					if ( is_array( $arr['function'] ) && ! empty( $arr['function'][0] ) && is_object( $arr['function'][0] ) && strpos( strtolower( get_class( $arr['function'][0] ) ), 'uncanny_groups' ) !== false ) {
						continue;
					}
					if ( ! empty( $name ) && strpos( $name, 'uncanny_groups' ) === false ) {
						unset( $wp_filter['all_admin_notices']->callbacks[ $priority ][ $name ] );
					}
				}
			}
		}
	}



	/**
	 * Update email options autoload value.
	 *
	 * This function updates the autoload value to 'no' for the specified email options.
	 *
	 * @uses $wpdb
	 * @uses update_option()
	 *
	 * @return void
	 */
	public function update_email_options_autoload() {
		if ( ! empty( get_option( 'ulgm_email_options_autoload_updated', '' ) ) ) {
			return;
		}

		global $wpdb;

		$option_keys = array(
			'ulgm_invitation_user_email_subject',
			'ulgm_invitation_user_email_body',
			'ulgm_user_welcome_email_subject',
			'ulgm_user_welcome_email_body',
			'ulgm_existing_user_welcome_email_subject',
			'ulgm_existing_user_welcome_email_body',
			'ulgm_group_leader_welcome_email_subject',
			'ulgm_group_leader_welcome_email_body',
			'ulgm_existing_group_leader_welcome_email_subject',
			'ulgm_existing_group_leader_welcome_email_body',
			'ulgm_email_from',
			'ulgm_name_from',
			'ulgm_reply_to',
			'ulgm_new_group_purchase_email_subject',
			'ulgm_new_group_purchase_email_body',
			'ulgm_send_code_redemption_email',
			'ulgm_send_user_welcome_email',
			'ulgm_send_existing_user_welcome_email',
			'ulgm_send_group_leader_welcome_email',
			'ulgm_send_existing_group_leader_welcome_email',
			'ulgm_send_new_group_purchase_email',
		);

		foreach ( $option_keys as $option_key ) {
			$wpdb->update(
				$wpdb->prefix . 'options',
				array( 'autoload' => 'no' ),
				array( 'option_name' => $option_key )
			);
		}

		update_option( 'ulgm_email_options_autoload_updated', time(), false );
	}

	/**
	 * One-time cleanup of duplicate group enrollments where users are enrolled in both parent and child groups
	 *
	 * @return void
	 */
	public function cleanup_duplicate_group_enrollments() {
		// Check if cleanup has already run
		if ( ! empty( get_option( 'ulgm_duplicate_enrollments_cleaned', '' ) ) ) {
			return;
		}

		// Check if pool seats are enabled globally
		if ( ! SharedFunctions::is_pool_seats_enabled_for_all_groups() ) {
			update_option( 'ulgm_duplicate_enrollments_cleaned', time() );
			return;
		}

		// Get all LearnDash groups
		$groups = get_posts(
			array(
				'post_type'      => 'groups',
				'posts_per_page' => 9999,
				'post_status'    => 'publish',
			)
		);

		global $wpdb;

		// Audit data structure
		$audit_data = array(
			'total_groups_processed' => 0,
			'groups_affected'        => array(),
			'total_users_affected'   => 0,
			'cleanup_details'        => array(),
		);

		foreach ( $groups as $group ) {
			// Skip if pool seats not enabled for this group
			if ( ! SharedFunctions::is_pool_seats_enabled_for_current_parent_group( $group->ID ) ) {
				continue;
			}

			// Get child groups
			$child_groups = learndash_get_group_children( $group->ID );
			if ( empty( $child_groups ) ) {
				continue;
			}

			++$audit_data['total_groups_processed'];
			$group_affected = false;

			foreach ( $child_groups as $child_group_id ) {
				// Find users enrolled in both parent and child groups
				$sql = $wpdb->prepare(
					"SELECT DISTINCT u1.user_id 
					FROM {$wpdb->prefix}usermeta u1 
					JOIN {$wpdb->prefix}usermeta u2 ON u1.user_id = u2.user_id 
					WHERE u1.meta_key = %s 
					AND u2.meta_key = %s",
					'learndash_group_users_' . $group->ID,
					'learndash_group_users_' . $child_group_id
				);

				$duplicate_users = $wpdb->get_col( $sql );

				if ( empty( $duplicate_users ) ) {
					continue;
				}

				foreach ( $duplicate_users as $user_id ) {
					// Get enrollment dates
					$parent_enrolled_at = get_user_meta( $user_id, 'learndash_group_' . $group->ID . '_enrolled_at', true );
					$child_enrolled_at  = get_user_meta( $user_id, 'learndash_group_' . $child_group_id . '_enrolled_at', true );

					// If child enrollment is older, remove parent enrollment
					if ( $child_enrolled_at && $parent_enrolled_at && (int) $child_enrolled_at < (int) $parent_enrolled_at ) {
						delete_user_meta( $user_id, 'learndash_group_users_' . $group->ID );
						delete_user_meta( $user_id, 'learndash_group_' . $group->ID . '_enrolled_at' );
						delete_user_meta( $user_id, 'group_' . $group->ID . '_access_from' );

						// Record audit data
						$group_affected = true;
						++$audit_data['total_users_affected'];

						// Store detailed cleanup information
						$audit_data['cleanup_details'][] = array(
							'user_id'            => $user_id,
							'parent_group_id'    => $group->ID,
							'child_group_id'     => $child_group_id,
							'parent_enrolled_at' => $parent_enrolled_at,
							'child_enrolled_at'  => $child_enrolled_at,
							'timestamp'          => current_time( 'timestamp' ),
						);
					}
				}
			}

			// If this group had any affected users, add it to the groups affected list
			if ( $group_affected ) {
				$audit_data['groups_affected'][] = array(
					'group_id'   => $group->ID,
					'group_name' => $group->post_title,
					'children'   => $child_groups,
				);
			}
		}

		// Add summary data
		$audit_data['summary'] = array(
			'cleanup_date'          => current_time( 'mysql' ),
			'total_groups_scanned'  => count( $groups ),
			'total_groups_affected' => count( $audit_data['groups_affected'] ),
			'total_users_affected'  => $audit_data['total_users_affected'],
		);

		// Store audit data with autoload disabled
		update_option( 'ulgm_duplicate_enrollments_audit', $audit_data, false );

		// Mark cleanup as completed
		update_option( 'ulgm_duplicate_enrollments_cleaned', time() );
	}
}
