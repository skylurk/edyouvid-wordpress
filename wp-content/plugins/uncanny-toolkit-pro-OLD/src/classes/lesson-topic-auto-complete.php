<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Contains Lesson/Topic Autocompletion Module
 *
 * @package     uncanny_pro_toolkit
 */

namespace uncanny_pro_toolkit;

use uncanny_learndash_toolkit as toolkit;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Handles Lesson/Topic Autocompletion
 *
 * @since       1.0.0
 */
class LessonTopicAutoComplete extends toolkit\Config implements toolkit\RequiredFunctions {

	/**
	 * Post Types that can be autocommpleted
	 *
	 * @var array
	 */
	public static $auto_completed_post_types = array( 'sfwd-lessons', 'sfwd-topic' );

	/**
	 * Keys for the settings metaboxes
	 *
	 * @var array
	 */
	public static $settings_metabox_key = array(
		'learndash-lesson-display-content-settings',
		'learndash-topic-display-content-settings',
	);

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( __CLASS__, 'run_frontend_hooks' ) );
	}

	/**
	 * Initialize frontend actions and filters
	 */
	public static function run_frontend_hooks() {

		// bail, if dependents don't exist.
		if ( ! self::dependants_exist() ) {
			return;
		}

		/*
		 * LD 2.3 removed the next if until mark complete is clicked, since we removed the mark complete button
		 * there is no way to progress. LD allows to added the next to be added back in version 2.3.0.2. Let's use it!
		 */
		add_filter( 'learndash_show_next_link', array( __CLASS__, 'learndash_show_next_link_progression' ), 10, 3 );

		// Remove html output of mark complete button.
		add_filter( 'learndash_mark_complete', array( __CLASS__, 'remove_mark_complete_button' ), 99, 2 );

		// autocomplete through ajax when lesson/topic timer is complete.
		add_action( 'wp_ajax_uc_toolkit_auto_complete', array( __CLASS__, 'timer_auto_complete' ) );

		// 3.0+ - Save custom lesson settings field.
		add_filter( 'learndash_metabox_save_fields', array( __CLASS__, 'save_lesson_custom_meta' ), 60, 3 );

		// 3.0+  - Add auto complete setting to LearnDash Lessons (auto creates field and loads value)
		add_filter( 'learndash_settings_fields', array( __CLASS__, 'add_auto_complete_to_post_args' ), 10, 2 ); // 3.0+

		// 3.0+ - Save custom lesson settings field
		add_filter( 'learndash_metabox_save_fields', array( __CLASS__, 'save_lesson_custom_meta' ), 60, 3 );

	}

	/**
	 * Does the plugin rely on another function or plugin.
	 *
	 * @return bool|string Return either true or name of function or plugin.
	 */
	public static function dependants_exist() {

		// Checks for LearnDash.
		global $learndash_post_types;
		if ( ! isset( $learndash_post_types ) ) {
			return 'Plugin: LearnDash';
		}

		// Return true if no dependency or dependency is available.
		return true;

	}

	/**
	 * Save post meta when a post is saved.
	 *
	 * @param array  $settings_field_updates The array of updates.
	 * @param string $settings_metabox_key The metabox key.
	 * @param string $settings_screen_id The ID of the screen.
	 *
	 * @return array
	 */
	public static function save_lesson_custom_meta( $settings_field_updates, $settings_metabox_key, $settings_screen_id ) {

		global $post;

		if ( in_array( $settings_metabox_key, self::$settings_metabox_key ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
			// Update the post's metadata. Nonce already verified by LearnDash.
			if (
				filter_has_var( INPUT_POST, 'learndash-lesson-display-content-settings' ) && // phpcs:ignore WordPress.Security.NonceVerification.Missing
				isset( $_POST['learndash-lesson-display-content-settings']['uo_auto_complete'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			) {
				$auto_complete_setting_value = sanitize_text_field( $_POST['learndash-lesson-display-content-settings']['uo_auto_complete'] ); // phpcs:ignore WordPress.Security
				learndash_update_setting( $post, 'uo_auto_complete', $auto_complete_setting_value );
			}

			if (
				filter_has_var( INPUT_POST, 'learndash-topic-display-content-settings' ) && // phpcs:ignore WordPress.Security.NonceVerification.Missing
				isset( $_POST['learndash-topic-display-content-settings']['uo_auto_complete'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			) {
				$auto_complete_setting_value = sanitize_text_field( $_POST['learndash-topic-display-content-settings']['uo_auto_complete'] ); // phpcs:ignore WordPress.Security
				learndash_update_setting( $post, 'uo_auto_complete', $auto_complete_setting_value );
			}
		}

		return $settings_field_updates;
	}

	/**
	 * Add settings to Lessons and Topics settings tab.
	 *
	 * @param array  $setting_option_fields The option fields.
	 * @param string $settings_metabox_key Metabox key.
	 *
	 * @return mixed
	 */
	public static function add_auto_complete_to_post_args( $setting_option_fields, $settings_metabox_key ) {

		if ( in_array( $settings_metabox_key, self::$settings_metabox_key ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict

			global $post;
			$learndash_post_settings = learndash_get_setting( $post, null );

			$value = '';
			if ( isset( $learndash_post_settings['uo_auto_complete'] ) ) {
				if ( ! empty( $learndash_post_settings['uo_auto_complete'] ) ) {
					$value = $learndash_post_settings['uo_auto_complete'];
				}
			}

			$setting_option_fields['uo_auto_complete'] = array(
				'name'      => 'uo_auto_complete',
				'label'     => esc_attr__( 'Auto Complete', 'uncanny-pro-toolkit' ),
				'type'      => 'select',
				'help_text' => esc_attr__( 'Automatically complete lesson or topic on page visit', 'uncanny-pro-toolkit' ),
				'options'   => array(
					'use_globals' => 'Use Global Settings',
					'disabled'    => 'Disabled',
					'enabled'     => 'Enabled',
				),
				'default'   => 'use_globals',
				'value'     => $value,
			);
		}

		return $setting_option_fields;

	}

	/**
	 * Description of class in Admin View
	 *
	 * @return array
	 */
	public static function get_details() {
		$module_id = 'autocomplete-lessons-topics';

		$class_title = esc_html__( 'Autocomplete Lessons & Topics', 'uncanny-pro-toolkit' );

		$kb_link = 'http://www.uncannyowl.com/knowledge-base/autocomplete-lessons-topics/';

		/* Sample Simple Description with shortcode */
		$class_description = esc_html__( 'Automatically mark all lessons and topics as completed on user visit and remove Mark Complete buttons. Global settings can be overridden for individual lessons and topics.', 'uncanny-pro-toolkit' );

		/* Icon as fontawesome icon */
		$class_icon = '<i class="uo_icon_pro_fa uo_icon_fa fa fa-book "></i><span class="uo_pro_text">PRO</span>';
		$category   = 'learndash';
		$type       = 'pro';

		return array(
			'id'               => $module_id,
			'title'            => $class_title,
			'type'             => $type,
			'category'         => $category,
			'kb_link'          => $kb_link, // OR set as null not to display.
			'description'      => $class_description,
			'dependants_exist' => self::dependants_exist(),
			'settings'         => self::get_class_settings( $class_title ),
			'icon'             => $class_icon,
		);

	}

	/**
	 * HTML for modal to create settings
	 *
	 * @static
	 *
	 * @param string $class_title The title of the class.
	 *
	 * @return HTML
	 */
	public static function get_class_settings( $class_title ) {

		// Get pages to populate drop down.
		$args = array(
			'sort_order'  => 'asc',
			'sort_column' => 'post_title',
			'post_type'   => 'page',
			'post_status' => 'publish',
		);

		$pages     = get_pages( $args );
		$drop_down = array();
		array_push(
			$drop_down,
			array(
				'value' => '',
				'text'  => esc_attr__( 'Select a Page', 'uncanny-pro-toolkit' ),
			)
		);

		foreach ( $pages as $page ) {
			if ( empty( $page->post_title ) ) {
				$page->post_title = esc_attr__( '(no title)', 'uncanny-pro-toolkit' );
			}

			array_push(
				$drop_down,
				array(
					'value' => $page->ID,
					'text'  => $page->post_title,
				)
			);
		}

		// Create options.
		$options = array(
			array(
				'type'       => 'radio',
				'label'      => 'Global Settings',
				'radio_name' => 'uo_global_auto_complete',
				'radios'     => array(
					array(
						'value' => 'auto_complete_all',
						'text'  => 'Enable auto-completion for all lessons and topics **<br>',
					),
					array(
						'value' => 'auto_complete_only_lesson_topics_set',
						'text'  => 'Disable autocompletion for all lessons and topics **',
					),
				),
			),
			array(
				'type'       => 'html',
				'class'      => 'uo-additional-information',
				'inner_html' => '<div>' . esc_attr__( '** This global setting can be overridden for individual lessons and topics in the Edit page of the associated lesson or topic.', 'uncanny-pro-toolkit' ) . '</div>',
			),
		);

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
	 * Filter HTML output to mark course complete button
	 *
	 * @param string $button_markup The original button markup.
	 * @param object $post          The current post object.
	 *
	 * @return string
	 */
	public static function remove_mark_complete_button( $button_markup, $post ) {

		// bail, if this is not auto-completable.
		if ( ! in_array( $post->post_type, self::$auto_completed_post_types, true ) ) {
			return $button_markup;
		}

		// bail, if it is already complete.
		if ( ! self::maybe_complete( $post ) ) {
			return $button_markup;
		}

		// bail, if gravity forms autocomplete module is supposed to handle this instead.
		if ( self::is_gravity_forms_taking_over( $post ) ) {
			return $button_markup;
		}

		$forced_time = learndash_forced_lesson_time( $post );

		$cookie_key = learndash_forced_lesson_time_cookie_key( $post );

		$elapsed_time = filter_input( INPUT_COOKIE, "learndash_timer_cookie_{$cookie_key}", FILTER_VALIDATE_INT );

		$display_timer = false;

		// no cookie was set or cookie already has some time recorded (timer was started but not completed).
		if ( ( is_null( $elapsed_time ) || $elapsed_time > 0 ) && ! empty( $forced_time ) ) {
			$display_timer = self::timer_element( $post, $forced_time );
		}

		if ( ! empty( $display_timer ) ) {
			return $display_timer;
		}

		// otherwise, if there is an elapsed time, it is 0 (already spent), so we can autocomplete.

		// Auto Complete LearnDash Module learndash_after everything gets loaded
		// Calling at 'shutdown' because we want to give the user at least a second to navigate through.
		add_action( 'shutdown', array( __CLASS__, 'auto_complete_module' ), 10 );

		// and remove everything.
		return '';

	}

	/**
	 * Checks if a step is supposed to be handled by the Gravity Forms autocomplete module.
	 *
	 * @param WP_Post $post The post object of the step.
	 *
	 * @return boolean
	 */
	public static function is_gravity_forms_taking_over( $post ) {

		$uncanny_active_classes = get_option( 'uncanny_toolkit_active_classes', '' );

		// Bail if gravity form autocomplete module is not on.
		if ( ! key_exists( 'uncanny_pro_toolkit\GfLessonTopicAutoComplete', $uncanny_active_classes ) ) {
			return false;
		}

		// otherwise, gravity forms module is on. Check if this step contains a form.

		// if there's a form shortcode.
		if ( has_shortcode( $post->post_content, 'gravityform' ) ) {
			return true;
		}

		// if there's a form block.
		if ( function_exists( 'has_blocks' ) ) {
			if ( has_blocks( $post->post_content ) ) {
				$blocks = parse_blocks( $post->post_content );
				foreach ( $blocks as $block ) {
					if ( 'gravityforms/block' === $block['blockName'] || 'gravityforms/form' === $block['blockName'] ) {
						$form_id = $block['attrs']['formId'];
						break;
					}
				}
				if ( isset( $form_id ) ) {
					return true;
				}
			}
		}

		// no shortcode or block, so no form!
		return false;

	}

	/**
	 * Returns a timer element
	 *
	 * @param WP_Post $post   Lesson or Topic post object.
	 * @param string  $timeval Timer value set up for lesson/topic.
	 *
	 * @return string
	 */
	public static function timer_element( $post, $timeval ) {

		$time = 0;

		if ( ! empty( $timeval ) ) {
			$time = learndash_convert_lesson_time_time( $timeval );
		}

		// the following is mostly copied over from LD's UI.

		// no  timer for admins.
		if ( self::can_user_bypass( $post ) ) {
			return '';
		}

		// return nothing if timer is empty.
		if ( empty( $time ) ) {
			return '';
		}

		$time_cookie_key = learndash_forced_lesson_time_cookie_key( $post );
		$course_id       = learndash_get_course_id( $post );
		wp_enqueue_script(
			'jquery-cookie',
			plugins_url( 'js/jquery.cookie' . learndash_min_asset() . '.js', WPPROQUIZ_FILE ),
			array( 'jquery' ),
			'1.4.0',
			true
		);

		global $learndash_assets_loaded;
		$learndash_assets_loaded['scripts']['jquery-cookie'] = 'learndash_mark_complete';

		$asset_url = plugins_url( 'src/assets/legacy/frontend/js/learndash_template_script.js', UO_FILE );

		if ( isset( $learndash_assets_loaded['scripts']['learndash_template_script_js'] ) ) {
			wp_dequeue_script( 'learndash_template_script_js' );

			wp_enqueue_script( 'learndash_template_script_js_2', $asset_url, array( 'jquery' ), LEARNDASH_VERSION . '.01', true );
			$data            = array();
			$data['ajaxurl'] = admin_url( 'admin-ajax.php' );
			wp_localize_script( 'learndash_template_script_js_2', 'sfwd_data', $data );
		}

		$label = __( 'Completing in', 'uncanny-pro-toolkit' );

		$wait_label = __( 'Please wait...', 'uncanny-pro-toolkit' );

		return '<span class="uc_ld_timer" data-timer-seconds="' . $time . '" data-cookie-key="' . $time_cookie_key . '" data-post-id="' . $post->ID . '" data-uc-ac-nonce="' . wp_create_nonce( 'uc-ac-nonce' ) . '" data-course-id="' . $course_id . '">
			<span class="uc_ld_timer_label" data-wait-complete-label="' . $wait_label . '">' . $label . '</span>&nbsp;<span class="uc_ld_timer_time"></span>
		</span>';

	}

	/**
	 * Checks if the current user can bypass timer.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return boolean
	 */
	public static function can_user_bypass( $post ) {

		$user_id = get_current_user_id();

		// a non-admin user can't bypass.
		if ( ! learndash_is_admin_user( $user_id ) ) {
			return false;
		}

		// LD >= 3.1.7 has an API function for this.
		if ( function_exists( 'learndash_can_user_bypass' ) ) {
			return learndash_can_user_bypass( $user_id, 'learndash_course_progression', $post->ID, $post );
		}

		// For LD < 3.1.7, we have to replicate the API funnction above.
		if ( \LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'bypass_course_limits_admin_users' ) ) {
			return true;
		}

		// otherwise, they can't bypass.
		return false;
	}

	/**
	 * Handles timer based completion
	 *
	 * @return void
	 */
	public static function timer_auto_complete() {

		$post_id = filter_input( INPUT_POST, 'postID', FILTER_VALIDATE_INT );

		// 404, if no post ID.
		if ( ! $post_id ) {
			wp_send_json_error( 'No post ID', '404' );
		}

		$nonce = filter_input( INPUT_POST, 'ucAcNonce', FILTER_DEFAULT );

		// nonce failure, unauthorized.
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'uc-ac-nonce' ) ) {
			wp_send_json_error( 'Nonce failure', '403' );
		}

		$course_id = filter_input( INPUT_POST, 'courseID', FILTER_VALIDATE_INT );

		// 404, if no post ID.
		if ( ! $course_id ) {
			wp_send_json_error( 'No course ID', '404' );
		}

		// everything is find, proceed with autocomplete.
		$post = get_post( $post_id );

		$is_completed = self::auto_complete_module( $post, $course_id );

		if ( $is_completed ) {
			$data = null;
			if ( is_string( $is_completed ) ) {
				$data = array(
					'buttonLink' => $is_completed,
				);
			}
			wp_send_json_success( $data );
		} else {
			wp_send_json_error( 'not completed' );
		}
	}

	/**
	 * Filter to bring back next navigation links
	 *
	 * @param  WP_Post $post_object The post object.
	 *
	 * @return bool
	 */
	public static function maybe_complete( $post_object ) {

		$maybe_complete = true;
		$lesson_id      = learndash_get_lesson_id( $post_object->ID );

		// Checking if lesson is available to be marked complete?
		$uncanny_active_classes = get_option( 'uncanny_toolkit_active_classes', '' );

		if ( ! empty( $uncanny_active_classes ) ) {
			if ( key_exists( 'uncanny_pro_toolkit\UncannyDripLessonsByGroup', $uncanny_active_classes ) ) {
				$lesson_access_from = UncannyDripLessonsByGroup::get_lesson_access_from( $lesson_id, wp_get_current_user()->ID );
				if ( __( 'Available', 'uncanny-pro-toolkit' ) === (string) $lesson_access_from ) {
					$lesson_access_from = false;
				}

				if ( ! empty( $lesson_access_from ) ) {
					$maybe_complete = false;

					return $maybe_complete;
				}
			}
		}

		$feature_auto_complete_default = self::get_settings_value( 'uo_global_auto_complete', 'uncanny_pro_toolkitLessonTopicAutoComplete' );
		$post_options_auto_complete    = (array) learndash_get_setting( $post_object );

		// Is this lesson using auto-complete?
		if ( isset( $post_options_auto_complete['uo_auto_complete'] ) ) {

			if ( 'disabled' === $post_options_auto_complete['uo_auto_complete'] ) {
				$maybe_complete = false;
			}

			if ( 'use_globals' === $post_options_auto_complete['uo_auto_complete'] && 'auto_complete_only_lesson_topics_set' === $feature_auto_complete_default ) {
				$maybe_complete = false;
			}
		}

		// Is the lesson topic auto-complete not set?
		if ( ! isset( $post_options_auto_complete['uo_auto_complete'] ) ) {
			if ( 'auto_complete_only_lesson_topics_set' === $feature_auto_complete_default ) {
				$maybe_complete = false;
			}
		}

		// Check if assignment is turned on.
		$maybe_complete = self::is_linked_with_assignment( $post_object, $maybe_complete );

		return $maybe_complete;
	}

	/**
	 * Checks if a step has an assignment.
	 *
	 * @param WP_Post $post           Current step's post object.
	 * @param bool    $maybe_complete Whether to complete the step.
	 *
	 * @return bool
	 */
	public static function is_linked_with_assignment( $post, $maybe_complete ) {

		// Check if assignment is turned on.
		$assignments_exist_fn = function_exists( 'learndash_lesson_hasassignments' ) ? 'learndash_lesson_hasassignments' : 'lesson_hasassignments';
		if ( $assignments_exist_fn( $post ) ) {
			$post_options_auto_complete = (array) learndash_get_setting( $post );
			if ( key_exists( 'auto_approve_assignment', $post_options_auto_complete ) && 'on' === $post_options_auto_complete['auto_approve_assignment'] ) {
				$maybe_complete = true;
			} else {
				$maybe_complete = false;
			}
		}

		return $maybe_complete;

	}

	/**
	 * Displays next link progression.
	 *
	 * @param bool $show_next_link Whether the next link should be displayed.
	 * @param int  $user_id The User ID.
	 * @param int  $post_id The step's Post ID.
	 */
	public static function learndash_show_next_link_progression( $show_next_link = false, $user_id = 0, $post_id = 0 ) {
		$post = get_post( $post_id );

		if ( 'sfwd-lessons' === $post->post_type ) {
			$progress = learndash_get_course_progress( null, $post->ID );

			if ( ! empty( $progress['prev'] ) && empty( $progress['prev']->completed ) && learndash_lesson_progression_enabled() ) {
				return false;
			}
		}

		if ( 'sfwd-topic' === $post->post_type ) {
			$progress = learndash_get_course_progress( null, $post->ID );

			if ( ! empty( $progress['prev'] ) && empty( $progress['prev']->completed ) && learndash_lesson_progression_enabled() ) {
				if ( ! apply_filters( 'learndash_previous_step_completed', false, $progress['prev']->ID, $user_id ) ) {
					return false;
				}
			}
		}

		// If the module is enabled, but autocomplete is disabled globally (or on an individual lesson settings page), Revert to LearnDash navigation? 
		$feature_auto_complete_default = self::get_settings_value( 'uo_global_auto_complete', 'uncanny_pro_toolkitLessonTopicAutoComplete' );
		$revert_next_button = false;
		if( 'auto_complete_only_lesson_topics_set' === $feature_auto_complete_default ) {
			$revert_next_button = true; // Auto complete is disabled globally.
		}
		
		$post_options_auto_complete    = (array) learndash_get_setting( $post );
		
		if ( isset( $post_options_auto_complete['uo_auto_complete'] ) ) {
			if ( 'disabled' === $post_options_auto_complete['uo_auto_complete'] ) { // Is Auto complete disable for the post?
				$revert_next_button = true;
			}
		}

		if( $revert_next_button ) {
			return $show_next_link; // return the LD default value.
		}

		return true;
	}


	/**
	 * Only complete if the Pro Auto complete for lesson/Topic is on
	 *
	 * @param WP_Post $post custom post type lesson object.
	 * @param int     $course_id The Course ID.
	 *
	 * @return bool $maybe_complete
	 */
	public static function auto_complete_module( $post = null, $course_id = null ) {

		// don't affect REST API requests.
		if ( defined( 'REST_REQUEST' ) && true === REST_REQUEST ) {
			return false;
		}
		// Beaver-Builder-conflict-ticket-17372.
		if ( filter_has_var( INPUT_GET, 'fl_builder' ) || isset( $_REQUEST['fl_builder'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return false;
		}

		$user_ID = get_current_user_id();

		// no user, bail.
		if ( ! $user_ID ) {
			return false;
		}

		if ( empty( $post ) ) {
			global $post;
		}

		// no post object, or on admin, bail.
		if ( empty( $post ) ) {
			return false;
		}

		// bail, if this post type is not supposed to be autocompleted.
		if ( ! in_array( $post->post_type, self::$auto_completed_post_types, true ) ) {
			return false;
		}

		// do not autocomplete it this is restricted.
		if ( self::is_restricted() ) {
			return false;
		}

		if ( empty( $course_id ) ) {
			// get the course ID.
			$course_id = learndash_get_course_id( $post );
		}

		// if user doesn't have access to the course, bail.
		if ( ! sfwd_lms_has_access( $course_id ) ) {
			return false;
		}

		// is lesson progression enabled?
		$lesson_progression_enabled = learndash_lesson_progression_enabled();

		$is_previous_completed   = function_exists( 'learndash_is_previous_complete' ) ? 'learndash_is_previous_complete' : 'is_previous_complete';
		$previous_step_completed = $is_previous_completed( $post );

		// if lesson progression is enabled and previous step is not complete, we can't complete things.
		if ( $lesson_progression_enabled && ! $previous_step_completed ) {
			return false;
		}

		// is this restricted by drip?
		$lesson_access_from = self::is_restricted_by_drip( $post->ID, $user_ID );

		// Bail, if lesson access is restricted by date.
		if ( $lesson_access_from ) {
			return false;
		}

		/* Set up information */
		$lesson_id = 0;
		$post_ID   = $post->ID;
		$post_type = $post->post_type;

		// when the post is a lesson, lesson ID is post ID.
		if ( 'sfwd-lessons' === (string) $post->post_type ) {
			$lesson_id = $post->ID;
		}

		if ( in_array( $post->post_type, array( 'sfwd-topic', 'sfwd-quiz' ), true ) ) {
			if ( 'yes' === \LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) ) {
				$course_id = learndash_get_course_id( $post );
				$lesson_id = learndash_course_get_single_parent_step( $course_id, $post->ID );
			} else {
				$lesson_id = learndash_get_setting( $post, 'lesson' );
			}
		}

		$is_complete = self::is_already_complete( $post, $user_ID, $course_id );

		// Already completed, bail.
		if ( $is_complete ) {
			return false;
		}

		// something's wrong if we still don't have a lesson id, bail.
		if ( ! $lesson_id ) {
			return false;
		}

		$next_post_link = learndash_next_post_link();
		$is_last_step   = empty( $next_post_link );
		$button_link    = '';

		// there is no next post link!
		if ( $is_last_step ) {

			// this is the last topic in the lesson, get the lesson's link.
			if ( 'sfwd-topic' === (string) $post->post_type ) {
				$lesson = get_post( $lesson_id );

				// are all quizzes complete?
				$lesson_quizzes_completed = self::are_quizzes_complete( $lesson, $user_ID, $course_id );

				// are all assignments approved?
				$lesson_assignments_approved = self::are_assignments_approved( $lesson, $user_ID, $course_id );

				if ( $lesson_quizzes_completed && $lesson_assignments_approved ) {
					// if everything is fine, progress to the next lesson.
					$button_link = learndash_next_post_link( '', true, $lesson );
				}

				// otherwise, go back to the lesson.
				if ( empty( $button_link ) ) {
					$button_link = get_permalink( $lesson_id );
				}
			} else {
				// this is the last lesson in the course, get the global quiz/course's link.
				$button_link = learndash_next_global_quiz( true, null, $course_id );
			}
		}

		// are all quizzes complete?
		$all_quizzes_completed = self::are_quizzes_complete( $post, $user_ID, $course_id );

		// quizzes for this step are not done, and this is not the last step.
		if ( ! $all_quizzes_completed ) {
			return false;
		}

		// are all assignments approved?
		$all_assignments_approved = self::are_assignments_approved( $post, $user_ID, $course_id );

		// no approved assignments and this is not the last step.
		if ( ! $all_assignments_approved ) {
			return false;
		}

		// we don't return if there's no next link because we need to create a replacement button.

		// handle lessons. only mark complete if the lessons isn't already complete.
		if ( 'sfwd-lessons' === $post_type ) {

			$topics_completed = learndash_lesson_topics_completed( $lesson_id, false );

			// topics for this lesson are not done.
			if ( ! $topics_completed ) {
				return false;
			}

			// all set, complete the lesson!
			learndash_process_mark_complete( $user_ID, $post_ID, false, $course_id );
		}

		// Mark topic complete.
		if ( 'sfwd-topic' === $post_type ) {

			// both quizzes and assignments are complete, complete the topic.
			learndash_process_mark_complete( $user_ID, $post_ID, false, $course_id );

			// check if this has completed the lesson.
			$topics_completed = learndash_lesson_topics_completed( $lesson_id, false );

			// if there are still topics left or if this is not the last step, we're done.
			if ( ! $topics_completed ) {
				return false;
			} else {

				if( ! isset($lesson) ) {
					$lesson = get_post( $lesson_id );
				}

				// try to autocomplete the lesson.
				self::auto_complete_module( $lesson, $course_id );
			}
		}

		// if this isn't the last step, we're done here!
		if ( ! $is_last_step ) {
			return false;
		}

		// at this point, it's fair to check if the course itself has been completed.
		$completed = learndash_course_completed( $user_ID, $course_id );

		// if the course is complete, or there's no button link or the previous step is not completed.
		if ( $completed || '' === $button_link || ! $previous_step_completed ) {

			// Set the button link to the course completion URL, if applicable. Otherwise, fallback to the course page.
			$button_link = learndash_course_get_completion_url( $course_id );
		}

		// this isn't an ajax call.
		if ( ! wp_doing_ajax() ) {

			$button_link = apply_filters( 'uo_done_button_redirect_link', $button_link, $user_ID, $course_id, $lesson_id );

			// display the button on the last step.
			self::done_button( $button_link );
		} else {

			return $button_link;
		}
	}

	/**
	 * Checks if the step is restricted by the restrict page access module.
	 *
	 * @return boolean
	 */
	public static function is_restricted() {

		// get other active modules.
		$classes = self::get_active_classes();

		// Restrict page access isn't active, no restrictions!
		if ( ! in_array( 'uncanny_pro_toolkit\RestrictPageAccess', $classes, true ) || ! class_exists( '\uncanny_pro_toolkit\RestrictPageAccess' ) ) {
			return false;
		}

		// module is active, check if page is restricted.
		$page_restricted = \uncanny_pro_toolkit\RestrictPageAccess::$page_restricted;
		if ( ! $page_restricted ) {
			return false;
		}

		return true;
	}

	/**
	 * Checks if the step is already complete.
	 *
	 * @param \WP_Post $post The step's WP Post object.
	 * @param int      $user_id The ID of the user.
	 * @param int      $course_id Course ID.
	 *
	 * @return boolean
	 */
	public static function is_already_complete( \WP_Post $post, $user_id, $course_id ) {

		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		// get function name fragment from the step's post type.
		$type = str_replace( 'sfwd-', '', $post->post_type );

		// if it isn't a lesson or topic, it is incomplete.
		if ( ! in_array( $type, array( 'lessons', 'topic' ), true ) ) {
			return false;
		}

		if ( 'lessons' === $type ) {
			$type = 'lesson';
		}

		// prepare the name of completion function.
		$completion_function = "learndash_is_{$type}_complete";

		if ( ! function_exists( $completion_function ) ) {
			return false;
		}

		// return the result of the function call.
		return $completion_function( $user_id, $post->ID, $course_id );
	}

	/**
	 * Checks if the step is restricted by a drip.
	 *
	 * @param int $lesson_id Lesson ID.
	 * @param int $user_ID User ID.
	 *
	 * @return boolean
	 */
	public static function is_restricted_by_drip( $lesson_id, $user_ID ) {

		// Make sure the module is available.
		$active_classes = get_option( 'uncanny_toolkit_active_classes', 0 );

		// group drip isn't active, bail!
		if ( 0 === $active_classes ) {
			return false;
		}

		if ( ! is_array( $active_classes ) || isset( $active_classes['uncanny_pro_toolkit\UncannyDripLessonsByGroup'] ) ) {
			return false;
		}

		if ( ! class_exists( 'uncanny_pro_toolkit\UncannyDripLessonsByGroup' ) ) {
			return false;
		}

		// check if an access date is set up because of drip.
		$lesson_access_from = UncannyDripLessonsByGroup::get_lesson_access_from( $lesson_id, $user_ID );

		// if step is available, drip is off.
		if ( __( 'Available', 'uncanny-pro-toolkit' ) === (string) $lesson_access_from || empty( $lesson_access_from ) ) {
			$lesson_access_from = false;
		} elseif ( $lesson_access_from > time() ) { // otherwise, if lesson is going to be available in future, drip is active.
			$lesson_access_from = true;
		}

		// if group drip is off.
		if ( ! $lesson_access_from ) {
			// also check presence of core LearnDash drip.
			$ld_access_from = ld_lesson_access_from( $lesson_id, $user_ID );

			// if step is going to be available in some time, drip is on.
			if ( self::is_timestamp( $ld_access_from ) ) {
				$lesson_access_from = true;
			}
		}

		return $lesson_access_from;
	}

	/**
	 * Checks if all the quizzes for a step are complete.
	 *
	 * @param \WP_Post $post The step's post object.
	 * @param integer  $user_id The user's ID.
	 * @param integer  $course_id The course ID.
	 *
	 * @return boolean
	 */
	public static function are_quizzes_complete( $post, $user_id, $course_id ) {

		// get all the quizzes for a step.
		$quizzes = learndash_get_lesson_quiz_list( $post, null, $course_id );

		// if there are no quizzes, then everything is already complete.
		if ( empty( $quizzes ) ) {
			return true;
		}

		// set up an array for holding quiz IDs.
		$quizids = array();

		// pluck and populate quiz IDs.
		foreach ( $quizzes as $quiz ) {
			$quizids[ $quiz['post']->ID ] = $quiz['post']->ID;
		}

		// check if any of the quizzes have not been completed by the user.
		return ! learndash_is_quiz_notcomplete( null, $quizids, false, $course_id );
	}


	/**
	 * Checks if use has approved assignments.
	 *
	 * @param WP_Post $post The step's post object.
	 * @param integer $user_id The user's ID.
	 * @param integer $course_id The course ID.
	 *
	 * @return boolean
	 */
	public static function are_assignments_approved( $post, $user_id, $course_id ) {

		// no assignments, no restrictions.
		$assignments_exist_fn = function_exists( 'learndash_lesson_hasassignments' ) ? 'learndash_lesson_hasassignments' : 'lesson_hasassignments';
		if ( ! $assignments_exist_fn( $post ) ) {
			return true;
		}

		// otherwise, we have assignments.

		// get user submitted assigments for this topic/lesson.
		$user_assignments = learndash_get_user_assignments( $post->ID, $user_id, $course_id );

		// user hasn't submitted any, can't complete.
		if ( empty( $user_assignments ) ) {
			return false;
		}

		// loop through assignments.
		foreach ( $user_assignments as $assignment ) {
			// just one assignment needs to have been approved already.
			if ( learndash_is_assignment_approved_by_meta( $assignment->ID ) ) {
				return true;
			}
		}

		// user has submitted assignments that haven't been approved.

		// if they're not approved, are they set to auto-approve?
		$post_options_auto_complete = (array) learndash_get_setting( $post );
		if ( key_exists( 'auto_approve_assignment', $post_options_auto_complete ) && 'on' === $post_options_auto_complete['auto_approve_assignment'] ) {
			return true;
		}

		// no possibility of getting current assignments approved automatically.
		return false;

	}

	/**
	 * Display a done button on the last step.
	 *
	 * @param string $link The URL to link the button to.
	 *
	 * @return void
	 */
	public static function done_button( $link ) {
		/*
		 * With typical LearnDash functionality, when users click "Mark Complete" on the last topic in a
		 * lesson, they are automatically advanced to the next lesson.  With "autocomplete" turned on,
		 * there is no button, and also no link to the next lesson.
		 *
		 * Add hidden button to page if its a topic and on click progress to the next lesson or quiz
		 */
		?>
		<script>
			var uoDoneRedirect = jQuery.noConflict();
			uoDoneRedirect(function ($) {
				var formButton =
					'<form data-uo-redirect="" style="" id="sfwd-mark-complete" class="uo-done-redirect">' +
					'<input type="submit" value="<?php echo __( 'Done', 'uncanny-pro-toolkit' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" id="learndash_mark_complete_button">' +
					'</form>';

				if ($('#learndash_next_prev_link').length) {
					$('#learndash_next_prev_link').before(formButton);
				} else if ($('#learndash_back_to_lesson').length) {
					$('#learndash_back_to_lesson').after(formButton);
				} else if ($('.ld-content-actions .ld-content-action').length) {
					$('.ld-content-actions .ld-content-action').last().html(formButton);
				}

				$('.uo-done-redirect input').on('click', function (e) {
					e.preventDefault();
					var link = '<?php echo $link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>';
					if ('' !== link) {
						window.location.href = link;
					}
				});
			});
		</script>
		<?php
	}

	/**
	 * Checks if a string is a timestamp.
	 *
	 * @param string $string The string to check.
	 *
	 * @return bool
	 */
	public static function is_timestamp( $string ) {
		try {
			return ( (string) (int) $string === $string ) && ( $string <= PHP_INT_MAX ) && ( $string >= ~PHP_INT_MAX );
		} catch ( \Exception $e ) {
			return false;
		}
	}


	/**
	 * Add Auto Complete to LearnDash Options Meta Box
	 *
	 * @param array $post_args array of options from the LearnDash custom post type option meta box.
	 *
	 * @return array
	 */
	public static function add_auto_complete_to_post_args_legacy( $post_args ) {

		// Push existing and new fields.
		$new_post_args = array();

		// Loop through all post arguments.
		foreach ( $post_args as $key => $val ) {

			// add option on LD post type settings meta box.
			if ( in_array( $val['post_type'], self::$auto_completed_post_types ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				$new_post_args[ $key ]           = $val;
				$new_post_args[ $key ]['fields'] = array();

				// Add new field to top.
				$new_post_args[ $key ]['fields']['uo_auto_complete'] = array(
					'name'            => esc_attr__( 'Auto Complete', 'uncanny-pro-toolkit' ),
					'type'            => 'select',
					'help_text'       => esc_attr__( 'Automatically complete lesson or topic on page visit', 'uncanny-pro-toolkit' ),
					'initial_options' => array(
						'use_globals' => 'Use Global Settings',
						'disabled'    => 'Disabled',
						'enabled'     => 'Enabled',
					),
					'default'         => 'use_globals',
				);

				// loop through existing fields to get proper placement of new fields.
				foreach ( $post_args[ $key ]['fields'] as $field_key => $field_val ) {
					$new_post_args[ $key ]['fields'][ $field_key ] = $field_val;

				}
			} else {
				$new_post_args[ $key ] = $val;
			}
		}

		return $new_post_args;
	}
}
