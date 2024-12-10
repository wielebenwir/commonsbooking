<?php

namespace CommonsBooking\Service;

use CommonsBooking\Messages\BookingCodesMessage;
use CommonsBooking\Model\BookingCode;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use WP_Query;
use DateTimeImmutable;

class BookingCodes {


	/**
	 * Send booking codes by E-mail.
	 * is triggered in  Service\Scheduler initHooks()
	 */
	public static function sendBookingCodesMessage(): void {
		$query = new WP_Query(
			array(
				'post_type'    => Timeframe::$postType,
				'meta_query' => array(
					array(
						'key'     => \CommonsBooking\View\BookingCodes::NEXT_CRON_EMAIL,
						'value'   => strtotime( 'today' ),
						'type'    => 'numeric',
						'compare' => '<=',
					),
				),
			)
		);

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$params = self::getCronParams( $post->ID );
				if ( $params === false ) {
					continue;
				}

				$booking_msg = new BookingCodesMessage( $post->ID, 'codes', $params['from'], $params['to'] );
				if ( ! $booking_msg->sendMessage() ) {
					set_transient(
						BookingCode::ERROR_TYPE,
						commonsbooking_sanitizeHTML(
							__( 'Error sending booking codes by E-mail for Timeframe ', 'commonsbooking' ) . get_the_title( $post ) . " ({$post->ID})"
						),
						0
					);
				} else {
					update_post_meta( $post->ID, \CommonsBooking\View\BookingCodes::NEXT_CRON_EMAIL, $params['nextCronEventTs'] );
				}
			}
		}
	}

	/**
	 * Retrieves Parameters of the next booking codes by Email Cron event.
	 *
	 * @param int $timeframeId
	 *
	 * @return array   Parameters.
	 */
	public static function getCronParams( $timeframeId ): array {
		$tsCurrentCronEvent = get_post_meta( $timeframeId, \CommonsBooking\View\BookingCodes::NEXT_CRON_EMAIL, true );
		if ( empty( $tsCurrentCronEvent ) ) {
			return false;
		}

		$cronEmailCodes = get_post_meta( $timeframeId, \CommonsBooking\View\BookingCodes::CRON_EMAIL_CODES, true );
		if ( ! is_numeric( $cronEmailCodes['cron-email-booking-code-nummonth'] ) || empty( @$cronEmailCodes['cron-booking-codes-enabled'] ) ) {
			return false;
		}

		$dtCurrentCronEvent = new DateTimeImmutable( '@' . $tsCurrentCronEvent );
		$dtFrom             = $dtCurrentCronEvent->modify( 'midnight first day of next month' );
		$dtTo               = $dtCurrentCronEvent->modify( 'midnight last day of next month +' . ( $cronEmailCodes['cron-email-booking-code-nummonth'] - 1 ) . ' month' );

		$dtInitial = new DateTimeImmutable( '@' . $cronEmailCodes['cron-email-booking-code-start'] );
		$daydiff   = $dtTo->format( 'j' ) - $dtInitial->format( 'j' );
		if ( $daydiff > 0 ) {
			$dtNextCronEvent = $dtTo->modify( '-' . $daydiff . ' days' );
		} else {
			$dtNextCronEvent = $dtTo;
		}

		return array(
			'from' => $dtFrom->getTimestamp(),
			'to' => $dtTo->getTimestamp(),
			'nextCronEventTs' => $dtNextCronEvent->getTimestamp(),
		);
	}
}
