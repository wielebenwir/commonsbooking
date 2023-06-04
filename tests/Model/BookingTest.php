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
		
		$commentValue = "Comment on this";
		update_post_meta( $this->testBookingId, 'comment', $commentValue );
		wp_cache_flush();
		$this->testBookingTomorrow = new Booking( get_post( $this->testBookingId ) );
		$this->assertEquals( $commentValue, $this->testBookingTomorrow->returnComment() );
	}
	
	public function testPickupDatetime() {
		$this->assertEquals( '02.07.2021 08:00-12:00', $this->testBookingTomorrow->pickupDatetime() );
	}
	
	public function testReturnDatetime() {
		$this->assertEquals( '03.07.2021 08:00-12:00', $this->testBookingTomorrow->returnDatetime() );
	}
	
	protected function setUpTestBooking():void{
		$this->testBookingId       = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '+1 day',  strtotime( self::CURRENT_DATE ) ),
			strtotime( '+2 days', strtotime( self::CURRENT_DATE ) )
		);
		$this->testBookingTomorrow = new Booking(get_post($this->testBookingId));
		$this->testBookingPastId       = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime('-2 days', strtotime( self::CURRENT_DATE )),
			strtotime('-1 day',  strtotime( self::CURRENT_DATE ))
		);
		$this->testBookingPast = new Booking(get_post($this->testBookingPastId));
	}

	protected function setUp() : void {
		parent::setUp();

		$this->firstTimeframeId   = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '-5 days',  strtotime( self::CURRENT_DATE )),
			strtotime( '+90 days', strtotime( self::CURRENT_DATE ))
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
