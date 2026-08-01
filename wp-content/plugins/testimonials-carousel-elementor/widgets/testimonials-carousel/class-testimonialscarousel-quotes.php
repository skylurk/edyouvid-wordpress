<?php
/**
 * TestimonialsCarousel_Quotes class.
 *
 * @category   Class
 * @package    TestimonialsCarouselElementor
 * @subpackage WordPress
 * @author     UAPP GROUP
 * @copyright  2026 UAPP GROUP
 * @license    https://opensource.org/licenses/GPL-3.0 GPL-3.0-only
 * @link
 * @since      12.0.1
 * php version 7.4.1
 */

namespace TestimonialsCarouselElementor\Widgets;

use Elementor\Embed;
use Elementor\Plugin;
use Elementor\Repeater;
use Elementor\Widget_Base;
use Elementor\Icons_Manager;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

// Security Note: Blocks direct access to the plugin PHP files.
defined('ABSPATH') || die();

/**
 * TestimonialsCarousel_Quotes widget class.
 *
 * @since 12.0.1
 */
class TestimonialsCarousel_Quotes extends Widget_Base
{
  /**
   * Widget name.
   */
  public function get_name()
  {
    return 'testimonials-carousel-quotes';
  }

  /**
   * Widget title.
   */
  public function get_title()
  {
    return __('Testimonial Quotes Carousel', 'testimonials-carousel-elementor');
  }

  /**
   * Widget icon.
   */
  public function get_icon()
  {
    return 'icon-testimonials-carousel-quotes';
  }

  /**
   * Widget categories.
   */
  public function get_categories()
  {
    return ['testimonials_carousel'];
  }

  /**
   * Styles dependencies.
   */
  public function get_style_depends()
  {
    return ['owl-carousel', 'elementor-icons-fa-solid', 'testimonials-carousel-quotes'];
  }

  /**
   * Scripts dependencies.
   */
  public function get_script_depends()
  {
    return ['owl-carousel', 'testimonials-carousel-quotes-handler'];
  }

  /**
   * Default slide.
   */
  protected function get_default_slide()
  {
    return [
        'testimonial_text' => __('Lorem Ipsum is simply dummy text of the printing and typesetting industry.', 'testimonials-carousel-elementor'),

        'client_name' => __('John Doe', 'testimonials-carousel-elementor'),

        'client_role' => __('Developer', 'testimonials-carousel-elementor'),

        'client_icon' => [
            'value'   => 'fas fa-play',
            'library' => 'fa-solid',
        ],

        'client_video_source' => 'youtube',

        'client_youtube_video' => [
            'url' => '',
        ],

        'client_self_hosted_video' => [
            'url' => '',
        ],
    ];
  }

  /**
   * Register controls.
   */
  protected function register_controls()
  {
    // Content Section

    $this->start_controls_section(
        'section_content',
        [
            'label' => __('Content', 'testimonials-carousel-elementor'),
        ]
    );

    $repeater = new Repeater();

    $repeater->add_control(
        'testimonial_text',
        [
            'label'   => __('Text', 'testimonials-carousel-elementor'),
            'type'    => Controls_Manager::TEXTAREA,
            'default' => __('Lorem Ipsum is simply dummy text of the printing and typesetting industry.', 'testimonials-carousel-elementor'),
            'dynamic' => [
                'active' => true,
            ],
        ]
    );

    $repeater->add_control(
        'client_name',
        [
            'label'   => __('Name', 'testimonials-carousel-elementor'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('John Doe', 'testimonials-carousel-elementor'),
            'dynamic' => [
                'active' => true,
            ],
        ]
    );

    $repeater->add_control(
        'client_role',
        [
            'label'   => __('Role', 'testimonials-carousel-elementor'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('Developer', 'testimonials-carousel-elementor'),
            'dynamic' => [
                'active' => true,
            ],
        ]
    );

    $repeater->add_control(
        'client_icon',
        [
            'label'       => __('Icon', 'testimonials-carousel-elementor'),
            'type'        => Controls_Manager::ICONS,
            'default'     => [
                'value'   => 'fas fa-play',
                'library' => 'fa-solid',
            ],
            'recommended' => [
                'fa-solid' => [
                    'play',
                    'play-circle',
                    'circle-play',
                    'video',
                ],
            ],
        ]
    );

    $repeater->add_control(
        'client_video_source',
        [
            'label'     => __('Video Source', 'testimonials-carousel-elementor'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'youtube',
            'options'   => [
                'youtube'     => __('YouTube', 'testimonials-carousel-elementor'),
                'self_hosted' => __('Self Hosted', 'testimonials-carousel-elementor'),
            ],
            'separator' => 'before',
        ]
    );

    $repeater->add_control(
        'client_youtube_video',
        [
            'label'       => __('YouTube Video URL', 'testimonials-carousel-elementor'),
            'type'        => Controls_Manager::URL,
            'dynamic'     => [
                'active' => true,
            ],
            'placeholder' => __('https://www.youtube.com/watch?v=...', 'testimonials-carousel-elementor'),
            'options'     => false,
            'condition'   => [
                'client_video_source' => 'youtube',
            ],
        ]
    );

    $repeater->add_control(
        'client_self_hosted_video',
        [
            'label'       => __('Self Hosted Video', 'testimonials-carousel-elementor'),
            'type'        => Controls_Manager::MEDIA,
            'media_types' => ['video'],
            'dynamic'     => [
                'active' => true,
            ],
            'condition'   => [
                'client_video_source' => 'self_hosted',
            ],
        ]
    );

    $this->add_control(
        'slides',
        [
            'label'       => __('Slides', 'testimonials-carousel-elementor'),
            'type'        => Controls_Manager::REPEATER,
            'fields'      => $repeater->get_controls(),
            'default'     => [
                $this->get_default_slide(),
                $this->get_default_slide(),
                $this->get_default_slide(),
            ],
            'title_field' => '{{{ client_name }}}',
        ]
    );

    $this->add_control(
        'quote_icon',
        [
            'label'       => __('Quote Decoration Icon', 'testimonials-carousel-elementor'),
            'type'        => Controls_Manager::ICONS,
            'default'     => [
                'value'   => 'fas fa-quote-right',
                'library' => 'fa-solid',
            ],
            'recommended' => [
                'fa-solid' => [
                    'quote-left',
                    'quote-right',
                    'comment',
                    'comment-dots',
                ],
            ],
        ]
    );

    $this->end_controls_section();

    // Slider Options

    $this->start_controls_section(
        'section_slider_options',
        [
            'label' => __('Slider Options', 'testimonials-carousel-elementor'),
        ]
    );

    $this->start_controls_tabs('slider_options_tabs');

    $this->start_controls_tab(
        'slider_behavior_tab',
        [
            'label' => esc_html__('Behavior', 'testimonials-carousel-elementor'),
        ]
    );

    $this->add_control(
        'slider_loop',
        [
            'label'        => __('Loop', 'testimonials-carousel-elementor'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Yes', 'testimonials-carousel-elementor'),
            'label_off'    => __('No', 'testimonials-carousel-elementor'),
            'return_value' => 'yes',
            'default'      => 'yes',
        ]
    );

    $this->add_control(
        'autoplay',
        [
            'label'        => __('Autoplay', 'testimonials-carousel-elementor'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Yes', 'testimonials-carousel-elementor'),
            'label_off'    => __('No', 'testimonials-carousel-elementor'),
            'return_value' => 'yes',
            'default'      => '',
        ]
    );

    $this->add_control(
        'autoplay_timeout',
        [
            'label'     => __('Autoplay Timeout', 'testimonials-carousel-elementor'),
            'type'      => Controls_Manager::NUMBER,
            'default'   => 4000,
            'condition' => [
                'autoplay' => 'yes',
            ],
        ]
    );

    $this->add_control(
        'carousel_speed',
        [
            'label'   => __('Animation Speed (ms)', 'testimonials-carousel-elementor'),
            'type'    => Controls_Manager::NUMBER,
            'default' => 800,
            'min'     => 0,
            'max'     => 3000,
            'step'    => 50,
        ]
    );

    $this->end_controls_tab();

    $this->start_controls_tab(
        'slider_layout_tab',
        [
            'label' => esc_html__('Layout', 'testimonials-carousel-elementor'),
        ]
    );

    $this->add_control(
        'carousel_margin',
        [
            'label'   => __('Space Between Slides', 'testimonials-carousel-elementor'),
            'type'    => Controls_Manager::NUMBER,
            'default' => 50,
            'min'     => 0,
            'max'     => 120,
            'step'    => 1,
        ]
    );

    $this->add_responsive_control(
        'slides_to_show',
        [
            'label'          => __('Slides To Show', 'testimonials-carousel-elementor'),
            'type'           => Controls_Manager::SELECT,
            'default'        => '2',
            'tablet_default' => '1',
            'mobile_default' => '1',
            'options'        => [
                '1' => '1',
                '2' => '2',
                '3' => '3',
            ],
        ]
    );

    $this->end_controls_tab();

    $this->start_controls_tab(
        'slider_navigation_tab',
        [
            'label' => esc_html__('Navigation', 'testimonials-carousel-elementor'),
        ]
    );

    $this->add_control(
        'navigation',
        [
            'label'   => __('Navigation', 'testimonials-carousel-elementor'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'dots',
            'options' => [
                'dots'   => __('Dots', 'testimonials-carousel-elementor'),
                'arrows' => __('Arrows', 'testimonials-carousel-elementor'),
                'both'   => __('Dots + Arrows', 'testimonials-carousel-elementor'),
                'none'   => __('None', 'testimonials-carousel-elementor'),
            ],
        ]
    );

    $this->end_controls_tab();
    $this->end_controls_tabs();

    $this->end_controls_section();

    // Layout Styles

    $this->start_controls_section(
        'layout_styles_section',
        [
            'label' => __('Layout', 'testimonials-carousel-elementor'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]
    );

    $this->start_controls_tabs('layout_style_tabs');

    $this->start_controls_tab(
        'layout_section_tab',
        [
            'label' => esc_html__('Section', 'testimonials-carousel-elementor'),
        ]
    );

    $this->add_group_control(
        Group_Control_Background::get_type(),
        [
            'name'     => 'section_background',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .tcq-testimonial-area',
        ]
    );

    $this->add_responsive_control(
        'section_padding',
        [
            'label'      => esc_html__('Padding', 'testimonials-carousel-elementor'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em', 'rem', 'vw', 'custom'],
            'default'    => [
                'top'      => 80,
                'right'    => 0,
                'bottom'   => 80,
                'left'     => 0,
                'unit'     => 'px',
                'isLinked' => false,
            ],
            'selectors'  => [
                '{{WRAPPER}} .tcq-testimonial-area' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]
    );

    $this->end_controls_tab();

    $this->start_controls_tab(
        'layout_card_tab',
        [
            'label' => esc_html__('Card', 'testimonials-carousel-elementor'),
        ]
    );

    $this->add_group_control(
        Group_Control_Background::get_type(),
        [
            'name'     => 'card_background',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .tcq-testimonial-surface',
        ]
    );

    $this->add_responsive_control(
        'card_padding',
        [
            'label'      => esc_html__('Padding', 'testimonials-carousel-elementor'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em', 'rem', 'custom'],
            'default'    => [
                'top'      => 44,
                'right'    => 40,
                'bottom'   => 36,
                'left'     => 40,
                'unit'     => 'px',
                'isLinked' => false,
            ],
            'selectors'  => [
                '{{WRAPPER}} .tcq-testimonial-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]
    );

    $this->add_group_control(
        Group_Control_Border::get_type(),
        [
            'name'     => 'card_border',
            'selector' => '{{WRAPPER}} .tcq-testimonial-surface',
        ]
    );

    $this->add_responsive_control(
        'card_border_radius',
        [
            'label'      => esc_html__('Border Radius', 'testimonials-carousel-elementor'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em', 'rem', 'custom'],
            'default'    => [
                'top'      => 40,
                'right'    => 40,
                'bottom'   => 40,
                'left'     => 40,
                'unit'     => 'px',
                'isLinked' => true,
            ],
            'selectors'  => [
                '{{WRAPPER}} .tcq-testimonial-surface' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]
    );

    $this->add_group_control(
        Group_Control_Box_Shadow::get_type(),
        [
            'name'     => 'card_box_shadow',
            'selector' => '{{WRAPPER}} .tcq-testimonial-surface',
        ]
    );

    $this->end_controls_tab();

    $this->start_controls_tab(
        'layout_frame_tab',
        [
            'label' => esc_html__('Frame', 'testimonials-carousel-elementor'),
        ]
    );

    $this->add_control(
        'frame_color',
        [
            'label'     => esc_html__('Color', 'testimonials-carousel-elementor'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#000000',
            'selectors' => [
                '{{WRAPPER}} .tcq-frame-arm--tr' => 'border-color: {{VALUE}};',
                '{{WRAPPER}} .tcq-frame-arm--bl' => 'border-color: {{VALUE}};',
            ],
        ]
    );

    $this->add_responsive_control(
        'frame_stroke',
        [
            'label'      => esc_html__('Stroke Width', 'testimonials-carousel-elementor'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => [
                'px' => [
                    'min' => 1,
                    'max' => 20,
                ],
            ],
            'default'    => [
                'unit' => 'px',
                'size' => 7,
            ],
            'selectors'  => [
                '{{WRAPPER}} .tcq-testimonial-item' => '--tcq-frame-stroke: {{SIZE}}{{UNIT}};',
            ],
        ]
    );

    $this->add_responsive_control(
        'frame_gap',
        [
            'label'      => esc_html__('Frame Gap', 'testimonials-carousel-elementor'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'range'      => [
                'px' => [
                    'min' => 0,
                    'max' => 100,
                ],
                '%'  => [
                    'min' => 0,
                    'max' => 50,
                ],
            ],
            'default'    => [
                'unit' => '%',
                'size' => 16,
            ],
            'selectors'  => [
                '{{WRAPPER}} .tcq-testimonial-item' => '--tcq-frame-gap: {{SIZE}}{{UNIT}};',
            ],
        ]
    );

    $this->add_responsive_control(
        'frame_radius',
        [
            'label'      => esc_html__('Corner Radius', 'testimonials-carousel-elementor'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'default'    => [
                'unit' => 'px',
                'size' => 40,
            ],
            'selectors'  => [
                '{{WRAPPER}} .tcq-testimonial-item' => '--tcq-frame-radius: {{SIZE}}{{UNIT}}; border-radius: {{SIZE}}{{UNIT}};',
            ],
        ]
    );

    $this->end_controls_tab();
    $this->end_controls_tabs();

    $this->end_controls_section();

    // Content Styles

    $this->start_controls_section(
        'content_styles_section',
        [
            'label' => __('Content', 'testimonials-carousel-elementor'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]
    );

    $this->start_controls_tabs('content_style_tabs');

    $this->start_controls_tab(
        'content_testimonial_tab',
        [
            'label' => esc_html__('Text', 'testimonials-carousel-elementor'),
        ]
    );

    $this->add_responsive_control(
        'testimonial_text_align',
        [
            'label'     => esc_html__('Alignment', 'testimonials-carousel-elementor'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => [
                'left'   => [
                    'title' => esc_html__('Left', 'testimonials-carousel-elementor'),
                    'icon'  => 'eicon-text-align-left',
                ],
                'center' => [
                    'title' => esc_html__('Center', 'testimonials-carousel-elementor'),
                    'icon'  => 'eicon-text-align-center',
                ],
                'right'  => [
                    'title' => esc_html__('Right', 'testimonials-carousel-elementor'),
                    'icon'  => 'eicon-text-align-right',
                ],
            ],
            'default'   => 'center',
            'selectors' => [
                '{{WRAPPER}} .tcq-description' => 'text-align: {{VALUE}};',
            ],
        ]
    );

    $this->add_control(
        'testimonial_text_color',
        [
            'label'     => esc_html__('Color', 'testimonials-carousel-elementor'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#000000',
            'selectors' => [
                '{{WRAPPER}} .tcq-description' => 'color: {{VALUE}};',
            ],
        ]
    );

    $this->add_group_control(
        Group_Control_Typography::get_type(),
        [
            'name'     => 'testimonial_text_typography',
            'selector' => '{{WRAPPER}} .tcq-description',
        ]
    );

    $this->end_controls_tab();

    $this->start_controls_tab(
        'content_client_name_tab',
        [
            'label' => esc_html__('Name', 'testimonials-carousel-elementor'),
        ]
    );

    $this->add_control(
        'client_name_color',
        [
            'label'     => esc_html__('Color', 'testimonials-carousel-elementor'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#000000',
            'selectors' => [
                '{{WRAPPER}} .tcq-client-details h6' => 'color: {{VALUE}};',
            ],
        ]
    );

    $this->add_group_control(
        Group_Control_Typography::get_type(),
        [
            'name'     => 'client_name_typography',
            'selector' => '{{WRAPPER}} .tcq-client-details h6',
        ]
    );

    $this->end_controls_tab();

    $this->start_controls_tab(
        'content_client_role_tab',
        [
            'label' => esc_html__('Role', 'testimonials-carousel-elementor'),
        ]
    );

    $this->add_control(
        'client_role_color',
        [
            'label'     => esc_html__('Color', 'testimonials-carousel-elementor'),
            'type'      => Controls_Manager::COLOR,
            'default'   => 'rgba(0, 0, 0, 0.8)',
            'selectors' => [
                '{{WRAPPER}} .tcq-client-details span' => 'color: {{VALUE}};',
            ],
        ]
    );

    $this->add_group_control(
        Group_Control_Typography::get_type(),
        [
            'name'     => 'client_role_typography',
            'selector' => '{{WRAPPER}} .tcq-client-details span',
        ]
    );

    $this->end_controls_tab();

    $this->start_controls_tab(
        'content_client_info_tab',
        [
            'label' => esc_html__('Info', 'testimonials-carousel-elementor'),
        ]
    );

    $this->add_responsive_control(
        'client_info_align',
        [
            'label'     => esc_html__('Alignment', 'testimonials-carousel-elementor'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => [
                'flex-start' => [
                    'title' => esc_html__('Left', 'testimonials-carousel-elementor'),
                    'icon'  => 'eicon-text-align-left',
                ],
                'center'     => [
                    'title' => esc_html__('Center', 'testimonials-carousel-elementor'),
                    'icon'  => 'eicon-text-align-center',
                ],
                'flex-end'   => [
                    'title' => esc_html__('Right', 'testimonials-carousel-elementor'),
                    'icon'  => 'eicon-text-align-right',
                ],
            ],
            'default'   => 'flex-start',
            'selectors' => [
                '{{WRAPPER}} .tcq-client-info' => 'justify-content: {{VALUE}};',
            ],
        ]
    );

    $this->add_responsive_control(
        'client_info_gap',
        [
            'label'      => esc_html__('Gap', 'testimonials-carousel-elementor'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em', 'rem'],
            'range'      => [
                'px' => [
                    'min' => 0,
                    'max' => 60,
                ],
            ],
            'default'    => [
                'unit' => 'px',
                'size' => 15,
            ],
            'selectors'  => [
                '{{WRAPPER}} .tcq-client-info' => 'gap: {{SIZE}}{{UNIT}};',
            ],
        ]
    );

    $this->end_controls_tab();
    $this->end_controls_tabs();

    $this->end_controls_section();

    // Icons Styles

    $this->start_controls_section(
        'icon_styles_section',
        [
            'label' => __('Icons', 'testimonials-carousel-elementor'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]
    );

    $this->start_controls_tabs('icon_style_tabs');

    $this->start_controls_tab(
        'icon_quote_style_tab',
        [
            'label' => esc_html__('Quote', 'testimonials-carousel-elementor'),
        ]
    );

    $this->add_control(
        'quote_icon_color',
        [
            'label'     => esc_html__('Color', 'testimonials-carousel-elementor'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#000000',
            'selectors' => [
                '{{WRAPPER}} .tcq-quote-icon'          => 'color: {{VALUE}};',
                '{{WRAPPER}} .tcq-quote-icon i'        => 'color: {{VALUE}};',
                '{{WRAPPER}} .tcq-quote-icon svg'      => 'fill: {{VALUE}};',
                '{{WRAPPER}} .tcq-quote-icon svg path' => 'fill: {{VALUE}};',
            ],
        ]
    );

    $this->add_responsive_control(
        'quote_icon_size',
        [
            'label'          => esc_html__('Size', 'testimonials-carousel-elementor'),
            'type'           => Controls_Manager::SLIDER,
            'size_units'     => ['px', 'em', 'rem'],
            'range'          => [
                'px' => [
                    'min' => 16,
                    'max' => 120,
                ],
            ],
            'default'        => [
                'unit' => 'px',
                'size' => 60,
            ],
            'tablet_default' => [
                'unit' => 'px',
                'size' => 52,
            ],
            'mobile_default' => [
                'unit' => 'px',
                'size' => 42,
            ],
            'selectors'      => [
                '{{WRAPPER}} .tcq-quote-icon'     => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .tcq-quote-icon i'   => 'font-size: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .tcq-quote-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
            ],
        ]
    );

    $this->add_control(
        'quote_shadow_color',
        [
            'label'     => esc_html__('Shadow Color', 'testimonials-carousel-elementor'),
            'type'      => Controls_Manager::COLOR,
            'default'   => 'rgba(0, 0, 0, 0.5)',
            'selectors' => [
                '{{WRAPPER}} .tcq-quote' => '--tcq-quote-shadow-color: {{VALUE}};',
            ],
        ]
    );

    $this->add_responsive_control(
        'quote_shadow_horizontal',
        [
            'label'      => esc_html__('Shadow Horizontal', 'testimonials-carousel-elementor'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => [
                'px' => [
                    'min' => -30,
                    'max' => 30,
                ],
            ],
            'default'    => [
                'unit' => 'px',
                'size' => 0,
            ],
            'selectors'  => [
                '{{WRAPPER}} .tcq-quote' => '--tcq-quote-shadow-x: {{SIZE}}{{UNIT}};',
            ],
        ]
    );

    $this->add_responsive_control(
        'quote_shadow_vertical',
        [
            'label'      => esc_html__('Shadow Vertical', 'testimonials-carousel-elementor'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => [
                'px' => [
                    'min' => -30,
                    'max' => 30,
                ],
            ],
            'default'    => [
                'unit' => 'px',
                'size' => 1,
            ],
            'selectors'  => [
                '{{WRAPPER}} .tcq-quote' => '--tcq-quote-shadow-y: {{SIZE}}{{UNIT}};',
            ],
        ]
    );

    $this->add_responsive_control(
        'quote_shadow_blur',
        [
            'label'      => esc_html__('Shadow Blur', 'testimonials-carousel-elementor'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => [
                'px' => [
                    'min' => 0,
                    'max' => 50,
                ],
            ],
            'default'    => [
                'unit' => 'px',
                'size' => 3,
            ],
            'selectors'  => [
                '{{WRAPPER}} .tcq-quote' => '--tcq-quote-shadow-blur: {{SIZE}}{{UNIT}};',
            ],
        ]
    );

    $this->end_controls_tab();

    $this->start_controls_tab(
        'icon_quote_open_tab',
        [
            'label' => esc_html__('Opening', 'testimonials-carousel-elementor'),
        ]
    );

    $this->add_responsive_control(
        'quote_open_top',
        [
            'label'          => esc_html__('Top', 'testimonials-carousel-elementor'),
            'type'           => Controls_Manager::SLIDER,
            'size_units'     => ['px', '%', 'em', 'rem', 'vw', 'custom'],
            'range'          => [
                'px' => [
                    'min' => -150,
                    'max' => 150,
                ],
            ],
            'default'        => [
                'unit' => 'px',
                'size' => -25,
            ],
            'tablet_default' => [
                'unit' => 'px',
                'size' => -20,
            ],
            'mobile_default' => [
                'unit' => 'px',
                'size' => -16,
            ],
            'selectors'      => [
                '{{WRAPPER}} .tcq-quote--open' => 'top: {{SIZE}}{{UNIT}};',
            ],
        ]
    );

    $this->add_responsive_control(
        'quote_open_left',
        [
            'label'          => esc_html__('Left', 'testimonials-carousel-elementor'),
            'type'           => Controls_Manager::SLIDER,
            'size_units'     => ['px', '%', 'em', 'rem', 'vw', 'custom'],
            'range'          => [
                'px' => [
                    'min' => -150,
                    'max' => 150,
                ],
            ],
            'default'        => [
                'unit' => 'px',
                'size' => 5,
            ],
            'tablet_default' => [
                'unit' => 'px',
                'size' => 4,
            ],
            'mobile_default' => [
                'unit' => 'px',
                'size' => 2,
            ],
            'selectors'      => [
                '{{WRAPPER}} .tcq-quote--open' => 'left: {{SIZE}}{{UNIT}};',
            ],
        ]
    );

    $this->end_controls_tab();

    $this->start_controls_tab(
        'icon_quote_close_tab',
        [
            'label' => esc_html__('Closing', 'testimonials-carousel-elementor'),
        ]
    );

    $this->add_responsive_control(
        'quote_close_right',
        [
            'label'          => esc_html__('Right', 'testimonials-carousel-elementor'),
            'type'           => Controls_Manager::SLIDER,
            'size_units'     => ['px', '%', 'em', 'rem', 'vw', 'custom'],
            'range'          => [
                'px' => [
                    'min' => -150,
                    'max' => 150,
                ],
            ],
            'default'        => [
                'unit' => 'px',
                'size' => 5,
            ],
            'tablet_default' => [
                'unit' => 'px',
                'size' => 4,
            ],
            'mobile_default' => [
                'unit' => 'px',
                'size' => 2,
            ],
            'selectors'      => [
                '{{WRAPPER}} .tcq-quote--close' => 'right: {{SIZE}}{{UNIT}};',
            ],
        ]
    );

    $this->add_responsive_control(
        'quote_close_bottom',
        [
            'label'          => esc_html__('Bottom', 'testimonials-carousel-elementor'),
            'type'           => Controls_Manager::SLIDER,
            'size_units'     => ['px', '%', 'em', 'rem', 'vw', 'custom'],
            'range'          => [
                'px' => [
                    'min' => -150,
                    'max' => 150,
                ],
            ],
            'default'        => [
                'unit' => 'px',
                'size' => -25,
            ],
            'tablet_default' => [
                'unit' => 'px',
                'size' => -20,
            ],
            'mobile_default' => [
                'unit' => 'px',
                'size' => -16,
            ],
            'selectors'      => [
                '{{WRAPPER}} .tcq-quote--close' => 'bottom: {{SIZE}}{{UNIT}};',
            ],
        ]
    );

    $this->end_controls_tab();

    $this->start_controls_tab(
        'icon_client_tab',
        [
            'label' => esc_html__('Play Icon', 'testimonials-carousel-elementor'),
        ]
    );

    $this->add_control(
        'client_icon_color',
        [
            'label'     => esc_html__('Color', 'testimonials-carousel-elementor'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#000000',
            'selectors' => [
                '{{WRAPPER}} .tcq-client-icon'          => 'color: {{VALUE}};',
                '{{WRAPPER}} .tcq-client-icon i'        => 'color: {{VALUE}};',
                '{{WRAPPER}} .tcq-client-icon svg'      => 'fill: {{VALUE}};',
                '{{WRAPPER}} .tcq-client-icon svg path' => 'fill: {{VALUE}};',
            ],
        ]
    );

    $this->add_responsive_control(
        'client_icon_size',
        [
            'label'      => esc_html__('Icon Size', 'testimonials-carousel-elementor'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em', 'rem'],
            'range'      => [
                'px' => [
                    'min' => 8,
                    'max' => 80,
                ],
            ],
            'default'    => [
                'unit' => 'px',
                'size' => 22,
            ],
            'selectors'  => [
                '{{WRAPPER}} .tcq-client-icon i'   => 'font-size: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .tcq-client-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
            ],
        ]
    );

    $this->add_responsive_control(
        'client_icon_box_size',
        [
            'label'      => esc_html__('Box Size', 'testimonials-carousel-elementor'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em', 'rem'],
            'range'      => [
                'px' => [
                    'min' => 30,
                    'max' => 120,
                ],
            ],
            'default'    => [
                'unit' => 'px',
                'size' => 50,
            ],
            'selectors'  => [
                '{{WRAPPER}} .tcq-client-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
            ],
            'separator'  => 'before',
        ]
    );

    $this->add_group_control(
        Group_Control_Background::get_type(),
        [
            'name'     => 'client_icon_background',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .tcq-client-icon',
        ]
    );

    $this->add_group_control(
        Group_Control_Border::get_type(),
        [
            'name'     => 'client_icon_border',
            'selector' => '{{WRAPPER}} .tcq-client-icon',
        ]
    );

    $this->add_group_control(
        Group_Control_Box_Shadow::get_type(),
        [
            'name'     => 'client_icon_box_shadow',
            'selector' => '{{WRAPPER}} .tcq-client-icon',
        ]
    );

    $this->add_responsive_control(
        'client_icon_border_radius',
        [
            'label'      => esc_html__('Border Radius', 'testimonials-carousel-elementor'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em', 'rem', 'custom'],
            'default'    => [
                'top'      => 50,
                'right'    => 50,
                'bottom'   => 50,
                'left'     => 50,
                'unit'     => '%',
                'isLinked' => true,
            ],
            'selectors'  => [
                '{{WRAPPER}} .tcq-client-icon' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]
    );

    $this->end_controls_tab();
    $this->end_controls_tabs();

    $this->end_controls_section();

    // Navigation Styles

    $this->start_controls_section(
        'navigation_dots_styles_section',
        [
            'label'     => __('Pagination', 'testimonials-carousel-elementor'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => [
                'navigation' => 'dots',
            ],
        ]
    );

    $this->add_navigation_dots_style_controls();

    $this->end_controls_section();

    $this->start_controls_section(
        'navigation_arrows_styles_section',
        [
            'label'     => __('Arrows', 'testimonials-carousel-elementor'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => [
                'navigation' => 'arrows',
            ],
        ]
    );

    $this->add_navigation_arrows_style_controls();

    $this->end_controls_section();

    $this->start_controls_section(
        'navigation_styles_section',
        [
            'label'     => __('Navigation', 'testimonials-carousel-elementor'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => [
                'navigation' => 'both',
            ],
        ]
    );

    $this->start_controls_tabs('navigation_style_tabs');

    $this->start_controls_tab(
        'navigation_pagination_tab',
        [
            'label' => esc_html__('Pagination', 'testimonials-carousel-elementor'),
        ]
    );

    $this->add_navigation_dots_style_controls('_both');

    $this->end_controls_tab();

    $this->start_controls_tab(
        'navigation_arrows_tab',
        [
            'label' => esc_html__('Arrows', 'testimonials-carousel-elementor'),
        ]
    );

    $this->add_navigation_arrows_style_controls('_both');

    $this->end_controls_tab();
    $this->end_controls_tabs();

    $this->end_controls_section();
  }

  /**
   * Add pagination style controls.
   */
  protected function add_navigation_dots_style_controls($suffix = '')
  {
    $this->add_responsive_control(
        'dots_margin' . $suffix,
        [
            'label'      => esc_html__('Margin', 'testimonials-carousel-elementor'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em', 'rem', 'custom'],
            'default'    => [
                'top'      => 40,
                'right'    => 0,
                'bottom'   => 0,
                'left'     => 0,
                'unit'     => 'px',
                'isLinked' => false,
            ],
            'selectors'  => [
                '{{WRAPPER}} .tcq-testimonial-slider .owl-dots' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]
    );

    $this->add_control(
        'dots_size' . $suffix,
        [
            'label'     => esc_html__('Dot Size', 'testimonials-carousel-elementor'),
            'type'      => Controls_Manager::SLIDER,
            'range'     => [
                'px' => [
                    'min' => 4,
                    'max' => 24,
                ],
            ],
            'default'   => [
                'unit' => 'px',
                'size' => 12,
            ],
            'selectors' => [
                '{{WRAPPER}} .tcq-testimonial-slider .owl-dots .owl-dot' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
            ],
        ]
    );

    $this->add_control(
        'active_dot_width' . $suffix,
        [
            'label'     => esc_html__('Active Dot Width', 'testimonials-carousel-elementor'),
            'type'      => Controls_Manager::SLIDER,
            'range'     => [
                'px' => [
                    'min' => 8,
                    'max' => 80,
                ],
            ],
            'default'   => [
                'unit' => 'px',
                'size' => 34,
            ],
            'selectors' => [
                '{{WRAPPER}} .tcq-testimonial-slider .owl-dots .owl-dot.active' => 'width: {{SIZE}}{{UNIT}};',
            ],
        ]
    );

    $this->add_control(
        'dots_spacing' . $suffix,
        [
            'label'     => esc_html__('Spacing', 'testimonials-carousel-elementor'),
            'type'      => Controls_Manager::SLIDER,
            'range'     => [
                'px' => [
                    'min' => 0,
                    'max' => 30,
                ],
            ],
            'default'   => [
                'unit' => 'px',
                'size' => 6,
            ],
            'selectors' => [
                '{{WRAPPER}} .tcq-testimonial-slider .owl-dots .owl-dot' => 'margin-left: {{SIZE}}{{UNIT}}; margin-right: {{SIZE}}{{UNIT}};',
            ],
        ]
    );

    $this->add_control(
        'dots_color' . $suffix,
        [
            'label'     => esc_html__('Dot Color', 'testimonials-carousel-elementor'),
            'type'      => Controls_Manager::COLOR,
            'default'   => 'rgba(0, 0, 0, 0.5)',
            'selectors' => [
                '{{WRAPPER}} .tcq-testimonial-slider .owl-dots .owl-dot' => 'background: {{VALUE}};',
            ],
        ]
    );

    $this->add_control(
        'active_dot_color' . $suffix,
        [
            'label'     => esc_html__('Active Dot Color', 'testimonials-carousel-elementor'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#000000',
            'selectors' => [
                '{{WRAPPER}} .tcq-testimonial-slider .owl-dots .owl-dot.active' => 'background: {{VALUE}};',
            ],
        ]
    );
  }

  /**
   * Add arrow style controls.
   */
  protected function add_navigation_arrows_style_controls($suffix = '')
  {
    $this->add_control(
        'arrows_position_heading' . $suffix,
        [
            'label' => esc_html__('Position', 'testimonials-carousel-elementor'),
            'type'  => Controls_Manager::HEADING,
        ]
    );

    $this->add_responsive_control(
        'arrows_vertical_position' . $suffix,
        [
            'label'      => esc_html__('Vertical Position', 'testimonials-carousel-elementor'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['%', 'px'],
            'range'      => [
                '%'  => [
                    'min' => 0,
                    'max' => 100,
                ],
                'px' => [
                    'min' => -100,
                    'max' => 600,
                ],
            ],
            'default'    => [
                'unit' => '%',
                'size' => 50,
            ],
            'selectors'  => [
                '{{WRAPPER}} .tcq-testimonial-slider' => '--tcq-arrow-top: {{SIZE}}{{UNIT}};',
            ],
        ]
    );

    $this->add_responsive_control(
        'arrows_prev_offset' . $suffix,
        [
            'label'      => esc_html__('Previous Arrow Offset', 'testimonials-carousel-elementor'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'range'      => [
                'px' => [
                    'min' => -120,
                    'max' => 120,
                ],
                '%'  => [
                    'min' => -50,
                    'max' => 50,
                ],
            ],
            'default'    => [
                'unit' => 'px',
                'size' => -26,
            ],
            'selectors'  => [
                '{{WRAPPER}} .tcq-testimonial-slider' => '--tcq-arrow-prev-offset: {{SIZE}}{{UNIT}};',
            ],
        ]
    );

    $this->add_responsive_control(
        'arrows_next_offset' . $suffix,
        [
            'label'      => esc_html__('Next Arrow Offset', 'testimonials-carousel-elementor'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'range'      => [
                'px' => [
                    'min' => -120,
                    'max' => 120,
                ],
                '%'  => [
                    'min' => -50,
                    'max' => 50,
                ],
            ],
            'default'    => [
                'unit' => 'px',
                'size' => -26,
            ],
            'selectors'  => [
                '{{WRAPPER}} .tcq-testimonial-slider' => '--tcq-arrow-next-offset: {{SIZE}}{{UNIT}};',
            ],
        ]
    );

    $this->add_control(
        'arrows_style_heading' . $suffix,
        [
            'label'     => esc_html__('Style', 'testimonials-carousel-elementor'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]
    );

    $this->add_control(
        'arrows_size' . $suffix,
        [
            'label'     => esc_html__('Arrow Size', 'testimonials-carousel-elementor'),
            'type'      => Controls_Manager::SLIDER,
            'range'     => [
                'px' => [
                    'min' => 20,
                    'max' => 80,
                ],
            ],
            'default'   => [
                'unit' => 'px',
                'size' => 52,
            ],
            'selectors' => [
                '{{WRAPPER}} .tcq-testimonial-slider .owl-nav button' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; font-size: calc({{SIZE}}{{UNIT}} / 2);',
            ],
        ]
    );

    $this->add_control(
        'arrows_color' . $suffix,
        [
            'label'     => esc_html__('Arrow Color', 'testimonials-carousel-elementor'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#000000',
            'selectors' => [
                '{{WRAPPER}} .tcq-testimonial-slider .owl-nav button' => 'color: {{VALUE}}; border-color: {{VALUE}};',
            ],
        ]
    );

    $this->add_control(
        'arrows_background' . $suffix,
        [
            'label'     => esc_html__('Arrow Background', 'testimonials-carousel-elementor'),
            'type'      => Controls_Manager::COLOR,
            'default'   => 'rgba(255, 255, 255, 0.94)',
            'selectors' => [
                '{{WRAPPER}} .tcq-testimonial-slider .owl-nav button' => 'background: {{VALUE}};',
            ],
        ]
    );

    $this->add_control(
        'arrows_hover_color' . $suffix,
        [
            'label'     => esc_html__('Arrow Hover Color', 'testimonials-carousel-elementor'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => [
                '{{WRAPPER}} .tcq-testimonial-slider .owl-nav button:hover' => 'color: {{VALUE}}; border-color: {{VALUE}};',
            ],
        ]
    );

    $this->add_control(
        'arrows_hover_background' . $suffix,
        [
            'label'     => esc_html__('Arrow Hover Background', 'testimonials-carousel-elementor'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#000000',
            'selectors' => [
                '{{WRAPPER}} .tcq-testimonial-slider .owl-nav button:hover' => 'background: {{VALUE}};',
            ],
        ]
    );
  }

  /**
   * Get slide video lightbox data.
   */
  protected function get_slide_video_lightbox_data($item)
  {
    $video_source = !empty($item['client_video_source']) ? $item['client_video_source'] : 'youtube';
    $video_url    = '';
    $lightbox_url = '';
    $video_type   = 'youtube';

    if ('self_hosted' === $video_source) {
      $video_url    = !empty($item['client_self_hosted_video']['url']) ? $item['client_self_hosted_video']['url'] : '';
      $lightbox_url = $video_url;
      $video_type   = 'hosted';
    } else {
      $video_url = !empty($item['client_youtube_video']['url']) ? $item['client_youtube_video']['url'] : '';

      if ($video_url) {
        $lightbox_url = Embed::get_embed_url(
            $video_url,
            [
                'autoplay'    => 1,
                'playsinline' => 1,
                'enablejsapi' => 1,
                'rel'         => 0,
                'wmode'       => 'opaque',
                'origin'      => home_url(),
            ]
        );
      }
    }

    if (empty($video_url) || empty($lightbox_url)) {
      return [];
    }

    $lightbox_options = [
        'type'         => 'video',
        'videoType'    => $video_type,
        'url'          => esc_url_raw($lightbox_url),
        'autoplay'     => 'yes',
        'modalOptions' => [
            'id'               => 'elementor-lightbox-' . $this->get_id(),
            'videoAspectRatio' => '169',
        ],
    ];

    if ('hosted' === $video_type) {
      $lightbox_options['videoParams'] = [
          'controls' => '',
          'preload'  => 'metadata',
      ];
    }

    return [
        'href'         => esc_url_raw($video_url),
        'lightbox_url' => esc_url_raw($lightbox_url),
        'options'      => $lightbox_options,
    ];
  }

  /**
   * Render widget.
   */
  protected function render()
  {
    $settings = $this->get_settings_for_display();

    $slides = $settings['slides'];

    if (empty($slides)) {
      return;
    }

    $widget_id = 'tcq-slider-' . $this->get_id();

    $show_dots = (
        $settings['navigation'] === 'dots'
        || $settings['navigation'] === 'both'
    );

    $show_arrows = (
        $settings['navigation'] === 'arrows'
        || $settings['navigation'] === 'both'
    );

    $carousel_margin = isset($settings['carousel_margin']) ? (int)$settings['carousel_margin'] : 50;
    if ($carousel_margin < 0) {
      $carousel_margin = 0;
    }

    $carousel_speed = isset($settings['carousel_speed']) ? (int)$settings['carousel_speed'] : 800;
    if ($carousel_speed < 0) {
      $carousel_speed = 800;
    }

    $slides_to_show = \TestimonialsCarouselElementor\Testimonials_Carousel_Assets::get_responsive_setting(
        $settings,
        'slides_to_show',
        '2'
    );

    ?>

    <section class="tcq-testimonial-area">
      <div class="tcq-container">
        <div id="<?php echo esc_attr($widget_id); ?>"
             class="tcq-testimonial-slider owl-carousel"
             data-loop="<?php echo esc_attr($settings['slider_loop']); ?>"
             data-autoplay="<?php echo esc_attr($settings['autoplay']); ?>"
             data-autoplay-timeout="<?php echo esc_attr($settings['autoplay_timeout']); ?>"
             data-margin="<?php echo esc_attr($carousel_margin); ?>"
             data-smart-speed="<?php echo esc_attr($carousel_speed); ?>"
             data-slides-desktop="<?php echo esc_attr($slides_to_show['desktop']); ?>"
             data-slides-tablet="<?php echo esc_attr($slides_to_show['tablet']); ?>"
             data-slides-mobile="<?php echo esc_attr($slides_to_show['mobile']); ?>"
             data-dots="<?php echo esc_attr($show_dots ? 'yes' : 'no'); ?>"
             data-nav="<?php echo esc_attr($show_arrows ? 'yes' : 'no'); ?>"
        >
          <?php foreach ($slides as $index => $item) : ?>
            <div class="tcq-testimonial-item">
              <div class="tcq-testimonial-surface" aria-hidden="true"></div>
              <div class="tcq-frame" aria-hidden="true">
                <span class="tcq-frame-arm tcq-frame-arm--tr"></span>
                <span class="tcq-frame-arm tcq-frame-arm--bl"></span>
              </div>
              <span class="tcq-quote tcq-quote--open" aria-hidden="true">
                <span class="tcq-quote-icon">
                  <?php Icons_Manager::render_icon($settings['quote_icon'], ['aria-hidden' => 'true']); ?>
                </span>
              </span>
              <span class="tcq-quote tcq-quote--close" aria-hidden="true">
                <span class="tcq-quote-icon">
                  <?php Icons_Manager::render_icon($settings['quote_icon'], ['aria-hidden' => 'true']); ?>
                </span>
              </span>
              <div class="tcq-testimonial-inner">
                <p class="tcq-description">
                  <?php echo esc_html($item['testimonial_text']); ?>
                </p>

                <div class="tcq-client-info">
                  <div class="tcq-client-video">
                    <?php
                    $video_lightbox_data = $this->get_slide_video_lightbox_data($item);

                    if ($video_lightbox_data) {
                      $video_link_key = 'client_video_link_' . $index;
                      $this->add_render_attribute(
                          $video_link_key,
                          [
                              'href'                          => $video_lightbox_data['href'],
                              'class'                         => 'tcq-client-icon disable-owl-swipe',
                              'aria-label'                    => esc_attr__('Open testimonial video', 'testimonials-carousel-elementor'),
                              'data-elementor-open-lightbox'  => 'yes',
                              'data-elementor-lightbox-video' => $video_lightbox_data['lightbox_url'],
                              'data-elementor-lightbox'       => wp_json_encode($video_lightbox_data['options']),
                          ]
                      );

                      if (
                          Plugin::instance()->frontend
                          && method_exists(Plugin::instance()->frontend, 'create_action_hash')
                      ) {
                        $this->add_render_attribute(
                            $video_link_key,
                            'data-e-action-hash',
                            Plugin::instance()->frontend->create_action_hash('lightbox', $video_lightbox_data['options'])
                        );
                      }

                      if (Plugin::$instance->editor->is_edit_mode()) {
                        $this->add_render_attribute($video_link_key, 'class', 'elementor-clickable');
                      }
                      ?>
                      <a <?php $this->print_render_attribute_string($video_link_key); ?>>
                        <?php Icons_Manager::render_icon($item['client_icon'], ['aria-hidden' => 'true']); ?>
                      </a>
                    <?php } else { ?>
                      <span class="tcq-client-icon" aria-hidden="true">
                        <?php Icons_Manager::render_icon($item['client_icon'], ['aria-hidden' => 'true']); ?>
                      </span>
                    <?php } ?>
                  </div>

                  <div class="tcq-client-details">
                    <h6>
                      <?php echo esc_html($item['client_name']); ?>
                    </h6>

                    <span>
                      <?php echo esc_html($item['client_role']); ?>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php
  }
}