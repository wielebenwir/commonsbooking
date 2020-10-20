<?php


namespace CommonsBooking\Repository;


class CB1
{

    /**
     * @var string
     */
    public static $LOCATION_TYPE_ID = 'cb_locations';

    /**
     * @var string
     */
    public static $ITEM_TYPE_ID = 'cb_items';

    /**
     * @var string
     */
    public static $BOOKINGS_TABLE = 'cb_bookings';

    /**
     * @var string
     */
    public static $TIMEFRAMES_TABLE = 'cb_timeframes';

    /**
     * @param $postType
     *
     * @return array
     */
    protected static function get($postType)
    {
        $posts = [];
        $args = array(
            'post_type' => $postType
        );
        /** @var WP_Query $query */
        $query = new \WP_Query($args);
        if ($query->have_posts()) {
            $posts = $query->get_posts();
        }

        return $posts;
    }

    /**
     * @return array
     */
    public static function getLocations()
    {
        return self::get(self::$LOCATION_TYPE_ID);
    }

    /**
     * @return array
     */
    public static function getItems()
    {
        return self::get(self::$ITEM_TYPE_ID);
    }

    /**
     * @return mixed
     */
    public static function getBookings()
    {
        global $wpdb;
        $table_bookings = $wpdb->prefix . self::$BOOKINGS_TABLE;

        return $wpdb->get_results("SELECT * FROM $table_bookings", ARRAY_A);
    }

    /**
     * @return mixed
     */
    public static function getTimeframes()
    {
        global $wpdb;
        $table_timeframes = $wpdb->prefix . self::$TIMEFRAMES_TABLE;

        return $wpdb->get_results("SELECT * FROM $table_timeframes", ARRAY_A);
    }

}
