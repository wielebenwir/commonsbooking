<?php


namespace CommonsBooking\View;


use CommonsBooking\Model\Day;
use CommonsBooking\Model\Week;
use CommonsBooking\PostType\Item;
use CommonsBooking\PostType\Location;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Dashboard
{

    public static function render() {
        $loader = new \Twig\Loader\FilesystemLoader(COMMONSBOOKING__PLUGIN_DIR . "templates");
        $twig = new \Twig\Environment($loader/*, [ 'cache' => '/path/to/compilation_cache']*/);

        $metaLoader = new TwigFilter('get_meta_field', function($post, $field) {
            return get_post_meta($post->ID,$field, true);
        });
        $twig->addFilter($metaLoader);

        echo $twig->render('dashboard/index.html.twig',
            [
                'locations' => Location::getAllPosts(),
                'items' => Item::getAllPosts(),
                'week' => new Week(15)
            ]
        );
    }

}
