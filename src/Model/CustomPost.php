<?php


namespace CommonsBooking\Model;


class CustomPost
{

    protected $post;

    /**
     * CustomPost constructor.
     *
     * @param int|WP_Post $post
     */
    public function __construct($post)
    {
        if($post instanceof \WP_Post) {
            $this->post = $post;
        } elseif (is_int($post)) {
            $this->post = get_post($post);
        } else {
            throw new \Exception("invalid post param. needed WP_Post or ID (int)");
        }
    }

    /**
     * Returns meta-field value.
     * @param $field
     *
     * @return mixed
     */
    public function get_meta($field) {
        return get_post_meta($this->post->ID, $field, true);
    }

    public function __get($name)
    {
        if(property_exists($this->post, $name)) {
            return $this->post->$name;
        }
    }

    public function __call($name, $arguments)
    {
        if(method_exists($this->post, $name)) {
            $reflectionMethod = new \ReflectionMethod($this->post, $name);
            return $reflectionMethod->invokeArgs($this->post, $arguments);
        }
        if(property_exists($this->post, $name)) {
            return $this->post->$name;
        }
    }

    /**
     * Return Excerpt 
     *
     * @return html
     */
    public function excerpt()
    {
        $excerpt = '';
        if (has_excerpt($this->ID)) {
            $excerpt .= wp_strip_all_tags( get_the_excerpt( $this->ID ) );
        }
        return $excerpt;
    }

    /**
     * Return Title with permalink
     *
     * @return html
     */
    public function titleLink()
    {
        return sprintf('<a href="%s" class="cb-title cb-title-link">%s</a>', get_the_permalink($this->ID), $this->post_title );
    }

    /**
     * Return Thumbnail 
     *
     * @return html
     */
    public function thumbnail( $size='thumbnail' )
    {
        if ( has_post_thumbnail($this->ID) ) {
            return '<div class="cb-thumbnail">' . get_the_post_thumbnail($this->ID, $size , array( 'class' => 'alignleft cb-image' ) ) . '</div>';
        }
       return '';
    }
}
