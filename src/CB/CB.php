<?php

namespace CommonsBooking\CB;

use Exception;
use function get_user_by;

class CB {

	public static $theObjectID;
	public static $key;
	public static $property;
	public static $args;
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
		echo self::get( $key, $property, $theObject );
	}

	/**
	 * get
	 *
	 * @TODO i feel we should not pass ids or args into CB::get(), but instead use a seperate function. it was primaruly built for parsing email templates where we do not have the possibility.
	 *
	 * @param mixed $key
	 * @param mixed $property
	 * @param mixed $theObject
	 * @param mixed $args
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public static function get( $key, $property, $theObject = null, $args = null ) {

		self::$key  = $key;
		self::$args = $args;
		self::substitions( $key, $property );         // substitute keys
		self::setupPost( $theObject );                  // query sub post or initial post?
		$result = self::lookUp();               // Find matching methods, properties or metadata


		$filterName = sprintf( 'cb_tag_%s_%s', self::$key, self::$property );

		return apply_filters( $filterName, $result );
	}

	/**
	 * substitions
	 *
	 * @param mixed $key
	 * @param mixed $property
	 *
	 * @return void
	 */
	public static function substitions( $key, $property ) {
		//$key 	= strtolower($key);
		//$property = strtolower($property);

		$key_substitutions_array      = array(//'booking' => 'timeframe',		// so we can use booking_*
		);
		$property_substitutions_array = array();

		$key      = strtr( $key, $key_substitutions_array );
		$property = strtr( $property, $property_substitutions_array );

		self::$key      = $key;            // e.g. item
		self::$property = $property;    // e.g. mymetadata

	}

	/**
	 * setupPost
	 *
	 * @param $initialPostId
	 *
	 * @return void
	 * @throws Exception
	 */
	private static function setupPost( $initialPostId ) {
		// Set WP Post
		global $post;


		// we read the post object from the global post if no postID is set
		if ( is_null( $initialPostId ) ) {
			$initialPost = $post;
		}

		// we check if we are dealing with a timeframe then get the time timeframe-object as post
		if ( is_null( $initialPostId ) and isset( $_GET['cb_timeframe'] ) ) {
			$initialPost = get_page_by_path( sanitize_text_field( $_GET['cb_timeframe'] ), OBJECT, 'cb_timeframe' );
		}

		// set post object from given postID
		if ( ! is_null( $initialPostId ) ) {
			$initialPost = get_post( $initialPostId );
		}

		if ( ! is_null( $initialPost ) ) {
			// Check post type
			$initialPostType = get_post_type( $initialPost );

			// If we are dealing with a timeframe and key ist not booking, we may need to look up the CHILDs post meta, not the parents'
			if ( $initialPostType == 'cb_timeframe' and self::$key != "booking" and self::$key != 'user' ) {
				$subPostID = get_post_meta( $initialPost->ID, self::$key . '-id', true );    // item-id, location-id
				if ( get_post_status( $subPostID ) ) { // Post with that ID exists
					$theObjectID = $subPostID; // we will query the sub post
				} else {
					throw new Exception('ERROR: Post ' . $subPostID . ' not found.');
				}
			} else { // Not a timeframe, look at original post meta
				$theObjectID = $initialPost->ID;
			}

			self::$theObjectID = $theObjectID; // e.g. item id
		}
	}

	/**
	 * @throws Exception
	 */
	public static function lookUp(): ?string {

		$result = '';

		$repo     = 'CommonsBooking\Repository\\' . ucfirst( self::$key ); // we access the Repository not the cpt class here
		$model    = 'CommonsBooking\Model\\' . ucfirst( self::$key ); // we check method_exists against model as workaround, cause it doesn't work on repo
		$property = self::$property;
		$postID   = self::$theObjectID;


		/**
		 * TODO: Better integration of user class and handling user data / just a first draft right now
		 */

		// Look up
		if ( class_exists( $repo ) and self::$key != 'user' ) {
			$post = $repo::getPostById( $postID );

			if ( method_exists( $model, $property ) ) {
				$result = $post->$property( self::$args );
			}

			if ( $post->$property ) {
				$result = $post->$property;
			}
		}

		if ( get_post_meta( $postID, $property, true ) ) { // Post has meta fields
			$result = get_post_meta( $postID, $property, true );


			// if we need user data
		} elseif ( self::$key == 'user' ) {

			$userID  = intval( get_post( $postID )->post_author );
			$cb_user = get_user_by( 'ID', $userID );

			if ( method_exists( $model, $property ) ) {
				$result = $cb_user->$property( self::$args );
			}

			if ( $cb_user->$property ) {
				$result = $cb_user->$property;
			}

			if ( get_user_meta( $userID, $property, true ) ) { // User has meta fields
				$result = get_user_meta( $userID, $property, true );
			}


		}

		if ( $result ) {
			// sanitize output
			return commonsbooking_sanitizeHTML( $result );
		}

		return null;
	}

}
