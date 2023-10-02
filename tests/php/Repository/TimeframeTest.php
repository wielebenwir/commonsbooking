<?php

namespace CommonsBooking\Tests\Repository;

use CommonsBooking\Repository\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class TimeframeTest extends CustomPostTypeTest {

	const REPETITION_START = '1623801600';

	const REPETITION_END = '1661472000';

	protected int $timeframeId;

	protected int $otherItemId;
	protected int $otherLocationId;
	protected int $otherTimeframeId;

	protected function setUp(): void {
		parent::setUp();
	}

	/**
	 * Create a completely seperate item, location and timeframe.
	 * @return void
	 */
	private function createOtherTimeframe( $start = self::REPETITION_START, $end = self::REPETITION_END ) {
		$this->otherItemId      = $this->createItem( "Other Item" );
		$this->otherLocationId  = $this->createLocation( "Other Location" );
		$this->otherTimeframeId = $this->createTimeframe(
			$this->otherLocationId,
			$this->otherItemId,
			$start,
			$end
		);
	}

	private function createOtherTFwithItemAtFirstLocation( $start = self::REPETITION_START, $end = self::REPETITION_END ) {
		$this->otherItemId      = $this->createItem( "Other Item" );
		$this->otherTimeframeId = $this->createTimeframe(
			$this->locationId,
			$this->otherItemId,
			$start,
			$end
		);
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function testGetInRange_withEndDate() {
		// Timeframe with enddate
		$this->timeframeId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			self::REPETITION_START,
			self::REPETITION_END
		);
		$inRangeTimeFrames = Timeframe::getInRange( self::REPETITION_START, self::REPETITION_END );
		$postIds           = array_map( function ( $timeframe ) {
			return $timeframe->ID;
		}, $inRangeTimeFrames );
		$this->assertContains( $this->timeframeId, $postIds );
		$this->assertEquals( 1, count( $inRangeTimeFrames ) );

		// Create a completely seperate item, location and timeframe. This should now also be in the range.
		$this->createOtherTimeframe();
		$inRangeTimeFrames = Timeframe::getInRange( self::REPETITION_START, self::REPETITION_END );
		$this->assertEquals( 2, count( $inRangeTimeFrames ) );
		$postIds = array_map( function ( $timeframe ) {
			return $timeframe->ID;
		}, $inRangeTimeFrames );
		$this->assertContains( $this->otherTimeframeId, $postIds );

		//different location, same item, should be in range
		$this->createOtherTFwithItemAtFirstLocation();
		$inRangeTimeFrames = Timeframe::getInRange( self::REPETITION_START, self::REPETITION_END );
		$this->assertEquals( 3, count( $inRangeTimeFrames ) );
		$postIds = array_map( function ( $timeframe ) {
			return $timeframe->ID;
		}, $inRangeTimeFrames );
		$this->assertContains( $this->otherTimeframeId, $postIds );

		//item and location are the same, but timeframe is not in range because it ends before the start of the range
		$earlierStart = new \DateTime();
		$earlierStart->setTimestamp( self::REPETITION_START );
		$earlierStart->modify( '-10 day' );

		$earlierEnd = clone $earlierStart;
		$earlierEnd->modify( '+5 day' );

		$this->timeframeId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			$earlierStart->getTimestamp(),
			$earlierEnd->getTimestamp()
		);
		$inRangeTimeFrames = Timeframe::getInRange( self::REPETITION_START, self::REPETITION_END );
		$this->assertEquals( 3, count( $inRangeTimeFrames ) );
		$postIds = array_map( function ( $timeframe ) {
			return $timeframe->ID;
		}, $inRangeTimeFrames );
		$this->assertNotContains( $this->timeframeId, $postIds );
	}

	public function testGetInRange_withoutEndDate() {
		// Timeframe without enddate
		$this->timeframeId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			self::REPETITION_START,
			null
		);
		$inRangeTimeFrames = Timeframe::getInRange( self::REPETITION_START, self::REPETITION_END );
		$postIds           = array_map( function ( $timeframe ) {
			return $timeframe->ID;
		}, $inRangeTimeFrames );
		$this->assertContains( $this->timeframeId, $postIds );
		$this->assertEquals( 1, count( $inRangeTimeFrames ) );
	}

	public function testGetForItem() {
		// Timeframe with enddate
		$this->timeframeId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			self::REPETITION_START,
			self::REPETITION_END
		);
		$inItemTimeframes  = Timeframe::get(
			[],
			[ $this->itemId ],
		);
		$this->assertEquals( 1, count( $inItemTimeframes ) );
		$this->assertEquals( $this->timeframeId, $inItemTimeframes[0]->ID );

		//test for one item that is first at one location and then at another location, should get both timeframes
		$otherLocationId = $this->createLocation( "Other Location" );
		$earlierStart    = new \DateTime();
		$earlierStart->setTimestamp( self::REPETITION_START );
		$earlierStart->modify( '-10 day' );

		$earlierEnd = clone $earlierStart;
		$earlierEnd->modify( '+5 day' );
		$otherTimeframeId = $this->createTimeframe(
			$otherLocationId,
			$this->itemId,
			$earlierStart->getTimestamp(),
			$earlierEnd->getTimestamp()
		);
		$inItemTimeframes = Timeframe::get(
			[],
			[ $this->itemId ],
		);
		$this->assertEquals( 2, count( $inItemTimeframes ) );
		$postIds = array_map( function ( $timeframe ) {
			return $timeframe->ID;
		}, $inItemTimeframes );
		$this->assertContains( $this->timeframeId, $postIds );
		$this->assertContains( $otherTimeframeId, $postIds );
	}

	/**
	 * Tests for timeframes which have more than one assigned item or location
	 * @return void
	 */
	public function testGetMultiTimeframe() {
		$otherItem = $this->createItem( "Other Item" );
		$otherLocation = $this->createLocation( "Other Location" );
		// Timeframe just for original item and location
		$this->timeframeId = $this->createBookableTimeFrameIncludingCurrentDay();
		$holidayTF = $this->createHolidayTimeframeForAllItemsAndLocations();
		//from first item
		$inItemTimeframes = Timeframe::get(
			[],
			[ $this->itemId ],
		);
		$this->assertEquals( 2, count( $inItemTimeframes ) );
		$postIds = array_map( function ( $timeframe ) {
			return $timeframe->ID;
		}, $inItemTimeframes );
		$this->assertContains( $this->timeframeId, $postIds );
		$this->assertContains( $holidayTF, $postIds );

		//from second item
		$inItemTimeframes = Timeframe::get(
			[],
			[ $otherItem ],
		);
		$this->assertEquals( 1, count( $inItemTimeframes ) );
		$this->assertEquals( $holidayTF, $inItemTimeframes[0]->ID );

		//from first location
		$inLocationTimeframes = Timeframe::get(
			[ $this->locationId ],
		);
		$this->assertEquals( 2, count( $inLocationTimeframes ) );
		$postIds = array_map( function ( $timeframe ) {
			return $timeframe->ID;
		}, $inLocationTimeframes );
		$this->assertContains( $this->timeframeId, $postIds );
		$this->assertContains( $holidayTF, $postIds );

		//from second location
		$inLocationTimeframes = Timeframe::get(
			[ $otherLocation ],
		);
		$this->assertEquals( 1, count( $inLocationTimeframes ) );
		$this->assertEquals( $holidayTF, $inLocationTimeframes[0]->ID );
	}

	public function testGetForLocation() {
		// Timeframe with enddate
		$this->timeframeId    = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			self::REPETITION_START,
			self::REPETITION_END
		);
		$inLocationTimeframes = Timeframe::get(
			[ $this->locationId ],
		);
		$this->assertEquals( 1, count( $inLocationTimeframes ) );
		$this->assertEquals( $this->timeframeId, $inLocationTimeframes[0]->ID );

		//test for one location that has two items, should get both timeframes
		$this->createOtherTFwithItemAtFirstLocation();
		$inLocationTimeframes = Timeframe::get(
			[ $this->locationId ],
		);
		$this->assertEquals( 2, count( $inLocationTimeframes ) );
		$postIds = array_map( function ( $timeframe ) {
			return $timeframe->ID;
		}, $inLocationTimeframes );
		$this->assertContains( $this->timeframeId, $postIds );
		$this->assertContains( $this->otherTimeframeId, $postIds );
	}

	public function testGetForLocationAndItem() {
		// Timeframe with enddate
		$this->timeframeId           = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			self::REPETITION_START,
			self::REPETITION_END
		);
		$inLocationAndItemTimeframes = Timeframe::get(
			[ $this->locationId ],
			[ $this->itemId ],
		);
		$this->assertEquals( 1, count( $inLocationAndItemTimeframes ) );
		$this->assertEquals( $this->timeframeId, $inLocationAndItemTimeframes[0]->ID );

		//test for one location that has two items and completely separate item/location combo should still only get the specific timeframe
		$this->createOtherTFwithItemAtFirstLocation();
		$inLocationAndItemTimeframes = Timeframe::get(
			[ $this->locationId ],
			[ $this->itemId ],
		);
		$this->assertEquals( 1, count( $inLocationAndItemTimeframes ) );
		$this->assertEquals( $this->timeframeId, $inLocationAndItemTimeframes[0]->ID );
	}

	public function testGetPostIdsByType_singleItem() {
		// Timeframe with enddate
		$this->timeframeId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			self::REPETITION_START,
			self::REPETITION_END
		);
		$postIds           = Timeframe::getPostIdsByType(
			[ \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID ],
			[ $this->itemId ],
		);
		$this->assertEquals( 1, count( $postIds ) );
		$this->assertEquals( $this->timeframeId, $postIds[0] );

		//test for one item that is first at one location and then at another location, should get both timeframes
		$otherLocationId = $this->createLocation( "Other Location" );
		$earlierStart    = new \DateTime();
		$earlierStart->setTimestamp( self::REPETITION_START );
		$earlierStart->modify( '-10 day' );
		$earlierEnd = clone $earlierStart;
		$earlierEnd->modify( '+5 day' );
		$otherTimeframeId = $this->createTimeframe(
			$otherLocationId,
			$this->itemId,
			$earlierStart->getTimestamp(),
			$earlierEnd->getTimestamp()
		);
		$postIds          = Timeframe::getPostIdsByType(
			[ \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID ],
			[ $this->itemId ]
		);
		$this->assertEquals( 2, count( $postIds ) );
		$postIds = array_map( 'intval', $postIds ); //the assertContains can not handle string/int comparison
		$this->assertContains( $this->timeframeId, $postIds );
		$this->assertContains( $otherTimeframeId, $postIds );
	}

	public function testGetPostIdsByType_singleLocation() {
		// Timeframe with enddate
		$this->timeframeId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			self::REPETITION_START,
			self::REPETITION_END
		);
		$postIds           = Timeframe::getPostIdsByType(
			[ \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID ],
			[],
			[ $this->locationId ]
		);
		$this->assertEquals( 1, count( $postIds ) );
		$this->assertEquals( $this->timeframeId, $postIds[0] );

		//test for one location that has two items, should get both timeframes
		$this->createOtherTFwithItemAtFirstLocation();
		$postIds = Timeframe::getPostIdsByType(
			[ \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID ],
			[],
			[ $this->locationId ]
		);
		$postIds = array_map( 'intval', $postIds ); //the assertContains can not handle string/int comparison
		$this->assertEquals( 2, count( $postIds ) );
		$this->assertContains( $this->timeframeId, $postIds );
		$this->assertContains( $this->otherTimeframeId, $postIds );
	}

	public function testGetPostIdsByType_singleLocationAndItem() {
		// Timeframe with enddate
		$this->timeframeId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			self::REPETITION_START,
			self::REPETITION_END
		);
		$postIds           = Timeframe::getPostIdsByType(
			[ \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID ],
			[ $this->itemId ],
			[ $this->locationId ]
		);
		$this->assertEquals( 1, count( $postIds ) );
		$this->assertEquals( $this->timeframeId, $postIds[0] );

		//test for one location that has two items and completely separate item/location combo should still only get the specific timeframe
		$this->createOtherTFwithItemAtFirstLocation();
		$postIds = Timeframe::getPostIdsByType(
			[ \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID ],
			[ $this->itemId ],
			[ $this->locationId ]
		);
		$this->assertEquals( 1, count( $postIds ) );
		$this->assertEquals( $this->timeframeId, $postIds[0] );
	}

	public function testGetPostIdsByType_oneLocationMultiItem() {
		$otherItemId = $this->createItem( "Other Item" );
		// Timeframe with enddate and two items
		$this->timeframeId = $this->createTimeframe(
			$this->locationId,
			[$this->itemId, $otherItemId],
			self::REPETITION_START,
			self::REPETITION_END
		);
		$fromFirstItem     = Timeframe::getPostIdsByType(
			[ \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID ],
			[ $this->itemId ],
			[ $this->locationId ]
		);
		$this->assertEquals( 1, count( $fromFirstItem ) );
		$this->assertEquals( $this->timeframeId, $fromFirstItem[0] );

		$fromSecondItem     = Timeframe::getPostIdsByType(
			[ \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID ],
			[ $otherItemId ],
			[ $this->locationId ]
		);
		$this->assertEquals( 1, count( $fromSecondItem ) );
		$this->assertEquals( $this->timeframeId, $fromSecondItem[0] );

		$fromBothItems     = Timeframe::getPostIdsByType(
			[ \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID ],
			[ $this->itemId, $otherItemId ],
			[ $this->locationId ]
		);
		$this->assertEquals( 1, count( $fromBothItems ) );
		$this->assertEquals( $this->timeframeId, $fromBothItems[0] );
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
			self::REPETITION_START,
			self::REPETITION_END,
		);
		$this->createOtherTimeframe();

		//create holiday applicable for both
		$holidayId = $this->createTimeframe(
			[$this->locationId, $this->otherLocationId],
			[$this->itemId, $this->otherItemId],
			self::REPETITION_START,
			self::REPETITION_END,
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
