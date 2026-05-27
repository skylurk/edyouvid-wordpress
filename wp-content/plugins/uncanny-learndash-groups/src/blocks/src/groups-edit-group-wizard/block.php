<?php

/**
 * Register Edit Group Wizard
 * render it with a callback function
 */

register_block_type(
	'uncanny-learndash-groups/uo-groups-edit-group',
	array(
		'attributes'      => array(
			'groupParent'    => array(
				'type'    => 'string',
				'default' => 'hide',
			),
			'groupName'      => array(
				'type'    => 'string',
				'default' => 'show',
			),
			'totalSeats'     => array(
				'type'    => 'string',
				'default' => 'show',
			),
			'groupCourses'   => array(
				'type'    => 'string',
				'default' => 'show',
			),
			'groupImage'     => array(
				'type'    => 'string',
				'default' => 'show',
			),
			'category'       => array(
				'type'    => 'string',
				'default' => '',
			),
			'courseCategory' => array(
				'type'    => 'string',
				'default' => '',
			),
		),
		'render_callback' => 'render_uo_edit_group_func',
	)
);

/**
 * @param $attributes
 *
 * @return false|string
 */
function render_uo_edit_group_func( $attributes ) {

	// Get course ID
	$courses_cats_section   = $attributes['category'];
	$courses_ld_cat_section = $attributes['courseCategory'];
	$group_parent_selector  = $attributes['groupParent'];
	$group_name_selector    = $attributes['groupName'];
	$total_seats_selector   = $attributes['totalSeats'];
	$group_courses_selector = $attributes['groupCourses'];
	$group_image_selector   = $attributes['groupImage'];

	// Start output
	ob_start();

	// Check if the class exists
	if ( class_exists( '\uncanny_learndash_groups\EditGroupWizard' ) ) {

		/** @var \uncanny_learndash_groups\EditGroupWizard $class */
		$class = \uncanny_learndash_groups\Utilities::get_class_instance( 'EditGroupWizard' );
		// Check if the course ID is empty
		echo $class->uo_groups_edit_group_func(
			array(
				'category'        => $courses_cats_section,
				'course_category' => $courses_ld_cat_section,
				'group_name'      => $group_name_selector,
				'parent_selector' => $group_parent_selector,
				'total_seats'     => $total_seats_selector,
				'group_courses'   => $group_courses_selector,
				'group_image'     => $group_image_selector,
			)
		);
	}

	// Get output
	$output = ob_get_clean();

	// Return output
	return $output;
}
