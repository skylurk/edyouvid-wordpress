<div class="<?php if ( ! $from_module ) { ?>wrap<?php } ?>"> <!-- WP container -->
	<?php if ( ! $from_module ) { ?>
		<div class="uncannyowl-header">
			<div class="uncannyowl-header-top">
				<div class="uncannyowl-header-top__content">
					<div class="uncannyowl-header-top__title">
						Uncanny Toolkit for LearnDash
					</div>
					<div class="uncannyowl-header-top__author">
						<span><?php esc_attr_e( 'by', 'uncanny-pro-toolkit' ); ?></span>
						<a href="https://uncannyowl.com" target="_blank" class="uncannyowl-header-top__logo uncannyowl-header-top__logo--svg">
							UncannyOwl
						</a>
					</div>
				</div>
			</div>
		</div>
	<?php } else { ?>
		<!-- <div class="uncannyowl-header-top__title"><h3><?php esc_attr_e( 'License', 'uncanny-pro-toolkit' ); ?></h3></div> -->
	<?php } ?>
	<div id="poststuff"> <!-- WP container -->
		<?php if ( ! $from_module ) { ?>

			<nav class="nav-tab-wrapper">
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
			</nav>
		<?php } ?>

		<div class="uncannyowl-license <?php echo implode( ' ', $css_classes ); ?>">
			<div class="uncannyowl-license-status <?php echo $license_is_active ? 'uncannyowl-license-status--active' : ''; ?>">
				<div class="uncannyowl-license-status__icon">

					<?php if ( $license_is_active ) { ?>

						<svg class="uncannyowl-license-status-icon__svg" xmlns="http://www.w3.org/2000/svg"
							 xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512">
							<path class="uncannyowl-license-status-icon__svg-path uncannyowl-license-status-icon__svg-check"
								  d="M173.898 439.404l-166.4-166.4c-9.997-9.997-9.997-26.206 0-36.204l36.203-36.204c9.997-9.998 26.207-9.998 36.204 0L192 312.69 432.095 72.596c9.997-9.997 26.207-9.997 36.204 0l36.203 36.204c9.997 9.997 9.997 26.206 0 36.204l-294.4 294.401c-9.998 9.997-26.207 9.997-36.204-.001z"></path>
						</svg>

					<?php } else { ?>

						<svg class="uncannyowl-license-status-icon__svg" xmlns="http://www.w3.org/2000/svg"
							 xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 352 512">
							<path class="uncannyowl-license-status-icon__svg-path uncannyowl-license-status-icon__svg-times"
								  d="M242.72 256l100.07-100.07c12.28-12.28 12.28-32.19 0-44.48l-22.24-22.24c-12.28-12.28-32.19-12.28-44.48 0L176 189.28 75.93 89.21c-12.28-12.28-32.19-12.28-44.48 0L9.21 111.45c-12.28 12.28-12.28 32.19 0 44.48L109.28 256 9.21 356.07c-12.28 12.28-12.28 32.19 0 44.48l22.24 22.24c12.28 12.28 32.2 12.28 44.48 0L176 322.72l100.07 100.07c12.28 12.28 32.2 12.28 44.48 0l22.24-22.24c12.28-12.28 12.28-32.19 0-44.48L242.72 256z"></path>
						</svg>

					<?php } ?>

				</div>
			</div>
			<div class="uncannyowl-license-content">

				<form class="uncannyowl-license-content-form" method="POST" action="options.php">

					<?php //settings_fields( 'uo_license' ); ?>

					<?php wp_nonce_field( 'uo_nonce', 'uo_nonce' ); ?>

					<div class="uncannyowl-license-content-top">
						<div class="uncannyowl-license-content-info">
							<?php if ( $license_check && ! $license_is_active ) { ?>
								<div class="uncannyowl-license-content-description">

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
							<?php if ( $license_is_active ) { ?>
								<div class="uncannyowl-license-content-title"><?php esc_attr_e( 'Your license is active', 'uncanny-pro-toolkit' ); ?></div>
								<div class="uncannyowl-license-content-description"><?php esc_attr_e( 'Your license is valid and active. You have access to all premium features and updates.', 'uncanny-pro-toolkit' ); ?></div>
								
								<?php 
										if( isset( $license_data->license_html ) ) {
											echo $license_data->license_html;
										}else{
											// Show basic license details
										?>
										<div>
											<strong><?php esc_attr_e( 'Expires', 'uncanny-pro-toolkit' ); ?>:</strong> <span class="license-expires-value"><?php echo $license_data->expires; ?></span>
										</div>
										<?php
										}
									?>
									<?php
									if ( ! empty( $license_data ) ) {
										do_action( 'uo_pro_after_license_details', $license_data );
									}
									?>
							<?php } else { ?>
								<div class="uncannyowl-license-content-title"><?php esc_attr_e( 'Your license is not active', 'uncanny-pro-toolkit' ); ?></div>
								<div class="uncannyowl-license-content-description"><?php esc_attr_e( 'Please enter a valid license code and click "Activate now".', 'uncanny-pro-toolkit' ); ?></div>
								
								<input id="uncannyowl-license-field"
									   name="uo_license_key"
									   type="password"
									   value="<?php echo esc_attr( $license ); ?>"
									   placeholder="<?php esc_attr_e( 'Enter your Uncanny Toolkit Pro for LearnDash license key', 'uncanny-pro-toolkit' ); ?>"
									   class="uncannyowl-license-field"
									   required>
							<?php } ?>



						</div>
						<div class="uncannyowl-license-content-faq">
							<?php if ( $license_is_active ) { ?>
								<div class="uncannyowl-license-content-title">
									<?php esc_attr_e( 'License Options', 'uncanny-pro-toolkit' ); ?>
								</div>
							<?php } else { ?>
								<div class="uncannyowl-license-content-title">
									<?php esc_attr_e( 'Need help?', 'uncanny-pro-toolkit' ); ?>
								</div>
							<?php } ?>

							<div class="uncannyowl-license-content-faq-list">
								<ul class="uncannyowl-license-content-faq-list-ul">
									<?php if ( $license_is_active ) { ?>
										<li class="uncannyowl-license-content-faq-item">
											<a href="<?php echo $buy_new_license; ?>" target="_blank">
												<?php esc_attr_e( 'Renew license', 'uncanny-pro-toolkit' ); ?>
											</a>
										</li>
										<li class="uncannyowl-license-content-faq-item">
											<a href="<?php echo $knowledge_base; ?>" target="_blank">
												<?php esc_attr_e( 'Manage sites', 'uncanny-pro-toolkit' ); ?>
											</a>
										</li>
										<li class="uncannyowl-license-content-faq-item">
											<a href="<?php echo $knowledge_base; ?>" target="_blank">
												<?php esc_attr_e( 'Download updates', 'uncanny-pro-toolkit' ); ?>
											</a>
										</li>
									<?php } else { ?>
										<li class="uncannyowl-license-content-faq-item">
											<a href="<?php echo $where_to_get_my_license; ?>" target="_blank">
												<?php esc_attr_e( 'Where to get my license key', 'uncanny-pro-toolkit' ); ?>
											</a>
										</li>
										<li class="uncannyowl-license-content-faq-item">
											<a href="<?php echo $buy_new_license; ?>" target="_blank">
												<?php esc_attr_e( 'Buy a new license', 'uncanny-pro-toolkit' ); ?>
											</a>
										</li>
										<li class="uncannyowl-license-content-faq-item">
											<a href="<?php echo $knowledge_base; ?>" target="_blank">
												<?php esc_attr_e( 'Knowledge Base', 'uncanny-pro-toolkit' ); ?>
											</a>
										</li>
									<?php } ?>
								</ul>
							</div>
						</div>
					</div>
					<div class="uncannyowl-license-content-footer">

						<?php if ( $license_is_active ) { ?>
							<?php if ( false === self::is_defined_license_key() ) { ?>
								<button type="submit" name="uo_license_deactivate"
										class="uncannyowl-btn uncannyowl-btn--secondary">
									<?php esc_attr_e( 'Deactivate license', 'uncanny-pro-toolkit' ); ?>
								</button>
							<?php } else { ?>
								<div><?php _e( 'Your license is managed by your site administrator.', 'uncanny-pro-toolkit' ); ?></div>
							<?php } ?>
						<?php } else { ?>
							<button type="submit" name="uo_license_activate"
									class="uncannyowl-btn uncannyowl-btn--primary">
								<?php esc_attr_e( 'Activate now', 'uncanny-pro-toolkit' ); ?>
							</button>

							<a href="<?php echo $buy_new_license; ?>" target="_blank"
							   class="uncannyowl-btn uncannyowl-btn--secondary">
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
	<?php
}
