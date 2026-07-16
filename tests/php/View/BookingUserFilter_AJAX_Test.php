<?php

namespace CommonsBooking\Tests\View;

use CommonsBooking\Plugin;
use CommonsBooking\Tests\Wordpress\CustomPostType_AJAX_Test;
use CommonsBooking\View\Admin\BookingUserFilter;

/**
 * @group ajax
 */
class BookingUserFilter_AJAX_Test extends CustomPostType_AJAX_Test {

	protected $hooks = [
		BookingUserFilter::AJAX_ACTION => [ BookingUserFilter::class, 'ajaxSearchUsers' ],
	];

	private int $adminUserId;
	private int $searchUserId;

	public function testSearchUsersReturnsAutocompleteResults() {
		$_POST['term']  = 'autocomplete';
		$_POST['nonce'] = wp_create_nonce( BookingUserFilter::AJAX_ACTION );

		$response = $this->runHook();

		$this->assertTrue( $response->success );
		$this->assertCount( 1, $response->data );
		$this->assertSame( $this->searchUserId, $response->data[0]->id );
		$this->assertSame( 'autocomplete-user', $response->data[0]->value );
	}

	public function testSearchUsersRequiresPermission() {
		wp_set_current_user( 0 );
		$_POST['term']  = 'autocomplete';
		$_POST['nonce'] = wp_create_nonce( BookingUserFilter::AJAX_ACTION );

		$response = $this->runHook();

		$this->assertFalse( $response->success );
	}

	public function testSearchUsersRequiresTwoCharacters() {
		$_POST['term']  = 'a';
		$_POST['nonce'] = wp_create_nonce( BookingUserFilter::AJAX_ACTION );

		$response = $this->runHook();

		$this->assertTrue( $response->success );
		$this->assertSame( [], $response->data );
	}

	public function set_up() {
		parent::set_up();

		Plugin::addCPTRoleCaps();
		$this->adminUserId  = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$this->searchUserId = self::factory()->user->create(
			[
				'user_login'   => 'autocomplete-user',
				'user_email'   => 'autocomplete@example.test',
				'display_name' => 'Autocomplete User',
			]
		);
		wp_set_current_user( $this->adminUserId );
	}
}
