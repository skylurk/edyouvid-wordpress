<?php
/**
 * Shared slider markup helpers for Elementor widgets.
 *
 * @package TestimonialsCarouselElementor
 */

namespace TestimonialsCarouselElementor;

defined('ABSPATH') || exit;

/**
 * Outputs data-tc-* attributes on swiper roots for the frontend handler.
 */
class Testimonials_Carousel_Slider_Render
{

  /**
   * @param array $settings Widget display settings.
   */
  public static function print_data_attributes(array $settings)
  {
    echo ' data-tc-autoplay="' . esc_attr($settings['autoplay'] ?? '') . '"';
    echo ' data-tc-autoplay-speed="' . esc_attr($settings['autoplay_speed'] ?? '') . '"';
    echo ' data-tc-slider-loop="' . esc_attr($settings['slider_loop'] ?? '') . '"';
    echo ' data-tc-disable-interaction="' . esc_attr($settings['disable_interaction'] ?? '') . '"';
    echo ' data-tc-navigation="' . esc_attr($settings['navigation'] ?? '') . '"';
  }

  /**
   * Cube carousel — all Additional Options for the frontend handler (Elementor 3.5+).
   *
   * @param array $settings Widget display settings.
   */
  public static function print_cube_data_attributes(array $settings)
  {
    self::print_data_attributes($settings);
    echo ' data-tc-slider-speed="' . esc_attr($settings['slider_speed'] ?? '') . '"';
    echo ' data-tc-slide-shadows="' . esc_attr($settings['slide_shadows'] ?? '') . '"';
    echo ' data-tc-shadow-offset="' . esc_attr($settings['slider_shadow_offset'] ?? '') . '"';
    echo ' data-tc-shadow-scale="' . esc_attr($settings['slider_shadow_scale'] ?? '') . '"';
    echo ' data-tc-slider-rotate="' . esc_attr($settings['slider_rotate'] ?? '') . '"';
    echo ' data-tc-pause-mouse-enter="' . esc_attr($settings['slider_pause_mouse_enter'] ?? '') . '"';
    echo ' data-tc-reverse-direction="' . esc_attr($settings['slider_reverse_direction'] ?? '') . '"';
    echo ' data-tc-overlay-enable="' . esc_attr($settings['overlay_enable'] ?? '') . '"';
  }
}
