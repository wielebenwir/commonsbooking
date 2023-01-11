<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Service\BookingRule;

class BookingRuleTest extends CustomPostTypeTest
{
	private $timeframeOne;

	private $testItem;

	private $testLocation;

	protected $testBooking;
	protected BookingRule $alwaysdeny;
	protected BookingRule $alwaysallow;

	protected int $normalUserID = 10;


    public function test__construct()
    {
		self::assertNotNull(new BookingRule(
				"testRule",
				"test",
				"Testing rule creation",
				"Error message",
				function (\CommonsBooking\Model\Booking $booking, array $params){
					return true;
				},
				array(
					"First param description",
					"Second param description"
				)
			)
		);
    }

	protected function setUp() {
		parent::setUp();
		$userdata = array(
			'ID' 					=> 10, 	//(int) User ID. If supplied, the user will be updated.
			'user_pass'				=> 'normal', 	//(string) The plain-text user password.
			'user_login' 			=> 'normal', 	//(string) The user's login username.
			'user_nicename' 		=> 'normal', 	//(string) The URL-friendly user name.
			'user_url' 				=> '', 	//(string) The user URL.
			'user_email' 			=> 'a@a.de', 	//(string) The user email address.
			'display_name' 			=> 'normal', 	//(string) The user's display name. Default is the user's username.
			'nickname' 				=> 'normal', 	//(string) The user's nickname. Default is the user's username.
			'first_name' 			=> '', 	//(string) The user's first name. For new users, will be used to build the first part of the user's display name if $display_name is not specified.
			'last_name' 			=> '', 	//(string) The user's last name. For new users, will be used to build the second part of the user's display name if $display_name is not specified.
			'description' 			=> '', 	//(string) The user's biographical description.
			'rich_editing' 			=> '', 	//(string|bool) Whether to enable the rich-editor for the user. False if not empty.
			'syntax_highlighting' 	=> '', 	//(string|bool) Whether to enable the rich code editor for the user. False if not empty.
			'comment_shortcuts' 	=> '', 	//(string|bool) Whether to enable comment moderation keyboard shortcuts for the user. Default false.
			'admin_color' 			=> 'fresh', 	//(string) Admin color scheme for the user. Default 'fresh'.
			'use_ssl' 				=> '', 	//(bool) Whether the user should always access the admin over https. Default false.
			'user_registered' 		=> '', 	//(string) Date the user registered. Format is 'Y-m-d H:i:s'.
			'show_admin_bar_front' 	=> '', 	//(string|bool) Whether to display the Admin Bar for the user on the site's front end. Default true.
			'role' 					=> '', 	//(string) User's role.
			'locale' 				=> '', 	//(string) User's locale. Default empty.

		);
		wp_insert_user($userdata);
		$this->timeframeOne = parent::createConfirmedBookingEndingToday();
		$this->timeframeTwo = parent::createConfirmedBookingStartingToday();
		$this->testItem     = parent::createItem( 'testitem', 'publish' );
		$this->testLocation = parent::createLocation( 'testlocation', 'publish' );

		$this->testBooking = $this->createBooking(
			$this->testLocation,
			$this->testItem,
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
			strtotime( '+2 days', strtotime( self::CURRENT_DATE ) ),
			'8:00 AM',
			'12:00 PM',
			'unconfirmed',
			$this->normalUserID
		);
		$this->alwaysallow = new BookingRule(
			"alwaysAllow",
			"Always allow",
			"Rule will always evaluate to true",
			"Rule did not evaluate to true",
			function(\CommonsBooking\Model\Booking $booking){
				return true;
			}
		);
		$this->alwaysdeny = new BookingRule(
			"alwaysDeny",
			"Always deny",
			"Rule will always evaluate to false",
			"Rule evaluated correctly",
			function(\CommonsBooking\Model\Booking $booking){
				return false;
			}
		);

	}

	protected function tearDown() {
		parent::tearDown();
	}
}
