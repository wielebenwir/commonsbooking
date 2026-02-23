<?php


namespace CommonsBooking\API\GBFS;

use CommonsBooking\Helper\GeoHelper;
use CommonsBooking\Model\Location;
use Exception;
use stdClass;
use WP_REST_Response;

class StationInformation extends BaseRoute {

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	protected $rest_base = 'station_information.json';

	/**
	 * Commons-API schema definition.
	 *
	 * @var string
	 */
	protected $schemaUrl = COMMONSBOOKING_PLUGIN_DIR . 'includes/gbfs-json-schema/station_information.json';

	/**
	 * @param $item Location
	 * @param $request
	 *
	 * @return WP_REST_Response
	 * @throws \CommonsBooking\Geocoder\Exception\Exception
	 * @throws Exception
	 */
	public function prepare_item_for_response( $item, $request ): WP_REST_Response {
		$preparedItem                   = new stdClass();
		$preparedItem->station_id       = $item->ID . '';
		$preparedItem->name             = $item->post_title;
		$preparedItem->address          = $item->formattedAddressOneLine();
		$preparedItem->rental_uris      = new stdClass();
		$preparedItem->rental_uris->web = get_permalink( $item->ID );

		// Additional possible fields (but we don't have the information):
		// $preparedItem->short_name = "";
		// $preparedItem->cross_street = "";
		// $preparedItem->region_id = "";
		// $preparedItem->post_code = "";
		// $preparedItem->rental_methods = [];
		// $preparedItem->is_virtual_station = false;
		// $preparedItem->station_area = "";
		// $preparedItem->capacity = "";
		// $preparedItem->vehicle_capacity = "";
		// $preparedItem->is_valet_station = "";
		// $preparedItem->vehicle_type_capacity = "";

		$latitude  = get_post_meta( $item->ID, 'geo_latitude', true ); // TODO this can be part of model $item Location
		$longitude = get_post_meta( $item->ID, 'geo_longitude', true );

		// If we have latitude and longitude defined, we use them.
		if ( $latitude && $longitude ) {
			$preparedItem->lat = floatval( $latitude );
			$preparedItem->lon = floatval( $longitude );
		} elseif ( $item->formattedAddressOneLine() ) {
			$address = GeoHelper::getAddressData( $item->formattedAddressOneLine() );
			if ( $address !== null ) {
				$preparedItem->lat = $preparedItem->geometry->coordinates[1];
				$preparedItem->lon = $preparedItem->geometry->coordinates[0];

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
