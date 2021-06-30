<?php


namespace CommonsBooking\API\GBFS;


use CommonsBooking\Model\Location;
use CommonsBooking\Repository\Item;
use Geocoder\Exception\Exception;
use stdClass;

class StationStatus extends BaseRoute {

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	protected $rest_base = 'station_status';

	/**
	 * Commons-API schema definition.
	 * @var string
	 */
	protected $schemaUrl = "https://raw.githubusercontent.com/MobilityData/gbfs-json-schema/master/station_status.json";

	/**
	 * @param $item Location
	 * @param $request
	 *
	 * @return stdClass
	 * @throws Exception
	 */
	public function prepare_item_for_response( $item, $request ) {
		$preparedItem                      = new stdClass();
		$preparedItem->station_id          = $item->ID . "";
		$preparedItem->num_bikes_available = count( Item::getByLocation( $item->ID ) );
		$preparedItem->is_installed        = true;
		$preparedItem->is_renting          = true;
		$preparedItem->is_returning        = true;

		return $preparedItem;
	}

}