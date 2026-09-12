<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Model\Day;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class DayTest extends CustomPostTypeTest {

	private Day $instance;

	protected $bookableTimeframeForCurrentDayId;

	protected $bookableTimeframeNoRepSingleDayTomorrowId;

	protected $bookableTimeframeNoRepSingleDayTodayId;

	protected $bookableTimeframeNoRepStartsYesterdayEndsTomorrowId;

	protected $bookableTimeframeOnceWeeklyValidTodayNoEnd;

	protected $bookableTimeframeOnceWeeklyValidTodayWithEnd;

	protected $bookableTimeframeManualDateInputOnlyForToday;

	private $bookableTimeframeManualDateInputTomorrow;

	protected function setUp(): void {
		parent::setUp();
		$this->bookableTimeframeForCurrentDayId = $this->createBookableTimeFrameIncludingCurrentDay();

		$this->bookableTimeframeNoRepSingleDayTomorrowId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '+1 days', strtotime( self::CURRENT_DATE ) ),
			null,
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			'on',
			'norep'
		);

		$this->bookableTimeframeNoRepSingleDayTodayId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( self::CURRENT_DATE ),
			null,
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			'on',
			'norep'
		);

		$this->bookableTimeframeNoRepStartsYesterdayEndsTomorrowId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '-1 days', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+1 days', strtotime( self::CURRENT_DATE ) ),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			'on',
			'norep'
		);

		// get the current weekday of the current date
		$weekday = date( 'w', strtotime( self::CURRENT_DATE ) );
		$weekday = $weekday == 0 ? 7 : $weekday;

		$this->bookableTimeframeOnceWeeklyValidTodayNoEnd = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '-7 days', strtotime( self::CURRENT_DATE ) ),
			null,
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			'on',
			'w',
			0,
			'8:00 AM',
			'12:00 PM',
			'publish',
			[ strval( $weekday ) ]
		);

		$this->bookableTimeframeOnceWeeklyValidTodayWithEnd = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '-7 days', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+7 days', strtotime( self::CURRENT_DATE ) ),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			'on',
			'w',
			0,
			'8:00 AM',
			'12:00 PM',
			'publish',
			[ strval( $weekday ) ]
		);

		$this->bookableTimeframeManualDateInputOnlyForToday = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			null,
			null,
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			'on',
			'manual',
			0,
			'8:00 AM',
			'12:00 PM',
			'publish',
			'',
			$this->dateFormatted
		);
		$tfModel = new Timeframe( $this->bookableTimeframeManualDateInputOnlyForToday );
		$tfModel->updatePostMetaStartAndEndDate();

		$this->bookableTimeframeManualDateInputTomorrow = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			null,
			null,
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			'on',
			'manual',
			0,
			'8:00 AM',
			'12:00 PM',
			'publish',
			'',
			date( 'Y-m-d', strtotime( '+1 days', strtotime( self::CURRENT_DATE ) ) )
		);
		// we need to save the post so that a valid repetition_start and repetition_end is set
		$tfModel = new Timeframe( $this->bookableTimeframeManualDateInputTomorrow );
		$tfModel->updatePostMetaStartAndEndDate();

		$this->createUnconfirmedBookingEndingTomorrow();

		$this->instance = new Day(
			$this->dateFormatted,
			[ $this->locationId ],
			[ $this->itemId ]
		);
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function testGetFormattedDate() {
		$this->assertTrue( self::CURRENT_DATE == $this->instance->getFormattedDate( 'd.m.Y' ) );
	}

	public function testGetDayOfWeek() {
		$this->assertTrue( date( 'w', strtotime( self::CURRENT_DATE ) ) == $this->instance->getDayOfWeek() );
	}

	public function testGetDate() {
		$this->assertEquals( $this->dateFormatted, $this->instance->getDate() );
	}

	public function testIsInTimeframe() {
		$timeframe = new Timeframe( $this->bookableTimeframeForCurrentDayId );
		$this->assertTrue( $this->instance->isInTimeframe( $timeframe ) );

		$timeframe = new Timeframe( $this->bookableTimeframeNoRepSingleDayTomorrowId );
		$this->assertFalse( $this->instance->isInTimeframe( $timeframe ) );

		$timeframe = new Timeframe( $this->bookableTimeframeNoRepSingleDayTodayId );
		$this->assertTrue( $this->instance->isInTimeframe( $timeframe ) );

		$timeframe = new Timeframe( $this->bookableTimeframeNoRepStartsYesterdayEndsTomorrowId );
		$this->assertTrue( $this->instance->isInTimeframe( $timeframe ) );

		$timeframe = new Timeframe( $this->bookableTimeframeOnceWeeklyValidTodayNoEnd );
		$this->assertTrue( $this->instance->isInTimeframe( $timeframe ) );

		$timeframe = new Timeframe( $this->bookableTimeframeOnceWeeklyValidTodayWithEnd );
		$this->assertTrue( $this->instance->isInTimeframe( $timeframe ) );

		$timeframe = new Timeframe( $this->bookableTimeframeManualDateInputOnlyForToday );
		$this->assertTrue( $this->instance->isInTimeframe( $timeframe ) );

		$timeframe = new Timeframe( $this->bookableTimeframeManualDateInputTomorrow );
		$this->assertFalse( $this->instance->isInTimeframe( $timeframe ) );
	}

	public function testGetName() {
		$this->assertTrue( date( 'l', strtotime( self::CURRENT_DATE ) ) == $this->instance->getName() );
	}

	public function testGetTimeframes() {
		// Should only find confirmed timeframes
		$this->assertEquals( 6, count( $this->instance->getTimeframes() ) );
	}

	public function testGetRestrictions() {
		$this->assertTrue( count( $this->instance->getRestrictions() ) == 0 );

		$this->createRestriction(
			'hint',
			$this->locationId,
			$this->itemId,
			strtotime( self::CURRENT_DATE ),
			strtotime( 'tomorrow', strtotime( self::CURRENT_DATE ) )
		);

		$this->assertIsArray( $this->instance->getRestrictions() );
		$this->assertTrue( count( $this->instance->getRestrictions() ) == 1 );
	}


	public function testGetStartTimestamp() {
		$start = strtotime( self::CURRENT_DATE . ' midnight' );
		$this->assertEquals( $start, $this->instance->getStartTimestamp() );
	}

	public function testGetEndTimestamp() {
		$end = strtotime( self::CURRENT_DATE . ' 23:59:59' );
		$this->assertEquals( $end, $this->instance->getEndTimestamp() );
	}

	public function testGetGrid() {
		// one grid entry spanning whole day
		$grid = $this->instance->getGrid();
		$this->assertCount( 1, $grid );
		$this->assertArrayHasKey( 95, $grid );
		$this->assertEquals( $grid[95]['timeframe']->ID, $this->bookableTimeframeForCurrentDayId );

		// hourly grid
		// we explicitly define a new location and item here to avoid interference with other tests
		$hourlyLocation  = $this->createLocation( 'Hourly Location' );
		$hourlyItem      = $this->createItem( 'Hourly Item', $hourlyLocation );
		$hourlyTimeframe = $this->createTimeframe(
			$hourlyLocation,
			$hourlyItem,
			strtotime( self::CURRENT_DATE ),
			strtotime( 'tomorrow', strtotime( self::CURRENT_DATE ) ),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			'off',
			'd',
			1,
			'8:00 AM',
			'4:00 PM'
		);
		$instance        = new Day(
			$this->dateFormatted,
			[ $hourlyLocation ],
			[ $hourlyItem ]
		);
		$grid            = $instance->getGrid();
		$this->assertCount( 8, $grid );

		// timeframe blocking parts of hourly grid with no end date (related to bug #1553)
		$blockingTimeframe = $this->createTimeframe(
			$hourlyLocation,
			$hourlyItem,
			strtotime( self::CURRENT_DATE ),
			0,
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::REPAIR_ID,
			'off',
			'd',
			1,
			'08:00 AM',
			'10:00 AM'
		);
		// new instance of day to fetch new timeframe
		$instance = new Day(
			$this->dateFormatted,
			[ $hourlyLocation ],
			[ $hourlyItem ]
		);
		$grid     = $instance->getGrid();

		$assertGridLocked = function ( $grid ) {
			$this->assertCount( 8, $grid );
			$this->assertArrayHasKey( 35, $grid );
			$this->assertArrayHasKey( 63, $grid );

			// make sure that only 8:00-10:00 is correctly blocked
			$this->assertTrue( $grid[35]['timeframe']->locked );
			$this->assertTrue( $grid[39]['timeframe']->locked );
			$this->assertFalse( $grid[43]['timeframe']->locked );
			$this->assertFalse( $grid[47]['timeframe']->locked );
			$this->assertFalse( $grid[51]['timeframe']->locked );
			$this->assertFalse( $grid[55]['timeframe']->locked );
			$this->assertFalse( $grid[59]['timeframe']->locked );
			$this->assertFalse( $grid[63]['timeframe']->locked );
		};

		$assertGridLocked( $grid );

		// now set an end-date to trigger bug #1553
		update_post_meta( $blockingTimeframe, 'repetition-end', strtotime( 'tomorrow', strtotime( self::CURRENT_DATE ) ) );
		$grid = $instance->getGrid();
		$assertGridLocked( $grid );

		// but still, if the timeframe does not have a repetition set, the block should span over the whole range
		update_post_meta( $blockingTimeframe, Timeframe::META_REPETITION, 'norep' );
		$grid = $instance->getGrid();
		// blocking grid should extend till end of the day
		$assertOverbookLocked = function ( $grid ) {
			$this->assertCount( 16, $grid );
			$this->assertArrayHasKey( 35, $grid );
			$this->assertArrayHasKey( 95, $grid );
			for ( $i = 35; $i <= 95; $i += 4 ) {
				$this->assertTrue( $grid[ $i ]['timeframe']->locked );
			}
		};
		$assertOverbookLocked( $grid );

		// do the same for a booking (bug #1900)
		wp_delete_post( $blockingTimeframe, true );
		$booking = $this->createBooking(
			$hourlyLocation,
			$hourlyItem,
			strtotime( self::CURRENT_DATE ),
			strtotime( 'tomorrow', strtotime( self::CURRENT_DATE ) ),
			'9:00 AM',
			'10:00 AM'
		);
		// delete repetition key (also usually not set in the wild)
		delete_post_meta( $booking, Timeframe::META_REPETITION );
		// rebuild instance to fetch new booking
		$instance = new Day(
			$this->dateFormatted,
			[ $hourlyLocation ],
			[ $hourlyItem ]
		);
		$grid     = $instance->getGrid();
		// first hour (8:00-9:00) still free, rest blocked
		$this->assertCount( 2, $grid );
		$this->assertArrayHasKey( 35, $grid );
		$this->assertArrayHasKey( 95, $grid );
		$this->assertFalse( $grid[35]['timeframe']->locked );
		$this->assertTrue( $grid[95]['timeframe']->locked );
	}
	public function testGetGridSupportsFractionalIntervals() {
		$quarterLocation  = $this->createLocation( 'Quarter-hour Location' );
		$quarterItem      = $this->createItem( 'Quarter-hour Item', $quarterLocation );
		$quarterTimeframe = $this->createTimeframe(
			$quarterLocation,
			$quarterItem,
			strtotime( self::CURRENT_DATE ),
			strtotime( 'tomorrow', strtotime( self::CURRENT_DATE ) ),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			'off',
			'd',
			'0.25',
			'9:00 AM',
			'10:00 AM'
		);
		$quarterGrid      = ( new Day( $this->dateFormatted, [ $quarterLocation ], [ $quarterItem ] ) )->getGrid();

		$this->assertSame( [ 36, 37, 38, 39 ], array_keys( $quarterGrid ) );
		foreach ( $quarterGrid as $slot ) {
			$this->assertEquals( 899, $slot['timestampend'] - $slot['timestampstart'] );
			$this->assertSame( $quarterTimeframe, $slot['timeframe']->ID );
		}

		$halfLocation  = $this->createLocation( 'Half-hour Location' );
		$halfItem      = $this->createItem( 'Half-hour Item', $halfLocation );
		$halfTimeframe = $this->createTimeframe(
			$halfLocation,
			$halfItem,
			strtotime( self::CURRENT_DATE ),
			strtotime( 'tomorrow', strtotime( self::CURRENT_DATE ) ),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			'off',
			'd',
			'0.5',
			'9:00 AM',
			'10:00 AM'
		);
		$halfGrid      = ( new Day( $this->dateFormatted, [ $halfLocation ], [ $halfItem ] ) )->getGrid();

		$this->assertSame( [ 37, 39 ], array_keys( $halfGrid ) );
		foreach ( $halfGrid as $slot ) {
			$this->assertEquals( 1799, $slot['timestampend'] - $slot['timestampstart'] );
			$this->assertSame( $halfTimeframe, $slot['timeframe']->ID );
		}

		$priorityLocation = $this->createLocation( 'Priority Location' );
		$priorityItem     = $this->createItem( 'Priority Item', $priorityLocation );
		$this->createTimeframe(
			$priorityLocation,
			$priorityItem,
			strtotime( self::CURRENT_DATE ),
			strtotime( 'tomorrow', strtotime( self::CURRENT_DATE ) ),
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			'off',
			'd',
			'0.5',
			'9:00 AM',
			'10:00 AM'
		);
		$priorityBooking = $this->createBooking(
			$priorityLocation,
			$priorityItem,
			strtotime( self::CURRENT_DATE ),
			strtotime( 'tomorrow', strtotime( self::CURRENT_DATE ) ),
			'9:00 AM',
			'10:00 AM',
			'confirmed',
			self::USER_ID,
			'w',
			3,
			'Quarter-hour priority booking',
			'0.25'
		);
		$priorityGrid    = ( new Day( $this->dateFormatted, [ $priorityLocation ], [ $priorityItem ] ) )->getGrid();

		$this->assertSame( [ 36, 37, 38, 39 ], array_keys( $priorityGrid ) );
		foreach ( $priorityGrid as $slot ) {
			$this->assertEquals( 899, $slot['timestampend'] - $slot['timestampstart'] );
			$this->assertSame( $priorityBooking, $slot['timeframe']->ID );
			$this->assertTrue( $slot['timeframe']->locked );
		}
	}
	public function testGetGridIncludesBookingEndingAtEndOfDay() {
		$booking = $this->createConfirmedBookingEndingToday();
		$grid    = $this->instance->getGrid();

		$this->assertSame( $booking, $grid[95]['timeframe']->ID );
		$this->assertTrue( $grid[95]['timeframe']->locked );
	}
}
