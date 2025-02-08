<?php

namespace CommonsBooking\Tests\API\GBFS;

use CommonsBooking\API\GBFS\StationStatus;
use CommonsBooking\Model\Location;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use SlopeIt\ClockMock\ClockMock;

class StationStatusTest extends CustomPostTypeTest {

	/**
	 * @group failing
	 */
	public function testPrepare_item_for_response() {
		$currDate       = new \DateTimeImmutable( self::CURRENT_DATE );
		$locationObject = new Location( $this->locationId );
		ClockMock::freeze( $currDate );
		$routeObject       = new StationStatus();
		$spanningTimeframe = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			$currDate->modify( '-1 day' )->getTimestamp(),
			$currDate->modify( '+10 days' )->getTimestamp()
		);
		$stationStatus     = $routeObject->prepare_item_for_response( $locationObject, null )->get_data();
		$this->assertEquals( $this->locationId, $stationStatus->station_id );
		$this->assertEquals( 1, $stationStatus->num_bikes_available );
		$this->assertTrue( $stationStatus->is_installed );
		$this->assertTrue( $stationStatus->is_renting );
		$this->assertTrue( $stationStatus->is_returning );
		$this->assertEquals( time(), $stationStatus->last_reported );

		// now let's book the current day and check, that the station is empty
		$tf    = $this->createConfirmedBookingStartingToday();
		$model = new Timeframe( $tf );
		// echo "This is a booking: " . $model->getStartDateDateTime()->format( 'Y-m-d\TH:i:sP' ) . " " . $model->getEndDateDateTime()->format( 'Y-m-d\TH:i:sP' );
		$this->assertEquals( 0, $routeObject->prepare_item_for_response( $locationObject, null )->num_bikes_available );
		// $this->assertEquals( 0, $routeObject->prepare_item_for_response( $locationObject, null )->get_data()->num_bikes_available );

		// the timeframe has ended now, so the station should be empty
		$currDate->modify( '+11 days' );
		ClockMock::freeze( $currDate );
		$this->assertEquals( 0, $routeObject->prepare_item_for_response( $locationObject, null )->get_data()->num_bikes_available );

		// very important for GBFS: when bookings are only allowed with a certain offset (time difference between booking and start of booking), the station should be empty
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		$otherLocationId = $this->createLocation( 'Other Location', 'publish' );
		$otherItemId     = $this->createItem( 'Other Item', 'publish' );
		$timeframeID     = $this->createTimeframe(
			$otherLocationId,
			$otherItemId,
			strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+10 days', strtotime( self::CURRENT_DATE ) ),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			'on',
			'd',
			0,
			'8:00 AM',
			'12:00 PM',
			'publish',
			[],
			'',
			self::USER_ID,
			3,
			30,
			2
		);
		$this->assertEquals( 0, $routeObject->prepare_item_for_response( new Location( $otherLocationId ), null )->get_data()->num_bikes_available );
		// remove the offset and the station should have the item
		update_post_meta( $timeframeID, 'booking-startday-offset', 0 );
		$this->assertEquals( 1, $routeObject->prepare_item_for_response( new Location( $otherLocationId ), null )->get_data()->num_bikes_available );
	}

	public function testPrepare_item_for_response_hourly() {
		$currDate = new \DateTime( self::CURRENT_DATE );
		$currDate->setTime( 8, 0, 0 );
		$locationObject = new Location( $this->locationId );
		ClockMock::freeze( $currDate );
		$routeObject     = new StationStatus();
		$hourlyTimeframe = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+2 days', strtotime( self::CURRENT_DATE ) ),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			'off',
			'd',
			1,
			'8:00 AM',
			'11:59 PM'
		);
		$this->assertEquals( 1, $routeObject->prepare_item_for_response( $locationObject, null )->get_data()->num_bikes_available );

		// before 08:00AM the bike is not available
		$currDate->setTime( 6, 0, 0 );
		ClockMock::freeze( $currDate );
		$this->assertEquals( 0, $routeObject->prepare_item_for_response( $locationObject, null )->get_data()->num_bikes_available );

		// now let's book two hours out of the timeframe and check that the station is empty for those two hours
		$startBooking = new \DateTime( self::CURRENT_DATE );
		$startBooking->setTime( 10, 0, 0 );
		$endBooking = clone $startBooking;
		$endBooking->setTime( 13, 0, 0 );
		$this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '10:00 AM', strtotime( self::CURRENT_DATE ) ),
			strtotime( '01:00 PM', strtotime( self::CURRENT_DATE ) ),
			'10:00 AM',
			'01:00 PM'
		);
		ClockMock::freeze( $startBooking );
		$this->assertEquals( 0, $routeObject->prepare_item_for_response( $locationObject, null )->get_data()->num_bikes_available );
		$startBooking->modify( '+1 hour' );
		ClockMock::freeze( $startBooking );
		$this->assertEquals( 0, $routeObject->prepare_item_for_response( $locationObject, null )->get_data()->num_bikes_available );
		ClockMock::freeze( $endBooking );
		$this->assertEquals( 1, $routeObject->prepare_item_for_response( $locationObject, null )->get_data()->num_bikes_available );
	}

	protected function setUp(): void {
		parent::setUp();
		date_default_timezone_set( 'Europe/Berlin' );
	}

	protected function tearDown(): void {
		parent::tearDown();
		date_default_timezone_set( 'UTC' );
	}
}
