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
     * returns title
     * @return string
     */
    public function name()
    {
        return $this->post->post_title;
    }
}
