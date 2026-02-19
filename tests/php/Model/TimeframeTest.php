<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Exception\OverlappingException;
use CommonsBooking\Exception\TimeframeInvalidException;
use CommonsBooking\Model\Item;
use CommonsBooking\Model\Location;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use SlopeIt\ClockMock\ClockMock;

/**
 * @covers \CommonsBooking\Model\Timeframe
 */
class TimeframeTest extends CustomPostTypeTest {

	protected Timeframe $firstTimeframe;
	protected Timeframe $secondTimeframe;
	private Timeframe $validTF;
	private Location $firstLocation;
	private Location $otherLocation;
	private Item $firstItem;
	private Item $otherItem;

	public function testHasTimeframeDateOverlap() {
		// timeframe for only yesterday and today should not overlap with timeframe for next week
		$this->assertFalse( $this->firstTimeframe->hasTimeframeDateOverlap( $this->secondTimeframe ) );

		$secondLocationID = $this->createLocation( 'Second Location', 'publish' );
		$secondItemID     = $this->createItem( 'Second Item', 'publish' );

		// timeframe for today +30 days should overlap with timeframe for next week
		$thisMonthTimeframe = new Timeframe(
			$this->createTimeframe(
				$secondLocationID,
				$secondItemID,
				strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+30 day', strtotime( self::CURRENT_DATE ) ),
			)
		);
		$this->assertTrue( $this->secondTimeframe->hasTimeframeDateOverlap( $thisMonthTimeframe ) );

		// timeframe overlap test for far future (beyond max booking days)
		$farFutureTimeframe    = new Timeframe(
			$this->createTimeframe(
				$secondLocationID,
				$secondItemID,
				strtotime( '+60 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+90 day', strtotime( self::CURRENT_DATE ) ),
			)
		);
		$farFutureTimeframeTwo = new Timeframe(
			$this->createTimeframe(
				$secondLocationID,
				$secondItemID,
				strtotime( '+70 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+100 day', strtotime( self::CURRENT_DATE ) ),
			)
		);
		$this->assertTrue( $farFutureTimeframe->hasTimeframeDateOverlap( $farFutureTimeframeTwo ) );

		// timeframe without enddate should overlap with timeframe with enddate
		$noEndDate = new Timeframe(
			$this->createTimeframe(
				$secondLocationID,
				$secondItemID,
				strtotime( self::CURRENT_DATE ),
				null,
			)
		);
		$this->assertTrue( $noEndDate->hasTimeframeDateOverlap( $thisMonthTimeframe ) );
		// and the other way around
		$this->assertTrue( $thisMonthTimeframe->hasTimeframeDateOverlap( $noEndDate ) );
	}

	/**
	 * This will provide all of the possible grid / slot combinations and if they would be considered as valid IF the timeframe overlaps on the same day
	 * isValid determines if the timeframes should be considered as valid when the repetition is set to the same day
	 * @return array[]
	 */
	public function getBookableTimeframeSameDayCombos(): array {
		// two timeframes, that have slots on the same day, but they do not overlap
		$nonOverlappingSlots = [
			'isValid' => true,
			'tf1' => [
				'grid'    => '0',
				'fullDay' => 'off',
				'start_time' => '08:00 AM',
				'end_time' => '10:00 AM',
			],
			'tf2' => [
				'grid'    => '0',
				'fullDay' => 'off',
				'start_time' => '01:00 PM',
				'end_time' => '11:59 PM',
			],
		];

		// two timeframes, that have slots directly adjacent to each other, but they do not overlap
		$nonOverlappingSlotsDirectlyAdjacent = [
			'isValid' => true,
			'tf1' => [
				'grid'    => '0',
				'fullDay' => 'off',
				'start_time' => '08:00 AM',
				'end_time' => '10:00 AM',
			],
			'tf2' => [
				'grid'    => '0',
				'fullDay' => 'off',
				'start_time' => '10:00 AM',
				'end_time' => '11:59 PM',
			],
		];

		// two timeframes, that have hourly bookings on the same day, but do not overlap
		$nonOverlappingHourly = [
			'isValid' => true,
			'tf1' => [
				'grid'    => '1',
				'fullDay' => 'off',
				'start_time' => '08:00 AM',
				'end_time' => '10:00 AM',
			],
			'tf2' => [
				'grid'  => '1',
				'fullDay' => 'off',
				'start_time' => '10:00 AM',
				'end_time' => '12:00 PM',
			],
		];

		// two timeframes, that have hourly bookings directly adjacent to each other, but do not overlap
		$nonOverlappingHourlyDirectlyAdjacent = [
			'isValid' => true,
			'tf1' => [
				'grid'    => '1',
				'fullDay' => 'off',
				'start_time' => '08:00 AM',
				'end_time' => '10:00 AM',
			],
			'tf2' => [
				'grid'  => '1',
				'fullDay' => 'off',
				'start_time' => '10:00 AM',
				'end_time' => '12:00 PM',
			],
		];

		// two timeframes that are bookable for the full day, should overlap
		$overlappingFullDay = [
			'isValid' => false,
			'tf1' => [
				'grid'  => '0',
				'fullDay' => 'on',
				'start_time' => '08:00 AM',
				'end_time' => '08:00 PM',
			],
			'tf2' => [
				'grid'  => '0',
				'fullDay' => 'on',
				'start_time' => '10:00 AM',
				'end_time' => '06:00 PM',
			],
		];

		return [
			'non overlapping slots' => $nonOverlappingSlots,
			'non overlapping slots directly adjacent' => $nonOverlappingSlotsDirectlyAdjacent,
			'non overlapping hourly' => $nonOverlappingHourly,
			'non overlapping hourly directly adjacent' => $nonOverlappingHourlyDirectlyAdjacent,
			'overlapping full day' => $overlappingFullDay,
		];
	}

	public function provideOverlappingTest() {
		$today             = strtotime( self::CURRENT_DATE );
		$todayFormatted    = date( 'Y-m-d', $today );
		$tomorrow          = strtotime( '+1 day', $today );
		$tomorrowFormatted = date( 'Y-m-d', $tomorrow );
		$dayAfterTomorrow  = strtotime( '+2 days', $today );
		$inAWeek           = strtotime( '+7 days', $today );
		$inAWeekFormatted  = date( 'Y-m-d', $inAWeek );

		// in the following we have all possible combinations of repetitions amongst timeframes and if the days should collide with each other
		$dailyDoesNotOverlap = [
			'daysOverlap' => false,
			'tf1' => [
				'repetition' => 'd',
				'repetition_start' => $today,
				'repetition_end' => $tomorrow,
			],
			'tf2' => [
				'repetition' => 'd',
				'repetition_start' => $dayAfterTomorrow,
				'repetition_end' => $inAWeek,
			],
		];

		$dailyOverlaps = [
			'daysOverlap' => true,
			'tf1' => [
				'repetition' => 'd',
				'repetition_start' => $today,
				'repetition_end' => $tomorrow,
			],
			'tf2' => [
				'repetition' => 'd',
				'repetition_start' => $tomorrow,
				'repetition_end' => $inAWeek,
			],
		];

		$weeklyDoesNotOverlapOtherWeekdays = [
			'daysOverlap' => false,
			'tf1' => [
				'repetition' => 'w',
				'repetition_start' => $today,
				'repetition_end' => $inAWeek,
				'repetition_weekdays' => [ '1', '2', '3', '4', '5' ],
			],
			'tf2' => [
				'repetition' => 'w',
				'repetition_start' => $today,
				'repetition_end' => $inAWeek,
				'repetition_weekdays' => [ '6', '7' ],
			],
		];

		$weeklyWeekdaysOverlap = [
			'daysOverlap' => true,
			'tf1' => [
				'repetition' => 'w',
				'repetition_start' => $today,
				'repetition_end' => $inAWeek,
				'repetition_weekdays' => [ '1', '2', '3', '4', '5' ],
			],
			'tf2' => [
				'repetition' => 'w',
				'repetition_start' => $tomorrow,
				'repetition_end' => $inAWeek,
				'repetition_weekdays' => [ '3', '4', '5' ],
			],
		];

		$dailyWeeklyOverlap = [
			'daysOverlap' => true,
			'tf1' => [
				'repetition' => 'd',
				'repetition_start' => $today,
				'repetition_end' => $tomorrow,
			],
			'tf2' => [
				'repetition' => 'w',
				'repetition_start' => $today,
				'repetition_end' => $inAWeek,
				'repetition_weekdays' => [ '3', '4', '5' ],
			],
		];

		$dailyManualDoNotOverlap = [
			'daysOverlap' => false,
			'tf1' => [
				'repetition' => 'd',
				'repetition_start' => $today,
				'repetition_end' => $tomorrow,
			],
			'tf2' => [
				'repetition' => 'manual',
				'repetition_start' => $dayAfterTomorrow,
				'repetition_end' => $inAWeek,
				'repetition_dates' => $inAWeekFormatted,
			],
		];

		$dailyManualOverlap = [
			'daysOverlap' => true,
			'tf1' => [
				'repetition' => 'd',
				'repetition_start' => $today,
				'repetition_end' => $tomorrow,
			],
			'tf2' => [
				'repetition' => 'manual',
				'repetition_start' => $today,
				'repetition_end' => $inAWeek,
				'repetition_dates' => $todayFormatted,
			],
		];

		// CURRENT_DATE is a thursday, so they should not overlap
		$weeklyManualDoNotOverlap = [
			'daysOverlap' => false,
			'tf1' => [
				'repetition' => 'w',
				'repetition_start' => $today,
				'repetition_end' => $inAWeek,
				'repetition_weekdays' => [ '1', '2', '3' ],
			],
			'tf2' => [
				'repetition' => 'manual',
				'repetition_start' => $today,
				'repetition_end' => $inAWeek,
				'repetition_dates' => $todayFormatted,
			],
		];

		// CURRENT_DATE is a thursday, so they should overlap
		$weeklyManualOverlap = [
			'daysOverlap' => true,
			'tf1' => [
				'repetition' => 'w',
				'repetition_start' => $today,
				'repetition_end' => $inAWeek,
				'repetition_weekdays' => [ '3', '4', '5' ],
			],
			'tf2' => [
				'repetition' => 'manual',
				'repetition_start' => $today,
				'repetition_end' => $inAWeek,
				'repetition_dates' => $tomorrowFormatted,
			],
		];

		$overlapTests = [
			'daily does not overlap' => $dailyDoesNotOverlap,
			'daily overlaps' => $dailyOverlaps,
			'weekly does not overlap other weekdays' => $weeklyDoesNotOverlapOtherWeekdays,
			'weekly weekdays overlap' => $weeklyWeekdaysOverlap,
			'daily weekly overlap' => $dailyWeeklyOverlap,
			'daily manual do not overlap' => $dailyManualDoNotOverlap,
			'daily manual overlap' => $dailyManualOverlap,
			'weekly manual do not overlap' => $weeklyManualDoNotOverlap,
			'weekly manual overlap' => $weeklyManualOverlap,
		];
		// construct our tests from all possible combinations of timeframes
		$configurationsSameDay = $this->getBookableTimeframeSameDayCombos();
		foreach ( $overlapTests as $overlapTestLabel => $overlapTest ) {
			foreach ( $configurationsSameDay as $sameDayTestLabel => $configSameDay ) {
				// if the dates do not overlap, we need to make sure that a same day combination will still be valid
				$comboIsValid = $overlapTest['daysOverlap'] ? $configSameDay['isValid'] : true;
				$tf1          = array_merge( $overlapTest['tf1'], $configSameDay['tf1'] );
				$tf2          = array_merge( $overlapTest['tf2'], $configSameDay['tf2'] );
				yield $overlapTestLabel . ' - ' . $sameDayTestLabel => [ $comboIsValid, $tf1, $tf2 ];
				yield $overlapTestLabel . ' - ' . $sameDayTestLabel . ' - swapped' => [ $comboIsValid, $tf2, $tf1 ];
			}
		}
	}

	/**
	 * @dataProvider provideOverlappingTest
	 */
	public function testOverlaps( $valid, $tf1, $tf2 ) {
		$testItem     = $this->createItem( 'Test Item', 'publish' );
		$testLocation = $this->createLocation( 'Test Location', 'publish' );
		$tf1          = new Timeframe(
			$this->createTimeframe(
				$testLocation,
				$testItem,
				$tf1['repetition_start'],
				$tf1['repetition_end'],
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				$tf1['fullDay'],
				$tf1['repetition'],
				$tf1['grid'],
				$tf1['start_time'],
				$tf1['end_time'],
				'publish',
				$tf1['repetition_weekdays'] ?? [],
				$tf1['repetition_dates'] ?? '',
			)
		);

		$tf2 = new Timeframe(
			$this->createTimeframe(
				$testLocation,
				$testItem,
				$tf2['repetition_start'],
				$tf2['repetition_end'],
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				$tf2['fullDay'],
				$tf2['repetition'],
				$tf2['grid'],
				$tf2['start_time'],
				$tf2['end_time'],
				'publish',
				$tf2['repetition_weekdays'] ?? [],
				$tf2['repetition_dates'] ?? '',
			)
		);
		if ( $valid ) {
			$this->assertFalse( $tf1->overlaps( $tf2 ) );
		} else {
			$this->expectException( OverlappingException::class );
			$tf1->overlaps( $tf2 );
		}
	}

	public function testOverlaps_differentGrid() {
		$testItem     = $this->createItem( 'Test Item', 'publish' );
		$testLocation = $this->createLocation( 'Test Location', 'publish' );
		$tf1          = new Timeframe(
			$this->createTimeframe(
				$testLocation,
				$testItem,
				strtotime( self::CURRENT_DATE ),
				strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'off',
				'd',
				0,
				'08:00 AM',
				'10:00 AM',
			)
		);

		$tf2 = new Timeframe(
			$this->createTimeframe(
				$testLocation,
				$testItem,
				strtotime( self::CURRENT_DATE ),
				strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'off',
				'd',
				1,
				'10:00 AM',
				'12:00 PM',
			)
		);

		$this->expectException( OverlappingException::class );
		$tf1->overlaps( $tf2 );
	}
	public function testIsValid() {

		$newLoc      = $this->createLocation( 'New Location', 'publish' );
		$newItem     = $this->createItem( 'New Item', 'publish' );
		$noEndDateTf = new Timeframe(
			$this->createTimeframe(
				$newLoc,
				$newItem,
				strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
				'',
			)
		);
		$this->assertTrue( $noEndDateTf->isValid() );

		$this->assertTrue( $this->validTF->isValid() );

		$noItemTF = new Timeframe(
			$this->createTimeframe(
				$this->locationId,
				'',
				strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+3 days', strtotime( self::CURRENT_DATE ) )
			)
		);
		try {
			$noItemTF->isValid();
			$this->fail( 'TimeframeInvalidException was not thrown' );
		} catch ( TimeframeInvalidException $e ) {
			$this->assertStringContainsString( 'Item or location is missing. Please set item and location.', $e->getMessage() );
			// also test, that correct notice for Timeframes is shown
			$this->assertStringContainsString( 'Timeframe is saved as draft.', $e->getMessage() );
		}

		$noLocationTF = new Timeframe(
			$this->createTimeframe(
				'',
				$this->itemId,
				strtotime( '+20 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+25 days', strtotime( self::CURRENT_DATE ) )
			)
		);

		try {
			$noLocationTF->isValid();
			$this->fail( 'TimeframeInvalidException was not thrown' );
		} catch ( TimeframeInvalidException $e ) {
			$this->assertStringContainsString( 'Item or location is missing. Please set item and location.', $e->getMessage() );
		}

		$exceptionCaught = false;
		$noStartDateTF   = new Timeframe(
			$this->createTimeframe(
				$this->locationId,
				$this->itemId,
				'',
				strtotime( '+10 days', strtotime( self::CURRENT_DATE ) )
			)
		);
		try {
			$noStartDateTF->isValid();
			$this->fail( 'TimeframeInvalidException was not thrown' );
		} catch ( TimeframeInvalidException $e ) {
			$this->assertStringContainsString( 'Startdate is missing.', $e->getMessage() );
		}

		$isOverlapping = new Timeframe(
			$this->createTimeframe(
				$this->locationId,
				$this->itemId,
				strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+2 days', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'off'
			)
		);

		$this->assertTrue( $isOverlapping->hasTimeframeDateOverlap( $this->validTF ) );

		$this->expectException( TimeframeInvalidException::class );
		// overlaps exactly with $this->validTF
		$this->assertTrue( $isOverlapping->isValid() );
	}

	public function testIsUserPrivileged() {
		$this->createSubscriber();
		$this->createCBManager();
		$this->createAdministrator();
		$managedItem       = $this->createItem( 'Managed Item', 'publish', [ $this->cbManagerUserID ] );
		$unmanagedLocation = $this->createLocation( 'Unmanaged Location', 'publish' );
		$timeframe         = $this->createBookableTimeFrameIncludingCurrentDay( $unmanagedLocation, $managedItem );
		$timeframe         = new Timeframe( $timeframe );

		wp_set_current_user( $this->subscriberId );
		$this->assertFalse( $timeframe->isUserPrivileged() );

		wp_set_current_user( $this->cbManagerUserID );
		$this->assertTrue( $timeframe->isUserPrivileged() );

		wp_set_current_user( $this->adminUserID );
		$this->assertTrue( $timeframe->isUserPrivileged() );
	}

	public function testisValid_throwsException() {

		$secondLocation = $this->createLocation( 'Newest Location', 'publish' );

		$isOverlapping = new Timeframe(
			$this->createTimeframe(
				$secondLocation,
				$this->itemId,
				strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+2 days', strtotime( self::CURRENT_DATE ) )
			)
		);

		// $this->assertNotEquals( $isOverlapping->getLocation(), $this->validTF->getLocation() );
		$this->assertTrue( $isOverlapping->hasTimeframeDateOverlap( $this->validTF ) );

		$exceptionCaught = false;
		try {
			$isOverlapping->isValid();
		} catch ( TimeframeInvalidException $e ) {
			$this->assertStringContainsString( 'Item is already bookable at another location within the same date range.', $e->getMessage() );
			$exceptionCaught = true;
		}
		$this->assertTrue( $exceptionCaught );

		// test, if end date is before start date (should throw exception)
		$exceptionCaught = false;
		$endBeforeStart  = new Timeframe(
			$this->createTimeframe(
				$this->locationId,
				$this->itemId,
				strtotime( '+5 days', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+4 day', strtotime( self::CURRENT_DATE ) )
			)
		);
		try {
			$endBeforeStart->isValid();
		} catch ( TimeframeInvalidException $e ) {
			$this->assertStringContainsString( 'End date is before start date.', $e->getMessage() );
			$exceptionCaught = true;
		}
		$this->assertTrue( $exceptionCaught );

		// test if slot is too short (should throw exception) #1353
		// we have to create that more in the future so that it does not overlap with other timeframes
		$notCorrectSlot = new Timeframe(
			$this->createTimeframe(
				$this->locationId,
				$this->itemId,
				strtotime( '+31 days', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+32 days', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'',
				'd',
				0,
				'00:00 AM',
				'00:00 AM'
			)
		);
		try {
			$notCorrectSlot->isValid();
			$this->fail( 'Expected Exception not thrown' );
		} catch ( TimeframeInvalidException $e ) {
			$this->assertStringContainsString( 'The start- and end-time of the timeframe can not be the same. Please check the full-day checkbox if you want users to be able to book the full day.', $e->getMessage() );
		}

		$pickupTimeInvalid = new Timeframe(
			$this->createTimeframe(
				$this->locationId,
				$this->itemId,
				strtotime( '+10 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+13 days', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'off',
				'w',
				0,
				'09:00 AM',
				null
			)
		);
		try {
			$pickupTimeInvalid->isValid();
			$this->fail( 'TimeframeInvalidException was not thrown' );
		} catch ( TimeframeInvalidException $e ) {
			$this->assertStringContainsString( 'A pickup time but no return time has been set. Please set the return time.', $e->getMessage() );
		}
	}

	public function testGetTimeframeEndDate() {

		$this->assertEquals(
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			$this->firstTimeframe->getTimeframeEndDate()
		);

		$this->assertEquals(
			strtotime( '+30 day', strtotime( self::CURRENT_DATE ) ),
			$this->secondTimeframe->getTimeframeEndDate()
		);

		$noEndDate = new Timeframe(
			$this->createTimeframe(
				$this->locationId,
				$this->itemId,
				strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
				''
			)
		);
		$this->assertFalse( $noEndDate->getEndDate() );
	}

	public function testGetLatestPossibleBookingDateTimestamp() {
		// the default advance booking days in our tests are 30
		$advanceBookingDays = 30;
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		// case 1: timeframe is longer than advance booking days
		$lateTimeframe = new Timeframe(
			$this->createTimeframe(
				$this->locationId,
				$this->itemId,
				strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+100 days', strtotime( self::CURRENT_DATE ) ),
			)
		);

		$this->assertEquals(
			strtotime( '+29 days', strtotime( self::CURRENT_DATE ) ),
			$lateTimeframe->getLatestPossibleBookingDateTimestamp()
		);

		/*
		NOT SUPPORTED
		//case 2: timeframe ends before the advance booking days
		$this->assertEquals(
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			$this->firstTimeframe->getLatestPossibleBookingDateTimestamp()
		);
		*/
		// case 3: timeframe is infinite and no advance booking days are set, should default to one year
		$noEndDate = new Timeframe(
			$this->createTimeframe(
				$this->locationId,
				$this->itemId,
				strtotime( '-1 day', strtotime( self::CURRENT_DATE ) ),
				''
			)
		);
		update_post_meta( $noEndDate->ID, Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS, '' );
		$this->assertEquals(
			strtotime( '+364 days', strtotime( self::CURRENT_DATE ) ),
			$noEndDate->getLatestPossibleBookingDateTimestamp()
		);

		// case 4: timeframe is infinite and advance booking days are set
		update_post_meta( $noEndDate->ID, Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS, $advanceBookingDays );
		$this->assertEquals(
			strtotime( '+' . ( $advanceBookingDays - 1 ) . ' days', strtotime( self::CURRENT_DATE ) ),
			$noEndDate->getLatestPossibleBookingDateTimestamp()
		);

		/*
		NOT SUPPORTED
		//case 5: timeframe is not infinite and no advance booking days are set
		$yesEndDate = new Timeframe(
			$this->createTimeframe(
				$this->locationId,
				$this->itemId,
				strtotime( '-1 day', strtotime(self::CURRENT_DATE) ),
				strtotime( '+1 day', strtotime(self::CURRENT_DATE) )
			)
		);
		update_post_meta($yesEndDate->ID, Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS, '');
		$this->assertEquals(
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			$yesEndDate->getLatestPossibleBookingDateTimestamp()
		);
		*/
	}

	public function testIsBookable() {
		$this->assertTrue( $this->validTF->isBookable() );

		/*
		$passedTimeframe = new Timeframe($this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime("-5 days",time()),
			strtotime("-3 days",time())
		));
		$this->assertFalse($passedTimeframe->isBookable());*/
		// This test does not work, function maybe broken?
	}

	/**
	 * Tests all validity concerns of timeframes with manual repetition
	 * @return void
	 */
	public function testIsValid_manualRepetition() {
		// make sure, that timeframes with manual repetition can be saved without a start date or end date
		$noStartDateManualRep = new Timeframe(
			$this->createTimeframe(
				$this->locationId,
				$this->itemId,
				'',
				'',
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'on',
				'manual',
				0,
				'08:00 AM',
				'12:00 PM',
				'publish',
				[],
				'01-07-2021',
			)
		);
		$this->assertTrue( $noStartDateManualRep->isValid() );

		// but also make sure, that timeframes with manual repetition do not have empty manual repetition values
		try {
			$noManualRepValues = new Timeframe(
				$this->createTimeframe(
					$this->locationId,
					$this->itemId,
					'',
					'',
					\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
					'on',
					'manual',
				)
			);
			$noManualRepValues->isValid();
			$this->fail( 'TimeframeInvalidException was not thrown' );
		} catch ( TimeframeInvalidException $e ) {
			$this->assertEquals( 'No dates selected. Please select at least one date. Timeframe is saved as draft.', $e->getMessage() );
		}

		// make sure, that dates do not occur twice in the manual repetition field
		try {
			$doubleDateManualRep = new Timeframe(
				$this->createTimeframe(
					$this->locationId,
					$this->itemId,
					'',
					'',
					\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
					'on',
					'manual',
					0,
					'08:00 AM',
					'12:00 PM',
					'publish',
					[],
					'01-07-2021,01-07-2021',
				)
			);
			$doubleDateManualRep->isValid();
			$this->fail( 'TimeframeInvalidException was not thrown' );
		} catch ( TimeframeInvalidException $e ) {
			$this->assertStringContainsString( 'The same date was selected multiple times. Please select each date only once.', $e->getMessage() );
		}
	}

	public function testGetLocation() {
		$location = new Location( $this->locationId );
		$this->assertEquals( $location, $this->firstTimeframe->getLocation() );

		// when location has been deleted
		wp_delete_post( $this->locationId );
		$this->assertNull( $this->firstTimeframe->getLocation() );
	}

	public function testGetLocationID() {
		$this->assertEquals( $this->locationId, $this->firstTimeframe->getLocationID() );
	}

	public function testGetLocations() {
		// for just one location
		$oneLocation = $this->firstTimeframe->getLocations();
		$this->assertCount( 1, $oneLocation );
		$this->assertEquals( $this->locationId, $oneLocation[0]->ID );

		// for multiple defined locations
		$holiday4all        = $this->createHolidayTimeframeForAllItemsAndLocations();
		$holiday            = new Timeframe( $holiday4all );
		$retrievedLocations = $holiday->getLocations();
		$this->assertIsArray( $retrievedLocations );
		$locationIds = array_map(
			function ( $location ) {
				return $location->ID;
			},
			$retrievedLocations
		);
		$this->assertCount( 2, $retrievedLocations );
		$this->assertEqualsCanonicalizing( $locationIds, [ $this->firstLocation->ID,$this->otherLocation->ID ] );

		// when location has been deleted
		wp_delete_post( $this->locationId );
		$locations = $this->firstTimeframe->getLocations();
		$this->assertIsArray( $locations );
		$this->assertCount( 0, $locations );
	}

	public function testGetLocationIDs() {
		$holiday4all        = $this->createHolidayTimeframeForAllItemsAndLocations();
		$holiday            = new Timeframe( $holiday4all );
		$retrievedLocations = $holiday->getLocationIDs();
		$this->assertIsArray( $retrievedLocations );
		$this->assertCount( 2, $retrievedLocations );
		$this->assertEqualsCanonicalizing( $retrievedLocations, [ $this->firstLocation->ID,$this->otherLocation->ID ] );

		$this->assertEquals( [ $this->locationId ], $this->firstTimeframe->getLocationIDs() );
	}

	public function testGetItem() {
		$item = new Item( $this->itemId );
		$this->assertEquals( $item, $this->firstTimeframe->getItem() );

		// when item has been deleted
		wp_delete_post( $this->itemId );
		$this->assertNull( $this->firstTimeframe->getItem() );
	}

	public function testGetItemID() {
		$this->assertEquals( $this->itemId, $this->firstTimeframe->getItemID() );
	}

	public function testGetItems() {
		// for just one item
		$singleItem = $this->validTF->getItems();
		$this->assertIsArray( $singleItem );
		$itemIds = array_map(
			function ( $item ) {
				return $item->ID;
			},
			$singleItem
		);
		$this->assertEquals( [ $this->otherItem->ID ], $itemIds );

		// for multiple defined items
		$holiday4all = $this->createHolidayTimeframeForAllItemsAndLocations();
		$holiday     = new Timeframe( $holiday4all );
		$items       = $holiday->getItems();
		$this->assertIsArray( $items );
		$itemIds = array_map(
			function ( $item ) {
				return $item->ID;
			},
			$items
		);
		$this->assertEqualsCanonicalizing( [ $this->firstItem->ID,$this->otherItem->ID ], $itemIds );

		// when item has been deleted
		wp_delete_post( $this->itemId );
		$items = $this->firstTimeframe->getItems();
		$this->assertIsArray( $items );
		$this->assertCount( 0, $items );
	}

	public function testGetItemIDs() {
		// for just one item
		$singleItem = $this->validTF->getItemIDs();
		$this->assertIsArray( $singleItem );
		$this->assertEquals( [ $this->otherItem->ID ], $singleItem );

		// for multiple defined items
		$holiday4all = $this->createHolidayTimeframeForAllItemsAndLocations();
		$holiday     = new Timeframe( $holiday4all );
		$items       = $holiday->getItemIDs();
		$this->assertIsArray( $items );
		$this->assertEqualsCanonicalizing( [ $this->firstItem->ID,$this->otherItem->ID ], $items );
	}

	/**
	 * @return void
	 *
	 * @dataProvider providerFormatBookableDate()
	 */
	public function test_formatBookableDate( $todayMockDate, $expectedString, $start, $end ) {

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
		// check, that they are properly presented in an array
		$dateFormattedInAWeek       = date( 'Y-m-d', strtotime( '+1 week', strtotime( self::CURRENT_DATE ) ) );
		$tfWithManualSelectionDates = new Timeframe(
			$this->createTimeframe(
				$this->locationId,
				$this->itemId,
				strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+2 days', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'off',
				'manual',
				0,
				'08:00 AM',
				'12:00 PM',
				'publish',
				'[]',
				"{$this->dateFormatted},{$dateFormattedInAWeek}"
			)
		);
		$expectedDates              = array(
			$this->dateFormatted,
			$dateFormattedInAWeek,
		);
		$this->assertEquals( $expectedDates, $tfWithManualSelectionDates->getManualSelectionDates() );
	}

	public function testGetGridSize() {
		$fullDayTimeframe = new Timeframe(
			$this->createTimeframe(
				$this->locationId,
				$this->itemId,
				strtotime( self::CURRENT_DATE ),
				strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'on',
			)
		);
		$this->assertEquals( 24, $fullDayTimeframe->getGridSize() );

		$twoHourSlotEachDay = new Timeframe(
			$this->createTimeframe(
				$this->locationId,
				$this->itemId,
				strtotime( self::CURRENT_DATE ),
				strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'',
				'd',
				0,
				'08:00 AM',
				'10:00 AM',
			)
		);
		$this->assertEquals( 2, $twoHourSlotEachDay->getGridSize() );

		$hourlyBookable = new Timeframe(
			$this->createTimeframe(
				$this->locationId,
				$this->itemId,
				strtotime( self::CURRENT_DATE ),
				strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'',
				'd',
				1,
				'08:00 AM',
				'10:00 AM',
			)
		);
		$this->assertEquals( 1, $hourlyBookable->getGridSize() );
	}

	public function testGetAdmins() {
		// Case 1: no admins set
		// $this->assertEquals( [], $this->firstTimeframe->getAdmins() ); - The author is currently always included as admin
		$this->assertEquals( [ self::USER_ID ], $this->firstTimeframe->getAdmins() );

		// Case 2: Item admin set
		// Should get the item admin as eligible
		$this->createCBManager();
		$managedItem       = $this->createItem( 'Managed Item', 'publish', [ $this->cbManagerUserID ] );
		$unmanagedLocation = $this->createLocation( 'Unmanaged Location', 'publish' );
		$timeframe         = new Timeframe( $this->createBookableTimeFrameIncludingCurrentDay( $unmanagedLocation, $managedItem ) );
		$this->assertEqualsCanonicalizing( [ $this->cbManagerUserID, self::USER_ID ], $timeframe->getAdmins() );

		// Case 3: Location admin set
		$managedLocation = $this->createLocation( 'Managed Location', 'publish', [ $this->cbManagerUserID ] );
		$unmanagedItem   = $this->createItem( 'Unmanaged Item', 'publish' );
		$timeframe       = new Timeframe( $this->createBookableTimeFrameIncludingCurrentDay( $managedLocation, $unmanagedItem ) );
		$this->assertEqualsCanonicalizing( [ $this->cbManagerUserID, self::USER_ID ], $timeframe->getAdmins() );

		// Case 4: Both admins set
		$otherManagedLocation = $this->createLocation( 'Other Managed Location', 'publish', [ $this->cbManagerUserID ] );
		$otherManagedItem     = $this->createItem( 'Other Managed Item', 'publish', [ $this->cbManagerUserID ] );
		$timeframe            = new Timeframe( $this->createBookableTimeFrameIncludingCurrentDay( $otherManagedLocation, $otherManagedItem ) );
		$this->assertEqualsCanonicalizing( [ $this->cbManagerUserID, self::USER_ID ], $timeframe->getAdmins() );

		// case 5: timeframe which can have multiple items / locations assigned (#1961)
		$holiday4managedLocation = $this->createTimeframe(
			[ $otherManagedLocation ],
			[ $otherManagedItem ],
			strtotime( self::CURRENT_DATE ),
			strtotime( '+10 days', strtotime( self::CURRENT_DATE ) ),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::HOLIDAYS_ID,
		);
		$timeframe               = new Timeframe( $holiday4managedLocation );
		$this->assertEqualsCanonicalizing( [ $this->cbManagerUserID, self::USER_ID ], $timeframe->getAdmins() );

		// case 6: one of the locations of holiday timeframe is managed, other is not. manager should not be allowed to edit timeframes that are not completely under their control for multi-assigned timeframes
		$holiday4partlyManagedLocation = $this->createTimeframe(
			[ $otherManagedLocation, $unmanagedLocation ],
			[ $otherManagedItem, $unmanagedItem ],
			strtotime( self::CURRENT_DATE ),
			strtotime( '+10 days', strtotime( self::CURRENT_DATE ) ),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::HOLIDAYS_ID
		);
		$timeframe                     = new Timeframe( $holiday4partlyManagedLocation );
		$this->assertEqualsCanonicalizing( [ self::USER_ID ], $timeframe->getAdmins() );
	}

	protected function setUp(): void {

		parent::setUp();
		$this->firstTimeframeId  = $this->createBookableTimeFrameIncludingCurrentDay();
		$this->secondTimeframeId = $this->createBookableTimeFrameStartingInAWeek();
		$this->firstTimeframe    = new Timeframe( $this->firstTimeframeId );
		$this->secondTimeframe   = new Timeframe( $this->secondTimeframeId );
		$this->firstItem         = new Item( $this->itemId );
		$this->firstLocation     = new Location( $this->locationId );
		$otherItem               = $this->createItem( 'Other Item', 'publish' );
		$this->otherItem         = new Item( $otherItem );
		$otherLocation           = $this->createLocation( 'Other Location', 'publish' );
		$this->otherLocation     = new Location( $otherLocation );
		$this->validTF           = new Timeframe(
			$this->createTimeframe(
				$otherLocation,
				$otherItem,
				strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+3 days', strtotime( self::CURRENT_DATE ) )
			)
		);
	}

	protected function tearDown(): void {
		parent::tearDown(); // TODO: Change the autogenerated stub
	}
}
