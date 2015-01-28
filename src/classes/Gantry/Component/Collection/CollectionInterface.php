<?php
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
     * @return int|void
     */
    public function count();
}
