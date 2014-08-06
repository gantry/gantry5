<?php
namespace Gantry\Component\Getters;

/**
 * Abstract class to implement magic __get(), __set(), __isset() and __unset().
 * Also implements ArrayAccess.
 *
 * @author RocketTheme
 * @license MIT
 */
abstract class Getters implements \ArrayAccess, \Countable
{
    /**
     * Structure used by getters.
     *
     * @var array
     */
	protected $items = array();

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

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
         return isset($this->items[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return isset($this->items[$offset]) ? $this->items[$offset] : null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->items[$offset] = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    /**
     * @return int
     */
    public function count()
    {
        count($this->items);
    }

    /**
     * Returns an associative array of object properties.
     *
     * @return array
     */
    public function toArray()
    {
        return (array) $this->items;
    }
}
