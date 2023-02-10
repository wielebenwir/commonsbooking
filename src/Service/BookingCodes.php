<?php

namespace CommonsBooking\Service;

use CommonsBooking\Messages\BookingCodesMessage;
use CommonsBooking\Model\BookingCode;

use WP_Query;

class BookingCodes {


	/**
	 * Send Booking Codes by E-mail.
     * is triggered in  Service\Scheduler initHooks()
	 * 
	 */
	public static function sendBookingCodesMessage() {
		$query = new WP_Query( [
			'post_type'    => 'cb_timeframe',
			'meta_query' => array(
					array(
						'key'     => \CommonsBooking\View\BookingCodes::$nextCronEmailID,
						'value'   => strtotime("today"),
						'type'    => 'numeric',
						'compare' => '<='
					),
				),
		  ] );

		if($query->have_posts()){
			foreach( $query->posts as $post ) {
				$params=\CommonsBooking\View\BookingCodes::getCodesChunkParams($post->ID);
				if($params === false) continue;

				$booking_msg = new BookingCodesMessage($post->ID, "codes",$params['from'],$params['to'] );
				if(!$booking_msg->sendMessage()) {
					set_transient(
						BookingCode::ERROR_TYPE,
						commonsbooking_sanitizeHTML(
							__( "Error sending Booking Codes by E-mail for Timeframe ", 'commonsbooking' ) . get_the_title($post) . " ({$post->ID})"
						),
						0
					);
				}
				else {
					update_post_meta( $post->ID, \CommonsBooking\View\BookingCodes::$nextCronEmailID, $params['nextCronEventTs'] ); 
				}

			}	
		}
	}

}