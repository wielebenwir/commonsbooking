<?php

namespace CommonsBooking\Service;

use CommonsBooking\Settings\Settings;
use CommonsBooking\View\TimeframeExport;

class Scheduler {

	protected string $jobhook;
	protected string $reccurence;
	protected int $timestamp;

	const UNSCHEDULER_HOOK = COMMONSBOOKING_PLUGIN_SLUG . '_unschedule';

	//constructs the class, if job does not exist yet it is created
	function __construct(
		string $jobhook, //the action hook to run when the event is executed
		callable $callback, //the callback function of that hook
		string $reccurence, //how often the event should subsequently recur
		string $executionTime = '', //takes time of day the job should be executed, only for daily reccurence
		array $option = array(), //first element is the options_key, second is the field_id. If set, the field is checked and determines wether the hook should be ran
		string $updateHook = ''  //The wordpress hook that should update the option
	) {
		// Add custom cron intervals
		add_filter( 'cron_schedules', array( self::class, 'initIntervals' ) );

		$this->jobhook = COMMONSBOOKING_PLUGIN_SLUG . '_' . $jobhook; //Prepends plugin slug so that hooks can be found easily afterwards

		if ( ( count( $option ) == 2 ) && Settings::getOption( $option[0], $option[1] ) != 'on' ) { //removes job if option unset
			$this->unscheduleJob();

			return false;
		}

		if ( empty( $executionTime ) ) {
			$this->timestamp = time();
		} elseif ( $reccurence == 'daily' ) {
			$this->timestamp = strtotime( $executionTime );
			if ( $this->timestamp < time() ) { //if timestamp is in the past, add one day
				$this->timestamp = strtotime( "+1 day", $this->timestamp );
			}
		} else {
			return false;
		}


		$this->reccurence = $reccurence;

		add_action( $this->jobhook, $callback ); //attaches the jobhook to the callback function

		if ( ! wp_next_scheduled( $this->jobhook ) ) { //add job if it does not exist yet
			wp_schedule_event( $this->timestamp, $this->reccurence, $this->jobhook );
		}

		if ( $updateHook ) { //attach updateHook to updater function
			add_action(
				$updateHook,
				function () {
					$this->unscheduleJob(); //hooks is unscheduled upon change, needs to be rescheduled
				}
			);
		}

		add_action(
			self::UNSCHEDULER_HOOK,
			function () {
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
	 */
	public static function initHooks() {
		// Init booking cleanup job
		new Scheduler(
			'cleanup',
			array( \CommonsBooking\Service\Booking::class, 'cleanupBookings' ),
			'ten_minutes'
		);

		// Init booking reminder job
		new Scheduler(
			'reminder',
			array( \CommonsBooking\Service\Booking::class, 'sendReminderMessage' ),
			'daily',
			'today ' . Settings::getOption( 'commonsbooking_options_reminder', 'pre-booking-time' ) . ':00',
			array( 'commonsbooking_options_reminder', 'pre-booking-reminder-activate' ),
			'update_option_commonsbooking_options_reminder'
		);

		// Init booking feedback job
		new Scheduler(
			'feedback',
			array( \CommonsBooking\Service\Booking::class, 'sendFeedbackMessage' ),
			'daily',
			'tomorrow midnight',
			array( 'commonsbooking_options_reminder', 'post-booking-notice-activate' ),
			'update_option_commonsbooking_options_reminder'
		);

		// Init timeframe export job
		$exportPath     = Settings::getOption( 'commonsbooking_options_export', 'export-filepath' );
		$exportInterval = Settings::getOption( 'commonsbooking_options_export', 'export-interval' );
		new Scheduler(
			'export',
			function () use ( $exportPath ) {
				TimeframeExport::exportCsv( $exportPath );
			},
			$exportInterval,
			'',
			array( 'commonsbooking_options_export', 'export-cron' ),
			'update_option_commonsbooking_options_export'
		);
	}

	/**
	 * Unschedules the current job
	 *
	 * @return boolean
	 */
	private function unscheduleJob() {
		$timestamp = wp_next_scheduled( $this->jobhook );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, $this->jobhook );

			return true;
		}

		return false;
	}

	/**
	 * Unschedules legacy jobs
	 */
	public static function unscheduleOldEvents() {
		$cbCronHooks = [
			'cb_cron_hook',
			'cb_reminder_cron_hook',
			'cb_feedback_cron_hook',
			'cb_cron_export'
		];

		foreach ( $cbCronHooks as $cbCronHook ) {
			$timestamp = wp_next_scheduled( $cbCronHook );
			wp_unschedule_event( $timestamp, $cbCronHook );
		}
	}
}