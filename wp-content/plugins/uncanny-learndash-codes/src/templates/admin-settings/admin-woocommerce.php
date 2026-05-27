<?php
/**
 * @since   4.0
 * @author  Saad S.
 */

use uncanny_learndash_codes\SharedFunctionality;

?>
<?php if ( SharedFunctionality::is_active( 'woocommerce' ) ) { ?>
	<div class="uo-admin-section">
		<div class="uo-admin-header">
			<div class="uo-admin-title"><?php esc_html_e( 'WooCommerce', 'uncanny-learndash-codes' ); ?></div>
		</div>
		<div class="uo-admin-block">
			<div class="uo-admin-field">
				<label class="uo-checkbox">
					<input type="checkbox" value="1" name="autocomplete-codes-orders"
						   id="autocomplete-codes-orders"<?php if ( 1 === intval( $autocomplete_settings ) ) {
						echo 'checked="checked"';
					} ?>/>
					<div class="uo-checkmark"></div>
					<span class="uo-label">
												<?php esc_html_e( 'Automatically change the status of orders that include Uncanny Codes products to Completed.', 'uncanny-learndash-codes' ); ?>
											</span>
				</label>
				<!-- Submit -->
				<div class="uo-admin-field">
					<br/><input type="submit" name="submit" id="submit"
								class="uo-admin-form-submit"
								value="<?php esc_html_e( 'Save Changes', 'uncanny-learndash-codes' ); ?>">
				</div>
			</div>
		</div>
	</div>
<?php } ?>
