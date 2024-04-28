<?php

namespace CommonsBooking\Model;

use CommonsBooking\Messages\Message;
use WP_User;

/**
 * Contains the necessary properties to send a message to a recipient using wp_mail()
 * This class is implemented so that we do not have to rely on the WP_User class for recipients
 * Used by @see Message::prepareMail()
 */
class MessageRecipient {

	/**
	 * The email address of the recipient
	 * @var string
	 */
	private string $email;
	/**
	 * The human-readable name in the "To" field of the email
	 * @var string
	 */
	private string $niceName;

	/**
	 * Construct a new recipient from manually provided data
	 *
	 * @param string $email
	 * @param string $niceName
	 */
	public function __construct( string $email, string $niceName ) {
		$this->email    = $email;
		$this->niceName = $niceName;
	}

	/**
	 * Construct a new recipient from a WP_User object
	 *
	 * @param WP_User $user
	 *
	 * @return MessageRecipient
	 */
	public static function fromUser( WP_User $user ): MessageRecipient {
		return new self( $user->user_email, $user->user_nicename );
	}

	public function getEmail(): string {
		return $this->email;
	}

	public function getNiceName(): string {
		return $this->niceName;
	}
}