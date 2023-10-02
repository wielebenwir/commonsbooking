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
			//then, try to get a model from the post. Every declared CPT should have a model
			$this->assertInstanceOf(CustomPost::class, CustomPostType::getModel($post));
		}
    }

	public function testRemoveBreakingPostmeta() {
		ClockMock::freeze(new \DateTime(self::CURRENT_DATE));
		//Create timeframe that should still be valid after the cleanup
		$validTF = new Timeframe($this->createBookableTimeFrameStartingInAWeek());
		$this->assertTrue($validTF->isValid());

		//create holiday with ADVANCE_BOOKING_DAYS setting (the function does this by default)
		$holiday = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime('+1 week', strtotime(self::CURRENT_DATE)),
			strtotime('+2 weeks', strtotime(self::CURRENT_DATE)),
		);
		Plugin::removeBreakingPostmeta();
		$this->assertEmpty(get_post_meta($holiday, 'advance_booking_days', true));

	}

	protected function setUp(): void {
		parent::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}
}
