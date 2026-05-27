<?php

namespace uncanny_learndash_codes;

if ( ! defined( 'WPINC' ) ) {
	die;
}
$is_edit       = false;
$group_details = array();
if ( SharedFunctionality::ulc_filter_has_var( 'edit' ) && SharedFunctionality::ulc_filter_has_var( 'group_id' ) ) {
	$is_edit = true;
	global $wpdb;
	$tbl_groups    = $wpdb->prefix . Config::$tbl_groups;
	$group_details = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $tbl_groups WHERE ID = %d", absint( SharedFunctionality::ulc_filter_input( 'group_id' ) ) ) );
	if ( isset( $group_details->character_type ) ) {
		$group_details->character_type = maybe_unserialize( $group_details->character_type );
	}
}

/**
 * New in 4.0
 */
$group_issue_count = 0;
if ( ! empty( $group_details ) ) {
	$group_issue_count = SharedFunctionality::ulc_get_issue_count( $group_details->ID );
}
$generate_codes = (object) array(
	'mode' => $is_edit ? 'edit' : 'create',

	'batch' => (object) array(
		'name'        => $is_edit && ! empty( $group_details->name ) ? $group_details->name : '',
		// a string, or ''.
		'dependency'  => $is_edit ? ( 'course' === (string) $group_details->code_for || 'group' === (string) $group_details->code_for ) ? 'learndash' : $group_details->code_for : '',
		// 'learndash', 'automator' or ''.
		'learndash'   => (object) array(
			'content'   => $is_edit ? ( 'course' === $group_details->code_for || 'group' === $group_details->code_for ) ? $group_details->code_for : '' : '',
			// 'learndash-courses', 'learndash-groups' or ''.
			'courses'   => $is_edit && ! empty( $group_details->linked_to ) ? maybe_unserialize( $group_details->linked_to ) : array(),
			// Array with IDs, or empty array.
			'groups'    => $is_edit && ! empty( $group_details->linked_to ) ? maybe_unserialize( $group_details->linked_to ) : array(),
			// Array with IDs, or empty array.
			'code_type' => $is_edit && ! empty( $group_details->paid_unpaid ) ? $group_details->paid_unpaid : 'default',
			// 'default', 'paid', 'unpaid', or the default value.
		),
		'codes_setup' => (object) array(
			'uses_per_code'     => $is_edit && ! empty( $group_details->issue_max_count ) ? $group_details->issue_max_count : 1,
			// a number, or the default value.
			'expiry_date'       => $is_edit && ! empty( $group_details->expire_date ) ? date_i18n( 'Y-m-d', strtotime( $group_details->expire_date ) ) : '',
			// a date formatted dd/mm/yyyy, or ''.
			'expiry_time'       => $is_edit && ! empty( $group_details->expire_date ) ? date_i18n( 'H:i:s', strtotime( $group_details->expire_date ) ) : '',
			// a time formatted as in the field, or ''.
			'generation_method' => (object) array(
				'method' => '', // 'auto', 'manual', or ''.
				'auto'   => (object) array(
					'number_of_codes'      => $is_edit && ! empty( $group_issue_count ) ? $group_issue_count : 1,
					// a number, or the default value.
					'number_of_characters' => $is_edit && ! empty( $group_details->dash ) ? array_sum( explode( '-', $group_details->dash ) ) : 20,
					// a number, or the default value.
					'type_of_characters'   => $is_edit && ! empty( $group_details->character_type ) ? $group_details->character_type : array(
						'uppercase-letters',
						'numbers',
					),
					// an array, with the values 'lowercase-letters', 'uppercase-letters' and/or 'numbers'.
					'dash_separation'      => $is_edit && ! empty( $group_details->dash ) ? $group_details->dash : '4-4-4-4-4',
					// a string, or the default value.
					'prefix'               => $is_edit && ! empty( $group_details->prefix ) ? $group_details->prefix : '',
					// a string, or '',.
					'suffix'               => $is_edit && ! empty( $group_details->suffix ) ? $group_details->suffix : '',
					// a string, or '',.
				),
				'manual' => (object) array(
					'manual_codes' => '',
					// a string with the codes, or ''.
				),
			),
		),
	),
);
// Check if the user already selected a dependency.
if ( $generate_codes->batch->dependency === null && SharedFunctionality::ulc_filter_has_var( 'dependency' ) ) {
	$generate_codes->batch->dependency = SharedFunctionality::ulc_filter_input( 'dependency' );
}

$ld_courses = get_posts( array(
	'post_type'      => 'sfwd-courses',
	'posts_per_page' => 10000,
	'post_status'    => 'publish',
	'orderby'        => 'post_title',
	'order'          => 'ASC',
) );
$courses    = array();
if ( $ld_courses ) {
	foreach ( $ld_courses as $ld_course ) {
		$courses[ $ld_course->ID ] = $ld_course->post_title;
	}
}
$ld_groups = get_posts( array(
	'post_type'      => 'groups',
	'posts_per_page' => 10000,
	'post_status'    => 'publish',
	'orderby'        => 'post_title',
	'order'          => 'ASC',
) );
$groups    = array();
if ( $ld_groups ) {
	foreach ( $ld_groups as $ld_group ) {
		$groups[ $ld_group->ID ] = $ld_group->post_title;
	}
}
$learndash_posts = (object) array(
	'courses' => $courses,
	'groups'  => $groups,
);

?>

<div class="uo-ulc-admin wrap">
	<div class="ulc">

		<?php

		// Add admin header and tabs.
		$tab_active = 'uncanny-learndash-codes-create';

		include Config::get_template( 'admin-header.php' );

		include Config::get_template( 'admin-generate-codes.php' );

		?>
	</div>
</div>
