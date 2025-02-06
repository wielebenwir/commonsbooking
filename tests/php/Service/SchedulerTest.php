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
	 * Tests re-scheduling for jobs with daily recurrence
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
		$this->jobhooks[] = $dailyJob->getJobhook();

		$now = new DateTime();
		$nextTime = DateTime::createFromFormat('H:i', '13:00', wp_timezone() );
		$nextTime->setTimezone(new \DateTimeZone('UTC'));

		if ($nextTime < $now) {
			$nextTime->modify('+1 day');
		}
		$nextTimeTimestamp = $nextTime->getTimestamp();

		$this->assertEquals(
			$nextTimeTimestamp,
			wp_next_scheduled($dailyJob->getJobhook())
		);

		//now we update the time and check if the job is rescheduled (it is first unscheduled and then loaded again)
		Settings::updateOption($this->dummyOptionsKey,$this->dummyFieldId,'13:00');
		//job was unloaded to be rescheduled
		$this->assertFalse(
			wp_next_scheduled($dailyJob->getJobhook())
		);
	}

	/**
	 * Tests custom recurrences via wp cron interfaces
	 */
	public function testCustomRecurrence() {

		$thirty_minutes = 'thirty_minutes';

		$customSchedule = new Scheduler(
			'test2',
			'CommonsBooking\Tests\Service\dummyFunction',
			$thirty_minutes,
			'',
			array($this->dummyOptionsKey,$this->dummyFieldId),
			$this->dummyUpdateHook
		);
		$this->jobhooks[] = $customSchedule->getJobhook();

		// Should contain custom cron intervals, because Scheduler(...) adds filter
		$this->assertContains( $thirty_minutes, array_keys( wp_get_schedules() ) );

		$this->assertEquals(
			// returns timestamp which is used to set internal event object (via wp_schedule_event in Scheduler class)
			$customSchedule->getTimestamp(),
			// returns timestamp from internal event object identified by job hook
			wp_next_scheduled( $customSchedule->getJobhook() )
		);

		$event = wp_get_scheduled_event($customSchedule->getJobhook());
		$this->assertEquals( $thirty_minutes , $event->schedule );

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
		$this->jobhooks[] = $this->scheduler->getJobhook();
	}

	protected function tearDown(): void {
		foreach ($this->jobhooks as $jobhook){
			wp_clear_scheduled_hook($jobhook);
		}
		parent::tearDown();
	}

}
