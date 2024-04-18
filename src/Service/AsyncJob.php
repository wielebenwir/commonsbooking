<?php

namespace CommonsBooking\Service;

/**
 * This class is used to schedule jobs to be executed in the background.
 * The jobs are scheduled using cron and will be run upon the next cron run.
 *
 * We only support single actions that are run right away.
 * Create a new instance of this class to schedule a new job to be run ASAP.
 */
class AsyncJob {

	/**
	 * Will schedule a new job to be run as soon as possible.
	 *
	 * @param callable $callback The callback to run when the job is executed.
	 * @param array $args An array of args where each entry is a single argument.
	 */
	public function __construct( callable $callback, array $args = [] ) {
		$jobhook          = COMMONSBOOKING_PLUGIN_SLUG . '_async_' . wp_rand( 0, 999999999 );
		add_action( $jobhook, $callback );
		$success = $this->addAsync( $jobhook, $args );
		if ( is_wp_error( $success ) ) {
			$callback( $args );
		}
	}

	/**
	 * Enqueue an action to run one time, as soon as possible
	 *
	 * @param string $hook The hook to trigger.
	 * @param array $args Arguments to pass when the hook triggers.
	 *
	 * @return bool|\WP_Error True if the action was successfully scheduled, WP_Error on failure.
	 */
	private function addAsync( string $hook, array $args = [] ): string {
		return wp_schedule_single_event( time(), $hook, $args );
	}

}