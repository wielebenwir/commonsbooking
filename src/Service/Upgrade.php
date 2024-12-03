<?php

namespace CommonsBooking\Service;

use CommonsBooking\Messages\AdminMessage;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Plugin;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Wordpress\Options\AdminOptions;
use Psr\Cache\InvalidArgumentException;

/**
 * This class contains migration functionality that is run when the plugin is upgraded
 * to a newer version. When upgrading, create a new instance of this class and call the
 * run() function.
 *
 * At the moment you can implement your own migrations in $upgradeTasks.
 *
 * A version string must be given in semantic versioning format (https://semver.org/).
 */
class Upgrade {

	const VERSION_OPTION = COMMONSBOOKING_PLUGIN_SLUG . '_plugin_version';
	/**
	 * The number of posts that will be processed in each iteration of the AJAX upgrade tasks.
	 */
	const POSTS_PER_ITERATION = 10;
	private string $previousVersion;
	private string $currentVersion;

	/**
	 * This array contains all the tasks that need to be run when upgrading from a version lower than the key to the version of the value.
	 * For example, if you introduce a new feature in version 2.6.0,
	 * you would add a new entry to this array with the key being "2.6.0" and the value being the function that needs to be run.
	 *
	 * This is so that once the upgrade from a specific version has been run, it will not be run again.
	 * @var array[]
	 */
	private static array $upgradeTasks = [
		'2.6.0' => [
			[ \CommonsBooking\Migration\Booking::class, 'migrate' ],
			[ self::class, 'setAdvanceBookingDaysDefault' ]
		],
		'2.8.0' => [
			[ \CommonsBooking\Service\Scheduler::class, 'unscheduleOldEvents' ]
		],
		'2.8.2' => [
			[ self::class, 'resetBrokenColorScheme' ],
			[ self::class, 'fixBrokenICalTitle' ]
		],
		'2.9.2' => [
			[self::class, 'enableLocationBookingNotification']
		]
	];

	/**
	 * This does the same as the above, but is for tasks that need a long time to run and might time out.
	 * For this purpose we will use AJAX to run these tasks.
	 *
	 * The functions should be static, support being run multiple times,
	 * take a page argument as the first parameter
	 * and return an int with the last processed page and true if the task is done.
	 *
	 * ATTENTION: These tasks will be ignored upon new installations.
	 *
	 * @var array|array[]
	 */
	private static array $ajaxUpgradeTasks = [
		'2.8.5' => [
			[ self::class, 'removeBreakingPostmeta' ]
		],
		'2.9.0' => [
			[ self::class, 'setMultiSelectTimeFrameDefault' ]
		]
	];

	/**
	 * The tasks that will be run upon every upgrade.
	 * @return void
	 */
	private function runEveryUpgrade(): void {
		// set Options default values (e.g. if there are new fields added)
		AdminOptions::SetOptionsDefaultValues();

		// flush rewrite rules
		flush_rewrite_rules();

		// Update Location Coordinates
		self::updateLocationCoordinates();

		// add role caps for custom post types
		Plugin::addCPTRoleCaps();

		// update version number in options
		update_option( self::VERSION_OPTION, $this->currentVersion );

		// Clear cache
		try {
			Plugin::clearCache();
		} catch ( InvalidArgumentException $e ) {
			// Do nothing
		}

	}

	/**
	 * Constructs new upgrade object for a version range
	 *
	 * @param string $previousVersion
	 * @param string $currentVersion
	 */
	public function __construct( string $previousVersion, string $currentVersion ) {
		$this->previousVersion = $previousVersion;
		$this->currentVersion  = $currentVersion;
	}

	/**
	 * Run a complete upgrade from the previous version to the current version.
	 * Will return true if the version has changed and the upgrade has been run.
	 * Will return false if the version has not changed and the upgrade has not been run.
	 */
	public function run(): bool {
		// check if version has changed, or it is a new installation
		if ( ! empty( $this->previousVersion ) ) {
			// version has not changed
			if ( $this->previousVersion === $this->currentVersion ) {
				return false;
			}
			//upgrade needs to be run in AJAX
			if ( $this->getTasksForUpgrade( self::$ajaxUpgradeTasks ) ) {
				new AdminMessage(
					'<b>CommonsBooking:</b> ' .
					__( 'There are some tasks that need to be run to complete the update process. <br> This needs to be done so that the plugin can function correctly.', 'commonsbooking' )
					. '<br>'
					. '<a href=' . esc_url( admin_url( 'admin.php?page=commonsbooking_options_migration' ) ) . '>'
					. __( 'Click here to run the upgrade tasks.', 'commonsbooking' ) . '</a>',
					'warning'
				);

				return false;
			}
		}

		// run upgrade tasks that are specific for version updates and should only run once
		$this->runUpgradeTasks();

		$this->runEveryUpgrade();

		return true;
	}

	/**
	 * This runs the tasks that are specific for version updates and should only run once.
	 *
	 * @return void
	 */
	public function runUpgradeTasks(): void {
		// TODO let thirdparty plugins be able to hook into this part, then they don't have to add their own implementation of this class
		foreach ( $this->getTasksForUpgrade( self::$upgradeTasks ) as $task ) {
			list( $className, $methodName ) = $task;
			call_user_func( array( $className, $methodName ) );
		}
	}

	/**
	 * Returns an array of tasks that need to be run for this upgrade.
	 *
	 * @param $upgradeTasks - An associative array with the version as key and the tasks as value (array of tasks).
	 *
	 * @return array
	 */
	private function getTasksForUpgrade( $upgradeTasks ): array {
		$tasks = [];
		foreach ( $upgradeTasks as $version => $versionTasks ) {
			if ( version_compare( $this->previousVersion, $version, '<' ) && version_compare( $this->currentVersion, $version, '>=' ) ) {
				$tasks = array_merge( $tasks, $versionTasks );
			}
		}

		return $tasks;
	}

	/**
	 * This function will determine if the plugin has been updated and run the upgrade tasks if necessary.
	 *
	 * @return void
	 */
	public static function runTasksAfterUpdate(): void {
		$upgrade = new Upgrade(
			esc_html( get_option( self::VERSION_OPTION ) ),
			COMMONSBOOKING_VERSION
		);
		$upgrade->run();
	}

	/**
	 *
	 * Test in @see \CommonsBooking\Tests\Service\UpgradeTest_AJAX
	 *
	 */
	public static function runAJAXUpgradeTasks(): void {
		//verify nonce
		check_ajax_referer( 'cb_run_upgrade', 'nonce' );
		$data = isset ( $_POST['data'] ) ? (array) $_POST['data'] : array();
		$data = commonsbooking_sanitizeArrayorString( $data );

		$taskNo = $data['progress']['task'] ?? 0;
		$page   = $data['progress']['page'] ?? 1;

		$taskNo     = (int) $taskNo;
		$page       = (int) $page;
		$upgrade    = new Upgrade(
			esc_html( get_option( self::VERSION_OPTION ) ),
			COMMONSBOOKING_VERSION
		);
		$totalTasks = $upgrade->getTasksForUpgrade( self::$ajaxUpgradeTasks );
		$task       = $totalTasks[ $taskNo ];
		list ( $className, $methodName ) = $task;
		$page = call_user_func( array( $className, $methodName ), $page );
		//previous task was successful
		if ( $page === true ) {
			//check if there are more tasks
			if ( isset( $totalTasks[ $taskNo + 1 ] ) ) {
				$response = [
					'success'  => false,
					'error'    => false,
					'progress' => [
						'task' => $taskNo + 1,
						'page' => 1
					]
				];
			} else {
				//all tasks are done
				$response = [
					'success' => true,
					'error'   => false,

				];
			}
		} else {
			$response = [
				'success'  => false,
				'error'    => false,
				'progress' => [
					'task' => $taskNo,
					'page' => $page
				]
			];
		}

		//run other upgrade actions
		if ( $response['success'] === true ) {
			$upgrade->runUpgradeTasks();
			$upgrade->runEveryUpgrade();
		}

		wp_send_json( $response );
	}

	/**
	 * Will determine if the latest upgrade needs to run AJAX actions to complete.
	 * @return bool true if AJAX actions are needed, false if not.
	 */
	public static function isAJAXUpgrade(): bool {
		$previousVersion = esc_html( get_option( self::VERSION_OPTION ) );
		$currentVersion  = COMMONSBOOKING_VERSION;
		if ( empty( $previousVersion ) | $previousVersion === $currentVersion ) {
			return false;
		}
		$upgrade = new Upgrade(
			$previousVersion,
			$currentVersion
		);

		return ! empty( $upgrade->getTasksForUpgrade( self::$ajaxUpgradeTasks ) );
	}

	/**
	 * renders a custom update notice in plugin list if the version number increases
	 * in a major release e.g. 2.5 -> 2.6
	 * This is a warning to users BEFORE they update to a new version.
	 *
	 * @return void (but renders html)
	 */
	public function updateNotice(): void {
		if ( ! $this->isMajorUpdate() ) {
			return;
		}
		?>
		<hr class="cb-major-update-warning__separator"/>
		<div class="cb-major-update-warning">
			<div class="cb-major-update-warning__icon">
				<i class="dashicons dashicons-megaphone"></i>
			</div>
			<div>
				<div class="cb-major-update-warning__title">
					<?php echo esc_html__( 'New features and changes: Please backup your site before upgrading!', 'commonsbooking' ); ?>
				</div>
				<div class="e-major-update-warning__message">
					<?php
					printf(
					/* translators: %1$s Link open tag, %2$s: Link close tag. */
						commonsbooking_sanitizeHTML(
							__(
								'
					This CommonsBooking update has a lot of new features and changes on some templates.<br>
					If you have modified any template files, please back them up and re-apply your changes after the update. <br>
					<br><br>We highly recommend you to <strong>%1$sread the update information%2$s </strong> and make a backup of your site before upgrading.',
								'commonsbooking'
							)
						),
						'<a target="_blank" href="https://commonsbooking.org/docs/installation/update-info/">',
						'</a>'
					);
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Will get if the current version is a major update.
	 * Note, that in CB, major updates are not the same as in semantic versioning.
	 *
	 * We consider a major update to be a change in the first or second number of the version.
	 * The third number is considered a minor update or a patch.
	 *
	 * We do not usually update the first number, but if we do, it is a major update.
	 *
	 * Example:
	 * 2.5.0 -> 2.6.0 is a major update
	 * 2.5.0 -> 2.5.1 is a minor update
	 *
	 * @return bool
	 */
	public function isMajorUpdate(): bool {
		$previousVersion = explode( '.', $this->previousVersion );
		$currentVersion  = explode( '.', $this->currentVersion );

		if ( $previousVersion[0] < $currentVersion[0] ) {
			return true;
		}

		if ( $previousVersion[1] < $currentVersion[1] ) {
			return true;
		}

		return false;
	}

	/**
	 * Gets location position for locations without coordinates.
	 */
	public static function updateLocationCoordinates(): void {
		$locations = \CommonsBooking\Repository\Location::get();

		foreach ( $locations as $location ) {
			if ( ! ( $location->getMeta( 'geo_latitude' ) && $location->getMeta( 'geo_longitude' ) ) ) {
				$location->updateGeoLocation();
			}
		}
	}

	/**
	 * sets advance booking days to default value for existing timeframes.
	 * Advances booking timeframes are available since 2.6 - all timeframes created prior to this version need to have this value set to a default value.
	 * @return void
	 * @see \CommonsBooking\Wordpress\CustomPostType\Timeframe::ADVANCE_BOOKING_DAYS
	 *
	 * @since 2.6
	 */
	public static function setAdvanceBookingDaysDefault(): void {
		$timeframes = \CommonsBooking\Repository\Timeframe::getBookable( [], [], null, true );

		foreach ( $timeframes as $timeframe ) {
			if ( $timeframe->getMeta( Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS ) < 1 ) {
				update_post_meta( $timeframe->ID, Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS, strval( \CommonsBooking\Wordpress\CustomPostType\Timeframe::ADVANCE_BOOKING_DAYS ) );
			}
		}
	}

	/**
	 * Fixing #1357. The holiday timeframe field had postmeta that would make
	 * it get filtered out through our GET functions and not display holidays correctly.
	 * Therefore, we iterate ovr our timeframes and remove the breaking postmeta.
	 *
	 * This function is labour intensive and runs in AJAX.
	 *
	 * @return int|bool
	 * @since 2.8.5
	 */
	public static function removeBreakingPostmeta( int $page = 1 ) {
		$response   = \CommonsBooking\Repository\Timeframe::getAllPaginated( $page, self::POSTS_PER_ITERATION );
		$timeframes = $response->posts;
		foreach ( $timeframes as $timeframe ) {
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::removeIrrelevantPostmeta( $timeframe );
		}

		return $response->done ? true : $page + 1;
	}

	/**
	 * reset greyed out color when upgrading, see issue #1121
	 *
	 * @return void
	 * @since 2.8.2
	 */
	public static function resetBrokenColorScheme(): void {
		Settings::updateOption( 'commonsbooking_options_templates', 'colorscheme_greyedoutcolor', '#e0e0e0' );
		Settings::updateOption( 'commonsbooking_options_templates', 'colorscheme_lighttext', '#a0a0a0' );
	}

	/**
	 * reset iCalendar Titles when upgrading, see issue #1251
	 *
	 * @return void
	 * @since 2.8.2
	 */
	public static function fixBrokenICalTitle(): void {
		$eventTitle      = Settings::getOption( 'commonsbooking_options_templates', 'emailtemplates_mail-booking_ics_event-title' );
		$otherEventTitle = Settings::getOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options', 'event_title' );
		if ( str_contains( $eventTitle, 'post_name' ) ) {
			$updatedString = str_replace( 'post_name', 'post_title', $eventTitle );
			Settings::updateOption( 'commonsbooking_options_templates', 'emailtemplates_mail-booking_ics_event-title', $updatedString );
		}
		if ( str_contains( $otherEventTitle, 'post_name' ) ) {
			$updatedString = str_replace( 'post_name', 'post_title', $otherEventTitle );
			Settings::updateOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_advanced-options', 'event_title', $updatedString );
		}
	}


	/**
	 * sets the default value for multi selection to manual in all existing timeframes.
	 * Multi selection for timeframes are available since 2.9 (estimated) - all timeframes created prior to this version need to have a value for selection
	 *
	 * This function is labour intensive and runs in AJAX.
	 *
	 * @param int $page
	 *
	 * @return int|bool
	 * @since 2.9
	 */
	public static function setMultiSelectTimeFrameDefault( int $page = 1 ) {
		$response   = \CommonsBooking\Repository\Timeframe::getAllPaginated( $page, self::POSTS_PER_ITERATION );
		$timeframes = $response->posts;
		foreach ( $timeframes as $timeframe ) {
			if ( empty( $timeframe->getMeta( \CommonsBooking\Model\Timeframe::META_ITEM_SELECTION_TYPE ) ) ) {
				update_post_meta( $timeframe->ID, \CommonsBooking\Model\Timeframe::META_ITEM_SELECTION_TYPE, \CommonsBooking\Model\Timeframe::SELECTION_MANUAL_ID );
			}
			if ( empty( $timeframe->getMeta( \CommonsBooking\Model\Timeframe::META_LOCATION_SELECTION_TYPE ) ) ) {
				update_post_meta( $timeframe->ID, \CommonsBooking\Model\Timeframe::META_LOCATION_SELECTION_TYPE, \CommonsBooking\Model\Timeframe::SELECTION_MANUAL_ID );
			}
		}

		return $response->done ? true : $page + 1;
	}

	/**
	 * Previously, if a location email was set that meant that they also receive a copy of each booking / cancellation email.
	 * Now we have a separate checkbox to enable that which should be enabled for existing locations so that they will still receive emails after upgrade.
	 *
	 * @since 2.9.2
	 * @return void
	 */
	public static function enableLocationBookingNotification() {
		$locations = \CommonsBooking\Repository\Location::get();

		foreach ($locations as $location) {
			update_post_meta($location->ID, COMMONSBOOKING_METABOX_PREFIX . 'location_email_bcc', 'on');
		}
	}
}
