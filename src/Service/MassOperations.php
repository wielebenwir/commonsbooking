<?php

namespace CommonsBooking\Service;

class MassOperations
{

	public static function ajaxMigrateOrphaned()
	{
		check_ajax_referer('cb_orphaned_booking_migration', 'nonce');

		if ( $_POST['data'] == 'false' ) {
			$post_data = 'false';
		} else {
			$post_data = isset( $_POST['data'] ) ? (array) $_POST['data'] : array();
			$post_data = commonsbooking_sanitizeArrayorString( $post_data );
			$post_data = array_map( 'intval', $post_data );
		}

		$result = self::migrateOrphaned($post_data);

		wp_send_json($result);
	}

	/**
	 * Will migrate an array of booking IDs to a new location
	 * The new location is determined by the location of the previous timeframe the booking was in
	 * If no timeframe is found, the booking will be skipped
	 *
	 * @param int[] $bookingIds
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function migrateOrphaned(array $bookingIds): array {
		$result = array(
			'success' => true,
			'message' => ''
		);

		if (empty($bookingIds)) {
			$result['success'] = false;
			$result['message'] = __('No bookings to move selected.','commonsbooking');
			return $result;
		}

		$orphanedBookings = \CommonsBooking\Repository\Booking::getOrphaned();
		//iterate over them and assign them new locations
		foreach ($orphanedBookings as $booking) {
			if ( !in_array($booking->ID(), $bookingIds) ) {
				continue;
			}
			try {
				$moveLocation = $booking->getMoveableLocation();
			} catch ( \Exception $e ) {
				$moveLocation = null;
			}
			if ($moveLocation !== null) {
				update_post_meta($booking->ID, 'location-id', $moveLocation->ID());
			}
			else {
				$result['message'] .= sprintf( __('New location not found for booking with ID %s','commonsbooking'), $booking->ID() ) ;
				$result['success'] = false;
			}
		}

		if ($result['success']) {
			$result['message'] = __('All selected orphaned bookings have been migrated.','commonsbooking');
		}

		return $result;
	}
}