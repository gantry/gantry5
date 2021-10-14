<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Collection;

/**
 * Interface CollectionInterface
 * @package Gantry\Component\Collection
 */
interface CollectionInterface extends \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * @return array
     */
    public function toArray();

    /**
     * @param mixed $item
     */
    public function add($item);

    /**
     * @return \ArrayIterator
     */
    public function getIterator();

    /**
     * @param string|int $offset
     * @return bool
     */
    public function offsetExists($offset);

    /**
     * @param string|int $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value);

    /**
     * @param string|int $offset
     * @return mixed
     */
    public function offsetGet($offset);

    /**
     * @param string|int $offset
     */
    public function offsetUnset($offset);

    /**
     * @return int
     */
    public function count();
}
