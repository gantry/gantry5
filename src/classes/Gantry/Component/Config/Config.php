<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
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

/**
 * The Config class contains configuration information.
 *
 * @author RocketTheme
 */
class Config implements \ArrayAccess, \Countable, \Iterator, ExportInterface
{
    use NestedArrayAccessWithGetters, Iterator, Export;

    /**
     * @var array
     */
    protected $items;

    /**
     * @var BlueprintSchema|BlueprintForm|callable
     */
    protected $blueprint;

    /**
     * Constructor to initialize array.
     *
     * @param  array  $items  Initial items inside the iterator.
     * @param  callable $blueprints  Function to load Blueprints for the configuration.
     */
    public function __construct(array $items, callable $blueprint = null)
    {
        $this->items = $items;
        $this->blueprint = $blueprint;
    }

    /**
     * Join nested values together by using blueprints.
     *
     * @param string  $name       Dot separated path to the requested value.
     * @param mixed   $value      Value to be joined.
     * @param string  $separator  Separator, defaults to '.'
     * @return $this
     * @throws \RuntimeException
     */
    public function join($name, $value, $separator = '.')
    {
        $old = $this->get($name, null, $separator);
        if ($old !== null) {
            if (!is_array($old)) {
                throw new \RuntimeException("Value is not array in {$name}: " . print_r($old, true));
            }
            if (is_object($value)) {
                $value = (array) $value;
            } elseif (!is_array($value)) {
                throw new \RuntimeException("Value is not array in {$name}: " . print_r($value, true));
            }
            $value = $this->blueprint()->mergeData($old, $value, $name, $separator);
        }

        $this->set($name, $value, $separator);

        return $this;
    }

    /**
     * Get nested structure containing default values defined in the blueprints.
     *
     * Fields without default value are ignored in the list.

     * @return array
     */
    public function getDefaults()
    {
        return $this->blueprint()->getDefaults();
    }

    /**
     * Set default values by using blueprints.
     *
     * @param string  $name       Dot separated path to the requested value.
     * @param mixed   $value      Value to be joined.
     * @param string  $separator  Separator, defaults to '.'
     * @return $this
     */
    public function joinDefaults($name, $value, $separator = '.')
    {
        if (is_object($value)) {
            $value = (array) $value;
        }
        $old = $this->get($name, null, $separator);
        if ($old !== null) {
            $value = $this->blueprint()->mergeData($value, $old, $name, $separator);
        }

        $this->set($name, $value, $separator);

        return $this;
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
            throw new \RuntimeException("Value is not array in '{$name}': " . print_r($value, true));
        }

        $old = $this->get($name, null, $separator);

        if ($old === null) {
            // No value set; no need to join data.
            return $value;
        }

        if (!is_array($old)) {
            throw new \RuntimeException("Value is not array in '{$name}': " . print_r($value, true));
        }

        // Return joined data.
        return $this->blueprint()->mergeData($old, $value, $name, $separator);
    }

    /**
     * Merge two configurations together.
     *
     * @param array $data
     * @return $this
     */
    public function merge(array $data)
    {
        $this->items = $this->blueprint()->mergeData($this->items, $data);

        return $this;
    }

    /**
     * Set default values to the configuration if variables were not set.
     *
     * @param array $data
     * @return $this
     */
    public function setDefaults(array $data)
    {
        $this->items = $this->blueprint()->mergeData($data, $this->items);

        return $this;
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
     * @param string $name
     * @param array  $element
     * @param string $separator
     * @param string $prefix
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
     * Return blueprint.
     *
     * @return BlueprintSchema|BlueprintForm
     * @since 5.4.7
     */
    public function blueprint()
    {
        if (!$this->blueprint){
            $this->blueprint = new BlueprintSchema;
        } elseif (is_callable($this->blueprint)) {
            // Lazy load blueprints.
            $blueprint = $this->blueprint;
            $this->blueprint = $blueprint();
        }
        return $this->blueprint;
    }

    /**
     * Return blueprints.
     *
     * @return BlueprintSchema
     * @deprecated 5.4.7
     */
    public function blueprints()
    {
        return $this->blueprint();
    }

    /**
     * Count items in nested array.
     *
     * @param string $path
     * @param string $separator
     * @return int
     */
    public function count($path = null, $separator = '.')
    {
        $items = $path ? $this->get($path, null, $separator) : $this->items;

        return is_array($items) ? count($items) : 0;
    }
}
