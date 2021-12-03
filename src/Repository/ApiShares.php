<?php


namespace CommonsBooking\Repository;


use CommonsBooking\API\Share;
use CommonsBooking\Settings\Settings;

class ApiShares
{

    /**
     * Returns all existing API shares.
     * @return Share[]
     */
    public static function getAll()
    {
        $apiSharesConfig = Settings::getOption('commonsbooking_options_api', 'api_share_group');
        $apiShares = [];

        foreach ($apiSharesConfig as $apiShare) {
            $apiShares[] = new Share(
                $apiShare['api_name'],
                $apiShare['api_enabled'],
                $apiShare['push_url'],
                $apiShare['api_key'],
	            get_bloginfo('name')
            );
        }

        return $apiShares;
    }

    /**
     * Returns share if one exists
     * @param $key
     * @return Share|void
     */
    public static function getByKey($key)
    {
        $apiShares = self::getAll();
        foreach ($apiShares as $apiShare) {
            if ($apiShare->getKey() == $key) {
                return $apiShare;
            }
        }
    }

}