<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Exception\TimeframeInvalidException;

use CommonsBooking\Model\Timeframe;
use CommonsBooking\Model\Location;
use CommonsBooking\Model\Item;
use SlopeIt\ClockMock\ClockMock;

/**
 * @covers \CommonsBooking\Model\Timeframe
 */
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
			strtotime( "+1 day", time() ),
			strtotime( "+3 days", time() )
		));
		try {
			$noItemTF->isValid();
			$this->fail("TimeframeInvalidException was not thrown");
		}
		catch ( TimeframeInvalidException $e ) {
			$this->assertEquals("Item or location is missing. Please set item and location. Timeframe is saved as draft",$e->getMessage());
		}

		$noLocationTF = new Timeframe($this->createTimeframe(
			"",
			$this->itemId,
			strtotime( "+20 day", time() ),
			strtotime( "+25 days", time() )
		));

		try {
			$noLocationTF->isValid();
			$this->fail("TimeframeInvalidException was not thrown");
		}
		catch ( TimeframeInvalidException $e ) {
			$this->assertEquals("Item or location is missing. Please set item and location. Timeframe is saved as draft",$e->getMessage());
		}

		$noStartDateTF = new Timeframe($this->createTimeframe(
			$this->locationId,
			$this->itemId,
			"",
			strtotime( "+10 days", time() )
		));
		try {
			$noStartDateTF->isValid();
			$this->fail("TimeframeInvalidException was not thrown");
		}
		catch (TimeframeInvalidException $e ){
			$this->assertEquals("Startdate is missing. Timeframe is saved as draft. Please enter a start date to publish this timeframe.",$e->getMessage());
		}

		//make sure, that timeframes with manual repetition can be saved without a start date or end date
		$noStartDateManualRep = new Timeframe($this->createTimeframe(
			$this->locationId,
			$this->itemId,
			"",
			"",
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			"on",
			"manual",
			0,
			'08:00 AM',
			'12:00 PM',
			'publish',
			["1","2","3","4","5","6","7"],
			"01-07-2021",
		));
		$this->assertNull( $noStartDateManualRep->isValid() );

		//but also make sure, that timeframes with manual repetition do not have empty manual repetition values
		try {
			$noManualRepValues = new Timeframe( $this->createTimeframe(
				$this->locationId,
				$this->itemId,
				"",
				"",
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				"on",
				"manual",
			) );
			$noManualRepValues->isValid();
			$this->fail("TimeframeInvalidException was not thrown");
		}
		catch (TimeframeInvalidException $e) {
			$this->assertEquals("No dates selected. Please select at least one date. Timeframe is saved as draft.",$e->getMessage());
		}

		$pickupTimeInvalid = new Timeframe($this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( "+10 day", time() ),
			strtotime( "+13 days", time() ),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			"off",
			'w',
			0,
			'09:00 AM',
			null
		));
		try {
			$pickupTimeInvalid->isValid();
			$this->fail("TimeframeInvalidException was not thrown");
		}
		catch ( TimeframeInvalidException $e ) {
			$this->assertEquals( "A pickup time but no return time has been set. Please set the return time.", $e->getMessage() );
		}
		
		$isOverlapping = new Timeframe($this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '+1 day', time() ),
			strtotime( '+2 days', time() ),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			"off"
		));		

		// $this->assertNotEquals( $isOverlapping->getLocation(), $this->validTF->getLocation() );
		$this->assertTrue( $isOverlapping->hasTimeframeDateOverlap( $this->validTF ) );

		// $this->expectException( TimeframeInvalidException::class );
		$isOverlapping->isValid();
	}
	
	public function test_isValid_throwsException() {

		$secondLocation = $this->createLocation("Newest Location", 'publish');

		$isOverlapping = new Timeframe($this->createTimeframe(
			$secondLocation,
			$this->itemId,
			strtotime( '+1 day', time() ),
			strtotime( '+2 days', time() )
		));		

		// $this->assertNotEquals( $isOverlapping->getLocation(), $this->validTF->getLocation() );
		$this->assertTrue( $isOverlapping->hasTimeframeDateOverlap( $this->validTF ) );

		// $this->expectException( TimeframeInvalidException::class );
		try {
			$isOverlapping->isValid();
			$this->fail("TimeframeInvalidException was not thrown");
		} catch (TimeframeInvalidException $e ) {
			$this->assertStringContainsString( "Item is already bookable at another location within the same date range.", $e->getMessage() );
		}
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

	/**
	 * @return void
	 *
	 * @dataProvider providerFormatBookableDate()
	 */
	public function test_formatBookableDate($todayMockDate, $expectedString, $start, $end) {

		// Mocks strtotime
		ClockMock::freeze( new \DateTime( $todayMockDate ) );

		$result = Timeframe::formatBookableDate( $start, $end );

		ClockMock::reset();

		$this->assertEquals( $expectedString, $result );
	}

	/**
	 * Provider for test_formatBookableDate
	 */
	public function providerFormatBookableDate() {
		return array(
			// case: only one day (start = end)
			'Available here on January 24, 2021' => array(
				'2021-01-22',
				'Available here on January 24, 2021',
				strtotime( '2021-01-24' ),
				strtotime( '2021-01-24' ),
			),
			// case: open end
			'Available here from January 24, 2021 (open end)' => array(
				'2021-01-22',
				'Available here from January 24, 2021',
				strtotime( '2021-01-24' ),
				false,
			),
			// case: start passed, open end date
			'Available here permanently' => array(
				'2021-01-25',
				'Available here permanently',
				strtotime( '2021-01-24' ),
				false,
			),
			// case: start and end
			'Available here from January 24, 2021 until January 26, 2021' => array(
				'2021-01-22',
				'Available here from January 24, 2021 until January 26, 2021',
				strtotime( '2021-01-24' ),
				strtotime( '2021-01-26' ),
			),
			// case: start passed, with end date
			'Available here until January 26, 2021' => array(
				'2021-01-25',
				'Available here until January 26, 2021',
				strtotime( '2021-01-24' ),
				strtotime( '2021-01-26' ),
			),
		);

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
			strtotime( "+1 day", time() ),
			strtotime( "+3 days", time() )
		));
	}

	protected function tearDown() : void {
		parent::tearDown(); // TODO: Change the autogenerated stub
	}

}
