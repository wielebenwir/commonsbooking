<?php

namespace CommonsBooking\Model;

use CommonsBooking\Plugin;

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

        if ($startDate !== 0 && $endDate !== 0 && $startDate == $endDate) { // available only one day
            $availableString = sprintf(__('on %s', 'commonsbooking'), $startDateFormatted);
        } elseif ($startDate > 0 && ($endDate == 0)) { // start but no end date
            if ($startDate > $today) { // start is in the future
                $availableString = sprintf(__('from %s', 'commonsbooking'), $startDateFormatted);
            } else { // start has passed, no end date, probably a fixed location
                $availableString = ' permanently';
            }
        } elseif ($startDate > 0 && $endDate > 0) { // start AND end date
            if ($startDate > $today) { // start is in the future, with an end date
                $availableString = sprintf(__(' from %s until %s', 'commonsbooking'), $startDateFormatted,
                    $endDateFormatted);
            } else { // start has passed, with an end date
                $availableString = sprintf(__(' until %s', 'commonsbooking'), $endDateFormatted);
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

    /**
     * Returns grit type id
     * @return mixed
     */
    public function getGrid()
    {
        return self::get_meta('grid');
    }

    /**
     * Returns type id
     * @return mixed
     */
    public function getType()
    {
        return self::get_meta('type');
    }

    /**
     * Returns start time for day-slots.
     * @return mixed
     */
    public function getStartTime() {
        return self::get_meta('start-time');
    }

    /**
     * Returns end time for day-slots.
     * @return mixed
     */
    public function getEndTime() {
        return self::get_meta('end-time');
    }

    /**
     * Checks if Timeframe is valid, if not timeframe will be removed!!!
     * @throws \Exception
     */
    public function isValid()
    {
        if (
            $this->getType() == \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID &&
            $this->getLocation() &&
            $this->getItem() &&
            $this->getStartDate()
        ) {
            $postId = $this->ID;

            if($this->getStartTime() && !$this->getEndTime()) {
                set_transient("timeframeValidationFailed",
                    __("Es wurde eine Startzeit, aber keine Endzeit gesetzt.", 'commonsbooking'), 45);
                return false;
            }

            // Get Timeframes with same location, item and a startdate
            $existingTimeframes = \CommonsBooking\Repository\Timeframe::get(
                [$this->getLocation()->ID],
                [$this->getItem()->ID],
                [\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID],
                null,
                true
            );

            // filter current timeframe
            $existingTimeframes = array_filter($existingTimeframes, function ($timeframe) use ($postId) {
                return $timeframe->ID !== $postId && $timeframe->getStartDate();
            });

            // Validate against existing other timeframes
            foreach ($existingTimeframes as $timeframe) {

                // check if timeframes overlap
                if (
                    $this->hasTimeframeDateOverlap($this, $timeframe)
                ) {
                    // Compare grid types
                    if ($timeframe->getGrid() != $this->getGrid()) {
                        set_transient("timeframeValidationFailed",
                            __("Sich überlagernde buchbare Timeframes dürfen nur das gleiche Raster haben. (Timeframe (ID: ".$timeframe->ID."): '".$timeframe->post_title."')", 'commonsbooking'), 5);
                        return false;
                    }

                    // Check if day slots overlap
                    if( $this->hasTimeframeTimeOverlap($this, $timeframe)) {
                        set_transient("timeframeValidationFailed",
                            __("Zeiträume dürfen sich nicht überlagern. (Timeframe (ID: ".$timeframe->ID."): '".$timeframe->post_title."')", 'commonsbooking'), 5);
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Checks if timeframes are overlapping in date range.
     * @param $timeframe1
     * @param $timeframe2
     *
     * @return bool
     */
    protected function hasTimeframeDateOverlap($timeframe1, $timeframe2) {
        return
            !$timeframe1->getEndDate() && !$timeframe2->getEndDate() ||
            (
                $timeframe1->getEndDate() && !$timeframe2->getEndDate() &&
                $timeframe2->getStartDate() <= $timeframe1->getEndDate() &&
                $timeframe2->getStartDate() >= $timeframe1->getStartDate()
            ) ||
            (
                !$timeframe1->getEndDate() && $timeframe2->getEndDate() &&
                $timeframe2->getEndDate() > $timeframe1->getStartDate()
            ) ||
            (
                $timeframe1->getEndDate() && $timeframe2->getEndDate() &&
                (
                    ($timeframe1->getEndDate() > $timeframe2->getStartDate() && $timeframe1->getEndDate() < $timeframe2->getEndDate()) ||
                    ($timeframe2->getEndDate() > $timeframe1->getStartDate() && $timeframe2->getEndDate() < $timeframe1->getEndDate())
                )
            );
    }

    /**
     * Checks if timeframes are overlapping in daily slots.
     * @param $timeframe1
     * @param $timeframe2
     *
     * @return bool
     */
    protected function hasTimeframeTimeOverlap($timeframe1, $timeframe2) {
        return
            !strtotime($timeframe1->getEndTime()) && !strtotime($timeframe2->getEndTime()) ||
            (
                strtotime($timeframe1->getEndTime()) && !strtotime($timeframe2->getEndTime()) &&
                strtotime($timeframe2->getStartTime()) <= strtotime($timeframe1->getEndTime()) &&
                strtotime($timeframe2->getStartTime()) >= strtotime($timeframe1->getStartTime())
            ) ||
            (
                !strtotime($timeframe1->getEndTime()) && strtotime($timeframe2->getEndTime()) &&
                strtotime($timeframe2->getEndTime()) > strtotime($timeframe1->getStartTime())
            ) ||
            (
                strtotime($timeframe1->getEndTime()) && strtotime($timeframe2->getEndTime()) &&
                (
                    (strtotime($timeframe1->getEndTime()) > strtotime($timeframe2->getStartTime()) && strtotime($timeframe1->getEndTime()) < strtotime($timeframe2->getEndTime())) ||
                    (strtotime($timeframe2->getEndTime()) > strtotime($timeframe1->getStartTime()) && strtotime($timeframe2->getEndTime()) < strtotime($timeframe1->getEndTime()))
                )
            );
    }

}
