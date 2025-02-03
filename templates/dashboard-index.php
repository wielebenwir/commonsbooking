<h1>Dashboard</h1>
<!-- based on WordPress Dashboard --> 
<div class="wrap">
	<div id="cb_welcome-panel" class="cb_welcome-panel">
		<div class="cb_welcome-panel-content">
			<h2><?php

			echo esc_html__( 'Welcome to CommonsBooking', 'commonsbooking' );?>.</h2>
			<div class="cb_welcome-panel-column-container">
				<div class="cb_welcome-panel-column">
					<img src="<?php echo plugin_dir_url( __DIR__ ) . 'assets/global/cb-ci/logo.png'; ?>" style="width:200px">
				</div><!-- .cb_welcome-panel-column -->
				<div class="cb_welcome-panel-column">
				<p></p>
				</div><!-- .cb_welcome-panel-column -->
				<div class="cb_welcome-panel-column cb_welcome-panel-last">
					<h3><?php echo esc_html__( 'Support', 'commonsbooking' ); ?></h3>
					<ul>
						<li><a href="https://commonsbooking.org/dokumentation" target="_blank"><?php echo esc_html__( 'Documentation & Tutorials', 'commonsbooking' ); ?></a></li>		
						<li><a href="mailto:mail@commonsbooking.org?body=%0D%0A%0D%0A-----------%0D%0A%0D%0AInstallations-URL: <?php echo home_url(); ?>%0D%0A%0D%0ACB-Version: <?php echo commonsbooking_sanitizeHTML( COMMONSBOOKING_VERSION ); ?>" target="_blank"><?php echo esc_html__( 'Support E-Mail', 'commonsbooking' ); ?></a></li>
						<li><a href="https://commonsbooking.org/kontakt/" target="_blank"><?php echo __( 'Contact & Newsletter', 'commonsbooking' ); ?></a></li>
					</ul>
				<p>			<?php echo esc_html__( 'CommonsBooking Version', 'commonsbooking' ) . ' ' . commonsbooking_sanitizeHTML( COMMONSBOOKING_VERSION . ' ' . COMMONSBOOKING_VERSION_COMMENT ); ?></p>
				</div><!-- .cb_welcome-panel-column -->
			</div><!-- .cb_welcome-panel-column-container -->
			<div style="clear:both;">
			<hr style="border-top: 8px solid #bbb; border-radius: 5px; border-color:#67b32a;">
			</div>
			<div class="cb_welcome-panel-column-container" style="margin-top: 10px;">
				<div class="cb_welcome-panel-column">
					<h3 style="padding-bottom:20px"><?php echo esc_html__( 'Setup and manage Items, Locations and Timeframes', 'commonsbooking' ); ?></h3>
					<ul>
						<li><a href="edit.php?post_type=cb_item"><span class="dashicons dashicons-carrot"></span> <?php echo esc_html__( 'Items', 'commonsbooking' ); ?></a>
						</li>
						<li><a href="edit.php?post_type=cb_location"><span class="dashicons dashicons-store"></span> <?php echo esc_html__( 'Locations', 'commonsbooking' ); ?></a>
						</li>
						<li><a href="edit.php?post_type=cb_timeframe"><span class="dashicons dashicons-calendar-alt"></span> <?php echo esc_html__( 'Timeframes', 'commonsbooking' ); ?></a>
						</li>
					</ul>

				</div><!-- .cb_welcome-panel-column -->
				<div class="cb_welcome-panel-column">
					<h3 style="padding-bottom:20px"><?php echo esc_html__( 'See Bookings & manage restrictions', 'commonsbooking' ); ?></h3>
					<ul>
						<li><a href="edit.php?post_type=cb_booking"><span class="dashicons dashicons-list-view"></span> <?php echo esc_html__( 'Bookings', 'commonsbooking' ); ?></a>
						</li>
						<li><a href="edit.php?post_type=cb_restriction"><span class="dashicons dashicons-warning"></span> <?php echo esc_html__( 'Restrictions', 'commonsbooking' ); ?></a>
						</li>
					</ul>
				</div><!-- .cb_welcome-panel-column -->
				<div class="cb_welcome-panel-column cb_welcome-panel-last">
					<h3 style="padding-bottom:20px"><?php echo esc_html__( 'Configuration', 'commonsbooking' ); ?></h3>
					<ul>
					<?php if ( commonsbooking_isCurrentUserAdmin() ) { ?>
							<li><a href="edit.php?post_type=cb_map"><span class="dashicons dashicons-location-alt"></span> <?php echo esc_html__( 'Maps', 'commonsbooking' ); ?></a>
							</li>
							<li><a href="options-general.php?page=commonsbooking_options"><span class="dashicons dashicons-admin-settings"></span> <?php echo esc_html__( 'Settings', 'commonsbooking' ); ?></a>
							</li>
						<?php } ?>
					</ul>
				</div><!-- .cb_welcome-panel-column -->
			</div><!-- .cb_welcome-panel-column-container -->
		</div> <!-- .cb_welcome-panel-content -->
	</div> <!-- .cb_welcome-panel -->
	<div id="cb_welcome-panel" class="cb_welcome-panel">
		<div class="cb_welcome-panel-content">
			<div class="cb_welcome-panel-column-container">
				<div class="cb_welcome-panel-column" style="width: 50%;">
					<h3><?php echo esc_html__( "Today's pickups", 'commonsbooking' ); ?></h3>
					<?php
					// Display list of bookings with pickup date = today
					$BeginningBookings = CommonsBooking\View\Dashboard::renderBeginningBookings();
					if ( $BeginningBookings ) {
						echo commonsbooking_sanitizeHTML( $BeginningBookings );
					} else {
						echo esc_html__( 'No pickups today', 'commonsbooking' );
					}

					?>
				</div>
				<div class="cb_welcome-panel-column" style="width: 50%">
					<h3><?php echo esc_html__( "Today's returns", 'commonsbooking' ); ?></h3>
					<?php
					// Display list of bookings with return date = today
					$BeginningBookings = CommonsBooking\View\Dashboard::renderEndingBookings();
					if ( $BeginningBookings ) {
						echo commonsbooking_sanitizeHTML( $BeginningBookings );
					} else {
						echo esc_html__( 'No returns today', 'commonsbooking' );
					}

					?>
				</div>
			</div>

		</div>
	</div>
</div>