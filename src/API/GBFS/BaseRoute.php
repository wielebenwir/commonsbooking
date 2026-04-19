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
		$response                 = new stdClass();
		$response->data           = new stdClass();
		$response->data->stations = $this->getItemData( $request );
		$response->last_updated   = date( 'c' ); // ISO-8601 timestamp
		$response->ttl            = 60;
		$response->version        = '3.1-RC2';

		return $this->respond_with_validation( $response );
	}

	/**
	 * Returns item data array.
	 *
	 * @since 2.9.3 includes filter hooks for location get-args and for-each item skip.
	 *
	 * @param WP_REST_Request $request inbound rest request.
	 *
	 * @return array
	 */
	public function getItemData( $request ): array {
		$data = array();

		/**
		 * Lets you customize the args for database query. Affects all endpoints, because gbfs is based on location model.
		 *
		 * @param array $args request args array
		 * @param WP_REST_Request $request
		 */
		$args = apply_filters( 'commonsbooking_gbfs_location_getargs', array(), $request );

		/** @var \CommonsBooking\Model\Location[] $locations */
		$locations = Location::get( $args );

		foreach ( $locations as $location ) {
			/**
			 * You can compute if an item should be skipped or not for the api response, based on $item data.
			 *
			 * Example: add_filter( '...', function ( Location $location ) {
			 *      return 'my_category' in $location->term;
			 * })
			 *
			 * @param \CommonsBooking\Model\Location $location bookable post type.
			 */
			if ( true === apply_filters( 'commonsbooking_gbfs_location_skipitem', $location ) ) {
				continue;
			}
			try {
				$itemdata = $this->prepare_item_for_response( $location, $request );
				$data[]   = $itemdata->data;
			} catch ( Exception $exception ) {
				if ( WP_DEBUG ) {
					error_log( $exception->getMessage() );
				}
			}
		}

		return $data;
	}
}
