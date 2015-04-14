<?php
namespace Gantry\Prime;

use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Gantry;
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
        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];
        $finder = new ConfigFileFinder;

        // Generate a flat list of all existing pages containing a list of file paths with timestamps.
        $this->items = $finder->listFiles($locator->findResources('gantry-pages://'), '|\.html\.twig|');

        // And list the pages in alphabetical order.
        ksort($this->items);
    }
}
