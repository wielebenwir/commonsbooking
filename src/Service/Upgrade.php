<?php

namespace CommonsBooking\Service;

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
			[\CommonsBooking\Migration\Booking::class, 'migrate'],
			[self::class, 'setAdvanceBookingDaysDefault']
		],
		'2.8.0' => [
			[\CommonsBooking\Service\Scheduler::class, 'unscheduleOldEvents']
		],
		'2.8.2' => [
			[self::class, 'resetBrokenColorScheme'],
			[self::class, 'fixBrokenICalTitle']
		],
		'2.8.5' => [
			[self::class, 'removeBreakingPostmeta']
		],
		'2.9.0' => [
			[self::class, 'setMultiSelectTimeFrameDefault']
		],
		'2.9.2' => [ 
			[self::class, 'enableLocationBookingNotification']
		]
	];

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
		if ( ! empty( $this->previousVersion ) && ( $this->previousVersion === $this->currentVersion ) ) {
			return false;
		}

		// run upgrade tasks that are specific for version updates and should only run once
		$this->runUpgradeTasks();

		// the following tasks will be run on every update

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
		return true;
	}

	/**
	 * This runs the tasks that are specific for version updates and should only run once.
	 *
	 * @return void
	 */
	public function runUpgradeTasks() : void {
		// TODO let thirdparty plugins be able to hook into this part, then they don't have to add their own implementation of this class
		foreach ( self::$upgradeTasks as $version => $tasks ) {
			if ( version_compare( $this->previousVersion, $version, '<' ) && version_compare( $this->currentVersion, $version, '>=' ) ) {
				foreach ( $tasks as $task ) {
					list($className, $methodName) = $task;
					call_user_func( array( $className, $methodName ) );
				}
			}
		}
	}

	/**
	 * This function will determine if the plugin has been updated and run the upgrade tasks if necessary.
	 *
	 * @return void
	 */
	public static function runTasksAfterUpdate() : void {
		$upgrade = new Upgrade(
			esc_html( get_option( self::VERSION_OPTION ) ),
			COMMONSBOOKING_VERSION
		);
		$upgrade->run();
	}

	/**
	 * renders a custom update notice in plugin list if the version number increases
	 * in a major release e.g. 2.5 -> 2.6
	 * This is a warning to users BEFORE they update to a new version.
	 *
	 * @return void (but renders html)
	 */
	public function updateNotice() : void {
		if ( ! $this->isMajorUpdate() ) {
			return;
		}
		?>
		<hr class="cb-major-update-warning__separator" />
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
	public function isMajorUpdate() : bool {
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
	public static function updateLocationCoordinates() : void {
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
	 * @since 2.6
	 * @see \CommonsBooking\Wordpress\CustomPostType\Timeframe::ADVANCE_BOOKING_DAYS
	 *
	 * @return void
	 */
	public static function setAdvanceBookingDaysDefault() : void {
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
	 * @since 2.8.5
	 * @return void
	 */
	public static function removeBreakingPostmeta() {
		$timeframes = \CommonsBooking\Repository\Timeframe::get(
			[],
			[],
			[],
			null,
			true
		);
		foreach ($timeframes as $timeframe) {
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::removeIrrelevantPostmeta($timeframe);
		}
	}

	/**
	 * reset greyed out color when upgrading, see issue #1121
	 *
	 * @since 2.8.2
	 * @return void
	 */
	public static function resetBrokenColorScheme() : void {
		Settings::updateOption( 'commonsbooking_options_templates', 'colorscheme_greyedoutcolor', '#e0e0e0' );
		Settings::updateOption( 'commonsbooking_options_templates', 'colorscheme_lighttext', '#a0a0a0' );
	}

	/**
	 * reset iCalendar Titles when upgrading, see issue #1251
	 *
	 * @since 2.8.2
	 * @return void
	 */
	public static function fixBrokenICalTitle() : void {
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
	 * @since 2.9
	 * @return void
	 * @throws InvalidArgumentException
	 */
	public static function setMultiSelectTimeFrameDefault() {
		$timeframes = \CommonsBooking\Repository\Timeframe::get( [],[],[], null, true );

		foreach ($timeframes as $timeframe) {
			if ( empty($timeframe->getMeta(\CommonsBooking\Model\Timeframe::META_ITEM_SELECTION_TYPE ) ) ) {
				update_post_meta($timeframe->ID, \CommonsBooking\Model\Timeframe::META_ITEM_SELECTION_TYPE, \CommonsBooking\Model\Timeframe::SELECTION_MANUAL_ID);
			}
			if ( empty($timeframe->getMeta(\CommonsBooking\Model\Timeframe::META_LOCATION_SELECTION_TYPE ) ) ) {
				update_post_meta($timeframe->ID, \CommonsBooking\Model\Timeframe::META_LOCATION_SELECTION_TYPE, \CommonsBooking\Model\Timeframe::SELECTION_MANUAL_ID);
			}
		}
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
