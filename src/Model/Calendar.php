<?php

namespace CommonsBooking\Model;

use CommonsBooking\Helper\Wordpress;
use CommonsBooking\Plugin;
use stdClass;

/**
 * Represents a span of weeks, which is used to display a calendar.
 *
 * @uses Week
 */
class Calendar {

	/**
	 * @var Day
	 */
	protected Day $startDate;

	/**
	 * @var Day
	 */
	protected Day $endDate;

	/**
	 * @var int[]
	 */
	protected array $items;

	/**
	 * @var int[]
	 */
	protected array $locations;

	/**
	 * @var array
	 */
	protected $types;

	/**
	 * The timeframes that are relevant for this calendar.
	 *
	 * @var Timeframe[]
	 */
	protected array $timeframes;

	/**
	 * When this is enabled, @see Timeframe::META_BOOKING_START_DAY_OFFSET is ignored.
	 * This is used for the API routes, where we want to show the actual availability of the items, regardless of the booking start day offset.
	 *
	 * @var bool
	 */
	protected bool $ignoreStartDayOffset = false;

	/**
	 * When this is enabled, restrictions are ignored when creating availabilities.
	 * This is useful when you want to differentiate between an item that is booked / not available
	 * and an item that would be bookable but is in repair.
	 *
	 * Just passed to the \CommonsBooking\Model\Day model, because restriction calculations are made there
	 *
	 * Used in @see \CommonsBooking\API\GBFS\VehicleStatus to differentiate between booked items and disabled items
	 *
	 * @var bool
	 */
	protected bool $ignoreRestrictions = false;

	/**
	 * Calendar constructor.
	 *
	 * @param Day   $startDate
	 * @param Day   $endDate
	 * @param int[] $locations
	 * @param int[] $items
	 * @param array $types
	 */
	public function __construct( Day $startDate, Day $endDate, array $locations = [], array $items = [], array $types = [] ) {
		// check, that it spans at least two days
		if ( $startDate->getDate() == $endDate->getDate() ) {
			throw new \InvalidArgumentException( 'Calendar must span at least two days' );
		}

		$startDate->setIgnoreRestrictions( $this->ignoreRestrictions );
		$endDate->setIgnoreRestrictions( $this->ignoreRestrictions );

		$this->startDate = $startDate;
		$this->endDate   = $endDate;
		$this->items     = $items;
		$this->locations = $locations;
		$this->types     = $types;

		$this->timeframes = \CommonsBooking\Repository\Timeframe::getInRange(
			$this->startDate->getStartTimestamp(),
			$this->endDate->getEndTimestamp(),
			$this->locations,
			$this->items,
			$this->types,
			true,
			[ 'confirmed', 'publish' ]
		);
	}

	/**
	 * Returns weeks for calendar time range.
	 *
	 * Uses cache and expires at midnight on a daily basis.
	 *
	 * @return array
	 */
	public function getWeeks(): array {
		$startDate = strtotime( $this->startDate->getDate() ) + 1;
		$endDate   = strtotime( $this->endDate->getDate() );

		$customId = md5(
			$startDate .
			$endDate .
			serialize( $this->items ) .
			serialize( $this->locations ) .
			serialize( $this->types )
		);

		$cacheItem = Plugin::getCacheItem( $customId );
		if ( $cacheItem ) {
			return $cacheItem;
		} else {
			$weeks = array();
			while ( $startDate <= $endDate ) {
				$dayOfYear = date( 'z', $startDate );
				$year      = date( 'Y', $startDate );
				$weeks[]   = new Week(
					$year,
					$dayOfYear,
					$this->locations,
					$this->items,
					$this->types,
					$this->timeframes
				);
				$startDate = strtotime( 'next monday', $startDate );
			}

			// set cache expiration to force daily fresh after midnight
			Plugin::setCacheItem( $weeks, array( 'misc' ), $customId, 'midnight' );

			return $weeks;
		}
	}

	/**
	 * Will retrieve the respective availability slots for a given calendar.
	 * This is used to display availabilities for the API routes.
	 *
	 * Because we process the calendar by weeks, at least two days are needed to get a valid calendar.
	 * The calendar does not consider the individual boundaries set by $startDate and $endDate but will always return a full week.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function getAvailabilitySlots(): array {
		$slots     = [];
		$doneSlots = [];
		/** @var Week $week */
		foreach ( $this->getWeeks() as $week ) {
			/** @var Day $day */
			foreach ( $week->getDays() as $day ) {
				$day->setIgnoreRestrictions( $this->ignoreRestrictions );
				foreach ( $day->getGrid() as $slot ) {
					$timeframe     = new Timeframe( $slot['timeframe'] );
					$timeFrameType = get_post_meta( $slot['timeframe']->ID, 'type', true );

					// Skip everything where the most important slot is not bookable. We are only interested in direct availability.
					if ( $timeFrameType != \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID ) {
						continue;
					}

					// Skip timeframes that are not bookable today
					if ( ! $this->ignoreStartDayOffset && $timeframe->getFirstBookableDay() > $day->getDate() ) {
						continue;
					}

					$availabilitySlot = new stdClass();

					// Init DateTime object for start
					$dateTimeStart = Wordpress::getDateTime( 'now' );
					$dateTimeStart->setTimestamp( $slot['timestampstart'] );
					$availabilitySlot->start = $dateTimeStart->format( 'Y-m-d\TH:i:sP' );

					// Init DateTime object for end
					$dateTimeend = Wordpress::getDateTime( 'now' );
					$dateTimeend->setTimestamp( $slot['timestampend'] );
					$availabilitySlot->end = $dateTimeend->format( 'Y-m-d\TH:i:sP' );

					$availabilitySlot->locationId = '';
					if ( $timeframe->getLocation() ) {
						$availabilitySlot->locationId = $timeframe->getLocationID() . '';
					}

					$availabilitySlot->itemId = '';
					if ( $timeframe->getItem() ) {
						$availabilitySlot->itemId = $timeframe->getItemID() . '';
					}

					$slotId = md5( serialize( $availabilitySlot ) );
					if ( ! in_array( $slotId, $doneSlots ) ) {
						$doneSlots[] = $slotId;
						$slots[]     = $availabilitySlot;
					}
				}
			}
		}
		return $slots;
	}

	public function setIgnoreStartDayOffset( bool $ignoreStartDayOffset ): void {
		$this->ignoreStartDayOffset = $ignoreStartDayOffset;
	}

	public function setIgnoreRestrictions( bool $ignoreRestrictions ): void {
		$this->ignoreRestrictions = $ignoreRestrictions;
	}
}
