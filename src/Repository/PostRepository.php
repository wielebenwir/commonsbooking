<?php


namespace CommonsBooking\Repository;

use CommonsBooking\Plugin;
use WP_Post;

abstract class PostRepository {

	/**
	 * Returns post by id as CB-CPT if possible.
	 * Will try to return a model class if possible.
	 *
	 * @param int $postId
	 *
	 * @return \CommonsBooking\Model\Booking|\CommonsBooking\Model\Item|\CommonsBooking\Model\Location|mixed|WP_Post
	 * @throws \Psr\Cache\CacheException
	 * @throws \Psr\Cache\InvalidArgumentException
	 */
	public static function getPostById( $postId ) {
		$cacheItem = Plugin::getCacheItem();
		if ( $cacheItem ) {
			return $cacheItem;
		} else {
			$post = get_post( $postId );

			if ( $post instanceof WP_Post ) {
				if ( $post->post_type == \CommonsBooking\Wordpress\CustomPostType\Timeframe::getPostType() ) {
					$type = get_post_meta( $post->ID, 'type', true );
					switch ( $type ) {
						case \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_ID: // booking
							return new \CommonsBooking\Model\Booking( $post );
						case \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKING_CANCELED_ID: // booking canceled
							return new \CommonsBooking\Model\Booking( $post );
					}
				}

				if ( $post->post_type == \CommonsBooking\Wordpress\CustomPostType\Item::getPostType() ) {
					return new \CommonsBooking\Model\Item( $post );
				}

				if ( $post->post_type == \CommonsBooking\Wordpress\CustomPostType\Location::getPostType() ) {
					return new \CommonsBooking\Model\Location( $post );
				}

				if ( $post->post_type == \CommonsBooking\Wordpress\CustomPostType\Restriction::getPostType() ) {
					return new \CommonsBooking\Model\Restriction( $post );
				}

				if ( $post->post_type == \CommonsBooking\Wordpress\CustomPostType\Booking::getPostType() ) {
					return new \CommonsBooking\Model\Booking( $post );
				}
			}
			Plugin::setCacheItem( $post, [ $postId ] );

			return $post;
		}
	}
}
