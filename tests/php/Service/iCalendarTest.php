<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Model\Booking;
use CommonsBooking\Tests\CB\CBTest;
use CommonsBooking\Service\iCalendar;
use DateTimeImmutable;

class iCalendarTest extends CBTest {

	private iCalendar $calendar;
	private Booking $bookingModel;
	private static string $eventTitle       = 'My Test Event';
	private static string $eventDescription = 'My Test Event Description';
	public function testGetCalendarData() {
		$this->calendar->addBookingEvent(
			$this->bookingModel,
			static::$eventTitle,
			static::$eventDescription
		);
		$this->assertNotNull( $this->calendar->getCalendarData() );
		$calendarData = $this->calendar->getCalendarData();
		$this->checkCalendarStringValid( $calendarData );
	}

	public function testAddEventOneDay() {
		// tests the generic event adding method
		// test just for one day:
		$event = $this->calendar->addEvent(
			DateTimeImmutable::createFromFormat( 'Y-m-d', '2020-01-01' ),
			static::$eventTitle,
			static::$eventDescription
		);
		$this->assertInstanceOf( \Eluceo\iCal\Domain\Entity\Event::class, $event );
		$calendarData = $this->calendar->getCalendarData();
		$this->checkCalendarStringValid( $calendarData );
	}

	public function testAddEventMultipleDays() {
		$event = $this->calendar->addEvent(
			[ new \DateTimeImmutable( '2020-01-01 00:00:00' ),new \DateTimeImmutable( '2020-01-02 01:00:00' ) ],
			static::$eventTitle,
			static::$eventDescription,
			true
		);
		$this->assertInstanceOf( \Eluceo\iCal\Domain\Entity\Event::class, $event );
		$calendarData = $this->calendar->getCalendarData();
		$this->checkCalendarStringValid( $calendarData );
	}

	private function checkCalendarStringValid( string $calendar ) {
		$iCalendarArray = explode( "\r\n", $calendar );
		$iCalendarArray = array_filter( $iCalendarArray );
		$this->assertIsArray( $iCalendarArray );

		$this->assertEquals( 'BEGIN:VCALENDAR', $iCalendarArray[0] );
		$this->assertEquals( 'END:VCALENDAR', end( $iCalendarArray ) );
	}

	protected function setUp(): void {
		parent::setUp();
		$this->calendar     = new iCalendar();
		$this->bookingModel = new Booking( $this->bookingId );
	}
}
