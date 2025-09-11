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
		$this->assertArrayHasKey( 23, $grid );
		$this->assertEquals( $grid[23]['timeframe']->ID, $this->bookableTimeframeForCurrentDayId );

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
			$this->assertArrayHasKey( 8, $grid );
			$this->assertArrayHasKey( 15, $grid );

			// make sure, that only 8:00-10:00 is correctly blocked
			$this->assertTrue( $grid[8]['timeframe']->locked );
			$this->assertTrue( $grid[9]['timeframe']->locked );
			$this->assertFalse( $grid[10]['timeframe']->locked );
			$this->assertFalse( $grid[11]['timeframe']->locked );
			$this->assertFalse( $grid[12]['timeframe']->locked );
			$this->assertFalse( $grid[13]['timeframe']->locked );
			$this->assertFalse( $grid[14]['timeframe']->locked );
			$this->assertFalse( $grid[15]['timeframe']->locked );
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
		$this->assertCount( 16, $grid );
		$this->assertArrayHasKey( 8, $grid );
		$this->assertArrayHasKey( 23, $grid );
		for ( $i = 8; $i <= 23; $i++ ) {
			$this->assertTrue( $grid[ $i ]['timeframe']->locked );
		}
	}
}
