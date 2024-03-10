<?php

namespace CommonsBooking\Tests\Messages;

use CommonsBooking\Messages\RestrictionMessage;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Wordpress\CustomPostType\Restriction;

class RestrictionMessageTest extends Email_Test_Case {

	const RESTRICION_HINT_NAME = 'Test Restriction (Hint)';
	const RESTRICION_REPAIR_NAME = 'Test Restriction (Repair)';

	private $hintMessageText;
	private $hintMessage;
	private $hintId;

	private $repairMessageText;
	private $repairMessage;
	private $repairId;

	private $expectedHintSubject;
	private $expectedHintBody;
	private $expectedRepairSubject;
	private $expectedRepairBody;
	private $expectedCancelledSubject;
	private $expectedCancelledBody;

	public function testGetUser() {
		$this->assertEquals( $this->userId, $this->hintMessage->getUser()->ID );
    }

    public function testGetBooking() {
		$this->assertEquals( $this->bookingId, $this->hintMessage->getBooking()->ID );
    }

    public function testSendMessage() {
		$this->hintMessage->sendMessage();
		$mailer = $this->getMockMailer();
		$this->assertEmpty( $mailer->ErrorInfo );
		$this->assertEquals( self::FROM_MAIL, $mailer->From );
		$this->assertEquals( self::FROM_NAME, $mailer->FromName );
		$bcc = $mailer->getBccAddresses();
		$this->assertCount( 1, $bcc );
		$this->assertEquals( self::ITEM_BCC_ADDRESS, $bcc[0][0] );
	    $this->assertEquals( $this->expectedHintSubject, $mailer->Subject );
	    $this->assertEquals( $this->expectedHintBody, $mailer->Body );

		//reset the mock mailer
	    $this->resetMailer();

		//now cancel the restriction
	    update_post_meta( $this->hintId, \CommonsBooking\Model\Restriction::META_STATE, \CommonsBooking\Model\Restriction::STATE_SOLVED );
		$this->hintMessage = new RestrictionMessage(
			new \CommonsBooking\Model\Restriction( $this->hintId ),
			get_userdata( $this->userId ),
			new \CommonsBooking\Model\Booking( $this->bookingId ),
			\CommonsBooking\Model\Restriction::TYPE_HINT,
			true
		);
		$this->hintMessage->sendMessage();
		$mailer = $this->getMockMailer();
		$this->assertEmpty( $mailer->ErrorInfo );
		$this->assertEquals( self::FROM_MAIL, $mailer->From );
		$this->assertEquals( self::FROM_NAME, $mailer->FromName );
		$bcc = $mailer->getBccAddresses();
		$this->assertCount( 1, $bcc );
		$this->assertEquals( self::ITEM_BCC_ADDRESS, $bcc[0][0] );
	    $this->assertEquals( $this->expectedCancelledSubject, $mailer->Subject );
	    $this->assertEquals( $this->expectedCancelledBody, $mailer->Body );

		//reset the mock mailer
	    $this->resetMailer();

		//now test repair message
	    $this->repairMessage->sendMessage();
		$mailer = $this->getMockMailer();
		$this->assertEmpty( $mailer->ErrorInfo );
		$this->assertEquals( self::FROM_MAIL, $mailer->From );
		$this->assertEquals( self::FROM_NAME, $mailer->FromName );
		$bcc = $mailer->getBccAddresses();
		$this->assertCount( 1, $bcc );
		$this->assertEquals( self::ITEM_BCC_ADDRESS, $bcc[0][0] );
	    $this->assertEquals( $this->expectedRepairSubject, $mailer->Subject );
	    $this->assertEquals( $this->expectedRepairBody, $mailer->Body );
    }

    public function testGetRestriction() {
		$this->assertEquals( $this->hintId, $this->hintMessage->getRestriction()->ID );
    }

	public function setUp(): void {
		parent::setUp();
		$this->hintMessageText = "This is a warning message";

		$hintMeta = [
			\CommonsBooking\Model\Restriction::META_TYPE => \CommonsBooking\Model\Restriction::TYPE_HINT,
			\CommonsBooking\Model\Restriction::META_LOCATION_ID => $this->locationId,
			\CommonsBooking\Model\Restriction::META_ITEM_ID => $this->itemId,
			\CommonsBooking\Model\Restriction::META_HINT => $this->hintMessageText,
			\CommonsBooking\Model\Restriction::META_START => strtotime( 'now' ),
			\CommonsBooking\Model\Restriction::META_END => strtotime( '+1 week' ),
			\CommonsBooking\Model\Restriction::META_STATE => \CommonsBooking\Model\Restriction::STATE_ACTIVE
		];
		$this->hintId    = wp_insert_post( [
			'post_type'   => Restriction::$postType,
			'post_title'  => self::RESTRICION_HINT_NAME,
			'post_status' => 'publish',
			'meta_input'  => $hintMeta
		] );

		$this->hintMessage = new RestrictionMessage(
			new \CommonsBooking\Model\Restriction( $this->hintId ),
			get_userdata( $this->userId ),
			new \CommonsBooking\Model\Booking( $this->bookingId ),
			\CommonsBooking\Model\Restriction::TYPE_HINT,
			true
		);

		$repairMessage = "This is a repair message";
		$repairMeta = [
			\CommonsBooking\Model\Restriction::META_TYPE => \CommonsBooking\Model\Restriction::TYPE_REPAIR,
			\CommonsBooking\Model\Restriction::META_LOCATION_ID => $this->locationId,
			\CommonsBooking\Model\Restriction::META_ITEM_ID => $this->itemId,
			\CommonsBooking\Model\Restriction::META_HINT => $repairMessage,
			\CommonsBooking\Model\Restriction::META_START => strtotime( 'now' ),
			\CommonsBooking\Model\Restriction::META_END => strtotime( '+1 week' ),
			\CommonsBooking\Model\Restriction::META_STATE => \CommonsBooking\Model\Restriction::STATE_ACTIVE
		];
		$this->repairId    = wp_insert_post( [
			'post_type'   => Restriction::$postType,
			'post_title'  => self::RESTRICION_REPAIR_NAME,
			'post_status' => 'publish',
			'meta_input'  => $repairMeta
		] );

		$this->repairMessage = new RestrictionMessage(
			new \CommonsBooking\Model\Restriction( $this->repairId ),
			get_userdata( $this->userId ),
			new \CommonsBooking\Model\Booking( $this->bookingId ),
			\CommonsBooking\Model\Restriction::TYPE_REPAIR,
			true
		);

		//setup restriction from headers
		Settings::updateOption('commonsbooking_options_restrictions', 'restrictions-from-name', self::FROM_NAME);
		Settings::updateOption('commonsbooking_options_restrictions', 'restrictions-from-email', self::FROM_MAIL);

		//setup template settings
		$hintSubject = 'Type:Hint | {{restriction:hint}}';
		$hintBody = 'Type:Hint | {{booking:post_title}}';
		$this->expectedHintSubject = 'Type:Hint | ' . $this->hintMessageText;
		$this->expectedHintBody = 'Type:Hint | ' . self::BOOKING_NAME;
		Settings::updateOption('commonsbooking_options_restrictions', 'restrictions-hint-subject', $hintSubject);
		Settings::updateOption('commonsbooking_options_restrictions', 'restrictions-hint-body', $hintBody);

		$repairSubject = 'Type:Repair | {{restriction:hint}}';
		$repairBody = 'Type:Repair | {{booking:post_title}}';
		$this->expectedRepairSubject = 'Type:Repair | ' . $repairMessage;
		$this->expectedRepairBody = 'Type:Repair | ' . self::BOOKING_NAME;
		Settings::updateOption('commonsbooking_options_restrictions', 'restrictions-repair-subject', $repairSubject);
		Settings::updateOption('commonsbooking_options_restrictions', 'restrictions-repair-body', $repairBody);

		$cancelledSubject = 'Type:Cancel | {{restriction:hint}}';
		$cancelledBody = 'Type:Cancel | {{booking:post_title}}';
		$this->expectedCancelledSubject = 'Type:Cancel | ' . $this->hintMessageText;
		$this->expectedCancelledBody = 'Type:Cancel | ' . self::BOOKING_NAME;
		Settings::updateOption('commonsbooking_options_restrictions', 'restrictions-restriction-cancelled-subject', $cancelledSubject);
		Settings::updateOption('commonsbooking_options_restrictions', 'restrictions-restriction-cancelled-body', $cancelledBody);
	}

	public function tearDown(): void {
		parent::tearDown();
		wp_delete_post( $this->hintId, true );
		wp_delete_post( $this->repairId, true );
	}
}
