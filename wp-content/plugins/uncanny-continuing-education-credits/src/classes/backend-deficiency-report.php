<?php

namespace uncanny_ceu;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class UncannyCeuCompletionStatus
 * @package uncanny_ceu
 */
class BackendDeficiencyReport {

	public static $user = - 1;
	public static $group = - 1;
	public static $users_ceus = array();
	static $root_path = 'ceu/v2';

	/**
	 * UncannyCeuCompletionStatus constructor.
	 */
	public function __construct() {

		add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_custom_wp_admin_style' ) );

	}

	/**
	 *
	 */
	public function admin_menu() {

		$page_title  = __( 'Deficiency Report', 'uncanny-ceu' );
		$menu_title  = __( 'Deficiency Report', 'uncanny-ceu' );
		$capability  = 'manage_options';
		$menu_slug   = 'uncanny-deficiency-report';
		$parent_slug = 'uncanny-ceu-report';
		$function    = array( $this, 'options_menu_page_output' );

		add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
	}

	/**
	 *
	 */
	public function load_custom_wp_admin_style( $hook ) {

		/*
		 * Only load styles if the page hook contains the pages slug
		 *
		 * Hook can be either the toplevel_page_{page_slug} if its a parent  page OR
		 * it can be {parent_slug}_pag_{page_slug} if it is a sub page.
		 * Lets just check if our page slug is in the hook.
		 */

		// Target Licensing page
		if ( strpos( $hook, 'uncanny-deficiency-report' ) ) {
			// Select2
			wp_enqueue_script( 'ucec-admin-select2', Utilities::get_vendor( 'select2/select2.min.js' ), [ 'jquery' ], Utilities::get_version(), true );
			wp_enqueue_style( 'ucec-admin-select2', Utilities::get_vendor( 'select2/select2.min.css' ), [], Utilities::get_version() );

			// DataTables
			wp_enqueue_script( 'ucec-admin-datatables', Utilities::get_vendor( 'datatables/datatables.min.js' ), [ 'jquery' ], Utilities::get_version(), true );
			wp_enqueue_style( 'ucec-admin-datatables', Utilities::get_vendor( 'datatables/datatables.min.css' ), [], Utilities::get_version() );

			// Global assets
			wp_register_script( 'ucec-admin', Utilities::get_asset( 'backend', 'bundle.min.js' ), [
				'jquery',
				'ucec-admin-datatables',
				'ucec-admin-select2',
			], Utilities::get_version(), true );

			wp_enqueue_style( 'ucec-admin', Utilities::get_asset( 'backend', 'bundle.min.css' ), [
				'ucec-admin-datatables',
				'ucec-admin-select2',
			], Utilities::get_version() );

			// API data
			$api_setup = array(
				'api'  => [
					'root'  => esc_url_raw( rest_url() . self::$root_path ),
					'nonce' => \wp_create_nonce( 'wp_rest' ),
				],
				'l10n' => [
					'filters' => [
						'allUsers' => __( 'All users', 'uncanny-ceu' ),
					],
				],
			);

			wp_localize_script( 'ucec-admin', 'UO_CEU_EDITOR', $api_setup );

			wp_enqueue_script( 'ucec-admin' );
		}

	}

	/**
	 *
	 */
	public function options_menu_page_output() {

		$get_data = false;

		if ( isset( $_GET['user'] ) ) {

			if ( '-1' === $_GET['user'] ) {
				self::$user = 0;
			} else {
				self::$user = absint( $_GET['user'] );
			}

			$get_data = true;
		}

		if ( isset( $_GET['group'] ) ) {

			if ( '-1' === $_GET['group'] ) {
				self::$group = 0;
			} else {
				self::$group = absint( $_GET['group'] );
			}

			$get_data = true;
		}

		if ( true === $get_data ) {
			$this->get_user_deficiency_data();
		}

		include( Utilities::get_template( 'admin-deficiency-report.php' ) );

	}

	/**
	 * Collect user data
	 */
	private function get_user_deficiency_data() {

		global $wpdb;

		$roll_over_date = Utilities::rollover_date_as_unix();

		$query = "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = 'group_credit_required'";

		$group_credits_required        = $wpdb->get_results( $query );
		$g_credits                     = array();
		$highest_group_credit_required = 0;

		foreach ( $group_credits_required as $group_credit_required ) {
			$g_credits[ $group_credit_required->post_id ] = $group_credit_required;
		}

		if ( 0 !== self::$group ) {

			if ( isset( $g_credits[ self::$group ] ) ) {
				$highest_group_credit_required = (float) $g_credits[ self::$group ]->meta_value;
			}

			$group       = self::$group;
			$group_users = learndash_get_groups_users( $group );

			$group_user_ids = [];
			foreach ( $group_users as $group_user ) {
				$group_user_ids[ $group_user->ID ] = $group_user->ID;
			}
		}

		$query = "SELECT ID, user_email FROM $wpdb->users";

		if ( 0 !== self::$user ) {
			$user_id = self::$user;
			$query   .= " WHERE ID = $user_id";
		}

		$users = $wpdb->get_results( $query );

		$ceu_completions = array();

		foreach ( $users as $user ) {

			$user_meta = get_user_meta( $user->ID );

			// If the group filter is set then only collection user within the group
			if ( self::$group && ! isset( $group_user_ids[ $user->ID ] ) ) {
				continue;
			}

			$avatar = '';
			if ( true === apply_filters( 'uo_ceu_show_avatar_in_reports', false ) ) {
				$avatar = get_avatar_url( $user->ID );
			}
			$first_name = $user_meta['first_name'][0];
			$last_name  = $user_meta['last_name'][0];
			$email      = strtolower( $user->user_email );

			foreach ( $user_meta as $key => $value ) {

				if ( ! isset( $ceu_completions[ $user->ID ] ) ) {
					$ceu_completions[ $user->ID ]                      = array();
					$ceu_completions[ $user->ID ]['user_id']           = $user->ID;
					$ceu_completions[ $user->ID ]['avatar']            = $avatar;
					$ceu_completions[ $user->ID ]['first_name']        = $first_name;
					$ceu_completions[ $user->ID ]['last_name']         = $last_name;
					$ceu_completions[ $user->ID ]['email']             = $email;
					$ceu_completions[ $user->ID ]['ceu_earned']        = 0;
					$ceu_completions[ $user->ID ]['ceu_earned_before'] = 0;

					if ( self::$group ) {
						$ceu_completions[ $user->ID ]['highest_group_credit_required'] = $highest_group_credit_required;
					}
				}

				if ( false !== strpos( $key, 'learndash_group_users_' ) && ! self::$group ) {

					if ( absint( $value[0] ) ) {

						if ( ! isset( $ceu_completions[ $user->ID ]['highest_group_credit_required'] ) ) {
							if ( isset( $g_credits[ absint( $value[0] ) ] ) && isset( $g_credits[ absint( $value[0] ) ]->meta_value ) ) {
								$ceu_completions[ $user->ID ]['highest_group_credit_required'] = (float) $g_credits[ absint( $value[0] ) ]->meta_value;
							}
						} else {
							if ( isset( $g_credits[ absint( $value[0] ) ] ) && isset( $g_credits[ absint( $value[0] ) ]->meta_value ) ) {

								if ( $ceu_completions[ $user->ID ]['highest_group_credit_required'] < (float) $g_credits[ absint( $value[0] ) ]->meta_value ) {

									$ceu_completions[ $user->ID ]['highest_group_credit_required'] = (float) $g_credits[ absint( $value[0] ) ]->meta_value;
								}
							}
						}
					}
				}

				if ( 'individual_credit_required' === $key ) {
					$ceu_completions[ $user->ID ]['ceu_required_personal'] = (float) $value[0];
				}

				if ( false !== strpos( $key, 'ceu_earned' ) ) {

					$x_key = explode( '_', $key );

					$ceu_date_earned = absint( $x_key[2] ); // Unix timestamp

					if ( $ceu_date_earned && $roll_over_date && (int) $ceu_date_earned > (int) $roll_over_date->format( 'U' ) ) {
						// before roll over
						$ceu_completions[ $user->ID ]['ceu_earned'] += (float) $value[0];
					}
				}
			}
		}

		BackendDeficiencyReport::$users_ceus = $ceu_completions;

	}

	/**
	 * Get array with the list of all users from the site
	 * Each array element will have a "value" element, which will contain the user id,
	 * and also will have a "text" element, which will contain the text to be shown in the option
	 *
	 * @return Array
	 * @since  3.0
	 *
	 */

	static function get_users() {
		// Get list of all users
		$users = get_users();

		// Create array that will contain the optiosn
		$options = array();

		// Iterate users and create main array
		foreach ( $users as $user ) {
			// Get avatar
			$user->avatar = get_avatar_url( $user->ID );

			// Add option
			$options[] = array(
				'value' => $user->ID,
				'text'  => sprintf( '%s::%s', wp_strip_all_tags( $user->display_name ), wp_strip_all_tags( $user->user_email ) ),
			);
		}

		// Apply filters and return
		return apply_filters( 'ucec_course_report_users', $options );
	}

	/**
	 * Get select options for the "Users" dropdown
	 *
	 * @return String
	 * @since 3.0
	 *
	 */

	static function get_users_dropdown() {
		// Get options
		$options = [
			[
				'value' => 0,
				'text'  => __( 'All users', 'uncanny-ceu' ),
			],
		];

		if ( self::$user != 0 ) {
			// Check if the selected user exists
			$user = get_userdata( self::$user );

			if (
				( $user !== false )
				&& isset( $user->ID )
				&& isset( $user->user_email )
			) {
				$options[] = [
					'value' => $user->ID,
					'text'  => sprintf( '%s::%s',
						wp_strip_all_tags( $user->display_name ),
						wp_strip_all_tags( $user->user_email )
					),
				];
			}
		}

		// Start HTML
		ob_start();

		// Iterate and create options
		foreach ( $options as $option ) {
			// Check if the option is selected
			$is_selected = self::$user == $option['value'];

			?>

			<option value="<?php echo $option['value']; ?>" <?php if ( $is_selected ) {
				echo 'selected="selected"';
			} ?>>
				<?php echo $option['text']; ?>
			</option>

			<?php
		}

		// Get HTML
		$options_html = ob_get_clean();

		// Return HTML
		return $options_html;
	}

	/**
	 * Get select options for the "Groups" dropdown
	 *
	 * @return String
	 * @since 3.0
	 *
	 */

	public static function get_groups_dropdown() {
		$groups = new \WP_Query( [
			'post_type'      => 'groups',
			'posts_per_page' => - 1,
			'orderby'        => 'title',
		] );

		$groups_options = [];

		if ( $groups->have_posts() ) {
			while ( $groups->have_posts() ) {
				$groups->the_post();

				$groups_options[] = [
					'value' => get_the_ID(),
					'text'  => get_the_title(),
				];
			}

			/* Restore original Post Data */
			wp_reset_postdata();
		}

		// Get options
		$options = array_merge(
			[
				[
					'value' => - 1,
					'text'  => __( 'Select a group', 'uncanny-ceu' ),
				],
				[
					'value' => 0,
					'text'  => __( 'All groups', 'uncanny-ceu' ),
				],
			],
			$groups_options
		);

		// Start HTML
		ob_start();

		// Iterate and create options
		foreach ( $options as $option ) {
			// Check if the option is selected
			$is_selected = self::$group == $option['value'];

			?>

			<option value="<?php echo $option['value']; ?>" <?php if ( $is_selected ) {
				echo 'selected="selected"';
			} ?>>
				<?php echo $option['text']; ?>
			</option>

			<?php
		}

		// Get HTML
		$options_html = ob_get_clean();

		// Return HTML
		return $options_html;
	}
}
