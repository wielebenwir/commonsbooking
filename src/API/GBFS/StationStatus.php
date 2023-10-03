<?php


namespace CommonsBooking\API\GBFS;


use CommonsBooking\Model\Day;
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
	protected $rest_base = 'station_status.json';

	/**
	 * Commons-API schema definition.
	 * @var string
	 */
    protected $schemaUrl = COMMONSBOOKING_PLUGIN_DIR . 'includes/gbfs-json-schema/station_status.json';

	/**
	 * @param $item Location
	 * @param $request
	 *
	 * @return stdClass
	 * @throws \Exception
	 */
	public function prepare_item_for_response( $item, $request ): stdClass {
		$today                             = new Day(date( 'Y-m-d', time()), [$item->ID], Item::getByLocation( $item->ID ) );
		$preparedItem                      = new stdClass();
		$preparedItem->station_id          = $item->ID . "";
		$preparedItem->num_bikes_available = count ($today->getBookableItems()); // TODO should be the item availability in this moment
		$preparedItem->is_installed        = true;
		$preparedItem->is_renting          = true;
		$preparedItem->is_returning        = true;
		$preparedItem->last_reported       = current_time('timestamp');

		return $preparedItem;
	}
}