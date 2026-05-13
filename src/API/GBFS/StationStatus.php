<?php


namespace CommonsBooking\API\GBFS;

use CommonsBooking\Model\Location;
use CommonsBooking\Repository\Item;
use stdClass;
use WP_REST_Response;

class StationStatus extends BaseRoute {

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	protected $rest_base = 'station_status.json';

	/**
	 * Commons-API schema definition.
	 *
	 * @var string
	 */
	protected $schemaUrl = COMMONSBOOKING_PLUGIN_DIR . 'includes/gbfs-json-schema/station_status.json';

	/**
	 * @param Location $location
	 * @param $request
	 *
	 * @return WP_REST_Response
	 * @throws \Exception
	 */
	public function prepare_item_for_response( $location, $request ): WP_REST_Response {
		$preparedItem                         = new stdClass();
		$preparedItem->station_id             = strval( $location->ID );
		$preparedItem->num_vehicles_available = count(
			array_filter(
				Item::getByLocation( $location->ID, true ),
				fn( $item ) => $item->isCurrentlyFreeAtLocation( $location->ID ) && ! $item->getMeta( COMMONSBOOKING_METABOX_PREFIX . 'api_exclude' ) == 'on'
			)
		);
		$preparedItem->is_installed           = true;
		$preparedItem->is_renting             = true;
		$preparedItem->is_returning           = true;
		$preparedItem->last_reported          = date( 'c' ); // ISO-8601 timestamp

		return new WP_REST_Response( $preparedItem );
	}
}
