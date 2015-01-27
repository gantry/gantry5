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

    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    /**
     * @param string $path
     * @return $this
     */
    public function load($path = 'gantry-layouts://')
    {
        /** @var UniformResourceLocator $locator */
        $locator =$this->container['locator'];

        $finder = new ConfigFileFinder();
        $files = $finder->getFiles($locator->findResources($path, false), '|\.json$|');
        $files += $finder->getFiles($locator->findResources($path, false));
        $layouts = array_keys($files);

        $layouts = array_filter($layouts, function($val) { return strpos($val, 'presets/') !== 0 && substr($val, 0, 1) !== '_'; });
        sort($layouts);

        $this->items = $layouts;

        return $this;
    }
}
