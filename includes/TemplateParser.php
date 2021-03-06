<?php

use CommonsBooking\CB\CB;

function commonsbooking_parse_template( String $template='' ) {
  $template = preg_replace_callback('/\{{.*?\}}/', 'commonsbooking_parse_template_callback', $template);
  return apply_filters( 'cb_template_tag', $template );
}

function commonsbooking_parse_shortcode( $tag ) {
  $tag = (array) $tag;
  return commonsbooking_parse_template_callback ( $tag );
}

function commonsbooking_parse_template_callback( $match ) {
    if (isset($match[0])) {
      $match = $match[0];
      $match = preg_replace('/(\{\{)|(\}\})/m', '', $match);
      $path = explode( ':', $match, 2);
      if (isset($path[0]) AND isset($path[1])) {
        $replacement = CB::get( $path[0], $path[1]);
        return $replacement;
      } else {
        return false;
      }
    } else {
      return false;
    }
}
