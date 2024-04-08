<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Service\Upgrade;

/**
 * We need this as a separate testing class because we need to inherit the WP_Ajax_UnitTestCase
 */
class UpgradeTest_AJAX extends \WP_Ajax_UnitTestCase {

	const ACTION = 'cb_run_upgrade';
	private static int $functionCounter = 1;

	public function testRunAJAXUpgradeTasks() {
		try {
			$this->_handleAjax( self::ACTION );
		} catch ( \WPAjaxDieContinueException $e ) {
			// We expect this exception to be thrown
		}

		$firstResponse = $this->_last_response;
		$response = json_decode( $firstResponse );
		$this->assertEquals( 2, self::$functionCounter );
		$this->assertEquals( 2, $response->progress->page );
		$this->assertEquals( 0, $response->progress->task );
		$this->assertFalse( $response->success );

		// Run the AJAX task again
		$_POST[ 'data' ] = [
			'progress' => [
				'task' => $response->progress->task,
				'page' => $response->progress->page
			]
		];
		$this->fail("Check if reaches");
		try {
			$this->_handleAjax( self::ACTION );
		} catch ( \WPAjaxDieContinueException $e ) {
			// We expect this exception to be thrown
		}

		//trim the first response away, for some reason, the responses are just appended to each other
		$secondResponse = substr( $this->_last_response, strlen( $firstResponse ) );
		$response = json_decode( $secondResponse );
		$this->assertEquals( 3, self::$functionCounter );
		$this->assertEquals( 3, $response->progress->page );
		$this->assertEquals( 0, $response->progress->task );
		$this->assertFalse( $response->success );

		// Run the AJAX task, it should be successful now
		$_POST[ 'data' ] = [
			'progress' => [
				'task' => $response->progress->task,
				'page' => $response->progress->page
			]
		];
		try {
			$this->_handleAjax( self::ACTION );
		} catch ( \WPAjaxDieContinueException $e ) {
			// We expect this exception to be thrown
		}

		$finalResponse = substr( $this->_last_response, strlen( $firstResponse ) + strlen( $secondResponse ) );
		$response = json_decode( $finalResponse );
		$this->assertEquals( 4, self::$functionCounter );
		$this->assertTrue( $response->success );
		$this->assertEquals( COMMONSBOOKING_VERSION, get_option( Upgrade::VERSION_OPTION ) );
	}

	/**
	 * This acts as a dummy AJAX tasks that will run 3 times before it returns true
	 *
	 * @param int $page
	 *
	 * @return int|true
	 * @throws \Exception
	 */
	public static function incrementerFunction(int $page = 1) {
		if ( $page === 3) {
			// Task successful after 3 runs
			++self::$functionCounter;
			return true;
		}
		if ( $page != self::$functionCounter) {
			throw new \Exception('Counter is not correct');
		}
		return ++self::$functionCounter;
	}
	public function set_up() {
		parent::set_up();
		update_option(Upgrade::VERSION_OPTION, '2.5.1');
		add_action( 'wp_ajax_' . self::ACTION , array( \CommonsBooking\Service\Upgrade::class, 'runAJAXUpgradeTasks' ) );
		$_POST['_wpnonce'] = wp_create_nonce( self::ACTION );
		$_POST[ 'data' ] = [
			'task' => '0',
			'page' => '1'
		];

		$tasks = new \ReflectionProperty( '\CommonsBooking\Service\Upgrade', 'upgradeTasks' );
		$tasks->setAccessible( true );
		$tasks->setValue( [] );

		$ajaxTasks = new \ReflectionProperty( '\CommonsBooking\Service\Upgrade', 'ajaxUpgradeTasks' );
		$ajaxTasks->setAccessible( true );
		$ajaxTasks->setValue( [
			'2.5.2' => [
				[ self::class, 'incrementerFunction' ]
			]
		] );
	}

	public function tear_down() {
		self::$functionCounter = 1;
		parent::tear_down();
	}
}