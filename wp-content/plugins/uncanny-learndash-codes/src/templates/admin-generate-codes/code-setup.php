<?php

namespace uncanny_learndash_codes;

if ( ! defined( 'WPINC' ) ) {
	die;
}
/**
 * @var object $generate_codes
 * @var object $learndash_posts
 * @var object $learndash_groups
 */

?>

<div
	class="uoc-generate-section uoc-generate-code-setup uoc-generate-hidden"
	id="uoc-generate-code-setup"
>

	<div class="uoc-generate-section__title">
		<?php esc_html_e( 'Code settings', 'uncanny-learndash-codes' ); ?>
	</div>

	<div class="uoc-generate-section-content">

		<div class="uoc-generate-section-form">

			<label class="uoc-generate-section-form-field">

				<div class="uoc-generate-section-form-field__label">
					<?php esc_html_e( 'Number of uses per code', 'uncanny-learndash-codes' ); ?> <span
						class="uoc-generate-required-asterisk"></span>
				</div>

				<input
					type="number"
					step="1"
					min="1"
					name="coupon-max-usage"
					id="uoc-generate-coupon-max-usage"

					class="uoc-generate-section-form-field__text uoc-generate--always-required-js"

					required

					value="<?php echo $generate_codes->batch->codes_setup->uses_per_code; ?>"
				>

			</label>

			<div
				class="uoc-generate-notice uoc-generate-notice--warning uoc-generate-notice--invert-margin uoc-generate-hidden"
				id="uoc-generate-code-setup-max-usage-woocommerce-notice"
			>
				<div class="uoc-generate-notice__description">
					<?php printf( esc_html__( 'Batches with codes that can be redeemed more than once cannot be sold as %s products and will not be available on the edit product page.', 'uncanny-learndash-codes' ), 'WooCommerce' ); ?>
					<strong><?php esc_html_e( 'If you plan to sell these codes, this value must be set to 1.', 'uncanny-learndash-codes' ); ?></strong>
				</div>
			</div>

			<div class="uoc-generate-section-form-row uoc-generate-section-form-row--half">
				<label class="uoc-generate-section-form-field">

					<div class="uoc-generate-section-form-field__label">
						<?php esc_html_e( 'Expiry date (optional)', 'uncanny-learndash-codes' ); ?>
					</div>

					<input
						type="date"
						name="expiry-date"
						id="uoc-generate-expiry-date"

						min="<?php echo date_i18n( 'Y-m-d' ); ?>"

						class="uoc-generate-section-form-field__text"

						value="<?php echo $generate_codes->batch->codes_setup->expiry_date; ?>"
					>

				</label>

				<label class="uoc-generate-section-form-field">

					<div class="uoc-generate-section-form-field__label">
						<?php esc_html_e( 'Expiry time (optional)', 'uncanny-learndash-codes' ); ?>
					</div>

					<input
						type="time"
						name="expiry-time"
						id="uoc-generate-expiry-time"

						class="uoc-generate-section-form-field__text"

						value="<?php echo $generate_codes->batch->codes_setup->expiry_time; ?>"
					>

				</label>
			</div>

			<?php if ( $generate_codes->mode != 'edit' ) { ?>

				<div class="uoc-generate-section-form-row">

					<div class="uoc-generate-section-form-field">

						<div class="uoc-generate-section-form-field__label">
							<?php esc_html_e( 'Generation method', 'uncanny-learndash-codes' ); ?> <span
								class="uoc-generate-required-asterisk"></span>
						</div>

						<label class="uoc-generate-section-form-field">
							<div class="uoc-generate-section-form-field__radio">
								<input
									type="radio"
									name="generation-type"
									value="auto"

									class="uoc-generate-generation-method--js uoc-generate-section-form-field__radio-element uoc-generate--always-required-js"

									required

									<?php echo $generate_codes->batch->codes_setup->generation_method->method === 'auto' ? 'checked' : ''; ?>
								>

								<div class="uoc-generate-section-form-field__radio-description">
									<?php esc_html_e( 'Auto-generate codes', 'uncanny-learndash-codes' ); ?>
								</div>
							</div>
						</label>

						<label class="uoc-generate-section-form-field">
							<div class="uoc-generate-section-form-field__radio">
								<input
									type="radio"
									name="generation-type"
									value="manual"

									class="uoc-generate-generation-method--js uoc-generate-section-form-field__radio-element uoc-generate--always-required-js"

									required

									<?php echo $generate_codes->batch->codes_setup->generation_method->method === 'manual' ? 'checked' : ''; ?>
								>

								<div class="uoc-generate-section-form-field__radio-description">
									<?php esc_html_e( 'Manually enter codes', 'uncanny-learndash-codes' ); ?>
								</div>
							</div>
						</label>

					</div>

				</div>

				<div
					class="uoc-generate-subsection uoc-generate-hidden"
					id="uoc-generate-generation-method-auto"
				>

					<label class="uoc-generate-section-form-field">

						<div class="uoc-generate-section-form-field__label">
							<?php esc_html_e( 'Number of unique codes', 'uncanny-learndash-codes' ); ?> <span
								class="uoc-generate-required-asterisk"></span>
						</div>

						<input
							type="number"
							step="1"
							min="1"

							name="coupon-amount"
							id="uoc-generate-coupon-amount"

							class="uoc-generate-section-form-field__text"

							value="<?php echo $generate_codes->batch->codes_setup->generation_method->auto->number_of_codes; ?>"
						>

					</label>

					<label class="uoc-generate-section-form-field">

						<div class="uoc-generate-section-form-field__label">
							<?php esc_html_e( 'Number of characters', 'uncanny-learndash-codes' ); ?> <span
								class="uoc-generate-required-asterisk"></span>
						</div>

						<input
							type="number"
							step="1"
							min="1"

							name="coupon-length"
							id="uoc-generate-coupon-length"

							class="uoc-generate-section-form-field__text"

							value="<?php echo $generate_codes->batch->codes_setup->generation_method->auto->number_of_characters; ?>"
						>

					</label>

					<div class="uoc-generate-section-form-field">

						<div class="uoc-generate-section-form-field__label">
							<?php esc_html_e( 'Type of characters', 'uncanny-learndash-codes' ); ?> <span
								class="uoc-generate-required-asterisk"></span>
						</div>

						<label class="uoc-generate-section-form-field">
							<div class="uoc-generate-section-form-field__checkbox">
								<input
									type="checkbox"
									name="coupon-character-type[]"
									value="lowercase-letters"

									class="uoc-generate-section-form-field__checkbox-element"

									<?php echo in_array( 'lowercase-letters', $generate_codes->batch->codes_setup->generation_method->auto->type_of_characters ) ? 'checked' : ''; ?>
								>

								<div class="uoc-generate-section-form-field__radio-description">
									<?php esc_html_e( 'Lowercase letters', 'uncanny-learndash-codes' ); ?>
								</div>
							</div>
						</label>

						<label class="uoc-generate-section-form-field">
							<div class="uoc-generate-section-form-field__checkbox">
								<input
									type="checkbox"
									name="coupon-character-type[]"
									value="uppercase-letters"

									class="uoc-generate-section-form-field__checkbox-element"

									<?php echo in_array( 'uppercase-letters', $generate_codes->batch->codes_setup->generation_method->auto->type_of_characters ) ? 'checked' : ''; ?>
								>

								<div class="uoc-generate-section-form-field__radio-description">
									<?php esc_html_e( 'Uppercase letters', 'uncanny-learndash-codes' ); ?>
								</div>
							</div>
						</label>

						<label class="uoc-generate-section-form-field">
							<div class="uoc-generate-section-form-field__checkbox">
								<input
									type="checkbox"
									name="coupon-character-type[]"
									value="numbers"

									class="uoc-generate-section-form-field__checkbox-element"

									<?php echo in_array( 'numbers', $generate_codes->batch->codes_setup->generation_method->auto->type_of_characters ) ? 'checked' : ''; ?>
								>

								<div class="uoc-generate-section-form-field__radio-description">
									<?php esc_html_e( 'Numbers', 'uncanny-learndash-codes' ); ?>
								</div>
							</div>
						</label>

					</div>

					<label class="uoc-generate-section-form-field">

						<div class="uoc-generate-section-form-field__label">
							<?php esc_html_e( 'Dash separation', 'uncanny-learndash-codes' ); ?> <span
								class="uoc-generate-required-asterisk"></span>
						</div>

						<input
							type="text"

							name="coupon-dash"
							id="uoc-generate-coupon-dash"

							class="uoc-generate-section-form-field__text"

							value="<?php echo $generate_codes->batch->codes_setup->generation_method->auto->dash_separation; ?>"
						>

					</label>

					<div class="uoc-generate-section-form-row uoc-generate-section-form-row--half">

						<label class="uoc-generate-section-form-field">

							<div class="uoc-generate-section-form-field__label">
								<?php esc_html_e( 'Prefix (optional)', 'uncanny-learndash-codes' ); ?>
							</div>

							<input
								type="text"

								name="coupon-prefix"
								id="uoc-generate-coupon-prefix"

								class="uoc-generate-section-form-field__text"

								value="<?php echo $generate_codes->batch->codes_setup->generation_method->auto->prefix; ?>"
							>

							<div class="uoc-generate-section-form-field__description">
								<?php esc_html_e( 'Only alphanumeric characters allowed.', 'uncanny-learndash-codes' ); ?>
							</div>

						</label>

						<label class="uoc-generate-section-form-field">

							<div class="uoc-generate-section-form-field__label">
								<?php esc_html_e( 'Suffix (optional)', 'uncanny-learndash-codes' ); ?>
							</div>

							<input
								type="text"

								name="coupon-suffix"
								id="uoc-generate-coupon-suffix"

								class="uoc-generate-section-form-field__text"

								value="<?php echo $generate_codes->batch->codes_setup->generation_method->auto->suffix; ?>"
							>

							<div class="uoc-generate-section-form-field__description">
								<?php esc_html_e( 'Only alphanumeric characters allowed.', 'uncanny-learndash-codes' ); ?>
							</div>

						</label>

					</div>

					<div class="uoc-generate-code-preview">
						<div class="uoc-generate-code-preview__title">
							<?php esc_html_e( 'Sample code', 'uncanny-learndash-codes' ); ?>
						</div>
						<div
							class="uoc-generate-code-preview__code"
							id="uoc-generate-code-preview-fake-code"
						>—
						</div>
					</div>

				</div>

				<div
					class="uoc-generate-subsection uoc-generate-hidden"
					id="uoc-generate-generation-method-manual"
				>
					<label class="uoc-generate-section-form-field">

						<div class="uoc-generate-section-form-field__label">
							<?php esc_html_e( 'Enter one code per line', 'uncanny-learndash-codes' ); ?> <span
								class="uoc-generate-required-asterisk"></span>
						</div>

						<div
							class="uoc-generate-section-form-field__description uoc-generate-section-form-field__description--top">
							<?php printf( esc_html__( 'Codes may only contain alphanumeric characters and hyphens and must be between %s and %s characters long.', 'uncanny-learndash-codes' ), '<u>4</u>', '<u>30</u>' ); ?>
						</div>

						<textarea
							name="manual-codes"
							rows="10"

							class="uoc-generate-section-form-field__textarea"

							placeholder="<?php echo esc_html__( 'Example:', 'uncanny-learndash-codes' ) . "\nQNUV-MV3S-R7NN-5E9K-362G" . "\nZFJV-PDE9-63QN-8CM8-NCUN" . "\nUNWL-8BJY-T8H6-26W1-ZC6P"; ?>"><?php echo $generate_codes->batch->codes_setup->generation_method->manual->manual_codes; ?></textarea>

					</label>
				</div>

			<?php } ?>

			<div class="uoc-generate-subsection">
				<div class="uoc-generate-section-form-row">

					<input
						type="submit"
						id="uoc-generate-submit-button"
						value="<?php if ( 'edit' === (string) $generate_codes->mode ) {
							_e( 'Modify codes', 'uncanny-learndash-codes' );
						} else {
							_e( 'Generate codes', 'uncanny-learndash-codes' );
						} ?>"

						class="uoc-generate-button uoc-generate-button--primary"
					>

				</div>
			</div>

		</div>

	</div>

</div>
