<?php

namespace CommonsBooking\Messages;

use CommonsBooking\Model\Booking;
use CommonsBooking\Model\Restriction;
use CommonsBooking\Settings\Settings;

class RestrictionMessages extends Messages {

	protected $user;

	protected $restriction;

	protected $action;

	protected $booking;

	protected $validActions = [
		Restriction::TYPE_REPAIR,
		Restriction::TYPE_HINT
	];

	/**
	 * @param $restriction Restriction
	 * @param $user \WP_User
	 * @param $booking Booking
	 * @param $action
	 */
	public function __construct( $restriction, $user, Booking $booking, $action ) {
		$this->restriction = $restriction;
		$this->user        = $user;
		$this->booking    = $booking;
		$this->action      = $action;
	}

	/**
	 * Sends mails related to restriction type and state.
	 */
	public function sendMessage() {
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
	 */
	protected function sendHintMail() {
		$body    = Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-hint-body' );
		$subject = Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-hint-subject' );

		$this->prepareRestrictionMail(
			$body,
			$subject
		);

		$this->SendNotificationMail();
	}

	/**
	 * Sends repair mail.
	 */
	protected function sendRepairMail() {
		$body    = Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-repair-body' );
		$subject = Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-repair-subject' );

		$this->prepareRestrictionMail(
			$body,
			$subject
		);

		$this->SendNotificationMail();
	}

	/**
	 * Sends mail, when restriction is canceled (not active).
	 */
	protected function sendRestrictionCancelationMail() {
		$body    = Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-restriction-cancelled-body' );
		$subject = Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-restriction-cancelled-subject' );

		$this->prepareRestrictionMail(
			$body,
			$subject
		);

		$this->SendNotificationMail();
	}

	/**
	 * Prepares mail for sending.
	 *
	 * @param $body
	 * @param $subject
	 */
	protected function prepareRestrictionMail( $body, $subject ) {
		$fromHeader = 'From: ' . Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-from-name' ) .
		              ' <' . Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-from-email' ) . '>';
		$restriction = $this->getRestriction();

		$this->prepareMail(
			$this->getUser(),
			$body,
			$subject,
			$fromHeader,
			null,
			[
				'restriction' => $restriction,
				'item'        => get_post( $restriction->getItemId() ),
				'location'    => get_post( $restriction->getLocationId() ),
				'booking'     => $this->getBooking()
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
	 * @return mixed
	 */
	public function getAction() {
		return $this->action;
	}

	/**
	 * @return array
	 */
	public function getValidActions(): array {
		return $this->validActions;
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