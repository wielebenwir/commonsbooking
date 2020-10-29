<?php


namespace CommonsBooking\Repository;


use CommonsBooking\Model\BookingCode;
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
     * @param $timeframeId
     *
     * @return array
     */
    public static function get($timeframeId)
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
        foreach ( $bookingCodes as $bookingCode ) {
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
        add_option(CB_PLUGIN_SLUG . '_bookingcodes_db_version', $cb_db_version);
    }

    /**
     * Generates booking codes for timeframe.
     * @param $timeframeId
     *
     * @throws \Exception
     */
    public static function generate($timeframeId)
    {
        $bookablePost = new \CommonsBooking\Model\Timeframe($timeframeId);

        $begin = new DateTime($bookablePost->getStartDate());
        $end = new DateTime($bookablePost->getEndDate());
        $end->setTimestamp($end->getTimestamp() + 1);

        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($begin, $interval, $end);

        $bookingCodes = Settings::getOption('commonsbooking_options_bookingcodes', 'bookingcodes');
        $bookingCodesArray = explode(',', $bookingCodes);

        foreach ($period as $key => $dt) {
            $bookingCode = new BookingCode(
                $dt->format('Y-m-d'),
                $bookablePost->getItem()->ID,
                $bookablePost->getLocation()->ID,
                $timeframeId,
                $bookingCodesArray[$dt->format('z') % count($bookingCodesArray)]
            );
            self::persist($bookingCode);
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

        $result = $wpdb->insert(
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

}
