<?php

use uncanny_learndash_codes\Config;

/**
 * @since   4.0
 * @author  Saad S.
 */
?>
<!-- Theme my Login -->
<?php if ( class_exists( '\Theme_My_Login' ) ) { ?>
	<?php
	if ( is_multisite() ) {
		$tml_registration_field = get_blog_option( get_current_blog_id(), Config::$uncanny_codes_tml_template_override, 0 );
		$tml_required_field     = get_blog_option( get_current_blog_id(), Config::$uncanny_codes_tml_codes_required_field, 0 );
	} else {
		$tml_registration_field = get_option( Config::$uncanny_codes_tml_template_override, 0 );
		$tml_required_field     = get_option( Config::$uncanny_codes_tml_codes_required_field, 0 );
	}
	?>

	<div class="uo-admin-section">
		<div class="uo-admin-header">
			<div
				class="uo-admin-title"><?php esc_html_e( 'Theme My Login registration settings', 'uncanny-learndash-codes' ); ?></div>
		</div>
		<div class="uo-admin-block">
			<form method="post" action="" id="uncanny-learndash-codes-form">
				<input type="hidden" name="_wp_http_referer"
					   value="<?php echo admin_url( 'admin.php?page=uncanny-learndash-codes-settings&saved=true' ); ?>"/>
				<input type="hidden" name="_tml_wpnonce"
					   value="<?php echo wp_create_nonce( Config::get_project_name() ); ?>"/>

				<div class="uo-admin-form">
					<div class="uo-admin-field">
						<label class="uo-checkbox">
							<input type="checkbox" value="1" name="tml-replace-registration-form"
								   id="tml-replace-registration-form"<?php if ( 1 === intval( $tml_registration_field ) ) {
								echo 'checked="checked"';
							} ?>>
							<div class="uo-checkmark"></div>
							<span class="uo-label">
													<?php esc_html_e( 'Use Custom Theme My Login registration form that includes Registration Code field', 'uncanny-learndash-codes' ); ?>
												</span>
						</label>
					</div>

					<div class="uo-admin-field">
						<label class="uo-checkbox">
							<input type="checkbox" value="1" name="tml-code-required-field"
								   id="tml-code-required-field"<?php if ( 1 === intval( $tml_required_field ) ) {
								echo 'checked="checked"';
							} ?>>
							<div class="uo-checkmark"></div>
							<span class="uo-label">
													<?php _e( 'Make <strong>Registration code</strong> field required', 'uncanny-learndash-codes' ); ?>
												</span>
						</label>
					</div>

					<div class="uo-admin-field">
						<input type="submit" name="submit" id="submit" class="uo-admin-form-submit"
							   value="<?php esc_html_e( 'Save Theme My Login Changes', 'uncanny-learndash-codes' ); ?>">
					</div>

				</div>
			</form>
		</div>
	</div>
<?php } ?>
