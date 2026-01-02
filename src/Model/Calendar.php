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
	 *
	 * TODO seems unused in php-code, always empty list, maybe remove
	 */
	protected array $types; // @phpstan-ignore missingType.iterableValue

	/**
	 * The timeframes that are relevant for this calendar.
	 *
	 * @var Timeframe[]
	 */
	protected array $timeframes;

	/**
	 * Calendar constructor.
	 *
	 * @param Day   $startDate
	 * @param Day   $endDate
	 * @param int[] $locations
	 * @param int[] $items
	 * @param array $types // @phpstan-ignore missingType.iterableValue
	 */
	public function __construct( Day $startDate, Day $endDate, array $locations = [], array $items = [], array $types = [] ) {
		// check, that it spans at least two days
		if ( $startDate->getDate() == $endDate->getDate() ) {
			throw new \InvalidArgumentException( 'Calendar must span at least two days' );
		}

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
	 * @return Week[]
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
	 * TODO add proper type for returning availability slots
	 *
	 * @return stdClass[]
	 * @throws \Exception
	 */
	public function getAvailabilitySlots(): array {
		$slots     = [];
		$doneSlots = [];
		/** @var Week $week */
		foreach ( $this->getWeeks() as $week ) {
			/** @var Day $day */
			foreach ( $week->getDays() as $day ) {
				foreach ( $day->getGrid() as $slot ) {
					$timeframe     = new Timeframe( $slot['timeframe'] );
					$timeFrameType = get_post_meta( $slot['timeframe']->ID, 'type', true );

					// Skip everything where the most important slot is not bookable. We are only interested in direct availability.
					if ( $timeFrameType != \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID ) {
						continue;
					}

					// Skip timeframes that are not bookable today
					if ( $timeframe->getFirstBookableDay() > $day->getDate() ) {
						continue;
					}

					$availabilitySlot = new stdClass();

					// Init DateTime object for start
					$dateTimeStart = Wordpress::getUTCDateTime( 'now' );
					$dateTimeStart->setTimestamp( $slot['timestampstart'] );
					$availabilitySlot->start = $dateTimeStart->format( 'Y-m-d\TH:i:sP' );

					// Init DateTime object for end
					$dateTimeend = Wordpress::getUTCDateTime( 'now' );
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
}
