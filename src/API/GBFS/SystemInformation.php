<?php


namespace CommonsBooking\API\GBFS;

use CommonsBooking\CB\CB;
use CommonsBooking\Repository\Timeframe;
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
		$response->data->opening_hours      = $this->isOpen() ? '24/7' : '24/7 closed';
		$response->data->system_id          = COMMONSBOOKING_PLUGIN_SLUG . '_' . strtolower( preg_replace( '/\s+/', '_', get_bloginfo( 'name' ) ) );
		$response->data->feed_contact_email = get_bloginfo( 'admin_email' );
		$response->data->languages          = [ get_bloginfo( 'language' ) ];
		$response->data->timezone           = $tz;
		$response->last_updated             = date( 'c' ); // ISO-8601 timestamp;
		$response->ttl                      = 86400;
		$response->version                  = '3.1-RC3';

		return $this->respond_with_validation( $response );
	}

	private function isOpen(): bool {
		$timeframes = Timeframe::getBookable(
			[],
			[],
			date( CB::getInternalDateFormat(), current_time( 'timestamp' ) ),
		);
		return count( $timeframes ) > 0;
	}
}
