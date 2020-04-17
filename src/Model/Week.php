<?php

namespace CommonsBooking\Model;

class Week
{

    /**
     * Week of year.
     * @var
     */
    protected $week;

    /**
     * Week constructor.
     *
     * @param $week
     */
    public function __construct($week)
    {
        $this->week = $week;
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
            $days[] = new Day($dto->format('Y-m-d'));
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
