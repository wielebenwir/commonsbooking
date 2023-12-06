<?php

namespace CommonsBooking\View;

class Statistics extends View {

	public static function __callStatic( $name, $arguments ) {
		if ( strpos( $name, 'sum_' ) === 0 ) {
			$metaValue = str_replace('sum_', '', $name);
			return self::sumMeta($arguments[0], $metaValue);
		}
		elseif ( strpos( $name, 'count_' ) === 0 ) {
			$metaValue = str_replace('count_', '', $name);
			return self::countMeta($arguments[0], $metaValue);
		}
		elseif ( strpos( $name, 'avg_' ) === 0 ) {
			$metaValue = str_replace('avg_', '', $name);
			return self::avgMeta($arguments[0], $metaValue);
		}
	}

	public static function shortcode( $args ) {
		$args = shortcode_atts( [
			'do' => '',
			'type' => '',
		], $args, 'cb_statistics' );

		$fn = $args['do'];
		$type = $args['type'];

		if ( ! $fn || ! $type ) {
			return '';
		}

		switch ($type) {
			case 'booking':
				$posts = \CommonsBooking\Repository\Booking::get( [], [], null, true, null, ['confirmed', 'unconfirmed', 'canceled', 'publish', 'inherit'] );
				break;
			case 'item':
				$posts = \CommonsBooking\Repository\Item::get([],true);
				break;
			case 'location':
				$posts = \CommonsBooking\Repository\Location::get([],true);
				break;
			default:
				return '';
		}

		return self::$fn( $posts );

	}

	public static function count( $posts ): int {
		return count( $posts );
	}

	private static function getProperty( $post, $property ) {
		if ( method_exists( $post, $property ) ) {
			return $post->{$property}();
		} else {
			return $post->getMeta( $property );
		}
	}

	public static function sumMeta( $posts, $metaValue ): int {
		return array_sum( array_map( fn( $post ) => self::getProperty( $post, $metaValue ), $posts ) );
	}

	public static function countMeta( $posts, $metaValue ): int {
		return count( array_filter( $posts, fn( $post ) => self::getProperty( $post, $metaValue ) ) );
	}

	public static function avgMeta( $posts, $metaValue ): int {
		$sum = self::sumMeta($posts, $metaValue);
		$count = self::countMeta($posts, $metaValue);
		return $sum / $count;
	}
}