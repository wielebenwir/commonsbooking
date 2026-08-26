<?php
/**
 * Prefix php-font-lib class names that are assembled dynamically.
 *
 * Strauss prefixes names that it can identify statically. php-font-lib also
 * constructs two class names at runtime and derives a font type from its
 * original namespace depth, so these expressions need a small, deterministic
 * post-processing step.
 */

( static function (): void {
	$projectRoot  = dirname( __DIR__ );
	$replacements = [
		'vendor-prefixed/dompdf/php-font-lib/src/FontLib/Font.php' => [
			'$class = "FontLib\\\\$class";' => '$class = "CommonsBooking\\\\FontLib\\\\$class";',
		],
		'vendor-prefixed/dompdf/php-font-lib/src/FontLib/TrueType/File.php' => [
			'return $class_parts[1];' => 'return $class_parts[2];',
			'$class = "FontLib\\\\$type\\\\TableDirectoryEntry";' => '$class = "CommonsBooking\\\\FontLib\\\\$type\\\\TableDirectoryEntry";',
		],
	];

	foreach ( $replacements as $relativePath => $fileReplacements ) {
		$path    = $projectRoot . '/' . $relativePath;
		$content = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( false === $content ) {
			throw new RuntimeException( sprintf( 'Unable to read %s.', $relativePath ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$updatedContent = $content;

		foreach ( $fileReplacements as $search => $replacement ) {
			$searchCount      = substr_count( $updatedContent, $search );
			$replacementCount = substr_count( $updatedContent, $replacement );

			if ( 0 === $searchCount && 1 === $replacementCount ) {
				continue;
			}

			if ( 1 !== $searchCount || 0 !== $replacementCount ) {
				// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- This is a CLI-only build failure.
				throw new RuntimeException(
					sprintf(
						'Expected one unprefixed and no prefixed match in %s; found %d and %d.',
						$relativePath,
						$searchCount,
						$replacementCount
					)
				);
				// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
			}

			$updatedContent = str_replace( $search, $replacement, $updatedContent );
		}

		if ( $updatedContent !== $content && false === file_put_contents( $path, $updatedContent ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			throw new RuntimeException( sprintf( 'Unable to write %s.', $relativePath ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
	}
} )();
