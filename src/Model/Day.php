<?php

namespace CommonsBooking\Model;

use CommonsBooking\Wordpress\CustomPostType\Timeframe;

class Day
{

    protected $date;

    protected $locations;

    protected $items;

    protected $types;

    /**
     * Day constructor.
     *
     * @param $date
     * @param $locations
     * @param $items
     * @param $types
     */
    public function __construct($date, $locations = [], $items = [], $types = [])
    {
        $this->date = $date;
        $this->locations = array_map(function ($location) {
            return $location instanceof \WP_Post ? $location->ID : $location;
        }, $locations);
        $this->items = array_map(function ($item) {
            return $item instanceof \WP_Post ? $item->ID : $item;
        }, $items);

        $this->types = $types;
    }

    /**
     * @return mixed
     */
    public function getDayOfWeek()
    {
        return date('w', strtotime($this->getDate()));
    }

    public function getDateObject()
    {
        return new \DateTime($this->getDate());
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }

    public function getFormattedDate($format)
    {
        return date($format, strtotime($this->getDate()));
    }

    public function getSlotStartTimestamp($slotNr)
    {
        $slot = $this->getSlot($slotNr);
        return intval(strtotime($this->getDate() . ' ' . $slot['timestart']));
    }

    public function getSlotEndTimestamp($slotNr)
    {
        $slot = $this->getSlot($slotNr);
        return intval(strtotime($this->getDate() . ' ' . $slot['timeend'])) - 1;
    }

    public function getFormattedSlotStartDate($format, $slotNr)
    {
        $time = $this->getSlotStartTimestamp($slotNr);
        return date($format, $time);
    }

    public function getFormattedSlotEndDate($format, $slotNr)
    {
        $time = $this->getSlotEndTimestamp($slotNr);
        return date($format, $time);
    }

    public function getSlot($slotNr)
    {
        $grid = $this->getGrid();

        if (count($grid) && !empty($grid[$slotNr])) {
            return $grid[$slotNr];
        } else {
            throw new \Exception(__CLASS__ . "::" . __FUNCTION__ . ": Invalid slot: " . $slotNr);
        }
    }

    /**
     * @param mixed $date
     *
     * @return Day
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    public function getName()
    {
        return date('l', strtotime($this->getDate()));
    }

    /**
     * Returns grid of timeframes.
     * @return array
     * @throws \Exception
     */
    public function getGrid()
    {
        $timeFrames = \CommonsBooking\Repository\Timeframe::get(
            $this->locations,
            $this->items,
            $this->types,
            $this->getDate()
        );
        $slots = $this->getTimeframeSlots($timeFrames);

        return $slots;
    }

    /**
     * Returns the slot number for specific timeframe and time.
     *
     * @param \DateTime $date
     * @param $grid
     *
     * @return float|int
     */
    protected function getSlotByTime(\DateTime $date, $grid)
    {
        $hourSlots = $date->format('H') / $grid;
        $minuteSlots = $date->format('i') / 60 / $grid;

        $slot = $hourSlots + $minuteSlots;

        return $slot;
    }

    /**
     * Returns start-slot id.
     * @param $grid
     * @param $timeframe
     * @return float|int
     */
    protected function getStartSlot($grid, $timeframe)
    {
        // Timeframe
        $timeframeType = get_post_meta($timeframe->ID, 'type', true);
        $fullDay = get_post_meta($timeframe->ID, 'full-day', true);
        $startDate = $this->getStartDate($timeframe);

        // Slots
        $startSlot = 0;

        // If timeframe isn't configured as full day
        if (!$fullDay) {
            $startSlot = $this->getSlotByTime($startDate, $grid);
        }

        // If we have a overbooked day, we need to mark all slots as booked
        if ($timeframeType == Timeframe::BOOKING_ID) {
            // Check if timeframe began before the current day
            if (strtotime($this->getDate()) > $startDate->getTimestamp()) {
                $startSlot = 0;
            }
        }
        return $startSlot;
    }

    /**
     * Returns end-slot id.
     * @param $slots
     * @param $grid
     * @param $timeframe
     * @return float|int
     */
    protected function getEndSlot($slots, $grid, $timeframe)
    {
        // Timeframe
        $timeframeType = get_post_meta($timeframe->ID, 'type', true);
        $fullDay = get_post_meta($timeframe->ID, 'full-day', true);
        $endDate = $this->getEndDate($timeframe);

        // Slots
        $endSlot = count($slots);

        // If timeframe isn't configured as full day
        if (!$fullDay) {
            $endSlot = $this->getSlotByTime($endDate, $grid);
        }

        // If we have a overbooked day, we need to mark all slots as booked
        if ($timeframeType == Timeframe::BOOKING_ID) {
            // Check if timeframe ends after the current day
            if (strtotime($this->getFormattedDate('d.m.Y 23:59')) < $endDate->getTimestamp()) {
                $endSlot = count($slots);
            }
        }
        return $endSlot;
    }


    /**
     * Returns start-date DateTime.
     * @param $timeframe
     * @return \DateTime
     */
    protected function getStartDate($timeframe)
    {
        $startDateString = get_post_meta($timeframe->ID, 'start-date', true);
        $startDate = new \DateTime();
        $startDate->setTimestamp($startDateString);
        return $startDate;
    }

    /**
     * Returns end-date DateTime.
     * @param $timeframe
     * @return \DateTime
     */
    protected function getEndDate($timeframe)
    {
        $startDateString = get_post_meta($timeframe->ID, 'end-date', true);
        $startDate = new \DateTime();
        $startDate->setTimestamp($startDateString);
        return $startDate;
    }

    /**
     * Checks if timeframe is relevant for current day/date.
     * @param $timeframe
     * @return bool
     */
    protected function continueBecauseOfRepetition($timeframe)
    {
        $repetitionType = get_post_meta($timeframe->ID, 'timeframe-repetition', true);
        if (
            $repetitionType && $repetitionType !== "norep"
        ) {
            switch ($repetitionType) {
                // Weekly Rep
                case "w":
                    $dayOfWeek = intval($this->getDateObject()->format('w'));
                    $timeframeWeekdays = get_post_meta($timeframe->ID, 'weekdays', true);

                    // Because of different day of week calculation we need to recalculate
                    if ($dayOfWeek == 0) {
                        $dayOfWeek = 7;
                    }
                    if (is_array($timeframeWeekdays) && ! in_array($dayOfWeek, $timeframeWeekdays)) {
                        return true;
                    }
                    break;


                // Monthly Rep
                case "m":
                    $dayOfMonth = intval($this->getDateObject()->format('j'));
                    $timeframeStartDayOfMonth = $this->getStartDate($timeframe)->format('j');

                    if ($dayOfMonth != $timeframeStartDayOfMonth) {
                        return true;
                    }
                    break;

                // Yearly Rep
                case "y":
                    $date = intval($this->getDateObject()->format('dm'));
                    $timeframeDate = $this->getStartDate($timeframe)->format('dm');
                    if ($date != $timeframeDate) {
                        return true;
                    }
                    break;
            }
        }
        return false;
    }

    /**
     * Fills timeslots with timeframes.
     *
     * @param $slots
     * @param $timeframes
     *
     * @throws \Exception
     */
    protected function mapTimeFrames(&$slots, $timeframes)
    {
        $grid = 24 / count($slots);

        // Iterate through timeframes and fill slots
        foreach ($timeframes as $timeframe) {
            // Check for repetition timeframe selected days
            if ($this->continueBecauseOfRepetition($timeframe)) continue;

            // Slots
            $startSlot = $this->getStartSlot($grid, $timeframe);
            $endSlot = $this->getEndSlot($slots, $grid, $timeframe);

            // Add timeframe to relevant slots
            while ($startSlot < $endSlot) {
                if (!array_key_exists('timeframe', $slots[$startSlot]) || !$slots[$startSlot]['timeframe']) {
                    $timeframe->locked = Timeframe::isLocked($timeframe);
                    $slots[$startSlot]['timeframe'] = $timeframe;
                } else {
                    $slots[$startSlot]['timeframe'] = Timeframe::getHigherPrioFrame($timeframe, $slots[$startSlot]['timeframe']);
                }

                $startSlot++;
            }
        }

        $this->sanitizeSlots($slots);
    }

    /**
     * Remove empty and merge connected slots.
     * @param $slots
     */
    protected function sanitizeSlots(&$slots) {
        $this->removeEmptySlots($slots);

        // merge multiple slots if they are of same type
        foreach ($slots as $slotNr => $slot) {
            if (!array_key_exists($slotNr - 1, $slots)) {
                continue;
            }
            $slotBefore = $slots[$slotNr - 1];

            // If Slot before is of same timeframe and we have no hourly grid, we merge them.
            if (
                $slotBefore &&
                $slotBefore['timeframe']->ID == $slot['timeframe']->ID &&
                (
                    get_post_meta($slot['timeframe']->ID, 'full-day', true) == 'on' ||
                    get_post_meta($slot['timeframe']->ID, 'grid', true) == 0
                )
            ) {
                // Take over start time from slot before
                $slots[$slotNr]['timestart'] = $slotBefore['timestart'];
                $slots[$slotNr]['timestampstart'] = $slotBefore['timestampstart'];

                // unset timeframe from slot before
                unset($slots[$slotNr - 1]['timeframe']);
            }
        }

        $this->removeEmptySlots($slots);
    }

    /**
     * remove slots without timeframes
     * @param $slots
     */
    protected function removeEmptySlots(&$slots)
    {
        // remove slots without timeframes
        foreach ($slots as $slotNr => $slot) {
            if (!array_key_exists('timeframe', $slot) || !($slot['timeframe'] instanceof \WP_Post)) {
                unset($slots[$slotNr]);
            }
        }
    }

    /**
     * Returns array of timeslots filled with timeframes.
     *
     * @param $timeframes
     *
     * @return array
     * @throws \Exception
     */
    protected function getTimeframeSlots($timeframes)
    {
        $slots = [];
        $slotsPerDay = 24;

        // Init Slots
        for ($i = 0; $i < $slotsPerDay; $i++) {
            $slots[$i] = [
                'timestart' => date('H:i', $i * ((24 / $slotsPerDay) * 3600)),
                'timeend' => date('H:i', ($i + 1) * ((24 / $slotsPerDay) * 3600)),
                'timestampstart' => $this->getSlotTimestampStart($slotsPerDay, $i),
                'timestampend' => $this->getSlotTimestampEnd($slotsPerDay, $i)
            ];
        }

        $this->mapTimeFrames($slots, $timeframes);
        return $slots;
    }

    protected function getSlotTimestampStart($slotsPerDay, $slotNr)
    {
        return strtotime($this->getDate()) + ($slotNr * ((24 / $slotsPerDay) * 3600));
    }

    protected function getSlotTimestampEnd($slotsPerDay, $slotNr)
    {
        return strtotime($this->getDate()) + (($slotNr + 1) * ((24 / $slotsPerDay) * 3600)) - 1;
    }

}
