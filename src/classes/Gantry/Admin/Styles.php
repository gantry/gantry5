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

namespace Gantry\Admin;

use Gantry\Component\Config\BlueprintForm;
use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Framework\Theme as SiteTheme;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Styles
{
    protected $container;
    protected $files;
    protected $blocks;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function all()
    {
        if (!$this->blocks)
        {
            $files = $this->locateBlocks();

            $this->blocks = [];
            foreach ($files as $key => $fileArray) {
                $filename = key($fileArray);
                $file = CompiledYamlFile::instance(GANTRY5_ROOT . '/' . $filename);
                $this->blocks[$key] = $file->content();
                $file->free();
            }
        }

        return $this->blocks;
    }

    public function group()
    {
        $blocks = $this->all();

        $list = [];
        foreach ($blocks as $name => $style) {
            $type = isset($style['type']) ? $style['type'] : 'block';
            $list[$type][$name] = $style;
        }

        return $this->sort($list);
    }

    public function get($id)
    {
        if ($this->blocks[$id]) {
            return $this->blocks[$id];
        }

        $files = $this->locateBlocks();

        if (empty($files[$id])) {
            throw new \RuntimeException(sprintf("Settings for '%s' not found.", $id), 404);
        }

        $filename = key($files[$id]);
        $file = CompiledYamlFile::instance(GANTRY5_ROOT . '/' . $filename);
        $particle = $file->content();
        $file->free();

        return $particle;
    }

    /**
     * @param string $id
     * @return BlueprintForm
     */
    public function getBlueprintForm($id)
    {
        return BlueprintForm::instance($id, 'gantry-blueprints://styles');
    }

    protected function sort(array $blocks)
    {
        $list = [];

        /** @var SiteTheme $theme */
        $theme = $this->container['theme'];
        $ordering = (array) $theme->details()['admin.styles'];

        ksort($blocks);

        foreach ($ordering as $name => $order) {
            if (isset($blocks[$name])) {
                $list[$name] = $this->sortItems($blocks[$name], (array) $order);
            }
        }
        $list += $blocks;

        return $list;
    }

    protected function sortItems(array $items, array $ordering)
    {
        $list = [];

        ksort($items);

        foreach ($ordering as $name) {
            if (isset($items[$name])) {
                $list[$name] = $items[$name];
            }
        }
        $list += $items;

        return $list;
    }

    protected function locateBlocks()
    {
        if (!$this->files) {
            /** @var UniformResourceLocator $locator */
            $locator = $this->container['locator'];
            $paths = $locator->findResources('gantry-blueprints://styles');

            $this->files = (new ConfigFileFinder)->listFiles($paths);
        }

        return $this->files;
    }
}
