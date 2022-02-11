<?php

namespace CommonsBooking\Repository;

use CommonsBooking\Plugin;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use Exception;
use WP_Post;
use WP_Query;

class Location extends BookablePost {

	/**
	 * Returns array with locations for item.
	 *
	 * @param $itemId
	 *
	 * @param bool $bookable
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function getByItem( $itemId, bool $bookable = false ): array {
		if ( $itemId instanceof WP_Post ) {
			$itemId = $itemId->ID;
		}

		if ( Plugin::getCacheItem() ) {
			return Plugin::getCacheItem();
		} else {
			$locations   = [];
			$locationIds = [];
			$args        = array(
				'post_type'   => Timeframe::$postType,
				'post_status' => array( 'confirmed', 'unconfirmed', 'publish', 'inherit' ),
				'meta_query'  => array(
					'relation' => 'AND',
					array(
						'key'   => 'item-id',
						'value' => $itemId,
					),
				),
				'nopaging'    => true,
			);

			$query = new WP_Query( $args );
			if ( $query->have_posts() ) {
				$timeframes = $query->get_posts();
				foreach ( $timeframes as $timeframe ) {
					$locationId = get_post_meta( $timeframe->ID, 'location-id', true );
					if ( $locationId && ! in_array( $locationId, $locationIds ) ) {
						$locationIds[] = $locationId;
						$location      = get_post( $locationId );

						if ( $location ) {
							// add only published items
							if ( $location->post_status == 'publish' ) {
								$locations[] = $location;
							}
						}
					}
				}
			}

			foreach ( $locations as $key => &$location ) {
				$location = new \CommonsBooking\Model\Location( $location );
				if ( $bookable && ! $location->getBookableTimeframesByItem( $itemId ) ) {
					unset( $locations[ $key ] );
				}
			}
			Plugin::setCacheItem( $locations );

			return $locations;
		}
	}

	/**
	 * @return string
	 */
	protected static function getPostType(): string {
		return \CommonsBooking\Wordpress\CustomPostType\Location::getPostType();
	}

	/**
	 * @return string
	 */
	protected static function getModelClass(): string {
		return \CommonsBooking\Model\Location::class;
	}

}
