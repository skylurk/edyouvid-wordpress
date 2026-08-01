<?php

//restrict participation in forum ( replies & topic creation )
add_filter( 'bbp_current_user_can_publish_topics', 'uo_ld_restrict_forum_participation' );
add_filter( 'bbp_current_user_can_publish_replies', 'uo_ld_restrict_forum_participation' );
function uo_ld_restrict_forum_participation( $can_post ) {

	// Always allow keymasters
	$role = bbp_get_user_role( get_current_user_id() );
	if ( bbp_is_user_keymaster() || $role == 'bbp_moderator' ) {
		return true;
	}

	$forum_id = bbp_get_forum_id();

	global $wpdb;
	$group_forums = $wpdb->get_results( "Select meta_value as forum_id FROM $wpdb->postmeta WHERE meta_key LIKE 'uo_ld_associated_forum%' AND meta_value = $forum_id" );

	if( empty($group_forums) ){
		return $can_post;
	}



	$can_post = true;

	$user_forums = get_user_forum_group_access();

	if ( ! isset( $user_forums[ $forum_id ] ) || 0 === $user_forums[ $forum_id ]) {
		$can_post = false;
	}

	return $can_post;
}

//disable topic subscription & favorite link for users except course students.
add_filter( 'bbp_get_user_subscribe_link', 'uo_ld_disable_topic_subscription', 10, 4 );
add_filter( 'bbp_get_user_favorites_link', 'uo_ld_disable_topic_subscription', 10, 4 );
function uo_ld_disable_topic_subscription( $html, $r, $user_id, $topic_id ) {
	// Always allow keymasters
	$role = bbp_get_user_role( $user_id );
	if ( bbp_is_user_keymaster() || $role == 'bbp_moderator' ) {
		return $html;
	}

	$forum_id = bbp_get_forum_id();

	$user_forums = get_user_forum_group_access();

	$can_subscribe = true;

	if ( ! isset( $user_forums[ $forum_id ] ) || 0 === $user_forums[ $forum_id ]) {
		$can_subscribe = false;
	}

	if ( $can_subscribe ) {
		return $html;
	} else {
		return '';
	}
}

// Restrict access to forum & topics completely & show take course message in place

add_filter( 'bbp_user_can_view_forum', 'uo_ld_restrict_forum_access', 15, 3 );
function uo_ld_restrict_forum_access( $retval, $forum_id, $user_id ) {
	// Always allow keymasters
	$role = bbp_get_user_role( $user_id );
	if ( bbp_is_user_keymaster() || $role == 'bbp_moderator' ) {
		return true;
	}

	global $wpdb;
	$group_forums = $wpdb->get_results( "Select meta_value as forum_id FROM $wpdb->postmeta WHERE meta_key LIKE 'uo_ld_associated_forum%' AND meta_value = $forum_id" );

	if( empty($group_forums) ){
		return $retval;
	}

	$user_forums = get_user_forum_group_access();

	$has_access = true;

	if ( ! isset( $user_forums[ $forum_id ] ) ) {
		$has_access = false;
	}

	$message_without_access = get_post_meta( $forum_id, 'uo_ld_message_without_access', true );

	$content = "<div id='bbpress-forums' class='ld-bbpress-forums'>
					<p class='pre-message'>" . $message_without_access . "</p>
				</div>";

	if ( $has_access === false ) {
		$retval = false;
		echo apply_filters( 'uo_ld_forum_access_restricted_message', $content, $forum_id, $user_forums );
	}




	return $retval;
}

//show associated group below the forum title in forum archive
add_action( 'bbp_theme_after_forum_description', 'uo_ld_associated_course_link' );
function uo_ld_associated_course_link() {
	$content = "<span class='ld-bbpress-desc-link'><small><strong>" .
			   /* Translators: 1. LearnDash courses label  */
			   sprintf( esc_attr__( 'Associated %1$s', '', 'uncanny-pro-toolkit' ), 'groups' ) .
			   ":</strong>";
	$groups  = get_post_meta( get_the_ID(), 'uo_ld_associated_groups', true );
	if ( is_array( $groups ) ) {
		foreach ( $groups as $group_id ) {
			if ( $group_id != null && $group_id > 0 ) {
				$content .= "<br /><a href='" . get_permalink( $group_id ) . "'>" . get_the_title( $group_id ) . "</a>";
			}
		}
	}

	$content .= "</small></span>";
	if ( ! empty( $groups ) ) {
		echo $content;
	}
}

//remove repetation of private twice in private forum titles
add_filter( 'bbp_get_forum_title', 'uo_wdm_title', 10, 2 );
function uo_wdm_title( $title, $forum_id ) {
	return str_replace( "Private:", "", $title );
}

// Assign participant forum role to new students
add_action( 'learndash_update_course_access', 'uo_ld_bbp_assign_role', 10, 4 );
function uo_ld_bbp_assign_role( $user_id, $course_id, $access_list, $remove ) {
	if ( true === $remove ) {
		return;
	}

	$role = bbp_get_user_role( $user_id );
	if ( empty( $role ) || false === $role || 'bbp_spectator' === $role ) {
		bbp_set_user_role( $user_id, 'bbp_participant' );
	}
}

/**
 * @return array
 */
function get_user_forum_group_access() {

	global $wpdb;
	// All group forum associations
	$logged_put_group_forums = $wpdb->get_results( "Select post_id as forum_id FROM $wpdb->postmeta WHERE meta_key = 'uo_ld_allow_forum_view' AND meta_value = '1'" );

	// All users forums
	$user_forums = [];

	foreach ( $logged_put_group_forums as $data ) {
		$forum_id = $data->forum_id;

		if ( isset( $user_forums[ $forum_id ] ) ) {
			continue;
		}

		$user_forums[ $forum_id ] = 0;
	}

	if ( is_user_logged_in() ) {
		// List of all groups a user is in
		$user_groups = learndash_get_users_group_ids( get_current_user_id() );

		global $wpdb;
		// All group forum associations
		$group_forums = $wpdb->get_results( "Select post_id as group_id, meta_value as forum_id FROM $wpdb->postmeta WHERE meta_key LIKE 'uo_ld_associated_forum%'" );

		$forum_groups = [];

		// Loop through group forum ids
		foreach ( $group_forums as $data ) {

			$forum_id = $data->forum_id;
			$group_id = $data->group_id;

			// Check if the user has already been given access to the forum
			if ( isset( $user_forums[ $forum_id ] ) && 0 !== $user_forums[ $forum_id ]) {
				continue;
			}

			// Check if the user needs to have access to all the groups associated to the forum to have access or any single one
			$limit_post_access = get_post_meta( $forum_id, 'uo_ld_post_limit_access', true );

			// The the user is in any group associated with the forum then they are allowed to access it
			if ( empty( $limit_post_access ) || 'any' === $limit_post_access ) {

				// Check if the user has access to the group
				if ( in_array( $group_id, $user_groups ) ) {
					$user_forums[ $forum_id ] = $group_id;
				}
			} else {
				// The user must have access to all groups associated to the forum to have access
				// Lets store them all first by  fully running through all forum data and then loop through to check if the user has all groups
				$forum_groups[ $forum_id ][] = $group_id;
			}
		}

		// Loop through all forums that need users to have access to all groups before access is given
		foreach ( $forum_groups as $forum_id => $forum_groups ) {

			$has_all_forum_groups = ! array_diff( $forum_groups, $user_groups );

			// Does the user have all the needed groups
			if ( $has_all_forum_groups ) {
				// The the user is in any group associated with the forum then they are allowed to access it
				$user_forums[ $forum_id ] = $group_id;
			}
		}

	}

	return $user_forums;
}