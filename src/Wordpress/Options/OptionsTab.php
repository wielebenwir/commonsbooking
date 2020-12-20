<?php

namespace CommonsBooking\Wordpress\Options;

use CommonsBooking\Settings\Settings;

class OptionsTab
{

    public $option_key = COMMONSBOOKING_PLUGIN_SLUG . '_options';
    public $id;
    public $tab_title;
    public $content;
    public $groups;

    public function __construct(string $id, array $content)
    {
        $this->id = $id;
        $this->content = $content;
        $this->groups = $content['field_groups'];
        $this->tab_title = $this->content['title'];

        add_action('cmb2_admin_init', array($this, 'register'));
    }

    public function register()
    {
        $this->registerOptionsTab();
        $this->registerOptionsGroups();
    }

    /**
     * Register Tab
     */
    public function registerOptionsTab()
    {

        $default_args = array(
            'id' => $this->id,
            'title' => esc_html__('CommonsBooking', 'commonsbooking'),
            'object_types' => array('options-page'),
            'option_key' => $this->option_key . '_' . $this->id,
            'tab_group' => $this->option_key,
            'tab_title' => $this->tab_title,
            'parent_slug' => $this->option_key
        );

        $top_level_args = array(
            'option_key' => $this->option_key,
            'parent_slug' => 'options-general.php'
        );

        /* set first option as top level parent */
        if (isset ($this->content['is_top_level']) && $this->content['is_top_level']) {
            $args = array_merge($default_args, $top_level_args);
        } else {
            $args = $default_args;
        }

        $this->metabox = new_cmb2_box($args);
    }

    /**
     * Register Tab Contents (Groups + Fields)
     */
    public function registerOptionsGroups()
    {

        foreach ($this->groups as $group_id => $group) {

            $group = $this->prependTitle($group); /* prepend title + description html */

            // Add Fields
            $fields = $group['fields'];
            foreach ($fields as $field) {
                $this->metabox->add_field($field);
            }
        }
    }
    
    /**
     * set default option values if option field is empty and a default value is set in OptionsArray.php
     * TODO: needs to be checked and optimized - we need to call this on plugin activation hook / its not called right now from anywhere
     *
     * @return void
     */
    public function setDefaultPluginOptions() {

        foreach ($this->groups as $group_id => $group) {

            $fields = $group['fields'];
            $option_key = $this->option_key . '_' . $this->id;
            $option = array();
            
            foreach ($fields as $field) {
                
                // we check if there there is a default value for this field
                if (array_key_exists( 'default', $field ) ) {
                    // if field-value is not set already we add the default value to the options array
                    if ( empty ( Settings::getOption($option_key, $field['id'] ) ) ) {
                        $option[$field['id']] = $field['default'];
                    }
                }
            }

            // update option 
            if (!empty ( $option ) ) {
                update_option($option_key, $option);
            }
        }
    }

    /**
     * If array contains title or description, create a new row contaning this text
     *
     * @param array $metabox_group
     * @return array $metabox_group with title + description added as row
     */
    public static function prependTitle($metabox_group)
    {

        if (isset ($metabox_group['title']) OR isset ($metabox_group['desc'])) {

            $title = isset($metabox_group['title']) ? $metabox_group['title'] : '';
            $desc = isset($metabox_group['desc']) ? $metabox_group['desc'] : '';

            $header_html = sprintf(
                '<h4>%s</h4>%s', $title, $desc
            );

            $header_field = array(
                'id' => $metabox_group['id'] . '_header',
                'desc' => $header_html,
                'type' => 'title',
                'classes' => 'cb_form_title',
            );

            array_unshift($metabox_group['fields'], $header_field);
        }

        return $metabox_group;
    }
}
