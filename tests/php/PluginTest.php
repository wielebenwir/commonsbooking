<?php

namespace CommonsBooking\Tests;

use CommonsBooking\Model\CustomPost;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Plugin;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Wordpress\CustomPostType\CustomPostType;
use SlopeIt\ClockMock\ClockMock;

class PluginTest extends CustomPostTypeTest
{

	private $postIDs = [];
    public function testGetCustomPostTypes()
    {
		$plugin = new Plugin();
		$this->assertIsArray($plugin->getCustomPostTypes());
		//make sure, that we also have a model for each custom post type
        foreach ($plugin->getCustomPostTypes() as $customPostType){
	        //first, create a post of this type
	        $post = wp_insert_post([
		        'post_type' => $customPostType::getPostType(),
		        'post_title' => 'Test ' . $customPostType::getPostType(),
		        'post_status' => 'publish'
	        ]);
	        $this->assertIsInt($post);
	        $this->postIDs[] = $post;
			//then, try to get a model from the post. Every declared CPT should have a model
			$this->assertInstanceOf(CustomPost::class, CustomPostType::getModel($post));
		}
    }

	protected function setUp(): void {
		parent::setUp();
	}

	protected function tearDown(): void {
		foreach ($this->postIDs as $postID){
			wp_delete_post($postID, true);
		}
		parent::tearDown();
	}
}
