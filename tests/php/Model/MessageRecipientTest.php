<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Model\MessageRecipient;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class MessageRecipientTest extends CustomPostTypeTest {

	private \WP_User $subscriber;
	private MessageRecipient $manualRecipient;

	public function testGetEmail() {
		$this->assertEquals( 'testmail@example.com', $this->manualRecipient->getEmail() );
	}

	public function testGetNiceName() {
		$this->assertEquals( 'Test User', $this->manualRecipient->getNiceName() );
	}

	public function testFromUser() {
		$recipient = MessageRecipient::fromUser( $this->subscriber );
		$this->assertEquals( 'a@a.de', $recipient->getEmail() );
		$this->assertEquals( 'normaluser', $recipient->getNiceName() );
	}

	protected function setUp(): void {
		parent::setUp();
		$this->manualRecipient = new MessageRecipient( 'testmail@example.com', 'Test User' );

		$this->createSubscriber();
		$this->subscriber = get_userdata( $this->subscriberID );
	}
}
