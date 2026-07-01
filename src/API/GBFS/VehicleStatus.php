<?php

namespace CommonsBooking\API\GBFS;

use CommonsBooking\Model\Day;
use CommonsBooking\Model\Location;
use CommonsBooking\Model\Restriction;
use CommonsBooking\Repository\Item;
use CommonsBooking\Repository\PostRepository;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use stdClass;
use WP_REST_Response;

/**
 * Describes all vehicles that are not currently in active rental.
 */
class VehicleStatus extends BaseRoute {

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	protected $rest_base = 'vehicle_status.json';

	/**
	 * Commons-API schema definition.
	 *
	 * @var string
	 */
	protected $schemaUrl = COMMONSBOOKING_PLUGIN_DIR . 'includes/gbfs-json-schema/vehicle_status.json';

	/**
	 * @param \CommonsBooking\Model\Item $item
	 * @param $request
	 *
	 * @return WP_REST_Response
	 * @throws \Exception
	 */
	public function prepare_item_for_response( $item, $request ): WP_REST_Response {
		$location = $item->getLocation();
		if ( ! $location ) {
			throw new \Exception( 'No location for item. (ID: ' . $item->ID . ')' );
		}

		// Vehicles that are part of an active rental MUST NOT appear in this feed
		if ( ! $item->isCurrentlyFreeAtLocation( $location->ID, true, true ) ) {
			throw new \Exception( 'Item currently not available (skipped in VehicleStatus) ' . '(ID: ' . $item->ID . ')' );
		}

		$preparedItem                  = new stdClass();
		$preparedItem->vehicle_id      = strval( $item->getCloakedId() );
		$preparedItem->vehicle_type_id = VehicleTypes::DEFAULT_NAME;
		$preparedItem->station_id      = strval( $location->ID );
		$preparedItem->is_reserved     = false; // this never happens, we do not know the difference between the start of a booking period and if it has actually been picked up
		$preparedItem->is_disabled     = $this->isDisabled( $item, $location );
		$preparedItem->rental_uris     = (object) [
			'web' => $item->getCloakedURL(),
		];
		// $preparedItem->available_until //TODO: The date and time when any rental of the vehicle must be completed. The vehicle must be returned and made available for the next user by this time. If this field is empty, it indicates that the vehicle is available indefinitely. This field SHOULD be published by carsharing or other mobility systems where vehicles can be booked in advance for future travel.

		return new WP_REST_Response( $preparedItem );
	}

	private function isDisabled( \CommonsBooking\Model\Item $item, Location $location ): bool {
		$today        = new Day( date( 'Y-m-d', time() ), [ $location->ID ], [ $item->ID ], [ Timeframe::BOOKABLE_ID ] );
		$restrictions = $today->getRestrictions();
		$restrictions = array_filter(
			$restrictions,
			fn( $restriction ) => $restriction->isActive() && $restriction->getType() == Restriction::TYPE_REPAIR
		);

		if ( empty( $restrictions ) ) {
			return false;
		} else {
			return true;
		}
	}

	protected static function getListName(): string {
		return 'vehicles';
	}


	protected static function getRepository(): PostRepository {
		// we iterate over posts with cb_item post type
		return new Item();
	}
}
