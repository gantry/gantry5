<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
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
        foreach ($this as $object) {
            $list[] = $object->{$property};
        }
    }

    public function __call($name, $arguments)
    {
        $list = [];
        foreach ($this as $object) {
            if (method_exists($object, $name)) {
                $list[] = call_user_func_array([$object, $name], $arguments);
            }
        }
    }
}
