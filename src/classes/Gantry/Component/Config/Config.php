<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Config;

use RocketTheme\Toolbox\ArrayTraits\Export;
use RocketTheme\Toolbox\ArrayTraits\ExportInterface;
use RocketTheme\Toolbox\ArrayTraits\Iterator;
use RocketTheme\Toolbox\ArrayTraits\NestedArrayAccessWithGetters;
use RocketTheme\Toolbox\Blueprints\Blueprints;

/**
 * The Config class contains configuration information.
 *
 * @author RocketTheme
 */
class Config implements \ArrayAccess, \Iterator, ExportInterface
{
    use NestedArrayAccessWithGetters, Iterator, Export;

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
            if (!is_array($old)) {
                throw new \RuntimeException('Value ' . $old);
            }
            if (is_object($value)) {
                $value = (array) $value;
            } elseif (!is_array($value)) {
                throw new \RuntimeException('Value ' . $value);
            }
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
        if (is_object($value)) {
            $value = (array) $value;
        }
        $old = $this->get($name, null, $separator);
        if ($old !== null) {
            $value = $this->blueprints()->mergeData($value, $old, $name, $separator);
        }

        $this->set($name, $value, $separator);
    }

    /**
     * Get value from the configuration and join it with given data.
     *
     * @param string  $name       Dot separated path to the requested value.
     * @param array   $value      Value to be joined.
     * @param string  $separator  Separator, defaults to '.'
     * @return array
     * @throws \RuntimeException
     */
    public function getJoined($name, $value, $separator = '.')
    {
        if (is_object($value)) {
            $value = (array) $value;
        } elseif (!is_array($value)) {
            throw new \RuntimeException('Value ' . $value);
        }

        $old = $this->get($name, null, $separator);

        if ($old === null) {
            // No value set; no need to join data.
            return $value;
        }

        if (!is_array($old)) {
            throw new \RuntimeException('Value ' . $old);
        }

        // Return joined data.
        return $this->blueprints()->mergeData($old, $value, $name, $separator);
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
     * Make a flat list from the configuration.
     *
     * @param string $name      Dot separated path to the requested value.
     * @param string $separator Separator, defaults to '.'
     * @param string $prefix    Name prefix.
     * @return array
     */
    public function flatten($name = null, $separator = '.', $prefix = '')
    {
        $element = $name ? $this->offsetGet($name) : $this->items;

        if (!is_array($element)) {
            return [$name, $element];
        }

        if (strlen($separator) == 2 && in_array($separator, ['][', ')(', '}{'])) {
            $separator = [$separator[1], $separator[0]];
        }

        return $this->flattenNested('', $element, $separator, $prefix);
    }

    /**
     * @param $name
     * @param $element
     * @param $separator
     * @return array
     * @internal
     */
    protected function flattenNested($name, &$element, $separator, $prefix)
    {
        $list = [];
        foreach ($element as $key => $value) {
            $new = $name ? $name : $prefix;
            if (is_array($separator)) {
                $new .= $separator[0] . $key . $separator[1];
            } else {
                $new .= ($new ? $separator : '') . $key;
            }
            if (!is_array($value) || empty($value)) {
                $list[$new] = $value;
            } else {
                $list += $this->flattenNested($new, $value, $separator, $prefix);
            }
        }

        return $list;
    }

    /**
     * Return blueprints.
     *
     * @return Blueprints
     */
    public function blueprints()
    {
        if (!$this->blueprints){
            $this->blueprints = new Blueprints;
        } elseif (is_callable($this->blueprints)) {
            // Lazy load blueprints.
            $blueprints = $this->blueprints;
            $this->blueprints = $blueprints();
        }
        return $this->blueprints;
    }
}
