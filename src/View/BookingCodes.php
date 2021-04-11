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
            <div class="cmb-row cmb2-id-booking-codes-info">
                <div class="cmb-th">
                    <label for="booking-codes-list">'. commonsbooking_sanitizeHTML( __('Download Booking Codes', 'commonsbooking')) .'</label>
                </div>
                <div class="cmb-td">
                    <a id="booking-codes-list" href="'. esc_url(admin_url('post.php')) . '?post='.$timeframeId.'&action=csvexport" target="_blank"><strong>Download Booking Codes</strong></a></br>
                    '. commonsbooking_sanitizeHTML( __('The file will be exported as tab delimited .txt file so you can choose wether you want to print it, open it in a separate application (like Word, Excel etc.)', 'commonsbooking')) .'
                </div>
            </div>
                <div <div class="cmb-row cmb2-id-booking-codes-list">
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
        //header("content-type:application/csv;charset=UTF-8");
        header('Content-Encoding: UTF-8');
        header('Content-type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=buchungscode-$timeframeId.txt");
        header('Content-Transfer-Encoding: binary'); 
        header("Pragma: no-cache");
        header("Expires: 0");
        echo "\xEF\xBB\xBF"; // UTF-8 BOM

        foreach ($bookingCodes as $bookingCode) {
            echo $bookingCode->getDate() .
                 "\t" . $bookingCode->getItemName() .
                 "\t".  $bookingCode->getCode() . "\n";
        }
        die;
    }

}
