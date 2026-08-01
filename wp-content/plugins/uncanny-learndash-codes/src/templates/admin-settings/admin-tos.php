<?php
/**
 * @since   4.0
 * @author  Saad S.
 */
?>
<div class="uncannyowl-admin-section">
	<div class="uncannyowl-admin-header">
		<div class="uncannyowl-admin-title"><?php esc_html_e( 'Terms & Conditions', 'uncanny-learndash-codes' ); ?></div>
	</div>
	<div class="uncannyowl-admin-block">
		<div class="uncannyowl-admin-form">
			<!-- Start process description -->
			<div class="uncannyowl-admin-field">
				<div class="uncannyowl-admin-description">
					<?php esc_html_e( 'To enable a Terms & Conditions checkbox on the Registration Form, enter text for the checkbox below. Does not apply if you are using a Gravity Form or Theme My Login for registration.', 'uncanny-learndash-codes' ) ?>
				</div>
			</div>
			<!-- Start process button -->
			<div class="uncannyowl-admin-field uncannyowl-admin-extra-space">
				<?php wp_editor( $term_conditions, 'uo_codes_term_condition', array( 'editor_height' => '120' ) ); ?>
			</div>
			<!-- Submit -->
			<div class="uncannyowl-admin-field">
				<input type="submit" name="submit" id="submit"
					   class="uncannyowl-btn uncannyowl-btn--secondary uncannyowl-btn-sm"
					   value="<?php esc_html_e( 'Save Changes', 'uncanny-learndash-codes' ); ?>">
			</div>
		</div>
	</div>
</div>
