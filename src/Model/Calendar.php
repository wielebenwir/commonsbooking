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
		
		$startDate = strtotime( $this->startDate->getDate() );
		$endDate   = strtotime( $this->endDate->getDate() );

		$customId = md5(
			$startDate .
			$endDate .
			serialize( $this->items ) .
			serialize( $this->locations ) .
			serialize( $this->types )
		);

		
		// TODO: Cache deactivated. Need solution to realize a day by day Cache refresh
		if ( 1 != 1 OR Plugin::getCacheItem( $customId ) ) {
			return Plugin::getCacheItem( $customId );
		} else {
			$weeks = [];
			while ( $startDate <= $endDate ) {
				$weeks[]   = new Week( date( 'Y', $startDate ), date( 'W', $startDate ), $this->locations, $this->items, $this->types );
				$startDate = strtotime( "next monday", $startDate );
			}

			Plugin::setCacheItem( $weeks, $customId );

			return $weeks;
		}
	}


}
