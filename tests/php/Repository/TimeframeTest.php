<?php

namespace CommonsBooking\Tests\Repository;

use CommonsBooking\Repository\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use SlopeIt\ClockMock\ClockMock;

class TimeframeTest extends CustomPostTypeTest {

	protected int $repetition_start;
	protected int $repetition_end;

	protected string $formattedDate;

	protected int $timeframeWithEndDate;
	protected int $timeframeWithoutEndDate;
	protected int $timeframeDailyRepetition;
	protected int $timeframeWeeklyRepetition;
	protected int $timeframeManualRepetition;

	/**
	 * The tests are designed in a way, that all timeframes should lie in the CURRENT_DATE plus 10 days.
	 * The only exception is the manual repetition timeframe, which is only valid for today and in a week.
	 * all apply to the location with id $this->locationId and the item with id $this->itemId
	 * @var array|int|\WP_Error
	 */
	protected array $allTimeframes;

	/**
	 * Create a completely seperate item, location and timeframe.
	 * @return void
	 */
	private function createOtherTimeframe( $start = null, $end = null ) {
		if ( $start = null ) {
			$start = $this->repetition_start;
		}
		if ( $end = null ) {
			$end = $this->repetition_end;
		}
		$this->otherItemId      = $this->createItem( "Other Item" );
		$this->otherLocationId  = $this->createLocation( "Other Location" );
		$this->otherTimeframeId = $this->createTimeframe(
			$this->otherLocationId,
			$this->otherItemId,
			$start,
			$end
		);
	}


	public function testGetInRange() {
		$inRangeTimeFrames = Timeframe::getInRange( $this->repetition_start, $this->repetition_end );
		//All timeframes should be in range
		$this->assertEquals( count( $this->allTimeframes ), count( $inRangeTimeFrames ) );
		$postIds = array_map( function ( $timeframe ) {
			return $timeframe->ID;
		}, $inRangeTimeFrames );
		asort( $postIds );
		$this->assertEquals( $this->allTimeframes, $postIds );
	}

	public function testGetForItem() {
		$inItemTimeframes = Timeframe::get(
			[],
			[ $this->itemId ],
		);
		$this->assertEquals( count( $this->allTimeframes ), count( $inItemTimeframes ) );
		$postIds = array_map( function ( $timeframe ) {
			return $timeframe->ID;
		}, $inItemTimeframes );
		asort( $postIds );
		$this->assertEquals( $this->allTimeframes, $postIds );
	}

	public function testGetForLocation() {
		$inLocationTimeframes = Timeframe::get(
			[ $this->locationId ],
		);
		$this->assertEquals( count( $this->allTimeframes ), count( $inLocationTimeframes ) );
		$postIds = array_map( function ( $timeframe ) {
			return $timeframe->ID;
		}, $inLocationTimeframes );
		asort( $postIds );
		$this->assertEquals( $this->allTimeframes, $postIds );
	}

	public function testGetForLocationAndItem() {
		$inLocationAndItemTimeframes = Timeframe::get(
			[ $this->locationId ],
			[ $this->itemId ],
		);
		$this->assertEquals( count( $this->allTimeframes ), count( $inLocationAndItemTimeframes ) );
		$postIds = array_map( function ( $timeframe ) {
			return $timeframe->ID;
		}, $inLocationAndItemTimeframes );
		asort( $postIds );
		$this->assertEquals( $this->allTimeframes, $postIds );
	}

	/**
	 * Will check if we can get a timeframe of the holiday type just the same as a normal timeframe
	 * @return void
	 */
	public function testGetHoliday() {
		$holidayId                  = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::HOLIDAYS_ID
		);
		$allTimeframesForLocAndItem = Timeframe::get(
			[ $this->locationId ],
			[ $this->itemId ],
		);
		$this->assertEquals( 6, count( $allTimeframesForLocAndItem ) );
		$this->assertEqualsCanonicalizing(
			[
				$this->timeframeWithEndDate,
				$this->timeframeWithoutEndDate,
				$this->timeframeDailyRepetition,
				$this->timeframeWeeklyRepetition,
				$this->timeframeManualRepetition,
				$holidayId
			],
			array_map( function ( $timeframe ) {
				return $timeframe->ID;
			}, $allTimeframesForLocAndItem )
		);

		//Test-case for #1357 . The holiday should be returned regardless of the 'maxBookingDays'(aka advanceBookingDays) setting for the holiday. The maxBookingDays setting is only applicable for bookable timeframes.
		//We remove the irrelevant postmeta so that it is not processed by the filtering functions anymore
		$holidayInFuture = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '+61 days', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+62 days', strtotime( self::CURRENT_DATE ) ),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::HOLIDAYS_ID,
			"on",
			"d",
			0,
			'8:00 AM',
			'12:00 PM',
			'publish',
			[],
			self::USER_ID,
			3,
			30
		);
		\CommonsBooking\Wordpress\CustomPostType\Timeframe::savePost(
			$holidayInFuture,
			get_post( $holidayInFuture )
		);
		//This is necessary, because the getLatestPossibleBookingDateTimestamp takes time() as the calculation base.
		//the getLatestPossibleBookingDateTimestamp function takes the current time and adds the extra days on top to determine at what day you are allowed to book.
		//Because our CURRENT_DATE is so far in the past, the latest possible booking date is also very far in the past which means that the test would not fail for a broken filterTimeframesByMaxBookingDays function.
		//Therefore we have to freeze the time or else the test would make no sense.
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		$allTimeframesForLocAndItem = Timeframe::get(
			[ $this->locationId ],
			[ $this->itemId ],
		);
		$this->assertEquals( 7, count( $allTimeframesForLocAndItem ) );
		$this->assertEqualsCanonicalizing(
			[
				$this->timeframeWithEndDate,
				$this->timeframeWithoutEndDate,
				$this->timeframeDailyRepetition,
				$this->timeframeWeeklyRepetition,
				$this->timeframeManualRepetition,
				$holidayId,
				$holidayInFuture
			],
			array_map( function ( $timeframe ) {
				return $timeframe->ID;
			}, $allTimeframesForLocAndItem )
		);
	}

	protected function setUp(): void {
		parent::setUp();
		$this->repetition_start = strtotime( self::CURRENT_DATE );
		$this->repetition_end   = strtotime( '+10 days', $this->repetition_start );

		// Timeframe with enddate
		$this->timeframeWithEndDate = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			$this->repetition_start,
			$this->repetition_end
		);
		$this->allTimeframes[]      = $this->timeframeWithEndDate;

		// Timeframe without enddate
		$this->timeframeWithoutEndDate = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			$this->repetition_start,
			null
		);
		$this->allTimeframes[]         = $this->timeframeWithoutEndDate;

		//timeframe with daily repetition
		$this->timeframeDailyRepetition = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			$this->repetition_start,
			$this->repetition_end,
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			'on',
			'd'
		);
		$this->allTimeframes[]          = $this->timeframeDailyRepetition;

		//timeframe with weekly repetition from monday to friday
		$this->timeframeWeeklyRepetition = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			$this->repetition_start,
			$this->repetition_end,
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			'on',
			'w',
			0,
			'08:00 AM',
			'12:00 PM',
			'publish',
			[ "1", "2", "3", "4", "5" ]
		);
		$this->allTimeframes[]           = $this->timeframeWeeklyRepetition;

		$dateInAWeek = date( 'Y-m-d', strtotime( '+1 week', $this->repetition_start ) );
		//timeframe with manual repetition for today and in a week
		$this->timeframeManualRepetition = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			$this->repetition_start,
			$this->repetition_end,
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			'on',
			'manual',
			0,
			'08:00 AM',
			'12:00 PM',
			'publish',
			[],
			"{$this->dateFormatted},{$dateInAWeek}"
		);
		$this->allTimeframes[]           = $this->timeframeManualRepetition;

		asort( $this->allTimeframes );
	}

	public function testGetForSpecificDate() {
		$inSpecificDate = Timeframe::get(
			[ $this->locationId ],
			[ $this->itemId ],
			[],
			$this->dateFormatted
		);
		$this->assertEquals( count( $this->allTimeframes ), count( $inSpecificDate ) );
		$postIds = array_map( function ( $timeframe ) {
			return $timeframe->ID;
		}, $inSpecificDate );
		asort( $postIds );
		$this->assertEquals( $this->allTimeframes, $postIds );

		$inOneWeek = Timeframe::get(
			[ $this->locationId ],
			[ $this->itemId ],
			[],
			date( 'Y-m-d', strtotime( '+1 week', $this->repetition_start ) )
		);
		//it should contain everything
		$this->assertEquals( count( $this->allTimeframes ), count( $inOneWeek ) );
		$postIds = array_map( function ( $timeframe ) {
			return $timeframe->ID;
		}, $inOneWeek );
		asort( $postIds );
		$this->assertEquals( $this->allTimeframes, $postIds );

		$tomorrow = Timeframe::get(
			[ $this->locationId ],
			[ $this->itemId ],
			[],
			date( 'Y-m-d', strtotime( '+1 day', $this->repetition_start ) )
		);
		//it should contain everything except the manual repetition
		$this->assertEquals( count( $this->allTimeframes ) - 1, count( $tomorrow ) );
		$postIds = array_map( function ( $timeframe ) {
			return $timeframe->ID;
		}, $tomorrow );
		asort( $postIds );
		$this->assertEquals( array_diff( $this->allTimeframes, [ $this->timeframeManualRepetition ] ), $postIds );
	}

	public function testGetAllPaginated() {
		$response = Timeframe::getAllPaginated( 1, 10 );
		$allTimeframes = $response->posts;
		$this->assertEquals( count( $this->allTimeframes ), $response->totalPosts );
		$this->assertEquals( 1, $response->totalPages );
		$this->assertTrue( $response->done );
		$this->assertEquals( count( $this->allTimeframes ), count( $allTimeframes ) );
		$postIds = array_map( function ( $timeframe ) {
			return $timeframe->ID;
		}, $allTimeframes );
		$this->assertEqualsCanonicalizing( $this->allTimeframes, $postIds );

		//test pagination
		$response = Timeframe::getAllPaginated( 1, 2 );
		$timeframes = $response->posts;
		$this->assertEquals( 2, count( $timeframes ) );
		$this->assertEquals( count( $this->allTimeframes ), $response->totalPosts );
		$this->assertEquals( 3, $response->totalPages );
		$this->assertFalse( $response->done );
		$allTimeframes = $timeframes;

		$response = Timeframe::getAllPaginated( 2, 2 );
		$timeframes = $response->posts;
		$this->assertEquals( 2, count( $timeframes ) );
		$this->assertFalse( $response->done );
		$allTimeframes = array_merge( $allTimeframes, $timeframes );

		//last page
		$response = Timeframe::getAllPaginated( 3, 2 );
		$timeframes = $response->posts;
		$this->assertEquals( 1, count( $timeframes ) );
		$this->assertTrue( $response->done );
		$allTimeframes = array_merge( $allTimeframes, $timeframes );
		$postIds = array_map( function ( $timeframe ) {
			return $timeframe->ID;
		}, $allTimeframes );
		$this->assertEqualsCanonicalizing( $this->allTimeframes, $postIds );
	}

	public function testGetInRangePaginated() {
		$originalTimeframes = Timeframe::getInRangePaginated(
			$this->repetition_start,
			$this->repetition_end
		);
		$this->assertTrue($originalTimeframes['done']);
		$this->assertEquals(1, $originalTimeframes['totalPages']);
		$this->assertEquals(count($this->allTimeframes), count($originalTimeframes['posts']));
		$postIds = array_map(function($timeframe) {
			return $timeframe->ID;
		}, $originalTimeframes['posts']);
		$this->assertEqualsCanonicalizing($this->allTimeframes, $postIds);
		//create a bunch of bookings to test pagination properly
		$bookingIds = [];
		for($i = 0; $i < 21; $i++) {
			$bookingIds[] = $this->createBooking(
				$this->locationId,
				$this->itemId,
				strtotime("+ " . ($i + 10) . " days", strtotime(self::CURRENT_DATE)),
				strtotime("+ ".($i + 11)." days", strtotime(self::CURRENT_DATE)),
			);
		}
		$firstPage = Timeframe::getInRangePaginated(
			strtotime("+ 10 days", strtotime(self::CURRENT_DATE)),
			strtotime("+ 32 days", strtotime(self::CURRENT_DATE)),
			1,
			10,
			[ \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID ],
		);
		$this->assertEquals(10, count($firstPage['posts']));
		$this->assertEquals(3, $firstPage['totalPages']);
		$this->assertFalse($firstPage['done']);

		$secondPage = Timeframe::getInRangePaginated(
			strtotime("+ 10 days", strtotime(self::CURRENT_DATE)),
			strtotime("+ 32 days", strtotime(self::CURRENT_DATE)),
			2,
			10,
			[ \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID ],
		);
		$this->assertFalse($secondPage['done']);
		$this->assertEquals(3, $secondPage['totalPages']);
		$this->assertEquals(10, count($secondPage['posts']));

		$thirdPage = Timeframe::getInRangePaginated(
			strtotime("+ 10 days", strtotime(self::CURRENT_DATE)),
			strtotime("+ 32 days", strtotime(self::CURRENT_DATE)),
			3,
			10,
			[ \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID ],
		);
		$this->assertTrue($thirdPage['done']);
		$this->assertEquals(3, $thirdPage['totalPages']);
		$this->assertEquals(1, count($thirdPage['posts']));

		//make sure, that no booking is in more than one page
		$firstPageIDS = array_map(function($booking) {
			return $booking->ID;
		}, $firstPage['posts']);
		$secondPageIDS = array_map(function($booking) {
			return $booking->ID;
		}, $secondPage['posts']);
		$thirdPageIDS = array_map(function($booking) {
			return $booking->ID;
		}, $thirdPage['posts']);

		//make sure, that there are no duplicates among the pages
		$this->assertEmpty(array_intersect($firstPageIDS, $secondPageIDS,$thirdPageIDS));

		//make sure, that all bookings are in one of the pages
		$merged = array_merge($firstPageIDS, $secondPageIDS, $thirdPageIDS);
		$this->assertEquals(21, count($merged));
		$this->assertEqualsCanonicalizing($bookingIds, $merged);
	}

	public function testGetPostIdsByType_oneLocationMultiItem() {
		$otherItemId = $this->createItem( "Other Item" );
		// Timeframe with enddate and two items
		$multiItemTF   = $this->createTimeframe(
			$this->locationId,
			[ $this->itemId, $otherItemId ],
			$this->repetition_start,
			$this->repetition_end
		);
		$fromFirstItem = Timeframe::getPostIdsByType(
			[ \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID ],
			[ $this->itemId ],
			[ $this->locationId ]
		);
		$this->assertEquals( 6, count( $fromFirstItem ) );
		$this->assertEqualsCanonicalizing( [
			$multiItemTF,
			$this->timeframeManualRepetition,
			$this->timeframeWeeklyRepetition,
			$this->timeframeDailyRepetition,
			$this->timeframeWithoutEndDate,
			$this->timeframeWithEndDate
		], $fromFirstItem );

		$fromSecondItem = Timeframe::getPostIdsByType(
			[ \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID ],
			[ $otherItemId ],
			[ $this->locationId ]
		);
		$this->assertEquals( 1, count( $fromSecondItem ) );
		$this->assertEquals( $multiItemTF, $fromSecondItem[0] );

		$fromBothItems = Timeframe::getPostIdsByType(
			[ \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID ],
			[ $this->itemId, $otherItemId ],
			[ $this->locationId ]
		);
		$this->assertEquals( 6, count( $fromBothItems ) );
		$this->assertEqualsCanonicalizing( [
			$multiItemTF,
			$this->timeframeManualRepetition,
			$this->timeframeWeeklyRepetition,
			$this->timeframeDailyRepetition,
			$this->timeframeWithoutEndDate,
			$this->timeframeWithEndDate
		], $fromBothItems );
	}

	/**
	 * This test is tricky because it only makes sense for holiday timeframes.
	 * Otherwise, this configuration would create a conflict.
	 *
	 * @return void
	 */
	public function testGetPostIdsByType_multiLocationMultiItem() {
		// Timeframe with enddate and one item
		$this->timeframeId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			$this->repetition_start,
			$this->repetition_end,
		);
		$this->createOtherTimeframe();

		//create holiday applicable for both
		$holidayId = $this->createTimeframe(
			[ $this->locationId, $this->otherLocationId ],
			[ $this->itemId, $this->otherItemId ],
			$this->repetition_start,
			$this->repetition_end,
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::HOLIDAYS_ID
		);

		$holidayFromFirstItemAndLoc = Timeframe::getPostIdsByType(
			[ \CommonsBooking\Wordpress\CustomPostType\Timeframe::HOLIDAYS_ID ],
			[ $this->itemId ],
			[ $this->locationId ]
		);
		$this->assertEquals( 1, count( $holidayFromFirstItemAndLoc ) );
		$this->assertEquals( $holidayId, $holidayFromFirstItemAndLoc[0] );

		$holidayFromSecondItemAndLoc = Timeframe::getPostIdsByType(
			[ \CommonsBooking\Wordpress\CustomPostType\Timeframe::HOLIDAYS_ID ],
			[ $this->otherItemId ],
			[ $this->otherLocationId ]
		);
		$this->assertEquals( 1, count( $holidayFromSecondItemAndLoc ) );
		$this->assertEquals( $holidayId, $holidayFromSecondItemAndLoc[0] );

	}

}
