<?php

namespace CommonsBooking\Model;

use CommonsBooking\Plugin;

class Calendar {

	/**
	 * @var Day
	 */
	protected $startDate;

	/**
	 * @var Day
	 */
	protected $endDate;

	/**
	 * @var array
	 */
	protected $items;

	/**
	 * @var array
	 */
	protected $locations;

	/**
	 * @var array
	 */
	protected $types;

	/**
	 * @var
	 */
	protected $weeks;

	/**
	 * Calendar constructor.
	 *
	 * @param Day $startDate
	 * @param Day $endDate
	 * @param array $locations
	 * @param array $items
	 * @param array $types
	 */
	public function __construct( Day $startDate, Day $endDate, array $locations = [], array $items = [], array $types = [] ) {
		$this->startDate = $startDate;
		$this->endDate   = $endDate;
		$this->items     = $items;
		$this->locations = $locations;
		$this->types     = $types;
	}

	/**
	 * Returns weeks for calendar time range.
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


		if ( Plugin::getCacheItem( $customId ) ) {
			return Plugin::getCacheItem( $customId );
		} else {
			$weeks = [];
			while ( $startDate <= $endDate ) {
				$dayOfYear = date( 'z', $startDate );
				$year      = date( 'Y', $startDate );
				$weeks[]   = new Week(
					$year,
					$dayOfYear,
					$this->locations,
					$this->items,
					$this->types
				);
				$startDate = strtotime( "next monday", $startDate );
			}

			// set cache expiration to force daily fresh after midnight
			Plugin::setCacheItem( $weeks, [ 'misc' ], $customId, 'midnight' );

			return $weeks;
		}
	}


}
