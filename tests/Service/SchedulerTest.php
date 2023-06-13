<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Service\Scheduler;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use DateTime;

function dummyFunction() {
	print("Hello world");
}

class SchedulerTest extends CustomPostTypeTest
{
	private Scheduler $scheduler;
	private String $dummyOptionsKey = 'commonsbooking_options_tests';
	private String $dummyFieldId = 'test_field_toggle';
	private String $dummyUpdateHook = 'update_option_commonsbooking_options_tests';
	private array $jobhooks;

	public function testSchedule() {
		$this->assertIsInt(
			wp_next_scheduled($this->scheduler->getJobhook())
		);
	}

	public function testUnschedule() {
		$this->assertIsInt(
			wp_next_scheduled($this->scheduler->getJobhook())
		);
		Settings::updateOption($this->dummyOptionsKey,$this->dummyFieldId,'off');
		$this->assertFalse(
			wp_next_scheduled($this->scheduler->getJobhook())
		);
	}

	/**
	 * Tests re-schedulign for jobs with daily recurrence
	 * @return void
	 */
	public function testReSchedule() {
		$dailyJob = new Scheduler(
			'test2',
			'CommonsBooking\Tests\Service\dummyFunction',
			'daily',
			'13:00',
			array($this->dummyOptionsKey,$this->dummyFieldId),
			$this->dummyUpdateHook
		);
		$this->jobhooks[] = 'test2';

		$now = new DateTime();
		$nextTime = DateTime::createFromFormat('H:i', '13:00');

		if ($nextTime < $now) {
			$nextTime->modify('+1 day');
		}
		$nextTimeTimestamp = $nextTime->getTimestamp();

		$this->assertEquals(
			$nextTimeTimestamp,
			wp_next_scheduled($dailyJob->getJobhook())
		);

		//now we update the time and check if the job is rescheduled
		Settings::updateOption($this->dummyOptionsKey,$this->dummyFieldId,'13:00');
		$nextTime = DateTime::createFromFormat('H:i', '13:00');
		$nextTimeTimestamp = $nextTime->getTimestamp();
		if ($nextTime < $now) {
			$nextTime->modify('+1 day');
		}
		$this->assertEquals(
			$nextTimeTimestamp,
			wp_next_scheduled($dailyJob->getJobhook())
		);

	}

	public function testCustomRecurrence() {
		$customSchedule = new Scheduler(
			'test2',
			'CommonsBooking\Tests\Service\dummyFunction',
			'thirty_minutes',
			'',
			array($this->dummyOptionsKey,$this->dummyFieldId),
			$this->dummyUpdateHook
		);
		$this->jobhooks[] = 'test2';

		$now = new DateTime();
		$now->modify('+30 minutes');
		$nextTimeTimestamp = $now->getTimestamp();
		$this->assertEquals(
			$nextTimeTimestamp,
			wp_next_scheduled($customSchedule->getJobhook())
		);

	}

	protected function setUp(): void {
		parent::setUp();
		Settings::updateOption($this->dummyOptionsKey,$this->dummyFieldId,'on');
		$this->scheduler = new Scheduler(
			'test',
			'CommonsBooking\Tests\Service\dummyFunction',
			'hourly',
			'',
			array($this->dummyOptionsKey,$this->dummyFieldId),
			$this->dummyUpdateHook
		);
		$this->jobhooks[] = 'test';
	}

	protected function tearDown(): void {
		foreach ($this->jobhooks as $jobhook){
			$timestamp = wp_next_scheduled($jobhook);
			wp_unschedule_event($timestamp,$jobhook);
		}
		parent::tearDown();
	}

}
