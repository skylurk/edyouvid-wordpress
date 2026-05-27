<?php
/**
 * Template to display the Learner activity onboarding notice.
 *
 * @package learndash-reports-by-wisdmlabs
 */

?>
<div class="wrap">
	<div class="notice wrld-la-main-container" style="padding:0px !important;">
		<a class="wrld-dismiss-notice-link" href="<?php echo esc_html( $dismiss_attribute ); ?>"><span class="dashicons dashicons-no-alt"></span></a>
		<div class="wrld-la-logo">
			<img class="wrld-la-logo-img" src='<?php echo esc_html( $wisdm_logo ); ?>'>
		</div>
		<div class="wrld-la-center">
			<div class="wrld-la-head-text2">
				Get in-depth insights with LearnDash LMS - Reports Pro
			</div>
			<div class="wrld-la-sub-text2">
				We have an <span>Amazing Offer</span> for you!
			</div>
			<div class="special-section">
				<div class="triangle-div">
					Upgrade to Pro at just <s>$120</s> $99
				</div>
				<div class="inverted-triangle"></div>
			</div>
			<button><a href="https://www.learndash.com/reports-by-learndash" target="_blank">Upgrade Now</a></button>
		</div>
	</div>
</div>
