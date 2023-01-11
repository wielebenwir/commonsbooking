<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Service\BookingRule;

class BookingRuleTest extends CustomPostTypeTest
{
	private $timeframeOne;

	private $testItem;

	private $testLocation;

	protected $testBooking;
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
		$this->timeframeOne = parent::createConfirmedBookingEndingToday();
		$this->timeframeTwo = parent::createConfirmedBookingStartingToday();
		$this->testItem     = parent::createItem( 'testitem', 'publish' );
		$this->testLocation = parent::createLocation( 'testlocation', 'publish' );

		$this->testBooking = $this->createBooking(
			$this->testLocation,
			$this->testItem,
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+2 days', strtotime( self::CURRENT_DATE ) )
		);
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
