<?php

namespace CommonsBooking\Messages;

use CommonsBooking\CB\CB;
use CommonsBooking\Model\Booking;
use CommonsBooking\Model\MessageRecipient;
use CommonsBooking\Model\Restriction;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Wordpress\CustomPostType\Item;

/**
 * This message is sent out to a user to inform them that their booking is influenced by a restriction.
 */
class RestrictionMessage extends Message {

	/**
	 * @var \WP_User
	 */
	protected $user;

	/**
	 * @var Restriction
	 */
	protected Restriction $restriction;

	protected string $action;

	/**
	 * @var Booking
	 */
	protected Booking $booking;

	protected bool $firstMessage;

	/**
	 * @var string[]
	 */
	protected array $validActions = [
		Restriction::TYPE_REPAIR,
		Restriction::TYPE_HINT,
	];

	/**
	 * @param Restriction $restriction
	 * @param \WP_User    $user
	 * @param Booking     $booking
	 * @param string      $action
	 * @param bool        $firstMessage
	 */
	public function __construct( Restriction $restriction, \WP_User $user, Booking $booking, string $action, bool $firstMessage = false ) {
		$this->restriction  = $restriction;
		$this->user         = $user;
		$this->booking      = $booking;
		$this->action       = $action;
		$this->firstMessage = $firstMessage;
	}

	/**
	 * Sends mails related to restriction type and state.
	 *
	 * @return bool
	 */
	public function sendMessage(): bool {
		if ( $this->getRestriction()->isActive() ) {
			if ( $this->getRestriction()->getType() == Restriction::TYPE_HINT ) {
				// send hint mail
				return $this->sendHintMail();
			} else {
				// Send repair mail
				return $this->sendRepairMail();
			}
		} elseif ( $this->getRestriction()->isCancelled() ) {
			// send restriction cancellation
			return $this->sendRestrictionCancelationMail();
		} else {
			return false;
		}
	}

	/**
	 * Sends hint mail.
	 *
	 * @return bool
	 */
	protected function sendHintMail(): bool {
		$body    = Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-hint-body' );
		$subject = Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-hint-subject', 'sanitize_text_field' );

		try {
			$this->prepareRestrictionMail(
				$body,
				$subject
			);
		} catch ( \Exception $e ) {
			commonsbooking_write_log(
				sprintf(
					'Error preparing hint mail for restriction message: %s',
					$e->getMessage()
				)
			);
			return false;
		}

		$this->sendNotificationMail();
		return true;
	}

	/**
	 * Sends repair mail.
	 *
	 * @return bool
	 */
	protected function sendRepairMail(): bool {
		$body    = Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-repair-body' );
		$subject = Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-repair-subject', 'sanitize_text_field' );

		try {
			$this->prepareRestrictionMail(
				$body,
				$subject
			);
		} catch ( \Exception $e ) {
			commonsbooking_write_log(
				sprintf(
					'Error preparing repair mail for restriction message: %s',
					$e->getMessage()
				)
			);
			return false;
		}

		$this->sendNotificationMail();
		return true;
	}

	/**
	 * Sends mail, when restriction is canceled (not active).
	 *
	 * @return bool
	 */
	protected function sendRestrictionCancelationMail(): bool {
		$body    = Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-restriction-cancelled-body' );
		$subject = Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-restriction-cancelled-subject', 'sanitize_text_field' );

		try {
			$this->prepareRestrictionMail(
				$body,
				$subject
			);
		} catch ( \Exception $e ) {
			commonsbooking_write_log(
				sprintf(
					'Error preparing restriction cancellation mail for restriction message: %s',
					$e->getMessage()
				)
			);
			return false;
		}

		$this->sendNotificationMail();
		return true;
	}

	/**
	 * Prepares mail for sending.
	 *
	 * @param string $body
	 * @param string $subject
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected function prepareRestrictionMail( string $body, string $subject ): void {
		$fromHeader  = 'From: ' . Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-from-name', 'sanitize_text_field' ) .
						' <' . Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-from-email' ) . '>';
		$restriction = $this->getRestriction();

		$bcc_addresses = '';
		if ( $this->firstMessage ) { // Notify the maintainer about the damage by putting them in the BCC for the first notice. Avoids the maintainer getting flooded with restriction messages.
			$item_maintainer_email = CB::get( Item::$postType, COMMONSBOOKING_METABOX_PREFIX . 'item_maintainer_email', $this->booking->getItem() ); /*  email addresses, comma-seperated  */
			$bcc_addresses         = str_replace( ' ', '', $item_maintainer_email );
		}

		$this->prepareMail(
			MessageRecipient::fromUser( $this->getUser() ),
			$body,
			$subject,
			$fromHeader,
			$bcc_addresses,
			[
				'restriction' => $restriction,
				'item'        => $this->booking->getItem(),
				'location'    => $this->booking->getLocation(),
				'booking'     => $this->getBooking(),
				'user'        => $this->getUser(),
			]
		);
	}

	/**
	 * @return \WP_User
	 */
	public function getUser(): \WP_User {
		return $this->user;
	}

	/**
	 * @return Restriction
	 */
	public function getRestriction(): Restriction {
		return $this->restriction;
	}

	/**
	 * @return Booking
	 */
	public function getBooking(): Booking {
		return $this->booking;
	}
}
