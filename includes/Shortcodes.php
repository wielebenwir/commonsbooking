<?php

/**
 * Runs the cb_tag shortcode to output parsed template tag.
 *
 * WordPress versions before 6.5 pass an empty string instead of an array
 * to shortcode callbacks when no attributes were given.
 *
 * @param array<string, string>|string $atts
 * @throws Exception
 */
function commonsbooking_tag( array|string $atts ): void {
	$atts = is_array( $atts ) ? $atts : [];
	$atts = shortcode_atts(
		array(
			'tag' => '',
		),
		$atts,
		'cb'
	);

	echo commonsbooking_sanitizeHTML( commonsbooking_parse_shortcode( $atts['tag'] ) );
}

// @phpstan-ignore argument.type (callback echoes instead of returning; see issue #2405: https://github.com/wielebenwir/commonsbooking/issues/2405)
add_shortcode( 'cb', 'commonsbooking_tag' );
