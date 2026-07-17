<?php

namespace CommonsBooking\API\GBFS;

use stdClass;
use WP_REST_Response;

class VehicleTypes extends \CommonsBooking\API\BaseRoute {


	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	protected $rest_base = 'vehicle_types.json';

	/**
	 * Commons-API schema definition.
	 *
	 * @var string
	 */
	protected $schemaUrl = COMMONSBOOKING_PLUGIN_DIR . 'includes/gbfs-json-schema/vehicle_types.json';

	/**
	 * In the core plugin, we offer just one vehicle type with hardcoded defaults.
	 */
	const DEFAULT_NAME = COMMONSBOOKING_PLUGIN_SLUG . '_default';

	/**
	 * Returns feed urls for different endpoints
	 *
	 * @param mixed $request
	 * @return WP_REST_Response
	 */
	public function get_items( $request ): WP_REST_Response {
		$response                      = new stdClass();
		$response->data                = new stdClass();
		$response->data->vehicle_types = [
			(object) [
				'vehicle_type_id' => self::DEFAULT_NAME,
				'form_factor' => 'cargo_bicycle',
				'propulsion_type' => 'human',
			],
		];
		$response->last_updated        = date( 'c' ); // ISO-8601 timestamp
		$response->ttl                 = 86400;
		$response->version             = '3.1-RC3';

		return $this->respond_with_validation( $response );
	}
}
