<?php

namespace CommonsBooking\View;

use CommonsBooking\Plugin;
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
		$item     = $post;
		$location = get_query_var( 'cb-location' ) ?: false;
		$customId = md5( $item->ID . $location );

		$cacheItem = Plugin::getCacheItem( $customId );
		if ( $cacheItem ) {
			return $cacheItem;
		} else {
			$locations   = \CommonsBooking\Repository\Location::getByItem( $item->ID, true );
			$locationIds = array_map(
				function ( \CommonsBooking\Model\Location $location ) {
					return $location->getPost()->ID;
				},
				$locations
			);

			$args = [
				'post'      => $post,
				'actionUrl' => admin_url( 'admin.php' ),
				'item'      => new \CommonsBooking\Model\Item( $item ),
				'postUrl'   => get_permalink( $item ),
				'type'      => Timeframe::BOOKING_ID,
				'restrictions' => \CommonsBooking\Repository\Restriction::get(
					$locationIds,
					[ $item->ID ],
					null,
					true,
					time()
				),
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

			$calendarData                           = Calendar::getCalendarDataArray(
				$item,
				array_key_exists( 'location', $args ) ? $args['location'] : null,
				date( 'Y-m-d', strtotime( 'first day of this month', time() ) ),
				date( 'Y-m-d', strtotime( '+3 months', time() ) )
			);
			$calendarData['i18n.buttonText.apply']  = __( 'Book', 'commonsbooking' );
			$calendarData['i18n.buttonText.cancel'] = __( 'Cancel', 'commonsbooking' );
			$args['calendar_data']                  = wp_json_encode( $calendarData );

			Plugin::setCacheItem( $args, [ 'misc' ], $customId );

			return $args;
		}
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
		$queryArgs    = shortcode_atts( static::$allowedShortCodeArgs, $atts, \CommonsBooking\Wordpress\CustomPostType\Item::getPostType() );

		if ( is_array( $atts ) && array_key_exists( 'location-id', $atts ) ) {
			$items = \CommonsBooking\Repository\Item::getByLocation( $atts['location-id'] );
		} else {
			$items = \CommonsBooking\Repository\Item::get( $queryArgs );
		}

		$itemData = [];
		/** @var \CommonsBooking\Model\Item $item */
		foreach ( $items as $item ) {
			$itemData[ $item->ID ] = self::getShortcodeData( $item, 'Location' );
		}

		if ( $itemData ) {
			ob_start();
			foreach ( $itemData as $id => $data ) {
				$templateData['item'] = $id;
				$templateData['data'] = $data;
				commonsbooking_get_template_part( 'shortcode', 'items', true, false, false );
			}
			return ob_get_clean();
		} else { // Message to show when no item matches query
			return '<div class="cb-wrapper cb-shortcode-items template-shortcode-items post-post no-post-thumbnail">
			<div class="cb-list-error">'
			. __( 'No bookable items found.', 'commonsbooking' ) .
			'</div>
			</div>';
		}
	}
}
