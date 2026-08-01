<?php

namespace uncanny_pro_toolkit;

use uncanny_learndash_toolkit as toolkit;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class BbpressLearndashGroupsAccess
 * @package uncanny_pro_toolkit
 */
class BbpressLearndashGroupsAccess extends toolkit\Config implements toolkit\RequiredFunctions {

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( __CLASS__, 'run_hooks' ) );
	}

	/*
	 * Initialize frontend actions and filters
	 */
	public static function run_hooks() {

		if ( true === self::dependants_exist() ) {
			/* ADD FILTERS ACTIONS FUNCTION */
			require_once Boot::get_pro_include( 'learndash-bbpress.php', UO_FILE );
			if ( class_exists( 'uncanny_pro_toolkit\Learndash_BBPress' ) ) {
				new Learndash_BBPress();
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

		if ( ! class_exists( 'bbPress' ) ) {
			return 'Plugin: bbPress';
		}

		// Return true if no dependency or dependency is available
		return true;
	}

	/**
	 * Description of class in Admin View
	 *
	 * @return array
	 */
	public static function get_details() {
		$module_id = 'group-forums-with-bbpress';

		$class_title = esc_html__( 'Group Forums with bbPress', 'uncanny-pro-toolkit' );

		// TODO get KB link
		$kb_link = 'https://www.uncannyowl.com/knowledge-base/learndash-group-forums-with-bbpress/';

		// TODO get description
		/* Sample Simple Description with shortcode */
		$class_description = esc_html__( 'Create group-specific discussion forums with bbPress.  Only group members will have access to group-specific forums.  Includes a handy widget.', 'uncanny-pro-toolkit' );

		$tags = 'learndash';
		$type = 'pro';
		$category = 'learndash';

		return array(
			'id'               => $module_id,
			'title'            => $class_title,
			'type'             => $type,
			'category'         => $category,
			'tags'             => $tags,
			'kb_link'          => $kb_link, // OR set as null not to display
			'description'      => $class_description,
			'dependants_exist' => self::dependants_exist(),
			'settings'         => false,
		);

	}
}
