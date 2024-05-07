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

	/**
	 * @var
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
	 * @var array
	 */
	protected $timeframes;

	/**
	 * Day constructor.
	 *
	 * @param string $date
	 * @param array $locations
	 * @param array $items
	 * @param array $types
	 */
	public function __construct( string $date, array $locations = [], array $items = [], array $types = [] ) {
		$this->date      = $date;
		$this->locations = array_map( function ( $location ) {
			return $location instanceof WP_Post ? $location->ID : $location;
		}, $locations );
		$this->items     = array_map( function ( $item ) {
			return $item instanceof WP_Post ? $item->ID : $item;
		}, $items );

		$this->types = $types;
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
	 * @return string
	 */
	public function getDate(): string {
		return $this->date;
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
	 * @return false|string
	 */
	public function getName() {
		return date( 'l', strtotime( $this->getDate() ) );
	}

	/**
	 * Returns array with timeframes relevant for the Day.
	 * This function will only be able to run once.
	 * When on the first try, no Timeframes are found, it will set it to an empty array
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
			foreach ( $timeFrames as $key => $timeframe ) {
				if ( ! commonsbooking_isCurrentUserAllowedToBook( $timeframe->ID ) ||
				     ! $this->isInTimeframe( $timeframe )) {
					unset( $timeFrames[ $key ] );
				}
			}

			$this->timeframes = $timeFrames;
		}

		return $this->timeframes;
	}

	/**
	 * Returns array with restrictions.
	 * @return array
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
	 * @param DateTime $date
	 * @param int $grid
	 *
	 * @return float|int
	 */
	protected function getSlotByTime( DateTime $date, int $grid ) {
		$hourSlots   = $date->format( 'H' ) / $grid;
		$minuteSlots = $date->format( 'i' ) / 60 / $grid;

		return $hourSlots + $minuteSlots;
	}

	/**
	 * Returns start-slot id.
	 *
	 * @param int $grid
	 * @param \CommonsBooking\Model\Timeframe $timeframe
	 *
	 * @return float|int
	 * @throws Exception
	 */
	protected function getStartSlot( int $grid, \CommonsBooking\Model\Timeframe $timeframe ) {
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
	 * @param int $grid
	 * @param Restriction $restriction
	 
	 * @return float|int
	 */
	protected function getRestrictionStartSlot( int $grid, \CommonsBooking\Model\Restriction $restriction ) {

		$startTime = $restriction->getStartTimeDateTime();
		$startSlot = $this->getSlotByTime( $startTime, $grid );

		$startDateBooking = intval( $restriction->getStartDate() );
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
	 * @param array $slots
	 * @param int $grid
	 * @param \CommonsBooking\Model\Timeframe $timeframe
	 *
	 * @return float|int
	 * @throws Exception
	 */
	protected function getEndSlot( array $slots, int $grid, \CommonsBooking\Model\Timeframe $timeframe ) {
		// Timeframe
		$fullDay = get_post_meta( $timeframe->ID, 'full-day', true );
		$endTime = $timeframe->getEndTimeDateTime( $this->getDateObject()->getTimestamp() );
		$endDate = $timeframe->getEndDateDateTime();

		// Slots
		$endSlot = count( $slots );

		// If timeframe isn't configured as full day
		if ( ! $fullDay ) {
			$endSlot = $this->getSlotByTime( $endTime, $grid );
		}

		// If we have a overbooked day, we need to mark all slots as booked
		if ( ! $timeframe->isOverBookable() && !empty( $endDate ) ) {
			// Check if timeframe ends after the current day
			if ( strtotime( $this->getFormattedDate( 'd.m.Y 23:59' ) ) < $endDate->getTimestamp() ) {
				$endSlot = count( $slots );
			}
		}

		return $endSlot;
	}

	/**
	 * Returns end slot for restriction.
	 *
	 * @param array $slots
	 * @param int $grid
	 * @param Restriction $restriction
	 *
	 * @return float|int
	 * @throws Exception
	 */
	protected function getRestrictionEndSlot( array $slots, int $grid, \CommonsBooking\Model\Restriction $restriction ) {
		$endTime = $restriction->getEndTimeDateTime( $this->getDateObject()->getTimestamp() );
		$endDate = $restriction->getEndDateDateTime();

		// Slots
		$endSlot = $this->getSlotByTime( $endTime, $grid );

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
				case "w":
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
				case "m":
					$dayOfMonth               = intval( $this->getDateObject()->format( 'j' ) );
					$timeframeStartDayOfMonth = date('j',$timeframe->getStartDate());

					if ( $dayOfMonth == $timeframeStartDayOfMonth ) {
						return true;
					} else {
						return false;
					}

				// Yearly Rep
				case "y":
					$date          = intval( $this->getDateObject()->format( 'dm' ) );
					$timeframeDate = date('dm',$timeframe->getStartDate());
					if ( $date == $timeframeDate ) {
						return true;
					} else {
						return false;
					}

				// Manual Rep
				case "manual":
					return in_array( $this->getDate(), $timeframe->getManualSelectionDates() );

				// No Repetition
				case "norep":
					$timeframeStartTimestamp = intval( $timeframe->getMeta( \CommonsBooking\Model\Timeframe::REPETITION_START ));
					$timeframeEndTimestamp   = intval( $timeframe->getMeta( \CommonsBooking\Model\Timeframe::REPETITION_END ));

					$currentDayStartTimestamp = strtotime('midnight', $this->getDateObject()->getTimestamp());
					$currentDayEndTimestamp = strtotime('+1 day midnight', $this->getDateObject()->getTimestamp()) - 1;

					$timeframeStartsBeforeEndOfToday = $timeframeStartTimestamp <= $currentDayEndTimestamp;
					$timeframeEndsAfterStartOfToday = $timeframeEndTimestamp >= $currentDayStartTimestamp;

					if(!$timeframeEndTimestamp) {
						return $timeframeStartsBeforeEndOfToday;
					} else {
						return $timeframeStartsBeforeEndOfToday && $timeframeEndsAfterStartOfToday;
					}
			}
		}

		return true;
	}

	/**
	 * Maps timeframes to timeslots.
	 *
	 * @param array $slots
	 *
	 * @throws Exception
	 */
	protected function mapTimeFrames( array &$slots ) {
		$grid = 24 / count( $slots );

		// Iterate through timeframes and fill slots
		/** @var \CommonsBooking\Model\Timeframe $timeframe */
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
					$slots[ $startSlot ]['timeframe'] = $timeframePost;
				} else {
					$slots[ $startSlot ]['timeframe'] = Timeframe::getHigherPrioFrame( $timeframePost, $slots[ $startSlot ]['timeframe'] );
				}

				$startSlot ++;
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
		$grid = 24 / count( $slots );

		// Iterate through timeframes and fill slots
		/** @var \CommonsBooking\Model\Restriction $restriction */
		foreach ( $this->getRestrictions() as $restriction ) {

			// Only if there is a repair we block the timeframe
			if ( $restriction->isActive() && $restriction->getType() == Restriction::TYPE_REPAIR ) {
				// Slots
				$startSlot = $this->getRestrictionStartSlot( $grid, $restriction );
				$endSlot   = $this->getRestrictionEndSlot( $slots, $grid, $restriction );

				// Add timeframe to relevant slots
				while ( $startSlot < $endSlot ) {
					// Set locked property
					$restrictionPost                  = $restriction->getPost();
					$restrictionPost->locked          = true;
					$slots[ $startSlot ]['timeframe'] = $restrictionPost;
					$startSlot ++;
				}
			}
		}
	}

	/**
	 * Remove empty and merge connected slots.
	 *
	 * @param array $slots Given an array of assocs in hourly slot resolution.
	 */
	protected function sanitizeSlots( array &$slots ) {
		$this->removeEmptySlots( $slots );

		// merge multiple slots if they are of same type
		foreach ( $slots as $slotNr => $slot ) {
			if ( ! array_key_exists( $slotNr - 1, $slots ) ) {
				continue;
			}
			$slotBefore = $slots[ $slotNr - 1 ];

			// If Slot before is of same timeframe and we have no hourly grid, we merge them.
			if (
				$slotBefore &&
				$slotBefore['timeframe']->ID == $slot['timeframe']->ID &&
				(
					get_post_meta( $slot['timeframe']->ID, 'full-day', true ) == 'on' ||
					get_post_meta( $slot['timeframe']->ID, 'grid', true ) == 0
				)
			) {
				// Take over start time from slot before
				$slots[ $slotNr ]['timestart']      = $slotBefore['timestart'];
				$slots[ $slotNr ]['timestampstart'] = $slotBefore['timestampstart'];

				// unset timeframe from slot before
				unset( $slots[ $slotNr - 1 ]['timeframe'] );
			}
		}

		$this->removeEmptySlots( $slots );
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
	 * Returns an array of timeslots, which is build according the relevant timeframes and their configuration.
	 * So this takes the hourly-, daily or custom-sized-slot configuration of timeframes into account.
	 *
	 * Implementation note: An hourly resolution is used, but as a last step, the hourly slots are merged into
	 * the representation that is configured in the timeframes.
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function getTimeframeSlots(): array {
		$customCacheKey = $this->getDate() . serialize( $this->items ) . serialize( $this->locations );
		$customCacheKey = md5( $customCacheKey );
		$cacheItem     = Plugin::getCacheItem( $customCacheKey );
		if ( $cacheItem ) {
			return $cacheItem;
		} else {
			$slots       = [];
			$slotsPerDay = 24;

			// Init Slots
			for ( $i = 0; $i < $slotsPerDay; $i ++ ) {
				$slots[ $i ] = [
					'timestart'      => date( esc_html(get_option( 'time_format' )), $i * ( ( 24 / $slotsPerDay ) * 3600 ) ),
					'timeend'        => date( esc_html(get_option( 'time_format' )), ( $i + 1 ) * ( ( 24 / $slotsPerDay ) * 3600 ) ),
					'timestampstart' => $this->getSlotTimestampStart( $slotsPerDay, $i ),
					'timestampend'   => $this->getSlotTimestampEnd( $slotsPerDay, $i )
				];
			}

			$this->mapTimeFrames( $slots );
			$this->mapRestrictions( $slots );
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
	 * @param $slotsPerDay
	 * @param $slotNr
	 *
	 * @return false|float|int
	 */
	protected function getSlotTimestampStart( $slotsPerDay, $slotNr ) {
		return strtotime( $this->getDate() ) + ( $slotNr * ( ( 24 / $slotsPerDay ) * 3600 ) );
	}

	/**
	 * Returns timestamp when $slotNr ends.
	 *
	 * @param $slotsPerDay
	 * @param $slotNr
	 *
	 * @return false|float|int
	 */
	protected function getSlotTimestampEnd( $slotsPerDay, $slotNr ) {
		return strtotime( $this->getDate() ) + ( ( $slotNr + 1 ) * ( ( 24 / $slotsPerDay ) * 3600 ) ) - 1;
	}

}
