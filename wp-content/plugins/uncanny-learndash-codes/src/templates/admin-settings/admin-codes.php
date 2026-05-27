<?php
/**
 * @since   4.0
 * @author  Saad S.
 */
?>
<!-- LearnDash Group Settings -->
<div class="uo-admin-section">
	<div class="uo-admin-header">
		<div class="uo-admin-title"><?php esc_html_e( 'Multiple code redemptions', 'uncanny-learndash-codes' ); ?></div>
	</div>
	<div class="uo-admin-block">
		<div class="uo-admin-form">
			<div class="uo-admin-field">
				<label class="uo-checkbox">
					<input type="checkbox" value="1" name="allow-user-to-redeem-same-code"
						   id="allow-user-to-redeem-same-code"
						<?php
						if ( 1 === intval( $allow_user_redeem_same_code ) ) {
							echo 'checked="checked"';
						}
						?>
					/>
					<div class="uo-checkmark"></div>
					<span class="uo-label">
												<?php esc_html_e( 'Allow users to redeem the same code multiple times', 'uncanny-learndash-codes' ); ?>
											</span>
				</label>

				<?php /* <div class="uo-admin-description">More info</div> */ ?>
			</div>

			<div class="uo-admin-field">
				<label class="uo-text">
					<span
						class="uo-admin-label"><?php esc_html_e( 'Number of times the same code can be redeemed by one user', 'uncanny-learndash-codes' ); ?></span>
					<br/>
					<input type="number" class="uo-admin-input" max="100" min="1"
						   value="<?php echo absint( $times_code_can_be_reused ); ?>"
						   name="times_code_can_be_reused"
						   id="times_code_can_be_reused"
					/>
				</label>

				<div
					class="uo-admin-description"><?php echo esc_html__( 'Enter a value between 1 and 100.', 'uncanny-learndash-codes' ) ?></div>
			</div>

			<div class="uo-admin-field">
				<input type="submit" name="submit" id="submit" class="uo-admin-form-submit"
					   value="<?php esc_html_e( 'Save Changes', 'uncanny-learndash-codes' ); ?>">
			</div>
		</div>
	</div>
</div>
