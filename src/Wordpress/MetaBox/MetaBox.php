<?php


namespace CommonsBooking\Wordpress\MetaBox;


/**
 * Class MetaBox
 *
 * add_meta_box("year_completed-meta", "Year Completed", array($this, 'year_completed'), self::TYPE);
 *
 * @package CommonsBooking\Wordpress
 */
class MetaBox
{

    protected $id;

    protected $title;

    protected $callback;

    protected $screen;

    protected $context;

    protected $priority;

    protected $callbackArgs;

    /**
     * MetaBox constructor.
     *
     * @param $id
     * @param $title
     * @param $callback
     * @param $screen
     * @param string $context
     * @param string $priority
     * @param $callbackArgs
     */
    public function __construct($id, $title, $callback, $screen = null, string $context = 'advanced', string $priority = 'default', $callbackArgs = null)
    {
        $this->id = $id;
        $this->title = $title;
        $this->callback = $callback;
        $this->screen = $screen;
        $this->context = $context;
        $this->priority = $priority;
        $this->callbackArgs = $callbackArgs;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return MetaBox
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     *
     * @return MetaBox
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @param mixed $callback
     *
     * @return MetaBox
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getScreen()
    {
        return $this->screen;
    }

    /**
     * @param mixed $screen
     *
     * @return MetaBox
     */
    public function setScreen($screen)
    {
        $this->screen = $screen;

        return $this;
    }

    /**
     * @return string
     */
    public function getContext(): string
    {
        return $this->context;
    }

    /**
     * @param string $context
     *
     * @return MetaBox
     */
    public function setContext(string $context): MetaBox
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @return string
     */
    public function getPriority(): string
    {
        return $this->priority;
    }

    /**
     * @param string $priority
     *
     * @return MetaBox
     */
    public function setPriority(string $priority): MetaBox
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCallbackArgs()
    {
//        $this->callbackArgs[] = $this->getId();
        return $this->callbackArgs;
    }

    /**
     * @param mixed $callbackArgs
     *
     * @return MetaBox
     */
    public function setCallbackArgs($callbackArgs)
    {
        $this->callbackArgs = $callbackArgs;

        return $this;
    }

}
