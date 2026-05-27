<?php
namespace uncanny_pro_toolkit;

/**
 * Available variables
 * ===================
 *
 * $grid_classes -- Core grid layout classes
 *
 * @param bool $completed -- If Course is completed
 * @param $permalink -- Link to Course
 * @param $atts -- Array of shortcode attributes
 * @param $course_price_type -- LearnDash Course Access Mode ex. free,close,paid
 * @param $course_price -- LearnDash Course Price
 * @param $course -- Course Post Object
 * @param $currency -- $ default // Set from LearnDash
 * @param $short_description -- Short Description of course
 * @param $hide_progress -- Hide progress & percentage
 * @param $status_icon -- Completed, In Progress etc
 * @param $show_start_button -- Show Start Course Button
 * @param $percentage -- Course progress in percentage
 */
$completed_class = '';
if ( $completed ) {
	$completed_class = 'completed';
}
?>
<div class="<?php echo esc_attr( implode( ' ', $grid_classes ) ); ?>">
	<div class="uo-border <?php echo esc_attr( $completed_class ); ?>">
		<a href="<?php echo esc_url_raw( $permalink ); ?>">
			<?php do_action_deprecated( 'uo-course-grid-before-course-info-holder', array( $course ), '3.7.11', 'uo_course_grid_before_course_info_holder' ); ?>
			<?php do_action( 'uo_course_grid_before_course_info_holder', $course ); ?>
			<!-- Price Ribbon section -->
			<?php if ( 'yes' === $atts['price'] && 'yes' === $atts['show_image'] ) { ?>
				<div id="ribbon"
					 class="price <?php echo ! empty( $course_price_type ) ? esc_attr( 'price_' . $currency ) : esc_html( $course_price_type ); ?>">
					<?php
					do_action( 'uo_course_before_grid_ribbon_text', $course->ID );
					if ( empty( $course_price ) ) {
						switch ( strtolower( $course_price_type ) ) {
							case 'open':
							case 'free':
								$output = esc_attr__( 'Free', 'uncanny-pro-toolkit' );
								break;

							// Buy now
							case 'paynow':
								$output = esc_attr__( 'Buy now', 'uncanny-pro-toolkit' );
								break;

							// Recurring
							case 'subscribe':
								$output = esc_attr__( 'Subscribe', 'uncanny-pro-toolkit' );
								break;

							// Closed
							case 'closed':
								$output = esc_attr__( 'Closed', 'uncanny-pro-toolkit' );
								break;

							default:
								$output = esc_html( $course_price_type );
								break;
						}
						echo wp_kses_post( apply_filters( 'uo_course_grid_ribbon_text', $output, $course->ID ) );
						do_action( 'uo_course_after_grid_ribbon_text', $course->ID );
					} else {
						echo wp_kses_post( apply_filters( 'uo_course_grid_ribbon_price', $course_price, $course->ID ) );
						do_action( 'uo_course_after_grid_ribbon_price', $course->ID );
					}

					?>
				</div>
			<?php } ?>
			<!-- Price Ribbon section -- End -->
			<!-- Feature Image -- Start -->
			<?php if ( 'yes' === $atts['show_image'] ) { ?>
				<div class="featured-image">
					<?php 
					$default_alt_attr = $image_alt = $course->post_title . ' course image';
					if ( has_post_thumbnail( $course->ID ) ) {
						$thumbnail_id = get_post_thumbnail_id( $course->ID );
						$image_alt = get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true );
						if( empty( $image_alt ) ) {
							$image_alt = $default_alt_attr;
						}
					 ?>
						<img
							src="<?php echo esc_url_raw( \uncanny_pro_toolkit\ShowAllCourses::resize_grid_image( $course->ID, 'uo_course_image_size' ) ); ?>"
							class="uo-grid-featured-image"
							alt="<?php echo esc_attr( $image_alt ); ?>"/>
					<?php } else { ?>
						<img
							src="<?php echo esc_url_raw( plugins_url( '/assets/legacy/frontend/img/no_image.jpg', dirname( __FILE__ ) ) ); ?>"
							class="uo-grid-featured-image"
							alt="<?php echo esc_attr( $image_alt ); ?>"/>
					<?php } ?>
				</div>
				<?php
			}
			?>
			<!-- Feature Image -- End -->
			<!-- Course Info holder -- Start -->
			<div class="course-info-holder <?php echo esc_attr( $completed_class ); ?>">
				<!-- Course title -->
				<div
					class="course-before-title"><?php do_action( 'uo_course_before_course_title', $course->ID ); ?></div>
				<?php if ( 'no' === $atts['hide_title'] ) { ?>
					<div class="course-title"><?php echo esc_attr( $course->post_title ); ?></div>
				<?php } ?>
				<div class="course-after-title"><?php do_action( 'uo_course_after_course_title', $course->ID ); ?></div>
				<!-- Course title - End -->

				<?php
				/**
				 * Check plugin activity is not on the page plugins.
				 */
				require_once ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'plugin.php';
				if ( defined( 'CEU_PLUGIN_NAME' ) ) {
					$points = get_post_meta( $course->ID, 'ceu_value', true );
					if ( ( 'no' === $atts['hide_credits'] ) && ! empty( $points ) && $points > 0 ) {
						?>
						<p class="cue-points">
							<?php
							echo esc_attr( $points );
							echo ' ';
							if ( 1 === absint( $points ) ) {
								echo esc_attr( get_option( 'credit_designation_label', esc_attr__( 'CEU', 'uncanny-ceu' ) ) );
							} else {
								echo esc_attr( get_option( 'credit_designation_label_plural', esc_attr__( 'CEUs', 'uncanny-ceu' ) ) );
							}
							?>
						</p>
						<?php
					}
				}
				?>
				<!-- Course description - Start -->
				<div
					class="course-before-short-description"><?php do_action( 'uo_course_before_short_description', $course->ID ); ?></div>
				<?php
				if ( ( 'no' === $atts['hide_description'] ) && $short_description ) {
					?>
					<p class="uo-course-short-desciption"><?php echo wp_kses_post( $short_description ); ?></p>
					<?php
				}
				?>
				<div
					class="course-after-short-description"><?php do_action( 'uo_course_after_short_description', $course->ID ); ?></div>
				<!-- Course description - End -->
			</div>
			<div class="course-info-holder  <?php echo esc_attr( $completed_class ); ?> bottom">
				<?php
				/* translators: LearnDash course title */
				if ( sprintf( esc_html__( 'View %s Outline', 'uncanny-pro-toolkit' ), \LearnDash_Custom_Label::get_label( 'course' ) ) !== $status_icon && 'Coming Soon' !== $status_icon ) {
					?>
					<?php if ( 'no' === $hide_progress ) { ?>
						<h3 class="percentage"><?php echo esc_attr( $percentage ); ?>%</h3>
						<dd class="uo-course-progress" title="">
							<div class="course_progress" style="width: <?php echo esc_attr( $percentage ); ?>%;">
							</div>
						</dd>
						<div
							class="list-tag-container <?php echo esc_attr( sanitize_title( $status_icon ) ); ?>"><?php echo wp_kses_post( $status_icon ); ?></div>
					<?php } ?>
					<?php
				} /* translators: LearnDash course title */
				elseif ( sprintf( esc_html__( 'View %s Outline', 'uncanny-pro-toolkit' ), \LearnDash_Custom_Label::get_label( 'course' ) ) !== $status_icon ) {
					?>
					<?php if ( 'no' === $hide_progress ) { ?>
						<dd class="uo-course-progress" title="" style="visibility: hidden">
							<div class="course_progress" style="width: 100%;">
							</div>
						</dd>
					<?php } ?>
					<h4><?php esc_html_e( 'Coming Soon', 'uncanny-pro-toolkit' ); ?></h4>
					<div class="list-tag-container <?php echo esc_attr( sanitize_title( 'Coming Soon' ) ); ?>"
						 style="visibility: hidden">
						&nbsp;
					</div>
					<?php
				} /* translators: LearnDash course title */
				elseif ( sprintf( esc_html__( 'View %s Outline', 'uncanny-pro-toolkit' ), \LearnDash_Custom_Label::get_label( 'course' ) ) === $status_icon ) {
					?>
					<?php if ( 'no' === $hide_progress ) { ?>
						<dd class="uo-course-progress" title="" style="visibility: hidden">
							<div class="course_progress" style="width: 100%;">
							</div>
						</dd>
					<?php } ?>
					<h4 class="view-course-outline">
						<?php
						/* translators: LearnDash course title */
						echo esc_attr( sprintf( esc_html__( 'View %s Outline', 'uncanny-pro-toolkit' ), \LearnDash_Custom_Label::get_label( 'course' ) ) );
						?>
					</h4>
					<div class="list-tag-container <?php echo esc_attr( sanitize_title( 'View Course Outline' ) ); ?>"
						 style="visibility: hidden">
						&nbsp;
					</div>
				<?php } ?>
			</div>

			<?php do_action_deprecated( 'uo-course-grid-after-course-info-holder', $course, '3.7.11', 'uo_course_grid_after_course_info_holder' ); ?>
			<?php do_action( 'uo_course_grid_after_course_info_holder', $course ); ?>
		</a>
		<?php
		/* translators: LearnDash course title */
		if ( sprintf( esc_html__( 'View %s Outline', 'uncanny-pro-toolkit' ), \LearnDash_Custom_Label::get_label( 'course' ) ) !== $status_icon && 'Coming Soon' !== $status_icon ) {
			if ( ( 'show' === $show_start_button && 0 === $percentage ) || ( 'show' === $show_resume_button && $percentage > 0 && $percentage < 100 ) ) {
				?>
				<div class="uo-toolkit-grid__course-action">
					<?php do_action_deprecated( 'uo-course-grid-before-action-buttons', array( $course ), '3.7.11', 'uo_course_grid_before_action_buttons' ); ?>
					<?php do_action( 'uo_course_grid_before_action_buttons', $course ); ?>
					<?php
					if ( 'show' === $show_start_button && 0 === $percentage ) {
						/* translators: LearnDash course title */
						$btn_text          = sprintf( esc_html__( 'Start %s', 'uncanny-pro-toolkit' ), class_exists( '\LearnDash_Custom_Label' ) ? \LearnDash_Custom_Label::get_label( 'course' ) : 'course' );
						$start_button_html = sprintf( '<a href="%s"><input type="submit" value="%s" class="" /></a>', $permalink, $btn_text );
						$start_button_html = apply_filters_deprecated(
							'uo-course-grid-start-button',
							array(
								$start_button_html,
								$course,
								$permalink,
							),
							'3.7.11',
							'uo_course_grid_start_button'
						);
						echo apply_filters( 'uo_course_grid_start_button', $start_button_html, $course, $permalink );
					}
					?>

					<?php
					if ( 'show' === $show_resume_button && $percentage > 0 && $percentage < 100 ) {
						$uo_active_classes = get_option( 'uncanny_toolkit_active_classes', 0 );
						if ( is_array( $uo_active_classes ) && ! empty( $uo_active_classes ) ) {
							if ( key_exists( 'uncanny_learndash_toolkit\LearnDashResume', $uo_active_classes ) ) {
								$resume_button_html = do_shortcode( '[uo_course_resume show_name="no" course_id="' . $course->ID . '"]' );
								$resume_button_html = apply_filters_deprecated(
									'uo-course-grid-resume-button',
									array(
										$resume_button_html,
										$course,
									),
									'3.7.11',
									'uo_course_grid_resume_button'
								);
								echo apply_filters( 'uo_course_grid_resume_button', $resume_button_html, $course );
							}
						}
					}
					?>
					<?php do_action_deprecated( 'uo-course-grid-after-action-buttons', array( $course ), '3.7.11', 'uo_course_grid_after_action_buttons' ); ?>
					<?php do_action( 'uo_course_grid_after_action_buttons', $course ); ?>
				</div>
				<?php
			}
		}
		?>
	</div>
</div>
