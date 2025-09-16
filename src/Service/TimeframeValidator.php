<?php

namespace CommonsBooking\Service;

use CommonsBooking\Exception\OverlappingException;
use CommonsBooking\Exception\TimeframeInvalidException;
use CommonsBooking\Model\Timeframe;

/**
 * Validates Timeframe model objects
 */
class TimeframeValidator {

	/**
	 * Checks if Timeframe is valid. This should be called before publishing a timeframe as it will prevent broken timeframes / configurations.
	 *
	 * First checks missing values, then validates against existing timeframes.
	 * Will check for the mandatory item / location fields.
	 * Will check if the start- and end-date are set.
	 * Will check if there is no timeframe with the same item and location that overlaps with this timeframe.
	 *
	 * Will throw a TimeframeInvalidException with error message
	 *
	 * @param Timeframe $timeframe
	 *
	 * @return true if valid
	 * @throws \CommonsBooking\Exception\TimeframeInvalidException
	 */
	public static function isValid( Timeframe $timeframe ): bool {
		if (
			$timeframe->getType() === \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID
		) {
			try {
				$item     = $timeframe->getItem();
				$location = $timeframe->getLocation();
			} catch ( \Exception $e ) {
				throw new TimeframeInvalidException(
					__(
						'Could not get item or location. Please set a valid item and location.',
						'commonsbooking'
					)
				);
			}
			if ( ! $item || ! $location ) {
				// if location or item is missing
				throw new TimeframeInvalidException(
					__(
						'Item or location is missing. Please set item and location.',
						'commonsbooking'
					)
				);
			}

			// a timeframe with a manual repetition does not need a start date.
			// start- and enddate are automatically set upon saving the post
			if ( $timeframe->getRepetition() == 'manual' ) {
				$manual_selection_dates = $timeframe->getManualSelectionDates();
				if ( empty( $manual_selection_dates ) ) {
					throw new TimeframeInvalidException(
						__(
							'No dates selected. Please select at least one date.',
							'commonsbooking'
						)
					);
				}
				// make sure that there are no duplicate dates
				$unique_dates = array_unique( $manual_selection_dates );
				if ( count( $unique_dates ) != count( $manual_selection_dates ) ) {
					throw new TimeframeInvalidException(
						__(
							'The same date was selected multiple times. Please select each date only once.',
							'commonsbooking'
						)
					);
				}
			} elseif ( ! $timeframe->getStartDate() ) {
				// If there is at least one mandatory parameter missing, we cannot save/publish timeframe.
				throw new TimeframeInvalidException(
					__(
						'Startdate is missing. Please enter a start date to publish this timeframe.',
						'commonsbooking'
					)
				);
			}

			if (
				$timeframe->getStartDate()
			) {
				$postId = $timeframe->ID;

				if ( $timeframe->getStartTime() && ! $timeframe->getEndTime() && ! $timeframe->isFullDay() ) {
					throw new TimeframeInvalidException(
						__(
							'A pickup time but no return time has been set. Please set the return time.',
							'commonsbooking'
						)
					);
				}

				// check if end date is before start date
				if ( $timeframe->getEndDate() && ( $timeframe->getStartDate() > $timeframe->getTimeframeEndDate() ) ) {
					throw new TimeframeInvalidException(
						__(
							'End date is before start date. Please set a valid end date.',
							'commonsbooking'
						)
					);
				}

				// check if start-time and end-time are the same
				if ( ( $timeframe->getStartTime() && $timeframe->getEndTime() ) && ( $timeframe->getStartTime() == $timeframe->getEndTime() ) ) {
					throw new TimeframeInvalidException(
						__(
							'The start- and end-time of the timeframe can not be the same. Please check the full-day checkbox if you want users to be able to book the full day.',
							'commonsbooking'
						)
					);
				}

				// First we check if the item is already connected to another location to avoid overlapping bookable dates
				$sameItemTimeframes = \CommonsBooking\Repository\Timeframe::getBookable(
					[],
					[ $timeframe->getItemID() ],
					null,
					true,
					null,
					[ 'publish' ]
				);

				// check if timeframes of other locations overlap in date and return error message if true
				foreach ( $sameItemTimeframes as $sameItemTimeframe ) {
					if ( $location != $sameItemTimeframe->getLocation()
						&& self::hasTimeframeDateOverlap( $timeframe, $sameItemTimeframe )
					) {
						throw new TimeframeInvalidException(
						/* translators: %1$s = timeframe-ID, %2$s is timeframe post_title */
							sprintf(
								__(
									'Item is already bookable at another location within the same date range. See other timeframe ID: %1$s: %2$s',
									'commonsbooking'
								),
								'<a href=" ' . get_edit_post_link( $sameItemTimeframe->ID ) . '">' . $sameItemTimeframe->ID . '</a>',
								'<a href=" ' . get_edit_post_link( $sameItemTimeframe->ID ) . '">' . $sameItemTimeframe->post_title . '</a>',
							)
						);
					}
				}

				// Get Timeframes with same location, item and a startdate
				$existingTimeframes = \CommonsBooking\Repository\Timeframe::getBookable(
					[ $timeframe->getLocationID() ],
					[ $timeframe->getItemID() ],
					null,
					true
				);

				// filter current timeframe
				$existingTimeframes = array_filter(
					$existingTimeframes,
					function ( $timeframe ) use ( $postId ) {
						return $timeframe->ID !== $postId && $timeframe->getStartDate();
					}
				);

				// Validate against existing other timeframes
				foreach ( $existingTimeframes as $otherTimeframe ) {
					try {
						self::overlaps( $timeframe, $otherTimeframe );
					} catch ( OverlappingException $e ) {
						throw new TimeframeInvalidException(
							$e->getMessage() .
							sprintf(
							/* translators: first %s = timeframe-ID, second %s is timeframe post_title */
								__( 'See overlapping timeframe ID: %1$s %2$s', 'commonsbooking' ),
								'<a href=" ' . get_edit_post_link( $otherTimeframe->ID ) . '">' . $otherTimeframe->ID . '</a>',
								'<a href=" ' . get_edit_post_link( $otherTimeframe->ID ) . '">' . $otherTimeframe->post_title . '</a>'
							)
						);
					}
				}
			}
		}
		return true;
	}

	/**
	 * Will return false if the timeframes do not overlap in date range or time range.
	 * Will throw an exception with the formatted error message and the affected timeframe if the timeframes overlap.
	 *
	 * TODO: Refactor to return true if timeframes overlap and false if not. Throw exception in calling function.
	 *
	 * @uses Timeframe::hasTimeframeDateOverlap()
	 * @uses Timeframe::hasTimeframeTimeOverlap()
	 *
	 * @param Timeframe $otherTimeframe
	 *
	 * @return false
	 * @throws OverlappingException
	 */
	public static function overlaps( Timeframe $timeframe, Timeframe $otherTimeframe ): bool {
		if (
			self::hasTimeframeDateOverlap( $timeframe, $otherTimeframe )
		) {
			// Compare grid types
			if ( $otherTimeframe->getGrid() !== $timeframe->getGrid() ) {
				throw new OverlappingException(
					__( 'Overlapping bookable timeframes are only allowed to have the same grid.', 'commonsbooking' )
				);
			}

			// timeframes that don't overlap in time range are not overlapping
			if ( ! self::hasTimeframeTimeOverlap( $timeframe, $otherTimeframe ) ) {
				return false;
			}

			$otherTimeframeRepetition = $otherTimeframe->getRepetition();
			$repetition               = $timeframe->getRepetition();

			// One of the timeframes takes up the full day and therefore none of the dates can overlap
			// at this stage there is already overlap in the date range and time range, therefore we must check if the repetitions create an overlap
			if ( $repetition === 'd' || $otherTimeframeRepetition === 'd' ) {
				throw new OverlappingException(
					__( 'Daily repeated time periods are not allowed to overlap.', 'commonsbooking' )
				);
			}

			// we concatenate the repetitions to make the switch statement more readable
			switch ( $repetition . '|' . $otherTimeframeRepetition ) {
				case 'w|w':
					if ( $timeframe->getWeekDays() && $otherTimeframe->getWeekDays() ) {
						$weekDaysOverlap = array_intersect(
							$timeframe->getWeekDays(),
							$otherTimeframe->getWeekDays()
						);
						if ( ! empty( $weekDaysOverlap ) ) {
							throw new OverlappingException(
								__( 'Overlapping bookable timeframes are not allowed to have the same weekdays.', 'commonsbooking' )
							);
						}
					}
					break;
				case 'manual|manual':
					$manualDateOverlap = array_intersect(
						$timeframe->getManualSelectionDates(),
						$otherTimeframe->getManualSelectionDates()
					);
					if ( ! empty( $manualDateOverlap ) ) {
						throw new OverlappingException(
							__( 'Overlapping bookable timeframes are not allowed to have the same dates.', 'commonsbooking' )
						);
					}
					break;
				case 'w|manual':
					if ( self::hasWeeklyManualOverlap( $timeframe, $otherTimeframe ) ) {
						throw new OverlappingException(
							__( 'The other timeframe is overlapping with your weekly configuration.', 'commonsbooking' )
						);
					}
					break;
				case 'manual|w':
					if ( self::hasWeeklyManualOverlap( $otherTimeframe, $timeframe ) ) {
						throw new OverlappingException(
							__( 'The other timeframe is overlapping with your weekly configuration.', 'commonsbooking' )
						);
					}
					break;
			}
		}
		return false;
	}

	/**
	 * Checks if timeframes are overlapping in time ranges or daily slots.
	 *
	 * Use {@see TimeframeValidator::overlaps()} if you want to compute full-overlap between two timeframes.
	 *
	 * @param Timeframe $otherTimeframe
	 *
	 * @return bool If start-time and end-time overlaps, regardless of overlapping start-date and end-date.
	 */
	public static function hasTimeframeTimeOverlap( Timeframe $timeframe, Timeframe $otherTimeframe ) {
		// Check if both timeframes have an end time, if not, there is no overlap
		if ( ! strtotime( $timeframe->getEndTime() ) && ! strtotime( $otherTimeframe->getEndTime() ) ) {
			return true;
		}

		// Check if only timeframe1 has an end time and if it overlaps with the other timeframe
		if ( strtotime( $timeframe->getEndTime() ) && ! strtotime( $otherTimeframe->getEndTime() )
			&& strtotime( $otherTimeframe->getStartTime() ) <= strtotime( $timeframe->getEndTime() )
			&& strtotime( $otherTimeframe->getStartTime() ) >= strtotime( $timeframe->getStartTime() ) ) {
			return true;
		}

		// Check if only timeframe2 has an end time and if it overlaps with the other timeframe
		if ( ! strtotime( $timeframe->getEndTime() ) && strtotime( $otherTimeframe->getEndTime() )
			&& strtotime( $otherTimeframe->getEndTime() ) > strtotime( $timeframe->getStartTime() ) ) {
			return true;
		}

		// Check if both timeframes have an end time and if they overlap
		if ( strtotime( $timeframe->getEndTime() ) && strtotime( $otherTimeframe->getEndTime() )
			&& ( ( strtotime( $timeframe->getEndTime() ) > strtotime( $otherTimeframe->getStartTime() )
					&& strtotime( $timeframe->getEndTime() ) < strtotime( $otherTimeframe->getEndTime() ) )
					|| ( strtotime( $otherTimeframe->getEndTime() ) > strtotime( $timeframe->getStartTime() )
						&& strtotime( $otherTimeframe->getEndTime() ) < strtotime( $timeframe->getEndTime() ) ) ) ) {
			return true;
		}

		// Check if both timeframes have the same start and end time
		if ( strtotime( $timeframe->getEndTime() ) && strtotime( $otherTimeframe->getEndTime() )
			&& strtotime( $timeframe->getEndTime() ) === strtotime( $otherTimeframe->getEndTime() )
			&& strtotime( $timeframe->getStartTime() ) === strtotime( $otherTimeframe->getStartTime() ) ) {
			return true;
		}

		// If none of the above conditions are true, there is no overlap
		return false;
	}

	/**
	 * Checks if timeframes are overlapping in weekly slot and slot with manual repetition.
	 *
	 * @param $weeklyTimeframe
	 * @param $manualTimeframe
	 *
	 * @return bool
	 */
	public static function hasWeeklyManualOverlap( $weeklyTimeframe, $manualTimeframe ): bool {
		$manualSelectionWeekdays = array_unique(
			array_map(
				fn ( $date ) => date( 'w', strtotime( $date ) ),
				$manualTimeframe->getManualSelectionDates()
			)
		);
		// we have to make the sunday a 7 instead of 0 in order to detect overlaps with our other array correctly
		$manualSelectionWeekdays = array_map(
			fn ( $weekday ) => $weekday == 0 ? 7 : $weekday,
			$manualSelectionWeekdays
		);
		$weekDaysOverlap         = array_intersect( $weeklyTimeframe->getWeekDays(), $manualSelectionWeekdays );
		if ( ! empty( $weekDaysOverlap ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Checks if timeframes are overlapping in date range.
	 *
	 * @param Timeframe $timeframe
	 * @param Timeframe $otherTimeframe
	 *
	 * @return bool
	 */
	public static function hasTimeframeDateOverlap( Timeframe $timeframe, Timeframe $otherTimeframe ): bool {

		// Check if both timeframes have no end date or if both are ongoing
		if ( ! $timeframe->getTimeframeEndDate() && ! $otherTimeframe->getTimeframeEndDate() ) {
			return true;
		}

		// Check if only one timeframe has an end date
		if ( $timeframe->getTimeframeEndDate() && ! $otherTimeframe->getTimeframeEndDate() ) {
			return ( $otherTimeframe->getStartDate() <= $timeframe->getTimeframeEndDate() && $otherTimeframe->getStartDate() >= $timeframe->getStartDate() );
		}

		if ( ! $timeframe->getTimeframeEndDate() && $otherTimeframe->getTimeframeEndDate() ) {
			return ( $otherTimeframe->getTimeframeEndDate() > $timeframe->getStartDate() );
		}

		// Check if both timeframes have an end date
		if ( $timeframe->getTimeframeEndDate() && $otherTimeframe->getTimeframeEndDate() ) {
			return (
				// Check if the end date of the first timeframe is within the second timeframe
				( $timeframe->getTimeframeEndDate() >= $otherTimeframe->getStartDate() && $timeframe->getTimeframeEndDate() <= $otherTimeframe->getTimeframeEndDate() ) ||
				// Check if the end date of the second timeframe is within the first timeframe
				( $otherTimeframe->getTimeframeEndDate() >= $timeframe->getStartDate() && $otherTimeframe->getTimeframeEndDate() <= $timeframe->getTimeframeEndDate() )
			);
		}

		// If none of the above conditions are true, there is no overlap
		// TODO: When does this condition ever apply?
		return false;
	}
}
