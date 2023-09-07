<?php


namespace CommonsBooking\View;


use CommonsBooking\Exception\BookingCodeException;
use CommonsBooking\Model\BookingCode;

/**
 * Booking codes can be get using the @see \CommonsBooking\Repository\BookingCodes class.
 * They will then be present as a @see CommonsBooking\Model\BookingCode object.
 *
 */
class BookingCodes {

	/**
	 * Renders table of booking codes for backend.
	 *
	 * @param $timeframeId
	 */
	public static function renderTable( $timeframeId ) {
		try {
			$bookingCodes = \CommonsBooking\Repository\BookingCodes::getCodes( $timeframeId );
		} catch ( BookingCodeException $e ) {
			echo $e->getMessage();
			return;
		}

		echo '
            <div class="cmb-row cmb2-id-booking-codes-info">
                <div class="cmb-th">
                    <label for="booking-codes-download">' . commonsbooking_sanitizeHTML( __( 'Download Booking Codes', 'commonsbooking' ) ) . '</label>
                </div>
                <div class="cmb-td">
                    <a id="booking-codes-download" href="' . esc_url( admin_url( 'post.php' ) ) . '?post=' . commonsbooking_sanitizeHTML( $timeframeId ) . '&action=csvexport" target="_blank"><strong>Download Booking Codes</strong></a></br>
                    ' . commonsbooking_sanitizeHTML( __( 'The file will be exported as tab delimited .txt file so you can choose wether you want to print it, open it in a separate application (like Word, Excel etc.)', 'commonsbooking' ) ) . '
                </div>
            </div>
            <div class="cmb-row cmb2-id-booking-codes-list">
            	<div class="cmb-th">
                    <label for="booking-codes-list">' . commonsbooking_sanitizeHTML( __( 'Booking codes list', 'commonsbooking' ) ) . '</label>
                </div>
	            <div class="cmb-td">
	                <table id="booking-codes-list">
	                    <tr>
							<td><b>' . esc_html__( 'Pickup date', 'commonsbooking' ) . '</b></td>
	                        <td><b>' . esc_html__( 'Item', 'commonsbooking' ) . '</b></td>
							<td><b>' . esc_html__( 'Code', 'commonsbooking' ) . '</b></td>
	                    </tr>';

						/** @var BookingCode $bookingCode */
						foreach ( $bookingCodes as $bookingCode ) {
							echo "<tr>
									<td>" . commonsbooking_sanitizeHTML( date_i18n( get_option( 'date_format' ), strtotime ($bookingCode->getDate() ) ) ) . "</td>
				                    <td>" . commonsbooking_sanitizeHTML( $bookingCode->getItemName() ) . "</td>
				                    <td>" . commonsbooking_sanitizeHTML( $bookingCode->getCode() ) . "</td>
				                </tr>";
						}
		echo '    </table>
	            </div>
            </div>';
	}

	/**
	 * Renders CVS file (txt-format) with booking codes for download
	 * @param $timeframeId
	 */
	public static function renderCSV( $timeframeId = null ) {
		if ( $timeframeId == null ) {
			$timeframeId = intval( $_GET['post'] );
		}

		header( 'Content-Encoding: UTF-8' );
		header( 'Content-type: text/csv; charset=UTF-8' );
		header( "Content-Disposition: attachment; filename=buchungscode-" . commonsbooking_sanitizeHTML( $timeframeId ) . ".txt" );
		header( 'Content-Transfer-Encoding: binary' );
		header( "Pragma: no-cache" );
		header( "Expires: 0" );
		echo "\xEF\xBB\xBF"; // UTF-8 BOM

		try {
			$bookingCodes = \CommonsBooking\Repository\BookingCodes::getCodes( $timeframeId );
		} catch ( BookingCodeException $e ) {
			echo $e->getMessage();
			die;
		}

		foreach ( $bookingCodes as $bookingCode ) {
			echo commonsbooking_sanitizeHTML( $bookingCode->getDate() ) .
			     "\t" . commonsbooking_sanitizeHTML( $bookingCode->getItemName() ) .
			     "\t" . commonsbooking_sanitizeHTML( $bookingCode->getCode() ) . "\n";
		}
		die;
	}

}
