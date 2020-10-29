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
        $bookingCodes = \CommonsBooking\Repository\BookingCodes::get($timeframeId);

        echo '
            <div class="cmb-row cmb-type-checkbox cmb2-id-create-booking-codes" data-fieldtype="checkbox">
                <div class="cmb-th">
                    <label for="create-booking-codes">Booking Codes</label>
                </div>
                    <div class="cmb-td">
                        <table>
                            <tr>
                                <td><b>'.__('Item').'</b></td><td><b>'.__('Startdatum').'</b></td><td><b>'.__('Code').'</b></td>
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

}
