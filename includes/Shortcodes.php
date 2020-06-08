<?php

class Shortcodes {
	function __construct() {
    add_shortcode( 'cb', array( $this, 'cbtag') );
  }
  
  public function cbtag( $atts ) {
    $atts = shortcode_atts( array(
        'tag' => '',
    ), $atts, 'cb' );
 
    echo cb_parse_shortcode( $atts['tag'] );
  }
}

new Shortcodes();