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

<div class="uoc-generate-section uoc-generate-dependency">

	<div class="uoc-generate-section__title">
		<?php esc_html_e( 'How are the codes going to be used?', 'uncanny-learndash-codes' ); ?> <span
			class="uoc-generate-required-asterisk"></span>
	</div>

	<div class="uoc-generate-section-content">

		<div class="uoc-generate-dependency-general">

			<label
				class="uoc-generate-dependency-option uoc-generate-dependency-option--automator"
				data-option="automator"
			>
				<div class="uoc-generate-dependency-option__checkbox">
					<div class="uoc-generate-section-form-field">
						<input
							type="radio"
							name="dependency"
							value="automator"

							class="uoc-generate-dependency-option--js uoc-generate-section-form-field__radio uoc-generate--always-required-js"

							required

							<?php echo $generate_codes->batch->dependency === 'automator' ? 'checked' : ''; ?>
						>
					</div>
				</div>

				<div
					class="uoc-generate-dependency-option__icon uoc-generate-dependency-option__icon--automator"
					alt="Uncanny Automator"
				></div>

				<div class="uoc-generate-dependency-option__content">

					<div class="uoc-generate-dependency-option__title">
						Uncanny Automator
					</div>

					<div class="uoc-generate-dependency-option__description">
						<?php esc_html_e( 'Define exactly what will happen when a code is redeemed using an Automator recipe. This code type can also be sold if WooCommerce is installed.', 'uncanny-learndash-codes' ); ?>
					</div>

					<div
						class="uoc-generate-notice uoc-generate-notice--warning uoc-generate-hidden"
						id="uoc-generate-dependency-automator-install"
					>
						<div class="uoc-generate-notice__title">
							<?php printf( esc_html__( 'You must install %s to continue', 'uncanny-learndash-codes' ), 'Uncanny Automator' ); ?>
						</div>

						<div class="uoc-generate-notice__description">
							<?php printf( esc_html__( '%s is required to link code redemption to other plugins and apps. Install the free version to unlock code use in recipes.', 'uncanny-learndash-codes' ), 'Uncanny Automator' ); ?>
						</div>

						<div class="uoc-generate-notice__action">
							<?php
							$plugin_activate = new Auto_Plugin_Install();
							echo $plugin_activate->button( 'uncanny-automator' );
							?>
						</div>

					</div>

					<div
						class="uoc-generate-notice uoc-generate-notice--warning uoc-generate-hidden"
						id="uoc-generate-dependency-automator-update"
					>
						<div class="uoc-generate-notice__title">
							<?php printf( esc_html__( 'Please update %s', 'uncanny-learndash-codes' ), 'Uncanny Automator' ); ?>
						</div>

						<div class="uoc-generate-notice__description">
							<?php printf( esc_html__( '%1$s %2$s or higher is required to create recipes linked to a specific set of codes. Please upgrade Automator to use these codes in a recipe.', 'uncanny-learndash-codes' ), 'Uncanny Automator', '2.11.1' ); ?>
						</div>

					</div>

					<div
						class="uoc-generate-notice uoc-generate-notice--info uoc-generate-hidden"
						id="uoc-generate-dependency-automator-what-now"
					>
						<div class="uoc-generate-notice__title">
							<?php esc_html_e( 'Cool! Make sure to set up a recipe', 'uncanny-learndash-codes' ); ?>
						</div>
						<div class="uoc-generate-notice__description">
							<?php esc_html_e( 'After generating your codes you’ll be taken directly into a new recipe. Add your actions, make the recipe live and you can start redeeming codes!', 'uncanny-learndash-codes' ); ?>
						</div>

					</div>

				</div>
			</label>

			<label
				class="uoc-generate-dependency-option uoc-generate-dependency-option--learndash"
				data-option="learndash"
			>
				<div class="uoc-generate-dependency-option__checkbox">
					<div class="uoc-generate-section-form-field">
						<input
							type="radio"
							name="dependency"
							value="learndash"

							class="uoc-generate-dependency-option--js uoc-generate-section-form-field__radio uoc-generate--always-required-js"

							required

							<?php echo $generate_codes->batch->dependency === 'learndash' ? 'checked' : ''; ?>
						>
					</div>
				</div>

				<div
					class="uoc-generate-dependency-option__icon uoc-generate-dependency-option__icon--learndash"
					alt="LearnDash"
				></div>

				<div class="uoc-generate-dependency-option__content">

					<div class="uoc-generate-dependency-option__title">
						LearnDash
					</div>

					<div class="uoc-generate-dependency-option__description">
						<?php esc_html_e( 'Enroll users into courses or groups when they redeem a code.', 'uncanny-learndash-codes' ); ?>
					</div>

					<div
						class="uoc-generate-notice uoc-generate-notice--warning uoc-generate-hidden"
						id="uoc-generate-dependency-learndash-install"
					>
						<div class="uoc-generate-notice__title">
							<?php printf( esc_html__( 'You must install %s to continue', 'uncanny-learndash-codes' ), 'LearnDash' ); ?>
						</div>

						<div class="uoc-generate-notice__description">
							<?php printf( esc_html__( '%s is a popular LMS plugin for WordPress. Uncanny Codes supports adding users to courses and groups when codes are redeemed.', 'uncanny-learndash-codes' ), 'LearnDash' ); ?>
						</div>

						<div class="uoc-generate-notice__action">
							<a
								href="https://www.uncannyowl.com/share/learndash/"
								class="uoc-generate-button uoc-generate-notice__button"
								target="_blank">
								<?php esc_html_e( 'Learn more', 'uncanny-learndash-codes' ); ?>
							</a>
						</div>

					</div>

				</div>
			</label>

		</div>

		<div class="uoc-generate-dependency-specific">

			<div
				class="uoc-generate-hidden"
				id="uoc-generate-dependency-specific-learndash"
			>

				<div
					class="uoc-generate-subsection"
					id="uoc-generate-dependency-specific-learndash-courses-or-groups"
				>

					<div class="uoc-generate-section__title">
						<?php esc_html_e( "What's going to happen when users redeem the code?", 'uncanny-learndash-codes' ); ?>
						<span class="uoc-generate-required-asterisk"></span>
					</div>

					<div class="uoc-generate-section-form">
						<label class="uoc-generate-section-form-field">
							<div class="uoc-generate-section-form-field__radio">
								<input
									type="radio"
									name="learndash-content"
									value="learndash-courses"

									class="uoc-generate-dependency-ld-content-option--js uoc-generate-section-form-field__radio-element"

									<?php echo $generate_codes->batch->learndash->content === 'course' ? 'checked' : ''; ?>
								>

								<div class="uoc-generate-section-form-field__radio-description">
									<?php esc_html_e( 'Enroll users into courses', 'uncanny-learndash-codes' ); ?>
								</div>
							</div>
						</label>

						<label class="uoc-generate-section-form-field">
							<div class="uoc-generate-section-form-field__radio">
								<input
									type="radio"
									name="learndash-content"
									value="learndash-groups"

									class="uoc-generate-dependency-ld-content-option--js uoc-generate-section-form-field__radio-element"

									<?php echo $generate_codes->batch->learndash->content === 'group' ? 'checked' : ''; ?>
								>

								<div class="uoc-generate-section-form-field__radio-description">
									<?php esc_html_e( 'Enroll users into groups', 'uncanny-learndash-codes' ); ?>
								</div>
							</div>
						</label>
					</div>

				</div>

				<div
					class="uoc-generate-subsection uoc-generate-hidden"
					id="uoc-generate-dependency-specific-learndash-courses"
				>

					<div class="uoc-generate-section__title">
						<?php printf( esc_html__( '%s courses', 'uncanny-learndash-codes' ), 'LearnDash' ); ?> <span
							class="uoc-generate-required-asterisk"></span>
					</div>

					<div class="uoc-generate-section-form-field">
						<select
							name="learndash-courses[]"
							class="uoc-generate-section-form-field__select uoc-generate--select2-js"
							multiple
						>
							<?php

							foreach ( $learndash_posts->courses as $course_id => $course_label ) {
								?>

								<option
									value="<?php echo $course_id; ?>"
									<?php echo in_array( $course_id, $generate_codes->batch->learndash->courses ) ? 'selected' : ''; ?>
								>
									<?php echo $course_label; ?>
								</option>

								<?php
							}

							?>
						</select>
					</div>

				</div>

				<div
					class="uoc-generate-subsection uoc-generate-hidden"
					id="uoc-generate-dependency-specific-learndash-groups"
				>

					<div class="uoc-generate-section__title">
						<?php printf( esc_html__( '%s groups', 'uncanny-learndash-codes' ), 'LearnDash' ); ?> <span
							class="uoc-generate-required-asterisk"></span>
					</div>

					<div class="uoc-generate-section-form-field">
						<select
							name="learndash-groups[]"
							class="uoc-generate-section-form-field__select uoc-generate--select2-js"
							multiple
						>
							<?php

							foreach ( $learndash_posts->groups as $group_id => $group_label ) {
								?>

								<option
									value="<?php echo $group_id; ?>"
									<?php echo in_array( $group_id, $generate_codes->batch->learndash->groups ) ? 'selected' : ''; ?>
								>
									<?php echo $group_label; ?>
								</option>

								<?php
							}

							?>
						</select>
					</div>

				</div>

				<div
					class="uoc-generate-subsection"
					id="uoc-generate-dependency-specific-learndash-code-type"
				>

					<div class="uoc-generate-section__title">
						<?php esc_html_e( "What's the code type?", 'uncanny-learndash-codes' ); ?> <span
							class="uoc-generate-required-asterisk"></span>
					</div>

					<div class="uoc-generate-section-form">

						<label class="uoc-generate-section-form-field">
							<div class="uoc-generate-section-form-field__radio">
								<input
									type="radio"
									name="learndash-code-type"
									value="default"
									checked

									class="uoc-generate-section-form-field__radio-element"

									<?php echo $generate_codes->batch->learndash->code_type === 'default' ? 'checked' : ''; ?>
								>

								<div class="uoc-generate-section-form-field__radio-description">
									<div class="uoc-generate-section-form-field__radio-description-title">
										<?php esc_html_e( 'Default', 'uncanny-learndash-codes' ); ?>

										<div class="uoc-generate-tag uoc-generate-tag--info">
											<?php esc_html_e( 'Recommended', 'uncanny-learndash-codes' ); ?>
										</div>
									</div>
									<div class="uoc-generate-section-form-field__radio-description-subtitle">
										<?php esc_html_e( 'Code redemption on registration or while logged in immediately adds the user to courses or groups. This code type is not linked to a purchase.', 'uncanny-learndash-codes' ); ?>
									</div>
								</div>
							</div>
						</label>

						<label class="uoc-generate-section-form-field">
							<div class="uoc-generate-section-form-field__radio">
								<input
									type="radio"
									name="learndash-code-type"
									value="paid"

									class="uoc-generate-section-form-field__radio-element"

									<?php echo $generate_codes->batch->learndash->code_type === 'learndash-code-type-paid' ? 'checked' : ''; ?>
								>

								<div class="uoc-generate-section-form-field__radio-description">
									<div class="uoc-generate-section-form-field__radio-description-title">
										<?php esc_html_e( 'Prepaid', 'uncanny-learndash-codes' ); ?>

										<div class="uoc-generate-tag uoc-generate-tag--warning">
											<?php printf( esc_html__( 'Requires %s', 'uncanny-learndash-codes' ), 'WooCommerce' ); ?>
										</div>
									</div>
									<div class="uoc-generate-section-form-field__radio-description-subtitle">
										<?php esc_html_e( 'Codes are redeemed during a WooCommerce purchase and the cost of the associated product is reduced to $0.', 'uncanny-learndash-codes' ); ?>
									</div>
								</div>
							</div>
						</label>

						<label class="uoc-generate-section-form-field">
							<div class="uoc-generate-section-form-field__radio">
								<input
									type="radio"
									name="learndash-code-type"
									value="unpaid"

									class="uoc-generate-section-form-field__radio-element"

									<?php echo $generate_codes->batch->learndash->code_type === 'unpaid' ? 'checked' : ''; ?>
								>

								<div class="uoc-generate-section-form-field__radio-description">
									<div class="uoc-generate-section-form-field__radio-description-title">
										<?php esc_html_e( 'Not prepaid', 'uncanny-learndash-codes' ); ?>

										<div class="uoc-generate-tag uoc-generate-tag--warning">
											<?php printf( esc_html__( 'Requires %s', 'uncanny-learndash-codes' ), 'WooCommerce' ); ?>
										</div>
									</div>
									<div class="uoc-generate-section-form-field__radio-description-subtitle">
										<?php esc_html_e( 'Codes are redeemed during a WooCommerce purchase and the cost of the product is not adjusted. The code is used to unlock access to purchase the product.', 'uncanny-learndash-codes' ); ?>
									</div>
								</div>
							</div>
						</label>

					</div>

				</div>

			</div>

		</div>

	</div>

</div>
