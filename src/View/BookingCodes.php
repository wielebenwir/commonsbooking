<?php

namespace CommonsBooking\View;

use CommonsBooking\Settings\Settings;
use CommonsBooking\CB\CB;
use CommonsBooking\Exception\BookingCodeException;
use CommonsBooking\Model\BookingCode;
use CommonsBooking\Messages\BookingCodesMessage;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Helper\Wordpress;
use DateTime;
use DateTimeImmutable;

/**
 * Booking codes can be gotten using the @see \CommonsBooking\Repository\BookingCodes class.
 * They will then be present as a @see CommonsBooking\Model\BookingCode object.
 */
class BookingCodes {

	public const LAST_CODES_EMAIL = COMMONSBOOKING_METABOX_PREFIX . 'last_email_codes';
	public const NEXT_CRON_EMAIL  = COMMONSBOOKING_METABOX_PREFIX . 'next_cron_email_codes';
	public const CRON_EMAIL_CODES = COMMONSBOOKING_METABOX_PREFIX . 'cron_email_codes';

	/**
	 * Next booking codes by Email Cron event for timeframe.
	 * based on current timestamp calculated start and period
	 *
	 * @param int $tsInitial - Timestamp of the inital date
	 * @param int $periodMonths
	 *
	 * @return DateTimeImmutable   Datetime of nex Cron Event.
	 */
	public static function initialCronEmailEvent( int $tsInitial, int $periodMonths ): DateTimeImmutable {
		$start = new DateTimeImmutable();
		$start = $start->setTimestamp( $tsInitial );
		if ( $tsInitial >= strtotime( 'today' ) ) {
			return $start;
		}

		$end  = new DateTime( 'today' );
		$diff = $start->diff( $end );

		$yearsInMonths = intval( $diff->format( '%r%y' ) ) * 12;
		$months        = intval( $diff->format( '%r%m' ) );
		$totalMonths   = $yearsInMonths + $months;
		$numPeriods    = floor( $totalMonths / $periodMonths ) + 1;
		$fullMonth     = $numPeriods * $periodMonths;

		$dtTo = $start->modify( 'midnight last day of next month +' . ( $fullMonth - 1 ) . ' month' );

		$daydiff = (int) $dtTo->format( 'j' ) - (int) $start->format( 'j' );
		if ( $daydiff > 0 ) {
			$dtNextCronEvent = $dtTo->modify( '-' . $daydiff . ' days' );
		} else {
			$dtNextCronEvent = $dtTo;
		}

		return $dtNextCronEvent;
	}

	/**
	 * CMB2 callback on field saved
	 *
	 * @param bool        $updated Whether the metadata update action occurred.
	 * @param string      $action  Action performed. Could be "repeatable", "updated", or "removed".
	 * @param \CMB2_Field $field   This field object
	 */
	public static function cronEmailCodesSaved( $updated, $action, $field ): void {
		$postID = $field->object_id();
		if ( ! $updated || empty( $postID ) ) {
			return;
		}

		switch ( $action ) {
			case 'updated':
				$value = $field->value();
				if ( empty( $value['cron-booking-codes-enabled'] ) ) {
					delete_post_meta( $postID, self::NEXT_CRON_EMAIL );
				} elseif ( ! empty( $value['cron-email-booking-code-start'] )
							&& ! empty( $value['cron-email-booking-code-nummonth'] ) ) {
					$dtNextCron = self::initialCronEmailEvent( $value['cron-email-booking-code-start'], $value['cron-email-booking-code-nummonth'] );
					update_post_meta( $postID, self::NEXT_CRON_EMAIL, $dtNextCron->getTimestamp() );
				}
				break;

			case 'removed':
				delete_post_meta( $postID, self::NEXT_CRON_EMAIL );
				break;

			default:
				break;
		}
	}

	/**
	 * CMB2 sanitize field callback
	 * Will take the entered start date and saves it as a timestamp.
	 *
	 * @param  mixed       $value      The unsanitized value from the form.
	 * @param  array       $field_args Array of field arguments.
	 * @param  \CMB2_Field $field      The field object
	 *
	 * @return ?array                  Sanitized value to be stored.
	 */
	public static function sanitizeCronEmailCodes( $value, $field_args, $field ): ?array {
		if ( $value == null ) {
			return null;
		}

		$automatedSendingActivated = $value['cron-booking-codes-enabled'];
		$sendInitialCodes          = $value['cron-email-booking-code-start'];
		$monthsToSendPerEmail      = $value['cron-email-booking-code-nummonth'];

		$startOfFirstEmail = DateTime::createFromFormat( $field_args['date_format_start'], $sendInitialCodes );
		if ( $startOfFirstEmail ) {
			$startOfFirstEmail->setTime( 0, 0 ); // normalize to midnight, otherwise always modified/updated state
		}
		$toSave = array(
			'cron-booking-codes-enabled' => sanitize_text_field( $automatedSendingActivated ),
			'cron-email-booking-code-nummonth' => absint( $monthsToSendPerEmail ),
			'cron-email-booking-code-start' => $startOfFirstEmail ? $startOfFirstEmail->getTimestamp() : $field->args['default_start'],
		);

		return $toSave;
	}

	/**
	 * CMB2 escape field callback
	 *
	 * @param  mixed       $value      The unescaped value from the database.
	 * @param  array       $field_args Array of field arguments.
	 * @param  \CMB2_Field $field      The field object
	 *
	 * @return array                  Escaped value to be displayed.
	 */
	public static function escapeCronEmailCodes( $value, $field_args, $field ): array {

		if ( is_array( $value ) ) {
			return array(
				'cron-booking-codes-enabled' => ! empty( $value['cron-booking-codes-enabled'] ) ? $value['cron-booking-codes-enabled'] : '',
				'cron-email-booking-code-nummonth' => ! empty( $value['cron-email-booking-code-nummonth'] ) && is_numeric( $value['cron-email-booking-code-nummonth'] ) ? $value['cron-email-booking-code-nummonth'] :
																											$field->args['default_nummonth'],
				'cron-email-booking-code-start' => ! empty( $value['cron-email-booking-code-start'] ) && is_numeric( $value['cron-email-booking-code-start'] ) ?
														date( $field_args['date_format_start'], $value['cron-email-booking-code-start'] ) :
														date( $field_args['date_format_start'], $field->args['default_start'] ),
			);
		} else {
			return array(
				'cron-booking-codes-enabled' => '',
				'cron-email-booking-code-nummonth' => $field->args['default_nummonth'],
				'cron-email-booking-code-start' => date( $field_args['date_format_start'], $field->args['default_start'] ),
			);
		}
	}

	/**
	 * renders custom CMB2 field settings for Cron Booking Codes
	 *
	 * @param  $field: The current CMB2_Field object.
	 * @param  $escaped_value: The value of this field passed through the escaping filter.
	 * @param  $object_id: The id of the object you are working with. Most commonly, the post id.
	 * @param  $object_type: The type of object you are working with.
	 * @param  $field_type_object: This is an instance of the CMB2_Types object and gives you access to all of the methods
	 */
	public static function renderCronEmailFields( $field, $escaped_value, $object_id, $object_type, $field_type ): void {

		$timeframeId = $object_id;
		$timeframe   = \CommonsBooking\Repository\Timeframe::getPostById( $timeframeId );

		$location        = $timeframe->getLocation();
		$location_emails = $location ?
			CB::get( \CommonsBooking\Wordpress\CustomPostType\Location::$postType, COMMONSBOOKING_METABOX_PREFIX . 'location_email', $timeframe->getLocation() ) :
			array();

		$errMsg = null;
		if ( empty( $location ) ) {
			$errMsg = commonsbooking_sanitizeHTML( __( 'No location configured for this timeframe', 'commonsbooking' ) );
		} elseif ( empty( $location_emails ) ) {
			$errMsg = commonsbooking_sanitizeHTML(
				__( 'Unable to send emails. No location email configured, check location', 'commonsbooking' ) .
													sprintf( ' <a href="%s" class="cb-title cb-title-link">%s</a>', esc_url( get_edit_post_link( $location->ID ) ), commonsbooking_sanitizeHTML( $location->post_title ) )
			);
		} elseif ( ! $timeframe->hasBookingCodes() ) {
			$errMsg = commonsbooking_sanitizeHTML( __( 'This timeframe has no booking codes. To generate booking codes you need to save the timeframe.', 'commonsbooking' ) );
		}

		if ( $errMsg != null ) {
			printf( '<div id="cron-email-booking-code">%s</div>', $errMsg );

			return;
		}
		$value = wp_parse_args(
			$escaped_value,
			array(
				'cron-booking-codes-enabled' => '',
				'cron-email-booking-code-start' => $field->args['default_start'],
				'cron-email-booking-code-nummonth'      => $field->args['default_nummonth'],
			)
		);

		$checked = ! empty( $value['cron-booking-codes-enabled'] ) ? 'checked="checked"' : '';

		$jsDateFormat = \CMB2_Utils::php_to_js_dateformat( $field->args['date_format_start'] );
		wp_add_inline_script(
			'jquery-ui-datepicker',
			"
            jQuery( function() {
                jQuery( '.cb-custom-dtp' ).datepicker({
                    dateFormat: '{$jsDateFormat}',
                    beforeShow: function(input, inst) {
                        jQuery('#ui-datepicker-div').addClass('cmb2-element');
                    }
                });
            } );        
        ",
			'after'
		);

		$tsNextCronEvent = get_post_meta( $field->object_id(), self::NEXT_CRON_EMAIL, true );
		if ( ! empty( $tsNextCronEvent ) ) {
			$nextCronEventFmt = wp_date( get_option( 'date_format' ), $tsNextCronEvent );
		} else {
			$nextCronEventFmt = $field->args['msg_email_not_planned'];
		}

		echo <<<HTML
            <input type="checkbox" class="cmb2-option cmb2-list" name="{$field_type->_name( '[cron-booking-codes-enabled]' )}" 
                                        id="{$field_type->_id( '[cron-booking-codes-enabled]' )}" value="on" {$checked} >
            <label id="cron-email-booking-code" for="{$field_type->_id( '[cron-booking-codes-enabled]' )}">
                <span class="cmb2-metabox-description">{$field->args['desc_cb']} </span>
            </label>

            <div class="cb-admin-page">
            <div class="cb-admin-cols">
                <div>
                    <p class="cmb-add-row">
                    <label for="{$field_type->_id( '[cron-email-booking-code-start]' )}">
                        <b>{$field->args['name_start']}</b>
                    </label>
                    </p>
                    <input class='cmb2-text-small cb-custom-dtp' name="{$field_type->_name( '[cron-email-booking-code-start]' )}" 
                                value="{$value['cron-email-booking-code-start']}" type='text' id="{$field_type->_id( '[cron-email-booking-code-start]' )}" >
                    <p class="cmb2-metabox-description">{$field->args['desc_start']}</p>
                </div>

                <div>
                <p class="cmb-add-row">
                    <label for="{$field_type->_id( '[cron-email-booking-code-nummonth]' )}">
                        <b>{$field->args['name_nummonth']}</b>
                    </label>
                    </p>
                    <input class='cmb2-text-small' name="{$field_type->_name( '[cron-email-booking-code-nummonth]' )}" 
                            value="{$value['cron-email-booking-code-nummonth']}" type='number' min="1" id="{$field_type->_id( '[cron-email-booking-code-nummonth]' )}" >
                    <p  class="cmb2-metabox-description">{$field->args['desc_nummonth']}</p>
                </div>
        </div> <!-- cb-admin-cols -->
        </div>
        <p class="cmb2-metabox-description">{$field->args['msg_next_email']} {$nextCronEventFmt}</p>
HTML;
	}

	/**
	 * renders CMB2 field row
	 *
	 * @param  array       $field_args Array of field arguments.
	 * @param  \CMB2_Field $field      The field object
	 */
	public static function renderDirectEmailRow( $field_args, $field ) {

		$timeframeId = $field->object_id();
		$timeframe   = \CommonsBooking\Repository\Timeframe::getPostById( $timeframeId );

		$location        = $timeframe->getLocation();
		$location_emails = $location ?
							CB::get( \CommonsBooking\Wordpress\CustomPostType\Location::$postType, COMMONSBOOKING_METABOX_PREFIX . 'location_email', $timeframe->getLocation() ) :
							array();

		echo '<div class="cmb-row cmb2-id-email-booking-codes-info">
                <div class="cmb-th">
                    <label for="email-booking-codes-list">' . commonsbooking_sanitizeHTML( __( 'Send booking codes by email', 'commonsbooking' ) ) . '</label>
                </div>

                <div id="email-booking-codes-list" class="cmb-td">';

		if ( empty( $location ) ) {
			echo commonsbooking_sanitizeHTML( __( 'No location configured for this timeframe', 'commonsbooking' ) );
		} elseif ( empty( $location_emails ) ) {
			echo commonsbooking_sanitizeHTML(
				__( 'Unable to send Emails. No location email configured, check location', 'commonsbooking' ) .
				sprintf( ' <a href="%s" class="cb-title cb-title-link">%s</a>', esc_url( get_edit_post_link( $location->ID ) ), commonsbooking_sanitizeHTML( $location->post_title ) )
			);
		} elseif ( ! $timeframe->hasBookingCodes() ) {
			echo commonsbooking_sanitizeHTML( __( 'This timeframe has no booking codes. To generate booking codes you need to save the timeframe.', 'commonsbooking' ) );
		} else {
			echo '
            	<div id ="timeframe-bookingcodes-sendall">
                  <a id="email-booking-codes-list-all" 
                                href="' . esc_url(
				add_query_arg(
					[
						'action' => 'cb_email-bookingcodes',
						'redir' => rawurlencode( add_query_arg( [] ) ),
					]
				)
			) . '" >
                            <strong>' . commonsbooking_sanitizeHTML( __( 'Email booking codes for the entire timeframe', 'commonsbooking' ) ) . '</strong>
                        </a>
                    <br>
                    ' . commonsbooking_sanitizeHTML( __( '<b>All codes for the entire timeframe</b> will be emailed to the location email(s), given in bold below.', 'commonsbooking' ) )
				. '</div>';

			$from = strtotime( 'midnight first day of this month' );
			$to   = strtotime( 'midnight last day of this month' );
			echo '
			<div id ="timeframe-bookingcodes-send_current_month">
          		<br><a id="email-booking-codes-list-current" 
                                    href="' . esc_url(
				add_query_arg(
					[
						'action' => 'cb_email-bookingcodes',
						'from' => $from,
						'to' => $to,
						'redir' => rawurlencode( add_query_arg( [] ) ),
					]
				)
			) . '" >
                            <strong>' . commonsbooking_sanitizeHTML( __( 'Email booking codes of current month', 'commonsbooking' ) ) . '</strong>
                        </a><br>
                        ' . commonsbooking_sanitizeHTML( __( 'The codes <b>of the current month</b> will be sent to all the location email(s), given in bold below', 'commonsbooking' ) )
			. '</div>';
			$from = strtotime( 'midnight first day of next month' );
			$to   = strtotime( 'midnight last day of next month' );

			echo '
			<div id ="timeframe-bookingcodes-send_next_month">
          	<br><a id="email-booking-codes-list-next" 
                                    href="' . esc_url(
				add_query_arg(
					[
						'action' => 'cb_email-bookingcodes',
						'from' => $from,
						'to' => $to,
						'redir' => rawurlencode( add_query_arg( [] ) ),
					]
				)
			) . '" >
                            <strong>' . commonsbooking_sanitizeHTML( __( 'Email booking codes of next month', 'commonsbooking' ) ) . '</strong>
                        </a><br>
                        ' . commonsbooking_sanitizeHTML( __( 'The codes <b>of the next month</b> will be sent to all the location email(s), given in bold below.', 'commonsbooking' ) ) .
						'<br><br>
                        <div>' . commonsbooking_sanitizeHTML( __( 'Currently configured location email(s): ', 'commonsbooking' ) ) . '<b>' . $location_emails . '</b></div>'
				. '<br><br>
                 <div>' . commonsbooking_sanitizeHTML( __( '<b>IMPORTANT</b>: You need to save the timeframe before you can send out the booking codes.' ) ) . '</div>'
			. '</div>';

			$lastBookingEmail = get_post_meta( $timeframeId, self::LAST_CODES_EMAIL, true );
			if ( ! empty( $lastBookingEmail ) ) {
				echo '<p  class="cmb2-metabox-description"><b>'
						. commonsbooking_sanitizeHTML( __( 'Last booking codes email sent:', 'commonsbooking' ) ) . ' </b>'
						. wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $lastBookingEmail ) . '</p>';
			}
		}

		echo '
                </div>
            </div>';

		return true;
	}

	/**
	 * Renders table of booking codes.
	 *
	 * @param $timeframeId
	 */
	public static function renderTable( $timeframeId ) {
		try {
			$timeframe = \CommonsBooking\Repository\Timeframe::getPostById( $timeframeId );} catch ( BookingCodeException $e ) {
			echo $e->getMessage();
			return;
			}

			echo '
            <div class="cmb-row cmb2-id-booking-codes-info">
                <div class="cmb-th">
                    <label for="booking-codes-download-link">' . commonsbooking_sanitizeHTML( __( 'Download booking codes', 'commonsbooking' ) ) . '</label>
                </div>
                <div id="booking-codes-download" class="cmb-td">' . ( $timeframe->hasBookingCodes() ? '
                    <a id="booking-codes-download-link" href="' . esc_url( add_query_arg( [ 'action' => 'cb_download-bookingscodes-csv' ] ) ) . '" target="_blank"><strong>Download booking codes</strong></a>
                    <p  class="cmb2-metabox-description">
                    ' . commonsbooking_sanitizeHTML( sprintf( __( 'Will download all available booking codes for this timeframe. If the timeframe has no end-date, the booking codes for the next %s days will be retrieved.', 'commonsbooking' ), \CommonsBooking\Repository\BookingCodes::ADVANCE_GENERATION_DAYS ) ) . '<br>
                    ' . commonsbooking_sanitizeHTML( __( 'The file will be exported as tab delimited .txt file so you can choose wether you want to print it, open it in a separate application (like Word, Excel etc.)', 'commonsbooking' ) ) . '
                    </p>' : commonsbooking_sanitizeHTML( __( 'This timeframe has no booking codes. To generate booking codes you need to save the timeframe.', 'commonsbooking' ) ) ) . '
                </div>
            </div>';

		// This settings is the amount of booking codes that should be shown to the user
		$bcToShow = Settings::getOption( 'commonsbooking_options_bookingcodes', 'bookingcodes-listed-timeframe' );
		if ( $bcToShow > 0 ) {
			$tsStart      = max( Wordpress::getUTCDateTime( 'today' )->getTimestamp(), $timeframe->getStartDate() );
			$tsEnd        = strtotime( ' +' . ( $bcToShow - 1 ) . ' days', $tsStart );
			$bookingCodes = \CommonsBooking\Repository\BookingCodes::getCodes( $timeframeId, $tsStart, $tsEnd );

			echo '<div class="cmb-row cmb2-id-booking-codes-list">
                <div class="cmb-th">
                    <label id="booking-codes-list">' . commonsbooking_sanitizeHTML( __( 'Booking codes list', 'commonsbooking' ) ) . '</label>
                </div>
                <div class="cmb-td">';
			if ( $timeframe->hasBookingCodes() ) {
				echo self::renderTableFor( 'timeframe_form', $bookingCodes );

				echo '<br>';
				echo '<p  class="cmb2-metabox-description">';
					printf( __( 'Only showing booking codes for the next %s days.', 'commonsbooking' ), $bcToShow );
					echo '<br>';
					echo __( 'The amount of booking codes shown in the overview can be changed in the settings.', 'commonsbooking' );
				echo '</p>';
			} else {
				echo commonsbooking_sanitizeHTML( __( 'This timeframe has no booking codes. To generate booking codes you need to save the timeframe.', 'commonsbooking' ) );
			}
			echo '</div></div>';
		}

		return true;
	}

	/**
	 * Renders HTML table of bookingCodes List.
	 *
	 * @param BookingCode[] $bookingCodes list of booking codes
	 *
	 * @return string HTML table
	 */
	public static function renderBookingCodesTable( $bookingCodes ): string {
		if ( empty( $bookingCodes ) ) {
			return '';
		}

		$lines = [];
		foreach ( $bookingCodes as $bookingCode ) {
			$lines[] = '<tr><td>' . commonsbooking_sanitizeHTML( wp_date( 'D j. F Y', strtotime( $bookingCode->getDate() ) ) ) .
						'</td><td>' . commonsbooking_sanitizeHTML( $bookingCode->getCode() ) . '</td></tr>';
		}

		// if odd number of lines add empty row
		if ( count( $lines ) % 2 != 0 ) {
			array_push( $lines, '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>' );
		}
		$parts = array_chunk( $lines, intval( ceil( count( $lines ) / 2 ) ) );

		return "<table  cellspacing='0' cellpadding='20' class='cmb2-codes-outer-table' ><tbody><tr><td><table class='cmb2-codes-column' cellspacing=\"0\" cellpadding=\"8\" border=\"1\"><tbody>" .
						implode( '', $parts[0] ) .
						"</tbody></table></td><td><table class='cmb2-codes-column'  cellspacing=\"0\" cellpadding=\"8\" border=\"1\"><tbody>" .
						implode( '', $parts[1] ) .
						'</tbody></table></td></tr></tbody></table>';
	}

	/**
	 * Renders CVS file (txt-format) with booking codes for download
	 *
	 * @param int|null $timeframeId
	 */
	public static function renderCSV( $timeframeId = null ) {
		if ( $timeframeId == null ) {
			$timeframeId = intval( $_GET['post'] );
		}
		header( 'Content-Encoding: UTF-8' );
		header( 'Content-type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename=buchungscode-' . commonsbooking_sanitizeHTML( $timeframeId ) . '.txt' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
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

	/**
	 * action for sending Booking Codes by E-mail
	 *
	 * @param int $timeframeId      ID of requested timeframe
	 * @param int $tsFrom           Timestamp of first Booking Code
	 * @param int $tsTo             Timestamp of last Booking Code
	 */
	public static function emailCodes( $timeframeId = null, $tsFrom = null, $tsTo = null ) {

		if ( $timeframeId == null ) {
			$timeframeId = empty( $_GET['post'] ) ? null : sanitize_text_field( $_GET['post'] );
		}

		if ( empty( $timeframeId ) ) {
			wp_die(
				commonsbooking_sanitizeHTML( __( 'Unable to retrieve booking codes', 'commonsbooking' ) ),
				commonsbooking_sanitizeHTML( __( 'Missing ID of timeframe post', 'commonsbooking' ) ),
				[ 'back_link' => true ]
			);
		}

		if ( $tsFrom == null ) {
			$tsFrom = empty( $_GET['from'] ) ? null : absint( $_GET['from'] );
		}
		if ( $tsTo == null ) {
			$tsTo = empty( $_GET['to'] ) ? null : absint( $_GET['to'] );
		}

		add_action(
			'commonsbooking_mail_sent',
			function ( $action, $result ) use ( $timeframeId ) {
				$redir = empty( $_GET['redir'] ) ? add_query_arg(
					[
						'post' => $timeframeId,
						'action' => 'edit',
					],
					admin_url( 'post.php' )
				) : $_GET['redir'];

				if ( is_wp_error( $result ) ) {
					set_transient( BookingCode::ERROR_TYPE, $result->get_error_message() );
				} elseif ( $result === false ) {
					set_transient( BookingCode::ERROR_TYPE, __( 'Error sending booking codes', 'commonsbooking' ) );
				} else {
					// $redir=add_query_arg([ 'cmb2_bookingcodes_result' => 'success' ],$redir);
					$redir = explode( '#', $redir )[0] . '#email-booking-codes-list';
				}

				wp_safe_redirect( $redir );
				exit;
			},
			10,
			2
		);

		$booking_msg = new BookingCodesMessage( $timeframeId, 'codes', $tsFrom, $tsTo );
		$booking_msg->sendMessage();

		// this should never happen
		wp_die(
			commonsbooking_sanitizeHTML( __( 'An unknown error occured', 'commonsbooking' ) ),
			commonsbooking_sanitizeHTML( __( 'Email booking codes', 'commonsbooking' ) ),
			[ 'back_link' => true ]
		);
	}

	/**
	 * @param string        $renderTarget code where email is rendered (email|timeframe_form)
	 * @param BookingCode[] $bookingCodes array of string booking codes
	 *
	 * @return string|null
	 */
	public static function renderTableFor( $renderTarget, $bookingCodes ) {
		$renderedTable = self::renderBookingCodesTable( $bookingCodes );
		/**
		 * Default rendering of the booking code table in the specified target.
		 *
		 * @since 2.9.0
		 *
		 * @param string        $renderedTable rendering of booking codes list as html string
		 * @param BookingCode[] $bookingCodes list of booking codes
		 * @param string        $renderTarget where email is rendered (email|timeframe_form)
		 */
		return apply_filters(
			'commonsbooking_emailcodes_rendertable',
			$renderedTable,
			$bookingCodes,
			$renderTarget
		);
	}
}
