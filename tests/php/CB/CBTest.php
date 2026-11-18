<?php

namespace CommonsBooking\Tests\CB;

use CommonsBooking\CB\CB;
use CommonsBooking\Model\Booking as BookingModel;
use CommonsBooking\Model\Item as ItemModel;
use CommonsBooking\Model\Location as LocationModel;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Wordpress\CustomPostType\Booking;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use Exception;

class CBTest extends CustomPostTypeTest {

	private const SUBSCRIBER_LOGIN    = 'normaluser';
	private const USER_META_KEY       = 'meta-key';
	private const USER_META_VALUE     = 'meta-value';
	private const POST_TITLE          = 'test post title';
	private const POST_EXCERPT        = 'test post excerpt';
	private const LOCATION_META_KEY   = 'location-meta-key';
	private const LOCATION_META_VALUE = 'location-meta-value';
	private const ITEM_META_KEY       = 'item-meta-key';
	private const ITEM_META_VALUE     = 'item-meta-value';
	private const ITEM_HTML_META_KEY  = 'item-html-meta';
	private const ITEM_HTML_META      = '<strong>bold</strong>';
	private const BOOKING_META_KEY    = 'booking-meta-key';
	private const BOOKING_META_VALUE  = 'booking-meta-value';

	private int $postInstanceId;
	private int $bookingId;

	protected function setUp(): void {
		parent::setUp();

		$this->createSubscriber();
		update_user_meta( $this->subscriberId, self::USER_META_KEY, self::USER_META_VALUE );

		$this->postInstanceId = wp_insert_post(
			[
				'post_title'   => self::POST_TITLE,
				'post_content' => 'test post content',
				'post_excerpt' => self::POST_EXCERPT,
				'post_author'  => $this->subscriberId,
			],
			true
		);

		add_post_meta( $this->locationId, self::LOCATION_META_KEY, self::LOCATION_META_VALUE );
		add_post_meta( $this->itemId, self::ITEM_META_KEY, self::ITEM_META_VALUE );
		add_post_meta( $this->itemId, self::ITEM_HTML_META_KEY, self::ITEM_HTML_META );

		$this->bookingId = $this->createConfirmedBookingEndingToday();
		add_post_meta( $this->bookingId, self::BOOKING_META_KEY, self::BOOKING_META_VALUE );
	}

	protected function tearDown(): void {
		parent::tearDown();

		wp_delete_post( $this->postInstanceId, true );
	}

	/**
	 * Resolves the fixture names used in the data providers.
	 */
	private function fixture( ?string $name ) {
		return match ( $name ) {
			null        => null,
			'post'      => get_post( $this->postInstanceId ),
			'user'      => get_userdata( $this->subscriberId ),
			'item'      => new ItemModel( $this->itemId ),
			'item_post' => get_post( $this->itemId ),
			'location'  => new LocationModel( $this->locationId ),
			'booking'   => new BookingModel( $this->bookingId ),
		};
	}

	/**
	 * @dataProvider provideLookUp
	 */
	public function testLookUp( string $key, string $property, ?string $fixture, string $sanitizeFunction, ?string $expected ): void {
		$this->assertSame(
			$expected,
			CB::lookUp( $key, $property, $this->fixture( $fixture ), [], $sanitizeFunction )
		);
	}

	public function provideLookUp(): array {
		return [
			'user meta via post author' => [ 'user', self::USER_META_KEY, 'post', 'commonsbooking_sanitizeHTML', self::USER_META_VALUE ],
			'user property'             => [ 'user', 'user_login', 'user', 'commonsbooking_sanitizeHTML', self::SUBSCRIBER_LOGIN ],
			'user meta via user'        => [ 'user', self::USER_META_KEY, 'user', 'commonsbooking_sanitizeHTML', self::USER_META_VALUE ],
			'unknown user property'     => [ 'user', 'does-not-exist', 'user', 'commonsbooking_sanitizeHTML', null ],
			'post property'             => [ 'post', 'post_title', 'post', 'commonsbooking_sanitizeHTML', self::POST_TITLE ],
			'post excerpt'              => [ 'post', 'post_excerpt', 'post', 'commonsbooking_sanitizeHTML', self::POST_EXCERPT ],
			'unknown post property'     => [ 'post', 'does-not-exist', 'post', 'commonsbooking_sanitizeHTML', null ],
			'item meta'                 => [ 'item', self::ITEM_META_KEY, 'item', 'commonsbooking_sanitizeHTML', self::ITEM_META_VALUE ],
			'location meta'             => [ 'location', self::LOCATION_META_KEY, 'location', 'commonsbooking_sanitizeHTML', self::LOCATION_META_VALUE ],
			'booking meta'              => [ 'booking', self::BOOKING_META_KEY, 'booking', 'commonsbooking_sanitizeHTML', self::BOOKING_META_VALUE ],
			'html meta kept'            => [ 'item', self::ITEM_HTML_META_KEY, 'item', 'commonsbooking_sanitizeHTML', self::ITEM_HTML_META ],
			'html meta stripped'        => [ 'item', self::ITEM_HTML_META_KEY, 'item', 'sanitize_text_field', 'bold' ],
			'without object (user)'     => [ 'user', 'test', null, 'commonsbooking_sanitizeHTML', null ],
			'without object (booking)'  => [ 'booking', 'test', null, 'commonsbooking_sanitizeHTML', null ],
			'without object (item)'     => [ 'item', 'test', null, 'commonsbooking_sanitizeHTML', null ],
			'without object (location)' => [ 'location', 'test', null, 'commonsbooking_sanitizeHTML', null ],
		];
	}

	/**
	 * @dataProvider provideGet
	 */
	public function testGet( string $key, string $property, string $fixture, string $sanitizeFunction, ?string $expected ): void {
		$this->assertSame(
			$expected,
			CB::get( $key, $property, $this->fixture( $fixture ), null, $sanitizeFunction )
		);
	}

	public function provideGet(): array {
		return [
			'item meta'                 => [ Item::$postType, self::ITEM_META_KEY, 'item', 'commonsbooking_sanitizeHTML', self::ITEM_META_VALUE ],
			'item meta via post'        => [ Item::$postType, self::ITEM_META_KEY, 'item_post', 'commonsbooking_sanitizeHTML', self::ITEM_META_VALUE ],
			'location meta'             => [ Location::$postType, self::LOCATION_META_KEY, 'location', 'commonsbooking_sanitizeHTML', self::LOCATION_META_VALUE ],
			'booking meta'              => [ Booking::$postType, self::BOOKING_META_KEY, 'booking', 'commonsbooking_sanitizeHTML', self::BOOKING_META_VALUE ],
			'user meta via user'        => [ 'user', self::USER_META_KEY, 'user', 'commonsbooking_sanitizeHTML', self::USER_META_VALUE ],
			'user meta via post author' => [ 'user', self::USER_META_KEY, 'post', 'commonsbooking_sanitizeHTML', self::USER_META_VALUE ],
			'user login'                => [ 'user', 'user_login', 'user', 'commonsbooking_sanitizeHTML', self::SUBSCRIBER_LOGIN ],
			'item title'                => [ Item::$postType, 'post_title', 'item', 'commonsbooking_sanitizeHTML', 'TestItem' ],
			'html meta kept'            => [ Item::$postType, self::ITEM_HTML_META_KEY, 'item', 'commonsbooking_sanitizeHTML', self::ITEM_HTML_META ],
			'html meta stripped'        => [ Item::$postType, self::ITEM_HTML_META_KEY, 'item', 'sanitize_text_field', 'bold' ],
			'unknown property'          => [ Booking::$postType, 'does-not-exist', 'booking', 'commonsbooking_sanitizeHTML', '' ],
		];
	}

	public function testLookUpCallsModelMethod(): void {
		$booking = new BookingModel( $this->bookingId );

		$this->assertSame(
			$booking->formattedBookingDate(),
			CB::lookUp( Booking::$postType, 'formattedBookingDate', $booking, [], 'commonsbooking_sanitizeHTML' )
		);
	}

	public function testGetCallsModelMethodWithArgs(): void {
		$booking = new BookingModel( $this->bookingId );

		$this->assertSame(
			$booking->bookingLink( 'Custom link text' ),
			CB::get( Booking::$postType, 'bookingLink', $booking, 'Custom link text' )
		);
	}

	public function testGetWithoutObjectUsesGlobalPost(): void {
		global $post;
		$previousPost = $post;
		$post         = get_post( $this->postInstanceId );

		try {
			$this->assertSame( self::USER_META_VALUE, CB::get( 'user', self::USER_META_KEY ) );
		} finally {
			$post = $previousPost;
		}
	}

	public function testGetUsesTimeframeSubPostFromRequest(): void {
		$timeframeId          = $this->createBookableTimeFrameIncludingCurrentDay();
		$_GET['cb_timeframe'] = get_post( $timeframeId )->post_name;

		global $post;
		$previousPost = $post;
		$post         = null;

		try {
			// 'item' instead of 'cb_item', because the timeframe stores its relation as 'item-id'
			$this->assertSame( 'TestItem', CB::get( 'item', 'post_title' ) );
		} finally {
			unset( $_GET['cb_timeframe'] );
			$post = $previousPost;
		}
	}

	public function testGetReturnsEmptyStringWhenNoPostIsFound(): void {
		global $post;
		$previousPost = $post;
		$post         = null;

		try {
			$this->assertSame( '', CB::get( Item::$postType, 'post_title' ) );
		} finally {
			$post = $previousPost;
		}
	}

	public function testGetAppliesTagFilter(): void {
		$filteredArgs = null;
		$hook         = 'commonsbooking_tag_' . Item::$postType . '_' . self::ITEM_META_KEY;

		$filter = static function ( $result, $wpObject, $args ) use ( &$filteredArgs ) {
			$filteredArgs = $args;

			return 'filtered';
		};

		add_filter( $hook, $filter, 10, 3 );

		try {
			$this->assertSame( 'filtered', CB::get( Item::$postType, self::ITEM_META_KEY, new ItemModel( $this->itemId ), 'some-args' ) );
			$this->assertSame( 'some-args', $filteredArgs );
		} finally {
			remove_filter( $hook, $filter, 10 );
		}
	}

	public function testGetThrowsForInvalidObjectType(): void {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'invalid object type.' );

		// an int post id is not an object, get() only accepts WP_Post, WP_User or CustomPost
		CB::get( Item::$postType, 'post_title', $this->itemId );
	}

	public function testLookUpThrowsForInvalidUserObject(): void {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'invalid $post type.' );

		CB::lookUp( 'user', 'user_login', 'not-an-object', [], 'commonsbooking_sanitizeHTML' );
	}

	public function testLookUpUserPropertyOnNonUserObjectThrows(): void {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'invalid $post type.' );

		CB::lookUp( 'user', 'user_login', new ItemModel( $this->itemId ), [], 'commonsbooking_sanitizeHTML' );
	}
}
