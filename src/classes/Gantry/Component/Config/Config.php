<?php
namespace Gantry\Component\Config;

use RocketTheme\Toolbox\ArrayTraits\Export;
use RocketTheme\Toolbox\ArrayTraits\NestedArrayAccess;
use RocketTheme\Toolbox\Blueprints\Blueprints;

/**
 * The Config class contains configuration information.
 *
 * @author RocketTheme
 */
class Config
{
    use NestedArrayAccess, Export;

    /**
     * @var array
     */
    protected $items;

    /**
     * @var Blueprints|callable
     */
    protected $blueprints;

    /**
     * Constructor to initialize array.
     *
     * @param  array  $items  Initial items inside the iterator.
     * @param  callable $blueprints  Function to load Blueprints for the configuration.
     */
    public function __construct(array $items, callable $blueprints = null)
    {
        $this->items = $items;
        $this->blueprints = $blueprints;
    }

    /**
     * Join nested values together by using blueprints.
     *
     * @param string  $name       Dot separated path to the requested value.
     * @param mixed   $value      Value to be joined.
     * @param string  $separator  Separator, defaults to '.'
     */
    public function join($name, $value, $separator = '.')
    {
        $old = $this->get($name, null, $separator);
        if ($old !== null) {
            $value = $this->blueprints()->mergeData($old, $value, $name, $separator);
        }

        $this->set($name, $value, $separator);
    }

    /**
     * Set default values by using blueprints.
     *
     * @param string  $name       Dot separated path to the requested value.
     * @param mixed   $value      Value to be joined.
     * @param string  $separator  Separator, defaults to '.'
     */
    public function joinDefaults($name, $value, $separator = '.')
    {
        $old = $this->get($name, null, $separator);
        if ($old !== null) {
            $value = $this->blueprints()->mergeData($value, $old, $name, $separator);
        }

        $this->set($name, $value, $separator);
    }


    /**
     * Merge two configurations together.
     *
     * @param array $data
     * @return void
     */
    public function merge(array $data)
    {
        $this->items = $this->blueprints()->mergeData($this->items, $data);
    }

    /**
     * Set default values to the configuration if variables were not set.
     *
     * @param array $data
     * @return void
     */
    public function setDefaults(array $data)
    {
        $this->items = $this->blueprints()->mergeData($data, $this->items);
    }

    /**
     * Return blueprints.
     *
     * @return Blueprints
     */
    public function blueprints()
    {
        if (is_callable($this->blueprints)) {
            // Lazy load blueprints.
            $blueprints = $this->blueprints;
            $this->blueprints = $blueprints();
        }
        return $this->blueprints;
    }

    // Implement getters for Twig templates.

    /**
     * Magic setter method
     *
     * @param mixed $offset Asset name value
     * @param mixed $value  Asset value
     */
    public function __set($offset, $value)
    {
        $this->offsetSet($offset, $value);
    }

    /**
     * Magic getter method
     *
     * @param  mixed $offset Asset name value
     * @return mixed         Asset value
     */
    public function __get($offset)
    {
        return $this->offsetGet($offset);
    }

    /**
     * Magic method to determine if the attribute is set
     *
     * @param  mixed   $offset Asset name value
     * @return boolean         True if the value is set
     */
    public function __isset($offset)
    {
        return $this->offsetExists($offset);
    }

    /**
     * Magic method to unset the attribute
     *
     * @param mixed $offset The name value to unset
     */
    public function __unset($offset)
    {
        $this->offsetUnset($offset);
    }
}
