<?php

namespace CommonsBooking\Service;

use CommonsBooking\Settings\Settings;

/**
 * This class is used to schedule jobs that are executed in the background.
 * The jobs are scheduled using the WordPress cron system.
 * We enhance this system by adding
 * custom intervals,
 * hooks that allow us to unschedule jobs and re-schedule them when the execution time in the settings is changed
 * and the check for an option that determines weather the job should be run.
 */
class Scheduler {

	protected string $jobhook; 
	protected string $reccurence; 
	protected int $timestamp;

	const UNSCHEDULER_HOOK = COMMONSBOOKING_PLUGIN_SLUG . '_unschedule';


	/**
	 * Every job will be constructed again when the page is loaded.
	 * The constructor determines if the job is scheduled and if it should be scheduled.
	 * It also hooks the appropriate actions that will un-schedule the job upon certain changes.
	 * We can safely un-schedule the job upon changes, because the job will be re-scheduled with the correct settings when the page is loaded again.
	 * @param string $jobhook the action hook to run when the event is executed
	 * @param callable $callback the callback function of that hook
	 * @param string $reccurence how often the event should subsequently recur
	 * @param string $executionTime takes time of day the job should be executed, only for daily reccurence
	 * @param array $option first element is the options_key, second is the field_id. If set, the field is checked and determines wether the hook should be ran
	 * @param string $updateHook The wordpress hook that should update the option
	 */
	function __construct(
		string $jobhook,
		callable $callback,
		string $reccurence,
		string $executionTime = '',
		array $option = array(),
		string $updateHook= ''
	)
	{
		// Add custom cron intervals
		add_filter( 'cron_schedules', array( self::class, 'initIntervals' ) );
		$this->jobhook = COMMONSBOOKING_PLUGIN_SLUG . '_' .$jobhook; //Prepends plugin slug so that hooks can be found easily afterwards 

		if ((count($option) == 2)  && Settings::getOption($option[0],$option[1]) != 'on' ) { //removes job if option unset
			$this->unscheduleJob();
			return false;
		}

		if (empty($executionTime)){
			$this->timestamp = time();
		} 
		elseif ($reccurence == 'daily'){
			$this->timestamp = strtotime($executionTime);
			if($this->timestamp < time()) { //if timestamp is in the past, add one day
				$this->timestamp = strtotime("+1 day",$this->timestamp);
			}
		}
		else {
			return false;
		}


		$this->reccurence = $reccurence;

		add_action($this->jobhook,$callback); //attaches the jobhook to the callback function

		if (! wp_next_scheduled( $this->jobhook )){ //add job if it does not exist yet
			wp_schedule_event($this->timestamp,$this->reccurence,$this->jobhook);
		}

		if ($updateHook) { //attach updateHook to updater function
			add_action(
				$updateHook,
				function(){
					$this->unscheduleJob(); //hooks is unscheduled upon change, needs to be rescheduled
				}
			);
		}

		add_action(
			self::UNSCHEDULER_HOOK,
			function(){
				$this->unscheduleJob();
			}
		); //registers unschedule action
	}
	/**
	 * Returns array with custom time intervals.
	 * @return array[]
	 */
	public static function getIntervals(): array {
		return array(
			'ten_seconds'    => array(
				'display'  => 'Every 10 Seconds',
				'interval' => 10,
			),
			'ten_minutes'    => array(
				'display'  => 'Every 10 Minutes',
				'interval' => 600,
			),
			'five_minutes'   => array(
				'display'  => 'Every 5 Minutes',
				'interval' => 300,
			),
			'thirty_minutes' => array(
				'display'  => 'Every 30 Minutes',
				'interval' => 1800,
			)
		);
	}

	/**
	 * Inits custom intervals.
	 *
	 * @param $schedules
	 *
	 * @return array
	 */
	public static function initIntervals( $schedules ): array {
		return array_merge( $schedules, self::getIntervals() );
	}

	/**
	 * Inits scheduler hooks.
	 * If you want to add a new job, add it here.
	 * These hooks will be executed when the page is loaded.
	 *
	 * @return void
	 */
	public static function initHooks() {
		// Init booking cleanup job
		New Scheduler(
			'cleanup',
			array( \CommonsBooking\Service\Booking::class, 'cleanupJobs' ),
			'ten_minutes'
		);

		// Init booking reminder job
		New Scheduler(
			'reminder',
			array( \CommonsBooking\Service\Booking::class, 'sendReminderMessage' ),
			'daily',
			'today ' . Settings::getOption( 'commonsbooking_options_reminder', 'pre-booking-time' ) . ':00',
			array( 'commonsbooking_options_reminder', 'pre-booking-reminder-activate'),
			'update_option_commonsbooking_options_reminder'
		);

		// Init booking start reminder job for locations
		New Scheduler(
			'location-reminder-booking-start',
			array( \CommonsBooking\Service\Booking::class, 'sendBookingStartLocationReminderMessage' ),
			'daily',
			'today ' . Settings::getOption( 'commonsbooking_options_reminder', 'booking-start-location-reminder-time' ) . ':00',
			array( 'commonsbooking_options_reminder', 'booking-start-location-reminder-activate'),
			'update_option_commonsbooking_options_reminder'
		);

		// Init booking end reminder job for locations
		New Scheduler(
			'location-reminder-booking-end',
			array( \CommonsBooking\Service\Booking::class, 'sendBookingEndLocationReminderMessage' ),
			'daily',
			'today ' . Settings::getOption( 'commonsbooking_options_reminder', 'booking-end-location-reminder-time' ) . ':00',
			array( 'commonsbooking_options_reminder', 'booking-end-location-reminder-activate'),
			'update_option_commonsbooking_options_reminder'
		);

		// Init booking feedback job
		New Scheduler(
			'feedback',
			array( \CommonsBooking\Service\Booking::class, 'sendFeedbackMessage' ),
			'daily',
			'tomorrow midnight',
			array( 'commonsbooking_options_reminder', 'post-booking-notice-activate'),
			'update_option_commonsbooking_options_reminder'
		);

		// Init email booking codes job
		New Scheduler(
			'email_bookingcodes',
			array( \CommonsBooking\Service\BookingCodes::class, 'sendBookingCodesMessage' ),
			'daily',
			'today midnight +3 hour'
		);

		// Init timeframe export job
		$exportPath = Settings::getOption( 'commonsbooking_options_export', 'export-filepath' );
		$exportInterval = Settings::getOption( 'commonsbooking_options_export', 'export-interval' );
		New Scheduler(
			'export',
			function() use ( $exportPath ) {
				TimeframeExport::cronExport( $exportPath );
			},
			$exportInterval,
			'',
			array( 'commonsbooking_options_export', 'export-cron'  ),
			'update_option_commonsbooking_options_export'
		);
	}

	/**
	 * Returns the jobhook of the current job
	 *
	 * @return string
	 */
	public function getJobhook(): string {
		return $this->jobhook;
	}

	/**
	 * @return int timestamp of scheduled event
	 */
	public function getTimestamp(): int {
		return $this->timestamp;
	}

	/**
	 * Unschedules the current job.
	 * This can have multiple reasons:
	 * - The job is no longer needed
	 * - The job has been updated and needs to be rescheduled
	 * - The plugin has been deactivated
	 * @return boolean
	 */
	private function unscheduleJob() {
		return wp_clear_scheduled_hook($this->jobhook);
	}

	/**
	 * Unschedules legacy jobs.
	 * These jobs come from earlier versions of CommonsBooking 2.x and are no longer needed.
	 * There are also jobs still from CommonsBooking 0.X listed here.
	 * It is important to remove the jobs, because WordPress does not delete them on it's own, not even on plugin deactivation.
	 */
	public static function unscheduleOldEvents() {
		$cbCronHooks = [
			'cb_cron_hook',
			'cb_reminder_cron_hook',
			'cb_feedback_cron_hook',
			'cb_cron_export',
			'cb_map_import',
			'cb_cron_delete_pending'
		];

		foreach ( $cbCronHooks as $cbCronHook ) {
			wp_clear_scheduled_hook($cbCronHook);
		}
	}
}
