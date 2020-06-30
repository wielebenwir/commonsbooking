<?php


namespace CommonsBooking\Model;


use CommonsBooking\Repository\Timeframe;

class Item extends CustomPost
{
    /**
     * @return array
     */
    public function getBookableTimeframes()
    {
        return Timeframe::get([], [$this->ID], [\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID]);
    }
}
