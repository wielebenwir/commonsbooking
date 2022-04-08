<?php
/* Booking codes Genevieve Cory Freie Lastenradl München customization: emailing the bookingcodes directly to the station https://app.clickup.com/t/1w8vbmv 20220128 */

namespace CommonsBooking\View;
use CommonsBooking\Model\BookingCode;
use CommonsBooking\Repository\UserRepository;

class BookingCodes {

    /**
     * Renders table of booking codes for backend.
     * @param $timeframeId
     */
    public static function renderTable($timeframeId) {
        $bookingCodes = \CommonsBooking\Repository\BookingCodes::getCodes($timeframeId);
        $i = 0;


        /** @var BookingCode $bookingCode */

        $codesTable ="";

        foreach ($bookingCodes as $bookingCode) {
            $bookingDate = $bookingCode->getDate();
            if (strtotime($bookingDate) > time()) {

                $bookingDate = date_i18n("D j. F Y", strtotime($bookingDate));
                if( $i == 0 ){
                    $bookingMonth = $bookingCode->getDate();
                    $bookingMonth = date_i18n("F-Y", strtotime($bookingMonth));

                    $codesTable .= '<h1>'. $bookingCode->getItemName().' Codewörter ab ' . $bookingMonth . '</h1>';

                    echo '                    <table>';
                }
                    $codesTable .= "<tr>
                        <td>" . $bookingDate . "</td>
                        <td>" . $bookingCode->getCode() . "</td>
                     </tr>";
                $i++;
                if ($i == 45) break;  //to keep it all on one page, assuming we send these once a month, with some leeway in both directions
            }
        }
        $codesTable .= '    </table>';
        echo '<br>';
        $admins = [];

        if ($bookingCodes)  //Genevieve 20220225 - fixes the error, I hope
        {
            $admins = $bookingCode->getItemAdmins();
            if ($admins != null) {
                $users       = UserRepository::getCBManagers();
                $adminEmails = [];
                foreach($admins as $admin) {
                    foreach ($users as $user) {
                        if ($user->ID == $admin) {
                            $adminEmails[] = $user->get('user_email');
                        }
                    }
                }
                $adminEmailsList = implode(', ', $adminEmails);
            }
        }
        //begin rendering in the WordPress Backend, visible to Admins only, they have to click for it to send
        echo '
                    <div class="cmb-row cmb2-id-booking-codes-info">
                        <div class="cmb-th">
                            <label for="booking-codes-list">'. commonsbooking_sanitizeHTML( __('Download Booking Codes', 'commonsbooking')) .'</label>
                        </div>
                        <div class="cmb-td">
                            <a id="booking-codes-list" href="'. esc_url(admin_url('post.php')) . '?post='.$timeframeId.'&action=csvexport" target="_blank"><strong>Download Booking Codes</strong></a></br>
                            '. commonsbooking_sanitizeHTML( __('The file will be exported as a tab delimited .txt file so you can choose whether you want to print it,
                            open it in a separate application (like Word, Excel etc.). This will export <b>all codes for the entire timeframe</b>.', 'commonsbooking')) .'
                        <br>
                        <br>
                        </div>
                        <div class="cmb-th"><br><br>Email the codes now</div>
                        <div class="cmb-td">';
        if ($admins != null) {
            echo '          <a id="booking-codes-list" href="'. esc_url(admin_url('post.php')) . '?post='.$timeframeId.'&action=emailcodes" target="_blank"><strong>Email the Booking Codes</strong></a></br>
                            '. commonsbooking_sanitizeHTML( __('The codes will be sent to all the CBManagers set for each item, given in bold below. This will send only the next <b>45 codes starting from today, as visible below.</b>', 'commonsbooking')) .
                            '<br><b>' . $adminEmailsList . '</b>';
        }
        else {
            echo 'No admins are set for this item, codes cannot be automatically emailed!';  // Each item must have at least one admin. We also send cc to our it logs so we can resend from there if necessary.
        }
        echo '
                        </div>
                    </div>

                    <div class="cmb-row cmb2-id-booking-codes-list">';

        echo $codesTable;
        echo '</div>';

    }


    /**
     * @param $timeframeId
     */
    public static function renderCSV($timeframeId = null) {
        if($timeframeId == null) {
            $timeframeId = sanitize_text_field($_GET['post']);
        }
        $bookingCodes = \CommonsBooking\Repository\BookingCodes::getCodes($timeframeId);

        foreach ($bookingCodes as $bookingCode) { // this is the rendering for the txt download file
            if( $i == 0 ){
                $bookingMonth = $bookingCode->getDate();
                $bookingMonth = date_i18n("F-Y", strtotime($bookingMonth));

                $itemName = $bookingCode->getItemName();

                header('Content-Encoding: UTF-8');
                header('Content-type: text/csv; charset=UTF-8');
                header("Content-Disposition: attachment; filename=buchungscodes-$itemName-ab-$bookingMonth.txt");
                header('Content-Transfer-Encoding: binary');
                header("Pragma: no-cache");
                header("Expires: 0");
                echo "\xEF\xBB\xBF"; // UTF-8 BOM
                echo $itemName . " ab " . $bookingMonth . "\n\n" .
                    "Bitte am Monatsende mit den alten Ausleihformularen vernichten. Vielen Dank und gute Fahrt!\n\n";
            }

            $bookingDate = $bookingCode->getDate();
            $bookingDate = date_i18n("D j. F Y", strtotime($bookingDate));
            echo $bookingDate .
                 "\t|\t".  $bookingCode->getCode() .
                 "\n-------------------------------------------------\n";
            $i++;
            //if ($i == 45) break; // so that it's possible to still send a station the full set of codes for a longer time
        }
        die;
    }


    /**
     * @param $timeframeId
     */
    public static function emailCodes($timeframeId = null) { // begin building the email with formatting
        if($timeframeId == null) {
            $timeframeId = sanitize_text_field($_GET['post']);
        }
        $bookingCodes = \CommonsBooking\Repository\BookingCodes::getCodes($timeframeId);

        foreach ($bookingCodes as $bookingCode) { // this is the rendering for the email

            $bookingDate = $bookingCode->getDate(); // Date comparison so that the Timeframe doesn't have to get adjusted every time
            if (strtotime($bookingDate) > time()) {
                $bookingDate = date_i18n("D j. F Y", strtotime($bookingDate));

                if( $i == 0 ){
                    $bookingMonth = $bookingCode->getDate();
                    $bookingMonth = date_i18n("F-Y", strtotime($bookingMonth));

                    $itemName = $bookingCode->getItemName();

                    $body = "<h1>" . $itemName . " ab " . $bookingDate . "</h1>" . // TODO at first implementation - when I switched to this system, I added the paragraph below asking for confirmation of receipt.
                        "<p>

                        Liebe Lastenradbetreuer*innen, würdet Ihr bitte auf diese Mail kurz antworten,
                        um den Empfang der Codes zu bestätigen?
                        Wir haben uns angestrengt, dass diese gesamte Mail mit Codes gedruckt werden kann.
                        Alles passt auf einer Seite,
                        und dass einige Tage vorher und nachher auch angegeben sind,
                        damit wir mit dem Zeitpunkt des Verschickens flexibel sind.
                        Verbesserungsvorschläge sind auch willkommen!
                        <b>Kommen auch alle Umlaute richtig an?</b>
                        <br><br>
                        Bitte am Monatsende mit den alten Ausleihformularen vernichten. Vielen Dank und gute Fahrt!</p>


                        <table><tr><td>";
                }
                $body .= $bookingDate . "&nbsp;&nbsp;&nbsp;".  $bookingCode->getCode() . "<br>";
                $i++;
                if ($i == 23) {
                    $body .= "</td><td style='padding-left:2em;'>";
                    }
                if ($i == 45) {
                    $body .= "</td></tr></table>";
                    break;
                }
            }
        }
        $admins = [];
        $admins = $bookingCode->getItemAdmins();
        if ($admins != null) {
            $users       = UserRepository::getCBManagers();
            $adminEmails = [];
            foreach($admins as $admin) {
                foreach ($users as $user) {
                    if ($user->ID == $admin) {
                        $adminEmails[] = $user->get('user_email');
                    }
                }
            }
            $adminEmailsList = implode(', ', $adminEmails);
        }

        $message = '<html>
        <head>
        <title>Codes</title>
        </head>
        <body>' . $body . '</body></html>';

        // Multiple recipients
        $to = $adminEmailsList;

        // Subject
        $subject = 'Buchungscodes bis ' . $bookingDate . ' ' . $bookingCode->getItemName();

        // To send HTML mail, the Content-type header must be set
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=utf-8';

        // Additional headers
        //$headers[] = 'To: ' . $adminEmailsList;
        $headers[] = 'From: Freie Lastenradl Buchungs-Codes <info@freie-lastenradl.de>';  //TODO convert this to use the WordPress Admin address

        // Mail it
        mail($to, $subject, $message, implode("\r\n", $headers));

        die;
    }
}
