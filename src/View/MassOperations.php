<?php

namespace CommonsBooking\View;

class MassOperations {
	public static function index() {
		global $templateData;
		$templateData                     = [];
		$templateData['orphanedBookings'] = \CommonsBooking\Repository\Booking::getOrphaned();

		ob_start();
		commonsbooking_sanitizeHTML( commonsbooking_get_template_part( 'massoperations', 'index' ) );
		echo ob_get_clean();
	}

	/**
	 * @param \CommonsBooking\Model\Booking[] $bookings
	 *
	 * @return void
	 */
	public static function renderBookingViewTable( array $bookings ) {

		if ( empty( $bookings ) ) {
			echo '<p>' . esc_html__( 'No bookings found.' ) . '</p>';

			return;
		}

		$tableString = '
		<table class="wp-list-table widefat fixed striped">
			<thead>
			<tr>
				<th scope="col" class="manage-column column-cb check-column">
					<label class="screen-reader-text" for="cb-select-all-1">' . esc_html__( 'Select All', 'commonsbooking' ) . '</label>
					<input type="checkbox" id="cb-select-all-1">
				</th>
				<th scope="col" class="manage-column column-title column-primary" id="id">' . esc_html__( 'ID', 'commonsbooking' ) . '</th>
				<th scope="col" class="manage-column column-title column-primary" id="user">' . esc_html__( 'User', 'commonsbooking' ) . '</th>
				<th scope="col" class="manage-column column-title column-primary" id="item-name">' . esc_html__( 'Item name', 'commonsbooking' ) . '
				<th scope="col" class="manage-column column-title column-primary" id="date-start">' . esc_html__( 'Start-date', 'commonsbooking' ) . '</th>
				<th scope="col" class="manage-column column-title column-primary" id="date-end">' . esc_html__( 'End-date', 'commonsbooking' ) . '</th>
				<th scope="col" class="manage-column column-title column-primary" id="status">' . esc_html__( 'Status', 'commonsbooking' ) . '</th>
				<th scope="col" class="manage-column column-title column-primary" id="location-name">' . esc_html__( 'Location name', 'commonsbooking' ) . '</th>
				<th scope="col" class="manage-column column-title column-primary" id="new-location-name">' . esc_html__( 'New location name', 'commonsbooking' ) . '</th>
			</tr>
			</thead>
			<tbody> ';
		foreach ( $bookings as $booking ) :
			try {
				$itemTitle = $booking->getItem()->post_title;
			} catch ( \Exception $e ) {
				$itemTitle = esc_html__( 'Item not found' );
			}

			try {
				$locationTitle = $booking->getLocation()->post_title;
			} catch ( \Exception $e ) {
				$locationTitle = esc_html__( 'Location not found' );
			}

			try {
				$newLocationTitle = $booking->getMoveableLocation()->post_title;
			} catch ( \Exception $e ) {
				$newLocationTitle = esc_html__( 'New Location not found' );
			}

			$tableString .= '
				<tr id="row-booking-' . $booking->ID . '">
					<th scope="row" class="check-column">
						<label class="screen-reader-text" for="cb-select-booking-' . $booking->ID . '">Select booking ' . $booking->ID . '</label>
						<input type="checkbox" id="cb-select-booking-' . $booking->ID . '" class="post-checkboxes" value="' . $booking->ID . '">
					</th>
					<td class="manage-column column-cb_id">' . $booking->ID . '</td>
					<td class="manage-column column-cb_user_nicename">' . $booking->getUserData()->user_nicename . '</td>
					<td class="manage-column column-cb_item">' . $itemTitle . '</td>
					<td class="manage-column column-cb_start">' . $booking->getFormattedStartDate() . '</td>
					<td class="manage-column column-cb_end">' . $booking->getFormattedEndDate() . '</td>
					<td class="manage-column column-cb_status">' . $booking->post_status . '</td>
					<td class="manage-column column-cb_location">' . $locationTitle . '</td>
					<td class="manage-column column-cb_new_location">' . $newLocationTitle . '</td>
				</tr>';
		endforeach;
		$tableString .= '
			</tbody>
		</table>
		';
		echo $tableString;
	}

	public static function renderOrphanedMigrationButton() {
		echo '
		<div class="cmb-row cmb-type-text">
			<div id="orphans-migration-in-progress">
				<strong style="color: red">
					<span>' . esc_html__( 'migration in process .. please wait ...', 'commonsbooking' ) . '</span>
				</strong>
			</div>
			<div id="orphans-migration-done">
				<strong style="color: green">
					<span>' . esc_html__( 'Migration finished', 'commonsbooking' ) . '</span>
				</strong>
			</div>
			<div id="orphans-migration-failed">
				<strong style="color: red">
					<span>' . esc_html__( 'Migration failed', 'commonsbooking' ) . '</span>
				</strong>
			</div>
			<a id="orphans-migration-start" class="button button-secondary" href="#">
				<span>
					' . esc_html__( 'Migrate bookings', 'commonsbooking' ) . '
				</span>
			</a>
		</div>';
	}
}
