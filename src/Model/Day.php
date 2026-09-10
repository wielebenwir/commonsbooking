<?php

namespace CommonsBooking\Model;

use CommonsBooking\Helper\Wordpress;
use CommonsBooking\Plugin;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use DateTime;
use Exception;
use WP_Post;

/**
 * Serves as abstraction for days of a week in the Week object of the Calendar.
 * Computes booking slots according to the timeframes available.
 *
 * @see Week
 */
class Day {

	private const MINUTES_PER_DAY = 1440;

	private const MINUTES_PER_CELL = 15;

	/**
	 * @var string
	 */
	protected $date;

	/**
	 * @var array
	 */
	protected $locations;

	/**
	 * @var array
	 */
	protected $items;

	/**
	 * @var array|mixed
	 */
	protected $types;

	/**
	 * @var \CommonsBooking\Model\Timeframe[]|null
	 */
	protected ?array $timeframes = null;

	/**
	 * When this is enabled, restrictions are ignored when creating availabilities.
	 * This is useful when you want to differentiate between an item that is booked / not available
	 * and an item that would be bookable but is in repair.
	 *
	 * Used in @see \CommonsBooking\API\GBFS\VehicleStatus to differentiate between booked items and disabled items
	 *
	 * @var bool
	 */
	protected bool $ignoreRestrictions = false;

	/**
	 * Day constructor.
	 *
	 * @param string $date
	 * @param array  $locations
	 * @param array  $items
	 * @param array  $types
	 * @param array  $possibleTimeframes
	 */
	public function __construct( string $date, array $locations = [], array $items = [], array $types = [], array $possibleTimeframes = [] ) {
		$this->date      = $date;
		$this->locations = array_map(
			function ( $location ) {
				return $location instanceof WP_Post ? $location->ID : $location;
			},
			$locations
		);
		$this->items     = array_map(
			function ( $item ) {
				return $item instanceof WP_Post ? $item->ID : $item;
			},
			$items
		);

		$this->types = $types;

		if ( ! empty( $possibleTimeframes ) ) {
			$this->timeframes = \CommonsBooking\Repository\Timeframe::filterTimeframesForTimerange( $possibleTimeframes, $this->getStartTimestamp(), $this->getEndTimestamp() );
			$this->timeframes = array_filter( $this->timeframes, fn( $timeframe ) => $this->filterTimeframe( $timeframe ) );
		}
	}

	/**
	 * @return false|string
	 */
	public function getDayOfWeek() {
		return date( 'w', strtotime( $this->getDate() ) );
	}

	/**
	 * @return DateTime
	 * @throws Exception
	 */
	public function getDateObject(): DateTime {
		return Wordpress::getUTCDateTime( $this->getDate() );
	}

	/**
	 * Gets the date in Y-m-d format.
	 *
	 * @return string
	 */
	public function getDate(): string {
		return date( 'Y-m-d', strtotime( $this->date ) );
	}

	/**
	 * Returns formatted date.
	 *
	 * @param $format string Date format
	 *
	 * @return false|string
	 */
	public function getFormattedDate( string $format ) {
		return date( $format, strtotime( $this->getDate() ) );
	}

	/**
	 * Returns name of the day.
	 *
	 * @return false|string
	 */
	public function getName() {
		return date( 'l', strtotime( $this->getDate() ) );
	}

	/**
	 * Returns array with timeframes relevant for the Day.
	 * This function will only be able to run once.
	 * When on the first try, no Timeframes are found, it will set it to an empty array
	 *
	 * @return \CommonsBooking\Model\Timeframe[]
	 * @throws Exception
	 */
	public function getTimeframes(): array {
		if ( $this->timeframes === null ) {
			$timeFrames = \CommonsBooking\Repository\Timeframe::get(
				$this->locations,
				$this->items,
				$this->types,
				$this->getDate(),
				true,
				null,
				[ 'publish', 'confirmed' ]
			);

			// check if user is allowed to book this timeframe and remove unallowed timeframes from array
			// OR: Check for repetition timeframe selected days
			$timeFrames = array_filter( $timeFrames, fn( $timeframe ) => $this->filterTimeframe( $timeframe ) );

			$this->timeframes = $timeFrames;
		}

		return $this->timeframes;
	}

	/**
	 * Returns array with restrictions.
	 *
	 * @return Restriction[]
	 * @throws Exception
	 */
	public function getRestrictions(): array {
		return \CommonsBooking\Repository\Restriction::get(
			$this->locations,
			$this->items,
			$this->getDate(),
			true,
			null,
			[ 'publish', 'confirmed', 'unconfirmed' ]
		);
	}

	/**
	 * Returns grid for the day defined by the timeframes.
	 *
	 * @see Day::getTimeframeSlots()
	 * @return array
	 * @throws Exception
	 */
	public function getGrid(): array {
		return $this->getTimeframeSlots();
	}

	/**
	 * Returns the slot number for specific timeframe and time.
	 *
	 * @param DateTime $date        Date to map.
	 * @param int      $grid Minutes per availability cell.
	 *
	 * @return int
	 */
	protected function getSlotByTime( DateTime $date, int $grid ): int {
		$minutesSinceMidnight = (int) $date->format( 'G' ) * 60 + (int) $date->format( 'i' );

		return intdiv( $minutesSinceMidnight, $grid );
	}

	/**
	 * Returns start-slot id.
	 *
	 * @param int                             $grid      Minutes per availability cell.
	 * @param \CommonsBooking\Model\Timeframe $timeframe Timeframe to map.
	 *
	 * @return int
	 * @throws Exception
	 */
	protected function getStartSlot( int $grid, \CommonsBooking\Model\Timeframe $timeframe ): int {
		// Timeframe
		$fullDay   = $timeframe->isFullDay();
		$startTime = $timeframe->getStartTimeDateTime();
		$startSlot = $this->getSlotByTime( $startTime, $grid );

		// If we have an overbooked day, we need to mark all slots as booked
		if ( $timeframe->getType() === Timeframe::BOOKING_ID ) {
			$booking          = new Booking( $timeframe->getPost() );
			$startDateBooking = $booking->getStartDate();
			$startDateDay     = strtotime( $this->getDate() );

			// if booking starts on day before, we set startslot to 0
			if ( $startDateBooking < $startDateDay ) {
				$startSlot = 0;
			}
		}

		// If timeframe is full day, it starts at slot 0
		if ( $fullDay ) {
			$startSlot = 0;
		}

		return $startSlot;
	}

	/**
	 * Returns start slot for restriction.
	 *
	 * @param int         $grid        Minutes per availability cell.
	 * @param Restriction $restriction Timeframe restriction to map.
	 *
	 * @return int
	 */
	protected function getRestrictionStartSlot( int $grid, Restriction $restriction ): int {

		$startTime = $restriction->getStartTimeDateTime();
		$startSlot = $this->getSlotByTime( $startTime, $grid );

		$startDateBooking = $restriction->getStartDate();
		$startDateDay     = strtotime( $this->getDate() );

		// if restriction starts on day before, we set startslot to 0
		if ( $startDateBooking < $startDateDay ) {
			$startSlot = 0;
		}

		return $startSlot;
	}

	/**
	 * Returns end-slot id.
	 *
	 * @param array                           $slots     Availability cells to map.
	 * @param int                             $grid      Minutes per availability cell.
	 * @param \CommonsBooking\Model\Timeframe $timeframe Timeframe to map.
	 *
	 * @return int
	 * @throws Exception
	 */
	protected function getEndSlot( array $slots, int $grid, \CommonsBooking\Model\Timeframe $timeframe ): int {
		// Timeframe
		$endTime = $timeframe->getEndTimeDateTime( $this->getDateObject()->getTimestamp() );
		$endDate = $timeframe->getEndDateDateTime();

		// Slots
		$endSlot = count( $slots );

		// If timeframe isn't configured as full day
		if ( ! $timeframe->isFullDay() ) {
			$endSlot = $this->getSlotByTime( $endTime, $grid );
		}

		if ( $endDate && $endDate->getTimestamp() === $this->getEndTimestamp() ) {
			$endSlot = count( $slots );
		}

		// If we have a overbooked day, we need to mark all slots as booked
		if ( ! $timeframe->isOverBookable() && ! empty( $endDate ) && $timeframe->getRepetition() == 'norep' ) {
			// Check if timeframe ends after the current day
			if ( strtotime( $this->getFormattedDate( 'd.m.Y 23:59:59' ) ) < $endDate->getTimestamp() ) {
				$endSlot = count( $slots );
			}
		}

		return $endSlot;
	}

	/**
	 * Returns end slot for restriction.
	 *
	 * @param array       $slots       Availability cells to map.
	 * @param int         $grid        Minutes per availability cell.
	 * @param Restriction $restriction Timeframe restriction to map.
	 *
	 * @return int
	 * @throws Exception
	 */
	protected function getRestrictionEndSlot( array $slots, int $grid, Restriction $restriction ): int {
		$endTime = $restriction->getEndTimeDateTime( $this->getDateObject()->getTimestamp() );
		$endDate = $restriction->getEndDateDateTime();

		// Slots
		$endSlot = $this->getSlotByTime( $endTime, $grid );

		if ( $endDate->getTimestamp() === $this->getEndTimestamp() ) {
			$endSlot = count( $slots );
		}

		// Check if timeframe ends after the current day
		if ( strtotime( $this->getFormattedDate( 'd.m.Y 23:59' ) ) < $endDate->getTimestamp() ) {
			$endSlot = count( $slots );
		}

		return $endSlot;
	}

	/**
	 * Checks if timeframe is relevant for current day/date.
	 *
	 * @param \CommonsBooking\Model\Timeframe $timeframe
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function isInTimeframe( \CommonsBooking\Model\Timeframe $timeframe ): bool {

		if ( $timeframe->getRepetition() ) {
			switch ( $timeframe->getRepetition() ) {
				// Weekly Rep
				case 'w':
					$dayOfWeek         = intval( $this->getDateObject()->format( 'w' ) );
					$timeframeWeekdays = get_post_meta( $timeframe->ID, 'weekdays', true );

					// Because of different day of week calculation we need to recalculate
					if ( $dayOfWeek == 0 ) {
						$dayOfWeek = 7;
					}

					if ( is_array( $timeframeWeekdays ) && in_array( $dayOfWeek, $timeframeWeekdays ) ) {
						return true;
					} else {
						return false;
					}

					// Monthly Rep
				case 'm':
					$dayOfMonth               = intval( $this->getDateObject()->format( 'j' ) );
					$timeframeStartDayOfMonth = date( 'j', $timeframe->getStartDate() );

					if ( $dayOfMonth == $timeframeStartDayOfMonth ) {
						return true;
					} else {
						return false;
					}

					// Yearly Rep
				case 'y':
					$date          = intval( $this->getDateObject()->format( 'dm' ) );
					$timeframeDate = date( 'dm', $timeframe->getStartDate() );
					if ( $date == $timeframeDate ) {
						return true;
					} else {
						return false;
					}

					// Manual Rep
				case 'manual':
					return in_array( $this->getDate(), $timeframe->getManualSelectionDates() );

				// No Repetition
				case 'norep':
					$timeframeStartTimestamp = intval( $timeframe->getMeta( \CommonsBooking\Model\Timeframe::REPETITION_START ) );
					$timeframeEndTimestamp   = intval( $timeframe->getMeta( \CommonsBooking\Model\Timeframe::REPETITION_END ) );

					$currentDayStartTimestamp = strtotime( 'midnight', $this->getDateObject()->getTimestamp() );
					$currentDayEndTimestamp   = strtotime( '+1 day midnight', $this->getDateObject()->getTimestamp() ) - 1;

					$timeframeStartsBeforeEndOfToday = $timeframeStartTimestamp <= $currentDayEndTimestamp;
					$timeframeEndsAfterStartOfToday  = $timeframeEndTimestamp >= $currentDayStartTimestamp;

					if ( ! $timeframeEndTimestamp ) {
						return $timeframeStartsBeforeEndOfToday;
					} else {
						return $timeframeStartsBeforeEndOfToday && $timeframeEndsAfterStartOfToday;
					}
			}
		}

		return true;
	}

	/**
	 * Can be used as a callback to filter timeframes if they belong
	 * to the day and are bookable for the current user
	 *
	 * @param \CommonsBooking\Model\Timeframe $timeframe
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function filterTimeframe( \CommonsBooking\Model\Timeframe $timeframe ): bool {
		if ( ! $this->isInTimeframe( $timeframe ) ) {
			return false;
		}
		if ( ! commonsbooking_isCurrentUserAllowedToBook( $timeframe->ID ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Maps timeframes to timeslots.
	 *
	 * @param array $slots untyped structure of timeframe slot information
	 *
	 * @throws Exception
	 */
	protected function mapTimeFrames( array &$slots ) {
		$grid = intdiv( self::MINUTES_PER_DAY, count( $slots ) );

		// Iterate through timeframes and fill slots
		foreach ( $this->getTimeframes() as $timeframe ) {
			// Slots
			$startSlot = $this->getStartSlot( $grid, $timeframe );
			$endSlot   = $this->getEndSlot( $slots, $grid, $timeframe );

			// Add timeframe to relevant slots
			while ( $startSlot < $endSlot ) {
				// Set locked property
				$timeframePost         = $timeframe->getPost();
				$timeframePost->locked = $timeframe->isLocked();

				if ( ! array_key_exists( 'timeframe', $slots[ $startSlot ] ) || ! $slots[ $startSlot ]['timeframe'] ) {
					$slots[ $startSlot ]['timeframe']   = $timeframePost;
					$slots[ $startSlot ]['gridMinutes'] = $timeframe->getGridMinutes();
				} else {
					$winningTimeframe = Timeframe::getHigherPrioFrame( $timeframePost, $slots[ $startSlot ]['timeframe'] );
					if ( $winningTimeframe === $timeframePost ) {
						$slots[ $startSlot ]['timeframe']   = $timeframePost;
						$slots[ $startSlot ]['gridMinutes'] = $timeframe->getGridMinutes();
					}
				}

				++$startSlot;
			}
		}
	}

	/**
	 * Overwrites restricted slots
	 *
	 * @param array $slots
	 *
	 * @throws Exception
	 */
	protected function mapRestrictions( array &$slots ) {
		$grid = intdiv( self::MINUTES_PER_DAY, count( $slots ) );

		// Iterate through timeframes and fill slots
		/** @var Restriction $restriction */
		foreach ( $this->getRestrictions() as $restriction ) {

			// Only if there is a repair we block the timeframe
			if ( $restriction->isActive() && $restriction->getType() == Restriction::TYPE_REPAIR ) {
				// Slots
				$startSlot = $this->getRestrictionStartSlot( $grid, $restriction );
				$endSlot   = $this->getRestrictionEndSlot( $slots, $grid, $restriction );

				// Add timeframe to relevant slots
				while ( $startSlot < $endSlot ) {
					// Set locked property
					$restrictionPost                    = $restriction->getPost();
					$restrictionPost->locked            = true;
					$slots[ $startSlot ]['timeframe']   = $restrictionPost;
					$slots[ $startSlot ]['gridMinutes'] = 0;
					++$startSlot;
				}
			}
		}
	}

	public function getStartTimestamp(): int {
		$dt = new DateTime( $this->getDate() );
		$dt->modify( 'midnight' );

		return $dt->getTimestamp();
	}

	public function getEndTimestamp(): int {
		$dt = new DateTime( $this->getDate() );
		$dt->modify( '23:59:59' );

		return $dt->getTimestamp();
	}


	/**
	 * Removes empty cells and groups contiguous cells according to their timeframe's grid.
	 *
	 * @param array $slots Given an array of assocs in fifteen-minute cell resolution.
	 */
	protected function sanitizeSlots( array &$slots ) {
		$this->removeEmptySlots( $slots );

		$sanitizedSlots = [];
		$run            = [];
		$previousSlotNr = null;
		$timeframeId    = null;

		foreach ( $slots as $slotNr => $slot ) {
			$slot['slotNr']     = $slotNr;
			$currentTimeframeId = $slot['timeframe']->ID;
			if ( $run && ( $slotNr !== $previousSlotNr + 1 || $currentTimeframeId !== $timeframeId ) ) {
				$this->appendSanitizedRun( $run, $sanitizedSlots );
				$run = [];
			}

			$run[]          = $slot;
			$previousSlotNr = $slotNr;
			$timeframeId    = $currentTimeframeId;
		}
		if ( $run ) {
			$this->appendSanitizedRun( $run, $sanitizedSlots );
		}

		$slots = $sanitizedSlots;
	}

	/**
	 * Merges a contiguous timeframe run into its configured grid size.
	 *
	 * @param non-empty-array $run
	 * @param array           $sanitizedSlots
	 */
	protected function appendSanitizedRun( array $run, array &$sanitizedSlots ): void {
		$timeframe    = $run[0]['timeframe'];
		$gridMinutes  = $run[0]['gridMinutes'];
		$slotsPerGrid = get_post_meta( $timeframe->ID, 'full-day', true ) === 'on' || 0 === $gridMinutes ?
			count( $run ) :
			intdiv( $gridMinutes, self::MINUTES_PER_CELL );

		foreach ( array_chunk( $run, $slotsPerGrid ) as $group ) {
			$slot                 = $group[0];
			$lastSlot             = $group[ count( $group ) - 1 ];
			$slot['timeend']      = $lastSlot['timeend'];
			$slot['timestampend'] = $lastSlot['timestampend'];
			unset( $slot['slotNr'], $slot['gridMinutes'] );
			$sanitizedSlots[ $lastSlot['slotNr'] ] = $slot;
		}
	}

	/**
	 * remove slots without timeframes
	 *
	 * @param $slots
	 */
	protected function removeEmptySlots( &$slots ) {
		// remove slots without timeframes
		foreach ( $slots as $slotNr => $slot ) {
			if ( ! array_key_exists( 'timeframe', $slot ) || ! ( $slot['timeframe'] instanceof WP_Post ) ) {
				unset( $slots[ $slotNr ] );
			}
		}
	}

	/**
	 * Returns an array of timeslots, which is built according to the relevant timeframes and their configuration.
	 * So this takes the hourly-, daily or custom-sized-slot configuration of timeframes into account.
	 *
	 * Implementation note: A fifteen-minute resolution is used, but as a last step, the cells are merged into
	 * the representation that is configured in the timeframes.
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function getTimeframeSlots(): array {
		$slotsPerDay    = intdiv( self::MINUTES_PER_DAY, self::MINUTES_PER_CELL );
		$customCacheKey = $this->getDate() . serialize( $this->items ) . serialize( $this->locations ) . serialize( $this->ignoreRestrictions ) . $slotsPerDay;
		$customCacheKey = md5( $customCacheKey );
		$cacheItem      = Plugin::getCacheItem( $customCacheKey );
		if ( $cacheItem ) {
			return $cacheItem;
		} else {
			$slots      = [];
			$timeFormat = esc_html( get_option( 'time_format' ) );

			// Init slots.
			for ( $i = 0; $i < $slotsPerDay; $i++ ) {
				$slots[ $i ] = [
					'timestart'      => date( $timeFormat, $i * self::MINUTES_PER_CELL * 60 ),
					'timeend'        => date( $timeFormat, ( $i + 1 ) * self::MINUTES_PER_CELL * 60 ),
					'timestampstart' => $this->getSlotTimestampStart( $i ),
					'timestampend'   => $this->getSlotTimestampEnd( $i ),
				];
			}

			$this->mapTimeFrames( $slots );
			if ( ! $this->ignoreRestrictions ) {
				$this->mapRestrictions( $slots );
			}
			$this->sanitizeSlots( $slots );

			Plugin::setCacheItem(
				$slots,
				Wordpress::getTags( $this->getTimeframes(), $this->items, $this->locations ),
				$customCacheKey
			);

			return $slots;
		}
	}

	/**
	 * Returns timestamp when $slotNr starts.
	 *
	 * @return int
	 */
	protected function getSlotTimestampStart( int $slotNr ): int {
		return $this->getStartTimestamp() + $slotNr * self::MINUTES_PER_CELL * 60;
	}

	/**
	 * Returns timestamp when $slotNr ends.
	 *
	 * @return int
	 */
	protected function getSlotTimestampEnd( int $slotNr ): int {
		return $this->getStartTimestamp() + ( $slotNr + 1 ) * self::MINUTES_PER_CELL * 60 - 1;
	}

	public function setIgnoreRestrictions( bool $ignoreRestrictions ): void {
		$this->ignoreRestrictions = $ignoreRestrictions;
	}
}
