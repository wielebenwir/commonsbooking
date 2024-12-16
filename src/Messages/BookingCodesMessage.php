<?php

namespace CommonsBooking\Messages;

use CommonsBooking\Model\BookingCode;
use CommonsBooking\Model\MessageRecipient;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Repository\BookingCodes;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Service\iCalendar;
use CommonsBooking\CB\CB;
use CommonsBooking\Wordpress\CustomPostType\Location;
use DateTimeImmutable;

/**
 * A message that contains booking codes to be sent by mail to the location admins.
 * These booking codes can either be sent once or can also be sent periodically.
 * The automatic sending of booking codes is handled by a cron job.
 */
class BookingCodesMessage extends Message {

	protected $validActions = [ 'codes' ];
	protected $to;
	private ?int $tsFrom;
	private ?int $tsTo;
	private ?array $locationAdmins;

	/**
	 * @param int /post $postId     ID or Post of timeframe
	 * @param string    $action        Message action
	 * @param int|null  $tsFrom           Timestamp of first Booking Code
	 * @param int|null  $tsTo             Timestamp of last Booking Code
	 */
	public function __construct( $postId, string $action, int $tsFrom = null, int $tsTo = null ) {
		parent::__construct( $postId, $action );
		$this->tsFrom = $tsFrom;
		$this->tsTo   = $tsTo;
	}

	/**
	 * prepares Message and sends by E-mail
	 *
	 * @return bool    true if message was sent, false otherwise. If the message is not sent, an error is raised.
	 */
	public function sendMessage(): bool {
		$timeframeId = (int) $this->getPostId();
		$timeframe   = new Timeframe( $timeframeId );

		if ( ! $this->prepareReceivers( $timeframe ) ) {
			return $this->raiseError(
				__( 'Unable to send Emails. No location email(s) configured, check location', 'commonsbooking' )
			);
		}

		$bookingCodes = BookingCodes::getCodes( $timeframeId, $this->tsFrom, $this->tsTo );
		if ( empty( $bookingCodes ) ) {
			return $this->raiseError( __( 'Could not find booking codes for this timeframe/period', 'commonsbooking' ) );
		}
		/**
		 * TODO
		 *
		 * @since 2.9.0
		 */
		$bookingTable = apply_filters(
			'commonsbooking_emailcodes_rendertable',
			\CommonsBooking\View\BookingCodes::renderBookingCodesTable( $bookingCodes ),
			$bookingCodes,
			'email'
		);

		/**
		 * TODO
		 *
		 * @since 2.9.0
		 */
		$bAddIcal   = apply_filters(
			'commonsbooking_emailcodes_addical',
			Settings::getOption( 'commonsbooking_options_bookingcodes', 'mail-booking-' . $this->action . '-attach-ical' ),
			$timeframe
		);
		$attachment = $bAddIcal ? $this->getIcalAttachment( $bookingCodes ) : null;

		// Workaround: arbitrary object for template parser
		$codes            = new \WP_User( (object) array( 'ID' => -1 ) );
		$codes->codeTable = $bookingTable;

		$dispTo                 = wp_date( 'M-Y', strtotime( end( $bookingCodes )->getDate() ) );
		$dispFrom               = wp_date( 'M-Y', strtotime( reset( $bookingCodes )->getDate() ) );
		$codes->formatDateRange = ( $dispFrom == $dispTo ) ? $dispFrom : $dispFrom . ' - ' . $dispTo;

		// get templates from Admin Options
		$template_body    = Settings::getOption(
			'commonsbooking_options_bookingcodes',
			'mail-booking-' . $this->action . '-body'
		);
		$template_subject = Settings::getOption(
			'commonsbooking_options_bookingcodes',
			'mail-booking-' . $this->action . '-subject'
		);
		$template_bcc     = sanitize_email(
			Settings::getOption(
				'commonsbooking_options_bookingcodes',
				'mail-booking-' . $this->action . '-bcc'
			)
		);

		// Setup email: From
		$fromHeaders = sprintf(
			'From: %s <%s>',
			Settings::getOption( 'commonsbooking_options_templates', 'emailheaders_from-name' ),
			sanitize_email( Settings::getOption( 'commonsbooking_options_templates', 'emailheaders_from-email' ) )
		);

		$this->prepareMail(
			MessageRecipient::fromUser( $this->locationAdmins[0] ),
			$template_body,
			$template_subject,
			$fromHeaders,
			$template_bcc,
			[
				'codes' => $codes,
				'item' => $timeframe->getItem(),
				'location' => $timeframe->getLocation(),
			],
			$attachment
		);

		add_action( 'commonsbooking_mail_sent', array( $this, 'updateEmailSent' ), 5, 2 );

		if ( count( $this->locationAdmins ) > 1 ) {
			add_filter( 'commonsbooking_mail_to', array( $this, 'addMultiTo' ), 25 );
			$this->sendNotificationMail();
			remove_filter( 'commonsbooking_mail_to', array( $this, 'addMultiTo' ), 25 );
		} else {
			$this->sendNotificationMail();
		}

		remove_action( 'commonsbooking_mail_sent', array( $this, 'updateEmailSent' ), 5 );

		return true;
	}

	/**
	 * Updates the information about the last sent email.
	 * This is triggered through the commonsbooking_mail_sent action.
	 *
	 * @param $action
	 * @param $result
	 *
	 * @return void
	 */
	public function updateEmailSent( $action, $result ) {
		if ( $this->action != $action ) {
			return;
		}

		if ( $result === true ) {
			update_post_meta( (int) $this->getPostId(), \CommonsBooking\View\BookingCodes::LAST_CODES_EMAIL, time() );
		}
	}

	/**
	 * filter commonsbooking_mail_to for adding multiple to email addresses
	 *
	 * @return array
	 */
	public function addMultiTo(): array {
		$to = array();
		foreach ( $this->locationAdmins as $admin ) {
			$to[] = sprintf( '%s <%s>', $admin->user_nicename, $admin->user_email );
		}

		return $to;
	}

	/**
	 * builds e-mail receivers by creating dummy WP_User objects from location emails
	 *
	 * TODO: Replace dummy \WP_User objects with @see MessageRecipient
	 *
	 * @param Timeframe $timeframe
	 *
	 * @return bool
	 */
	protected function prepareReceivers( Timeframe $timeframe ): bool {
		$dummy_id        = -2;
		$location_emails = CB::get( Location::$postType, COMMONSBOOKING_METABOX_PREFIX . 'location_email', $timeframe->getLocation() ); /*  email addresses, comma-seperated  */
		if ( ! empty( $location_emails ) ) {
			foreach ( array_map( 'trim', explode( ',', $location_emails ) ) as $email ) {
				$dUser                = $this->locationAdmins[] = new \WP_User( (object) array( 'ID' => $dummy_id-- ) );
				$dUser->user_nicename = '';
				$dUser->user_email    = $email;
			}
		}

		return ! empty( $this->locationAdmins );
	}

	/**
	 * generates iCalendar attachment with all requested booking codes
	 *
	 * @param BookingCode[] $bookingCodes   List of BookingCode objects
	 *
	 * @return array
	 */
	protected function getIcalAttachment( array $bookingCodes ): array {
		$calendar = new iCalendar();

		foreach ( $bookingCodes as $bookingCode ) {
			/**
			 * Default title of booking codes ical event
			 *
			 * @param string default title
			 * @param BookingCode object
			 *
			 * @since 2.9.0
			 */
			$title = apply_filters(
				'commonsbooking_emailcodes_icalevent_title',
				$bookingCode->getItemName() . ' (' . $bookingCode->getCode() . ')',
				$bookingCode
			);

			/**
			 * Default description of booking codes ical event
			 *
			 * @param string default description
			 * @param BookingCode object
			 *
			 * @since 2.9.0
			 */
			$desc = apply_filters(
				'commonsbooking_emailcodes_icalevent_desc',
				sprintf( __( 'booking code for item "%1$s": %2$s', 'commonsbooking' ), $bookingCode->getItemName(), $bookingCode->getCode() ),
				$bookingCode
			);

			$calendar->addEvent( DateTimeImmutable::createFromFormat( 'Y-m-d', $bookingCode->getDate() ), $title, $desc );
		}

		$attachment = [
			'string' => $calendar->getCalendarData(), // String attachment data (required)
			'filename' => 'BookingCodes' . '.ics', // Name of the attachment (required)
			'encoding' => 'base64', // File encoding (defaults to 'base64')
			'type' => 'text/calendar', // File MIME type (if left unspecified, PHPMailer will try to work it out from the file name)
			'disposition' => 'attachment', // Disposition to use (defaults to 'attachment')
		];

		return $attachment;
	}

	/**
	 * raises mail_sent action with error info
	 *
	 * @param string $msg Error msg content
	 *
	 * @return bool false
	 */
	protected function raiseError( string $msg ): bool {
		do_action( 'commonsbooking_mail_sent', $this->getAction(), new \WP_Error( 'e-mail', $msg ) );
		return false;
	}
}
