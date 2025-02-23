<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Model\Day;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

use CommonsBooking\Model\Week;

/**
 * Tests weekdays
 */
class WeekTest extends CustomPostTypeTest {

	private Week $week;

	/**
	 * @group failing
	 */
	public function testGetDays() {
		$this->week = new Week( 2023, 120 );
		$this->assertEquals( 7, count( $this->week->getDays() ) );
		$this->assertEquals(
			array(
				new Day( '2023-05-01' ),
				new Day( '2023-05-02' ),
				new Day( '2023-05-03' ),
				new Day( '2023-05-04' ),
				new Day( '2023-05-05' ),
				new Day( '2023-05-06' ),
				new Day( '2023-05-07' ),
			),
			$this->week->getDays()
		);
	}

	/**
	 * @group failing
	 */
	public function testGetDays2() {
		$this->week = new Week( 2023, 121 );
		$this->assertEquals( 6, count( $this->week->getDays() ) );
		$this->assertEquals(
			array(
				new Day( '2023-05-02' ),
				new Day( '2023-05-03' ),
				new Day( '2023-05-04' ),
				new Day( '2023-05-05' ),
				new Day( '2023-05-06' ),
				new Day( '2023-05-07' ),
			),
			$this->week->getDays()
		);
	}
}
