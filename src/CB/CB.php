<?php

namespace CommonsBooking\CB;

use CommonsBooking\Helper\Helper;
use CommonsBooking\Model\CustomPost;
use CommonsBooking\Wordpress\CustomPostType\Booking;
use Exception;
use WP_Post;
use WP_User;
use function get_user_by;

class CB {

	protected static $INTERNAL_DATE_FORMAT = 'd.m.Y';

	public static function getInternalDateFormat(): string {
		return static::$INTERNAL_DATE_FORMAT;
	}

	/**
	 * Returns property of (custom) post by class key and property.
	 *
	 * @param string                                $key
	 * @param string                                $property
	 * @param mixed|CustomPost|WP_Post|WP_User|null $wpObject
	 * @param mixed|null                            $args
	 * @param callable                              $sanitizeFunction The callable used to remove unwanted tags/characters (use default 'commonsbooking_sanitizeHTML' or 'sanitize_text_field')
	 *
	 * @since 2.10.5 parameters in doc are correctly typed.
	 *
	 * @return null|string property of (custom) post (sanitized) or null if not found
	 * @throws Exception
	 */
	public static function get( $key, $property, $wpObject = null, $args = null, $sanitizeFunction = 'commonsbooking_sanitizeHTML' ) {

		// Only CustomPost, WP_User or WP_Post ist allowed.
		if ( $wpObject && ! (
			( $wpObject instanceof WP_Post ) ||
			( $wpObject instanceof WP_User ) ||
			( $wpObject instanceof CustomPost )
		) ) {
			throw new Exception( 'invalid object type.' );
		}

		// first we need to check if we are dealing with a post and set the post object properly
		if ( ! $wpObject ) {
			$postId   = self::getPostId( $key );
			$wpObject = get_post( $postId );
		}

		// If possible cast to CB Custom Post Type Model to get additional functions
		$wpObject = Helper::castToCBCustomType( $wpObject, $key );

		// Find matching methods, properties or metadata
		$result = self::lookUp( $key, $property, $wpObject, $args, $sanitizeFunction );

		/**
		 * Default value for post type properties.
		 *
		 * The dynamic part of the hook $key is the name of the post type and the $property is the name of the meta
		 * field.
		 *
		 * @since 2.7.1 refactored filter name from cb_tag_* to its current form
		 * @since 2.1.1
		 *
		 * @param string|null $result from property lookup
		 */
		return apply_filters( "commonsbooking_tag_{$key}_{$property}", $result, $wpObject, $args );
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
			if ( $initialPostType == 'cb_timeframe' &&
				$key != Booking::$postType &&
				$key != 'user'
			) {
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
	 * @param string   $key
	 * @param string   $property
	 * @param $post
	 * @param $args
	 * @param callable $sanitizeFunction The callable used to remove unwanted tags/characters
	 *
	 * @return string|null
	 * @throws Exception
	 */
	public static function lookUp( string $key, string $property, $post, $args, $sanitizeFunction ): ?string {
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
			return call_user_func( $sanitizeFunction, $result );
		}

		return $result;
	}

	/**
	 * Tries to get a property of a post with different approaches.
	 *
	 * @param $post
	 * @param $property
	 * @param $args
	 *
	 * @return mixed|null
	 */
	private static function getPostProperty( $post, $property, $args ) {
		$result = null;

		$postId = is_int( $post ) ? $post : $post->ID;

		if ( get_post_meta( $postId, $property, true ) ) { // Post has meta fields
			$result = get_post_meta( $postId, $property, true );
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
	 *
	 * @param WP_Post|WP_User $post
	 * @param string          $property
	 * @param $args
	 *
	 * @return int|mixed|null
	 * @throws Exception
	 */
	private static function getUserProperty( $post, string $property, $args ) {
		$result = null;

		$cb_user = self::getUserFromObject( $post );

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

	/**
	 * @param $object
	 *
	 * @return false|WP_User
	 * @throws Exception
	 */
	private static function getUserFromObject( $object ) {
		// Check if $post is of type WP_Post, then we're using Author as User
		if ( $object instanceof WP_Post ) {
			$userID = intval( $object->post_author );
			return get_userdata( $userID );

			// Check if $post is of Type WP_User, than we can use it directly.
		} elseif ( $object instanceof WP_User ) {
			return $object;

			// Other types than WP_Post or WP_User are not allowed
		} else {
			throw new Exception( 'invalid $post type.' );
		}
	}
}
