<?php

namespace CommonsBooking\Tests\View;

use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\View\Calendar;

class CalendarTest extends CustomPostTypeTest {

	public function testGetCalendarDataArray() {
		$startDate    = date( 'Y-m-d', strtotime( self::CURRENT_DATE ) );
		$jsonresponse = Calendar::getCalendarDataArray(
			$this->itemId,
			$this->locationId,
			$startDate,
			date( 'Y-m-d', strtotime( '+20 days', strtotime( self::CURRENT_DATE ) ) ),
			true
		);

		$dayKeys  = array_keys( $jsonresponse['days'] );
		$firstDay = array_shift( $dayKeys );
		$this->assertTrue( $firstDay == $startDate );
	}

	protected function setUp() {
		parent::setUp();
	}
}
