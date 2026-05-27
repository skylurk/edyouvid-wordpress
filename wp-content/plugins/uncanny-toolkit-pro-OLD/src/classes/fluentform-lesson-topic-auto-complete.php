<?php

namespace uncanny_pro_toolkit;

use uncanny_learndash_toolkit as toolkit;
use WP_Post;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class FluentformLessonTopicAutoComplete
 *
 * @package     uncanny_pro_toolkit
 */
class FluentformLessonTopicAutoComplete extends toolkit\Config implements toolkit\RequiredFunctions {
	/**
	 * @var array
	 */
	public static $auto_completed_post_types = array( 'sfwd-lessons', 'sfwd-topic' );
	/**
	 * @var int
	 */
	public static $show_mark_complete = 0;
	/**
	 * @var int
	 */
	public static $course_id = 0;
	/**
	 * @var int
	 */
	public static $lesson_id = 0;
	/**
	 * @var int
	 */
	public static $topic_id = 0;
	/**
	 * @var int
	 */
	public static $redirection = null;

	/**
	 * Class constructor
	 */

	public function __construct() {
		add_action( 'plugins_loaded', array( __CLASS__, 'run_frontend_hooks' ), 10 );
	}

	/*
	 * Initialize frontend actions and filters
	 */
	/**
	 *
	 */
	public static function run_frontend_hooks() {

		if ( true === self::dependants_exist() ) {
			/* ADD FILTERS ACTIONS FUNCTION */

			add_action( 'fluentform/before_form_render', array( __CLASS__, 'add_hidden_course_lesson_topic' ), 10 );

			add_filter( 'learndash_mark_complete', array( __CLASS__, 'remove_mark_complete_button_focus' ), 10, 2 );
			add_filter( 'learndash_show_next_link', array( __CLASS__, 'learndash_show_next_link_progression' ), 10, 3 );

			add_filter( 'fluentform/rendering_form', array( __CLASS__, 'create_hidden_field_ff'), 10 );

			add_action( 'fluentform_before_insert_submission', array( __CLASS__, 'maybe_mark_lesson_topic_complete' ), 99, 3 );
			add_action( 'init', array( __CLASS__, 'is_save_and_continue' ), 99 );
			add_action( 'fluentform_before_form_actions_processing', array( __CLASS__, 'pre_submission' ), 99, 3 );

	    }

    }


	/**
	 * @param $form
	 *
	 * @return mixed
	 */
	public static function create_hidden_field_ff( $form ) {
		global $post;

		$form_fields = json_decode($form->form_fields);

		$course_id = 0;
		$lesson_id = 0;
		$topic_id  = 0;

		if ( 'sfwd-courses' === $post->post_type ) {
			$course_id = $post->ID;
		}
		if ( 'sfwd-lessons' === $post->post_type ) {
			$course_id = learndash_get_course_id( $post->ID );
			$lesson_id = $post->ID;
		}
		if ( 'sfwd-topic' === $post->post_type ) {
			$course_id = learndash_get_course_id( $post->ID );
			$topic_id  = $post->ID;
		}

		$new_field_course =  array(
			'index'          => 10001,
			'element'        => 'input_hidden',
			'attributes'     => array(
				'type'       => 'hidden',
				'name'       => '10001',
				'value'      => $course_id,

			),
			'settings'       => '',
			'editor_options' => '',
			'uniqElKey'      => '10001'

		);
		$new_field_lesson =  array(
			'index'          => 10002,
			'element'        => 'input_hidden',
			'attributes'     => array(
				'type'       => 'hidden',
				'name'       => '10002',
				'value'      => $lesson_id,

			),
			'settings'       => '',
			'editor_options' => '',
			'uniqElKey'      => '10002'

		);
		$new_field_topic =  array(
			'index'          => 10003,
			'element'        => 'input_hidden',
			'attributes'     => array(
				'type'       => 'hidden',
				'name'       => '10003',
				'value'      => $topic_id,

			),
			'settings'       => '',
			'editor_options' => '',
			'uniqElKey'      => '10003'

		);

		array_push( $form->fields['fields'], $new_field_course,$new_field_lesson,$new_field_topic );
		array_push( $form_fields->fields, $new_field_course,$new_field_lesson,$new_field_topic  );
		$form->form_fields = json_encode( $form_fields );

		return $form;
	}


	/**
	 * @param $form
	 *
	 * @return mixed
	 */
	public static function add_hidden_course_lesson_topic( $form ) {

		if ( ! is_user_logged_in() ) {
			return $form;
		}

		global $post;
		if ( ! $post instanceof \WP_Post ) {
			return $form;
		}
		global $learndash_post_types;
		if ( empty( $learndash_post_types ) ) {
			return $form;
		}

		$form_id = $form->id;
		$user    = wp_get_current_user()->ID; // current logged in user ID

		$checked = self::get_settings_value( 'ff_autocomplete_mark', __CLASS__ );
		if ( ! empty( $checked ) ) {
			$checked = 1;
		}else {
			$checked = 0;
		}

		if ( 1 !== absint( $checked ) ) {
			return $form;
		}

		if ( ! in_array( $post->post_type, $learndash_post_types, true ) ) {
			return $form;
		}

		$lead_id = self::get_lead_id( $user, $form_id ); // checking if there's any lead available for $user for $form_id.

		if ( ! empty( $lead_id ) && 'sfwd-lessons' === $post->post_type ) {
			if ( true === self::maybe_learndash_mark_complete( $post ) ) {
				self::$show_mark_complete = 1;
			} else {
				self::$show_mark_complete = 1;
			}
		} elseif ( ! empty( $lead_id ) && 'sfwd-topic' === $post->post_type ) {
			if ( true === self::maybe_learndash_mark_complete( $post ) ) {
				self::$show_mark_complete = 1;
			} else {
				self::$show_mark_complete = 1;
			}
		} else {
			if ( 1 === (int) $checked ) {
				self::$show_mark_complete = 0;
			} else {
				self::$show_mark_complete = 1;
			}
		}

		add_filter( 'learndash_mark_complete', array( __CLASS__, 'remove_mark_complete_button' ), 100, 2 );

		return $form;

	}

    /**
	 * Description of class in Admin View
	 *
	 * @return array
	 */
	public static function get_details() {
		$module_id = 'autocomplete-lessons-topics-on-ffforms-submission';

		$class_title = esc_html__( 'Autocomplete Lessons & Topics on Fluent Forms Submission', 'uncanny-pro-toolkit' );

		$kb_link = 'http://www.uncannyowl.com/knowledge-base/ffforms-auto-completes-lessons-topics/';

		/* Sample Simple Description with shortcode */
		$class_description = esc_html__( 'Automatically mark LearnDash lessons and topics as complete when the user submits a form.', 'uncanny-pro-toolkit' );

		/* Icon as text - max four characters wil fit */
		$icon_styles = 'width: 40px;  padding-top: 5px; padding-left: 9px;';
		$class_icon  = '<img style="' . $icon_styles . '" src="' . self::get_admin_media( 'ff-forms-icon-white.png' ) . '" />';

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
	 *
	 */
	public static function get_class_settings( $class_title ) {

		// Create options
		$options = array(

			array(
				'type'        => 'checkbox',
				'label'       => esc_attr__( 'Hide the "Mark Complete" button automatically when the Fluent Forms are added to the lessons and topics', 'uncanny-pro-toolkit' ),
				'option_name' => 'ff_autocomplete_mark',
			),

			array(
				'type'        => 'checkbox',
				'label'       => esc_html__( 'Hide the "Mark Complete" button if the user has a previous entry', 'uncanny-pro-toolkit' ),
				'option_name' => 'uo-lesson-topic-hide-button-for-ff',
			),
		);

		// Build html
		$html = self::settings_output(
			array(
				'class'   => __CLASS__,
				'title'   => $class_title,
				'options' => $options,
			) );

		return $html;
	}

    /**
	 * Does the plugin rely on another function or plugin
	 *
	 * @return boolean || string Return either true or name of function or plugin
	 *
	 */
	public static function dependants_exist() {

		/* Checks for LearnDash */
		global $learndash_post_types;
		if ( ! isset( $learndash_post_types ) ) {
			return 'Plugin: LearnDash';
		}

		/* Check for fluentform */
		if ( ! is_plugin_active( 'fluentform/fluentform.php' ) ) {
			return 'Plugin: Fluent Forms';
		}

		return true;
	}

	/**
	 * Filter HTML output to mark course complete button
	 *
	 * @param string $return
	 * @param object $post
	 *
	 * @return string $return
	 */
	public static function remove_mark_complete_button( $return, $post ) {

		/* LD 2.3 removed the next if until mark complete is clicked, since we removed the mark complete button
		 * there is no way to progress. LD allows to added the next to be added back in version 2.3.0.2. Let's use it!
		*/
		$settings_value = self::get_settings_value( 'uo-lesson-topic-hide-button-for-ff', __CLASS__ );
		if ( ! empty( $settings_value ) ) {
			self::$show_mark_complete = 0;
		}

		$post_type = $post->post_type;
		if ( 0 === (int) self::$show_mark_complete ) {
			return '';
		}

		if ( true === self::maybe_learndash_mark_complete( $post ) ) {
			// Remove mark complete button if its a lesson or topic
			if ( in_array( $post_type, self::$auto_completed_post_types, true ) ) {
				if ( 1 !== (int) self::$show_mark_complete ) {
					return '';
				}
			}
		}

		return $return;

	}

	/**
	 * @param $post
	 *
	 * @return bool
	 */
	public static function maybe_learndash_mark_complete( $post ) {

		$current_user = wp_get_current_user();

		if ( filter_has_var( INPUT_POST, 'sfwd_mark_complete' ) && filter_has_var( INPUT_POST, 'post' ) && $post->ID === filter_input( INPUT_POST, 'post' ) ) {
			return false;
		}

		$course_id   = learndash_get_course_id( $post->ID );
		$progression = learndash_lesson_progression_enabled( $course_id );

		if ( 'sfwd-lessons' === (string) $post->post_type ) {
			$progress = learndash_get_course_progress( $current_user->ID, $post->ID );

			$prev_completed = 1;
			if ( isset( $progress['prev'] ) && ! empty( $progress['prev'] ) ) {
				$prev_completed = $progress['prev']->completed;
			}

			$this_completed = 1;
			if ( isset( $progress['this'] ) && ! empty( $progress['this'] ) ) {
				$this_completed = $progress['this']->completed;
			}

			if ( 1 === absint( $progression ) && 0 === $prev_completed ) {
				//Lesson progression enabled and previous is not completed
				return false;
			}

			if ( 0 !== $this_completed ) {
				//Lesson already completed
				return false;
			}

			if ( ! learndash_lesson_topics_completed( $post->ID ) ) {
				//if topics of lessons are not completed
				return false;
			}
		}

		if ( 'sfwd-topic' === $post->post_type ) {
			$progress = learndash_get_course_progress( $current_user->ID, $post->ID );

			$prev_completed = 1;
			if ( isset( $progress['prev'] ) && ! empty( $progress['prev'] ) ) {
				$prev_completed = $progress['prev']->completed;
			}

			$this_completed = 1;
			if ( isset( $progress['this'] ) && ! empty( $progress['this'] ) ) {
				$this_completed = $progress['this']->completed;
			}

			if ( 1 === absint( $progression ) && 0 === $prev_completed ) {
				//Lesson progression enabled and previous is not completed
				return false;
			}

			if ( 0 !== $this_completed ) {
				//Lesson already completed
				return false;
			}

			if ( 1 === absint( $progression ) ) {
				$lesson_id             = learndash_get_lesson_id( $post->ID );
				$lesson                = get_post( $lesson_id );
				$is_previous_completed = function_exists( 'learndash_is_previous_complete' ) ? 'learndash_is_previous_complete' : 'is_previous_complete';
				if ( ! $is_previous_completed( $lesson ) ) {
					return false;
				}
			}
		}

		$assignments_exist_fn = function_exists( 'learndash_lesson_hasassignments' ) ? 'learndash_lesson_hasassignments' : 'lesson_hasassignments';
		if ( $assignments_exist_fn( $post ) ) {
			return false;
		}

		return true;

	}


	/**
	 * Standalone filter function for mark complete button
	 *
	 * @param string $return content of current post.
	 * @param object $post WP Post object.
	 *
	 * @return string
	 */
	public static function remove_mark_complete_button_focus( $return, $post ) {

		$form_id = 0;
		global $learndash_post_types;

		if ( function_exists( 'has_blocks' ) ) {
			if ( has_blocks( $post->post_content ) ) {
				$blocks = parse_blocks( $post->post_content );

				foreach ( $blocks as $block ) {
					if ( $block['blockName'] === 'fluentfom/guten-block' || $block['blockName'] === 'fluentfom/guten-block' ) {
						$form_id = $block['attrs']['formId'];
						break;
					}
				}
			}
		}

		if ( has_shortcode( $post->post_content, 'fluentform' ) ) {
			$short_codes = self::attribute_map( $post->post_content );

			if ( ! isset( $short_codes['fluentfom'] ) && $form_id == 0 ) {
				return $return;
			}

			$form_id = isset( $short_codes['fluentform']['id'] ) ? $short_codes['fluentform']['id'] : $form_id;
			$form_id = isset( $short_codes['fluentform'][0]['id'] ) ? $short_codes['fluentform'][0]['id'] : $form_id;
		}

		if ( ! $form_id ) {
			return $return;
		}

		$form         = wpFluent()->table('fluentform_forms')->find($form_id);
		$form_id       = $form->id;

		$checked = self::get_settings_value( 'ff_autocomplete_mark', __CLASS__ );
		if ( ! empty( $checked ) ) {
			$checked = 1;
		}else {
			$checked = 0;
		}


		$user          = wp_get_current_user()->ID; // current logged in user ID

		if ( in_array( $post->post_type, $learndash_post_types, true ) && 1 === ( int ) $checked ) {

			$lead_id        = self::get_lead_id( $user, $form_id ); // checking if there's any lead available for $user for $form_id.
			$settings_value = self::get_settings_value( 'uo-lesson-topic-hide-button-for-ff', __CLASS__ );

			if ( ! empty( $lead_id ) && 'sfwd-lessons' === $post->post_type ) {
				if ( true === self::maybe_learndash_mark_complete( $post ) ) {
					self::$show_mark_complete = 1;
				} else {
					self::$show_mark_complete = 1;
				}
			} elseif ( ! empty( $lead_id ) && 'sfwd-topic' === $post->post_type ) {
				if ( true === self::maybe_learndash_mark_complete( $post ) ) {
					self::$show_mark_complete = 1;
				} else {
					self::$show_mark_complete = 1;
				}
			} else {
				if ( 1 === (int) $checked ) {
					self::$show_mark_complete = 0;
				} else {
					self::$show_mark_complete = 1;
				}
			}

			if ( ! empty( $lead_id ) && ! empty( $settings_value ) ) {
				return '';
			}

			if ( true === self::maybe_learndash_mark_complete( $post ) ) {
				$post_type = $post->post_type;
				// Remove mark complete button if its a lesson or topic
				if ( in_array( $post_type, self::$auto_completed_post_types, true ) ) {
					if ( 1 !== (int) self::$show_mark_complete ) {
						return '';
					}
				}
			}
		}

		return $return;

	}

	/**
	 * @param bool $show_next_link
	 * @param int $user_id
	 * @param int $post_id
	 *
	 * @return bool
	 */
	public static function learndash_show_next_link_progression( $show_next_link = false, $user_id = 0, $post_id = 0 ) {

		//$focus_mode = \LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'focus_mode_enabled' );

		$module    = get_post( $post_id );
		$course_id = learndash_get_course_id( $post_id );

		$lesson_progression_enabled = learndash_lesson_progression_enabled( $course_id );

		if ( function_exists( 'has_blocks' ) ) {
			if ( has_blocks( $module->post_content ) ) {
				$blocks = parse_blocks( $module->post_content );
				foreach ( $blocks as $block ) {
					if ( $block['blockName'] === 'fluentfom/guten-block' || $block['blockName'] === 'fluentfom/guten-block' ) {
						$form_id = $block['attrs']['formId'];
						break;
					}
				}
				if ( ! isset( $form_id ) ) {
					return $show_next_link;
				}
			}
		}


		if ( ! has_shortcode( $module->post_content, 'fluentfom' ) || ! $lesson_progression_enabled ) {
			return $show_next_link;
		}

		// Do not hide next lesson if fluentfom is ajax submission
		$pattern = get_shortcode_regex();
		if ( preg_match_all( '/' . $pattern . '/s', $module->post_content, $matches ) && array_key_exists( 2, $matches ) && in_array( 'fluentfom', $matches[2] ) ) {
			$all_matched = $matches[0]; // contains all shortcodes in content
			if ( $all_matched ) {
				foreach ( $all_matched as $m ) {
					//Match wpform with ajax="true"
					if ( preg_match( '/(\[fluentfom(.*)(ajax)(.*)\])/', $m ) ) {
						return $show_next_link;
					}
				}
			}
		}

		if ( ! empty( $module ) && 0 !== get_current_user_id() ) {
			if ( 'sfwd-lessons' === $module->post_type ) {
				if ( ! learndash_is_lesson_complete( get_current_user_id(), $module->ID ) ) {
					return false;
				}
			}

			if ( 'sfwd-topic' === $module->post_type ) {
				if ( ! learndash_is_topic_complete( get_current_user_id(), $module->ID ) ) {
					return false;
				}
			}
		}

		return $show_next_link;
	}


	/**
	 * @param $user_id
	 * @param $form_id
	 *
	 * @return bool
	 */
	public static function get_lead_id( $user_id, $form_id ) {
		global $wpdb;

		$entries_table = $wpdb->prefix . 'fluentform_submissions';
		$query         = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $entries_table ) );
		if ( ! $wpdb->get_var( $query ) == $entries_table ) {
			return false;
		} else {
			$entry = $wpdb->get_row( "SELECT id FROM {$entries_table} WHERE user_id = $user_id AND form_id = $form_id" );

			if ( isset( $entry->id ) && is_numeric( intval( $entry->id ) ) ) {
				return $entry->id;
			} else {
				return false;
			}

		}
	}

	/**
	 * short code parsing function with attributes
	 *
	 * @param string $str .
	 * @param string $att .
	 *
	 * @return array
	 */
	private static function attribute_map( $str, $att = null ) {
		$res = array();
		$reg = get_shortcode_regex();
		preg_match_all( '~' . $reg . '~', $str, $matches );
		foreach ( $matches[2] as $key => $name ) {
			$parsed = shortcode_parse_atts( $matches[3][ $key ] );
			$parsed = is_array( $parsed ) ? $parsed : array();

			if ( array_key_exists( $name, $res ) ) {
				$arr = array();
				if ( is_array( $res[ $name ] ) ) {
					$arr = $res[ $name ];
				} else {
					$arr[] = $res[ $name ];
				}

				$arr[]        = array_key_exists( $att, $parsed ) ? $parsed[ $att ] : $parsed;
				$res[ $name ] = $arr;

			} else {
				$res[ $name ][] = array_key_exists( $att, $parsed ) ? $parsed[ $att ] : $parsed;
			}
		}

		return $res;
	}

	/**
	 *
	 */
	public static function is_save_and_continue() {
		if ( filter_has_var( INPUT_POST, 'action' ) && 'fluentform_submit' ===  filter_input( INPUT_POST, 'action' )  ) {
			self::$show_mark_complete = 0;
			add_filter( 'learndash_mark_complete', array( __CLASS__, 'remove_mark_complete_button' ), 99, 2 );
		}
	}


	/**
	 * @param $insertData
	 * @param $data
	 * @param $form
	 */
	public static function maybe_mark_lesson_topic_complete( $insertData, $data, $form ) {
		// if course ID is not set, return
		if ( ! key_exists( 10001, $data) ) {
			return;
		}

		$course_id = (int) $data[10001];
		$lesson_id = (int) $data[10002];
		$topic_id  = (int) $data[10003];

		if ( ( empty( $course_id ) || 0 === $course_id ) && 0 !== self::$course_id ) {
			$course_id = self::$course_id;
		}

		if ( ( empty( $lesson_id ) || 0 === $lesson_id ) && 0 !== self::$lesson_id ) {
			$lesson_id = self::$lesson_id;
		}

		if ( ( empty( $topic_id ) || 0 === $topic_id ) && 0 !== self::$topic_id ) {
			$topic_id = self::$topic_id;
		}

		$result = false;
		$post   = array();
		$user   = wp_get_current_user();
		if ( 0 !== (int) $lesson_id ) {
			$post   = get_post( $lesson_id );
			$result = self::maybe_learndash_mark_complete( $post );

		} elseif ( 0 !== (int) $topic_id ) {
			$post   = get_post( $topic_id );
			$result = self::maybe_learndash_mark_complete( $post );
		}

		if ( true !== $result ) {
			return;
		}

		if ( 'sfwd-lessons' === $post->post_type ) {
			if ( ! learndash_is_lesson_complete( get_current_user_id(), $post->ID ) ) {
				learndash_process_mark_complete( $user->ID, $post->ID );
			}
		} elseif ( 'sfwd-topic' === $post->post_type ) {
			if ( ! learndash_is_topic_complete( get_current_user_id(), $post->ID ) ) {
				learndash_process_mark_complete( $user->ID, $post->ID );
			}
		}

		// if doing ajax, return
		if ( wp_doing_ajax() ) {
			return;
		}

		/**
		 * Filters whether a student should be redirected after completing
		 *
		 * @param bool
		 *
		 * @since 3.5.1
		 */
		if ( false === apply_filters( 'uo_ff_maybe_autocomplete_redirect', true ) ) {
			return;
		}

		$next_post_link = learndash_next_post_link( '', true, $post );
		$is_last_step   = empty( $next_post_link );
		$button_link    = $next_post_link;

		// there is no next post link!
		if ( $is_last_step ) {

			// this is the last topic in the lesson, get the lesson's link.
			if ( 'sfwd-topic' === (string) $post->post_type ) {
				$button_link = get_permalink( learndash_get_lesson_id( $post->ID ) );
			} else {
				// this is the last lesson in the course, get the global quiz/course's link.
				$button_link = learndash_next_global_quiz( true, null, $course_id );
			}
		}

		if ( wp_safe_redirect( $button_link ) ) {
			exit();
		} elseif ( 'sfwd-topic' === $post->post_type ) {
			$redirection_link = get_permalink( learndash_get_lesson_id( $post->ID ) );
			wp_safe_redirect( $redirection_link );
			exit();
		}
	}

	/**
	 * Make sure to capture course, lesson and topic IDs
	 *
	 * @param $insertId
	 * @param $formData
	 * @param $form
	 */
	public static function pre_submission( $insertId, $formData, $form ) {
		if ( $form && isset( $formData ) ) {
			foreach ( $formData as $key => $value ) {
				if ( in_array( $key, array( 10001, 10002, 10003 ), true ) ) {
					$_POST[ 'input_' . $key ] = $value;
					switch ( $key ) {
						case 10001;
							self::$course_id = $value;
							break;
						case 10002;
							self::$lesson_id = $value;
							break;
						case 10003;
							self::$topic_id = $value;
							break;
					}
				}
			}
		}
	}

}
