<?php

namespace CommonsBooking\Model;

class Timeframe extends CustomPost
{
    /**
     * @return Location
     * @throws \Exception
     */
    public function getLocation()
    {
        $locationId = self::get_meta('location-id');
        if ($post = get_post($locationId)) {
            return new Location($post);
        }

        return $post;
    }

    /**
     * @return Item
     * @throws \Exception
     */
    public function getItem()
    {
        $itemId = self::get_meta('item-id');

        if ($post = get_post($itemId)) {
            return new Item($post);
        }

        return $post;
    }

    /**
     * Return residence in a human readable format
     *
     * "From xx.xx.",  "Until xx.xx.", "From xx.xx. until xx.xx.", "no longer available"
     *
     * @return string
     */
    public function formattedBookableDate()
    {
        $format = self::getDateFormat();

        //  workaround because we need to calculate, and get_meta returns empty *string* if not set
        $startDate = $this->getStartDate() ? $this->getStartDate() : 0;
        $endDate = $this->getEndDate() ? $this->getEndDate() : 0;
        $today = strtotime('now');

        $startDateFormatted = date($format, $startDate);
        $endDateFormatted = date($format, $endDate);

        $label = __('Available here', 'commonsbooking');
        $availableString = '';

        // we check if there is no timeframe in daterange
        if (
            ($endDate !== 0 && $endDate < $today) |
            ($startDate !== 0 && $startDate > $today) |
            ($endDate == 0 && $startDate == 0)
        ) {
            return __('Currently not available here', 'commonsbooking');
        }


        if ($startDate == $endDate) { // available only one day 
            $availableString = sprintf(__('on %s', 'commonsbooking'), $startDateFormatted);
        } elseif ($startDate > 0 && ($endDate == 0)) { // start but no end date
            if ($startDate > $today) { // start is in the future
                $availableString = sprintf(__('from %s', 'commonsbooking'), $startDateFormatted);
            } else { // start has passed, no end date, probably a fixed location
                $availableString = '';
            }
        } elseif ($startDate > 0 && $endDate > 0) { // start AND end date
            if ($startDate > $today) { // start is in the future, with an end date
                $availableString = sprintf(__('from %s until %s', 'commonsbooking'), $startDateFormatted,
                    $endDateFormatted);
            } else { // start has passed, with an end date
                $availableString = sprintf(__('until %s', 'commonsbooking'), $endDateFormatted);
            }
        }

        return $label . ' ' . $availableString;
    }

    /**
     * Return date format
     *
     * @return string
     */
    public function getDateFormat()
    {
        return get_option('date_format');
    }

    /**
     * Return  time format
     *
     * @return string
     */
    public function getTimeFormat()
    {
        return get_option('time_format');
    }

    /**
     * Return Start (repetition) date
     *
     * @return string
     */
    public function getStartDate()
    {
        return self::get_meta('repetition-start');
    }

    /**
     * Return End (repetition) date
     *
     * @return string
     */
    public function getEndDate()
    {
        return self::get_meta('repetition-end');
    }


}
