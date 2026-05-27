<?php
add_filter( 'wp_robots', function( $robots ){
	$robots['noindex'] = true;
	$robots['nofollow'] = true;
	return $robots;
}, 99);

get_header();
$ulgm_group_slug = sanitize_text_field( wp_unslash( get_query_var( 'ulgm_group_slug' ) ) );
$args            = array(
	'name'           => $ulgm_group_slug,
	'post_type'      => 'groups',
	'post_status'    => 'publish',
	'no_found_rows'  => true,
	'posts_per_page' => 1
);

$group_query = new WP_Query( $args );
$group_key = '';
?>
	<style>
		.post-pagination-wrap,
		.post-pagination,
		.post-prev,
		.post-next {
			display: none !important;
		}
	</style>
	<div id="main-content">
		<div id="group-main" class="page type-page status-publish hentry">
			<div class="entry-content">
				<div id="left-area" class="content-area clr">
					<main id="content" class="site-content clr" role="main">
					<?php
					if ( $group_query->have_posts() ) :
						while ( $group_query->have_posts() ) :
							$group_query->the_post();
							$group_post_id = get_the_ID();
							$group_post    = get_post( $group_post_id );
		
							// lets check for key
							$group_key = crypt( $group_post->ID, 'uncanny-group' );
							//Fixing $_GET string from . (dot) & space to _ ( underscore )
							$group_key = str_replace( array( ' ', '.', '[', '-' ), '_', $group_key );

							if ( ! isset( $_GET[ $group_key ] ) ) {
								?>
								<p><?php echo esc_html__( 'This page can only be used by organizations with a valid group ID. The URL used to reach this page is not valid. Please contact your organization to obtain the correct registration URL.', 'uncanny-pro-toolkit' ); ?></p>
								<?php
								if ( current_user_can( 'manage_options' ) ) {

									$ulgm_admin_signup_link = add_query_arg(
											array(
												'gid' => $group_post_id,
											),
											site_url( 'sign-up/' . $group_post->post_name . '/' )
										) . '&' . $group_key;

									printf(
										'<h2>' . esc_attr__( 'Shown to admins only.', 'uncanny-pro-toolkit' ) . '</h2>' . '<p>' . esc_attr__( 'The sign up link for this group is:', 'uncanny-pro-toolkit' ) . ' <br /><a href="%1$s" >%1$s</a></p>',
										apply_filters( 'ulgm_admin_signup_link', $ulgm_admin_signup_link, $group_post, $group_key )
									);
								}
							} else {
								?>
								<article <?php post_class(); ?>>
									<?php
									if ( ! is_user_logged_in() ) {
										if ( ! isset( $_REQUEST['registered'] ) ) {

											$show_content = true;
											if ( class_exists( '\uncanny_learndash_groups\SharedFunctions' ) ) {
												$code_group_id = get_post_meta( $group_post_id, '_ulgm_code_group_id', true );

												if ( $code_group_id ) {
													$remaining_seats = \uncanny_learndash_groups\SharedFunctions::remaining_seats( $group_post_id );

													if ( 0 === $remaining_seats ) {
														echo '<div class="uncanny_group_signup_form-container">';
														echo esc_attr__( 'Sorry, no more seats are available for this group.', 'uncanny-pro-toolkit' );
														echo '</div>';

														$show_content = false;
													}
												}
											}

											if ( $show_content ) {
												echo do_shortcode( do_blocks( $group_post->post_content ) );
											}

											$is_gravityform_block = false;
											if ( function_exists( 'has_blocks' ) ) {
												if ( has_blocks( $group_post->post_content ) ) {
													$blocks = parse_blocks( $group_post->post_content );
													foreach ( $blocks as $block ) {
														if ( $block['blockName'] === 'gravityforms/form' ) {
															$is_gravityform_block = true;
															break;
														}
													}
												}
											}
											if ( ! has_shortcode( $group_post->post_content, 'gravityform' ) && ! has_shortcode( $group_post->post_content, 'theme-my-login' ) && $is_gravityform_block === false ) {
												if ( $show_content ) {
													\uncanny_pro_toolkit\LearnDashGroupSignUp::groups_register_form();
												}
											}
										} else {
											?>
											<?php
											$frontEndLogin = \uncanny_learndash_toolkit\Config::get_settings_value( 'uo_frontendloginplus_needs_verifcation', 'FrontendLoginPlus' );
											if ( ! empty( $frontEndLogin ) && 'on' === $frontEndLogin ) {
												?>
												<p><?php echo esc_html__( 'Thank you for registering. Your account needs to be approved by site administrator.', 'uncanny-pro-toolkit' ); ?></p>
											<?php } else { ?>
												<p><?php echo esc_html__( 'Congratulations! You are now registered on this site. You will receive an email shortly with login details.', 'uncanny-pro-toolkit' ); ?></p>
											<?php } ?>
											<?php
										}
									} elseif ( is_user_logged_in() && isset( $_REQUEST['registered'] ) ) {
										?>
										<p><?php echo wp_kses_post( apply_filters( 'uo_ld_group_signup_registered_message',
											esc_html__( 'Congratulations! You are now registered on this site.', 'uncanny-pro-toolkit' ), 
											$group_post_id
											) ); ?></p>
										<?php
									} elseif ( is_user_logged_in() && isset( $_REQUEST['joined'] ) ) {
										?>
										<p>
											<?php
											if ( isset( $_REQUEST['msg'] ) && $_REQUEST['msg'] === '2' ) {
												echo wp_kses_post( apply_filters( 'uo_ld_group_signup_joined_remove_previous_group_message',
													esc_html__( 'Congratulations! You have successfully joined the new group and have been removed from the previous group.', 'uncanny-pro-toolkit' ), 
													$group_post_id
												) );
											} else {
												echo wp_kses_post( apply_filters( 'uo_ld_group_signup_joined_message',
													esc_html__( 'Congratulations! You are now a member of this group.', 'uncanny-pro-toolkit' ), 
													$group_post_id
												) );
											}
											?>
										</p>
										<?php
									} else {
										$show_content = true;
										$user_id      = get_current_user_id();

										$meta = get_user_meta( $user_id, 'learndash_group_users_' . $group_post_id, true );
										if ( ! empty( $meta ) ) {
											echo '<div class="uncanny_group_signup_form-container">';
											echo esc_attr__( 'You are already in this group.', 'uncanny-pro-toolkit' );
											echo '</div>';

											$show_content = false;
										}

										if ( class_exists( '\uncanny_learndash_groups\SharedFunctions' ) && $show_content ) {
											$code_group_id = get_post_meta( $group_post_id, '_ulgm_code_group_id', true );

											if ( $code_group_id ) {
												$remaining_seats = \uncanny_learndash_groups\SharedFunctions::remaining_seats( $group_post_id );

												if ( 0 === $remaining_seats ) {
													echo '<div class="uncanny_group_signup_form-container">';
													echo esc_attr__( 'Sorry, no more seats are available for this group.', 'uncanny-pro-toolkit' );
													echo '</div>';

													$show_content = false;
												}
											}
										}

										if ( $show_content ) {
											echo \uncanny_pro_toolkit\LearnDashGroupSignUp::groups_login_form( 'single-group' );
											echo \uncanny_pro_toolkit\LearnDashGroupSignUp::check_group_membership();
										}
									}
									?>
								</article><!-- .entry -->
								<?php
							}

						wp_reset_postdata();

						endwhile;
					else:
						?>
						<p><?php echo esc_html__( 'Page not found.', 'uncanny-pro-toolkit' ); ?></p>
						<?php
					endif; 
						?>
					</main>
				</div>
				<div id="right-area" class="sidebar">
					<?php
					if ( isset( $_GET[ $group_key ] ) ) {
						echo do_shortcode( '[uo_group_organization]' );
					}
					if ( ! is_user_logged_in() ) {
						echo do_shortcode( '[uo_group_login]' );
					}
					?>
				</div>
			</div>
		</div><!-- .container -->
	</div>
<?php
get_footer();
