<?php
/**
 * Central asset registration for Testimonials Carousel Elementor widgets.
 *
 * @package TestimonialsCarouselElementor
 */

namespace TestimonialsCarouselElementor;

defined('ABSPATH') || exit;

/**
 * Registers shared scripts/styles once and picks the correct frontend handler per Elementor version.
 */
class Testimonials_Carousel_Assets
{

  const LEGACY_ELEMENTOR_VERSION = '3.5.0';

  /**
   * @var bool
   */
  private static $registered = false;

  /**
   * Hook asset registration into WordPress enqueue lifecycle.
   */
  public static function init()
  {
    add_action('wp_enqueue_scripts', [__CLASS__, 'register'], 5);
    add_action('admin_enqueue_scripts', [__CLASS__, 'register'], 5);
    add_action('elementor/editor/before_enqueue_scripts', [__CLASS__, 'register'], 5);
  }

  /**
   * Register swiper + widget handler scripts (idempotent).
   */
  public static function register()
  {
    if (self::$registered) {
      return;
    }

    self::$registered = true;

    $plugin_file = TESTIMONIALS_CAROUSEL_ELEMENTOR;
    $version     = defined('TESTIMONIALS_VERSION') ? TESTIMONIALS_VERSION : '12.0.1';

    if (!wp_script_is('swiper', 'registered')) {
      wp_register_script(
        'swiper',
        plugins_url('/assets/js/swiper-bundle.min.js', $plugin_file),
        [],
        $version,
        true
      );
    }

    if (!wp_style_is('swiper', 'registered')) {
      wp_register_style(
        'swiper',
        plugins_url('/assets/css/swiper-bundle.min.css', $plugin_file),
        [],
        $version
      );
    }

    $handler_deps = [];

    if (self::uses_modern_handler()) {
      $handler_deps[] = 'jquery';
      $handler_deps[] = 'elementor-frontend-modules';
      $handler_deps[] = 'elementor-frontend';
      $handler_url    = plugins_url('/assets/js/testimonials-carousel-widget-handler.min.js', $plugin_file);
    } else {
      $handler_deps[] = 'swiper';
      $handler_deps[] = 'jquery';
      $handler_url    = plugins_url('/assets/js/testimonials-carousel-widget-old-elementor-handler.min.js', $plugin_file);
    }

    wp_register_script(
      'testimonials-carousel-widget-handler',
      $handler_url,
      $handler_deps,
      $version,
      true
    );

    self::register_widget_styles();
    self::register_quotes_assets();
    self::register_swiper_v11();
  }

  /**
   * Per-widget styles used via get_style_depends().
   */
  private static function register_widget_styles()
  {
    $styles = [
      'testimonials-carousel'            => 'testimonials-carousel.min.css',
      'testimonials-carousel-quotes'     => 'testimonials-carousel-quotes.min.css',
      'testimonials-gallery-carousel'    => 'testimonials-gallery-carousel.min.css',
      'testimonials-carousel-employees'  => 'testimonials-carousel-employees.min.css',
      'testimonials-carousel-blog'       => 'testimonials-carousel-blog.min.css',
      'testimonials-carousel-creative'   => 'testimonials-carousel-creative.min.css',
      'testimonials-carousel-thumbnails' => 'testimonials-carousel-thumbnails.min.css',
      'testimonials-carousel-cube'       => 'testimonials-carousel-cube.min.css',
      'testimonials-carousel-cube-360'   => 'testimonials-carousel-cube-360.min.css',
      'section-with-carousel-cube'       => 'testimonials-section-with-cube.min.css',
      'section-with-carousel-cube-360'   => 'testimonials-section-with-cube-360.min.css',
    ];

    foreach ($styles as $handle => $css_file) {
      self::register_style($handle, $css_file);
    }
  }

  /**
   * Owl Carousel assets for the Quotes widget.
   */
  private static function register_quotes_assets()
  {
    $plugin_file = TESTIMONIALS_CAROUSEL_ELEMENTOR;
    $version     = defined('TESTIMONIALS_VERSION') ? TESTIMONIALS_VERSION : '12.0.1';

    if (!wp_style_is('owl-carousel', 'registered')) {
      wp_register_style(
        'owl-carousel',
        plugins_url('/assets/css/owl.carousel.min.css', $plugin_file),
        [],
        $version
      );
    }

    if (!wp_script_is('owl-carousel', 'registered')) {
      wp_register_script(
        'owl-carousel',
        plugins_url('/assets/js/owl.carousel.min.js', $plugin_file),
        ['jquery'],
        $version,
        true
      );
    }

    if (!wp_script_is('testimonials-carousel-quotes-handler', 'registered')) {
      wp_register_script(
        'testimonials-carousel-quotes-handler',
        plugins_url('/assets/js/testimonials-carousel-quotes-handler.min.js', $plugin_file),
        ['jquery', 'owl-carousel'],
        $version,
        true
      );
    }
  }

  /**
   * Script handles for Elementor widgets (avoids loading plugin Swiper over Elementor's).
   *
   * @param array $extra Extra script handles (e.g. ai-btn).
   * @return array
   */
  public static function get_widget_script_depends(array $extra = array())
  {
    $scripts = array('testimonials-carousel-widget-handler');

    if (!self::uses_modern_handler()) {
      return array_merge(array('swiper'), $scripts, $extra);
    }

    if (in_array('swiper', $extra, true)) {
      return array_merge(array('swiper'), $scripts, array_diff($extra, array('swiper')));
    }

    return array_merge($scripts, $extra);
  }

  /**
   * Elementor 3.5+ exposes element settings to frontend handlers via elementorModules.
   *
   * @return bool
   */
  public static function uses_modern_handler()
  {
    return defined('ELEMENTOR_VERSION')
      && version_compare(ELEMENTOR_VERSION, self::LEGACY_ELEMENTOR_VERSION, '>=');
  }

  /**
   * @param string $css_file Filename under assets/css/.
   */
  public static function register_style($handle, $css_file)
  {
    if (wp_style_is($handle, 'registered')) {
      return;
    }

    wp_register_style(
      $handle,
      plugins_url('/assets/css/' . $css_file, TESTIMONIALS_CAROUSEL_ELEMENTOR),
      [],
      defined('TESTIMONIALS_VERSION') ? TESTIMONIALS_VERSION : '12.0.1'
    );
  }

  /**
   * Swiper v11 bundle for widgets that require it.
   */
  public static function register_swiper_v11()
  {
    $version = defined('TESTIMONIALS_VERSION') ? TESTIMONIALS_VERSION : '12.0.1';

    wp_register_style(
      'swiper',
      plugins_url('/assets/css/swiper-bundle-v11.min.css', TESTIMONIALS_CAROUSEL_ELEMENTOR),
      [],
      $version
    );

    wp_register_script(
      'swiper',
      plugins_url('/assets/js/swiper-bundle-v11.min.js', TESTIMONIALS_CAROUSEL_ELEMENTOR),
      [],
      $version,
      true
    );

    global $wp_scripts;

    if (isset($wp_scripts->registered['testimonials-carousel-widget-handler'])) {
      $handler = $wp_scripts->registered['testimonials-carousel-widget-handler'];

      if (!in_array('swiper', $handler->deps, true)) {
        $handler->deps[] = 'swiper';
      }
    }
  }

  /**
   * Read a responsive control value with safe fallbacks (Elementor 3/4).
   *
   * @param array  $settings Widget display settings.
   * @param string $key      Control base name.
   * @param string $fallback Default when no value is set.
   * @return array{desktop: string, tablet: string, mobile: string}
   */
  public static function get_responsive_setting(array $settings, $key, $fallback = '')
  {
    $desktop = isset($settings[$key]) && $settings[$key] !== '' ? $settings[$key] : $fallback;

    return [
      'desktop' => $desktop,
      'tablet'  => isset($settings[$key . '_tablet']) && $settings[$key . '_tablet'] !== ''
        ? $settings[$key . '_tablet']
        : $desktop,
      'mobile'  => isset($settings[$key . '_mobile']) && $settings[$key . '_mobile'] !== ''
        ? $settings[$key . '_mobile']
        : (isset($settings[$key . '_tablet']) && $settings[$key . '_tablet'] !== ''
          ? $settings[$key . '_tablet']
          : $desktop),
    ];
  }
}
