<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Model\Timeframe;
use CommonsBooking\Service\Upgrade;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Wordpress\CustomPostType\Map;
use SlopeIt\ClockMock\ClockMock;

class UpgradeTest extends CustomPostTypeTest {

	private static bool $functionAHasRun = false;
	private static bool $functionBHasRun = false;
	private $testTasks;
	private $ajaxTasks;

	public function testFixBrokenICalTitle() {
		Settings::updateOption(
			'commonsbooking_options_templates',
			'emailtemplates_mail-booking_ics_event-title',
			'Booking for {{item:post_name}}'
		);
		Settings::updateOption(
			COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options',
			'event_title',
			'Booking for {{item:post_name}}'
		);
		Upgrade::fixBrokenICalTitle();
		$this->assertEquals( 'Booking for {{item:post_title}}', Settings::getOption( 'commonsbooking_options_templates', 'emailtemplates_mail-booking_ics_event-title' ) );
		$this->assertEquals( 'Booking for {{item:post_title}}', Settings::getOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options', 'event_title' ) );
	}

	public function testIsMajorUpdate() {
		$majorUpdate = new Upgrade( '2.5.0', '2.6.0' );
		$this->assertTrue( $majorUpdate->isMajorUpdate() );
		$minorUpdate = new Upgrade( '2.5.0', '2.5.1' );
		$this->assertFalse( $minorUpdate->isMajorUpdate() );
		$majorestUpdate = new Upgrade( '2.5.0', '3.0.0' );
		$this->assertTrue( $majorestUpdate->isMajorUpdate() );
		$downgrade = new Upgrade( '2.6.0', '2.5.0' );
		$this->assertFalse( $downgrade->isMajorUpdate() );
	}

	/**
	 * This will test if the upgrade tasks are run correctly.
	 * The test function should only run, when upgrading on or over version 2.5.2.
	 * It should for example not run when upgrading from 2.5.2 to 2.5.3.
	 *
	 * @dataProvider provideUpgradeConditions
	 */
	public function testRunUpgradeTasks( $previousVersion, $currentVersion, $shouldRunFunction ) {
		$upgrade = new Upgrade( $previousVersion, $currentVersion );
		$upgrade->runUpgradeTasks();
		$this->assertEquals( $shouldRunFunction, self::$functionAHasRun );
		$this->assertEquals( $shouldRunFunction, self::$functionBHasRun );
	}

	/**
	 * The set_up defines a fake upgrade task that should only run when upgrading on or over version 2.5.2.
	 * The data provider will provide different upgrade conditions and the test will check if the function has run or not.
	 * true means, that the function is expected to run under these conditions, false means it is not expected to run.
	 *
	 * @return array[]
	 */
	public function provideUpgradeConditions() {
		return array(
			'Upgrade directly on version with new function (major)'       => [ '2.4.0', '2.5.2', true ],
			'Upgrade past version with new function (major)'              => [ '2.4.0', '2.6.0', true ],
			'Direct minor upgrade on same version'                        => [ '2.5.1', '2.5.2', true ],
			'Direct minor upgrade on version without new function'        => [ '2.5.0', '2.5.1', false ],
			// This is a weird case that should not happen, usually the function would not be added before it is needed
			'Direct minor upgrade past version with new function'         => [ '2.5.2', '2.5.3', false ],
			'Direct minor upgrade past version with new function (major)' => [ '2.5.2', '2.6.0', false ],
			'Downgrade from previous versions'                            => [ '2.5.3', '2.5.2', false ],
		);
	}

	public static function fakeUpdateFunctionA() {
		self::$functionAHasRun = true;
	}

	public static function fakeUpdateFunctionB() {
		self::$functionBHasRun = true;
	}

	public function testRunTasksAfterUpdate() {
		$olderVersion = '2.5.0';
		update_option( Upgrade::VERSION_OPTION, $olderVersion );
		Upgrade::runTasksAfterUpdate();
		$this->assertEquals( COMMONSBOOKING_VERSION, get_option( Upgrade::VERSION_OPTION ) );
	}

	public function testRun() {
		$upgrade = new Upgrade( '2.5.0', '2.6.0' );
		$this->assertTrue( $upgrade->run() );
		$this->assertEquals( '2.6.0', get_option( Upgrade::VERSION_OPTION ) );

		$upgrade = new Upgrade( '2.5.0', '2.5.1' );
		$this->assertTrue( $upgrade->run() );
		$this->assertEquals( '2.5.1', get_option( Upgrade::VERSION_OPTION ) );

		// new installation
		$upgrade = new Upgrade( '', '2.5.0' );
		$this->assertTrue( $upgrade->run() );
		$this->assertEquals( '2.5.0', get_option( Upgrade::VERSION_OPTION ) );

		// no version change
		$upgrade = new Upgrade( '2.5.0', '2.5.0' );
		$this->assertFalse( $upgrade->run() );

		// AJAX tasks present -> should not be run
		$this->setUpAJAX();
		$upgrade = new Upgrade( '2.5.1', '2.5.2' );
		$this->assertFalse( $upgrade->run() );

		// AJAX tasks should be ignored on new installation
		$upgrade = new Upgrade( '', '2.5.2' );
		$this->assertTrue( $upgrade->run() );
	}

	public function testSetAdvanceBookingDaysDefault() {
		// create timeframe without advance booking days
		$timeframeId = $this->createBookableTimeFrameIncludingCurrentDay();
		update_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS, '' );
		Upgrade::setAdvanceBookingDaysDefault();
		$this->assertEquals( \CommonsBooking\Wordpress\CustomPostType\Timeframe::ADVANCE_BOOKING_DAYS, get_post_meta( $timeframeId, \CommonsBooking\Model\Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS, true ) );
	}

	public function testRemoveBreakingPostmeta() {
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		// Create timeframe that should still be valid after the cleanup
		$validTF = new Timeframe( $this->createBookableTimeFrameStartingInAWeek() );
		$this->assertTrue( $validTF->isValid() );

		// create holiday with ADVANCE_BOOKING_DAYS setting (the function does this by default)
		$holiday = $this->createTimeframe(
			$this->locationId,
			$this->itemId,
			strtotime( '+1 week', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+2 weeks', strtotime( self::CURRENT_DATE ) ),
		);
		$this->assertTrue( Upgrade::removeBreakingPostmeta() );
		$this->assertEmpty( get_post_meta( $holiday, 'advance_booking_days', true ) );
	}

	public function testSetMultiSelectTimeFrameDefault() {
		$tf = $this->createBookableTimeFrameIncludingCurrentDay();
		update_post_meta( $tf, Timeframe::META_ITEM_SELECTION_TYPE, '' );
		update_post_meta( $tf, Timeframe::META_LOCATION_SELECTION_TYPE, '' );
		$this->assertTrue( Upgrade::setMultiSelectTimeFrameDefault() );
		$this->assertEquals( Timeframe::SELECTION_MANUAL_ID, get_post_meta( $tf, Timeframe::META_ITEM_SELECTION_TYPE, true ) );
		$this->assertEquals( Timeframe::SELECTION_MANUAL_ID, get_post_meta( $tf, Timeframe::META_LOCATION_SELECTION_TYPE, true ) );
	}

	public function testEnableLocationBookingNotification() {
		Upgrade::enableLocationBookingNotification();
		$this->assertEquals( 'on', get_post_meta( $this->locationId, COMMONSBOOKING_METABOX_PREFIX . 'location_email_bcc', true ) );
	}

	public function testIsAJAXUpgrade() {
		// 2.5.0 -> COMMONSBOOKING_VERSION
		update_option( Upgrade::VERSION_OPTION, '2.5.0' );
		$this->assertFalse( Upgrade::isAJAXUpgrade() );

		$this->setUpAJAX();

		// 2.5.0 -> COMMONSBOOKING_VERSION
		$this->assertTrue( Upgrade::isAJAXUpgrade() );

		// 2.6.0 -> COMMONSBOOKING_VERSION
		update_option( Upgrade::VERSION_OPTION, '2.6.0' );
		$this->assertFalse( Upgrade::isAJAXUpgrade() );

		// fresh install
		update_option( Upgrade::VERSION_OPTION, '' );
		$this->assertFalse( Upgrade::isAJAXUpgrade() );
	}

	/**
	 * Modifies the task so that task B is an AJAX task and the upgrade to 2.5.2 is therefore an AJAX upgrade
	 * @return void
	 */
	protected function setUpAJAX(): void {
		$this->testTasks->setValue(
			[
				'2.5.2' => [
					[ self::class, 'fakeUpdateFunctionA' ],
				],
			]
		);

		$this->ajaxTasks->setValue(
			[
				'2.5.2' => [
					[ self::class, 'fakeUpdateFunctionB' ],
				],
			]
		);
	}

	public function testMigrateMapSettings() {

		// create taxonomies for test
		$twowheelsCat      = wp_insert_term( '2 Räder', 'cb_items_category' )['term_id'];
		$threewheelsCat    = wp_insert_term( '3 Räder', 'cb_items_category' )['term_id'];
		$comboCat          = wp_insert_term( 'Gespann', 'cb_items_category' )['term_id'];
		$childTransportCat = wp_insert_term( 'Kindertransport', 'cb_items_category' )['term_id'];
		$chestCat          = wp_insert_term( 'Kiste mit Schloss', 'cb_items_category' )['term_id'];
		$rainCoverCat      = wp_insert_term( 'Regenverdeck', 'cb_items_category' )['term_id'];
		$withMotorCat      = wp_insert_term( 'mit Elektro', 'cb_items_category' )['term_id'];
		$manualPowerCat    = wp_insert_term( 'Reine Muskelkraft', 'cb_items_category' )['term_id'];
		$mapOptions        = array(
			'base_map'                              => 1,
			'show_scale'                            => true,
			'map_height'                            => 400,
			'custom_no_locations_message'           => 'No locations found',
			'custom_filterbutton_label'             => '',
			'zoom_min'                              => 9,
			'zoom_max'                              => 19,
			'scrollWheelZoom'                       => true,
			'zoom_start'                            => 9,
			'lat_start'                             => 50.937531,
			'lon_start'                             => 6.960279,
			'marker_map_bounds_initial'             => true,
			'marker_map_bounds_filter'              => true,
			'max_cluster_radius'                    => 80,
			'marker_tooltip_permanent'              => false,
			'custom_marker_media_id'                => 0,
			'marker_icon_width'                     => 0.0,
			'marker_icon_height'                    => 0.0,
			'marker_icon_anchor_x'                  => 0.0,
			'marker_icon_anchor_y'                  => 0.0,
			'show_location_contact'                 => false,
			'label_location_contact'                => '',
			'show_location_opening_hours'           => false,
			'label_location_opening_hours'          => '',
			'show_item_availability'                => false,
			'custom_marker_cluster_media_id'        => 0,
			'marker_cluster_icon_width'             => 0.0,
			'marker_cluster_icon_height'            => 0.0,
			'address_search_bounds_left_bottom_lon' => null,
			'address_search_bounds_left_bottom_lat' => null,
			'address_search_bounds_right_top_lon'   => null,
			'address_search_bounds_right_top_lat'   => null,
			'show_location_distance_filter'         => false,
			'label_location_distance_filter'        => '',
			'show_item_availability_filter'         => false,
			'label_item_availability_filter'        => '',
			'label_item_category_filter'            => '',
			'item_draft_appearance'                 => '1',
			'marker_item_draft_media_id'            => 0,
			'marker_item_draft_icon_width'          => 0.0,
			'marker_item_draft_icon_height'         => 0.0,
			'marker_item_draft_icon_anchor_x'       => 0.0,
			'marker_item_draft_icon_anchor_y'       => 0.0,
			'cb_items_available_categories'         =>
				array(
					'g1723473456166-602276' => 'Radzahl',
					$twowheelsCat           => '2 Räder',
					$threewheelsCat         => '3 Räder',
					$comboCat               => 'Gespann',
					'g1723473486911-550535' => '',
					$childTransportCat      => 'Kindertransport',
					$chestCat               => 'Kiste mit Schloss',
					$rainCoverCat           => 'Regenverdeck',
					'g1723473495758-257563' => '',
					$withMotorCat           => 'mit Elektro',
					$manualPowerCat         => 'Zum Strampeln',
					// this is changed markup for that category, should be moved to term meta (_cb_markup)
				),
			'cb_items_preset_categories'            =>
				array(),
			'cb_locations_preset_categories'        =>
				array(),
			'availability_max_days_to_show'         => 11,
			'availability_max_day_count'            => 14,
		);
		$oldMapId = wp_insert_post(
			[
				'post_title'  => 'Map',
				'post_type'   => Map::$postType,
				'post_status' => 'publish',
			]
		);

		update_post_meta( $oldMapId, 'cb_map_options', $mapOptions );

		Upgrade::migrateMapSettings();
		// each option should now have it's own meta entry
		foreach ( $mapOptions as $key => $value ) {
			if ( $key === 'cb_items_available_categories' || empty( $value ) ) {
				continue;
			}
			$this->assertEquals( $value, get_post_meta( $oldMapId, $key, true ) );
		}

		$expectedFilterCategories =
			array(
				0 =>
					array(
						'name'        => 'Radzahl',
						'type'        => '',
						'isExclusive' => false,
						'categories'  =>
							array(
								0 => (string) $twowheelsCat,
								1 => (string) $threewheelsCat,
								2 => (string) $comboCat,
							),
					),
				1 =>
					array(
						'name'        => '',
						'type'        => '',
						'isExclusive' => false,
						'categories'  =>
							array(
								0 => (string) $childTransportCat,
								1 => (string) $chestCat,
								2 => (string) $rainCoverCat,
							),
					),
				2 =>
					array(
						'name'        => '',
						'type'        => '',
						'isExclusive' => false,
						'categories'  =>
							array(
								0 => (string) $withMotorCat,
								1 => (string) $manualPowerCat,
							),
					),
			);

		$this->assertEquals( $expectedFilterCategories, get_post_meta( $oldMapId, 'filtergroups', true ) );
		// assert, that custom markup has been moved
		$expectedMarkup = 'Zum Strampeln';
		$this->assertEquals( $expectedMarkup, get_term_meta( $manualPowerCat, COMMONSBOOKING_METABOX_PREFIX . 'markup', true ) );

		wp_delete_post( $oldMapId, true );
		wp_delete_term( $twowheelsCat, 'cb_items_category' );
		wp_delete_term( $threewheelsCat, 'cb_items_category' );
		wp_delete_term( $comboCat, 'cb_items_category' );
		wp_delete_term( $childTransportCat, 'cb_items_category' );
		wp_delete_term( $chestCat, 'cb_items_category' );
		wp_delete_term( $rainCoverCat, 'cb_items_category' );
		wp_delete_term( $withMotorCat, 'cb_items_category' );
		wp_delete_term( $manualPowerCat, 'cb_items_category' );
	}

	public function testMigrateCacheSettings() {
		// redis enabled
		Settings::updateOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options', 'redis_enabled', 'on' );
		Upgrade::migrateCacheSettings();
		$this->assertEquals( 'redis', Settings::getOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options', 'cache_adapter' ) );

		// redis disabled, custom filesystem path
		Settings::updateOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options', 'redis_enabled', 'off' );
		Settings::updateOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options', 'cache_path', '/tmp/commonsbooking-cache' );
		Upgrade::migrateCacheSettings();
		$this->assertEquals( '/tmp/commonsbooking-cache', Settings::getOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options', 'cache_location' ) );
		$this->assertEquals( 'filesystem', Settings::getOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options', 'cache_adapter' ) );
	}

	protected function setUp(): void {
		parent::setUp();
		// This replaces the original update tasks with a internal test function that just sets a variable to true
		$this->testTasks = new \ReflectionProperty( '\CommonsBooking\Service\Upgrade', 'upgradeTasks' );
		$this->testTasks->setAccessible( true );
		$this->testTasks->setValue(
			[
				'2.5.2' => [
					[ self::class, 'fakeUpdateFunctionA' ],
					[ self::class, 'fakeUpdateFunctionB' ],
				],
			]
		);

		// empty AJAX tasks
		$this->ajaxTasks = new \ReflectionProperty( '\CommonsBooking\Service\Upgrade', 'ajaxUpgradeTasks' );
		$this->ajaxTasks->setAccessible( true );
		$this->ajaxTasks->setValue( [] );
	}

	protected function tearDown(): void {
		self::$functionAHasRun = false;
		self::$functionBHasRun = false;
		// resets version back to current version
		update_option( \CommonsBooking\Service\Upgrade::VERSION_OPTION, COMMONSBOOKING_VERSION );
		parent::tearDown();
	}
}
