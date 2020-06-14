<?php


namespace CommonsBooking\Wordpress\CustomPostType;

abstract class CustomPostType
{

    public static $postType;

    protected $customFields;

    protected $menuPosition;

    protected $listFields = [];

    abstract public function getArgs();

    public static function getPostType()
    {
        return static::$postType;
    }

    public function getMenuParams()
    {
        return [
            'cb-dashboard',
            $this->getArgs()['labels']['name'],
            $this->getArgs()['labels']['name'],
            'manage_options',
            'edit.php?post_type=' . static::getPostType(),
            '',
            $this->menuPosition ?: null
        ];
    }

    /**
     * Remove the default Custom Fields meta box
     */
    public function removeDefaultCustomFields($type, $context, $post)
    {
        foreach (array('normal', 'advanced', 'side') as $context) {
            remove_meta_box('postcustom', static::getPostType(), $context);
        }
    }

    /**
     * @deprecated Defined in getArgs-function of CTP
     * https://knowthecode.io/docx/wordpress/remove_post_type_support
     */
    public function removeAllFormFields()
    {
        $this->removeFormTitle();
        $this->removeFormDescription();
        $this->removeFormImage();
    }

    /**
     * @deprecated Defined in getArgs-function of CTP
     */
    public function removeFormTitle()
    {
        remove_post_type_support(static::getPostType(), 'title');
    }

    /**
     * @deprecated Defined in getArgs-function of CTP
     */
    public function removeFormDescription()
    {
        remove_post_type_support(static::getPostType(), 'editor');
    }

    /**
     * @deprecated Defined in getArgs-function of CTP
     */
    public function removeFormImage()
    {
        remove_post_type_support(static::getPostType(), 'thumbnail');
    }

    public static function getWPAction()
    {
        return static::getPostType() . "-custom-fields";
    }

    public static function getWPNonceId()
    {
        return static::getPostType() . "-custom-fields" . '_wpnonce';
    }

    public static function getWPNonceField()
    {
        return wp_nonce_field(static::getWPAction(), static::getWPNonceId(), false, true);
    }

    /**
     * Save the new Custom Fields values
     */
    public function saveCustomFields($post_id, $post)
    {
        if (
            !isset($_REQUEST[static::getWPNonceId()]) ||
            !wp_verify_nonce($_REQUEST[static::getWPNonceId()], static::getWPAction())
        ) {
            return;
        }
//        if (!current_user_can('edit_post', $post_id)) {
//            return;
//        }
        if ($post->post_type !== static::getPostType()) {
            return;
        }

        $noDeleteMetaFields = ['start-time', 'end-time', 'timeframe-repetition'];

        /** @var Field $customField */
        foreach ($this->getCustomFields() as $customField) {
            if (current_user_can($customField->getCapability(), $post_id)) {
                $fieldNames = [];
                if ($customField->getType() == "checkboxes") {
                    $fieldNames = $customField->getOptionFieldNames();
                } else {
                    $fieldNames[] = $customField->getName();
                }

                foreach ($fieldNames as $fieldName) {
                    if (isset($_REQUEST[$fieldName]) && $value = trim($_REQUEST[$fieldName])) {
                        // Auto-paragraphs for any WYSIWYG
                        if ($customField->getType() == "wysiwyg") {
                            $value = wpautop($value);
                        }
                        update_post_meta($post_id, $fieldName, $value);

                        // Update time-fields by date-fields
                        if(in_array($fieldName, ['start-date', 'end-date'])) {
                            update_post_meta(
                                $post_id,
                                str_replace('date','time', $fieldName),
                                date('h:i A', $value)
                            );
                        }

                        // if we have a booking, there shall be set no repetition
                        if($fieldName == "type" && $value == Timeframe::BOOKING_ID) {
                            update_post_meta($post_id, 'timeframe-repetition', 'norep');
                        }

                    } else {
                        if(!in_array($fieldName, $noDeleteMetaFields)) {
                            delete_post_meta($post_id, $fieldName);
                        }
                    }
                }
            }
        }
    }

    public function registerMetabox() {
        $cmb = new_cmb2_box([
            'id' => static::getPostType() . "-custom-fields",
            'title' => "Timeframe",
            'object_types' => array(static::getPostType())
        ]);

        /** @var Field $customField */
        foreach ($this->getCustomFields() as $customField) {
            $cmb->add_field( $customField->getParamsArray());
        }
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

    public function setSortableColumns($columns)
    {
        if (isset($this->listColumns)) {
            foreach ($this->listColumns as $key => $label) {
                $columns[$key] = $key;
            }
        }

        return $columns;
    }

    public function removeListTitleColumn()
    {
        add_filter('manage_' . static::getPostType() . '_posts_columns', function ($columns) {
            unset($columns['title']);
            return $columns;
        });
    }

    public function removeListDateColumn()
    {
        add_filter('manage_' . static::getPostType() . '_posts_columns', function ($columns) {
            unset($columns['date']);
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
                if (in_array($orderby, array_keys($this->listColumns))) {
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
            echo '-';
        }
    }

    public static function getAllPostsQuery($order = 'ASC')
    {
        $args = array(
            'post_type' => static::getPostType(),
            'order' => $order
        );

        return new \WP_Query($args);
    }

    public static function getAllPosts($order = 'ASC')
    {
        /** @var \WP_Query $query */
        $query = static::getAllPostsQuery();
        $posts = [];
        if ($query->have_posts()) {
            $posts = $query->get_posts();
        }

        return $posts;
    }

    abstract public static function getView();

}
