<?php


namespace CommonsBooking\View;


use CommonsBooking\Model\BookingCode;

class BookingCodes
{

    /**
     * Renders table of booking codes for backend.
     * @param $timeframeId
     */
    public static function renderTable($timeframeId) {
        $bookingCodes = \CommonsBooking\Repository\BookingCodes::getCodes($timeframeId);

        echo '
            <div class="cmb-row cmb2-id-booking-codes-list">
                <div class="cmb-th">
                    <label for="booking-codes-list">Booking Codes</label>
                    <a id="booking-codes-list" href="'. esc_url(admin_url('post.php')) . '?post='.$timeframeId.'&action=csvexport" target="_blank">Download</a>
                </div>
                    <div class="cmb-td">
                        <table>
                            <tr>
                                <td><b>'.esc_html__('Item', 'commonsbooking').'</b></td><td><b>' . esc_html__('Pickup date', 'commonsbooking').'</b></td><td><b>'.esc_html__('Code', 'commonsbooking').'</b></td>
                            </tr>';

        /** @var BookingCode $bookingCode */
        foreach ($bookingCodes as $bookingCode) {
            echo "<tr>
                    <td>" . $bookingCode->getItemName()."</td>
                    <td>" . $bookingCode->getDate() . "</td>
                    <td>" . $bookingCode->getCode() . "</td>
                </tr>";
        }
        echo '    </table>
                </div>
            </div>';
    }

    /**
     * @param $timeframeId
     */
    public static function renderCSV($timeframeId = null) {
        if($timeframeId == null) {
            $timeframeId = sanitize_text_field($_GET['post']);
        }
        $bookingCodes = \CommonsBooking\Repository\BookingCodes::getCodes($timeframeId);
        header("content-type:application/csv;charset=UTF-8");
        header("Content-Disposition: attachment; filename=buchungscode-$timeframeId.csv");
        header("Pragma: no-cache");
        header("Expires: 0");

        foreach ($bookingCodes as $bookingCode) {
            echo $bookingCode->getDate() .
                 "," . $bookingCode->getItemName() .
                 ",".  $bookingCode->getCode() . "\n";
        }
        die;
    }

}
