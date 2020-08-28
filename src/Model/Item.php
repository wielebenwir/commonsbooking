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

    /**
     * Returns bookable timeframes for a specific location
     * @param $locationId
     *
     * @return array
     */
    public function getBookableTimeframesByLocation($locationId)
    {
        return Timeframe::get([$locationId], [$this->ID], [\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID]);
    }
}
