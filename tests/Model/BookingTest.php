<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Model\Booking;
use CommonsBooking\Model\Item;
use CommonsBooking\Model\Location;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Plugin;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class BookingTest extends CustomPostTypeTest {

	private Booking $testBookingTomorrow;
	private Booking $testBookingPast;
	private int $testBookingId;
	private Item  $testItem;
	private Location $testLocation;
	private Timeframe $testTimeFrame;
	/**
	 * @var int|\WP_Error
	 */
	private int $testBookingPastId;

	public function testGetBookableTimeFrame() {
		$this->assertEquals($this->testTimeFrame,$this->testBookingTomorrow->getBookableTimeFrame());
	}

	public function testGetLocation() {
		$this->assertEquals($this->testLocation,$this->testBookingTomorrow->getLocation());
	}

	public function testCancel() {
		$this->testBookingTomorrow->cancel();
		$this->testBookingPast->cancel();
		//flush cache to reflect updated post
		wp_cache_flush();
		$this->testBookingTomorrow = new Booking(get_post($this->testBookingId));
		$this->testBookingPast = new Booking(get_post($this->testBookingPastId));
		$this->assertTrue($this->testBookingTomorrow->isCancelled());
		$this->assertFalse($this->testBookingPast->isCancelled());
		parent::tearDownAllBookings();
		wp_cache_flush();
		$this->setUpTestBooking();
	}

	public function testGetItem() {
		$this->assertEquals($this->testItem,$this->testBookingTomorrow->getItem());
	}

	public function testIsPast(){
		$this->assertFalse($this->testBookingTomorrow->isPast());
		$this->assertTrue($this->testBookingPast->isPast());
	}
	
	public function testCanCancel() {
				
		// Case: Booking in the past, no one can cancel
		$this->assertFalse( $this->testBookingPast->canCancel() );
		
		// Case: Booking in the future and same author
		wp_set_current_user( self::USER_ID );
		$regularUserBooking = new Booking(
			$this->createBooking(
				$this->locationId,
				$this->itemId,
				strtotime( '+1 day', strtotime(self::CURRENT_DATE)),
				strtotime( '+2 days', strtotime(self::CURRENT_DATE)),
				self::USER_ID
			)
		);
		$this->assertTrue( $regularUserBooking->canCancel() );

		// Case: role can edit and != post_author => can cancel
		// Prerequisites
		$userId = wp_create_user('user_who_can_edit', '');
		$userObj = get_user_by( 'id', $userId );
		$userObj->remove_role( 'subscriber' );
		$userObj->add_role( 'administrator' );
		wp_set_current_user( $userId );
		// Pre-Test asserts
		$this->assertTrue( $userObj->exists() );
		$this->assertTrue( Plugin::isPostCustomPostType( $regularUserBooking ) );
		$this->assertTrue( commonsbooking_isUserAdmin( $userObj ) );
		$this->assertNotSame( $userObj->ID, intval( $regularUserBooking->post_author ) );
		$this->assertTrue( commonsbooking_isUserAllowedToSee( $regularUserBooking->getPost(), $userObj ) );
		$this->assertTrue( current_user_can('administrator' ) );
		$this->assertTrue( current_user_can('edit_posts' ) );
		$this->assertTrue( current_user_can('edit_others_posts' ) );
		// TODO investigate further, $userObj (with admin role) isn't able to edit post
		/*$this->assertTrue( current_user_can('edit_post', $this->testBookingId ) );
		$this->assertTrue( commonsbooking_isUserAllowedToEdit( $this->testBookingTomorrow->getPost(), $userObj ) );
		$this->assertTrue( commonsbooking_isCurrentUserAllowedToEdit( $this->testBookingId ) );
		$this->assertTrue( commonsbooking_isCurrentUserAdmin() );
		// Tests
		$this->assertTrue(  $this->testBookingTomorrow->canCancel() );*/
		
		// Case: role cannot edit, role != post_author, booking in the future => can't cancel
		$userObj->remove_role( 'administrator' );
		$userObj->add_role( 'subscriber' );
		// Tests
		$this->assertFalse(  $regularUserBooking->canCancel() );
		
	}

	protected function setUpTestBooking():void{
		$this->testBookingId       = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '+1 day', strtotime(self::CURRENT_DATE)),
			strtotime( '+2 days', strtotime(self::CURRENT_DATE))
		);
		$this->testBookingTomorrow = new Booking(get_post($this->testBookingId));
		$this->testBookingPastId       = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime('-2 days', strtotime(self::CURRENT_DATE)),
			strtotime('-1 day', strtotime(self::CURRENT_DATE))
		);
		$this->testBookingPast = new Booking(get_post($this->testBookingPastId));
	}

	protected function setUp() : void {
		parent::setUp();

		$this->firstTimeframeId   = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '-5 days',strtotime(self::CURRENT_DATE)),
			strtotime( '+90 days', strtotime(self::CURRENT_DATE))
		);
		$this->testItem = new Item(get_post($this->itemId));
		$this->testLocation = new Location(get_post($this->locationId));
		$this->testTimeFrame = new Timeframe(get_post($this->firstTimeframeId));
		$this->setUpTestBooking();
		$this->createSubscriber();
	}

	protected function tearDown() : void {
		parent::tearDown();
	}
}
