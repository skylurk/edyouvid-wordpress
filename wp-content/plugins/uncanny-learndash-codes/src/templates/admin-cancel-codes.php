<?php

namespace uncanny_learndash_codes;

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>
<div class="wrap uo-ulc-admin uncannyowl-default-design">
	<div class="ulc">
		<?php

		// Add admin header and tabs.
		$tab_active = 'uncanny-learndash-codes-cancel';
		require Config::get_template( 'admin-header.php' );
		?>
		<div class="uncannyowl-card">
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
						<div class="uncannyowl-card-header">
							<div class="uo-admin-header">
								<h1 class="wp-heading-inline"><?php esc_html_e( 'Cancel Codes', 'uncanny-learndash-codes' ); ?></h1>
							</div>
						</div>	
						<div class="uo-admin-block">
							<div class="uo-admin-form">

								<div class="uncannyowl-form-field">
									<label for="cancel_codes_csv" class="uncannyowl-label">
										<?php esc_html_e( 'Upload a CSV file that contains the Code(s) you want to cancel.', 'uncanny-learndash-codes' ); ?>
									</label>
									<div class="uncannyowl-file-input-wrapper">
										<input type="file" 
											required="required"
											class="uncannyowl-file-input uo-admin-input"
											name="cancel_codes_csv"
											id="cancel_codes_csv"
											accept=".csv"/>
										<label for="cancel_codes_csv" class="uncannyowl-file-input-label">
											<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
												<path d="M10 4V16M10 4L6 8M10 4L14 8M4 16H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
											</svg>
											<span class="uncannyowl-file-input-text"><?php esc_html_e( 'Choose CSV file', 'uncanny-learndash-codes' ); ?></span>
										</label>
										<span class="uncannyowl-file-input-filename"></span>
									</div>
								</div>

								<div class="uo-admin-field">
									<input type="submit" name="submit_csv"
										id="submit_csv"
										class="uo-admin-form-submit uncannyowl-btn uncannyowl-btn--primary"
										value="<?php esc_html_e( 'Upload and Cancel Codes', 'uncanny-learndash-codes' ); ?>">							
								</div>
							</div>
						</div>
					</div>

				</form>

			</div>
		</div>	
	</div>
</div>


