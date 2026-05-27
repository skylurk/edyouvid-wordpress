<?php

namespace uncanny_learndash_groups;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class learndashBreadcrumbs
 *
 * @package uncanny_learndash_groups
 */
class Blocks {

	/**
	 * Blocks constructor.
	 *
	 */
	public function __construct() {
		// Initialize everything on a late init hook, after LearnDash's text domain is loaded
		add_action( 'init', array( $this, 'initialize_blocks' ), PHP_INT_MAX );
	}

	/**
	 * Initialize blocks and related functionality
	 */
	public function initialize_blocks() {
		// Check if Gutenberg exists
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$this->register_block_classes();

		// Enqueue Gutenberg block assets for both frontend + backend
		add_action(
			'enqueue_block_assets',
			function () {
				wp_enqueue_style(
					'ulgm-gutenberg-blocks',
					plugins_url( 'blocks/dist/index.css', __DIR__ ),
					array(),
					Utilities::get_version()
				);
			}
		);

		// Enqueue Gutenberg block assets for backend editor
		add_action(
			'enqueue_block_editor_assets',
			function () {
				wp_enqueue_script(
					'ulgm-gutenberg-editor',
					plugins_url( 'blocks/dist/index.js', __DIR__ ),
					array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ),
					Utilities::get_version(),
					true
				);

				wp_enqueue_style(
					'ulgm-gutenberg-editor',
					plugins_url( 'blocks/dist/index.css', __DIR__ ),
					array( 'wp-edit-blocks' ),
					Utilities::get_version()
				);
			}
		);

		// Determine the appropriate hook based on WordPress version
		$block_categories_hook = version_compare( get_bloginfo( 'version' ), '5.8', '<' ) ? 'block_categories' : 'block_categories_all';

		// Create custom block category
		add_filter(
			$block_categories_hook,
			array(
				$this,
				'block_categories',
			),
			10,
			2
		);
	}

	/**
	 * Register block classes.
	 *
	 * @return void
	 */
	public function register_block_classes() {
		require_once __DIR__ . '/src/groups-group-management-interface/block.php';
		require_once __DIR__ . '/src/groups-edit-group-wizard/block.php';
		require_once __DIR__ . '/src/groups-group-reports-interface/block.php';
		require_once __DIR__ . '/src/groups-group-quiz-report/block.php';
		require_once __DIR__ . '/src/groups-woocommerce-buy-courses/block.php';
		require_once __DIR__ . '/src/groups-group-essays-report/block.php';
		require_once __DIR__ . '/src/groups-group-assignments-report/block.php';
		require_once __DIR__ . '/src/groups-group-progress-report/block.php';
		require_once __DIR__ . '/src/groups-group-enrollment-key-redemption/block.php';
		require_once __DIR__ . '/src/groups-group-enrollment-key-registration/block.php';
	}

	/**
	 * @param $categories
	 * @param $post
	 *
	 * @return array
	 */
	public function block_categories( $categories, $post ) {
		return array_merge(
			$categories,
			array(
				array(
					'slug'  => 'uncanny-learndash-groups',
					'title' => 'Uncanny Groups for LearnDash',
				),
			)
		);
	}
}
