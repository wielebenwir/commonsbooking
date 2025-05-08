<?php


namespace CommonsBooking\API\GBFS;

use CommonsBooking\Model\Calendar;
use CommonsBooking\Model\Day;
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
	 * @param Location $item
	 * @param $request
	 *
	 * @return WP_REST_Response
	 * @throws \Exception
	 */
	public function prepare_item_for_response( $item, $request ): WP_REST_Response {
		$preparedItem                      = new stdClass();
		$preparedItem->station_id          = $item->ID . '';
		$preparedItem->num_bikes_available = $this->getItemCountAtLocation( $item->ID );
		$preparedItem->is_installed        = true;
		$preparedItem->is_renting          = true;
		$preparedItem->is_returning        = true;
		$preparedItem->last_reported       = time();

		return new WP_REST_Response( $preparedItem );
	}

	/**
	 * Will get the amount of items that are currently in the location
	 * and marked as "green" (bookable right now by the user) for the current time.
	 * This purposefully excludes items that have a Holiday Timeframe, are bookable in advance
	 * or can only be booked through overbooking.
	 * This is because the GBFS spec only accounts for items available in that instant.
	 *
	 * @param int $locationId
	 *
	 * @return int
	 * @throws \Exception
	 */
	private function getItemCountAtLocation( $locationId ): int {
		$items            = Item::getByLocation( $locationId, true );
		$nowDT            = new \DateTime();
		$availableCounter = 0;
		foreach ( $items as $item ) {
			// we have to make our calendar span at least one day, otherwise we get no results
			$itemCalendar      = new Calendar(
				new Day( date( 'Y-m-d', time() ) ),
				new Day( date( 'Y-m-d', strtotime( '+1 day' ) ) ),
				[ $locationId ],
				[ $item->ID ]
			);
			$availabilitySlots = $itemCalendar->getAvailabilitySlots();
			// we have to iterate over multiple slots because the calendar will give us more than we asked for
			foreach ( $availabilitySlots as $availabilitySlot ) {
				// match our exact current time to the slot
				$startDT = new \DateTime( $availabilitySlot->start );
				$endDT   = new \DateTime( $availabilitySlot->end );
				if ( $nowDT >= $startDT && $nowDT <= $endDT ) {
					++$availableCounter;
					// break out of the loop, we only need one match of availability per item
					break;
				}
			}
		}
		return $availableCounter;
	}
}
