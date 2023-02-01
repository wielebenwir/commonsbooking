<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Model\Booking;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Service\BookingRule;

class BookingRuleTest extends CustomPostTypeTest
{
	protected $testBooking;
	protected BookingRule $alwaysdeny;
	protected BookingRule $alwaysallow;
	protected int $normalUser;

    public function test__construct()
    {
		$this->assertNotNull(new BookingRule(
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

	public function testCheckSimultaneousBookings(){
		$testBookingOne       = new Booking( get_post( $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '+1 day', time()),
			strtotime( '+2 days', time()),
			'8:00 AM',
			'12:00 PM',
			'confirmed',
			$this->normalUser
		) ) );
		$itemtwo = $this->createItem("Item2",'publish');
		$locationtwo = $this->createLocation("Location2",'publish');
		$this->secondTimeframeId = $this->createTimeframe(
			$locationtwo,
			$itemtwo,
			strtotime( '-5 days',time()),
			strtotime( '+90 days', time()),
		);
		$testBookingTwo = new Booking(get_post(
			$this->createBooking(
				$locationtwo,
				$itemtwo,
				strtotime('+1 day', time()),
				strtotime('+2 days', time()),
				'8:00 AM',
				'12:00 PM',
				'unconfirmed',
				$this->normalUser
			)
		));
		$this->assertTrue(BookingRule::checkSimultaneousBookings($testBookingTwo));
		$this->tearDownAllBookings();
	}

	public function testCheckChainBooking(){
		$testBookingOne       = new Booking( get_post( $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '+1 day', time()),
			strtotime( '+4 days', time()),
			'8:00 AM',
			'12:00 PM',
			'confirmed',
			$this->normalUser
		) ) );
		$testBookingTwo = new Booking(get_post(
			$this->createBooking(
				$this->locationId,
				$this->itemId,
				strtotime('+4 day', time()),
				strtotime('+5 days', time()),
				'8:00 AM',
				'12:00 PM',
				'unconfirmed',
				$this->normalUser
			)
		));
		$this->assertTrue(BookingRule::checkChainBooking($testBookingTwo));
	}

	public function testCheckMaxBookingDays(){
		$testBookingOne       = new Booking( get_post( $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( '+1 day', time()),
			strtotime( '+2 days', time()),
			'8:00 AM',
			'12:00 PM',
			'confirmed',
			$this->normalUser
		) ) );
		$testBookingTwo = new Booking(get_post(
			$this->createBooking(
				$this->locationId,
				$this->itemId,
				strtotime('+4 day', time()),
				strtotime('+5 days', time()),
				'8:00 AM',
				'12:00 PM',
				'confirmed',
				$this->normalUser
			)
		));

		$testBookingThree = new Booking(get_post(
			$this->createBooking(
				$this->locationId,
				$this->itemId,
				strtotime('+6 day', time()),
				strtotime('+7 days', time()),
				'8:00 AM',
				'12:00 PM',
				'unconfirmed',
				$this->normalUser
			)
		));
		$this->assertTrue(BookingRule::checkMaxBookingDays($testBookingThree,array(2,30)));
	}

	protected function setUp() {
		parent::setUp();
		$this->alwaysallow = new BookingRule(
			"alwaysAllow",
			"Always allow",
			"Rule will always evaluate to true",
			"Rule did not evaluate to true",
			function(\CommonsBooking\Model\Booking $booking){
				return false;
			}
		);
		$this->alwaysdeny = new BookingRule(
			"alwaysDeny",
			"Always deny",
			"Rule will always evaluate to false",
			"Rule evaluated correctly",
			function(\CommonsBooking\Model\Booking $booking){
				return true;
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

	}

	protected function tearDown(){
		parent::tearDown();
	}
}
