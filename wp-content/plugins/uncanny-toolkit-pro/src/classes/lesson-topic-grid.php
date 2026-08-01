<?php
/**
 * Class LessonTopicGrid
 *
 * This class fetches Custom Post Type of Lessons/Topics
 * created under LearnDash to form a grid view.
 *
 * @since       2.1 Initial release
 * @subpackage  uncanny_pro_toolkit\LessonTopicGrid
 * @package     uncanny_learndash_toolkit
 */

namespace uncanny_pro_toolkit;

use LDLMS_Factory_Post;
use LDLMS_Transients;
use LearnDash_Settings_Section;
use uncanny_learndash_toolkit as toolkit;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class LessonTopicGrid
 *
 * @package uncanny_pro_toolkit
 */
class LessonTopicGrid extends toolkit\Config implements toolkit\RequiredFunctions {
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
	public static function run_frontend_hooks() {

		if ( true === self::dependants_exist() ) {
			/* ADD FILTERS ACTIONS FUNCTION */
			if ( ! is_admin() ) {
				add_shortcode( 'uo_lessons_topics_grid', array( __CLASS__, 'uo_lessons_topics_grid' ) );
			}
			add_filter( 'uo_lesson_topic_grid_view_style', array( __CLASS__, 'uo_grid_view_get_style' ), 10, 1 );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'uo_grid_view_style' ), 99 );
			add_image_size( 'uo_lesson_image_size', 624, 468, true ); //3X the image we need so that it looks good on mobile view
			add_action( 'wp_footer', array( __CLASS__, 'grid_page_js' ) );
			add_filter( 'learndash_template', array( __CLASS__, 'replace_lesson_topic_template' ), 9999, 5 );
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
		$module_id = 'enhanced-lessons-topics-grid';

		$class_title = esc_html__( 'Enhanced Lessons/Topics Grid', 'uncanny-pro-toolkit' );

		$kb_link = 'https://www.uncannyowl.com/knowledge-base/enhanced-lessons-topics-grid/';

		/* Sample Simple Description with shortcode */
		$class_description = esc_html__( 'Add a highly customizable grid of LearnDash lessons/topics to the front end, learner dashboard or anywhere you want. This is a great tool for sites with a large number of lessons/topics.', 'uncanny-pro-toolkit' );

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
			'settings'         => self::get_class_settings( $class_title ),
			'icon'             => $class_icon,
		);

	}

	/**
	 * HTML for modal to create settings
	 *
	 * @param String
	 *
	 * @return array || string Return either false or settings html modal
	 */
	public static function get_class_settings( $class_title ) {

		// Create options
		$options = array(

			array(
				'type'        => 'checkbox',
				'label'       => esc_html__( 'Replace lesson table on course pages with grid', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-lesson-grid-replace-lessons',
			),

			array(
				'type'        => 'checkbox',
				'label'       => esc_html__( 'Replace topics table on lesson pages with grid', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-lesson-grid-replace-topics',
			),
			array(
				'type'        => 'checkbox',
				'label'       => esc_html__( 'Include quizzes in course and lesson grids', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-lesson-grid-replace-quizzes',
			),
			array(
				'type'        => 'text',
				'placeholder' => '2',
				'label'       => esc_html__( 'Number of columns per row', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-lesson-grid-default-cols',
				'description' => esc_html__( '1 to 6 columns are supported', 'uncanny-pro-toolkit' ),
			),
			array(
				'type'        => 'checkbox',
				'label'       => esc_html__( 'Show Featured Image?', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-lesson-grid-featured-image',
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

		if (
			! has_shortcode( $post->post_content, 'uo_lessons_topics_grid' )
			&& ! has_block( 'uncanny-toolkit-pro/lesson-topic-grid', $post )
			&& ! in_array( $post->post_type, array( 'sfwd-courses', 'sfwd-lessons' ), true )
		) {
			return;
		}

		wp_enqueue_style( 'course-grid-view-core', plugins_url( '/assets/legacy/frontend/css/course-grid-view-core.css', dirname( __FILE__ ) ), array(), UNCANNY_TOOLKIT_PRO_VERSION );
		$grid_view_css = apply_filters( 'uo_lesson_topic_grid_view_style', plugins_url( '/assets/legacy/frontend/css/lesson-topic-grid-view.css', dirname( __FILE__ ) ) );
		wp_enqueue_style( 'lesson-topic-grid-view', $grid_view_css, array(), UNCANNY_TOOLKIT_PRO_VERSION );
	}

	/**
	 *
	 * @param $style_sheet
	 *
	 * @return string
	 * @since 1.0.1
	 */
	public static function uo_grid_view_get_style( $style_sheet ) {
		$file_path = get_stylesheet_directory() . '/uncanny-toolkit-pro/css/lesson-topic-grid-view.css';
		$http_path = get_stylesheet_directory_uri() . '/uncanny-toolkit-pro/css/lesson-topic-grid-view.css';

		if ( file_exists( $file_path ) ) {
			return $http_path;
		} else {
			return $style_sheet;
		}
	}

	/**
	 * @param $filepath
	 * @param $name
	 * @param $args
	 * @param $echo
	 * @param $return_file_path
	 *
	 * @return string
	 */
	public static function replace_lesson_topic_template( $filepath, $name, $args, $echo, $return_file_path ) {

		// Bail if not the templates we're looking for.
		$template_names = array( 'course', 'lesson' );
		if ( ! in_array( $name, $template_names, true ) ) {
			return $filepath;
		}

		// Check if we have replace values set to on.
		$replace_key = 'course' === $name ? 'lessons' : 'topics';
		$replace     = self::get_settings_value( "uncanny-lesson-grid-replace-{$replace_key}", __CLASS__ );

		// Bail if not replacing.
		if ( 'on' !== $replace ) {
			return $filepath;
		}

		$template = 'legacy';
		if ( class_exists( 'LearnDash_Theme_Register' ) ) {
			$template = \LearnDash_Theme_Register::get_active_theme_key();
		}

		// Get the file path.
		$file     = 'legacy' === $template ? "single-{$name}.php" : "single-ld30-{$name}.php";
		$filepath = self::get_template( $file, dirname( dirname( __FILE__ ) ) . '/src' );

		// Return the filtered file path.
		return apply_filters( "uo_single_{$name}_template", $filepath );
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
	public static function uo_lessons_topics_grid( $atts ) {
		$atts           = shortcode_atts(
			array(
				'course_id'            => '',
				//all|category-slug
				'lesson_id'            => '',
				//all|category-slug
				'is_lesson'            => 'no',
				//all|category-slug
				'is_topic'             => 'no',
				//all|category-slug
				'enrolled_only'        => 'no',
				//yes|no
				'limit'                => 'all',
				//all|3-9
				'cols'                 => 2,
				//3|4|5
				'hide_view_more'       => 'yes',
				//yes|no
				'show_image'           => 'yes',
				//yes|no
				'show_quizzes'         => 'no',
				//yes|no
				'link_to_content'      => 'yes',
				//yes|no
				'orderby'              => 'title',
				//date|title|any acceptable WP_Query argument
				'order'                => 'ASC',
				//ASC|DESC
				'border_hover'         => '',
				//''|#HEX
				'view_more_color'      => '',
				//''|#HEX
				'view_more_hover'      => '',
				//''|#HEX
				'view_more_text_color' => '',
				//''|#HEX
				'view_more_text'       => esc_attr__( 'View more', 'uncanny-pro-toolkit' ) . ' <i class="fa fa fa-arrow-circle-right"></i>',
				//View More
				'view_less_text'       => esc_attr__( 'View less', 'uncanny-pro-toolkit' ) . ' <i class="fa fa fa-arrow-circle-right"></i>',
				//View Less
				'category'             => 'all',
				//all|category-slug
				'tag'                  => 'all',
			),
			$atts,
			'uo_lessons_topics_grid'
		);
		$featured_image = self::get_settings_value( 'uncanny-lesson-grid-featured-image', __CLASS__ );
		if ( 'on' !== $featured_image ) {
			$atts['show_image'] = 'no';
		}

		global $post;
		$query_post_type = '';
		$course_step_id  = 0;
		$results         = array();

		if ( '' !== $atts['course_id'] ) {
			$course_id = absint( $atts['course_id'] );
		} else {
			$course_id = 'sfwd-courses' === $post->post_type ? $post->ID : learndash_get_course_id( $post->ID );
			$course_id = absint( $course_id );
		}

		/**
		 * Fix to show lesson grid on all pages regardless of user's access
		 * Fix to hide lesson grid ONLY on course page if user is not enrolled AND course content is disabled
		 *
		 * @since 2.4.2
		 *
		 * {block start}
		 */
		$display_grid    = true;
		$course_settings = get_post_meta( $course_id, '_sfwd-courses', true );
		if ( ! empty( $course_settings ) && ( key_exists( 'sfwd-courses_course_disable_content_table', $course_settings ) && 'on' === $course_settings['sfwd-courses_course_disable_content_table'] ) ) {
			if ( ! empty( $course_id ) ) {
				if ( ! sfwd_lms_has_access( $course_id, get_current_user_id() ) ) {
					if ( 'sfwd-courses' === $post->post_type ) {
						$display_grid = false;
					}
				}
			}
		}

		// Bail.
		if ( false === $display_grid || empty( $course_id ) ) {
			return '';
		}

		/**
		 * {block end}
		 */

		// Is Lesson with Course ID set.
		if ( 'yes' === $atts['is_lesson'] || '' !== $atts['course_id'] ) {
			$query_post_type = 'sfwd-lessons';
			// Is Topic with Lesson ID set.
		} elseif ( 'yes' === $atts['is_topic'] || '' !== $atts['lesson_id'] ) {
			$query_post_type = 'sfwd-topic';
			$course_step_id  = is_numeric( $atts['lesson_id'] ) ? (int) $atts['lesson_id'] : $post->ID;
			// Current Page is Course.
		} elseif ( 'sfwd-courses' === $post->post_type ) {
			$query_post_type = 'sfwd-lessons';
			// Current Page is Lesson.
		} elseif ( 'sfwd-lessons' === $post->post_type ) {
			$query_post_type = 'sfwd-topic';
			$course_step_id  = is_numeric( $atts['lesson_id'] ) ? (int) $atts['lesson_id'] : $post->ID;
			// Current Page is Topic.
		} elseif ( 'sfwd-topic' === $post->post_type ) {
			$course_step_id = $post->ID;
			// Default to Lessons.
		} else {
			$query_post_type = 'sfwd-lessons';
		}

		// Build Query
		$args = null;
		if ( isset( $atts['category'] ) && '' === $atts['category'] ) {
			$atts['category'] = 'all';
		}
		if ( isset( $atts['tag'] ) && '' === $atts['tag'] ) {
			$atts['tag'] = 'all';
		}

		if ( 'sfwd-lessons' === $query_post_type ) {
			if ( isset( $atts['category'] ) && 'all' !== $atts['category'] ) {
				$args = array(
					'taxonomy'  => 'category',
					'tax_field' => 'slug',
					'tax_terms' => sanitize_text_field( $atts['category'] ),
				);
			}
			if ( isset( $atts['tag'] ) && 'all' !== $atts['tag'] ) {
				$args = array(
					'taxonomy'  => 'post_tag',
					'tax_field' => 'slug',
					'tax_terms' => sanitize_text_field( $atts['tag'] ),
				);
			}
			if ( function_exists( 'learndash_get_course_lessons_list_legacy' ) ) {
				$get_lessons = learndash_get_course_lessons_list_legacy( $course_id, null, $args );
			} else {
				$get_lessons = learndash_get_course_lessons_list( $course_id, null, $args );
			}
			if ( $get_lessons ) {
				foreach ( $get_lessons as $lesson ) {
					$results[ $lesson['post']->ID ] = $lesson['post'];
				}
			}
		}

		if ( 'sfwd-topic' === $query_post_type ) {
			if ( isset( $atts['category'] ) && 'all' !== $atts['category'] ) {
				$args['tax_query'][] = array(
					'taxonomy' => 'category',
					'field'    => 'slug',
					'terms'    => array( sanitize_text_field( $atts['category'] ) ),
				);
			}
			if ( isset( $atts['tag'] ) && 'all' !== $atts['tag'] ) {
				$args['tax_query'][] = array(
					'taxonomy' => 'post_tag',
					'field'    => 'slug',
					'terms'    => array( sanitize_text_field( $atts['tag'] ) ),
				);
			}
			if ( isset( $args['tax_query'] ) && count( $args['tax_query'] ) > 1 ) {
				$args['tax_query']['relation'] = 'OR';
			}
			$results = self::learndash_get_topic_list( $course_step_id, $course_id, $args );
		}

		// Check if we are to include Quizzes.
		if ( 'yes' === $atts['show_quizzes'] ) {

			// Get Quizzes.
			$quizzes = array();
			// Get top level Course Quizzes.
			if ( 'sfwd-lessons' === $query_post_type ) {
				$quizzes = learndash_get_course_quiz_list( $course_id, null );
				// Get current level Quizzes.
			} elseif ( 'sfwd-topic' === $query_post_type ) {
				$step_id = ! empty( $course_step_id ) ? (int) $course_step_id : (int) $atts['lesson_id'];
				$quizzes = empty( $step_id ) ? array() : learndash_get_lesson_quiz_list( $step_id, null, $course_id );
			}

			// Add Quizzes to Results.
			if ( ! empty( $quizzes ) ) {
				foreach ( $quizzes as $quiz ) {
					$results[ $quiz['post']->ID ] = $quiz['post'];
				}
			}
		}

		// Set Template vars.
		$total_lessons_topics = is_array( $results ) ? count( $results ) : 0;
		$total                = 0;
		$cols                 = $atts['cols'];
		$show                 = 'all' === $atts['limit'] ? 999 : $atts['limit'];
		$cols                 = $cols > 6 ? 2 : $cols;

		if ( $total_lessons_topics > $show && 'all' !== $atts['limit'] ) {
			$total = 1;
		}
		if ( $atts['limit'] < $atts['cols'] ) {
			$total = 0;
		}
		if ( 'yes' === $atts['hide_view_more'] ) {
			$total = 0;
		}

		// LD 3.0.7+ change, adding sections
		$sections = function_exists( 'learndash_30_get_course_sections' ) ? learndash_30_get_course_sections( $course_id ) : array();
		// Generate Grid View.
		$grid = self::grid_view_list( $results, $cols, $query_post_type, $course_id );
		return self::build_default_view( $grid, $atts, $total, $show, $total_lessons_topics, $query_post_type, $sections );
	}

	/**
	 * Get topics list for a lesson
	 *
	 * @param int $for_lesson_id
	 * @param int $course_id
	 * @param array $topic_args
	 *
	 * @return array topics list
	 * @since 3.2
	 */
	private static function learndash_get_topic_list( $for_lesson_id = null, $course_id = null, $topic_args = null ) {
		if ( empty( $course_id ) ) {
			$course_id = learndash_get_course_id( $for_lesson_id );
		}

		if ( ( ! empty( $for_lesson_id ) ) && ( ! empty( $course_id ) ) ) {
			$transient_key = 'learndash_lesson_topics_' . $course_id . '_' . $for_lesson_id;
		} elseif ( ! empty( $for_lesson_id ) ) {
			$transient_key = 'learndash_lesson_topics_' . $for_lesson_id;
		} else {
			$transient_key = 'learndash_lesson_topics_all';
		}

		if ( class_exists( 'LDLMS_Transients' ) ) {
			$topics_array = LDLMS_Transients::get( $transient_key );
		} else {
			$topics_array = learndash_get_valid_transient( $transient_key );
		}

		if ( false === $topics_array ) {

			if ( ! empty( $for_lesson_id ) ) {

				$lessons_options = sfwd_lms_get_post_options( 'sfwd-lessons' );
				$orderby         = $lessons_options['orderby'];
				$order           = $lessons_options['order'];

				if ( ! empty( $course_id ) ) {
					$course_lessons_args = learndash_get_course_lessons_order( $course_id );
					$orderby             = isset( $course_lessons_args['orderby'] ) ? $course_lessons_args['orderby'] : 'title';
					$order               = isset( $course_lessons_args['order'] ) ? $course_lessons_args['order'] : 'ASC';
				}
			} else {
				$orderby = 'name';
				$order   = 'ASC';
			}

			$topics_query_args = array(
				'post_type'   => 'sfwd-topic',
				'numberposts' => - 1,
				'orderby'     => $orderby,
				'order'       => $order,
			);

			if ( ! empty( $for_lesson_id ) ) {
				$topics_query_args['meta_key']     = 'lesson_id';
				$topics_query_args['meta_value']   = $for_lesson_id;
				$topics_query_args['meta_compare'] = '=';
			}

			if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) ) {
				if ( ! empty( $course_id ) ) {

					$ld_course_steps_object = LDLMS_Factory_Post::course_steps( $course_id );
					$ld_course_steps_object->load_steps();
					$steps = $ld_course_steps_object->get_steps();

					if ( ( isset( $steps['sfwd-lessons'][ $for_lesson_id ]['sfwd-topic'] ) ) && ( ! empty( $steps['sfwd-lessons'][ $for_lesson_id ]['sfwd-topic'] ) ) ) {
						$topic_ids                    = array_keys( $steps['sfwd-lessons'][ $for_lesson_id ]['sfwd-topic'] );
						$topics_query_args['include'] = $topic_ids;
						$topics_query_args['orderby'] = 'post__in';

						unset( $topics_query_args['order'] );
						unset( $topics_query_args['meta_key'] );
						unset( $topics_query_args['meta_value'] );
						unset( $topics_query_args['meta_compare'] );
					} else {
						return array();
					}
				}
			}

			if ( ! empty( $topic_args ) ) {
				$topics_query_args = array_merge( $topics_query_args, $topic_args );
			}

			$topics = get_posts( $topics_query_args );

			if ( ! empty( $topics ) ) {
				if ( empty( $for_lesson_id ) ) {
					$topics_array = array();

					foreach ( $topics as $topic ) {
						if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) ) {
							$course_id = learndash_get_course_id( $topic->ID );
							$lesson_id = learndash_course_get_single_parent_step( $course_id, $topic->ID );
						} else {
							$lesson_id = learndash_get_setting( $topic, 'lesson' );
						}

						if ( ! empty( $lesson_id ) ) {
							// Need to clear out the post_content before transient storage.
							$topic->post_content          = 'EMPTY';
							$topics_array[ $lesson_id ][] = $topic;
						}
					}
					set_transient( $transient_key, $topics_array, MINUTE_IN_SECONDS );

					return $topics_array;
				} else {
					set_transient( $transient_key, $topics, MINUTE_IN_SECONDS );

					return $topics;
				}
			}
		} else {
			return $topics_array;
		}
	}

	/**
	 * @param $results
	 * @param $show
	 * @param $post_type
	 * @param $course_id
	 *
	 * @return array
	 * @since 1.0.1 || Returns pre-sorted multiple arrays for default_sorting
	 */
	private static function grid_view_list( $results, $show, $post_type, $course_id = null ) {
		$grid_classes = array( 'grid-course', $post_type );
		$content_info = array();

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
			case 5:
				$grid_classes[] = 'uo-col-15';
				$grid_classes[] = 'uo-5-col';
				break;
			case 6:
				$grid_classes[] = 'uo-col-16';
				$grid_classes[] = 'uo-6-col';
				break;
			case 4:
			default:
				$grid_classes[] = 'uo-col-14';
				$grid_classes[] = 'uo-4-col';
				break;
		}
		if ( $results ) {
			foreach ( $results as $result ) {
				$user_id = get_current_user_id();
				$status  = 'not-completed';
				switch ( $result->post_type ) {
					case 'sfwd-lessons':
						$status = learndash_is_lesson_complete( $user_id, $result->ID, $course_id ) ? 'completed' : $status;
						break;
					case 'sfwd-topic':
						$status = learndash_is_topic_complete( $user_id, $result->ID, $course_id ) ? 'completed' : $status;
						break;
					case 'sfwd-quiz':
						$status = learndash_is_quiz_complete( $user_id, $result->ID, $course_id ) ? 'completed' : $status;
						break;
				}
				if ( 'completed' === $status ) {
					$status_icon = esc_html__( 'Complete', 'uncanny-pro-toolkit' ) . ' <span class="ultp-icon ultp-icon--check-circle"></span>';
				} else {
					$status_icon = esc_html__( 'Not Completed', 'uncanny-pro-toolkit' ) . '';
				}
				$content_info[ $result->ID ] = (object) array(
					'ID'          => $result->ID,
					'post_title'  => $result->post_title,
					'post_type'   => $result->post_type,
					'status_icon' => $status_icon,
					'status'      => $status,
					'is_enrolled' => sfwd_lms_has_access( $course_id, $user_id ),
				);
			}
		}

		$view_more = array( 'classes' => $grid_classes );

		return array(
			'content_info' => $content_info,
			'view_more'    => $view_more,
			'grid_classes' => $grid_classes,
		);

	}

	/**
	 *
	 * @param $grid
	 * @param $atts
	 * @param $total
	 * @param $show
	 * @param $total_courses
	 * @param $post_type
	 *
	 * @param $sections
	 *
	 * @return string
	 * @since 3.3 || Added default sorting so that subset in grid can be re-arranged
	 *
	 * @since 1.1.0 || Added default sorting so that subset in grid can be re-arranged
	 *
	 * @since 1.0.1 || Initial release
	 */
	private static function build_default_view( $grid, $atts, $total, $show, $total_courses, $post_type, $sections ) {
		$grid_wrapper_start = '<div class="uo-ultp-grid-container"><div class="uo-grid-wrapper ' . $post_type . '">';
		$grid_wrapper_end   = '</div></div>';
		$return_content     = '';

		if ( ! empty( $grid['content_info'] ) ) {
			foreach ( $grid['content_info'] as $key => $value ) {
				$post_id = $key;
				if ( is_array( $sections ) && key_exists( $post_id, $sections ) ) {
					$return_content .= '<h3 class="grid-course grid-section">' . $sections[ $post_id ]->post_title . '</h3>';
				}
				$return_content .= self::course_grid_single( $atts, get_post( $post_id ), $value->status_icon, $grid['grid_classes'], $value->status );
			}
		}

		$style  = self::grid_style( $atts );
		$script = self::grid_js( $atts );

		$semi_grid = $grid_wrapper_start . $return_content . $grid_wrapper_end;

		return $style . $semi_grid . $script;
	}

	/**
	 * @param $atts
	 * @param $lesson_topic_id
	 * @param $status_icon
	 * @param $grid_classes
	 * @param $completed
	 *
	 * @return string
	 * @since 1.0.1 || Returns a single "block" of grid with all course info
	 * @since 1.1.0 || Added language support to hardcoded Text, i.e., View Course Outline
	 */
	private static function course_grid_single( $atts, $lesson_topic_id, $status_icon, $grid_classes, $completed = 'not-completed' ) {
		$course_id = learndash_get_course_id( $lesson_topic_id );
		$user_id   = wp_get_current_user()->ID;
		if ( sfwd_lms_has_access( $course_id, $user_id ) ) {
			$permalink = get_permalink( $lesson_topic_id->ID );
		} elseif ( learndash_is_sample( $lesson_topic_id->ID ) ) {
			$permalink      = get_permalink( $lesson_topic_id->ID );
			$status_icon    = str_replace( esc_html__( 'Not Completed', 'uncanny-pro-toolkit' ), esc_html__( 'Sample Lesson', 'uncanny-pro-toolkit' ), $status_icon );
			$grid_classes[] = 'sample';
		} else {
			$permalink   = 'javascript:;';
			$status_icon = str_replace( esc_html__( 'Not Completed', 'uncanny-pro-toolkit' ), esc_html__( 'Course not enrolled', 'uncanny-pro-toolkit' ), $status_icon );
		}

		$default_no_image_path = plugins_url( '/assets/legacy/frontend/img/no_image.jpg', dirname( __FILE__ ) );
		ob_start();
		$template_path = self::get_template( 'lesson-topic-grid.php', dirname( dirname( __FILE__ ) ) . '/src' );
		$grid_template = apply_filters( 'uo_lesson_topic_grid_template', $template_path );
		include $grid_template;

		return ob_get_clean();
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
		$style .= '#learndash_lesson_topics_list, #learndash_course_content{display:none !important;}';
		$style .= '#learndash_quizzes{clear:both !important;}';
		//$style .= '.learndash-wrapper .ld-lesson-topic-list, .learndash-wrapper .ld-item-list{display:none !important;}';
		$style .= '</style>';

		return $style;
	}

	/**
	 * @param $atts
	 *
	 * @return string
	 * @since 1.1.0 || Returns page inline <javascript> tag for View More Animation
	 */
	private static function grid_js( $atts ) {

		ob_start();

		?>

		<script>
			if (typeof uoViewMoreText === 'undefined') {
				// the namespace is not defined
				var uoViewMoreText = true;

				(function ($) { // Self Executing function with $ alias for jQuery

					/* Initialization  similar to include once but since all js is loaded by the browser automatically the all
					 * we have to do is call our functions to initialize them, his is only run in the main configuration file
					 */
					$(document).ready(function () {

						jQuery('.uo-view-more-anchor').click(function () {
							var target = jQuery(jQuery(this).attr('data-target'))
							if (target.length > 0) {
								if (target.is(':visible')) {
									jQuery(this).html('<?php echo $atts['view_more_text']; ?>')
								} else {
									jQuery(this).html('<?php echo $atts['view_less_text']; ?>')
								}
							}

						})

					})
				})(jQuery)
			}
		</script>

		<?php

		return ob_get_clean();
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

						jQuery('.uo-view-more-anchor').click(function (e) {
							var target = jQuery(jQuery(this).attr('data-target'))
							if (target.length > 0) {
								if (target.is(':visible')) {
									target.removeClass('uo-grid-wrapper--expanded')
								} else {
									target.addClass('uo-grid-wrapper--expanded')
									jQuery('html, body').animate({
										scrollTop: target.offset().top - 250
									}, 2000)
								}
							}
						})

					})
				})(jQuery)
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
	private static function resize_grid_image( $id, $size ) {
		$medium_array = image_downsize( get_post_thumbnail_id( $id ), $size );
		$medium_path  = $medium_array[0];

		return $medium_path;
	}

	/**
	 * Extract the show_quizzes parameter from the shortcode
	 *
	 * @param string $content
	 *
	 * @return bool
	 */
	public static function shortcode_has_show_quizzes_set( $content ) {
		$shortcode = 'uo_lessons_topics_grid';
		$pattern   = get_shortcode_regex( array( $shortcode ) );
		preg_match_all( '/' . $pattern . '/s', $content, $matches );
		if ( ! empty( $matches[0] ) ) {
			foreach ( $matches[0] as $shortcode ) {
				$atts = shortcode_parse_atts( $shortcode );
				return ! empty( $atts['show_quizzes'] ) && 'yes' === $atts['show_quizzes'];
			}
		}

		return false;
	}

	/**
	 * Get the default cols attribute for the shortcode
	 *
	 * @return string
	 */
	public static function get_shortcode_default_cols_attr() {
		$cols  = self::get_settings_value( 'uncanny-lesson-grid-default-cols', __CLASS__ );
		$total = ! empty( $cols ) ? $cols : 2;
		return ' " cols="' . $total . '"';
	}

	/**
	 * Get the default show_quiz attribute for the shortcode
	 *
	 * @return string
	 */
	public static function get_shortcode_default_show_quizzes_attr() {
		$show_quizzes = self::get_settings_value( 'uncanny-lesson-grid-replace-quizzes', __CLASS__ );
		return $show_quizzes ? ' show_quizzes="yes"' : '';
	}

}
