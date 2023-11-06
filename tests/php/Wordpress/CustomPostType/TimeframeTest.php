<?php

namespace CommonsBooking\Tests\Wordpress\CustomPostType;

use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;


class TimeframeTest extends CustomPostTypeTest {

	public $testPostId;

	protected function setUp(): void {
		parent::setUp();

		$this->testPostId = wp_insert_post( [
			'post_title'   => 'Booking'
		] );

		// Timeframe is a booking
		update_post_meta( $this->testPostId, 'type', Timeframe::BOOKING_ID );
	}

	protected function tearDown() : void {
		parent::tearDown();
	}

	public function testIsLocked() {
		$timeframe = get_post( $this->testPostId );
		$this->assertTrue( Timeframe::isLocked( $timeframe ) );
	}

	public function testIsOverBookable() {
		$timeframe = get_post( $this->testPostId );
		$this->assertFalse( Timeframe::isOverBookable( $timeframe ) );
	}

	public function testGetTimeframeRepetitions() {
		$this->assertIsArray( Timeframe::getTimeFrameRepetitions( ) );
	}

	/**
	 * Tests that the save post function validates the timeframe and saves it as draft if it is invalid.
	 * Also tests, that the timeframes with manual repetition are assigned a valid REPETITION_START and REPETITION_END dynamically.
	 * @return void
	 */
	public function testPostSaving() {
		$validDailyTimeframe = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+10 days', strtotime( self::CURRENT_DATE ) ),
			Timeframe::BOOKABLE_ID,
			'on',
			'd'
		);
		$timeframeCPT = new Timeframe();
		$timeframeCPT->savePost( $validDailyTimeframe , get_post( $validDailyTimeframe ) );

		$this->assertEquals( 'publish', get_post_status( $validDailyTimeframe ) );

		$invalidDailyTimeframe = $this->createTimeframe(
			null,
			$this->itemId,
			strtotime( self::CURRENT_DATE ),
			strtotime( '-10 days', strtotime( self::CURRENT_DATE ) ),
			Timeframe::BOOKABLE_ID,
			'on',
			'd'
		);
		$timeframeCPT->savePost( $invalidDailyTimeframe , get_post( $invalidDailyTimeframe ) );
		$this->assertEquals( 'draft', get_post_status( $invalidDailyTimeframe ) );

		$manualRepetitionTimeframe = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			null,
			null,
			Timeframe::BOOKABLE_ID,
			'on',
			'manual',
			0,
			'08:00 AM',
			'12:00 PM',
			'publish',
			[ "1", "2", "3", "4", "5", "6", "7" ],
			"{$this->dateFormatted}"
		);
		$timeframeCPT->savePost( $manualRepetitionTimeframe , get_post( $manualRepetitionTimeframe ) );
		$this->assertEquals( 'publish', get_post_status( $manualRepetitionTimeframe ) );
		$this->assertEquals( strtotime(self::CURRENT_DATE), get_post_meta( $manualRepetitionTimeframe, \CommonsBooking\Model\Timeframe::REPETITION_START, true ) );
		//the end date is always moved to the last second of the day
		$this->assertEquals( strtotime('+23 Hours +59 Minutes +59 Seconds',strtotime(self::CURRENT_DATE) ), get_post_meta( $manualRepetitionTimeframe, \CommonsBooking\Model\Timeframe::REPETITION_END, true ) );

	}

	public function testManageTimeframeMeta() {
		//First, let's test if we can assign a timeframe to all items
		$timeframeId = $this->createBookableTimeFrameIncludingCurrentDay();
		//We now create a second item that can be automatically assigned with the "ALL" option
		$secondItemId = $this->createItem('Second Item');
		//now, let's set our timeframe to be assigned to all items
		update_post_meta( $timeframeId,
			\CommonsBooking\Model\Timeframe::META_ITEM_SELECTION_TYPE,
			\CommonsBooking\Model\Timeframe::SELECTION_ALL_ID
		);
		//and run our function to update the information
		\CommonsBooking\Wordpress\CustomPostType\Timeframe::manageTimeframeMeta($timeframeId);
		//now, let's check if our second item is assigned to our timeframe by getting the timeframe with the specific second item
		$timeframe = \CommonsBooking\Repository\Timeframe::get(
			[$this->locationId],
			[$secondItemId],
		);
		$this->assertEquals( 1, count($timeframe) );
		$this->assertEquals( $timeframeId, $timeframe[0]->ID );

		\CommonsBooking\Plugin::registerItemTaxonomy();

		//now let's assign our item to a category, that timeframe also to the same category and check if we can still get the timeframe
		$term = wp_create_term( 'Test Category', Item::getPostType() . 's_category' );
		wp_set_post_terms( $secondItemId, [$term['term_id']], Item::getPostType() . 's_category' );
		//check, if our item is assigned to the category
		$terms = wp_get_post_terms( $secondItemId, Item::getPostType() . 's_category' );
		$this->assertEquals( 1, count($terms) );
		$this->assertEquals( $term['term_id'], $terms[0]->term_id );

		//now, let's assign our timeframe meta to the same category, we clear the multi select
		update_post_meta( $timeframeId,
			\CommonsBooking\Model\Timeframe::META_ITEM_IDS,
			[]
		);
		update_post_meta( $timeframeId,
			\CommonsBooking\Model\Timeframe::META_ITEM_SELECTION_TYPE,
			\CommonsBooking\Model\Timeframe::SELECTION_CATEGORY_ID
		);
		update_post_meta( $timeframeId,
			\CommonsBooking\Model\Timeframe::META_ITEM_CATEGORY_IDS,
			([strval($term['term_id'])])
		);
		//and run our function to update the information
		\CommonsBooking\Wordpress\CustomPostType\Timeframe::manageTimeframeMeta($timeframeId);
		//now, let's check if our second item is assigned to our timeframe by getting the timeframe with the specific second item
		$timeframe = \CommonsBooking\Repository\Timeframe::get(
			[$this->locationId],
			[$secondItemId],
		);
		$this->assertEquals( 1, count($timeframe) );
		$this->assertEquals( $timeframeId, $timeframe[0]->ID );
	}

}
