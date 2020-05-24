<?php


namespace CommonsBooking\View;


use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use Twig\TwigFilter;

abstract class View
{

    protected static function getTwigLoader() {
        return new \Twig\Loader\FilesystemLoader(CB_PLUGIN_DIR . "Resources" . DIRECTORY_SEPARATOR . "views");
    }

    protected static function getTwig() {
        $options = [];
        if(!WP_DEBUG) {
            $options = [
                'cache' => CB_PLUGIN_DIR . 'cache'
            ] ;
        }
        $twig = new \Twig\Environment(
            static::getTwigLoader(),
            $options
        );
        $metaLoader = new TwigFilter('get_meta_field', function($post, $field) {
            return get_post_meta($post->ID, $field, true);
        });
        $twig->addFilter($metaLoader);

        $typeLoader = new TwigFilter('get_type_label', function($post) {
            return Timeframe::getTypeLabel(get_post_meta($post->ID, 'type', true));
        });
        $twig->addFilter($typeLoader);

        $thumbLoader = new TwigFilter('get_thumbnail', function($post) {
            return get_the_post_thumbnail( $post->ID, 'thumbnail' );
        });
        $twig->addFilter($thumbLoader);

        $detailLinkLoader = new TwigFilter('get_link', function($post) {
            if($post instanceof \WP_Post) {
                return get_permalink( $post->ID);
            }
            if($post instanceof \WP_User) {
                return get_edit_user_link( $post->ID);
            }
        });
        $twig->addFilter($detailLinkLoader);

        return $twig;
    }

    public static function render($template, $params) {
        return static::getTwig()->render($template, $params);
    }

}
