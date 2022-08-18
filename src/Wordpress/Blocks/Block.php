<?php

namespace CommonsBooking\Wordpress\Blocks;

use CommonsBooking\View\Item;

class Block {

    const COMMONSBOOKING_BLOCK_LOCATION = COMMONSBOOKING_PLUGIN_DIR . 'assets/blocks/build';

    private $blockName;

    function __construct($blockName)
    {
        $this->blockName = $blockName;
        if ( function_exists( 'register_block_type' ) ) {
            register_block_type(
                self::COMMONSBOOKING_BLOCK_LOCATION . '/' . $this->blockName,
                array(
					'category'        => COMMONSBOOKING_PLUGIN_SLUG . '_category',
                    'render_callback' => array($this, 'template_callback')
                )
            );
        }
    }

    function template_callback($attributes){
	    $queryArgs = [
		    'p' => '',
		    'author' => $attributes['selectedAuthor'],
		    'author_name'   => '',
		    'cat'   => '',
		    'category_name' => '',
		    'category_slug' => array(),
		    'tag'   => '',
		    'tag_id'    => '',
		    'post_status'   => 'publish',
		    'posts_per_page'    => '',
		    'nopaging'  => '',
		    'offset'    => '',
		    'order' => $attributes['order'],
		    'orderby'   => $attributes['orderBy'],
	    ];

	    if ( ! empty( $attributes['categories'] ) ) {
			foreach ($attributes['categories'] as $category) {
				$queryArgs['category_slug'][] = $category['slug']; //throws error: https://wordpress.org/support/topic/gutenberg-attributes-for-category-not-always-containing-slug-taxonomy/
			}
	    }
	    if ( isset( $attributes['selectedAuthor'] ) ) {
		    $args['author'] = $attributes['selectedAuthor'];
	    }

		return Item::shortcode($queryArgs);
    }

    public static function init(){
	    add_filter( 'block_categories_all' , function( $categories ) {

		    // Adding a new category.
		    $categories[] = array(
			    'slug'  => COMMONSBOOKING_PLUGIN_SLUG . '_category',
			    'title' => COMMONSBOOKING_PLUGIN_SLUG
		    );

		    return $categories;
	    } );

        New Block('cb-items');
    }
    
}