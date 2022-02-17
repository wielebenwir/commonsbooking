<?php

namespace CommonsBooking\View;

use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use Exception;
use WP_Post;

class Location extends View {

	/**
	 * Returns template data for frontend.
	 *
	 * @param WP_Post|null $post
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function getTemplateData( WP_Post $post = null ): array {
		if ( $post == null ) {
			global $post;
		}
		$location = $post;
		$item     = get_query_var( 'item' ) ?: false;
		$items    = \CommonsBooking\Repository\Item::getByLocation( $location->ID, true );
		$itemIds = array_map(
			function (\CommonsBooking\Model\Item $item) {
				return $item->getPost()->ID;
			},
			$items
		);

		$args = [
			'post'      => $post,
			'wp_nonce'  => \CommonsBooking\Wordpress\CustomPostType\Booking::getWPNonceField(),
			'actionUrl' => admin_url( 'admin.php' ),
			'location'  => new \CommonsBooking\Model\Location( $location ),
			'postUrl'   => get_permalink( $location ),
			'type'      => Timeframe::BOOKING_ID,
			'restrictions' => \CommonsBooking\Repository\Restriction::get(
				[$location->ID],
				$itemIds,
				null,
				true,
				time()
			)
		];

		// If there's no item selected, we'll show all available.
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

		$calendarData          = Calendar::getCalendarDataArray(
			array_key_exists('item', $args) ? $args['item'] : null,
			$location,
			date( 'Y-m-d', strtotime( Calendar::DEFAULT_RANGE_START, time() ) ),
			date( 'Y-m-d', strtotime( Calendar::DEFAULT_RANGE, time() ) )
		);
		$args['calendar_data'] = wp_json_encode( $calendarData );

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
			foreach ($shortCodeData as $item) {
				uasort( $item['ranges'], function ( $a, $b ) {
					return $a['start_date'] <=> $b['start_date'];
				} );
			}

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

	/**
	 * locationMap
	 *
	 * Renders map for location when checkbox is set
	 *
	 * @return void
	 */
	public static function renderLocationMap( \CommonsBooking\Model\Location $post = null ) {
		//renders map for location-calendar-header template, only renders when set as option
		if ( $post->getMeta( 'loc_showmap' ) ) {
			$latitude  = $post->getMeta( 'geo_latitude' );
			$longitude = $post->getMeta( 'geo_longitude' );
			wp_enqueue_style( 'cb_map_leaflet_css', COMMONSBOOKING_MAP_ASSETS_URL . 'leaflet/leaflet.css' );
			wp_enqueue_script( 'cb_map_leaflet_js', COMMONSBOOKING_MAP_ASSETS_URL . 'leaflet/leaflet-src.js' );
            

			echo '<div id="cb_locationview_map" style="width: 100%; height: 300px;"></div>';
            wp_enqueue_script( 'cb-map-locationview_js', COMMONSBOOKING_MAP_ASSETS_URL . 'js/cb-map-locationview.js' );

			//map defaults
			$defaults = [
				'latitude'  => $latitude,
				'longitude' => $longitude,
			];
			echo '<script>cb_map_locationview.defaults = ' . wp_json_encode( $defaults ) . ';</script>';
		}
	}
}
