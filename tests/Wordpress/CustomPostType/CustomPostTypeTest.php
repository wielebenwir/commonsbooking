<?php

namespace CommonsBooking\Tests\Wordpress\CustomPostType;

use CommonsBooking\Model\Item;
use CommonsBooking\Model\Location;
use CommonsBooking\Model\Restriction;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Model\Booking;
use CommonsBooking\Plugin;
use CommonsBooking\Wordpress\CustomPostType\CustomPostType;

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

	public function testSanitizeOptions() {
		//create an array of locations and see if it is sanitized correctly
		$locationOne = get_post($this->createLocation("Location One",'publish'));
		$locationTwo = get_post($this->createLocation("Location Two",'publish'));
		$locationThree = get_post($this->createLocation("Location Three",'publish'));
		$locationsArray = [$locationOne, $locationTwo, $locationThree];
		$locationArray = [
			$locationOne->ID => $locationOne->post_title,
			$locationTwo->ID => $locationTwo->post_title,
			$locationThree->ID => $locationThree->post_title
		];
		$this->assertEquals($locationArray, CustomPostType::sanitizeOptions($locationsArray));

		//now do the same with items
		$itemOne = get_post($this->createItem("Item One",'publish'));
		$itemTwo = get_post($this->createItem("Item Two",'publish'));
		$itemThree = get_post($this->createItem("Item Three",'publish'));
		$itemsArray = [$itemOne, $itemTwo, $itemThree];
		$itemArray = [
			$itemOne->ID => $itemOne->post_title,
			$itemTwo->ID => $itemTwo->post_title,
			$itemThree->ID => $itemThree->post_title
		];
		$this->assertEquals($itemArray, CustomPostType::sanitizeOptions($itemsArray));

		//and now with an associative array of strings (should return the same array)
		$array = [
			'one' => 'One',
			'two' => 'Two',
			'three' => 'Three'
		];
		$this->assertEquals($array, CustomPostType::sanitizeOptions($array));

		//make sure that unsupported post types are not returned
		$unsupported = [new \DateTime()];
		$this->assertEmpty(CustomPostType::sanitizeOptions($unsupported));

		//make sure, that for the admin user all is passed along when requested but not for the other users
		$this->createAdministrator();
		$this->createSubscriber();
		$itemArrayAndAll = [
			CustomPostType::SELECTION_ALL_POSTS => 'All',
			$itemOne->ID => $itemOne->post_title,
			$itemTwo->ID => $itemTwo->post_title,
			$itemThree->ID => $itemThree->post_title
		];
		wp_set_current_user($this->adminUserID);
		$this->assertEquals($itemArrayAndAll, CustomPostType::sanitizeOptions($itemsArray, true));
		wp_set_current_user($this->subscriberId);
		$this->assertEquals($itemArray, CustomPostType::sanitizeOptions($itemsArray, true));
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
