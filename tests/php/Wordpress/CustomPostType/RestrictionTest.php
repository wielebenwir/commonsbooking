<?php

namespace CommonsBooking\Tests\Wordpress\CustomPostType;

use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Wordpress\CustomPostType\Restriction as RestrictionPostType;
use WP_Query;

class RestrictionTest extends CustomPostTypeTest {

	private $previousGet;
	private $previousPagenow;
	private $previousWpQuery;
	private $previousWpTheQuery;
	private $previousCurrentScreen;

	public function testRestrictionWithoutItemCanBeFilteredByManagerPermissions(): void {
		$restrictionId = $this->createRestriction(
			'hint',
			$this->locationId,
			$this->itemId,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) )
		);
		delete_post_meta( $restrictionId, \CommonsBooking\Model\Restriction::META_ITEM_ID );

		$this->createCBManager();
		update_post_meta( $this->locationId, '_cb_location_admins', [ (string) $this->cbManagerUserID ] );
		wp_set_current_user( $this->cbManagerUserID );

		$restriction   = get_post( $restrictionId );
		$filteredPosts = apply_filters( 'the_posts', [ $restriction ], $this->createMainRestrictionQuery() );

		$this->assertSame( [ $restriction ], array_values( $filteredPosts ) );
	}

	public function testRestrictionWithoutItemUsesOnlyLocationAdmins(): void {
		$restrictionId = $this->createRestriction(
			'hint',
			$this->locationId,
			$this->itemId,
			strtotime( self::CURRENT_DATE ),
			strtotime( '+1 day', strtotime( self::CURRENT_DATE ) )
		);
		delete_post_meta( $restrictionId, \CommonsBooking\Model\Restriction::META_ITEM_ID );

		$restriction = new \CommonsBooking\Model\Restriction( $restrictionId );
		$admins      = $restriction->getAdmins();

		$this->assertSame(
			[ intval( get_post_field( 'post_author', $this->locationId ) ) ],
			$admins
		);
	}

	protected function setUp(): void {
		parent::setUp();

		$this->previousGet           = $_GET;
		$this->previousPagenow       = $GLOBALS['pagenow'] ?? null;
		$this->previousWpQuery       = $GLOBALS['wp_query'] ?? null;
		$this->previousWpTheQuery    = $GLOBALS['wp_the_query'] ?? null;
		$this->previousCurrentScreen = get_current_screen();

		$this->createSubscriber();
		wp_set_current_user( $this->subscriberId );
		set_current_screen( 'edit-' . RestrictionPostType::$postType );
		$_GET['post_type']  = RestrictionPostType::$postType;
		$GLOBALS['pagenow'] = 'edit.php';
	}

	protected function tearDown(): void {
		$_GET                    = $this->previousGet;
		$GLOBALS['pagenow']      = $this->previousPagenow;
		$GLOBALS['wp_query']     = $this->previousWpQuery;
		$GLOBALS['wp_the_query'] = $this->previousWpTheQuery;

		if ( $this->previousCurrentScreen ) {
			set_current_screen( $this->previousCurrentScreen->id );
		} else {
			set_current_screen( 'front' );
		}

		parent::tearDown();
	}

	private function createMainRestrictionQuery(): WP_Query {
		$query        = new WP_Query();
		$query->query = [
			'post_type' => RestrictionPostType::$postType,
		];
		$query->set( 'post_type', RestrictionPostType::$postType );

		$GLOBALS['wp_query']     = $query;
		$GLOBALS['wp_the_query'] = $query;

		return $query;
	}
}
