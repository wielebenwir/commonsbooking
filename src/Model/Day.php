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

}
