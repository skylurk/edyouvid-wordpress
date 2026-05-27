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

<div class="uoc-generate-section uoc-generate-batch-name">

	<div class="uoc-generate-section__title">
		<?php esc_html_e( 'Name this batch of codes', 'uncanny-learndash-codes' ); ?> <span
			class="uoc-generate-required-asterisk"></span>
	</div>

	<div class="uoc-generate-section-content">

		<div
			class="uoc-generate-section-form-field"
			id="uoc-generate-batch-name-container"
		>

			<input
				type="text"
				name="batch-name"
				class="uoc-generate-section-form-field__text uoc-generate--always-required-js"

				id="uoc-generate-batch-name"
				required
				maxlength="200"

				value="<?php echo $generate_codes->batch->name; ?>"
				<?php echo $generate_codes->mode === 'edit' ? 'readonly' : ''; ?>
			>

			<div
				class="uoc-generate-section-form-field__error"
				id="uoc-generate-batch-name-error"
			></div>

			<div class="uoc-generate-section-form-field__description">
				<?php esc_html_e( 'The name you set here will help you identify the batch if you sell codes or use them in Automator recipes.', 'uncanny-learndash-codes' ); ?>
				<strong><?php esc_html_e( 'The name must be unique and cannot be changed after code generation.', 'uncanny-learndash-codes' ); ?></strong>
			</div>

		</div>

	</div>

</div>
