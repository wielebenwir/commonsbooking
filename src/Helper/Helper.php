<?php

namespace CommonsBooking\Helper;

use CommonsBooking\Model\Booking;
use CommonsBooking\Model\Item;
use CommonsBooking\Model\Location;
use CommonsBooking\Model\Timeframe;

/**
 * Set <code>arr1[key]</code> to false, if either key of arr1 or key of arr2 is false.
 * <p>If not false, use func (which takes two args) to compute a result
 * from both <code>func(arr1[key], arr2[key])</code></p>
 *
 * @param string $key
 * @param object $arr1
 * @param object $arr2
 * @param callable $func
 *
 * @return object|null
 */
function set_false_if_either_key_null_else_set_func( string $key, array &$arr1, array &$arr2, callable $func ) : void {
	if ( $arr1[ $key ] === false) {
		// Do nothing, interval1 is open
	} else if ( $arr2[ $key ] === false) {
		// Set interval_1 false because interval 2 is open
		$arr1[$key] = false;
	} else {
		// Both intervals are closed
		$arr1[$key] = $func($arr1[$key], $arr2[$key]);
	}
}

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

		$date_format = commonsbooking_sanitizeHTML( get_option( 'date_format' ) );
		$time_format = commonsbooking_sanitizeHTML( get_option( 'time_format' ) );

		return date_i18n( $date_format, $timestamp ) . ' ' . date_i18n( $time_format, $timestamp );
	}

	/**
	 * Returns timestamp of last full hour, needed to get more cache hits.
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
	 * @param $arrayOfRanges
	 *
	 * @return array():TimeFrame
	 */
	public static function mergeRangesToBookableDate( $arrayOfRanges ): array {

		if ( count($arrayOfRanges) == 1) {
			return $arrayOfRanges;
		}

		$result = array();

		// Sort by start date
		usort($arrayOfRanges, function( $a, $b ) {
			return $a['start_date'] <=> $b['start_date'];
		});

		$result[] = $arrayOfRanges[0];
		$last = 0;

		// For each range, compare with last (or first) merged range
		for ($i = 1; $i < count($arrayOfRanges); $i++) {

			// Either
			//  If first/last interval is open => overlaps next
			//  Or first/last interval end is greater than next interval begin
			if (
				$result[ $last ]['end_date'] === false
			    || $result[ $last ]['end_date'] >= $arrayOfRanges[$i]['start_date'])
			{
				// => Overlap, merge both
				set_false_if_either_key_null_else_set_func( 'start_date', $result[ $last ], $arrayOfRanges[ $i ], function($a,$b) {return min($a, $b);} );
				set_false_if_either_key_null_else_set_func( 'end_date',   $result[ $last ], $arrayOfRanges[ $i ], function($a,$b) {return max($a, $b);} );
			} else {
				// => No overlap, add new interval to result. Use as new last interval
				$result[] = $arrayOfRanges[ $i ];
				$last ++;
			}
		}

		return $result;

	}
}