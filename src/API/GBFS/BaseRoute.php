<?php


namespace CommonsBooking\API\GBFS;

use CommonsBooking\Repository\Location;
use Exception;
use stdClass;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Base class which implements retrieval of basic data attributes of
 * GBFS spec.
 *
 * Note: When deriving from this class, implement \WP_REST_Controller::prepare_item_for_response,
 *       which is called in \BaseRoute::getItemData
 */
class BaseRoute extends \CommonsBooking\API\BaseRoute {

	/**
	 * Returns Rest Response with items.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_items( $request ): WP_REST_Response {
		$data                 = new stdClass();
		$data->data           = new stdClass();
		$data->data->stations = $this->getItemData( $request );
		$data->last_updated   = time();
		$data->ttl            = 60;
		$data->version        = '2.3';

		if ( WP_DEBUG ) {
			$this->validateData( $data );
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Returns item data array.
	 *
	 * @param $request
	 *
	 * @return array
	 */
	public function getItemData( $request ): array {
		$data      = [];
		$locations = Location::get();

		foreach ( $locations as $location ) {
			try {
				$itemdata = $this->prepare_item_for_response( $location, $request );
				$data[]   = $itemdata;
			} catch ( Exception $exception ) {
				if ( WP_DEBUG ) {
					error_log( $exception->getMessage() );
				}
			}
		}

		return $data;
	}
}
