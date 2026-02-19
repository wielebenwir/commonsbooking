<?php

/**
 * Runs the cb_tag shortcode to output parsed template tag.
 * @param array $atts
 */
function commonsbooking_tag( $atts ) {
	$atts = shortcode_atts(
		array(
			'tag' => '',
		),
		$atts,
		'cb'
	);

	echo commonsbooking_sanitizeHTML( commonsbooking_parse_shortcode( $atts['tag'] ) );
}

add_shortcode( 'cb', 'commonsbooking_tag' );
