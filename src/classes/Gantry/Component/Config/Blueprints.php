<?php
namespace Gantry\Component\Config;

use RocketTheme\Toolbox\ArrayTraits\Constructor;
use RocketTheme\Toolbox\ArrayTraits\Export;
use RocketTheme\Toolbox\ArrayTraits\NestedArrayAccess;

/**
 * The Config class contains configuration information.
 *
 * @author RocketTheme
 */
class Blueprints implements \ArrayAccess
{
    use Constructor, NestedArrayAccess, Export;

    /**
     * @var array
     */
    protected $items;

    /**
     * Get blueprints by using dot notation for nested arrays/objects.
     *
     * @example $value = $this->resolve('this.is.my.nested.variable');
     * returns ['this.is.my', 'nested.variable']
     *
     * @param string  $path
     * @return mixed  Value.
     */
    public function resolve(array $path, $separator = '.')
    {
        $fields = false;
        $parts = [];
        $current = $this['form.fields'];

        while (($field = current($path)) !== null) {
            if (!$fields && isset($current['fields'])) {
                if (!empty($current['array'])) {
                    break;
                }

                $current = $current['fields'];
                $fields = true;

            } elseif (isset($current[$field])) {
                $parts[] = array_shift($path);
                $current = $current[$field];
                $fields = false;

            } else {
                return [null, null];
            }
        }

        return [$current, $parts, $path ? implode($separator, $path) : null];
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
