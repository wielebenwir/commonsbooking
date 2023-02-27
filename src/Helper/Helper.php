<?php

namespace CommonsBooking\Helper;

use CommonsBooking\Model\Booking;
use CommonsBooking\Model\Item;
use CommonsBooking\Model\Location;
use CommonsBooking\Model\Timeframe;

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

		function if_either_null_else_func( callable $func, object $end_date, object $end_date1 ): ?object {
			if ($end_date == null || $end_date1 == null) {
				return null;
			}

			return $func($end_date1, $end_date1);
		}

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

		// For each element
		for ($i = 1; $i < count($arrayOfRanges); $i++) {

			if ($result[$last]['end_date'] >= $arrayOfRanges[$i]['start_date']) {
				// Overlap => do the merge
				$result[$last]["start_date"] = if_either_null_else_func(min, $result[ $last ]['start_date'], $arrayOfRanges[ $i ]['start_date'] );
				$result[$last]["end_date"]   = if_either_null_else_func(max, $result[ $last ]['end_date'],   $arrayOfRanges[ $i ]['end_date'] );
			} else {
				// No overlap => Add new interval to result
				// And use this as new last interval
				$result[] = $arrayOfRanges[ $i ];
				$last ++;
			}
		}

		return $result;

	}

}