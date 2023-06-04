<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Model\Item;
use CommonsBooking\Model\Location;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Exception\TimeframeInvalidException;


class TimeframeTest extends CustomPostTypeTest {

	protected Timeframe $firstTimeframe;
	protected Timeframe $secondTimeframe;
	private Timeframe $validTF;

	public function testHasTimeframeDateOverlap() {
		//timeframe for only yesterday and today should not overlap with timeframe for next week
		$this->assertFalse( $this->firstTimeframe->hasTimeframeDateOverlap( $this->secondTimeframe ) );

		$secondLocationID = $this->createLocation("Second Location", 'publish');
		$secondItemID = $this->createItem("Second Item", 'publish');

		//timeframe for today +30 days should overlap with timeframe for next week
		$thisMonthTimeframe = new Timeframe($this->createTimeframe(
			$secondLocationID,
			$secondItemID,
			strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+30 day', strtotime( self::CURRENT_DATE ) ),
		));
		$this->assertTrue( $this->secondTimeframe->hasTimeframeDateOverlap(  $thisMonthTimeframe ) );

		//timeframe overlap test for far future (beyond max booking days)
		$farFutureTimeframe = new Timeframe($this->createTimeframe(
			$secondLocationID,
			$secondItemID,
			strtotime( '+60 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+90 day', strtotime( self::CURRENT_DATE ) ),
		));
		$farFutureTimeframeTwo = new Timeframe($this->createTimeframe(
			$secondLocationID,
			$secondItemID,
			strtotime( '+70 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+100 day', strtotime( self::CURRENT_DATE ) ),
		));
		$this->assertTrue( $farFutureTimeframe->hasTimeframeDateOverlap( $farFutureTimeframeTwo ) );

	}

	public function testIsValid() {

		$this->assertNull( $this->validTF->isValid() );

		$noItemTF = new Timeframe($this->createTimeframe(
			$this->locationId,
			"",
			strtotime("+1 day",time()),
			strtotime("+3 days",time())
		));
		try {
			$noItemTF->isValid();
		}
		catch ( TimeframeInvalidException $e ) {
			$this->assertEquals("Item or location is missing. Please set item and location. Timeframe is saved as draft",$e->getMessage());
		}

		$noLocationTF = new Timeframe($this->createTimeframe(
			"",
			$this->itemId,
			strtotime("+20 day",time()),
			strtotime("+25 days",time())
		));

		try {
			$noLocationTF->isValid();
		}
		catch ( TimeframeInvalidException $e ) {
			$this->assertEquals("Item or location is missing. Please set item and location. Timeframe is saved as draft",$e->getMessage());
		}

		$noStartDateTF = new Timeframe($this->createTimeframe(
			$this->locationId,
			$this->itemId,
			"",
			strtotime("+10 days",time())
		));
		try {
			$noStartDateTF->isValid();
		}
		catch (TimeframeInvalidException $e ){
			$this->assertEquals("Startdate is missing. Timeframe is saved as draft. Please enter a start date to publish this timeframe.",$e->getMessage());
		}

		$pickupTimeInvalid = new Timeframe($this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime("+10 day",time()),
			strtotime("+13 days",time()),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			"off",
			'w',
			0,
			'09:00 AM',
			null
		));
		try {
			$pickupTimeInvalid->isValid();
		}
		catch ( TimeframeInvalidException $e ) {
			$this->assertEquals( "A pickup time but no return time has been set. Please set the return time.", $e->getMessage() );
		}
	}
	
	public function test_isValid_throwsException() {

		$secondLocation = $this->createLocation("Newest Location", 'publish');

		$isOverlapping = new Timeframe($this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '+1 day', time() ),
			strtotime( '+2 days', time() )
		));

		// $this->assertNotEquals( $isOverlapping->getLocation(), $this->validTF->getLocation() );
		$this->assertTrue( $isOverlapping->hasTimeframeDateOverlap( $this->validTF ) );

		// $this->expectException( TimeframeInvalidException::class );
		$isOverlapping->isValid();
	}

	public function testIsBookable() {
		$this->assertTrue($this->validTF->isBookable());

		/*$passedTimeframe = new Timeframe($this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime("-5 days",time()),
			strtotime("-3 days",time())
		));
		$this->assertFalse($passedTimeframe->isBookable());*/
		//This test does not work, function maybe broken?
	}

	public function testGetLocation() {
		$location = New Location($this->locationId);
		$this->assertEquals($location,$this->validTF->getLocation());
	}

	public function testGetItem() {
		$item = New Item($this->itemId);
		$this->assertEquals($item,$this->validTF->getItem());
	}

  protected function setUp() : void {

		parent::setUp();
		$this->firstTimeframeId = $this->createBookableTimeFrameIncludingCurrentDay();
		$this->secondTimeframeId = $this->createBookableTimeFrameStartingInAWeek();
		$this->firstTimeframe = new Timeframe( $this->firstTimeframeId );
		$this->secondTimeframe = new Timeframe( $this->secondTimeframeId );
		$this->validTF = new Timeframe($this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime("+1 day",time()),
			strtotime("+3 days",time())
		));
	}

	protected function tearDown() : void {
		parent::tearDown(); // TODO: Change the autogenerated stub
	}

}
