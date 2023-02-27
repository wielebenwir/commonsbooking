<?php

namespace CommonsBooking\Tests\Helper;

use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

/**
 * Returns date time int
 *
 * @param $time_str like '2020-01-01T12:00:00+00:00'
 *
 * @return int
 */
function dt_to_ts( $time_str ): int {
	return DateTime::createFromFormat(DateTimeInterface::ISO8601, $time_str);
}

function to_ts( $time_str ): int {
	return DateTime::createFromFormat('Y-m-d', $time_str);
}

class HelperTest extends CustomPostTypeTest {

	public function test_whenAllTimeFramesOverlap_returnOneTimeframe() {

		$arrayOfBookableDates = Helper::mergeRangesToBookableDate(
			array(
				array(
					"start-date" => to_ts("2020-01-01"),
					"end-date"   => to_ts("2020-01-03")
				),
				array(
					"start-date" => to_ts("2020-01-02"),
					"end-date"   => to_ts("2020-01-04")
				)
			)
		);

		$this->assertTrue(count($arrayOfBookableDates) == 1);
		$merged = $arrayOfBookableDates[0];

		$this->assertTrue($merged['start-date'] == to_ts("2020-01-01"));
		$this->assertTrue($merged['end-date']   == to_ts("2020-01-04"));
	}


	public function test_whenTimeframesOverlapOnDay_returnOneTimeframe() {

		$arrayOfBookableDates = Helper::mergeRangesToBookableDate(
			array(
				array(
					"start-date" => to_ts("2020-01-01"),
					"end-date"   => to_ts("2020-01-02")
				),
				array(
					"start-date" => to_ts("2020-01-02"),
					"end-date"   => to_ts("2020-01-04")
				)
			)
		);

		$this->assertTrue(count($arrayOfBookableDates) == 1);
		$merged = $arrayOfBookableDates[0];

		$this->assertTrue($merged['start-date'] == to_ts("2020-01-01"));
		$this->assertTrue($merged['end-date']   == to_ts("2020-01-04"));
	}

	/*
	public function test_whenTwoTimeframesDontOverlap_returnTwoTimeframes() {

		$arrayOfBookableDates = Helper::mergeRangesToBookableDate(
			array(
				array(
					"start-date" => to_ts('2020-01-01T12:00:00+00:00'),
					"end-date"   => to_ts('2020-01-02T12:00:00+00:00'),
				),
				array(
					"start-date" => to_ts('2020-01-03T12:00:00+00:00'),
					"end-date"   => to_ts('2020-01-04T12:00:00+00:00'),
				)
			)
		);

		$this->assertTrue(count($arrayOfBookableDates) == 2);

		$this->assertTrue($arrayOfBookableDates[0]['start-date'] == "2020-01-01");
		$this->assertTrue($arrayOfBookableDates[1]['end-date']   == "2020-01-04");
	}

	public function test_whenThreeTimeFramesOverlap_returnOneTimeframe() {

		$arrayOfBookableDates = Helper::mergeRangesToBookableDate(
			array(
				array(
					"start-date" => to_ts("2020-01-01"),
					"end-date"   => to_ts("2020-01-05")
				),
				array(
					"start-date" => to_ts("2020-01-02"),
					"end-date"   => to_ts("2020-01-04")
				),
				array(
					"start-date" => to_ts("2020-01-05"),
					"end-date"   => to_ts("2020-01-06")
				)
			)
		);

		$this->assertTrue(count($arrayOfBookableDates) == 1);
		$merged = $arrayOfBookableDates[0];

		$this->assertTrue($merged['start-date'] == "2020-01-01");
		$this->assertTrue($merged['end-date']   == "2020-01-06");
	}*/
}