<?php

namespace CommonsBooking\Messages;

Class AdminMessage 
{
    
    /**
     * __construct
     *
     * @param  mixed $message message text
     * @param  mixed $notice_type admin_notice type (can be: info, warning, success, error)
     * @return void
     */
    public function __construct( $message, $notice_type = 'info') 
    {
        $this->message = $message;
        $this->notice_type = $notice_type;

        add_action( 'admin_notices', array ($this, 'render') );
    }

    
    /**
     * renders an admin message
     *
     * @return void
     */
    public function render() {

        echo '<div class="notice notice-'. $this->notice_type . ' is-dismissible">';
        echo '<p>';
        echo $this->message;
        echo '</p>';
        echo '</div>';
    }

}