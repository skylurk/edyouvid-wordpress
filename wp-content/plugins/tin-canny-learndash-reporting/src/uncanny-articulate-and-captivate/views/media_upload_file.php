<div id="snc-media_upload_file_wrap" class="wrap snc_TB">
	<div class="title">
		<?php esc_html_e( 'Upload File', 'uncanny-learndash-reporting' ); ?>
	</div>

	<div class="clear"></div>

	<form enctype="multipart/form-data" id="snc-media_upload_file_form" action="<?php echo esc_attr( admin_url( 'admin-ajax.php' ) ); ?>" method="POST">
		<input type="hidden" name="action" value="SnC_Media_Upload" />
		<input type="hidden" name="security" value="<?php echo esc_attr( wp_create_nonce( 'snc-media_upload_form' ) ); ?>" />
		<?php if ( ultc_filter_has_var( 'content_id' ) ) { ?>
			<input type="hidden" id="content_id" name="content_id" value="<?php echo esc_attr( ultc_filter_input( 'content_id' ) ); ?>" />
		<?php } ?>
		<?php if ( ultc_filter_has_var( 'no_tab' ) ) { ?>
			<input type="hidden" id="no_tab" name="no_tab" value="no_tab" />
		<?php } ?>
		<?php if ( ultc_filter_has_var( 'no_refresh' ) ) { ?>
			<input type="hidden" id="no_refresh" name="no_refresh" value="no_refresh" />
			<input type="hidden" id="ele_id" name="ele_id" value="<?php echo esc_attr( ultc_get_filter_var( 'item_id', '' ) ); ?>" />

		<?php } ?>
		<input type="hidden" name="extension" id="snc-extension" value="" />
		<input type="hidden" name="max_file_size" id="snc-max_file_size" value="<?php echo esc_attr( wp_max_upload_size() ); ?>" />

		<p class="description">
			<?php
			echo wp_kses(
				__( 'Please upload a zip file published from one of <a href="https://www.uncannyowl.com/knowledge-base/authoring-tools-supported/" target="_blank">the supported authoring tools.</a>', 'uncanny-learndash-reporting' ),
				array(
					'a' => array(
						'href'   => array(),
						'target' => array(),
					),
				)
			);
			?>
		</p>

		<div class="clear"></div>

		<!-- Button Wrapper -->
		<section id="snc-media_upload_button_wrap" class="snc-file_upload_button_wrapper">
			<button id="snc-upload_button" type="button" class="file_upload_button">
				<span class="button is-secondary">
					<span class="dashicons dashicons-upload"></span>
					<span><?php esc_html_e( 'Click to Upload', 'uncanny-learndash-reporting' ); ?></span>
				</span>
				<span class="button-description"><?php esc_html_e( 'No file selected.', 'uncanny-learndash-reporting' ); ?></span>
			</button>
			<input type="file" accept="application/zip" style="display: none;">
		</section>

		<div class="clear"></div>

		<!-- Settings Wrapper -->
		<section id="snc-media_upload_settings_wrap"/>

			<label for="snc-full_zip_upload" class="">
				<input type="checkbox" name="full_zip_upload" id="snc-full_zip_upload" value="1" />
				<?php esc_html_e( 'Upload entire zip file', 'uncanny-learndash-reporting' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'Choose this option for faster uploads; it may not work in all environments or with large file sizes.', 'uncanny-learndash-reporting' ); ?>
				<span id="snc-full_zip_upload__max" style="display: none; margin-top:10px;">
					<?php printf(
						/* translators: %s: Maximum allowed file size. */
						__( 'Maximum upload file size: %s.' ),
						esc_html( size_format( wp_max_upload_size() ) )
					); ?>
				</span>
			</p>
			
		</section>

		<!-- File Upload Component Wrapper -->
		<section id="snc-media_upload_tincanny-uploader-wrapper"></section>

	</form>
</div>
