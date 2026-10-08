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

	protected $user;

	protected $restriction;

	protected $action;

	protected $booking;

	protected bool $firstMessage;

	protected $validActions = [
		Restriction::TYPE_REPAIR,
		Restriction::TYPE_HINT,
	];

	/**
	 * @param $restriction Restriction
	 * @param $user \WP_User
	 * @param $booking Booking
	 * @param $action
	 */
	public function __construct( $restriction, $user, Booking $booking, $action, bool $firstMessage = false ) {
		$this->restriction  = $restriction;
		$this->user         = $user;
		$this->booking      = $booking;
		$this->action       = $action;
		$this->firstMessage = $firstMessage;
	}

	/**
	 * Sends mails related to restriction type and state.
	 *
	 * @return void
	 */
	public function sendMessage(): void {
		if ( $this->getRestriction()->isActive() ) {
			if ( $this->getRestriction()->getType() == Restriction::TYPE_HINT ) {
				// send hint mail
				$this->sendHintMail();
			} else {
				// Send repair mail
				$this->sendRepairMail();
			}
		}

		if ( $this->getRestriction()->isCancelled() ) {
			// send restriction cancellation
			$this->sendRestrictionCancelationMail();
		}
	}

	/**
	 * Sends hint mail.
	 *
	 * @return void
	 */
	protected function sendHintMail(): void {
		$body    = Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-hint-body' );
		$subject = Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-hint-subject', 'sanitize_text_field' );

		$this->prepareRestrictionMail(
			$body,
			$subject
		);

		$this->sendNotificationMail();
	}

	/**
	 * Sends repair mail.
	 *
	 * @return void
	 */
	protected function sendRepairMail(): void {
		$body    = Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-repair-body' );
		$subject = Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-repair-subject', 'sanitize_text_field' );

		$this->prepareRestrictionMail(
			$body,
			$subject
		);

		$this->sendNotificationMail();
	}

	/**
	 * Sends mail, when restriction is canceled (not active).
	 *
	 * @return void
	 */
	protected function sendRestrictionCancelationMail(): void {
		$body    = Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-restriction-cancelled-body' );
		$subject = Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-restriction-cancelled-subject', 'sanitize_text_field' );

		$this->prepareRestrictionMail(
			$body,
			$subject
		);

		$this->sendNotificationMail();
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
	 * @return mixed
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * @return Restriction
	 */
	public function getRestriction() {
		return $this->restriction;
	}

	/**
	 * @return Booking
	 */
	public function getBooking(): Booking {
		return $this->booking;
	}
}
