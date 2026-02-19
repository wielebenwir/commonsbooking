<?php


namespace CommonsBooking\API\GBFS;

use Exception;
use stdClass;
use WP_REST_Response;
use WP_REST_Request;

/**
 * Assembles feed urls for different gbfs endpoints and it's purpose is service discovery.
 */
class Discovery extends \CommonsBooking\API\BaseRoute {

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	protected $rest_base = 'gbfs.json';

	/**
	 * Commons-API schema definition.
	 *
	 * @var string
	 */
	protected string $schemaUrl = COMMONSBOOKING_PLUGIN_DIR . 'includes/gbfs-json-schema/gbfs.json';

	/**
	 * Returns feed urls for different endpoints
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response
	 */
	public function get_items( $request ): WP_REST_Response {

		$feeds   = array();
		$feeds[] = $this->get_feed( 'system_information' );
		$feeds[] = $this->get_feed( 'station_information' );
		$feeds[] = $this->get_feed( 'station_status' );

		$lang                     = get_bloginfo( 'language' );
		$data                     = new stdClass();
		$data->data               = new stdClass();
		$data->data->$lang        = new stdClass();
		$data->data->$lang->feeds = $feeds;
		$data->last_updated       = current_time( 'timestamp' );
		$data->ttl                = 86400;
		$data->version            = '2.3';

		if ( WP_DEBUG ) {
			$this->validateData( $data );
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * @param string $name
	 *
	 * @return stdClass
	 */
	private function get_feed( string $name ): stdClass {
		$feed       = new stdClass();
		$feed->name = $name;
		$feed->url  = get_rest_url() . 'commonsbooking/v1/' . $name . '.json';
		return $feed;
	}
}
