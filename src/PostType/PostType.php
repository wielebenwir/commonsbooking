<?php


namespace CommonsBooking\PostType;


use CommonsBooking\Form\Field;
use CommonsBooking\View\Form;
use CommonsBooking\Wordpress\MetaBox;

abstract class PostType
{

    protected $customFields;

    protected $menuPosition;

    protected $listFields = [];

    abstract public function getPostType();

    abstract public function getArgs();

    public function getMenuParams()
    {
        return [
            'cb-dashboard',
            $this->getArgs()['labels']['name'],
            $this->getArgs()['labels']['name'],
            'manage_options',
            'edit.php?post_type=' . $this->getPostType(),
            '',
            $this->menuPosition ?: null
        ];
    }

    /**
     * Remove the default Custom Fields meta box
     */
    public function removeDefaultCustomFields( $type, $context, $post ) {
        foreach ( array( 'normal', 'advanced', 'side' ) as $context ) {
            remove_meta_box( 'postcustom', $this->getPostType(), $context );
        }
    }

    /**
     * https://knowthecode.io/docx/wordpress/remove_post_type_support
     */
    public function removeAllFormFields() {
        $this->removeFormTitle();
        $this->removeFormDescription();
        $this->removeFormImage();
    }

    public function removeFormTitle() {
        remove_post_type_support( $this->getPostType(), 'title');
    }

    public function removeFormDescription() {
        remove_post_type_support( $this->getPostType(), 'editor');
    }

    public function removeFormImage() {
        remove_post_type_support( $this->getPostType(), 'thumbnail');
    }


    public function createCustomFields() {
        if ( function_exists( 'add_meta_box' ) ) {
            /** @var MetaBox $metabox */
            foreach ($this->getMetaboxes() as $metabox) {
                add_meta_box(
                    $metabox->getId(),
                    $metabox->getTitle(),
                    $metabox->getCallback(),
                    $metabox->getScreen(),
                    $metabox->getContext(),
                    $metabox->getPriority(),
                    $metabox->getCallbackArgs()
                );
            }
        }
    }

    /**
     * Save the new Custom Fields values
     */
    function saveCustomFields( $post_id, $post ) {
        if (
            !isset( $_POST[ $this->getPostType() . "-custom-fields". '_wpnonce' ] ) ||
            !wp_verify_nonce( $_POST[ $this->getPostType() . "-custom-fields". '_wpnonce' ], $this->getPostType() . "-custom-fields")
        ) {
            return;
        }
        if ( !current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        if ( $post->post_type !== $this->getPostType()){
            return;
        }

        /** @var Field $customField */
        foreach ( $this->getCustomFields() as $customField ) {
            if ( current_user_can( $customField->getCapability(), $post_id ) ) {

                if ( isset( $_POST[ $customField->getName() ] ) && trim( $_POST[ $customField->getName() ] ) ) {
                    $value = $_POST[ $customField->getName() ];
                    // Auto-paragraphs for any WYSIWYG
                    if ( $customField->getType() == "wysiwyg" ) $value = wpautop( $value );
                    update_post_meta( $post_id, $customField->getName(), $value );
                } else {
                    delete_post_meta( $post_id, $customField->getName() );
                }
            }
        }

    }

    public function renderMetabox($post, $args){
        global $post;
        ?>
        <div class="form-wrap">
            <?php
            wp_nonce_field( $this->getPostType() . "-custom-fields", $this->getPostType() . "-custom-fields". '_wpnonce', false, true );

            /** @var Field $customField */
            foreach ($this->getCustomFields() as $customField) {

                $output = true;
                // Check capability
                if ( !current_user_can( $customField->getCapability(), $post->ID ) )
                    $output = false;
                // Output if allowed
                if ( $output ) {
                    Form::renderField($post, $customField);
                }
            }
            ?>
        </div>
        <?php
    }

    /**
     * Manages custom columns for list view.
     * @param $columns
     *
     * @return mixed
     */
    public function setCustomColumns($columns) {

        foreach($this->listColumns as $key => $label) {
            $columns[$key] = $label;
        }

        return $columns;
    }

    public function setSortableColumns($columns) {
        foreach($this->listColumns as $key => $label) {
            $columns[$key] = $key;
        }
        return $columns;
    }

    public function removeListTitleColumn() {
        add_filter( 'manage_' . $this->getPostType() . '_posts_columns', function($columns) {
            unset($columns['title']);
            return $columns;
        });
    }

    public function removeListDateColumn() {
        add_filter( 'manage_' . $this->getPostType() . '_posts_columns', function($columns) {
            unset($columns['date']);
            return $columns;
        });
    }

    public function initListView() {
        // List-View
        add_filter( 'manage_' . $this->getPostType() . '_posts_columns', array($this, 'setCustomColumns'));
        add_action( 'manage_' . $this->getPostType() . '_posts_custom_column' , array($this, 'setCustomColumnsData'), 10, 2 );
        add_filter( 'manage_edit-' . $this->getPostType() . '_sortable_columns', array($this, 'setSortableColumns'));
        add_action( 'pre_get_posts', function($query) {
            if( ! is_admin() )
                return;

            $orderby = $query->get( 'orderby');
            if( in_array($orderby, array_keys($this->listColumns))) {
                $query->set('meta_key',$orderby);
                $query->set('orderby','meta_value');
            }
        } );
    }

    /**
     * Adds data to custom columns
     * @param $column
     * @param $post_id
     */
    public function setCustomColumnsData($column, $post_id) {
        if($value = get_post_meta( $post_id , $column , true ))
            echo $value;
    }
}
