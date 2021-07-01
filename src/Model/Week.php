<?php

namespace CommonsBooking\Model;

use CommonsBooking\Plugin;
use DateTime;
use Exception;

class Week {

	/**
	 * @var integer
	 */
	protected $year;

	/**
	 * Week of year.
	 * @var integer
	 */
	protected $week;

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
	 * Week constructor.
	 *
	 * @param null $year
	 * @param $week
	 * @param array $locations
	 * @param array $items
	 * @param array $types
	 */
	public function __construct( $year, $week, array $locations = [], array $items = [], array $types = [] ) {
		if ( $year === null ) {
			$year = date( 'Y' );
		}
		$this->year      = $year;
		$this->week      = $week;
		$this->locations = $locations;
		$this->items     = $items;
		$this->types     = $types;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getDays() {
		$customId = md5(
			$this->year .
			$this->week .
			serialize( $this->locations ) .
			serialize( $this->items ) .
			serialize( $this->types )
		);
		if ( Plugin::getCacheItem( $customId ) ) {
			return Plugin::getCacheItem( $customId );
		} else {
			$dto = new DateTime();
			$dto->setISODate( $this->getYear(), $this->getWeek() );

			$days = [];
			for ( $i = 0; $i < 7; $i ++ ) {
				$days[] = new Day( $dto->format( 'Y-m-d' ), $this->locations, $this->items, $this->types );
				$dto->modify( '+1 day' );
			}

			Plugin::setCacheItem( $days, $customId );

			return $days;
		}
	}

	/**
	 * @return integer
	 */
	public function getYear() {
		return $this->year;
	}

	/**
	 * @return int
	 */
	public function getWeek(): int {
		return $this->week;
	}

	/**
	 * @param mixed $week
	 *
	 * @return Week
	 */
	public function setWeek( $week ): Week {
		$this->week = $week;

		return $this;
	}

}
