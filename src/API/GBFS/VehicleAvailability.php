<?php

namespace CommonsBooking\API\GBFS;

use CommonsBooking\API\AvailabilityRoute;
use CommonsBooking\Repository\Item;
use CommonsBooking\Repository\PostRepository;
use stdClass;
use WP_REST_Response;

class VehicleAvailability extends BaseRoute {

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	protected $rest_base = 'vehicle_availability.json';

	/**
	 * Commons-API schema definition.
	 *
	 * @var string
	 */
	protected $schemaUrl = COMMONSBOOKING_PLUGIN_DIR . 'includes/gbfs-json-schema/vehicle_availability.json';

	/**
	 * @param \CommonsBooking\Model\Item $item
	 * @param $request
	 *
	 * @return WP_REST_Response
	 * @throws \Exception
	 */
	public function prepare_item_for_response( $item, $request ): WP_REST_Response {
		$preparedItem                 = new stdClass();
		$preparedItem->vehicle_id     = strval( $item->ID ); // TODO: Must be officially rotated to a random string after each trip. Needs evaluation
		$preparedItem->station_id     = strval( $item->getLocation()?->ID ); // TODO: Question: Only when the item is at the location or home_location?
		$preparedItem->availabilities = self::getAvailabilities( $item );

		return new WP_REST_Response( $preparedItem );
	}

	private static function getAvailabilities( $item ): array {
		$availabilities = AvailabilityRoute::getItemData( $item->ID );
		$finalAvail     = [];

		if ( ! empty( $availabilities ) ) {
			$firstAvail    = array_shift( $availabilities );
			$finalAvail[0] = (object) [
				'from' => $firstAvail->start,
				'until' => $firstAvail->end,
			];
			$currentIndex  = 0;
			foreach ( $availabilities as $availability ) {
				$current  = new \DateTime( $finalAvail[ $currentIndex ]->until );
				$next     = new \DateTime( $availability->start );
				$interval = $current->diff( $next );
				if ( $interval->y == 0 && $interval->m == 0 && $interval->d == 0 && $interval->h == 0 && $interval->i == 0 && $interval->s < 59 ) {
					$finalAvail[ $currentIndex ]->until = $availability->end;
				} else { // we create a new slot
					++$currentIndex;
					$finalAvail[] = (object) [
						'from' => $availability->start,
						'until' => $availability->end,
					];
				}
			}
		}
		return $finalAvail;
	}

	protected static function getListName(): string {
		return 'vehicles';
	}


	protected static function getRepository(): PostRepository {
		// we iterate over posts with cb_item post type
		return new Item();
	}
}
