<?php

namespace CommonsBooking\Tests\Repository;

use CommonsBooking\Plugin;
use CommonsBooking\Repository\UserRepository;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class UserRepositoryTest extends CustomPostTypeTest {


	public function testGetSelectableCBManagers() {
		$cbAdmins = UserRepository::getSelectableCBManagers();
		$this->assertIsArray( $cbAdmins );
		$this->assertNotEmpty( $cbAdmins );
		$this->assertContainsOnlyInstancesOf( \WP_User::class, $cbAdmins );
		$this->assertCount( 1, $cbAdmins );
		$this->assertEquals( 'cb_manager', $cbAdmins[0]->roles[0] );
		$this->assertEquals( $this->cbManagerUserID, $cbAdmins[0]->ID );
	}

	public function testGetOwners() {
		$owners = UserRepository::getOwners();
		// filter out the original author
		$owners = array_filter(
			$owners,
			function ( $owner ) {
				return $owner->ID !== self::USER_ID;
			}
		);
		$this->assertIsArray( $owners );
		$this->assertEmpty( $owners );
		$ownedLocation = $this->createLocation(
			'Owned Location',
			'publish',
			[
				$this->cbManagerUserID,
			]
		);
		$owners        = UserRepository::getOwners();
		$owners        = array_filter(
			$owners,
			function ( $owner ) {
				return $owner->ID !== self::USER_ID;
			}
		);
		$this->assertIsArray( $owners );
		$this->assertNotEmpty( $owners );
		$this->assertContainsOnlyInstancesOf( \WP_User::class, $owners );
		$this->assertCount( 1, $owners );
		$this->assertEquals( $this->cbManagerUserID, reset( $owners )->ID );
	}

	public function testUserHasRoles() {
		$this->createEditor();
		$this->assertTrue( UserRepository::userHasRoles( $this->editorUserID, array( 'editor' ) ) );
		$this->assertTrue( UserRepository::userHasRoles( $this->editorUserID, 'editor' ) );
		// now, let's also make the editor a cb_manager, for this we need to create one first so the role is created
		$this->createCBManager();
		$user = new \WP_User( $this->editorUserID );
		$user->add_role( Plugin::$CB_MANAGER_ID );
		$this->assertTrue( UserRepository::userHasRoles( $this->editorUserID, array( 'editor', 'cb_manager' ) ) );
		$this->assertTrue( UserRepository::userHasRoles( $this->editorUserID, 'cb_manager' ) );
		$this->assertTrue( UserRepository::userHasRoles( $this->editorUserID, 'editor' ) );
		$this->assertFalse( UserRepository::userHasRoles( $this->editorUserID, array( 'subscriber' ) ) );
		$this->assertFalse( UserRepository::userHasRoles( $this->editorUserID, 'subscriber' ) );

		// remove the role again for cleanup
		$user->remove_role( Plugin::$CB_MANAGER_ID );
	}

	protected function setUp(): void {
		parent::setUp();
		$this->createCBManager();
		$this->createSubscriber();
		$this->createAdministrator();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}
}
