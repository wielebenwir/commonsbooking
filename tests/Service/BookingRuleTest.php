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
					return null;
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
		$this->assertEquals(array($testBookingOne),BookingRule::checkSimultaneousBookings($testBookingTwo));
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
		$this->assertEquals(array($testBookingOne),BookingRule::checkChainBooking($testBookingTwo));
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
		$this->assertEquals(array($testBookingOne,$testBookingTwo),BookingRule::checkMaxBookingDays($testBookingThree,array(2,30)));
	}

	public function testMaxBookingPerWeek() {
		$nextWeekDate = new \DateTime(self::CURRENT_DATE);
		// we add one week here so that it does not interfere with the bookings of the other tests
		$nextWeekDate->modify('+1 week');
		$testBookingOne       = new Booking( get_post( $this->createBooking(
			$this->locationId,
			$this->itemId,
			strtotime( 'monday this week', $nextWeekDate->getTimestamp()),
			strtotime( 'tuesday this week', $nextWeekDate->getTimestamp()),
			'8:00 AM',
			'12:00 PM',
			'confirmed',
			$this->normalUser
		) ) );
		$testBookingTwo = new Booking(get_post(
			$this->createBooking(
				$this->locationId,
				$this->itemId,
				strtotime('wednesday this week', $nextWeekDate->getTimestamp()),
				strtotime('thursday this week', $nextWeekDate->getTimestamp()),
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
				strtotime('friday this week', $nextWeekDate->getTimestamp()),
				strtotime('saturday this week', $nextWeekDate->getTimestamp()),
				'8:00 AM',
				'12:00 PM',
				'unconfirmed',
				$this->normalUser
			)
		));
		$mondayFollowingWeek = $nextWeekDate;
		$mondayFollowingWeek->modify('monday this week');
		$mondayFollowingWeek->modify('+1 week');

		$tuesdayFollowingWeek = $nextWeekDate;
		$tuesdayFollowingWeek->modify('tuesday this week');
		$tuesdayFollowingWeek->modify('+1 week');

		$testBookingFour = new Booking(get_post(
			$this->createBooking(
				$this->locationId,
				$this->itemId,
				$mondayFollowingWeek->getTimestamp(),
				$tuesdayFollowingWeek->getTimestamp(),
				'8:00 AM',
				'12:00 PM',
				'unconfirmed',
				$this->normalUser
			)
		));

		$this->assertEquals(array($testBookingOne,$testBookingTwo),BookingRule::checkMaxBookingsPerWeek(
			$testBookingThree, array(2,null,0)
		));
		$this->assertNull(BookingRule::checkMaxBookingsPerWeek($testBookingFour, array(2,null,0)));
	}
	protected function setUp() {
		parent::setUp();
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

	}

	protected function tearDown(){
		parent::tearDown();
	}
}
