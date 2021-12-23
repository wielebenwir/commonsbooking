<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Model\Day;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class DayTest extends CustomPostTypeTest {

	private $instance;

	protected $timeframeId;

	protected function setUp() {
		parent::setUp();
		$this->timeframeId = $this->createBookableTimeFrameIncludingCurrentDay();

		$this->instance = new Day(
			self::CURRENT_DATE,
			[ $this->locationId ],
			[ $this->itemId ]
		);
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testGetFormattedDate() {
		$this->assertTrue( self::CURRENT_DATE == $this->instance->getFormattedDate( 'd.m.Y' ) );
	}

	public function testGetDayOfWeek() {
		$this->assertTrue( date('w', strtotime(self::CURRENT_DATE)) == $this->instance->getDayOfWeek() );
	}

	public function testGetDate() {
		$this->assertTrue( self::CURRENT_DATE == $this->instance->getDate() );
	}

	public function testIsInTimeframe() {
		$timeframe = new Timeframe( $this->timeframeId );
		$this->assertTrue( $this->instance->isInTimeframe( $timeframe ) );
	}

	public function testGetName() {
		$this->assertTrue( date( 'l', strtotime( self::CURRENT_DATE ) ) == $this->instance->getName() );
	}

	public function testGetTimeframes() {
		$this->assertTrue(count($this->instance->getTimeframes()) == 1);
	}

	public function testGetRestrictions() {
		$this->assertTrue( count($this->instance->getRestrictions()) == 0 );

		$this->createRestriction(
			"hint",
			$this->locationId,
			$this->itemId,
			strtotime(self::CURRENT_DATE),
			strtotime("tomorrow", strtotime(self::CURRENT_DATE))
		);

		$this->assertIsArray($this->instance->getRestrictions());
		$this->assertTrue(count($this->instance->getRestrictions()) == 1);
	}

}
