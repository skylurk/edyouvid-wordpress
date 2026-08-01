<?php

namespace uncanny_learndash_codes;

use UncannyOwl\Codes\UsageReports\Reporting_Schedule;

// Initialize the reporting scheduler after all plugins are fully loaded
add_action(
	'plugins_loaded',
	function () {

		// Load the plugin-specific report class
		require_once dirname( UO_CODES_FILE ) . '/src/usage-reports/codes-report.php';

		$reporting_enabled = true;

		// Optionally disable reporting by setting UNCANNY_LEARNDASH_CODES_REPORTING to false
		if ( defined( 'UNCANNY_LEARNDASH_CODES_REPORTING' ) ) {
			$reporting_enabled = UNCANNY_LEARNDASH_CODES_REPORTING;
		}

		new Reporting_Schedule(
			'Uncanny Codes',
			$reporting_enabled,
			new CodesReport()
		);
	}
);
