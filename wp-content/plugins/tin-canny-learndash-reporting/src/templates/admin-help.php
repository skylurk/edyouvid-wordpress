<?php

namespace uncanny_learndash_reporting;

if ( ! defined( 'WPINC' ) ) {
	die;
}

// Get data about the license
$license = get_option( 'uo_reporting_license_key' );
// This will be either "valid", "invalid", "expired", "disabled"
$status = get_option( 'uo_reporting_license_status' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

// Check license status
$license_is_active = 'valid' === $status;

// Check if the user wants to send a ticket
$is_send_ticket_page = ultc_filter_has_var( 'send-ticket' );

if ( $is_send_ticket_page ) {
	// Show Send a ticket
	include Config::get_template( 'admin-send-ticket.php' );
} else {
	// Show KB articles
	include Config::get_template( 'admin-kb-articles.php' );
}
