<?php

namespace CommonsBooking\Tests\API;

use CommonsBooking\Helper\NominatimGeoCodeService;
use CommonsBooking\Plugin;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Tests\Helper\GeoHelperTest;

/**
 * Abstract Unit Test case, which initializes REST functionality
 */
class CB_REST_UnitTestCase extends \WP_UnitTestCase {

	protected $server;

	protected $ENDPOINT;

	public function setUp() : void {
		parent::setUp();
		GeoHelperTest::setUpGeoHelperMock( $this );

		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$this->server = $wp_rest_server = new \WP_REST_Server;

		// Enables api
		Settings::updateOption( 'commonsbooking_options_api', 'api-activated', 'on' );
		Settings::updateOption( 'commonsbooking_options_api', 'apikey_not_required', 'on' );

		// Registers routes (via rest_api_init hook)
		( new Plugin() )->initRoutes();

		// Applies hook
		do_action( 'rest_api_init' );
	}
}