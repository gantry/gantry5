<?php
namespace Gantry\Admin\Styles;

use Gantry\Component\Config\Blueprints;
use Gantry\Component\Config\CompiledBlueprints;
use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Base\Gantry;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Styles
{
    protected $container;
    protected $files;
    protected $styles;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function all()
    {
        if (!$this->styles)
        {
            $files = $this->locateStyles();

            $this->styles = [];
            foreach ($files as $key => $file) {
                $filename = key($file);
                $this->styles[$key] = CompiledYamlFile::instance(GANTRY5_ROOT . '/' . $filename)->content();
            }
        }

        return $this->styles;
    }

    public function group()
    {
        $styles = $this->all();

        $list = [];
        foreach ($styles as $name => $style) {
            $type = isset($style['type']) ? $style['type'] : 'style';
            $list[$type][$name] = $style;
        }

        return $list;
    }

    public function get($id)
    {
        if ($this->styles[$id]) {
            return $this->styles[$id];
        }

        $files = $this->locateStyles();

        if (empty($files[$id])) {
            throw new \RuntimeException("Settings for '{$id}' not found.", 404);
        }

        $filename = key($files[$id]);
        $particle = CompiledYamlFile::instance(GANTRY5_ROOT . '/' . $filename)->content();

        return $particle;
    }

    protected function locateStyles()
    {
        if (!$this->files) {
            /** @var UniformResourceLocator $locator */
            $locator = $this->container['locator'];
            $paths = $locator->findResources('gantry-styles://');

            $this->files = (new ConfigFileFinder)->listFiles($paths);
        }

        return $this->files;
    }
}
