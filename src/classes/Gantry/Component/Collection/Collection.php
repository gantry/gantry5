<?php
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
    protected $items = array();

    public static function __set_state($variables)
    {
        $instance = new static();
        $instance->items = $variables['items'];
        return $instance;
    }

    /**
     * @param $item
     */
    public function add($item)
    {
        $this->items[] = $item;
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }
}
