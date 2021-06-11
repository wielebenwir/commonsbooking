<?php


namespace CommonsBooking\Wordpress\CustomPostType;

use CommonsBooking\Plugin;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Wordpress\MetaBox\Field;

abstract class CustomPostType
{

    /**
     * @var string
     */
    public static $postType;

    /**
     * @var
     */
    protected $menuPosition;

    /**
     * @return mixed
     */
    abstract public function getArgs();

    /**
     * @return string
     */
    public static function getPostType()
    {
        return static::$postType;
    }

    /**
     * Returns param for backend menu.
     * @return array
     */
    public function getMenuParams()
    {
        return [
            'cb-dashboard',
            $this->getArgs()['labels']['name'],
            $this->getArgs()['labels']['name'],
            'manage_' . COMMONSBOOKING_PLUGIN_SLUG,
            'edit.php?post_type=' . static::getPostType(),
            '',
            $this->menuPosition ?: null
        ];
    }

    /**
     * Remove the default Custom Fields meta box
     *
     * @param string $post_type
     * @param string $context
     * @param WP_Post|object|string $post
     */
    public function removeDefaultCustomFields($post_type, $context, $post)
    {
        foreach (array('normal', 'advanced', 'side') as $context) {
            remove_meta_box('postcustom', static::getPostType(), $context);
        }
    }

    /**
     * @return string
     */
    public static function getWPAction()
    {
        return static::getPostType() . "-custom-fields";
    }

    /**
     * @return string
     */
    public static function getWPNonceId()
    {
        return static::getPostType() . "-custom-fields" . '_wpnonce';
    }

    /**
     * @return mixed
     */
    public static function getWPNonceField()
    {
        return wp_nonce_field(static::getWPAction(), static::getWPNonceId(), false, true);
    }

    /**
     * Replaces WP_Posts by their title for options array.
     * @param $data
     *
     * @return array
     */
    public static function sanitizeOptions($data) {
        $options = [];
        if($data) {
            foreach ($data as $key => $item) {
                if($item instanceof \WP_Post) {
                    $key = $item->ID;
                    $label = $item->post_title;
                } else {
                    $label = $item;
                }
                $options[$key] = $label;
            }
        }
        return $options;
    }

    /**
     * Manages custom columns for list view.
     *
     * @param $columns
     *
     * @return mixed
     */
    public function setCustomColumns($columns)
    {
        if (isset($this->listColumns)) {
            foreach ($this->listColumns as $key => $label) {
                $columns[$key] = $label;
            }
        }

        return $columns;
    }

    /**
     * @param $columns
     *
     * @return mixed
     */
    public function setSortableColumns($columns)
    {
        if (isset($this->listColumns)) {
            foreach ($this->listColumns as $key => $label) {
                $columns[$key] = $key;
            }
        }

        return $columns;
    }

    /**
     * Removes title column from backend listing.
     */
    public function removeListTitleColumn()
    {
        add_filter('manage_' . static::getPostType() . '_posts_columns', function ($columns) {
            unset($columns['title']);
            return $columns;
        });
    }

    /**
     * Removes date column from backend listing.
     */
    public function removeListDateColumn()
    {
        add_filter('manage_' . static::getPostType() . '_posts_columns', function ($columns) {
            unset($columns['date']);
            unset ($columns[ 'author' ]); // = 'Nutzer*in';
            return $columns;
        });
    }

    /**
     * Configures list-view
     */
    public function initListView()
    {
        // List-View
        add_filter('manage_' . static::getPostType() . '_posts_columns', array($this, 'setCustomColumns'));
        add_action('manage_' . static::getPostType() . '_posts_custom_column', array($this, 'setCustomColumnsData'), 10,
            2);
        add_filter('manage_edit-' . static::getPostType() . '_sortable_columns', array($this, 'setSortableColumns'));
        if (isset($this->listColumns)) {
            add_action('pre_get_posts', function ($query) {
                if (!is_admin()) {
                    return;
                }

                $orderby = $query->get('orderby');
                if (
                    strpos($orderby, 'post_') === false &&
                    in_array($orderby, array_keys($this->listColumns))
                ) {
                    $query->set('meta_key', $orderby);
                    $query->set('orderby', 'meta_value');
                }
            });
        }
    }

    /**
     * Adds data to custom columns
     *
     * @param $column
     * @param $post_id
     */
    public function setCustomColumnsData($column, $post_id)
    {

        if ($value = get_post_meta($post_id, $column, true)) {
            echo $value;
        } else {
            if ( property_exists($post = get_post($post_id), $column)) {
                echo $post->{$column};
            } else {
                echo '-';
            }
        }
    }
    
    /**
     * retrieve Custom Meta Data from CommonsBooking Options and convert them to cmb2 fields array
     *
     * @param  mixed $type (item or location)
     * @return array
     */
    public static function getCMB2FieldsArrayFromCustomMetadata($type) {

        $metaDataRaw = array();
        $metaDataRaw = Settings::getOption(COMMONSBOOKING_PLUGIN_SLUG . '_options_metadata', 'metadata');

        $metaDataLines = explode("\r\n", $metaDataRaw);

        $metaDataArray = array();
        $metaDataFields = array();

        foreach ($metaDataLines as $metaDataLine) {
            $metaDataArray = explode(';', $metaDataLine);

            // $metaDataArray[0] = Type
            $metaDataFields[$metaDataArray[0]][] = array(
                'id'        => $metaDataArray[1],
                'name'      => $metaDataArray[2],
                'type'      => $metaDataArray[3],
                'desc'      => commonsbooking_sanitizeHTML( __($metaDataArray[4], 'commonsbooking') ),
            );    
        }

        if (array_key_exists( $type, $metaDataFields) ) {
            return $metaDataFields[$type];
        }
    } 

    /**
     * @return mixed
     */
    abstract public static function getView();

}
