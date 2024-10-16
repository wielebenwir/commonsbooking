<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Model\Location;
use CommonsBooking\Model\Map;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use PHPUnit\Framework\TestCase;
use SlopeIt\ClockMock\ClockMock;

class MapTest extends CustomPostTypeTest {
	private Map $map;
	private Location $geoLocation;

	public function testGet_locations() {
		//this tests need to take place in the present because only bookable locations are retrieved
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		$locations = $this->map->get_locations( [] );
		$this->assertIsArray( $locations );
		$this->assertNotEmpty( $locations );
		$this->assertArrayHasKey( $this->geoLocation->ID, $locations );
		$this->assertEquals( 50.9413035, $locations[ $this->geoLocation->ID ]['lat'] );
		$this->assertEquals( 6.9581379978318, $locations[ $this->geoLocation->ID ]['lon'] );
		$this->assertEquals( "Location with Geo", $locations[ $this->geoLocation->ID ]['location_name'] );
		$this->assertEquals( "Domkloster 4", $locations[ $this->geoLocation->ID ]['address']['street'] );
		$this->assertEquals( "Köln", $locations[ $this->geoLocation->ID ]['address']['city'] );
		$this->assertEquals( "50667", $locations[ $this->geoLocation->ID ]['address']['zip'] );

		$this->assertEquals( $this->itemId, $locations[ $this->geoLocation->ID ]['items'][0]['id'] );
	}

	public function testIs_json() {
		//valid JSON string
		$this->assertTrue( Map::is_json( '{"key":"value"}' ) );
		//invalid JSON string
		$this->assertFalse( Map::is_json( '{"key":"value"' ) );

	}

	public function testCleanup_location_data() {
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		update_post_meta( $this->geoLocation->ID, COMMONSBOOKING_METABOX_PREFIX . 'location_contact', "Contact with <b> HTML </b> and \n linebreaks" );
		update_post_meta( $this->map->ID, 'show_location_contact', 'on' );
		$locations = $this->map->get_locations( [] );
		$this->assertNotEmpty( $locations );
		$locations = Map::cleanup_location_data( $locations, '<br>' );
		$this->assertEquals( "Contact with  HTML  and <br> linebreaks", $locations[ $this->geoLocation->ID ]['contact'] );
	}

	protected function setUp(): void {
		parent::setUp();
		$this->map         = new Map ( $this->createMap() );
		$this->geoLocation = new Location ( $this->createLocation( "Location with Geo" ) );
		update_post_meta( $this->geoLocation->ID, 'geo_latitude', 50.9413035 );
		update_post_meta( $this->geoLocation->ID, 'geo_longitude', 6.9581379978318 );
		update_post_meta( $this->geoLocation->ID, COMMONSBOOKING_METABOX_PREFIX . 'location_city', 'Köln' );
		update_post_meta( $this->geoLocation->ID, COMMONSBOOKING_METABOX_PREFIX . 'location_country', 'Deutschland' );
		update_post_meta( $this->geoLocation->ID, COMMONSBOOKING_METABOX_PREFIX . 'location_street', 'Domkloster 4' );
		update_post_meta( $this->geoLocation->ID, COMMONSBOOKING_METABOX_PREFIX . 'location_postcode', '50667' );
		$this->createBookableTimeFrameIncludingCurrentDay( $this->geoLocation->ID );
	}


}
