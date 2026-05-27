<?php
/**
 * @since   4.0
 * @author  Saad S.
 */
?>
<div class="uo-admin-section">
	<div class="uo-admin-header">
		<div class="uo-admin-title"><?php esc_html_e( 'Terms & Conditions', 'uncanny-learndash-codes' ); ?></div>
	</div>
	<div class="uo-admin-block">
		<div class="uo-admin-form">
			<!-- Start process description -->
			<div class="uo-admin-field">
				<div
					class="uo-admin-description"><?php esc_html_e( 'To enable a Terms & Conditions checkbox on the Registration Form, enter text for the checkbox below. Does not apply if you are using a Gravity Form or Theme My Login for registration.', 'uncanny-learndash-codes' ) ?></div>
			</div>
			<!-- Start process button -->
			<div class="uo-admin-field uo-admin-extra-space">
				<?php wp_editor( $term_conditions, 'uo_codes_term_condition', array( 'editor_height' => '120' ) ); ?>
			</div>
			<!-- Submit -->
			<div class="uo-admin-field">
				<input type="submit" name="submit" id="submit"
					   class="uo-admin-form-submit"
					   value="<?php esc_html_e( 'Save Changes', 'uncanny-learndash-codes' ); ?>">
			</div>
		</div>
	</div>
</div>
