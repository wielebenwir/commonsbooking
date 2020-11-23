<?php

namespace CommonsBooking\Model;

/**
 * Class Timeframe
 * @package CommonsBooking\Model
 */
class Timeframe extends CustomPost
{
    /**
     * Return residence in a human readable format
     *
     * "From xx.xx.",  "Until xx.xx.", "From xx.xx. until xx.xx.", "no longer available"
     *
     * @return string
     */
    public function formattedBookableDate()
    {
        $startDate = $this->getStartDate() ? $this->getStartDate() : 0;
        $endDate = $this->getEndDate() ? $this->getEndDate() : 0;
        return self::formatBookableDate($startDate, $endDate);
    }

    /**
     * @param $startDate
     * @param $endDate
     *
     * @return string
     */
    public static function formatBookableDate($startDate, $endDate) {
        $format = self::getDateFormat();
        $today = strtotime('now');

        $startDateFormatted = date($format, $startDate);
        $endDateFormatted = date($format, $endDate);

        $label = __('Available here', 'commonsbooking');
        $availableString = '';

        if ($startDate !== 0 && $endDate !== 0 && $startDate == $endDate) { // available only one day
            /* translators: %s = date in wordpress defined format */
            $availableString = sprintf(__('on %s', 'commonsbooking'), $startDateFormatted);
        } elseif ($startDate > 0 && ($endDate == 0)) { // start but no end date
            if ($startDate > $today) { // start is in the future
                /* translators: %s = date in wordpress defined format */
                $availableString = sprintf(__('from %s', 'commonsbooking'), $startDateFormatted);
            } else { // start has passed, no end date, probably a fixed location
                $availableString = __('permanently', 'commonsbooking');
            }
        } elseif ($startDate > 0 && $endDate > 0) { // start AND end date
            if ($startDate > $today) { // start is in the future, with an end date
                /* translators: %1$s = startdate, second %2$s = enddate in wordpress defined format */
                $availableString = sprintf(__(' from %1$s until %1$s', 'commonsbooking'), $startDateFormatted,
                    $endDateFormatted);
            } else { // start has passed, with an end date
                /* translators: %s = enddate in wordpress defined format */
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
    public static function getDateFormat()
    {
        return get_option('date_format');
    }

    /**
     * Return Start (repetition) date
     *
     * @return string
     */
    public function getStartDate()
    {
        $startDate = $this->getMeta('repetition-start');

        if((string) intval($startDate) !== $startDate) {
            $startDate = strtotime($startDate);
        }
        return $startDate;
    }

    /**
     * Return End (repetition) date
     *
     * @return string
     */
    public function getEndDate()
    {
        $endDate = $this->getMeta('repetition-end');
        if((string) intval($endDate) !== $endDate) {
            $endDate = strtotime($endDate);
        }
        return $endDate;
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
     * Validates if there can be booking codes created for this timeframe.
     * @return bool
     * @throws \Exception
     */
    public function bookingCodesApplieable()
    {
        return $this->getLocation() && $this->getItem() &&
               $this->getStartDate() && $this->getEndDate() &&
               $this->getType() == \CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID;
    }

    /**
     * @return Location
     * @throws \Exception
     */
    public function getLocation()
    {
        $locationId = $this->getMeta('location-id');
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
        $itemId = $this->getMeta('item-id');

        if ($post = get_post($itemId)) {
            return new Item($post);
        }

        return $post;
    }

    /**
     * Returns type id
     * @return mixed
     */
    public function getType()
    {
        return $this->getMeta('type');
    }

    /**
     * Checks if Timeframe is valid.
     * @return bool
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

            if ($this->getStartTime() && ! $this->getEndTime()) {
                set_transient("timeframeValidationFailed",
                    __("A pickup time but no return time has been set. Please set the return time.", 'commonsbooking'),
                    45);

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
                            /* translators: %1$s = timeframe-ID, %2$s is timeframe post_title */
                            sprintf(__('Overlapping bookable timeframes are only allowed to have the same grid. See overlapping timeframe ID: %1$s: %2$s',
                                'commonsbooking', 5), $timeframe->ID, $timeframe->post_title));

                        return false;
                    }

                    // Check if in day slots overlap
                    if (!$this->getMeta('full-day') && $this->hasTimeframeTimeOverlap($this, $timeframe)) {
                        set_transient("timeframeValidationFailed",
                            /* translators: first %s = timeframe-ID, second %s is timeframe post_title */
                            sprintf(__('time periods are not allowed to overlap. Please check the other timeframe to avoid overlapping time periods during one specific day. See affected timeframe ID: %1$s: %2$s',
                                'commonsbooking', 5), $timeframe->ID, $timeframe->post_title));

                        return false;
                    }

                    // Check if full-day slots overlap
                    if ($this->getMeta('full-day')) {
                        set_transient("timeframeValidationFailed",
                            /* translators: first %s = timeframe-ID, second %s is timeframe post_title */
                            sprintf(__('Date periods are not allowed to overlap. Please check the other timeframe to avoid overlapping Date periods. See affected timeframe ID: %1$s: %2$s',
                                'commonsbooking', 5), $timeframe->ID, $timeframe->post_title));
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Returns start time for day-slots.
     * @return mixed
     */
    public function getStartTime()
    {
        return $this->getMeta('start-time');
    }

    /**
     * Returns end time for day-slots.
     * @return mixed
     */
    public function getEndTime()
    {
        return $this->getMeta('end-time');
    }

    /**
     * Checks if timeframes are overlapping in date range.
     *
     * @param $timeframe1
     * @param $timeframe2
     *
     * @return bool
     */
    protected function hasTimeframeDateOverlap($timeframe1, $timeframe2)
    {
        return
            ! $timeframe1->getEndDate() && ! $timeframe2->getEndDate() ||
            (
                $timeframe1->getEndDate() && ! $timeframe2->getEndDate() &&
                $timeframe2->getStartDate() <= $timeframe1->getEndDate() &&
                $timeframe2->getStartDate() >= $timeframe1->getStartDate()
            ) ||
            (
                ! $timeframe1->getEndDate() && $timeframe2->getEndDate() &&
                $timeframe2->getEndDate() > $timeframe1->getStartDate()
            ) ||
            (
                $timeframe1->getEndDate() && $timeframe2->getEndDate() &&
                (
                    ($timeframe1->getEndDate() >= $timeframe2->getStartDate() && $timeframe1->getEndDate() <= $timeframe2->getEndDate()) ||
                    ($timeframe2->getEndDate() >= $timeframe1->getStartDate() && $timeframe2->getEndDate() <= $timeframe1->getEndDate())
                )
            );
    }

    /**
     * Returns grit type id
     * @return mixed
     */
    public function getGrid()
    {
        return $this->getMeta('grid');
    }

    /**
     * Checks if timeframes are overlapping in daily slots.
     *
     * @param $timeframe1
     * @param $timeframe2
     *
     * @return bool
     */
    protected function hasTimeframeTimeOverlap($timeframe1, $timeframe2)
    {
        return
            ! strtotime($timeframe1->getEndTime()) && ! strtotime($timeframe2->getEndTime()) ||
            (
                strtotime($timeframe1->getEndTime()) && ! strtotime($timeframe2->getEndTime()) &&
                strtotime($timeframe2->getStartTime()) <= strtotime($timeframe1->getEndTime()) &&
                strtotime($timeframe2->getStartTime()) >= strtotime($timeframe1->getStartTime())
            ) ||
            (
                ! strtotime($timeframe1->getEndTime()) && strtotime($timeframe2->getEndTime()) &&
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
