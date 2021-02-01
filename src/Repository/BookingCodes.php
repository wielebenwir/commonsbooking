<?php


namespace CommonsBooking\Repository;


use CommonsBooking\Model\BookingCode;
use CommonsBooking\Model\Day;
use CommonsBooking\Settings\Settings;
use DateInterval;
use DatePeriod;
use DateTime;

class BookingCodes
{

    /**
     * Table name of booking codes.
     * @var string
     */
    public static $tablename = 'cb_bookingcodes';

    /**
     * Returns booking codes for timeframe.
     *
     * @param $timeframeId
     *
     * @return array
     */
    public static function getCodes($timeframeId)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$tablename;

        $bookingCodes = $wpdb->get_results(
            "
                SELECT *
                FROM $table_name
                WHERE timeframe = $timeframeId
                ORDER BY item ASC ,date ASC
            "
        );

        $codes = [];
        foreach ($bookingCodes as $bookingCode) {
            $bookingCodeObject = new BookingCode(
                $bookingCode->date,
                $bookingCode->item,
                $bookingCode->location,
                $bookingCode->timeframe,
                $bookingCode->code
            );
            $codes[] = $bookingCodeObject;
        }

        return $codes;
    }

    /**
     * @param $timeframeId
     * @param $itemId
     * @param $locationId
     * @param $date
     *
     * @return array
     */
    public static function getCode($timeframeId, $itemId, $locationId, $date)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$tablename;

        $bookingCodes = $wpdb->get_results(
            "
                SELECT *
                FROM $table_name
                WHERE 
                    timeframe = '$timeframeId' AND 
                    item = '$itemId' AND 
                    location = '$locationId' AND 
                    date = '$date'
                ORDER BY item ASC ,date ASC
            "
        );

        $bookingCodeObject = null;
        if (count($bookingCodes)) {
            $bookingCodeObject = new BookingCode(
                $bookingCodes[0]->date,
                $bookingCodes[0]->item,
                $bookingCodes[0]->location,
                $bookingCodes[0]->timeframe,
                $bookingCodes[0]->code
            );
        }

        return $bookingCodeObject;
    }

    /**
     * Creates booking-codes table;
     */
    public static function initBookingCodesTable()
    {
        global $wpdb;
        global $cb_db_version;

        $table_name = $wpdb->prefix . self::$tablename;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            date date DEFAULT '0000-00-00' NOT NULL,
            timeframe bigint(20) unsigned NOT NULL,
            location bigint(20) unsigned NOT NULL,
            item bigint(20) unsigned NOT NULL,
            code varchar(100) NOT NULL,
            PRIMARY KEY (date, timeframe, location, item, code) 
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        add_option(COMMONSBOOKING_PLUGIN_SLUG . '_bookingcodes_db_version', $cb_db_version);
    }

    /**
     * Generates booking codes for timeframe.
     *
     * @param $timeframeId
     *
     * @throws \Exception
     */
    public static function generate($timeframeId)
    {
        $bookablePost = new \CommonsBooking\Model\Timeframe($timeframeId);

        $begin = new DateTime();
        $begin->setTimestamp($bookablePost->getStartDate());
        $end = new DateTime();
        $end->setTimestamp($bookablePost->getEndDate());
        $end->setTimestamp($end->getTimestamp() + 1);

        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($begin, $interval, $end);

        $bookingCodes = Settings::getOption('commonsbooking_options_bookingcodes', 'bookingcodes');
        $bookingCodesArray = array_filter(explode(',', trim($bookingCodes)));
        $bookingCodesArray = array_map(function ($item) {
            return preg_replace("/\r|\n/", "", $item);
        }, $bookingCodesArray);

        // Check if codes are available, show error if not.
        if(!count($bookingCodesArray)) {
            set_transient(
                BookingCode::ERROR_TYPE,
                commonsbooking_sanitizeHTML(
                    __("No booking codes could be created because there were no booking codes to choose from. Please set some booking codes in the CommonsBooking settings.", 'commonsbooking')
                ),
                45
            );
            return false;
        }

        $bookingCodesRandomizer = intval($timeframeId);
        $bookingCodesRandomizer += $bookablePost->getItem()->ID;
        $bookingCodesRandomizer += $bookablePost->getLocation()->ID;

        foreach ($period as $key => $dt) {
            $day = new Day($dt->format('Y-m-d'));
            if ( ! $day->isInTimeframe($bookablePost)) {
                $bookingCode = new BookingCode(
                    $dt->format('Y-m-d'),
                    $bookablePost->getItem()->ID,
                    $bookablePost->getLocation()->ID,
                    $timeframeId,
                    $bookingCodesArray[($dt->format('z') + $bookingCodesRandomizer) % count($bookingCodesArray)]
                );
                self::persist($bookingCode);
            }
        }
    }

    /**
     * @param BookingCode $bookingCode
     *
     * @return
     */
    public static function persist(BookingCode $bookingCode)
    {
        global $wpdb;
        $wpdb->show_errors(0);
        $table_name = $wpdb->prefix . self::$tablename;

        $result = $wpdb->replace(
            $table_name,
            array(
                'timeframe' => $bookingCode->getTimeframe(),
                'date'      => $bookingCode->getDate(),
                'location'  => $bookingCode->getLocation(),
                'item'      => $bookingCode->getItem(),
                'code'      => $bookingCode->getCode()
            )
        );
        $wpdb->show_errors(1);

        return $result;
    }

    /**
     * Deletes booking codes for current post.
     *
     * @param null $post
     */
    public static function deleteBookingCodes($postId = null)
    {
        if ($postId) {
            $post = get_post($postId);
        } else {
            global $post;
        }
        if (
            $post &&
            $post->post_type == \CommonsBooking\Wordpress\CustomPostType\Timeframe::$postType
        ) {
            global $wpdb;
            $table_name = $wpdb->prefix . self::$tablename;


            $query = $wpdb->prepare('SELECT timeframe FROM ' . $table_name . ' WHERE timeframe = %d', $post->ID);
            $var = $wpdb->get_var($query);
            if ($var) {
                $query2 = $wpdb->prepare('DELETE FROM ' . $table_name . ' WHERE timeframe = %d', $post->ID);
                $wpdb->query($query2);
            }
        }
    }

}
