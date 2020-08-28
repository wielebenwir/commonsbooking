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

    protected $types;

    protected $weeks;

    /**
     * Calendar constructor.
     *
     * @param $startDate
     * @param $endDate
     * @param array $locations
     * @param array $items
     * @param array $types
     */
    public function __construct($startDate, $endDate, $locations = [], $items = [], $types = [])
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->items = $items;
        $this->locations = $locations;
        $this->types = $types;
    }


    public function getWeeks()
    {
        $weeks = [];
        $currentDate = strtotime($this->startDate->getDate());
        $endDate = strtotime($this->endDate->getDate());

        while($currentDate <= $endDate) {
            $weeks[] = new Week(date('Y', $currentDate), date('W', $currentDate), $this->locations, $this->items, $this->types);
            $currentDate = strtotime("+7 day", $currentDate);
        }

        return $weeks;
    }


}
