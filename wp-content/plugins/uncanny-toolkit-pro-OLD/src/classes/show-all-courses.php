<?php
/**
 * Class ShowAllCourses
 *
 * This class fetches Custom Post Type of Courses
 * created under LearnDash to form a grid view.
 *
 * @since       1.0.1
 * @since       1.1.0 Added ignore_default_soring, default_sorting
 * @since       1.2.0 Fixed has_shortcode line to include if ! empty $post->ID for 404 pages
 * @since       1.4 added enrolled_only to grid_view_ignore_list() in order to remove courses that user is not enrolled in
 * @since       1.4 added empty courses message in case no enrolled courses found or empty courses
 * @subpackage  uncanny_pro_toolkit\ShowAllCourses
 * @package     uncanny_learndash_toolkit
 */

namespace uncanny_pro_toolkit;

use uncanny_learndash_toolkit as toolkit;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class ShowAllCourses
 *
 * @package uncanny_pro_toolkit
 */
class ShowAllCourses extends toolkit\Config implements toolkit\RequiredFunctions {

	/**
	 * Class constructor
	 *
	 * @since 1.0.1
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( __CLASS__, 'run_frontend_hooks' ) );
	}

	/*
	 * Initialize frontend actions and filters
	 *
	 * @since 1.0.1
	 */
	/**
	 * @return void
	 */
	public static function run_frontend_hooks() {

		if ( true === self::dependants_exist() ) {
			/* ADD FILTERS ACTIONS FUNCTION */
			if ( ! is_admin() ) {
				add_shortcode( 'uo_courses', array( __CLASS__, 'uo_courses' ) );
			}
			add_filter( 'uo_grid_view_style', array( __CLASS__, 'uo_grid_view_get_style' ), 10, 1 );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'uo_grid_view_style' ), 99 );
			add_image_size( 'uo_course_image_size', 624, 468, true ); //3X the image we need so that it looks good on mobile view
			add_action( 'wp_footer', array( __CLASS__, 'grid_page_js' ) );
			add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
			add_action( 'save_post', array( __CLASS__, 'save_post' ) );
		}

	}

	/**
	 * Does the plugin rely on another function or plugin
	 *
	 * @return boolean || string Return either true or name of function or plugin
	 *
	 * @since           1.0.1
	 */
	public static function dependants_exist() {

		/* Checks for LearnDash */
		global $learndash_post_types;
		if ( ! isset( $learndash_post_types ) ) {
			return 'Plugin: LearnDash';
		}

		// Return true if no dependency or dependency is available
		return true;

	}

	/**
	 * Description of class in Admin View
	 *
	 * @return array
	 * @since 1.0.1
	 */
	public static function get_details() {
		$module_id = 'enhanced-course-grid';

		$class_title = esc_html__( 'Enhanced Course Grid', 'uncanny-pro-toolkit' );

		$kb_link = 'http://www.uncannyowl.com/knowledge-base/enhanced-course-grid/';

		/* Sample Simple Description with shortcode */
		$class_description = esc_html__( 'Add a highly customizable grid of LearnDash courses to the front end, learner dashboard or anywhere you want. This is a great tool for sites with a large number of courses.', 'uncanny-pro-toolkit' );

		/* Icon as fontawesome icon */
		$class_icon = '<i class="uo_icon_pro_fa uo_icon_fa fa fa-book"></i><span class="uo_pro_text">PRO</span>';

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
			'settings'         => self::get_class_settings( $class_title ), // OR
			'icon'             => $class_icon,
		);

	}

	/**
	 * HTML for modal to create settings
	 *
	 * @static
	 *
	 * @param $class_title
	 *
	 * @return string
	 */
	public static function get_class_settings( $class_title ) {

		// Create options
		$options = array(

			array(
				'type'        => 'textarea',
				'label'       => esc_html__( 'Text displayed when no courses are shown', 'uncanny-pro-toolkit' ),
				'option_name' => 'learn-dash-no-course-message',
			),

		);

		// Build html
		$html = self::settings_output(
			array(
				'class'   => __CLASS__,
				'title'   => $class_title,
				'options' => $options,
			)
		);

		return $html;
	}

	/**
	 * @since 1.0.1
	 *
	 * If there's a shortcode on page, than add stylesheets
	 * else ignore adding on all pages.
	 */
	public static function uo_grid_view_style() {

		global $post;

		if ( empty( $post->ID ) ) {
			return;
		}

		if ( ! has_shortcode( $post->post_content, 'uo_courses' ) && ! has_block( 'uncanny-toolkit-pro/course-grid', $post ) && ! self::has_reusable_block_shortcode( $post->post_content ) ) {
			return;
		}

		wp_enqueue_style( 'course-grid-view-core', plugins_url( '/assets/legacy/frontend/css/course-grid-view-core.css', dirname( __FILE__ ) ), array(), UNCANNY_TOOLKIT_PRO_VERSION );
		$grid_view_css = apply_filters( 'uo_grid_view_style', plugins_url( '/assets/legacy/frontend/css/course-grid-view.css', dirname( __FILE__ ) ) );
		wp_enqueue_style( 'course-grid-view', $grid_view_css, array(), UNCANNY_TOOLKIT_PRO_VERSION );
		// wp_enqueue_style( 'uo-menu-slug-css-fontawesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css' );

	}

	/**
	 *
	 * @param $style_sheet
	 *
	 * @return string
	 * @since 1.0.1
	 */
	public static function uo_grid_view_get_style( $style_sheet ) {
		$file_path = get_stylesheet_directory() . '/uncanny-toolkit-pro/css/course-grid-view.css';
		$http_path = get_stylesheet_directory_uri() . '/uncanny-toolkit-pro/css/course-grid-view.css';

		if ( file_exists( $file_path ) ) {
			return $http_path;
		} else {
			return $style_sheet;
		}
	}

	/**
	 *
	 * @param $atts
	 *
	 * @return string || Returns complete grid if courses are found or empty if conditions are not met
	 * @since          1.1.0 || Added new attributes: default_sorting which calls a method to generate grid view
	 *
	 * @since          1.0.1
	 * @since          1.1.0 || Added new attributes: ignore_default_sorting which calls a method to generate grid view
	 */
	public static function uo_courses( $atts ) {

		$atts = shortcode_atts(
			array(
				'category'                => 'all',
				//all|category-slug
				'ld_category'             => 'all',
				//all|category-slug
				'enrolled_only'           => 'no',
				//yes|no
				'not_enrolled'            => 'no',
				//yes|no
				'limit'                   => 4,
				//all|3-9
				'cols'                    => 4,
				//1|2|3|4|5
				'hide_view_more'          => 'no',
				//yes|no
				'hide_credits'            => 'no',
				//yes|no
				'hide_description'        => 'no',
				//yes|no
				'hide_progress'           => 'no',
				//yes|no
				'more'                    => '',
				//''|URL
				'show_image'              => 'yes',
				//yes|no
				'price'                   => 'yes',
				//$|Any
				'currency'                => '',
				//yes|no
				'link_to_course'          => 'yes',
				//yes|no
				'orderby'                 => 'title',
				//date|title|any acceptable WP_Query argument
				'order'                   => 'ASC',
				//ASC|DESC
				'default_sorting'         => 'course-progress,enrolled,not-enrolled,coming-soon,completed',
				//course-progress, enrolled, not-enrolled, coming-soon, completed
				'ignore_default_sorting'  => 'no',
				//yes|no
				'border_hover'            => '',
				//''|#HEX
				'view_more_color'         => '',
				//''|#HEX
				'view_more_hover'         => '',
				//''|#HEX
				'view_more_text_color'    => '',
				//''|#HEX
				'view_more_text'          => esc_attr__( 'View more', 'uncanny-pro-toolkit' ) . ' <i class="fa fa fa-arrow-circle-right"></i>',
				//View More
				'view_less_text'          => esc_attr__( 'View less', 'uncanny-pro-toolkit' ) . ' <i class="fa fa fa-arrow-circle-right"></i>',
				//View Less
				'categoryselector'        => 'hide',
				//show|hide
				'course_categoryselector' => 'hide',
				//show|hide
				'start_course_button'     => 'hide',
				//show|hide
				'resume_course_button'    => 'hide',
				//show|hide
				'tag'                     => 'all',
				//all|tag-slug
				'course_tag'              => 'all',
				//all|course-tag-slug
				'hide_title'              => 'no',
				//yes|no
				'taxonomy_relation'       => 'OR',
				//OR|AND
			),
			$atts,
			'uo_courses'
		);


		$args = array(
			'post_type'        => 'sfwd-courses',
			'post_status'      => 'publish',
			'posts_per_page'   => 999,
			'suppress_filters' => false,
			'order'            => sanitize_text_field( $atts['order'] ),
			'orderby'          => sanitize_text_field( $atts['orderby'] ),
		);

		if ( isset( $atts['ld_category'] ) && '' === $atts['ld_category'] ) {
			$atts['ld_category'] = 'all';
		}
		if ( isset( $atts['ld_category'] ) && 'all' !== $atts['ld_category'] ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'ld_course_category',
				'field'    => 'slug',
				'terms'    => explode( ',', sanitize_text_field( $atts['ld_category'] ) ),
			);
		}

		if ( isset( $atts['category'] ) && '' === $atts['category'] ) {
			$atts['category'] = 'all';
		}
		if ( isset( $atts['category'] ) && 'all' !== $atts['category'] ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'category',
				'field'    => 'slug',
				'terms'    => explode( ',', sanitize_text_field( $atts['category'] ) ),
			);
		}
		// Tag attribute filter
		if ( isset( $atts['course_tag'] ) && '' === $atts['course_tag'] ) {
			$atts['course_tag'] = 'all';
		}
		if ( isset( $atts['course_tag'] ) && 'all' !== $atts['course_tag'] ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'ld_course_tag',
				'field'    => 'slug',
				'terms'    => array( sanitize_text_field( $atts['course_tag'] ) ),
			);
		}

		if ( isset( $atts['tag'] ) && '' === $atts['tag'] ) {
			$atts['tag'] = 'all';
		}
		if ( isset( $atts['tag'] ) && 'all' !== $atts['tag'] ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'post_tag',
				'field'    => 'slug',
				'terms'    => array( sanitize_text_field( $atts['tag'] ) ),
			);
		}

		if ( isset( $atts['categoryselector'] ) && 'hide' !== $atts['categoryselector'] ) {
			if ( ( filter_has_var( INPUT_GET, 'catid' ) ) && ( ! empty( filter_input( INPUT_GET, 'catid' ) ) ) ) {
				$atts['cat']         = intval( filter_input( INPUT_GET, 'catid' ) );
				$args['tax_query'][] = array(
					'taxonomy' => 'category',
					'field'    => 'term_id',
					'terms'    => intval( filter_input( INPUT_GET, 'catid' ) ),
				);
			}
		}
		if ( isset( $atts['course_categoryselector'] ) && 'hide' !== $atts['course_categoryselector'] ) {
			if ( ( filter_has_var( INPUT_GET, 'course_catid' ) ) && ( ! empty( filter_input( INPUT_GET, 'course_catid' ) ) ) ) {
				//$atts['cat']         = intval( $_GET['course_catid'] );
				$args['tax_query'][] = array(
					'taxonomy' => 'ld_course_category',
					'field'    => 'term_id',
					'terms'    => intval( filter_input( INPUT_GET, 'course_catid' ) ),
				);
			}
		}
		if ( isset( $args['tax_query'] ) && count( $args['tax_query'] ) > 1 ) {

			$taxonomy_relation = strtoupper($atts['taxonomy_relation']);

			if( ! in_array( $taxonomy_relation, array('OR','AND'), true ) ) {
				$taxonomy_relation = 'OR';
			}

			$args['tax_query']['relation'] = $taxonomy_relation;
		}

		$args    = apply_filters( 'uo_courses_shortcode_args', $args, $atts, $_GET );
		$courses = apply_filters( 'uo_courses_shortcode_get_posts', get_posts( $args ), $atts, $_GET );

		$total_courses = count( $courses );
		$total         = 0;
		$cols          = $atts['cols'];
		$show          = $atts['limit'];
		$ignore        = $atts['ignore_default_sorting'];

		if ( $cols > 5 ) {
			$cols = 5;
		}

		if ( $cols < 1 ) {
			$cols = 1;
		}

		if ( 'all' === $atts['limit'] ) {
			$show = 999;
		}
		if ( count( $courses ) > $show && 'all' !== $atts['limit'] ) {
			$total = 1;
		}
		if ( $atts['limit'] < $atts['cols'] ) {
			$total = 0;
		}
		if ( 'yes' === $atts['hide_view_more'] ) {
			$total = 0;
		}

		$filter = '';
		if ( isset( $atts['categoryselector'] ) && 'show' === $atts['categoryselector'] ) {
			$filter .= self::category_selector( 'category', $atts );
		}

		if ( isset( $atts['course_categoryselector'] ) && 'show' === $atts['course_categoryselector'] ) {
			$filter .= self::category_selector( 'ld_course_category', $atts );
		}

		if ( 'yes' === $ignore ) {
			$grid   = self::grid_view_ignore_list( $courses, $cols, $atts['enrolled_only'], $atts['hide_progress'] );
			$return = self::build_ignored_view( $grid, $atts, $total, $show, $total_courses, $filter );
		} else {
			$grid   = self::grid_view_course_list( $courses, $cols, $atts['hide_progress'] );
			$return = self::build_default_view( $grid, $atts, $total, $show, $total_courses, $filter );
		}

		return $return;
	}

	/**
	 *
	 * @param $type
	 *
	 * @return string
	 * @since 2.5.0 || Initial release
	 * @since 2.5.0 || Added dropdown option category and course category
	 */
	private static function category_selector( $type, $atts ) {
		$filter = '';

		$categories = array();

		$get_categories_args = array(
			'taxonomy'   => $type,
			'type'       => 'sfwd-courses',
			'orderby'    => 'name',
			'order'      => 'ASC',
			'hide_empty' => false,
		);
		$categories          = apply_filters( 'uo_course_category_cats', get_categories( $get_categories_args ), $type, $atts );

		if ( $type === 'category' ) {
			$filter .= '<div class="uo-ultp-grid-container uo-ultp-grid-container--category-dropdown">';
			$filter .= '<div class="uo-grid-wrapper" id="uo_categorydropdown">';
			$filter .= '<form method="get">
                                    <label for="uo_categorydropdown_select">' . esc_html__( 'Category', 'uncanny-pro-toolkit' ) . '</label>
                                    <select id="uo_categorydropdown_select" name="catid" onChange="jQuery(\'#uo_categorydropdown form\').submit()">';
			$filter .= '<option value="">' . esc_html__( 'All categories', 'uncanny-pro-toolkit' ) . '</option>';

			foreach ( $categories as $category ) {
				$posts    = get_posts( 'post_type=sfwd-courses&category=' . $category->term_id );
				$count    = count( $posts );
				$selected = '';
				if ( $count > 0 ) {
					if ( filter_has_var( INPUT_GET, 'catid' ) || filter_has_var( INPUT_GET, 'course_catid' ) ) {
						if ( filter_has_var( INPUT_GET, 'catid' ) && absint( filter_input( INPUT_GET, 'catid' ) ) ) {
							if ( $category->term_id === absint( filter_input( INPUT_GET, 'catid' ) ) ) {
								$selected = 'selected="selected"';
							}
						}
					} else {
						if ( isset( $atts['category'] ) ) {
							if ( $atts['category'] === $category->slug ) {
								$selected = 'selected="selected"';
							}
						}
					}
					$filter .= "<option value='" . $category->term_id . "' " . $selected . '>' . $category->name . ' (' . $count . ')</option>';
				}
			}

			$filter .= "</select><input type='submit' style='display:none'></form></div></div>";
		}

		if ( $type === 'ld_course_category' ) {
			//$filter = '';
			$filter .= '<div class="uo-ultp-grid-container uo-ultp-grid-container--course-category-dropdown">';
			$filter .= '<div class="uo-grid-wrapper" id="uo_course_categorydropdown">';
			$filter .= '<form method="get">
                                    <label for="uo_course_categorydropdown_select">' . sprintf( esc_html__( '%s Category', 'uncanny-pro-toolkit' ), \LearnDash_Custom_Label::get_label( 'course' ) ) . '</label>
                                    <select id="uo_course_categorydropdown_select" name="course_catid" onChange="jQuery(\'#uo_course_categorydropdown form\').submit()">';
			$filter .= '<option value="">' . esc_html__( 'All categories', 'uncanny-pro-toolkit' ) . '</option>';

			foreach ( $categories as $category ) {
				$args  = array(
					'post_type'      => 'sfwd-courses',
					'post_status'    => 'publish',
					'posts_per_page' => 999,
					'tax_query'      => array(
						array(
							'taxonomy' => 'ld_course_category',
							'field'    => 'term_id',
							'terms'    => $category->term_id,
						),
					),
				);
				$posts = get_posts( $args );
				$count = count( $posts );
				//$selected = ( empty( $_GET['course_catid'] ) || $_GET['course_catid'] != $category->term_id ) ? '' : 'selected="selected"';
				$selected = '';
				if ( $count > 0 ) {
					if ( filter_has_var( INPUT_GET, 'course_catid' ) || filter_has_var( INPUT_GET, 'catid' ) ) {
						if ( filter_has_var( INPUT_GET, 'course_catid' ) && absint( filter_input( INPUT_GET, 'course_catid' ) ) ) {
							if ( $category->term_id === absint( filter_input( INPUT_GET, 'course_catid' ) ) ) {
								$selected = 'selected="selected"';
							}
						}
					} else {
						if ( isset( $atts['ld_category'] ) ) {
							if ( $atts['ld_category'] === $category->slug ) {
								$selected = 'selected="selected"';
							}
						}
					}
					$filter .= "<option value='" . $category->term_id . "' " . $selected . '>' . $category->name . ' (' . $count . ')</option>';
				}
			}

			$filter .= "</select><input type='submit' style='display:none'></form></div></div>";
		}

		return $filter;
	}

	/**
	 * @param        $courses
	 * @param        $show
	 * @param string $enrolled_only
	 * @param string $ignore_progress
	 *
	 * @return array
	 * @since 1.4   || added enrolled_only view, so that it does not add all courses
	 *
	 * @since 1.1.0 || Returns two arrays, Course Info and All Courses back to grid generator
	 */
	private static function grid_view_ignore_list( $courses, $show, $enrolled_only = 'no', $ignore_progress = 'no' ) {
		$is_enrolled  = false;
		$grid_classes = array( 'grid-course' );
		$course_info  = array();
		$all_courses  = array();

		switch ( $show ) {
			case 3:
				$grid_classes[] = 'uo-col-13';
				$grid_classes[] = 'uo-3-col';
				break;
			case 4:
				$grid_classes[] = 'uo-col-14';
				$grid_classes[] = 'uo-4-col';
				break;
			case 5:
				$grid_classes[] = 'uo-col-15';
				$grid_classes[] = 'uo-5-col';
				break;
			case 6:
				$grid_classes[] = 'uo-col-16';
				$grid_classes[] = 'uo-6-col';
				break;
			default:
				$grid_classes[] = 'uo-col-14';
				$grid_classes[] = 'uo-4-col';
				break;
		}

		foreach ( $courses as $course ) {
			$course_info[ $course->ID ] = (object) array(
				'ID'         => $course->ID,
				'post_title' => $course->post_title,
			);
			$status_icon                = '';
			if ( is_user_logged_in() ) {
				$user_id     = wp_get_current_user()->ID;
				$is_enrolled = sfwd_lms_has_access( $course->ID, $user_id );
			}

			if ( learndash_course_completed( get_current_user_id(), $course->ID ) ) {
				$status_icon = esc_html__( 'Complete', 'uncanny-pro-toolkit' ) . ' <span class="ultp-icon ultp-icon--check-circle"></span>';
			} elseif ( has_tag( 'coming-soon', $course->ID ) ) {
				$status_icon = esc_html__( 'Coming Soon', 'uncanny-pro-toolkit' );
			} elseif ( $is_enrolled ) {
				$status_icon = sprintf( esc_html__( '%s Status', 'uncanny-pro-toolkit' ), \LearnDash_Custom_Label::get_label( 'course' ) );
			} elseif ( ! $is_enrolled ) {
				//$status_icon = esc_html__( 'View Course Outline', 'uncanny-pro-toolkit' );
				$status_icon = sprintf( esc_html__( 'View %s Outline', 'uncanny-pro-toolkit' ), \LearnDash_Custom_Label::get_label( 'course' ) );
			}

			if ( $is_enrolled ) {
				if ( 'no' === $ignore_progress ) {
					$progress = learndash_course_progress(
						array(
							'course_id' => $course->ID,
							'array'     => true,
						)
					);
				} else {
					$progress = array( 'percentage' => 0 );
				}
				if ( learndash_course_completed( get_current_user_id(), $course->ID ) ) {
					$completed                  = true;
					$all_courses[ $course->ID ] = array(
						'status_icon'  => $status_icon,
						'grid_classes' => $grid_classes,
						'percentage'   => $progress['percentage'],
						'completed'    => $completed,
					);
				} else {
					$completed = false;
					if ( absint( $progress['percentage'] ) > 0 && absint( $progress['percentage'] ) < 100 ) {
						$all_courses[ $course->ID ] = array(
							'percentage'   => $progress['percentage'],
							'grid_classes' => $grid_classes,
							'completed'    => $completed,
							'status_icon'  => $status_icon,
						);
					} elseif ( has_tag( 'coming-soon', $course->ID ) ) {
						$all_courses[ $course->ID ] = array(
							'status_icon'  => $status_icon,
							'grid_classes' => $grid_classes,
						);
					} else {
						$all_courses[ $course->ID ] = array(
							'status_icon'  => $status_icon,
							'grid_classes' => $grid_classes,
							'percentage'   => $progress['percentage'],
							'completed'    => $completed,
						);
					}
				}
			} else {
				if ( 'no' === $enrolled_only ) {
					if ( has_tag( 'coming-soon', $course->ID ) ) {
						$all_courses[ $course->ID ] = array(
							'status_icon'  => $status_icon,
							'grid_classes' => $grid_classes,
						);
					} else {
						$all_courses[ $course->ID ] = array(
							'status_icon'  => $status_icon,
							'grid_classes' => $grid_classes,
						);
					}
				}
			}
		}

		$grid_classes[] = 'uo-view-more';
		$view_more      = array( 'classes' => $grid_classes );

		return array(
			'course_info' => $course_info,
			'all_courses' => $all_courses,
			'view_more'   => $view_more,
		);

	}

	/**
	 * @param $grid
	 * @param $atts
	 * @param $total
	 * @param $show
	 * @param $total_courses
	 * @param $filter
	 *
	 * @return string
	 * @since 1.1.0 || This function generates ignore_default grid view
	 * @since 1.4   || added enrolled_only view, so that it does not show all courses
	 * @since 2.5.0 || Added dropdown filter
	 */
	private static function build_ignored_view( $grid, $atts, $total, $show, $total_courses, $filter ) {
		$grid_wrapper_start = '<div class="uo-ultp-grid-container"><div class="uo-grid-wrapper">';
		$grid_wrapper_end   = '</div></div>';
		$all_courses        = '';
		$grid1              = '';
		$grid2              = '';
		$view_more          = '';

		if ( count( $grid['all_courses'] ) ) {
			if ( count( $grid['all_courses'] ) && $total < $show ) {
				foreach ( $grid['all_courses'] as $key => $value ) {
					$course              = $grid['course_info'][ $key ];
					$value['percentage'] = isset( $value['percentage'] ) ? $value['percentage'] : 0;
					$value['completed']  = isset( $value['completed'] ) ? $value['completed'] : 0;
					$all_courses         .= self::course_grid_single( $atts, $course, $value['status_icon'], $value['grid_classes'], $value['percentage'], $value['completed'] );
					unset( $grid['all_courses'][ $key ] );
					$total ++;
					if ( (int) $total === (int) $show ) {
						$total ++;
						break;
					}
				}
			}

			$rand = rand( 598, 45451 );
			if ( 'all' !== $show && $total_courses > $show ) {
				$view_more = self::show_view_more( $atts, $grid['view_more']['classes'], $atts['category'] . '-' . $rand, $atts['more'] );
			}
			if ( 'yes' === $atts['hide_view_more'] ) {
				$view_more = '';
			} else {
				$view_more = self::show_view_more( $atts, $grid['view_more']['classes'], $atts['category'] . '-' . $rand, $atts['more'] );
			}
			if ( 999 === (int) $show ) {
				$view_more = '';
			}

			$grid1 = $grid_wrapper_start . $all_courses . $view_more . $grid_wrapper_end;

			if ( 'no' !== $atts['more'] ) {

				$grid_wrapper_start = '<div class="uo-ultp-grid-container uo-ultp-grid-container--all"><div class="uo-grid-wrapper uo-clear-all" id="' . self::format_target_id( $atts['category'] . '-' . $rand ) . '">';
				$grid_wrapper_end   = '</div></div>';
				$all_courses        = '';
				$view_more          = '';

				if ( count( $grid['all_courses'] ) ) {
					foreach ( $grid['all_courses'] as $key => $value ) {
						$course = $grid['course_info'][ $key ];
						if ( ! isset( $value['percentage'] ) ) {
							$value['percentage'] = 0;
						}
						if ( ! isset( $value['completed'] ) ) {
							$value['completed'] = 0;
						}
						$all_courses .= self::course_grid_single( $atts, $course, $value['status_icon'], $value['grid_classes'], $value['percentage'], $value['completed'] );
						unset( $grid['all_courses'][ $key ] );
					}
				}

				$grid2 = $grid_wrapper_start . $all_courses . $view_more . $grid_wrapper_end;

			}
		} else {
			$no_course_message = self::get_settings_value( 'learn-dash-no-course-message', __CLASS__ );

			if ( ! empty( $no_course_message ) ) {
				$grid1 .= $no_course_message;
			} else {
				$grid1 = '<h3>';
				$grid1 .= sprintf( esc_attr__( "Sorry! You don't have any enrolled %s.", 'uncanny-pro-toolkit' ), \LearnDash_Custom_Label::get_label( 'courses' ) );
				$grid1 .= '</h3>';
			}
		}

		$style = self::grid_style( $atts );

		$semi_grid = $filter . $grid1 . $grid2;
		if ( substr_count( $semi_grid, 'grid-course' ) <= $show ) {
			$semi_grid = str_replace( 'uo-view-more-holder', 'uo-view-more-holder hidden ', $semi_grid );
		}

		return $style . $semi_grid;
	}

	/**
	 * @param        $atts
	 * @param        $course
	 * @param        $status_icon
	 * @param        $grid_classes
	 * @param int $percentage
	 * @param bool $completed
	 * @param string $permalink
	 *
	 * @return string
	 * @since 1.0.1 || Returns a single "block" of grid with all course info
	 * @since 1.1.0 || Added language support to hardcoded Text, i.e., View Course Outline
	 */
	private static function course_grid_single( $atts, $course, $status_icon, $grid_classes, $percentage = 0, $completed = false, $permalink = 'course-page' ) {
		if ( 'course-page' === $permalink ) {
			$permalink = get_permalink( $course->ID );
		} else {
			$permalink = 'javascript:;';
		}

		$hide_progress      = $atts['hide_progress'];
		$show_start_button  = $atts['start_course_button'];
		$show_resume_button = $atts['resume_course_button'];

		$course_options = get_post_meta( $course->ID, '_sfwd-courses', true );

		// Added in LD 4.1.0
		if ( function_exists( 'learndash_get_currency_symbol' ) ) {
			$currency = learndash_get_currency_symbol();
		}
		elseif ( function_exists( 'learndash_30_the_currency_symbol' ) ) {
			$currency = learndash_30_get_currency_symbol();
		} else {
			$options  = get_option( 'sfwd_cpt_options' );
			$currency = null;
			if ( ! is_null( $options ) ) {
				if ( isset( $options['modules'] ) && isset( $options['modules']['sfwd-courses_options'] ) && isset( $options['modules']['sfwd-courses_options']['sfwd-courses_paypal_currency'] ) ) {
					$currency = $options['modules']['sfwd-courses_options']['sfwd-courses_paypal_currency'];
				}

				if ( is_null( $currency ) ) {
					$paypal_settings = get_option( 'learndash_settings_paypal', '' );
					if ( ! empty( $paypal_settings ) ) {
						if ( ! empty( $paypal_settings['paypal_currency'] ) ) {
							$currency = $paypal_settings['paypal_currency'];
						} else {
							$currency = '$';
						}
					} else {
						$currency = '$';
					}
				}
			}
		}

		// Get the course price
		$course_price_type = ( $course_options && isset( $course_options['sfwd-courses_course_price_type'] ) ) ? $course_options['sfwd-courses_course_price_type'] : esc_html__( 'Free', 'uncanny-pro-toolkit' );
		$course_price      = ( $course_options && isset( $course_options['sfwd-courses_course_price'] ) ) ? $course_options['sfwd-courses_course_price'] : '';

		//Override Currency Symbol
		if ( ! empty( $atts['currency'] ) ) {
			$currency = $atts['currency'];
		}

		if ( absint( $course_price ) ) {
			$course_price = sprintf( esc_attr__( '%1$s %2$s', 'uncanny-pro-toolkit' ), $currency, (string) $course_price );
		}

		$short_description = '';
		if ( is_array( $course_options ) && key_exists( 'sfwd-courses_course_short_description', $course_options ) ) {
			$short_description = do_shortcode( $course_options['sfwd-courses_course_short_description'] );
		}
		$short_description_check = get_post_meta( $course->ID, 'course_short_description', true );
		if ( ! empty( $short_description_check ) ) {
			$short_description = $short_description_check;
		}
		$short_description = do_shortcode( htmlspecialchars_decode( $short_description ) );
		$short_description = apply_filters( 'uo_course_grid_description', $short_description, $atts, $course, $status_icon, $grid_classes, $percentage, $completed, $permalink );

		ob_start();
		$grid_template = self::get_template( 'course-grid.php', dirname( dirname( __FILE__ ) ) . '/src' );
		$grid_template = apply_filters( 'uo_course_grid_template', $grid_template );

		include $grid_template;

		return ob_get_clean();
	}

	/**
	 * @param        $atts
	 * @param        $class
	 * @param        $category
	 * @param string $more
	 *
	 * @return string
	 * @since 1.0.1 || Returns View More "block"
	 */
	private static function show_view_more( $atts, $class, $category, $more = '' ) {
		$url         = 'javascript:;';
		$data_target = "#" . self::format_target_id( $category );
		if ( self::is_url( $more ) ) {
			$url         = $more;
			$data_target = '';
		}

		ob_start();
		?>
		<div class="uo-view-more uo-view-more-holder <?php esc_attr_e( implode( ' ', $class ) ); ?>">
			<a class="uo-view-more-anchor"
			   data-target="<?php esc_attr_e( $data_target ); ?>"
			   data-viewmore="<?php esc_attr_e( $atts['view_more_text'] ); ?>"
			   data-viewless="<?php esc_attr_e( $atts['view_less_text'] ); ?>"
			   href="<?php echo $url; ?>">
				<?php echo $atts['view_more_text']; ?>
			</a>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * @param $string
	 *
	 * @return string
	 * @since 1.0.1 || Returns true if the $string is a URL
	 */
	public static function is_url( $string ) {
		$domain = '[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])'; // one domain component //! IDN

		return ( preg_match( "~^(https?)://($domain?\\.)+$domain(:\\d+)?(/.*)?(\\?.*)?(#.*)?\$~i", $string, $match ) ? strtolower( $match[1] ) : '' ); //! restrict path, query and fragment characters
	}

	/**
	 * @param $atts
	 *
	 * @return string
	 * @since 1.1.0 || Returns page inline <style> tag if override attributes are added to shortcode
	 */
	private static function grid_style( $atts ) {
		$style = '<style>';
		if ( ! empty( $atts['border_hover'] ) ) {
			$style .= '.uo-grid-wrapper .grid-course:hover .uo-border{border-color:' . esc_attr( $atts['border_hover'] ) . '}';
		}
		if ( ! empty( $atts['view_more_color'] ) ) {
			$style .= '.uo-view-more a{background-color:' . esc_attr( $atts['view_more_color'] ) . '}';
			$style .= '#ribbon{background-color:' . esc_attr( $atts['view_more_color'] ) . '; box-shadow: 0px 2px 4px ' . esc_attr( $atts['view_more_color'] ) . '}';
		}
		if ( ! empty( $atts['view_more_hover'] ) ) {
			$style .= '.uo-view-more a:hover{background-color:' . esc_attr( $atts['view_more_hover'] ) . '}';
			$style .= '#ribbon:after{border-color:' . esc_attr( $atts['view_more_hover'] ) . ' ' . esc_attr( $atts['view_more_hover'] ) . ' transparent transparent;}';
		}
		if ( ! empty( $atts['view_more_text_color'] ) ) {
			$style .= '.uo-view-more a{color:' . esc_attr( $atts['view_more_text_color'] ) . '}';
		}
		$style .= '</style>';

		return $style;
	}

	/**
	 * @param        $courses
	 * @param        $show
	 * @param string $ignore_progress
	 *
	 * @return array
	 * @since 1.0.1 || Returns pre-sorted multiple arrays for default_sorting
	 */
	private static function grid_view_course_list( $courses, $show, $ignore_progress = 'no' ) {
		$is_enrolled      = false;
		$grid_classes     = array( 'grid-course' );
		$enrolled         = array();
		$completed_course = array();
		$not_enrolled     = array();
		$coming_soon      = array();
		$course_progress  = array();
		$percentage_array = array();
		$course_info      = array();

		switch ( $show ) {
			case 1:
				$grid_classes[] = 'uo-col-11';
				$grid_classes[] = 'uo-1-col';
				break;
			case 2:
				$grid_classes[] = 'uo-col-12';
				$grid_classes[] = 'uo-2-col';
				break;
			case 3:
				$grid_classes[] = 'uo-col-13';
				$grid_classes[] = 'uo-3-col';
				break;
			case 4:
				$grid_classes[] = 'uo-col-14';
				$grid_classes[] = 'uo-4-col';
				break;
			case 5:
				$grid_classes[] = 'uo-col-15';
				$grid_classes[] = 'uo-5-col';
				break;
			case 6:
				$grid_classes[] = 'uo-col-16';
				$grid_classes[] = 'uo-6-col';
				break;
			default:
				$grid_classes[] = 'uo-col-14';
				$grid_classes[] = 'uo-4-col';
				break;
		}

		foreach ( $courses as $course ) {
			$course_info[ $course->ID ] = (object) array(
				'ID'         => $course->ID,
				'post_title' => $course->post_title,
			);
			$status_icon                = '';
			if ( is_user_logged_in() ) {
				$user_id     = get_current_user_id();
				$is_enrolled = sfwd_lms_has_access( $course->ID, $user_id );
			}

			if ( learndash_course_completed( get_current_user_id(), $course->ID ) ) {
				$status_icon = esc_html__( 'Complete', 'uncanny-pro-toolkit' ) . ' <span class="ultp-icon ultp-icon--check-circle"></span>';
			} elseif ( has_tag( 'coming-soon', $course->ID ) ) {
				$status_icon = esc_html__( 'Coming Soon', 'uncanny-pro-toolkit' );
			} elseif ( $is_enrolled ) {
				$status_icon = sprintf( esc_html__( '%s Status', 'uncanny-pro-toolkit' ), \LearnDash_Custom_Label::get_label( 'course' ) );
			} elseif ( ! $is_enrolled ) {
				//$status_icon = 'View Course Outline';
				$status_icon = sprintf( esc_html__( 'View %s Outline', 'uncanny-pro-toolkit' ), \LearnDash_Custom_Label::get_label( 'course' ) );
			}

			if ( $is_enrolled ) {
				if ( 'no' === $ignore_progress ) {
					$progress = learndash_course_progress(
						array(
							'course_id' => $course->ID,
							'array'     => true,
						)
					);
				} else {
					$progress = array( 'percentage' => 0 );
				}

				if ( learndash_course_completed( get_current_user_id(), $course->ID ) ) {
					$completed                       = true;
					$completed_course[ $course->ID ] = array(
						'status_icon'  => $status_icon,
						'grid_classes' => $grid_classes,
						'percentage'   => $progress['percentage'],
						'completed'    => $completed,
					);
				} else {
					$completed = false;
					if ( absint( $progress['percentage'] ) > 0 && absint( $progress['percentage'] ) < 100 ) {
						$percentage_array[ $course->ID ] = $progress['percentage'];
					} elseif ( has_tag( 'coming-soon', $course->ID ) ) {
						$coming_soon[ $course->ID ] = array(
							'status_icon'  => $status_icon,
							'grid_classes' => $grid_classes,
						);
					} else {
						$enrolled[ $course->ID ] = array(
							'status_icon'  => $status_icon,
							'grid_classes' => $grid_classes,
							'percentage'   => $progress['percentage'],
							'completed'    => $completed,
						);
					}
				}
			} else {
				if ( has_tag( 'coming-soon', $course->ID ) ) {
					$coming_soon[ $course->ID ] = array(
						'status_icon'  => $status_icon,
						'grid_classes' => $grid_classes,
					);
				} else {
					$not_enrolled[ $course->ID ] = array(
						'status_icon'  => $status_icon,
						'grid_classes' => $grid_classes,
					);
				}
			}
		}
		if ( 'no' === $ignore_progress ) {
			if ( is_array( $percentage_array ) ) {
				arsort( $percentage_array );

				foreach ( $percentage_array as $key => $value ) {
					$course                         = get_post( $key );
					$completed                      = false;
					$status_icon                    = sprintf( esc_attr__( '%s Status', 'uncanny-pro-toolkit' ), \LearnDash_Custom_Label::get_label( 'course' ) );
					$course_progress[ $course->ID ] = array(
						'status_icon'  => $status_icon,
						'grid_classes' => $grid_classes,
						'percentage'   => $value,
						'completed'    => $completed,
					);
				}
			}
		}

		$grid_classes[] = 'uo-view-more';
		$view_more      = array( 'classes' => $grid_classes );

		return array(
			'course_info'     => $course_info,
			'course_progress' => $course_progress,
			'enrolled'        => $enrolled,
			'not_enrolled'    => $not_enrolled,
			'coming_soon'     => $coming_soon,
			'completed'       => $completed_course,
			'view_more'       => $view_more,
		);

	}

	/**
	 *
	 * @param $grid
	 * @param $atts
	 * @param $total
	 * @param $show
	 * @param $total_courses
	 * @param $filter
	 *
	 * @return string
	 * @since 2.5.0 || Added dropdown filter
	 *
	 * @since 1.0.1 || Initial release
	 */
	private static function build_default_view( $grid, $atts, $total, $show, $total_courses, $filter = '' ) {
		$grid_wrapper_start = '<div class="uo-ultp-grid-container"><div class="uo-grid-wrapper">';
		$grid_wrapper_end   = '</div></div>';
		$course_progress    = '';
		$enrolled           = '';
		$not_enrolled       = '';
		$coming_soon        = '';
		$completed_course   = '';
		$view_more          = '';
		$default_order      = explode( ',', $atts['default_sorting'] );
		$is_no_course       = true;

		$grid1 = '';
		$grid2 = '';

		if ( is_array( $default_order ) ) {
			foreach ( $default_order as $order ) {
				$order = trim( $order );
				switch ( $order ) {
					case 'course-progress':
						if ( 'yes' === $atts['enrolled_only'] || ( 'yes' !== $atts['not_enrolled'] && 'yes' !== $atts['enrolled_only'] ) ) {
							if ( count( $grid['course_progress'] ) && $total < $show ) {
								foreach ( $grid['course_progress'] as $key => $value ) {
									$course = $grid['course_info'][ $key ];
									if ( 'no' === $atts['link_to_course'] ) {
										$permalink = false;
									} else {
										$permalink = 'course-page';
									}
									$is_no_course    = false;
									$course_progress .= self::course_grid_single( $atts, $course, $value['status_icon'], $value['grid_classes'], $value['percentage'], $value['completed'], $permalink );
									unset( $grid['course_progress'][ $key ] );
									$total ++;
									if ( (int) $total === (int) $show ) {
										$total ++;
										break;
									}
								}
							}
						}
						break;
					case 'enrolled':
						if ( 'yes' === $atts['enrolled_only'] || ( 'yes' !== $atts['not_enrolled'] && 'yes' !== $atts['enrolled_only'] ) ) {
							if ( count( $grid['enrolled'] ) && $total < $show ) {
								foreach ( $grid['enrolled'] as $key => $value ) {
									$course = $grid['course_info'][ $key ];
									if ( 'no' === $atts['link_to_course'] ) {
										$permalink = false;
									} else {
										$permalink = 'course-page';
									}
									if ( ! isset( $value['percentage'] ) ) {
										$value['percentage'] = 0;
									}
									$is_no_course = false;
									$enrolled     .= self::course_grid_single( $atts, $course, $value['status_icon'], $value['grid_classes'], $value['percentage'], $value['completed'], $permalink );
									unset( $grid['enrolled'][ $key ] );
									$total ++;
									if ( (int) $total === (int) $show ) {
										$total ++;
										break;
									}
								}
							}
						}
						break;
					case 'completed':
						if ( 'yes' === $atts['enrolled_only'] || ( 'yes' !== $atts['not_enrolled'] && 'yes' !== $atts['enrolled_only'] ) ) {
							if ( count( $grid['completed'] ) && $total < $show ) {
								foreach ( $grid['completed'] as $key => $value ) {
									$course = $grid['course_info'][ $key ];
									if ( 'no' === $atts['link_to_course'] ) {
										$permalink = false;
									} else {
										$permalink = 'course-page';
									}
									if ( ! isset( $value['percentage'] ) ) {
										$value['percentage'] = 0;
									}
									$is_no_course     = false;
									$completed_course .= self::course_grid_single( $atts, $course, $value['status_icon'], $value['grid_classes'], $value['percentage'], $value['completed'], $permalink );
									unset( $grid['completed'][ $key ] );
									$total ++;
									if ( (int) $total === (int) $show ) {
										$total ++;
										break;
									}
								}
							}
						}

						break;
					case 'not-enrolled':
						if ( 'yes' === $atts['not_enrolled'] || ( 'yes' !== $atts['not_enrolled'] && 'yes' !== $atts['enrolled_only'] ) ) {
							if ( count( $grid['not_enrolled'] ) && $total < $show ) {
								foreach ( $grid['not_enrolled'] as $key => $value ) {
									$course = $grid['course_info'][ $key ];
									if ( 'no' === $atts['link_to_course'] ) {
										$permalink = false;
									} else {
										$permalink = 'course-page';
									}
									$is_no_course = false;
									$not_enrolled .= self::course_grid_single( $atts, $course, $value['status_icon'], $value['grid_classes'], 0, false, $permalink );
									unset( $grid['not_enrolled'][ $key ] );
									$total ++;
									if ( (int) $total === (int) $show ) {
										$total ++;
										break;
									}
								}
							}
						}

						break;
					case 'coming-soon':
						if ( 'yes' === $atts['not_enrolled'] || ( 'yes' !== $atts['not_enrolled'] && 'yes' !== $atts['enrolled_only'] ) ) {
							if ( count( $grid['coming_soon'] ) && $total < $show ) {
								foreach ( $grid['coming_soon'] as $key => $value ) {
									$course = $grid['course_info'][ $key ];
									if ( 'no' === $atts['link_to_course'] ) {
										$permalink = false;
									} else {
										$permalink = 'course-page';
									}
									$is_no_course = false;
									$coming_soon  .= self::course_grid_single( $atts, $course, $value['status_icon'], $value['grid_classes'], 0, false, $permalink );
									unset( $grid['coming_soon'][ $key ] );
									$total ++;
									if ( (int) $total === (int) $show ) {
										$total ++;
										break;
									}
								}
							}
						}
						break;
				}
			}
		}

		$rand = rand( 598, 45451 );

		if ( 'all' !== $show && $total_courses > $show ) {
			$view_more = self::show_view_more( $atts, $grid['view_more']['classes'], $atts['category'] . '-' . $rand, $atts['more'] );
		}

		if ( 'yes' === $atts['hide_view_more'] ) {
			$view_more = '';
		} else {
			$view_more = self::show_view_more( $atts, $grid['view_more']['classes'], $atts['category'] . '-' . $rand, $atts['more'] );
		}
		if ( 999 === (int) $show ) {
			$view_more = '';
		}

		if ( $total_courses == $show ) {
			$view_more = '';
		}

		if ( 'yes' === $atts['enrolled_only'] ) {
			$grid1 = $grid_wrapper_start;
			if ( is_array( $default_order ) ) {
				foreach ( $default_order as $order ) {
					$order = trim( $order );
					switch ( $order ) {
						case 'course-progress':
							$grid1 .= $course_progress;
							break;
						case 'enrolled':
							$grid1 .= $enrolled;
							break;
						case 'completed':
							$grid1 .= $completed_course;
							break;
					}
				}
			}
			$grid1 .= $view_more . $grid_wrapper_end;
		} elseif ( 'yes' === $atts['not_enrolled'] ) {
			$grid1 = $grid_wrapper_start;
			if ( is_array( $default_order ) ) {
				foreach ( $default_order as $order ) {
					$order = trim( $order );
					switch ( $order ) {
						case 'not-enrolled':
							$grid1 .= $not_enrolled;
							break;
						case 'coming-soon':
							$grid1 .= $coming_soon;
							break;
					}
				}
			}
			$grid1 .= $view_more . $grid_wrapper_end;
		} else {
			$grid1 = $grid_wrapper_start;
			if ( is_array( $default_order ) ) {
				foreach ( $default_order as $order ) {
					$order = trim( $order );
					switch ( $order ) {
						case 'course-progress':
							$grid1 .= $course_progress;
							break;
						case 'enrolled':
							$grid1 .= $enrolled;
							break;
						case 'completed':
							$grid1 .= $completed_course;
							break;
						case 'not-enrolled':
							$grid1 .= $not_enrolled;
							break;
						case 'coming-soon':
							$grid1 .= $coming_soon;
							break;
					}
				}
			}
			$grid1 .= $view_more . $grid_wrapper_end;
		}

		if ( 'no' !== $atts['more'] ) {

			$grid_wrapper_start = '<div class="uo-ultp-grid-container uo-ultp-grid-container--all"><div class="uo-grid-wrapper uo-clear-all" id="' . self::format_target_id( $atts['category'] . '-' . $rand ) . '">';
			$grid_wrapper_end   = '</div></div>';
			$course_progress    = '';
			$enrolled           = '';
			$not_enrolled       = '';
			$coming_soon        = '';
			$completed_course   = '';
			$view_more          = '';

			if ( count( $grid['course_progress'] ) ) {
				foreach ( $grid['course_progress'] as $key => $value ) {
					$course = $grid['course_info'][ $key ];
					if ( ! isset( $value['percentage'] ) ) {
						$value['percentage'] = 0;
					}
					$course_progress .= self::course_grid_single( $atts, $course, $value['status_icon'], $value['grid_classes'], $value['percentage'], $value['completed'] );
					unset( $grid['course_progress'][ $key ] );
				}
			}
			if ( count( $grid['enrolled'] ) ) {
				foreach ( $grid['enrolled'] as $key => $value ) {
					$course = $grid['course_info'][ $key ];
					if ( ! isset( $value['percentage'] ) ) {
						$value['percentage'] = 0;
					}
					$enrolled .= self::course_grid_single( $atts, $course, $value['status_icon'], $value['grid_classes'], $value['percentage'], $value['completed'] );
					unset( $grid['enrolled'][ $key ] );
				}
			}
			if ( count( $grid['not_enrolled'] ) ) {
				foreach ( $grid['not_enrolled'] as $key => $value ) {
					$course = $grid['course_info'][ $key ];
					if ( 'no' === $atts['link_to_course'] ) {
						$permalink = false;
					} else {
						$permalink = 'course-page';
					}
					$not_enrolled .= self::course_grid_single( $atts, $course, $value['status_icon'], $value['grid_classes'], 0, false, $permalink );
					unset( $grid['not_enrolled'][ $key ] );
				}
			}
			if ( count( $grid['coming_soon'] ) ) {
				foreach ( $grid['coming_soon'] as $key => $value ) {
					$course = $grid['course_info'][ $key ];
					if ( 'no' === $atts['link_to_course'] ) {
						$permalink = false;
					} else {
						$permalink = 'course-page';
					}
					$coming_soon .= self::course_grid_single( $atts, $course, $value['status_icon'], $value['grid_classes'], 0, false, $permalink );
					unset( $grid['coming_soon'][ $key ] );
				}
			}
			if ( count( $grid['completed'] ) ) {
				foreach ( $grid['completed'] as $key => $value ) {
					$course           = $grid['course_info'][ $key ];
					$completed_course .= self::course_grid_single( $atts, $course, $value['status_icon'], $value['grid_classes'], $value['percentage'], $value['completed'] );
					unset( $grid['completed'][ $key ] );
				}
			}

			if ( 'yes' === $atts['enrolled_only'] ) {
				$grid2 = $grid_wrapper_start;
				if ( is_array( $default_order ) ) {
					foreach ( $default_order as $order ) {
						$order = trim( $order );
						switch ( $order ) {
							case 'course-progress':
								$grid2 .= $course_progress;
								break;
							case 'enrolled':
								$grid2 .= $enrolled;
								break;
							case 'completed':
								$grid2 .= $completed_course;
								break;
						}
					}
				}
				$grid2 .= $view_more . $grid_wrapper_end;
			} elseif ( 'yes' === $atts['not_enrolled'] ) {
				$grid2 = $grid_wrapper_start;
				if ( is_array( $default_order ) ) {
					foreach ( $default_order as $order ) {
						$order = trim( $order );
						switch ( $order ) {
							case 'not-enrolled':
								$grid2 .= $not_enrolled;
								break;
							case 'coming-soon':
								$grid2 .= $coming_soon;
								break;
						}
					}
				}
				$grid2 .= $view_more . $grid_wrapper_end;
			} else {
				$grid2 = $grid_wrapper_start;
				if ( is_array( $default_order ) ) {
					foreach ( $default_order as $order ) {
						$order = trim( $order );
						switch ( $order ) {
							case 'course-progress':
								$grid2 .= $course_progress;
								break;
							case 'enrolled':
								$grid2 .= $enrolled;
								break;
							case 'completed':
								$grid2 .= $completed_course;
								break;
							case 'not-enrolled':
								$grid2 .= $not_enrolled;
								break;
							case 'coming-soon':
								$grid2 .= $coming_soon;
								break;
						}
					}
				}
				$grid2 .= $view_more . $grid_wrapper_end;
			}
		}

		$style = self::grid_style( $atts );
		if ( $is_no_course ) {
			$no_course_message = self::get_settings_value( 'learn-dash-no-course-message', __CLASS__ );

			if ( ! empty( $no_course_message ) ) {
				$grid1 = $no_course_message;
			}
		}
		$semi_grid = $filter . $grid1 . $grid2;
		if ( substr_count( $semi_grid, 'grid-course' ) <= $show ) {
			$semi_grid = str_replace( 'uo-view-more-holder', 'uo-view-more-holder hidden ', $semi_grid );
		}

		return $style . $semi_grid;
	}

	/**
	 * @since 1.5 || echos <javascript> in the footer.. helpful for multiple
	 * grid implementations on a page.
	 */
	public static function grid_page_js() {

		ob_start();

		?>

		<script>
			if (typeof uoViewMoreModules === 'undefined') {
				// the namespace is not defined
				var uoViewMoreModules = true;

				(function ($) { // Self Executing function with $ alias for jQuery

					/* Initialization  similar to include once but since all js is loaded by the browser automatically the all
					 * we have to do is call our functions to initialize them, his is only run in the main configuration file
					 */
					$(document).ready(function () {

						jQuery('.uo-view-more-anchor').click(function (event) {

							// select the target grid to show/hide
							var target = jQuery(jQuery(event.currentTarget).data('target'));
							if (target.length > 0) {

								// if it is already visible, hide.
								if (target.is(':visible')) {
									target.removeClass('uo-grid-wrapper--expanded');
								} else { // animate the display.
									target.addClass('uo-grid-wrapper--expanded');
									jQuery('html, body').animate({
										scrollTop: target.offset().top - 250
									}, 2000)
								}
							}
						});

					});
				})(jQuery);
			}

			if (typeof uoViewMoreText === 'undefined') {
				// the namespace is not defined
				var uoViewMoreText = true;

				(function ($) { // Self Executing function with $ alias for jQuery

					/* Initialization  similar to include once but since all js is loaded by the browser automatically the all
					 * we have to do is call our functions to initialize them, his is only run in the main configuration file
					 */
					$(document).ready(function () {

						jQuery('.uo-view-more-anchor').click(function (event) {

							// select the target grid.
							var target = jQuery(jQuery(this).data('target'));

							if (target.length > 0) {

								// if target is visible, switch to view less text.
								if (target.is(':visible')) {
									jQuery(this).html(jQuery(this).data('viewless'));
								} else { // otherwise, switch to view more label.
									jQuery(this).html(jQuery(this).data('viewmore'));
								}
							}
						});

					});
				})(jQuery);
			}
		</script>

		<?php

		echo ob_get_clean();

	}

	/**
	 * @param $id
	 * @param $size
	 *
	 * @return mixed
	 * @since 1.0.1 || Returns URL of the resized Image cropped as per grid specification
	 */
	public static function resize_grid_image( $id, $size ) {
		$medium_array = image_downsize( get_post_thumbnail_id( $id ), $size );
		$medium_path  = $medium_array[0];

		return $medium_path;
	}

	/**
	 * @return void
	 */
	public static function add_meta_boxes() {
		add_meta_box(
			__( 'Course Short Description', 'uncanny-pro-toolkit' ),
			__( 'Course Short Description', 'uncanny-pro-toolkit' ),
			array( __CLASS__, 'add_meta_box_callback' ),
			'sfwd-courses',
			'normal',
			'core'
		);
	}

	/**
	 * @param $post_id
	 *
	 * @return void
	 */
	public static function save_post( $post_id ) {
		if ( filter_has_var( INPUT_POST, 'course_short_description' ) ) {
			$sanitized = wp_kses_post( filter_input( INPUT_POST, 'course_short_description' ) );
			update_post_meta( $post_id, 'course_short_description', $sanitized );
		}
	}

	/**
	 * @return void
	 */
	public static function add_meta_box_callback() {
		echo '<div class="rwp-description">' . sprintf( esc_attr__( 'A short description of the %s to show on grid.', 'uncanny-pro-toolkit' ), \LearnDash_Custom_Label::get_label( 'course' ) ) . '</div>';
		self::fields_table();
	}

	/**
	 * @return void
	 */
	private static function fields_table() {
		?>
		<table class="form-table" role="presentation">
			<tbody>
			<tr>
				<th scope="row"><?php echo esc_attr__( 'Short Description', 'uncanny-pro-toolkit' ); ?></th>
			</tr>
			<tr>
				<td>
					<?php
					printf(
						'<textarea class="regular-text" id="%s" name="%s" rows="%d">%s</textarea>',
						'course_short_description',
						'course_short_description',
						5,
						wp_kses_post( self::value( 'course_short_description' ) )
					);
					?>
				</td>
			</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * @param $field
	 *
	 * @return string
	 */
	private static function value( $field ) {
		global $post;
		if ( metadata_exists( 'post', $post->ID, $field ) ) {
			$value = get_post_meta( $post->ID, $field, true );
		} else {
			$course_options = get_post_meta( $post->ID, '_sfwd-courses', true );
			if ( is_array( $course_options ) && key_exists( 'sfwd-courses_course_short_description', $course_options ) ) {
				$value = $course_options['sfwd-courses_course_short_description'];
			} else {

				return '';
			}
		}

		return str_replace( '\u0027', "'", $value );
	}

	private static function has_reusable_block_shortcode( $content ){
		if( $content ){

			// Check blocks
			$blocks = parse_blocks( $content );

			if ( ! is_array( $blocks ) || empty( $blocks ) ) {
				return false;
			}

			foreach ( $blocks as $block ) {
				if ( $block['blockName'] === 'core/block' && ! empty( $block['attrs']['ref'] ) ) {
					$content = get_post_field( 'post_content', $block['attrs']['ref'] );
					if ( has_shortcode( $content, 'uo_courses' ) ||  has_block( 'uncanny-toolkit-pro/course-grid', $block['attrs']['ref']) ){
					   return true;
					}
				}
			}
		}

		return false;
	}

	private static function format_target_id( $str ){
		return md5( $str );
	}
}
