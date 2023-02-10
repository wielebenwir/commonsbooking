<?php

namespace CommonsBooking\Messages;

use CommonsBooking\Settings\Settings;
use \CommonsBooking\Repository\BookingCodes;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Plugin;


class BookingCodesMessage extends Message {

	protected $validActions = [ "codes" ];
    protected $to;
    private $tsFrom=null;
    private $tsTo=null;

	/**
	 * @param int /post $postId     ID or Post of timeframe
	 * @param string $action        Message action
     * @param int $tsFrom           Timestamp of first Booking Code
     * @param int $tsTo             Timestamp of last Booking Code
	 */
	public function __construct( $postId, $action, $tsFrom=null, $tsTo=null ) {
        parent::__construct($postId, $action);
        $this->tsFrom=$tsFrom;
        $this->tsTo=$tsTo;
    }

    /**
     * prepares Message and sends by E-mail
     * 
     * @return bool                  Successfully initiated sending.
     */
	public function sendMessage(): bool {
		/** @var \CommonsBooking\Repository\BookingCodes $bookingCodes */
        $postID=(int)$this->getPostId();
		$bookingCodes = BookingCodes::getCodes($postID);
        $timeframe=new Timeframe($postID);
        $item=$timeframe->getItem();
                  
        $itemAdmins = self::getItemAdminsByItem($item);// get_users( $args );
        if(empty($itemAdmins)){
            do_action( 'commonsbooking_mail_sent', $this->getAction(),
                        new \WP_Error(
                            "e-mail",
                            commonsbooking_sanitizeHTML( __( "Could not find CBManagers for this item. Check item -> Item Admin(s)", "commonsbooking" ) ),
                            [ 'title' => commonsbooking_sanitizeHTML( __( "Error sending booking codes", "commonsbooking" ) ) ]
                        )
                );

            return false;
        }

        $bookingTable=$this->getCodes($bookingCodes);
        if(empty($bookingTable['table'])) {
            do_action( 'commonsbooking_mail_sent', $this->getAction(),
                        new \WP_Error(
                            "e-mail",
                            commonsbooking_sanitizeHTML( __( "Could not find Booking codes for this timeframe/period", "commonsbooking" ) ),
                            [ 'title' => commonsbooking_sanitizeHTML( __( "Error sending booking codes", "commonsbooking" ) ) ]
                        )
            );
            
            return false;
        }

        //add Table content to post_meta of item for template rendering
        update_post_meta( $item->ID, '_codeTable', $bookingTable['table'] );
        
        $dispFrom= wp_date("M-Y",$bookingTable['startDate']);
        $dispTo= wp_date("M-Y",$bookingTable['endDate']);
        if($dispFrom == $dispTo)
            update_post_meta( $item->ID, '_dateRange', $dispFrom );
        else
            update_post_meta( $item->ID, '_dateRange', $dispFrom . " - " . $dispTo );

		// get templates from Admin Options
		$template_body    = Settings::getOption( 'commonsbooking_options_templates',
			'emailtemplates_mail-booking-' . $this->action . '-body' );
        $template_subject = Settings::getOption( 'commonsbooking_options_templates',
			'emailtemplates_mail-booking-' . $this->action . '-subject' );
        $template_bcc = sanitize_email(Settings::getOption( 'commonsbooking_options_templates',
			'email_mail-booking-' . $this->action . '-bcc' ));

		// Setup email: From
		$fromHeaders = sprintf(
			"From: %s <%s>",
			Settings::getOption( 'commonsbooking_options_templates', 'emailheaders_from-name' ),
			sanitize_email( Settings::getOption( 'commonsbooking_options_templates', 'emailheaders_from-email' ) )
		);

		$this->prepareMail(
			$itemAdmins[0],
			$template_body,
			$template_subject,
			$fromHeaders,
			$template_bcc,
			[
                'item' => $item,
                'location' => $timeframe->getLocation(),
            ]
		);
        
        //cleanup post_meta thats only used for template rendering
        delete_post_meta( $item->ID, '_codeTable');
        delete_post_meta( $item->ID, '_dateRange');

        //in the case of multiple receivers add real to: because base class does not allow multiple users
        if(count($itemAdmins) > 1)
        {
            $this->to=array();
            foreach($itemAdmins as $admin)
                $this->to[]=sprintf( '%s <%s>', $admin->user_nicename, $admin->user_email );
        }


		$this->SendNotificationMail();

        return true;
    }

 	/**
	 * renders Booking Codes Table
	 *
     * @param  array $bookingCodes    array of Booking Codes for this timeframe.
     *
     * @return array                  HTML of table and dates of first and last Code.
 	 */
      protected function getCodes($bookingCodes): array {
        
        $lines=[];
        $startDate=null;
        $endDate=null;
        foreach($bookingCodes as $bookingCode) {
            $ts=strtotime($bookingCode->getDate());
            if($this->tsFrom != null && $ts < $this->tsFrom) continue;
            if($this->tsTo != null && $ts > $this->tsTo) break;
            if($startDate == null) $startDate=$ts;
            $endDate=$ts;
            $fmtDate=wp_date("D j. F Y", $ts);
            $lines[]="<tr><td>{$fmtDate}</td><td>{$bookingCode->getCode()}</td></tr>";
        }

        // if odd number of lines add empty row
        if(count($lines) % 2 != 0) {
            array_push($lines,"<tr><td>&nbsp;</td><td>&nbsp;</td></tr>");
        }
        $parts=array_chunk($lines,ceil(count($lines) / 2));

        $table="<table  cellspacing='0' cellpadding='20' ><tbody><tr><td><table cellspacing=\"0\" cellpadding=\"10\" border=\"1\"><tbody>" . 
                        implode("",$parts[0]) . 
                        "</tbody></table></td><td><table  cellspacing=\"0\" cellpadding=\"10\" border=\"1\"><tbody>" . 
                        implode("",$parts[1]) . 
                        "</tbody></table></td></tr></tbody></table>";
        return [
            "startDate" => $startDate,
            "endDate" => $endDate,
            "table" => $table,
        ];


	}

 	/**
	 * get admins of timeframe item
	 *
     * @param  int $timeframeId  ID of Timeframe
     *
     * @return array             Item admins.
 	 */
      public static function getItemAdminsByTimeframeId($timeframeId): array {
        $timeframe=new Timeframe($timeframeId);
        $item=$timeframe->getItem();
                
        return self::getItemAdminsByItem($item);
    }

 	/**
	 * get admins of timeframe item
	 *
     * @param  CommonBookings\Model\Timeframe $item  Timeframe object
     *
     * @return array             Item admins.
 	 */
      public static function getItemAdminsByItem($item): array {
        $itemAdmins=$item->getPost()->_cb_item_admins;
        $args = [
            'include' => $itemAdmins,
            'role__in' => [ Plugin::$CB_MANAGER_ID ],
        ];
        
        return get_users( $args );
    }
}