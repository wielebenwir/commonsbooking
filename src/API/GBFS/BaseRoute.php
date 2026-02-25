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
