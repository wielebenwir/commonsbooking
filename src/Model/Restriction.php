<?php


namespace CommonsBooking\Model;


class Restriction extends CustomPost {

	const TYPE_REPAIR = 'repair';

	const TYPE_HINT = 'hint';

	const META_START = 'restriction-start';

	const META_END = 'restriction-end';

	const META_TYPE = 'restriction-type';

	/**
	 * Returns start-time \DateTime.
	 *
	 * @param $timeframe
	 *
	 * @return \DateTime
	 */
	public function getStartTimeDateTime(): \DateTime {
		$startDateString = $this->getMeta( self::META_START );
		$startDate       = new \DateTime();
		$startDate->setTimestamp( $startDateString );
		return $startDate;
	}

	/**
	 * Returns end-date \DateTime.
	 *
	 * @return \DateTime
	 */
	public function getEndDateDateTime(): \DateTime {
		$endDateString = intval( $this->getMeta( self::META_END ) );
		$endDate       = new \DateTime();
		$endDate->setTimestamp( $endDateString );

		return $endDate;
	}

	/**
	 * Returns start-time \DateTime.
	 *
	 * @return \DateTime
	 * @throws Exception
	 */
	public function getEndTimeDateTime( $endDateString ): \DateTime {
		$endTimeString = $this->getMeta( self::META_END  );
		$endDate       = new \DateTime();
		$endDate->setTimestamp( $endDateString );
		if ( $endTimeString ) {
			$endTime = new \DateTime();
			$endTime->setTimestamp( $endTimeString);
			$endDate->setTime( $endTime->format( 'H' ), $endTime->format( 'i' ) );
		}

		return $endDate;
	}

	public function getStartDate() {
		return $this->getMeta( self::META_START );
	}

	public function getEndDate() {
		return $this->getMeta( self::META_END );
	}

	public function isOverBookable(): bool {
		return false;
	}

	public function isLocked(): bool {
		return true;
	}

	public function getType() {
		return $this->getMeta(self::META_TYPE);
	}

}