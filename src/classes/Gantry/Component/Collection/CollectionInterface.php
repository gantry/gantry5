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

interface CollectionInterface extends \IteratorAggregate, \ArrayAccess, \Countable
{
    public function toArray();

    /**
     * @param $item
     */
    public function add($item);

    /**
     * @return \ArrayIterator
     */
    public function getIterator();

    /**
     * @param $offset
     *
     * @return bool
     */
    public function offsetExists($offset);

    /**
     * @param $offset
     * @param $value
     */
    public function offsetSet($offset, $value);

    /**
     * @param $offset
     *
     * @return mixed
     */
    public function offsetGet($offset);

    /**
     * @param $offset
     */
    public function offsetUnset($offset);

    /**
     * @return int
     */
    public function count();
}
