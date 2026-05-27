<?php

namespace uncanny_learndash_groups;

// If this file is called directly, abort.

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class EditGroupWizard
 *
 * @package uncanny_learndash_groups
 */
class EditGroupWizard {


	/**
	 * class constructor
	 */
	public function __construct() {
		add_shortcode( 'uo_groups_edit_group', array( $this, 'uo_groups_edit_group_func' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'create_new_group_scripts' ), 30 );
		add_action( 'init', array( $this, 'process_edit_group_func' ), 99 );
	}

	/**
	 * Create Theme Options page
	 *
	 * @since 1.0.0
	 */
	public function uo_groups_edit_group_func( $atts = array() ) {
		global $post;

		$atts = shortcode_atts(
			array(
				'category'        => '',
				'course_category' => '',
				'group_name'      => 'show',
				'total_seats'     => 'show',
				'group_courses'   => 'show',
				'group_image'     => 'show',
				'parent_selector' => 'hide',
			),
			$atts,
			'uo_groups_edit_group'
		);

		if (
			Utilities::has_shortcode( $post, 'uo_groups_edit_group' ) ||
			Utilities::has_block( $post, 'uncanny-learndash-groups/uo-groups-edit-group' )
		) {
			ob_start();

			echo $this->should_render_shortcode( $atts );

			return ob_get_clean();
		}

		return '';
	}

	/**
	 *
	 */
	public function create_new_group_scripts() {
		global $post;

		if ( Utilities::has_shortcode( $post, 'uo_groups_edit_group' ) || Utilities::has_block( $post, 'uncanny-learndash-groups/uo-groups-edit-group' ) ) {
			self::enqueue_frontend_assets();
		}
	}

	/**
	 * @return string|true|null
	 */
	public function should_render_shortcode( $atts ) {
		if ( ! is_user_logged_in() ) {
			return __( 'Oops! You are not logged in to view this page.' );
		}
		$allowed_roles = apply_filters(
			'ulgm_gm_allowed_roles',
			array(
				'administrator',
				'group_leader',
				'ulgm_group_management',
				'super_admin',
			)
		);

		// Is the user a group leader or administartor
		if ( ! current_user_can( 'manage_options' ) && ! array_intersect( wp_get_current_user()->roles, $allowed_roles ) ) {
			return __( 'The Edit Group tool can only be used by a user with a Group Leader or Administrator role.' );
		}
		if ( ! ulgm_filter_has_var( 'group-id' ) ) {
			return __( 'The Edit Group tool requires that a valid group ID be included in the URL.', 'uncanny-learndash-groups.' );
		}
		$group_id                 = absint( ulgm_filter_input( 'group-id' ) );
		$groups_administrator_ids = learndash_get_groups_administrator_ids( $group_id );
		if ( ! in_array( get_current_user_id(), $groups_administrator_ids ) && ! current_user_can( 'manage_options' ) ) {
			return __( 'Only Leaders of the associated group can edit the group.' );
		}

		$parent_group    = Utilities::show_section( $atts['parent_selector'] );
		$group_name      = Utilities::show_section( $atts['group_name'] );
		$total_seats     = Utilities::show_section( $atts['total_seats'] );
		$group_courses   = Utilities::show_section( $atts['group_courses'] );
		$group_image     = Utilities::show_section( $atts['group_image'] );
		$is_editable     = self::is_editable( $group_id );
		$is_editable_woo = self::is_editable( $group_id, true );
		$disabled        = self::is_disabled( $group_id );

		include Utilities::get_template( 'frontend-edit-group/admin-custom-groups-edit.php' );

		return ob_get_clean();
	}

	/**
	 * @since 3.7.5
	 * @author Agus B.
	 * @internal Saad S.
	 */
	public static function enqueue_frontend_assets() {
		global $post;

		if ( ! empty( $post ) ) {
			wp_enqueue_script(
				'ulgm-frontend',
				Utilities::get_asset( 'frontend', 'bundle.min.js' ),
				array(
					'jquery',
					'ulgm-select2',
				),
				Utilities::get_version(),
				true
			);

			// Load Styles for Licensing page located in general plugin styles
			wp_register_style( 'ulgm-frontend', Utilities::get_asset( 'frontend', 'bundle.min.css' ), array(), Utilities::get_version() );
			$user_colors = Utilities::user_colors();
			wp_add_inline_style( 'ulgm-frontend', $user_colors );
			wp_enqueue_style( 'ulgm-frontend', $user_colors );

			wp_enqueue_media();

			wp_enqueue_script( 'ulgm-select2', Utilities::get_vendor( 'select2/js/select2.min.js' ), array( 'jquery' ), Utilities::get_version(), true );
			wp_enqueue_style( 'ulgm-select2', Utilities::get_vendor( 'select2/css/select2.min.css' ), array(), Utilities::get_version() );
		}
	}

	/**
	 * @return void
	 */
	public static function process_edit_group_func() {

		if (
			ulgm_filter_has_var( 'is_custom_group_edit_nonce', INPUT_POST ) &&
			wp_verify_nonce( ulgm_filter_input( 'is_custom_group_edit_nonce', INPUT_POST ), 'ulgm_nonce' )
		) {

			$group_id            = ulgm_filter_input( 'ulgm_group_id', INPUT_POST );
			$group_name          = ulgm_filter_input( 'ulgm_group_name', INPUT_POST );
			$group_parent        = ulgm_filter_has_var( 'ulgm_group_name', INPUT_POST ) ? ulgm_filter_input( 'parent_group_id', INPUT_POST ) : 0;
			$number_of_seats     = ulgm_filter_input( 'ulgm_group_total_seats', INPUT_POST );
			$group_courses       = ulgm_filter_input_array( 'ulgm_group_courses', INPUT_POST );
			$group_image         = ulgm_filter_input( 'ulgm_group_edit_image_attachment_id', INPUT_POST );
			$edit_group_page_url = ulgm_filter_input( 'edit_group_page_id', INPUT_POST );
			$redirect_to         = ulgm_filter_input( 'redirect_to', INPUT_POST );

			$args = array(
				'ulgm_group_id'          => $group_id,
				'ulgm_group_parent'      => $group_parent,
				'ulgm_group_name'        => $group_name,
				'ulgm_group_total_seats' => $number_of_seats,
				'ulgm_group_courses'     => $group_courses,
				'ulgm_group_edit_image'  => $group_image,
				'edit_group_page_url'    => $edit_group_page_url,
				'redirect_to'            => $redirect_to,
			);

			self::process_edit( $args );
		}
	}

	/**
	 * @param $args
	 * @param null $_post
	 *
	 * @return int|\WP_Error
	 */
	public static function process_edit( $args ) {

		$group_id                = absint( $args['ulgm_group_id'] );
		$group_name              = sanitize_text_field( $args['ulgm_group_name'] );
		$group_parent            = sanitize_text_field( $args['ulgm_group_parent'] );
		$number_of_seats_entered = absint( $args['ulgm_group_total_seats'] );
		$group_courses           = $args['ulgm_group_courses'];
		//      $group_image             = absint( $args['ulgm_group_edit_image'] );
		$edit_group_page_url = sanitize_url( $args['edit_group_page_url'] );
		$is_editable         = self::is_editable( $group_id );
		$is_editable_woo     = self::is_editable( $group_id, true );
		$redirect_to         = esc_url_raw( $args['redirect_to'] );
		$group_title         = $group_name;
		$edit_group_page_url = add_query_arg( 'group-id', $group_id, $edit_group_page_url );

		if ( empty( $group_title ) ) {
			$edit_group_page_url = add_query_arg( 'group-name-error', 'yes', $edit_group_page_url );
			$redirect            = $edit_group_page_url;
			wp_safe_redirect( $redirect );
			exit;
		}

		$ld_group_args = array(
			'ID'          => $group_id,
			'post_title'  => $group_title,
			'post_parent' => $group_parent,
		);
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . SharedFunctions::$db_group_tbl,
			array( 'group_name' => $group_title ),
			array(
				'ld_group_id' => $group_id,
			),
			array( '%s' ),
			array( '%d' )
		);

		wp_update_post( $ld_group_args );

		// Modify only if the group is not purchased
		if ( true === $is_editable_woo ) {
			if ( ! empty( $group_courses ) ) {
				$__group_courses = learndash_group_enrolled_courses( $group_id );
				foreach ( $__group_courses as $course_id ) {
					ld_update_course_group_access( (int) $course_id, (int) $group_id, true );
					$transient_key = 'learndash_course_groups_' . $course_id;
					delete_transient( $transient_key );
				}
				foreach ( $group_courses as $course_id ) {
					ld_update_course_group_access( (int) $course_id, (int) $group_id );
					$transient_key = 'learndash_course_groups_' . $course_id;
					delete_transient( $transient_key );
				}
			} else {
				// Remove all courses
				$group_courses = learndash_group_enrolled_courses( $group_id );
				foreach ( $group_courses as $course_id ) {
					ld_update_course_group_access( (int) $course_id, (int) $group_id, true );
					$transient_key = 'learndash_course_groups_' . $course_id;
					delete_transient( $transient_key );
				}
			}
		}

		if ( true === $is_editable && true === $is_editable_woo && ! empty( $number_of_seats_entered ) ) {

			$code_group_id   = ulgm()->group_management->seat->get_code_group_id( $group_id );
			$existing_seats  = ulgm()->group_management->seat->total_seats( $group_id );
			$available_seats = ulgm()->group_management->seat->available_seats( $group_id );
			$combined        = ( $existing_seats - $available_seats );
			// Increasing seat count
			if ( $number_of_seats_entered > $existing_seats ) {
				$diff      = $number_of_seats_entered - $existing_seats;
				$new_codes = ulgm()->group_management->generate_random_codes( $diff );
				$attr      = array(
					'qty'           => $diff,
					'code_group_id' => $code_group_id,
				);
				ulgm()->group_management->add_additional_codes( $attr, $new_codes );
				update_post_meta( $group_id, '_ulgm_total_seats', $number_of_seats_entered );
			} elseif ( $number_of_seats_entered >= $combined ) {
				// Decreasing seat counts
				$diff = $existing_seats - $number_of_seats_entered;
				//$diff             = $diff * - 1; //convert to positive
				$fetch_code_count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(code) AS available FROM ' . $wpdb->prefix . SharedFunctions::$db_group_codes_tbl . ' WHERE group_id = %d AND student_id IS NULL LIMIT %d', $code_group_id, $diff ) );
				if ( ! empty( $fetch_code_count ) && $fetch_code_count >= $diff ) {
					//difference seats are empty, lets delete them
					$sql = $wpdb->prepare( 'DELETE FROM ' . $wpdb->prefix . SharedFunctions::$db_group_codes_tbl . ' WHERE group_id = %d AND student_id IS NULL LIMIT %d', $code_group_id, $diff );
					$wpdb->query( $sql );
					update_post_meta( $group_id, '_ulgm_total_seats', $existing_seats - $diff );

					do_action( 'ulgm_seats_removed', $diff, $group_id, $code_group_id );
				}
			} elseif ( $number_of_seats_entered < $combined ) {
				$edit_group_page_url = add_query_arg( 'seat-available-error', 'yes', $edit_group_page_url );
				$redirect            = $edit_group_page_url;
				wp_safe_redirect( $redirect );
				exit;
			}
		}

		$redirect = $redirect_to;
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * @param $group_id
	 * @param $is_purchased
	 *
	 * @return bool
	 */
	public static function is_editable( $group_id, $is_purchased = false ) {
		// Check if the group was purchased
		if ( true === $is_purchased ) {
			// Return inverse value (has license converts from true to false)
			return ! SharedFunctions::is_license_exists( $group_id );
		}

		// Check if pool seats are enabled
		if ( false === SharedFunctions::is_pool_seats_enabled() ) {
			return true;
		}

		// If not a parent group, disallow
		if ( false === SharedFunctions::is_a_parent_group( $group_id ) ) {
			return false;
		}

		// If pool seats are enabled for the parent group, then disallow child
		if ( false === SharedFunctions::is_pool_seats_enabled_for_current_parent_group( $group_id, true ) ) {
			return true;
		}

		return true;
	}

	/**
	 * @param $group_id
	 *
	 * @return string
	 */
	public static function is_disabled( $group_id ) {
		$is_editable_woo = self::is_editable( $group_id, true );
		$disabled        = false === $is_editable_woo ? 'disabled' : '';
		if ( false === SharedFunctions::is_pool_seats_enabled() ) {
			return $disabled;
		}
		if ( false === SharedFunctions::is_pool_seats_enabled_for_current_parent_group( $group_id, true ) ) {
			return $disabled;
		}
		$has_children = SharedFunctions::has_children_in_group( $group_id );
		$is_parent    = SharedFunctions::is_a_parent_group( $group_id );
		if ( $is_parent && $has_children ) {
			return '';
		}

		return 'disabled';
	}
}
