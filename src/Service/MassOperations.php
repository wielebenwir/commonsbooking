<?php

namespace CommonsBooking\Service;

class MassOperations
{

	public static function ajaxMigrateOrphaned()
	{
		check_ajax_referer('cb_orphaned_booking_migration', 'nonce');

		$result = self::migrateOrphaned();

		wp_send_json($result);
	}

	public static function migrateOrphaned(): array {
		$result = array(
			'success' => true,
			'message' => ''
		);

		$orphanedBookings = \CommonsBooking\Repository\Booking::getOrphaned();
		//iterate over them and assign them new locations
		foreach ($orphanedBookings as $booking) {
			$moveLocation = $booking->getMoveableLocation();
			if ($moveLocation !== null) {
				update_post_meta($booking->ID, 'location-id', $moveLocation->ID());
			}
			else {
				$result['message'] .= sprintf( __('No location found for booking with ID %s','commonsbooking'), $booking->ID() ) . '<br>' ;
				$result['success'] = false;
			}
		}

		if ($result['success']) {
			$result['message'] = __('All orphaned bookings have been migrated.','commonsbooking');
		}

		return $result;
	}
}