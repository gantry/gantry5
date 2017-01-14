<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla\Object;

use \Gantry\Component\Collection\Collection as BaseCollection;

class Collection extends BaseCollection
{
    public function __construct(array $items)
    {
        $this->items = $items;
    }

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

    public function __call($name, $arguments)
    {
        $list = [];

        foreach ($this as $object) {
            $list[$object->id] = method_exists($object, $name) ? call_user_func_array([$object, $name], $arguments) : null;
        }

        return $list;
    }
}
