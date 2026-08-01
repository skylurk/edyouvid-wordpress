<?php
/**
 * CA Framework - Offer Template: Flash
 *
 * A bold, attention-grabbing flash sale template with animated elements.
 *
 * @package CA_Framework
 * @version 1.1.0
 *
 * Available variables:
 * @var array $offer      Offer configuration array.
 * @var bool  $no_dismiss Whether dismiss button is hidden (show_on_hook mode).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$no_dismiss = $no_dismiss ?? false;
?>
<div class="ca-fw-offer ca-fw-offer-flash" id="ca-fw-offer-<?php echo esc_attr( $offer['id'] ); ?>" data-dismiss-id="<?php echo esc_attr( $offer['id'] ); ?>">
    <div class="ca-fw-offer-inner">
        <?php if ( ! $no_dismiss ) : ?>
            <button class="ca-fw-dismiss ca-fw-offer-close" data-dismiss-id="<?php echo esc_attr( $offer['id'] ); ?>" data-dismiss-type="<?php echo esc_attr( $offer['dismiss_type'] ); ?>" data-reshow-after="<?php echo esc_attr( $offer['reshow_after'] ); ?>" data-reshow-unit="<?php echo esc_attr( $offer['reshow_unit'] ); ?>" title="<?php esc_attr_e( 'Dismiss', 'flavor-jelee' ); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        <?php endif; ?>

        <?php if ( ! empty( $offer['image_url'] ) ) : ?>
            <div class="ca-fw-offer-image">
                <img src="<?php echo esc_url( $offer['image_url'] ); ?>" alt="<?php echo esc_attr( $offer['title'] ); ?>">
            </div>
        <?php endif; ?>

        <div class="ca-fw-offer-content">
            <?php if ( ! empty( $offer['badge_text'] ) ) : ?>
                <span class="ca-fw-badge ca-fw-badge-flash"><?php echo esc_html( $offer['badge_text'] ); ?></span>
            <?php endif; ?>

            <?php if ( ! empty( $offer['title'] ) ) : ?>
                <h3 class="ca-fw-offer-title"><?php echo wp_kses_post( $offer['title'] ); ?></h3>
            <?php endif; ?>

            <?php if ( ! empty( $offer['highlight_text'] ) ) : ?>
                <div class="ca-fw-offer-highlight ca-fw-flash-highlight"><?php echo wp_kses_post( $offer['highlight_text'] ); ?></div>
            <?php endif; ?>

            <?php if ( ! empty( $offer['description'] ) ) : ?>
                <p class="ca-fw-offer-desc"><?php echo wp_kses_post( $offer['description'] ); ?></p>
            <?php endif; ?>

            <?php echo CA_Framework_Offer::render_countdown( $offer ); ?>

            <?php echo CA_Framework_Offer::render_buttons( $offer['buttons'] ); ?>
        </div>
    </div>
</div>
