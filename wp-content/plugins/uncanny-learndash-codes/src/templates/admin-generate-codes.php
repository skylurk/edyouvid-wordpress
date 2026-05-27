<?php

namespace uncanny_learndash_codes;

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<div class="uoc-generate" id="uoc-generate">

	<div class="uoc-generate-main">

		<form
			action=""
			autocomplete="off"
			method="POST"
		>

			<?php include Config::get_template( 'admin-generate-codes/header.php' ); ?>

			<?php include Config::get_template( 'admin-generate-codes/batch-name.php' ); ?>

			<?php include Config::get_template( 'admin-generate-codes/dependency.php' ); ?>

			<?php include Config::get_template( 'admin-generate-codes/code-setup.php' ); ?>

			<input type="hidden" name="_custom_wpnonce"
				   value="<?php echo wp_create_nonce( Config::get_project_name() ); ?>">
			<?php if ( SharedFunctionality::ulc_filter_has_var( 'group_id' ) ) { ?>
				<input type="hidden" name="group_id"
					   value="<?php echo absint( SharedFunctionality::ulc_filter_input( 'group_id' ) ); ?>"/>
				<input type="hidden" name="edit_group"
					   value="yes"/>
			<?php } ?>
		</form>
	</div>

	<div class="uoc-generate-sidebar">
		<?php include Config::get_template( 'admin-generate-codes/sidebar.php' ); ?>
	</div>

</div>
