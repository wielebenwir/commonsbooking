<?php

namespace CommonsBooking\Tests;

use CommonsBooking\Model\CustomPost;
use CommonsBooking\Plugin;
use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\Wordpress\CustomPostType\CustomPostType;
use CommonsBooking\Wordpress\CustomPostType\Item;
use CommonsBooking\Wordpress\CustomPostType\Location;

class PluginTest extends CustomPostTypeTest {


	private $postIDs = [];

	/**
	 * Regression test for wielebenwir/commonsbooking#1510.
	 *
	 * The item/location category submenu pages linked to
	 * edit-tags.php?taxonomy=<tax> without passing the post_type. Because of
	 * that, the "Anzahl" (count) column on the category overview built its link
	 * with the default `post` post type, so clicking it listed no items at all.
	 *
	 * The category menu URLs must carry the matching post_type so the overview
	 * (and the count link WordPress derives from the screen) target the correct
	 * custom post type.
	 *
	 * @return void
	 */
	public function testCategoryMenuPagesPassPostType() {
		// The category submenus are only registered for admins.
		$this->createAdministrator();
		wp_set_current_user( $this->adminUserID );

		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		// Start from a clean menu state so we only inspect what addMenuPages() registers.
		global $menu, $submenu;
		$menu    = [];
		$submenu = [];

		Plugin::addMenuPages();

		$this->assertArrayHasKey(
			'cb-dashboard',
			$submenu,
			'Expected commonsbooking submenu pages to be registered under cb-dashboard.'
		);

		$cases = [
			Item::getTaxonomyName()     => Item::getPostType(),
			Location::getTaxonomyName() => Location::getPostType(),
		];

		foreach ( $cases as $taxonomy => $postType ) {
			$menuSlug = null;
			foreach ( $submenu['cb-dashboard'] as $entry ) {
				// Index 2 holds the menu slug / URL of the submenu page.
				if ( isset( $entry[2] ) && strpos( $entry[2], 'taxonomy=' . $taxonomy ) !== false ) {
					$menuSlug = $entry[2];
					break;
				}
			}

			$this->assertNotNull(
				$menuSlug,
				sprintf( 'Category menu page for taxonomy "%s" was not registered.', $taxonomy )
			);

			// Parse the query string to compare the actual param value.
			$query = wp_parse_url( $menuSlug, PHP_URL_QUERY );
			parse_str( (string) $query, $params );

			$this->assertArrayHasKey(
				'post_type',
				$params,
				sprintf(
					'Category menu URL for taxonomy "%s" is missing the post_type param (#1510): %s',
					$taxonomy,
					$menuSlug
				)
			);
			$this->assertSame(
				$postType,
				$params['post_type'],
				sprintf( 'Category menu URL for taxonomy "%s" points at the wrong post_type.', $taxonomy )
			);
		}
	}
	public function testGetCustomPostTypes() {
		$this->assertIsArray( Plugin::getCustomPostTypes() );
		// make sure, that we also have a model for each custom post type
		foreach ( Plugin::getCustomPostTypes() as $customPostType ) {
			// first, create a post of this type
			$post = wp_insert_post(
				[
					'post_type' => $customPostType,
					'post_title' => 'Test ' . $customPostType,
					'post_status' => 'publish',
				]
			);
			$this->assertIsInt( $post );
			$this->postIDs[] = $post;
			// then, try to get a model from the post. Every declared CPT should have a model
			$this->assertInstanceOf( CustomPost::class, CustomPostType::getModel( $post ) );
		}
	}

	protected function setUp(): void {
		parent::setUp();
	}

	protected function tearDown(): void {
		foreach ( $this->postIDs as $postID ) {
			wp_delete_post( $postID, true );
		}
		parent::tearDown();
	}
}
