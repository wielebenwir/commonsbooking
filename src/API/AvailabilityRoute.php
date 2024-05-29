<?php


namespace CommonsBooking\API;

use CommonsBooking\Helper\Wordpress;
use CommonsBooking\Model\Calendar;
use CommonsBooking\Model\Day;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Model\Week;
use CommonsBooking\Repository\Item;
use DateTime;
use DateTimeZone;
use Exception;
use stdClass;
use WP_Error;
use WP_REST_Response;

/**
 * Endpoint exposes item availability
 *
 * @See Calendar for computing item availability
 */
class AvailabilityRoute extends BaseRoute {

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	protected $rest_base = 'availability';

	/**
	 * Commons-API schema definition.
	 *
	 * @var string
	 */
	protected $schemaUrl = COMMONSBOOKING_PLUGIN_DIR . 'includes/commons-api-json-schema/commons-api.availability.schema.json';

	/**
	 * This retrieves bookable timeframes and the different items assigned, with their respective availability.
	 *
	 * @param bool $id The id of a \CommonsBooking\Wordpress\CustomPostType\Item::post_type post to search for
	 * @param null $startTime The start date of the calendar to get the data for
	 * @param null $endTime The end date of the calendar to get the data for
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getItemData( $id = false ): array {
		$calendar = new Calendar(
			new Day( date( 'Y-m-d', time() ) ),
			new Day( date( 'Y-m-d', strtotime( '+2 weeks' ) ) ), // TODO why two weeks? seems like a configurable option
			[],
			$id ? [ $id ] : []
		);

		return $calendar->getAvailabilitySlots();
	}

	/**
	 * Get one item from the collection
	 */
	public function get_item( $request ) {
		//get parameters from request
		$params             = $request->get_params();
		$data               = new stdClass();
		try {
			$data->availability = $this->getItemData( $params['id'] );

			//return a response or error based on some conditional
			if ( count( $data->availability ) ) {
				return new WP_REST_Response( $data, 200 );
			} else {

			}
		} catch (Exception $e) {
			return new WP_Error( 'code', $e->getMessage() );
		}

	}

	/**
	 * Get a collection of items
	 *
	 * @param $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$data               = new stdClass();
		$data->availability = [];

		// Get all items
		$items = Item::get([], true);

		// Collect availabilies for each item
		foreach ($items as $item) {
			$data->availability = array_merge(
				$data->availability,
				$this->getItemData($item->ID)
			);
		}
		return new WP_REST_Response( $data, 200 );
	}

}
