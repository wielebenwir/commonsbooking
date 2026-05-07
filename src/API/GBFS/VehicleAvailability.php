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
		$preparedItem->vehicle_id     = strval( $item->getCloakedId() );
		$preparedItem->station_id     = strval( $item->getLocation()?->ID ); // This is what you could consider the home location. Regardless if the item is there atm or not.
		$preparedItem->availabilities = self::getAvailabilities( $item );

		return new WP_REST_Response( $preparedItem );
	}

	private static function getAvailabilities( $item ): array {
		$availabilities = AvailabilityRoute::getItemData( $item->ID );

		if ( empty( $availabilities ) ) {
			return [];
		}

		$firstAvailability = array_shift( $availabilities );
		$slots             = [
			(object) [
				'from' => $firstAvailability->start,
				'until' => $firstAvailability->end,
			],
		];

		foreach ( $availabilities as $availability ) {
			$gapSeconds = strtotime( $availability->start ) - strtotime( end( $slots )->until );

			if ( $gapSeconds < 59 ) {
				end( $slots )->until = $availability->end;
			} else {
				$slots[] = (object) [
					'from' => $availability->start,
					'until' => $availability->end,
				];
			}
		}

		return $slots;
	}

	protected static function getListName(): string {
		return 'vehicles';
	}


	protected static function getRepository(): PostRepository {
		// we iterate over posts with cb_item post type
		return new Item();
	}
}
