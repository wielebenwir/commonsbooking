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

		$date_format = commonsbooking_sanitizeHTML( get_option( 'date_format' ) );
		$time_format = commonsbooking_sanitizeHTML( get_option( 'time_format' ) );

		return date_i18n( $date_format, $timestamp ) . ' ' . date_i18n( $time_format, $timestamp );
	}

	/**
	 * Returns timestamp of last full hour, needed to get more cache hits.
	 * @return int
	 */
	public static function getLastFullHourTimestamp() {
		$now = time();

		return $now - ( $now % 3600 );
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

}