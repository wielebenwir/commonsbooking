<?php

namespace CommonsBooking\Tests;

use CommonsBooking\Model\CustomPost;
use CommonsBooking\Plugin;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Wordpress\CustomPostType\CustomPostType;

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

	public function testTimeframeNoRepToDaily() {
		$timeframeNoRepWEnd = new \CommonsBooking\Model\Timeframe(
			$this->createTimeframe(
				$this->locationId,
				$this->itemId,
				strtotime( '+1 days', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+2 days', strtotime( self::CURRENT_DATE ) ),
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'on',
				'norep',
			)
		);
		$timeframeNoRepWithoutEnd = new \CommonsBooking\Model\Timeframe(
			$this->createTimeframe(
				$this->locationId,
				$this->itemId,
				strtotime( '+1 days', strtotime( self::CURRENT_DATE ) ),
				null,
				\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
				'on',
			'norep',
			)
		);
		$this->assertFalse($timeframeNoRepWithoutEnd->getEndDate());
		Plugin::timeframeNoRepToDaily();
		$this->assertEquals('d',$timeframeNoRepWEnd->getRepetition());
		$this->assertEquals('d',$timeframeNoRepWithoutEnd->getRepetition());
		$this->assertEquals(strtotime( '+1 days', strtotime( self::CURRENT_DATE ) ),$timeframeNoRepWithoutEnd->getEndDate());
	}

	protected function setUp(): void {
		parent::setUp();
	}
}
