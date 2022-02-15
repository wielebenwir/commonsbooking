<?php

function commonsbooking_tag( $atts ) {
	$atts = shortcode_atts( array(
		'tag' => '',
	), $atts, 'cb' );

	echo esc_html( commonsbooking_parse_shortcode( $atts['tag'] ) );
}

add_shortcode( 'cb', 'commonsbooking_tag' );


