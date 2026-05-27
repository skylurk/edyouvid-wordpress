<?php
/**
 * HTML structure of License Manager page.
 *
 * @package   LoginPress-Pro
 * @since     1.0.0
 * @version   6.1.0
 */

$loginpress_pro_license = '';
if ( 'valid' === self::get_registered_license_status() && null !== get_option( 'loginpress_pro_license_key' ) ) {
	$loginpress_pro_license = self::mask_license( get_option( 'loginpress_pro_license_key' ) );
}
if ( version_compare( LOGINPRESS_VERSION, '3.0.5', '>=' ) ) {
	echo LoginPress_Settings::loginpress_admin_page_header(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
$end_date = strtotime( '2025-03-25' ); // Timestamp for 25th March 2025.
/**
 * Add "NEW" tag to any tab.
 *
 * @param int|null $time Timestamp to check against current time.
 * @since 4.0.0
 * @version 6.1.0
 * @return string The HTML output.
 */
function loginpress_new_tag( $time = null ) {
	// Check if $time is set and is a valid timestamp.
	if ( is_null( $time ) || ! is_numeric( $time ) ) {
		return; // Exit early if no valid time is provided.
	}
	// Compare the provided time with the current time.
	if ( time() <= $time ) {
		return '<strong class="loginpress-new-tag">New</strong>';
	}

	// Return nothing if the time has passed.
	return '';
}
?>

<div class="wrap">
	<h2 class="loginpress-license-heading">
		<?php esc_html_e( 'Activate your License', 'loginpress-pro' ); ?>
	</h2>
	<div class="loginpress-admin-setting">
		<div class="loginpress-tabs-main">
			<span class="tabs-toggle"><?php esc_html_e( 'Menu', 'loginpress-pro' ); ?></span>
			<?php if ( version_compare( LOGINPRESS_VERSION, '3.0.5', '>=' ) ) : ?>
				<ul class="nav-tab-wrapper loginpress-tabs-wrapper">
					<li class="settings-tabs-list">
						<a href="<?php echo esc_url( site_url() . '/wp-admin/admin.php?page=loginpress-settings&tab=setting' ); ?>" class="nav-tab" id="loginpress_setting-tab">
							<?php echo esc_html_e( 'Settings', 'loginpress-pro' ); ?>
							<span><?php echo esc_html_e( 'Login Page Setting', 'loginpress-pro' ); ?></span>
						</a>
					</li>
					<?php if ( apply_filters( 'loginpress_captcha_tab_visible', true ) ) : ?>
						<li class="settings-tabs-list">
							<a href="<?php echo esc_url( site_url() . '/wp-admin/admin.php?page=loginpress-settings&tab=captcha_settings' ); ?>" class="nav-tab" id="loginpress_captcha_settings-tab">
								<?php echo esc_html_e( 'Captchas', 'loginpress-pro' ); ?>
								<span><?php echo esc_html_e( 'CAPTCHA Protection Settings', 'loginpress-pro' ); ?></span>
							</a>
						</li>
					<?php endif; ?>
					<?php
						$addons_array = get_option( 'loginpress_pro_addons' );
						$addon_tabs   = array(
							'autologin'            => 'auto-login',
							'login_redirects'      => 'login-redirects',
							'limit_login_attempts' => 'limit-login-attempts',
							'hidelogin'            => 'hide-login',
							'social_logins'        => 'social-login',
						);
						if ( $addons_array ) {
							foreach ( $addons_array as $addon ) {
								$addon_tab = array_search( $addon['slug'], $addon_tabs, true );

								if ( $addon['is_active'] && $addon_tab ) {
									?>
									<li class="settings-tabs-list">
										<a href="<?php echo esc_url( site_url() . '/wp-admin/admin.php?page=loginpress-settings&tab=' . $addon_tab ); ?>" class="nav-tab" id="loginpress_<?php echo esc_attr( $addon_tab ); ?>-tab">
											<?php echo esc_html( $addon['title'] ); ?>
											<span><?php echo esc_html( $addon['short_desc'] ); ?></span>
										</a>
									</li>
									<?php
								}
							}
						}
						?>
					<li class="settings-tabs-list">
						<a href="<?php echo esc_url( site_url() . '/wp-admin/admin.php?page=loginpress-settings&tab=integration_settings' ); ?>" class="nav-tab" id="loginpress_integration_settings-tab">
							<?php echo esc_html_e( 'Integrations', 'loginpress-pro' ); ?>
							<span><?php echo esc_html_e( 'All Plugin Integrations', 'loginpress-pro' ); ?></span>
						</a>
					</li>

					<li class="settings-tabs-list">
						<a href="#loginpress_pro_license" class="nav-tab nav-tab-active" id="loginpress_pro_license-tab">
							<?php
								/* translators: License Manager Of LoginPress */
								echo sprintf( __( 'License Manager %1$sManage Your License Key%2$s', 'loginpress' ), '<span>', '</span>' );  // @codingStandardsIgnoreLine.
							?>
						</a>
					</li>
				</ul>
			<?php endif; ?>
		</div>
		<div class="metabox-holder loginpress-settings" id="loginpress_pro_license-tab">
			<div id="loginpress-license-settings" class="group" style="">
				<form method="post" action="options.php" class="loginpress-settings loginpress-license-settings">
					<?php settings_fields( 'loginpress_pro_license' ); ?>
					<table class="form-table">
						<tbody>
							<tr valign="top">
								<th scope="row" valign="top">
									<?php
									echo '<label for="loginpress_pro_license_key">' . esc_html__( 'License Key', 'loginpress-pro' ) . '</label>';
									?>
								</th>
								<td>
									<input id="loginpress_pro_license_key" required placeholder="<?php esc_html_e( 'Enter your license key', 'loginpress-pro' ); ?>" name="loginpress_pro_license_key" type="text" class="regular-text" value="<?php echo esc_html( $loginpress_pro_license ); ?>" />
									<label class="description" for="loginpress_pro_license_key"><?php echo esc_html__( 'Validating license key is mandatory to use automatic updates and plugin support.', 'loginpress-pro' ); ?></label>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row" valign="top"></th>
								<td>
									<?php
									if ( self::is_registered() ) {
										wp_nonce_field( 'loginpress_pro_deactivate_license_nonce', 'loginpress_pro_deactivate_license_nonce' );
										?>
										<input type="submit" class="button-secondary" name="loginpress_pro_license_deactivate" value="<?php echo esc_html__( 'Deactivate License', 'loginpress-pro' ); ?>"/>
										<?php
									} else {
										wp_nonce_field( 'loginpress_pro_activate_license_nonce', 'loginpress_pro_activate_license_nonce' );
										?>
										<input type="submit" class="button-primary" name="loginpress_pro_license_activate" value="<?php echo esc_html__( 'Activate License', 'loginpress-pro' ); ?>"/>
										<?php
									}
									?>
								</td>
							</tr>

							<tr>
								<th></th>
								<td class="loginpress-license-desc">
									<?php
									if ( self::is_registered() ) {

										$expiration_date = self::get_expiration_date();

										if ( 'lifetime' === $expiration_date ) {
											$license_desc = esc_html__( 'You have a lifetime licenses, it will never expire.', 'loginpress-pro' );
										} else {
											$license_desc = sprintf(
											/* Translators: Validity of key. */
												__( 'Your (%2$s) license key is valid until %1$s.', 'loginpress-pro' ),
												'<strong>' . date_i18n( get_option( 'date_format' ), strtotime( $expiration_date, time() ) ) . '</strong>',
												self::get_license_type()
											);
										}

										if ( 'lifetime' !== $expiration_date ) {
											$license_tooltip_desc = sprintf(
											/* Translators: Automatic Renewal of key. */
												esc_html__( 'The license will automatically renew, if you have an active subscription to the LoginPress Pro - at %s', 'loginpress-pro' ),
												'<a href="https://wpbrigade.com/account/?utm_source=loginpress-pro&utm_medium=licnese-tab&utm_campaign=wpbrigade-account&utm_content=WPBrigade-text-link">WPBrigade.com</a>'
											);
										} else {
											$license_tooltip_desc = '';
										}

										if ( self::has_license_expired() ) {
											$license_desc = sprintf(
												/* Translators: Key Expired on. */
												esc_html__( 'Your license key expired on %s. Please input a valid non-expired license key. If you think, that this license has not yet expired (was renewed already), then please save the settings, so that the license will verify again and get the up-to-date expiration date.', 'loginpress-pro' ),
												'<strong>' . date_i18n( get_option( 'date_format' ), strtotime( $expiration_date, time() ) ) . '</strong>'
											);
											$license_tooltip_title = '';
											$license_tooltip_desc  = '';
										}

										echo wp_kses_post( $license_desc . '<br /><i>' . $license_tooltip_desc . '</i>' );
									} else {
										echo wp_kses_post( self::get_registered_license_status() );
									}
									?>
								</td>
							</tr>
						</tbody>
					</table>
				</form>
			</div>
		</div>
	</div>
</div>
