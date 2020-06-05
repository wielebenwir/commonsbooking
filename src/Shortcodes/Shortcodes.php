<?php

namespace CommonsBooking\Shortcodes;
use CMB2;

class Shortcodes
{
    public function __construct()
    {
        
        
        /**
         * Adding all shortcodes 
         * shortcodes can be used in mail-templates or frontend-templates via shortcode-tag [shortcode]
         */
        add_shortcode('cmb-form', array($this, 'cmb2_do_frontend_form_shortcode' ));
        add_shortcode('cb_location_pickupinstructions', array($this, 'Shortcode_cb_location_pickupinstructions' ));
    }
    
    
    
    /**
     * TODO: 
     * - Funktion, die automatisch Shortcodes für alle post-meta-felder anlegt
     * - Funktion die 
     * 
     */

    
    /**
     * Render all shortcote in a given string, replaces shortcodes with shortcode-value and returns the replaced shortcodes
     *
     * @param  mixed $string
     * @return void
     */
    public static function getRenderedShortcodes($content)
    {
        global $shortcode_tags;
        
        if (preg_match_all('/' . get_shortcode_regex() . '/s', $content, $matches))
        {
            var_dump($matches[2]);
            foreach ($matches[2] as $i => $sc) {
                $shortcode = $matches[0][$i];
                var_dump($shortcode);
                $replace = do_shortcode($shortcode);
                $content = str_replace($shortcode, $replace, $content);
                var_dump($content);
            }
        }
        return $content;
    }


    /**
     * Shortcode to display a CMB2 form for a post ID.
     * @param  array  $atts Shortcode attributes
     * @return string       Form HTML markup
     */
    public static function cmb2_do_frontend_form_shortcode( $atts = array() ) {
        global $post;

        /**
         * Depending on your setup, check if the user has permissions to edit_posts
         */
        //if ( ! current_user_can( 'edit_posts' ) ) {
        //    return __( 'You do not have permissions to edit this post.', 'lang_domain' );
       // }

        /**
         * Make sure a WordPress post ID is set.
         * We'll default to the current post/page
         */
        if ( ! isset( $atts['post_id'] ) ) {
            $atts['post_id'] = $post->ID;
        }

        // If no metabox id is set, yell about it
        if ( empty( $atts['id'] ) ) {
            return __( "Please add an 'id' attribute to specify the CMB2 form to display.", 'lang_domain' );
        }

        $metabox_id = esc_attr( $atts['id'] );
        $object_id = absint( $atts['post_id'] );
        // Get our form
        //$form = \cmb2_get_metabox_form( $metabox_id, $object_id );
        $form = get_post_meta($object_id, $metabox_id, true);

        return $form;
    }

    public static function Shortcode_cb_location_pickupinstructions() {
        $timeframe = get_post($_GET['cb_timeframe']);
        $locationID = get_post_meta($timeframe->ID,'location-id');
        $postmeta = get_post_meta($locationID[0], '_cb_location_pickupinstructions');  

        return $postmeta[0];
    }
}