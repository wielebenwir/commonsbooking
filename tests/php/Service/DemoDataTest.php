<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Service\DemoData;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use SlopeIt\ClockMock\ClockMock;

class DemoDataTest extends CustomPostTypeTest {

	protected function setUp(): void {
		parent::setUp();
		// Clean slate for every test: delete the flags set by DemoData
		delete_option( DemoData::CREATED_OPTION );
		delete_option( DemoData::INSTALL_DATE_OPTION );
	}

	protected function tearDown(): void {
		delete_option( DemoData::CREATED_OPTION );
		delete_option( DemoData::INSTALL_DATE_OPTION );
		parent::tearDown();
	}

	// -----------------------------------------------------------------------
	// shouldShowButton() tests
	// -----------------------------------------------------------------------

	public function testShouldShowButton_noDataNoInstallDate() {
		// No CPTs, no install date → should show
		$this->assertTrue( DemoData::shouldShowButton() );
	}

	public function testShouldShowButton_withinSevenDays() {
		update_option( DemoData::INSTALL_DATE_OPTION, time() );
		$this->assertTrue( DemoData::shouldShowButton() );
	}

	public function testShouldShowButton_afterSevenDays() {
		update_option( DemoData::INSTALL_DATE_OPTION, strtotime( '-8 days' ) );
		$this->assertFalse( DemoData::shouldShowButton() );
	}

	public function testShouldShowButton_withExistingLocation() {
		$this->createLocation( 'Existing Location', 'publish' );
		$this->assertFalse( DemoData::shouldShowButton() );
	}

	public function testShouldShowButton_withExistingItem() {
		$this->createItem( 'Existing Item', 'publish' );
		$this->assertFalse( DemoData::shouldShowButton() );
	}

	public function testShouldShowButton_afterDemoCreated() {
		update_option( DemoData::CREATED_OPTION, true );
		$this->assertFalse( DemoData::shouldShowButton() );
	}

	// -----------------------------------------------------------------------
	// create() tests
	// -----------------------------------------------------------------------

	public function testCreate_setsCreatedFlag() {
		DemoData::create();
		$this->assertTrue( DemoData::hasBeenCreated() );
	}

	public function testCreate_createsLocation() {
		DemoData::create();
		$locations = get_posts( [
			'post_type'      => 'cb_location',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		] );
		$this->assertNotEmpty( $locations );
		$titles = array_column( $locations, 'post_title' );
		$this->assertContains( 'Demo Location', $titles );
	}

	public function testCreate_createsItem() {
		DemoData::create();
		$items = get_posts( [
			'post_type'      => 'cb_item',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		] );
		$this->assertNotEmpty( $items );
		$titles = array_column( $items, 'post_title' );
		$this->assertContains( 'Demo Item', $titles );
	}

	public function testCreate_createsTimeframeWithCorrectMeta() {
		$result      = DemoData::create();
		$timeframes  = get_posts( [
			'post_type'      => 'cb_timeframe',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'title'          => 'Demo Timeframe',
		] );
		$this->assertNotEmpty( $timeframes );
		$tf = $timeframes[0];

		$locationId = get_post_meta( $tf->ID, 'location-id', true );
		$itemId     = get_post_meta( $tf->ID, 'item-id', true );

		$this->assertNotEmpty( $locationId );
		$this->assertNotEmpty( $itemId );
		$this->assertEquals( \CommonsBooking\Model\Timeframe::BOOKABLE_ID, (int) get_post_meta( $tf->ID, 'type', true ) );
	}

	public function testCreate_createsThreeBookings() {
		DemoData::create();
		$bookings = get_posts( [
			'post_type'      => 'cb_booking',
			'post_status'    => 'confirmed',
			'posts_per_page' => -1,
		] );
		$this->assertCount( 3, $bookings );
	}

	public function testCreate_twoPastBookings() {
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		DemoData::create();
		$bookings = get_posts( [
			'post_type'      => 'cb_booking',
			'post_status'    => 'confirmed',
			'posts_per_page' => -1,
		] );

		$now  = time();
		$past = array_filter( $bookings, function ( $b ) use ( $now ) {
			return (int) get_post_meta( $b->ID, 'repetition-end', true ) < $now;
		} );
		$this->assertCount( 2, $past );
		ClockMock::reset();
	}

	public function testCreate_oneFutureBooking() {
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		DemoData::create();
		$bookings = get_posts( [
			'post_type'      => 'cb_booking',
			'post_status'    => 'confirmed',
			'posts_per_page' => -1,
		] );

		$now    = time();
		$future = array_filter( $bookings, function ( $b ) use ( $now ) {
			return (int) get_post_meta( $b->ID, 'repetition-start', true ) > $now;
		} );
		$this->assertCount( 1, $future );
		ClockMock::reset();
	}

	public function testCreate_createsPrivatePage() {
		DemoData::create();
		$pages = get_posts( [
			'post_type'      => 'page',
			'post_status'    => 'private',
			'posts_per_page' => -1,
			'title'          => 'My Bookings',
		] );
		$this->assertNotEmpty( $pages );
		$this->assertStringContainsString( '[cb_bookings]', $pages[0]->post_content );
	}

	public function testCreate_returnsPageUrl() {
		$result = DemoData::create();
		$this->assertArrayHasKey( 'page_url', $result );
		$this->assertNotEmpty( $result['page_url'] );
	}
}
