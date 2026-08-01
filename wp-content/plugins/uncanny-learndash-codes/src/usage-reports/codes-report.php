<?php
/**
 * Codes Report Class
 *
 * A class that prepares usage report data for Uncanny Codes plugin
 * and sends it to the analytics server for tracking plugin usage statistics.
 */
namespace uncanny_learndash_codes;

use UncannyOwl\Codes\UsageReports\Report;

/**
 * Codes Report
 *
 * A class that prepares usage report data for Uncanny Codes plugin
 * and sends it to the analytics server for tracking plugin usage statistics.
 */
class CodesReport extends Report {

	/**
	 * Plugin slug identifier
	 *
	 * @var string
	 */
	const PLUGIN_SLUG = 'uncanny-codes';

	/**
	 * Plugin display name
	 *
	 * @var string
	 */
	const PLUGIN_NAME = 'Uncanny Codes';

	/**
	 * Populate license details in the report
	 *
	 * @return void
	 */
	private function populate_license_details() {
		// Check for license status specific to Codes plugin
		$this->report['license_status'] = get_option( 'uo_codes_license_status', 'not_active' );

		// If no license status found, check if there's a general uncanny license
		if ( 'not_active' === $this->report['license_status'] ) {
			$this->report['license_status'] = get_option( 'uncanny_license_status', 'not_active' );
		}
	}

	/**
	 * Populate base plugin constants in the report
	 *
	 * @return void
	 */
	private function populate_base_plugins_constants() {
		$this->report['base_plugins_constants'] = array(
			'codes_version'     => UNCANNY_LEARNDASH_CODES_VERSION,
			'codes_db_version'  => defined( 'UNCANNY_LEARNDASH_CODES_DB_VERSION' ) ? UNCANNY_LEARNDASH_CODES_DB_VERSION : 'Not installed',
			'ld_version'        => defined( 'LEARNDASH_VERSION' ) ? LEARNDASH_VERSION : 'Not installed',
			'ld_db_version'     => defined( 'LEARNDASH_SETTINGS_DB_VERSION' ) ? LEARNDASH_SETTINGS_DB_VERSION : 'Not installed',
			'ld_script_debug'   => defined( 'LEARNDASH_SCRIPT_DEBUG' ) ? LEARNDASH_SCRIPT_DEBUG : 'Not set',
			'wc_version'        => class_exists( 'WooCommerce' ) ? WC()->version : 'Not installed',
			'automator_version' => defined( 'AUTOMATOR_PLUGIN_VERSION' ) ? AUTOMATOR_PLUGIN_VERSION : 'Not installed',
		);
	}

	/**
	 * Populate plugin settings in the report
	 *
	 * @return void
	 */
	private function populate_settings() {
		// Direct value for times_code_can_be_reused
		$this->report['settings']['times_code_can_be_reused'] = absint( get_option( Config::$uncanny_codes_times_code_can_be_reused, 1 ) );

		// Convert remaining settings to MongoDB table chart format
		$this->report['settings']['table'] = array(
			array(
				'key'   => 'allow_user_to_redeem_same_code',
				'name'  => 'Allow User to Redeem Same Code',
				'value' => absint( get_option( Config::$uncanny_codes_same_code_user_redemption, 0 ) ),
			),
			array(
				'key'   => 'allow_multiple_group_registration',
				'name'  => 'Allow Multiple Group Registration',
				'value' => absint( get_option( Config::$uncanny_codes_settings_multiple_groups, 0 ) ),
			),
			array(
				'key'   => 'autocomplete_codes_orders',
				'name'  => 'Autocomplete Codes Orders',
				'value' => absint( get_option( Config::$uncanny_codes_settings_autocomplete, 0 ) ),
			),
		);
	}

	/**
	 * Populate code batches data in the report
	 *
	 * @return void
	 */
	private function populate_batches() {
		global $wpdb;

		$tbl_groups    = $wpdb->prefix . Config::$tbl_groups;
		$tbl_codes     = $wpdb->prefix . Config::$tbl_codes;
		$total_codes   = absint( $wpdb->get_var( "SELECT COUNT(*) FROM $tbl_codes" ) );
		$total_batches = absint( $wpdb->get_var( "SELECT COUNT(*) FROM $tbl_groups" ) );

		// Get total batches
		$this->report['batches']['total'] = $total_batches;

		// Average codes per batch
		$this->report['batches']['average_codes_per_batch'] = $total_batches > 0 ?
			round( $total_codes / $total_batches, 2 ) : 0;

		// Get batches with expiry dates
		$this->report['batches']['with_expiry'] = absint( $wpdb->get_var( "SELECT COUNT(*) FROM $tbl_groups WHERE expire_date != '0000-00-00 00:00:00'" ) );

		// Get batches linked to WooCommerce products
		$this->report['batches']['woo_product_linked'] = absint( $wpdb->get_var( "SELECT COUNT(*) FROM $tbl_groups WHERE product_id > 0" ) );

		// Convert batch types to array of objects for MongoDB donut charts
		$this->report['batches']['types'] = array(
			array(
				'status' => 'automator',
				'count'  => absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $tbl_groups WHERE code_for = %s", 'automator' ) ) ),
			),
			array(
				'status' => 'learndash_courses',
				'count'  => absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $tbl_groups WHERE code_for = %s", 'course' ) ) ),
			),
			array(
				'status' => 'learndash_groups',
				'count'  => absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $tbl_groups WHERE code_for = %s", 'group' ) ) ),
			),
		);

		// Convert payment status to array of objects for MongoDB donut charts
		$this->report['batches']['learndash_payment_status'] = array(
			array(
				'status' => 'paid',
				'count'  => absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $tbl_groups WHERE paid_unpaid = %s", 'paid' ) ) ),
			),
			array(
				'status' => 'unpaid',
				'count'  => absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $tbl_groups WHERE paid_unpaid = %s", 'unpaid' ) ) ),
			),
			array(
				'status' => 'default',
				'count'  => absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $tbl_groups WHERE paid_unpaid = %s", 'default' ) ) ),
			),
		);
	}

	/**
	 * Populate codes data in the report
	 *
	 * @return void
	 */
	private function populate_codes() {
		global $wpdb;

		$tbl_codes = $wpdb->prefix . Config::$tbl_codes;
		$tbl_usage = $wpdb->prefix . Config::$tbl_codes_usage;

		// Get total codes
		$this->report['codes']['total'] = absint( $wpdb->get_var( "SELECT COUNT(*) FROM $tbl_codes" ) );

		// Get total usage (from usage table)
		$this->report['codes']['total_usage'] = absint( $wpdb->get_var( "SELECT COUNT(*) FROM $tbl_usage" ) );

		// Codes with expiry dates
		$this->report['codes']['with_expiry'] = absint( $wpdb->get_var( "SELECT COUNT(*) FROM $tbl_codes WHERE expire_date IS NOT NULL" ) );

		// Get codes linked to WooCommerce orders
		$this->report['codes']['woo_order_linked'] = absint( $wpdb->get_var( "SELECT COUNT(*) FROM $tbl_codes WHERE order_id > 0" ) );

		// Convert statuses to array of objects for MongoDB donut charts
		$this->report['codes']['statuses'] = array(
			array(
				'status' => 'active',
				'count'  => absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $tbl_codes WHERE is_active = %d", 1 ) ) ),
			),
			array(
				'status' => 'inactive',
				'count'  => absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $tbl_codes WHERE is_active = %d", 0 ) ) ),
			),
			array(
				'status' => 'used',
				'count'  => absint( $wpdb->get_var( "SELECT COUNT(*) FROM $tbl_codes WHERE used_date IS NOT NULL" ) ),
			),
			array(
				'status' => 'unused',
				'count'  => absint( $wpdb->get_var( "SELECT COUNT(*) FROM $tbl_codes WHERE used_date IS NULL" ) ),
			),
			array(
				'status' => 'expired',
				'count'  => absint( $wpdb->get_var( "SELECT COUNT(*) FROM $tbl_codes WHERE expire_date < NOW()" ) ),
			),
		);
	}

	/**
	 * Populate redemptions usage data
	 *
	 * @return void
	 */
	private function populate_redemptions() {
		global $wpdb;

		$this->report['redemption_types'] = array();

		// Count usage from tracking meta
		$tracking_meta     = Config::$uncanny_codes_tracking;
		$integrations_used = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_value, COUNT(*) as count FROM {$wpdb->usermeta} WHERE meta_key = %s GROUP BY meta_value",
				$tracking_meta
			)
		);

		foreach ( $integrations_used as $integration ) {
			// Lets normalize some old values
			switch ( $integration->meta_value ) {
				case 'Fluent_Forms':
					$meta_value = 'Fluent Forms';
					break;
				case 'Formidable':
					$meta_value = 'Formidable Forms';
					break;
				case 'Forminator':
					$meta_value = 'Forminator Forms';
					break;
				case 'Woocommerce':
					$meta_value = 'WooCommerce';
					break;
				default:
					$meta_value = $integration->meta_value;
					break;
			}
			$key = str_replace( '-', '_', sanitize_title( $meta_value ) );

			// Convert to MongoDB table chart format
			$this->report['redemption_types'][] = array(
				'key'   => $key,
				'name'  => $meta_value,
				'value' => absint( $integration->count ),
			);
		}
	}

	/**
	 * Count WooCommerce products with Automator Codes type
	 *
	 * @return void
	 */
	private function populate_woocommerce_data() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			// Set default values when WooCommerce is not installed
			$this->report['woocommerce']['automator_code_products']     = 0;
			$this->report['woocommerce']['orders_with_automator_codes'] = 0;
			return;
		}

		// Get products with automator_codes type
		$automator_product_ids = wc_get_products(
			array(
				'status' => 'publish',
				'type'   => 'automator_codes',
				'limit'  => 9999999,
				'return' => 'ids',
			)
		);

		$automator_code_products = absint( count( $automator_product_ids ) );

		// Get orders with automator code products
		$automator_orders = 0;
		if ( ! empty( $automator_product_ids ) ) {
			$orders = wc_get_orders(
				array(
					'limit'  => 9999999,
					'return' => 'ids',
				)
			);

			foreach ( $orders as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( $order ) {
					foreach ( $order->get_items() as $item ) {
						if ( in_array( $item->get_data()['product_id'], $automator_product_ids ) ) {
							$automator_orders++;
							break; // Count each order only once
						}
					}
				}
			}
		}

		$automator_orders = absint( $automator_orders );

		// Separate values for individual number charts
		$this->report['woocommerce']['automator_code_products']     = $automator_code_products;
		$this->report['woocommerce']['orders_with_automator_codes'] = $automator_orders;
	}

	/**
	 * Populate LearnDash data in the report
	 *
	 * @return void
	 */
	private function populate_learndash_data() {
		if ( ! isset( $GLOBALS['learndash_post_types'] ) ) {
			return;
		}

		global $wpdb;

		// Get LearnDash courses
		$total_courses = absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'",
					'sfwd-courses'
				)
			)
		);

		// Get LearnDash groups
		$total_groups = absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'",
					'groups'
				)
			)
		);

		// Get LearnDash lessons
		$total_lessons = absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'",
					'sfwd-lessons'
				)
			)
		);

		// Check if groups hierarchical is enabled
		$hierarchical_groups_enabled = function_exists( 'learndash_is_groups_hierarchical_enabled' ) && learndash_is_groups_hierarchical_enabled() ? 1 : 0;

		// Convert to MongoDB table chart format
		$this->report['learndash'] = array(
			array(
				'key'   => 'total_courses',
				'name'  => 'Total Courses',
				'value' => $total_courses,
			),
			array(
				'key'   => 'total_groups',
				'name'  => 'Total Groups',
				'value' => $total_groups,
			),
			array(
				'key'   => 'total_lessons',
				'name'  => 'Total Lessons',
				'value' => $total_lessons,
			),
			array(
				'key'   => 'hierarchical_groups_enabled',
				'name'  => 'Hierarchical Groups Enabled',
				'value' => $hierarchical_groups_enabled,
			),
		);
	}

	/**
	 * Track administrative actions
	 *
	 * @return void
	 */
	private function populate_admin_actions() {
		// Convert to MongoDB table chart format
		$this->report['admin_actions'] = array(
			array(
				'key'   => 'bulk_expiry_operations',
				'name'  => 'Bulk Expiry Operations',
				'value' => absint( get_option( 'uncanny_codes_expiry_updates_count', 0 ) ),
			),
			array(
				'key'   => 'bulk_replace_operations',
				'name'  => 'Bulk Replace Operations',
				'value' => absint( get_option( 'uncanny_codes_bulk_replace_count', 0 ) ),
			),
			array(
				'key'   => 'bulk_cancel_operations',
				'name'  => 'Bulk Cancel Operations',
				'value' => absint( get_option( 'uncanny_codes_bulk_cancel_count', 0 ) ),
			),
			array(
				'key'   => 'bulk_delete_operations',
				'name'  => 'Bulk Delete Operations',
				'value' => absint( get_option( 'uncanny_codes_bulk_delete_count', 0 ) ),
			),
		);
	}

	/**
	 * Track weekly activity metrics
	 *
	 * @return void
	 */
	private function populate_weekly_activity() {
		// Convert to MongoDB table chart format
		$this->report['weekly_activity'] = array(
			array(
				'key'   => 'codes_redeemed_this_week',
				'name'  => 'Codes Redeemed This Week',
				'value' => absint( $this->get_codes_redeemed_this_week() ),
			),
			array(
				'key'   => 'unique_users_this_week',
				'name'  => 'Unique Users This Week',
				'value' => absint( $this->get_unique_users_this_week() ),
			),
			array(
				'key'   => 'batches_created_this_week',
				'name'  => 'Batches Created This Week',
				'value' => absint( $this->get_batches_created_this_week() ),
			),
			array(
				'key'   => 'bulk_expiry_operations_week',
				'name'  => 'Bulk Expiry Operations This Week',
				'value' => absint( get_option( 'uncanny_codes_expiry_updates_week', 0 ) ),
			),
			array(
				'key'   => 'bulk_replace_operations_week',
				'name'  => 'Bulk Replace Operations This Week',
				'value' => absint( get_option( 'uncanny_codes_bulk_replace_week', 0 ) ),
			),
			array(
				'key'   => 'bulk_cancel_operations_week',
				'name'  => 'Bulk Cancel Operations This Week',
				'value' => absint( get_option( 'uncanny_codes_bulk_cancel_week', 0 ) ),
			),
			array(
				'key'   => 'bulk_delete_operations_week',
				'name'  => 'Bulk Delete Operations This Week',
				'value' => absint( get_option( 'uncanny_codes_bulk_delete_week', 0 ) ),
			),
			array(
				'key'   => 'woo_orders_this_week',
				'name'  => 'WooCommerce Orders This Week',
				'value' => absint( $this->get_woo_orders_this_week() ),
			),
		);
	}

	/**
	 * Get codes redeemed in the last 7 days
	 *
	 * @return int
	 */
	private function get_codes_redeemed_this_week() {
		global $wpdb;
		$tbl_codes = $wpdb->prefix . Config::$tbl_codes;
		return absint( $wpdb->get_var( "SELECT COUNT(*) FROM $tbl_codes WHERE used_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)" ) );
	}

	/**
	 * Get unique users who redeemed codes in the last 7 days
	 *
	 * @return int
	 */
	private function get_unique_users_this_week() {
		global $wpdb;
		$tbl_usage = $wpdb->prefix . Config::$tbl_codes_usage;
		$result    = $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM $tbl_usage WHERE DATE(date_redeemed) >= DATE_SUB(NOW(), INTERVAL 7 DAY)" );
		return $result ? absint( $result ) : 0;
	}

	/**
	 * Get batches created in the last 7 days
	 *
	 * @return int
	 */
	private function get_batches_created_this_week() {
		global $wpdb;
		$tbl_groups = $wpdb->prefix . Config::$tbl_groups;
		$result     = $wpdb->get_var( "SELECT COUNT(*) FROM $tbl_groups WHERE DATE(issue_date) >= DATE_SUB(NOW(), INTERVAL 7 DAY)" );
		return $result ? absint( $result ) : 0;
	}

	/**
	 * Get WooCommerce orders with codes in the last 7 days
	 *
	 * @return int
	 */
	private function get_woo_orders_this_week() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return 0;
		}

		$orders = wc_get_orders(
			array(
				'limit'        => 9999999,
				'date_created' => '>=' . ( time() - 7 * DAY_IN_SECONDS ),
				'return'       => 'ids',
			)
		);

		$orders_with_codes = 0;
		foreach ( $orders as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				foreach ( $order->get_items() as $item ) {
					$product_id  = $item->get_data()['product_id'];
					$codes_group = get_post_meta( $product_id, 'codes_group_name', true );
					if ( ! empty( $codes_group ) ) {
						$orders_with_codes++;
						break; // Count each order only once
					}
				}
			}
		}

		return absint( $orders_with_codes );
	}



	/**
	 * Reset weekly counters after report is sent
	 *
	 * @return void
	 */
	private function reset_weekly_counters() {
		delete_option( 'uncanny_codes_expiry_updates_week' );
		delete_option( 'uncanny_codes_bulk_replace_week' );
		delete_option( 'uncanny_codes_bulk_cancel_week' );
		delete_option( 'uncanny_codes_bulk_delete_week' );
	}

	/**
	 * Populate Automator recipe data (Donut Chart Data)
	 *
	 * @return void
	 */
	private function populate_automator_recipes() {
		if ( ! defined( 'AUTOMATOR_BASE_FILE' ) || ! function_exists( 'Automator' ) ) {
			return;
		}

		global $wpdb;

		// Get total recipes
		$total_recipes = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status IN ('publish', 'draft')",
				'uo-recipe'
			)
		);

		// Define Uncanny Codes trigger codes
		$codes_trigger_codes = array(
			'CODEREDEEMED',
			'UCBATCH',
			'UCPREFIX',
			'UCSUFFIX',
			'ANONCODEBATCHCREATED',
		);

		// Get recipes using Codes triggers - search in post meta for trigger codes
		$placeholders         = implode( ',', array_fill( 0, count( $codes_trigger_codes ), '%s' ) );
		$unique_codes_recipes = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT p.post_parent) FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE pm.meta_key = %s
			AND pm.meta_value IN ($placeholders)",
				array_merge( array( 'code' ), $codes_trigger_codes )
			)
		);

		$codes_recipes     = intval( $unique_codes_recipes );
		$total_recipes     = intval( $total_recipes );
		$non_codes_recipes = $total_recipes - $codes_recipes;

		// Format as donut chart data (matching existing pattern)
		$this->report['automator_recipes'] = array(
			array(
				'status' => 'codes_recipes',
				'count'  => $codes_recipes,
			),
			array(
				'status' => 'other_recipes',
				'count'  => $non_codes_recipes,
			),
		);
	}

	/**
	 * Populate Automator actions used with Codes triggers (Table Data)
	 *
	 * @return void
	 */
	private function populate_automator_actions_with_codes() {
		if ( ! defined( 'AUTOMATOR_BASE_FILE' ) || ! function_exists( 'Automator' ) ) {
			return;
		}

		global $wpdb;

		// Define Uncanny Codes trigger codes
		$codes_trigger_codes = array(
			'CODEREDEEMED',
			'UCBATCH',
			'UCPREFIX',
			'UCSUFFIX',
			'ANONCODEBATCHCREATED',
		);

		// Get recipe IDs that have Codes triggers
		$trigger_placeholders = implode( ',', array_fill( 0, count( $codes_trigger_codes ), '%s' ) );
		$recipes_with_codes   = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT p.post_parent FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE pm.meta_key = %s
			AND pm.meta_value IN ($trigger_placeholders)",
				array_merge( array( 'code' ), $codes_trigger_codes )
			)
		);

		if ( empty( $recipes_with_codes ) ) {
			$this->report['automator_actions']       = array();
			$this->report['automator_codes_actions'] = array();
			return;
		}

		$actions_usage = array();

		// For each recipe with Codes triggers, get the recipe object and extract actions
		foreach ( $recipes_with_codes as $recipe_id ) {
			try {
				// Get the full recipe object using Automator's method
				$recipe_object = Automator()->get_recipe_object( $recipe_id, 'ARRAY_A' );

				// Extract actions from the recipe object
				if ( ! empty( $recipe_object['actions']['items'] ) ) {
					foreach ( $recipe_object['actions']['items'] as $action ) {
						if ( ! empty( $action['code'] ) ) {
							$action_code      = $action['code'];
							$integration_code = ! empty( $action['integration_code'] ) ? $action['integration_code'] : '';

							// Get clean action title using Automator's core function
							$action_sentence = Automator()->get->action_title_from_action_code( $action_code );
							if ( empty( $action_sentence ) ) {
								$action_sentence = $action_code; // fallback
							}

							// Get integration name for separate column
							$integration_name = Automator()->get_integration_name_by_code( $integration_code );
							if ( empty( $integration_name ) ) {
								$integration_name = $integration_code; // fallback to code
							}

							if ( ! isset( $actions_usage[ $action_code ] ) ) {
								$actions_usage[ $action_code ] = array(
									'integration_code' => $integration_code,
									'integration_name' => $integration_name,
									'action_code'      => $action_code,
									'action_name'      => $action_sentence,
									'usage_count'      => 0,
								);
							}
							$actions_usage[ $action_code ]['usage_count']++;
						}
					}
				}
			} catch ( \Exception $e ) {
				// Skip recipes that can't be processed
				continue;
			}
		}

		// Collect ONLY Codes triggers usage from ALL recipes
		$triggers_usage = array();
		$all_recipes    = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'uo-recipe' AND post_status IN ('publish', 'draft')" );

		foreach ( $all_recipes as $recipe_id ) {
			try {
				// Get the full recipe object using Automator's method
				$recipe_object = Automator()->get_recipe_object( $recipe_id, 'ARRAY_A' );

				// Extract ONLY Codes triggers from the recipe object
				if ( ! empty( $recipe_object['triggers']['items'] ) ) {
					foreach ( $recipe_object['triggers']['items'] as $trigger ) {
						if ( ! empty( $trigger['code'] ) && in_array( $trigger['code'], $codes_trigger_codes ) ) {
							$trigger_code     = $trigger['code'];
							$integration_code = ! empty( $trigger['integration_code'] ) ? $trigger['integration_code'] : '';

							// Get clean trigger title using Automator's core function
							$trigger_sentence = Automator()->get->trigger_title_from_trigger_code( $trigger_code );
							if ( empty( $trigger_sentence ) ) {
								$trigger_sentence = $trigger_code; // fallback
							}

							if ( ! isset( $triggers_usage[ $trigger_code ] ) ) {
								$triggers_usage[ $trigger_code ] = array(
									'integration_code' => $integration_code,
									'trigger_code'     => $trigger_code,
									'trigger_name'     => $trigger_sentence,
									'usage_count'      => 0,
								);
							}
							$triggers_usage[ $trigger_code ]['usage_count']++;
						}
					}
				}
			} catch ( \Exception $e ) {
				// Skip recipes that can't be processed
				continue;
			}
		}

		// Sort by usage count
		uasort(
			$actions_usage,
			function( $a, $b ) {
				return $b['usage_count'] <=> $a['usage_count'];
			}
		);

		// Limit to top 20 actions and format with integration column
		$actions_table = array();
		$actions_slice = array_slice( array_values( $actions_usage ), 0, 20 );
		foreach ( $actions_slice as $action ) {
			$actions_table[] = array(
				'key'         => $action['action_code'],
				'name'        => $action['action_name'],
				'integration' => $action['integration_name'],
				'value'       => $action['usage_count'],
			);
		}

		$this->report['automator_actions'] = $actions_table;

		// Sort triggers by usage count and format like actions
		uasort(
			$triggers_usage,
			function( $a, $b ) {
				return $b['usage_count'] <=> $a['usage_count'];
			}
		);

		// Format triggers data
		$triggers_table = array();
		foreach ( $triggers_usage as $trigger ) {
			$triggers_table[] = array(
				'key'   => $trigger['trigger_code'],
				'name'  => $trigger['trigger_name'],
				'value' => $trigger['usage_count'],
			);
		}

		$this->report['automator_triggers'] = $triggers_table;

		// Get Codes actions from ANY recipe (not just recipes with Codes triggers)
		$codes_action_codes = array(
			'UCGENERATECODES',
			'UCADDBATCHCODES',
			'UCCANCELCODE',
			'UCDELETEBATCHCODES',
		);

		$codes_actions_usage = array();

		foreach ( $all_recipes as $recipe_id ) {
			try {
				// Get the full recipe object using Automator's method
				$recipe_object = Automator()->get_recipe_object( $recipe_id, 'ARRAY_A' );

				// Extract ONLY Codes actions from the recipe object
				if ( ! empty( $recipe_object['actions']['items'] ) ) {
					foreach ( $recipe_object['actions']['items'] as $action ) {

						if ( ! empty( $action['code'] ) && in_array( $action['code'], $codes_action_codes ) ) {
							$action_code      = $action['code'];
							$integration_code = ! empty( $action['integration_code'] ) ? $action['integration_code'] : '';

							// Get clean action title using Automator's core function
							$action_sentence = Automator()->get->action_title_from_action_code( $action_code );
							if ( empty( $action_sentence ) ) {
								$action_sentence = $action_code; // fallback
							}

							// Get integration name for separate column
							$integration_name = Automator()->get_integration_name_by_code( $integration_code );
							if ( empty( $integration_name ) ) {
								$integration_name = $integration_code; // fallback to code
							}

							if ( ! isset( $codes_actions_usage[ $action_code ] ) ) {
								$codes_actions_usage[ $action_code ] = array(
									'integration_code' => $integration_code,
									'integration_name' => $integration_name,
									'action_code'      => $action_code,
									'action_name'      => $action_sentence,
									'usage_count'      => 0,
								);
							}
							$codes_actions_usage[ $action_code ]['usage_count']++;
						}
					}
				}
			} catch ( \Exception $e ) {
				// Skip recipes that can't be processed
				continue;
			}
		}

		// Format codes actions data with integration column
		$codes_actions_table = array();
		foreach ( $codes_actions_usage as $action ) {
			$codes_actions_table[] = array(
				'key'         => $action['action_code'],
				'name'        => $action['action_name'],
				'integration' => $action['integration_name'],
				'value'       => $action['usage_count'],
			);
		}

		$this->report['automator_codes_actions'] = $codes_actions_table;
	}



	/**
	 * Compile and send the usage report to the server
	 *
	 * @return array|\WP_Error The response from wp_remote_post() or WP_Error on failure.
	 */
	public function make_request() {
		$this->populate_license_details();
		$this->populate_base_plugins_constants();
		$this->populate_settings();
		$this->populate_batches();
		$this->populate_codes();
		$this->populate_redemptions();
		$this->populate_woocommerce_data();
		$this->populate_learndash_data();
		$this->populate_admin_actions();
		$this->populate_weekly_activity();
		$this->populate_automator_recipes();
		$this->populate_automator_actions_with_codes();

		$json_report = wp_json_encode( $this->report );

		$timestamp = time();

		// Create signature using timestamp and API key
		$signature = hash_hmac( 'sha256', $timestamp, UNCANNY_API_KEY );

		$response = wp_remote_post(
			UNCANNY_API_URL . 'reports',
			array(
				'body' => array(
					'action'    => 'save',
					'signature' => $signature,
					'timestamp' => $timestamp,
					'report'    => $json_report,
				),
			)
		);

		// Reset weekly counters after successful report submission
		if ( ! is_wp_error( $response ) ) {
			$this->reset_weekly_counters();
		}

		return $response;
	}
}
