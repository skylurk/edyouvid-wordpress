<?php
/**
 * @since   4.0
 * @author  Saad S.
 */

$uo_codes_settings = array(
	'invalid-code'          => '',
	'cancelled-code'        => '',
	'expired-code'          => '',
	'already-redeemed'      => '',
	'redeemed-maximum'      => '',
	'successfully-redeemed' => '',
	'already-redeemed-course' => '',
	'already-redeemed-group' => '',
);

if ( null !== $custom_messages ) {
	$uo_codes_settings = array(
		'invalid-code'            => isset( $custom_messages['invalid-code'] ) ? $custom_messages['invalid-code'] : '',
		'cancelled-code'          => isset( $custom_messages['cancelled-code'] ) ? $custom_messages['cancelled-code'] : '',
		'expired-code'            => isset( $custom_messages['expired-code'] ) ? $custom_messages['expired-code'] : '',
		'already-redeemed'        => isset( $custom_messages['already-redeemed'] ) ? $custom_messages['already-redeemed'] : '',
		'already-redeemed-course' => isset( $custom_messages['already-redeemed-course'] ) ? $custom_messages['already-redeemed-course'] : '',
		'already-redeemed-group'  => isset( $custom_messages['already-redeemed-group'] ) ? $custom_messages['already-redeemed-group'] : '',
		'redeemed-maximum'        => isset( $custom_messages['redeemed-maximum'] ) ? $custom_messages['redeemed-maximum'] : '',
		'successfully-redeemed'   => isset( $custom_messages['successfully-redeemed'] ) ? $custom_messages['successfully-redeemed'] : '',
	);
}
?>
<!-- Custom Messages -->
<div class="uncannyowl-admin-section">
	<div class="uncannyowl-admin-header">
		<div class="uncannyowl-admin-title"><?php esc_html_e( 'Custom messages', 'uncanny-learndash-codes' ); ?></div>
	</div>
	<div class="uncannyowl-admin-block">
		<div class="uncannyowl-admin-form">

			<div class="uncannyowl-admin-field  pb-md">
				<label for="invalid-code"><?php esc_html_e( 'Invalid code', 'uncanny-learndash-codes' ); ?></label>
				<input class="uncannyowl-input" type="text"
					   value="<?php echo esc_html( $uo_codes_settings['invalid-code'] ); ?>" name="invalid-code" id="invalid-code"
					   placeholder="<?php esc_html_e( 'Sorry, the code you entered is not valid.', 'uncanny-learndash-codes' ); ?>"/>
			</div>
			
			<div class="uncannyowl-admin-field  pb-md">
				<label for="expired-code"><?php esc_html_e( 'Expired code', 'uncanny-learndash-codes' ); ?></label>
				<input class="uncannyowl-input" type="text"
					   value="<?php echo esc_html( $uo_codes_settings['expired-code'] ); ?>" name="expired-code" id="expired-code"
					   placeholder="<?php esc_html_e( 'Sorry, the code you entered has expired.', 'uncanny-learndash-codes' ); ?>"/>
			</div>

			<div class="uncannyowl-admin-field  pb-md">
				<label for="already-redeemed"><?php esc_html_e( 'Code already redeemed', 'uncanny-learndash-codes' ); ?></label>
				<input class="uncannyowl-input" type="text"
					   value="<?php echo esc_html( $uo_codes_settings['already-redeemed'] ); ?>" name="already-redeemed" id="already-redeemed"
					   placeholder="<?php esc_html_e( 'Sorry, the code you entered is not valid.', 'uncanny-learndash-codes' ); ?>"/>
			</div>

			<div class="uncannyowl-admin-field  pb-md">
				<label for="already-redeemed-course"><?php esc_html_e( 'Courses Code already redeemed', 'uncanny-learndash-codes' ); ?></label>
				<input class="uncannyowl-input" type="text"
					   value="<?php echo esc_html( $uo_codes_settings['already-redeemed-course'] ); ?>" name="already-redeemed-course" id="already-redeemed-course"
					   placeholder="<?php esc_html_e( 'Code already redeemed.', 'uncanny-learndash-codes' ); ?>"/>
			</div>

			<div class="uncannyowl-admin-field  pb-md">
				<label for="already-redeemed-group"><?php esc_html_e( 'Groups Code already redeemed', 'uncanny-learndash-codes' ); ?></label>
				<input class="uncannyowl-input" type="text"
					   value="<?php echo esc_html( $uo_codes_settings['already-redeemed-group'] ); ?>" name="already-redeemed-group" id="already-redeemed-group"
					   placeholder="<?php esc_html_e( 'Code already redeemed.', 'uncanny-learndash-codes' ); ?>"/>
			</div>

			<div class="uncannyowl-admin-field  pb-md">
				<label for="redeemed-maximum"><?php esc_html_e( 'Code redeemed maximum times', 'uncanny-learndash-codes' ); ?></label>
				<input class="uncannyowl-input" type="text"
					   value="<?php echo esc_html( $uo_codes_settings['redeemed-maximum'] ); ?>" name="redeemed-maximum" id="redeemed-maximum"
					   placeholder="<?php esc_html_e( 'Sorry, the code you entered has already been redeemed maximum times.', 'uncanny-learndash-codes' ); ?>"/>
			</div>

			<div class="uncannyowl-admin-field pb-md">
				<label for="cancelled-code"><?php esc_html_e( 'Code cancelled', 'uncanny-learndash-codes' ); ?></label>
				<input class="uncannyowl-input" type="text"
					   value="<?php echo esc_html( $uo_codes_settings['cancelled-code'] ); ?>" name="cancelled-code" id="cancelled-code"
					   placeholder="<?php esc_html_e( 'Sorry, the code you entered is cancelled.', 'uncanny-learndash-codes' ); ?>"/>
			</div>

			<div class="uncannyowl-admin-field pb-md">
				<label for="successfully-redeemed"><?php esc_html_e( 'Successfully redeemed', 'uncanny-learndash-codes' ); ?></label>
				<?php
				if ( null !== $custom_messages ) {
					$allowed_html = wp_kses_allowed_html( 'post' );
					$success_msg  = wp_kses( $custom_messages['successfully-redeemed'], $allowed_html );
				} else {
					$success_msg = esc_html__( 'Congratulations, the code you entered has successfully been redeemed.', 'uncanny-learndash-codes' );
				}
				wp_editor(
					$success_msg,
					'successfully-redeemed',
					array(
						'textarea_rows' => 5,
						'tabindex'      => 40,
					)
				);
				?>
			</div>

			<div class="uncannyowl-admin-field">
				<input type="submit" name="submit" id="submit" class="uncannyowl-btn uncannyowl-btn--secondary uncannyowl-btn-sm"
					   value="<?php esc_html_e( 'Save Changes', 'uncanny-learndash-codes' ); ?>">
			</div>

		</div>
	</div>
</div>
