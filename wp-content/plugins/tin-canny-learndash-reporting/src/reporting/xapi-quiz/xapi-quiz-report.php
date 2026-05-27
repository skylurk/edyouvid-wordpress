<div class="tclr wrap" id="tincanny-reporting"
     data-context="">
	<div id="ld_course_info" class="uo-tclr-admin uo-admin-reporting">

		<?php
		do_action( 'tincanny_reporting_after_opening_wrapper' );

		\uncanny_learndash_reporting\TinCannyShortcode::add_header_and_tabs();

		do_action( 'tincanny_reporting_wrapper_before_begin' );

		?>

		<div class="uo-tclr-admin__content">

			<div class="uo-admin-reporting-tab-single" id="tinCanReportTab" style="display: block">
				<div id="coursesOverviewContainer">

					<?php
					if ( false === \uncanny_learndash_reporting\TinCannyShortcode::$is_independent_shortcode || is_admin() ){
						// Include Breadcrumb
						include __DIR__ . '/templates/breadcrumb.php';
					}

					$tincan_database = new \UCTINCAN\Database\Admin();

					// Include Setup
					include __DIR__ . '/templates/setup.php';

					// Include Filter
					include __DIR__ . '/templates/xapi-quiz-filter.php';
					?>

					<?php do_action( 'tincanny_reporting_wrapper_after_begin' ); ?>

					<section id="first-tab-group" class="uo-admin-reporting-tabgroup">

						<?php

						do_action( 'tincanny_reporting_before_content' );
						if ( ! ultc_filter_has_var( 'tc_filter_mode' ) ) {
							?>
							<div class="tincanny-tin-canny-error">
								<p><?php _e( 'Please select your criteria to filter the xAPI Quiz data', 'uncanny-learndash-reporting' ); ?></p>
							</div>
							<?php
						} else {
							do_action( 'tincanny_reporting_content' );

							include __DIR__ . '/templates/table.php';
						}

						do_action( 'tincanny_reporting_after_content' );
						?>
					</section>

					<?php do_action( 'tincanny_reporting_wrapper_before_end' ); ?>
				</div>
			</div>
		</div>
	</div>

	<?php do_action( 'tincanny_reporting_wrapper_after_end' ); ?>
