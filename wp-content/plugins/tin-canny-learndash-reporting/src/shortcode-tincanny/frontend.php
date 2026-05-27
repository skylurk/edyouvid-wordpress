<div class="tclr wrap <?php use uncanny_learndash_reporting\TinCannyShortcode;

echo esc_attr( $css_classes[0] ); ?>" id="tincanny-reporting"
	 data-context="<?php echo esc_attr( $context ); ?>">
	<div id="ld_course_info" class="uo-tclr-admin uo-admin-reporting">

		<?php
		$path = dirname(UO_REPORTING_FILE) . '/src/reporting/learndash';
		do_action( 'tincanny_reporting_after_opening_wrapper' );

		// Loads tabs
		\uncanny_learndash_reporting\TinCannyShortcode::add_header_and_tabs();

		// Pre loads learndash labels etc.
		\uncanny_learndash_reporting\TinCannyShortcode::tincanny_reporting_wrapper_ld_course_info();

		do_action( 'tincanny_reporting_wrapper_before_begin' );

		?>

		<div class="uo-tclr-admin__content">

			<h3 id="failed-response"></h3>

			<?php
			// Loads the groups dropdown bar just under tabs on course and user reports
			include $path . '/templates/groups-drop-down.php';
			?>

			<?php do_action( 'tincanny_reporting_wrapper_after_begin' ); ?>

			<section id="first-tab-group" class="uo-admin-reporting-tabgroup">
				<?php

				do_action( 'tincanny_reporting_before_content' );

				$tab = ultc_filter_has_var('tab') ? ultc_filter_input('tab') : 'courseReportTab';

				if ( 'courseReportTab' === $tab ) {
					include $path . '/templates/course-report-tab.php';
				}

				if ( 'uncanny-tincanny-user-report' === $tab ) {
					include $path . '/templates/user-report-tab.php';
				}

				if ( 'uncanny-tincanny-tin-can-report' === $tab ) {
					TinCannyShortcode::tincan_report_page();
				}

				if ( 'uncanny-tincanny-xapi-quiz-report' === $tab ) {
					TinCannyShortcode::xapi_report_page();
				}

//				$template_data     = \uncanny_learndash_reporting\TinCannyShortcode::$template_data;
//				$tincanny_settings = \TINCANNYSNC\Admin\Options::get_options();
//
//				$course_tab = true;
//				$user_tab   = false;
//
//				if ( ultc_filter_has_var( 'page' ) && 'uncanny-tincanny-user-report' === ultc_get_filter_var( 'page' ) ) {
//					$course_tab = false;
//					$user_tab   = true;
//				}
//
//				if ( $course_tab ) {
//					include $path . '/templates/course-report-tab.php';
//				}
//
//				if ( $user_tab ) {
//					include $path . '/templates/user-report-tab.php';
//				}

				do_action( 'tincanny_reporting_content' );

				do_action( 'tincanny_reporting_after_content' );
				?>
			</section>

			<?php do_action( 'tincanny_reporting_wrapper_before_end' ); ?>

		</div>

		<?php do_action( 'tincanny_reporting_wrapper_after_end' ); ?>

	</div>
</div>
