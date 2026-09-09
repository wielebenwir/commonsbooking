<?php

namespace CommonsBooking\Tests\View;

use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\View\Admin\BookingUserFilter;
use CommonsBooking\Wordpress\CustomPostType\Booking;

class BookingUserFilterTest extends CustomPostTypeTest {

	private int $filterUserId;

	public function testBookingHooksRegisterUserFilterCallbacks() {
		$booking = new Booking();
		$booking->initHooks();

		$this->assertSame(
			10,
			has_action( 'restrict_manage_posts', [ BookingUserFilter::class, 'renderFilter' ] )
		);
		$this->assertSame(
			10,
			has_action( 'pre_get_posts', [ BookingUserFilter::class, 'applyFilter' ] )
		);
		$this->assertSame(
			10,
			has_action( 'admin_enqueue_scripts', [ BookingUserFilter::class, 'enqueueAssets' ] )
		);
		$this->assertSame(
			10,
			has_action(
				'wp_ajax_' . BookingUserFilter::AJAX_ACTION,
				[ BookingUserFilter::class, 'ajaxSearchUsers' ]
			)
		);
	}

	public function testRenderFilterOutputsCurrentSearchAndSelectedUser() {
		$this->prepareBookingListRequest(
			[
				'admin_filter_user'    => 'filter-user',
				'admin_filter_user_id' => (string) $this->filterUserId,
			]
		);

		ob_start();
		BookingUserFilter::renderFilter();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'name="admin_filter_user"', $html );
		$this->assertStringContainsString( 'value="filter-user"', $html );
		$this->assertStringContainsString( 'name="admin_filter_user_id"', $html );
		$this->assertStringContainsString( 'value="' . $this->filterUserId . '"', $html );
		$this->assertStringContainsString( 'autocomplete="off"', $html );
	}

	public function testRenderFilterDoesNotOutputOnAnotherAdminScreen() {
		set_current_screen( 'edit-post' );

		ob_start();
		BookingUserFilter::renderFilter();
		$html = ob_get_clean();

		$this->assertSame( '', $html );
	}

	public function testRenderFilterDoesNotOutputOutsideAdmin() {
		set_current_screen( 'front' );

		ob_start();
		BookingUserFilter::renderFilter();
		$html = ob_get_clean();

		$this->assertSame( '', $html );
	}

	public function testEnqueueAssetsAddsAutocompleteConfiguration() {
		wp_register_script( 'cb-scripts-admin', '/admin.js', [], '1.0', false );

		BookingUserFilter::enqueueAssets( 'edit.php' );

		$this->assertTrue( wp_script_is( 'jquery-ui-autocomplete', 'enqueued' ) );
		$scriptData = wp_scripts()->get_data( 'cb-scripts-admin', 'data' );
		$this->assertIsString( $scriptData );
		$this->assertStringContainsString( 'var cbBookingUserFilter =', $scriptData );
		$this->assertStringContainsString( '"minimumLength":"2"', $scriptData );
		$this->assertStringContainsString( BookingUserFilter::AJAX_ACTION, $scriptData );
		$this->assertStringContainsString( 'admin_filter_user_id', $scriptData );
	}

	public function testEnqueueAssetsIgnoresOtherAdminPages() {
		wp_dequeue_script( 'jquery-ui-autocomplete' );

		BookingUserFilter::enqueueAssets( 'post.php' );

		$this->assertFalse( wp_script_is( 'jquery-ui-autocomplete', 'enqueued' ) );
	}

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

	public function testEmptyFilterDoesNotModifyQuery() {
		$this->prepareBookingListRequest( [] );

		$query = $this->createMainBookingQuery();
		BookingUserFilter::applyFilter( $query );

		$this->assertEmpty( $query->get( 'author__in' ) );
		$this->assertSame( '', $query->get( 's' ) );
	}

	public function testExplicitFilterWithoutMatchingUserReturnsNoBookings() {
		$this->prepareBookingListRequest( [ 'admin_filter_user' => 'does-not-match-a-user' ] );

		$query = $this->createMainBookingQuery();
		BookingUserFilter::applyFilter( $query );

		$this->assertSame( [ 0 ], $query->get( 'author__in' ) );
		$this->assertSame( '', $query->get( 's' ) );
	}

	public function testFilterIgnoresNonMainQueries() {
		$this->prepareBookingListRequest( [ 'admin_filter_user' => 'filter-user' ] );

		$mainQuery = $this->createMainBookingQuery();
		$query     = new \WP_Query();
		$query->set( 'post_type', Booking::getPostType() );
		BookingUserFilter::applyFilter( $query );

		$this->assertNotSame( $mainQuery, $query );
		$this->assertEmpty( $query->get( 'author__in' ) );
	}

	public function testFilterAcceptsPostTypeArray() {
		$this->prepareBookingListRequest( [ 'admin_filter_user' => 'filter-user' ] );

		$query = $this->createMainBookingQuery();
		$query->set( 'post_type', [ Booking::getPostType() ] );
		BookingUserFilter::applyFilter( $query );

		$this->assertSame( [ $this->filterUserId ], $query->get( 'author__in' ) );
	}

	public function testFilterReadsPostTypeFromRequest() {
		$this->prepareBookingListRequest( [ 'admin_filter_user' => 'filter-user' ] );

		$query = $this->createMainBookingQuery();
		$query->set( 'post_type', '' );
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

	public function testAutocompleteResultCanContainOnlyLogin() {
		$userId = wp_insert_user(
			[
				'user_login'   => 'login-only',
				'user_pass'    => 'test-password',
				'user_email'   => '',
				'display_name' => 'login-only',
			]
		);
		$this->assertIsInt( $userId );
		$user = get_user_by( 'ID', $userId );

		$this->assertSame(
			[
				'id'    => $userId,
				'label' => 'login-only',
				'value' => 'login-only',
			],
			BookingUserFilter::formatUserResult( $user )
		);

		wp_delete_user( $userId );
	}

	protected function setUp(): void {
		parent::setUp();
		$_GET               = [];
		$_POST              = [];
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
		$_GET  = [];
		$_POST = [];
		wp_dequeue_script( 'jquery-ui-autocomplete' );
		wp_deregister_script( 'cb-scripts-admin' );
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
