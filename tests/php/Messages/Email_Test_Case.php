<?php

namespace CommonsBooking\Tests\Messages;

class Email_Test_Case extends \WP_UnitTestCase {

	/** @inheritdoc */
	public function setUp(): void {
		parent::setUp();
		$this->resetMailer();
	}
	/** @inheritdoc */
	public function tearDown(): void {
		parent::tearDown();
		$this->resetMailer();
	}

	/**
	 * Reset mailer
	 *
	 * @return bool
	 */
	protected function resetMailer(){
		return reset_phpmailer_instance();
	}
	/**
	 * Get mock mailer
	 *
	 * Wraps tests_retrieve_phpmailer_instance()
	 *
	 * @return MockPHPMailer
	 */
	protected function getMockMailer(){
		return tests_retrieve_phpmailer_instance();
	}

}