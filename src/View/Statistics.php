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
				$posts = \CommonsBooking\Repository\Booking::get( [], [], null, true );
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

	public static function countConfirmed( $posts ): int {
		$bookings = array_filter( $posts, fn( $post ) => $post->post_type == 'cb_booking' );
		return count( array_filter( $bookings, fn( $booking ) => $booking->isConfirmed() ) );
	}

	public static function sumMeta( $posts, $metaValue ): int {
		return array_sum( array_map( fn( $post ) => $post->getMeta( $metaValue ), $posts ) );
	}

	public static function countMeta( $posts, $metaValue ): int {
		return count( array_filter( $posts, fn( $post ) => $post->getMeta( $metaValue ) ) );
	}
}