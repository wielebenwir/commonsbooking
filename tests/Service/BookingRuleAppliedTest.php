<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Model\Booking;
use CommonsBooking\Service\BookingRuleApplied;

class BookingRuleAppliedTest extends BookingRuleTest {

	private Booking $testBookingTomorrow;
	private int $testBookingId;
	protected BookingRuleApplied $appliedAlwaysAllow,$appliedAlwaysDeny;

	protected function setUpTestBooking(): void {
		$wp_user = get_user_by('email',"a@a.de");
		if (! $wp_user){
			$wp_user = wp_create_user("normaluser","normal","a@a.de");
		}
		else {
			$wp_user = $wp_user->ID;
		}
		$this->testBookingId       = $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '+1 day', time() ),
			strtotime( '+2 days', time() ),
			'8:00 AM',
			'12:00 PM',
			'unconfirmed',
			$wp_user
		);
		$this->testBookingTomorrow = new Booking( get_post( $this->testBookingId ) );
	}
	public function testCheckBooking()
	{
		$this->assertNull($this->appliedAlwaysAllow->checkBookingCompliance( $this->testBookingTomorrow));
		$this->assertNotNull($this->appliedAlwaysDeny->checkBookingCompliance( $this->testBookingTomorrow));

	}

	protected function setUp(): void {
		parent::setUp();

		$this->firstTimeframeId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '-5 days', time() ),
			strtotime( '+90 days', time() )
		);
		$this->setUpTestBooking();
		$this->appliedAlwaysAllow = new BookingRuleApplied( $this->alwaysallow );
		$this->appliedAlwaysAllow->setAppliesToWho(true);
		$this->appliedAlwaysDeny = new BookingRuleApplied( $this->alwaysdeny );
		$this->appliedAlwaysDeny->setAppliesToWho(true);
	}

	protected function tearDown(): void {
		parent::tearDown();
	}
}