<?php

namespace CommonsBooking\Tests\CB;

use CommonsBooking\CB\CB;
use CommonsBooking\Model\Booking;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;

class CBTest extends CustomPostTypeTest {

	private $userInstanceId;

	private $postInstanceId;

	private $userMetaKey = 'meta-key';

	private $userMetaValue = 'meta-value';

	private $postTitle = 'test post title';

	private $locationMetaKey = 'location-meta-key';

	private $locationMetaValue = 'location-meta-value';

	private $itemMetaKey = 'item-meta-key';

	private $itemMetaValue = 'item-meta-value';

	private $bookingMetaKey = 'booking-meta-key';

	private $bookingMetaValue = 'booking-meta-value';

	protected $bookingId;

	public function testLookUp() {
		// Test if user meta value is found when handing over WP_Post object
		$post = get_post( $this->postInstanceId );
		$this->assertEquals( CB::lookUp( 'user', $this->userMetaKey, $post, [], 'commonsbooking_sanitizeHTML' ), $this->userMetaValue );

		// Test if post title is returned when handing over post key and post object
		$this->assertEquals( CB::lookUp( 'post', 'post_title', $post, [], 'commonsbooking_sanitizeHTML' ), $this->postTitle );

		// Test if null is returned when trying to get not existing property of post
		$this->assertEquals( null, CB::lookUp( 'user', 'post_title', $post, [], 'commonsbooking_sanitizeHTML' ) );

		// Trying to get property without post object
		$this->assertEquals( CB::lookUp( 'user', 'test', null, [], 'commonsbooking_sanitizeHTML' ), null );
		$this->assertEquals( CB::lookUp( 'booking', 'test', null, [], 'commonsbooking_sanitizeHTML' ), null );
		$this->assertEquals( CB::lookUp( 'item', 'test', null, [], 'commonsbooking_sanitizeHTML' ), null );
		$this->assertEquals( CB::lookUp( 'location', 'test', null, [], 'commonsbooking_sanitizeHTML' ), null );
	}

	public function testGet() {
		// Test if item meta info is returned
		$this->assertEquals(
			$this->itemMetaValue,
			CB::get(
				Item::$postType,
				$this->itemMetaKey,
				get_post( $this->itemId )
			)
		);

		// Test if location meta info is returned
		$this->assertEquals(
			$this->locationMetaValue,
			CB::get(
				Location::$postType,
				$this->locationMetaKey,
				get_post( $this->locationId )
			)
		);

		// Test if booking meta info is returned
		$this->assertEquals(
			$this->bookingMetaValue,
			CB::get(
				\CommonsBooking\Wordpress\CustomPostType\Booking::$postType,
				$this->bookingMetaKey,
				get_post( $this->bookingId )
			)
		);

		// Test if property based on user id is returned
		$this->assertEquals(
			$this->userMetaValue,
			CB::get(
				'user',
				$this->userMetaKey,
				get_userdata( $this->userInstanceId )
			)
		);

		// Test if property based on post author is returned
		$this->assertEquals(
			$this->userMetaValue,
			CB::get(
				'user',
				$this->userMetaKey,
				get_post( $this->postInstanceId )
			)
		);

		// Test if property based on post author is returned, without handover of post id
		global $post;
		$post = get_post( $this->postInstanceId );
		$this->assertEquals(
			$this->userMetaValue,
			CB::get(
				'user',
				$this->userMetaKey
			)
		);

		// Try to get property by model function
		$booking = new Booking( $this->bookingId );
		$this->assertEquals(
			$booking->formattedBookingDate(),
			CB::get(
				\CommonsBooking\Wordpress\CustomPostType\Booking::$postType,
				'formattedBookingDate',
				get_post( $this->bookingId )
			)
		);
	}

	protected function setUp(): void {
		parent::setUp();

		$this->userInstanceId = wp_create_user(
			'cb-test-user',
			'cb-test-user-password',
			'cb-test-user@commonbsbooking.org'
		);
		add_user_meta( $this->userInstanceId, $this->userMetaKey, $this->userMetaValue );

		$this->postInstanceId = wp_insert_post(
			[
				'post_title'    => $this->postTitle,
				'post_content ' => 'test post content',
				'post_excerpt'  => 'test post excerpt',
				'post_author'   => $this->userInstanceId,
			],
			true
		);

		add_post_meta( $this->locationId, $this->locationMetaKey, $this->locationMetaValue );
		add_post_meta( $this->itemId, $this->itemMetaKey, $this->itemMetaValue );

		$this->bookingId = $this->createConfirmedBookingEndingToday();
		add_post_meta( $this->bookingId, $this->bookingMetaKey, $this->bookingMetaValue );
	}

	protected function tearDown(): void {
		parent::tearDown();

		wp_delete_user( $this->userInstanceId );
		wp_delete_post( $this->postInstanceId );
	}
}
