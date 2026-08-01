<?php

/**
 * Download certificates in bulk
 */
register_block_type( 'uncanny-toolkit-pro/download-certificates-bulk', [
	'attributes'      => array(),
	'render_callback' => 'render_block_download_certificates_bulk'
] );

function render_block_download_certificates_bulk( $attributes ) {
	// Start output
	ob_start();

	// Check if the class exists
	if ( class_exists( '\uncanny_pro_toolkit\DownloadCertificatesInBulk' ) ) {
		echo \uncanny_pro_toolkit\DownloadCertificatesInBulk::shortcode_uo_download_certificates_in_bulk();
	}

	// Get output
	$output = ob_get_clean();

	// Return output
	return $output;
}
