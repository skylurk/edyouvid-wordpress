<?php
/**
 * @since   4.0
 * @author  Saad S.
 */

use uncanny_learndash_codes\SharedFunctionality;

?>
<?php if ( SharedFunctionality::is_active( 'woocommerce' ) ) { ?>
	<div class="uncannyowl-admin-section">
		<div class="uncannyowl-admin-header">
			<div class="uncannyowl-admin-title"><?php esc_html_e( 'WooCommerce', 'uncanny-learndash-codes' ); ?></div>
		</div>
		<div class="uncannyowl-admin-block">
			<div class="uncannyowl-admin-field">
				<label class="uncannyowl-toggle">
					<input type="checkbox" value="1" name="autocomplete-codes-orders"
						   id="autocomplete-codes-orders"
						   <?php
							if ( 1 === intval( $autocomplete_settings ) ) {
								echo 'checked="checked"';
							}
							?>
					/>
					<span class="uncannyowl-toggle-slider"></span>
				</label>
				<span class="uncannyowl-toggle-label">
					<?php esc_html_e( 'Automatically change the status of orders that include Uncanny Redemption Codes products to Completed.', 'uncanny-learndash-codes' ); ?>
				</span>
				<!-- Submit -->
				<div class="uncannyowl-admin-field">
					<input type="submit" name="submit" id="submit"
							class="uncannyowl-btn uncannyowl-btn--secondary"
							value="<?php esc_html_e( 'Save Changes', 'uncanny-learndash-codes' ); ?>">
				</div>
			</div>
		</div>
	</div>
<?php } ?>
