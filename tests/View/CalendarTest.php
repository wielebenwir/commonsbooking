<?php

namespace CommonsBooking\Tests\View;

use CommonsBooking\Model\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\View\Calendar;

class CalendarTest extends CustomPostTypeTest {

	protected const bookingDaysInAdvance = 35;

	protected const timeframeStart = 29;

	protected const timeframeEnd = 100;

	public function testKeepDateRangeParam() {
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

	public function testAdvancedBookingDays() {
		$jsonresponse = Calendar::getCalendarDataArray(
			$this->itemId,
			$this->locationId,
			date( 'Y-m-d', strtotime( 'midnight' ) ),
			date( 'Y-m-d', strtotime( '+60 days midnight' ) )
		);

		$jsonReponseBookableDaysOnly = array_filter( $jsonresponse['days'], function ( $day ) {
			return ! $day['locked'];
		} );

		// we should be able to book 2 days of the timeframe, because
		// advanced booking days == 30, timeframe starts in 29 days, today isn't recognized in calculation
		$this->assertTrue(
			count( $jsonReponseBookableDaysOnly ) == ( self::bookingDaysInAdvance - self::timeframeStart + 1 )
		);
	}

	protected function setUp() {
		parent::setUp();

		$now         = time();
		$timeframeId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '+' . self::timeframeStart . ' days midnight', $now ),
			strtotime( '+' . self::timeframeEnd . ' days midnight', $now )
		);
		// set booking days in advance
		update_post_meta( $timeframeId, Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS, self::bookingDaysInAdvance );
	}

}
