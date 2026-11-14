<?php

namespace CommonsBooking\Tests\API;

use CommonsBooking\Helper\NominatimGeoCodeService;
use CommonsBooking\Plugin;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Tests\Helper\GeoHelperTest;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;

/**
 * Abstract Unit Test case, which initializes REST functionality
 */
class CB_REST_UnitTestCase extends \WP_UnitTestCase {

	// prefix of $id used in the Commons-API schemas (URL of the Github repo)
	const SCHEMA_URL = 'https://github.com/wielebenwir/commons-api/blob/master/';

	protected $server;

	protected $ENDPOINT;

	public function setUp(): void {
		parent::setUp();
		GeoHelperTest::setUpGeoHelperMock( $this );

		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$this->server = $wp_rest_server = new \WP_REST_Server();

		// Enables api
		Settings::updateOption( 'commonsbooking_options_api', 'api-activated', 'on' );
		Settings::updateOption( 'commonsbooking_options_api', 'apikey_not_required', 'on' );

		// Registers routes (via rest_api_init hook)
		( new Plugin() )->initRoutes();

		// Applies hook
		do_action( 'rest_api_init' );
	}

	/**
	 * Asserts that a route response matches one of the Commons-API JSON schemas.
	 *
	 * @param mixed  $data       Response data (as returned by WP_REST_Response::get_data()).
	 * @param string $schemaFile File name inside includes/commons-api-json-schema/.
	 */
	protected function assertMatchesSchema( $data, string $schemaFile ): void {
		$schemaPath = COMMONSBOOKING_PLUGIN_DIR . 'includes/commons-api-json-schema/';

		$validator = new Validator();
		$validator->resolver()->registerPrefix( self::SCHEMA_URL, $schemaPath );

		// normalize to plain stdClass/array structures
		$data   = json_decode( wp_json_encode( $data ) );
		$result = $validator->validate( $data, file_get_contents( $schemaPath . $schemaFile ) );

		$this->assertTrue(
			$result->isValid(),
			$result->hasError()
				? wp_json_encode( ( new ErrorFormatter() )->formatOutput( $result->error(), 'basic' ), JSON_PRETTY_PRINT )
				: ''
		);
	}
}
