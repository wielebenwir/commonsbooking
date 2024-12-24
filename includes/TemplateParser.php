<?php

use CommonsBooking\CB\CB;

/**
 * Parses templates and extracts the template tags used in e-mail templates: {{xxx:yyyy}}
 *
 * @param string $template
 * @param array  $objects
 * @param callable $sanitizeFunction The callable used to remove unwanted tags/characters (use default 'commonsbooking_sanitizeHTML' or 'sanitize_text_field')
 *
 * @return mixed
 */
function commonsbooking_parse_template( string $template = '', $objects = [], $sanitizeFunction = 'commonsbooking_sanitizeHTML' ) {
	$template = preg_replace_callback(
		'/\{{.*?\}}/',
		function ( $match ) use ( $objects, $sanitizeFunction ) {
			return commonsbooking_parse_template_callback( $match, $objects, $sanitizeFunction );
		},
		$template
	);

    // template is checked recursively to support templates tags within custom fields that are for example added to items or locations
    // why? users can add e.g. inidvidual booking-mail texts per location by adding a custom field like 'custom_booking_message' and use all avaiable template tags within this custom field
    if ( preg_match_all( '/{{.*?}}/', $template ) === 0 ) {
        return apply_filters( 'commonsbooking_template_tag', $template );
    } else {
        return commonsbooking_parse_template( $template, $objects, $sanitizeFunction );
    }
}

function commonsbooking_parse_shortcode( $tag ) {
	$tag = (array) $tag;
	return commonsbooking_parse_template_callback( $tag );
}

/**
 * Extracts the template tag parts divided by : or # and replaces the tag with values using the CB::get method
 *
 * Renders html before and after the template tag if it is given by using [html text] before or after the template tag
 * Example: {{[this comes before: ]item:post_title[this comes after]}}
 *
 * @param mixed $match
 * @param array $objects
 * @param callable $sanitizeFunction The callable used to remove unwanted tags/characters
 *
 * @return false|mixed
 */
function commonsbooking_parse_template_callback( $match, array $objects = [], $sanitizeFunction = 'commonsbooking_sanitizeHTML' ) {

    if ( isset( $match[0] ) ) {
        $match = $match[0];

        // extract the html before part, looking for {{[*] pattern
        if ( preg_match( '/\{\{\[([^\]]*)\]/m', $match, $html_before ) === 1 ) {
            $html_before = preg_replace( '/(\{\{)|(\}\})|(\[)|(\])/m', '', $html_before[1] );
            $html_before = call_user_func( $sanitizeFunction, $html_before );
        } else {
            $html_before = '';
        }

        // extract the html after part looking for [*]}} pattern
        if ( preg_match( '/\[([^\]]*)\]\}\}/m', $match, $html_after ) === 1 ) {
            $html_after = preg_replace( '/(\{\{)|(\}\})|(\[)|(\])/m', '', $html_after[1] );
            $html_after = call_user_func( $sanitizeFunction, $html_after );
        } else {
            $html_after = '';
        }

        // remove string between the [  ] control delimiters
        $match = preg_replace( '/\[[^\]]*\]/m', '', $match );

        // remove the {{  }} control delimiters
        $match = preg_replace( '/(\{\{)|(\}\})/m', '', $match );

        // remove whitspace
        $match = trim( $match );

        // we accept : and # as separator cause the : delimiter wasn't working when using the template tag in a href links in the template (like <a href="{{xxx#yyyy}}"></a>)
        $path = preg_split( '/(\:|\#)/', $match, 2 );
        if ( isset( $path[0] ) && isset( $path[1] ) ) {

            $post = null;
            if ( array_key_exists( $path[0], $objects ) ) {
                $post = $objects[ $path[0] ];
            }

            $rendered_template_tag = CB::get( commonsbooking_getCBType( $path[0] ), $path[1], $post, null, $sanitizeFunction );
            if ( $rendered_template_tag !== null && strlen( $rendered_template_tag ) > 0 ) {
                return $html_before . $rendered_template_tag . $html_after;
            } else {
                return $rendered_template_tag;
            }
        }

        return false;
    }
}

/**
 *  Return Custom Post Type postType for template type string
 *
 * @param string $type type could be location, booking, item
 *
 * @return string
 */
function commonsbooking_getCBType( $type ) {
	if ( $type == 'location' ) {
		return \CommonsBooking\Wordpress\CustomPostType\Location::$postType;
	}
	if ( $type == 'booking' ) {
		return \CommonsBooking\Wordpress\CustomPostType\Booking::$postType;
	}
	if ( $type == 'item' ) {
		return \CommonsBooking\Wordpress\CustomPostType\Item::$postType;
	}
	return $type;
}
