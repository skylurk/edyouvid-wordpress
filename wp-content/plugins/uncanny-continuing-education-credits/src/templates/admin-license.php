<?php

namespace uncanny_ceu;
?>

<div class="wrap" style="margin-right: 0;">
	<div class="ceu">

		<div class="ucec-license <?php echo implode( ' ', $license_css_classes ); ?>">
			<div class="ucec-license-status">
				<div class="ucec-license-status__icon">

					<?php if ( $license_is_active ) { ?>

						<svg class="ucec-license-status-icon__svg" xmlns="http://www.w3.org/2000/svg"
							 xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512">
							<path class="ucec-license-status-icon__svg-path ucec-license-status-icon__svg-check"
								  d="M173.898 439.404l-166.4-166.4c-9.997-9.997-9.997-26.206 0-36.204l36.203-36.204c9.997-9.998 26.207-9.998 36.204 0L192 312.69 432.095 72.596c9.997-9.997 26.207-9.997 36.204 0l36.203 36.204c9.997 9.997 9.997 26.206 0 36.204l-294.4 294.401c-9.998 9.997-26.207 9.997-36.204-.001z"></path>
						</svg>

					<?php } else { ?>

						<svg class="ucec-license-status-icon__svg" xmlns="http://www.w3.org/2000/svg"
							 xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 352 512">
							<path class="ucec-license-status-icon__svg-path ucec-license-status-icon__svg-times"
								  d="M242.72 256l100.07-100.07c12.28-12.28 12.28-32.19 0-44.48l-22.24-22.24c-12.28-12.28-32.19-12.28-44.48 0L176 189.28 75.93 89.21c-12.28-12.28-32.19-12.28-44.48 0L9.21 111.45c-12.28 12.28-12.28 32.19 0 44.48L109.28 256 9.21 356.07c-12.28 12.28-12.28 32.19 0 44.48l22.24 22.24c12.28 12.28 32.2 12.28 44.48 0L176 322.72l100.07 100.07c12.28 12.28 32.2 12.28 44.48 0l22.24-22.24c12.28-12.28 12.28-32.19 0-44.48L242.72 256z"></path>
						</svg>

					<?php } ?>

				</div>
			</div>
			<div class="ucec-license-content">

				<form class="ucec-license-content-form" method="POST">

					<?php wp_nonce_field( 'ceu_nonce', 'ceu_nonce' ); ?>

					<div class="ucec-license-content-top">
						<div class="ucec-license-content-info">

							<?php ?>

							<div class="ucec-license-content-title">

								<?php

								if ( $license_is_active ) {
									_e( 'Your license is active', 'uncanny-ceu' );
								} elseif ( 'expired' === $status ) {
									_e( 'Your license has expired!', 'uncanny-ceu' );
								} else {
									if ( empty( $license ) ) {
										_e( 'Enter your license key', 'uncanny-ceu' );
									} else {
										_e( 'Your license is not active', 'uncanny-ceu' );
									}
								}

								?>
							</div>

							<div class="ucec-license-content-description">

								<?php

								switch ( $status ) {
									case 'valid':
										break;
									case 'expired':
										printf(
											_x(
												'You must renew your license to get access to plugin updates and support. Click %s to renew your license.',
												'Your license has expired. Please renew your license to get instant access to updates and support.',
												'uncanny-ceu'
											),
											sprintf(
												'<a href="%s" target="_blank">%s</a>',
												'https://www.uncannyowl.com/checkout/?edd_license_key=' . $license . '&download_id=11141&utm_medium=uo_ceu&utm_campaign=license_page',
												__( 'here', 'uncanny-ceu' )
											)
										);
										break;

									case 'disabled':
										printf(
											_x( 'Your license is disabled. Please %s to get instant access to updates and support.',
												'Your license has disabled. Please renew your license to get instant access to updates and support.',
												'uncanny-ceu' ),
											sprintf(
												'<a href="%s" target="_blank">%s</a>',
												'https://www.uncannyowl.com/checkout/?edd_license_key=' . $license . '&download_id=11141&utm_medium=uo_ceu&utm_campaign=license_page',
												_x( 'renew your license',
													'Your license has expired. Please renew your license to get instant access to updates and support.',
													'uncanny-ceu' )
											)
										);
										break;

									case 'invalid':
										_e( 'The license code you entered is invalid.', 'uncanny-ceu' );
										break;
									case 'inactive':
										_e( 'The license code you entered is deactivated.', 'uncanny-ceu' );
										break;
									default:
										_e( 'Please enter a valid license code and click "Activate now".', 'uncanny-ceu' );
										break;

								}

								?>

							</div>

							<div class="ucec-license-content-form">

								<?php if ( $license_is_active ) { ?>

									<input id="ucec-license-field"
										   name="ceu_license_key"
										   type="password"
										   value="<?php echo md5( $license ); ?>"
										   disabled
										   placeholder="<?php _e( 'Enter your license key', 'uncanny-ceu' ); ?>"
										   required>
									<div class="license-data">
										<p>
											<?php
											if ( isset( $license_data->expires ) ) {
												if ( 'lifetime' === $license_data->expires ) {
													$date = __( 'Lifetime', 'uncanny-ceu' );
												} else {
													$date = wp_date( get_option( 'date_format' ), strtotime( $license_data->expires ) );
												}
												printf( '<strong>%s</strong>: %s', __( 'Expires', 'uncanny-ceu' ), $date );
											}
											?>
											<br/>
											<?php
											if ( isset( $license_data->license_limit ) ) {
												printf( '<strong>%s:</strong> %d of %d', __( 'Activations', 'uncanny-ceu' ), $license_data->activations_left, $license_data->license_limit );
											}
											?>
											<br/>
											<?php
											if ( isset( $license_data->customer_name ) ) {
												printf( '<strong>%s:</strong> %s (%s)', __( 'Account', 'uncanny-ceu' ), $license_data->customer_name, $license_data->customer_email );
											}
											?>
										</p>
										<?php do_action( 'ucec_after_license_details', $license_data ); ?>
									</div>
								<?php } else { ?>

									<input id="ucec-license-field"
										   name="ceu_license_key" type="password"
										   value="<?php esc_attr_e( $license ); ?>"
										   placeholder="<?php _e( 'Enter your license key', 'uncanny-ceu' ); ?>"
										   required>
									<div class="license-data">
										<p>
											<?php
											if ( isset( $license_data->expires ) && ! empty( $license_data->expires ) && 'lifetime' !== $license_data->expires ) {
												printf( '<strong>%s</strong>: %s', __( 'Expired', 'uncanny-ceu' ), wp_date( get_option( 'date_format' ), strtotime( $license_data->expires ) ) );
											}
											?>
										</p>
									</div>
								<?php } ?>

							</div>

							<div class="ucec-license-content-mobile-buttons">

								<?php if ( $license_is_active ) { ?>
									<?php if ( ! defined( 'UNCANNY_CEUS_LICENSE_KEY' ) || empty( UNCANNY_CEUS_LICENSE_KEY ) ) { ?>

										<button type="submit"
												name="ceu_license_deactivate"
												class="ucec-license-btn ucec-license-btn--error">
											<?php _e( 'Deactivate license', 'uncanny-ceu' ); ?>
										</button>

										<a href="<?php echo admin_url( 'admin.php?page=uncanny-ceu&clear_license=true&wpnonce=' . wp_create_nonce( 'uncanny-owl' ) ); ?>"
										   onclick="return confirm('<?php _e( 'Are you sure you want to clear your license?', 'uncanny-ceu' ); ?>')"
										   class="ucec-license-btn ucec-license-btn--secondary">
											<?php _e( 'Clear license', 'uncanny-ceu' ); ?>
										</a>
									<?php } else {
										_e( 'Your license is managed by your site administrator.', 'uncanny-ceu' );
									}
								} else { ?>

									<button type="submit" name="ceu_license_activate"
											class="ucec-license-btn ucec-license-btn--primary">
										<?php _e( 'Activate now', 'uncanny-ceu' ); ?>
									</button>

									<a href="<?php echo $buy_new_license; ?>" target="_blank"
									   class="ucec-license-btn ucec-license-btn--secondary">
										<?php _e( 'Buy license', 'uncanny-ceu' ); ?>
									</a>

								<?php } ?>

							</div>

						</div>
						<div class="ucec-license-content-faq">
							<div class="ucec-license-content-title">
								<?php _e( 'Need help?', 'uncanny-ceu' ); ?>
							</div>

							<div class="ucec-license-content-faq-list">
								<ul class="ucec-license-content-faq-list-ul">
									<li class="ucec-license-content-faq-item">
										<a href="<?php echo $where_to_get_my_license; ?>" target="_blank">
											<?php _e( 'Where to get my license key', 'uncanny-ceu' ); ?>
										</a>
									</li>
									<li class="ucec-license-content-faq-item">
										<a href="<?php echo $buy_new_license; ?>" target="_blank">
											<?php _e( 'Buy a new license', 'uncanny-ceu' ); ?>
										</a>
									</li>
									<li class="ucec-license-content-faq-item">
										<a href="<?php echo $knowledge_base; ?>" target="_blank">
											<?php _e( 'Knowledge base', 'uncanny-ceu' ); ?>
										</a>
									</li>
								</ul>
							</div>
						</div>
					</div>
					<div class="ucec-license-content-footer">

						<?php if ( $license_is_active ) { ?>
							<?php if ( ! defined( 'UNCANNY_CEUS_LICENSE_KEY' ) || empty( UNCANNY_CEUS_LICENSE_KEY ) ) { ?>
								<button type="submit" name="ceu_license_deactivate"
										class="ucec-license-btn ucec-license-btn--error">
									<?php _e( 'Deactivate license', 'uncanny-ceu' ); ?>
								</button>

								<a href="<?php echo admin_url( 'admin.php?page=uncanny-ceu&clear_license=true&wpnonce=' . wp_create_nonce( 'uncanny-owl' ) ); ?>"
								   onclick="return confirm('<?php _e( 'Are you sure you want to clear your license?', 'uncanny-ceu' ); ?>')"
								   class="ucec-license-btn ucec-license-btn--secondary">
									<?php _e( 'Clear license', 'uncanny-ceu' ); ?>
								</a>
							<?php } else {
								_e( 'Your license is managed by your site administrator.', 'uncanny-ceu' );
							}
						} else { ?>

							<button type="submit" name="ceu_license_activate"
									class="ucec-license-btn ucec-license-btn--primary">
								<?php _e( 'Activate now', 'uncanny-ceu' ); ?>
							</button>

							<a href="<?php echo $buy_new_license; ?>" target="_blank"
							   class="ucec-license-btn ucec-license-btn--secondary">
								<?php _e( 'Buy license', 'uncanny-ceu' ); ?>
							</a>

						<?php } ?>

					</div>

				</form>

			</div>
		</div>
	</div>
</div>
