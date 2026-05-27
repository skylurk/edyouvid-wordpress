<?php

namespace uncanny_learndash_codes;

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>
<div class="wrap uo-ulc-admin">
	<div class="ulc">
		<?php

		// Add admin header and tabs.
		$tab_active = 'uncanny-learndash-codes-cancel';
		require Config::get_template( 'admin-header.php' );
		?>

		<div class="ulc__admin-content">
			<h2></h2> <!-- LearnDash notice will be shown here -->

			<form method="post" action=""
				  id="uncanny-learndash-cancel-codes-form"
				  enctype="multipart/form-data">
				<input type="hidden" name="_wp_http_referer"
					   value="<?php echo admin_url( 'admin.php?page=uncanny-learndash-codes-cancel&saved=true' ); ?>"/>
				<input type="hidden" name="_cancel_codes_wpnonce"
					   value="<?php echo wp_create_nonce( Config::get_project_name() ); ?>"/>

				<!-- Cancel codes page -->
				<div class="uo-admin-section">
					<div class="uo-admin-header">
						<div
							class="uo-admin-title"><?php esc_html_e( 'Upload and cancel codes via CSV', 'uncanny-learndash-codes' ); ?></div>
					</div>
					<div class="uo-admin-block">
						<div class="uo-admin-form">

							<div class="uo-admin-field">
								<label class="uo-text">
					<span
						class="uo-admin-label"><?php esc_html_e( 'Upload file here', 'uncanny-learndash-codes' ); ?></span>
									<br/>
									<input type="file" required="required"
										   class="uo-admin-input"
										   value=""
										   name="cancel_codes_csv"
										   id="cancel_codes_csv"/>
								</label>
							</div>

							<div class="uo-admin-field">
								<input type="submit" name="submit_csv"
									   id="submit_csv"
									   class="uo-admin-form-submit"
									   value="<?php esc_html_e( 'Upload and cancel codes', 'uncanny-learndash-codes' ); ?>">
							</div>
						</div>
					</div>
				</div>

			</form>

		</div>
	</div>
</div>


