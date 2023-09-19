<?php

namespace CommonsBooking\Tests\Helper;

use DateTime;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use CommonsBooking\Helper\Helper;

class HelperTest extends TestCase {

	public function test_whenAllTimeFramesOverlap_returnOneTimeframe() {
		$arrayOfBookableDates = Helper::mergeRangesToBookableDates(
			array(
				array(
					'start_date' => self::to_ts( '2020-01-01' ),
					'end_date'   => self::to_ts( '2020-01-03' ),
				),
				array(
					'start_date' => self::to_ts( '2020-01-02' ),
					'end_date'   => self::to_ts( '2020-01-04' ),
				),
			)
		);

		$this->assertCount( 1, $arrayOfBookableDates );
		$merged = $arrayOfBookableDates[0];

		$this->assertEquals( $merged['start_date'], self::to_ts( '2020-01-01' ) );
		$this->assertEquals( $merged['end_date'], self::to_ts( '2020-01-04' ) );
	}


	public function test_whenTimeframesOverlapOnDay_returnOneTimeframe() {
		$arrayOfBookableDates = Helper::mergeRangesToBookableDates(
			array(
				array(
					'start_date' => self::to_ts( '2020-01-01' ),
					'end_date'   => self::to_ts( '2020-01-02' ),
				),
				array(
					'start_date' => self::to_ts( '2020-01-02' ),
					'end_date'   => self::to_ts( '2020-01-04' ),
				),
			)
		);

		$this->assertCount( 1, $arrayOfBookableDates );
		$merged = $arrayOfBookableDates[0];

		$this->assertEquals( $merged['start_date'], self::to_ts( '2020-01-01' ) );
		$this->assertEquals( $merged['end_date'], self::to_ts( '2020-01-04' ) );
	}


	public function test_whenTwoTimeframesDontOverlap_returnTwoTimeframes() {
		$arrayOfBookableDates = Helper::mergeRangesToBookableDates(
			array(
				array(
					'start_date' => self::dt_to_ts( '2020-01-01T12:00:00+00:00' ),
					'end_date'   => self::dt_to_ts( '2020-01-02T12:00:00+00:00' ),
				),
				array(
					'start_date' => self::dt_to_ts( '2020-01-03T12:00:00+00:00' ),
					'end_date'   => self::dt_to_ts( '2020-01-04T12:00:00+00:00' ),
				),
			)
		);

		$this->assertCount( 2, $arrayOfBookableDates );

		$this->assertEquals( $arrayOfBookableDates[0]['start_date'], self::dt_to_ts( '2020-01-01T12:00:00+00:00' ) );
		$this->assertEquals( $arrayOfBookableDates[1]['end_date'],   self::dt_to_ts( '2020-01-04T12:00:00+00:00' ) );
	}

	public function test_whenThreeTimeFramesOverlap_returnOneTimeframe() {
		$arrayOfBookableDates = Helper::mergeRangesToBookableDates(
			array(
				array(
					'start_date' => self::to_ts( '2020-01-01' ),
					'end_date'   => self::to_ts( '2020-01-05' ),
				),
				array(
					'start_date' => self::to_ts( '2020-01-02' ),
					'end_date'   => self::to_ts( '2020-01-04' ),
				),
				array(
					'start_date' => self::to_ts( '2020-01-05' ),
					'end_date'   => self::to_ts( '2020-01-06' ),
				),
			)
		);

		$this->assertCount( 1, $arrayOfBookableDates );
		$merged = $arrayOfBookableDates[0];

		$this->assertEquals( $merged['start_date'], self::to_ts( '2020-01-01' ) );
		$this->assertEquals( $merged['end_date'], self::to_ts( '2020-01-06' ) );
	}

	public function test_whenTimeFrameHasOpenInterval_returnOneTimeFrame() {
		$arrayOfBookableDates = Helper::mergeRangesToBookableDates(
			array(
				array(
					'start_date' => self::to_ts( '2020-01-01' ),
					'end_date'   => false,
				),
				array(
					'start_date' => self::to_ts( '2020-01-01' ),
					'end_date'   => self::to_ts( '2020-01-04' ),
				),
			)
		);

		$this->assertCount( 1, $arrayOfBookableDates );
		$merged = $arrayOfBookableDates[0];

		$this->assertEquals( self::to_ts( '2020-01-01' ), $merged['start_date'] );
		$this->assertEquals( false, $merged['end_date'] );
	}

	public function test_whenTimeFrameHasOpenInterval2_returnOneTimeFrame() {
		$arrayOfBookableDates = Helper::mergeRangesToBookableDates(
			array(
				array(
					'start_date' => self::to_ts( '2020-01-01' ),
					'end_date'   => self::to_ts( '2020-04-01' ),
				),
				array(
					'start_date' => self::to_ts( '2020-03-01' ),
					'end_date'   => false,
				),
			)
		);

		$this->assertCount( 1, $arrayOfBookableDates );
		$merged = $arrayOfBookableDates[0];

		$this->assertEquals( self::to_ts( '2020-01-01' ), $merged['start_date'] );
		$this->assertEquals( false, $merged['end_date'] );
	}

	public function test_whenTimeFrameHasOpenInterval3_HasSameStart_butOneOpen_returnOneOpenInterval() {
		$arrayOfBookableDates = Helper::mergeRangesToBookableDates(
			array(
				array(
					'start_date' => 1673136000,
					'end_date'   => false,
				),
				array(
					'start_date' => 1673136000,
					'end_date'   => 1681430399,
				),
			)
		);

		$this->assertCount( 1, $arrayOfBookableDates );
		$merged = $arrayOfBookableDates[0];

		$this->assertEquals( 1673136000, $merged['start_date'] );
		$this->assertEquals( false, $merged['end_date'] );
	}

	private function dt_to_ts( $time_str ): DateTime {
		return DateTime::createFromFormat( DateTimeInterface::ATOM, $time_str );
	}

	/**
	 * Returns date time object from string
	 *
	 * @param $time_str string like '2020-01-01T12:00:00+00:00'
	 *
	 * @return \DateTime
	 */
	private function to_ts( string $time_str ): DateTime {
		return DateTime::createFromFormat( 'Y-m-d', $time_str );
	}
}
