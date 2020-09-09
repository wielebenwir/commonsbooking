<?php

namespace CommonsBooking\CB;

use CommonsBooking\Model\User;
use CommonsBooking\Repository\PostRepository;

use function PHPUnit\Framework\isEmpty;

class CB
{

    protected static $INTERNAL_DATE_FORMAT = 'd.m.Y';

    public static $theObjectID;
    public static $key;
    public static $property;
    public static $args;

    public static function getInternalDateFormat() {
        return static::$INTERNAL_DATE_FORMAT;
    }

    /**
     * get
     *
     * @TODO i feel we should not pass ids or args into CB::get(), but instead use a seperate function. it was primaruly built for parsing email templates where we do not have the possibility. 
     * 
     * @param  mixed $key
     * @param  mixed $property
     * @param  mixed $theObject
     * @param  mixed $args
     * @return void
     */
    public static function get($key, $property, $theObject = NULL, $args = NULL)
    {
        
        self::$key = $key;
        self::$args = $args;
        self::substitions($key, $property);         // substitute keys
        self::setupPost($theObject);                  // query sub post or initial post?     
        $result = self::lookUp($args);               // Find matching methods, properties or metadata
        

        $filterName = sprintf('cb_tag_%s_%s', self::$key, self::$property);
        return apply_filters($filterName, $result);


    }

    /**
     * echo
     *
     * @param  mixed $key
     * @param  mixed $property
     * @param  mixed $theObject
     * @return void
     */
    public static function echo($key, $property, $theObject = NULL)
    {
        echo self::get($key, $property, $theObject);
    }

    /**
     * setupPost
     *
     * @param  mixed $initialPost
     * @return void
     */
    private static function setupPost($initialPostId)
    {
        // Set WP Post
        global $post;
    
        
        // we read the post object from the global post if no postID is set
        if (is_null( $initialPostId ) ) {
            $initialPost = $post;
        }             
       
        // we check if we are dealing with a timeframe then get the time timeframe-object as post
        if (is_null($initialPostId) AND isset($_GET['cb_timeframe'])) {
            $initialPost = get_page_by_path($_GET['cb_timeframe'], OBJECT, 'cb_timeframe');
        }

        // set post object from given postID
        if (!is_null($initialPostId)) {
            $initialPost = get_post($initialPostId);
        }

        if (is_null($initialPost)) {
           return false;
        }

        //var_dump($initialPost);       

        // Check post type
        $initialPostType = get_post_type($initialPost);

        // If we are dealing with a timeframe and key ist not booking, we may need to look up the CHILDs post meta, not the parents'
        if ($initialPostType == 'cb_timeframe' and self::$key != "booking" AND self::$key != 'user') {
            $subPostID = get_post_meta($initialPost->ID, self::$key . '-id', TRUE);    // item-id, location-id
            if (get_post_status($subPostID)) { // Post with that ID exists
                $theObjectID =  $subPostID; // we will query the sub post
            } else {
                return 'ERROR: Post ' . $subPostID . ' not found.';
            }
        } else { // Not a timeframe, look at original post meta
            $theObjectID = $initialPost->ID;
        }

        

        self::$theObjectID    = $theObjectID; // e.g. item id
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
        $property_substitutions_array = array();

        $key         = strtr($key, $key_substitutions_array);
        $property     = strtr($property, $property_substitutions_array);

        self::$key         = $key;            // e.g. item
        self::$property = $property;    // e.g. mymetadata

    }

    /**
     * lookUp
     *
     * @return void
     */
    public static function lookUp()
    {
        /** @var PostRepository $repo */
        $repo        = 'CommonsBooking\Repository\\' . ucfirst(self::$key); // we access the Repository not the cpt class here
        $model      = 'CommonsBooking\Model\\' . ucfirst(self::$key); // we check method_exists against model as workaround, cause it doesn't work on repo
        $property     = self::$property;
        $postID        = self::$theObjectID;

        // DEBUG
        //echo "<pre><br>";
        //echo $repo . " -> " . self::$key . " -> "  . $property . " -> " . $postID . " = ";

        /**
         * TODO: Better integration of user class and handling user data / just a first draft right now
         */
        
        // Look up        
        if (class_exists($repo) AND self::$key != 'user') {
            $post = $repo::getByPostById($postID);

            if (method_exists($model, $property)) {
                //echo ($post->$property(self::$args));
                return $post->$property(self::$args);
            }

            if ($post->$property) {
                //echo ($post->$property);
                return $post->$property;
            }
        }

        if (get_post_meta($postID, $property, TRUE)) { // Post has meta fields
            //echo get_post_meta($postID, $property, TRUE);
            return get_post_meta($postID, $property, TRUE);

        
        // if we need user data    
        } elseif (self::$key == 'user') {

            $userID = intval(get_post($postID)->post_author);
            $cb_user = \get_user_by('ID', $userID);
        
            if (method_exists($model, $property)) {
                //echo ($cb_user->$property(self::$args));
                return $cb_user->$property(self::$args);
            }

            if ($cb_user->$property) {
                //echo ($cb_user->$property);
                return $cb_user->$property;
            }

            if (get_user_meta($userID, $property, TRUE)) { // User has meta fields
                //echo get_user_meta($userID, $property, TRUE);
                return $cb_user->get_meta($property);
            }


        }
    }
    
}
