<?php

namespace CommonsBooking\Tests\CB;

use CommonsBooking\CB\CB;
use CommonsBooking\Model\Booking;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

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

	private $bookingId;

	protected function setUp() {
		parent::setUp();

		$this->userInstanceId = wp_create_user(
			'cb-test-user',
			'cb-test-user-password',
			'cb-test-user@commonbsbooking.org'
		);
		add_user_meta($this->userInstanceId, $this->userMetaKey, $this->userMetaValue);

		$this->postInstanceId = wp_insert_post(
			[
				'post_title'   => $this->postTitle,
				'post_content ' => 'test post content',
				'post_excerpt'  => 'test post excerpt',
				'post_author'   => $this->userInstanceId
			],
			true
		);

		add_post_meta($this->locationId, $this->locationMetaKey, $this->locationMetaValue);
		add_post_meta($this->itemId, $this->itemMetaKey, $this->itemMetaValue);

		$this->bookingId = $this->createConfirmedBookingEndingToday();
		add_post_meta($this->bookingId, $this->bookingMetaKey, $this->bookingMetaValue);
	}

	protected function tearDown() {
		parent::tearDown();

		wp_delete_user($this->userInstanceId);
		wp_delete_post($this->postInstanceId);
	}

	public function testLookUp() {
		// Test if user meta value is found when handing over WP_Post object
		$post = get_post($this->postInstanceId);
		$this->assertEquals(CB::lookUp('user',$this->userMetaKey, $post, []), $this->userMetaValue);

		// Test if post title is returned when handing over post key and post object
		$this->assertEquals(CB::lookUp('post','post_title', $post, []), $this->postTitle);

		// Test if null is returned when trying to get not existing property of post
		$this->assertEquals(null, CB::lookUp('user','post_title', $post, []));

		// Test if item meta info is returned
		$this->assertEquals($this->itemMetaValue, CB::get(\CommonsBooking\Wordpress\CustomPostType\Item::$postType, $this->itemMetaKey, $this->itemId, []));

		// Test if location meta info is returned
		$this->assertEquals($this->locationMetaValue, CB::get(\CommonsBooking\Wordpress\CustomPostType\Location::$postType,$this->locationMetaKey, $this->locationId, []));

		$this->assertEquals($this->bookingMetaValue, CB::get(\CommonsBooking\Wordpress\CustomPostType\Booking::$postType,$this->bookingMetaKey, $this->bookingId, []));

		// Try to get property by model function
		$booking = new Booking($this->bookingId);
		$this->assertEquals($booking->formattedBookingDate(), CB::get(\CommonsBooking\Wordpress\CustomPostType\Booking::$postType,'formattedBookingDate', $this->bookingId, []));

		// Trying to get property without post object
		$this->assertEquals(CB::lookUp('user', 'test', null, []), null);
		$this->assertEquals(CB::lookUp('booking', 'test', null, []), null);
		$this->assertEquals(CB::lookUp('item', 'test', null, []), null);
		$this->assertEquals(CB::lookUp('location', 'test', null, []), null);
	}
}
