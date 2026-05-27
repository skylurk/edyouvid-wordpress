<?php

// Exit if accessed directly.
if ( ! defined( 'UO_ABS_PATH' ) ) {
	exit;
}

// Check if Gutenberg exists
if ( ! function_exists( 'register_block_type' ) ) {
	return;
}
// Register Blocks
add_action(
	'init',
	function () {

		if ( ! uo_tincanny_block_dependency_check() ) {
			return;
		}

		require_once dirname( __FILE__ ) . '/src/tincanny-content/block.php';
		require_once dirname( __FILE__ ) . '/src/tincanny-course-user-reports/block.php';
		require_once dirname( __FILE__ ) . '/src/tincanny-individual-quiz-report/block.php';
		require_once dirname( __FILE__ ) . '/src/tincanny-group-quiz-report/block.php';
		require_once dirname( __FILE__ ) . '/src/tincanny-lesson-report/block.php';
		require_once dirname( __FILE__ ) . '/src/tincanny-topic-report/block.php';
	}
);


// Enqueue Gutenberg block assets for backend editor.
add_action(
	'enqueue_block_editor_assets',
	function () {

		if ( ! uo_tincanny_block_dependency_check() ) {
			return;
		}

		wp_enqueue_script(
			'tclr-gutenberg-editor',
			plugins_url( 'blocks/dist/index.js', dirname( __FILE__ ) ),
			array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ),
			UNCANNY_REPORTING_VERSION,
			true
		);

		$uotc_defaults = uo_tincanny_content_block_defaults();

		// Add Tin Canny security data
		$vc_ajax = array(
			'ajaxurl'          => admin_url( 'admin-ajax.php' ),
			'ajax_nonce'       => wp_create_nonce( 'vc-snc-data-nonce' ),
			'ajax_nonce_2'     => wp_create_nonce( 'snc-media_upload_form' ),
			'tincanny_content' => array(
				'lightbox_defaults' => array(
					'widthValue'  => $uotc_defaults['width'],
					'widthUnit'   => $uotc_defaults['width_type'],
					'heightValue' => $uotc_defaults['height'],
					'heightUnit'  => $uotc_defaults['height_type'],
				),
			),
			'i18n'             => array(
				'tinCannyContent'                      => __( 'Tin Canny Content', 'uncanny-learndash-reporting' ),
				'uploadEmbedTinCanny'                  => __( 'Upload and embed Tin Canny content such as Storyline, Captivate and iSpring files. Display the uploaded content in an iFrame, lightbox or new window.', 'uncanny-learndash-reporting' ),
				'embedTinCanny'                        => __( 'Embed Tin Canny content and display the uploaded content in an iFrame, lightbox or new window.', 'uncanny-learndash-reporting' ),

				'somethingWrongWrong'                  => __( 'Something went wrong. Please, try again', 'uncanny-learndash-reporting' ),
				'loadingLibrary'                       => __( 'Loading Library...', 'uncanny-learndash-reporting' ),
				'weDidntFindContent'                   => __( "We didn't find any content. Please, try uploading some", 'uncanny-learndash-reporting' ),
				'idColon'                              => __( 'ID:', 'uncanny-learndash-reporting' ),
				'iframe'                               => __( 'Iframe', 'uncanny-learndash-reporting' ),
				'lightbox'                             => __( 'Lightbox', 'uncanny-learndash-reporting' ),
				'page'                                 => __( 'Page', 'uncanny-learndash-reporting' ),

				'fade'                                 => __( 'Fade', 'uncanny-learndash-reporting' ),
				'zoom'                                 => __( 'Zoom', 'uncanny-learndash-reporting' ),
				'none'                                 => __( 'None', 'uncanny-learndash-reporting' ),
				'openInTheSameWindow'                  => __( 'Open in the same window', 'uncanny-learndash-reporting' ),
				'openInANewWindow'                     => __( 'Open in a new window', 'uncanny-learndash-reporting' ),
				'newTab'                               => __( 'New tab', 'uncanny-learndash-reporting' ),

				'selectFromLibrary'                    => __( 'Select from Library', 'uncanny-learndash-reporting' ),
				'selectContent'                        => __( 'Select content', 'uncanny-learndash-reporting' ),
				'searchContent'                        => __( 'Search content', 'uncanny-learndash-reporting' ),
				'noResultsFound'                       => __( 'No results found', 'uncanny-learndash-reporting' ),
				'cancel'                               => __( 'Cancel', 'uncanny-learndash-reporting' ),
				'open'                                 => __( 'Open', 'uncanny-learndash-reporting' ),

				'displayContentIn'                     => __( 'Display Content In', 'uncanny-learndash-reporting' ),
				'openWith'                             => __( 'Open with', 'uncanny-learndash-reporting' ),

				'button'                               => __( 'Button', 'uncanny-learndash-reporting' ),
				'image'                                => __( 'Image', 'uncanny-learndash-reporting' ),
				'link'                                 => __( 'Link', 'uncanny-learndash-reporting' ),

				'lightboxSettings'                     => __( 'Lightbox Settings', 'uncanny-learndash-reporting' ),

				'title'                                => __( 'Title', 'uncanny-learndash-reporting' ),
				'useGlobalHeightWidth'                 => __( 'Use global Height and Width setting.', 'uncanny-learndash-reporting' ),
				'effect'                               => __( 'Effect', 'uncanny-learndash-reporting' ),
				'iframeSettings'                       => __( 'Iframe Settings', 'uncanny-learndash-reporting' ),

				'width'                                => __( 'Width', 'uncanny-learndash-reporting' ),
				'height'                               => __( 'Height', 'uncanny-learndash-reporting' ),

				'buttonSettings'                       => __( 'Button Settings', 'uncanny-learndash-reporting' ),

				'text'                                 => __( 'Text', 'uncanny-learndash-reporting' ),
				'size'                                 => __( 'Size', 'uncanny-learndash-reporting' ),

				'small'                                => __( 'Small', 'uncanny-learndash-reporting' ),
				'normal'                               => __( 'Normal', 'uncanny-learndash-reporting' ),
				'big'                                  => __( 'Big', 'uncanny-learndash-reporting' ),

				'imageSettings'                        => __( 'Image Settings', 'uncanny-learndash-reporting' ),
				'upload'                               => __( 'Upload', 'uncanny-learndash-reporting' ),

				'mediaLibrary'                         => __( 'Media Library', 'uncanny-learndash-reporting' ),
				'linkSettings'                         => __( 'Link Settings', 'uncanny-learndash-reporting' ),
				'pageSettings'                         => __( 'Page Settings', 'uncanny-learndash-reporting' ),

				'openIn'                               => __( 'Open in', 'uncanny-learndash-reporting' ),

				'sameWindow'                           => __( 'Same window', 'uncanny-learndash-reporting' ),
				'newWindow'                            => __( 'New window', 'uncanny-learndash-reporting' ),

				'groupQuizReportSettings'              => __( 'Group Quiz Report Settings', 'uncanny-learndash-reporting' ),

				'tinCannyCourseReportBlockTitle'       => __( 'Tin Canny Course/User Report', 'uncanny-learndash-reporting' ),
				'tinCannyCourseReportBlockDescription' => __( 'Embed Tin Canny course and user reports.', 'uncanny-learndash-reporting' ),

				'tinCannyGroupQuizReportBlockTitle'    => __( 'Tin Canny Group Quiz Report', 'uncanny-learndash-reporting' ),
				'tinCannyGroupQuizReportBlockDescription' => __( 'Embed Tin Canny report for group leaders that displays quiz results of group members.', 'uncanny-learndash-reporting' ),

				'userQuizReportURL'                    => __( 'User Quiz Report URL', 'uncanny-learndash-reporting' ),

				'tinCannyIndividualQuizReportBlockTitle' => __( 'Tin Canny Individual Quiz Report', 'uncanny-learndash-reporting' ),
				'tinCannyIndividualQuizReportBlockDescription' => __( 'Embed Tin Canny report that displays quiz results for the current user.', 'uncanny-learndash-reporting' ),

				'tinCannyLessonReportBlockTitle'       => __( 'Tin Canny Lesson Report', 'uncanny-learndash-reporting' ),
				'tinCannyLessonReportBlockDescription' => __( 'Embed a LearnDash lesson completion report.', 'uncanny-learndash-reporting' ),

				'tinCannyTopicReportBlockTitle'        => __( 'Tin Canny Topic Report', 'uncanny-learndash-reporting' ),
				'tinCannyTopicReportBlockDescription'  => __( 'Embed a LearnDash topic completion report.', 'uncanny-learndash-reporting' ),
			),
		);

		wp_localize_script( 'tclr-gutenberg-editor', 'vc_snc_data_obj', $vc_ajax );

		wp_enqueue_style(
			'tclr-gutenberg-editor',
			plugins_url( 'blocks/dist/index.css', dirname( __FILE__ ) ),
			array( 'wp-edit-blocks' ),
			UNCANNY_REPORTING_VERSION
		);
	}
);

if ( version_compare( get_bloginfo( 'version' ), '5.8', '<' ) ) {
	// Legacy filter
	// Create custom block category
	add_filter( 'block_categories', 'uo_tincanny_block_categories', 10, 2 );
} else {
	// Create custom block category
	add_filter( 'block_categories_all', 'uo_tincanny_block_categories', 10, 2 );
}

// Create custom block category
/**
 * @param $categories
 * @param $post
 *
 * @return array
 */
function uo_tincanny_block_categories( $categories, $post ) {

	if ( ! uo_tincanny_block_dependency_check() ) {
		return $categories;
	}

	return array_merge(
		$categories,
		array(
			array(
				'slug'  => 'uncanny-learndash-reporting',
				'title' => __( 'Tin Canny Reporting for LearnDash', 'uncanny-learndash-reporting' ),
			),
		)
	);
}

/**
 * Check if Learndash is installed and active
 *
 * @return bool
 */
function uo_tincanny_block_dependency_check() {
	return defined( 'LEARNDASH_VERSION' );
}

/**
 * Get Tin Canny default settings
 *
 * @return array
 */
function uo_tincanny_content_block_defaults() {
	$defaults = array(
		'height'      => 90,
		'height_type' => 'vh',
		'width'       => 90,
		'width_type'  => 'vw',
	);
	$settings = get_option( 'storyline-and-captivate', $defaults );
	$settings = wp_parse_args( $settings, $defaults );
	return array(
		'height'      => empty( $settings['height'] ) ? $defaults['height'] : $settings['height'],
		'height_type' => empty( $settings['height_type'] ) ? $defaults['height_type'] : $settings['height_type'],
		'width'       => empty( $settings['width'] ) ? $defaults['width'] : $settings['width'],
		'width_type'  => empty( $settings['width_type'] ) ? $defaults['width_type'] : $settings['width_type'],
	);
}
