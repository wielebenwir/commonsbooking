<?php

namespace CommonsBooking\Tests\Repository;

use CommonsBooking\Repository\UserRepository;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class UserRepositoryTest extends CustomPostTypeTest
{

    public function testGetSelectableCBManagers()
    {
		$cbAdmins = UserRepository::getSelectableCBManagers();
		$this->assertIsArray($cbAdmins);
		$this->assertNotEmpty($cbAdmins);
		$this->assertContainsOnlyInstancesOf(\WP_User::class, $cbAdmins);
		$this->assertCount(1, $cbAdmins);
		$this->assertEquals('cb_manager', $cbAdmins[0]->roles[0]);
		$this->assertEquals($this->cbManagerUserID, $cbAdmins[0]->ID);
	}

	public function testGetOwners()
	{
		$owners = UserRepository::getOwners();
		//filter out the original author
		$owners = array_filter($owners, function ($owner) {
			return $owner->ID !== self::USER_ID;
		});
		$this->assertIsArray($owners);
		$this->assertEmpty($owners);
		$ownedLocation = $this->createLocation(
			"Owned Location",
			'publish',
			[
				$this->cbManagerUserID
			]
		);
		$owners = UserRepository::getOwners();
		$owners = array_filter($owners, function ($owner) {
			return $owner->ID !== self::USER_ID;
		});
		$this->assertIsArray($owners);
		$this->assertNotEmpty($owners);
		$this->assertContainsOnlyInstancesOf(\WP_User::class, $owners);
		$this->assertCount(1, $owners);
		$this->assertEquals($this->cbManagerUserID, reset($owners)->ID);
	}

	protected function setUp(): void {
		parent::setUp();
		$this->createCBManager();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}
}
