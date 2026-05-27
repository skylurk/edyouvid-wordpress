<?php
namespace uncanny_learndash_reporting;

?>
<style>#TB_ajaxContent{width:400px!important;}</style>
<?php add_thickbox(); ?>
<div class="uo-tclr-admin wrap" id="snc_options">
	<?php

	// Add admin header and tabs
	$tab_active = 'manage-content';
	require Config::get_template( 'admin-header.php' );

	?>

	<div class="tclr__admin-content">

		<!-- Reset database -->
		<div class="uo-admin-section" id="snc-content_library_wrap">
			<input type="hidden" name="security" value="<?php echo esc_attr( wp_create_nonce( 'snc-media_enbed_form' ) ); ?>"/>
			<?php $tincan_content_table->display(); ?>
		</div>

		<div id="tclr-replace-content">
			<div class="tclr-replace-content-container">
				<div class="tclr-replace-content__title">
					<?php esc_html_e( 'Replace Content', 'uncanny-learndash-reporting' ); ?>
				</div>

				<div class="tclr-replace-content__step-1">
					<div class="tclr-replace-content__description">
						<?php esc_html_e( 'Please select one of the following options:', 'uncanny-learndash-reporting' ); ?>
					</div>

					<div class="tclr-replace-content__actions">
						<div class="tclr-btn tclr-btn--secondary tclr-replace-content__task-btn" data-task="remove-bookmark">
							<?php esc_html_e( 'Delete bookmark (resume) data only', 'uncanny-learndash-reporting' ); ?>
						</div>

						<div class="tclr-btn tclr-btn--secondary tclr-replace-content__task-btn" data-task="remove-all-data">
							<?php esc_html_e( 'Delete all data', 'uncanny-learndash-reporting' ); ?>
						</div>
					</div>

					<a href="" id="replace_placeholder" class="snc_replace thickbox"></a>

					<div class="tclr-replace-content__info">
						<?php
						printf(
							/* translators: %s: Delete all data link */
							__( 'Note: When replacing a module, bookmark data must be deleted or the content may not load properly. If you prefer to delete all data, including saved xAPI statements, select %s', 'uncanny-learndash-reporting' ),
							sprintf( '<em>%s</em>', esc_html__( 'Delete all data.', 'uncanny-learndash-reporting' ) )
						);
						?>
					</div>
				</div>

				<div class="tclr-replace-content__step-2">

					<div class="tclr-replace-content__warning" id="bookmark-confirmation">
						<?php
						echo sprintf(
							/* translators: %1$s: Delete bookmark data link, %2$s: Delete all data link */
							__( 'Are you sure you want to delete bookmark data? %1$s, %2$s', 'uncanny-learndash-reporting' ),
							'<strong>' . esc_html__( 'Data will be deleted immediately', 'uncanny-learndash-reporting' ) . '</strong>',
							esc_html__( 'even if you cancel the file replacement in the next step.', 'uncanny-learndash-reporting' )
						);
						?>

						<div class="tclr-replace-content__actions">
							<div class="tclr-btn tclr-btn--error" id="snc-delete-book-only">
								<?php esc_html_e( 'Delete', 'uncanny-learndash-reporting' ); ?>
							</div>

							<div class="tclr-btn tclr-btn--secondary tclr-replace-content__cancel-2-step-btn">
								<?php esc_html_e( 'Cancel', 'uncanny-learndash-reporting' ); ?>
							</div>
						</div>
					</div>

					<div class="tclr-replace-content__warning" id="all-confirmation">
						<?php
						echo sprintf(
							'%s <strong>%s</strong>, %s',
							esc_html__( 'Are you sure you want to delete all data? ', 'uncanny-learndash-reporting' ),
							esc_html__( 'Data will be deleted immediately', 'uncanny-learndash-reporting' ),
							esc_html__( 'even if you cancel the file replacement in the next step.', 'uncanny-learndash-reporting' )
						);
						?>

						<div class="tclr-replace-content__actions">
							<div class="tclr-btn tclr-btn--error" id="snc-delete-all-data">
								<?php esc_html_e( 'Delete', 'uncanny-learndash-reporting' ); ?>

							</div><div class="tclr-btn tclr-btn--secondary tclr-replace-content__cancel-2-step-btn">
								<?php esc_html_e( 'Cancel', 'uncanny-learndash-reporting' ); ?>
							</div>
						</div>
					</div>

				</div>
			</div>
		</div>
	</div>
</div>
