<?php


namespace CommonsBooking\API;

use CommonsBooking\Helper\GeoHelper;
use CommonsBooking\Model\Location;
use Exception;
use CommonsBooking\Geocoder\Geocoder;
use CommonsBooking\Geocoder\Provider\Provider;
use stdClass;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Endpoint for places, where items are located.
 *
 * @see Location for exposed api data
 */
class LocationsRoute extends BaseRoute {

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	protected $rest_base = 'locations';

	/**
	 * Commons-API schema definition.
	 *
	 * @var string
	 */
	protected $schemaUrl = BaseRoute::SCHEMA_PATH . 'commons-api.locations.schema.json';

	/**
	 * Get one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		return $this->get_items( $request );
	}

	/**
	 * Get a collection of items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$data            = new stdClass();
		$data->locations = $this->getItemData( $request );

		return $this->respond_with_validation( $data );
		}

	public function getItemData( $request ) {
		$data       = new stdClass();
		$data->type = 'FeatureCollection';

		$params = $request->get_params();
		$args   = [];
		if ( array_key_exists( 'id', $params ) ) {
			$args = [
				'p' => $params['id'],
			];
		}

		$locations = \CommonsBooking\Repository\Location::get( $args );
		$features  = [];

		foreach ( $locations as $location ) {
			try {
				$itemdata   = $this->prepare_item_for_response( $location, $request );
				$features[] = $itemdata->get_data();
			} catch ( Exception $exception ) {
				if ( WP_DEBUG ) {
					error_log( $exception->getMessage() );
				}
			}
		}

		$data->features = $features;

		return $data;
	}

	/**
	 * @param $item Location
	 * @param $request
	 *
	 * @return WP_REST_Response
	 * @throws \CommonsBooking\Geocoder\Exception\Exception
	 */
	public function prepare_item_for_response( $item, $request ): WP_REST_Response {
		$preparedItem             = new stdClass();
		$preparedItem->type       = 'Feature';
		$preparedItem->properties = new stdClass();

		$preparedItem->properties->id                 = $item->ID . '';
		$preparedItem->properties->name               = $item->post_title;
		$preparedItem->properties->description        = $this->escapeJsonString( $item->post_content );
		$preparedItem->properties->url                = get_permalink( $item->ID );
		$preparedItem->properties->address            = $item->formattedAddressOneLine();
		$preparedItem->properties->pickupInstructions = $item->formattedPickupInstructionsOneLine();

		$latitude  = get_post_meta( $item->ID, 'geo_latitude', true );
		$longitude = get_post_meta( $item->ID, 'geo_longitude', true );

		// If we have latitude and longitude defined, we use them.
		if ( $latitude && $longitude ) {
			$preparedItem->geometry              = new stdClass();
			$preparedItem->geometry->type        = 'Point';
			$preparedItem->geometry->coordinates = [
				floatval( $longitude ),
				floatval( $latitude ),
			];
		} elseif ( $item->formattedAddressOneLine() ) {
			$address = GeoHelper::getAddressData( $item->formattedAddressOneLine() );
			if ( $address !== null ) {
				$preparedItem->geometry              = new stdClass();
				$preparedItem->geometry->type        = 'Point';
				$preparedItem->geometry->coordinates = $address->getCoordinates()->toArray();

				// Save data to items
				update_post_meta(
					$item->ID,
					'geo_latitude',
					$preparedItem->geometry->coordinates[1]
				);
				update_post_meta(
					$item->ID,
					'geo_longitude',
					$preparedItem->geometry->coordinates[0]
				);
			} else {
				throw new Exception( 'Location address missing. (ID: ' . $item->ID . ')' );
			}
		}

		return new WP_REST_Response( $preparedItem );
	}
}
