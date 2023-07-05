<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Model\Booking;
use CommonsBooking\Model\Item;
use CommonsBooking\Model\Location;
use CommonsBooking\Model\Timeframe;
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

	public function testGetLength(){
		$this->assertEquals(1,$this->testBookingTomorrow->getLength());
		$this->assertEquals(1,$this->testBookingPast->getLength());

	}


	public function testConfirm() {
		$this->assertTrue( $this->testBookingTomorrow->isConfirmed() );
	}

	public function testUnconfirm() {
		// Create booking
		$bookingId       = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '+1 day', time()),
			strtotime( '+2 days', time()),
			'8:00 AM',
			'12:00 PM',
			'unconfirmed',
		);
		$bookingObj = new Booking( get_post( $bookingId ) );

		$this->assertTrue( $bookingObj->isUnconfirmed() );
	}

	public function testReturnComment() {
		$this->assertEquals( '', $this->testBookingTomorrow->returnComment() );

		// Updates meta value
		$commentValue = "Comment on this";
		update_post_meta( $this->testBookingId, 'comment', $commentValue );
		wp_cache_flush();
		$this->testBookingTomorrow = new Booking( get_post( $this->testBookingId ) );

		$this->assertEquals( $commentValue, $this->testBookingTomorrow->returnComment() );
	}

	public function testPickupDatetime() {
		// TODO 12- 12 correct?
		$this->assertEquals( 'July 2, 2021 12:00 am - 12:00 am', $this->testBookingFixedDate->pickupDatetime() );
	}

	public function testReturnDatetime() {
		// TODO 12:01? correct
		$this->assertEquals( 'July 3, 2021 8:00 am - 12:01 am', $this->testBookingFixedDate->returnDatetime() );
	}

	public function testShowBookingCodes() {
		$this->assertFalse( $this->testBookingTomorrow->showBookingCodes() );

		// Updates meta value
		update_post_meta( $this->testBookingId, 'show-booking-codes', 'on' );
		wp_cache_flush();
		$this->testBookingTomorrow = new Booking( get_post( $this->testBookingId ) );

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
				'show-booking-codes',
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


	protected function setUpTestBooking():void{
		$this->testBookingId       = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '+1 day',  time() ),
			strtotime( '+2 days', time() )
		);
		$this->testBookingTomorrow = new Booking(get_post($this->testBookingId));
		$this->testBookingPastId       = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime('-2 days', time() ),
			strtotime('-1 day',  time() )
		);
		$this->testBookingPast = new Booking(get_post($this->testBookingPastId));

		// Create fixed date booking
		$this->testFixedDateBooking       = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '+1 day',  strtotime( self::CURRENT_DATE ) ),
			strtotime( '+2 days', strtotime( self::CURRENT_DATE ) ),
		);
		$this->testBookingFixedDate = new Booking( get_post( $this->testFixedDateBooking ) );
	}

	protected function setUp() : void {
		parent::setUp();

		$this->firstTimeframeId   = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '-5 days',  time() ),
			strtotime( '+90 days', time() )
		);
		$this->testItem = new Item(get_post($this->itemId));
		$this->testLocation = new Location(get_post($this->locationId));
		$this->testTimeFrame = new Timeframe(get_post($this->firstTimeframeId));
		$this->setUpTestBooking();
	}

	protected function tearDown() : void {
		parent::tearDown();
	}
}
