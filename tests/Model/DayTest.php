<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Model\Day;
use CommonsBooking\Repository\BookingCodes;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DayTest extends CustomPostTypeTest {

	private $instance;

	protected function setUp() {
		parent::setUp();
		$this->createBookableTimeFrameIncludingCurrentDay();

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
		$timeframe = get_post( $this->timeframeId );
		$this->assertTrue( $this->instance->isInTimeframe( $timeframe ) );
	}

	public function testGetName() {
		$this->assertTrue( date( 'l', strtotime( self::CURRENT_DATE ) ) == $this->instance->getName() );
	}
}
