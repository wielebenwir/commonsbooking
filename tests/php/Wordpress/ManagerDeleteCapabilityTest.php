<?php

namespace CommonsBooking\Tests\Wordpress;

use CommonsBooking\Plugin;

/**
 * Characterization test: which of our post types may a CB Manager delete (trash)?
 *
 * The user documentation (basics/permission-management) claims that CB Managers
 * can not delete items and locations, while Plugin::addRoleCaps() only removes
 * the "delete_others_*" / "delete_private_*" caps from the manager role.
 * This test pins the *actual* behaviour so docs and code can be aligned.
 *
 * It only checks capabilities (user_can on the "delete_post" meta capability),
 * so nothing is actually deleted.
 */
class ManagerDeleteCapabilityTest extends CustomPostTypeTest {

	private int $ownItemId;
	private int $assignedItemId;
	private int $foreignItemId;

	private int $ownLocationId;
	private int $assignedLocationId;
	private int $foreignLocationId;

	private int $ownTimeframeId;
	private int $foreignTimeframeId;

	protected function setUp(): void {
		parent::setUp();

		$this->createAdministrator();
		$this->createCBManager();

		$m = $this->cbManagerUserID;
		$a = $this->adminUserID;

		// authored by the manager
		$this->ownItemId     = $this->createItem( 'Own item', 'publish', [], $m );
		$this->ownLocationId = $this->createLocation( 'Own location', 'publish', [], $m );

		// authored by the admin, manager is only assigned as item / location admin
		$this->assignedItemId     = $this->createItem( 'Assigned item', 'publish', [ $m ], $a );
		$this->assignedLocationId = $this->createLocation( 'Assigned location', 'publish', [ $m ], $a );

		// authored by the admin, manager has no relation to it
		$this->foreignItemId     = $this->createItem( 'Foreign item', 'publish', [], $a );
		$this->foreignLocationId = $this->createLocation( 'Foreign location', 'publish', [], $a );

		$start = strtotime( self::CURRENT_DATE );
		$end   = strtotime( '+30 day', $start );

		$this->ownTimeframeId = $this->createTimeframe(
			$this->ownLocationId,
			$this->ownItemId,
			$start,
			$end,
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			'on',
			'w',
			0,
			'8:00 AM',
			'12:00 PM',
			'publish',
			[ '1', '2', '3', '4', '5', '6', '7' ],
			'',
			$m
		);

		$this->foreignTimeframeId = $this->createTimeframe(
			$this->foreignLocationId,
			$this->foreignItemId,
			$start,
			$end,
			\CommonsBooking\Wordpress\CustomPostType\Timeframe::BOOKABLE_ID,
			'on',
			'w',
			0,
			'8:00 AM',
			'12:00 PM',
			'publish',
			[ '1', '2', '3', '4', '5', '6', '7' ],
			'',
			$a
		);
	}

	private function managerCanDelete( int $postId ): bool {
		return user_can( $this->cbManagerUserID, 'delete_post', $postId );
	}

	/**
	 * Sanity check: the role really exists and carries the caps this test relies on.
	 */
	public function testManagerRoleCapSetup() {
		$role = get_role( Plugin::$CB_MANAGER_ID );
		$this->assertNotNull( $role );

		// removed by Plugin::addRoleCaps() for the manager role
		$this->assertFalse( $role->has_cap( 'delete_others_cb_items' ) );
		$this->assertFalse( $role->has_cap( 'delete_others_cb_locations' ) );
		$this->assertFalse( $role->has_cap( 'delete_private_cb_items' ) );

		// NOT removed, which is why managers may trash their own posts
		$this->assertTrue( $role->has_cap( 'delete_cb_items' ) );
		$this->assertTrue( $role->has_cap( 'delete_published_cb_items' ) );
		$this->assertTrue( $role->has_cap( 'delete_cb_locations' ) );
		$this->assertTrue( $role->has_cap( 'delete_published_cb_locations' ) );
	}

	/**
	 * Item/location authored by the manager: currently deletable (contradicts the docs).
	 */
	public function testManagerCanDeleteOwnItemsAndLocations() {
		$this->assertTrue( $this->managerCanDelete( $this->ownItemId ) );
		$this->assertTrue( $this->managerCanDelete( $this->ownLocationId ) );
	}

	/**
	 * Item/location the manager is only assigned to: not deletable.
	 */
	public function testManagerCannotDeleteAssignedItemsAndLocations() {
		$this->assertFalse( $this->managerCanDelete( $this->assignedItemId ) );
		$this->assertFalse( $this->managerCanDelete( $this->assignedLocationId ) );
	}

	/**
	 * Item/location without any relation to the manager: not deletable.
	 */
	public function testManagerCannotDeleteForeignItemsAndLocations() {
		$this->assertFalse( $this->managerCanDelete( $this->foreignItemId ) );
		$this->assertFalse( $this->managerCanDelete( $this->foreignLocationId ) );
	}

	/**
	 * Timeframes: only self-created ones (this matches the docs).
	 */
	public function testManagerCanOnlyDeleteOwnTimeframes() {
		$this->assertTrue( $this->managerCanDelete( $this->ownTimeframeId ) );
		$this->assertFalse( $this->managerCanDelete( $this->foreignTimeframeId ) );
	}

	/**
	 * Control group: administrators may delete everything.
	 */
	public function testAdminCanDeleteEverything() {
		foreach ( [
			$this->ownItemId,
			$this->assignedItemId,
			$this->foreignItemId,
			$this->ownLocationId,
			$this->foreignLocationId,
			$this->ownTimeframeId,
			$this->foreignTimeframeId,
		] as $postId ) {
			$this->assertTrue( user_can( $this->adminUserID, 'delete_post', $postId ), "admin should be able to delete $postId" );
		}
	}
}
