<div class="<?php if ( ! $from_module ) { ?>wrap<?php } ?>"> <!-- WP container -->
	<?php if ( ! $from_module ) { ?>
		<div class="uo-plugins-header">
			<div class="uo-plugins-header__title">
				Uncanny Toolkit for LearnDash
			</div>
			<div class="uo-plugins-header__author">
				<span><?php esc_attr_e( 'by', 'uncanny-pro-toolkit' ); ?></span>
				<a href="https://uncannyowl.com" target="_blank" class="uo-plugins-header__logo">
					<img
						src="<?php echo esc_url( \uncanny_learndash_toolkit\Config::get_admin_media( 'uncanny-owl-logo.svg' ) ); ?>"
						alt="Uncanny Owl">
				</a>
			</div>
		</div>
	<?php } else { ?>
		<div class="uo-plugins-header__title"><h1><?php esc_attr_e( 'License', 'uncanny-pro-toolkit' ); ?></h1></div>
	<?php } ?>
	<div id="poststuff"> <!-- WP container -->
		<?php if ( ! $from_module ) { ?>

			<h1 class="nav-tab-wrapper">
				<a href="?page=uncanny-toolkit"
				   class="nav-tab"><?php esc_attr_e( 'Modules', 'uncanny-pro-toolkit' ); ?></a>
				<a href="?page=uncanny-toolkit-kb"
				   class="nav-tab"><?php esc_attr_e( 'Help', 'uncanny-pro-toolkit' ); ?></a>
				<a href="?page=uncanny-toolkit-plugins"
				   class="nav-tab"><?php esc_attr_e( 'LearnDash Plugins', 'uncanny-pro-toolkit' ); ?></a>
				<?php
				$compare_version = version_compare( UNCANNY_TOOLKIT_VERSION, '3.7', '<=' );
				if ( $compare_version ) {
					?>
					<a href="?page=<?php echo UO_LICENSE_PAGE; ?>"
					   class="nav-tab nav-tab-active"><?php esc_attr_e( 'License Activation', 'uncanny-pro-toolkit' ); ?></a>
				<?php } ?>
			</h1>
		<?php } ?>

		<div class="uo-license <?php echo implode( ' ', $css_classes ); ?>">
			<div class="uo-license-status">
				<div class="uo-license-status__icon">

					<?php if ( $license_is_active ) { ?>

						<svg class="uo-license-status-icon__svg" xmlns="http://www.w3.org/2000/svg"
							 xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512">
							<path class="uo-license-status-icon__svg-path uo-license-status-icon__svg-check"
								  d="M173.898 439.404l-166.4-166.4c-9.997-9.997-9.997-26.206 0-36.204l36.203-36.204c9.997-9.998 26.207-9.998 36.204 0L192 312.69 432.095 72.596c9.997-9.997 26.207-9.997 36.204 0l36.203 36.204c9.997 9.997 9.997 26.206 0 36.204l-294.4 294.401c-9.998 9.997-26.207 9.997-36.204-.001z"></path>
						</svg>

					<?php } else { ?>

						<svg class="uo-license-status-icon__svg" xmlns="http://www.w3.org/2000/svg"
							 xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 352 512">
							<path class="uo-license-status-icon__svg-path uo-license-status-icon__svg-times"
								  d="M242.72 256l100.07-100.07c12.28-12.28 12.28-32.19 0-44.48l-22.24-22.24c-12.28-12.28-32.19-12.28-44.48 0L176 189.28 75.93 89.21c-12.28-12.28-32.19-12.28-44.48 0L9.21 111.45c-12.28 12.28-12.28 32.19 0 44.48L109.28 256 9.21 356.07c-12.28 12.28-12.28 32.19 0 44.48l22.24 22.24c12.28 12.28 32.2 12.28 44.48 0L176 322.72l100.07 100.07c12.28 12.28 32.2 12.28 44.48 0l22.24-22.24c12.28-12.28 12.28-32.19 0-44.48L242.72 256z"></path>
						</svg>

					<?php } ?>

				</div>
			</div>
			<div class="uo-license-content">

				<form class="uo-license-content-form" method="POST" action="options.php">

					<?php //settings_fields( 'uo_license' ); ?>

					<?php wp_nonce_field( 'uo_nonce', 'uo_nonce' ); ?>

					<div class="uo-license-content-top">
						<div class="uo-license-content-info">
							<?php if ( $license_check ) { ?>
								<div class="uo-license-content-description">

									<?php

									switch ( $license_check ) {
										case 'valid':
											break;

										case 'empty':
											_e( 'Please enter a valid license code and click "Activate now".', 'uncanny-pro-toolkit' );
											break;

										case 'expired':
											printf(
												_x(
													'Your license has expired. Please %s to get instant access to updates and support.',
													'Your license has expired. Please renew your license to get instant access to updates and support.',
													'uncanny-pro-toolkit'
												),
												sprintf(
													'<a href="%s" target="_blank">%s</a>',
													'https://www.uncannyowl.com/checkout/?edd_license_key=' . $license . '&download_id=1377',
													_x(
														'renew your license',
														'Your license has expired. Please renew your license to get instant access to updates and support.',
														'uncanny-pro-toolkit'
													)
												)
											);
											break;

										case 'disabled':
											printf(
												_x(
													'Your license is disabled. Please %s to get instant access to updates and support.',
													'Your license has disabled. Please renew your license to get instant access to updates and support.',
													'uncanny-pro-toolkit'
												),
												sprintf(
													'<a href="%s" target="_blank">%s</a>',
													'https://www.uncannyowl.com/checkout/?edd_license_key=' . $license . '&download_id=1377',
													_x(
														'renew your license',
														'Your license has expired. Please renew your license to get instant access to updates and support.',
														'uncanny-pro-toolkit'
													)
												)
											);
											break;

										case 'invalid':
										case 'inactive':
											_e( 'The license code you entered is invalid.', 'uncanny-pro-toolkit' );
											break;
									}

									?>

								</div>
							<?php } ?>
							<div class="uo-license-content-form">

								<?php if ( $license_is_active ) { ?>

									<input id="uo-license-field"
										   name="uo_license_key"
										   type="password"
										   value="<?php echo esc_attr( $license ); ?>"
										   disabled="disabled"
										   placeholder="<?php esc_attr_e( 'Enter your Uncanny Toolkit Pro for LearnDash license key', 'uncanny-pro-toolkit' ); ?>"
										   required>
									<div class="license-data">
										<p>
											<?php
											if ( isset( $license_data->expires ) ) {
												if ( 'lifetime' === $license_data->expires ) {
													$date = __( 'Lifetime', 'uncanny-pro-toolkit' );
												} else {
													$date = wp_date( get_option( 'date_format' ), strtotime( $license_data->expires ) );
												}
												printf( '<strong>%s</strong>: %s', __( 'Expires', 'uncanny-pro-toolkit' ), $date );
											}
											?>
											<br/>
											<?php
											if ( isset( $license_data->license_limit ) ) {
												printf( '<strong>%s:</strong> %d of %d', __( 'Activations', 'uncanny-pro-toolkit' ), $license_data->site_count, $license_data->license_limit );
											}
											?>
											<br/>
											<?php
											if ( isset( $license_data->customer_name ) ) {
												printf( '<strong>%s:</strong> %s (%s)', __( 'Account', 'uncanny-pro-toolkit' ), $license_data->customer_name, $license_data->customer_email );
											}
											?>
										</p>
										<?php
										if ( ! empty( $license_data ) ) {
											do_action( 'uo_pro_after_license_details', $license_data );
										}
										?>
									</div>
								<?php } else { ?>
									<div
										class="uo-license-content-title"><?php esc_attr_e( 'Your license is not active', 'uncanny-pro-toolkit' ); ?></div>
									<div
										class="uo-license-content-description"><?php esc_attr_e( 'Please enter a valid license code and click "Activate now".', 'uncanny-pro-toolkit' ); ?></div>
									<input id="uo-license-field"
										   name="uo_license_key"
										   type="password"
										   value="<?php echo esc_attr( $license ); ?>"
										   placeholder="<?php esc_attr_e( 'Enter your Uncanny Toolkit Pro for LearnDash license key', 'uncanny-pro-toolkit' ); ?>"
										   required>

								<?php } ?>

							</div>

							<div class="uo-license-content-mobile-buttons">

								<?php if ( $license_is_active ) { ?>
									<?php if ( false === self::is_defined_license_key() ) { ?>

										<button type="submit" name="uo_license_deactivate"
												class="uo-license-btn uo-license-btn--error">
											<?php esc_attr_e( 'Deactivate License', 'uncanny-pro-toolkit' ); ?>
										</button>

										<a href="<?php echo admin_url( 'admin.php?page=' . UO_LICENSE_PAGE . '&clear_license=true&wpnonce=' . wp_create_nonce( 'uncanny-owl' ) ); ?>"
										   onclick="return confirm('<?php _e( 'Are you sure you want to clear your license?', 'uncanny-pro-toolkit' ); ?>')"
										   class="uo-license-btn uo-license-btn--secondary">
											<?php _e( 'Clear License', 'uncanny-pro-toolkit' ); ?>
										</a>

										<?php
									} else {
										_e( 'Your license is managed by your site administrator.', 'uncanny-pro-toolkit' );
									}
								} else {
									?>

									<button type="submit" name="uo_license_activate"
											class="uo-license-btn uo-license-btn--primary">
										<?php esc_attr_e( 'Activate now', 'uncanny-pro-toolkit' ); ?>
									</button>

									<a href="<?php echo $buy_new_license; ?>" target="_blank"
									   class="uo-license-btn uo-license-btn--secondary">
										<?php esc_attr_e( 'Buy license', 'uncanny-pro-toolkit' ); ?>
									</a>

								<?php } ?>

							</div>

						</div>
						<div class="uo-license-content-faq">
							<div class="uo-license-content-title">
								<?php esc_attr_e( 'Need help?', 'uncanny-pro-toolkit' ); ?>
							</div>

							<div class="uo-license-content-faq-list">
								<ul class="uo-license-content-faq-list-ul">
									<li class="uo-license-content-faq-item">
										<a href="<?php echo $where_to_get_my_license; ?>" target="_blank">
											<?php esc_attr_e( 'Where to get my license key', 'uncanny-pro-toolkit' ); ?>
										</a>
									</li>
									<li class="uo-license-content-faq-item">
										<a href="<?php echo $buy_new_license; ?>" target="_blank">
											<?php esc_attr_e( 'Buy a new license', 'uncanny-pro-toolkit' ); ?>
										</a>
									</li>
									<li class="uo-license-content-faq-item">
										<a href="<?php echo $knowledge_base; ?>" target="_blank">
											<?php esc_attr_e( 'Knowledge Base', 'uncanny-pro-toolkit' ); ?>
										</a>
									</li>
								</ul>
							</div>
						</div>
					</div>
					<div class="uo-license-content-footer">

						<?php if ( $license_is_active ) { ?>
							<?php if ( false === self::is_defined_license_key() ) { ?>
								<button type="submit" name="uo_license_deactivate"
										class="uo-license-btn uo-license-btn--error">
									<?php esc_attr_e( 'Deactivate License', 'uncanny-pro-toolkit' ); ?>
								</button>

								<a href="<?php echo admin_url( 'admin.php?page=' . UO_LICENSE_PAGE . '&clear_license=true&wpnonce=' . wp_create_nonce( 'uncanny-owl' ) ); ?>"
								   onclick="return confirm('<?php _e( 'Are you sure you want to clear your license?', 'uncanny-pro-toolkit' ); ?>')"
								   class="uo-license-btn uo-license-btn--secondary">
									<?php _e( 'Clear License', 'uncanny-pro-toolkit' ); ?>
								</a>

								<?php
							} else {
								_e( 'Your license is managed by your site administrator.', 'uncanny-pro-toolkit' );
							}
						} else {
							?>

							<button type="submit" name="uo_license_activate"
									class="uo-license-btn uo-license-btn--primary">
								<?php esc_attr_e( 'Activate now', 'uncanny-pro-toolkit' ); ?>
							</button>

							<a href="<?php echo $buy_new_license; ?>" target="_blank"
							   class="uo-license-btn uo-license-btn--secondary">
								<?php esc_attr_e( 'Buy license', 'uncanny-pro-toolkit' ); ?>
							</a>

						<?php } ?>

					</div>

				</form>

			</div>
		</div>
	</div>
</div>
<?php
if ( $from_module ) { ?>
	<p>&nbsp;</p>
	<?php
}
