<?php

namespace CommonsBooking\Tests\View;

use CommonsBooking\Model\Booking;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\View\TimeframeExport;
use SlopeIt\ClockMock\ClockMock;

class TimeframeExportTest extends CustomPostTypeTest {

	protected $fileUnderTest;
	protected $directoryUnderTest;

	public function testGetTimeframeData() {
		$timeframeOneItemAndLocation = new Timeframe( $this->createBookableTimeFrameIncludingCurrentDay() );
		$dataArray = TimeframeExport::getTimeframeData( [ $timeframeOneItemAndLocation ] );
		$this->assertEquals( 1, count( $dataArray ) );

		$secondItem = $this->createItem( 'second-item' );
		$timeframeTwoItemsOneLocation = new Timeframe (
			$this->createTimeframe(
				$this->locationId,
				[ $this->itemId, $secondItem ],
				strtotime( self::CURRENT_DATE ),
				strtotime( '+1 week', strtotime( self::CURRENT_DATE ) )
			)
		);
		$dataArray = TimeframeExport::getTimeframeData( [ $timeframeTwoItemsOneLocation ] );
		$this->assertEquals( 2, count( $dataArray ) );

		$secondLocation = $this->createLocation( 'second-location' );
		$timeframeTwoItemsTwoLocations = new Timeframe (
			$this->createTimeframe(
				[ $this->locationId, $secondLocation ],
				[ $this->itemId, $secondItem ],
				strtotime( self::CURRENT_DATE ),
				strtotime( '+1 week', strtotime( self::CURRENT_DATE ) )
			)
		);
		$dataArray = TimeframeExport::getTimeframeData( [ $timeframeTwoItemsTwoLocations ] );
		$this->assertEquals( 4, count( $dataArray ) );

		$booking = new Booking($this->createConfirmedBookingStartingToday());
		$dataArray = TimeframeExport::getTimeframeData( [ $booking ] );
		$this->assertEquals( 1, count( $dataArray ) );
	}

	/**
	 * @ignore
	 */
	public function testExportCsv_testsFileCreation() {

		// TODO think about changing the signature of exportCSV or getExportData to include startDate and endDate, then it is less coupled to the actual usage atm
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		Settings::updateOption( 'commonsbooking_options_export', 'export-timerange', 14 );

		$timeframeOneItemAndLocation = new Timeframe( $this->createBookableTimeFrameIncludingCurrentDay() );

		TimeframeExport::exportCsv( $this->directoryUnderTest );

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
		$timeframeOneItemAndLocation = new Timeframe( $this->createBookableTimeFrameIncludingCurrentDay() );
		$dataArray = TimeframeExport::getTimeframeData( [ $timeframeOneItemAndLocation ] );
		$this->assertEquals( 1, count( $dataArray ) );

		$secondItem = $this->createItem( 'second-item' );
		$timeframeTwoItemsOneLocation = new Timeframe (
			$this->createTimeframe(
				$this->locationId,
				[ $this->itemId, $secondItem ],
				strtotime( self::CURRENT_DATE ),
				strtotime( '+1 week', strtotime( self::CURRENT_DATE ) )
			)
		);
		$dataArray = TimeframeExport::getTimeframeData( [ $timeframeTwoItemsOneLocation ] );
		$this->assertEquals( 2, count( $dataArray ) );

		Settings::updateOption( 'commonsbooking_options_export', 'export-timerange', '14' );

		$result = TimeFrameExport::getExportData( true );

		$this->assertEquals( 2, count( $result ));
		ClockMock::reset();
	}

	public function tearDown():void {
		parent::tearDown();

		if ($this->fileUnderTest)
			wp_delete_file( $this->fileUnderTest );

		rmdir( $this->directoryUnderTest );
	}

	public function setUp(): void {
		parent::setUp();

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
}
