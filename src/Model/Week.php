<?php

namespace CommonsBooking\Model;

class Week
{

    /**
     * Week of year.
     * @var
     */
    protected $week;

    protected $locations;

    protected $items;

    /**
     * Week constructor.
     *
     * @param $week
     * @param $locations
     * @param $items
     */
    public function __construct($week, $locations = [], $items = [])
    {
        $this->week = $week;
        $this->locations = $locations;
        $this->items = $items;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getDays() {
        $dto = new \DateTime();
        $dto->setISODate(date('Y'), $this->getWeek());

        $days = [];
        for($i = 0; $i < 7; $i++) {
            $days[] = new Day($dto->format('Y-m-d'), $this->locations, $this->items);
            $dto->modify('+1 day');
        }

        return $days;
    }

    /**
     * @return mixed
     */
    public function getWeek()
    {
        return $this->week;
    }

    /**
     * @param mixed $week
     *
     * @return Week
     */
    public function setWeek($week)
    {
        $this->week = $week;

        return $this;
    }

}
