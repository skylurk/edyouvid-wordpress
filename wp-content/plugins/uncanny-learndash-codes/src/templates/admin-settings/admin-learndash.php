<?php
/**
 * @since   4.0
 * @author  Saad S.
 */
?>
<!-- LearnDash Group Settings -->
<div class="uncannyowl-admin-section">
	<div class="uncannyowl-admin-header">
		<div class="uncannyowl-admin-title"><?php esc_html_e( 'LearnDash settings', 'uncanny-learndash-codes' ); ?></div>
	</div>
	<div class="uncannyowl-admin-block">
		<div class="uncannyowl-admin-form">
			<div class="uncannyowl-admin-field">
				<label class="uncannyowl-toggle">
					<input type="checkbox" value="1" name="allow-multiple-group-registration"
						   id="allow-multiple-group-registration"<?php if ( 1 === intval( $group_settings ) ) {
						echo 'checked="checked"';
					} ?>/>
					<span class="uncannyowl-toggle-slider"></span>
				</label>
				<span class="uncannyowl-toggle-label">
					<?php esc_html_e( 'Allow users to register in multiple LearnDash groups', 'uncanny-learndash-codes' ); ?>
				</span>

				<?php /* <div class="uncannyowl-admin-description">More info</div> */ ?>
			</div>

			<div class="uncannyowl-admin-field">
				<input type="submit" name="submit" id="submit" class="uncannyowl-btn uncannyowl-btn--secondary uncannyowl-btn-sm"
					   value="<?php esc_html_e( 'Save Changes', 'uncanny-learndash-codes' ); ?>">
			</div>
		</div>
	</div>
</div>
