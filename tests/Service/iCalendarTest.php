<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Model\Booking;
use CommonsBooking\Tests\CB\CBTest;
use CommonsBooking\Service\iCalendar;

class iCalendarTest extends CBTest {

	private iCalendar $calendar;
	private static String $eventTitle = "My Test Event";
	private static String $eventDescription = "My Test Event Description";
	public function testGetCalendarData() {
		$this->assertNotNull($this->calendar->getCalendarData());
		$calendarData = $this->calendar->getCalendarData();

		$iCalendarArray = explode("\r\n",$calendarData);
		$iCalendarArray = array_filter($iCalendarArray);
		$this->assertIsArray($iCalendarArray);

		$this->assertEquals("BEGIN:VCALENDAR",$iCalendarArray[0]);
		$this->assertEquals("END:VCALENDAR",end($iCalendarArray));
	}

	protected function setUp() : void {
		parent::setUp();
		$this->calendar = new iCalendar();
		$booking = new Booking( $this->bookingId );
		$this->calendar->addBookingEvent($booking,static::$eventTitle,static::$eventDescription);
	}
}
