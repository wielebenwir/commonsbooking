<?php

use CommonsBooking\CB\CB;

function cb_parse_template( String $template='' ) {
  $template = preg_replace_callback('/\{{.*?\}}/', 'cb_parse_template_callback', $template);
  return apply_filters( 'cb_template_tag', $template ); 
}

function cb_parse_shortcode( $tag ) {
  $tag = (array) $tag; 
  return cb_parse_template_callback ( $tag );
}

function cb_parse_template_callback( $match ) {
    $match = $match[0];
    $match = sanitize_key( $match );
    $path = explode( '_', $match, 2);  
    $replacement = CB::get( $path[0], $path[1]);
    return $replacement; 
}
