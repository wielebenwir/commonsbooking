<?php


namespace CommonsBooking\Model;


use CommonsBooking\Repository\Timeframe;
use Exception;

class Item extends BookablePost
{
    /**
     * Returns bookable timeframes for a specific location
     *
     * @param $locationId
     *
     * @param bool $asModel
     *
     * @return array
     * @throws Exception
     */
    public function getBookableTimeframesByLocation($locationId, $asModel = false)
    {
        return Timeframe::get(
            [$locationId],
            [$this->ID],
            [\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID],
            $this->getDate() ?: null,
            $asModel
        );
    }
}
