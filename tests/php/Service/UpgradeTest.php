<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Model\Timeframe;
use CommonsBooking\Service\Upgrade;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use SlopeIt\ClockMock\ClockMock;

class UpgradeTest extends CustomPostTypeTest
{

    private static bool $functionHasRun = false;

    public function testFixBrokenICalTitle()
    {
		\CommonsBooking\Settings\Settings::updateOption(
			'commonsbooking_options_templates',
			'emailtemplates_mail-booking_ics_event-title',
		'Booking for {{item:post_name}}'
		);
		\CommonsBooking\Settings\Settings::updateOption(
			COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options',
			'event_title',
			'Booking for {{item:post_name}}'
		);
		Upgrade::fixBrokenICalTitle();
		$this->assertEquals('Booking for {{item:post_title}}', \CommonsBooking\Settings\Settings::getOption('commonsbooking_options_templates', 'emailtemplates_mail-booking_ics_event-title'));
		$this->assertEquals('Booking for {{item:post_title}}', \CommonsBooking\Settings\Settings::getOption(COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options', 'event_title'));
    }

    public function testIsMajorUpdate()
    {
		$majorUpdate = new Upgrade('2.5.0', '2.6.0');
		$this->assertTrue($majorUpdate->isMajorUpdate());
		$minorUpdate = new Upgrade('2.5.0', '2.5.1');
		$this->assertFalse($minorUpdate->isMajorUpdate());
		$majorestUpdate = new Upgrade('2.5.0', '3.0.0');
		$this->assertTrue($majorestUpdate->isMajorUpdate());
		$downgrade = new Upgrade('2.6.0', '2.5.0');
		$this->assertFalse($downgrade->isMajorUpdate());
    }

	/**
	 * This will test if the upgrade tasks are run correctly.
	 * The test function should only run, when upgrading on or over version 2.5.2.
	 * It should for example not run when upgrading from 2.5.2 to 2.5.3.
	 *
	 * @dataProvider provideUpgradeConditions
	 */
	public function testRunUpgradeTasks($previousVersion, $currentVersion, $shouldRunFunction) {
		$upgrade = new Upgrade($previousVersion, $currentVersion);
		$upgrade->runUpgradeTasks();
		$this->assertEquals($shouldRunFunction, self::$functionHasRun);
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
			"Upgrade directly on version with new function (major)" => ["2.4.0", "2.5.2", true],
			"Upgrade past version with new function (major)" => ["2.4.0", "2.6.0", true],
			"Direct minor upgrade on same version" => ["2.5.1", "2.5.2", true],
			"Direct minor upgrade on version without new function" => ["2.5.0", "2.5.1", false], //This is a weird case that should not happen, usually the function would not be added before it is needed
			"Direct minor upgrade past version with new function" => ["2.5.2", "2.5.3", false],
			"Direct minor upgrade past version with new function (major)" => ["2.5.2", "2.6.0", false],
			"Downgrade from previous versions" => ["2.5.3", "2.5.2", false],
		);
	}

	public static function fakeUpdateFunction()
	{
		self::$functionHasRun = true;
	}

	public function testRunTasksAfterUpdate() {
		$olderVersion = '2.5.0';
		update_option(Upgrade::VERSION_OPTION, $olderVersion);
		Upgrade::runTasksAfterUpdate();
		$this->assertEquals(COMMONSBOOKING_VERSION, get_option(Upgrade::VERSION_OPTION));
	}

	public function testRun() {
		$upgrade = new Upgrade('2.5.0', '2.6.0');
		$this->assertTrue($upgrade->run());
		$this->assertEquals('2.6.0', get_option(Upgrade::VERSION_OPTION));

		$upgrade = new Upgrade('2.5.0', '2.5.1');
		$this->assertTrue($upgrade->run());
		$this->assertEquals('2.5.1', get_option(Upgrade::VERSION_OPTION));

		//new installation
		$upgrade = new Upgrade('', '2.5.0');
		$this->assertTrue($upgrade->run());
		$this->assertEquals('2.5.0', get_option(Upgrade::VERSION_OPTION));

		//no version change
		$upgrade = new Upgrade('2.5.0', '2.5.0');
		$this->assertFalse($upgrade->run());
	}

	public function testSetAdvanceBookingDaysDefault() {
		//create timeframe without advance booking days
		$timeframeId = $this->createBookableTimeFrameIncludingCurrentDay();
		update_post_meta($timeframeId, \CommonsBooking\Model\Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS, '');
		Upgrade::setAdvanceBookingDaysDefault();
		$this->assertEquals(\CommonsBooking\Wordpress\CustomPostType\Timeframe::ADVANCE_BOOKING_DAYS, get_post_meta($timeframeId, \CommonsBooking\Model\Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS, true));
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
		Upgrade::removeBreakingPostmeta();
		$this->assertEmpty(get_post_meta($holiday, 'advance_booking_days', true));
	}

	public function testSetMultiSelectTimeFrameDefault() {
		$tf = $this->createBookableTimeFrameIncludingCurrentDay();
		update_post_meta($tf, Timeframe::META_ITEM_SELECTION_TYPE, '');
		update_post_meta($tf, Timeframe::META_LOCATION_SELECTION_TYPE, '');
		Upgrade::setMultiSelectTimeFrameDefault();
		$this->assertEquals(Timeframe::SELECTION_MANUAL_ID, get_post_meta($tf, Timeframe::META_ITEM_SELECTION_TYPE, true));
		$this->assertEquals(Timeframe::SELECTION_MANUAL_ID, get_post_meta($tf, Timeframe::META_LOCATION_SELECTION_TYPE, true));
	}

	public function testEnableLocationBookingNotification() {
		Upgrade::enableLocationBookingNotification();
		$this->assertEquals('on', get_post_meta($this->locationId, COMMONSBOOKING_METABOX_PREFIX . 'location_email_bcc', true) );
	}

	protected function setUp(): void {
		parent::setUp();
		//This replaces the original update tasks with a internal test function that just sets a variable to true
		$testTasks = new \ReflectionProperty('\CommonsBooking\Service\Upgrade', 'upgradeTasks');
		$testTasks->setAccessible(true);
		$testTasks->setValue(
			[
				'2.5.2' => [
					[self::class, 'fakeUpdateFunction' ]
				]
			]
		);
	}

	protected function tearDown(): void {
		self::$functionHasRun = false;
		//resets version back to current version
		update_option(\CommonsBooking\Service\Upgrade::VERSION_OPTION, COMMONSBOOKING_VERSION);
		parent::tearDown();
	}
}
