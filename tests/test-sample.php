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

	/** @var \CommonsBooking\Wordpress\CustomPostType\Timeframe */
	private $timeframe;

	public function setUp(): void {
		$this->timeframe = Mockery::mock(\CommonsBooking\Wordpress\CustomPostType\Timeframe::class);

	}

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

	public function test_TimeframeIsBookable() {
		$this->assertTrue($this->timeframe::$postType == 'cb_timeframe');
	}
}
