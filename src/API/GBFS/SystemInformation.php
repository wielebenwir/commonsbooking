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

		$response                           = new stdClass();
		$response->data                     = new stdClass();
		$response->data->name               = [
			(object) [
				'text' => get_bloginfo( 'name' ),
				'language' => get_bloginfo( 'language' ),
			],
		];
		$response->data->opening_hours      = '24/7'; // TODO: Close, when no items are available
		$response->data->system_id          = sha1( site_url() );
		$response->data->feed_contact_email = get_bloginfo( 'admin_email' );
		$response->data->languages          = [ get_bloginfo( 'language' ) ];
		$response->data->timezone           = $tz;
		$response->last_updated             = date( 'c' ); // ISO-8601 timestamp;
		$response->ttl                      = 86400;
		$response->version                  = '3.1-RC2';

		return $this->respond_with_validation( $response );
	}
}
