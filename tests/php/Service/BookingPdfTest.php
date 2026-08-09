<?php

namespace CommonsBooking\Tests\Service;

use CommonsBooking\FontLib\Font;
use CommonsBooking\FontLib\TrueType\File;
use CommonsBooking\FontLib\TrueType\TableDirectoryEntry;
use CommonsBooking\Dompdf\Dompdf;
use PHPUnit\Framework\TestCase;

class BookingPdfTest extends TestCase {

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testPrefixedFontLibCoexistsWithLoadedUnprefixedVersion(): void {
		$this->assertTrue( class_exists( 'FontLib\\Font' ) );
		$this->assertTrue( class_exists( 'FontLib\\TrueType\\File' ) );
		$this->assertTrue( class_exists( 'FontLib\\TrueType\\TableDirectoryEntry' ) );

		$fontPath = dirname( __DIR__, 3 ) . '/vendor-prefixed/dompdf/dompdf/lib/fonts/DejaVuSans.ttf';
		$font     = Font::load( $fontPath );

		$this->assertInstanceOf( File::class, $font );
		$this->assertContainsOnlyInstancesOf( TableDirectoryEntry::class, $font->getTable() );

		$font->close();

		$pdf = new Dompdf();
		$pdf->loadHtml( '<html><body><p>FontLib coexistence</p></body></html>' );
		$pdf->render();

		$this->assertStringStartsWith( '%PDF-', $pdf->output() );
	}
}
