<?php

namespace CommonsBooking\Tests\View;

use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\View\Admin\BookingUserFilter;
use CommonsBooking\Wordpress\CustomPostType\Booking;

class BookingUserFilterTest extends CustomPostTypeTest {

	private int $filterUserId;

	public function testSelectedAutocompleteUserFiltersByExactId() {
		$this->prepareBookingListRequest(
			[
				'admin_filter_user'    => 'filter-user',
				'admin_filter_user_id' => (string) $this->filterUserId,
			]
		);

		$query = $this->createMainBookingQuery();
		BookingUserFilter::applyFilter( $query );

		$this->assertSame( [ $this->filterUserId ], $query->get( 'author__in' ) );
	}

	public function testFreeTextFilterSearchesLastName() {
		$this->prepareBookingListRequest( [ 'admin_filter_user' => 'Example-Surname' ] );

		$query = $this->createMainBookingQuery();
		BookingUserFilter::applyFilter( $query );

		$this->assertSame( [ $this->filterUserId ], $query->get( 'author__in' ) );
	}

	public function testDefaultSearchFallsBackToBookingSearchWithoutUserMatch() {
		$this->prepareBookingListRequest( [ 's' => 'does-not-match-a-user' ] );

		$query = $this->createMainBookingQuery();
		$query->set( 's', 'does-not-match-a-user' );
		BookingUserFilter::applyFilter( $query );

		$this->assertEmpty( $query->get( 'author__in' ) );
		$this->assertSame( 'does-not-match-a-user', $query->get( 's' ) );
	}

	public function testAutocompleteResultContainsLoginNameAndEmail() {
		$user = get_user_by( 'ID', $this->filterUserId );

		$this->assertSame(
			[
				'id'    => $this->filterUserId,
				'label' => 'filter-user — Filter User · filter-user@example.test',
				'value' => 'filter-user',
			],
			BookingUserFilter::formatUserResult( $user )
		);
	}

	protected function setUp(): void {
		parent::setUp();
		$this->filterUserId = wp_insert_user(
			[
				'user_login'   => 'filter-user',
				'user_pass'    => 'test-password',
				'user_email'   => 'filter-user@example.test',
				'display_name' => 'Filter User',
				'first_name'   => 'Filter',
				'last_name'    => 'Example-Surname',
			]
		);
		set_current_screen( 'edit-' . Booking::getPostType() );
	}

	protected function tearDown(): void {
		wp_delete_user( $this->filterUserId );
		set_current_screen( 'front' );
		parent::tearDown();
	}

	private function prepareBookingListRequest( array $parameters ): void {
		global $pagenow;

		$pagenow = 'edit.php';
		$_GET    = array_merge(
			[
				'post_type' => Booking::getPostType(),
			],
			$parameters
		);
	}

	private function createMainBookingQuery(): \WP_Query {
		global $wp_the_query;

		$query = new \WP_Query();
		$query->set( 'post_type', Booking::getPostType() );
		$wp_the_query = $query;

		return $query;
	}
}
