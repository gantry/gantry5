<?php
namespace Gantry\Component\Layout;

use Gantry\Component\Collection\Collection;
use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Framework\Gantry;
use RocketTheme\Toolbox\DI\Container;
use RocketTheme\Toolbox\File\JsonFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class LayoutCollection extends Collection
{
    /**
     * @var Container
     */
    public $container;

    public function __construct(Container $container, $items = [])
    {
        $this->container = $container;
        $this->items = $items;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function load($path = 'gantry-layouts://')
    {
        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        $finder = new ConfigFileFinder();
        $files = $finder->getFiles($locator->findResources($path));

        if (!isset($files['default'])) {
            throw new \RuntimeException('Fatal error: Theme does not have default layout');
        }
        unset($files['default']);

        $layouts = array_keys($files);
        sort($layouts);
        array_unshift($layouts, 'default');

        $this->items = $layouts;

        return $this;
    }

    public function presets()
    {
        $this->items = array_values(array_filter($this->items, function($val) { return strpos($val, 'presets/') === 0; }));

        return $this;
    }

    public function all()
    {
        $this->items = array_values(array_filter($this->items, function($val) { return strpos($val, 'presets/') !== 0; }));

        return $this;
    }

    public function system()
    {
        $this->items = array_values(array_filter($this->items, function($val) { return strpos($val, 'presets/') !== 0 && substr($val, 0, 1) === '_'; }));

        return $this;
    }

    public function user()
    {
        $this->items = array_values(array_filter($this->items, function($val) { return strpos($val, 'presets/') !== 0 && substr($val, 0, 1) !== '_'; }));

        return $this;
    }
}
