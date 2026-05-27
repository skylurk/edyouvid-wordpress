<?php

namespace uncanny_ceu;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Blocks
 * @package uncanny_ceu
 */
class Blocks {

	/**
	 * Blocks constructor.
	 *
	 */
	public function __construct() {

		// Check if Gutenberg exists
		if ( function_exists( 'register_block_type' ) ) {

			// Register Blocks
			add_action( 'init', function () {
				require_once( dirname( __FILE__ ) . '/src/ceu-available-credits/block.php' );
				require_once( dirname( __FILE__ ) . '/src/ceu-certificate-trigger-description/block.php' );
				require_once( dirname( __FILE__ ) . '/src/ceu-credits-remaining/block.php' );
				require_once( dirname( __FILE__ ) . '/src/ceu-earned-credits/block.php' );
				require_once( dirname( __FILE__ ) . '/src/ceu-course-report/block.php' );
				require_once( dirname( __FILE__ ) . '/src/ceu-total-credits/block.php' );
				require_once( dirname( __FILE__ ) . '/src/ceu-total-days-remaining/block.php' );
				require_once( dirname( __FILE__ ) . '/src/ceu-total-rollover-credits/block.php' );
			} );

			// Enqueue Gutenberg block assets for both frontend + backend
			// add_action( 'enqueue_block_assets', function () {
			// 	wp_enqueue_style(
			// 		'ucec-gutenberg-blocks',
			// 		plugins_url( 'blocks/dist/blocks.style.build.css', dirname( __FILE__ ) ),
			// 		[],
			// 		Utilities::get_version()
			// 	);
			// } );

			// Enqueue Gutenberg block assets for backend editor
			add_action( 'enqueue_block_editor_assets', function () {
				wp_enqueue_script(
					'ucec-gutenberg-editor',
					plugins_url( 'blocks/dist/index.js', dirname( __FILE__ ) ),
					[ 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ],
					Utilities::get_version(),
					true
				);

				wp_enqueue_style(
					'ucec-gutenberg-editor',
					plugins_url( 'blocks/dist/index.css', dirname( __FILE__ ) ),
					[ 'wp-edit-blocks' ],
					Utilities::get_version()
				);
			} );

			if ( version_compare( get_bloginfo( 'version' ), '5.8', '<' ) ) {
				// Legacy filter
				// Create custom block category
				add_filter( 'block_categories', array( $this, 'uo_ceu_block_categories' ), 10, 2 );
			} else {
				// Create custom block category
				add_filter( 'block_categories_all', array( $this, 'uo_ceu_block_categories' ), 10, 2 );
			}
		}
	}

	/**
	 * @param $categories
	 * @param $post
	 *
	 * @return array
	 */
	public function uo_ceu_block_categories( $categories, $post ) {
		return array_merge(
			$categories,
			array(
				array(
					'slug'  => 'uncanny-ceu',
					'title' => __( 'Uncanny Continuing Education Credits', 'uncanny-ceu' ),
				),
			)
		);
	}
}
