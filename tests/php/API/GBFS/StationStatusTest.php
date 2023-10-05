<?php

namespace CommonsBooking\Tests\API\GBFS;

use CommonsBooking\API\GBFS\StationStatus;
use CommonsBooking\Model\Day;
use CommonsBooking\Model\Location;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use SlopeIt\ClockMock\ClockMock;

class StationStatusTest extends CustomPostTypeTest
{

    public function testPrepare_item_for_response()
    {

    }

	public function testGetBookableItems() {
		$currDate = new \DateTime( self::CURRENT_DATE );
		$locationObject = new Location($this->locationId);
		ClockMock::freeze( $currDate );
		$routeObject = new StationStatus();
		$spanningTimeframe = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+10 days', strtotime( self::CURRENT_DATE ) )
		);
		$stationStatus = $routeObject->prepare_item_for_response($locationObject, null);
		$this->assertEquals($this->locationId, $stationStatus->station_id);
		$this->assertEquals(1, $stationStatus->num_bikes_available);
		$this->assertTrue($stationStatus->is_installed);
		$this->assertTrue($stationStatus->is_renting);
		$this->assertTrue($stationStatus->is_returning);
		$this->assertEquals(current_time('timestamp'), $stationStatus->last_reported);

		//now let's book the current day and check, that the station is empty
		$this->createConfirmedBookingStartingToday();
		//we set the time to lunch, because the booking start at 08:00AM. If we set the time to 00:00AM, the booking would not be recognized
		//This is regardless of the fact if more than one booking is available today or not
		$currDate->setTime(12,0,0);
		ClockMock::freeze( $currDate );
		$stationStatus = $routeObject->prepare_item_for_response($locationObject, null);
		$this->assertEquals(0, $stationStatus->num_bikes_available);


		//the timeframe has ended now, so the station should be empty
		$currDate->modify('+11 days');
		ClockMock::freeze( $currDate );
		$stationStatus = $routeObject->prepare_item_for_response($locationObject, null);
		$this->assertEquals(0, $stationStatus->num_bikes_available);

	}

	protected function setUp(): void {
		parent::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}
}
