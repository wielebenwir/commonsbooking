<?php

namespace CommonsBooking\Service;

use CommonsBooking\Tests\Repository\BookingTest;

class BookingRuleTest extends BookingTest
{

	protected BookingRule $alwaysdeny;
	protected BookingRule $alwaysallow;


    public function test__construct()
    {
		self::assertNotNull(new BookingRule(
				"testRule",
				"test",
				"Testing rule creation",
				"Error message",
				function (\CommonsBooking\Model\Booking $booking, array $params){
					return true;
				},
				array(
					"First param description",
					"Second param description"
				)
			)
		);
    }

	protected function setUp() {
		parent::setUp();
		$this->alwaysallow = new BookingRule(
			"alwaysAllow",
			"Always allow",
			"Rule will always evaluate to true",
			"Rule did not evaluate to true",
			function(\CommonsBooking\Model\Booking $booking){
				return true;
			}
		);
		$this->alwaysdeny = new BookingRule(
			"alwaysDeny",
			"Always deny",
			"Rule will always evaluate to false",
			"Rule evaluated correctly",
			function(\CommonsBooking\Model\Booking $booking){
				return false;
			}
		);

	}

	protected function tearDown() {
		parent::tearDown();
	}
}
