<?php

namespace CommonsBooking\Tests\Benchmark\View;

use CommonsBooking\Tests\Benchmark\BenchmarkCase;
use CommonsBooking\View\Booking;

use function PHPUnit\Framework\assertGreaterThan;
use function PHPUnit\Framework\assertNotEmpty;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;

/**
 * @BeforeMethods({"setUp"})
 * @AfterMethods({"tearDown"})
 */
class BookingShortcodeBench extends BenchmarkCase {

	private int $subscriberBookingTotal = 0;

	/**
	 * @BeforeMethods({"setUp", "loginAsSubscriber"})
	 * @Iterations(3)
	 * @Revs(3)
	 */
	public function benchBookingShortcodeAsSubscriber(): void {
		$output = Booking::shortcode( [] );

		$templateData = $GLOBALS['templateData'];
		$subscriber   = get_user_by( 'ID', $this->subscriberId );

		$this->executeBookingDataAjax();

		assertStringContainsString( 'booking-list', $output );
		assertGreaterThan( 0, $templateData['total'] );
		assertNotEmpty( $templateData['data'] );

		foreach ( $templateData['data'] as $row ) {
			assertSame( $subscriber->user_login, $row['user'] );
		}
	}

	/**
	 * @BeforeMethods({"setUp", "loginAsAdmin", "recordSubscriberBookingTotal"})
	 * @Iterations(3)
	 * @Revs(3)
	 */
	public function benchBookingShortcodeAsAdmin(): void {
		$output = Booking::shortcode( [] );

		$templateData = $GLOBALS['templateData'];

		$this->executeBookingDataAjax();

		assertStringContainsString( 'booking-list', $output );
		assertGreaterThan( $this->subscriberBookingTotal, $templateData['total'] );
	}

	public function loginAsSubscriber(): void {
		wp_set_current_user( $this->subscriberId );
	}

	public function loginAsAdmin(): void {
		wp_set_current_user( $this->adminUserID );
	}

	public function recordSubscriberBookingTotal(): void {
		$subscriber = get_user_by( 'ID', $this->subscriberId );
		$data       = Booking::getBookingListData( 1, $subscriber );

		$this->subscriberBookingTotal = is_array( $data ) ? (int) $data['total'] : 0;
	}

	private function executeBookingDataAjax(): void {
		$errorLevel = error_reporting();
		error_reporting( $errorLevel & ~E_WARNING );

		ob_start();
		do_action( 'wp_ajax_cb_bookings_data' );
		$response = ob_get_clean();

		error_reporting( $errorLevel );

		assertStringContainsString( '"total"', $response );
	}
}
