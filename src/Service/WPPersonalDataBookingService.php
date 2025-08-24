<?php

namespace CommonsBooking\Service;

use CommonsBooking\Helper\Helper;

/**
 * WP specific implementations for personal data export and deletion
 */
class WPPersonalDataBookingService {

	/**
	 * Export user bookings using the supplied email. This is for integration with the WordPress personal data exporter.
	 *
	 * @param string $emailAddress
	 * @param $page
	 *
	 * @return array
	 */
	public static function exportUserBookingsByEmail( string $emailAddress, $page = 1 ): array {
		$page         = intval( $page );
		$itemsPerPage = 10;
		$exportItems  = array();
		// The internal group ID used by WordPress to group the data exported by this exporter.
		$groupID    = 'bookings';
		$groupLabel = __( 'CommonsBooking Bookings', 'commonsbooking' );

		$user = get_user_by( 'email', $emailAddress );
		if ( ! $user ) {
			return array(
				'data' => $exportItems,
				'done' => true,
			);
		}
		$bookings = \CommonsBooking\Repository\Booking::getForUserPaginated( $user, $page, $itemsPerPage );
		if ( ! $bookings ) {
			return array(
				'data' => $exportItems,
				'done' => true,
			);
		}
		foreach ( $bookings as $booking ) {
			$bookingID = $booking->ID;
			// exclude bookings that the user is eligible to see but are not their own
			// we are only concerned about one user's personal data
			if ( $booking->getUserData()->user_email !== $emailAddress ) {
				continue;
			}
			$bookingData = [
				[
					'name'  => __( 'Booking start', 'commonsbooking' ),
					'value' => $booking->pickupDatetime(),
				],
				[
					'name'  => __( 'Booking end', 'commonsbooking' ),
					'value' => $booking->returnDatetime(),
				],
				[
					'name'  => __( 'Time of booking', 'commonsbooking' ),
					'value' => Helper::FormattedDateTime( get_post_timestamp( $bookingID ) ),
				],
				[
					'name'  => __( 'Status', 'commonsbooking' ),
					'value' => $booking->getStatus(),
				],
				[
					'name'  => __( 'Booking code', 'commonsbooking' ),
					'value' => $booking->getBookingCode(),
				],
				[
					'name'  => __( 'Comment', 'commonsbooking' ),
					'value' => $booking->returnComment(),
				],
				[
					'name'  => __( 'Location', 'commonsbooking' ),
					'value' => $booking->getLocation()->post_title,
				],
				[
					'name'  => __( 'Item', 'commonsbooking' ),
					'value' => $booking->getItem()->post_title,
				],
				[
					'name'  => __( 'Time of cancellation', 'commonsbooking' ),
					'value' => $booking->getMeta( 'cancellation_time' ) ? Helper::FormattedDateTime( $booking->getMeta( 'cancellation_time' ) ) : '',
				],
				[
					'name'  => __( 'Admin booking by', 'commonsbooking' ),
					'value' => $booking->getMeta( 'admin_booking_id' ) ? get_user_by( 'id', $booking->getMeta( 'admin_booking_id' ) )->display_name : '',
				],
			];

			$exportItems[] = [
				'group_id'    => $groupID,
				'group_label' => $groupLabel,
				'item_id'     => $bookingID,
				'data'        => $bookingData,
			];
		}
		$done = count( $bookings ) < $itemsPerPage;
		return array(
			'data' => $exportItems,
			'done' => $done,
		);
	}

	/**
	 * Remove user bookings using the supplied email. This is for integration with the WordPress personal data eraser.
	 *
	 * @param string $emailAddress The email address
	 * @param $page This parameter has no real use in this function, we just use it to stick to WordPress expected parameters.
	 *
	 * @return array
	 */
	public static function removeUserBookingsByEmail( string $emailAddress, $page = 1 ): array {
		// we reset the page to 1, because we are deleting our results as we go. Therefore, increasing the page number would skip some results.
		$page         = 1;
		$itemsPerPage = 10;
		$removedItems = false;

		$user = get_user_by( 'email', $emailAddress );
		if ( ! $user ) {
			return array(
				'items_removed'  => $removedItems,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}
		$bookings = \CommonsBooking\Repository\Booking::getForUserPaginated( $user, $page, $itemsPerPage );
		if ( ! $bookings ) {
			return array(
				'items_removed'  => $removedItems,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}
		foreach ( $bookings as $booking ) {
			$bookingID = $booking->ID;
			// exclude bookings that the user is eligible to see but are not their own
			// we are only concerned about one user's personal data
			if ( $booking->getUserData()->user_email !== $emailAddress ) {
				continue;
			}
			// Cancel the booking before deletion so that status change emails are sent
			$booking->cancel();
			// Delete the booking
			wp_delete_post( $bookingID, true );
			$removedItems = true;
		}

		$done = count( $bookings ) < $itemsPerPage;
		return array(
			'items_removed'  => $removedItems,
			'items_retained' => false, // always false, we don't retain any data
			'messages'       => array(),
			'done'           => $done,
		);
	}
}
