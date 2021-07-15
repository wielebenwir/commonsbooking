<?php

namespace CommonsBooking\View;

use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use Exception;
use WP_Post;

class Location extends View {

	/**
	 * Returns json-formatted calendardata.
	 * @throws Exception
	 */
	public static function getCalendarData() {
		$jsonResponse = Calendar::getCalendarDataArray();

		header( 'Content-Type: application/json' );
		echo json_encode( $jsonResponse );
		wp_die(); // All ajax handlers die when finished
	}

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
		$location = $post;
		$item     = get_query_var( 'item' ) ?: false;
		$items    = \CommonsBooking\Repository\Item::getByLocation( $location->ID, true );

		$args = [
			'post'          => $post,
			'wp_nonce'      => Timeframe::getWPNonceField(),
			'actionUrl'     => admin_url( 'admin.php' ),
			'location'      => new \CommonsBooking\Model\Location( $location ),
			'postUrl'       => get_permalink( $location ),
			'type'          => Timeframe::BOOKING_ID,
			'calendar_data' => json_encode( Calendar::getCalendarDataArray( $item ?: null, $location ) )
		];

		// If theres no item selected, we'll show all available.
		if ( ! $item ) {
			if ( count( $items ) ) {
				// If there's only one item available, we'll show it directly.
				if ( count( $items ) == 1 ) {
					$args['item'] = array_values( $items )[0];
				} else {
					$args['items'] = $items;
				}
			}
		} else {
			$args['item'] = new \CommonsBooking\Model\Item( get_post( $item ) );
		}

		return $args;
	}

	/**
	 * cb_locations shortcode
	 *
	 * A list of locations with timeframes.
	 *
	 * @param $atts
	 *
	 * @return false|string
	 * @throws Exception
	 */
	public static function shortcode( $atts ) {
		global $templateData;
		$templateData = [];
		$locations    = [];
		$queryArgs    = shortcode_atts( static::$allowedShortCodeArgs, $atts,
			\CommonsBooking\Wordpress\CustomPostType\Location::getPostType() );

		if ( is_array( $atts ) && array_key_exists( 'item-id', $atts ) ) {
			$location    = \CommonsBooking\Repository\Location::getByItem( $atts['item-id'] );
			$locations[] = $location;
		} else {
			$locations = \CommonsBooking\Repository\Location::get( $queryArgs );
		}

		$locationData = [];
		/** @var \CommonsBooking\Model\Location $location */
		foreach ( $locations as $location ) {
			$shortCodeData = self::getShortcodeData( $location, 'Item' );

			// Sort by start_date
			uasort( $shortCodeData, function ( $a, $b ) {
				return $a['start_date'] > $b['start_date'];
			} );

			$locationData[ $location->ID ] = $shortCodeData;
		}

		ob_start();
		foreach ( $locationData as $id => $data ) {
			$templateData['location'] = $id;
			$templateData['data']     = $data;
			commonsbooking_get_template_part( 'shortcode', 'locations', true, false, false );
		}

		return ob_get_clean();
	}

	public static function content( \WP_Post $post ) {
		// TODO: Implement content() method.
	}
}
