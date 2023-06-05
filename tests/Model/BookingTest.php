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
	
	public function testCanCancel() {
		
		
		// Case: Booking in the past, no one can cancel
		$this->assertFalse( $this->testBookingPast->canCancel() );
		
		// Case: Booking in the future and same author
		// TODO: Maybe mock get_current_user_id
		/** Brain\Monkey\Functions\expect( 'get_current_user_id' )
			// We expect the function to be called once.
			->once()
			// What the function should return when called.
			->andReturn( self::USER_ID );
			*/
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
		$this->assertTrue(  $regularUserBooking->canCancel() );
		
		// TODO Case: role can edit and != post_author => can cancel
		
		// TODO role cannot edit, role != post_author, booking in the future => can't cancel
		
	}

	protected function setUpTestBooking():void{
		$this->testBookingId       = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '+1 day', time()),
			strtotime( '+2 days', time())
		);
		$this->testBookingTomorrow = new Booking(get_post($this->testBookingId));
		$this->testBookingPastId       = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime('-2 days', time()),
			strtotime('-1 day', time())
		);
		$this->testBookingPast = new Booking(get_post($this->testBookingPastId));
	}

	protected function setUp() : void {
		parent::setUp();

		$this->firstTimeframeId   = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '-5 days',time()),
			strtotime( '+90 days', time())
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
