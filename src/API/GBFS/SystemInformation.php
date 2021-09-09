<?php


namespace CommonsBooking\API\GBFS;

use Exception;
use stdClass;
use WP_REST_Response;

class SystemInformation extends \CommonsBooking\API\BaseRoute {

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	protected $rest_base = 'system_information.json';

	/**
	 * Commons-API schema definition.
	 * @var string
	 */
	protected $schemaUrl = "https://raw.githubusercontent.com/MobilityData/gbfs-json-schema/master/system_information.json";

        public function get_items( $request ): WP_REST_Response {
		$data                 = new stdClass();
		$data->data           = new stdClass();
		$data->data->name     = get_bloginfo('name');
		$data->data->system_id = sha1(site_url());
		$data->data->language = get_bloginfo('language');
		$data->data->timezone = get_option('timezone_string');
		$data->last_updated   = time();
		$data->ttl            = 86400;
		$data->version        = "2.2";

		if ( WP_DEBUG ) {
			$this->validateData( $data );
		}

		return new WP_REST_Response( $data, 200 );
	}
}
