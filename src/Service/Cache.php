<?php

namespace CommonsBooking\Service;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\CacheItem;
use const WP_DEBUG;

trait Cache {

	/**
	 * Returns cache item based on calling class, function and args.
	 *
	 * @param null $custom_id
	 *
	 * @return mixed
	 * @throws InvalidArgumentException
	 */
	public static function getCacheItem( $custom_id = null ) {
		if ( WP_DEBUG ) {
			return false;
		}

		/** @var CacheItem $cacheItem */
		$cacheKey  = self::getCacheId( $custom_id );
		$cacheItem = self::getCache()->getItem( $cacheKey );
		if ( $cacheItem->isHit() ) {
			return $cacheItem->get();
		} else {
			return false;
		}
	}

	/**
	 * Returns cache id, based on calling class, function and args.
	 *
	 * @param null $custom_id
	 *
	 * @return string
	 */
	public static function getCacheId( $custom_id = null ): string {
		$backtrace = debug_backtrace()[2];
		if ( array_key_exists( 'args', $backtrace ) &&
		     count( $backtrace['args'] ) &&
		     is_array( $backtrace['args'][0] )
		) {
			if ( array_key_exists( 'taxonomy', $backtrace['args'][0] ) ) {
				unset( $backtrace['args'][0]['taxonomy'] );
			}
			if ( array_key_exists( 'term', $backtrace['args'][0] ) ) {
				unset( $backtrace['args'][0]['term'] );
			}
			if ( array_key_exists( 'category_slug', $backtrace['args'][0] ) ) {
				unset( $backtrace['args'][0]['category_slug'] );
			}
		}

		$namespace     = str_replace( '\\', '_', strtolower( $backtrace['class'] ) );
		$namespace     .= '_' . $backtrace['function'];
		$backtraceArgs = $backtrace['args'];
		$namespace     .= '_' . md5( serialize( $backtraceArgs ) );
		if ( $custom_id ) {
			$namespace .= $custom_id;
		}

		return md5( $namespace );
	}

	/**
	 * @param string $namespace
	 * @param int $defaultLifetime
	 * @param string|null $directory
	 *
	 * @return FilesystemAdapter
	 */
	public static function getCache( string $namespace = '', int $defaultLifetime = 0, string $directory = null ) {
		return new FilesystemAdapter( $namespace, $defaultLifetime, $directory );
	}

	/**
	 * Saves cache item based on calling class, function and args.
	 *
	 * @param $value
	 * @param null $custom_id
	 * @param $expiration - set expiration as timestamp or string 'midnight' to set expiration to 00:00 next day
	 *
	 * @return mixed
	 */
	public static function setCacheItem( $value, $custom_id = null, $expiration = 0 ) {
		// if expiration is set to 'midnight' we calculate the duration in seconds until midnight
		if ( $expiration == 'midnight' ) {
			$datetime   = current_time( 'timestamp' );
			$expiration = strtotime( 'tomorrow', $datetime ) - $datetime;
		}

		$cache = self::getCache( '', intval( $expiration ) );
		/** @var CacheItem $cacheItem */
		$cacheKey  = self::getCacheId( $custom_id );
		$cacheItem = $cache->getItem( $cacheKey );
		$cacheItem->set( $value );

		return $cache->save( $cacheItem );
	}

	/**
	 * Deletes cb transients.
	 *
	 * @param string $param
	 */
	public static function clearCache( string $param = "" ) {
//		global $wpdb;
//		$sql = "
//            DELETE
//            FROM $wpdb->options
//            WHERE option_name like '_transient_commonsbooking%" . $param . "%'
//        ";
//		$wpdb->query($sql);
		self::getCache()->clear();
	}

}