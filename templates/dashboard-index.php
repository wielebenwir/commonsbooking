<h1>Dashboard</h1>
<!-- based on Wordpress Dashboard --> 
<div class="wrap">
	<div id="welcome-panel" class="welcome-panel">
		<div class="welcome-panel-content">
			<h2><?php

			echo __('Welcome to CommonsBooking', 'commonsbooking') ;?>.</h2>
			<div class="welcome-panel-column-container">
				<div class="welcome-panel-column">
					<img src="<?php echo plugin_dir_url( __DIR__  ).'assets/global/cb-ci/logo.png'; ?>" style="width:200px">
				</div><!-- .welcome-panel-column -->
				<div class="welcome-panel-column">
				<p></p>
				</div><!-- .welcome-panel-column -->
				<div class="welcome-panel-column welcome-panel-last">
					<h3><?php echo __('Support', 'commonsbooking') ;?></h3>
					<ul>
						<li><a href="https://commonsbooking.org/dokumentation" target="_blank"><?php echo __('Documentation & Tutorials', 'commonsbooking') ;?></a></li>		
						<li><a href="mailto:mail@commonsbooking.org?body=%0D%0A%0D%0A-----------%0D%0A%0D%0AInstallations-URL: <?php echo home_url(); ?>%0D%0A%0D%0ACB-Version: <?php echo COMMONSBOOKING_VERSION; ?>" target="_blank"><?php echo __('Support E-Mail', 'commonsbooking') ;?></a></li>
						<li><a href="https://commonsbooking.org/kontakt/" target="_blank"><?php echo __('Contact & Newsletter', 'commonsbooking') ;?></a></li>
					</ul>
				<p>			<?php echo __('CommonsBooking Version', 'commonsbooking') . ' ' . COMMONSBOOKING_VERSION; ?></p>
				</div><!-- .welcome-panel-column -->
			</div><!-- .welcome-panel-column-container -->
			<div 
			<div style="clear:both;">
			<hr style="border-top: 8px solid #bbb; border-radius: 5px; border-color:#67b32a;">
			</div>
			<div class="welcome-panel-column-container" style="margin-top: 10px;">
				<div class="welcome-panel-column">
					<h3 style="padding-bottom:20px"><?php echo __('Setup and manage Items, Locations and Timeframes', 'commonsbooking') ;?></h3>
					<ul>
						<li><a href="edit.php?post_type=cb_item"><span class="dashicons dashicons-carrot"></span> <?php echo __('Items', 'commonsbooking') ;?></a>
							<ul>
								<li>dfksdf södklfnskdjfnskldjf sdkfjsn dfklsdfknsndfsldf sdfkjs ndf</li>
							</ul>
						</li>
						<li><a href="edit.php?post_type=cb_location"><span class="dashicons dashicons-store"></span> <?php echo __('Locations', 'commonsbooking') ;?></a>
							<ul>
								<li>dfksdf södklfnskdjfnskldjf sdkfjsn dfklsdfknsndfsldf sdfkjs ndf</li>
							</ul>
						</li>
						<li><a href="edit.php?post_type=cb_timeframe"><span class="dashicons dashicons-calendar-alt"></span> <?php echo __('Timeframes', 'commonsbooking') ;?></a></li>
					</ul>
	
				</div><!-- .welcome-panel-column -->
				<div class="welcome-panel-column">
					<h3 style="padding-bottom:20px"><?php echo __('See Bookings & manage restrictions', 'commonsbooking') ;?></h3>
					<ul>
						<li><a href="edit.php?post_type=cb_booking"><span class="dashicons dashicons-list-view"></span> <?php echo __('Bookings', 'commonsbooking') ;?></a></li>
						<li><a href="edit.php?post_type=cb_restriction"><span class="dashicons dashicons-warning"></span> <?php echo __('Restrictions', 'commonsbooking') ;?></a></li>

					</ul>
				</div><!-- .welcome-panel-column -->
				<div class="welcome-panel-column welcome-panel-last">
					<h3 style="padding-bottom:20px"><?php echo __('Configuration', 'commonsbooking') ;?></h3>
				    <ul>
					<?php if (commonsbooking_isCurrentUserAdmin()) { ?>
                            <li><a href="edit.php?post_type=cb_map"><span class="dashicons dashicons-location-alt"></span> <?php echo __('Maps', 'commonsbooking') ;?></a></li>
                            <li><a href="options-general.php?page=commonsbooking_options"><span class="dashicons dashicons-admin-settings"></span> <?php echo __('Settings', 'commonsbooking') ;?></a></li>
                        <?php } ?>
					</ul>
				</div><!-- .welcome-panel-column -->
			</div><!-- .welcome-panel-column-container -->

			<div class="welcome-panel-column-container">
				<div class="welcome-panel-column" style="width: 50%">
					<h3><?php echo __('Beginning Bookings', 'commonsbooking') ;?></h3>
					<?php \CommonsBooking\View\Dashboard::renderBeginningBookings();?>
				</div>
				<div class="welcome-panel-column" style="width: 50%">
					<h3><?php echo __('Ending Bookings', 'commonsbooking') ;?></h3>
					<?php \CommonsBooking\View\Dashboard::renderEndingBookings();?>
				</div>
			</div>

		</div>
	</div>
</div>

<?php
ob_start();
include(COMMONSBOOKING_PLUGIN_DIR . 'templates/dashboard-bookings.php');
ob_end_clean();
?>