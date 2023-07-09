<?php global $templateData; ?>
<h1>Mass Operations</h1>
<div class="wrap">
	<div id="cb_welcome-panel" class="cb_welcome-panel">
		<div class="cb_welcome-panel-content">
			<h2><?php
				echo esc_html__('Migrate orphaned bookings', 'commonsbooking') ;?>.</h2>
				<?php \CommonsBooking\View\MassOperations::renderBookingViewTable($templateData["orphanedBookings"]); ?>
		</div> <!-- .cb_welcome-panel-content -->
		<br>
		<div class="cb_orphan_migration">
			<?php \CommonsBooking\View\MassOperations::renderOrphanedMigrationButton() ?>
		</div>
	</div> <!-- .cb_welcome-panel -->
</div>