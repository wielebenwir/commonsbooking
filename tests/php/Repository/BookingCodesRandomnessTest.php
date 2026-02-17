<?php

namespace CommonsBooking\Tests\Repository;

use CommonsBooking\Repository\BookingCodes;
use CommonsBooking\Settings\Settings;
use SlopeIt\ClockMock\ClockMock;

class BookingCodesRandomnessTest extends BookingCodesTest {

	// Helper function to delete all booking codes
	private static function deleteAllBookingCodes() {
		global $wpdb;
		$table_name = $wpdb->prefix . BookingCodes::$tablename;
		$wpdb->query( "DELETE FROM {$table_name}" );
	}

	// Helper function to get a booking code for today
	private function getCodeForToday() {
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		$todayDate = date( 'Y-m-d', strtotime( self::CURRENT_DATE ) );
		BookingCodes::generate( $this->timeframeWithEndDate, 1 );
		$codeObj = BookingCodes::getCode( $this->timeframeWithEndDate, $this->itemID, $this->locationID, $todayDate, 1 );
		return $codeObj->getCode();
	}

	public function testGeneratedCodesAreRandom() {
		// Load code pool into an array and expect at least 6 codes
		$codePoolString = Settings::getOption( 'commonsbooking_options_bookingcodes', 'bookingcodes' );
		$codePoolArray  = explode( ',', trim( $codePoolString ) );
		$this->assertGreaterThanOrEqual( 6, count( $codePoolArray ) );

		// Prepare the Pearson chi-squared test by generating a code for today multiple times
		// and storing the samples in an array. The number of samples should be at least
		// 5 times the number of codes in the pool, but preferably many more
		$tests = 100 * count( $codePoolArray );

		$generatedCodeForTodayArray = [];
		for ( $i = 0; $i < $tests; $i++ ) {
			self::deleteAllBookingCodes();
			$generatedCodeForTodayArray[] = $this->getCodeForToday();
		}

		// Count the occurrences of each code
		$codeOccurrenceAssociativeArray = array_count_values( $generatedCodeForTodayArray );
		$codeOccurrenceArray            = array_values( $codeOccurrenceAssociativeArray );

		// As the number of samples is high, we expect each pool code to be generated at least once
		$this->assertEquals( count( $codePoolArray ), count( $codeOccurrenceArray ) );

		// Perform the chi-squared test on the sampled codes
		// Null hypothesis: The codes have a uniform probability distribution
		// Alpha = 0.001 (probability of rejecting the null hypothesis if it is true is 0.1%)
		// If there are 6 codes in the pool, the degrees of freedom is 5 and the
		// upper-tail critical value is 22.458
		$chi2_critical = 22.458;

		// Calculate chi-squared value from the sampled data
		$expectedOccurrences = floatval( $tests ) / count( $codePoolArray );

		$chi2 = 0.0;
		foreach ( $codeOccurrenceArray as $occurrence ) {
			$chi2 += ( ( $expectedOccurrences - $occurrence ) ** 2 ) / $expectedOccurrences;
		}

		// Assert that we do not reject the null hypothesis that the code generation is random
		$this->assertLessThan( $chi2_critical, $chi2 );
	}

	protected function setUp(): void {
		parent::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}
}
