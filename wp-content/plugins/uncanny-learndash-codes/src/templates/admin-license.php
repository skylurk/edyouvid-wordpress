<?php

namespace uncanny_learndash_codes;

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>


<div class="uo-admin-section">
	<div class="ulc uncannyowl-default-design">

		<?php
		if ( ! $minimal ) {
			// Add admin header and tabs.
			$tab_active = 'uncanny-codes-license-activation';
			require Config::get_template( 'admin-header.php' );
		} else {
			?>
		<?php } ?>
		
		<div class="ulc__admin-content ulc-license <?php echo implode( ' ', $license_css_classes ); ?>">
			
			<div class="uncannyowl-license">
				<div class="uncannyowl-license-status <?php echo $license_is_active ? 'uncannyowl-license-status--active' : ''; ?>">
					<div class="uncannyowl-license-status__icon">

						<?php if ( $license_is_active ) { ?>

							<svg class="uncannyowl-license-status-icon__svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
								<path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/>
							</svg>

						<?php } else { ?>

							<svg class="uncannyowl-license-status-icon__svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 352 512">
								<path class="uncannyowl-license-status-icon__svg-times" d="M242.72 256l100.07-100.07c12.28-12.28 12.28-32.19 0-44.48l-22.24-22.24c-12.28-12.28-32.19-12.28-44.48 0L176 189.28 75.93 89.21c-12.28-12.28-32.19-12.28-44.48 0L9.21 111.45c-12.28 12.28-12.28 32.19 0 44.48L109.28 256 9.21 356.07c-12.28 12.28-12.28 32.19 0 44.48l22.24 22.24c12.28 12.28 32.2 12.28 44.48 0L176 322.72l100.07 100.07c12.28 12.28 32.2 12.28 44.48 0l22.24-22.24c12.28-12.28 12.28-32.19 0-44.48L242.72 256z"></path>
							</svg>

						<?php } ?>

					</div>
				</div>
				
				<div class="uncannyowl-license-content">
					<form class="uncannyowl-license-content-form" method="POST">
						<?php wp_nonce_field( 'uo_codes_nonce', 'uo_codes_nonce' ); ?>
						
						<div class="uncannyowl-license-content-top">
							<div class="uncannyowl-license-content-info">
								<?php if ( $license_check && ! $license_is_active ) { ?>
									<div class="uncannyowl-license-content-description">

										<?php

										switch ( $license_check ) {
											case 'valid':
												break;

											case 'empty':
												_e( 'Please enter a valid license code and click "Activate now".', 'uncanny-learndash-codes' );
												break;

											case 'expired':
												printf(
													_x(
														'Your license has expired. Please %s to get instant access to updates and support.',
														'Your license has expired. Please renew your license to get instant access to updates and support.',
														'uncanny-learndash-codes'
													),
													sprintf(
														'<a href="%s" target="_blank">%s</a>',
														'https://www.uncannyowl.com/checkout/?edd_license_key=' . $license . '&download_id=1377',
														_x(
															'renew your license',
															'Your license has expired. Please renew your license to get instant access to updates and support.',
															'uncanny-learndash-codes'
														)
													)
												);
												break;

											case 'disabled':
												printf(
													_x(
														'Your license is disabled. Please %s to get instant access to updates and support.',
														'Your license has disabled. Please renew your license to get instant access to updates and support.',
														'uncanny-learndash-codes'
													),
													sprintf(
														'<a href="%s" target="_blank">%s</a>',
														'https://www.uncannyowl.com/checkout/?edd_license_key=' . $license . '&download_id=1377',
														_x(
															'renew your license',
															'Your license has expired. Please renew your license to get instant access to updates and support.',
															'uncanny-learndash-codes'
														)
													)
												);
												break;

											case 'invalid':
											case 'inactive':
												_e( 'The license code you entered is invalid.', 'uncanny-learndash-codes' );
												break;
										}

										?>

									</div>
								<?php } ?>
								<?php if ( $license_is_active ) { ?>
									<div class="uncannyowl-license-content-title"><?php esc_attr_e( 'Your license is active', 'uncanny-learndash-codes' ); ?></div>
									<div class="uncannyowl-license-content-description"><?php esc_attr_e( 'Your license is valid and active. You have access to all premium features and updates.', 'uncanny-learndash-codes' ); ?></div>
									<?php 
										if( isset( $license_data->license_html ) ) {
											echo $license_data->license_html;
										}else{
											// Show basic license details
										?>
										<div>
											<strong><?php esc_attr_e( 'Expires', 'uncanny-learndash-codes' ); ?>:</strong> <span class="license-expires-value"><?php echo $license_data->expires; ?></span>
										</div>
										<?php
										}
									?>
								<?php } else { ?>
									<div class="uncannyowl-license-content-title"><?php esc_attr_e( 'Your license is not active', 'uncanny-learndash-codes' ); ?></div>
									<div class="uncannyowl-license-content-description"><?php esc_attr_e( 'Please enter a valid license code and click "Activate now".', 'uncanny-learndash-codes' ); ?></div>
									
									<input id="uncannyowl-license-field"
										   name="uo_codes_license_key"
										   type="password"
										   value="<?php echo esc_attr( $license ); ?>"
										   placeholder="<?php esc_attr_e( 'Enter your Uncanny Redemption Codes license key', 'uncanny-learndash-codes' ); ?>"
										   class="uncannyowl-license-field"
										   required>
								<?php } ?>
							</div>
							
							<div class="uncannyowl-license-content-faq">
								<?php if ( $license_is_active ) { ?>
									<div class="uncannyowl-license-content-title">
										<?php esc_attr_e( 'License Info', 'uncanny-learndash-codes' ); ?>
									</div>
								<?php } else { ?>
									<div class="uncannyowl-license-content-title">
										<?php esc_attr_e( 'Need help?', 'uncanny-learndash-codes' ); ?>
									</div>
								<?php } ?>
								
								<div class="uncannyowl-license-content-faq-list">
									<ul class="uncannyowl-license-content-faq-list-ul">
										<?php if ( $license_is_active ) { ?>
											<li class="uncannyowl-license-content-faq-item">
												<a href="<?php echo $buy_new_license; ?>" target="_blank">
													<?php esc_attr_e( 'Renew license', 'uncanny-learndash-codes' ); ?>
												</a>
											</li>
											<li class="uncannyowl-license-content-faq-item">
												<a href="<?php echo $knowledge_base; ?>" target="_blank">
													<?php esc_attr_e( 'Manage sites', 'uncanny-learndash-codes' ); ?>
												</a>
											</li>
											<li class="uncannyowl-license-content-faq-item">
												<a href="<?php echo $knowledge_base; ?>" target="_blank">
													<?php esc_attr_e( 'Download updates', 'uncanny-learndash-codes' ); ?>
												</a>
											</li>
										<?php } else { ?>
											<li class="uncannyowl-license-content-faq-item">
												<a href="<?php echo $where_to_get_my_license; ?>" target="_blank">
													<?php esc_attr_e( 'Where to get my license key', 'uncanny-learndash-codes' ); ?>
												</a>
											</li>
											<li class="uncannyowl-license-content-faq-item">
												<a href="<?php echo $buy_new_license; ?>" target="_blank">
													<?php esc_attr_e( 'Buy a new license', 'uncanny-learndash-codes' ); ?>
												</a>
											</li>
											<li class="uncannyowl-license-content-faq-item">
												<a href="<?php echo $knowledge_base; ?>" target="_blank">
													<?php esc_attr_e( 'Knowledge Base', 'uncanny-learndash-codes' ); ?>
												</a>
											</li>
										<?php } ?>
									</ul>
								</div>
							</div>
						</div>
						
						<div class="uncannyowl-license-content-footer">
							<?php
							if ( $license_is_active ) {
								if ( ! Boot::is_defined_license_key() ) {
									?>
									<button type="submit" name="uo_codes_license_deactivate"
											class="uncannyowl-btn uncannyowl-btn--secondary">
										<?php esc_attr_e( 'Deactivate license', 'uncanny-learndash-codes' ); ?>
									</button>
									<?php
								} else {
									_e( 'Your license is managed by your site administrator.', 'uncanny-learndash-codes' );
								}
							} else {
								?>
								<button type="submit" name="uo_codes_license_activate"
										class="uncannyowl-btn uncannyowl-btn--primary">
									<?php esc_attr_e( 'Activate now', 'uncanny-learndash-codes' ); ?>
								</button>

								<a href="<?php echo $buy_new_license; ?>" target="_blank"
								   class="uncannyowl-btn uncannyowl-btn--secondary">
									<?php esc_attr_e( 'Buy license', 'uncanny-learndash-codes' ); ?>
								</a>
							<?php } ?>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
