<div class="wrap uo-install-automator">

	<div class="uo-install-automator__header">

		<h1>
			<?php esc_html_e( 'Take your site to the next level with Uncanny Automator', 'uncanny-ceu' ); ?>
		</h1>
		
		<p>

			<?php esc_html_e( 'Finding Uncanny Continuing Education Credits useful for your LearnDash site?', 'uncanny-ceu' ); ?></br>

			<?php esc_html_e( "You'll love", 'uncanny-ceu' ); ?> 

			<strong><?php esc_html_e( 'Uncanny Automator', 'uncanny-ceu' ); ?></strong>.

			<?php esc_html_e( 'Best of all,', 'uncanny-ceu' ); ?> 

			<strong>
				<?php esc_html_e( "it's free!", 'uncanny-ceu' ); ?>
			</strong>

		</p>

		<div class="uo-install-automator__box">

			<div class="uo-install-automator__logo">

				<img src="<?php echo esc_url( $this->get_image_url( 'uncanny-automator-icon.svg' ) ); ?>" alt="Uncanny Automator logo" />

			</div>

			<div class="uo-install-automator__text">

				<h3><?php esc_html_e( 'Uncanny Automator', 'uncanny-ceu' ); ?></h3>

				<span><?php esc_html_e( 'By the creators of Uncanny Toolkit', 'uncanny-ceu' ); ?></span>

				<span class="uo-install-automator__rating">
					<img src="<?php echo esc_url( $this->get_image_url( 'icon-star-yellow.svg' ) ); ?>" alt="Yellow star" />
					<img src="<?php echo esc_url( $this->get_image_url( 'icon-star-yellow.svg' ) ); ?>" alt="Yellow star" />
					<img src="<?php echo esc_url( $this->get_image_url( 'icon-star-yellow.svg' ) ); ?>" alt="Yellow star" />
					<img src="<?php echo esc_url( $this->get_image_url( 'icon-star-yellow.svg' ) ); ?>" alt="Yellow star" />
					<img src="<?php echo esc_url( $this->get_image_url( 'icon-star-yellow.svg' ) ); ?>" alt="Yellow star" />
				</span>

			</div>

			<div class="uo-install-automator__button">
					
				<?php echo $this->get_installer()->button( 'uncanny-automator', admin_url( 'admin.php?page=uncanny-automator-dashboard' ) ); ?>

			</div>

		</div>

	</div>

	<div class="uo-install-automator__body">

		<h2>
			<?php esc_html_e( 'How it works', 'uncanny-ceu' ); ?>
		</h2>

		<p>
			<?php echo sprintf( '%1$s - <strong>%2$s</strong>', esc_html__( 'Use Uncanny Automator to connect LearnDash and Uncanny Continuing Education Credits to your favourite plugins and apps', 'uncanny-ceu' ), esc_html__( 'without hiring a developer to write custom code.', 'uncanny-ceu' ) ); ?>
		</p>

		<p>
			<?php esc_html_e( 'Here are a few examples:', 'uncanny-ceu' ); ?>
		</p>
		
			<ul class="uo-install-automator__examples">
				<li>
					<?php esc_html_e( 'When users earns 10 credits, add a CRM tag.', 'uncanny-ceu' ); ?> 
					<div class="uo-install-automator__requirements">
						<label><?php esc_html_e( 'Requires', 'uncanny-ceu' ); ?></label>
						<ul>
							<li>
								<span class="uo-install-automator__icon">
									<img src="<?php echo esc_url( $this->get_image_url( 'uncanny-owl-icon.svg' ) ); ?>" alt="Uncanny Uncanny CEU icon" />
								</span> 
								Uncanny CEUs
							</li>
							<li>
								<span class="uo-install-automator__icon">
									<img src="<?php echo esc_url( $this->get_image_url( 'active-campaign.svg' ) ); ?>" alt="ActiveCampaign icon" />
								</span> 
								ActiveCampaign
							</li>
						</ul>
					</div>
				</li>
				<li>
					<?php esc_html_e( 'When users earn more than 50 credits, send them a coupon code for a new course.', 'uncanny-ceu' ); ?>
					<div class="uo-install-automator__requirements">
						<label><?php esc_html_e( 'Requires', 'uncanny-ceu' ); ?></label>
						<ul>
							<li>
								<span class="uo-install-automator__icon">
									<img src="<?php echo esc_url( $this->get_image_url( 'woocommerce-icon.svg' ) ); ?>" alt="WooCommerce icon" />
								</span> 
								WooCommerce
							</li>
							<li>
								<span class="uo-install-automator__icon">
									<img src="<?php echo esc_url( $this->get_image_url( 'uncanny-owl-icon.svg' ) ); ?>" alt="Uncanny Uncanny CEU icon" />
								</span> 
								Uncanny CEUs
							</li>
						</ul>
					</div>
				</li>
				<li>
					<?php esc_html_e( 'When users attend a live event, award credits that are included in a student’s transcript.', 'uncanny-ceu' ); ?>
					<div class="uo-install-automator__requirements">
						<label><?php esc_html_e( 'Requires', 'uncanny-ceu' ); ?></label>
						<ul>
							<li>
								<span class="uo-install-automator__icon">
									<img src="<?php echo esc_url( $this->get_image_url( 'the-events-calendar-icon.svg' ) ); ?>" alt="The Events Calendar icon" />
								</span> 
								The Events Calendar
							</li>
							<li>
								<span class="uo-install-automator__icon">
									<img src="<?php echo esc_url( $this->get_image_url( 'uncanny-owl-icon.svg' ) ); ?>" alt="Uncanny CEU" />
								</span> 
								Uncanny CEUs
							</li>
						</ul>
					</div>
				</li>
				<li>
					<?php esc_html_e( 'When users complete a course in another LMS, add credits to LearnDash.', 'uncanny-ceu' ); ?> 
					<div class="uo-install-automator__requirements">
						<label><?php esc_html_e( 'Requires', 'uncanny-ceu' ); ?></label>
						<ul>
							<li>
								<span class="uo-install-automator__icon">
									<img src="<?php echo esc_url( $this->get_image_url( 'webhooks-icon.svg' ) ); ?>" alt="Webhooks icon" />
								</span> 
								Webhooks
							</li>
							<li>
								<span class="uo-install-automator__icon">
									<img src="<?php echo esc_url( $this->get_image_url( 'uncanny-owl-icon.svg' ) ); ?>" alt="Uncanny CEU" />
								</span> 
								Uncanny CEUs
							</li>
						</ul>
					</div>
				</li>
			</ul>
	
			<div class="uo-install-automator__recipes">
				<div class="header">
					<strong><?php esc_html_e( 'Uncanny Automator', 'uncanny-ceu' ); ?></strong> <?php esc_html_e( 'supports', 'uncanny-ceu' ); ?> <a href="https://automatorplugin.com/integrations/?utm_source=uncanny_ceu&utm_medium=try_automator&utm_content=all_of_the_most_popular_plugins" target="_blank"> <?php esc_html_e( 'all of the most popular WordPress plugins', 'uncanny-ceu' ); ?> <span class="external-link"><img src="<?php echo esc_url( $this->get_image_url( 'icon-link-blue.svg' ) ); ?>" alt="External link icon" /></span></a> <?php esc_html_e( "and we're adding new integrations all the time. The possibilities are limitless.", 'uncanny-ceu' ); ?>
				</div>
				<div class="triggers">
					<div class="uo-recipe-simulator">
						<div class="uo-recipe-simulator__title"><?php esc_html_e( 'Choose any combination of triggers', 'uncanny-ceu' ); ?></div>
						<div class="uo-recipe-simulator__box">
							<div class="uo-recipe-simulator__items">
							<ul>
								<li><?php esc_html_e( 'Users complete a lesson', 'uncanny-ceu' ); ?></li>
								<li><?php esc_html_e( 'Users are added to a group', 'uncanny-ceu' ); ?></li>
								<li><?php esc_html_e( 'Users fill out a form', 'uncanny-ceu' ); ?></li>
								<li><?php esc_html_e( 'Users register for an event', 'uncanny-ceu' ); ?></li>
								<li><?php esc_html_e( 'Users buy a product', 'uncanny-ceu' ); ?></li>
								<li><?php esc_html_e( 'Users complete a course', 'uncanny-ceu' ); ?></li>
								<li><?php esc_html_e( 'Users fail a quiz', 'uncanny-ceu' ); ?></li>
							</ul>
							</div>
						</div>
					</div>
				</div>
				<div class="actions">
					<div class="uo-recipe-simulator">
						<div class="uo-recipe-simulator__title"><?php esc_html_e( '...to initiate any combination of actions', 'uncanny-ceu' ); ?></div>
						<div class="uo-recipe-simulator__box">
							<div class="uo-recipe-simulator__items">
							<ul>
								<li><?php esc_html_e( 'Add users to a group', 'uncanny-ceu' ); ?></li>
								<li><?php esc_html_e( 'Send an email', 'uncanny-ceu' ); ?></li>
								<li><?php esc_html_e( 'Mark a lesson complete', 'uncanny-ceu' ); ?></li>
								<li><?php esc_html_e( 'Unlock a new course', 'uncanny-ceu' ); ?></li>
								<li><?php esc_html_e( 'Reset course progress', 'uncanny-ceu' ); ?></li>
								<li><?php esc_html_e( 'Trigger a Zapier webhook', 'uncanny-ceu' ); ?></li>
								<li><?php esc_html_e( 'Add a tag in Infusionsoft', 'uncanny-ceu' ); ?></li>
							</ul>
							</div>
						</div>
					</div>
				</div>

				<script>

					// Global JS variable to init the JS
					window.hasRecipeSimulator = true;

				</script>

				<div class="robot">
					<span class="robot"><img src="<?php echo esc_url( $this->get_image_url( 'uncanny-automator-present-pose.svg' ) ); ?>" alt="External link icon" /></span>
				</div>

			</div>

			<p>
				<?php esc_html_e( 'Build better experiences for your users while saving money on custom development. And save', 'uncanny-ceu' ); ?>
				<strong><?php esc_html_e( 'your', 'uncanny-ceu' ); ?></strong>
				<?php esc_html_e( 'time by automating routine tasks — all with no code.', 'uncanny-ceu' ); ?>
			</p>

			<div class="uo-install-automator__box">
				<div class="uo-install-automator__logo">
					<img src="<?php echo esc_url( $this->get_image_url( 'uncanny-automator-icon.svg' ) ); ?>" alt="Uncanny Automator logo" />
				</div>
				<div class="uo-install-automator__text">
					<h3><?php esc_html_e( 'Uncanny Automator', 'uncanny-ceu' ); ?></h3>
					<span><?php esc_html_e( 'By the creators of Uncanny Toolkit', 'uncanny-ceu' ); ?></span>
					<span class="uo-install-automator__rating">
						<img src="<?php echo esc_url( $this->get_image_url( 'icon-star-yellow.svg' ) ); ?>" alt="Yellow star" />
						<img src="<?php echo esc_url( $this->get_image_url( 'icon-star-yellow.svg' ) ); ?>" alt="Yellow star" />
						<img src="<?php echo esc_url( $this->get_image_url( 'icon-star-yellow.svg' ) ); ?>" alt="Yellow star" />
						<img src="<?php echo esc_url( $this->get_image_url( 'icon-star-yellow.svg' ) ); ?>" alt="Yellow star" />
						<img src="<?php echo esc_url( $this->get_image_url( 'icon-star-yellow.svg' ) ); ?>" alt="Yellow star" />
				</div>
				<div class="uo-install-automator__button">
					
					<?php echo $this->get_installer()->button( 'uncanny-automator', admin_url( 'admin.php?page=uncanny-automator-dashboard' ) ); ?>

				</div>

			</div>

	</div>

</div>
