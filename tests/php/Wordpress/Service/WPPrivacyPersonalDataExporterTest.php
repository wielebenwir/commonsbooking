<?php


namespace CommonsBooking\Tests\Wordpress\Service;

use CommonsBooking\Wordpress\Service\WPPrivacyPersonalDataExporter;
use CommonsBooking\Tests\Wordpress\CustomPostType\CustomPostTypeTest;

class WPPrivacyPersonalDataExporterTest extends CustomPostTypeTest {
	/**
	 * This will check if the bookings can be exported through the WordPress personal data export tool
	 * @return void
	 */
	public function testExportUserBookingsByEmail() {
		$booking    = new \CommonsBooking\Model\Booking(
			$this->createBooking(
				$this->itemId,
				$this->locationId,
				strtotime( self::CURRENT_DATE ),
				strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
				'08:00 AM',
				'12:00 PM',
				'confirmed',
				$this->subscriberId
			)
		);
		$fullExport = WPPrivacyPersonalDataExporter::exportUserBookingsByEmail( get_user_by( 'ID', $this->subscriberId )->user_email );
		$this->assertIsArray( $fullExport );
		$this->assertCount( 1, $fullExport['data'] );
		$this->assertTrue( $fullExport['done'] );
		$data = $fullExport['data'][0]['data'];
		$this->assertEquals( $booking->pickupDatetime(), $data[0]['value'] );

		// get empty export when e-mail is unknown
		$emptyExport = WPPrivacyPersonalDataExporter::exportUserBookingsByEmail( 'doi@knowy.ou' );
		$this->assertIsArray( $emptyExport );
		$this->assertCount( 0, $emptyExport['data'] );
		$this->assertTrue( $emptyExport['done'] );

		// make sure, that the export does not contain any other bookings (like bookings that are not the user's own)
		$this->createAdministrator();
		$emptyExport = WPPrivacyPersonalDataExporter::exportUserBookingsByEmail( get_user_by( 'ID', $this->adminUserID )->user_email );
		$this->assertIsArray( $emptyExport );
		$this->assertCount( 0, $emptyExport['data'] );
		$this->assertTrue( $emptyExport['done'] );

		// now, we test the proper export of multiple bookings with pagination
		$bookingIds = [ $booking->ID ];
		for ( $i = 0; $i < 20; $i++ ) {
			$bookingIds[] = $this->createBooking(
				$this->itemId,
				$this->locationId,
				strtotime( '+' . ( $i + 10 ) . ' day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+' . ( $i + 11 ) . ' days', strtotime( self::CURRENT_DATE ) ),
				'08:00 AM',
				'12:00 PM',
				'confirmed',
				$this->subscriberId
			);
		}
		$partialExport = WPPrivacyPersonalDataExporter::exportUserBookingsByEmail( get_user_by( 'ID', $this->subscriberId )->user_email );
		$this->assertIsArray( $partialExport );
		$this->assertCount( 10, $partialExport['data'] );
		$this->assertFalse( $partialExport['done'] );
		$otherPartialExport = WPPrivacyPersonalDataExporter::exportUserBookingsByEmail( get_user_by( 'ID', $this->subscriberId )->user_email, 2 );
		$this->assertIsArray( $otherPartialExport );
		$this->assertCount( 10, $otherPartialExport['data'] );
		$this->assertFalse( $otherPartialExport['done'] );
		$lastPartialExport = WPPrivacyPersonalDataExporter::exportUserBookingsByEmail( get_user_by( 'ID', $this->subscriberId )->user_email, 3 );
		$this->assertIsArray( $lastPartialExport );
		$this->assertCount( 1, $lastPartialExport['data'] );
		$this->assertTrue( $lastPartialExport['done'] );
	}

	public function testRemoveUserBookingsByEmail() {
		$booking   = new \CommonsBooking\Model\Booking(
			$this->createBooking(
				$this->itemId,
				$this->locationId,
				strtotime( self::CURRENT_DATE ),
				strtotime( '+1 day', strtotime( self::CURRENT_DATE ) ),
				'08:00 AM',
				'12:00 PM',
				'confirmed',
				$this->subscriberId
			)
		);
		$deleteAll = WPPrivacyPersonalDataExporter::removeUserBookingsByEmail( get_user_by( 'ID', $this->subscriberId )->user_email );
		$this->assertIsArray( $deleteAll );
		$this->assertTrue( $deleteAll['items_removed'] );
		$this->assertFalse( $deleteAll['items_retained'] );
		$this->assertEmpty( $deleteAll['messages'] );
		$this->assertTrue( $deleteAll['done'] );

		// now we create a bunch of bookings and delete them in chunks
		$bookingIds = [];
		for ( $i = 0; $i < 21; $i++ ) {
			$bookingIds[] = $this->createBooking(
				$this->itemId,
				$this->locationId,
				strtotime( '+' . ( $i + 10 ) . ' day', strtotime( self::CURRENT_DATE ) ),
				strtotime( '+' . ( $i + 11 ) . ' days', strtotime( self::CURRENT_DATE ) ),
				'08:00 AM',
				'12:00 PM',
				'confirmed',
				$this->subscriberId
			);
		}
		// quickly test if the bookings are there
		$this->assertCount( 21, \CommonsBooking\Repository\Booking::getForUser( get_user_by( 'ID', $this->subscriberId ) ) );

		$deleteFirstPage = WPPrivacyPersonalDataExporter::removeUserBookingsByEmail( get_user_by( 'ID', $this->subscriberId )->user_email );
		$this->assertIsArray( $deleteFirstPage );
		$this->assertTrue( $deleteFirstPage['items_removed'] );
		$this->assertFalse( $deleteFirstPage['items_retained'] );
		$this->assertEmpty( $deleteFirstPage['messages'] );
		$this->assertFalse( $deleteFirstPage['done'] );
		$this->assertCount( 11, \CommonsBooking\Repository\Booking::getForUser( get_user_by( 'ID', $this->subscriberId ) ) );

		$deleteSecondPage = WPPrivacyPersonalDataExporter::removeUserBookingsByEmail( get_user_by( 'ID', $this->subscriberId )->user_email, 2 );
		$this->assertCount( 1, \CommonsBooking\Repository\Booking::getForUser( get_user_by( 'ID', $this->subscriberId ) ) );
		$this->assertIsArray( $deleteSecondPage );
		$this->assertTrue( $deleteSecondPage['items_removed'] );
		$this->assertFalse( $deleteSecondPage['items_retained'] );
		$this->assertEmpty( $deleteSecondPage['messages'] );
		$this->assertFalse( $deleteSecondPage['done'] );

		$deleteThirdPage = WPPrivacyPersonalDataExporter::removeUserBookingsByEmail( get_user_by( 'ID', $this->subscriberId )->user_email, 3 );
		$this->assertIsArray( $deleteThirdPage );
		$this->assertTrue( $deleteThirdPage['items_removed'] );
		$this->assertFalse( $deleteThirdPage['items_retained'] );
		$this->assertEmpty( $deleteThirdPage['messages'] );
		$this->assertTrue( $deleteThirdPage['done'] );
		$this->assertEmpty( \CommonsBooking\Repository\Booking::getForUser( get_user_by( 'ID', $this->subscriberId ) ) );
	}
}
