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


        // CMB2 method to register shortcodes for all cmb2 metaboxes
        add_shortcode('cb-field', array($this, 'postmeta_shortcode'));

        // Register cb-booking shortcode 
        add_shortcode('cb-booking', array($this, 'metabox_booking_shortcodes'));
    }

    /**
     * Render all shortcodes in a given string, replaces shortcodes with shortcode-value and returns the replaced shortcodes
     * is used in handling email templates
     *
     * @param  mixed $string
     * @return void
     */
    public static function getRenderedShortcodes($content)
    {
        global $shortcode_tags;
        print "<pre>";
        var_dump($shortcode_tags);

        if (preg_match_all('/' . get_shortcode_regex() . '/s', $content, $matches)) {
            foreach ($matches[2] as $i => $sc) {
                $shortcode = $matches[0][$i];
                $replace = do_shortcode($shortcode);
                $content = str_replace($shortcode, $replace, $content);
            }
        }
        return $content;
    }


    /**
     * handles postmeta shortcodes by getting location and item post_meta in context of a given timeframe_id
     * usage [booking type=item/location id=metabox_id post_id = cb_timeframe_id]
     *
     * @param  mixed $atts
     * @return void
     */
    public static function booking_shortcodes($atts = array())
    {
        global $post;

        /**
         * Make sure a WordPress post ID is set.
         * We'll default to the current post/page
         */
        if (!isset($atts['post_id'])) {
            $atts['post_id'] = $post->ID;
        }
        $object_id = $atts['post_id'];

        // If no metabox field is set, yell about it
        if (empty($atts['field'])) {
            return __("Please add an 'field' attribute to specify the costum post meta field to display.", CB_TEXTDOMAIN);
        }

        // check if type is set and get meta and post data from item and location related to the given timeframe-id
        if (isset($atts['type']) && ($atts['type'] == "location" || $atts['type'] == "item")) {
            
            /**
             * TODO: better solution to get timeframe_id other than via GET
             */            
            if (isset($_GET['cb_timeframe'] ) )
            {
                $timeframe = get_post($_GET['cb_timeframe']); 
                $object_id = get_post_meta($timeframe->ID, $atts['type'] . '-id', true);;
            } else {
                return false;
            }
        } else {
            return __("Please add a 'type' attribute and set it to 'location' or 'item'", CB_TEXTDOMAIN);
        }

        /**
         * handle special field to get standard post attributes
         */
        if ($atts['field'] == "title") {
            $post = get_post($object_id);
            $result = $post->post_title;
        
        } else {

            $object_id = absint($object_id);
            $metabox_id = esc_attr($atts['field']);

            // Get our form
            //$form =  cmb2_get_metabox_form( $metabox_id, $object_id );
            $result = get_post_meta($object_id, $metabox_id, true);
        }

        return $result;
    }


    /**
     * Shortcode to display a CMB2 form for a post ID.
     * @param  array  $atts Shortcode attributes
     * @return string       Form HTML markup
     */
    function postmeta_shortcode($atts = array())
    {
        global $post;

        /**
         * Depending on your setup, check if the user has permissions to edit_posts
         */
        if (!current_user_can('edit_posts')) {
            return __('You do not have permissions to edit this post.', 'lang_domain');
        }

        /**
         * Make sure a WordPress post ID is set.
         * We'll default to the current post/page
         */
        if (!isset($atts['post_id'])) {
            $atts['post_id'] = $post->ID;
        }

        // If no metabox id is set, yell about it
        if (empty($atts['field'])) {
            return __("Please add an 'id' attribute to specify the CMB2 form to display.", 'lang_domain');
        }

        $metabox_id = esc_attr($atts['field']);
        $object_id = absint($atts['post_id']);
        // Get our form
        // $form = cmb2_get_metabox_form( $metabox_id, $object_id );
        $form = get_post_meta($object_id, $metabox_id, true);

        return $form;
    }
}
