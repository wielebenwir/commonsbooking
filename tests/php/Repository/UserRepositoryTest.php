<?php

namespace CommonsBooking\Tests\Repository;

use CommonsBooking\Plugin;
use CommonsBooking\Repository\UserRepository;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;

class UserRepositoryTest extends CustomPostTypeTest {

	private array $searchUserIds = [];

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

	public function testSearchUsersByCoreFieldsAndNameMetadata() {
		$this->createSearchUser( 'anna', 'anna@example.test', 'Anna Meyer', 'Anna', 'Meyer' );
		$this->createSearchUser( 'annabelle', 'user8@example.test', 'Annabelle König', 'Annabelle', 'König' );
		$this->createSearchUser( 'bert', 'ann-team@example.test', 'Bert Beispiel', 'Bert', 'Beispiel' );
		$this->createSearchUser( 'zeta', 'zeta@example.test', 'Zeta User', 'Annika', 'Schmidt' );

		$users = UserRepository::search( 'ann', 20 );
		$this->assertSame(
			[ 'anna', 'annabelle', 'bert', 'zeta' ],
			array_map(
				static function ( \WP_User $user ): string {
					return $user->user_login;
				},
				$users
			)
		);

		$this->assertSame(
			[ 'anna', 'annabelle' ],
			array_map(
				static function ( \WP_User $user ): string {
					return $user->user_login;
				},
				UserRepository::search( 'ann', 2 )
			)
		);

		$this->assertSame(
			[ $this->searchUserIds[3] ],
			UserRepository::searchIds( 'Schmidt' )
		);
	}

	private function createSearchUser(
		string $login,
		string $email,
		string $displayName,
		string $firstName,
		string $lastName
	): void {
		$userId = wp_insert_user(
			[
				'user_login'   => $login,
				'user_pass'    => 'test-password',
				'user_email'   => $email,
				'display_name' => $displayName,
				'first_name'   => $firstName,
				'last_name'    => $lastName,
			]
		);
		$this->assertIsInt( $userId );
		$this->searchUserIds[] = $userId;
	}

	protected function setUp(): void {
		parent::setUp();
		$this->createCBManager();
		$this->createSubscriber();
		$this->createAdministrator();
	}

	protected function tearDown(): void {
		foreach ( $this->searchUserIds as $userId ) {
			wp_delete_user( $userId );
		}
		parent::tearDown();
	}
}
