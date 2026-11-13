<?php

namespace CommonsBooking\Tests;

use CommonsBooking\Model\Booking;
use CommonsBooking\Model\Item;
use CommonsBooking\Model\Location;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class TemplateParserTest extends CustomPostTypeTest {

	protected function setUp(): void {
		parent::setUp();

		$this->createSubscriber();
		update_user_meta( $this->subscriberId, 'first_name', 'Test' );
		update_post_meta( $this->itemId, 'lock_code', '1234' );
	}

	/**
	 * @dataProvider provideTemplateTypes
	 */
	public function testGetCBTypeReturnsPostType( string $type, string $expected ): void {
		$this->assertSame( $expected, commonsbooking_getCBType( $type ) );
	}

	public function provideTemplateTypes(): array {
		return [
			'location'            => [ 'location', 'cb_location' ],
			'booking'             => [ 'booking', 'cb_booking' ],
			'item'                => [ 'item', 'cb_item' ],
			'unknown passthrough' => [ 'user', 'user' ],
		];
	}

	/**
	 * @dataProvider provideTemplates
	 */
	public function testParseTemplate( string $template, string $expected ): void {
		$objects = [
			'item'     => new Item( $this->itemId ),
			'location' => new Location( $this->locationId ),
			'user'     => get_userdata( $this->subscriberId ),
		];

		$this->assertSame( $expected, commonsbooking_parse_template( $template, $objects ) );
	}

	public function testParseTemplateRendersBookingLinks(): void {
		$booking = new Booking( $this->createConfirmedBookingStartingToday() );
		$objects = [ 'booking' => $booking ];

		// bookingLink is not a url but a complete anchor element
		$this->assertSame(
			$booking->bookingLink(),
			commonsbooking_parse_template( '{{booking:bookingLink}}', $objects )
		);

		// bookingLinkUrl is the url, so it can be used in an href attribute ('#' works where ':' breaks the tag)
		$this->assertSame(
			'<a href="' . $booking->bookingLinkUrl() . '">link</a>',
			commonsbooking_parse_template( '<a href="{{booking#bookingLinkUrl}}">link</a>', $objects )
		);
	}

	public function provideTemplates(): array {
		return [
			'empty'                    => [ '', '' ],
			'plain text'               => [ 'Hello World', 'Hello World' ],
			'item and location'        => [
				'The item is {{item:post_title}} at {{location:post_title}}.',
				'The item is TestItem at Testlocation.',
			],
			'user'                     => [ '{{user:first_name}}', 'Test' ],
			'description'              => [
				'{{[before: ]item:post_title[ after]}}',
				'before: TestItem after',
			],
			// custom meta field with simple html in the descriptive text
			'html description'         => [
				'{{[<strong>Code:</strong> ]item:lock_code}}',
				'<strong>Code:</strong> 1234',
			],
			'empty field description'  => [
				'The item has {{[the code ]item:post_excerpt}}',
				'The item has ',
			],
			'unresolvable tag'         => [ '{{item:does_not_exist}}', '' ],
			'unresolvable tag in text' => [
				'{{[before: ]item:does_not_exist[ after]}}differenttext',
				'differenttext',
			],
		];
	}
}
