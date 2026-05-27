<?php

namespace uncanny_ceu;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * Class ViewCertificates
 * @package uncanny_ceu
 */
class RemoveToolkitAction {

	/**
	 * class constructor
	 *
	 */
	function __construct() {
		add_action('load_textdomain', array($this, 'remove_action') );
	}

	/**
	 * Remove do_action that fires multiple completions when a course page is visited after completion
	 * To be removed after toolkit free fix implementation
	 */
	function remove_action() {

		remove_action( 'wp', array( 'uncanny_learndash_toolkit\MarkLessonsComplete', 'check_course_completed' ), 20 );
	}
}