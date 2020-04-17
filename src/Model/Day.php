<?php

namespace CommonsBooking\Model;

use CommonsBooking\PostType\Timeframe;

class Day
{

    protected $date;

    /**
     * Day constructor.
     *
     * @param $date
     */
    public function __construct($date)
    {
        $this->date = $date;
    }


    /**
     * @return mixed
     */
    public function getDayOfWeek()
    {
        return date('w', strtotime($this->getDate()));
    }

    public function getDateObject() {
        return new \DateTime($this->getDate());
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
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

    public function getName() {
        return date('l', strtotime($this->getDate()));
    }

    /**
     *
     * 2020-04-09T10:30
     * @param null $locationId
     * @param null $itemId
     *
     * @return array|int[]|\WP_Post[]
     */
    public function getTimeframes($locationId = null, $itemId = null) {

        $args = array(
            'post_type' => Timeframe::getPostType(),
            'order' => 'ASC',
            'order_by' => "type",
            'meta_query' => array(
                'relation' => "OR",
                array(
                    'key' => 'start-date',
                    'value' => array(
                        date('Y-m-d\TH:i', strtotime($this->getDate())),
                        date('Y-m-d\TH:i', strtotime($this->getDate() . 'T23:59'))
                    ),
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                ),
                array(
                    'key' => 'end-date',
                    'value' => array(
                        date('Y-m-d\TH:i', strtotime($this->getDate())),
                        date('Y-m-d\TH:i', strtotime($this->getDate() . 'T23:59'))
                    ),
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                ),
                array(
                    'relation' => "AND",
                    array(
                        'key' => 'start-date',
                        'value' => date('Y-m-d\TH:i', strtotime($this->getDate())),
                        'compare' => '<',
                        'type' => 'DATE'
                    ),
                    array(
                        'key' => 'end-date',
                        'value' => date('Y-m-d\TH:i', strtotime($this->getDate())),
                        'compare' => '>',
                        'type' => 'DATE'
                    )
                )
            )
        );

        $query = new \WP_Query($args);
        if ($query->have_posts()) {
            return $query->get_posts();
        }

        return [];
    }

    /**
     * Returns grid of timeframes.
     * @return array
     */
    public function getGrid() {
        $timeFrames = $this->getTimeframes();
        $slots = $this->getTimeframeSlots($timeFrames);
        return $slots;
    }

    /**
     * Returns the slot number for specific time.
     * @param \DateTime $time
     * @param $grid
     *
     * @return float|int
     */
    protected function getSlotByTime(\DateTime $time, $grid) {
        $hourSlots = $time->format('H') / $grid;
        $minuteSlots = $time->format('i') / 60 / $grid;

        return $hourSlots + $minuteSlots;
    }

    /**
     * Returns minimal grid from list of timeframes.
     * @param $timeframes
     *
     * @return bool|float
     */
    protected function getMinimalGridFromTimeframes($timeframes) {
        $grid = 0.5;
        // Get grid size from existing timeframes
        foreach ($timeframes as $timeframe) {
            if($timeframeGrid = get_post_meta($timeframe->ID, 'grid', true) < $grid) {
                $grid = $timeframeGrid;
            }
        }
        return $grid;
    }

    /**
     * Fills timeslots with timeframes.
     * @param $slots
     * @param $timeframes
     *
     * @throws \Exception
     */
    protected function mapTimeFrames(&$slots, $timeframes) {
        $grid = 24 / count($slots);

        // Iterate through timeframes and fill slots
        foreach ($timeframes as $timeframe) {
            $startDateString = get_post_meta($timeframe->ID,'start-date', true);
            $endDateString = get_post_meta($timeframe->ID,'end-date', true);
            $startDate = new \DateTime($startDateString);
            $endDate = new \DateTime($endDateString);

            $startSlot = $this->getSlotByTime($startDate, $grid);
            // Check if Timeframe starts on another day before.
            if(
                $this->getDateObject()->diff($startDate)->format('%i') > 0 &&
                $this->getDateObject()->diff($startDate)->format('%R') == "-")
            {
                $startSlot = 0;
            }

            $endSlot = $this->getSlotByTime($endDate, $grid);
            // Check if Timeframe ends on another day after.
            if(
                $this->getDateObject()->diff($endDate)->format('%i') > 0 &&
                $this->getDateObject()->diff($endDate)->format('%R') == "+")
            {
                $endSlot = count($slots) - 1;
            }

            // Add timeframe to relevant slots
            while($startSlot < $endSlot) {
                $slots[$startSlot++]['timeframes'][] = $timeframe;
            }
        }
    }

    /**
     * Returns array of timeslots filled with timeframes.
     * @param $timeframes
     *
     * @return array
     * @throws \Exception
     */
    protected function getTimeframeSlots($timeframes) {

        $slots = [];
        $grid = $this->getMinimalGridFromTimeframes($timeframes);
        $slotsPerDay = 24 / $grid;

        // Init Slots
        for($i = 0; $i <= $slotsPerDay; $i++) {
            $slots[$i] = [
                'timestart' => date('H:i', $i * ((24 / $slotsPerDay) * 3600)),
                'timeend' => date('H:i', ($i+1) * ((24 / $slotsPerDay) * 3600)),
                'timeframes' => []
            ];
        }

        $this->mapTimeFrames($slots, $timeframes);
        return $slots;
    }


}
