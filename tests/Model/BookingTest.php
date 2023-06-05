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

		$this->assertTrue( Plugin::isPostCustomPostType( $this->subscriberBookingInFuture->getPost() ) );
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

		// TODO investigate further, $userObj (with admin role) isn't able to edit post
		/*$this->assertTrue( current_user_can('edit_post', $this->testBookingId ) );
		$this->assertTrue( commonsbooking_isUserAllowedToEdit( $this->testBookingTomorrow->getPost(), $userObj ) );
		$this->assertTrue( commonsbooking_isCurrentUserAllowedToEdit( $this->testBookingId ) );
		$this->assertTrue( commonsbooking_isCurrentUserAdmin() );*/
	}

	/**
	 * This tests the distinctions of CB Managers when they have been assigned an item or not.
	 * Generally speaking, CB Managers should be able to only cancel bookings of items / locations they manage.
	 * @return void
	 */
	public function testCanCancelCBManagerNoAssignment() {
		//Case : CB Manager should not be able to cancel because the location/item of the booking is not his
		wp_set_current_user( $this->cbManagerUserID );
		$managerUserObj = get_user_by( 'ID', $this->cbManagerUserID );
		//important distinction, CB Manager is not an admin
		$this->assertFalse( commonsbooking_isUserAdmin( $managerUserObj ) );
		$this->assertNotSame( $managerUserObj->ID, intval( $this->subscriberBookingInFuture->post_author ) );
		$this->assertFalse( commonsbooking_isUserAllowedToSee( $this->subscriberBookingInFuture->getPost(), $managerUserObj ) );
	}

	/**
	 * TODO: This test is currently not working, the CB Manager somehow lacks the capability to edit the booking.
	 *       Investigate.
	 * @return void
	 * @throws \Exception
	 */
	/*
	public function testCanCancelCBManagerItemAssignment() {
		$managerUserObj = get_user_by( 'ID', $this->cbManagerUserID );
		//let's now create a new item, location and timeframe where the CB Manager is the ITEM manager
		$managedItem         = new Item(
			$this->createItem(
				"Managed Item",
				'publish',
				[ $this->cbManagerUserID ]
			)
		);
		$unmanagedLocation   = new Location(
			$this->createLocation(
				"Unmanaged Location",
				'publish'
			)
		);
		$manageTestTimeframe = new Timeframe(
			$this->createBookableTimeFrameIncludingCurrentDay(
				$managedItem->ID(),
				$unmanagedLocation->ID()
			)
		);
		$testBookingTomorrow = new Booking(
			$this->createBooking(
				$unmanagedLocation->ID(),
				$managedItem->ID(),
				strtotime( self::CURRENT_DATE ),
				strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
				'08:00 AM',
				'12:00 PM',
				'confirmed',
				$this->subscriberId
			)
		);
		//and check if the CB Manager can see & cancel it (because it is their item)
		$this->assertTrue( commonsbooking_isUserAllowedToSee( $testBookingTomorrow, $managerUserObj ) );
		$this->assertTrue( commonsbooking_isUserAllowedToEdit( $testBookingTomorrow, $managerUserObj ) );
		wp_set_current_user( $this->cbManagerUserID );
		$this->assertTrue( commonsbooking_isCurrentUserAllowedToSee( $testBookingTomorrow ) );
		$this->assertTrue( commonsbooking_isCurrentUserAllowedToEdit( $testBookingTomorrow ) );
		$this->assertTrue( $testBookingTomorrow->canCancel() );
	}
	*/

	/**
	 * TODO: This test is currently not working, the CB Manager somehow lacks the capability to edit the booking.
	 *       Investigate.
	 * @return void
	 * @throws \Exception
	 */
	/*
	public function testCanCancelCBManagerLocationAssignment() {
		$managerUserObj = get_user_by( 'ID', $this->cbManagerUserID );
		//let's now create a new item, location and timeframe where the CB Manager is the LOCATION manager
		$unmanagedItem = new Item(
			$this->createItem(
				"Unmanaged Item",
				'publish'
			)
		);
		$managedLocation = new Location(
			$this->createLocation(
				"Managed Location",
				'publish',
				[$this->cbManagerUserID]
			)
		);
		$manageTestTimeframe = new Timeframe(
			$this->createBookableTimeFrameIncludingCurrentDay(
				$unmanagedItem->ID(),
				$managedLocation->ID()
			)
		);
		$testBookingTomorrow2 = new Booking(
			$this->createBooking(
				$managedLocation->ID(),
				$unmanagedItem->ID(),
				strtotime(self::CURRENT_DATE),
				strtotime( '+1 day', strtotime(self::CURRENT_DATE)),
				'08:00 AM',
				'12:00 PM',
				'confirmed',
				$this->subscriberId
			)
		);
		//and check if the CB Manager can see & cancel it (because it is their location)
		$this->assertTrue( commonsbooking_isUserAllowedToSee( $testBookingTomorrow2, $managerUserObj ) );
		$this->assertTrue( commonsbooking_isUserAllowedToEdit( $testBookingTomorrow2, $managerUserObj ) );
		wp_set_current_user($this->cbManagerUserID);
		$this->assertTrue( commonsbooking_isCurrentUserAllowedToSee( $testBookingTomorrow2 ) );
		$this->assertTrue( commonsbooking_isCurrentUserAllowedToEdit( $testBookingTomorrow2 ) );
		$this->assertTrue( $testBookingTomorrow2->canCancel() );
	}
	*/

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
