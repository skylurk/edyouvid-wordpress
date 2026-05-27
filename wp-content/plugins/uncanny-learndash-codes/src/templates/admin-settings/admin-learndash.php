<?php
/**
 * @since   4.0
 * @author  Saad S.
 */
?>
<!-- LearnDash Group Settings -->
<div class="uo-admin-section">
	<div class="uo-admin-header">
		<div class="uo-admin-title"><?php esc_html_e( 'LearnDash settings', 'uncanny-learndash-codes' ); ?></div>
	</div>
	<div class="uo-admin-block">
		<div class="uo-admin-form">
			<div class="uo-admin-field">
				<label class="uo-checkbox">
					<input type="checkbox" value="1" name="allow-multiple-group-registration"
						   id="allow-multiple-group-registration"<?php if ( 1 === intval( $group_settings ) ) {
						echo 'checked="checked"';
					} ?>/>
					<div class="uo-checkmark"></div>
					<span class="uo-label">
												<?php esc_html_e( 'Allow users to register in multiple LearnDash groups', 'uncanny-learndash-codes' ); ?>
											</span>
				</label>

				<?php /* <div class="uo-admin-description">More info</div> */ ?>
			</div>

			<div class="uo-admin-field">
				<input type="submit" name="submit" id="submit" class="uo-admin-form-submit"
					   value="<?php esc_html_e( 'Save Changes', 'uncanny-learndash-codes' ); ?>">
			</div>
		</div>
	</div>
</div>
