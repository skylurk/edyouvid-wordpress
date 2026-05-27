<?php
/**
 * LoginPress Pro - Theme Template File
 *
 * This file contains the fifth preset theme for LoginPress Pro.
 *
 * @package LoginPress Pro
 * @since 1.0.0
 */

/**
 * Fifth preset theme function.
 *
 * LoginPress Theme Name: Corporate
 *
 * @return string Theme HTML and CSS content.
 */
function fifth_presets() {

	ob_start();
	if ( version_compare( $GLOBALS['wp_version'], '5.3', '>=' ) ) { ?>

		<style media="screen"  id="loginpress-style-wp-5-3">
			.login form .input, .login input[type=text],.login form input[type=checkbox]{
				border-radius: 0;
			}
			input[type=checkbox]:checked:before {
				content: url('data:image/svg+xml;utf8,&lt;svg%20xmlns%3D%27http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%27%20viewBox%3D%270%200%2020%2020%27><path%20d%3D%27M14.83%204.89l1.34.94-5.81%208.38H9.02L5.78%209.67l1.34-1.25%202.57%202.4z%27%20fill%3D%27%232b358f%27%2F>&lt;%2Fsvg>');
			}
			input[type=checkbox],input[type=checkbox]:checked{
				border-color: #2b358f !important;
			}
			input[type=checkbox]:hover{
				border-color: #C3C4C7 !important;
			}
			.wp-core-ui #login .button-primary{
				margin-left: 0;
			}
			.admin-email__actions-primary .button:first-child{
				font: 700 18px "Roboto", sans-serif;
				color: #fff;
				height: auto;
				line-height: 20px !important;
				padding: 13px;
				padding-top: 13px;
				padding-bottom: 13px;
				border-radius: 0 !important;
				width: 100%;
				text-align: center;
				background-color: #2b358f;
				border: 0;
				margin-bottom: 8px;
			}
			.login form p.admin-email__details, .login h1.admin-email__heading{
				color: #fff !important;
			}
			.admin-email__actions-secondary a, .login .admin-email__details a{
				color: #d5d5d5;
			}
			.admin-email__actions-primary .button:first-child:hover{
				background-color: #343d85;
			}
			.dashicons-visibility:before {
				color: #2b358f;
			}
			.dashicons-hidden:before {
				color: #000000;
			}
			.dashicons-hidden:hover::before {
				color: #2b358f;
			}
		</style>
	<?php } else { ?>
		<style>
			input[type=checkbox]:checked:before {
				content: '\f147';
				color: #2b358f;
				font-size: 18px;
			}
		</style>
	<?php } ?>
	<style media="screen" id="loginpress-style">

		html, body.login {
			height: auto !important;
		}
		body.login {
			--background-desktop-image: url(<?php echo esc_url( plugins_url( 'assets/img/bg5.jpg', LOGINPRESS_PRO_PLUGIN_BASENAME ) ); ?>);
			background-image: var(--background-desktop-image, url(<?php echo esc_url( plugins_url( 'assets/img/bg5.jpg', LOGINPRESS_PRO_PLUGIN_BASENAME ) ); ?>));
			background-position: center center;
			/*background-color: #f1f1f1 !important;*/
			background-size: cover;
			display: table !important;
			min-height: 100vh;
			width: 100%;
			padding: 0;
			position: relative;
		}
		body.login.login-action-login{
			display: table  !important;
		}
		body.firefox.login.login-action-login{
			height: 1px !important;
		}
		/*body.login:after{
			width: 100%;
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			height: 60%;
			background: #263466;
			}*/
			/*.login label{
			font-size:0;
			line-height:0;
			margin-top: 0;
			display: block;
			margin-bottom:
		}*/
		.login label{
			font-size: 16px;
			color: #fff;
		}
		#login{
			/*background: url(img/form_bg.jpg) no-repeat 0 0 !important;*/
			background-image: url(<?php echo esc_url( plugins_url( 'assets/img/form_bg5.jpg', LOGINPRESS_PRO_PLUGIN_BASENAME ) ); ?>);
			background-size: cover;
			padding: 40px 30px 50px;
			max-width: 400px;
			width: 100% !important;
			border-radius: 10px;
			box-shadow: 0px 5px 50px 0px rgba(0, 0, 0, 0.1);
			margin-top: 8%;
		}
		#loginform{
			margin: 0 auto;
		}
		#login:after{
			content: '';
			display: table;
			clear: both;
		}
		#login form p + p:not(.forgetmenot){
			margin-top: 0;
		}
		.login form .input, .login input[type=text]{
			background: none;
			display: block;
			color: #fff;
			font-size: 16px;
			/*font-family: 'Open Sans';*/
			width:100%;
			border:0;
			height: 50px;
			padding: 0 15px;
			border:1px solid #ffffff;
			border-radius: 3px;
		}
		.login form{
			background: none;
			padding: 0;
			box-shadow: none;
		}
		.login form br{
			display: none;
		}
		#login form p.submit{
			clear: both;
			padding-top: 35px;
		}
		.wp-core-ui #login .wp-generate-pw,
		.login input[type="submit"],
		body.wp-core-ui.login .two-factor-email-resend .button,
		.wp-core-ui #login .button-primary {
			width:100% !important;
			display: block;
			float: none;
			background-color : #2b358f;
			font-weight: 700;
			font-size: 18px;
			/*font-family: 'Open Sans';*/
			color : #ffffff;
			height: 56px;
			border-radius: 0;
			border:0;
			box-shadow: none;
			border-radius: 3px;
		}
		.wp-core-ui #login .wp-generate-pw{
			background-color: #2b358f33;
			color: #2b358f;
			border: 1px solid #2b358f;
		}
		.login input[type="submit"]:hover{
			color: #ffffff;
		}
		.login input[type="submit"]:hover,
		.wp-core-ui #login .button-primary:hover{
			background-color: #343d85;
		}
		.login form .forgetmenot label{
			font-size: 13px;
			/*font-family: 'Open Sans';*/
			color: #fff;
		}
		.login form input[type=checkbox]{
			background: none;
			height: 13px;
			width: 13px;
			min-width: 13px;
		}
		.login #nav, .login #backtoblog {
			margin: 17px 0 0;
			padding: 0;
			font-size: 12px;
			/*font-family: "Open Sans";*/
			color: #fff;
		}
		.login #nav a, .login #backtoblog a, .login .privacy-policy-page-link>a.privacy-policy-link{
			font-size: 12px;
			/*font-family: "Open Sans";*/
			color: #fff;
		}
		.login #backtoblog{
			float: left;
		}
		.login #nav {
			font-size: 0;
			float: right;
			width: 100%;
		}
		.login #nav a:last-child {
			float: right;
		}
		.login #nav a:first-child {
			float: left;
		}
		.login #backtoblog a:hover, .login #nav a:hover, .login h1 a:hover{
			color: #eae8e8;
		}
		/* style two factor plugin */
		.login .backup-methods-wrap a, #login form p:not([class]){
			color: #fff;
		}
		.login .backup-methods-wrap a:hover{
			color: #eae8e8;
		}
		/*End style two factor plugin */
		.footer-wrapper{
			display: table-footer-group;
		}
		.footer-cont{
			right: 0;
			bottom: 0;
			left: 0;
			text-align: center;
			display: table-cell;
			vertical-align: bottom;
			height: 100px;
			width: 100vw;
		}
		.copyRight{
			text-align: center;
			padding: 12px;
			background-color: #e7405c;
			color: #ffffff;
		}
		#login form p + p:not(.forgetmenot){
			color: #d5d5d5;
		}
		.social-sep span{
			color: #fff;
		}
		.loginpress-show-love{
			color: #fff;
		}
		.loginpress-show-love a{
			color: #2b358f;
		}
		.loginpress-show-love a:hover{
			color: #fff;
		}
		@media screen and (max-width: 767px) {
			.login .loginpress-show-love{
			position: static;
			float: none;
			text-align: center;
			padding: 3px;
			}
			.login #login{
			max-width: 290px;
			}
		}
	</style>
	<?php
	$content = ob_get_clean();
	return $content;
}
echo fifth_presets(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
