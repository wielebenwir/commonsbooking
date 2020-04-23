<?php


namespace CommonsBooking\View;


use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use Twig\TwigFilter;

class View
{

    protected static function getTwigLoader() {
        return new \Twig\Loader\FilesystemLoader(COMMONSBOOKING__PLUGIN_DIR . "Resources" . DIRECTORY_SEPARATOR . "views");
    }

    protected static function getTwig() {
        $options = [];
        if(!WP_DEBUG) {
            $options = [
                'cache' => COMMONSBOOKING__PLUGIN_DIR . 'cache'
            ] ;
        }
        $twig = new \Twig\Environment(
            static::getTwigLoader(),
            $options
        );
        $metaLoader = new TwigFilter('get_meta_field', function($post, $field) {
            return get_post_meta($post->ID,$field, true);
        });
        $twig->addFilter($metaLoader);

        $typeLoader = new TwigFilter('get_type_label', function($post) {
            return Timeframe::getTypeLabel(get_post_meta($post->ID, 'type', true));
        });
        $twig->addFilter($typeLoader);

        return $twig;
    }

    public static function render($template, $params) {
        return static::getTwig()->render($template, $params);
    }

}
