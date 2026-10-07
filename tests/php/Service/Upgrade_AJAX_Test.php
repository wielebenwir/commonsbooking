<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Service\Upgrade;
use CommonsBooking\Tests\Wordpress\CustomPostType_AJAX_Test;

/**
 * We need this as a separate testing class because we need to inherit the WP_Ajax_UnitTestCase
 */
class Upgrade_AJAX_Test extends CustomPostType_AJAX_Test {

	protected $hooks                      = [
		'cb_run_upgrade' => array(
			\CommonsBooking\Service\Upgrade::class,
			'runAJAXUpgradeTasks',
		),
	];
	private static int $functionCounter   = 1;
	private static bool $secondTaskHasRun = false;

	public function testRunAJAXUpgradeTasks() {
		$response = $this->runHook();
		$this->assertEquals( 2, self::$functionCounter );
		$this->assertEquals( 2, $response->progress->page );
		$this->assertEquals( 0, $response->progress->task );
		$this->assertFalse( $response->success );

		// Run the AJAX task again
		$_POST['data'] = [
			'progress' => [
				'task' => $response->progress->task,
				'page' => $response->progress->page,
			],
		];
		$response      = $this->runHook();
		$this->assertEquals( 3, self::$functionCounter );
		$this->assertEquals( 3, $response->progress->page );
		$this->assertEquals( 0, $response->progress->task );
		$this->assertFalse( $response->success );

		// first task is done, after this the second task should be run
		$_POST['data'] = [
			'progress' => [
				'task' => $response->progress->task,
				'page' => $response->progress->page,
			],
		];
		$response      = $this->runHook();
		$this->assertFalse( $response->success );
		$this->assertEquals( 4, self::$functionCounter );
		$this->assertEquals( 1, $response->progress->task );

		// Run the AJAX task, it should be successful now
		$_POST['data'] = [
			'progress' => [
				'task' => $response->progress->task,
				'page' => $response->progress->page,
			],
		];
		$response      = $this->runHook();
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
	public static function incrementerFunction( int $page = 1 ) {
		if ( $page === 3 ) {
			// Task successful after 3 runs
			++ self::$functionCounter;

			return true;
		}
		if ( $page != self::$functionCounter ) {
			throw new \Exception( 'Counter is not correct' );
		}

		return ++ self::$functionCounter;
	}

	/**
	 * This is only a dummy function to check if the subsequent task has run
	 * @param int $page does nothing
	 *
	 * @return true
	 */
	public static function secondTaskFunction( int $page = 1 ) {
		self::$secondTaskHasRun = true;
		return true;
	}

	public function set_up() {
		parent::set_up();
		update_option( Upgrade::VERSION_OPTION, '2.5.1' );
		$_POST['data'] = [
			'task' => '0',
			'page' => '1',
		];

		$tasks = new \ReflectionProperty( '\CommonsBooking\Service\Upgrade', 'upgradeTasks' );
		$tasks->setAccessible( true );
		$tasks->setValue( [] );

		$ajaxTasks = new \ReflectionProperty( '\CommonsBooking\Service\Upgrade', 'ajaxUpgradeTasks' );
		$ajaxTasks->setAccessible( true );
		$ajaxTasks->setValue(
			[
				'2.5.2' => [
					[ self::class, 'incrementerFunction' ],
					[ self::class, 'secondTaskFunction' ],
				],
			]
		);
	}

	public function tear_down() {
		self::$functionCounter  = 1;
		self::$secondTaskHasRun = false;
		parent::tear_down();
	}
}
