<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Model\Booking;
use CommonsBooking\Service\TimeframeExport;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use DateTime;
use SlopeIt\ClockMock\ClockMock;
use stdClass;

class TimeframeExportTest extends CustomPostTypeTest
{
	protected Booking $booking;
	protected $fileUnderTest;
	protected $directoryUnderTest;

	public function testGetTimeframeData() {
		$timeframeOneItemAndLocation = $this->createBookableTimeFrameIncludingCurrentDay();
		$dataArray = \CommonsBooking\Service\TimeframeExport::getTimeframeData( [ $timeframeOneItemAndLocation ] );
		$this->assertEquals( 1, count( $dataArray ) );

		$secondItem = $this->createItem( 'second-item' );
		$timeframeTwoItemsOneLocation =
			$this->createTimeframe(
				$this->locationId,
				[ $this->itemId, $secondItem ],
				strtotime( self::CURRENT_DATE ),
				strtotime( '+1 week', strtotime( self::CURRENT_DATE ) )
		);
		$dataArray = TimeframeExport::getTimeframeData( [ $timeframeTwoItemsOneLocation ] );
		$this->assertEquals( 2, count( $dataArray ) );

		$secondLocation = $this->createLocation( 'second-location' );
		$timeframeTwoItemsTwoLocations =
			$this->createTimeframe(
				[ $this->locationId, $secondLocation ],
				[ $this->itemId, $secondItem ],
				strtotime( self::CURRENT_DATE ),
				strtotime( '+1 week', strtotime( self::CURRENT_DATE ) )
		);
		$dataArray = TimeframeExport::getTimeframeData( [ $timeframeTwoItemsTwoLocations ] );
		$this->assertEquals( 4, count( $dataArray ) );

		$booking = $this->createConfirmedBookingStartingToday();
		$dataArray = TimeframeExport::getTimeframeData( [ $booking ] );
		$this->assertEquals( 1, count( $dataArray ) );
		$this->assertArrayHasKey( 'pickup' , $dataArray[0] ); //because this is only set for bookings
	}

	public function testExportCsvAndFileCreation() {

		// TODO think about changing the signature of exportCSV or getExportData to include startDate and endDate, then it is less coupled to the actual usage atm
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		Settings::updateOption( 'commonsbooking_options_export', 'export-timerange', 14 );
		Settings::updateOption( 'commonsbooking_options_export', 'export-type', 'all' );

		$this->createBookableTimeFrameIncludingCurrentDay();

		TimeframeExport::cronExport( $this->directoryUnderTest );

		$fileName = scandir( $this->directoryUnderTest )[2];
		$this->fileUnderTest = $this->directoryUnderTest . $fileName;

		// Check basic properties
		$this->assertTrue( file_exists( $this->fileUnderTest ) );
		$this->assertGreaterThan( 0, filesize( $this->fileUnderTest ) );
		ClockMock::reset();

		// Parse csv contents
		$i = 0;
		$header = null;
		$firstLine = null;
		$file = fopen($this->fileUnderTest, 'r');
		while (($line = fgetcsv($file)) !== FALSE) {
			if ( $i == 0 ) {
				$header = $line[0];
			}
			if ( $i == 1 ) {
				$firstLine = $line[0];
			}
			$i += 1;
		}
		fclose($file);

		$this->assertNotNull( $header );
		$firstColumn = explode( ";", $header)[0];

		$this->assertEquals( "ID", $firstColumn );
		$this->assertNotNull( $firstLine );
	}

	public function testGetExportData() {
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		$timeframeOneItemAndLocation = $this->createBookableTimeFrameIncludingCurrentDay();
		$dataArray = TimeframeExport::getTimeframeData( [ $timeframeOneItemAndLocation ] );
		$this->assertEquals( 1, count( $dataArray ) );

		$secondItem = $this->createItem( 'second-item' );
		$timeframeTwoItemsOneLocation = $this->createTimeframe(
			$this->locationId,
			[ $this->itemId, $secondItem ],
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 week', strtotime( self::CURRENT_DATE ) )
		);
		$dataArray = TimeframeExport::getTimeframeData( [ $timeframeTwoItemsOneLocation ] );
		$this->assertEquals( 2, count( $dataArray ) );
	}

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
		$objects = static::csvStringToStdObjects($content);
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
		$objects = static::csvStringToStdObjects($csv);
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

		$this->directoryUnderTest = '/tmp/commonsbooking-timeframe-export-directory/';

		// Deletes directory if existent
		if ( file_exists( $this->directoryUnderTest ) ) {
			foreach( scandir( $this->directoryUnderTest ) as $fileInDir ) {
				wp_delete_file( $this->directoryUnderTest . $fileInDir );
			}
			rmdir( $this->directoryUnderTest );
		}

		// Creates new
		mkdir( $this->directoryUnderTest );
	}

	protected function tearDown(): void {
		parent::tearDown();

		if ($this->fileUnderTest)
			wp_delete_file( $this->fileUnderTest );

		rmdir( $this->directoryUnderTest );
	}
}
