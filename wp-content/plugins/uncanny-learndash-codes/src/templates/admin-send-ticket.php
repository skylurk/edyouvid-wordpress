<?php

namespace uncanny_learndash_codes;

if ( ! defined( 'WPINC' ) ) {
	die;
}

$name  = '';
$email = '';

if ( $license ) {
	$json = wp_remote_get( 'https://www.uncannyowl.com/wp-json/uncanny-rest-api/v1/license/' . $license . '?wpnonce=' . wp_create_nonce( time() ) );

	if ( ! is_wp_error( $json ) ) {
		if ( 200 === wp_remote_retrieve_response_code( $json ) ) {
			$data = json_decode( $json['body'], true );

			if ( $data ) {
				$name  = $data['name'];
				$email = $data['email'];
			}
		}
	}
}

ob_start();
include Config::get_template( 'admin-siteinfo.php' );
$installation_information = ob_get_clean();

?>

<div class="wrap uncannyowl-default-design">
	<div class="ulc">

		<?php

		// Add admin header and tabs.
		$tab_active = 'uncanny-codes-kb';
		include Config::get_template( 'admin-header.php' );

		?>

		<div class="section">

			<?php if ( $license_is_active ) { ?>

				<?php if ( isset( $_GET['sent'] ) ) { ?>

					<div id="message" class="updated uncannyowl-alert uncannyowl-alert-warning uncannyowl-alert--dismissible">
						<button  type="button" class="uncannyowl-alert-close" aria-label="Close alert">&times;</button>
					  	<?php esc_html_e( 'Your ticket has been created. Someone at Uncanny Owl will contact you regarding your issue.', 'uncanny-learndash-codes' ); ?>
					 </div>

				<?php } ?>

				<div class="uncannyowl-core">
					<div class="uncannyowl-flex uncannyowl-flex-wrap">
						<div class="uncannyowl-flex-0 uncannyowl-w-33">
							<h2>
								<?php esc_html_e( 'Submit a Ticket', 'uncanny-learndash-codes' ); ?>
							</h2>
							<div class="uncannyowl-card">
								<div class="uncannyowl-card-body">
									<form name="uncanny-help" method="POST" action="<?php echo admin_url( 'admin.php' ); ?>">
										<?php wp_nonce_field( 'uncanny0w1', 'ulc-send-ticket' ); ?>
										<textarea class="uo-send-ticket__hidden-field"
										  name="siteinfo"><?php echo $installation_information; ?></textarea>

										  <input type="hidden" value="uncanny-codes-kb&send-ticket=true" name="page"/>
										<div class="uncannyowl-form-field p-0">
											<label for="uo-fullname">
												<?php esc_html_e( 'Full Name', 'uncanny-learndash-codes' ); ?>
											</label>
											<input required name="fullname" id="uo-fullname" type="text"
												class="uncannyowl-input"
												value="<?php echo $name; ?>">
										</div>  
										<div class="uncannyowl-form-field p-0">
											<label for="uo-email" >
												<?php esc_html_e( 'Email', 'uncanny-learndash-codes' ); ?>
											</label>
											<input required name="email" id="uo-email" type="email"
												class="uncannyowl-input"
												value="<?php echo $email; ?>" />
										</div>

											<div class="uncannyowl-form-field p-0">
												<label for="uo-website">
													<?php esc_html_e( 'Site URL', 'uncanny-learndash-codes' ); ?>
												</label>
												<input required name="website" id="uo-website" type="url"
													class="uncannyowl-input"
													readonly value="<?php echo get_bloginfo( 'url' ); ?>">
											</div>

											<div class="uncannyowl-form-field p-0">
												<label for="license_key">
													<?php esc_html_e( 'License Key', 'uncanny-learndash-codes' ); ?>
												</label>
												<input required name="license_key" id="license_key" type="text"
													class="uncannyowl-input"
													readonly value="<?php echo $license; ?>">
											</div>

											<div class="uncannyowl-form-field p-0">
												<label for="uo-subject">
													<?php esc_html_e( 'Subject', 'uncanny-learndash-codes' ); ?>
												</label>
												<input required name="subject" id="uo-subject" type="text"
													class="uncannyowl-input"
													value="">
											</div>

											<div class="uncannyowl-form-field p-0">
												<label for="uo-message">
													<?php esc_html_e( 'Message', 'uncanny-learndash-codes' ); ?>
												</label>
												<textarea required name="message" id="uo-message"
														class="uncannyowl-textarea"></textarea>
											</div>

											<div class="uncannyowl-form-field p-0">
												<div class="uncannyowl-checkbox mb-0">
													<label class="uncannyowl-checkbox-label mb-0">
														<input type="checkbox" value="yes" name="site-data" checked="checked">
														<?php esc_html_e( 'Send site data', 'uncanny-learndash-codes' ); ?>
													</label>
												</div>	 
											</div>

											<div class="uncannyowl-form-field p-0">
												<p class="mt-0">
													<?php echo __( 'Emails must be enabled on your site to create a ticket using this form. If you don’t receive a confirmation email shortly after submitting this form, please log the ticket through your <a href="https://www.uncannyowl.com/my-account/submit-a-request/" target="_blank" rel="noreferrer">My Account</a> page.', 'uncanny-learndash-codes' ); ?>
												</p>
												<button type="submit" class="uncannyowl-btn uncannyowl-btn--primary">
													<?php esc_html_e( 'Create ticket', 'uncanny-learndash-codes' ); ?>
												</button>
											</div>
									</form>
								</div>
								 
							</div> 
						</div>
						<div class="uo-send-ticket__data uncannyowl-w-66 uncannyowl-flex-initial">
							 <h2>
								<?php esc_html_e( 'Site Data', 'uncanny-learndash-codes' ); ?>
							 </h2>

							<?php echo $installation_information; ?>
						</div>
					</div>
				</div>

			<?php } ?>

		</div>
	</div>
</div>
