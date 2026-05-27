<div class="tclr wrap <?php echo esc_attr( $css_classes[0] ); ?>" id="tincanny-reporting"
	 data-context="<?php echo esc_attr( $context ); ?>">
	<div id="ld_course_info" class="uo-tclr-admin uo-admin-reporting">

		<?php
		do_action( 'tincanny_reporting_after_opening_wrapper' );

		\uncanny_learndash_reporting\TinCannyShortcode::add_header_and_tabs();

		\uncanny_learndash_reporting\TinCannyShortcode::tincanny_reporting_wrapper_ld_course_info();

		do_action( 'tincanny_reporting_wrapper_before_begin' );

		?>

		<div class="uo-tclr-admin__content">

			<h3 id="failed-response"></h3>

			<?php
			include __DIR__ . '/groups-drop-down.php';
			?>

			<?php do_action( 'tincanny_reporting_wrapper_after_begin' ); ?>

			<section id="first-tab-group" class="uo-admin-reporting-tabgroup">
				<?php

				do_action( 'tincanny_reporting_before_content' );

				$template_data     = \uncanny_learndash_reporting\TinCannyShortcode::$template_data;
				$tincanny_settings = \TINCANNYSNC\Admin\Options::get_options();

				$course_tab = true;
				$user_tab   = false;

				if ( 'uncanny-tincanny-user-report' === \uncanny_learndash_reporting\TinCannyShortcode::$current_report_tab ) {
					$course_tab = false;
					$user_tab   = true;
				}

				if ( $course_tab ) {
					include __DIR__ . '/course-report-tab.php';
				}

				if ( $user_tab ) {
					include __DIR__ . '/user-report-tab.php';
				}

				do_action( 'tincanny_reporting_content' );

				do_action( 'tincanny_reporting_after_content' );
				?>
			</section>

			<?php do_action( 'tincanny_reporting_wrapper_before_end' ); ?>

		</div>

		<?php do_action( 'tincanny_reporting_wrapper_after_end' ); ?>

	</div>
</div>
