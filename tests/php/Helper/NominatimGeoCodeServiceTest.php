<?php

namespace CommonsBooking\Tests\Helper;

use CommonsBooking\Helper\GeoLocation;
use CommonsBooking\Helper\NominatimGeoCodeService;
use CommonsBooking\Tests\BaseTestCase;

/**
 * Tests the Nominatim service against faked HTTP responses (no real network access).
 */
class NominatimGeoCodeServiceTest extends BaseTestCase {

	private ?string $requestedUrl = null;

	private function fakeHttp( $response ): void {
		add_filter(
			'pre_http_request',
			function ( $pre, $args, $url ) use ( $response ) {
				$this->requestedUrl = $url;
				return $response;
			},
			10,
			3
		);
	}

	private static function httpResponse( int $code, string $body ): array {
		return [
			'headers'  => [],
			'body'     => $body,
			'response' => [
				'code'    => $code,
				'message' => '',
			],
			'cookies'  => [],
			'filename' => null,
		];
	}

	public function tearDown(): void {
		remove_all_filters( 'pre_http_request' );
		parent::tearDown();
	}

	public function testSuccess_returnsCoordinates() {
		$this->fakeHttp( self::httpResponse( 200, '[{"lat":"52.4863573","lon":"13.4247667","display_name":"x"}]' ) );

		$result = ( new NominatimGeoCodeService() )->getAddressData( 'Karl-Marx-Straße 1, 12043 Berlin' );

		$this->assertInstanceOf( GeoLocation::class, $result );
		$this->assertSame( 52.4863573, $result->getLatitude() );
		$this->assertSame( 13.4247667, $result->getLongitude() );
		$this->assertStringContainsString( 'nominatim.openstreetmap.org/search', $this->requestedUrl );
		$this->assertStringContainsString( 'format=jsonv2', $this->requestedUrl );
		$this->assertStringContainsString( 'Karl-Marx', urldecode( $this->requestedUrl ) );
	}

	public function testEmptyResult_returnsNull() {
		$this->fakeHttp( self::httpResponse( 200, '[]' ) );
		$this->assertNull( ( new NominatimGeoCodeService() )->getAddressData( 'nowhere' ) );
	}

	public function testWpError_returnsNull() {
		$this->fakeHttp( new \WP_Error( 'http_request_failed', 'boom' ) );
		$this->assertNull( ( new NominatimGeoCodeService() )->getAddressData( 'x' ) );
	}

	public function testServerError_returnsNull() {
		$this->fakeHttp( self::httpResponse( 429, 'Too many requests' ) );
		$this->assertNull( ( new NominatimGeoCodeService() )->getAddressData( 'x' ) );
	}

	public function testInvalidJson_returnsNull() {
		$this->fakeHttp( self::httpResponse( 200, '<html>' ) );
		$this->assertNull( ( new NominatimGeoCodeService() )->getAddressData( 'x' ) );
	}

	public function testRequestFilter_canAddQueryParam() {
		$this->fakeHttp( self::httpResponse( 200, '[{"lat":"1","lon":"2"}]' ) );
		add_filter(
			'commonsbooking_geocode_request',
			function ( $request ) {
				$request['query']['addressdetails'] = 1;
				return $request;
			}
		);

		( new NominatimGeoCodeService() )->getAddressData( 'x' );

		$this->assertStringContainsString( 'addressdetails=1', $this->requestedUrl );
		remove_all_filters( 'commonsbooking_geocode_request' );
	}

	public function testRequestFilter_canChangeEndpoint() {
		$this->fakeHttp( self::httpResponse( 200, '[{"lat":"1","lon":"2"}]' ) );
		add_filter(
			'commonsbooking_geocode_request',
			function ( $request ) {
				$request['url'] = 'https://nominatim.example.org/search';
				return $request;
			}
		);

		( new NominatimGeoCodeService() )->getAddressData( 'x' );

		$this->assertStringStartsWith( 'https://nominatim.example.org/search?', $this->requestedUrl );
		remove_all_filters( 'commonsbooking_geocode_request' );
	}

	public function testRaw_containsFullFirstResult() {
		$this->fakeHttp( self::httpResponse( 200, '[{"lat":"1","lon":"2","address":{"city":"Berlin"}}]' ) );

		$result = ( new NominatimGeoCodeService() )->getAddressData( 'x' );

		$this->assertSame( 'Berlin', $result->getRaw()['address']['city'] );
	}
}
