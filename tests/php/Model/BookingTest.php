<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Model\Booking;
use CommonsBooking\Model\Item;
use CommonsBooking\Model\Location;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Plugin;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use SlopeIt\ClockMock\ClockMock;

class BookingTest extends CustomPostTypeTest {

	private Booking $testBookingTomorrow;
	private Booking $testBookingPast;
	private Booking $testBookingSpanningOverTwoSlots;
	private Booking $testBookingFixedDate;
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
		ClockMock::freeze(new \DateTime(self::CURRENT_DATE));
		$this->assertEquals($this->testTimeFrame,$this->testBookingTomorrow->getBookableTimeFrame());
	}

	public function testGetLocation() {
		$this->assertEquals($this->testLocation,$this->testBookingTomorrow->getLocation());
	}

	public function testCancel() {
		ClockMock::freeze(new \DateTime(self::CURRENT_DATE));
		$this->testBookingTomorrow->cancel();
		$this->cancelBooking($this->testBookingPast);
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
		ClockMock::freeze(new \DateTime(self::CURRENT_DATE));
		$this->assertFalse($this->testBookingTomorrow->isPast());
		$this->assertTrue($this->testBookingPast->isPast());
	}

	public function testTermsApply(){
		\CommonsBooking\Plugin::registerItemTaxonomy();
		//now let's assign our item to a category, that timeframe also to the same category and check if we can still get the timeframe
		$taxonomy  = \CommonsBooking\Wordpress\CustomPostType\Item::getPostType() . 's_category';
		$term      = wp_create_term( 'Test Category', $taxonomy );
		$otherTerm = wp_create_term( 'Other Category', $taxonomy );
		wp_set_post_terms( $this->itemId, [$term['term_id']], $taxonomy );
		$this->assertTrue($this->testBookingTomorrow->termsApply($term['term_id']));
		$this->assertFalse($this->testBookingTomorrow->termsApply($otherTerm['term_id']));
	}

	public function testFilterTermsApply() {
		\CommonsBooking\Plugin::registerItemTaxonomy();
		//now let's assign our item to a category, that timeframe also to the same category and check if we can still get the timeframe
		$taxonomy  = \CommonsBooking\Wordpress\CustomPostType\Item::getPostType() . 's_category';
		$term      = wp_create_term( 'Test Category', $taxonomy );
		$otherItem = $this->createItem( "Other Item",'publish' );
		$otherLocation = $this->createLocation( "Other Location",'publish' );
		$otherTimeframe = $this->createBookableTimeFrameIncludingCurrentDay($otherLocation,$otherItem);
		$otherBooking = $this->createBooking(
			$otherLocation,
			$otherItem,
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+2 days', strtotime( self::CURRENT_DATE ) ),
		);
		wp_set_post_terms( $this->itemId, [ $term['term_id'] ], $taxonomy );
		$bookingArr = array(
			$this->testBookingTomorrow,
			new Booking ($otherBooking)
		);
		$filteredBookings = Booking::filterTermsApply($bookingArr,$term['term_id']);
		$this->assertEquals(1,count($filteredBookings));
		$this->assertEquals($this->testBookingTomorrow,$filteredBookings[0]);
	}

	public function testGetDuration(){
		$this->assertEquals(1,$this->testBookingTomorrow->getDuration());
		$this->assertEquals(1,$this->testBookingPast->getDuration());

		//we now create a booking and cancel it shortly after it ends
		//this way we can test if all days are counted when it is cancelled shortly before end of the tf
		$endTime       = strtotime( '+4 days', strtotime( self::CURRENT_DATE ) );
		$cancelBookingId          = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			$endTime,
		);
		$cancelBooking = new Booking(
			$cancelBookingId
		);
		$this->assertEquals(3,$cancelBooking->getDuration());
		$shortlyBeforeEnd = new \DateTime();
		$shortlyBeforeEnd->setTimestamp($endTime)->modify('-10 minutes');
		ClockMock::freeze($shortlyBeforeEnd);
		$this->cancelBooking($cancelBooking);
		$cancelBooking = new Booking( $cancelBookingId );

		$this->assertEquals(3,$cancelBooking->getDuration());

		//now we test what happens when a booking is cancelled in the middle of the tf
		$endTime         = strtotime( '+5 days', strtotime( self::CURRENT_DATE ) );
		$cancelBookingId            = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			$endTime,
		);
		$cancelBooking = new Booking(
			$cancelBookingId
		);
		$this->assertEquals(4,$cancelBooking->getDuration());
		$halfBeforeEnd = new \DateTime();
		$halfBeforeEnd->setTimestamp($endTime)->modify('-2 days');
		ClockMock::freeze($halfBeforeEnd);
		$this->cancelBooking($cancelBooking);
		$cancelBooking = new Booking( $cancelBookingId );
		$this->assertEquals(2,$cancelBooking->getDuration());

		//now we test what happens when a booking is cancelled before it starts. It should have a length of 0
		$cancelBookingId = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '+15 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+17 days', strtotime( self::CURRENT_DATE ) ),
		);
		$cancelBooking = new Booking(
			$cancelBookingId
		);
		$this->assertEquals(2,$cancelBooking->getDuration());
		$shortlyBeforeStart = new \DateTime();
		$shortlyBeforeStart->setTimestamp(strtotime( '+10 day', strtotime( self::CURRENT_DATE ) ));
		ClockMock::freeze($shortlyBeforeStart);
		$this->cancelBooking($cancelBooking);
		$cancelBooking = new Booking( $cancelBookingId );
		$this->assertEquals(0,$cancelBooking->getDuration());

		// now test with overbooked days
		update_post_meta( $this->locationId, COMMONSBOOKING_METABOX_PREFIX. 'count_lockdays_in_range', 'on' );
		update_post_meta( $this->locationId, COMMONSBOOKING_METABOX_PREFIX. 'count_lockdays_maximum', '1' );
		$overbookedBooking = new Booking( $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+4 days', strtotime( self::CURRENT_DATE ) ),
		));
		$overbookedBooking->setOverbookedDays(2);
		$this->assertEquals(2,$overbookedBooking->getDuration());

	}

	public function testConfirm() {
		$this->assertTrue( $this->testBookingTomorrow->isConfirmed() );
	}

	public function testUnconfirm() {
		// Create booking
		$bookingId       = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '+1 day', strtotime(self::CURRENT_DATE)),
			strtotime( '+2 days', strtotime(self::CURRENT_DATE)),
			'8:00 AM',
			'12:00 PM',
			'unconfirmed',
		);
		$bookingObj = new Booking( $bookingId );
		$this->assertTrue( $bookingObj->isUnconfirmed() );
	}

	public function testReturnComment() {
		$this->assertEquals( '', $this->testBookingTomorrow->returnComment() );

		// Updates meta value
		$commentValue = "Comment on this";
		update_post_meta( $this->testBookingId, 'comment', $commentValue );
		wp_cache_flush();
		$this->testBookingTomorrow = new Booking( $this->testBookingId );
		$this->assertEquals( $commentValue, $this->testBookingTomorrow->returnComment() );
	}

	public function testPickupDatetime() {
		// TODO 12- 12 correct?
		$this->assertEquals( 'July 2, 2021 12:00 am - 12:00 am', $this->testBookingTomorrow->pickupDatetime() );

		//Test Pickup for Slot Timeframes (#1342)
		$this->assertEquals( self::CURRENT_DATE_FORMATTED . ' 10:00 am - 3:00 pm', $this->testBookingSpanningOverTwoSlots->pickupDatetime() );
	}

	public function testReturnDatetime() {
		// TODO 12:01? correct
		$this->assertEquals( 'July 3, 2021 8:00 am - 12:01 am', $this->testBookingTomorrow->returnDatetime() );

		//Test Return for Slot Timeframes (#1342)
		$this->assertEquals( self::CURRENT_DATE_FORMATTED . ' 3:00 pm - 6:00 pm', $this->testBookingSpanningOverTwoSlots->returnDatetime() );
	}

	public function testShowBookingCodes() {
		$this->assertFalse( $this->testBookingTomorrow->showBookingCodes() );

		// Updates meta value
		update_post_meta( $this->testBookingId, Timeframe::META_SHOW_BOOKING_CODES, 'on' );
		wp_cache_flush();
		$this->testBookingTomorrow = new Booking( $this->testBookingId );
		$this->assertTrue( $this->testBookingTomorrow->showBookingCodes() );
	}

	public function testAssignBookableTimeframeFields() {
		// Prerequesites
		$timeframe = $this->testBookingTomorrow->getBookableTimeFrame();
		$this->assertNotNull( $timeframe );

		$neededMetaFields = [
				'full-day',
				'grid',
				'start-time',
				'end-time',
				Timeframe::META_SHOW_BOOKING_CODES,
				'timeframe-max-days',
			];

		// assert meta value timeframe not null and booking null
		foreach ( $neededMetaFields as $fieldName ) {
				$this->assertNotNull( get_post_meta(
					$timeframe->ID,
					$fieldName,
					true
				));

				//$this->assertEquals( '', get_post_meta(
				//	$this->testBookingId,
				//	$fieldName,
				//	true
				//));
		}

		$this->testBookingTomorrow->assignBookableTimeframeFields();

		// assert meta values of booking are set
		foreach ( $neededMetaFields as $fieldName ) {
				$this->assertNotNull( get_post_meta(
					$this->testBookingId,
					$fieldName,
					true
				));
		}
	}

	public function testFormattedBookingCode() {
		$this->assertEquals( '', $this->testBookingTomorrow->formattedBookingCode());
	}
public function testCanCancelBaseCase() {
		ClockMock::freeze(new \DateTime(self::CURRENT_DATE));

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

		$this->assertTrue( current_user_can('edit_post', $this->testBookingId ) );
		$this->assertTrue( commonsbooking_isUserAllowedToEdit( $this->testBookingTomorrow->getPost(), $userObj ) );
		$this->assertTrue( commonsbooking_isCurrentUserAllowedToEdit( $this->testBookingId ) );
		$this->assertTrue( commonsbooking_isCurrentUserAdmin() );
	}

	public function testHasTotalBreakdown() {
		$testBooking = new Booking( $this->createConfirmedBookingStartingToday() );
		$this->assertFalse( $this->testBookingTomorrow->hasTotalBreakdown() );
		//create a total breakdown
		$totalBreakdown = $this->createRestriction(
			\CommonsBooking\Model\Restriction::TYPE_REPAIR,
			$this->locationId,
			$this->itemId,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) )
		);
		$this->assertTrue( $testBooking->hasTotalBreakdown() );
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
	 *  Will test the case where the CB Manager is assigned to the item of the booking. They should be able to cancel and edit the booking.
	 * @return void
	 * @throws \Exception
	 */
	public function testCanCancelCBManagerItemAssignment() {
		ClockMock::freeze(new \DateTime(self::CURRENT_DATE));
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

	/**
	 * Will test the case of a custom user role that has been assigned to the item or location of the booking but does not have
	 * the edit_other_cb_bookings capability. They should not be able to cancel or edit the booking but should be able to see it.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function testOnlyViewerRole() {
		$subscriberUserObj = get_user_by( 'ID', $this->subscriberId );
		ClockMock::freeze(new \DateTime(self::CURRENT_DATE));
		//let's now create a new item, location and timeframe and assign the SUBSCRIBER to it
		$managedItem         = new Item(
			$this->createItem(
				"Managed Item",
				'publish',
				[ $this->subscriberId ]
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
			)
		);

		//just to make sure our test works, let's check that the booking is not from the subscriber
		$this->assertNotSame( $subscriberUserObj->ID, $testBookingTomorrow->getUserData()->ID );
		//the subscriber should be able to see the booking but because they lack the necessary role capabilities, they should not be able to edit or cancel it
		$this->assertTrue( commonsbooking_isUserAllowedToSee( $testBookingTomorrow, $subscriberUserObj ) );
		$this->assertFalse( commonsbooking_isUserAllowedToEdit( $testBookingTomorrow, $subscriberUserObj ) );
		wp_set_current_user( $this->subscriberId );
		$this->assertTrue( commonsbooking_isCurrentUserAllowedToSee( $testBookingTomorrow ) );
		$this->assertFalse( commonsbooking_isCurrentUserAllowedToEdit( $testBookingTomorrow ) );
		$this->assertFalse( $testBookingTomorrow->canCancel() );
	}

	/**
	 * Will test the case where the CB Manager is assigned to the location of the booking. They should be able to cancel and edit the booking.
	 * @return void
	 * @throws \Exception
	 */
	public function testCanCancelCBManagerLocationAssignment() {
		ClockMock::freeze(new \DateTime(self::CURRENT_DATE));
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


	public function testSetOverbookedDays() {
		//case 1: no overbooked days are counted towards the booking limit
		update_post_meta( $this->locationId, COMMONSBOOKING_METABOX_PREFIX . 'count_lockdays_in_range', 'off' );
		$this->assertEquals(2,$this->testBookingTomorrow->setOverbookedDays(2));
		$this->assertEquals(2, $this->testBookingTomorrow->getOverbookedDays());

		//case 2: one overbooked day is counted towards the booking limit, the rest was overbooked
		update_post_meta( $this->locationId, COMMONSBOOKING_METABOX_PREFIX . 'count_lockdays_in_range', 'on' );
		update_post_meta( $this->locationId, COMMONSBOOKING_METABOX_PREFIX . 'count_lockdays_maximum', '1' );
		$this->assertEquals(6,$this->testBookingTomorrow->setOverbookedDays(7));
		$this->assertEquals(6, $this->testBookingTomorrow->getOverbookedDays());

		//case 3: two days are counted but only one is overbooked
		update_post_meta( $this->locationId, COMMONSBOOKING_METABOX_PREFIX . 'count_lockdays_maximum', '2' );
		$this->assertEquals(0,$this->testBookingTomorrow->setOverbookedDays(1));
		$this->assertEquals(0, $this->testBookingTomorrow->getOverbookedDays());

		//case 4: all overbooked days are counted towards the booking limit
		update_post_meta( $this->locationId, COMMONSBOOKING_METABOX_PREFIX . 'count_lockdays_maximum', '0' );
		$this->assertEquals(0,$this->testBookingTomorrow->setOverbookedDays(2));
		$this->assertEquals(0, $this->testBookingTomorrow->getOverbookedDays());
	}

	protected function setUpTestBooking():void {
		$this->testBookingId       = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+2 days', strtotime( self::CURRENT_DATE ) ),
			'08:00 AM',
			'12:00 PM'
		);
		$this->testBookingTomorrow = new Booking( $this->testBookingId );
		$this->testBookingPastId   = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '-2 days', strtotime( self::CURRENT_DATE ) ),
			strtotime( '-1 day', strtotime( self::CURRENT_DATE ) )
		);
		$this->testBookingPast = new Booking(get_post($this->testBookingPastId));

		// Create fixed date booking
		$this->testBookingFixedDate       = new Booking (
			$this->createBooking(
				$this->locationId,
				$this->itemId,
				strtotime( '+1 day',  strtotime( self::CURRENT_DATE ) ),
				strtotime( '+2 days', strtotime( self::CURRENT_DATE ) ),
				'08:00 AM',
				'12:00 PM'
			)
		);
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

	/**
	 * Sets up a test booking that takes place at the CURRENT_DATE and goes from 10:00 to 17:59:59 while the two
	 * TFs go from 10:00 - 15:00 and from 15:00 - 18:00. Therefore the created booking spans over the two timeframes.
	 * @return void
	 * @throws \Exception
	 */
	protected function setUpTestBookingOverSlotsTimeframes(): void {
		$separateItem = $this->createItem("SlotItem", 'publish');
		$separateLocation = $this->createLocation("SlotLocation",'publish');
		$this->createTwoBookableTimeframeSlotsIncludingCurrentDay($separateLocation,$separateItem);
		//create booking spanning over two slots
		$beginningTime = new \DateTime(self::CURRENT_DATE);
		$beginningTime->setTime(10,00);
		$endingTime = new \DateTime(self::CURRENT_DATE);
		$endingTime->setTime(17,59,59);
		//we need to create this booking in the "frontend" way in order to save the correct grid sizes for the generation
		//pickup and returntimes
		$testBookingSpanningOverTwoSlotsID  = \CommonsBooking\Wordpress\CustomPostType\Booking::handleBookingRequest(
			$separateItem,
			$separateLocation,
			'confirmed',
			null,
			null,
			$beginningTime->getTimestamp(),
			$endingTime->getTimestamp(),
			null,
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID
		);
		$this->testBookingSpanningOverTwoSlots = new Booking(
			$testBookingSpanningOverTwoSlotsID
		);
		//add this to the array so it can be destroyed later
		$this->bookingIds[] = $testBookingSpanningOverTwoSlotsID;
	}

	protected function setUp() : void {
		parent::setUp();

		$this->firstTimeframeId   = $this->createTimeframe(
            $this->locationId,
            $this->itemId,
            strtotime('-5 days', strtotime(self::CURRENT_DATE)),
            strtotime('+90 days', strtotime(self::CURRENT_DATE))
		);
		$this->testItem = new Item($this->itemId);
		$this->testLocation = new Location($this->locationId);
		$this->testTimeFrame = new Timeframe($this->firstTimeframeId);
		$this->createSubscriber();
		$this->createAdministrator();
		$this->createCBManager();
		$this->setUpTestBooking();
		$this->setUpTestBookingOverSlotsTimeframes();
	}

	protected function tearDown() : void {
		parent::tearDown();
	}
}
