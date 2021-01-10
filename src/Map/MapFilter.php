<?php

namespace CommonsBooking\Map;

class MapFilter
{

    /**
     * get all the locations of the map with provided id that belong to timeframes and filter by given categories
     *
     * @param $locations
     * @param $cb_map_id
     * @param array $preset_categories
     *
     * @return array
     */
    public static function filter_locations_by_timeframes_and_categories(
        $locations,
        $cb_map_id,
        $preset_categories = []
    ) {
//        $cb_data = new CB_Data();

        $result     = [];
        $timeframes = Map::get_timeframes($cb_map_id);

        //$category_tree = Map::get_structured_cb_items_category_tree();
        $preset_category_groups = Map::get_cb_items_category_groups($preset_categories);

        foreach ($timeframes as $timeframe) {
            $location_id   = $timeframe['location_id'];
            $item          = $timeframe['item'];
            $is_valid_item = true;
            $item_terms    = wp_get_post_terms($item['id'], 'cb_items_category');

            if (count($preset_category_groups) > 0) {
                $is_valid_item = self::check_item_terms_against_categories($item_terms, $preset_category_groups);
            }

            if ($is_valid_item) {

                //if a location exists, that is allowed to be shown on map
                if (isset($locations[$location_id])) {

                    //if location is not present in result yet, add it
                    if ( ! isset($result[$location_id])) {
                        $result[$location_id] = $locations[$location_id];
                    }

                    //add term ids to item
                    $item_term_ids = [];
                    foreach ($item_terms as $item_term) {
                        $item_term_ids[] = $item_term->term_id;
                    }
                    $item['terms'] = $item_term_ids;

                    //add item to location
                    if ( ! isset($result[$location_id]['items'][$timeframe['item']['id']])) {
                        $item['timeframes']                                      = [];
                        $item['timeframe_hints']                                 = [];
                        $result[$location_id]['items'][$timeframe['item']['id']] = $item;
                    }

                    //add timeframe to item
                    $result[$location_id]['items'][$timeframe['item']['id']]['timeframes'][] = [
                        'date_start' => $timeframe['date_start'],
                        'date_end'   => $timeframe['date_end'],
                    ];

                    //add timeframe hint
                    $now = new \DateTime();

                    $date_start = new \DateTime();
                    $date_start->setTimestamp(strtotime($timeframe['date_start']));

                    $date_end = new \DateTime();
                    $date_end->setTimestamp(strtotime($timeframe['date_end']));
                    $diff_end = $date_end->diff($now)->format("%a");

                    //show hint if timeframe starts in the future
                    if ($date_start > $now) {
                        $result[$location_id]['items'][$timeframe['item']['id']]['timeframe_hints'][] = [
                            'type'      => 'from',
                            'timestamp' => strtotime($timeframe['date_start']),
                        ];
                    }

                    // @TODO: Check what it's for...
                    //show hint for near end of timeframe if it's before the last possible day to book (CB settings)
//                    if ($diff_end <= $cb_data->daystoshow) {
//                        $result[$location_id]['items'][$timeframe['item']['id']]['timeframe_hints'][] = [
//                            'type'      => 'until',
//                            'timestamp' => strtotime($timeframe['date_end']),
//                        ];
//                    }

                }
            }
        }

        //convert items to nummeric array
        foreach ($result as &$location) {
            $location['items'] = array_values($location['items']);
        }

        return $result;
    }

    protected static function check_item_terms_against_categories($item_terms, $category_groups)
    {
        $valid_groups_count = 0;

        foreach ($category_groups as $group) {
            foreach ($item_terms as $term) {
                if (in_array($term->term_id, $group)) {
                    $valid_groups_count++;
                    break;
                }
            }
        }

        return $valid_groups_count == count($category_groups);
    }
}

?>
