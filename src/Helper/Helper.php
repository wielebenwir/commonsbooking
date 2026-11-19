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
	 * Returns the PHP date format of the datepicker fields (CMB2) for the current locale.
	 *
	 * We need static types, because german month names don't work for datepicker.
	 * Dates in this format must be read with {@see Helper::getTimestampFromFormattedDate()}:
	 * strtotime() reads every date with slashes as m/d/Y, which is wrong for d/m/Y.
	 *
	 * @return string d.m.Y for de_*, m/d/Y for en_*, d/m/Y for every other locale.
	 */
	public static function getDatepickerDateFormat(): string {
		if ( str_starts_with( get_locale(), 'de_' ) ) {
			return 'd.m.Y';
		}

		if ( str_starts_with( get_locale(), 'en_' ) ) {
			return 'm/d/Y';
		}

		return 'd/m/Y';
	}

	/**
	 * Returns the timestamp of a date string in a known format, e.g. from a datepicker field.
	 *
	 * Unlike strtotime(), this reads 16/11/2026 as 16 November 2026 when the format is d/m/Y.
	 * Like strtotime(), the date is read in the default timezone (UTC in WordPress).
	 *
	 * @param string $date       Date, e.g. 16/11/2026.
	 * @param string $dateFormat PHP date format of $date, e.g. d/m/Y.
	 * @param string $time       Optional time, e.g. 08:30. Midnight if empty.
	 * @param string $timeFormat Optional PHP time format of $time. strtotime() is used if $time does not match it.
	 *
	 * @return int|false False if $date does not match $dateFormat, is not a valid date (e.g. 31/02/2026) or $time is not readable.
	 */
	public static function getTimestampFromFormattedDate( string $date, string $dateFormat, string $time = '', string $timeFormat = '' ): int|false {
		$date = trim( $date );
		$time = trim( $time );
		if ( $date === '' || $dateFormat === '' ) {
			return false;
		}

		// '!' sets the fields that are not in the format to zero instead of the current time.
		$dateTime = \DateTime::createFromFormat( '!' . $dateFormat, $date );
		$errors   = \DateTime::getLastErrors();
		// Warnings mean that PHP would roll the date over, e.g. 31/02 to 03/03.
		if ( ! $dateTime || ( $errors && ( $errors['warning_count'] || $errors['error_count'] ) ) ) {
			return false;
		}

		if ( $time === '' ) {
			return $dateTime->getTimestamp();
		}

		$parsedTime = $timeFormat !== '' ? \DateTime::createFromFormat( '!' . $timeFormat, $time ) : false;
		if ( $parsedTime ) {
			$dateTime->setTime( (int) $parsedTime->format( 'G' ), (int) $parsedTime->format( 'i' ) );

			return $dateTime->getTimestamp();
		}

		// e.g. 08:30 from the timepicker when the time format is g:i a
		return strtotime( $time, $dateTime->getTimestamp() );
	}

	/**
	 * CMB2 sanitization callback for text_datetime_timestamp fields.
	 *
	 * CMB2 reads date and time with date_create_from_format( date_format ), which always fails because
	 * of the added time, and then falls back to strtotime(). For d/m/Y this deleted dates with a day > 12
	 * and swapped day and month otherwise. We read the date in the format of the field instead.
	 *
	 * @param mixed                $value      The unsanitized value from the form, array with 'date' and 'time'.
	 * @param array<string, mixed> $field_args Array of field arguments.
	 * @param \CMB2_Field          $field      The field object.
	 *
	 * @return mixed Timestamp to be stored.
	 */
	public static function sanitizeDatetimeTimestamp( mixed $value, array $field_args, \CMB2_Field $field ): mixed {
		if ( is_array( $value ) && isset( $value['date'], $value['time'] ) && is_string( $value['date'] ) && is_string( $value['time'] ) ) {
			$timestamp = self::getTimestampFromFormattedDate(
				wp_unslash( $value['date'] ),
				(string) ( $field_args['date_format'] ?? '' ),
				wp_unslash( $value['time'] ),
				(string) ( $field_args['time_format'] ?? '' )
			);
			if ( $timestamp !== false ) {
				return $timestamp;
			}
		}

		// Empty values, timestamps from the REST API or dates in another format (e.g. 2026/11/16) are handled by CMB2 as before.
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
