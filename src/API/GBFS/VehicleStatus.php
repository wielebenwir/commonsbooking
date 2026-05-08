<?php

namespace CommonsBooking\API\GBFS;

use CommonsBooking\Repository\Item;
use CommonsBooking\Repository\PostRepository;
use stdClass;
use WP_REST_Response;

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

		$preparedItem              = new stdClass();
		$preparedItem->vehicle_id  = strval( $item->getCloakedId() );
		$preparedItem->station_id  = strval( $location->ID );
		$preparedItem->is_reserved = ! $item->isCurrentlyFreeAtLocation( intval( $preparedItem->station_id ) );
		$preparedItem->is_disabled = false; // This never happens, when the item is disabled it does not have a location and is therefore skipped
		$preparedItem->rental_uris = (object) [
			'web' => $item->getCloakedURL(),
		];
		// $preparedItem->available_until //TODO: The date and time when any rental of the vehicle must be completed. The vehicle must be returned and made available for the next user by this time. If this field is empty, it indicates that the vehicle is available indefinitely. This field SHOULD be published by carsharing or other mobility systems where vehicles can be booked in advance for future travel.

		return new WP_REST_Response( $preparedItem );
	}

	protected static function getListName(): string {
		return 'vehicles';
	}


	protected static function getRepository(): PostRepository {
		// we iterate over posts with cb_item post type
		return new Item();
	}
}
