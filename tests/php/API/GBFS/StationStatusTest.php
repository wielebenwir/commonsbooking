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
		$stationStatus = $routeObject->prepare_item_for_response($locationObject, null);
		$this->assertEquals(0, $stationStatus->num_bikes_available);


		//the timeframe has ended now, so the station should be empty
		$currDate->modify('+11 days');
		ClockMock::freeze( $currDate );
		$stationStatus = $routeObject->prepare_item_for_response($locationObject, null);
		$this->assertEquals(0, $stationStatus->num_bikes_available);

		//very important for GBFS: when bookings are only allowed with a certain offset (time difference between booking and start of booking), the station should be empty
	    ClockMock::freeze(new \DateTime( self::CURRENT_DATE) );
	    $otherLocationId = $this->createLocation("Other Location",'publish');
		$otherItemId = $this->createItem("Other Item",'publish');
		$timeframeID = $this->createTimeframe(
			$otherLocationId,
			$otherItemId,
			strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+10 days', strtotime( self::CURRENT_DATE ) ),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			"on",
			'd',
			0,
			'8:00 AM',
			'12:00 PM',
			'publish',
			[],
			self::USER_ID,
			3,
			30,
			2
		);
		$stationStatus = $routeObject->prepare_item_for_response(new Location($otherLocationId), null);
		$this->assertEquals(0, $stationStatus->num_bikes_available);
		//remove the offset and the station should have the item
	    update_post_meta($timeframeID, 'booking-startday-offset', 0);
		$stationStatus = $routeObject->prepare_item_for_response(new Location($otherLocationId), null);



	}

	protected function setUp(): void {
		parent::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}
}
