<div class="wrap" style="margin-right: 0;">
	<div class="ulgm">
		<div class="ulgm-license <?php echo implode( ' ', $license_css_classes ); ?>">
			<div class="ulgm-license-status">
				<div class="ulgm-license-status__icon">

					<?php if ( $license_is_active ) { ?>

						<svg class="ulgm-license-status-icon__svg" xmlns="http://www.w3.org/2000/svg"
							xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512">
							<path class="ulgm-license-status-icon__svg-path ulgm-license-status-icon__svg-check"
									d="M173.898 439.404l-166.4-166.4c-9.997-9.997-9.997-26.206 0-36.204l36.203-36.204c9.997-9.998 26.207-9.998 36.204 0L192 312.69 432.095 72.596c9.997-9.997 26.207-9.997 36.204 0l36.203 36.204c9.997 9.997 9.997 26.206 0 36.204l-294.4 294.401c-9.998 9.997-26.207 9.997-36.204-.001z"></path>
						</svg>

					<?php } else { ?>

						<svg class="ulgm-license-status-icon__svg" xmlns="http://www.w3.org/2000/svg"
							xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 352 512">
							<path class="ulgm-license-status-icon__svg-path ulgm-license-status-icon__svg-times"
									d="M242.72 256l100.07-100.07c12.28-12.28 12.28-32.19 0-44.48l-22.24-22.24c-12.28-12.28-32.19-12.28-44.48 0L176 189.28 75.93 89.21c-12.28-12.28-32.19-12.28-44.48 0L9.21 111.45c-12.28 12.28-12.28 32.19 0 44.48L109.28 256 9.21 356.07c-12.28 12.28-12.28 32.19 0 44.48l22.24 22.24c12.28 12.28 32.2 12.28 44.48 0L176 322.72l100.07 100.07c12.28 12.28 32.2 12.28 44.48 0l22.24-22.24c12.28-12.28 12.28-32.19 0-44.48L242.72 256z"></path>
						</svg>

					<?php } ?>

				</div>
			</div>
			<div class="ulgm-license-content">

				<form class="ulgm-license-content-form" method="POST">

					<?php wp_nonce_field( 'ulgm_nonce', 'ulgm_nonce' ); ?>

					<div class="ulgm-license-content-top">
						<div class="ulgm-license-content-info">


							<div class="ulgm-license-content-title">

								<?php

								if ( $license_is_active ) {
									//_e( 'Your license is active', 'uncanny-learndash-groups' );
								} elseif ( 'expired' === $status ) {
									_e( 'Your license has expired!', 'uncanny-learndash-groups' );
								} elseif ( empty( $license ) ) {
										_e( 'Enter your license key', 'uncanny-learndash-groups' );
								} else {
									_e( 'Your license is not active', 'uncanny-learndash-groups' );
								}

								?>
							</div>

							<div class="ulgm-license-content-description">

								<?php

								switch ( $status ) {
									case 'valid':
										break;
									case 'expired':
										printf(
											_x(
												'You must renew your license to get access to plugin updates and support. Click %s to renew your license.',
												'Your license has expired. Please renew your license to get instant access to updates and support.',
												'uncanny-learndash-groups'
											),
											sprintf(
												'<a href="%s" target="_blank">%s</a>',
												'https://www.uncannyowl.com/checkout/?edd_license_key=' . $license . '&download_id=1377&utm_medium=uo_groups&utm_campaign=license_page',
												__( 'here', 'uncanny-learndash-groups' )
											)
										);
										break;

									case 'disabled':
										printf(
											_x(
												'Your license is disabled. Please %s to get instant access to updates and support.',
												'Your license has disabled. Please renew your license to get instant access to updates and support.',
												'uncanny-learndash-groups'
											),
											sprintf(
												'<a href="%s" target="_blank">%s</a>',
												'https://www.uncannyowl.com/checkout/?edd_license_key=' . $license . '&download_id=1377&utm_medium=uo_groups&utm_campaign=license_page',
												_x(
													'renew your license',
													'Your license has expired. Please renew your license to get instant access to updates and support.',
													'uncanny-learndash-groups'
												)
											)
										);
										break;

									case 'invalid':
										_e( 'The license code you entered is invalid.', 'uncanny-learndash-codes' );
										break;
									case 'inactive':
										_e( 'The license code you entered is deactivated.', 'uncanny-learndash-codes' );
										break;
									default:
										_e( 'Please enter a valid license code and click "Activate now".', 'uncanny-learndash-groups' );
										break;

								}

								?>

							</div>

							<div class="ulgm-license-content-form">

								<?php if ( $license_is_active ) { ?>

									<input id="ulgm-license-field"
											name="<?php echo \uncanny_learndash_groups\Utilities::get_prefix(); ?>_license_key"
											type="password"
											value="<?php echo md5( $license ); ?>"
											disabled
											placeholder="<?php _e( 'Enter your license key', 'uncanny-learndash-groups' ); ?>"
											required>
									<div class="license-data">
										<p>
											<?php
											if ( isset( $license_data->expires ) ) {
												if ( 'lifetime' === $license_data->expires ) {
													$date = __( 'Lifetime', 'uncanny-learndash-groups' );
												} else {
													$date = wp_date( get_option( 'date_format' ), strtotime( $license_data->expires ) );
												}
												printf( '<strong>%s</strong>: %s', __( 'Expires', 'uncanny-learndash-groups' ), $date );
											}
											?>
											<br/>
											<?php
											if ( isset( $license_data->license_limit ) ) {
												printf( '<strong>%s:</strong> %d of %d', __( 'Activations', 'uncanny-learndash-groups' ), $license_data->activations_left, $license_data->license_limit );
											}
											?>
											<br/>
											<?php
											if ( isset( $license_data->customer_name ) ) {
												printf( '<strong>%s:</strong> %s (%s)', __( 'Account', 'uncanny-learndash-groups' ), $license_data->customer_name, $license_data->customer_email );
											}
											?>
										</p>
										<?php do_action( 'ulgm_after_license_details', $license_data ); ?>
									</div>
								<?php } else { ?>

									<input id="ulgm-license-field"
											name="<?php echo \uncanny_learndash_groups\Utilities::get_prefix(); ?>_license_key" type="password"
											value="<?php esc_attr_e( $license ); ?>"
											placeholder="<?php _e( 'Enter your license key', 'uncanny-learndash-groups' ); ?>"
											required>
									<div class="license-data">
										<p>
											<?php
											if ( isset( $license_data->expires ) && 'site_inactive' !== $license_data->license ) {
												printf( '<strong>%s</strong>: %s', __( 'Expired', 'uncanny-learndash-groups' ), wp_date( get_option( 'date_format' ), strtotime( $license_data->expires ) ) );
											}
											?>
										</p>
									</div>
								<?php } ?>

							</div>

							<div class="ulgm-license-content-mobile-buttons">

								<?php if ( true === $license_is_active && ( ! defined( 'UNCANNY_GROUPS_LICENSE_KEY' ) || empty( UNCANNY_GROUPS_LICENSE_KEY ) ) ) { ?>

									<button type="submit"
											name="<?php echo \uncanny_learndash_groups\Utilities::get_prefix(); ?>_license_deactivate"
											class="ulgm-license-btn ulgm-license-btn--error">
										<?php _e( 'Deactivate license', 'uncanny-learndash-groups' ); ?>
									</button>

									<a href="<?php echo admin_url( 'admin.php?page=uncanny-groups&clear_license=true&wpnonce=' . wp_create_nonce( 'uncanny-owl' ) ); ?>"
										onclick="return confirm('<?php _e( 'Are you sure you want to clear your license?', 'uncanny-learndash-groups' ); ?>')"
										class="ulgm-license-btn ulgm-license-btn--secondary">
										<?php _e( 'Clear license', 'uncanny-learndash-groups' ); ?>
									</a>

								<?php } elseif ( ! defined( 'UNCANNY_GROUPS_LICENSE_KEY' ) || empty( UNCANNY_GROUPS_LICENSE_KEY ) ) { ?>

									<button type="submit" name="<?php echo \uncanny_learndash_groups\Utilities::get_prefix(); ?>_license_activate"
											class="ulgm-license-btn ulgm-license-btn--primary">
										<?php _e( 'Activate now', 'uncanny-learndash-groups' ); ?>
									</button>

									<a href="<?php echo $buy_new_license; ?>" target="_blank"
										class="ulgm-license-btn ulgm-license-btn--secondary">
										<?php _e( 'Buy license', 'uncanny-learndash-groups' ); ?>
									</a>

								<?php } ?>

							</div>

						</div>
						<div class="ulgm-license-content-faq">
							<div class="ulgm-license-content-title">
								<?php _e( 'Need help?', 'uncanny-learndash-groups' ); ?>
							</div>

							<div class="ulgm-license-content-faq-list">
								<ul class="ulgm-license-content-faq-list-ul">
									<li class="ulgm-license-content-faq-item">
										<a href="<?php echo $where_to_get_my_license; ?>" target="_blank">
											<?php _e( 'Where to get my license key', 'uncanny-learndash-groups' ); ?>
										</a>
									</li>
									<li class="ulgm-license-content-faq-item">
										<a href="<?php echo $buy_new_license; ?>" target="_blank">
											<?php _e( 'Buy a new license', 'uncanny-learndash-groups' ); ?>
										</a>
									</li>
									<li class="ulgm-license-content-faq-item">
										<a href="<?php echo $knowledge_base; ?>" target="_blank">
											<?php _e( 'Knowledge base', 'uncanny-learndash-groups' ); ?>
										</a>
									</li>
								</ul>
							</div>
						</div>
					</div>
					<div class="ulgm-license-content-footer">

						<?php if ( $license_is_active ) { ?>

							<?php if ( ! defined( 'UNCANNY_GROUPS_LICENSE_KEY' ) || empty( UNCANNY_GROUPS_LICENSE_KEY ) ) { ?>
								<button type="submit" name="<?php echo \uncanny_learndash_groups\Utilities::get_prefix(); ?>_license_deactivate"
										class="ulgm-license-btn ulgm-license-btn--error">
									<?php _e( 'Deactivate license', 'uncanny-learndash-groups' ); ?>
								</button>
								<a href="<?php echo admin_url( 'admin.php?page=uncanny-groups&clear_license=true&wpnonce=' . wp_create_nonce( 'uncanny-owl' ) ); ?>"
									onclick="return confirm('<?php _e( 'Are you sure you want to clear your license?', 'uncanny-learndash-groups' ); ?>')"
									class="ulgm-license-btn ulgm-license-btn--secondary">
									<?php _e( 'Clear license', 'uncanny-learndash-groups' ); ?>
								</a>
								<?php
							} else {
								_e( 'Your license is managed by your site administrator.', 'uncanny-learndash-groups' );
							}
							?>

						<?php } elseif ( ! defined( 'UNCANNY_GROUPS_LICENSE_KEY' ) || empty( UNCANNY_GROUPS_LICENSE_KEY ) ) { ?>

							<button type="submit" name="<?php echo \uncanny_learndash_groups\Utilities::get_prefix(); ?>_license_activate"
									class="ulgm-license-btn ulgm-license-btn--primary">
								<?php _e( 'Activate now', 'uncanny-learndash-groups' ); ?>
							</button>

							<a href="<?php echo $buy_new_license; ?>" target="_blank"
								class="ulgm-license-btn ulgm-license-btn--secondary">
								<?php _e( 'Buy license', 'uncanny-learndash-groups' ); ?>
							</a>

						<?php } ?>

					</div>

				</form>

			</div>
		</div>
	</div>
</div>
