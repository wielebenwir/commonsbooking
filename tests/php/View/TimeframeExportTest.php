<?php

namespace CommonsBooking\Tests\View;

use CommonsBooking\Model\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\View\TimeframeExport;

class TimeframeExportTest extends CustomPostTypeTest {

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
	}
}
