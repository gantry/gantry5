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

namespace Gantry\Component\Collection;

use RocketTheme\Toolbox\ArrayTraits\ArrayAccess;
use RocketTheme\Toolbox\ArrayTraits\Countable;
use RocketTheme\Toolbox\ArrayTraits\Export;

class Collection implements CollectionInterface
{
    use ArrayAccess, Countable, Export;

    /**
     * @var array
     */
    protected $items = [];

    public static function __set_state($variables)
    {
        $instance = new static();
        $instance->items = $variables['items'];
        return $instance;
    }

    /**
     *
     * Create a copy of this collection.
     *
     * @return static
     */
    public function copy()
    {
        return clone $this;
    }

    /**
     * @param $item
     * @return $this
     */
    public function add($item)
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }
}
