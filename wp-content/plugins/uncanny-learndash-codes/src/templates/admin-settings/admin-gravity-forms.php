<?php

if ( class_exists( '\GFFormsModel' ) && function_exists( 'gf_user_registration' ) ) {
	$forms = GFFormsModel::get_forms();

	foreach ( $forms as $form ) {
		$results = gf_user_registration()->get_feeds( $form->id );
		if ( $results ) {
			printf( '<div id="form-id-%d" style="display:none" data-register="1"></div>', $form->id );
		} else {
			printf( '<div id="form-id-%d" style="display:none" data-register="0"></div>', $form->id );
		}
	}

	?>

	<!-- Gravity Forms -->
	<div class="uo-admin-section">
		<div class="uo-admin-header">
			<div
				class="uo-admin-title"><?php esc_html_e( 'Gravity Forms settings', 'uncanny-learndash-codes' ); ?></div>
		</div>
		<div class="uo-admin-block">
			<div class="uo-admin-form">
				<?php if ( ! empty( $existing ) ) { ?>
					<div class="uo-admin-field">
						<div
							class="uo-admin-label"><?php esc_html_e( 'Select form', 'uncanny-learndash-codes' ); ?></div>

						<select class="uo-admin-select" name="registration_form"
								id="registration_form">
							<option value="0"><?php esc_html_e( 'Select form', 'uncanny-learndash-codes' ); ?></option>
							<?php foreach ( $forms as $form ) { ?>
								<option
									<?php if ( $form->id === $existing ) {
										echo 'selected="selected"';
									} ?>
									value="<?php echo esc_attr( $form->id ) ?>"><?php echo esc_html( $form->title ); ?></option>
							<?php } ?>
						</select>
					</div>

					<div class="uo-admin-field">
						<div
							class="uo-admin-label"><?php esc_html_e( 'Mandatory code field', 'uncanny-learndash-codes' ); ?></div>

						<label class="uo-checkbox">
							<input type="checkbox" value="1" name="registration-field-mandatory"
								   id="registration-field-mandatory"<?php if ( 1 === intval( $code_field_mandatory ) ) {
								echo 'checked="checked"';
							} ?>>
							<div class="uo-checkmark"></div>
							<span class="uo-label">
													<?php esc_html_e( 'Make Code field mandatory on User Registration form.', 'uncanny-learndash-codes' ); ?>
												</span>
						</label>
					</div>
				<?php } ?>
				<div class="uo-admin-field">
					<div
						class="uo-admin-label"><?php esc_html_e( 'User registration field label', 'uncanny-learndash-codes' ); ?></div>

					<input class="uo-admin-input" type="text"
						   value="<?php if ( null !== $code_field_label ) {
							   echo esc_html( $code_field_label );
						   } ?>" name="code_field_label" id="code_field_label" class="widefat"
						   placeholder="<?php esc_html_e( 'Enter Registration Code', 'uncanny-learndash-codes' ); ?>"/>
				</div>

				<div class="uo-admin-field">
					<div
						class="uo-admin-label"><?php esc_html_e( 'User registration field error message', 'uncanny-learndash-codes' ); ?></div>

					<input class="uo-admin-input" type="text"
						   value="<?php if ( null !== $code_field_error_message ) {
							   echo esc_html( $code_field_error_message );
						   } ?>" name="code_field_error_message" id="code_field_error_message"
						   class="widefat"
						   placeholder="<?php esc_html_e( 'This Field is Mandatory', 'uncanny-learndash-codes' ); ?>"/>
				</div>

				<div class="uo-admin-field">
					<div
						class="uo-admin-label"><?php esc_html_e( 'User registration field placeholder', 'uncanny-learndash-codes' ); ?></div>

					<input class="uo-admin-input" type="text"
						   value="<?php if ( null !== $code_field_placeholder ) {
							   echo esc_html( $code_field_placeholder );
						   } ?>" name="code_field_placeholder" id="code_field_placeholder"
						   class="widefat"
						   placeholder="<?php esc_html_e( 'Enter Code', 'uncanny-learndash-codes' ); ?>"/>
				</div>

				<div class="uo-admin-field">
					<input type="submit" name="submit" id="submit" class="uo-admin-form-submit"
						   value="<?php esc_html_e( 'Save Changes', 'uncanny-learndash-codes' ); ?>">
				</div>

			</div>
		</div>
	</div>
	<script>
		jQuery('#uncanny-learndash-codes-form').submit(function (e) {
			if (jQuery('#registration_form').val() === '0') {
				/*e.preventDefault();
				 jQuery('#registration_form_error h4').html('Please Select Registration Form');
				 jQuery('#registration_form_error').show();
				 return false;*/
			} else {
				if (is_registration_form(jQuery('#registration_form').val())) {
					jQuery('#registration_form_error').hide()
					return true
				} else {
					jQuery('#registration_form_error h4').html('<?php echo esc_html__( 'Please Select a Valid Registration Form that has User Registration Feed enabled.', 'uncanny-learndash-codes' ); ?>')
					jQuery('#registration_form_error').show()
					return false
				}
			}
			// return true;.
		})
		jQuery('#registration_form').change(function (e) {
			if (jQuery(this).val() === '0') {
				/*e.preventDefault();
				 jQuery('#registration_form_error h4').html('Please Select Registration Form');
				 jQuery('#registration_form_error').show();
				 return false;*/
			} else {
				if (is_registration_form(jQuery(this).val())) {
					jQuery('#registration_form_error').hide()
					return true
				} else {
					jQuery('#registration_form_error h4').html('<?php echo esc_html__( 'Please Select a Valid Registration Form that has User Registration Feed enabled.', 'uncanny-learndash-codes' ); ?>')
					jQuery('#registration_form_error').show()
					return false
				}
			}
			return true
		})

		function is_registration_form(val) {
			if ('0' === jQuery('#form-id-' + val).attr('data-register')) {
				return false
			} else {
				return true
			}
		}
	</script>
<?php }

