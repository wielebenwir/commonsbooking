<?php

namespace CommonsBooking\Wordpress\PostStatus;

class PostStatus
{

    protected $name;

    protected $label;

    protected $public;

    /**
     * PostStatus constructor.
     * @param $name
     * @param $label
     * @param bool $public
     */
    public function __construct($name, $label, bool $public = true)
    {
        $this->name = $name;
        $this->label = $label;
        $this->public = $public;

        $this->registerPostStatus();
        $this->addActions();
    }


    public function registerPostStatus()
    {
        register_post_status($this->name, array(
            'label' => $this->label,
            'public' => $this->public,
            'label_count'               => _n_noop(
                $this->label . ' <span class="count">(%s)</span>',
                $this->label . ' <span class="count">(%s)</span>'
            )
        ));
    }

    public function addActions()
    {
        add_action('admin_footer-edit.php', array($this, 'addQuickedit'));
        add_action('admin_footer', array($this, 'addOption'));
    }

    public function addOption() {
        global $post;

        $active = "";
        if($post->post_status == $this->name) {
            $active = "jQuery( '#post-status-display' ).text( '".$this->label."' )";
        }

        echo "<script>
            jQuery(document).ready( function() {
                jQuery( 'select[name=\"post_status\"]' ).append( '<option value=\"".$this->name."\">".$this->label."</option>' );
                ".$active."
                jQuery( 'select[name=\"post_status\"]' ).val('".$this->name."');
            });
        </script>";

    }

    public function addQuickedit()
    {
        echo "<script>
                jQuery(document).ready( function() {
                    jQuery( 'select[name=\"_status\"]' ).append( '<option value=\"" . $this->name . "\">" . $this->label . "</option>' );
                });
            </script>";
    }

}
