<?php

namespace uncanny_pro_toolkit;

use LearnDash_Certificate_Builder\Component\PDF;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class CertificateBuilder
 *
 * @package uncanny_pro_toolkit
 */
class CertificateBuilder {

	/**
	 * path
	 *
	 * @var string
	 */
	public $path;

	/**
	 * Entity that we are processing. Course or Quiz.
	 *
	 * @var string
	 */
	public $entity;

	/**
	 * Arguments for the certificate.
	 *
	 * @var array
	 */
	public $args;

	/**
	 * Parameters of the quiz.
	 *
	 * @var array
	 */
	public $parameters;
	/**
	 * @var int|mixed
	 */
	public $current_user_id;

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
	}

	/**
	 * Checks if the certificate was created with the builder
	 *
	 * @param mixed $post_id
	 *
	 * @return bool
	 */
	public function created_with_builder( $post_id ) {

		// Check post meta flag.
		$built_with_block = absint( get_post_meta( $post_id, 'ld_certificate_builder_on', true ) ) === 1 ? true : false;
		if ( $built_with_block ) {
			// Check if content contains the course builder block - Ticket #57621
			$built_with_block = $this->content_block_scan( parse_blocks( get_post_field( 'post_content', $post_id ) ) );
		}

		return $built_with_block;
	}

	/**
	 * Checks if Certificate Builder block is in the content
	 *
	 * @param array $blocks
	 *
	 * @return bool
	 */
	public function content_block_scan( $blocks ) {
		$block_code       = 'learndash/ld-certificate-builder';
		$block_is_on_page = false;

		foreach ( $blocks as $block ) {
			if ( $block_code === $block['blockName'] ) {
				$block_is_on_page = true;
			}
			if ( ! $block_is_on_page && ! empty( $block['innerBlocks'] ) ) {
				$block_is_on_page = $this->detect_inner_block( $block['innerBlocks'] );
			}
		}

		return $block_is_on_page;
	}



	/**
	 * CHecks if the builder plugin is active
	 *
	 * @return bool
	 */
	public function builder_active() {
		return class_exists( 'LearnDash_Certificate_Builder\Controller\Certificate_Builder' );
	}

	/**
	 * Handles PDF generation for email notifications
	 *
	 * @param array $args
	 * @param string $entity - course or quiz
	 *
	 * @return string|array path to the file created
	 */
	public function generate_pdf( $args, $entity ) {

		$this->args       = $args;
		$this->parameters = $args['parameters'];
		$this->entity     = $entity;
		$this->path       = $args['save_path'] . $args['file_name'] . '.pdf';

		if ( $this->builder_active() ) {

			$entity_id = $this->get_post_id( $entity );

			$cert_id = intval( $args['certificate_post'] );

			$certificate_post = get_post( $cert_id );

			// Swap the data for some LD functions
			$this->filters( 'add' );

			$blocks = parse_blocks( $certificate_post->post_content );

			$blocks = $this->add_entity_ids( $blocks, $entity, $entity_id );

			$LD_PDF = new PDF();

			$LD_PDF->serve( $blocks, $cert_id, $entity_id );

			// Clean up the filters
			$this->filters( 'remove' );

			return $this->path;
		}

		return array(
			'error' => esc_html__( 'The certificate could not be attached because the Certificate Builder is not active. Please contact the site administrator.', 'uncanny-pro-toolkit' ),
		);
	}

	/**
	 * Return Quiz or Course ID, depending on what was requested.
	 *
	 * @param mixed $entity
	 *
	 * @return void
	 */
	public function get_post_id( $entity ) {

		switch ( $entity ) {
			case 'course':
				$post_id = $this->parameters['course-id'];
				break;
			case 'quiz':
				$post_id = isset( $this->parameters['quiz-id'] ) ? $this->parameters['quiz-id'] : $this->args['quiz_id'];
				break;
			case 'group':
				$post_id = $this->parameters['group-id'];
				break;
			default:
				$post_id = 0;
				break;
		}

		return $post_id;
	}

	/**
	 * Add/Remove filters.
	 *
	 * @param string $add
	 *
	 * @return void
	 */
	public function filters( $add ) {
		if ( 'add' === $add ) {

			// Store the current use ID in case the action is performed by an admin.
			if ( ! isset( $this->parameters['bulk_generator'] ) ) {
				$this->current_user_id = get_current_user_id();
			} else {
				$this->current_user_id = $this->parameters['userID'];
			}

			// Log the certificate user in.
			wp_set_current_user( $this->parameters['userID'] );

			add_filter( 'learndash_shortcode_atts', array( $this, 'inject_shortcode_atts' ), 1, 2 );

			if ( 'quiz' === $this->entity ) {
				// Mock the current quiz results in the next user meta query.
				add_filter( 'get_user_metadata', array( $this, 'inject_quiz_results' ), 1, 5 );
			}

			// Swap the path and destintation in the next PDF generation.
			add_filter( 'learndash_certificate_builder_pdf_name', array( $this, 'file_path' ), 1, 3 );
			add_filter( 'learndash_certificate_builder_pdf_output_mode', array( $this, 'destination' ), 1, 3 );

			return;
		}

		// Else, remove all the filters.
		if ( 'quiz' === $this->entity ) {
			remove_filter( 'get_user_metadata', array( $this, 'inject_quiz_results' ), 1 );
		}

		remove_filter( 'learndash_shortcode_atts', array( $this, 'inject_shortcode_atts' ), 1 );
		remove_filter( 'learndash_certificate_builder_pdf_name', array( $this, 'file_path' ), 1 );
		remove_filter( 'learndash_certificate_builder_pdf_output_mode', array( $this, 'destination' ), 1 );

		// Log the user back.
		wp_set_current_user( $this->current_user_id );

	}

	/**
	 * @return array
	 */
	public function mock_quizinfo() {
		return array(
			'quiz'         => $this->parameters['quiz-id'],
			'score'        => $this->parameters['points'],
			'count'        => $this->parameters['count'],
			'pass'         => 'Yes',
			'pro_quizid'   => $this->parameters['quiz-id'],
			'course'       => $this->parameters['course-id'],
			'points'       => $this->parameters['points'],
			'total_points' => $this->parameters['total-points'],
			'percentage'   => $this->parameters['result'],
			'timespent'    => $this->parameters['timespent'],
			'completed'    => $this->args['completion_time'],
			'time'         => time(),
		);
	}

	/**
	 * Inject current quiz results when the database is queried for them.
	 *
	 * @param string $value
	 * @param string $object_id
	 * @param string $meta_key
	 * @param string $single
	 * @param string $meta_type
	 *
	 * @return mixed
	 */
	public function inject_quiz_results( $value, $object_id, $meta_key, $single, $meta_type ) {

		if (
			$meta_type === 'user' &&
			$object_id === $this->parameters['userID'] &&
			$meta_key === '_sfwd-quizzes'
		) {
			$value   = array();
			$value[] = array( $this->mock_quizinfo() );

		}

		return $value;
	}

	/**
	 * Inject missing shortcode attributes.
	 *
	 * @param array $shortcode_atts
	 * @param string $shortcode_slug
	 *
	 * @return array
	 */
	public function inject_shortcode_atts( $shortcode_atts, $shortcode_slug ) {

		if ( 'quizinfo' === $shortcode_slug ) {
			$shortcode_atts['quiz'] = $this->parameters['quiz-id'];
		}

		if ( 'courseinfo' === $shortcode_slug ) {
			$shortcode_atts['course_id'] = $this->parameters['course-id'];
		}

		if ( 'groupinfo' === $shortcode_slug ) {
			$shortcode_atts['group_id'] = $this->parameters['group-id'];
		}

		return $shortcode_atts;
	}

	/**
	 * Returns file_path for mPDF
	 *
	 * @param string $path
	 * @param string $cert_id
	 * @param string $course_id
	 *
	 * @return string
	 */
	public function file_path( $path, $cert_id, $course_id ) {
		return $this->path;
	}

	/**
	 * Returns F as the destination for mPDF
	 *
	 * @param string $destination
	 * @param string $cert_id
	 * @param string $course_id
	 *
	 * @return string
	 */
	public function destination( $destination, $cert_id, $course_id ) {
		return 'F';
	}

	/**
	 * add_entity_id
	 *
	 * @param array $blocks
	 * @param string $entity - quiz or course?
	 * @param int $entity_id quiz or course id
	 *
	 * @return array $blocks
	 */
	public function add_entity_ids( $blocks, $entity, $entity_id ) {

		foreach ( $blocks as &$block ) {
			$block = $this->add_in_inner_blocks( $block, $entity, $entity_id );
		}

		return $blocks;

	}

	/**
	 * add_in_inner_blocks
	 *
	 * @param array $block
	 * @param string $entity - quiz or course?
	 * @param int $entity_id quiz or course id
	 *
	 * @return array $block
	 */
	public function add_in_inner_blocks( $block, $entity, $entity_id ) {

		if ( empty( $block['innerBlocks'] ) ) {
			return $block;
		}

		foreach ( $block['innerBlocks'] as &$inner_block ) {

			$inner_block = $this->add_in_inner_blocks( $inner_block, $entity, $entity_id );

			if ( 'learndash/ld-quizinfo' === $inner_block['blockName'] ) {
				$inner_block['attrs']['quiz_id']   = $entity_id;
				$inner_block['attrs']['user_id']   = $this->current_user_id;
				$inner_block['attrs']['course_id'] = isset( $this->parameters['course-id'] ) ? $this->parameters['course-id'] : 0;
			}

			if ( 'learndash/ld-groupinfo' === $inner_block['blockName'] ) {
				$inner_block['attrs']['group_id']  = $entity_id;
				$inner_block['attrs']['user_id']   = $this->current_user_id;
				$inner_block['attrs']['course_id'] = isset( $this->parameters['course-id'] ) ? $this->parameters['course-id'] : 0;
			}
		}

		return $block;

	}

}
