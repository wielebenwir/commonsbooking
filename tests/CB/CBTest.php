<?php

namespace CommonsBooking\Tests\CB;

use CommonsBooking\CB\CB;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class CBTest extends CustomPostTypeTest {

	private $userInstanceId;

	private $postInstanceId;

	private $userMetaKey = 'meta-key';

	private $userMetaValue = 'meta-value';

	private $postTitle = 'test post title';

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
	}

	protected function tearDown() {
		parent::tearDown();

		wp_delete_user($this->userInstanceId);
		wp_delete_post($this->postInstanceId);
	}

	public function testLookUp() {
		// Test if user meta value is found when handing over WP_User object
		$user = get_userdata($this->userInstanceId);
		$this->assertTrue(CB::lookUp('user',$this->userMetaKey, $user, []) == $this->userMetaValue);

		// Test if user meta value is found when handing over WP_Post object
		$post = get_post($this->postInstanceId);
		$this->assertTrue(CB::lookUp('user',$this->userMetaKey, $post, []) == $this->userMetaValue);

		// Test if post title is returned when handing ofer post key and post object
		$this->assertTrue(CB::lookUp('post','post_title', $post, []) == $this->postTitle);

		// Test if null is returned when trying to get not existing property of post
		$this->assertTrue(CB::lookUp('user','post_title', $post, []) == null);

		// Trying to get property without post object
		$this->assertTrue(CB::lookUp('user', 'test', null, []) == null);
		$this->assertTrue(CB::lookUp('booking', 'test', null, []) == null);
		$this->assertTrue(CB::lookUp('item', 'test', null, []) == null);
		$this->assertTrue(CB::lookUp('location', 'test', null, []) == null);
	}
}
