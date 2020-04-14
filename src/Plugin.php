<?php


namespace CommonsBooking;

use CommonsBooking\Form\Timeframe;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;

class Plugin
{
    /**
     * @return mixed
     */
    public static function getCustomPostTypes()
    {
        return [
            new \CommonsBooking\PostType\Item(),
            new \CommonsBooking\PostType\Location()
        ];
    }

    /**
     *
     */
    public function init() {
        // Register custom post types
        add_action('init', array(self::class, 'registerCustomPostTypes'));

        // Add menu pages
        add_action( 'admin_menu', array(self::class, 'addMenuPages'));

    }

    /**
     * @return EntityManager
     * @throws \Doctrine\ORM\ORMException
     */
    public static function getEntityManager() {

        $paths = array(__DIR__ . "/Entity");
        $isDevMode = false;

        // the connection configuration
        $dbParams = array(
            'driver'   => 'pdo_mysql',
            'dbname' => DB_NAME,
            'user' => DB_USER,
            'password' => DB_PASSWORD,
            'host' => DB_HOST,
        );


        $config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode, null, null, false);

        return EntityManager::create($dbParams, $config);
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    public function initTables() {
        $em = self::getEntityManager();
        $tool = new SchemaTool($em);
        $classes = array(
            $em->getClassMetadata(\CommonsBooking\Entity\Timeframe::class)
        );

        $tool->updateSchema($classes, true);
    }

    /**
     *
     */
    public static function addMenuPages() {
        // Dashboard
        add_menu_page(
            'Commons Booking',
            'Commons Booking',
            'manage_options',
            'cb-dashboard',
            array(\CommonsBooking\View\Dashboard::class, 'render')
        );

        add_submenu_page(
            'cb-dashboard',
            'Timeframes',
            'Timeframes',
            'manage_options',
            'cb-timeframes',
            array(Timeframe::class, 'render')
        );

        // Custom post types
        foreach (self::getCustomPostTypes() as $cbCustomPostType) {
            $params = $cbCustomPostType->getMenuParams();
            add_submenu_page(
                $params[0],
                $params[1],
                $params[2],
                $params[3],
                $params[4]
            );
        }
    }

    /**
     *
     */
    public static function registerCustomPostTypes() {
        foreach (self::getCustomPostTypes() as $customPostType) {
            register_post_type( $customPostType->getPostType(), $customPostType->getArgs() );
        }
    }

}
