<?php

namespace CommonsBooking\View;

use CommonsBooking\Plugin;
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
		$item     = get_query_var( 'cb-item' ) ?: false;
		$customId = md5( $item . $location->ID );

		$cacheItem = Plugin::getCacheItem( $customId );
		if ( $cacheItem ) {
			return $cacheItem;
		} else {
			$items   = \CommonsBooking\Repository\Item::getByLocation( $location->ID, true );
			$itemIds = array_map(
				function ( \CommonsBooking\Model\Item $item ) {
					return $item->getPost()->ID;
				},
				$items
			);

			$args = [
				'post'      => $post,
				'actionUrl' => admin_url( 'admin.php' ),
				'location'  => new \CommonsBooking\Model\Location( $location ),
				'postUrl'   => get_permalink( $location ),
				'type'      => Timeframe::BOOKING_ID,
				'restrictions' => \CommonsBooking\Repository\Restriction::get(
					[ $location->ID ],
					$itemIds,
					null,
					true,
					current_time( 'timestamp' )
				),
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
				array_key_exists( 'item', $args ) ? $args['item'] : null,
				$location,
				date( 'Y-m-d', strtotime( Calendar::DEFAULT_RANGE_START, time() ) ),
				date( 'Y-m-d', strtotime( Calendar::DEFAULT_RANGE, time() ) )
			);
			$args['calendar_data'] = wp_json_encode( $calendarData );

			Plugin::setCacheItem( $args, [ 'misc' ], $customId );

			return $args;
		}
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
		$queryArgs    = shortcode_atts(
			static::$allowedShortCodeArgs,
			$atts,
			\CommonsBooking\Wordpress\CustomPostType\Location::getPostType()
		);

		if ( is_array( $atts ) && array_key_exists( 'item-id', $atts ) ) {
			$location    = \CommonsBooking\Repository\Location::getByItem( $atts['item-id'] );
			$locations[] = $location;
		} else {
			$locations = \CommonsBooking\Repository\Location::get( $queryArgs );
		}

		$locationData = [];
		/** @var \CommonsBooking\Model\Location $location */
		foreach ( $locations as $location ) {
			$locationData[ $location->ID ] = self::getShortcodeData( $location, 'Item' );
		}

		if ( $locationData ) {
			ob_start();
			foreach ( $locationData as $id => $data ) {
				$templateData['location'] = $id;
				$templateData['data']     = $data;
				commonsbooking_get_template_part( 'shortcode', 'locations', true, false, false );
			}
			return ob_get_clean();
		} else { // Message to show when no item matches query
			return '
			<div class="cb-wrapper cb-shortcode-locations template-shortcode-locations post-post no-post-thumbnail">
			<div class="cb-list-error">'
			. __( 'No locations found.', 'commonsbooking' ) .
			'</div>
			</div>
			';
		}
	}

	/**
	 * locationMap
	 *
	 * Renders map for location when checkbox is set
	 *
	 * @return void
	 */
	public static function renderLocationMap( \CommonsBooking\Model\Location $post = null ) {
		// renders map for location-calendar-header template, only renders when set as option
		if ( $post->getMeta( 'loc_showmap' ) ) {
			$latitude  = $post->getMeta( 'geo_latitude' );
			$longitude = $post->getMeta( 'geo_longitude' );
			wp_enqueue_style( 'cb-leaflet' );
			wp_enqueue_script( 'cb-leaflet' );

			echo '<div id="cb_locationview_map" style="width: 100%; height: 300px;"></div>';

			wp_enqueue_script( 'cb-map-locationview_js', COMMONSBOOKING_MAP_ASSETS_URL . 'js/cb-map-locationview.js', array(), false, true );

			// location geo-coordinates
			$defaults = [
				'latitude'  => $latitude,
				'longitude' => $longitude,
			];
			wp_add_inline_script( 'cb-map-locationview_js', 'cb_map_locationview.defaults =' . wp_json_encode( $defaults ) );
		}
	}
}
