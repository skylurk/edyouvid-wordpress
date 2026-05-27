<?php
/**
 * This file is created for adding Social related JS code in login page footer.
 *
 * @since 1.0.7
 * @version 3.2.0
 *
 * @package LoginPress Pro
 */

$social_button_position = get_option( 'loginpress_social_logins' );
if ( isset( $social_button_position['social_button_position'] ) ) {
	$position = $social_button_position['social_button_position'];
} else {
	$position = 'default';
}
?>
<script>
function addLoginPressSocialButton() {
	var rmLoginPressChecked = false;
	// Save the state of the "Remember Me" checkbox
	if (document.getElementById('rememberme') && document.getElementById('rememberme').checked == true) {
		rmLoginPressChecked = true;
	}

	var existingContainer = document.querySelector('#loginform .social-networks');
	var loginForm = document.getElementById('loginform');
	var position = <?php echo wp_json_encode( $position ); ?>; // Replace this with dynamic logic if needed
	if (existingContainer) {
		// Clone the existing container before removing it
		let $slLoginPressContainer = existingContainer.cloneNode(true);
		existingContainer.remove(); // Safely remove the original container
		const separators = $slLoginPressContainer.querySelector('.social-sep');

		if (position === 'default' && existingContainer.querySelector('.lpsl-login-text')) {
			loginForm.append($slLoginPressContainer);
		} else if (position === 'below' && existingContainer.querySelector('.lpsl-login-text')) {
			loginForm.append($slLoginPressContainer);
			let gap = '20px'; // Larger gap for default
			$slLoginPressContainer.style.marginTop = gap;
			separators.remove();
		} else if(position === 'above' && existingContainer.querySelector('.lpsl-login-text')){
			separators.remove();
			loginForm.prepend($slLoginPressContainer);
		} else if (position === 'above_separator' && existingContainer.querySelector('.lpsl-login-text')) {
			separators.remove();
			loginForm.prepend(separators);
			loginForm.prepend($slLoginPressContainer);
		}
	}

	// Restore "Remember Me" checkbox state
	if (rmLoginPressChecked !== false) {
		document.getElementById('rememberme').setAttribute('checked', rmLoginPressChecked);
	}
};

function addLoginPressSocialButtonRegister() {
	var existingContainer = document.querySelector('#registerform .social-networks');
	var registerForm = document.getElementById('registerform');
	var position = <?php echo wp_json_encode( $position ); ?>; // Replace this with dynamic logic if needed
	if (existingContainer) {
		// Clone the existing container before removing it
		let $slLoginPressContainer = existingContainer.cloneNode(true);
		existingContainer.remove(); // Safely remove the original container
		const separators = $slLoginPressContainer.querySelector('.social-sep');
		if (position === 'default' && existingContainer.querySelector('.lpsl-login-text')) {
			registerForm.append($slLoginPressContainer);
		} else if (position === 'below' && existingContainer.querySelector('.lpsl-login-text')) {
			registerForm.append($slLoginPressContainer);
			let gap = '20px'; // Larger gap for default
			$slLoginPressContainer.style.marginTop = gap;
			separators.remove();
		} else if(position === 'above' && existingContainer.querySelector('.lpsl-login-text')){
			separators.remove();
			registerForm.prepend($slLoginPressContainer);
		} else if (position === 'above_separator' && existingContainer.querySelector('.lpsl-login-text')) {
			separators.remove();
			registerForm.prepend(separators);
			registerForm.prepend($slLoginPressContainer);
		}
	}
};
document.addEventListener('DOMContentLoaded', function () {
	// Check for the presence of the login form elements after DOM has loaded
	if (document.getElementById('rememberme') || document.querySelector('loginpress-login-widget #loginform')) {
		if(!document.getElementById('learndash-login-form'))
			addLoginPressSocialButton();
	} 
	if (!document.getElementById('learndash_registerform')) {
		addLoginPressSocialButtonRegister();
	}
});
</script>
