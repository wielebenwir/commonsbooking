<?php


namespace CommonsBooking\API;

use CommonsBooking\Model\Calendar;
use CommonsBooking\Model\Day;
use CommonsBooking\Repository\Item;
use CommonsBooking\Settings\Settings;
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
	 * How many weeks of item availability should be displayed by default.
	 * This value can be changed in the API settings.
	 *
	 * @var int
	 */
	const DEFAULT_WEEKS = 2;
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
	 * @param int|int[]|null $id The IDs of {@see \CommonsBooking\Wordpress\CustomPostType\Item::post_type} posts to search for
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function getItemData( $id = null ): array {
		$availabilityWeeks = Settings::getOption( 'commonsbooking_options_api', 'api_future_availability_weeks' );
		if ( ! $availabilityWeeks || ! is_numeric( $availabilityWeeks ) ) {
			$availabilityWeeks = self::DEFAULT_WEEKS;
		} else {
			$availabilityWeeks = intval( $availabilityWeeks );
		}
		$itemIds  = is_array( $id ) ? $id : ( $id ? [ $id ] : [] );
		$calendar = new Calendar(
			new Day( date( 'Y-m-d', time() ) ),
			new Day( date( 'Y-m-d', strtotime( '+' . $availabilityWeeks . ' weeks' ) ) ),
			[],
			$itemIds
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

		$itemIds            = array_map(
			static fn( $item ) => $item->ID,
			$items
		);
		$data->availability = $itemIds ? $this->getItemData( $itemIds ) : [];

		return $this->respond_with_validation( $data );
	}
}
