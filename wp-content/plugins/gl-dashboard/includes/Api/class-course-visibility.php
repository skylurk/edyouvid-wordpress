<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GLD_Api_CourseVisibility {

	public function register_routes(): void {
		register_rest_route( 'gl-dashboard/v1', '/groups/(?P<id>\d+)/course-visibility', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_visibility' ),
				'permission_callback' => array( 'GLD_Access', 'rest_permission' ),
				'args'                => array(
					'id' => array( 'validate_callback' => function( $v ) { return is_numeric( $v ); } ),
				),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'save_visibility' ),
				'permission_callback' => array( 'GLD_Access', 'rest_permission' ),
				'args'                => array(
					'id'                => array( 'validate_callback' => function( $v ) { return is_numeric( $v ); } ),
					'hidden_course_ids' => array( 'required' => true ),
				),
			),
		) );
	}

	public function get_visibility( WP_REST_Request $request ): WP_REST_Response {
		$group_id = (int) $request['id'];

		if ( ! GLD_Access::leads_group( $group_id ) ) {
			return new WP_REST_Response( array( 'message' => 'Forbidden' ), 403 );
		}

		$course_ids = learndash_group_enrolled_courses( $group_id );
		$hidden     = GLD_Course_Visibility::get_hidden_ids_for_group( $group_id );

		$courses = array();
		foreach ( $course_ids as $course_id ) {
			$courses[] = array(
				'id'     => (int) $course_id,
				'title'  => get_the_title( $course_id ),
				'hidden' => in_array( (int) $course_id, $hidden, true ),
			);
		}

		return new WP_REST_Response( $courses, 200 );
	}

	public function save_visibility( WP_REST_Request $request ): WP_REST_Response {
		$group_id = (int) $request['id'];

		if ( ! GLD_Access::leads_group( $group_id ) ) {
			return new WP_REST_Response( array( 'message' => 'Forbidden' ), 403 );
		}

		$enrolled_ids = array_map( 'intval', learndash_group_enrolled_courses( $group_id ) );
		$requested    = array_map( 'intval', (array) $request['hidden_course_ids'] );
		$hidden_ids   = array_values( array_intersect( $requested, $enrolled_ids ) );

		GLD_Course_Visibility::set_hidden_ids_for_group( $group_id, $hidden_ids );

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}
}
