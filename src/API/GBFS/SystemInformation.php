<?php


namespace CommonsBooking\API\GBFS;

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
	 *
	 * @var string
	 */
	protected $schemaUrl = COMMONSBOOKING_PLUGIN_DIR . 'includes/gbfs-json-schema/system_information.json';

	public function get_items( $request ): WP_REST_Response {
		$tz = timezone_name_get( wp_timezone() );
		if ( preg_match( '/^(\+|\-)0?(\d+)/', $tz, $matches ) ) {
			$tz = 'Etc/GMT' . $matches[1] . $matches[2];
		}

		$data                  = new stdClass();
		$data->data            = new stdClass();
		$data->data->name      = get_bloginfo( 'name' );
		$data->data->system_id = sha1( site_url() );
		$data->data->language  = get_bloginfo( 'language' );
		$data->data->timezone  = $tz;
		$data->last_updated    = current_time( 'timestamp' );
		$data->ttl             = 86400;
		$data->version         = '2.3';

		if ( WP_DEBUG ) {
			$this->validateData( $data );
		}

		return new WP_REST_Response( $data, 200 );
	}
}
