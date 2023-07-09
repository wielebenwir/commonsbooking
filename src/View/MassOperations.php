<?php

namespace CommonsBooking\View;

class MassOperations
{
	public static function index() {
		global $templateData;
		$templateData = [];
		$templateData["orphanedBookings"] = \CommonsBooking\Repository\Booking::getOrphaned();

		ob_start();
		commonsbooking_sanitizeHTML( commonsbooking_get_template_part( 'massoperations', 'index' ) );
		echo ob_get_clean();
	}

	/**
	 * @param \CommonsBooking\Model\Booking[] $bookings
	 *
	 * @return void
	 */
	public static function renderBookingViewTable (array $bookings){

		if (empty($bookings)) {
			echo '<p>No bookings found.</p>';
			return;
		}

		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
			<tr>
				<th scope="col" class="manage-column column-cb_id">ID</th>
				<th scope="col" class="manage-column column-cb_user_nicename">User</th>
				<th scope="col" class="manage-column column-cb_item">Item name</th>
				<th scope="col" class="manage-column column-cb_start">Start-Date</th>
				<th scope="col" class="manage-column column-cb_end">End-Date</th>
				<th scope="col" class="manage-column column-cb_status">Status</th>
				<th scope="col" class="manage-column column-cb_location">Location name</th>
				<th scope="col" class="manage-column column-cb_new_location">New Location Name</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($bookings as $booking): ?>
				<?php
				try {
					$itemTitle = $booking->getItem()->post_title;
				} catch ( \Exception $e ) {
					$itemTitle = 'Item not found';
				}

				try {
					$locationTitle = $booking->getLocation()->post_title;
				} catch ( \Exception $e ) {
					$locationTitle = 'Location not found';
				}

				try {
					$newLocationTitle = $booking->getMoveableLocation()->post_title;
				} catch ( \Exception $e ) {
					$newLocationTitle = 'New Location not found';
				}

				?>
				<tr>
					<td class="manage-column column-cb_id"><?php echo $booking->ID; ?></td>
					<td class="manage-column column-cb_user_nicename"><?php echo $booking->getUserData()->user_nicename ?></td>
					<td class="manage-column column-cb_item"><?php echo $itemTitle ?></td>
					<td class="manage-column column-cb_start"><?php echo $booking->getFormattedStartDate() ?></td>
					<td class="manage-column column-cb_end"><?php echo $booking->getFormattedEndDate() ?></td>
					<td class="manage-column column-cb_status"><?php echo $booking->post_status ?></td>
					<td class="manage-column column-cb_location"><?php echo $locationTitle ?></td>
					<td class="manage-column column-cb_new_location"><?php echo $newLocationTitle ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	public static function renderOrphanedMigrationButton() {
		echo '
		<div class="cmb-row cmb-type-text">
			<div id="orphans-migration-in-progress">
				<strong style="color: red">
					' . esc_html__( 'migration in process .. please wait ...', 'commonsbooking' ) . '
				</strong>
			</div>
			<div id="orphans-migration-done">
				<strong style="color: green">
					' . esc_html__( 'Migration finished', 'commonsbooking' ) . '
				</strong>
			</div>
			<div id="orphans-migration-failed">
				<strong style="color: red">
					' . esc_html__( 'Migration failed', 'commonsbooking' ) . '
				</strong>
			</div>
			<a id="orphans-migration-start" class="button button-secondary" href="#">
				' . esc_html__( 'Migrate bookings', 'commonsbooking' ) . '
			</a>
		</div>';
	}

}