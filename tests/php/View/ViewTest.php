<?php

namespace CommonsBooking\Tests\View;

use CommonsBooking\Model\Item;
use CommonsBooking\Model\Location;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\View\View;
use SlopeIt\ClockMock\ClockMock;
use CommonsBooking\Wordpress\Options\AdminOptions;

class ViewTest extends CustomPostTypeTest {

	protected const bookingDaysInAdvance = 30;
	protected Item $item;
	protected Location $location;
	protected $now;

	public function testGetShortcodeDataWithFourRangesByItem() {
		$shortCodeData = View::getShortcodeData( new Item( $this->itemID ), 'Item' );
		$this->assertTrue( is_array( $shortCodeData[ $this->itemID ]['ranges'] ) );
		$this->assertCount( 4, $shortCodeData[ $this->itemID ]['ranges'] );

		// Check for specific timeframe start date
		$this->assertEquals( $shortCodeData[ $this->itemID ]['ranges'][0]['start_date'], strtotime( '+2 days midnight', $this->now ) );
	}

	public function testGetShortcodeDataWithFourRangesByLocation() {
		$shortCodeData = View::getShortcodeData( new Location( $this->locationID ), 'Location' );
		$this->assertTrue( is_array( $shortCodeData[ $this->locationID ]['ranges'] ) );
		$this->assertCount( 4, $shortCodeData[ $this->locationID ]['ranges'] );

		// Check for specific timeframe start date
		$this->assertEquals( $shortCodeData[ $this->locationID ]['ranges'][0]['start_date'], strtotime( '+2 days midnight', $this->now ) );
	}

	public function testShortcodeForLocationView() {
		$body = \CommonsBooking\View\Location::shortcode( array() );
		$html = '<html><body>' . $body . '</body></html>';

		// naive way of testing html validity
		libxml_use_internal_errors( true );
		$doc = new \DOMDocument();
		$this->assertTrue( $doc->loadHTML( $html ) );
		$this->assertEquals( 0, count( libxml_get_errors() ) );

		// assert presence of location and item string
		$this->assertStringContainsString( $this->item->post_title, $body );
		$this->assertStringContainsString( $this->location->post_title, $body );
	}

	public function testShortcodeForItemView() {
		$body = \CommonsBooking\View\Item::shortcode( array() );
		$html = '<html><body>' . $body . '</body></html>';

		// naive way of testing html validity
		libxml_use_internal_errors( true );
		$doc = new \DOMDocument();
		$this->assertTrue( $doc->loadHTML( $html ) );
		$this->assertEquals( 0, count( libxml_get_errors() ) );

		// assert presence of location and item string
		$this->assertStringContainsString( $this->item->post_title, $body );
		$this->assertStringContainsString( $this->location->post_title, $body );
	}

	public function testShortcodeItemTable() {
		$body = \CommonsBooking\View\Calendar::shortcode( array() );
		$html = '<html><body>' . $body . '</body></html>';

		// naive way of testing html validity
		libxml_use_internal_errors( true );
		$doc = new \DOMDocument();
		$this->assertTrue( $doc->loadHTML( $html ) );
		$lib_XML_errors = libxml_get_errors();
		// TODO: This fails
		// $this->assertEquals( 0, count( $lib_XML_errors ));
	}

	public function testGetColorCSS() {
		// set the default color values
		AdminOptions::setOptionsDefaultValues();
		$defaultValue = '--commonsbooking-color-primary: #84AE53;';
		$colorCSS     = View::getColorCSS();
		$this->assertStringContainsString( $defaultValue, $colorCSS );
	}

	protected function setUp(): void {
		parent::setUp();
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );

		$now       = time();
		$this->now = $now;

		$this->item     = new Item( $this->itemID );
		$this->location = new Location( $this->locationID );

		$timeframeId = $this->createTimeframe(
			$this->locationID,
			$this->itemID,
			strtotime( '+5 days midnight', $now ),
			strtotime( '+6 days midnight', $now ),
		);
		// set booking days in advance
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS, self::bookingDaysInAdvance );

		$timeframeId = $this->createTimeframe(
			$this->locationID,
			$this->itemID,
			strtotime( '+2 days midnight', $now ),
			strtotime( '+3 days midnight', $now ),
		);// set booking days in advance
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS, self::bookingDaysInAdvance );

		$timeframeId = $this->createTimeframe(
			$this->locationID,
			$this->itemID,
			strtotime( '+8 days midnight', $now ),
			strtotime( '+9 days midnight', $now ),
		);
		// set booking days in advance
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS, self::bookingDaysInAdvance );

		$timeframeId = $this->createTimeframe(
			$this->locationID,
			$this->itemID,
			strtotime( '+12 days midnight', $now ),
			strtotime( '+13 days midnight', $now ),
		);
		// set booking days in advance
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS, self::bookingDaysInAdvance );

		$timeframeId = $this->createTimeframe(
			$this->locationID,
			$this->itemID,
			strtotime( '+14 days midnight', $now ),
			strtotime( '+15 days midnight', $now ),
		);
		// set booking days in advance
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS, self::bookingDaysInAdvance );

		// this timeframe should not be in shortcode data, because it's out of 30 days advanced booking range
		$timeframeId = $this->createTimeframe(
			$this->locationID,
			$this->itemID,
			strtotime( '+32 days midnight', $now ),
			strtotime( '+33 days midnight', $now ),
		);
		// set booking days in advance
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS, self::bookingDaysInAdvance );
	}

	protected function tearDown(): void {
		parent::tearDown();
	}
}
