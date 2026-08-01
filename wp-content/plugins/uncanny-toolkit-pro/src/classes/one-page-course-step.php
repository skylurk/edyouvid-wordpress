<?php
/**
 * Class OnePageCourseStep
 *
 * Create independent course pages that don't require lessons or topics.
 *
 *
 * @since       2.1 Initial release
 * @subpackage  uncanny_pro_toolkit\OnePageCourseStep
 * @package     uncanny_learndash_toolkit
 */

namespace uncanny_pro_toolkit;

use uncanny_learndash_toolkit as toolkit;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class InitializePlugin
 * @package uncanny_pro_toolkit
 */
class OnePageCourseStep extends toolkit\Config implements toolkit\RequiredFunctions {

	/**
	 * Class constructor
	 *
	 * @since 3.6.0
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( __CLASS__, 'run_frontend_hooks' ) );
	}

	/*
	 * Initialize frontend actions and filters
	 *
	 * @since 3.6.0
	 */
	/**
	 * @return void
	 */
	public static function run_frontend_hooks() {

		if ( true === self::dependants_exist() ) {
			/* ADD FILTERS ACTIONS FUNCTION */
			if ( is_admin() ) {
				add_action( 'add_meta_boxes', array( __CLASS__, 'single_page_meta_boxes' ) );
			}
			add_action( 'save_post', array( __CLASS__, 'single_page_on_publish' ), 999, 2 );
			add_action( 'wp', array( __CLASS__, 'check_course_and_override' ), 999 );
			add_action( 'wp', array( __CLASS__, 'manual_complete_module' ), 999 );
			add_action( 'template_redirect', array( __CLASS__, 'dummy_lesson_redirect' ), 0 );
		}
	}

	/**
	 * Does the plugin rely on another function or plugin
	 *
	 * @return boolean || string Return either true or name of function or plugin
	 *
	 * @since           3.6.0
	 *
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
	 *
	 * @return array
	 * @since 3.6.0
	 *
	 */
	public static function get_details() {
		$module_id = 'single-page-courses';

		$class_title = esc_html__( 'Single Page Courses', 'uncanny-pro-toolkit' );

		$kb_link = 'https://www.uncannyowl.com/knowledge-base/single-page-courses/';

		/* Sample Simple Description with shortcode */
		$class_description = esc_html__( 'Create independent course pages that don\'t require lessons or topics.', 'uncanny-pro-toolkit' );

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
			//'settings'         => self::get_class_settings( $class_title ),
			'icon'             => $class_icon,
		);

	}

	/**
	 * HTML for modal to create settings
	 *
	 * @param String
	 *
	 * @return array || string Return either false or settings html modal
	 *
	 */
	public static function get_class_settings( $class_title ) {

		// Create options
		$options = array();

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
	 * Adds course meta box
	 *
	 * @access public
	 * @return void
	 */
	public static function single_page_meta_boxes() {
		add_meta_box(
			'uo_single_page_course_box_id',
			'Single Page Course',
			array( __CLASS__, 'single_page_meta_box_html' ),
			'sfwd-courses',
			'side',
			'high'
		);
	}

	/**
	 * Adds course meta box HTML
	 *
	 * @access public
	 * @return void
	 */
	public static function single_page_meta_box_html( $post ) {
		$uo_single_page_course  = get_post_meta( $post->ID, 'uo_single_page_course', true );
		$uo_autocomplete_course = get_post_meta( $post->ID, 'uo_autocomplete_course', true );

		// get course steps
		$courseSteps = new \LDLMS_Course_Steps( $post->ID );

		$courseSteps->load_steps();
		$course_step_count = $courseSteps->get_steps_count();
		$is_possible       = true;

		if ( (int) $course_step_count === (int) 1 ) {
			$lessons      = $courseSteps->get_steps( 't' );
			$lesson_id    = $lessons['sfwd-lessons'][0];
			$saved_lesson = get_post_meta( $post->ID, '_duumy_course_step', true );
			if ( (int) $saved_lesson !== (int) $lesson_id ) {
				delete_post_meta( $post->ID, 'uo_single_page_course' );
				// Then autocomplete will not work.
				delete_post_meta( $post->ID, 'uo_autocomplete_course' );
				$is_possible = false;
			}
		} elseif ( $course_step_count > (int) 1 ) {
			delete_post_meta( $post->ID, 'uo_single_page_course' );
			// Then autocomplete will not work.
			delete_post_meta( $post->ID, 'uo_autocomplete_course' );
			$is_possible = false;
		}
		if ( ! $is_possible ) {
			?>
			<p class="error"><?php _e( 'This course already has one or more lessons and cannot be turned into a Single Page Course. Existing lessons must be deleted or reassigned first.', 'uncanny-pro-toolkit' ); ?></p>
			<?php
		} else {
			?>
			<input type="hidden" name="uo_single_page_course_check" id="uo_single_page_course_check"
				   value="<?php echo wp_create_nonce( 'uo_single_page_course' ); ?>">
			<p><label><input type="checkbox" id="uo_single_page_course"
							 name="uo_single_page_course"
						<?php
						if ( ! empty( $uo_single_page_course ) ) {
							?>
							checked="checked"<?php } ?>
							 value="1"> <?php echo __( 'Single Page Course', 'uncanny-pro-toolkit' ); ?></label></p>
			<p><label><input
						type="checkbox"
						<?php
						if ( empty( $uo_single_page_course ) ) {
							?>
							disabled="disabled" <?php } ?>
						id="uo_autocomplete_course"
						name="uo_autocomplete_course"
						<?php
						if ( ! empty( $uo_autocomplete_course ) ) {
							?>
							checked="checked"<?php } ?>
						value="1"> <?php echo __( 'Autocomplete course when viewed', 'uncanny-pro-toolkit' ); ?></label>
			</p>
			<script>
				jQuery(document).ready(function () {
					jQuery('#uo_single_page_course').change(function () {
						//find only the paid in the same row as the selected checkbox
						jQuery('#uo_autocomplete_course').attr('disabled', !this.checked);
						if (!this.checked) {
							jQuery('#uo_autocomplete_course').removeAttr('checked');
						}
					});
				});
			</script>
			<?php
		}
	}

	/**
	 * Save checkbox from course meta and convert single page course.
	 *
	 * @access public
	 * @return void
	 */
	public static function single_page_on_publish( $post_id, $post ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return true;
		}

		if ( ! filter_has_var( INPUT_POST, 'action' ) ) {
			return false;
		}

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return true;
		}

		if ( empty( $post ) ) {
			return false;
		}

		if ( ! in_array( $post->post_type, array( 'sfwd-courses' ) ) ) {
			return false;
		}

		if ( $post->post_status !== 'publish' ) {
			//return FALSE;
		}

		if ( filter_has_var( INPUT_POST, 'uo_single_page_course_check' ) && wp_verify_nonce( filter_input( INPUT_POST, 'uo_single_page_course_check' ), 'uo_single_page_course' ) ) {

			if ( filter_has_var( INPUT_POST, 'uo_autocomplete_course' ) ) {
				update_post_meta( $post_id, 'uo_autocomplete_course', true );
			} else {
				delete_post_meta( $post_id, 'uo_autocomplete_course' );
			}

			if ( filter_has_var( INPUT_POST, 'uo_single_page_course' ) ) {

				update_post_meta( $post_id, 'uo_single_page_course', true );

				$single_page_tutorial = get_post_meta( $post_id, 'uo_single_page_course', true );

				if ( $single_page_tutorial ) {
					// get course steps
					$courseSteps = new \LDLMS_Course_Steps( $post_id );

					$courseSteps->load_steps();
					$course_step_count = $courseSteps->get_steps_count();
					if ( $course_step_count === 0 ) {
						$lesson_post = array(
							'post_title'        => $post->post_title . ' - Lesson - ' . $post->ID,
							'post_content'      => '',
							'post_modified'     => $post->post_modified,
							'post_modified_gmt' => $post->post_modified_gmt,
							'post_status'       => 'publish',
							'post_type'         => 'sfwd-lessons',
							'comment_status'    => 'closed',
							'ping_status'       => 'closed',
						);

						$new_lesson_id = wp_insert_post( $lesson_post, true );
						update_post_meta( $new_lesson_id, '_duumy_course_step', $post_id );
						update_post_meta( $post_id, '_duumy_course_step', $new_lesson_id );
						$h_course                 = array();
						$h_course['sfwd-lessons'] = array( $new_lesson_id => array() );
						$h_course['sfwd-quiz']    = array();
						$courseSteps->set_steps( $h_course );
						$course_meta = get_post_meta( $post_id, '_sfwd-courses', true );
						if ( ( ! $course_meta ) || ( ! is_array( $course_meta ) ) ) {
							$course_meta = array();
						}
						if ( ! isset( $course_meta['sfwd-courses_course_disable_content_table'] ) ) {
							$course_meta['sfwd-courses_course_disable_content_table'] = 'on';
						}
						update_post_meta( $post_id, '_sfwd-courses', $course_meta );
					} else {
						if ( $course_step_count === 1 ) {
							$lessons      = $courseSteps->get_steps( 't' );
							$lesson_id    = $lessons['sfwd-lessons'][0];
							$saved_lesson = get_post_meta( $post_id, '_duumy_course_step', true );
							if ( (int) $saved_lesson !== (int) $lesson_id ) {
								delete_post_meta( $post_id, 'uo_single_page_course' );
								// Then autocomplete will not work.
								delete_post_meta( $post_id, 'uo_autocomplete_course' );
							}
						} else {
							delete_post_meta( $post_id, 'uo_single_page_course' );
							// Then autocomplete will not work.
							delete_post_meta( $post_id, 'uo_autocomplete_course' );
						}
					}
				}
			} else {

				$single_page_tutorial = get_post_meta( $post_id, 'uo_single_page_course', true );

				if ( $single_page_tutorial ) {
					// get course steps
					$courseSteps = new \LDLMS_Course_Steps( $post_id );
					$courseSteps->load_steps();

					if ( $courseSteps->get_steps_count() > 0 ) {
						$lessons      = $courseSteps->get_steps( 't' );
						$lesson_id    = $lessons['sfwd-lessons'][0];
						$saved_lesson = get_post_meta( $post_id, '_duumy_course_step', true );
						if ( $saved_lesson == $lesson_id ) {
							wp_delete_post( $lesson_id, true );
							$h_course['sfwd-lessons'] = array();
							$h_course['sfwd-quiz']    = array();
							$courseSteps->set_steps( $h_course );
							$course_meta = get_post_meta( $post_id, '_sfwd-courses', true );
							if ( ( ! $course_meta ) || ( ! is_array( $course_meta ) ) ) {
								$course_meta = array();
							}
							if ( ! isset( $course_meta['sfwd-courses_course_disable_content_table'] ) ) {
								$course_meta['sfwd-courses_course_disable_content_table'] = 'off';
							}
							update_post_meta( $post_id, '_sfwd-courses', $course_meta );
						}
					}
				}
				delete_post_meta( $post_id, '_duumy_course_step' );
				delete_post_meta( $post_id, 'uo_single_page_course' );
				// Then autocomplete will not work.
				delete_post_meta( $post_id, 'uo_autocomplete_course' );
			}
		}
	}

	/**
	 * Check course settings and override
	 *
	 * @access public
	 * @return void
	 */
	public static function check_course_and_override() {
		global $post;
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		if ( 'sfwd-courses' !== $post->post_type ) {
			return;
		}

		$has_access = sfwd_lms_has_access( $post->ID );
		if ( ! $has_access ) {
			return;
		}

		if ( false === self::is_single_page_course( $post ) ) {
			return;
		}
		$course_meta = get_post_meta( $post->ID, '_sfwd-courses', true );
		if ( ( ! $course_meta ) || ( ! is_array( $course_meta ) ) ) {
			$course_meta = array();
		}
		$course_meta['sfwd-courses_course_disable_content_table'] = 'on';
		update_post_meta( $post->ID, '_sfwd-courses', $course_meta );

		add_filter(
			'body_class',
			function ( $classes ) {
				return array_merge( $classes, array( 'uo-single-page-course' ) );
			}
		);

		$autocomplete_course = get_post_meta( $post->ID, 'uo_autocomplete_course', true );

		add_action( 'learndash-course-after', array( __CLASS__, 'uo_mark_complete_form' ), 10, 3 );
		if ( $autocomplete_course ) {
			add_action( 'shutdown', array( __CLASS__, 'auto_complete_module' ), 10 );
		}

	}


	/**
	 * Handles auto completion when course option enabled
	 *
	 * @access public
	 * @return void
	 */
	public static function auto_complete_module() {
		global $post;
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		if ( 'sfwd-courses' !== $post->post_type ) {
			return;
		}

		$has_access = sfwd_lms_has_access( $post->ID );
		if ( ! $has_access ) {
			return;
		}

		if ( false === self::is_single_page_course( $post ) ) {
			return;
		}
		self::mark_course_steps_complete( $post->ID );
	}

	/**
	 * @param $post
	 *
	 * @return bool
	 */
	public static function is_single_page_course( $post ) {
		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		if ( 'sfwd-courses' !== $post->post_type ) {
			return false;
		}

		if ( empty( get_post_meta( $post->ID, 'uo_single_page_course', true ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Adds mark complete button form
	 *
	 * @access public
	 * @return void
	 */
	public static function uo_mark_complete_form( $post_id, $course_id, $user_id ) {
		global $post;
		$single_page_tutorial = get_post_meta( $post->ID, 'uo_single_page_course', true );
		if ( $single_page_tutorial ) {

			$autocomplete_course = get_post_meta( $post->ID, 'uo_autocomplete_course', true );
			$has_access          = sfwd_lms_has_access( $post->ID );
			?>
			<style>
				.ld-item-list.ld-lesson-list {
					display: none !important;
				}
			</style>
			<?php
			if ( $has_access && ! $autocomplete_course ) :
				if ( ! learndash_course_completed( get_current_user_id(), $post->ID ) ) :

					if( function_exists( 'learndash_is_course_prerequities_completed' ) ) {
						if( false === learndash_is_course_prerequities_completed( $course_id, get_current_user_id() ) ) {
							return; // course prerequities are not completed. Bailout.
						}
					}

					?>

					<form id="sfwd-mark-complete" method="post" action="">
						<input type="hidden" value="<?php echo $post->ID; ?>" name="post"/>
						<input type="hidden"
							   value="<?php echo wp_create_nonce( 'sfwd_mark_complete_' . get_current_user_id() . '_' . $post->ID ); ?>"
							   name="pec_sfwd_mark_complete"/>
						<input type="submit"
							   value="<?php echo esc_html( \LearnDash_Custom_Label::get_label( 'button_mark_complete' ) ); ?>"
							   id="learndash_mark_complete_button"/>
					</form>

				<?php
				endif;
			endif;
		}
	}

	/**
	 * Handles manual completion process
	 *
	 * @access public
	 * @return void
	 */
	public static function manual_complete_module() {
		if ( ! is_user_logged_in() ) {
			return;
		}
		if ( ! filter_has_var( INPUT_POST, 'pec_sfwd_mark_complete' ) || empty( filter_input( INPUT_POST, 'pec_sfwd_mark_complete' ) ) ) {
			return;
		}
		if ( ! filter_has_var( INPUT_POST, 'post' ) || empty( filter_input( INPUT_POST, 'post' ) ) ) {
			return;
		}

		$userid  = get_current_user_id();
		$post_id = absint( filter_input( INPUT_POST, 'post' ) );

		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		if ( 'sfwd-courses' !== $post->post_type ) {
			return;
		}

		if ( ! wp_verify_nonce( filter_input( INPUT_POST, 'pec_sfwd_mark_complete' ), 'sfwd_mark_complete_' . $userid . '_' . $post_id ) ) {
			return;
		}

		$has_access = sfwd_lms_has_access( $post->ID );
		if ( ! $has_access ) {
			return;
		}

		if ( false === self::is_single_page_course( $post ) ) {
			return;
		}

		self::mark_course_steps_complete( $post->ID );
	}

	/**
	 * @param $course_id
	 *
	 * @return void
	 */
	private static function mark_course_steps_complete( $course_id ) {
		$user_id = wp_get_current_user()->ID;
		if ( learndash_course_completed( $user_id, $course_id ) ) {
			return;
		}

		if( function_exists( 'learndash_is_course_prerequities_completed' ) ) {
			if( false === learndash_is_course_prerequities_completed( $course_id, $user_id ) ) {
				return; // course prerequities are not completed. Bailout.
			}
		}

		$lessons = learndash_get_course_lessons_list( $course_id );
		if ( empty( $lessons ) ) {
			return;
		}

		// Marking each lesson complete
		foreach ( $lessons as $lesson ) {
			learndash_process_mark_complete( $user_id, $lesson['post']->ID, false, $course_id );
		}

		// Mark the course complete itself, fallback
		learndash_process_mark_complete( $user_id, $course_id );
	}

	/**
	 * Handles dummy lesson redirect
	 *
	 * @access public
	 * @return void
	 */

	public static function dummy_lesson_redirect() {

		// restrictions do not apply to the search query or archives
		if ( is_search() || ! is_singular() ) {
			return;
		}

		// Single post redirects
		global $post;

		if ( empty( $post ) ) {
			return;
		}

		if ( $post->ID == 0 ) {
			return;
		}

		if ( 'sfwd-lessons' !== $post->post_type ) {
			return;
		}

		$is_dummy_lesson = get_post_meta( $post->ID, '_duumy_course_step', true );

		if ( $is_dummy_lesson ) {
			$redirect_post = get_post( $is_dummy_lesson );
			if ( ! empty( $redirect_post ) && 'publish' === $redirect_post->post_status ) {
				$redirect = get_permalink( $redirect_post->ID );
				wp_redirect( $redirect );
				exit();
			}
		}
	}

	/**
	 * Show error if someone try to convert existing course in one page
	 *
	 * @access public
	 * @return void
	 */
	public static function error_admin_notice() {
		?>
		<div class="error">
			<p><?php _e( 'This course already has one or more lessons and cannot be turned into a Single Page Course. Existing lessons must be deleted or reassigned first.', 'uncanny-pro-toolkit' ); ?></p>
		</div>
		<?php
	}
}
