<?php


namespace CommonsBooking\Migration;


use CommonsBooking\Model\BookingCode;
use CommonsBooking\Repository\BookingCodes;
use CommonsBooking\Repository\CB1;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Wordpress\CustomPostType\CustomPostType;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;

class Migration
{

    /**
     * @return int[]
     * @throws \Exception
     */
    public static function migrateAll($type)
    {
        
        
        $results = [
            'locations'    => 0,
            'items'        => 0,
            'timeframes'   => 0,
            'bookings'     => 0,
            'bookingCodes' => 0,
            'termsUrl'     => 0,
            'taxonomies'   => 0
        ];

        if ($type == "locations") {
            foreach (CB1::getLocations() as $location) {
                if (self::migrateLocation($location)) {
                    $results['locations'] += 1;
                }
            }
        }

        if ($type == "items") {
            foreach (CB1::getItems() as $item) {
                if (self::migrateItem($item)) {
                    $results['items'] += 1;
                }
            }
        }

        
        if ($type == "timeframes") {
            foreach (CB1::getTimeframes() as $timeframe) {
                if (self::migrateTimeframe($timeframe)) {
                    $results['timeframes'] += 1;
                }
            }
        }

        if ($type == "bookings") {
            $bookingcounter = 1;
            foreach (CB1::getBookings() as $booking) {
                $bookingcounter += 1;
                if ($bookingcounter % 200 == 0) {
                    echo "... pause ... ";
                    flush();
                    sleep(1);
                }
                if (self::migrateBooking($booking)) {
                    $results['bookings'] += 1;
                }
            }
        }
          

        if ($type == "bookingCodes") {
            foreach (CB1::getBookingCodes() as $bookingCode) {
                if (self::migrateBookingCode($bookingCode)) {
                    $results['bookingCodes'] += 1;
                }
            }
        }        
        

        if ($type == "termsUrl") {
            if (self::migrateUserAgreementUrl()) {
                $results['termsUrl'] += 1;
            }
        }

        if ($type == "taxonomies") {
            foreach(CB1::getCB1Taxonomies() as $cb1Taxonomy) {
                if (self::migrateTaxonomy($cb1Taxonomy)) {
                    $results['taxonomies'] += 1;
                }
            }
        }

        return $results;
    }


    /**
     * fetchEmails
     * extract mails from a given string and return an array with email addresses
     *
     * @param  mixed $text
     * @return ARRAY
     */
    public static function fetchEmails($text) {
        $words = str_word_count($text, 1, '.@-_');
        return array_filter($words, function($word) {return filter_var($word, FILTER_VALIDATE_EMAIL);});
    }

    /**
     * @param \WP_Post $location CB1 Location
     *
     * @throws \Exception
     */
    public static function migrateLocation(\WP_Post $location)
    {
        // Collect post data
        $postData = array_merge($location->to_array(), [
                'post_type' => Location::$postType
            ]
        );

        // Remove existing post id
        unset($postData['ID']);

        // Exctract e-mails from CB1 contactinfo field so we can migrate it into new cb2 field _cb_location_email
        $cb1_location_emails = self::fetchEmails( get_post_meta( $location->ID, 'commons-booking_location_contactinfo_text', true ) );

        if ($cb1_location_emails) {
            $cb1_location_email_string = implode(',', $cb1_location_emails);
        } else {
            $cb1_location_email_string = '';
        }

        // Allow overbooking of locked days where no timeframes are defined
        $allowClosed = \CommonsBooking\Settings\Settings::getOption(
            'commons-booking-settings-bookings',
            'commons-booking_bookingsettings_allowclosed'
        ) == 'on';

        // CB2 <-> CB1
        $postMeta = [
            CB_METABOX_PREFIX . 'location_street'   => get_post_meta($location->ID,
                'commons-booking_location_adress_street', true),
            CB_METABOX_PREFIX . 'location_city'     => get_post_meta($location->ID,
                'commons-booking_location_adress_city', true),
            CB_METABOX_PREFIX . 'location_postcode' => get_post_meta($location->ID,
                'commons-booking_location_adress_zip', true),
            CB_METABOX_PREFIX . 'location_country'  => get_post_meta($location->ID,
                'commons-booking_location_adress_country', true),
            CB_METABOX_PREFIX . 'location_contact'  => get_post_meta($location->ID,
                'commons-booking_location_contactinfo_text', true),
            CB_METABOX_PREFIX . 'location_pickupinstructions'  => get_post_meta($location->ID,
            'commons-booking_location_openinghours', true),
            CB_METABOX_PREFIX . 'location_email'  => $cb1_location_email_string,
            CB_METABOX_PREFIX . 'cb1_post_post_ID'  => $location->ID,
            '_thumbnail_id' => get_post_meta($location->ID, '_thumbnail_id', true),
            CB_METABOX_PREFIX . 'allow_lockdays_in_range' => $allowClosed
        ];

        $existingPost = self::getExistingPost($location->ID, Location::$postType);

        return self::savePostData($existingPost, $postData, $postMeta);
    }

    /**
     * @param $id
     * @param $type
     *
     * @return mixed
     * @throws \Exception
     */
    public static function getExistingPost($id, $type)
    {
        $args = array(
            'meta_key'     => CB_METABOX_PREFIX . 'cb1_post_post_ID',
            'meta_value'   => $id,
            'meta_compare' => '=',
            'post_type'    => $type
        );

        /** @var WP_Query $query */
        $query = new \WP_Query($args);
        if ($query->have_posts()) {
            $posts = $query->get_posts();
            if (count($posts) > 1) {
                throw new \Exception('Migration duplicates found.');
            }
            if (count($posts) == 1) {
                return $posts[0];
            }
        }
    }

    /**
     * @param $existingPost
     * @param $postData array Post data
     * @param $postMeta array Post meta
     *
     * @return bool
     */
    protected static function savePostData($existingPost, $postData, array $postMeta)
    {
        if ($existingPost instanceof \WP_Post) {
            $updatedPost = array_merge($existingPost->to_array(), $postData);
            $postId = wp_update_post($updatedPost);
        } else {
            $postId = wp_insert_post($postData);
        }
        if ($postId) {
            foreach ($postMeta as $key => $value) {
                update_post_meta(
                    $postId,
                    $key,
                    $value
                );
            }

            return true;
        }

        return false;
    }

    /**
     * @param \WP_Post $item
     *
     * @throws \Exception
     */
    public static function migrateItem(\WP_Post $item)
    {
        // Collect post data
        $postData = array_merge($item->to_array(), [
                'post_type'    => Item::$postType,
                'post_excerpt' => get_post_meta($item->ID,
                    'commons-booking_item_descr', true)
            ]
        );

        // Remove existing post id
        unset($postData['ID']);

        // CB2 <-> CB1
        $postMeta = [
            CB_METABOX_PREFIX . 'cb1_post_post_ID' => $item->ID,
            '_thumbnail_id' => get_post_meta($item->ID, '_thumbnail_id', true)
        ];

        $existingPost = self::getExistingPost($item->ID, Item::$postType);

        return self::savePostData($existingPost, $postData, $postMeta);
    }

    /**
     * @param $timeframe
     *
     * @throws \Exception
     */
    public static function migrateTimeframe($timeframe)
    {
        $cbItem = self::getExistingPost($timeframe['item_id'], Item::$postType);
        $cbLocation = self::getExistingPost($timeframe['location_id'], Location::$postType);
        $cb1_closeddays = '';
        $weekdays = '';

        //get closed days in cb1 timeframe to migrate them into new cb timeframe weekdays (inversion of days)
        $cb1_closeddays = get_post_meta($timeframe['location_id'], 'commons-booking_location_closeddays', true);
        if (is_array($cb1_closeddays)) {
            $weekdays = array(1,2,3,4,5,6,7);
            $weekdays = array_diff($weekdays, $cb1_closeddays);
            $timeframe_repetition = "w"; //set repetition do weekly
        } else {
            $timeframe_repetition = "d"; // set repetition to daily
        }


        if ( ! $cbItem || ! $cbLocation) {
            //throw new \Exception('timeframe could not created, because linked location or item does not exist.');
        }

        // Collect post data
        $postData = [
            'post_title'  => $timeframe['timeframe_title'],
            'post_type'   => Timeframe::$postType,
            'post_name'   => CustomPostType::generateRandomSlug(),
            'post_status' => 'confirmed'
        ];

        // CB2 <-> CB1
        $postMeta = [
            CB_METABOX_PREFIX . 'cb1_post_post_ID' => $timeframe['id'],
            'repetition-start'                     => strtotime($timeframe['date_start']),
            'repetition-end'                       => strtotime($timeframe['date_end']),
            'item-id'                              => $cbItem->ID,
            'location-id'                          => $cbLocation->ID,
            'type'                                 => Timeframe::BOOKABLE_ID,
            'timeframe-repetition'                 => $timeframe_repetition,
            'start-time'                           => '00:00',
            'end-time'                             => '23:59',
            'full-day'                             => 'on',
            'grid'                                 => '0',
            'weekdays'                             => $weekdays,
        ];

        $existingPost = self::getExistingPost($timeframe['id'], Timeframe::$postType);

        return self::savePostData($existingPost, $postData, $postMeta);
    }

    /**
     * @param $booking
     *
     * @throws \Exception
     */
    public static function migrateBooking($booking)
    {
        $user = get_user_by('id', $booking['user_id']);
        $cbItem = self::getExistingPost($booking['item_id'], Item::$postType);
        $cbLocation = self::getExistingPost($booking['location_id'], Location::$postType);

        if ( ! $user || ! $cbItem || ! $cbLocation) {
                echo "booking from id: " . $booking['id'] . "could not be created, because one of the following entries are missing: user-id: " . $booking['user_id'] . " | item-id: " . $booking['item_id'] . " | location-id: " . $booking['location_id'] . "<br>" ;
                flush();
                return false;
            }

            //throw new \Exception('booking could not created, because user or linked location or item does not exist.');
            //echo __('booking could not created because user or linked location or item does not exist', 'commonsbooking');
        

        // Collect post data
        $postData = [
            'post_title'  => 'Buchung CB1-Import ' . $user->get('user_nicename') . ' - ' . $booking['date_start'],
            'post_type'   => Timeframe::$postType,
            'post_name'   => CustomPostType::generateRandomSlug(),
            'post_status' => 'confirmed'
        ];

        // CB2 <-> CB1
        $postMeta = [
            CB_METABOX_PREFIX . 'cb1_post_post_ID' => $booking['id'],
            'repetition-start'                     => strtotime($booking['date_start']),
            'repetition-end'                       => strtotime($booking['date_end']),
            'item-id'                              => $cbItem->ID,
            'location-id'                          => $cbLocation->ID,
            'type'                                 => Timeframe::BOOKING_ID,
            'timeframe-repetition'                 => 'norep',
            'start-time'                           => '00:00',
            'end-time'                             => '23:59',
            'full-day'                             => 'on',
            'grid'                                 => '0',
            CB_METABOX_PREFIX . 'bookingcode'      => CB1::getBookingCode($booking['code_id'])
        ];

        $existingPost = self::getExistingPost($booking['id'], Timeframe::$postType);

        return self::savePostData($existingPost, $postData, $postMeta);
    }

    /**
     * Migrates CB1 Booking Code to CB2.
     *
     * @param $bookingCode
     *
     * @return mixed
     */
    public static function migrateBookingCode($bookingCode)
    {
        $cb2LocationId = CB1::getCB2LocationId($bookingCode['location_id']);
        $cb2ItemId = CB1::getCB2ItemId($bookingCode['item_id']);
        $cb2TimeframeId = CB1::getCB2TimeframeId($bookingCode['timeframe_id']);
        $date = $bookingCode['booking_date'];
        $code = $bookingCode['bookingcode'];

        $bookingCode = new BookingCode(
            $date,
            $cb2ItemId,
            $cb2LocationId,
            $cb2TimeframeId,
            $code
        );

        return BookingCodes::persist($bookingCode);
    }

    /**
     * Migrates CB1 user agreement url option to CB2.
     * Only relevant for legacy user profile.
     *
     * @return mixed
     */
    public static function migrateUserAgreementUrl()
    {
        $cb1_url = Settings::getOption('commons-booking-settings-pages', 'commons-booking_termsservices_url');

        $options_array = array(
            'cb1-terms-url' => $cb1_url

        );

        update_option('commonsbooking_options_migration', $options_array);

        return true;
    }

    /**
     * Migrates CB1 taxonomy to CB2 posts.
     * @param $cb1Taxonomies
     *
     * @return bool
     */
    public static function migrateTaxonomy($cb1Taxonomies)
    {
        $cb2PostId = CB1::getCB2PostIdByCB1Id($cb1Taxonomies->object_id);
        try {
            wp_set_object_terms($cb2PostId, $cb1Taxonomies->term, $cb1Taxonomies->taxonomy);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

}
