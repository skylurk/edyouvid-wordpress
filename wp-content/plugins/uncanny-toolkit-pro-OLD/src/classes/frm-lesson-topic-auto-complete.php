<?php

namespace uncanny_pro_toolkit;

use FrmEntryMeta;
use FrmForm;
use FrmField;
use uncanny_learndash_toolkit as toolkit;
use WP_Post;


if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class FrmLessonTopicAutoComplete
 *
 * @package     uncanny_pro_toolkit
 */
class FrmLessonTopicAutoComplete extends toolkit\Config implements toolkit\RequiredFunctions {
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

			add_action( 'frm_additional_form_options', array( __CLASS__, 'additional_form_options_frm' ), 10, 1 );
			add_filter( 'frm_form_options_before_update', array( __CLASS__, 'form_options_before_update_frm' ), 20, 2 );

			add_action( 'frm_display_form_action', array( __CLASS__, 'add_hidden_course_lesson_topic' ), 8, 3 );

			add_filter( 'learndash_mark_complete', array( __CLASS__, 'remove_mark_complete_button_focus' ), 10, 2 );
			add_filter( 'learndash_show_next_link', array( __CLASS__, 'learndash_show_next_link_progression' ), 10, 3 );

			add_filter( 'frm_fields_in_form', array( __CLASS__, 'create_hidden_field_frm' ), 10, 2 );

			add_action( 'frm_after_create_entry', array( __CLASS__, 'maybe_mark_lesson_topic_complete' ), 99, 2 );
			add_action( 'init', array( __CLASS__, 'is_save_and_continue' ), 99 );
			add_action( 'frm_entries_before_create', array( __CLASS__, 'pre_submission' ), 99, 2 );
		}

	}

	/**
	 * @param $values
	 *
	 * @return array
	 */
	public static function additional_form_options_frm( $values ) { ?>
		<tr>
			<td colspan="2">
				<?php
				$opt = (array) get_option( 'frm_autocomplete_mark' ); ?>
				<label for="frm_autocomplete_mark">
					<input type="checkbox" value="1" id="frm_autocomplete_mark"
						   name="frm_autocomplete_mark" <?php echo ( in_array( $values['id'], $opt ) ) ? 'checked="checked"' : ''; ?> />
					<?php esc_html_e( 'Enable LearnDash Lesson/Topic Autocompletion', 'uncanny-pro-toolkit' ); ?>
					<span class="frm_help frm_icon_font frm_tooltip_icon"
						  title="<?php esc_attr_e( 'When enabled, the Uncanny Toolkit module will attempt to mark a lesson or topic containing this form complete on submission. It will also remove the Mark Complete button.', 'uncanny-pro-toolkit' ); ?>"
						  data-container="body"></span>
				</label>
			</td>
		</tr>

	<?php }

	/**
	 * @param $options
	 * @param $values
	 *
	 * @return array
	 */
	public static function form_options_before_update_frm( $options, $values ) {
		$opt = (array) get_option( 'frm_autocomplete_mark' );
		if ( isset( $values['frm_autocomplete_mark'] ) && ( ! isset( $values['id'] ) || ! in_array( $values['id'], $opt ) ) ) {
			$opt[] = $values['id'];
			update_option( 'frm_autocomplete_mark', $opt );
		} elseif ( ! isset( $values['frm_autocomplete_mark'] ) && isset( $values['id'] ) && in_array( $values['id'], $opt ) ) {
			$pos = array_search( $values['id'], $opt );
			unset( $opt[ $pos ] );
			update_option( 'frm_autocomplete_mark', $opt );
		}

		return $options;

	}

	/**
	 * Make sure to capture course, lesson and topic IDs
	 *
	 * @param $entry
	 * @param $form
	 */
	public static function pre_submission( $entry, $form ) {
		if ( $form && filter_has_var( INPUT_POST, 'item_meta' ) ) {
			foreach ( $_POST['item_meta'] as $key => $value ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( in_array( $key, array( 10001, 10002, 10003 ), true ) ) {
					$_POST[ 'input_' . $key ] = $value;
					switch ( $key ) {
						case 10001:
							self::$course_id = $value;
							break;
						case 10002:
							self::$lesson_id = $value;
							break;
						case 10003:
							self::$topic_id = $value;
							break;
					}
				}
			}
		}
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
		/* Check for Formidable Formidables */
		if ( ! class_exists( 'FrmForm' ) ) {
			return 'Plugin: Formidable Forms';

		}

		return true;
	}

	/**
	 * Description of class in Admin View
	 *
	 * @return array
	 */
	public static function get_details() {
		$module_id = 'autocomplete-lessons-topics-on-formidableform-submission';

		$class_title = esc_html__( 'Autocomplete Lessons & Topics on Formidable Forms Submission', 'uncanny-pro-toolkit' );

		$kb_link = 'http://www.uncannyowl.com/knowledge-base/formidable-auto-completes-lessons-topics/';

		/* Sample Simple Description with shortcode */
		$class_description = esc_html__( 'Automatically mark LearnDash lessons and topics as complete when the user submits a form.', 'uncanny-pro-toolkit' );

		/* Icon as text - max four characters wil fit */
		$icon_styles = 'width: 40px;  padding-top: 5px; padding-left: 9px;';
		$class_icon  = '<img style="' . $icon_styles . '" src="' . self::get_admin_media( 'wp-forms-icon-white.png' ) . '" />';

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
				'label'       => esc_html__( 'Hide the Mark Complete button if the user has a previous entry', 'uncanny-pro-toolkit' ),
				'option_name' => 'uo-lesson-topic-hide-button-for-frm',
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
	 * @param $entry
	 * @param $form
	 */
	public static function maybe_mark_lesson_topic_complete( $entry_id, $form_id ) {
		// if course ID is not set, return
		if ( ! key_exists( 10001, $_POST['item_meta'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}

		$course_id = (int) $_POST['item_meta'][10001];
		$lesson_id = (int) $_POST['item_meta'][10002];
		$topic_id  = (int) $_POST['item_meta'][10003];

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
		if ( false === apply_filters( 'uo_frm_maybe_autocomplete_redirect', true ) ) {
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
	 * @param $params
	 * @param $fields
	 * @param $form
	 *
	 * @return mixed
	 */
	public static function add_hidden_course_lesson_topic( $params, $fields, $form ) {
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


		$opt     = (array) get_option( 'frm_autocomplete_mark' );
		$checked = in_array( $form_id, $opt ) ? 1 : 0;


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
	 * @param $user_id
	 * @param $form_id
	 *
	 * @return bool
	 */
	public static function get_lead_id( $user_id, $form_id ) {
		global $wpdb;

		$entries_table = $wpdb->prefix . 'frm_items';
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
					if ( $block['blockName'] === 'formidable/block' || $block['blockName'] === 'formidable/form' ) {
						$form_id = $block['attrs']['formId'];
						break;
					}
				}
				if ( ! isset( $form_id ) ) {
					return $show_next_link;
				}
			}
		}


		if ( ! has_shortcode( $module->post_content, 'formidable' ) || ! $lesson_progression_enabled ) {
			return $show_next_link;
		}

		// Do not hide next lesson if formidable is ajax submission
		$pattern = get_shortcode_regex();
		if ( preg_match_all( '/' . $pattern . '/s', $module->post_content, $matches ) && array_key_exists( 2, $matches ) && in_array( 'formidable', $matches[2] ) ) {
			$all_matched = $matches[0]; // contains all shortcodes in content
			if ( $all_matched ) {
				foreach ( $all_matched as $m ) {
					//Match wpform with ajax="true"
					if ( preg_match( '/(\[formidable(.*)(ajax)(.*)\])/', $m ) ) {
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
	 *
	 */
	public static function is_save_and_continue() {
		if ( filter_has_var( INPUT_POST, 'frm_action' ) && 'create' === filter_input( INPUT_POST, 'frm_action' ) ) {
			self::$show_mark_complete = 0;
			add_filter( 'learndash_mark_complete', array( __CLASS__, 'remove_mark_complete_button' ), 99, 2 );
		}
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
					if ( $block['blockName'] === 'formidable/simple-form' || $block['blockName'] === 'formidable/simple-form' ) {
						$form_id = $block['attrs']['formId'];
						break;
					}
				}
			}
		}

		if ( has_shortcode( $post->post_content, 'formidable' ) ) {
			$short_codes = self::attribute_map( $post->post_content );
			if ( ! isset( $short_codes['formidable'] ) && $form_id == 0 ) {
				return $return;
			}

			$form_id = isset( $short_codes['formidable']['id'] ) ? $short_codes['formidable']['id'] : $form_id;
			$form_id = isset( $short_codes['formidable'][0]['id'] ) ? $short_codes['formidable'][0]['id'] : $form_id;
		}

		if ( ! $form_id ) {
			return $return;
		}

		$form_data    = FrmForm::getOne( $form_id );
		$form['form'] = $form_data;

		$form_id = $form['form']->id;

		$opt     = (array) get_option( 'frm_autocomplete_mark' );
		$checked = in_array( $form_id, $opt ) ? 1 : 0;

		$user = wp_get_current_user()->ID; // current logged in user ID

		if ( in_array( $post->post_type, $learndash_post_types, true ) && 1 === ( int ) $checked ) {

			$lead_id        = self::get_lead_id( $user, $form_id ); // checking if there's any lead available for $user for $form_id.
			$settings_value = self::get_settings_value( 'uo-lesson-topic-hide-button-for-frm', __CLASS__ );

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
	 * @param $fields
	 * @param $args
	 *
	 */
	public static function create_hidden_field_frm( $fields, $args ) {
		global $post;

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


		$field_new[] = array(
			'id'            => 10001,
			'placeholder'   => '',
			'value'         => $course_id,
			'field_key'     => 10001,
			'name'          => '',
			'description'   => '',
			'options'       => '',
			'field_options' => '',
			'type'          => 'hidden',
			'label'         => 'Course ID',
			'default_value' => $course_id,
			'required'      => 0,
		);

		$field_new[] = array(
			'id'            => 10002,
			'placeholder'   => '',
			'value'         => $lesson_id,
			'field_key'     => 10002,
			'name'          => '',
			'description'   => '',
			'options'       => '',
			'field_options' => '',
			'type'          => 'hidden',
			'label'         => 'Lesson ID',
			'default_value' => $lesson_id,
			'required'      => 0,
		);

		$field_new[] = array(
			'id'            => 10003,
			'placeholder'   => '',
			'value'         => $topic_id,
			'field_key'     => 10003,
			'name'          => '',
			'description'   => '',
			'options'       => '',
			'field_options' => '',
			'type'          => 'hidden',
			'label'         => 'Topic ID',
			'default_value' => $topic_id,
			'required'      => 0,
		);
		$fields      = array_merge( $fields, $field_new );

		return $fields;
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
		$settings_value = self::get_settings_value( 'uo-lesson-topic-hide-button-for-frm', __CLASS__ );
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


}
