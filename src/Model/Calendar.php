<?php

namespace CommonsBooking\Model;

class Calendar
{

    /**
     * @var Day
     */
    protected $startDate;

    /**
     * @var Day
     */
    protected $endDate;

    protected $items;

    protected $locations;

    protected $weeks;

    /**
     * Calendar constructor.
     *
     * @param $startDate
     * @param $endDate
     * @param $items
     * @param $locations
     */
    public function __construct($startDate, $endDate, $locations = [], $items = [])
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->items = $items;
        $this->locations = $locations;
    }


    public function getWeeks()
    {
        $weeks = [];
        $firstWeek = date('W', strtotime($this->startDate->getDate()));
        $lastWeek = date('W', strtotime($this->endDate->getDate()));

        $weeks[] = new Week($firstWeek, $this->locations, $this->items);
        if ($lastWeek > $firstWeek) {
            while ($firstWeek < $lastWeek) {
                $weeks[] = new Week(++$firstWeek, $this->locations, $this->items);
            }
        }

        return $weeks;
    }


}
