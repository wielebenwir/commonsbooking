<?php

namespace CommonsBooking\Tests\Settings;

use CommonsBooking\Settings\Settings;
use CommonsBooking\Wordpress\Options\AdminOptions;
use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase {


	public function testGetOption() {
		AdminOptions::setOptionsDefaultValues();
		$testMail = 'admin@example.org';
		Settings::updateOption( 'commonsbooking_options_templates', 'emailheaders_from-email', $testMail );
		$emailBodyActual = Settings::getOption( 'commonsbooking_options_templates', 'emailheaders_from-email' );
		$this->assertEquals( $testMail, $emailBodyActual );
	}

	public function testGetDefaultOption() {
		$this->assertEquals( get_option( 'admin_email' ), Settings::getDefaultOption( 'commonsbooking_options_templates', 'emailheaders_from-email' ) );
	}
}
