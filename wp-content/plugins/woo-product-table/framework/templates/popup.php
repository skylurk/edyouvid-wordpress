<?php
/**
 * CA Framework - Popup Template
 *
 * A modern modal popup with overlay, shown on specific plugin pages.
 *
 * @package CA_Framework
 * @version 1.1.0
 *
 * Available variables:
 * @var array $popup Popup configuration array.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="ca-fw-popup-overlay" id="ca-fw-popup-<?php echo esc_attr( $popup['id'] ); ?>" data-dismiss-id="<?php echo esc_attr( $popup['id'] ); ?>" data-dismiss-type="<?php echo esc_attr( $popup['dismiss_type'] ); ?>" data-reshow-after="<?php echo esc_attr( $popup['reshow_after'] ); ?>" data-reshow-unit="<?php echo esc_attr( $popup['reshow_unit'] ); ?>">
    <div class="ca-fw-popup" style="max-width: <?php echo esc_attr( $popup['width'] ); ?>;">
        <button class="ca-fw-dismiss ca-fw-popup-close" data-dismiss-id="<?php echo esc_attr( $popup['id'] ); ?>" data-dismiss-type="<?php echo esc_attr( $popup['dismiss_type'] ); ?>" data-reshow-after="<?php echo esc_attr( $popup['reshow_after'] ); ?>" data-reshow-unit="<?php echo esc_attr( $popup['reshow_unit'] ); ?>" title="<?php esc_attr_e( 'Close', 'flavor-jelee' ); ?>">
            <span class="dashicons dashicons-no-alt"></span>
        </button>

        <?php if ( ! empty( $popup['image_url'] ) ) : ?>
            <div class="ca-fw-popup-image">
                <img src="<?php echo esc_url( $popup['image_url'] ); ?>" alt="<?php echo esc_attr( $popup['title'] ); ?>">
            </div>
        <?php endif; ?>

        <div class="ca-fw-popup-content">
            <?php if ( ! empty( $popup['badge_text'] ) ) : ?>
                <span class="ca-fw-badge"><?php echo esc_html( $popup['badge_text'] ); ?></span>
            <?php endif; ?>

            <?php if ( ! empty( $popup['title'] ) ) : ?>
                <h3 class="ca-fw-popup-title"><?php echo wp_kses_post( $popup['title'] ); ?></h3>
            <?php endif; ?>

            <?php if ( ! empty( $popup['description'] ) ) : ?>
                <div class="ca-fw-popup-desc"><?php echo wp_kses_post( $popup['description'] ); ?></div>
            <?php endif; ?>

            <?php echo CA_Framework_Offer::render_countdown( $popup ); ?>

            <?php if ( ! empty( $popup['buttons'] ) ) : ?>
                <?php echo CA_Framework_Offer::render_buttons( $popup['buttons'] ); ?>
            <?php endif; ?>
        </div>
    </div>
</div>
