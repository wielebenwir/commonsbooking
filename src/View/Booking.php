<?php

namespace CommonsBooking\View;

use CommonsBooking\Model\Calendar;
use CommonsBooking\Model\Week;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;

class Booking extends View
{

    /**
     * Returns template data for frontend.
     *
     *
     * @return array
     * @throws \Exception
     */
    public static function getTemplateData(): array
    {
        $postsPerPage = sanitize_text_field($_POST['limit']) ?: 6;
        $page = sanitize_text_field($_POST['page']) ?: 1;

        $search = sanitize_text_field($_POST['search']);
        $sort = sanitize_text_field($_POST['sort']);
        $order = sanitize_text_field($_POST['order']);

        $bookingDataArray = [];
        $bookingDataArray['page'] = $page;
        $bookingDataArray['per_page'] = $postsPerPage;

        $posts = \CommonsBooking\Repository\Booking::getForCurrentUser(true);

        // Prepare Templatedata and remove invalid posts
        foreach ($posts as $booking) {

            // Get user infos
            $userInfo = get_userdata($booking->post_author);

            // Decide which edit link to use
            $editLink = get_permalink($booking->ID);
            if(commonsbooking_isCurrentUserAdmin()) {
                $editLink = get_edit_post_link($booking->ID);
            }
            $actions = '<a href="' . $editLink . '">'.__('editieren', COMMONSBOOKING_PLUGIN_SLUG).'</a>';

            // Prepare row data
            $rowData = [
                "startDate"   => date('d.m.Y H:i', $booking->getStartDate()),
                "endDate"     => date('d.m.Y H:i', $booking->getStartDate()),
                "item"        => $booking->getItem()->post_title,
                "location"    => $booking->getLocation()->post_title,
                "bookingDate" => date('d.m.Y H:i', strtotime($booking->post_date)),
                "user"        => $userInfo->user_login,
                "status"      => $booking->post_status
            ];

            // If search term was submitted, filter for it.
            if(
                !$search ||
                count(preg_grep('/.*' . $search . '.*/i', $rowData)) > 0
            ) {
                $rowData['actions'] = $actions;
                $bookingDataArray['data'][] = $rowData;
            }
        }

        $totalCount = count($bookingDataArray['data']);
        $bookingDataArray['total'] = $totalCount;
        $bookingDataArray['total_pages'] = ceil($totalCount / $postsPerPage);

        // Init function to pass sort and order param to sorting callback
        $sorter = function ($sort, $order) {
            return function ($a, $b) use ($sort, $order) {
                if($order == 'asc') {
                    return strcasecmp($a[$sort], $b[$sort]);
                } else {
                    return strcasecmp($b[$sort], $a[$sort]);
                }
            };
        };

        // Sorting
        usort(
            $bookingDataArray['data'],
            $sorter($sort, $order)
        );

        if($totalCount) {
            // Apply pagination...
            $index = 0;
            $pageCounter = 0;

            $offset = ($page - 1) * $postsPerPage;

            foreach ($bookingDataArray['data'] as $key => $post) {
                if($offset > $index++) {
                    unset($bookingDataArray['data'][$key]);
                    continue;
                }
                if($postsPerPage && $postsPerPage <= $pageCounter++) {
                    unset($bookingDataArray['data'][$key]);
                    continue;
                }
            }
        }

        header('Content-Type: application/json');
        echo json_encode($bookingDataArray);
        wp_die(); // All ajax handlers die when finished
    }

    /**
     * cb_items shortcode
     *
     * A list of items with timeframes.
     *
     * @param $atts
     *
     * @return false|string
     * @throws \Exception
     */
    public static function shortcode($atts)
    {
        global $templateData;
        $templateData = [];
        $templateData['bookings'] = \CommonsBooking\Repository\Booking::getForCurrentUser();

        ob_start();
        commonsbooking_get_template_part(
            'shortcode',
            'bookings',
            true,
            false,
            false
        );
        return ob_get_clean();
    }
}
