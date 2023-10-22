<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Model\Booking;
use CommonsBooking\Service\TimeframeExport;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use DateTime;
use stdClass;

class TimeframeExportTest extends CustomPostTypeTest
{
	protected Booking $booking;

	public function testCron() {
		$yesterday = new DateTime(self::CURRENT_DATE);
		$yesterday->modify('-1 day');
		$tomorrow = new DateTime(self::CURRENT_DATE);
		$tomorrow->modify('+1 day');
		$testFile = '/tmp/test.csv';
		$export = new TimeframeExport(
			Timeframe::BOOKING_ID,
			$yesterday->format('Y-m-d'),
			$tomorrow->format('Y-m-d')
		);
		$export->setCron();
		$export->getExportData();
		$export->getCSV($testFile);
		$this->assertFileExists($testFile);
		$this->assertFileIsReadable($testFile);
		$content = file_get_contents($testFile);
		$this->assertNotEmpty($content);
		$objects = $this->csvStringToStdObjects($content);
		$this->assertEquals(1, count($objects));
		$exportedBooking = reset($objects);
		$this->assertEquals($this->booking->ID, $exportedBooking->ID);
	}

	public function testGetCSV()
	{
		$yesterday = new DateTime(self::CURRENT_DATE);
		$yesterday->modify('-1 day');
		$tomorrow = new DateTime(self::CURRENT_DATE);
		$tomorrow->modify('+1 day');
		$export = new TimeframeExport(
			Timeframe::BOOKING_ID,
			$yesterday->format('Y-m-d'),
			$tomorrow->format('Y-m-d')
		);
		$export->getExportData();
		$csv = $export->getCSV();
		$objects = $this->csvStringToStdObjects($csv);
		$this->assertEquals(1, count($objects));
		$exportedBooking = reset($objects);
		$this->assertEquals($this->booking->ID, $exportedBooking->ID);

	}

	public static function csvStringToStdObjects( $csvString ): array {
		$rows   = explode( "\n", $csvString );
		$header = str_getcsv( array_shift( $rows ), ';' );

		$result = [];
		foreach ( $rows as $row ) {
			if ( empty( $row ) ) {
				continue;
			}
			$data = str_getcsv( $row, ';' );
			$obj  = new stdClass();

			foreach ( $header as $index => $field ) {
				$obj->$field = $data[ $index ] ?? null;
			}

			$result[] = $obj;
		}

		return $result;
	}


	protected function setUp(): void {
		parent::setUp();
		$this->booking = new Booking(
			$this->createConfirmedBookingStartingToday()
		);
	}

	protected function tearDown(): void {
		parent::tearDown();
	}
}
