<?php

namespace CommonsBooking\Helper;

use CommonsBooking\Model\Booking;
use CommonsBooking\Model\Item;
use CommonsBooking\Model\Location;

class Helper {

	/**
	 * generates a random string as hash
	 *
	 * @param mixed $length
	 *
	 * @return string
	 */
	public static function generateRandomString( $length = '24' ): string {
		$characters       = '0123456789abcdefghijklmnopqrstuvwxyz';
		$charactersLength = strlen( $characters );
		$randomString     = '';
		for ( $i = 0; $i < $length; $i ++ ) {
			$randomString .= $characters[ rand( 0, $charactersLength - 1 ) ];
		}

		return $randomString;
	}

	/**
	 * Returns formatted date default based on WP-settings and localized with datei_i18n
	 *
	 * @param mixed $timestamp
	 *
	 * @return string
	 */
	public static function FormattedDate( $timestamp ) {

		$date_format = commonsbooking_sanitizeHTML( get_option( 'date_format' ) );

		return date_i18n( $date_format, $timestamp );

	}

	/**
	 * Returns formatted time default based on WP-settings and localized with datei_i18n
	 *
	 * @param mixed $timestamp
	 *
	 * @return string
	 */
	public static function FormattedTime( $timestamp ) {

		$time_format = commonsbooking_sanitizeHTML( get_option( 'time_format' ) );

		return date_i18n( $time_format, $timestamp );

	}

	/**
	 * Returns formatted date and time default based on WP-settings and localized with datei_i18n
	 *
	 * @param mixed $timestamp
	 *
	 * @return string
	 */
	public static function FormattedDateTime( $timestamp ) {

		return Helper::FormattedDate( $timestamp ) . ' ' . Helper::FormattedTime( $timestamp );
	}

	/**
	 * Returns timestamp of last full hour, needed to get more cache hits.
	 * Also used to determine if a post is still bookable because it is in the past or not.
	 * @return int
	 */
	public static function getLastFullHourTimestamp() {
		$now = current_time('timestamp');
		return $now - ( $now % 3600 );
	}

	/**
	 * Returns timestamp of last full day, needed to get more cache hits.
	 * @param $timestamp
	 *
	 * @return int|mixed|null
	 */
	public static function getLastFullDayTimestamp($timestamp = null) {
		if($timestamp === null) $timestamp = current_time('timestamp');

		return $timestamp - ( $timestamp % (3600 * 24) );
	}

	/**
	 * Returns CB custom post type if possible.
	 * @param $post
	 * @param $type
	 *
	 * @return Booking|Item|Location|mixed
	 * @throws \Exception
	 */
	public static function castToCBCustomType( $post, $type ) {
		if ( $type == \CommonsBooking\Wordpress\CustomPostType\Booking::$postType ) {
			$post = new Booking( $post->ID );
		}
		if ( $type == \CommonsBooking\Wordpress\CustomPostType\Item::$postType) {
			$post = new Item( $post->ID );
		}
		if ( $type == \CommonsBooking\Wordpress\CustomPostType\Location::$postType) {
			$post = new Location( $post->ID );
		}

		return $post;
	}

	/**
	 * Returns one or more overlapping timeframes, given an array of timeframes
	 *
	 * NOTE: When performance issues arise, this operation can be implemented
	 * faster with an interval tree data structure
	 *
	 * @param array $array_of_ranges Array of one or more ranges.
	 *
	 * @return array - Array of overlapping ranges.
	 */
	public static function mergeRangesToBookableDates( array $array_of_ranges ): array {
		$interval_open = function ( $interval_value ): bool {
			return false === $interval_value;
		};

		if ( count( $array_of_ranges ) === 1 ) {
			return $array_of_ranges;
		}

		$result = array();

		// Sort by start date.
		usort(
			$array_of_ranges,
			function( $a, $b ) {
				return $a['start_date'] <=> $b['start_date'];
			}
		);

		$result[] = $array_of_ranges[0];
		$last     = 0;

		// For each range, compare with last (or first) merged range.
		for ( $i = 1; $i < count( $array_of_ranges ); $i++ ) {
			$last_interval = &$result[ $last ];
			$next_interval = &$array_of_ranges[ $i ];

			// Either
			// If first/last interval is open => overlaps next
			// Or first/last interval end is greater than next interval begin.
			if (
				$interval_open( $last_interval['end_date'] )
				|| $last_interval['end_date'] >= $next_interval['start_date'] ) {
				// TimeFrame overlap?
				// => Overlap, merge interval start and end.
				$last_interval['start_date'] = min( $last_interval['start_date'], $next_interval['start_date'] );

				if ( $interval_open( $last_interval['end_date'] ) ) {
					// Do nothing.
				} elseif ( $interval_open( $next_interval['end_date'] ) ) {
					$last_interval['end_date'] = false;
				} else {
					// Both intervals are closed.
					$last_interval['end_date'] = max( $last_interval['end_date'], $next_interval['end_date'] );
				}
			} else {
				// => No overlap, add new interval to result. Use as new last interval
				$result[] = $next_interval;
				$last ++;
			}
		}

		return $result;
	}


}
