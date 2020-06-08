<?php

// namespace CommonsBooking\CB;

class CB
{

	public static $thePostID;
	public static $object;
	public static $property;
	
	
	private static function setPost( $object, $property, $InitialPost )
	{

		// Set WP Post
		global $post;
		if ( is_null( $InitialPost ) ) $InitialPost = $post; 	
		
		// Check post type
		$initialPostType = get_post_type( $InitialPost ); 
		$InitialPost->ID = $InitialPost->ID;

		// If we are dealing with a timeframe, we may need to look up the CHILDs post meta, not the parents'
		if ( $initialPostType == 'cb_timeframe' ) { 
			$subPostID = get_post_meta( $InitialPost->ID , $object .'-id', TRUE );	// item-id, location-id
			if ( get_post_status( $subPostID ) ) { // Post with that ID exists
				$thePostID =  $subPostID; // set up the post
			} else {
				return 'Post ' . $thePostID . ' not found.';
			}
		} else { // Not a timeframe, look at original posts meta
			$thePostID = $InitialPost->ID ;
		}
		self::$object 		= $object;		// e.g. item
		self::$property 	= $property;	// e.g. title
		self::$thePostID	= $thePostID; // item id 
	}
	
	public static function get( $object, $property, $thePost = NULL )
	{
		self::initializer( $object, $property, $thePost );
		self::substitions();
		$tag = self::lookUp();
		return apply_filters( 'cb_tag', $tag ); 
	}

	public static function echo( $object, $property, $thePost = NULL )
	{
		echo self::get( $object, $property, $thePost);
	}

	public static function substitutions( $object, $property, $thePost = NULL )
	{
		echo self::get( $object, $property, $thePost);
	}
	
	public static function lookUp() 
	{
		$Class 		= 'CommonsBooking\Repository\\' . ucfirst( self::$object ); // we access the Repository not the cpt class here
		$property = self::$property;
		$postID			= self::$thePostID;

		// Look up 
		if ( class_exists ( $Class ) && property_exists( $Class, $property ) ) { // Class has property
			// echo( $Class . ' has property:' .  $property);
			$obj = new $Class;
			return $obj->$property;
		} else if ( class_exists ( $Class ) && method_exists( $Class, $property ) ) {  // Class has method
			$obj = new $Class;
			return $obj->$property();
		} else if ( get_post_meta( $postID, $property, TRUE ) ) { // Post has meta fields
			return get_post_meta( $postID, $property, TRUE );
		}
	}
}