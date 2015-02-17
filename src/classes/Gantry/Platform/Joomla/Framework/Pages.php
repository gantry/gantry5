<?php
namespace Gantry\Framework;

use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\Filesystem\Folder;
use RocketTheme\Toolbox\ArrayTraits\ArrayAccessWithGetters;
use RocketTheme\Toolbox\ArrayTraits\Export;
use RocketTheme\Toolbox\ArrayTraits\Iterator;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Pages implements \ArrayAccess, \Iterator
{
    use ArrayAccessWithGetters, Iterator, Export;

    /**
     * @var array
     */
    protected $items;

    public function __construct()
    {
        $this->items = [];
    }
}
