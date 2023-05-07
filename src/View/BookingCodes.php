<?php

namespace CommonsBooking\View;
use CommonsBooking\Model\BookingCode;
use CommonsBooking\Messages\BookingCodesMessage;
use CommonsBooking\Repository\UserRepository;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Helper\Wordpress;
use DateTime;
use DateTimeImmutable;

class BookingCodes
{
    public const LAST_CODES_EMAIL = COMMONSBOOKING_METABOX_PREFIX . 'last_email_codes';
    public const NEXT_CRON_EMAIL = COMMONSBOOKING_METABOX_PREFIX . 'next_cron_email_codes';
    public const CRON_EMAIL_CODES = COMMONSBOOKING_METABOX_PREFIX . 'cron_email_codes';

 	/**
	 * Next booking codes by Email Cron event for timeframe.
     * based on current timestamp calculated start and period 
	 *
	 * @param Timestamp $tsInitial
	 * @param int $periodMonths
     * 
     * @return DateTime   Datetime of nex Cron Event.
	 */
    public static function initialCronEmailEvent($tsInitial,$periodMonths): DateTimeImmutable {
        $start = new DateTimeImmutable("@{$tsInitial}");
        if($tsInitial >= strtotime("today"))
            return $start;

        $end = new DateTime("today");
        $diff = $start->diff($end);
        
        $yearsInMonths = $diff->format('%r%y') * 12;
        $months = $diff->format('%r%m');
        $totalMonths = $yearsInMonths + $months;
        $numPeriods=floor($totalMonths / $periodMonths) + 1;        
        $fullMonth=$numPeriods * $periodMonths;

        $dtTo=$start->modify("midnight last day of next month +" . ($fullMonth - 1) . " month");
        
        $daydiff=$dtTo->format("j") - $start->format("j");
        if($daydiff > 0 ) {
          $dtNextCronEvent=$dtTo->modify("-" . $daydiff . " days");
        } 
        else 
           $dtNextCronEvent=$dtTo;
        
        return $dtNextCronEvent;
                
    }

 	/**
	 * CMB2 callback on field saved
	 *
     * @param bool              $updated Whether the metadata update action occurred.
     * @param string            $action  Action performed. Could be "repeatable", "updated", or "removed".
     * @param CMB2_Field object $field   This field object
 	 */
    public static function cronEmailCodesSaved( $updated, $action, $field): void {
        if(!$updated || empty($field->object_id())) return;
            
        switch($action) {
            case 'updated':
                if(empty(@$field->value()['cron-booking-codes-enabled']))
                    delete_post_meta($field->object_id(), self::NEXT_CRON_EMAIL);
                else
                {
                    $dtNextCron=self::initialCronEmailEvent(@$field->value()['cron-email-booking-code-start'], @$field->value()['cron-email-booking-code-nummonth']);
                    update_post_meta( $field->object_id(), self::NEXT_CRON_EMAIL, $dtNextCron->getTimestamp() ); 
                }
                break;

            case 'removed':
                delete_post_meta($field->object_id(), self::NEXT_CRON_EMAIL);
                break;

            default:
                break;

        }
    }
    
 	/**
	 * CMB2 sanitize field callback
	 *
     * @param  mixed      $value      The unsanitized value from the form.
     * @param  array      $field_args Array of field arguments.
     * @param  CMB2_Field $field      The field object
     *
     * @return array                  Sanitized value to be stored.
 	 */
    public static function sanitizeCronEmailCodes( $value, $field_args, $field ): array {

        $dt=DateTime::createFromFormat($field_args['date_format_start'], @$value['cron-email-booking-code-start']);
        if($dt) $dt->setTime(0,0); //normalize to midnight, otherwise always modified/updated state
        $toSave = array(
            'cron-booking-codes-enabled' => sanitize_text_field( @$value['cron-booking-codes-enabled'] ),
            'cron-email-booking-code-nummonth' => absint(@$value['cron-email-booking-code-nummonth']),
            'cron-email-booking-code-start' => $dt?$dt->getTimestamp():$field->args['default_start'],
        );
 
        return $toSave;
    }

 	/**
	 * CMB2 escape field callback
	 *
     * @param  mixed      $value      The unescaped value from the database.
     * @param  array      $field_args Array of field arguments.
     * @param  CMB2_Field $field      The field object
     *
     * @return array                  Escaped value to be displayed.
 	*/
    public static function escapeCronEmailCodes( $value, $field_args, $field ): array {

        $dateStr="";
        try {
            $dt=new DateTime("@" . @$value['cron-email-booking-code-start']);
            $dateStr=$dt->format($field_args['date_format_start']);
        } catch( \Exception $e) {
            $dateStr=date($field_args['date_format_start'],$field->args['default_start']);
        }

        return array(
            'cron-booking-codes-enabled' =>  @$value['cron-booking-codes-enabled'],
            'cron-email-booking-code-nummonth' => @$value['cron-email-booking-code-nummonth']?$value['cron-email-booking-code-nummonth']:$field->args['default_nummonth'],
            'cron-email-booking-code-start' => $dateStr,
        );
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
     public static function renderCronEmailFields($field, $escaped_value, $object_id, $object_type, $field_type): void {
        $value = wp_parse_args( $escaped_value, array(
            'cron-booking-codes-enabled' => '',
            'cron-email-booking-code-start' => $field->args['default_start'],
            'cron-email-booking-code-nummonth'      =>  $field->args['default_nummonth'],
        ) );

        $checked       = !empty($value['cron-booking-codes-enabled'])?'checked="checked"':'';
        
        $jsDateFormat=\CMB2_Utils::php_to_js_dateformat($field->args['date_format_start'] );
        wp_add_inline_script( 'jquery-ui-datepicker', "
            jQuery( function() {
                jQuery( '.cb-custom-dtp' ).datepicker({
                    dateFormat: '{$jsDateFormat}',
                    beforeShow: function(input, inst) {
                        jQuery('#ui-datepicker-div').addClass('cmb2-element');
                    }
                });
            } );        
        ", 'after');
        
        $tsNextCronEvent=get_post_meta( $field->object_id(), self::NEXT_CRON_EMAIL, true );
        if(!empty($tsNextCronEvent)) {
            $nextCronEventFmt=wp_date(get_option( 'date_format' ),$tsNextCronEvent);
        }
        else
            $nextCronEventFmt=$field->args['msg_email_not_planned'];

        echo <<< HTML
            <input type="checkbox" class="cmb2-option cmb2-list" name="{$field_type->_name( '[cron-booking-codes-enabled]' )}" 
                                        id="{$field_type->_id( '[cron-booking-codes-enabled]' )}" value="on" {$checked} >
            <label for="{$field_type->_id( '[cron-booking-codes-enabled]' )}">
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
     * @param  array      $field_args Array of field arguments.
     * @param  CMB2_Field $field      The field object
  	*/
    public static function renderDirectEmailRow( $field_args, $field) {

        $timeframeId=$field->object_id();
        $timeframe=new Timeframe($timeframeId);

        if(!$timeframe->hasBookingCodes())
            return false;

        $locationAdminIds=$timeframe->getLocation()->getAdmins();
        $admins=UserRepository::getCBManagersByIds($locationAdminIds);

        echo '<div class="cmb-row cmb2-id-email-booking-codes-info">
                <div class="cmb-th">
                    <label for="email-booking-codes-list">'. commonsbooking_sanitizeHTML( __('Send booking codes by email', 'commonsbooking')) .'</label>
                </div>

                <div class="cmb-td">';
        if (!empty($admins)) 
        {
            $adminEmails = [];
            foreach($admins as $admin) {
                $adminEmails[] = $admin->get('user_email');
            }
            $adminEmailsList = implode(', ', $adminEmails);
            
            echo '      <a id="email-booking-codes-list" 
                                href="'. esc_url(add_query_arg([  "action" => "emailcodes", "redir" => rawurlencode(add_query_arg([])) ]))  . '" >
                            <strong>'. commonsbooking_sanitizeHTML( __('Email booking codes of the entire timeframe', 'commonsbooking')) .'</strong>
                        </a>
                        <br>
                        '. commonsbooking_sanitizeHTML( __('<b>All codes for the entire timeframe</b> will be emailed to the CBManagers, given in bold below.', 'commonsbooking'));


            $from=strtotime("midnight first day of this month");
            $to=strtotime("midnight last day of this month");
            echo '          <br><a id="email-booking-codes-list-current" 
                                    href="'. esc_url(add_query_arg([  "action" => "emailcodes", "from" => $from, "to" =>$to, "redir" => rawurlencode(add_query_arg([])) ]))  . '" >
                            <strong>'. commonsbooking_sanitizeHTML( __('Email booking codes of current month', 'commonsbooking')) .'</strong>
                        </a><br>
                        '. commonsbooking_sanitizeHTML( __('The codes <b>of the current month</b> will be sent to all the CBManagers, given in bold below', 'commonsbooking'));
            
            $from=strtotime("midnight first day of next month");
            $to=strtotime("midnight last day of next month");

            echo '          <br><a id="email-booking-codes-list-next" 
                                    href="'. esc_url(add_query_arg([  "action" => "emailcodes", "from" => $from, "to" =>$to, "redir" => rawurlencode(add_query_arg([])) ]))  . '" >
                            <strong>'. commonsbooking_sanitizeHTML( __('Email booking codes of next month', 'commonsbooking')) .'</strong>
                        </a><br>
                        '. commonsbooking_sanitizeHTML( __('The codes <b>of the next month</b> will be sent to all the CBManagers, given in bold below.', 'commonsbooking')) .
                        '<br><br>
                        <div>'. commonsbooking_sanitizeHTML( __('Currently configured CBManagers: ', 'commonsbooking')) .'<b>' . $adminEmailsList . '</b></div>';

            $lastBookingEmail=get_post_meta( $timeframeId, \CommonsBooking\View\BookingCodes::LAST_CODES_EMAIL, true);
            if(!empty($lastBookingEmail)) {
                echo '<p  class="cmb2-metabox-description"><b>'
                        . commonsbooking_sanitizeHTML( __('Last booking codes email sent:', 'commonsbooking')) . ' </b>'
                        . wp_date(get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),$lastBookingEmail) . '</p>';
            }
        }
        else {
            echo commonsbooking_sanitizeHTML( __('Emails cannot send to location Managers. None configured, check Location -> Location Admin(s)', 'commonsbooking')) ; 
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
        
        $timeframe=new Timeframe($timeframeId);
		$bookingCodes = \CommonsBooking\Repository\BookingCodes::getCodes( $timeframeId,Wordpress::getUTCDateTime("today")->getTimestamp(),$timeframe->getEndDate() );
        
        if(count($bookingCodes) <= 0) return false;

        echo '
            <div class="cmb-row cmb2-id-booking-codes-info">
                <div class="cmb-th">
                    <label for="booking-codes-download">' . commonsbooking_sanitizeHTML( __( 'Download booking codes', 'commonsbooking' ) ) . '</label>
                </div>
                <div class="cmb-td">
                    <a id="booking-codes-download" href="' . esc_url(add_query_arg([  "action" => "csvexport",  ])) . '" target="_blank"><strong>Download booking codes</strong></a>
                    <p  class="cmb2-metabox-description">
                    ' . commonsbooking_sanitizeHTML( __( 'The file will be exported as tab delimited .txt file so you can choose wether you want to print it, open it in a separate application (like Word, Excel etc.)', 'commonsbooking' ) ) . '
                    </p>
                </div>
            </div>
            <div class="cmb-row cmb2-id-booking-codes-list">
            	<div class="cmb-th">
                    <label for="booking-codes-list">' . commonsbooking_sanitizeHTML( __( 'Booking codes list', 'commonsbooking' ) ) . '</label>
                </div>
	            <div class="cmb-td">';
        
        echo apply_filters('commonsbooking_emailcodes_rendertable',
                            self::renderBookingCodesTable($bookingCodes),$bookingCodes,'timeframe_form');

        echo '</div></div>';

        return true;
	}

	/**
	 * Renders HTML table of bookingCodes List.
	 *
	 * @param $bookingCodes array : CommonsBooking\Model\BookingCode
     * 
     * @return string HTML table
	 */
	public static function renderBookingCodesTable( $bookingCodes ) : string {
        $lines=[];
        foreach($bookingCodes as $bookingCode) {
            $lines[]='<tr><td>' . commonsbooking_sanitizeHTML(wp_date("D j. F Y", strtotime ($bookingCode->getDate() )) ) . 
                        '</td><td>' . commonsbooking_sanitizeHTML( $bookingCode->getCode() ) . '</td></tr>';
        }
    
        // if odd number of lines add empty row
        if(count($lines) % 2 != 0) {
            array_push($lines,"<tr><td>&nbsp;</td><td>&nbsp;</td></tr>");
        }
        $parts=array_chunk($lines,ceil(count($lines) / 2));

        return "<table  cellspacing='0' cellpadding='20' class='cmb2-codes-outer-table' ><tbody><tr><td><table class='cmb2-codes-column' cellspacing=\"0\" cellpadding=\"8\" border=\"1\"><tbody>" . 
                        implode("",$parts[0]) . 
                        "</tbody></table></td><td><table class='cmb2-codes-column'  cellspacing=\"0\" cellpadding=\"8\" border=\"1\"><tbody>" . 
                        implode("",$parts[1]) . 
                        "</tbody></table></td></tr></tbody></table>";

    }

	/**
	 * Renders CVS file (txt-format) with booking codes for download
	 * @param $timeframeId
	 */
	public static function renderCSV( $timeframeId = null ) {
		if ( $timeframeId == null ) {
			$timeframeId = intval( $_GET['post'] );
		}

        $timeframe=new Timeframe($timeframeId);
		$bookingCodes = \CommonsBooking\Repository\BookingCodes::getCodes( $timeframeId, Wordpress::getUTCDateTime("today")->getTimestamp(), $timeframe->getRawEndDate() );

		header( 'Content-Encoding: UTF-8' );
		header( 'Content-type: text/csv; charset=UTF-8' );
		header( "Content-Disposition: attachment; filename=buchungscode-" . commonsbooking_sanitizeHTML( $timeframeId ) . ".txt" );
		header( 'Content-Transfer-Encoding: binary' );
		header( "Pragma: no-cache" );
		header( "Expires: 0" );
		echo "\xEF\xBB\xBF"; // UTF-8 BOM

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
    public static function emailCodes($timeframeId = null, $tsFrom=null, $tsTo=null) { 

        if($timeframeId == null) {
            $timeframeId = sanitize_text_field(@$_GET['post']);
        }


        if(empty($timeframeId)) {
            wp_die(
                commonsbooking_sanitizeHTML( __( "Unable to retrieve booking codes", "commonsbooking" ) ),
                commonsbooking_sanitizeHTML( __( "Missing ID of timeframe post", "commonsbooking" ) ),
                [ 'back_link' => true ]
            );
        }

        if($tsFrom == null) {
            $tsFrom = absint(@$_GET['from']);
        }
        if($tsTo == null) {
            $tsTo = absint(@$_GET['to']);
        }


        add_action( 'commonsbooking_mail_sent',function($action,$result) use ($timeframeId){
            $redir=empty(@$_GET['redir'])?add_query_arg([ "post" => $timeframeId, "action" => "edit"],admin_url('post.php')):$_GET['redir'];
            
            if(is_wp_error($result))
            {
                set_transient(BookingCode::ERROR_TYPE,$result->get_error_message());
            }
            elseif($result === false)
            {
                set_transient(BookingCode::ERROR_TYPE,__( "Error sending booking codes", "commonsbooking" ));
            }
            else
            {
                // $redir=add_query_arg([ 'cmb2_bookingcodes_result' => 'success' ],$redir);
                $redir=explode('#',$redir)[0] . '#email-booking-codes-list';
            }
            
            wp_safe_redirect($redir);
            exit;

        }
        , 10, 2 );

        $booking_msg = new BookingCodesMessage($timeframeId, "codes",$tsFrom,$tsTo );
        $booking_msg->sendMessage();

        //this should never happen
        wp_die(
            commonsbooking_sanitizeHTML( __( "An unknown error occured", "commonsbooking" ) ),
            commonsbooking_sanitizeHTML( __( "Email booking codes", "commonsbooking" ) ),
            [ 'back_link' => true ]
        );

     }
}
