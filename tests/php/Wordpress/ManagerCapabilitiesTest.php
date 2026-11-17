<?php

namespace CommonsBooking\Tests\Wordpress;

use CommonsBooking\Plugin;

/**
 * Characterization test: what may a CB Manager do with each of our post types?
 *
 * Plugin::addRoleCaps() gives the manager role all capabilities of the admin for every
 * custom post type and then only removes "read_private_*", "delete_private_*" and
 * "delete_others_*". This test pins the resulting behaviour (own vs. assigned vs. foreign
 * posts) so that the user documentation (basics/permission-management) can be kept in sync.
 *
 * It only checks capabilities (user_can on the "delete_post" / "edit_post" meta capabilities),
 * so nothing is actually deleted.
 */
class ManagerCapabilitiesTest extends CustomPostTypeTest {

	private int $ownItemId;
	private int $assignedItemId;
	private int $foreignItemId;

	private int $ownLocationId;
	private int $assignedLocationId;
	private int $foreignLocationId;

	private int $ownTimeframeId;
	private int $foreignTimeframeId;

	private int $ownRestrictionId;
	private int $foreignRestrictionId;

	private int $ownMapId;
	private int $foreignMapId;

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

		$restrictionStart = strtotime( self::CURRENT_DATE );
		$restrictionEnd   = strtotime( '+3 day', $restrictionStart );

		$this->ownRestrictionId = $this->createRestriction(
			\CommonsBooking\Model\Restriction::TYPE_HINT,
			$this->ownLocationId,
			$this->ownItemId,
			$restrictionStart,
			$restrictionEnd,
			'active',
			'Hint',
			$m
		);

		$this->foreignRestrictionId = $this->createRestriction(
			\CommonsBooking\Model\Restriction::TYPE_HINT,
			$this->foreignLocationId,
			$this->foreignItemId,
			$restrictionStart,
			$restrictionEnd,
			'active',
			'Hint',
			$a
		);

		// maps have no assignment concept, so only the author differs
		$this->ownMapId = $this->createMap();
		wp_update_post(
			[
				'ID'          => $this->ownMapId,
				'post_author' => $m,
			]
		);
		$this->foreignMapId = $this->createMap();
		wp_update_post(
			[
				'ID'          => $this->foreignMapId,
				'post_author' => $a,
			]
		);
	}

	private function managerCanDelete( int $postId ): bool {
		return user_can( $this->cbManagerUserID, 'delete_post', $postId );
	}

	private function managerCanEdit( int $postId ): bool {
		return user_can( $this->cbManagerUserID, 'edit_post', $postId );
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
	 * Restrictions behave like items: only self-created ones can be deleted.
	 */
	public function testManagerCanOnlyDeleteOwnRestrictions() {
		$this->assertTrue( $this->managerCanDelete( $this->ownRestrictionId ) );
		$this->assertFalse( $this->managerCanDelete( $this->foreignRestrictionId ) );
	}

	/**
	 * Restrictions: managers can create and edit them on capability level.
	 * Note that the "only for assigned items / locations" rule is NOT part of the capabilities,
	 * it is enforced by the admin list filter (Restriction::filterAdminList)
	 * and the post model (Restriction::getAdmins()).
	 */
	public function testManagerCapabilitiesForRestrictions() {
		$role = get_role( Plugin::$CB_MANAGER_ID );
		$this->assertTrue( $role->has_cap( 'edit_cb_restrictions' ) );
		$this->assertTrue( $role->has_cap( 'publish_cb_restrictions' ) );
		$this->assertTrue( $role->has_cap( 'manage_commonsbooking_cb_restriction' ) );

		$this->assertTrue( $this->managerCanEdit( $this->ownRestrictionId ) );
		// edit_others_* is not removed from the manager role
		$this->assertTrue( $this->managerCanEdit( $this->foreignRestrictionId ) );
	}

	/**
	 * Maps are not part of Plugin::getCBManagerCustomPostTypes(), but the manager role still
	 * receives all caps for them in Plugin::addCPTRoleCaps().
	 * Maps are registered without map_meta_cap, so WordPress checks the literal singular caps
	 * (edit_cb_map / delete_cb_map) and does not distinguish between own and foreign maps.
	 */
	public function testManagerCapabilitiesForMaps() {
		$role = get_role( Plugin::$CB_MANAGER_ID );
		$this->assertTrue( $role->has_cap( 'manage_commonsbooking_cb_map' ) );
		$this->assertTrue( $role->has_cap( 'edit_cb_maps' ) );
		$this->assertTrue( $role->has_cap( 'publish_cb_maps' ) );
		$this->assertTrue( $role->has_cap( 'edit_cb_map' ) );
		$this->assertTrue( $role->has_cap( 'delete_cb_map' ) );

		$this->assertTrue( $this->managerCanEdit( $this->ownMapId ) );
		$this->assertTrue( $this->managerCanEdit( $this->foreignMapId ) );
		$this->assertTrue( $this->managerCanDelete( $this->ownMapId ) );
		$this->assertTrue( $this->managerCanDelete( $this->foreignMapId ) );
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
			$this->ownRestrictionId,
			$this->foreignRestrictionId,
			$this->ownMapId,
			$this->foreignMapId,
		] as $postId ) {
			$this->assertTrue( user_can( $this->adminUserID, 'delete_post', $postId ), "admin should be able to delete $postId" );
		}
	}
}
