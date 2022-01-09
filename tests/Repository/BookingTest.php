<?php

namespace CommonsBooking\Tests\Repository;

use CommonsBooking\Repository\Booking;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use PHPUnit\Framework\TestCase;

class BookingTest extends CustomPostTypeTest {

	protected function setUp() {
		parent::setUp();
		parent::createConfirmedBookingEndingToday();
		parent::createConfirmedBookingStartingToday();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testGetEndingBookingsByDate() {
		$endingBookingsToday = Booking::getEndingBookingsByDate(strtotime(self::CURRENT_DATE));
		$this->assertTrue(count($endingBookingsToday) == 1);
	}

//	public function testGetByTimerange() {
//
//	}

	public function testGetBeginningBookingsByDate() {
		$beginningBookingsToday = Booking::getBeginningBookingsByDate(strtotime(self::CURRENT_DATE));
		$this->assertTrue(count($beginningBookingsToday) == 1);
	}

//	public function testGetByRestriction() {
//
//	}

//	public function testGetByDate() {
//
//	}

//	public function testGetCanceledByRestriction() {
//
//	}
}
