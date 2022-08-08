<?php

namespace CommonsBooking\Wordpress\Blocks;

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
                    'render_callback' => array($this, 'template_callback')
                )
            );
        }
    }

    function template_callback($attributes, $content, $block_instance){
       return "hello from php";
    }

    public static function init(){
        New Block('cb-items');
    }
    
}