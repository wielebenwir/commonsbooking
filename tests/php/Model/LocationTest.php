<?php

namespace CommonsBooking\Tests\Model;

use CommonsBooking\Model\Location;
use CommonsBooking\Model\Restriction;
use CommonsBooking\Model\Timeframe;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use SlopeIt\ClockMock\ClockMock;

class LocationTest extends CustomPostTypeTest {
	private Location $locationModel;
	private Timeframe $timeframeModel;

	/**
	 * Not working - Maybe bug in function?
	 * @return void
	 * @throws \Exception
	 */
	public function testGetBookableTimeframesByItem() {
		ClockMock::freeze( new \DateTime( self::CURRENT_DATE ) );
		$timeframeArray[] = $this->timeframeModel;
		$this->assertEquals( $timeframeArray, $this->locationModel->getBookableTimeframesByItem( $this->itemId, true ) );
	}

	public function testGetAdmins() {
		// Case: No admins
		// $this->assertEquals([], $this->locationModel->getAdmins()); - Currently this function includes the post author
		$this->assertEquals( [ self::USER_ID ], $this->locationModel->getAdmins() );

		// Case: CB Manager as admin
		$this->createCBManager();
		$adminLocationModel = new Location(
			$this->createLocation( 'TestLocation2', 'publish', [ $this->cbManagerUserID ] )
		);
		// $this->assertEquals([$this->cbManagerUserID], $adminLocationModel->getAdmins()); - Currently this function includes the post author
		$this->assertEquals( [ $this->cbManagerUserID, self::USER_ID ], $adminLocationModel->getAdmins() );
	}

	/**
	 * Can be used after PR #1179 is merged
	 * @return void
	 * @throws \Exception
	 */
	/*
	public function testGetRestrictions() {
		$this->restrictionIds = array_unique($this->restrictionIds);
		$restrictionArray = [];
		foreach ($this->restrictionIds as $restrictionId) {
			$restrictionArray[] = new Restriction($restrictionId);
		}
		$this->assertEquals($restrictionArray, $this->locationModel->getRestrictions());
	}
	*/

	public function testGetFormattedAddress() {

		// Case: Empty meta fields
		$this->assertEquals( 'Testlocation<br>', $this->locationModel->formattedAddress() );
		$this->assertEquals( '', $this->locationModel->formattedAddressOneLine() );

		// Case: Partial emtpy meta fields
		update_post_meta( $this->locationId, COMMONSBOOKING_METABOX_PREFIX . 'location_street', 'Karl-Marx-Allee' );
		wp_cache_flush();
		$this->locationModel = new Location( $this->locationId );
		$this->assertEquals( 'Karl-Marx-Allee  ', $this->locationModel->formattedAddressOneLine() );

		// Case: Complete meta fields
		update_post_meta( $this->locationId, COMMONSBOOKING_METABOX_PREFIX . 'location_postcode', '10115' );
		update_post_meta( $this->locationId, COMMONSBOOKING_METABOX_PREFIX . 'location_city', 'Berlin' );
		wp_cache_flush();
		$this->locationModel = new Location( $this->locationId );
		$this->assertEquals( 'Testlocation<br> Karl-Marx-Allee<br> 10115 Berlin<br>', $this->locationModel->formattedAddress() );

		$this->assertEquals( 'Karl-Marx-Allee, 10115 Berlin', $this->locationModel->formattedAddressOneLine() );
	}

	public function testGetFormattedContactInfo() {
		// Case: Complete contact meta fields
		update_post_meta( $this->locationId, COMMONSBOOKING_METABOX_PREFIX . 'location_contact', 'Max Weber' );
		wp_cache_flush();
		$this->locationModel = new Location( $this->locationId );
		$this->assertEquals( '<br><br>Please contact the contact persons at the location directly if you have any questions regarding collection or return:<br>Max Weber', $this->locationModel->formattedContactInfo() );
	}

	protected function setUp(): void {
		parent::setUp();
		$this->restrictionIds[] = $this->createRestriction(
			Restriction::META_HINT,
			$this->locationId,
			$this->itemId,
			strtotime( self::CURRENT_DATE ),
			null
		);
		$this->timeframeModel   = new Timeframe( $this->createBookableTimeFrameIncludingCurrentDay() );
		$this->locationModel    = new Location( $this->locationId );
		$this->createSubscriber();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}
}
