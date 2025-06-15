<?php

namespace CommonsBooking\Tests\Wordpress\CustomPostType;

use CommonsBooking\Model\Item;
use CommonsBooking\Model\Location;
use CommonsBooking\Model\Map;
use CommonsBooking\Model\Restriction;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Model\Booking;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Wordpress\CustomPostType\CustomPostType;

/**
 * This is the method to test the CustomPostType class.
 * Don't mix it up with CommonsBooking\Tests\Wordpress\CustomPostTypeTest which is the
 * parent class for all the custom post type tests.
 * This, however extends this class because we need it's methods
 */
class CustomPostTypeTest extends \CommonsBooking\Tests\Wordpress\CustomPostTypeTest {

	protected int $bookingId;
	protected Booking $bookingModel;
	protected int $timeframeId;
	protected Timeframe $timeframeModel;
	protected int $restrictionId;
	protected Restriction $restrictionModel;
	protected int $map;
	protected Map $mapModel;
	public function testGetModel() {
		$itemModel     = new Item( $this->itemId );
		$locationModel = new Location( $this->locationId );

		// get model by ID
		$this->assertEquals( $itemModel, CustomPostType::getModel( $this->itemId ) );
		$this->assertEquals( $locationModel, CustomPostType::getModel( $this->locationId ) );
		$this->assertEquals( $this->timeframeModel, CustomPostType::getModel( $this->timeframeId ) );
		$this->assertEquals( $this->bookingModel, CustomPostType::getModel( $this->bookingId ) );
		$this->assertEquals( $this->restrictionModel, CustomPostType::getModel( $this->restrictionId ) );
		$this->assertEquals( $this->mapModel, CustomPostType::getModel( $this->map ) );

		// get model by post object
		$this->assertEquals( $itemModel, CustomPostType::getModel( $itemModel->getPost() ) );
		$this->assertEquals( $locationModel, CustomPostType::getModel( $locationModel->getPost() ) );
		$this->assertEquals( $this->timeframeModel, CustomPostType::getModel( $this->timeframeModel->getPost() ) );
		$this->assertEquals( $this->bookingModel, CustomPostType::getModel( $this->bookingModel->getPost() ) );
		$this->assertEquals( $this->restrictionModel, CustomPostType::getModel( $this->restrictionModel->getPost() ) );
		$this->assertEquals( $this->mapModel, CustomPostType::getModel( $this->mapModel->getPost() ) );

		// get model by model (this should just return the object)
		$this->assertEquals( $itemModel, CustomPostType::getModel( $itemModel ) );
		$this->assertEquals( $locationModel, CustomPostType::getModel( $locationModel ) );
		$this->assertEquals( $this->timeframeModel, CustomPostType::getModel( $this->timeframeModel ) );
		$this->assertEquals( $this->bookingModel, CustomPostType::getModel( $this->bookingModel ) );
		$this->assertEquals( $this->restrictionModel, CustomPostType::getModel( $this->restrictionModel ) );
		$this->assertEquals( $this->mapModel, CustomPostType::getModel( $this->mapModel ) );

		// check that assertions are thrown, when trying to get a model for a post type that is not supported
		$otherPost = wp_insert_post(
			[
				'post_type' => 'post',
				'post_title' => 'Test Post',
			]
		);

		// post not in our CPT
		try {
			CustomPostType::getModel( $otherPost );
			$this->fail( 'Expected exception not thrown' );
		} catch ( \Exception $e ) {
			$this->assertStringContainsString( 'No suitable model found', $e->getMessage() );
		}

		// post ID does not exist
		try {
			CustomPostType::getModel( 99999999 );
			$this->fail( 'Expected exception not thrown' );
		} catch ( \Exception $e ) {
			$this->assertEquals( 'No suitable post object.', $e->getMessage() );
		}
	}
	public function testSanitizeOptions() {
		// test the sanitization for an array of Items
		$firstItem  = $this->createItem( 'First Item', 'publish' );
		$secondItem = $this->createItem( 'Second Item', 'publish' );
		$thirdItem  = $this->createItem( 'Third Item', 'publish' );
		$itemArray  = [ $firstItem, $secondItem, $thirdItem ];
		$itemArray  = array_map( 'get_post', $itemArray );
		$expected   = array(
			$firstItem => 'First Item',
			$secondItem => 'Second Item',
			$thirdItem => 'Third Item',
		);
		$this->assertEquals( $expected, CustomPostType::sanitizeOptions( $itemArray ) );
		// now add in one draft item
		$draftItem              = $this->createItem( 'Draft Item', 'draft' );
		$itemArray[]            = get_post( $draftItem );
		$expected[ $draftItem ] = 'Draft Item [Draft]';
		$this->assertEquals( $expected, CustomPostType::sanitizeOptions( $itemArray ) );

		// test the sanitization for terms
		$term     = wp_insert_term( 'test-item-term', \CommonsBooking\Wordpress\CustomPostType\Item::getTaxonomyName() );
		$term     = get_term( $term['term_id'] );
		$expected = array(
			$term->term_id => 'test-item-term (' . $term->slug . ')',
		);
		$this->assertEquals( $expected, CustomPostType::sanitizeOptions( [ $term ] ) );

		// other things should just be returned as a numbered array
		$input    = [ 1, 2, 3, 4, 5 ];
		$expected = array(
			0 => 1,
			1 => 2,
			2 => 3,
			3 => 4,
			4 => 5,
		);
		$this->assertEquals( $expected, CustomPostType::sanitizeOptions( $input ) );
	}

	public function testGetCMB2FieldsArrayFromCustomMetadata() {
		$metaDataRaw    = 'item;waterproof;Waterproof material;checkbox;"This item is waterproof and can be used in heavy rain"';
		$expectedOutput = [
			[
				'id' => 'waterproof',
				'name' => 'Waterproof material',
				'type' => 'checkbox',
				'desc' => '"This item is waterproof and can be used in heavy rain"',
			],
		];
		Settings::updateOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options', 'metadata', $metaDataRaw );
		$this->assertEquals( $expectedOutput, CustomPostType::getCMB2FieldsArrayFromCustomMetadata( 'item' ) );

		// test with multiple lines
		$metaDataRaw    = 'item;waterproof;Waterproof material;checkbox;"This item is waterproof and can be used in heavy rain"' . "\r\n" .
						'item;color;Color;text;"The color of this item"';
		$expectedOutput = [
			[
				'id' => 'waterproof',
				'name' => 'Waterproof material',
				'type' => 'checkbox',
				'desc' => '"This item is waterproof and can be used in heavy rain"',
			],
			[
				'id' => 'color',
				'name' => 'Color',
				'type' => 'text',
				'desc' => '"The color of this item"',
			],
		];
		Settings::updateOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options', 'metadata', $metaDataRaw );
		$this->assertEquals( $expectedOutput, CustomPostType::getCMB2FieldsArrayFromCustomMetadata( 'item' ) );

		// test with multiple lines and multiple types
		$metaDataRaw            = 'item;waterproof;Waterproof material;checkbox;"This item is waterproof and can be used in heavy rain"' . "\r\n" .
						'item;color;Color;text;"The color of this item"' . "\r\n" .
						'location;business;Business;checkbox;"Check, when the location is a business"';
		$expectedOutputItem     =
			[
				[
					'id' => 'waterproof',
					'name' => 'Waterproof material',
					'type' => 'checkbox',
					'desc' => '"This item is waterproof and can be used in heavy rain"',
				],
				[
					'id' => 'color',
					'name' => 'Color',
					'type' => 'text',
					'desc' => '"The color of this item"',
				],
			];
		$expectedOutputLocation =
			[
				[
					'id' => 'business',
					'name' => 'Business',
					'type' => 'checkbox',
					'desc' => '"Check, when the location is a business"',
				],
			];
		Settings::updateOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options', 'metadata', $metaDataRaw );
		$this->assertEquals( $expectedOutputItem, CustomPostType::getCMB2FieldsArrayFromCustomMetadata( 'item' ) );
		$this->assertEquals( $expectedOutputLocation, CustomPostType::getCMB2FieldsArrayFromCustomMetadata( 'location' ) );

		// add custom metadata through filter hook
		$metaDataRaw    = 'item;waterproof;Waterproof material;checkbox;"This item is waterproof and can be used in heavy rain"';
		$expectedOutput = [
			[
				'id' => 'waterproof',
				'name' => 'Waterproof material',
				'type' => 'checkbox',
				'desc' => '"This item is waterproof and can be used in heavy rain"',
			],
			[
				'id' => 'custom',
				'name' => 'Custom',
				'type' => 'text',
				'desc' => '"Custom metadata"',
				'show_on_cb' => 'custom_callback',
			],
		];
		add_filter(
			'commonsbooking_custom_metadata',
			function ( $metaData ) {
				$metaData['item'][] = [
					'id' => 'custom',
					'name' => 'Custom',
					'type' => 'text',
					'desc' => '"Custom metadata"',
					'show_on_cb' => 'custom_callback',
				];
				return $metaData;
			}
		);
		Settings::updateOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options', 'metadata', $metaDataRaw );
		$this->assertEquals( $expectedOutput, CustomPostType::getCMB2FieldsArrayFromCustomMetadata( 'item' ) );
		remove_all_filters( 'commonsbooking_custom_metadata' );
	}

	protected function setUp(): void {
		parent::setUp();
		$this->timeframeId      = $this->createBookableTimeFrameIncludingCurrentDay();
		$this->timeframeModel   = new Timeframe(
			$this->timeframeId
		);
		$this->bookingId        = $this->createConfirmedBookingStartingToday();
		$this->bookingModel     = new Booking(
			$this->bookingId
		);
		$this->restrictionId    = $this->createRestriction(
			Restriction::META_HINT,
			$this->locationId,
			$this->itemId,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+3 weeks', strtotime( self::CURRENT_DATE ) )
		);
		$this->restrictionModel = new Restriction(
			$this->restrictionId
		);
		$this->map              = $this->createMap();
		$this->mapModel         = new Map( $this->map );
	}
	protected function tearDown(): void {
		parent::tearDown();
	}
}
