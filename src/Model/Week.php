<?php

namespace CommonsBooking\Model;

use CommonsBooking\Helper\Wordpress;
use CommonsBooking\Plugin;
use Exception;

/**
 * Represents up to 7 days of the (rest of the) week, starting from any day of the year,
 * till the end of the week.
 *
 * @uses Day
 */
class Week {

	/**
	 * @var int
	 */
	protected $year;

	/**
	 * Day in the year to start the week from (0-365)
	 *
	 * @var int
	 */
	protected $dayOfYear;

	/**
	 * @var array
	 */
	protected $locations;

	/**
	 * @var array
	 */
	protected $items;

	/**
	 * @var array
	 */
	protected $types;

	/**
	 * @var Timeframe[]|null null = no pre-fetch was done; [] or non-empty = pre-fetched (possibly empty for this week).
	 */
	private ?array $timeframes = null;

	/**
	 * Pre-fetched restrictions for this week's range.
	 *
	 * @var \CommonsBooking\Model\Restriction[]|null
	 */
	private ?array $possibleRestrictions = null;

	/**
	 * Week constructor.
	 *
	 * @param $year
	 * @param $dayOfYear
	 * @param array       $locations
	 * @param array       $items
	 * @param array       $types
	 * @param Timeframe[]|null $possibleTimeframes Timeframes pre-fetched for the calendar range (null = not provided, query DB per day).
	 * @param array|null       $possibleRestrictions Pre-fetched restrictions for the calendar range (null = not provided, query DB).
	 */
	public function __construct( $year, $dayOfYear, array $locations = [], array $items = [], array $types = [], ?array $possibleTimeframes = null, ?array $possibleRestrictions = null ) {
		if ( $year === null ) {
			$year = date( 'Y' );
		}
		$this->year      = $year;
		$this->dayOfYear = $dayOfYear;
		$this->locations = $locations;
		$this->items     = $items;
		$this->types     = $types;

		if ( $possibleTimeframes !== null ) {
			// Pre-fetched: filter to this week's range. The result may be empty [] — that's fine,
			// it tells Day "we already know there are no timeframes, skip the DB query".
			$this->timeframes = \CommonsBooking\Repository\Timeframe::filterTimeframesForTimerange( $possibleTimeframes, $this->getStartTimestamp(), $this->getEndTimestamp() );
		}

		$this->possibleRestrictions = $possibleRestrictions;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getDays() {
		$customId = md5(
			$this->year .
			$this->dayOfYear .
			serialize( $this->locations ) .
			serialize( $this->items ) .
			serialize( $this->types )
		);

		$cacheItem = Plugin::getCacheItem( $customId );
		if ( $cacheItem ) {
			return $cacheItem;
		} else {
			$yearTimestamp = $this->getYearTimestamp();
			$dayOfYear     = $this->dayOfYear;
			$timestamp     = strtotime( "+ $dayOfYear days", $yearTimestamp );
			$dto           = Wordpress::getUTCDateTimeByTimestamp( $timestamp );

			$days = array();
			for ( $i = 0; $i < 7; $i++ ) {
				$dayDate   = $dto->format( 'Y-m-d' );
				$days[]    = new Day( $dayDate, $this->locations, $this->items, $this->types, $this->timeframes, $this->possibleRestrictions );
				$dayOfWeek = $dto->format( 'w' );
				if ( $dayOfWeek === '0' ) {
					break;
				}

				$dto->modify( '+1 day' );
			}

			// set cache expiration to force daily fresh after midnight
			Plugin::setCacheItem( $days, array( 'misc' ), $customId, 'midnight' );

			return $days;
		}
	}

	/**
	 * Will return the timestamp of the first second of the given week.
	 *
	 * @return int
	 */
	public function getStartTimestamp(): int {
		$yearTimestamp = $this->getYearTimestamp();
		$timestamp     = strtotime( "+ $this->dayOfYear days", $yearTimestamp );

		return $timestamp;
	}

	/**
	 * Will return the timestamp of the last second of the given week.
	 *
	 * @return int
	 */
	public function getEndTimestamp(): int {
		$yearTimestamp = $this->getYearTimestamp();
		$timestamp     = strtotime( "+ $this->dayOfYear days", $yearTimestamp );
		$timestamp     = strtotime( '+6 days 23:59:59', $timestamp );

		return $timestamp;
	}

	/**
	 * @return false|int
	 */
	private function getYearTimestamp() {
		return mktime( 0, 0, 0, 1, 1, $this->year );
	}
}
