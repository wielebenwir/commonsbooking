<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\Plugin;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase {
	private array $posts                       = [];
	private static array $fakeShortcodeACalled = [];
	private static array $fakeShortcodeBCalled = [];

	public static function fakeShortcodeA( $args, $content = null ) {
		self::$fakeShortcodeACalled = [ $args, $content ];
	}

	public static function fakeShortcodeB( $args, $content = null ) {
		self::$fakeShortcodeBCalled = [ $args, $content ];
	}


	public function testWarmupCache() {
		// one with args, one without
		$shortcodes = [ '[cb_items]', '[cb_locations id=123]' ];
		$this->createPages( $shortcodes );
		Plugin::warmupCache();
		$this->assertNotEmpty( self::$fakeShortcodeACalled );
		$this->assertNotEmpty( self::$fakeShortcodeBCalled );

		$this->assertEmpty( self::$fakeShortcodeACalled[0] );
		$this->assertEmpty( self::$fakeShortcodeACalled[1] );

		$this->assertEquals( [ 'id' => '123' ], self::$fakeShortcodeBCalled[0] );
		$this->assertEmpty( self::$fakeShortcodeBCalled[1] );
	}

	public function testWarmupCache_bodyAttributes() {
		$jsonBody   = '{<br>  "map": {<br>    "markerIcon": {<br>      "renderers": [{ "type": "traditional-icon" }]<br>    }<br>  }<br>}';
		$shortcodes = [
			'[cb_items id=123]' . $jsonBody . '[/cb_items]',
			'[cb_locations id=123 layouts=Map]',
		];
		$this->createPages( $shortcodes );

		Plugin::warmupCache();

		$this->assertNotEmpty( self::$fakeShortcodeACalled );
		$this->assertNotEmpty( self::$fakeShortcodeBCalled );

		$this->assertEquals( [ 'id' => '123' ], self::$fakeShortcodeACalled[0] );
		$this->assertEquals( $jsonBody, self::$fakeShortcodeACalled[1] );

		$this->assertEquals(
			[
				'id' => '123',
				'layouts' => 'Map',
			],
			self::$fakeShortcodeBCalled[0]
		);
		$this->assertEmpty( self::$fakeShortcodeBCalled[1] );
	}

	public function testWarmupCache_twoOnSamePage() {
		$this->createPages( [ '[cb_items id=123] [cb_locations id=456]' ] );

		Plugin::warmupCache();

		$this->assertNotEmpty( self::$fakeShortcodeACalled );
		$this->assertNotEmpty( self::$fakeShortcodeBCalled );

		$this->assertEquals( [ 'id' => '123' ], self::$fakeShortcodeACalled[0] );
		$this->assertEmpty( self::$fakeShortcodeACalled[1] );

		$this->assertEquals( [ 'id' => '456' ], self::$fakeShortcodeBCalled[0] );
		$this->assertEmpty( self::$fakeShortcodeBCalled[1] );
	}

	/**
	 * Test for #1723 (warmup crashing)
	 *
	 * @return void
	 */
	public function testWarmupCache_1723() {
		Plugin::warmupCache();
		// because it just checks for crashes
		$this->expectNotToPerformAssertions();
	}

	/**
	 * Test for #1725 (ran on all post types)
	 * @return void
	 */
	public function testWarmupCache_1725() {
		// Create a draft page
		$this->posts[] = wp_insert_post(
			[
				'post_type' => 'page',
				'post_content' => '[cb_items]',
			]
		);

		Plugin::warmupCache();
		$this->assertEmpty( self::$fakeShortcodeACalled );
	}

	protected function tearDown(): void {
		foreach ( $this->posts as $post ) {
			wp_delete_post( $post, true );
		}
		parent::tearDown();
	}

	protected function setUp(): void {
		// overwrite the existing shortcodes with dummy functions
		$shortcodes = new \ReflectionProperty( '\CommonsBooking\Plugin', 'cbShortCodeFunctions' );
		$shortcodes->setAccessible( true );
		$shortcodes->setValue(
			[
				'cb_items' => array( self::class, 'fakeShortcodeA' ),
				'cb_locations' => array( self::class, 'fakeShortcodeB' ),
			]
		);
		self::$fakeShortcodeACalled = [];
		self::$fakeShortcodeBCalled = [];
		parent::setUp();
	}

	private function createPages( $pageContents ) {
		foreach ( $pageContents as $pageContent ) {
			$postArray     = [
				'post_type'    => 'page',
				'post_content' => $pageContent,
				'post_status'  => 'publish',
			];
			$this->posts[] = wp_insert_post( $postArray );
		}
	}
}
