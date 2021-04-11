<?php


namespace CommonsBooking\View;

abstract class View
{

    /**
     * List of allowed query params for shortcodes.
     * @var string[]
     */
    protected static $allowedShortCodeArgs= array(
        'p'             => '', // post id
//        // Author: https://developer.wordpress.org/reference/classes/wp_query/#author-parameters
//        'author'        => '',
//        'author_name'   => '',
//        // Category: https://developer.wordpress.org/reference/classes/wp_query/#category-parameters
//        'cat'           => '',
//        'category_name'      => '',
        'category_slug'      => '',
//        // Tag: https://developer.wordpress.org/reference/classes/wp_query/#tag-parameters
//        'tag'           => '',
//        'tag_id'        => '',
//        // Status https://developer.wordpress.org/reference/classes/wp_query/#status-parameters
//        'post_status'   => '',
//        // Pagination: https://developer.wordpress.org/reference/classes/wp_query/#pagination-parameters
//        'posts_per_page'=> '',
//        'nopaging'      => '',
//        'offset'        => ''
    );

    /**
     * Generates data needed for shortcode listing.
     * @param $cpt
     *
     * @return array
     * @throws \Exception
     */
    public static function getShortcodeData($cpt, $type) {
        $cptData = [];
        $timeframes = $cpt->getBookableTimeframes(true);
        /** @var \CommonsBooking\Model\Timeframe $timeframe */
        foreach ($timeframes as $timeframe) {
            $item = $timeframe->{'get' . $type}();

            // We need only published items
            if($item->post_status !== 'publish') continue;

            if(!array_key_exists($item->ID, $cptData)) {
                $cptData[$item->ID] = [
                    'start_date' => false,
                    'end_date' => false
                ];
            }

            if(
                !$cptData[$item->ID]['start_date'] ||
                $cptData[$item->ID]['start_date'] > $timeframe->getStartDate()
            ) {
                $cptData[$item->ID]['start_date'] = $timeframe->getStartDate();
            }

            if(
                !$cptData[$item->ID]['end_date'] ||
                $cptData[$item->ID]['end_date'] > $timeframe->getStartDate()
            ) {
                $cptData[$item->ID]['end_date'] = $timeframe->getEndDate();
            }
        }
        return $cptData;
    }

}
