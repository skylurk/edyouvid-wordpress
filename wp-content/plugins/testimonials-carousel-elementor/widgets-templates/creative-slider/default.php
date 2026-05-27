<?php use Elementor\Icons_Manager;

function get_default_creative_template($settings, $attributes)
{
  $slide = $settings['slide'];

  if (get_plugin_data(ELEMENTOR__FILE__)['Version'] < "3.5.0") { ?>
    <div <?php echo $attributes; ?>></div>
  <?php }

  if ($settings['slider_random'] === 'yes') {
    $keys = array_keys($slide);
    shuffle($keys);
    $slide = array_map(fn($key) => $slide[$key], $keys);
  }
  ?>

  <section class="creative">
    <div class="swiper mySwiper mySwiperCreative">
      <div class="swiper-wrapper">
        <?php $counter = 1;
        foreach ($slide as $item) {
          $alt = esc_attr($item['slide_image']['alt'] ?? ''); ?>
          <div class="swiper-slide" id="<?php echo esc_attr('slide-' . $counter); ?>">
            <?php if (isset($item['slide_image'], $item['slide_image']['url']) && !empty($item['slide_image']['url'])) { ?>
              <img src="<?php echo esc_url($item['slide_image']['url']); ?>"
                   alt="<?php echo esc_attr($alt); ?>">
            <?php } ?>
          </div>
          <?php $counter++;
        } ?>
      </div>
    </div>

    <?php if ($settings['creative_content_enable'] === 'yes') { ?>
      <div class="creative__wrapper-content">
        <?php $counter_content = 1;
        foreach ($slide as $item_content) { ?>
          <div class="creative__slide-content <?php if ($counter_content === 1) {
            echo esc_attr('active');
          } ?>"
               data-id="<?php echo esc_attr('slide-' . $counter_content); ?>">
            <?php if ($item_content['slide_title_enable'] === 'yes') { ?>
              <div class="creative__slide-title">
                <?php echo wp_kses_post($item_content['slide_title']); ?>
              </div>
            <?php } ?>

            <div class="creative__slide-text">
              <?php echo wp_kses_post($item_content['slide_description']); ?>
            </div>
          </div>
          <?php $counter_content++;
        } ?>
      </div>
    <?php }

    if (
        esc_attr($settings['navigation']) === "dots" || esc_attr($settings['navigation']) === "both"
    ) { ?>
      <div class="swiper-pagination"></div>
    <?php }

    if (
        esc_attr($settings['navigation']) === "both" || esc_attr($settings['navigation']) === "arrows"
    ) { ?>
      <div class="swiper-button-next">
        <?php Icons_Manager::render_icon($settings['icon_scroll_right'], ['aria-hidden' => 'true']) ?>
      </div>
      <div class="swiper-button-prev">
        <?php Icons_Manager::render_icon($settings['icon_scroll_left'], ['aria-hidden' => 'true']) ?>
      </div>
    <?php } ?>
  </section>
<?php } ?>
