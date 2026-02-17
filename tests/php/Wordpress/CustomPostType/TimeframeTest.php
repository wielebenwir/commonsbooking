<?php

namespace CommonsBooking\Tests\Wordpress\CustomPostType;

use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;


class TimeframeTest extends CustomPostTypeTest {

	public $testPostId;

	protected function setUp(): void {
		parent::setUp();

		$this->testPostId = wp_insert_post(
			[
				'post_title'   => 'Booking',
			]
		);

		// Timeframe is a booking
		update_post_meta( $this->testPostId, 'type', Timeframe::BOOKING_ID );
	}

	protected function tearDown(): void {
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
		$this->assertIsArray( Timeframe::getTimeFrameRepetitions() );
	}

	public function testGetSelectionOptions() {
		$this->createCBManager();
		$this->createAdministrator();
		wp_set_current_user( $this->adminUserID );
		$this->assertCount( 3, Timeframe::getSelectionOptions() );
		wp_set_current_user( $this->cbManagerUserID );
		$this->assertCount( 1, Timeframe::getSelectionOptions() );
	}

	public function testGetGridOptions() {
		$this->assertIsArray( Timeframe::getGridOptions() );
	}

	/**
	 * Tests that the save post function validates the timeframe and saves it as draft if it is invalid.
	 * Also tests, that the timeframes with manual repetition are assigned a valid REPETITION_START and REPETITION_END dynamically.
	 * @return void
	 */
	public function testPostSaving() {
		$validDailyTimeframe = $this->createTimeframe(
			$this->locationID,
			$this->itemID,
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+10 days', strtotime( self::CURRENT_DATE ) ),
			Timeframe::BOOKABLE_ID,
			'on',
			'd'
		);
		$timeframeCPT        = new Timeframe();
		$timeframeCPT->savePost( $validDailyTimeframe, get_post( $validDailyTimeframe ) );

		$this->assertEquals( 'publish', get_post_status( $validDailyTimeframe ) );

		$invalidDailyTimeframe = $this->createTimeframe(
			null,
			$this->itemID,
			strtotime( self::CURRENT_DATE ),
			strtotime( '-10 days', strtotime( self::CURRENT_DATE ) ),
			Timeframe::BOOKABLE_ID,
			'on',
			'd'
		);
		$timeframeCPT->savePost( $invalidDailyTimeframe, get_post( $invalidDailyTimeframe ) );
		$this->assertEquals( 'draft', get_post_status( $invalidDailyTimeframe ) );

		$manualRepetitionTimeframe = $this->createTimeframe(
			$this->locationID,
			$this->itemID,
			null,
			null,
			Timeframe::BOOKABLE_ID,
			'on',
			'manual',
			0,
			'08:00 AM',
			'12:00 PM',
			'publish',
			[ '1', '2', '3', '4', '5', '6', '7' ],
			"{$this->dateFormatted}"
		);
		$timeframeCPT->savePost( $manualRepetitionTimeframe, get_post( $manualRepetitionTimeframe ) );
		$this->assertEquals( 'publish', get_post_status( $manualRepetitionTimeframe ) );
		$this->assertEquals( strtotime( self::CURRENT_DATE ), get_post_meta( $manualRepetitionTimeframe, \CommonsBooking\Model\Timeframe::REPETITION_START, true ) );
		// the end date is always moved to the last second of the day
		$this->assertEquals( strtotime( '+23 Hours +59 Minutes +59 Seconds', strtotime( self::CURRENT_DATE ) ), get_post_meta( $manualRepetitionTimeframe, \CommonsBooking\Model\Timeframe::REPETITION_END, true ) );
	}

	public function testManageTimeframeMeta_withItemTaxonomy() {
		// First, let's test if we can assign a timeframe to all items
		$timeframeId = $this->createBookableTimeFrameIncludingCurrentDay();
		// We now create a second item that can be automatically assigned with the "ALL" option
		$secondItemId = $this->createItem( 'Second Item' );
		// now, let's set our timeframe to be assigned to all items
		update_post_meta(
			$timeframeId,
			\CommonsBooking\Model\Timeframe::META_ITEM_SELECTION_TYPE,
			\CommonsBooking\Model\Timeframe::SELECTION_ALL_ID
		);
		// and run our function to update the information
		\CommonsBooking\Wordpress\CustomPostType\Timeframe::manageTimeframeMeta( $timeframeId );
		// now, let's check if our second item is assigned to our timeframe by getting the timeframe with the specific second item
		$timeframe = \CommonsBooking\Repository\Timeframe::get(
			[ $this->locationID ],
			[ $secondItemId ],
		);
		$this->assertEquals( 1, count( $timeframe ) );
		$this->assertEquals( $timeframeId, $timeframe[0]->ID );

		\CommonsBooking\Wordpress\CustomPostType\Item::registerPostTypeTaxonomy();

		// Let's assign our item to a category, that timeframe also to the same category and check if we can still get the timeframe
		$term = wp_create_term( 'Test Category', Item::getTaxonomyName() );
		wp_set_post_terms( $secondItemId, [ $term['term_id'] ], Item::getTaxonomyName() );
		// check, if our item is assigned to the category
		$terms = wp_get_post_terms( $secondItemId, Item::getTaxonomyName() );
		$this->assertEquals( 1, count( $terms ) );
		$this->assertEquals( $term['term_id'], $terms[0]->term_id );

		// now, let's assign our timeframe meta to the same category, we clear the multi select
		update_post_meta(
			$timeframeId,
			\CommonsBooking\Model\Timeframe::META_ITEM_ID_LIST,
			[]
		);
		update_post_meta(
			$timeframeId,
			\CommonsBooking\Model\Timeframe::META_ITEM_SELECTION_TYPE,
			\CommonsBooking\Model\Timeframe::SELECTION_CATEGORY_ID
		);
		update_post_meta(
			$timeframeId,
			\CommonsBooking\Model\Timeframe::META_ITEM_CATEGORY_IDS,
			( [ strval( $term['term_id'] ) ] )
		);
		// and run our function to update the information
		\CommonsBooking\Wordpress\CustomPostType\Timeframe::manageTimeframeMeta( $timeframeId );
		// now, let's check if our second item is assigned to our timeframe by getting the timeframe with the specific second item
		$timeframe = \CommonsBooking\Repository\Timeframe::get(
			[ $this->locationID ],
			[ $secondItemId ],
		);
		$this->assertEquals( 1, count( $timeframe ) );
		$this->assertEquals( $timeframeId, $timeframe[0]->ID );
	}

	public function testManageTimeframeMeta_withLocationTaxonomy() {

		// First, let's test if we can assign a timeframe to all items
		$timeframeId = $this->createBookableTimeFrameIncludingCurrentDay();
		// We now create a second location that can be automatically assigned with the "ALL" option
		$secondLocationId = $this->createLocation( 'Second Location' );
		// now, let's set our timeframe to be assigned to all locations
		update_post_meta(
			$timeframeId,
			\CommonsBooking\Model\Timeframe::META_LOCATION_SELECTION_TYPE,
			\CommonsBooking\Model\Timeframe::SELECTION_ALL_ID
		);
		// and run our function to update the information
		\CommonsBooking\Wordpress\CustomPostType\Timeframe::manageTimeframeMeta( $timeframeId );
		// now, let's check if our second location is assigned to our timeframe by getting the timeframe with the
		// specific second location
		$timeframe = \CommonsBooking\Repository\Timeframe::get(
			[ $secondLocationId ],
			[ $this->itemID ],
		);
		$this->assertEquals( 1, count( $timeframe ) );
		$this->assertEquals( $timeframeId, $timeframe[0]->ID );

		\CommonsBooking\Wordpress\CustomPostType\Location::registerPostTypeTaxonomy();

		// Let's assign our location to a category, that timeframe also to the same category and check if we can
		// still get the timeframe
		$term = wp_create_term( 'Location Test Category', Location::getTaxonomyName() );
		wp_set_post_terms( $secondLocationId, [ $term['term_id'] ], Location::getTaxonomyName() );
		// check, if our location is assigned to the category
		$terms = wp_get_post_terms( $secondLocationId, Location::getTaxonomyName() );
		$this->assertEquals( 1, count( $terms ) );
		$this->assertEquals( $term['term_id'], $terms[0]->term_id );

		// now, let's assign our timeframe meta to the same category, we clear the multi select
		update_post_meta(
			$timeframeId,
			\CommonsBooking\Model\Timeframe::META_LOCATION_ID_LIST,
			[]
		);
		update_post_meta(
			$timeframeId,
			\CommonsBooking\Model\Timeframe::META_LOCATION_SELECTION_TYPE,
			\CommonsBooking\Model\Timeframe::SELECTION_CATEGORY_ID
		);
		update_post_meta(
			$timeframeId,
			\CommonsBooking\Model\Timeframe::META_LOCATION_CATEGORY_IDS,
			( [ strval( $term['term_id'] ) ] )
		);
		// and run our function to update the information
		\CommonsBooking\Wordpress\CustomPostType\Timeframe::manageTimeframeMeta( $timeframeId );
		// now, let's check if our second location is assigned to our timeframe by getting the timeframe
		// with the specific location
		$timeframe = \CommonsBooking\Repository\Timeframe::get(
			[ $secondLocationId ],
			[ $this->itemID ],
		);
		$this->assertEquals( 1, count( $timeframe ) );
		$this->assertEquals( $timeframeId, $timeframe[0]->ID );
	}

	/**
	 * This test just checks for the functionality of removing the multi-select option item-id-list and location-id-list
	 * for Timeframes that are NOT Holidays (not yet supported #507)
	 *
	 * The other part is tested in
	 * @see \CommonsBooking\Tests\Service\UpgradeTest::testRemoveBreakingPostmeta()
	 * @return void
	 */
	public function testRemoveIrrelevantPostmeta() {
		$tf = new \CommonsBooking\Model\Timeframe( $this->createBookableTimeFrameIncludingCurrentDay() );
		update_post_meta( $tf->ID, \CommonsBooking\Model\Timeframe::META_ITEM_ID_LIST, [ $this->itemID ] );
		update_post_meta( $tf->ID, \CommonsBooking\Model\Timeframe::META_LOCATION_ID_LIST, [ $this->locationID ] );
		update_post_meta( $tf->ID, \CommonsBooking\Model\Timeframe::META_LOCATION_SELECTION_TYPE, \CommonsBooking\Model\Timeframe::SELECTION_ALL_ID );
		update_post_meta( $tf->ID, \CommonsBooking\Model\Timeframe::META_ITEM_SELECTION_TYPE, \CommonsBooking\Model\Timeframe::SELECTION_ALL_ID );
		update_post_meta( $tf->ID, \CommonsBooking\Model\Timeframe::META_LOCATION_CATEGORY_IDS, [ '123' ] );
		update_post_meta( $tf->ID, \CommonsBooking\Model\Timeframe::META_ITEM_CATEGORY_IDS, [ '123' ] );

		Timeframe::removeIrrelevantPostmeta( $tf );
		// especially assert, that no item ids are assigned when updating the multi-select
		Timeframe::manageTimeframeMeta( $tf->ID );
		$this->assertEmpty( get_post_meta( $tf->ID, \CommonsBooking\Model\Timeframe::META_ITEM_ID_LIST, true ) );
		$this->assertEmpty( get_post_meta( $tf->ID, \CommonsBooking\Model\Timeframe::META_LOCATION_ID_LIST, true ) );
		$this->assertEmpty( get_post_meta( $tf->ID, \CommonsBooking\Model\Timeframe::META_LOCATION_SELECTION_TYPE, true ) );
		$this->assertEmpty( get_post_meta( $tf->ID, \CommonsBooking\Model\Timeframe::META_ITEM_SELECTION_TYPE, true ) );
		$this->assertEmpty( get_post_meta( $tf->ID, \CommonsBooking\Model\Timeframe::META_LOCATION_CATEGORY_IDS, true ) );
		$this->assertEmpty( get_post_meta( $tf->ID, \CommonsBooking\Model\Timeframe::META_ITEM_CATEGORY_IDS, true ) );
	}
}
