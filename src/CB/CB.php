<?php

namespace CommonsBooking\CB;

use CommonsBooking\Repository\PostRepository;

use function PHPUnit\Framework\isEmpty;

class CB
{

    public static $thePostID;
    public static $key;
    public static $property;

    /**
     * get
     *
     * @param  mixed $key
     * @param  mixed $property
     * @param  mixed $thePost
     * @return void
     */
    public static function get($key, $property, $thePost = NULL)
    {
        self::substitions($key, $property);			// substitute keys
        self::setupPost($thePost);					// query sub post or initial post?
        $result = self::lookUp();					// Find matching methods, properties or metadata

        $filterName = sprintf('cb_tag_%s_%s', self::$key, self::$property);
        return apply_filters($filterName, $result);

        
    }

    /**
     * echo
     *
     * @param  mixed $key
     * @param  mixed $property
     * @param  mixed $thePost
     * @return void
     */
    public static function echo($key, $property, $thePost = NULL)
    {
        echo self::get($key, $property, $thePost);
    }

    /**
     * setupPost
     *
     * @param  mixed $initialPost
     * @return void
     */
    private static function setupPost($initialPost)
    {
        // Set WP Post
        global $post;

        if (is_null($initialPost)) $initialPost = $post;

        // if url = ?cb_timeframe=ID then set initalpost accordingly
        if (is_null($initialPost) and isset($_GET['cb_timeframe'])) {
            $initialPost = get_page_by_path($_GET['cb_timeframe'], OBJECT, 'cb_timeframe');
        }

		if (is_null($initialPost)) {
			return false;
		}

        // Check post type
        $initialPostType = get_post_type($initialPost);
        $initialPost->ID = $initialPost->ID;

		// If we are dealing with a timeframe and key ist not booking, we may need to look up the CHILDs post meta, not the parents'
		if ($initialPostType == 'cb_timeframe' AND self::$key != "booking") {
            $subPostID = get_post_meta($initialPost->ID, self::$key . '-id', TRUE);	// item-id, location-id
            if (get_post_status($subPostID)) { // Post with that ID exists
                $thePostID =  $subPostID; // we will query the sub post
            } else {
                return 'ERROR: Post ' . $subPostID . ' not found.';
            }
        } else { // Not a timeframe, look at original post meta
            $thePostID = $initialPost->ID;
        }
        self::$thePostID	= $thePostID; // e.g. item id
    }

    /**
     * substitions
     *
     * @param  mixed $key
     * @param  mixed $property
     * @return void
     */
    public static function substitions($key, $property)
    {
        //$key 	= strtolower($key);
        //$property = strtolower($property);

        $key_substitutions_array = array(
			//'booking' => 'timeframe',		// so we can use booking_*
        );
        $property_substitutions_array = array(

        );

        $key 		= strtr($key, $key_substitutions_array);
        $property 	= strtr($property, $property_substitutions_array);

        self::$key 		= $key;			// e.g. item
        self::$property = $property;	// e.g. mymetadata

    }

    /**
     * lookUp
     *
     * @return void
     */
    public static function lookUp()
    {
        /** @var PostRepository $repo */
        $repo 		= 'CommonsBooking\Repository\\' . ucfirst(self::$key); // we access the Repository not the cpt class here
        $model      = 'CommonsBooking\Model\\' . ucfirst(self::$key); // we check method_exists against model as workaround, cause it doesn't work on repo
        $property 	= self::$property;
        $postID		= self::$thePostID;

        // DEBUG
        //echo "<pre><br>";
        //echo $repo." -> ". self::$key . " -> "  . $property . " -> " . $postID . " = ";

        // Look up
        if(class_exists($repo)) 
        {
            $post = $repo::getByPostById($postID);

            if (method_exists($model, $property) ) 
            {
                //echo ($post->$property());
                return $post->$property(); 
            }

            if ( $post->$property ) 
            {
                //echo ($post->$property);
                return $post->$property;
            }
        }

        if (get_post_meta($postID, $property, TRUE)) { // Post has meta fields
            //echo get_post_meta($postID, $property, TRUE); 
            return get_post_meta($postID, $property, TRUE);
            
        }
           
    }
}
