<?php

namespace CommonsBooking\Tests\Wordpress\CustomPostType;

use CommonsBooking\Model\Item;
use CommonsBooking\Model\Location;
use CommonsBooking\Model\Restriction;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Model\Booking;
use CommonsBooking\Wordpress\CustomPostType\CustomPostType;
use PHPUnit\Framework\TestCase;

/**
 * This is the method to test the CustomPostType class.
 * Don't mix it up with CommonsBooking\Tests\Wordpress\CustomPostTypeTest which is the
 * parent class for all the custom post type tests.
 * This, however extends this class because we need it's methods
 */
class CustomPostTypeTest extends \CommonsBooking\Tests\Wordpress\CustomPostTypeTest
{
	protected int $bookingId;
	protected Booking $bookingModel;
	protected int $timeframeId;
	protected Timeframe $timeframeModel;
	protected int $restrictionId;
	protected Restriction $restrictionModel;
	public function testGetModel() {
		$itemModel = new Item($this->itemId);
		$locationModel = new Location($this->locationId);

		//get model by ID
		$this->assertEquals($itemModel, CustomPostType::getModel( $this->itemId ) );
		$this->assertEquals($locationModel, CustomPostType::getModel( $this->locationId ) );
		$this->assertEquals($this->timeframeModel, CustomPostType::getModel( $this->timeframeId ) );
		$this->assertEquals($this->bookingModel, CustomPostType::getModel( $this->bookingId ) );
		$this->assertEquals($this->restrictionModel, CustomPostType::getModel( $this->restrictionId ));

		//get model by post object
		$this->assertEquals($itemModel, CustomPostType::getModel( $itemModel->getPost() ) );
		$this->assertEquals($locationModel, CustomPostType::getModel( $locationModel->getPost() ) );
		$this->assertEquals($this->timeframeModel, CustomPostType::getModel( $this->timeframeModel->getPost() ) );
		$this->assertEquals($this->bookingModel, CustomPostType::getModel( $this->bookingModel->getPost() ) );
		$this->assertEquals($this->restrictionModel, CustomPostType::getModel( $this->restrictionModel->getPost() ));

		//get model by model (this should just return the object)
		$this->assertEquals($itemModel, CustomPostType::getModel( $itemModel ) );
		$this->assertEquals($locationModel, CustomPostType::getModel( $locationModel ) );
		$this->assertEquals($this->timeframeModel, CustomPostType::getModel( $this->timeframeModel ) );
		$this->assertEquals($this->bookingModel, CustomPostType::getModel( $this->bookingModel ) );
		$this->assertEquals($this->restrictionModel, CustomPostType::getModel( $this->restrictionModel ));

		//check that assertions are thrown, when trying to get a model for a post type that is not supported
		$otherPost = wp_insert_post([
			'post_type' => 'post',
			'post_title' => 'Test Post'
		]);

		//post not in our CPT
		try {
			CustomPostType::getModel($otherPost);
			$this->fail('Expected exception not thrown');
		} catch (\Exception $e) {
			$this->assertEquals('No suitable model found.', $e->getMessage());
		}

		//post ID does not exist
		try {
			CustomPostType::getModel(99999999);
			$this->fail('Expected exception not thrown');
		} catch (\Exception $e) {
			$this->assertEquals('No suitable post object.', $e->getMessage());
		}

	}
	protected function setUp(): void {
		parent::setUp();
		$this->timeframeId    = $this->createBookableTimeFrameIncludingCurrentDay();
		$this->timeframeModel = new Timeframe(
			$this->timeframeId
		);
		$this->bookingId             = $this->createConfirmedBookingStartingToday();
		$this->bookingModel   = new Booking(
			$this->bookingId
		);
		$this->restrictionId = $this->createRestriction(
			Restriction::META_HINT,
			$this->locationId,
			$this->itemId,
			strtotime(self::CURRENT_DATE),
			strtotime("+3 weeks", strtotime(self::CURRENT_DATE))
		);
		$this->restrictionModel = new Restriction(
			$this->restrictionId
		);
	}
	protected function tearDown(): void {
		parent::tearDown();
	}
}
