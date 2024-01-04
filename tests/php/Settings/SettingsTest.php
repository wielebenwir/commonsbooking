<?php

namespace CommonsBooking\Tests\Settings;

use CommonsBooking\Settings\Settings;
use CommonsBooking\Wordpress\Options\AdminOptions;
use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
{

    public function testGetOption()
    {
		AdminOptions::setOptionsDefaultValues();
		$emailHeaderExpected = get_option( 'admin_email' );
		$emailBodyActual = Settings::getOption( 'commonsbooking_options_templates', 'emailheaders_from-email' );
		$this->assertEquals($emailHeaderExpected, $emailBodyActual);
    }
}
