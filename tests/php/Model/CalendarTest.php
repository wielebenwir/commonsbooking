<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Model\Day;
use CommonsBooking\Model\Week;
use CommonsBooking\Model\Calendar;

use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;


/**
 * Tests weekdays
 */
class CalendarTest extends CustomPostTypeTest {

	private Calendar $calendar;

	public function testGetDays() {
		$this->calendar = new Calendar( new Day( '2023-05-01' ), new Day( '2023-06-01' ) );
		$this->assertEquals( 5, count( $this->calendar->getWeeks() ) );
		$this->assertEquals(
			array(
				new Week( 2023, 120 ),
				new Week( 2023, 127 ),
				new Week( 2023, 134 ),
				new Week( 2023, 141 ),
				new Week( 2023, 148 ),
			),
			$this->calendar->getWeeks()
		);
	}
}
