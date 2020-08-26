<?php

function cbtag($atts)
{
    $atts = shortcode_atts(array(
        'tag' => '',
    ), $atts, 'cb');

    echo cb_parse_shortcode($atts['tag']);
}

add_shortcode('cb', 'cbtag');


