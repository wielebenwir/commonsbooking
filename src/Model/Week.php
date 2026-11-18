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
	 * @var int|string
	 */
	protected string|int $dayOfYear;

	/**
	 * @var array<int, int|\WP_Post>
	 */
	protected array $locations;

	/**
	 * @var array<int, int|\WP_Post>
	 */
	protected array $items;

	/**
	 * @var array<int, int>
	 */
	protected array $types;

	/**
	 * @var Timeframe[]
	 */
	private array $timeframes = [];

	/**
	 * Week constructor.
	 *
	 * @param int|string|null          $year
	 * @param int|string               $dayOfYear
	 * @param array<int, int|\WP_Post> $locations
	 * @param array<int, int|\WP_Post> $items
	 * @param array<int, int>          $types
	 * @param Timeframe[]              $possibleTimeframes Timeframes that might be relevant for this week, need to be filtered.
	 */
	public function __construct( int|string|null $year, int|string $dayOfYear, array $locations = [], array $items = [], array $types = [], array $possibleTimeframes = [] ) {
		if ( $year === null ) {
			$year = date( 'Y' );
		}
		$this->year      = $year;
		$this->dayOfYear = $dayOfYear;
		$this->locations = $locations;
		$this->items     = $items;
		$this->types     = $types;

		if ( ! empty( $possibleTimeframes ) ) {
			$this->timeframes = \CommonsBooking\Repository\Timeframe::filterTimeframesForTimerange( $possibleTimeframes, $this->getStartTimestamp(), $this->getEndTimestamp() );
		}
	}

	/**
	 * Helper function for Calendar.
	 *
	 * Uses the cache and expires at midnight on a daily basis.
	 *
	 * @return Day[]
	 * @throws Exception
	 */
	public function getDays(): array {
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
				$days[]    = new Day( $dayDate, $this->locations, $this->items, $this->types, $this->timeframes ?: [] );
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
