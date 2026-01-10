<?php

namespace CommonsBooking\Tests\Messages;

use CommonsBooking\Settings\Settings;
use CommonsBooking\Wordpress\CustomPostType\Booking;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;

abstract class Email_Test_Case extends \WP_UnitTestCase {

	const FROM_MAIL   = 'test@example.com';
	const FROM_NAME   = 'siteAdmin';
	const FROM_HEADER = 'From: ' . self::FROM_NAME . ' <' . self::FROM_MAIL . '>';

	const BOOKINGUSER_USERNAME = 'testuser';
	const BOOKINGUSER_NICENAME = 'testuser';
	const BOOKINGUSER_EMAIL    = 'user@example.com';

	const LOCATION_BCC_ADDRESS = 'location-bcc@example.com';
	const ITEM_BCC_ADDRESS     = 'item-bcc@example.com';

	const ITEM_NAME             = 'Test Item';
	const LOCATION_NAME         = 'Test Location';
	const LOCATION_EMAIL        = 'location@example.com';
	const SECOND_LOCATION_EMAIL = 'locationalias@example.com';
	const TIMEFRAME_NAME        = 'Test Timeframe';
	const BOOKING_NAME          = 'Test Booking';

	protected $itemId;
	protected $locationId;
	protected $timeframeId;
	protected $bookingId;
	protected $userId;

	/** @inheritdoc */
	public function setUp(): void {
		parent::setUp();

		$this->userId = wp_insert_user(
			[
				'user_login' => self::BOOKINGUSER_USERNAME,
				'user_nicename' => self::BOOKINGUSER_NICENAME,
				'user_email' => self::BOOKINGUSER_EMAIL,
				'user_pass' => 'testPassword',
			]
		);

		// TODO: Refactor \CommonsBooking\Tests\Wordpress\CustomPostTypeTest::class as a trait so we can use it here
		$this->itemId     = wp_insert_post(
			[
				'post_type'   => Item::$postType,
				'post_title'  => self::ITEM_NAME,
				'post_status' => 'publish',
				'meta_input'  => [
					COMMONSBOOKING_METABOX_PREFIX . 'item_maintainer_email' => self::ITEM_BCC_ADDRESS,
				],
			]
		);
		$this->locationId = wp_insert_post(
			[
				'post_type'   => Location::$postType,
				'post_title'  => self::LOCATION_NAME,
				'post_status' => 'publish',
				'meta_input'  => [
					COMMONSBOOKING_METABOX_PREFIX . 'location_email' => self::LOCATION_EMAIL . ', ' . self::SECOND_LOCATION_EMAIL,
					COMMONSBOOKING_METABOX_PREFIX . 'location_email_bcc' => 'on',
				],
			]
		);

		$timeframeMeta     = [
			'location-id' => $this->locationId,
			'item-id'     => $this->itemId,
			'type'        => Timeframe::BOOKABLE_ID,
			'timeframe-max-days' => 3,
			\CommonsBooking\Model\Timeframe::META_TIMEFRAME_ADVANCE_BOOKING_DAYS => 30,
			\CommonsBooking\Model\Timeframe::META_BOOKING_START_DAY_OFFSET => 0,
			'full-day'    => 'on',
			\CommonsBooking\Model\Timeframe::META_REPETITION => 'd',
			'repetition-start' => strtotime( 'now' ),
			'grid'        => '0',
		];
		$this->timeframeId = wp_insert_post(
			[
				'post_type'   => Timeframe::$postType,
				'post_title'  => self::TIMEFRAME_NAME,
				'post_status' => 'publish',
				'meta_input'  => $timeframeMeta,
			]
		);

		$bookingMeta     = [
			'type' => Timeframe::BOOKING_ID,
			\CommonsBooking\Model\Timeframe::META_REPETITION => 'd',
			'repetition-start' => strtotime( 'now' ),
			'repetition-end' => strtotime( '+1 day' ),
			'location-id' => $this->locationId,
			'item-id' => $this->itemId,
			'start-time' => '00:00',
			'end-time' => '23:59',
			'grid' => '0',
		];
		$this->bookingId = wp_insert_post(
			[
				'post_type'   => Booking::$postType,
				'post_title'  => self::BOOKING_NAME,
				'post_status' => 'publish',
				'post_author' => $this->userId,
				'meta_input'  => $bookingMeta,
			]
		);

		// set from settings
		Settings::updateOption( 'commonsbooking_options_templates', 'emailheaders_from-name', self::FROM_NAME );
		Settings::updateOption( 'commonsbooking_options_templates', 'emailheaders_from-email', self::FROM_MAIL );

		$this->resetMailer();
	}
	/** @inheritdoc */
	public function tearDown(): void {
		parent::tearDown();
		$this->resetMailer();
		wp_delete_user( $this->userId, true );
		wp_delete_post( $this->itemId, true );
		wp_delete_post( $this->locationId, true );
		wp_delete_post( $this->timeframeId, true );
		wp_delete_post( $this->bookingId, true );
	}

	/**
	 * Reset mailer
	 *
	 * @return bool
	 */
	protected function resetMailer() {
		return reset_phpmailer_instance();
	}
	/**
	 * Get mock mailer
	 *
	 * Wraps tests_retrieve_phpmailer_instance()
	 *
	 * @return \CommonsBooking\PHPMailer\PHPMailer\PHPMailer
	 */
	protected function getMockMailer() {
		return tests_retrieve_phpmailer_instance();
	}
}
