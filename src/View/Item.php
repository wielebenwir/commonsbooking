<?php

namespace CommonsBooking\View;

use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use Exception;
use WP_Post;

class Item extends View {

	/**
	 * Returns template data for frontend.
	 *
	 * @param WP_Post|null $post
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function getTemplateData( WP_Post $post = null ) {
		if ( $post == null ) {
			global $post;
		}
		$item      = $post;
		$location  = get_query_var( 'location' ) ?: false;
		$locations = \CommonsBooking\Repository\Location::getByItem( $item->ID, true );

		$args = [
			'post'      => $post,
			'wp_nonce'  => \CommonsBooking\Wordpress\CustomPostType\Booking::getWPNonceField(),
			'actionUrl' => admin_url( 'admin.php' ),
			'item'      => new \CommonsBooking\Model\Item( $item ),
			'postUrl'   => get_permalink( $item ),
			'type'      => Timeframe::BOOKING_ID,
		];

		// If there's no location selected, we'll show all available.
		if ( ! $location ) {
			if ( count( $locations ) ) {
				// If there's only one location  available, we'll show it directly.
				if ( count( $locations ) == 1 ) {
					$args['location'] = array_values( $locations )[0];
				} else {
					$args['locations'] = $locations;
				}
			} else {
				$args['locations'] = [];
			}
		} else {
			$args['location'] = new \CommonsBooking\Model\Location( get_post( $location ) );
		}

		$calendarData          = Calendar::getCalendarDataArray(
			$item,
			array_key_exists('location', $args) ? $args['location'] : null,
			date( 'Y-m-d', strtotime( 'first day of this month', time() ) ),
			date( 'Y-m-d', strtotime( '+3 months', time() ) )
		);
		$args['calendar_data'] = json_encode( $calendarData );

		return $args;
	}

	/**
	 * cb_items shortcode
	 *
	 * A list of items with timeframes.
	 *
	 * @param $atts
	 *
	 * @return false|string
	 * @throws Exception
	 */
	public static function shortcode( $atts ) {
		global $templateData;
		$templateData = [];
		$items        = [];
		$queryArgs    = shortcode_atts( static::$allowedShortCodeArgs, $atts, \CommonsBooking\Wordpress\CustomPostType\Item::getPostType() );

		if ( is_array( $atts ) && array_key_exists( 'location-id', $atts ) ) {
			$item    = \CommonsBooking\Repository\Item::getByLocation( $atts['location-id'] );
			$items[] = $item;
		} else {
			$items = \CommonsBooking\Repository\Item::get( $queryArgs );
		}

		$itemData = [];
		/** @var \CommonsBooking\Model\Item $item */
		foreach ( $items as $item ) {
			$shortCodeData = self::getShortcodeData( $item, 'Location' );

			// Sort by start_date
			foreach ($shortCodeData as $location) {
				uasort( $location['ranges'], function ( $a, $b ) {
					return $a['start_date'] <=> $b['start_date'];
				} );
			}

			$itemData[ $item->ID ] = $shortCodeData;
		}

		ob_start();
		foreach ( $itemData as $id => $data ) {
			$templateData['item'] = $id;
			$templateData['data'] = $data;
			commonsbooking_get_template_part( 'shortcode', 'items', true, false, false );
		}

		return ob_get_clean();
	}
}
