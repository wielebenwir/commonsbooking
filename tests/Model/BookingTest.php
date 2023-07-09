<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Model\Booking;
use CommonsBooking\Model\Item;
use CommonsBooking\Model\Location;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use SlopeIt\ClockMock\ClockMock;

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
		ClockMock::freeze(new \DateTime(self::CURRENT_DATE));
		$this->assertEquals($this->testTimeFrame,$this->testBookingTomorrow->getBookableTimeFrame());
	}

	public function testGetLocation() {
		$this->assertEquals($this->testLocation,$this->testBookingTomorrow->getLocation());
	}

	public function testCancel() {
		ClockMock::freeze(new \DateTime(self::CURRENT_DATE));
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

	public function testGetLength(){
		$this->assertEquals(1,$this->testBookingTomorrow->getLength());
		$this->assertEquals(1,$this->testBookingPast->getLength());

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
		$this->assertEquals(3,$cancelBooking->getLength());
		$shortlyBeforeEnd = new \DateTime();
		$shortlyBeforeEnd->setTimestamp($endTime)->modify('-10 minutes');
		ClockMock::freeze($shortlyBeforeEnd);
		$cancelBooking->cancel();
		wp_cache_flush();
		$cancelBooking = new Booking(get_post($cancelBookingId));

		$this->assertEquals(3,$cancelBooking->getLength());

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
		$this->assertEquals(4,$cancelBooking->getLength());
		$halfBeforeEnd = new \DateTime();
		$halfBeforeEnd->setTimestamp($endTime)->modify('-2 days');
		ClockMock::freeze($halfBeforeEnd);
		$cancelBooking->cancel();
		wp_cache_flush();
		$cancelBooking = new Booking(get_post($cancelBookingId));
		$this->assertEquals(2,$cancelBooking->getLength());

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
		$this->assertEquals( 'July 2, 2021 12:00 am - 12:00 am', $this->testBookingTomorrow->pickupDatetime() );
	}

	public function testReturnDatetime() {
		// TODO 12:01? correct
		$this->assertEquals( 'July 3, 2021 8:00 am - 12:01 am', $this->testBookingTomorrow->returnDatetime() );
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


	protected function setUpTestBooking():void {
		$this->testBookingId       = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+2 days', strtotime( self::CURRENT_DATE ) )
		);
		$this->testBookingTomorrow = new Booking( get_post( $this->testBookingId ) );
		$this->testBookingPastId   = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '-2 days', strtotime( self::CURRENT_DATE ) ),
			strtotime( '-1 day', strtotime( self::CURRENT_DATE ) )
		);
		$this->testBookingPast     = new Booking( get_post( $this->testBookingPastId ) );

	}

	protected function setUp() : void {
		parent::setUp();

		$this->firstTimeframeId   = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '-5 days',  strtotime(self::CURRENT_DATE) ),
			strtotime( '+90 days', strtotime(self::CURRENT_DATE) )
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
