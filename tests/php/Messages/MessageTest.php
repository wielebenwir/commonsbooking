<?php

namespace CommonsBooking\Tests\Messages;

use CommonsBooking\Messages\Message;
use CommonsBooking\Model\MessageRecipient;


class MessageTest extends Email_Test_Case {

	private $message;
	/**
	 * @var int|\WP_Error
	 */
	private $postID;

	const ACTION = 'testSend';
	const SUBJECT = 'Test Subject';
	const BODY = 'Test Body';

	const ATTACHMENT_FILENAME = '/tmp/test.txt';
	const ATTACHMENT_STRING_FILENAME = 'stringattachment.txt';
	const ATTACHMENT_STRING = [
		'string' => 'Test String',
		'filename' => self::ATTACHMENT_STRING_FILENAME,
		'encoding' => 'base64',
		'type' => 'text/plain',
		'disposition' => 'attachment'
	];

	public function testGetAction() {
		$this->assertEquals(self::ACTION, $this->message->getAction() );
	}

	public function testGetTo() {
		$this->assertEquals( self::BOOKINGUSER_NICENAME . ' <' . self::BOOKINGUSER_EMAIL . '>', $this->message->getTo());
		add_filter( 'commonsbooking_mail_to', function($to) {
			return 'Filtered To';
		});
		$this->assertEquals('Filtered To', $this->message->getTo());
	}

	public function testGetHeaders() {
		$this->assertIsArray($this->message->getHeaders());
		$this->assertContains('Content-Type: text/html', $this->message->getHeaders());
		$this->assertContains('MIME-Version: 1.0', $this->message->getHeaders());
		$this->assertContains(self::FROM_HEADER, $this->message->getHeaders());
		$this->assertContains('BCC:' . self::LOCATION_BCC_ADDRESS, $this->message->getHeaders());
	}

	public function testGetSubject() {
		$this->assertEquals(self::SUBJECT, $this->message->getSubject());
		add_filter('commonsbooking_mail_subject', function($subject) {
			return 'Filtered Subject';
		});
		$this->assertEquals('Filtered Subject', $this->message->getSubject());
	}

	public function testGetBody() {
		$this->assertEquals(self::BODY, $this->message->getBody());
		add_filter('commonsbooking_mail_body', function($body) {
			return 'Filtered Body';
		});
		$this->assertEquals('Filtered Body', $this->message->getBody());
	}

	public function testGetAttachment () {
		$this->assertEquals([ self::ATTACHMENT_FILENAME, self::ATTACHMENT_STRING ], $this->message->getAttachment());
		add_filter('commonsbooking_mail_attachment', function($attachment) {
			$attachment[] = 'Added Attachment';
			return $attachment;
		});
		$this->assertEquals([ self::ATTACHMENT_FILENAME, self::ATTACHMENT_STRING, 'Added Attachment' ], $this->message->getAttachment());
	}

    public function testTriggerMail() {
		//We create a different mock for this, because we just test the action validation in isolation
	    $message = $this->getMockBuilder(Message::class)
	                    ->onlyMethods(['getValidActions', 'sendMessage'])
	                    ->setConstructorArgs([$this->postID, 'testSend'])
	                    ->getMock();

	    $message->expects($this->once())
	            ->method('getValidActions')
	            ->willReturn(['testSend']);
	    $message->expects($this->once())
	            ->method('sendMessage');
	    $message->triggerMail();
    }

	public function testTriggerMail_InvalidAction() {
		//We create a different mock for this, because we just test the action validation in isolation
	    $message = $this->getMockBuilder(Message::class)
	                    ->onlyMethods(['getValidActions', 'sendMessage'])
	                    ->setConstructorArgs([$this->postID, 'invalidAction'])
	                    ->getMock();
		$message->expects($this->once())
	            ->method('getValidActions')
	            ->willReturn(['testSend']);
	    $message->expects($this->never())
	            ->method('sendMessage');
	    $message->triggerMail();
	}

    public function testGetPost() {
		$this->assertEquals(get_post($this->postID), $this->message->getPost());
    }

    public function testSendNotificationMail() {
		$this->message->sendNotificationMail();
	    /** @var \PHPMailer\PHPMailer\PHPMailer $mailer */
	    $mailer = $this->getMockMailer();
	    $this->assertEmpty($mailer->ErrorInfo);
		$this->assertEquals(self::FROM_MAIL, $mailer->From);
		$this->assertEquals(self::FROM_NAME, $mailer->FromName);

		$to = $mailer->getToAddresses();
		$this->assertCount(1, $to);
		$to = $to[0];
		$this->assertEquals(self::BOOKINGUSER_EMAIL, $to[0]);
		$this->assertEquals(self::BOOKINGUSER_NICENAME, $to[1]);

	    $bcc = $mailer->getBccAddresses();
	    $this->assertCount(1, $bcc);
	    $bcc = $bcc[0];
	    $this->assertEquals(self::LOCATION_BCC_ADDRESS, $bcc[0]);


	    $this->assertEquals(self::SUBJECT, $mailer->Subject);
		$this->assertEquals(self::BODY, $mailer->Body);

		$attachment = $mailer->getAttachments();
		$this->assertCount(2, $attachment);
		$this->assertEquals(self::ATTACHMENT_FILENAME, $attachment[0][0]);
		$this->assertEquals(self::ATTACHMENT_STRING_FILENAME, $attachment[1][1]);
    }

    public function testGetPostId()
    {
		$this->assertEquals($this->postID, $this->message->getPostId());
    }

	public function testAddStringAttachments() {
		//This only tests the function standalone and not the integration with WordPress
		$atts = [ 'attachments' => [ self::ATTACHMENT_STRING ] ];
		$this->message->addStringAttachments($atts);
		global $wp_mail_attachments;

		$this->assertIsArray($wp_mail_attachments);
		$this->assertCount(1, $wp_mail_attachments);
		$this->assertEquals(self::ATTACHMENT_STRING, $wp_mail_attachments[0]);
	}

	/**
	 * Tests for https://github.com/wielebenwir/commonsbooking/issues/1433
	 * This concerns all FROM fields that contain a special character and subject lines
	 * @return void
	 */
	public function test_1433() {
		$specialChar = '&';
		$fromName = 'Test ' . $specialChar . ' Name';
		$fromMail = self::FROM_MAIL;
		$fromHeader = 'From: ' . $fromName . ' <' . $fromMail . '>';
		$subject = 'Test ' . $specialChar . ' Subject';

		$this->message = $this->getMockBuilder(Message::class)
		                      ->onlyMethods(['sendMessage'])
		                      ->setConstructorArgs([$this->postID, self::ACTION])
		                      ->getMock();
		$prepareMail   = $this->getReflectionMethod();
		//TODO: Mock MessageRecipient
		$prepareMail->invokeArgs( $this->message, [
			MessageRecipient::fromUser( get_userdata( $this->userId ) ),
			self::BODY,
			$subject,
			$fromHeader,
		] );
		$this->message->sendNotificationMail();
		/** @var \PHPMailer\PHPMailer\PHPMailer $mailer */
		$mailer = $this->getMockMailer();
		$this->assertEquals($fromMail, $mailer->From);
		$this->assertEquals($fromName, $mailer->FromName);
		$this->assertEquals($subject, $mailer->Subject);
	}

	public function setUp(): void {
		parent::setUp();

		//Create the attachment file
		file_put_contents(self::ATTACHMENT_FILENAME, 'Test Content');

		$this->postID = wp_insert_post([
			'post_title' => 'Test Post',
			'post_content' => 'Test Content',
			'post_status' => 'publish',
			'post_type' => 'post'
		]);

		$this->message = $this->getMockBuilder(Message::class)
		                      ->onlyMethods(['sendMessage'])
		                      ->setConstructorArgs([$this->postID, self::ACTION])
		                      ->getMock();
		$prepareMail   = $this->getReflectionMethod();
		//TODO: Mock MessageRecipient
		$prepareMail->invokeArgs( $this->message, [
			MessageRecipient::fromUser( get_userdata( $this->userId ) ),
			self::BODY,
			self::SUBJECT,
			self::FROM_HEADER,
			self::LOCATION_BCC_ADDRESS,
			[],
			[ self::ATTACHMENT_FILENAME, self::ATTACHMENT_STRING ]
		] );
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * @return \ReflectionMethod
	 * @throws \ReflectionException
	 */
	private function getReflectionMethod(): \ReflectionMethod {
		$reflection  = new \ReflectionClass( $this->message );
		$prepareMail = $reflection->getMethod( 'prepareMail' );
		$prepareMail->setAccessible(true);
		return $prepareMail;
	}
}
