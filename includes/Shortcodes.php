<?php

function cbtag($atts)
{
    $atts = shortcode_atts(array(
        'tag' => '',
    ), $atts, 'cb');

    echo cb_parse_shortcode($atts['tag']);
}

add_shortcode('cb', 'cbtag');

/**
* cb_items shortcode
* 
* A list of items with timeframes.
*/
function cb_items_shortcode_func($atts)
{
    $item_args = array (
        'post_type'    => 'cb_item'
    );
    
    $defaults = array (
        // @TODO set reasonable defaults
    );
    
    $args = array_merge( $defaults, $item_args );
    $atts = shortcode_atts( $args, $atts, 'cb_items');
    
    $query_args = array_intersect_key( $args, $atts );
    
    global $post;
    $query = new WP_Query($query_args);
    $posts = $query->posts;
    
    if ( ! $posts ) {
        return __('No items.', 'commonsbooking');
    }
    
    ob_start();
    echo '<div class="cb-content">';
    foreach ( $posts as $post ) {
        setup_postdata( $post );
        cb_get_template_part('shortcode', 'items', TRUE, FALSE, FALSE ); 
    }
    echo '</div>';
    return ob_get_clean();
    
}
add_shortcode('cb_items', 'cb_items_shortcode_func');


/**
* cb_locations shortcode
* 
* A list of locations with timeframes.
*/
function cb_locations_shortcode_func($atts)
{
    $item_args = array (
        'post_type'    => 'cb_location'
    );
    
    $defaults = array (
        // @TODO set defaults
    );
    
    $args = array_merge( $defaults, $item_args );
    $atts = shortcode_atts( $args, $atts, 'cb_locations');
    
    $query_args = array_intersect_key( $args, $atts );
    
    global $post;
    $query = new WP_Query( $query_args );
    $posts = $query->posts;
    
    if ( ! $posts ) {
        return __('No locations.', 'commonsbooking');
    }
    
    ob_start();
    echo '<div class="cb-content">';
    foreach ( $posts as $post ) {
        setup_postdata( $post );
        cb_get_template_part('shortcode', 'locations', TRUE, FALSE, FALSE ); 
    }
    echo '</div>';
    return ob_get_clean();
    
}
add_shortcode('cb_locations', 'cb_locations_shortcode_func');