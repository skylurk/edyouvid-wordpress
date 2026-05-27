function loginpressScaleCaptcha(wrapperSelector, captchaSelector, originalWidth) {
	const wrappers = document.querySelectorAll( wrapperSelector );

	wrappers.forEach(
		wrapper => {
			const captcha = wrapper.querySelector( captchaSelector );
			if (captcha) {
				const wrapperWidth = wrapper.clientWidth;
				const scale        = wrapperWidth < originalWidth ? wrapperWidth / originalWidth : 1;

				captcha.style.setProperty( 'transform', `scale( ${scale} )`, 'important' );
				captcha.style.setProperty( 'transform-origin', 'top left', 'important' );
			}
		}
	);
}

const captchaConfigs = [
	{ wrapper: '.cf-turnstile-wrapper', captcha: '.cf-turnstile', width: 300 },
	{ wrapper: '.h-captcha-container', captcha: '.h-captcha[data-size="normal"] iframe', width: 304 },
	{ wrapper: '.loginpress_recaptcha_wrapper', captcha: '.g-recaptcha iframe', width: 304 }
];

function scaleAllCaptchas() {
	captchaConfigs.forEach(
		({ wrapper, captcha, width }) => {
			// Combine selectors for inner query (e.g., wrapper.querySelector(captcha))
			const fullCaptchaSelector = captcha.startsWith( '>' ) ? ` : scope ${captcha}` : captcha;
			loginpressScaleCaptcha( wrapper, fullCaptchaSelector, width );
		}
	);
}

jQuery( document ).ready( scaleAllCaptchas );
jQuery( document ).ready(
	function ($) {
		$( '.loginpress_widget_links a' ).on(
			'click',
			function () {
				setTimeout( scaleAllCaptchas, 100 );
			}
		);
	}
);
window.addEventListener( 'load', scaleAllCaptchas );
window.addEventListener( 'resize', scaleAllCaptchas );
// Trigger on DOM modifications
const observer = new MutationObserver(
	() => {
		scaleAllCaptchas();
	}
);

// Start observing the entire document for changes
observer.observe(
	document.body,
	{
		childList: true, // watch for added/removed elements
		subtree: true,   // watch all descendant elements
		attributes: true // watch for attribute changes (optional)
	}
);