<?php
/**
 * @since   4.0
 * @author  Saad S.
 */
?>
<!-- LearnDash Group Settings -->
<div class="uncannyowl-admin-section">
	<div class="uncannyowl-admin-header">
		<div class="uncannyowl-admin-title"><?php esc_html_e( 'Multiple code redemptions', 'uncanny-learndash-codes' ); ?></div>
	</div>
	<div class="uncannyowl-admin-block">
		<div class="uncannyowl-admin-form">
			<div class="uncannyowl-admin-field">
				<label class="uncannyowl-toggle">
					<input type="checkbox" value="1" name="allow-user-to-redeem-same-code"
						   id="allow-user-to-redeem-same-code"
						<?php
						if ( 1 === intval( $allow_user_redeem_same_code ) ) {
							echo 'checked="checked"';
						}
						?>
					/>
					<span class="uncannyowl-toggle-slider"></span>
				</label>
				<span class="uncannyowl-toggle-label">
					<?php esc_html_e( 'Allow users to redeem the same code multiple times', 'uncanny-learndash-codes' ); ?>
				</span>

				<?php /* <div class="uncannyowl-admin-description">More info</div> */ ?>
			</div>

			<div class="uncannyowl-admin-field">
				<label for="times_code_can_be_reused">
					<?php esc_html_e( 'Number of times the same code can be redeemed by one user', 'uncanny-learndash-codes' ); ?>
				</label>
				<input type="number" class="uncannyowl-input" max="100" min="1"
					   value="<?php echo absint( $times_code_can_be_reused ); ?>"
					   name="times_code_can_be_reused"
					   id="times_code_can_be_reused"
				/>

				<div class="uncannyowl-admin-description">
					<?php echo esc_html__( 'Enter a value between 1 and 100.', 'uncanny-learndash-codes' ) ?>
				</div>
			</div>

			<div class="uncannyowl-admin-field ">
				<input type="submit" name="submit" id="submit" class="uncannyowl-btn uncannyowl-btn--secondary uncannyowl-btn-sm"
					   value="<?php esc_html_e( 'Save Changes', 'uncanny-learndash-codes' ); ?>">
			</div>
		</div>
	</div>
</div>
