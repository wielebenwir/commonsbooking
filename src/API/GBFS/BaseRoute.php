<?php


namespace CommonsBooking\API\GBFS;

use CommonsBooking\Repository\Location;
use CommonsBooking\Repository\PostRepository;
use Exception;
use stdClass;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Base class which implements retrieval of basic data attributes of
 * GBFS spec. Derive from this class, when you want to have a route that iterates over CustomPosts.
 *
 * Note: When deriving from this class
 *      - implement \WP_REST_Controller::prepare_item_for_response,
 *        which is called in \BaseRoute::getItemData
 *      - implement $rest_base
 *      - implement $schemaUrl
 *      - (if necessary) overwrite getRepository
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
		$response                                = new stdClass();
		$response->data                          = new stdClass();
		$response->data->{static::getListName()} = $this->getItemData( $request );
		$response->last_updated                  = date( 'c' ); // ISO-8601 timestamp
		$response->ttl                           = 60;
		$response->version                       = '3.1-RC2';

		return $this->respond_with_validation( $response );
	}

	/**
	 * Returns item data array.
	 *
	 * @param $request
	 *
	 * @return array
	 */
	public function getItemData( $request ): array {
		$data  = [];
		$items = static::getRepository()::get();

		foreach ( $items as $item ) {
			try {
				$itemdata = $this->prepare_item_for_response( $item, $request );
				$data[]   = $itemdata->data;
			} catch ( Exception $exception ) {
				if ( WP_DEBUG ) {
					error_log( $exception->getMessage() );
				}
			}
		}

		return $data;
	}

	/**
	 * Overwrite this, if you don't iterate over stations but need the resulting items in a list with a different name
	 *
	 * @return string
	 */
	protected static function getListName(): string {
		return 'stations';
	}

	/**
	 * The post type that the route will iterate over.
	 * By default, these are all the locations.
	 *
	 * @return PostRepository
	 */
	protected static function getRepository(): PostRepository {
		return new Location();
	}
}
