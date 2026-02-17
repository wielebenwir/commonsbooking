<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Model\Item;
use CommonsBooking\Model\Location;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use SlopeIt\ClockMock\ClockMock;

class BookablePostTest extends CustomPostTypeTest {

	protected int $timeframeId;
	protected Location $locationModel;
	protected Item $itemModel;
	protected Timeframe $timeframeModel;

	public function testIsBookable() {
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		// test basic getting with just one bookable timeframe for a location
		$this->assertTrue( $this->locationModel->isBookable() );

		// now the same for an item
		$this->assertTrue( $this->itemModel->isBookable() );
	}

	public function testGetBookableTimeframes() {
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		// test basic getting with just one bookable timeframe for a location
		$bookableTimeframes = $this->locationModel->getBookableTimeframes( true );
		$this->assertEquals( 1, count( $bookableTimeframes ) );
		$this->assertEquals( $this->timeframeModel, $bookableTimeframes[0] );

		// now the same for an item
		$bookableTimeframes = $this->itemModel->getBookableTimeframes( true );
		$this->assertEquals( 1, count( $bookableTimeframes ) );
		$this->assertEquals( $this->timeframeModel, $bookableTimeframes[0] );

		// now we create more than one item + timeframe for the first location and check if we get both
		$item2      = new Item( $this->createItem( 'Item2', 'publish' ) );
		$timeframe2 = new Timeframe( $this->createBookableTimeFrameIncludingCurrentDay( $this->locationID, $item2->ID ) );

		$bookableTimeframes = $this->locationModel->getBookableTimeframes( true );
		$this->assertEquals( 2, count( $bookableTimeframes ) );
		$this->assertEquals( $this->timeframeModel, $bookableTimeframes[0] );
		$this->assertEquals( $timeframe2, $bookableTimeframes[1] );

		// and now let's test if we can get the specific timeframe for just one item for the location
		$bookableTimeframes = $this->locationModel->getBookableTimeframes( true, [], [ $item2->ID ] );
		$this->assertEquals( 1, count( $bookableTimeframes ) );
		$this->assertEquals( $timeframe2, $bookableTimeframes[0] );
	}

	protected function setUp(): void {
		parent::setUp();

		$this->timeframeId    = $this->createBookableTimeFrameIncludingCurrentDay();
		$this->locationModel  = new Location( $this->locationID );
		$this->itemModel      = new Item( $this->itemID );
		$this->timeframeModel = new Timeframe( $this->timeframeId );
	}

	protected function tearDown(): void {
		parent::tearDown();
	}
}
