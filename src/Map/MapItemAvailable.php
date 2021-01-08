<?php

namespace CommonsBooking\Map;

class MapItemAvailable
{

    const ITEM_AVAILABLE = 0; //item is available
    const LOCATION_CLOSED = 1; //regular closed day / special closing day / holiday -> no pickup return
    const ITEM_BOOKED = 2; //item is booked
    const OUT_OF_TIMEFRAME = 3; //no timeframe for item set

    public static function create_items_availabilities($locations, $date_start, $date_end)
    {
        //trigger_error('filter_locations_by_item_availability');
        $result = [];

        $booked_days_by_item = self::fetch_booked_days_in_period($date_start, $date_end);

        $filter_period = new DatePeriod(new DateTime($date_start), new DateInterval('P1D'),
            new DateTime($date_end.' +1 day'));

        foreach ($locations as $location_id => $location) {
            //trigger_error('location_id: ' . $location_id);

            // build availability array
            foreach ($location['items'] as $item_id => &$item) {
                $availability = [];
                foreach ($filter_period as $date) {
                    $availability[] = $date->format('Y-m-d');
                }
                //$availability: the higher the number, the higher the priority
                $availability = array_fill_keys($availability, self::OUT_OF_TIMEFRAME);

                //mark days in timeframe
                $availability = self::mark_days_in_timeframe($item['timeframes'], $availability);

                //mark closing days (of location)
                $availability = self::mark_closed_days($location_id, $availability, $date_start, $date_end);

                //mark days which are booked (if there are any)
                if (isset($booked_days_by_item[$item['id']])) {
                    $availability = self::mark_booked_days($booked_days_by_item[$item['id']], $availability);
                }

                $locations[$location_id]['items'][$item_id]['availability'] = $availability; //TODO: can we do it by reference?

            }
        }

        return $locations;
    }

    protected static function fetch_booked_days_in_period($date_start, $date_end)
    {
        $bookings            = self::fetch_all_bookings_in_period($date_start, $date_end);
        $booked_days_by_item = [];

        //booked days (by item id)
        foreach ($bookings as $booking) {
            if ( ! isset($booked_days_by_item[$booking->item_id])) {
                $booked_days_by_item[$booking->item_id] = [];
            }

            $booked_days_period = new DatePeriod(new DateTime($booking->date_start), new DateInterval('P1D'),
                new DateTime($booking->date_end.' +1 day'));

            foreach ($booked_days_period as $booked_date) {
                $booked_days_by_item[$booking->item_id][] = $booked_date->format('Y-m-d');
            }
        }

        return $booked_days_by_item;
    }

    protected static function fetch_all_bookings_in_period($date_start, $date_end, $status = 'confirmed')
    {
        global $wpdb;

        //get bookings data
        $table_name       = $wpdb->prefix.'cb_bookings';
        $select_statement = "SELECT * FROM $table_name WHERE ".
                            "((date_start BETWEEN '".$date_start."' ".
                            "AND '".$date_end."') ".
                            "OR (date_end BETWEEN '".$date_start."' ".
                            "AND '".$date_end."') ".
                            "OR (date_start < '".$date_start."' ".
                            "AND date_end > '".$date_end."')) ".
                            "AND status = '".$status."'";

        $bookings_result = $wpdb->get_results($select_statement);

        return $bookings_result;
    }

    protected static function mark_days_in_timeframe($timeframes, $availability)
    {
        //prepare date_times for start/end of timeframes
        $timeframe_date_times = [];
        foreach ($timeframes as $timeframe) {
            $timeframe_date_time_start = new DateTime();
            $timeframe_date_time_start->setTimestamp(strtotime($timeframe['date_start']));
            $timeframe_date_time_end = new DateTime();
            $timeframe_date_time_end->setTimestamp(strtotime($timeframe['date_end']));

            $timeframe_date_times[] = [
                'date_time_start' => $timeframe_date_time_start,
                'date_time_end'   => $timeframe_date_time_end,
            ];
        }

        //mark days which are inside a timeframe
        foreach ($availability as $date => $status) {
            $av_date_time = new DateTime();
            $av_date_time->setTimestamp(strtotime($date));
            foreach ($timeframe_date_times as $timeframe_date_time) {
                if ($av_date_time >= $timeframe_date_time['date_time_start'] && $av_date_time <= $timeframe_date_time['date_time_end']) {
                    $availability[$date] = self::ITEM_AVAILABLE;
                }
            }
        }

        return $availability;
    }

    protected static function mark_closed_days($location_id, $availability, $date_start, $date_end)
    {
        //regular closed days of location
        $cb_data                 = new CB_Data();
        $location                = $cb_data->get_location($location_id);
        $regular_closed_weekdays = is_array($location['closed_days']) ? $location['closed_days'] : [];

        //trigger_error($location_id . ': ' .json_encode($regular_closed_weekdays));

        // if special days plugin available: fetch special closing days & holidays
        $cb_special_days_path           = cb_map\get_active_plugin_directory('commons-booking-special-days.php');
        $special_closed_days_timestamps = $cb_special_days_path ? CB_Special_Days::get_locations_special_closed_days($location_id,
            strtotime($date_start), strtotime($date_end)) : [];

        foreach ($availability as $date => $status) {
            $av_date_time = new DateTime();
            $av_date_time->setTimestamp(strtotime($date));

            //check only if date is free
            if ($status == self::ITEM_AVAILABLE) {
                foreach ($regular_closed_weekdays as $regular_closed_weekday) {

                    //availability date falls on a regular closed day
                    if ($regular_closed_weekday == date("N", $av_date_time->getTimestamp())) {
                        $availability[$date] = self::LOCATION_CLOSED;
                    }
                }

                //availability date falls on a special closed day / holiday
                foreach ($special_closed_days_timestamps as $special_closed_days_timestamp) {
                    if ($date == date('Y-m-d', $special_closed_days_timestamp)) {
                        $availability[$date] = self::LOCATION_CLOSED;
                    }
                }

            }
        }

        return $availability;
    }

    protected static function mark_booked_days($booked_days_of_item, $availability)
    {
        foreach ($availability as $date => $status) {
            if ($status <= self::OUT_OF_TIMEFRAME && in_array($date, $booked_days_of_item)) {
                $availability[$date] = self::ITEM_BOOKED;
            }
        }

        return $availability;
    }

    /**
     * to ensure stable order (for js processing) convert associative array holding availability to indexed array
     **/
    public static function availability_to_indexed_array($locations)
    {
        foreach ($locations as $location_id => $location) {
            foreach ($location['items'] as $item_id => &$item) {

                $restructured_availability = [];
                if (isset($locations[$location_id]['items'][$item_id]['availability'])) {
                    foreach ($locations[$location_id]['items'][$item_id]['availability'] as $date => $status) {

                        $restructured_availability[] = [
                            'date'   => $date,
                            'status' => $status,
                        ];
                    }

                    $locations[$location_id]['items'][$item_id]['availability'] = $restructured_availability;
                }
            }
        }

        return $locations;
    }
}

?>
