<h1>Dashboard</h1>
<!-- based on Wordpress Dashboard --> 
<div class="wrap">
	<div id="welcome-panel" class="welcome-panel">
		<div class="welcome-panel-content">
			<h2><?php echo __('Welcome to CommonsBooking', 'commonsbooking') ;?>.</h2>
			<div class="welcome-panel-column-container">
				<div class="welcome-panel-column">
					<img src="<?php echo plugin_dir_url( __DIR__  ).'assets/global/cb-ci/logo.png'; ?>" style="width:200px">
				</div><!-- .welcome-panel-column -->
				<div class="welcome-panel-column">
					<h3><?php echo __('Jump to...', 'commonsbooking') ;?>.</h3>
					<ul>
						<li><a href="edit.php?post_type=cb_item"><span class="dashicons dashicons-carrot"></span> <?php echo __('Items', 'commonsbooking') ;?></a></li>
						<li><a href="edit.php?post_type=cb_location"><span class="dashicons dashicons-store"></span> <?php echo __('Locations', 'commonsbooking') ;?></a></li>
						<li><a href="edit.php?post_type=cb_timeframe"><span class="dashicons dashicons-calendar-alt"></span> <?php echo __('Timeframes', 'commonsbooking') ;?></a></li>
						<li><a href="options-general.php?page=commonsbooking_options"><span class="dashicons dashicons-admin-settings"></span> <?php echo __('Settings', 'commonsbooking') ;?></a></li>
					</ul>
				</div><!-- .welcome-panel-column -->
				<div class="welcome-panel-column welcome-panel-last">
					<h3><?php echo __('Support', 'commonsbooking') ;?></h3>
					<ul>
						<li><a href="https://commonsbooking.org/dokumentation" target="_blank"><?php echo __('Documentation & Tutorials', 'commonsbooking') ;?></a></li>		
						<li><a href="https://commonsbooking.org/forums/forum/support/" target="_blank"><?php echo __('Support Forum', 'commonsbooking') ;?></a></li>
						<li><a href="https://commonsbooking.org/kontakt/" target="_blank"><?php echo __('Contact & Newsletter', 'commonsbooking') ;?></a></li>
					</ul>
				</div><!-- .welcome-panel-column -->
			</div><!-- .welcome-panel-column-container -->
		</div>
	</div>
</div>