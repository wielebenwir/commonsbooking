<?php


namespace CommonsBooking\API;

use CommonsBooking\Repository\UserRepository;
use stdClass;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_User;

/**
 * Endpoint for item/location owners data
 *
 * Unused at the moment.
 *
 * TODO Think about a dto/model abstraction, application layer in OwnersRoute type shouldn't contain the code to retrieve data from database/wp-post layer
 *
 * TODO Personal identifieable information is potentially exposed via firstname and lastname, this should be disclosed or configurable
 */
class OwnersRoute extends BaseRoute {

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	protected $rest_base = 'owners';

	/**
	 * Commons-API schema definition.
	 *
	 * @var string
	 */
	protected $schemaUrl = BaseRoute::SCHEMA_PATH . 'commons-api.owners.schema.json';

	/**
	 * Returns raw data collection.
	 *
	 * @param $request
	 *
	 * @return array
	 */
	public function getItemData( $request ): array {
		$data = [];

		foreach ( UserRepository::getOwners() as $owner ) {
			$data[] = $this->prepare_item_for_response( $owner, $request );
		}

		return $data;
	}

	/**
	 * @param WP_User         $owner
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $owner, $request ): WP_REST_Response {
		$ownerObject       = new stdClass();
		$ownerObject->id   = '' . $owner->ID;
		$ownerObject->name = get_user_meta( $owner->ID, 'first_name', true ) . ' ' . get_user_meta( $owner->ID, 'last_name', true );
		$ownerObject->url  = $owner->user_url;

		// if($items = \CommonsBooking\Repository\Item::getByUserId($owner->ID, true)) {
		// $ownerObject->items = [];
		// $itemsRoute = new ItemsRoute();
		// foreach($items as $item) {
		// $ownerObject->items[] = $itemsRoute->prepare_item_for_response($item, new \WP_REST_Request());
		// }
		// }
		//
		// if($locations = \CommonsBooking\Repository\Location::getByUserId($owner->ID, true)) {
		// $ownerObject->locations = [];
		// $locationsRoute = new LocationsRoute();
		// foreach($locations as $location) {
		// $ownerObject->locations[] = $locationsRoute->prepare_item_for_response($location, new \WP_REST_Request());
		// }
		// }
		return new WP_REST_Response( $ownerObject );
	}


	/**
	 * Get a single item
	 */
	public function get_item( $request ): WP_REST_Response {
		// get parameters from request
		$params         = $request->get_params();
		$owner          = get_user_by( 'id', $params['id'] );
		$data           = new stdClass();
		$data->owners[] = $this->prepare_item_for_response( $owner, $request );

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Get a collection of items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$data         = new stdClass();
		$data->owners = $this->getItemData( $request );

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * TODO investigate why we overwrite this method
	 */
	public function prepare_response_for_collection( $itemdata ) {
		return $itemdata; // @phpstan-ignore return.type
	}
}
