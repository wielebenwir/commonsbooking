<?php

namespace CommonsBooking\Tests\Helper;

use CommonsBooking\Helper\Helper;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use DateTime;
use DateTimeInterface;

function dt_to_ts( $time_str ): DateTime {
	return DateTime::createFromFormat(DateTimeInterface::ATOM, $time_str);
}

/**
 * Returns date time int
 *
 * @param $time_str string like '2020-01-01T12:00:00+00:00'
 *
 * @return int
 */
function to_ts( string $time_str ): DateTime {
	return DateTime::createFromFormat('Y-m-d', $time_str);
}

class HelperTest extends CustomPostTypeTest {

	public function test_whenAllTimeFramesOverlap_returnOneTimeframe() {

		$arrayOfBookableDates = Helper::mergeRangesToBookableDate(
			array(
				array(
					"start_date" => to_ts("2020-01-01"),
					"end_date"   => to_ts("2020-01-03")
				),
				array(
					"start_date" => to_ts("2020-01-02"),
					"end_date"   => to_ts("2020-01-04")
				)
			)
		);

		$this->assertTrue(count($arrayOfBookableDates) == 1);
		$merged = $arrayOfBookableDates[0];

		$this->assertTrue($merged['start_date'] == to_ts("2020-01-01"));
		$this->assertTrue($merged['end_date']   == to_ts("2020-01-04"));
	}


	public function test_whenTimeframesOverlapOnDay_returnOneTimeframe() {

		$arrayOfBookableDates = Helper::mergeRangesToBookableDate(
			array(
				array(
					"start_date" => to_ts("2020-01-01"),
					"end_date"   => to_ts("2020-01-02")
				),
				array(
					"start_date" => to_ts("2020-01-02"),
					"end_date"   => to_ts("2020-01-04")
				)
			)
		);

		$this->assertTrue(count($arrayOfBookableDates) == 1);
		$merged = $arrayOfBookableDates[0];

		$this->assertTrue($merged['start_date'] == to_ts("2020-01-01"));
		$this->assertTrue($merged['end_date']   == to_ts("2020-01-04"));
	}


	public function test_whenTwoTimeframesDontOverlap_returnTwoTimeframes() {

		$arrayOfBookableDates = Helper::mergeRangesToBookableDate(
			array(
				array(
					"start_date" => dt_to_ts('2020-01-01T12:00:00+00:00'),
					"end_date"   => dt_to_ts('2020-01-02T12:00:00+00:00'),
				),
				array(
					"start_date" => dt_to_ts('2020-01-03T12:00:00+00:00'),
					"end_date"   => dt_to_ts('2020-01-04T12:00:00+00:00'),
				)
			)
		);

		$this->assertTrue(count($arrayOfBookableDates) == 2);

		$this->assertTrue($arrayOfBookableDates[0]['start_date'] == dt_to_ts('2020-01-01T12:00:00+00:00'));
		$this->assertTrue($arrayOfBookableDates[1]['end_date']   == dt_to_ts('2020-01-04T12:00:00+00:00'));
	}

	public function test_whenThreeTimeFramesOverlap_returnOneTimeframe() {

		$arrayOfBookableDates = Helper::mergeRangesToBookableDate(
			array(
				array(
					"start_date" => to_ts("2020-01-01"),
					"end_date"   => to_ts("2020-01-05")
				),
				array(
					"start_date" => to_ts("2020-01-02"),
					"end_date"   => to_ts("2020-01-04")
				),
				array(
					"start_date" => to_ts("2020-01-05"),
					"end_date"   => to_ts("2020-01-06")
				)
			)
		);

		$this->assertTrue(count($arrayOfBookableDates) == 1);
		$merged = $arrayOfBookableDates[0];

		$this->assertTrue($merged['start_date'] == to_ts("2020-01-01"));
		$this->assertTrue($merged['end_date']   == to_ts("2020-01-06"));
	}

	public function test_whenTimeFrameHasOpenInterval_returnOneTimeFrame() {
		$arrayOfBookableDates = Helper::mergeRangesToBookableDate(
			array(
				array(
					"start_date" => to_ts("2020-01-01")
				),
				array(
					"start_date" => to_ts("2020-01-01"),
					"end_date"   => to_ts("2020-01-04")
				)
			)
		);

		$this->assertTrue(count($arrayOfBookableDates) == 1);
		$merged = $arrayOfBookableDates[0];

		$this->assertTrue($merged['start_date'] == to_ts("2020-01-01"));
		$this->assertFalse(array_key_exists('end_date', $merged));
	}
}