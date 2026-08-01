<?php

namespace uncanny_learndash_codes;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Variables:
 * $tab_active   The ID of the active tab
 */

$tabs = array(
	(object) array(
		'id'   => 'uncanny-learndash-codes',
		'href' => menu_page_url( 'uncanny-learndash-codes', false ),
		'name' => esc_html__( 'View Codes', 'uncanny-learndash-codes' ),
	),
	(object) array(
		'id'   => 'uncanny-learndash-codes-create',
		'href' => menu_page_url( 'uncanny-learndash-codes-create', false ),
		'name' => is_numeric( SharedFunctionality::ulc_filter_input( 'group_id' ) ) && 'true' === SharedFunctionality::ulc_filter_input( 'edit' ) ? esc_html__( 'Modify Codes', 'uncanny-learndash-codes' ) : esc_html__( 'Generate Codes', 'uncanny-learndash-codes' ),
	),
	(object) array(
		'id'   => 'uncanny-learndash-codes-cancel',
		'href' => menu_page_url( 'uncanny-learndash-codes-cancel', false ),
		'name' => esc_html__( 'Cancel Codes', 'uncanny-learndash-codes' ),
	),
	(object) array(
		'id'   => 'uncanny-learndash-codes-settings',
		'href' => menu_page_url( 'uncanny-learndash-codes-settings', false ),
		'name' => esc_html__( 'Settings', 'uncanny-learndash-codes' ),
	),
	(object) array(
		'id'   => 'uncanny-codes-kb',
		'href' => menu_page_url( 'uncanny-codes-kb', false ),
		'name' => esc_html__( 'Help', 'uncanny-learndash-codes' ),
	),
	(object) array(
		'id'   => 'uncanny-codes-plugins',
		'href' => menu_page_url( 'uncanny-codes-plugins', false ),
		'name' => esc_html__( 'LearnDash Plugins', 'uncanny-learndash-codes' ),
	),
//	(object) array(
//		'id'   => 'uncanny-codes-license-activation',
//		'href' => menu_page_url( 'uncanny-codes-license-activation', false ),
//		'name' => esc_html__( 'License activation', 'uncanny-learndash-codes' ),
//	),
);

?>

<div class="uncannyowl-header">
	<div class="uncannyowl-header-top">
		<div class="uncannyowl-header-top__content">
			<div class="uncannyowl-header-top__title">
				<?php esc_html_e( 'Uncanny Redemption Codes', 'uncanny-learndash-codes' ); ?>
			</div>
			<div class="uncannyowl-header-top__author">
				<span><?php esc_html_e( 'by', 'uncanny-learndash-codes' ); ?></span>
				<a href="https://www.uncannyowl.com/?utm=uncanny-codes" target="_blank" class="uncannyowl-header-top__logo uncannyowl-header-top__logo--svg">
					UncannyOwl
				</a>
			</div>
		</div>
		<div class="uncannyowl-header-top__social">
			<a href="https://www.facebook.com/UncannyOwl/" target="_blank"
				class="uncannyowl-header-social-icon" 
				title="<?php esc_attr_e( 'Follow us on Facebook', 'uncanny-learndash-codes' ); ?>">
				<span class="uncannyowl-icon uncannyowl-icon--facebook"></span>
			</a>

			<a href="https://twitter.com/UncannyOwl" target="_blank"
				class="uncannyowl-header-social-icon"
				title="<?php esc_attr_e( 'Follow us on Twitter', 'uncanny-learndash-codes' ); ?>">
				<span class="uncannyowl-icon uncannyowl-icon--twitter"></span>
			</a>

			<a href="https://www.linkedin.com/company/uncannyowl" target="_blank"
				class="uncannyowl-header-social-icon"
				title="<?php esc_attr_e( 'Follow us on LinkedIn', 'uncanny-learndash-codes' ); ?>">
				<span class="uncannyowl-icon uncannyowl-icon--linkedin"></span>
			</a>
		</div>
	</div>

	<nav class="uncannyowl-nav-tab-wrapper">
		<?php foreach ( $tabs as $tab ) { ?>

			<?php

			// Define the extra CSS classes of the tab.
			$css_classes = array();

			// Check if it's the current tab.
			if ( $tab_active == $tab->id ) {
				$css_classes[] = 'uncannyowl-nav-tab-active';
			}

			?>

			<a href="<?php echo $tab->href; ?>" class="uncannyowl-nav-tab <?php echo implode( ' ', $css_classes ); ?>">
				<?php echo $tab->name; ?>
			</a>

		<?php } ?>
	</nav>
</div>
