<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

namespace uncanny_pro_toolkit;

use uncanny_learndash_toolkit as toolkit;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Group Logo List
 *
 * @package uncanny_pro_toolkit
 */
class GroupLogoList extends toolkit\Config implements toolkit\RequiredFunctions {

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( __CLASS__, 'run_frontend_hooks' ) );
	}

	/**
	 * Initializes frontend actions and filters
	 */
	public static function run_frontend_hooks() {

		if ( true === self::dependants_exist() ) {

			add_filter( 'init', array( __CLASS__, 'add_thumbnail_support' ), 20, 2 );
			add_filter( 'post_type_labels_groups', array( __CLASS__, 'add_thumbnail_labels' ), 10, 1 );
			add_shortcode( 'uo_group_logo', array( __CLASS__, 'uo_group_logo' ) );

			// Add group list shortcode which takes attribute separator(default = ' ,').
			add_shortcode( 'uo_group_list', array( __CLASS__, 'uo_group_list' ) );
			add_shortcode( 'uo_group_leaders', array( __CLASS__, 'uo_group_leaders' ) );
		}

	}

	/**
	 * Does the plugin rely on another function or plugin
	 *
	 * @return boolean|string Return either true or name of function or plugin
	 */
	public static function dependants_exist() {

		/* Checks for LearnDash */
		global $learndash_post_types;
		if ( ! isset( $learndash_post_types ) ) {
			return 'Plugin: LearnDash';
		}

		// Return true if no dependency or dependency is available.
		return true;
	}

	/**
	 * Description of class in Admin View
	 *
	 * @return array
	 */
	public static function get_details() {
		$module_id = 'group-logo-list';

		$class_title = esc_html__( 'Group Logo/List', 'uncanny-pro-toolkit' );

		$kb_link = 'https://www.uncannyowl.com/knowledge-base/ld-group-logo-list/';

		/* Sample Simple Description with shortcode */
		$class_description = esc_html__( "Add group-specific logos to any page, including registration pages. A shortcode to list a user's LearnDash Groups is also available.", 'uncanny-pro-toolkit' );

		/* Icon as fontawesome icon */
		$class_icon = '<i class="uo_icon_pro_fa uo_icon_fa fa fa-file-image-o "></i><span class="uo_pro_text">PRO</span>';

		$category = 'learndash';
		$type     = 'pro';

		return array(
			'id'               => $module_id,
			'title'            => $class_title,
			'type'             => $type,
			'category'         => $category,
			'kb_link'          => $kb_link, // OR set as null not to display
			'description'      => $class_description,
			'dependants_exist' => self::dependants_exist(),
			'settings'         => false,
			'icon'             => $class_icon,
		);

	}

	/**
	 * Adds thumbnail support to groups.
	 */
	public static function add_thumbnail_support() {

		// bail if post type already supports thumbnails.
		if ( post_type_supports( 'groups', 'thumbnail' ) ) {
			return;
		}

		// otherwise, add support for thumbnails.
		add_post_type_support( 'groups', 'thumbnail' );

	}

	/**
	 * Adds custom labels for logos.
	 *
	 * @param obj $labels Default labels object.
	 *
	 * @return obj
	 */
	public static function add_thumbnail_labels( $labels ) {

		// set up groupl logo related labels.
		$labels->featured_image        = esc_attr__( 'Branding Logo', 'uncanny-pro-toolkit' );
		$labels->set_featured_image    = esc_attr__( 'Set Logo', 'uncanny-pro-toolkit' );
		$labels->remove_featured_image = esc_attr__( 'Remove Logo', 'uncanny-pro-toolkit' );
		$labels->use_featured_image    = esc_attr__( 'Use Logo', 'uncanny-pro-toolkit' );

		return $labels;
	}

	/**
	 * Outputs a images asscoiated with a user's groups
	 *
	 * @param array $attributes Shortcode attributes.
	 *
	 * @return string
	 */
	public static function uo_group_logo( $attributes ) {

		$attributes = shortcode_atts(
			array(
				'size'             => 'full',
				'limit'            => null,
				'group_id'         => null,
				'only_user_groups' => null,
				'hide_logo_admin'  => null,
			),
			$attributes
		);

		$user_id          = get_current_user_id();
		$only_user_groups = 'yes' === strtolower( (string) $attributes['only_user_groups'] );
		$hide_logo_admin  = 'yes' === strtolower( (string) $attributes['hide_logo_admin'] );
		$is_group_leader  = learndash_is_group_leader_user( $user_id );
		$is_admin_user    = current_user_can( 'administrator' );

		// Hide logo for admin users.
		if ( $hide_logo_admin && $is_admin_user ) {
			return '';
		}

		if ( $only_user_groups ) {
			$group_ids = learndash_get_users_group_ids( $user_id );
		} elseif ( $is_group_leader || $is_admin_user ) {
			$group_ids = learndash_get_administrators_group_ids( $user_id );
		} else {
			$group_ids = learndash_get_users_group_ids( $user_id );
		}

		if ( empty( $group_ids ) ) {
			return '';
		}

		$logo          = '';
		$limit         = ( isset( $attributes['limit'] ) && is_numeric( $attributes['limit'] ) ) ? absint( $attributes['limit'] ) : count( $group_ids );
		$limit         = ( $limit >= count( $group_ids ) ) ? count( $group_ids ) : $limit;
		$group_ids     = array_map( 'absint', $group_ids );
		$attr_group_id = ( isset( $attributes['group_id'] ) && is_numeric( $attributes['group_id'] ) ) ? absint( $attributes['group_id'] ) : null;

		if ( null !== $attr_group_id && ! in_array( $attr_group_id, $group_ids, true ) ) {
			return $logo;
		}

		if ( is_numeric( $attr_group_id ) ) {
			$group_ids = array( $attr_group_id );
		}

		if ( ! empty( $group_ids ) ) {
			$i = 1;
			foreach ( $group_ids as $group_id ) {
				if ( $i > $limit ) {
					break;
				}

				$img = get_the_post_thumbnail( $group_id, $attributes['size'] );
				if ( ! empty( $img ) ) {
					$logo .= $img;
					$i ++;
				}
			}
		}

		// Add custom class.
		return str_replace( 'class="', 'class="uo_white_label_logo ', $logo );
	}

	/**
	 * Return a list of LearnDash group names the use is a part of
	 *
	 * @param array $attributes Shortcode attributes.
	 *
	 * @return string
	 */
	public static function uo_group_list( $attributes ) {

		$attributes = shortcode_atts( array( 'separator' => ', ' ), $attributes );

		$user_id   = get_current_user_id();
		$group_ids = learndash_get_users_group_ids( $user_id );

		$logo = array();

		if ( ! empty( $group_ids ) ) {
			foreach ( $group_ids as $group_id ) {
				$post_title = get_the_title( $group_id );
				if ( $post_title ) {
					$logo[] = $post_title;
				}
			}
		}

		return implode( $attributes['separator'], $logo );

	}

	/**
	 * Returns a list of Group leaders.
	 *
	 * @param array $attributes Shortcode attributes.
	 *
	 * @return string
	 * @since 3.5.3
	 */
	public static function uo_group_leaders( $attributes ) {

		$group_leader_info = '';
		$user_id           = get_current_user_id();
		$group_ids         = learndash_get_users_group_ids( $user_id );
		if ( empty( $group_ids ) ) {
			return $group_leader_info;
		}
		$groups_count      = count( $group_ids );
		$group_leader_info .= '<div class="uo_group_info_wrapper">';
		foreach ( $group_ids as $group_id ) {
			if ( 0 !== $group_id ) {

				$group_leaders = learndash_get_groups_administrators( $group_id );

				if ( $groups_count > 1 ) {
					$group_leader_info .= '<div class="uo_group_name">' . get_the_title( $group_id ) . '</div>';
				}
				if ( ! empty( $group_leaders ) ) {
					foreach ( $group_leaders as $group_leader ) {

						if ( isset( $group_leader->data ) && isset( $group_leader->data->ID ) && absint( $group_leader->data->ID ) ) {

							$first_name = get_user_meta( $group_leader->data->ID, 'first_name', true );
							if ( ! empty( $first_name ) ) {
								$first_name = $first_name . ' ';
							} else {
								$first_name = '';
							}

							$last_name = get_user_meta( $group_leader->data->ID, 'last_name', true );
							if ( ! empty( $last_name ) ) {
								$last_name = $last_name . ' ';
							} else {
								$last_name = '';
							}

							$add_dash = '';
							if ( ! empty( $first_name ) || ! empty( $last_name ) ) {
								$add_dash = '<span class="uo_group_leader_dash"> - </span>';
							}

							$email             = $group_leader->data->user_email;
							$group_leader_info .= '<div class="uo_group_leader_list"> <span class="uo_group_leader_name">' . $first_name . $last_name . '</span>' . $add_dash . '<a href="mailto:' . $email . '"><span class="uo_group_leader_email">' . $email . '</span></a></div>';
						}
					}
				}
			}
		}
		$group_leader_info .= '</div>';

		/**
		 * Filters group leaders list.
		 *
		 * @param string $group_leader_info Group leaders html output.
		 * @param array $group_ids Array of group IDs.
		 */
		$group_leader_info = apply_filters( 'uo_groups_leaders', $group_leader_info, $group_ids );

		return $group_leader_info;
	}
}
