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

class TimeframeExportLargeTest extends CustomPostTypeTest {

	protected Booking $booking;
	protected $fileUnderTest;
	protected $directoryUnderTest;

	protected array $bookings;

	/**
	 * @group test23
	 */
	public function testGetCSV_with1000Records() {

		$numberOfDays     = 1700;
		$numberOfBookings = 10;

		// Initialize date range
		$threeYearsAgo = new DateTime( self::CURRENT_DATE );
		$threeYearsAgo->modify( "- $numberOfDays days" );
		$tomorrow = new DateTime( self::CURRENT_DATE );
		$tomorrow->modify( '+1 day' );

		echo "From " . $threeYearsAgo->format( "Y-m-d" ) . " to " . $tomorrow->format( "Y-m-d" ) . " days\n";

		// Generate unique random day offsets using array_rand
		$dayOffsets = (array) array_rand( array_flip( range( 0, $numberOfDays ) ), $numberOfBookings );
		sort( $dayOffsets );

		echo wp_json_encode( $dayOffsets ) . PHP_EOL;

		// Create 1000 Booking objects
		$this->bookings = [];
		foreach ( $dayOffsets as $i => $offset ) {
			$startDate = ( clone $threeYearsAgo )->modify( "+ $offset days" );
			$endDate   = ( clone $threeYearsAgo )->modify( '+ ' . ( $offset + 1 ) . ' days' );

			$this->bookings[] = new Booking(
				$this->createBooking(
					$this->locationId,
					$this->itemId,
					strtotime( $startDate->format( 'Y-m-d' ) ),
					strtotime( $endDate->format( 'Y-m-d' ) )
				)
			);
		}

		$this->assertEquals( $numberOfBookings, count( $this->bookings ) );

		echo "Step export " . $threeYearsAgo->format( 'Y-m-d' ) . " to " . $tomorrow->format( 'Y-m-d' ) . PHP_EOL;


		// Start with export
		$export = new TimeframeExport(
			Timeframe::BOOKING_ID,
			$threeYearsAgo->format( 'Y-m-d' ),
			$tomorrow->format( 'Y-m-d' )
		);
		$result = $export->getExportData();

		echo $result . PHP_EOL;

		echo "Step csv" . PHP_EOL;
		$csv = $export->getCSV();

		echo "Step csv to objects" . PHP_EOL;
		$objects = static::csvStringToStdObjects( $csv );

		$this->assertEquals( $numberOfBookings, count( $objects ) );

		$exportedBooking = reset( $objects );
		$this->assertEquals( $this->bookings[0]->ID, $exportedBooking->ID );
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

		$this->directoryUnderTest = '/tmp/commonsbooking-timeframe-export-directory/';

		// Deletes directory if existent
		if ( file_exists( $this->directoryUnderTest ) ) {
			foreach ( scandir( $this->directoryUnderTest ) as $fileInDir ) {
				wp_delete_file( $this->directoryUnderTest . $fileInDir );
			}
			rmdir( $this->directoryUnderTest );
		}

		// Creates new
		mkdir( $this->directoryUnderTest );
	}

	protected function tearDown(): void {
		parent::tearDown();

		// tearDown
		foreach ( $this->bookings as $booking ) {
			wp_delete_post( $booking->ID, true );
		}

		if ( $this->fileUnderTest ) {
			wp_delete_file( $this->fileUnderTest );
		}

		rmdir( $this->directoryUnderTest );
	}
}
