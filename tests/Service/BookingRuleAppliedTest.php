<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Model\Booking;
use CommonsBooking\Service\BookingRuleApplied;

class BookingRuleAppliedTest extends BookingRuleTest {

	private Booking $testBookingTomorrow;
	private int $testBookingId;
	protected BookingRuleApplied $appliedAlwaysAllow,$appliedAlwaysDeny;
	/**
	 * @var int|\WP_Error
	 */
	private int $testBookingPastId;

	public function testFromBookingRule()
	{
		$appliedRule = BookingRuleApplied::fromBookingRule($this->alwaysallow,true);
		$this->assertNotNull($appliedRule);
	}

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

	protected function setUp() {
		parent::setUp();

		$this->firstTimeframeId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '-5 days', time() ),
			strtotime( '+90 days', time() )
		);
		$this->setUpTestBooking();
		$this->appliedAlwaysAllow = new BookingRuleApplied(
			$this->alwaysallow->getName(),
			$this->alwaysallow->getTitle(),
			$this->alwaysallow->getDescription(),
			$this->alwaysallow->getErrorMessage(),
			$this->alwaysallow->getValidationFunction(),
			true
		);
		$this->appliedAlwaysDeny = new BookingRuleApplied(
			$this->alwaysdeny->getName(),
			$this->alwaysdeny->getTitle(),
			$this->alwaysdeny->getDescription(),
			$this->alwaysdeny->getErrorMessage(),
			$this->alwaysdeny->getValidationFunction(),
			true
		);
	}

	protected function tearDown() {
		parent::tearDown();
	}
}