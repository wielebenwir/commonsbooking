<?php


namespace CommonsBooking\Model;


use CommonsBooking\Repository\Timeframe;

class BookablePost extends CustomPost
{

    /**
     * @param false $asModel
     *
     * @return array
     * @throws \Exception
     * @TODO: should support $args
     */
    public function getBookableTimeframes($asModel = true)
    {
        $bookableTimeframes = [];
        if(get_called_class() == Location::class) {
            $bookableTimeframes = Timeframe::get(
                [$this->ID],
                [],
                [\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID],
                $this->getDate() ?: null,
                $asModel
            );

        }
        if(get_called_class() == Item::class) {
            $bookableTimeframes = Timeframe::get(
                [],
                [$this->ID],
                [\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID],
                $this->getDate() ?: null,
                $asModel
            );
        }
        return $bookableTimeframes;
    }

    /**
     * Returns bookable timeframes for a specific location
     *
     * @return int
     * @throws \Exception
     */
    public function isBookable()
    {
        return count($this->getBookableTimeframes());
    }

}
