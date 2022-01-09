<?php

namespace CommonsBooking\Tests\CB;

use CommonsBooking\CB\CB;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class CBTest extends CustomPostTypeTest {

	private $userInstanceId;

	private $postInstanceId;

	private $userMetaKey = 'meta-key';

	private $userMetaValue = 'meta-value';

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
				'post_title '   => 'test post title',
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
		wp_delete_user($this->postInstanceId);
	}

	public function testLookUp() {
		// Test if user meta value is found when handing over WP_User object
		$user = get_userdata($this->userInstanceId);
		$this->assertTrue(CB::lookUp('user',$this->userMetaKey, $user, []) == $this->userMetaValue);

		// Test if user meta value is found when handing over WP_Post object
		$post = get_post($this->postInstanceId);
		$this->assertTrue(CB::lookUp('user',$this->userMetaKey, $post, []) == $this->userMetaValue);
	}
}
