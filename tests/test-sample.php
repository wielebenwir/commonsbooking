<?php
/**
 * Class SampleTest
 *
 * @package Commonsbooking
 */

/**
 * Sample test case.
 */
class SampleTest extends WP_UnitTestCase {

	/**
	 * A single example test.
	 */
	public function test_sample() {
		// Replace this with some actual testing code.
		$this->assertTrue( true );
	}

	public function test_Plugin_function() {
		$this->assertTrue(\CommonsBooking\Plugin::returnString() == "string");
	}
}
