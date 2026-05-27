<?php

namespace uncanny_learndash_codes;

$atts = $GLOBALS['atts'];
if ( SharedFunctionality::ulc_filter_has_var( 'registered' ) ) { ?>
	<div class="registered">
		<h2><?php esc_html_e( 'Congratulations! You have successfully registered your account.', 'uncanny-learndash-codes' ); ?></h2>
	</div>
	<?php
} else {
	$default = array(
		'redirect'      => '',
		'code_optional' => 'no',
		'auto_login'    => 'yes',
		'role'          => 'subscriber',
	);
	if ( is_multisite() ) {
		$options = get_blog_option( get_current_blog_id(), 'uncanny-codes-custom-registration-atts', $default );
	} else {
		$options = get_option( 'uncanny-codes-custom-registration-atts', $default );
	}

	$user_first                = '';
	$user_last                 = '';
	$user_email                = '';
	$code_registration         = '';
	$uo_codes_terms_conditions = '';
	if ( SharedFunctionality::ulc_filter_has_var( 'uncanny-learndash-codes-user_first', INPUT_POST ) ) {
		$user_first = sanitize_text_field( SharedFunctionality::ulc_filter_input( 'uncanny-learndash-codes-user_first', INPUT_POST ) );
	}

	if ( SharedFunctionality::ulc_filter_has_var( 'uncanny-learndash-codes-user_last', INPUT_POST ) ) {
		$user_last = sanitize_text_field( SharedFunctionality::ulc_filter_input( 'uncanny-learndash-codes-user_last', INPUT_POST ) );
	}

	if ( SharedFunctionality::ulc_filter_has_var( 'uncanny-learndash-codes-user_email', INPUT_POST ) ) {
		$user_email = sanitize_text_field( SharedFunctionality::ulc_filter_input( 'uncanny-learndash-codes-user_email', INPUT_POST ) );
	}

	if ( SharedFunctionality::ulc_filter_has_var( 'uncanny-learndash-codes-code_registration', INPUT_POST ) ) {
		$code_registration = sanitize_text_field( SharedFunctionality::ulc_filter_input( 'uncanny-learndash-codes-code_registration', INPUT_POST ) );
	}

	if ( SharedFunctionality::ulc_filter_has_var( 'uo_codes_terms_conditions', INPUT_POST ) ) {
		$uo_codes_terms_conditions = sanitize_email( SharedFunctionality::ulc_filter_input( 'uo_codes_terms_conditions', INPUT_POST ) );
	}
	?>

	<form id="uncanny-learndash-codes-registration_form"
		  class="uncanny-learndash-codes-registration uncanny-learndash-codes" action="" method="POST">
		<fieldset>
			<table class="table table-form form-table clr">
				<tr>
					<td class="label"><label
							for="uncanny-learndash-codes-user_first"><?php echo esc_html__( 'First Name', 'uncanny-learndash-codes' ); ?></label>
					</td>
					<td class="input"><input name="uncanny-learndash-codes-user_first"
											id="uncanny-learndash-codes-user_first"
											required="required"
											value="<?php echo esc_attr( $user_first ); ?>"
											type="text"/></td>
				</tr>
				<tr>
					<td class="label"><label
							for="uncanny-learndash-codes-user_last"><?php echo esc_html__( 'Last Name', 'uncanny-learndash-codes' ); ?></label>
					</td>
					<td class="input"><input name="uncanny-learndash-codes-user_last"
											id="uncanny-learndash-codes-user_last"
											required="required"
											value="<?php echo esc_attr( $user_last ); ?>"
											type="text"/></td>
				</tr>
				<tr>
					<td class="label"><label
							for="uncanny-learndash-codes-user_email"><?php echo esc_html__( 'Email / Username', 'uncanny-learndash-codes' ); ?></label>
					</td>
					<td class="input"><input name="uncanny-learndash-codes-user_email"
											 id="uncanny-learndash-codes-user_email"
											 required="required"
											 value="<?php echo esc_attr( $user_email ); ?>"
											 class="required" type="email"/></td>
				</tr>
				<tr>
					<td class="label"><label
							for="password"><?php echo esc_html__( 'Password', 'uncanny-learndash-codes' ); ?></label>
					</td>
					<td class="input"><input name="uncanny-learndash-codes-user_pass" id="password"
											 required="required"
											 minlength="<?php echo sanitize_text_field( apply_filters( 'ulc_registration_min_password_length', 6 ) ); ?>"
											 class="required"
											 type="password"/></td>
				</tr>
				<tr>
					<td class="label"><label
							for="password_again"><?php echo esc_html__( 'Confirm password', 'uncanny-learndash-codes' ); ?></label>
					</td>
					<td class="input"><input name="uncanny-learndash-codes-user_pass_confirm" id="password_again"
											 required="required"
											 minlength="<?php echo sanitize_text_field( apply_filters( 'ulc_registration_min_password_length', 6 ) ); ?>"
											 class="required"
											 type="password"/></td>
				</tr>
				<tr>
					<td class="label"><label
							for="code_registration"><?php echo esc_html__( 'Registration code', 'uncanny-learndash-codes' ); ?></label>
					</td>
					<td class="input"><input name="uncanny-learndash-codes-code_registration" id="code_registration"
											 <?php
												if ( 'no' === $options['code_optional'] ) {
													?>
													required="required"
											 class="required"<?php } ?>
											 value="<?php echo esc_attr( $code_registration ); ?>"
											 type="text"/></td>
				</tr>
				<?php
				if ( ! empty( $term_conditions ) ) {
					?>
					<tr>
						<td class="label"><label
								for="terms_conditions"><?php echo esc_html__( 'Terms & Conditions', 'uncanny-learndash-codes' ); ?></label>
						</td>
						<td class="input"><input name="uo_codes_terms_conditions" id="terms_conditions"
												 required="required"
												 class="required"
												 value="<?php echo esc_attr( $uo_codes_terms_conditions ); ?>"
												 type="checkbox"/><?php echo $term_conditions; ?>
						</td>
					</tr>
				<?php } ?>
				<tr>
					<td class="label">
						<input type="hidden" name="_uo_nonce"
							   value="<?php echo wp_create_nonce( Config::get_project_name() ); ?>"/>
						<input type="hidden" name="redirect_to"
							   value="<?php echo $atts['redirect']; ?>"/>
						<input type="hidden" name="key"
							   value="<?php echo crypt( get_the_ID(), 'uncanny-learndash-codes' ); ?>"/></td>
					<td class="input"><input type="submit" class="btn btn-default"
											 value="<?php echo esc_html__( 'Register Your Account', 'uncanny-learndash-codes' ); ?>"/>
					</td>
				</tr>
			</table>
		</fieldset>
	</form>
<?php } ?>
