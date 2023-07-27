<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Exception\BookingRuleException;
use CommonsBooking\Model\Booking;
use CommonsBooking\Service\BookingRule;
use CommonsBooking\Service\BookingRuleApplied;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class BookingRuleAppliedTest extends CustomPostTypeTest {

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

	public function testRuleExceptions() {
		$bookingRule = new BookingRuleApplied($this->alwaysallow);
		try {
			$bookingRule->setAppliesToWho(false,[]);
			$this->fail("Expected exception not thrown");
		}
		catch (BookingRuleException $e){
			$this->assertEquals("You need to specify a category, if the rule does not apply to all items",$e->getMessage());
		}

		$alwaysAllowWithParams = new BookingRule(
			"alwaysAllow",
			"Always allow",
			"Rule will always evaluate to null",
			"Rule did not evaluate to null",
			function(\CommonsBooking\Model\Booking $booking){
				return null;
			},
			array(
				array(
					"title" => "Test Param",
					"description" => "Test Param Description",
				)
			)
		);
		$bookingRule = new BookingRuleApplied($alwaysAllowWithParams);
		try {
			$bookingRule->setAppliedParams([],"");
			$this->fail("Expected exception not thrown");
		} catch ( BookingRuleException $e ) {
			$this->assertEquals("Booking rules: Not enough parameters specified.",$e->getMessage());
		}
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
		$this->alwaysallow = new BookingRule(
			"alwaysAllow",
			"Always allow",
			"Rule will always evaluate to null",
			"Rule did not evaluate to null",
			function(\CommonsBooking\Model\Booking $booking){
				return null;
			}
		);
		$this->alwaysdeny = new BookingRule(
			"alwaysDeny",
			"Always deny",
			"Rule will always deny and return the current booking as conflict",
			"Rule evaluated correctly",
			function(\CommonsBooking\Model\Booking $booking){
				return array($booking);
			}
		);
		$this->firstTimeframeId   = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '-5 days',time()),
			strtotime( '+90 days', time())
		);

		$wp_user = get_user_by('email',"a@a.de");
		if (! $wp_user){
			$this->normalUser = wp_create_user("normaluser","normal","a@a.de");
		}
		else {
			$this->normalUser = $wp_user->ID;
		}
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