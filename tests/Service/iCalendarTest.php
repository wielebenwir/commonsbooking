<?php

namespace CommonsBooking\Service;

use CommonsBooking\Model\Booking;
use CommonsBooking\Tests\CB\CBTest;

class iCalendarTest extends CBTest {

	private iCalendar $calendar;
	private static String $eventTitle = "My Test Event";
	private static String $eventDescription = "My Test Event Description";
	public function testGetCalendarData() {
		$this->assertNotNull($this->calendar->getCalendarData());
		$calendarData = $this->calendar->getCalendarData();

		$iCalendarArray = explode('\r\n',$calendarData);
		$this->assertIsArray($iCalendarArray);

		$this->assertEquals("BEGIN:VCALENDAR",$iCalendarArray[0]);
		$this->assertEquals("END:VCALENDAR",$iCalendarArray[0]);

		if (is_array($iCalendarArray) || is_object($iCalendarArray))
		{
			foreach ($iCalendarArray as $calendar_line){
				if (str_contains($calendar_line,"SUMMARY:")){
					$this->assertEquals(static::$eventTitle,substr(strpos($calendar_line, "SUMMARY:") + 1));
				}
				elseif (str_contains($calendar_line,"DESCRIPTION:")){
					$this->assertEquals(static::$eventDescription,substr($calendar_line, strpos($calendar_line, "DESCRIPTION:") + 1));
				}
			}
		}
	}

	protected function setUp(){
		parent::setUp();
		$this->calendar = new iCalendar();
		$booking = new Booking( $this->bookingId );
		$this->calendar->addBookingEvent($booking,static::$eventTitle,static::$eventDescription);
	}
}
