<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla\Object;

use \Gantry\Component\Collection\Collection as BaseCollection;

/**
 * Class Collection
 * @package Gantry\Joomla\Object
 */
class Collection extends BaseCollection
{
    /**
     * Collection constructor.
     * @param array $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    /**
     * @param string $property
     * @return array
     */
    public function get($property)
    {
        $list = [];

        if ($property === 'id') {
            return array_keys($this->items);
        }

        foreach ($this as $object) {
            $list[$object->id] = $object->{$property};
        }

        return $list;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return array
     */
    public function __call($name, $arguments)
    {
        $list = [];

        foreach ($this as $object) {
            $list[$object->id] = method_exists($object, $name) ? \call_user_func_array([$object, $name], $arguments) : null;
        }

        return $list;
    }

    public function exportSql()
    {
        $objects = [];
        foreach ($this as $object) {
            // Initialize table object.
            $objects[] = trim($object->exportSql());
        }

        $out = '';
        if ($objects) {
            $out .= implode("\n", $objects);
        }

        return $out;
    }
}
