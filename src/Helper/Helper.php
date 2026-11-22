<?php

namespace CommonsBooking\Helper;

use CommonsBooking\Model\Booking;
use CommonsBooking\Model\CustomPost;
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
		for ( $i = 0; $i < $length; $i++ ) {
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

		return self::FormattedDate( $timestamp ) . ' ' . self::FormattedTime( $timestamp );
	}

	/**
	 * Date format of the datepicker fields: the WordPress date format if it only uses numeric
	 * day, month and year (month names can't be parsed back), otherwise a fallback per locale.
	 */
	public static function getDatepickerDateFormat(): string {
		$format = get_option( 'date_format' );
		if ( is_string( $format ) && preg_match( '~^(?=.*[dj])(?=.*[mn])(?=.*[yY])[djmnyY ./-]+$~', $format ) ) {
			return $format;
		}
		if ( str_starts_with( get_locale(), 'de_' ) ) {
			return 'd.m.Y';
		}

		return str_starts_with( get_locale(), 'en_' ) ? 'm/d/Y' : 'd/m/Y';
	}

	/**
	 * Timestamp of a date in the given format, false if it doesn't match.
	 * Unlike strtotime(), which reads every date with slashes as m/d/Y.
	 */
	public static function parseDate( string $date, string $format ): int|false {
		$dateTime = \DateTime::createFromFormat( '!' . $format, trim( $date ) );
		$errors   = \DateTime::getLastErrors();

		return $dateTime && ! ( $errors && $errors['warning_count'] ) ? $dateTime->getTimestamp() : false;
	}

	/**
	 * CMB2 sanitization_cb for text_datetime_timestamp fields: reads the date in the field's format.
	 * Anything else (empty value, timestamp, other format) is handled by CMB2 as before.
	 *
	 * @param mixed                $value
	 * @param array<string, mixed> $field_args
	 * @param \CMB2_Field          $field
	 */
	public static function sanitizeDatetimeTimestamp( mixed $value, array $field_args, \CMB2_Field $field ): mixed {
		if ( is_array( $value ) && is_string( $value['date'] ?? null ) && is_string( $value['time'] ?? null ) ) {
			$day       = self::parseDate( wp_unslash( $value['date'] ), (string) $field_args['date_format'] );
			$timestamp = $day === false ? false : strtotime( wp_unslash( $value['time'] ) ?: '00:00', $day );
			if ( $timestamp !== false ) {
				return $timestamp;
			}
		}

		return ( new \CMB2_Sanitize( $field, $value ) )->text_datetime_timestamp();
	}

	/**
	 * Returns timestamp of last full hour, needed to get more cache hits.
	 * Also used to determine if a post is still bookable because it is in the past or not.
	 *
	 * @return int
	 */
	public static function getLastFullHourTimestamp() {
		$now = current_time( 'timestamp' );
		return $now - ( $now % 3600 );
	}

	/**
	 * Returns timestamp of last full day, needed to get more cache hits.
	 *
	 * @param int|null $timestamp
	 *
	 * @return int
	 */
	public static function getLastFullDayTimestamp( ?int $timestamp = null ) {
		if ( $timestamp === null ) {
			$timestamp = current_time( 'timestamp' );
		}

		return $timestamp - ( $timestamp % ( 3600 * 24 ) );
	}

	/**
	 * Returns CB custom post type if possible.
	 *
	 * TODO try to reuse that where appropriate (PostRepository etc.)
	 *
	 * @param \WP_User|\WP_Post|CustomPost $post
	 * @param string                       $type a CPT name, if nothing, the post is returned
	 *
	 * @return Item|Booking|\WP_User|Location|\WP_Post|CustomPost
	 * @throws \Exception
	 */
	public static function castToCBCustomType( \WP_User|\WP_Post|\CommonsBooking\Model\CustomPost $post, string $type ): Item|Booking|\WP_User|Location|\WP_Post|\CommonsBooking\Model\CustomPost {
		if ( $type == \CommonsBooking\Wordpress\CustomPostType\Booking::$postType ) {
			$post = new Booking( $post->ID );
		}
		if ( $type == \CommonsBooking\Wordpress\CustomPostType\Item::$postType ) {
			$post = new Item( $post->ID );
		}
		if ( $type == \CommonsBooking\Wordpress\CustomPostType\Location::$postType ) {
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
	 * @param array<int, array{start_date: int, end_date: int|false}> $array_of_ranges Array of one or more ranges.
	 *
	 * @return array<int, array{start_date: int, end_date: int|false}> - Array of overlapping ranges.
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
			function ( $a, $b ) {
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
				++$last;
			}
		}

		return $result;
	}
}
