<?php

namespace CommonsBooking\CB;

use CommonsBooking\Model\CustomPost;
use WP_Post;
use WP_User;
use function get_user_by;

class CB {

	protected static $INTERNAL_DATE_FORMAT = 'd.m.Y';

	public static function getInternalDateFormat(): string {
		return static::$INTERNAL_DATE_FORMAT;
	}

	/**
	 * echo
	 *
	 * @param mixed $key
	 * @param mixed $property
	 * @param mixed $theObject
	 *
	 * @return void
	 */
	public static function echo( $key, $property, $theObject = null ) {
		echo commonsbooking_sanitizeHTML( self::get( $key, $property, $theObject ) );
	}

	/**
	 * Returns property of (custom) post by class key and property.
	 *
	 * @param mixed $key
	 * @param mixed $property
	 * @param null $post (can be a post-id or a user-id)
	 * @param mixed $args
	 *
	 * @return mixed
	 */
	public static function get( $key, $property, $post = null, $args = null ) {

		// first we need to check if we are dealing with a post and set the post object properly
			if ( ! $post ) {
				$postId = self::getPostId( $key );
				$post   = get_post( $postId );
			} else if ( ! ( $post instanceof WP_Post ) && ! ( $post instanceof CustomPost ) && ! ( $post instanceof WP_user ) ) {
				$post = get_post( intval( $post ) );
			}

		$result     = self::lookUp( $key, $property, $post, $args );  // Find matching methods, properties or metadata
		$filterName = sprintf( 'cb_tag_%s_%s', $key, $property );

		return apply_filters( $filterName, $result );
	}

	/**
	 * Returns post id by class name of (custom) post.
	 *
	 * @param string $key
	 *
	 * @return int|null
	 */
	private static function getPostId( string $key ): ?int {
		$postId = null;

		// Set WP Post
		global $post;

		// we read the post object from the global post if no postID is set
		$initialPost = $post;

		// we check if we are dealing with a timeframe then get the time timeframe-object as post
		if ( isset( $_GET['cb_timeframe'] ) ) {
			$initialPost = get_page_by_path( sanitize_text_field( $_GET['cb_timeframe'] ), OBJECT, 'cb_timeframe' );
		}

		if ( ! is_null( $initialPost ) ) {
			// Check post type
			$initialPostType = get_post_type( $initialPost );

			// If we are dealing with a timeframe and key ist not booking, we may need to look up the CHILDs post meta, not the parents'
			if ( $initialPostType == 'cb_timeframe' and $key != "booking" and $key != 'user' ) {
				$subPostID = get_post_meta( $initialPost->ID, $key . '-id', true );    // item-id, location-id
				if ( get_post_status( $subPostID ) ) { // Post with that ID exists
					$postId = $subPostID; // we will query the sub post
				}
			} else { // Not a timeframe, look at original post meta
				$postId = $initialPost->ID;
			}
		}

		return $postId;
	}

	/**
	 * @param $key
	 * @param $property
	 * @param $post
	 * @param $args
	 *
	 * @return string|null
	 */
	public static function lookUp( $key, $property, $post, $args ): ?string {
		// in any case we need the post object, otherwise we cannot return anything
		if ( ! $post ) {
			return null;
		}

		if ( $key == 'user' ) {
			$result = self::getUserProperty( $post, $property, $args );
		} else {
			$result = self::getPostProperty( $post, $property, $args );
		}

		if ( $result ) {
			// sanitize output
			return commonsbooking_sanitizeHTML( $result );
		}

		return $result;
	}

	/**
	 * Tries to get a property of a post with different approaches.
	 * @param $post
	 * @param $property
	 * @param $args
	 *
	 * @return mixed|null
	 */
	private static function getPostProperty( $post, $property, $args ) {
		$result = null;

		if ( get_post_meta( $post, $property, true ) ) { // Post has meta fields
			$result = get_post_meta( $post, $property, true );
		}

		if ( method_exists( $post, $property ) ) {
			$result = $post->$property( $args );
		}

		$prefixedProperty = 'get' . ucfirst( $property );
		if ( ! $result && method_exists( $post, $prefixedProperty ) ) {
			$result = $post->$prefixedProperty( $args );
		}

		if ( ! $result && $post->$property ) {
			$result = $post->$property;
		}

		return $result;
	}

	/**
	 * Tries to get a property of a user with different approaches.
	 * @param $post
	 * @param $property
	 * @param $args
	 *
	 * @return int|mixed|null
	 */
	private static function getUserProperty( $post, $property, $args ) {
		$result = null;
		// If user is defined and we don't use the post author
		if ( $post instanceof WP_User ) {
			$cb_user = $post;
		} else {
			$userID  = intval( $post->post_author );
			$cb_user = get_user_by( 'ID', $userID );
		}

		if ( method_exists( $cb_user, $property ) ) {
			$result = $cb_user->$property( $args );
		}

		if ( ! $result && $cb_user->$property ) {
			$result = $cb_user->$property;
		}

		if ( ! $result && get_user_meta( $cb_user->ID, $property, true ) ) { // User has meta fields
			$result = get_user_meta( $cb_user->ID, $property, true );
		}

		return $result;
	}

}
