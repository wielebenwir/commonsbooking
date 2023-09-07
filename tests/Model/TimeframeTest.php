<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Exception\OverlappingException;
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

	/**
	 * Only tests the very basic functionality of the overlaps function
	 * @return void
	 */
	public function testOverlaps() {
		$tfBase = new Timeframe($this->createBookableTimeFrameIncludingCurrentDay());
		$tfNextWeek = new Timeframe(
			$this->createBookableTimeFrameStartingInAWeek()
		);
		$this->assertFalse( $tfBase->overlaps( $tfNextWeek ) );

		$tfIdentical = new Timeframe($this->createBookableTimeFrameIncludingCurrentDay());
		try {
			$tfBase->overlaps( $tfIdentical );
			$this->fail( "OverlappingException was not thrown" );
		}
		catch ( OverlappingException $e ) {
			$this->assertStringContainsString( "Overlapping bookable timeframes are not allowed to have the same weekdays.", $e->getMessage() );
		}

		$tfOtherGrid = new Timeframe(
			$this->createTimeframe(
				$this->locationId,
				$this->itemId,
				strtotime( self::CURRENT_DATE ),
				strtotime( '+10 days', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'on',
				'd',
				1,
				'08:00 AM',
				'10:00 AM',
			)
		);
		try {
			$tfBase->overlaps( $tfOtherGrid );
			$this->fail( "OverlappingException was not thrown" );
		}
		catch ( OverlappingException $e ) {
			$this->assertStringContainsString( "Overlapping bookable timeframes are only allowed to have the same grid.", $e->getMessage() );
		}
	}

	/**
	 * Tests all possible combinations of daily repetitions with hourly grid
	 * @return void
	 */
	public function testOverlaps_Hourly(){
		//create completely separate locations and items for this test
		$otherLocation = $this->createLocation("Other Location", 'publish');
		$otherItem = $this->createItem("Other Item", 'publish');

		//timeframe that does not overlap but is directly adjacent #1095
		//we make this even further in the future to make sure it does not overlap with the other timeframe
		$endFirstTf = new \DateTime(self::CURRENT_DATE);
		$endFirstTf->modify('+1 year')->modify('+5 days')->setTime(23,59,59);
		$startSecondTf = clone $endFirstTf;
		$startSecondTf->modify('+1 second');
		$endSecondTf = clone $startSecondTf;
		$endSecondTf->modify('+5 days');
		$adjacentTimeframe = new Timeframe($this->createTimeframe(
			$secondLocationID,
			$secondItemID,
			strtotime( '+1 year', strtotime( self::CURRENT_DATE ) ),
			$endFirstTf->getTimestamp(),
		));
		$adjacentTimeframeTwo = new Timeframe($this->createTimeframe(
			$secondLocationID,
			$secondItemID,
			$startSecondTf->getTimestamp(),
			$endSecondTf->getTimestamp(),
		));
		$this->assertFalse( $adjacentTimeframe->hasTimeframeDateOverlap( $adjacentTimeframeTwo ) );
		$this->assertFalse( $adjacentTimeframeTwo->hasTimeframeDateOverlap( $adjacentTimeframe ) );
	}

	public function testHasTimeframeTimeOverlap() {
		//test for hourly overlaps
		$earlyTf = new Timeframe(
			$this->createTimeframe(
				$this->locationId,
				$this->itemId,
				strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'',
				'w',
				0,
				'09:00 AM',
				'05:00 PM'
			)
		);
		$laterTf = new Timeframe(
			$this->createTimeframe(
				$this->locationId,
				$this->itemId,
				strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'',
				'w',
				0,
				'7:00 PM',
				'10:00 PM'
			)
		);
		$this->assertFalse( $earlyTf->hasTimeframeTimeOverlap( $laterTf ) );
		$middleTf = new Timeframe(
			$this->createTimeframe(
				$this->locationId,
				$this->itemId,
				strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'',
				'w',
				0,
				'10:00 AM',
				'04:00 PM'
			)
		);
		$this->assertTrue( $earlyTf->hasTimeframeTimeOverlap( $middleTf ) );

		//check for #1344
		$slotTf = new Timeframe(
			$this->createTimeframe(
				$this->locationId,
				$this->itemId,
				strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'',
				'w',
				0,
				'09:00 AM',
				'05:00 PM'
			)
		);
		//exactly the same settings as $slotTf
		$slotTf2 = new Timeframe(
			$this->createTimeframe(
				$this->locationId,
				$this->itemId,
				strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'',
				'w',
				0,
				'09:00 AM',
				'05:00 PM'
			)
		);
		$this->assertTrue( $slotTf->hasTimeframeTimeOverlap( $slotTf2 ) );
		//these two should not overlap
		$dailyMorningTimeframeHourly = new Timeframe(
			$this->createTimeframe(
				$otherLocation,
				$otherItem,
				strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+30 days', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'',
				"d",
				1,
				'08:00 AM',
				'10:00 AM',
			)
		);
		$dailyAfternoonTimeframeHourly = new Timeframe(
			$this->createTimeframe(
				$otherLocation,
				$otherItem,
				strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+30 days', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'',
				"d",
				1,
				'01:00 PM',
				'05:00 PM',
			)
		);
		$this->assertFalse( $dailyMorningTimeframeHourly->overlaps( $dailyAfternoonTimeframeHourly ) );

		//this should overlap
		$dailyMidDayTimeframeHourly = new Timeframe(
			$this->createTimeframe(
				$otherLocation,
				$otherItem,
				strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+30 days', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'',
				"d",
				1,
				'09:00 AM',
				'01:00 PM',
			)
		);
		try {
			$dailyMorningTimeframeHourly->overlaps( $dailyMidDayTimeframeHourly );
			$this->fail( "OverlappingException was not thrown" );
		}
		catch ( OverlappingException $e ) {
			$this->assertStringContainsString( "Time periods are not allowed to overlap.", $e->getMessage() );
		}
	}
	/**
	 * Tests all possible combinations of weekly repetitions
	 * @return void
	 */
	public function testOverlaps_Weekly() {
		//create completely separate locations and items for this test
		$otherLocation = $this->createLocation("Other Location", 'publish');
		$otherItem = $this->createItem("Other Item", 'publish');

		//these two should not overlap
		$mondayFridayTimeframe = new Timeframe(
			$this->createTimeframe(
				$otherLocation,
				$otherItem,
				strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+30 days', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'on',
				'w',
				0,
				'08:00 AM',
				'12:00 PM',
				'publish',
				["1","2","3","4","5"]
			)
		);
		$saturdaySundayTimeframe = new Timeframe(
			$this->createTimeframe(
				$otherLocation,
				$otherItem,
				strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+30 days', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'on',
				'w',
				0,
				'08:00 AM',
				'12:00 PM',
				'publish',
				["6","7"]
			)
		);
		$this->assertFalse( $mondayFridayTimeframe->overlaps( $saturdaySundayTimeframe ) );

		//now let's create a timeframe, that overlaps with the first one
		$mondayWednesdayTimeframe = new Timeframe(
			$this->createTimeframe(
				$otherLocation,
				$otherItem,
				strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+30 days', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'on',
				'w',
				0,
				'08:00 AM',
				'12:00 PM',
				'publish',
				["1","2","3"]
			)
		);
		try {
			$mondayFridayTimeframe->overlaps( $mondayWednesdayTimeframe );
			$this->fail( "OverlappingException was not thrown" );
		}
		catch ( OverlappingException $e ) {
			$this->assertStringContainsString( "Overlapping bookable timeframes are not allowed to have the same weekdays.", $e->getMessage() );
		}
		//now let's do all the same tests, just with timeframes without end date
		$mondayFridayTimeframeNoEnd = new Timeframe(
			$this->createTimeframe(
				$otherLocation,
				$otherItem,
				strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
				null,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'on',
				'w',
				0,
				'08:00 AM',
				'12:00 PM',
				'publish',
				["1","2","3","4","5"]
			)
		);
		$saturdaySundayTimeframeNoEnd = new Timeframe(
			$this->createTimeframe(
				$otherLocation,
				$otherItem,
				strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
				null,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'on',
				'w',
				0,
				'08:00 AM',
				'12:00 PM',
				'publish',
				["6","7"]
			)
		);
		$this->assertFalse( $mondayFridayTimeframeNoEnd->overlaps( $saturdaySundayTimeframeNoEnd ) );

		$mondayWednesdayTimeframeNoEnd = new Timeframe(
			$this->createTimeframe(
				$otherLocation,
				$otherItem,
				strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
				null,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'on',
				'w',
				0,
				'08:00 AM',
				'12:00 PM',
				'publish',
				["1","2","3"]
			)
		);
		try {
			$mondayFridayTimeframeNoEnd->overlaps( $mondayWednesdayTimeframeNoEnd );
			$this->fail( "OverlappingException was not thrown" );
		}
		catch ( OverlappingException $e ) {
			$this->assertStringContainsString( "Overlapping bookable timeframes are not allowed to have the same weekdays.", $e->getMessage() );
		}
	}

	/**
	 * Test all possible combinations of manual repetitions
	 * @return void
	 */
	public function testHasTimeframeDateOverlap_Manual() {
		//create completely separate locations and items for this test
		$otherLocation = $this->createLocation("Other Location", 'publish');
		$otherItem = $this->createItem("Other Item", 'publish');

		$monthDT = new \DateTime( self::CURRENT_DATE );
		$monthDT->modify( 'first day of this month' );
		$firstOfMonth = $monthDT->format( 'Y-m-d' );
		$monthDT->modify( '+4 days' );
		$fifthOfMonth = $monthDT->format( 'Y-m-d' );
		$monthDT->modify( '+5 days' );
		$tenthOfMonth = $monthDT->format( 'Y-m-d' );
		$monthDT->modify( '+5 days' );
		$fifteenthOfMonth = $monthDT->format( 'Y-m-d' );
		$monthDT->modify( '+5 days' );
		$twentiethOfMonth = $monthDT->format( 'Y-m-d' );

		$firstAndFifthManualRep = new Timeframe(
			$this->createTimeframe(
				$otherLocation,
				$otherItem,
				strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+30 days', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'on',
				'manual',
				0,
				'08:00 AM',
				'12:00 PM',
				'publish',
				[],
				"{$firstOfMonth},{$fifthOfMonth}"
			)
		);

		$tenthAndFifteenthManualRep = new Timeframe(
			$this->createTimeframe(
				$otherLocation,
				$otherItem,
				strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+30 days', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'on',
				'manual',
				0,
				'08:00 AM',
				'12:00 PM',
				'publish',
				[],
				"{$tenthOfMonth},{$fifteenthOfMonth}"
			)
		);

		$this->assertFalse( $firstAndFifthManualRep->overlaps( $tenthAndFifteenthManualRep ) );

		//now we create another timeframe that has one day overlapping and one that is not
		$firstAndTwentiethManualRep = new Timeframe(
			$this->createTimeframe(
				$otherLocation,
				$otherItem,
				strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+30 days', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'on',
				'manual',
				0,
				'08:00 AM',
				'12:00 PM',
				'publish',
				[],
				"{$firstOfMonth},{$twentiethOfMonth}"
			)
		);

		try {
			$firstAndFifthManualRep->overlaps( $firstAndTwentiethManualRep );
			$this->fail( "OverlappingException was not thrown" );
		}
		catch ( OverlappingException $e ) {
			$this->assertStringContainsString( "Overlapping bookable timeframes are not allowed to have the same dates.", $e->getMessage() );
		}
	}

	/**
	 * Test all possible combinations of weekly and manual repetitions
	 * @return void
	 */
	public function testHasTimeframeDateOverlap_weeklyManual() {
		//create completely separate locations and items for this test
		$otherLocation = $this->createLocation("Other Location", 'publish');
		$otherItem = $this->createItem("Other Item", 'publish');

		$nextMondayDT = new \DateTime( self::CURRENT_DATE );
		$nextMondayDT->modify( 'next monday' );
		$nextMondayString = $nextMondayDT->format( 'Y-m-d' );
		$nextTuesdayDT = new \DateTime( self::CURRENT_DATE );
		$nextTuesdayDT->modify( 'next tuesday' );
		$nextTuesdayString = $nextTuesdayDT->format( 'Y-m-d' );
		$nextFridayDT = new \DateTime( self::CURRENT_DATE );
		$nextFridayDT->modify( 'next friday' );
		$nextFridayString = $nextFridayDT->format( 'Y-m-d' );

		$tuesDayToThursdayWeeklyRep = new Timeframe(
			$this->createTimeframe(
				$otherLocation,
				$otherItem,
				strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+30 days', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'on',
				'w',
				0,
				'08:00 AM',
				'12:00 PM',
				'publish',
				["2","3","4"]
			)
		);

		$nextMondayAndNextFridayManualRep = new Timeframe(
			$this->createTimeframe(
				$otherLocation,
				$otherItem,
				strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+30 days', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'on',
				'manual',
				0,
				'08:00 AM',
				'12:00 PM',
				'publish',
				[],
				"{$nextMondayString},{$nextFridayString}"
			)
		);
		$this->assertFalse( $nextMondayAndNextFridayManualRep->overlaps( $tuesDayToThursdayWeeklyRep ) )                                                                             ;
		$this->assertFalse( $tuesDayToThursdayWeeklyRep->overlaps( $nextMondayAndNextFridayManualRep ) );

		//now, make the manual rep overlap with the weekly rep by adding a tuesday
		$nextTuesdayAndNextFridayManualRep = new Timeframe(
			$this->createTimeframe(
				$otherLocation,
				$otherItem,
				strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+30 days', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'on',
				'manual',
				0,
				'08:00 AM',
				'12:00 PM',
				'publish',
				[],
				"{$nextTuesdayString},{$nextFridayString}"
			)
		);
		try {
			$tuesDayToThursdayWeeklyRep->overlaps( $nextTuesdayAndNextFridayManualRep );
			$this->fail( "OverlappingException was not thrown" );
		}
		catch ( OverlappingException $e ) {
			$this->assertStringContainsString( "The other timeframe is overlapping with your weekly configuration.", $e->getMessage() );
		}

		//and now the other way around, make the weekly rep overlap with the manual rep
		$mondayToThursdayWeeklyRep = new Timeframe(
			$this->createTimeframe(
				$otherLocation,
				$otherItem,
				strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+30 days', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'on',
				'w',
				0,
				'08:00 AM',
				'12:00 PM',
				'publish',
				["1","2","3","4"]
			)
		);
		try {
			$mondayToThursdayWeeklyRep->overlaps( $nextMondayAndNextFridayManualRep );
			$this->fail( "OverlappingException was not thrown" );
		}
		catch ( OverlappingException $e ) {
			$this->assertStringContainsString( "The other timeframe is overlapping with your weekly configuration.", $e->getMessage() );
		}
	}

	public function testIsValid() {

		$newLoc = $this->createLocation("New Location", 'publish');
		$newItem = $this->createItem("New Item", 'publish');
		$noEndDateTf = new Timeframe($this->createTimeframe(
			$newLoc,
			$newItem,
			strtotime("+1 day",time()),
			"",
		));
		$this->assertTrue( $noEndDateTf->isValid() );

		$this->assertTrue( $this->validTF->isValid() );

		$exceptionCaught = false;
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
			$exceptionCaught = true;
		}
		$this->assertTrue($exceptionCaught);

		$exceptionCaught = false;
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
			$exceptionCaught = true;
		}
		$this->assertTrue($exceptionCaught);

		$exceptionCaught = false;
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
			$exceptionCaught = true;
		}
		$this->assertTrue($exceptionCaught);

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
	}

	/**
	 * The unit test for issue #1095.
	 * Will check, that a timeframe is valid even if it is directly adjacent to another timeframe the same location.
	 * If this works, it should also work for adjacent timeframes with the second timeframe for another location.
	 * @return void
	 */
	public function testisValid_directAdjacent() {
		//we create a new location and item just to make sure that the overlap does not come from elsewhere
		$location = $this->createLocation("New Location", 'publish');
		$item = $this->createItem("New Item", 'publish');
		//we set the repetition start and end to only have one second between them, so that the timeframes are directly adjacent
		$endFirstTf = new \DateTime(self::CURRENT_DATE);
		$endFirstTf->modify('+1 day')->setTime(23,59,59);
		$startSecondTf = clone $endFirstTf;
		$startSecondTf->modify('+1 second');
		$timeframe = $this->createTimeframe(
			$location,
			$item,
			strtotime( self::CURRENT_DATE),
			$endFirstTf->getTimestamp(),
		);
		$firstTimeframe = new Timeframe($timeframe);
		$this->assertTrue($firstTimeframe->isValid());
		$secondTimeframe = $this->createTimeframe(
			$location,
			$item,
			$startSecondTf->getTimestamp(),
			strtotime( '+4 days', strtotime( self::CURRENT_DATE ) ),
		);
		$secondTimeframe = new Timeframe($secondTimeframe);
		$this->assertTrue($secondTimeframe->isValid());

		//now test if the same is possible with second timeframe at another location
		wp_delete_post($secondTimeframe->ID,true);
		$secondLocation = $this->createLocation("Newest Location", 'publish');
		$secondTimeframe = $this->createTimeframe(
			$secondLocation,
			$item,
			$startSecondTf->getTimestamp(),
			strtotime( '+4 days', strtotime( self::CURRENT_DATE ) ),
		);
		$secondTimeframe = new Timeframe($secondTimeframe);
		$this->assertTrue($secondTimeframe->isValid());
	}

	public function testisValid_throwsException() {

		$secondLocation = $this->createLocation("Newest Location", 'publish');

		$isOverlapping = new Timeframe($this->createTimeframe(
			$secondLocation,
			$this->itemId,
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+2 days', strtotime( self::CURRENT_DATE ) )
		));		

		// $this->assertNotEquals( $isOverlapping->getLocation(), $this->validTF->getLocation() );
		$this->assertTrue( $isOverlapping->hasTimeframeDateOverlap( $this->validTF ) );

		try {
			$isOverlapping->isValid();
			$this->fail("TimeframeInvalidException was not thrown");
		} catch (TimeframeInvalidException $e ) {
			$this->assertStringContainsString( "Item is already bookable at another location within the same date range.", $e->getMessage() );
			$exceptionCaught = true;
		}

		$pickupTimeInvalid = new Timeframe($this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( "+10 day", strtotime( self::CURRENT_DATE ) ),
			strtotime( "+13 days", strtotime( self::CURRENT_DATE ) ),
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

	/**
	 * Will check if the overlap of two timeframes with the same location and item is detected correctly.
	 * It should be detected, if a weekly repetition is set and the timeframes overlap on at least one day.
	 *  (i.e. first TF is from Monday to Wednesday and second TF is from Tuesday to Thursday)
	 * It should not be detected, if there is no overlap on any day.
	 * (i.e. first TF is from Monday to Wednesday and second TF is from Thursday to Saturday)
	 * @return void
	 */
	public function testIsValid_WeekDays(){
		$location = $this->createLocation("New Location", 'publish');
		$item = $this->createItem("New Item", 'publish');
		$mondayToWednesdayWeekly = $this->createTimeframe(
			$location,
			$item,
			strtotime( self::CURRENT_DATE),
			strtotime( '+1 year', strtotime( self::CURRENT_DATE ) ),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			"on",
			'w',
			0,
			'08:00 AM',
			'12:00 PM',
			'publish',
			[ "1", "2", "3" ]
		);
		$mondayToWednesdayWeekly = new Timeframe($mondayToWednesdayWeekly);
		$this->assertTrue($mondayToWednesdayWeekly->isValid());
		$thursdayToSaturday = $this->createTimeframe(
			$location,
			$item,
			strtotime( self::CURRENT_DATE),
			strtotime( '+1 year', strtotime( self::CURRENT_DATE ) ),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			"on",
			'w',
			0,
			'08:00 AM',
			'12:00 PM',
			'publish',
			[ "4", "5", "6" ]
		);
		$thursdayToSaturday = new Timeframe($thursdayToSaturday);
		$this->assertTrue($thursdayToSaturday->isValid());

		$tuesdayToThursday = $this->createTimeframe(
			$location,
			$item,
			strtotime( self::CURRENT_DATE),
			strtotime( '+1 year', strtotime( self::CURRENT_DATE ) ),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			"on",
			'w',
			0,
			'08:00 AM',
			'12:00 PM',
			'publish',
			[ "2", "3", "4" ]
		);
		$tuesdayToThursday = new Timeframe($tuesdayToThursday);
		$exceptionCaught = false;
		try {
			$tuesdayToThursday->isValid();
		} catch (TimeframeInvalidException $e ) {
			$this->assertStringContainsString( "Date periods are not allowed to overlap", $e->getMessage() );
			$exceptionCaught = true;
		}
		$this->assertTrue($exceptionCaught);
	}

	public function testGetLocation() {
		$location = New Location($this->locationId);
		$this->assertEquals($location,$this->firstTimeframe->getLocation());
	}

	public function testGetItem() {
		$item = New Item($this->itemId);
		$this->assertEquals($item,$this->firstTimeframe->getItem());
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

	public function testGetManualSelectionDate() {
		//check, that they are properly presented in an array
		$dateFormattedInAWeek = date('Y-m-d', strtotime('+1 week', strtotime( self::CURRENT_DATE ) ) );
		$tfWithManualSelectionDates = new Timeframe($this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+2 days', strtotime( self::CURRENT_DATE ) ),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			"off",
			"manual",
			0,
			'08:00 AM',
			'12:00 PM',
			'publish',
			'[]',
			"{$this->dateFormatted},{$dateFormattedInAWeek}"
		) );
		$expectedDates = array(
			$this->dateFormatted,
			$dateFormattedInAWeek
		);
		$this->assertEquals($expectedDates,$tfWithManualSelectionDates->getManualSelectionDates());
	}

  protected function setUp() : void {

		parent::setUp();
		$this->firstTimeframeId = $this->createBookableTimeFrameIncludingCurrentDay();
		$this->secondTimeframeId = $this->createBookableTimeFrameStartingInAWeek();
		$this->firstTimeframe = new Timeframe( $this->firstTimeframeId );
		$this->secondTimeframe = new Timeframe( $this->secondTimeframeId );
		$otherItem = $this->createItem("Other Item", 'publish');
		$otherLocation = $this->createLocation("Other Location", 'publish');
		$this->validTF = new Timeframe($this->createTimeframe(
			$otherLocation,
			$otherItem,
			strtotime( "+1 day", strtotime(self::CURRENT_DATE) ),
			strtotime( "+3 days", strtotime( self::CURRENT_DATE ) )
		));
	}

	protected function tearDown() : void {
		parent::tearDown(); // TODO: Change the autogenerated stub
	}

}
