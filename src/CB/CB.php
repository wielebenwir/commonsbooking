<?php

// namespace CommonsBooking\CB; @TODO

class CB
{

	public static $thePostID;
	public static $key;
	public static $property;
	
	public static function get( $key, $property, $thePost = NULL )
	{
		self::substitions( $key, $property );			// substitute keys
		self::setupPost( $thePost );							// query sub post or initial post?  
		$tag = self::lookUp();										// Find machting methods, properties or metadata
		
		return apply_filters( 'cb_tag', $tag ); 
	}

	public static function echo( $key, $property, $thePost = NULL )
	{
		echo self::get( $key, $property, $thePost);
	}
	
	private static function setupPost( $initialPost )
	{
		// Set WP Post
		global $post;
		if ( is_null( $initialPost ) ) $initialPost = $post; 	
		
		// Check post type
		$initialPostType = get_post_type( $initialPost ); 
		$initialPost->ID = $initialPost->ID;

		// If we are dealing with a timeframe, we may need to look up the CHILDs post meta, not the parents'
		if ( $initialPostType == 'cb_timeframe' ) { 
			$subPostID = get_post_meta( $initialPost->ID , self::$key .'-id', TRUE );	// item-id, location-id
			if ( get_post_status( $subPostID ) ) { // Post with that ID exists
				$thePostID =  $subPostID; // we will query the sub post
			} else {
				return 'Post ' . $thePostID . ' not found.';
			}
		} else { // Not a timeframe, look at original post meta
			$thePostID = $initialPost->ID ;
		}
		self::$thePostID	= $thePostID; // e.g. item id 
	}

	public static function substitions( $key, $property )
	{
		$key 	= strtolower( $key );
		$property = strtolower( $property );

		$substitutions_array = array (
			'booking' => 'timeframe',		// so we can use booking_* 
		);

		$key 	= strtr( $key, $substitutions_array );
		$property = strtr( $property, $substitutions_array );

		self::$key 		= $key;		// e.g. item
		self::$property 	= $property;	// e.g. mymetadata

	}
	
	public static function lookUp() 
	{
		$Class 		= 'CommonsBooking\Repository\\' . ucfirst( self::$key ); // we access the Repository not the cpt class here
		$property = self::$property;
		$postID			= self::$thePostID;

		// Look up 
		if ( class_exists ( $Class ) && property_exists( $Class, $property ) ) { // Class has property
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