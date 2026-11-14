<?php

namespace CommonsBooking\Tests\View;

use CommonsBooking\Tests\Wordpress\CustomPostTypeTest;
use CommonsBooking\View\View;
use CommonsBooking\Wordpress\Options\AdminOptions;

/**
 * Golden-master test: fixtures were generated once with the former scssphp implementation
 * with the scenarios below (generator since removed).
 */
class ColorCssFixtureTest extends CustomPostTypeTest {

	private const SCENARIOS = [
		'default' => [],
		'custom'  => [
			'colorscheme_primarycolor'          => '#ABCDEF',
			'colorscheme_secondarycolor'        => '#abc',
			'colorscheme_buttoncolor'           => '#FFFFFF',
			'colorscheme_acceptcolor'           => '#445566',
			'colorscheme_cancelcolor'           => '#112233',
			'colorscheme_holidaycolor'          => '#000000',
			'colorscheme_greyedoutcolor'        => '#F0F0F0',
			'colorscheme_backgroundcolor'       => '#778899',
			'colorscheme_noticebackgroundcolor' => '#AABBCCDD',
			'colorscheme_lighttext'             => '#FfFfFf',
			'colorscheme_darktext'              => '#010203',
		],
	];

	private function applyScenario( string $name ): void {
		AdminOptions::setOptionsDefaultValues();
		$options = array_merge( get_option( 'commonsbooking_options_templates' ), self::SCENARIOS[ $name ] );
		update_option( 'commonsbooking_options_templates', $options );
	}

	public static function scenarioProvider(): array {
		$names = array_keys( self::SCENARIOS );
		return array_combine( $names, array_map( fn( $n ) => [ $n ], $names ) );
	}

	/**
	 * Every variable emitted now must be identical to what scssphp produced.
	 * Variables only present in the old output must be static ones (already in public.css).
	 *
	 * @dataProvider scenarioProvider
	 */
	public function testMatchesScssphpFixture( string $name ) {
		$parse = function ( string $css ): array {
			preg_match_all( '/(--commonsbooking-[a-z-]+):\s*([^;]+);/', $css, $m );
			return array_combine( $m[1], $m[2] );
		};
		$old   = $parse( file_get_contents( __DIR__ . "/fixtures/colorcss-$name.css" ) );
		$this->applyScenario( $name );
		$new = $parse( View::getColorCSS() );

		$this->assertNotEmpty( $new );
		foreach ( $new as $property => $value ) {
			$this->assertSame( $old[ $property ], $value, $property );
		}
		foreach ( array_keys( array_diff_key( $old, $new ) ) as $property ) {
			$this->assertMatchesRegularExpression( '/font|spacer|radius|color-warning|gray-border/', $property );
		}
	}

	protected function tearDown(): void {
		delete_option( 'commonsbooking_options_templates' );
		parent::tearDown();
	}
}
