<?php


namespace CommonsBooking\View;


use Twig\TwigFilter;

class View
{

    protected static function getTwigLoader() {
        return new \Twig\Loader\FilesystemLoader(COMMONSBOOKING__PLUGIN_DIR . "templates");
    }

    protected static function getTwig() {
        $twig = new \Twig\Environment(static::getTwigLoader());
        $metaLoader = new TwigFilter('get_meta_field', function($post, $field) {
            return get_post_meta($post->ID,$field, true);
        });
        $twig->addFilter($metaLoader);

        return $twig;
    }

    public static function render($template, $params) {
        return static::getTwig()->render($template, $params);
    }

}
