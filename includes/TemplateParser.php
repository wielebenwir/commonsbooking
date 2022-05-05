<?php

use CommonsBooking\CB\CB;

/**
 * parses templates and extracts the template these tags used in e-mail templates: {{xxx:yyyy}}
 *
 * @param mixed $template
 *
 * @return mixed
 */
function commonsbooking_parse_template( string $template = '', $objects = [] ) {
	$template = preg_replace_callback(
		'/\{{.*?\}}/',
		function ( $match ) use ( $objects ) {
			return commonsbooking_parse_template_callback( $match, $objects );
		},
		$template
	);

	return apply_filters( 'cb_template_tag', $template );
}

function commonsbooking_parse_shortcode( $tag ) {
	$tag = (array) $tag;
	return commonsbooking_parse_template_callback( $tag );
}

/**
 * extracts the template tag parts divided by : or # and replaces the tag with values using the CB::get method
 *
 * @param mixed $match
 * @param array $objects
 *
 * @return false|mixed
 */
function commonsbooking_parse_template_callback( $match, array $objects = [] ) {

	if ( isset( $match[0] ) ) {
		$match = $match[0];
		$match = preg_replace( '/(\{\{)|(\}\})/m', '', $match );
		// we accept : and # as separator cause the : delimiter wasn't working when using the template tag in a href links in the template (like <a href="{{xxx#yyyy}}"></a>)
		$path = preg_split( '/(\:|\#)/', $match, 2 );
		if ( isset( $path[0] ) and isset( $path[1] ) ) {
			$post = null;
			if ( array_key_exists( $path[0], $objects ) ) {
				$post = $objects[ $path[0] ];
			}

			return CB::get( commonsbooking_getCBType($path[0]), $path[1], $post );
		}
	}

	return false;
}

// Return Custom Post Type postType for template type string
function commonsbooking_getCBType($type) {
	if($type == 'location') {
		return \CommonsBooking\Wordpress\CustomPostType\Location::$postType;
	}
	if($type == 'booking') {
		return \CommonsBooking\Wordpress\CustomPostType\Booking::$postType;
	}
	if($type == 'item') {
		return \CommonsBooking\Wordpress\CustomPostType\Item::$postType;
	}
	return $type;
}