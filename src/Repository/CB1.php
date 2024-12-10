<?php


namespace CommonsBooking\Repository;

use WP_Query;

/**
 * This class contains methods to query the database for old CB1 data.
 * CB1 is CommonsBooking up to version 0.9.4.18 (https://wordpress.org/plugins/commons-booking/)
 * This class is mainly used to migrate over the old data to CB2.
 */
class CB1 {

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
	public static $BOOKINGCODES_TABLE = 'cb_codes';

	/**
	 * @var string
	 */
	public static $TIMEFRAMES_TABLE = 'cb_timeframes';

	/**
	 * This function is able to check if the old CB1 plugin is installed.
	 *
	 * @return bool
	 */
	public static function isInstalled() {
		// we check for pages, since they have to be set up for the plugin to function.
		$option_set_by_cb1 = esc_html( get_option( 'commons-booking-settings-pages' ) );

		if ( $option_set_by_cb1 ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get the old CB1 location post type posts
	 *
	 * @return array
	 */
	public static function getLocations() {
		return self::get( self::$LOCATION_TYPE_ID );
	}

	/**
	 * @param $postType
	 *
	 * @return array
	 */
	protected static function get( $postType ) {
		$posts = array();
		$args  = array(
			'post_type'   => $postType,
			'post_status' => 'any',
			'nopaging'    => true,
		);
		$query = new WP_Query( $args );
		if ( $query->have_posts() ) {
			$posts = $query->get_posts();
		}

		return $posts;
	}

	/**
	 * Get the old CB1 item post type posts
	 *
	 * @return array
	 */
	public static function getItems() {
		return self::get( self::$ITEM_TYPE_ID );
	}

	/**
	 * Get the old CB1 bookings
	 *
	 * @return mixed
	 */
	public static function getBookings() {
		global $wpdb;
		$table_bookings = $wpdb->prefix . self::$BOOKINGS_TABLE;

		return $wpdb->get_results( "SELECT * FROM $table_bookings", ARRAY_A );
	}

	/**
	 * Get the old CB1 timeframes
	 *
	 * @return mixed
	 */
	public static function getTimeframes() {
		global $wpdb;
		$table_timeframes = $wpdb->prefix . self::$TIMEFRAMES_TABLE;

		return $wpdb->get_results( "SELECT * FROM $table_timeframes", ARRAY_A );
	}

	/**
	 * Get the old CB1 bookingcodes
	 *
	 * @return mixed
	 */
	public static function getBookingCodes() {
		global $wpdb;
		$table_bookingcodes = $wpdb->prefix . self::$BOOKINGCODES_TABLE;
		$table_timeframes   = $wpdb->prefix . self::$TIMEFRAMES_TABLE;

		return $wpdb->get_results(
			"SELECT
                c.booking_date,
                c.item_id,
                t.id as timeframe_id,
                t.location_id,
                c.bookingcode
            FROM $table_bookingcodes c, $table_timeframes t
            WHERE
                c.item_id = t.item_id AND
                c.booking_date >= t.date_start AND
                c.booking_date <= t.date_end
            ",
			ARRAY_A
		);
	}

	/**
	 *
	 * @param $id
	 *
	 * @return ?string
	 */
	public static function getBookingCode( $id ): ?string {
		global $wpdb;
		$table_bookingcodes = $wpdb->prefix . self::$BOOKINGCODES_TABLE;

		$sql    = $wpdb->prepare( "SELECT bookingcode FROM $table_bookingcodes WHERE id = %d", $id );
		$result = $wpdb->get_results( $sql, ARRAY_A );

		if ( $result && count( $result ) > 0 ) {
			return $result[0]['bookingcode'];
		}
		return null;
	}

	/**
	 * Returns CB2 Location-ID.
	 *
	 * @param $locationId "CB 1 Location-ID"
	 *
	 * @return int|false
	 */
	public static function getCB2LocationId( int $locationId ) {
		return self::getCB2PostIdByType( $locationId, \CommonsBooking\Wordpress\CustomPostType\Location::$postType );
	}

	/**
	 * @param int    $id
	 * @param string $type
	 *
	 * @return false|int
	 */
	protected static function getCB2PostIdByType( int $id, string $type ) {
		global $wpdb;
		$table_postmeta = $wpdb->prefix . 'postmeta';
		$table_posts    = $wpdb->prefix . 'posts';

		$sql    = $wpdb->prepare(
			"SELECT post_id FROM $table_postmeta
            WHERE
                meta_key = '_cb_cb1_post_post_ID' AND
                meta_value = %d AND
                post_id in (SELECT id from $table_posts where post_type = %s);
            ",
			$id,
			$type
		);
		$result = $wpdb->get_results( $sql );

		if ( $result && count( $result ) > 0 ) {
			return $result[0]->post_id;
		}

		return false;
	}

	/**
	 * Returns CB2 Location-ID.
	 *
	 * @param $locationId
	 *
	 * @return false|int
	 */
	public static function getCB2ItemId( $locationId ) {
		return self::getCB2PostIdByType( $locationId, \CommonsBooking\Wordpress\CustomPostType\Item::$postType );
	}

	/**
	 * @param $timeframeId
	 *
	 * @return false|int
	 */
	public static function getCB2TimeframeId( $timeframeId ) {
		return self::getCB2PostIdByType( $timeframeId, \CommonsBooking\Wordpress\CustomPostType\Timeframe::$postType );
	}

	/**
	 * Returns CB2 post id for CB1 post id, if available.
	 *
	 * @param $id
	 *
	 * @return ?int
	 */
	public static function getCB2PostIdByCB1Id( $id ): ?int {
		global $wpdb;
		$table_postmeta = $wpdb->prefix . 'postmeta';

		$sql    = $wpdb->prepare(
			"SELECT meta_value as cb1_id, post_id as cb2_id 
            FROM $table_postmeta
            WHERE
                meta_key = '_cb_cb1_post_post_ID' AND 
                meta_value = %s;
        	",
			$id
		);
		$result = $wpdb->get_results( $sql );

		if ( $result && count( $result ) > 0 ) {
			return $result[0]->cb2_id;
		}

		return null;
	}

	/**
	 * Returns CB1 taxonomies.
	 *
	 * @return mixed
	 */
	public static function getCB1Taxonomies() {
		global $wpdb;
		$table_postmeta           = $wpdb->prefix . 'postmeta';
		$table_term_relationships = $wpdb->prefix . 'term_relationships';
		$table_term_taxonomy      = $wpdb->prefix . 'term_taxonomy';
		$table_terms              = $wpdb->prefix . 'terms';

		return $wpdb->get_results(
			"
            SELECT
                tr.*, 
                tt.taxonomy,
                t.slug as term
            FROM $table_term_relationships tr 
            LEFT JOIN $table_term_taxonomy tt ON
                tr.term_taxonomy_id = tt.term_id
            LEFT JOIN $table_terms t ON
                t.term_id = tt.term_id
            WHERE tr.object_id IN (
                SELECT meta_value
                FROM $table_postmeta
                WHERE
                    meta_key = '_cb_cb1_post_post_ID'
            );
        "
		);
	}
}
