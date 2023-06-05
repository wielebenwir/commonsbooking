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
	private Booking $subscriberBookingInFuture;
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
	
	public function testCanCancelBaseCase() {
				
		// Case: Booking in the past, no one can cancel
		$this->assertFalse( $this->testBookingPast->canCancel() );
		
		// Case: Booking in the future and same author
		wp_set_current_user( $this->subscriberId );

		$this->assertTrue( Plugin::isPostCustomPostType( $this->subscriberBookingInFuture ) );
		$this->assertTrue( $this->subscriberBookingInFuture->canCancel() );

		// Case: role can edit and != post_author => can cancel | This is the case for WordPress administrators

		wp_set_current_user( $this->adminUserID );
		$this->assertTrue( current_user_can('administrator' ) );
		$this->assertTrue( current_user_can('edit_posts' ) );
		$this->assertTrue( current_user_can('edit_others_posts' ) );
		$userObj = get_user_by( 'ID', $this->adminUserID );

		$this->assertTrue( commonsbooking_isUserAdmin( $userObj ) );

		$this->assertNotSame( $userObj->ID, intval( $this->subscriberBookingInFuture->post_author ) );

		//once with WP_Post object
		$this->assertTrue( commonsbooking_isUserAllowedToSee( $this->subscriberBookingInFuture->getPost(), $userObj ) );
		$this->assertTrue( commonsbooking_isUserAllowedToEdit( $this->subscriberBookingInFuture->getPost(), $userObj ) );

		//and with Model
		$this->assertTrue( commonsbooking_isUserAllowedToSee( $this->subscriberBookingInFuture, $userObj ) );
		$this->assertTrue( commonsbooking_isUserAllowedToEdit( $this->subscriberBookingInFuture, $userObj ) );

		$this->assertTrue(  $this->testBookingTomorrow->canCancel() );

		// TODO admin $userObj won't be able to edit post
		/*$this->assertTrue( current_user_can('edit_post', $this->testBookingId ) );
		$this->assertTrue( commonsbooking_isUserAllowedToEdit( $this->testBookingTomorrow->getPost(), $userObj ) );
		$this->assertTrue( commonsbooking_isCurrentUserAllowedToEdit( $this->testBookingId ) );
		$this->assertTrue( commonsbooking_isCurrentUserAdmin() );
		$this->assertTrue(  $this->testBookingTomorrow->canCancel() );*/
		
		// Case: role cannot edit, role != post_author, booking in the future => can't cancel
		$userObj->remove_role( 'administrator' );
		$userObj->add_role( 'subscriber' );
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
		$this->subscriberBookingInFuture = new Booking(
			$this->createBooking(
				$this->locationId,
				$this->itemId,
				strtotime( '+1 day', strtotime(self::CURRENT_DATE)),
				strtotime( '+2 days', strtotime(self::CURRENT_DATE)),
				'08:00 AM',
				'12:00 PM',
				'confirmed',
				$this->subscriberId
			)
		);
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
		$this->createSubscriber();
		$this->createAdministrator();
		$this->createCBManager();
		$this->setUpTestBooking();
	}

	protected function tearDown() : void {
		parent::tearDown();
	}
}
