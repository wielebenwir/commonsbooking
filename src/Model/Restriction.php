<?php


namespace CommonsBooking\Model;


use CommonsBooking\Settings\Settings;

class Restriction extends CustomPost {

	const TYPE_REPAIR = 'repair';

	const TYPE_HINT = 'hint';

	const META_HINT = 'restriction-hint';

	const META_START = 'restriction-start';

	const META_END = 'restriction-end';

	const META_TYPE = 'restriction-type';

	const META_ACTIVE = 'restriction-active';

	const META_LOCATION_ID = 'restriction-location-id';

	const META_ITEM_ID = 'restriction-item-id';

	const META_SENT = 'restriction-sent';

	protected $active = false;

	/**
	 * Returns start-time \DateTime.
	 *
	 * @param $timeframe
	 *
	 * @return \DateTime
	 */
	public function getStartTimeDateTime(): \DateTime {
		$startDateString = $this->getMeta( self::META_START );
		$startDate       = new \DateTime();
		$startDate->setTimestamp( $startDateString );
		return $startDate;
	}

	/**
	 * Returns end-date \DateTime.
	 *
	 * @return \DateTime
	 */
	public function getEndDateDateTime(): \DateTime {
		$endDateString = intval( $this->getMeta( self::META_END ) );
		$endDate       = new \DateTime();
		$endDate->setTimestamp( $endDateString );

		return $endDate;
	}

	/**
	 * Returns start-time \DateTime.
	 *
	 * @param null $endDateString
	 *
	 * @return \DateTime
	 */
	public function getEndTimeDateTime( $endDateString = null ): \DateTime {
		$endTimeString = $this->getMeta( self::META_END );
		$endDate       = new \DateTime();

		if ( $endTimeString ) {
			$endTime = new \DateTime();
			$endTime->setTimestamp( $endTimeString );
			$endDate->setTime( $endTime->format( 'H' ), $endTime->format( 'i' ) );
		} else {
			$endDate->setTimestamp( $endDateString );
		}

		return $endDate;
	}

	/**
	 * @return int Timestamp
	 */
	public function getStartDate(): int {
		return intval( $this->getMeta( self::META_START ) );
	}

	/**
	 * @return int Timestamp
	 */
	public function getEndDate(): int {
		return intval( $this->getMeta( self::META_END ) );
	}

	public function isOverBookable(): bool {
		return ! $this->isActive();
	}

	public function isLocked(): bool {
		return $this->isActive();
	}

	public function getType() {
		return $this->getMeta( self::META_TYPE );
	}

	public function getHint() {
		return $this->getMeta( self::META_HINT );
	}

	/**
	 * @return bool
	 */
	public function isActive(): bool {
		if ( $this->active == null ) {
			$this->active = $this->getMeta( self::META_ACTIVE );
		}

		return $this->active;
	}

	public function getLocationId() {
		return self::getMeta( self::META_LOCATION_ID );
	}

	public function getItemId() {
		return self::getMeta( self::META_ITEM_ID );
	}

	protected function getItemName(): string {
		$itemName = "";
		if ( $this->getItemId() ) {
			$item     = get_post( $this->getItemId() );
			$itemName = $item->post_title;
		}

		return $itemName;
	}

	protected function getLocationName(): string {
		$locationName = "";
		if ( $this->getLocationId() ) {
			$location     = get_post( $this->getLocationId() );
			$locationName = $location->post_title;
		}

		return $locationName;
	}

	protected function getRestrictionMailData(): array {
		return [
			'headers'     => [
				'From: ' . Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-from-name' ) .
				' <' . Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-from-email' ) . '>'
			],
			'restriction' => [
				'itemName'         => $this->getItemName(),
				'locationName'     => $this->getLocationName(),
				'restrictionStart' => $this->getStartTimeDateTime()->format( 'd.m.Y h:i' ),
				'restrictionEnd'   => $this->getEndDateDateTime()->format( 'd.m.Y h:i' ),
				'hint'             => $this->getHint()
			]
		];
	}

	/**
	 * Mails regarding booked timeslots
	 *
	 * @param $bookings
	 * @param $mailData
	 */
	protected function sendBookingRestrictionMails( $bookings, $mailData ) {
		foreach ( $bookings as $booking ) {
			$mailData['user'] = $booking->getUserData();
			$this->sendMail( $mailData );

			// Cancel booking
			if ( $this->isActive() && $this->getType() == self::TYPE_REPAIR ) {
				$booking->cancel();
			}
		}
	}

	/**
	 * Mails regarding item/location admins
	 */
	protected function sendAdminRestrictionMails( $bookings, $mailData ) {
		$adminIds = [];
		foreach ( $bookings as $booking ) {
			$adminIds = array_merge( $adminIds, $booking->getAdmins() );
		}
		$adminIds = array_unique( $adminIds );

		foreach ( $adminIds as $adminId ) {
			$mailData['user'] = get_userdata( $adminId );
			$this->sendMail( $mailData );
		}
	}

	/**
	 * Apply restriction workflow.
	 */
	public function apply() {
		$bookings = \CommonsBooking\Repository\Booking::getByRestriction( $this );

		$mailData = $this->getRestrictionMailData();

		$this->sendBookingRestrictionMails( $bookings, $mailData );
		$this->sendAdminRestrictionMails( $bookings, $mailData );
	}

	/**
	 * Sends mail in relation to restriction type.
	 *
	 * @param $mailData
	 */
	protected function sendMail( $mailData ) {
		$this->prepareUserData( $mailData );

		if ( $this->isActive() ) {
			if ( $this->getType() == self::TYPE_HINT ) {
				// send hint mail
				$this->sendHintMail( $mailData );
			} else {
				// Send repair mail
				$this->sendRepairMail( $mailData );
			}
		} else {
			// send restriction cancellation
			$this->sendRestrictionCancelationMail( $mailData );
		}
	}

	protected function prepareUserData( &$mailData ) {
		$user             = $mailData['user'];
		$userData         = [
			'to'       => $user->get( 'user_email' ),
			'username' => $user->get( 'user_nicename' )
		];
		$mailData['user'] = $userData;
	}

	protected function sendHintMail( $mailData ) {
		$body    = Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-hint-body' );
		$this->replaceTemplatePlaceholder($body, $mailData);
		$subject = Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-hint-subject' );

		wp_mail(
			$mailData['user']['to'],
			$subject,
			$body,
			$mailData['headers']
		);
	}

	protected function sendRepairMail( $mailData ) {
		$body    = Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-repair-body' );
		$this->replaceTemplatePlaceholder($body, $mailData);
		$subject = Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-repair-subject' );

		wp_mail(
			$mailData['user']['to'],
			$subject,
			$body,
			$mailData['headers']
		);
	}

	protected function sendRestrictionCancelationMail( $mailData ) {
		$body    = Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-restriction-cancelled-body' );
		$this->replaceTemplatePlaceholder($body, $mailData);
		$subject = Settings::getOption( 'commonsbooking_options_restrictions', 'restrictions-restriction-cancelled-subject' );

		wp_mail(
			$mailData['user']['to'],
			$subject,
			$body,
			$mailData['headers']
		);
	}

	protected function replaceTemplatePlaceholder( &$template, $mailData ) {
		$mailDataKeys = ['restriction', 'user'];
		foreach ($mailDataKeys as $mailDataKey) {
			foreach ( $mailData[$mailDataKey] as $key => $value ) {
				$template = str_replace( '{{' . $key . '}}', $value, $template );
			}
		}
	}

}