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
		$shortCodeData = View::getShortcodeData( new Item( $this->itemId ), 'Item' );
		$this->assertTrue( is_array( $shortCodeData[ $this->itemId ]['ranges'] ) );
		$this->assertCount( 4, $shortCodeData[ $this->itemId ]['ranges'] );

		// Check for specific timeframe start date
		$this->assertEquals( $shortCodeData[ $this->itemId ]['ranges'][0]['start_date'], strtotime( '+2 days midnight', $this->now ) );
	}

	public function testGetShortcodeDataWithFourRangesByLocation() {
		$shortCodeData = View::getShortcodeData( new Location( $this->locationId ), 'Location' );
		$this->assertTrue( is_array( $shortCodeData[ $this->locationId ]['ranges'] ) );
		$this->assertCount( 4, $shortCodeData[ $this->locationId ]['ranges'] );

		// Check for specific timeframe start date
		$this->assertEquals( $shortCodeData[ $this->locationId ]['ranges'][0]['start_date'], strtotime( '+2 days midnight', $this->now ) );
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

	/**
	 * The static defaults are already compiled into public.css, so the runtime CSS
	 * must only contain the user-configurable overrides (and their derived values).
	 */
	public function testGetColorCSSOnlyContainsOverrides() {
		AdminOptions::setOptionsDefaultValues();
		$css = View::getColorCSS();

		$this->assertStringStartsWith( ':root', trim( $css ) );
		$this->assertStringContainsString( '--commonsbooking-color-primary: #84AE53;', $css );
		$this->assertStringContainsString( '--commonsbooking-textcolor-dark:', $css );
		$this->assertStringNotContainsString( '--commonsbooking-font-size', $css );
		$this->assertStringNotContainsString( '--commonsbooking-spacer', $css );
		$this->assertStringNotContainsString( '--commonsbooking-radius', $css );
	}

	public function testGetColorCSSDerivedVariables() {
		AdminOptions::setOptionsDefaultValues();
		$options                                = get_option( 'commonsbooking_options_templates' );
		$options['colorscheme_cancelcolor']     = '#112233';
		$options['colorscheme_acceptcolor']     = '#445566';
		$options['colorscheme_backgroundcolor'] = '#778899';
		update_option( 'commonsbooking_options_templates', $options );

		$css = View::getColorCSS();
		$this->assertStringContainsString( '--commonsbooking-color-error: #112233;', $css );
		$this->assertStringContainsString( '--commonsbooking-color-success: #445566;', $css );
		$this->assertStringContainsString( '--commonsbooking-color-gray-background: #778899;', $css );
	}

	public function testGetColorCSSRejectsInvalidValue() {
		AdminOptions::setOptionsDefaultValues();
		$options                             = get_option( 'commonsbooking_options_templates' );
		$options['colorscheme_primarycolor'] = 'red;}body{display:none';
		update_option( 'commonsbooking_options_templates', $options );

		$this->assertFalse( View::getColorCSS() );
	}

	public function testGetColorCSSReturnsFalseWhenUnset() {
		update_option( 'commonsbooking_options_templates', [] );
		$this->assertFalse( View::getColorCSS() );
	}

	public function testScssphpIsNotARuntimeDependency() {
		$composer = json_decode( file_get_contents( COMMONSBOOKING_PLUGIN_DIR . 'composer.json' ), true );
		$this->assertArrayNotHasKey( 'scssphp/scssphp', $composer['require'] );
		$this->assertStringNotContainsString(
			'ScssPhp',
			file_get_contents( COMMONSBOOKING_PLUGIN_DIR . 'src/View/View.php' )
		);
	}

	protected function setUp(): void {
		parent::setUp();
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );

		$this->item     = new Item( $this->itemId );
		$this->location = new Location( $this->locationId );
		$timeframeId    = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '+5 days midnight', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+6 days midnight', strtotime( self::CURRENT_DATE ) ),
		);
		// set booking days in advance
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS, self::bookingDaysInAdvance );
		$timeframeId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '+2 days midnight', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+3 days midnight', strtotime( self::CURRENT_DATE ) ),
		);
		// set booking days in advance
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS, self::bookingDaysInAdvance );
		$timeframeId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '+8 days midnight', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+9 days midnight', strtotime( self::CURRENT_DATE ) ),
		);
		// set booking days in advance
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS, self::bookingDaysInAdvance );
		$timeframeId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '+12 days midnight', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+13 days midnight', strtotime( self::CURRENT_DATE ) ),
		);
		// set booking days in advance
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS, self::bookingDaysInAdvance );
		$timeframeId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '+14 days midnight', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+15 days midnight', strtotime( self::CURRENT_DATE ) ),
		);
		// set booking days in advance
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS, self::bookingDaysInAdvance );
		// this timeframe should not be in shortcode data, because it's out of 30 days advanced booking range
		$timeframeId = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '+32 days midnight', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+33 days midnight', strtotime( self::CURRENT_DATE ) ),
		);
		// set booking days in advance
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS, self::bookingDaysInAdvance );
	}

	protected function tearDown(): void {
		parent::tearDown();
		delete_option( 'commonsbooking_options_templates' ); // color tests modify it
		ClockMock::reset();
	}
}
