<?php


namespace CommonsBooking\API;

use CommonsBooking\Model\Calendar;
use CommonsBooking\Model\Day;
use CommonsBooking\Repository\Item;
use Exception;
use stdClass;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Endpoint exposes item availability
 *
 * @see Calendar for computing item availability.
 *
 * @see JSON-schema-Specification {@see https://github.com/wielebenwir/commons-api/blob/master/commons-api.availability.schema.json}
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
	protected $schemaUrl = BaseRoute::SCHEMA_PATH . 'commons-api.availability.schema.json';

	/**
	 * This retrieves bookable timeframes and the different items assigned, with their respective availability.
	 *
	 * @param bool $id The id of a {@see \CommonsBooking\Wordpress\CustomPostType\Item::post_type} post to search for
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
	 *
	 * @param $request WP_REST_Request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		// get parameters from request
		$params = $request->get_params();
		$data   = new stdClass();
		try {
			$data->availability = $this->getItemData( $params['id'] );
			return $this->respond_with_validation( $data );
		} catch ( Exception $e ) {
			return new WP_Error( 'code', $e->getMessage() );
		}
	}

	/**
	 * Get a collection of items
	 *
	 * @param $request WP_REST_Request full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$data               = new stdClass();
		$data->availability = [];

		// Get all items
		$items = Item::get( [], true );

		// Collect availabilies for each item
		foreach ( $items as $item ) {
			$data->availability = array_merge(
				$data->availability,
				$this->getItemData( $item->ID )
			);
		}

		return $this->respond_with_validation( $data );
	}
}
