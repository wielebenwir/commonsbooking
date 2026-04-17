<?php


namespace CommonsBooking\API\GBFS;

use Exception;
use stdClass;
use WP_REST_Response;

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
	protected $schemaUrl = COMMONSBOOKING_PLUGIN_DIR . 'includes/gbfs-json-schema/gbfs.json';

	/**
	 * Returns feed urls for different endpoints
	 *
	 * @param mixed $request
	 * @return WP_REST_Response
	 */
	public function get_items( $request ): WP_REST_Response {

		/**
		 * The names of the feeds that are available in the GBFS route.
		 * They will be announced through the gbfs.json.
		 * The routes still need to be registered using register_rest_route.
		 *
		 * @since 2.11
		 *
		 * @param String[] $raw_feeds the names of the feeds without a .json suffix
		 */
		$raw_feeds = apply_filters(
			'commonsbooking_gbfs_feeds',
			[
				'system_information',
				'station_information',
				'station_status',
			]
		);
		$feeds     = array_map( fn( $feed ) => $this->get_feed( $feed ), $raw_feeds );

		$response               = new stdClass();
		$response->data         = new stdClass();
		$response->data->feeds  = $feeds;
		$response->last_updated = date( 'c' ); // ISO-8601 timestamp
		$response->ttl          = 86400;
		$response->version      = '3.1-RC2';

		return $this->respond_with_validation( $response );
	}

	private function get_feed( $name ): stdClass {
		$feed       = new stdClass();
		$feed->name = $name;
		$feed->url  = get_rest_url() . 'commonsbooking/v1/' . $name . '.json';
		return $feed;
	}
}
