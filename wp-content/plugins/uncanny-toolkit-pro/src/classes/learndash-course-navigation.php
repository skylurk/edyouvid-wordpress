<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

namespace uncanny_pro_toolkit;

use LearnDash_Settings_Section;
use uncanny_learndash_toolkit as toolkit;

defined( 'WPINC' ) || exit;

/**
 * Implements Lazy Load Navigation widget
 */
class LearndashCourseNavigation extends toolkit\Config implements toolkit\RequiredFunctions {

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( __CLASS__, 'hooks' ) );
	}

	/**
	 * Initialize frontend actions and filters
	 */
	public static function hooks() {

		if ( true === self::dependants_exist() ) {

			add_shortcode( 'uo-course-navigation', array( __CLASS__, 'render_shortcode' ) );
			add_action( 'rest_api_init', array( __CLASS__, 'rest_endpoint' ) );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'localize_data' ) );
		}

	}

	/**
	 * Does the plugin rely on another function or plugin
	 *
	 * @return boolean|string Return either true or name of function or plugin
	 */
	public static function dependants_exist() {

		// check for LearnDash.
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
		$module_id         = 'lazy-loading-course-navigation';
		$class_title       = esc_html__( 'Lazy Loading Course Navigation', 'uncanny-pro-toolkit' );
		$kb_link           = 'https://www.uncannyowl.com/knowledge-base/lazy-loading-course-navigation/';
		$class_description = esc_html__( 'Shortcode that loads a course navigation tree via AJAX.  Useful on sites with many lessons and topics where the native LearnDash Course Navigation widget is extending page load time.', 'uncanny-pro-toolkit' );
		$class_icon        = '<i class="uo_icon_pro_fa uo_icon_fa fa fa-book "></i><span class="uo_pro_text">PRO</span>';
		$category          = 'learndash';
		$type              = 'pro';

		return array(
			'id'               => $module_id,
			'title'            => $class_title,
			'type'             => $type,
			'category'         => $category,
			'kb_link'          => $kb_link,
			'description'      => $class_description,
			'dependants_exist' => self::dependants_exist(),
			'settings'         => self::get_class_settings( $class_title ),
			'icon'             => $class_icon,
		);

	}

	/**
	 * HTML for modal to create settings
	 *
	 * @param string $class_title Title of the class.
	 * @param bool $only_options False to only return options, True for the full markup.
	 *
	 * @return array Return either false or settings html modal
	 */
	public static function get_class_settings( $class_title, $only_options = false ) {

		// Create options.
		$options = array(
			array(
				'type'       => 'html',
				'inner_html' => '<h2>' . esc_attr__( 'Navigation Settings', 'uncanny-pro-toolkit' ) . '</h2>',
			),
			array(
				'type'        => 'text',
				'label'       => esc_html__( 'Widget Heading', 'uncanny-pro-toolkit' ),
				'placeholder' => esc_html__( 'Course Navigation.', 'uncanny-pro-toolkit' ),
				'option_name' => 'uo_course_navigation_heading',
			),
		);

		if ( $only_options ) {
			return $options;
		}

		// Build html.
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
	 * Renders shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return false|string|void
	 */
	public static function render_shortcode( $atts ) {

		global $post;

		if ( empty( $post->ID ) || ! is_single() ) {
			return;
		}

		$course_id = learndash_get_course_id( $post->ID );

		// bail if we don't have a valid course.
		if ( empty( $course_id ) ) {
			return;
		}

		// bail if user is not allowed to view content.
		if ( 'on' === learndash_get_setting( $course_id, 'course_disable_content_table' )
			 && ! sfwd_lms_has_access( $course_id, get_current_user_id() ) ) {
			return;
		}

		// Output content.
		ob_start();

		?>
		<div class="ultp-lazy-course-navigation ultp-lazy-course-navigation--loading">
			<div class="ultp-lazy-course-navigation-loading">
				<div class="ultp-lazy-course-navigation-loading__icon"></div>
				<div class="ultp-lazy-course-navigation-loading__text">
					<?php esc_attr_e( 'Loading...', 'uncanny-pro-toolkit' ); ?>
				</div>
			</div>
		</div>
		<?php

		$output = ob_get_clean();

		return $output;
	}

	/**
	 * Localizes course data
	 */
	public static function localize_data() {
		global $post;

		if ( empty( $post->ID ) || ! is_single() ) {
			return;
		}

		$course_id = learndash_get_course_id( $post->ID );
		if ( empty( $course_id ) ) {
			return;
		}

		$lesson_id = 0;
		$topic_id  = 0;
		$quiz_id   = 0;

		if ( 'sfwd-quiz' === (string) $post->post_type ) {
			$quiz_id   = absint( $post->ID );
			$lesson_id = learndash_get_lesson_id( $post->ID );

			if ( 'yes' === \LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) ) {
				$topic_id = learndash_course_get_single_parent_step( $course_id, $post->ID );
			} else {
				$topic_id = learndash_get_setting( $post, 'topic' );
			}

			if ( $topic_id === $lesson_id ) {
				$topic_id = 0;
			}
		} elseif ( 'sfwd-topic' === (string) $post->post_type ) {
			$topic_id  = $post->ID;
			$lesson_id = learndash_get_lesson_id( $post->ID );
		} elseif ( 'sfwd-lessons' === (string) $post->post_type ) {
			$lesson_id = $post->ID;
		}

		$uo_course_navigation_trigger = self::get_settings_value( 'uo_course_navigation_trigger', __CLASS__, '' );

		// API data.
		$rest_api_setup = array(
			'course_id'                    => $course_id,
			'lesson_id'                    => $lesson_id,
			'topic_id'                     => $topic_id,
			'quiz_id'                      => $quiz_id,
			'uo_course_navigation_trigger' => $uo_course_navigation_trigger,
			'nonce'                        => wp_create_nonce( 'uo_course_navigation_nonce' ),
		);

		wp_localize_script( 'ultp-frontend', 'UncannyToolkitProLazyCourseNavigation', $rest_api_setup );
	}

	/**
	 * Implements REST API endpoint.
	 */
	public static function rest_endpoint() {
		register_rest_route(
			'uo_toolkit/v1',
			'/course_navigation/',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'content' ),
				'permission_callback' => function () {
					return true;
				},
			)
		);
	}

	/**
	 * Returns navigation content.
	 *
	 * @return array
	 */
	public static function content() {

		$nonce = filter_input( INPUT_POST, 'nonce' );

		if ( ! wp_verify_nonce( $nonce, 'uo_course_navigation_nonce' ) ) {
			return array();
		}

		$course_id = filter_input( INPUT_POST, 'course_id' );

		if ( empty( $course_id ) ) {
			return array();
		}

		$course_id = learndash_get_course_id( absint( $course_id ) );

		// bail, if we don't have a course ID.
		if ( empty( $course_id ) ) {
			return array();
		}

		// bail if the user isn't allowed to view this content.
		if ( 'on' === learndash_get_setting( $course_id, 'course_disable_content_table' )
			 && ! sfwd_lms_has_access( $course_id, get_current_user_id() ) ) {
			return array();
		}

		$uo_course_navigation_heading = self::get_settings_value( 'uo_course_navigation_heading', __CLASS__, '' );

		$instance['title']               = $uo_course_navigation_heading;
		$instance['show_lesson_quizzes'] = false;
		$instance['show_topic_quizzes']  = false;
		$instance['show_course_quizzes'] = false;
		$instance['show_widget_wrapper'] = true;
		$instance['current_lesson_id']   = 0;
		$instance['current_step_id']     = 0;

		$instance['current_step_id'] = absint( filter_input( INPUT_POST, 'topic_id' ) );

		if ( empty( $instance['current_step_id'] ) ) {
			$instance['current_step_id'] = absint( filter_input( INPUT_POST, 'lesson_id' ) );
		}

		if ( empty( $instance['current_step_id'] ) ) {
			$instance['current_step_id'] = absint( $course_id );
		}

		global $post;

		if ( empty( $post ) ) {
			$post = get_post( $instance['current_step_id'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		if ( ! is_a( $post, 'WP_Post' ) ) {
			return array();
		}

		$lesson_query_args       = array();
		$course_lessons_per_page = learndash_get_course_lessons_per_page( $course_id );

		if ( in_array(
			$post->post_type,
			array(
				'sfwd-lessons',
				'sfwd-topic',
				'sfwd-quiz',
			),
			true
		) ) {

			if ( $course_lessons_per_page > 0 ) {

				if ( 'sfwd-lessons' === (string) $post->post_type ) {
					$instance['current_lesson_id'] = $post->ID;
				} elseif ( in_array( $post->post_type, array( 'sfwd-topic', 'sfwd-quiz' ), true ) ) {
					$instance['current_lesson_id'] = learndash_course_get_single_parent_step( $course_id, $post->ID, 'sfwd-lessons' );
				}

				if ( ! empty( $instance['current_lesson_id'] ) ) {
					$course_lesson_ids = learndash_course_get_steps_by_type( $course_id, 'sfwd-lessons' );
					if ( ! empty( $course_lesson_ids ) ) {
						$course_lessons_paged = array_chunk( $course_lesson_ids, $course_lessons_per_page, true );
						$lessons_paged        = 0;
						foreach ( $course_lessons_paged as $paged => $paged_set ) {
							if ( in_array( $instance['current_lesson_id'], $paged_set, true ) ) {
								$lessons_paged = $paged + 1;
								break;
							}
						}

						if ( ! empty( $lessons_paged ) ) {
							$lesson_query_args['pagination'] = 'true';
							$lesson_query_args['paged']      = $lessons_paged;
						}
					}
				} elseif ( 'sfwd-quiz' === $post->post_type ) {
					// If here we have a global Quiz. So we set the pager to the max number.
					$course_lesson_ids = learndash_course_get_steps_by_type( $course_id, 'sfwd-lessons' );
					if ( ! empty( $course_lesson_ids ) ) {
						$course_lessons_paged       = array_chunk( $course_lesson_ids, $course_lessons_per_page, true );
						$lesson_query_args['paged'] = count( $course_lessons_paged );
					}
				}
			} else {
				$instance['current_step_id'] = $post->ID;
				if ( 'sfwd-lessons' === (string) $post->post_type ) {
					$instance['current_lesson_id'] = $post->ID;
				} elseif ( in_array( $post->post_type, array( 'sfwd-topic', 'sfwd-quiz' ), true ) ) {
					$instance['current_lesson_id'] = learndash_course_get_single_parent_step( $course_id, $post->ID, 'sfwd-lessons' );
				}
			}
		}

		// Output content.
		ob_start();

		if ( ! empty( $uo_course_navigation_heading ) ) {
			?>
			<div class="ultp-lazy-course-navigation__heading">
				<?php echo esc_html( $uo_course_navigation_heading ); ?>
			</div>
			<?php
		}

		// Always show quizzes at every level.
		$instance['show_course_quizzes'] = apply_filters( 'uo_ld_course_navigation_show_course_quizzes', true, $course_id, $instance );
		$instance['show_lesson_quizzes'] = apply_filters( 'uo_ld_course_navigation_show_lesson_quizzes', true, $course_id, $instance );
		$instance['show_topic_quizzes']  = apply_filters( 'uo_ld_course_navigation_show_topic_quizzes', true, $course_id, $instance );
		?>
		<div class="ultp-lazy-course-navigation__content">
			<?php learndash_course_navigation( $course_id, $instance, $lesson_query_args ); ?>
		</div>
		<?php

		$content = ob_get_clean();

		return array( 'html' => $content );

	}
}
